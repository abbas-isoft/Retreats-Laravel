@component('mail::message')
# 🎉 Booking Confirmation

Hi **{{ $booking->first_name ?? 'Guest' }}**,  

Thank you for booking with us!  
Here are your booking details:

---

## 🏠 Booking Summary

**Booking Reference:** {{ $booking->booking_reference ?? 'N/A' }}  
**Property ID:** {{ $booking->property_id ?? 'N/A' }}  
**Booking ID:** {{ $booking->booking_id ?? 'N/A' }}

---

## 💳 Payment Details

| Description | Amount (£) | Status |
|--------------|-------------|---------|
| **Initial Payment (25%)** | {{ number_format($booking->initial_payment ?? 0, 2) }} | ✅ Paid |
| **Final Payment (Remaining)** | {{ number_format($booking->final_payment ?? 0, 2) }} | {{ $booking->payment_status == 'pending' ? '⏳ Pending' : '✅ Paid' }} |
| **Final Payment Due Date** | {{ $booking->final_payment_due_date ? \Carbon\Carbon::parse($booking->final_payment_due_date)->format('d M Y') : 'N/A' }} | — |
| **Damage Deposit Hold** | {{ number_format($booking->damage_deposit ?? 0, 2) }} | 💰 Held |
| **Hold Release Date** | {{ $booking->damage_deposit_hold_date ? \Carbon\Carbon::parse($booking->damage_deposit_hold_date)->format('d M Y') : 'N/A' }} | — |

---

@if($booking->receipt_url)
@component('mail::button', ['url' => $booking->receipt_url])
🧾 View Your Receipt
@endcomponent
@else
Your payment receipt will be available shortly.
@endif

---

### 📅 Next Steps
- Please ensure the **final payment** is made before the due date.  
- You will receive an update once your payment is confirmed.  
- For any queries or changes, feel free to reply to this email.

---

Thanks again for choosing us!  
We look forward to hosting you soon.  

Warm regards,  
**{{ config('app.name') }} Team**

@endcomponent
