@component('mail::message')
{{-- Header Section --}}
<div style="background-color:#b9975b; padding:25px 0; text-align:center;">
    <img src="{{ asset('images/logo.png') }}" alt="Logo" style="max-width:80px; border-radius:8px;">
</div>

{{-- Title --}}
<h1 style="text-align:center; margin-top:20px; color:#333;">Booking Invoice</h1>
<p style="text-align:center; color:#666;">Receipt #{{ strtoupper(substr($booking->booking_reference ?? 'N/A', 0, 8)) }}</p>

{{-- Summary Info --}}
<table width="100%" cellpadding="6" cellspacing="0" style="margin:25px 0; font-size:14px;">
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

<hr>

{{-- Booking Breakdown --}}
<h2 style="margin-top:25px; color:#333;">Payment Summary</h2>

<table width="100%" cellpadding="8" cellspacing="0" 
       style="margin-top:10px; font-size:14px; border-collapse:collapse; width:100%; border:1px solid #eee; border-radius:8px; overflow:hidden;">
    <tr style="background-color:#f9f9f9;">
        <td style="padding:10px; font-weight:600; color:#333;">Full Booking Amount</td>
        <td style="text-align:right; padding:10px; font-weight:600; color:#333;">
            £{{ number_format($booking->amount_cents / 100, 2) }}
        </td>
    </tr>
    <tr>
        <td style="padding:10px;">Initial Payment (25%)</td>
        <td style="text-align:right; padding:10px;">£{{ number_format($booking->upfront_cents / 100, 2) }} — ✅ Paid</td>
    </tr>

    @if(!empty($booking->remaining_cents) && $booking->remaining_cents > 0)
        <tr style="background-color:#f9f9f9;">
            <td style="padding:10px;">
                Remaining Balance (due on 
                {{ $booking->remaining_payment_scheduled_at ? \Carbon\Carbon::parse($booking->remaining_payment_scheduled_at)->format('D, d M Y') : 'N/A' }})
            </td>
            <td style="text-align:right; padding:10px;">
                £{{ number_format($booking->remaining_cents / 100, 2) }} — ⏳ Pending
            </td>
        </tr>
    @else
        <tr style="background-color:#f9f9f9;">
            <td style="padding:10px;">Remaining Balance</td>
            <td style="text-align:right; padding:10px;">£0.00 — ✅ Fully Paid</td>
        </tr>
    @endif

    <tr>
        <td style="padding:10px;">Damage Deposit (to be held later)</td>
        <td style="text-align:right; padding:10px;">£{{ number_format($booking->damage_deposit_cents / 100, 2) }}</td>
    </tr>

    <tr style="background-color:#f9f9f9; border-top:1px solid #eee;">
        <td style="padding:12px; font-weight:600; color:#333;">Total Amount Due</td>
        <td style="text-align:right; padding:12px; font-weight:600; color:#333;">
            £{{ number_format($booking->amount_cents / 100, 2) }}
        </td>
    </tr>
</table>


{{-- Note Section --}}
<p style="margin-top:15px; color:#555; font-size:13px;">
    💡 The remaining balance will be automatically charged on 
    <strong>{{ $booking->remaining_payment_scheduled_at ? \Carbon\Carbon::parse($booking->remaining_payment_scheduled_at)->format('d M Y') : 'your due date' }}</strong>.
    The damage deposit will be pre-authorized a few days before your arrival.
</p>

{{-- Receipt Button --}}
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
    You're receiving this email because you made a booking at {{ config('app.name') }},  
    which partners with Stripe to provide invoicing and secure payment processing.
</p>

<p style="text-align:center; font-size:12px; color:#999;">
    Something wrong with the email? 
    <a href="{{ $booking->receipt_url ?? '#' }}" style="color:#b9975b;">View it in your browser</a>
</p>

@endcomponent
