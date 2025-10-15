@component('mail::message')
# 🏡 Booking Confirmation

Hi **{{ $booking->first_name ?? 'Guest' }}**,  

Thank you for booking with us!  
We’re excited to have you stay. Below are your booking details:

---

## 📋 Booking Details

**Booking Reference:** {{ $booking->booking_reference ?? 'N/A' }}  
**Property ID:** {{ $booking->property_id ?? 'N/A' }}  
**Booking ID:** {{ $booking->booking_id ?? 'N/A' }}

---

## 💳 Payment Summary

| Description | Amount (£) | Status |
|--------------|-------------|---------|
| **Initial Payment (25%)** | {{ number_format($booking->upfront_cents ?? 0, 2) }} | ✅ Paid |
| **Final Payment (Remaining)** | {{ number_format($booking->remaining_cents ?? 0, 2) }} | {{ $booking->payment_status == 'pending' ? '⏳ Pending' : '✅ Paid' }} |
| **Final Payment Due Date** | {{ $booking->remaining_payment_scheduled_at ? \Carbon\Carbon::parse($booking->remaining_payment_scheduled_at)->format('d M Y') : 'N/A' }} | — |

---

@if($booking->receipt_url)
@component('mail::button', ['url' => $booking->receipt_url])
🧾 View Your Receipt
@endcomponent
@else
Your payment receipt will be available shortly.
@endif

---

@if($booking->payment_status == 'pending')
> 💰 **25% payment received.** The remaining balance is due by  
> **{{ $booking->remaining_payment_scheduled_at ? \Carbon\Carbon::parse($booking->remaining_payment_scheduled_at)->format('d M Y') : 'your due date' }}.**
@else
> ✅ **Your full payment has been received.** Thank you for completing your booking!
@endif

---

### 📅 What’s Next
- You’ll receive reminders before your final payment is due.  
- Our team will contact you with further stay details soon.  
- For any questions, just reply to this email — we’re happy to help.

---

Thanks again for choosing us!  
Warm regards,  
**{{ config('app.name') }} Team**

@endcomponent
