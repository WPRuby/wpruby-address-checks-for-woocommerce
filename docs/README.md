# Address Guard for WooCommerce (Lite)

WordPress.org-ready Lite plugin that performs **local checkout address checks** for WooCommerce.

## Lite scope

Lite catches common address problems before an order is placed:

* Missing house number
* PO box addresses
* Parcel locker / Packstation addresses
* Basic postcode format checks (US, CA, GB, AU)

Lite does **not** include:

* External providers (Google, Mapbox, Loqate, Smarty)
* Address autocomplete
* Provider-powered validation or correction suggestions
* Advanced rules engine
* Order review panel
* Activity logs
* Licensing or EDD updater

## Pro differences

Address Guard Pro adds provider integrations, autocomplete, advanced rules, order review tools, logs, and the address tester. See the **Upgrade to Pro** tab in WooCommerce → Address Guard.

If both Lite and Pro are active, Lite shows a conflict notice and does not boot checkout logic.

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

Output: `dist/address-guard-for-woocommerce-{version}.zip`

The ZIP excludes tests, source Vue files, development configs, and Pro-only code paths.

## WordPress.org release checklist

- [ ] Bump version in `address-guard-for-woocommerce.php` and `readme.txt` stable tag
- [ ] Run `make test` and `make build`
- [ ] Confirm ZIP passes `scripts/validate-build.php`
- [ ] Confirm no license prompts, API key fields, or external API dependencies
- [ ] Confirm upsell appears only on the plugin settings page
- [ ] Test classic checkout and Checkout Blocks
- [ ] Test with HPOS enabled
- [ ] Test conflict notice when Pro is active
- [ ] Upload to WordPress.org SVN

## Filter hooks

* `address_guard_default_settings`
* `address_guard_validate_address`
* `address_guard_lite_missing_house_number_patterns`
* `address_guard_lite_po_box_patterns`
* `address_guard_lite_parcel_locker_patterns`
