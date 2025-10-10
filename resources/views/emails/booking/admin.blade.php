@component('mail::message')
# New Booking Notification 🏡

A new booking has been confirmed.

**Booking ID:** {{ $booking->id }}
**Customer:** {{ $booking->first_name ?? 'N/A' }}
**Amount:** £{{ $booking->initial_payment ?? 'N/A' }}

@endcomponent
