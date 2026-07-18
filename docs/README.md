# Checkout Address Guard for WooCommerce

WordPress.org plugin that adds Google Places Autocomplete and local checkout address checks for WooCommerce.

Checkout Address Guard for WooCommerce is an independent plugin and is not affiliated with, endorsed by, or sponsored by WooCommerce, Automattic, WordPress, or Google.

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

Settings are stored in the WordPress option `address_guard_lite_settings`.

## Build release ZIP

```bash
make build
```

Output:

* `dist/address-guard-for-woocommerce.zip`
* `dist/address-guard-for-woocommerce-{version}.zip`

The ZIP includes compiled admin assets plus human-readable Vue source (`assets/admin/vue/`), `package.json`, and `vite.config.js` for WordPress.org review. It excludes tests, `node_modules`, `vendor`, and development folders.

## WordPress.org release checklist

- [ ] Bump version in `address-guard-for-woocommerce.php` and `readme.txt` stable tag
- [ ] Run `make test` and `make build`
- [ ] Confirm ZIP passes `scripts/validate-build.php`
- [ ] Run Plugin Check: `wp plugin check address-guard-for-woocommerce`
- [ ] Test classic checkout and Checkout Blocks
- [ ] Test with HPOS enabled
- [ ] Confirm Google API key is masked in admin and never exposed to checkout JS
- [ ] Upload to WordPress.org SVN

## Filter hooks

* `address_guard_default_settings`
* `address_guard_validate_address`
* `address_guard_street_line_format`
* `address_guard_lite_missing_house_number_patterns`
* `address_guard_lite_po_box_patterns`
* `address_guard_lite_parcel_locker_patterns`
