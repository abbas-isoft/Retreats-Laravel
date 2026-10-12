<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class InquiryMessageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('property_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
        });
        config(['services.hostaway.token' => 'test-token', 'mail.from.address' => 'website@example.com']);
        Http::preventStrayRequests();
    }

    private function payload(): array
    {
        return [
            'listing_id' => 452891, 'first_name' => 'Test', 'last_name' => 'Guest',
            'email' => 'guest@example.com', 'phone' => '+441234567890',
            'check_in' => '2026-11-19', 'check_out' => '2026-11-21',
            'guests' => 30, 'message' => 'Please confirm availability.',
        ];
    }

    private function fakeHostaway(): void
    {
        Http::fake([
            'api.hostaway.com/v1/reservations' => Http::response(['result' => ['id' => 123]], 200),
            'api.hostaway.com/v1/conversations*' => Http::response(['result' => [
                ['id' => 999, 'reservationId' => 999, 'type' => 'host-guest-email', 'hostEmail' => 'wrong@example.com'],
                ['id' => 456, 'reservationId' => 123, 'type' => 'host-guest-email',
                    'hostEmail' => 'host-relay@example.com', 'guestEmail' => 'guest-relay@example.com'],
            ]], 200),
        ]);
    }

    public function test_message_uses_host_relay_and_guest_reply_address(): void
    {
        $this->fakeHostaway();
        Mail::shouldReceive('raw')->once()->withArgs(function ($body, $callback) {
            $email = new Email();
            $callback(new Message($email));
            $this->assertSame('Please confirm availability.', $body);
            $this->assertSame('host-relay@example.com', $email->getTo()[0]->getAddress());
            $this->assertSame('guest@example.com', $email->getReplyTo()[0]->getAddress());
            $this->assertSame('website@example.com', $email->getFrom()[0]->getAddress());
            return true;
        });

        $this->postJson('/api/send-inquiry', $this->payload())->assertOk()
            ->assertJsonPath('message_status', 'forwarded')->assertJsonPath('conversation_id', 456);
        Http::assertSentCount(2);
    }

    public function test_no_message_keeps_existing_creation_flow(): void
    {
        $this->fakeHostaway();
        Mail::shouldReceive('raw')->never();
        $this->postJson('/api/send-inquiry', array_replace($this->payload(), ['message' => '']))
            ->assertOk()->assertJsonPath('status', true)->assertJsonPath('data.id', 123);
        Http::assertSentCount(1);
    }

    public function test_mail_failure_does_not_report_inquiry_creation_as_failed(): void
    {
        $this->fakeHostaway();
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->postJson('/api/send-inquiry', $this->payload())->assertOk()
            ->assertJsonPath('status', true)->assertJsonPath('message_status', 'not_sent')
            ->assertJsonPath('data.id', 123);
        Http::assertSentCount(2);
    }
}
