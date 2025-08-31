# Phone Number Clean Up

## Warning before use

This repository and plugin is an experiment in AI coding. Please use with caution.

**Phone Number Clean Up** is a WordPress plugin that allows users to extract phone numbers from a block of text. The plugin provides a public-facing form via shortcode or Gutenberg block where users can paste text and receive a list of unique, normalized phone numbers (currently USA + Spain). All extracted phone numbers are stored in the database for future reference.

---

## Features

- Public-facing page or post support via shortcode `[phone_number_cleanup]`
- Gutenberg block (Phone Number Clean Up) with toggle to show/hide normalized numbers
- Extracts and normalizes US + Spain phone numbers to E.164 (`+1...`, `+34...`)
- Deduplication of numbers (unique list)
- Optional display of normalized + original variant
- Input length limits and basic rate limiting (to mitigate abuse)
- Security hardening: nonces, sanitization, escaping
- Internationalization (English + Spanish provided) – easily extendable
- Storage of extracted phone numbers in database
- Admin interface to view extracted numbers
- For logged-in users, numbers are associated with their account and displayed on future visits

---

## Installation

1. Download or clone this repository into your `wp-content/plugins/` directory:
   ```
   git clone https://github.com/mrhobbeys/phone-number-clean-up.git
   ```
2. In your WordPress dashboard, go to **Plugins** and activate **Phone Number Clean Up**.
3. Important: Review the privacy considerations in PRIVACY.md and update your site's privacy policy accordingly.

If using the block editor, the block will appear under Widgets after activation.

---

## Usage

### Shortcode
```
[phone_number_cleanup show_normalized="yes"]
```
Parameters:
- `show_normalized`: `yes` (default) or `no` – whether to display normalized E.164 plus original raw variant.

### Gutenberg Block
Insert the block "Phone Number Clean Up" and toggle the "Show normalized numbers" option in the sidebar.

### Admin View
Administrators can view all extracted phone numbers under Tools > Phone Numbers in the WordPress admin.

---

## How It Works
- User pastes text.
- Plugin scans for US and Spanish phone number patterns.
- Numbers are normalized to E.164 and deduplicated.
- Results (unique) are displayed on the same page.
- Numbers are stored in the database for future reference.
- Logged-in users can see their previously extracted numbers.

---

## Privacy Considerations
This plugin stores extracted phone numbers in your WordPress database. 
If you use this plugin on a public site, you should update your privacy policy to reflect this data collection.

See the [PRIVACY.md](PRIVACY.md) file for recommended privacy policy text and regulatory considerations.

---

## Roadmap / Ideas
- Add more country patterns (configurable)
- CSV export / copy button
- Client-side extraction (JS) for instant feedback
- Settings page for customization
- Unit tests (regex & extraction performance)
- Data retention policy configuration

---

## Security
Implemented:
- Nonce validation
- Input length cap (`PNC_MAX_INPUT_CHARS`)
- Basic IP-based rate limiting
- Sanitization & escaping of user output

Recommended future improvements:
- Optional CAPTCHA integration
- More granular per-user throttling
- Logging (opt-in)

---

## Internationalization
Spanish (`es_ES`) translation provided. Add additional `.po` / `.mo` files under `languages/` as needed.

---

## License
This plugin is licensed under the [GNU General Public License v2.0 or later (GPLv2+)](LICENSE).

---

## Contributing
Pull requests and suggestions are welcome! Please open an issue or PR if you spot a bug or have an idea for improvement.

---

## Support
For help or questions, open an issue on this repository.
