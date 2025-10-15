@component('mail::message')
{{-- Header Section --}}
<div style="background-color:#b9975b; padding:25px 0; text-align:center;">
<img src="{{ url('images/logo.png') }}" alt="Logo" style="max-width:80px; border-radius:8px;">
</div>

{{-- Title --}}
<h1 style="text-align:center; margin-top:20px; color:#333;">Booking Invoice</h1>
<p style="text-align:center; color:#666;">Receipt #{{ strtoupper(substr($booking->booking_reference ?? 'N/A', 0, 8)) }}</p>

{{-- Summary Info --}}
<table width="100%" cellpadding="8" cellspacing="0" style="margin:25px 0; font-size:14px; color:#333;">
    <tr>
        <td><strong>Amount Paid</strong></td>
        <td style="text-align:right;">£{{ number_format($booking->upfront_cents / 100, 2) }}</td>
    </tr>
    <tr>
        <td><strong>Date Paid</strong></td>
        <td style="text-align:right;">{{ $booking->created_at ? \Carbon\Carbon::parse($booking->created_at)->format('M d, Y, h:i A') : \Carbon\Carbon::now()->format('M d, Y, h:i A') }}</td>
    </tr>
    <tr>
        <td><strong>Payment Method</strong></td>
        <td style="text-align:right;">{{ strtoupper($booking->card_brand ?? 'VISA') }} •••• {{ $booking->card_last4 ?? '4242' }}</td>
    </tr>
</table>

<hr style="border:0; border-top:1px solid #ddd; margin:20px 0;">

{{-- Payment Summary Section --}}
<h2 style="font-size:18px; color:#222; margin-top:30px;">Payment Summary</h2>

@php
    // Dynamically fetch Security Deposit for this property
    $securityDeposit = \App\Models\PriceConfiguration::where('property_id', $booking->property_id)->value('security_deposit') ?? 0;
@endphp

<table width="100%" cellpadding="8" cellspacing="0" style="margin:15px 0 25px; font-size:14px; color:#333; border-collapse:collapse;">
    <tr style="border-bottom:1px solid #eee;">
        <td><strong>Amount Paid</strong></td>
        <td style="text-align:right;">£{{ number_format(($booking->upfront_cents ?? 0) / 100, 2) }}</td>
    </tr>

    <tr style="border-bottom:1px solid #eee;">
        <td><strong>Date Paid</strong></td>
        <td style="text-align:right;">
            {{ $booking->created_at 
                ? \Carbon\Carbon::parse($booking->created_at)->format('M d, Y, h:i A') 
                : \Carbon\Carbon::now()->format('M d, Y, h:i A') }}
        </td>
    </tr>

    <tr style="border-bottom:1px solid #eee;">
        <td><strong>Payment Method</strong></td>
        <td style="text-align:right;">
            {{ strtoupper($booking->card_brand ?? 'VISA') }} •••• {{ $booking->card_last4 ?? '4242' }}
        </td>
    </tr>

    <tr style="border-bottom:1px solid #eee;">
        <td>
            Remaining Balance (due on 
            {{ $booking->remaining_payment_scheduled_at 
                ? \Carbon\Carbon::parse($booking->remaining_payment_scheduled_at)->format('D, d M Y') 
                : 'N/A' }})
        </td>
        <td style="text-align:right;">
            £{{ number_format(($booking->remaining_cents ?? 0) / 100, 2) }}
            <span style="color:#b9975b;">
                {{ $booking->payment_status == 'pending' ? '⏳ Pending' : '✅ Paid' }}
            </span>
        </td>
    </tr>

    <tr style="border-bottom:1px solid #eee;">
        <td>Damage Deposit (to be held later)</td>
        <td style="text-align:right;">£{{ number_format(($booking->damage_deposit_cents ?? 0) / 100, 2) }}</td>
    </tr>

    <tr style="border-bottom:1px solid #eee;">
        <td>Security Deposit (to be held later)</td>
        <td style="text-align:right;">£{{ number_format($securityDeposit, 2) }}</td>
    </tr>

    <tr>
        <td><strong>Total Amount Due</strong></td>
        <td style="text-align:right;">
            <strong>
                £{{ number_format(
                    (
                        (($booking->upfront_cents ?? 0) + 
                         ($booking->remaining_cents ?? 0) + 
                         ($booking->damage_deposit_cents ?? 0))
                        / 100
                    , 2) 
                ) }}
            </strong>
        </td>
    </tr>
</table>

@if($booking->receipt_url)
    <p style="text-align:center; margin-top:25px;">
        <a href="{{ $booking->receipt_url }}" 
           style="display:inline-block; background:#007bff; color:#fff; text-decoration:none; padding:10px 20px; border-radius:5px; font-weight:bold;">
            🔗 View Payment Receipt
        </a>
    </p>
@endif

<hr style="margin:40px 0; border:0; border-top:1px solid #ddd;">

{{-- Footer --}}
<p style="text-align:center; color:#555;">
    If you have any questions, contact us at  
    <a href="mailto:{{ config('mail.from.address') }}" style="color:#b9975b; text-decoration:none;">
        {{ config('mail.from.address') }}
    </a>
</p>

<p style="text-align:center; color:#999; font-size:12px; margin-top:20px;">
    You're receiving this email because you made a booking at {{ config('app.name') }},  
    which partners with Stripe to provide invoicing and secure payment processing.
</p>

<p style="text-align:center; font-size:12px; color:#999;">
    Something wrong with the email? 
    <a href="{{ $booking->receipt_url ?? '#' }}" style="color:#b9975b;">View it in your browser</a>
</p>
@endcomponent
