@component('mail::message')
# Booking Confirmed 🎉

Thank you {{ $booking->first_name ?? 'Guest' }} for your booking!

**Booking Reference:** {{ $booking->booking_reference }}

We’ll contact you soon.

Thanks,  
{{ config('app.name') }}
@endcomponent
