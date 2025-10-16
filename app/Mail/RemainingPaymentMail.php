<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\TestBooking;

class RemainingPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    public function __construct(TestBooking $booking)
    {
        $this->booking = $booking;
    }

    public function build()
    {
        return $this->subject('Booking Confirmed')
                    ->markdown('emails.booking.confirmed');
    }
}
