@component('mail::message')
{{-- Header Section --}}
<div style="background-color:#b9975b; padding:25px 0; text-align:center;">
    <img src="{{ asset('images/logo.png') }}" alt="Logo" style="max-width:80px; border-radius:8px;">
</div>

{{-- Title --}}
<h1 style="text-align:center; margin-top:20px; color:#333;">Invoice</h1>
<p style="text-align:center; color:#666;">Receipt #{{ strtoupper(substr($booking->booking_reference ?? 'N/A', 0, 8)) }}</p>

{{-- Summary Info --}}
<table width="100%" cellpadding="6" cellspacing="0" style="margin:25px 0; font-size:14px;">
    <tr>
        <td><strong>Amount Paid</strong></td>
        <td style="text-align:right;">£{{ number_format(($booking->upfront_cents ?? 0) + ($booking->remaining_cents ?? 0), 2) }}</td>
    </tr>
    <tr>
        <td><strong>Date Paid</strong></td>
        <td style="text-align:right;">{{ \Carbon\Carbon::now()->format('M d, Y, h:i A') }}</td>
    </tr>
    <tr>
        <td><strong>Payment Method</strong></td>
        <td style="text-align:right;">VISA •••• {{ $booking->card_last4 ?? '4242' }}</td>
    </tr>
</table>

<hr>

{{-- Booking Summary --}}
<h2 style="margin-top:25px; color:#333;">Summary</h2>
<table width="100%" cellpadding="6" cellspacing="0" style="font-size:14px; border:1px solid #eee; border-radius:8px;">
    <tr style="background-color:#f9f9f9;">
        <td>Full payment for Booking {{ strtoupper($booking->booking_reference ?? 'N/A') }}</td>
        <td style="text-align:right;">£{{ number_format(($booking->upfront_cents ?? 0) + ($booking->remaining_cents ?? 0), 2) }}</td>
    </tr>
    <tr>
        <td><strong>Amount Paid</strong></td>
        <td style="text-align:right;"><strong>£{{ number_format(($booking->upfront_cents ?? 0) + ($booking->remaining_cents ?? 0), 2) }}</strong></td>
    </tr>
</table>

@if($booking->receipt_url)
    <div style="text-align:center; margin-top:30px;">
        <a href="{{ $booking->receipt_url }}" 
           style="display:inline-block; background-color:#b9975b; color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none;">
            🧾 View Receipt
        </a>
    </div>
@endif

<hr style="margin:40px 0;">

{{-- Footer --}}
<p style="text-align:center; color:#555;">
    If you have any questions, contact us at  
    <a href="mailto:{{ config('mail.from.address') }}" style="color:#b9975b; text-decoration:none;">
        {{ config('mail.from.address') }}
    </a>
</p>

<p style="text-align:center; color:#999; font-size:12px; margin-top:20px;">
    You're receiving this email because you made a purchase at {{ config('app.name') }},  
    which partners with Stripe to provide invoicing and secure payment processing.
</p>

<p style="text-align:center; font-size:12px; color:#999;">
    Something wrong with the email? <a href="{{ $booking->receipt_url ?? '#' }}" style="color:#b9975b;">View it in your browser</a>
</p>

@endcomponent
