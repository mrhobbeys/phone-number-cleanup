# Phone Number Clean Up

## Warning before use

This repository and plugin is an expirment in AI coding. Please use with caution.

**Phone Number Clean Up** is a simple WordPress plugin that allows users to extract all phone numbers from a block of text. The plugin provides a public-facing form (via shortcode, or gutenberg block(maybe)) where users can paste text, and receive a list of all phone numbers found within that text.

---

## Features

- Public-facing page or post support via shortcode
- Paste any large block of text; plugin will extract all phone numbers found (maybe by country)
- Simple UI, no configuration required
- Designed for easy use and privacy (nothing is sent offsite)

---

## Installation

1. Download or clone this repository into your `wp-content/plugins/` directory:
   ```
   git clone https://github.com/mrhobbeys/phone-number-clean-up.git
   ```
2. In your WordPress dashboard, go to **Plugins** and activate **Phone Number Clean Up**.

---

## Usage

1. **Add the shortcode to any page or post:**

   ```
   [phone_number_cleanup]
   ```

2. Visit the page. You’ll see a form where users can paste text and extract phone numbers.

---

## How It Works

- The plugin uses a regular expression to find phone numbers in the user’s pasted text.
- Numbers are extracted and displayed in a list on the same page after submission.

---

## License

This plugin is licensed under the [GNU General Public License v2.0 or later (GPLv2+)](LICENSE).

---

## Contributing

Pull requests and suggestions are welcome! Please open an issue or PR if you spot a bug or have an idea for improvement.

---

## Support

For help or questions, open an issue on this repository.
