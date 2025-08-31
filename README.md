# Phone Number Clean Up

## Warning before use

This repository and plugin is an experiment in AI coding. Please use with caution.

**Phone Number Clean Up** is a WordPress plugin that allows users to extract phone numbers from a block of text. The plugin provides a public-facing form via shortcode or Gutenberg block where users can paste text and receive a list of unique, normalized phone numbers (currently USA + Spain). No data is sent offsite.

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
- Privacy friendly: no storage, no external API calls

---

## Installation

1. Download or clone this repository into your `wp-content/plugins/` directory:
   ```
   git clone https://github.com/mrhobbeys/phone-number-clean-up.git
   ```
2. In your WordPress dashboard, go to **Plugins** and activate **Phone Number Clean Up**.

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

---

## How It Works
- User pastes text.
- Plugin scans for US and Spanish phone number patterns.
- Numbers are normalized to E.164 and deduplicated.
- Results (unique) are displayed on the same page.

---

## Roadmap / Ideas
- Add more country patterns (configurable)
- CSV export / copy button
- Client-side extraction (JS) for instant feedback
- Settings page for customization
- Unit tests (regex & extraction performance)

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
