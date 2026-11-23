# Changelog

## 1.0.1 - 2026-11-23

- The token is compared with `hash_equals`. If no token is stored, every request is rejected. The settings page creates a token when none exists.
- The message now depends on the event. Add `evt=booking_added`, `booking_canceled`, `booking_rescheduled` or `booking_status_updated` to the webhook URL (or the JSON body). Without `evt` the message follows the appointment status, and falls back to a neutral text instead of always saying the booking was approved.
- Customer name and phone are also read from `appointment.bookings[0].customer`, and date and time from `appointment.bookingStart`, which is what Amelia actually sends. The old flat fields still work.
- If there is no customer name the SMS says "Müşterimiz" instead of leaving a blank.
- Form input is unslashed before saving. The password field is a password input, it is not shown again after saving, and leaving it empty keeps the stored password.
- The webhook URL in the admin is built with `rest_url()`, so it also works with plain permalinks.

## 1.0.0 - 2025-10-27

First release.

- REST endpoint `POST /wp-json/amelia-netgsm/v1/hook`, protected by a token created on activation.
- Reads the customer and appointment fields from the Amelia webhook JSON and sends a confirmation SMS through the Netgsm REST v2 API.
- Settings page under Settings → Amelia → Netgsm SMS with usercode, password, msgheader and the webhook URL.
