@component('mail::message')
# Booking Confirmed

Thank you **{{ $booking->first_name ?? 'Guest' }}** for your booking!

---

### Booking Details

**Booking Reference:** {{ $booking->booking_reference ?? 'N/A' }}  
**Property ID:** {{ $booking->property_id ?? 'N/A' }}  
**Booking ID:** {{ $booking->booking_id ?? 'N/A' }}  
**Initial Payment:** £{{ number_format($booking->upfront_cents ?? 0, 2) }}  
**Final Payment:** £{{ number_format($booking->remaining_cents ?? 0, 2) }}  
**Final Payment Due Date:** {{ $booking->remaining_payment_scheduled_at ? \Carbon\Carbon::parse($booking->remaining_payment_scheduled_at)->format('d M Y') : 'N/A' }}

---

@if($booking->receipt_url)
Your receipt: [View Receipt]({{ $booking->receipt_url }})
@else
Your receipt will be available shortly.
@endif

---

We’ll contact you soon with more details about your stay.

Thanks,  
{{ config('app.name') }}
@endcomponent
