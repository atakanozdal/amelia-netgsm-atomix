# Changelog

## 1.0.0 - 2025-10-27

First release.

- REST endpoint `POST /wp-json/amelia-netgsm/v1/hook`, protected by a token created on activation.
- Reads the customer and appointment fields from the Amelia webhook JSON and sends a confirmation SMS through the Netgsm REST v2 API.
- Settings page under Settings → Amelia → Netgsm SMS with usercode, password, msgheader and the webhook URL.
