# WPRuby Address Checks for WooCommerce

WordPress.org plugin that adds Google Places Autocomplete and local checkout address checks for WooCommerce.

WPRuby Address Checks for WooCommerce is an independent plugin by WPRuby and is not affiliated with, endorsed by, or sponsored by WooCommerce, Automattic, WordPress, or Google.

## Features

* Google Places Autocomplete (merchant-owned API key)
* Missing house number detection
* PO box detection
* Parcel locker / Packstation detection
* Basic postcode format checks (US, CA, GB, AU)
* Warn or block checkout modes
* Checkout Blocks and classic checkout support

## Local development

```bash
composer install
npm ci
npm run build
make test
```

Run the admin Vue app in watch mode:

```bash
npm run dev
```

Settings are stored in the WordPress option `wpruby_address_checks_settings`.

## Build release ZIP

```bash
make build
```

Output:

* `dist/wpruby-address-checks-for-woocommerce.zip`
* `dist/wpruby-address-checks-for-woocommerce-{version}.zip`

The ZIP includes compiled admin assets only. Vue source, build tooling, tests, `node_modules`, `vendor`, and development folders are excluded. Human-readable source is available in the public GitHub repository.

## WordPress.org release checklist

- [ ] Bump version in `wpruby-address-checks-for-woocommerce.php` and `readme.txt` stable tag
- [ ] Run `make test` and `make build`
- [ ] Confirm ZIP passes `scripts/validate-build.php`
- [ ] Run Plugin Check: `wp plugin check wpruby-address-checks-for-woocommerce --slug=wpruby-address-checks-for-woocommerce`
- [ ] Test classic checkout and Checkout Blocks
- [ ] Test with HPOS enabled
- [ ] Confirm Google API key is masked in admin and never exposed to checkout JS
- [ ] Upload to WordPress.org SVN

## Ownership verification reminder

Before replying to the WordPress.org Plugins Team, the submitter should do one of:

* Change the WordPress.org account email to a WPRuby-domain email, or
* Add the requested DNS TXT record to the wpruby.com domain, or
* Ask the Plugins Team to transfer the submission to an official WPRuby WordPress.org account

Do not resubmit using another account.

## Filter hooks

* `wpruby_address_checks_default_settings`
* `wpruby_address_checks_validate_address`
* `wpruby_address_checks_street_line_format`
* `wpruby_address_checks_missing_house_number_patterns`
* `wpruby_address_checks_po_box_patterns`
* `wpruby_address_checks_parcel_locker_patterns`
