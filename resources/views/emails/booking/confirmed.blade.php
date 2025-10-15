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
        <td style="text-align:right;">£{{ number_format($booking->amount_paid ?? 0, 2) }}</td>
    </tr>
    <tr>
        <td><strong>Date Paid</strong></td>
        <td style="text-align:right;">{{ $booking->payment_date ? \Carbon\Carbon::parse($booking->payment_date)->format('M d, Y, h:i A') : \Carbon\Carbon::now()->format('M d, Y, h:i A') }}</td>
    </tr>
    <tr>
        <td><strong>Payment Method</strong></td>
        <td style="text-align:right;">{{ strtoupper($booking->card_brand ?? 'VISA') }} •••• {{ $booking->card_last4 ?? '4242' }}</td>
    </tr>
</table>

<hr>

{{-- Full Rate Section --}}
<h2 style="margin-top:25px; color:#333;">FULL RATE</h2>
<table width="100%" cellpadding="8" cellspacing="0" style="font-size:14px; border:1px solid #eee; border-radius:8px;">
    @if(!empty($booking->dogs))
    <tr>
        <td>Dogs ({{ $booking->dogs }})</td>
        <td style="text-align:right;">£{{ number_format($booking->dogs_total ?? 0, 2) }}</td>
    </tr>
    @endif

    <tr>
        <td>Deposit</td>
        <td style="text-align:right;">£{{ number_format($booking->deposit_amount ?? 0, 2) }}</td>
    </tr>

    <tr>
        <td>Balance (Due Now)</td>
        <td style="text-align:right;">£{{ number_format($booking->balance_due_now ?? 0, 2) }}</td>
    </tr>

    <tr>
        <td>Initial Payment</td>
        <td style="text-align:right;">£{{ number_format($booking->initial_payment ?? 0, 2) }}</td>
    </tr>

    <tr>
        <td>Final Payment (due: {{ $booking->final_payment_date ? \Carbon\Carbon::parse($booking->final_payment_date)->format('D d F Y') : 'N/A' }})</td>
        <td style="text-align:right;">£{{ number_format($booking->final_payment_amount ?? 0, 2) }}</td>
    </tr>

    <tr>
        <td>Damage Deposit (hold: {{ $booking->damage_deposit_date ? \Carbon\Carbon::parse($booking->damage_deposit_date)->format('D d F Y') : 'N/A' }})</td>
        <td style="text-align:right;">£{{ number_format($booking->damage_deposit_amount ?? 0, 2) }}</td>
    </tr>

    <tr>
        <td>Advance ({{ $booking->advance_percentage ?? 25 }}%)</td>
        <td style="text-align:right;">£{{ number_format($booking->advance_amount ?? 0, 2) }}</td>
    </tr>
</table>

{{-- Total Section --}}
<h2 style="margin-top:25px; color:#333;">TOTAL PRICE</h2>
<table width="100%" cellpadding="8" cellspacing="0" style="font-size:14px; border:1px solid #eee; border-radius:8px;">
    <tr style="background-color:#f9f9f9;">
        <td><strong>Total Price</strong></td>
        <td style="text-align:right;"><strong>£{{ number_format($booking->total_price ?? 0, 2) }}</strong></td>
    </tr>
    <tr>
        <td>Security Deposit</td>
        <td style="text-align:right;">£{{ number_format($booking->security_deposit ?? 0, 2) }}</td>
    </tr>
</table>

{{-- Security Deposit Note --}}
<p style="margin-top:15px; color:#555; font-size:13px;">
    We ask our guests for a security deposit, pre-authorised against your credit card. 
    This is in case there is any damage to the property during your stay. 
    The money is simply reserved and then released afterwards if there is no damage. 
    We will contact you nearer the time of your arrival to arrange this.
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
