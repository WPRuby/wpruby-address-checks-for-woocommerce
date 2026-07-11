=== Address Guard for WooCommerce ===
Contributors: wpruby
Tags: woocommerce, checkout, address, validation, shipping
Requires at least: 5.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Catch missing house numbers, PO boxes, parcel lockers, and common checkout address issues before WooCommerce orders are placed.

== Description ==

Address Guard for WooCommerce helps merchants prevent common checkout address mistakes before an order is placed.

This free Lite plugin performs **local address checks only**. It does not call external APIs, does not require API keys, and does not claim full postal deliverability validation.

**Local checks include:**

* Missing house or building number detection
* PO box detection
* Parcel locker / Packstation detection
* Basic postcode format checks for selected countries

**Checkout support:**

* WooCommerce Checkout Blocks
* Classic `[woocommerce_checkout]` shortcode
* Warn customer or block checkout modes
* Optional private order notes when a check triggers

**Need more?** Upgrade to [Address Guard Pro](https://wpruby.com/plugin/woocommerce-address-guard-pro/) for Google, Mapbox, and Loqate providers, address autocomplete, provider-powered validation, correction suggestions, advanced rules, order review tools, and logs.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/address-guard-for-woocommerce`, or install through the WordPress plugins screen.
2. Activate the plugin through the Plugins screen.
3. Ensure WooCommerce is installed and active.
4. Go to WooCommerce → Address Guard to configure checks and messages.

== Frequently Asked Questions ==

= Does this plugin validate addresses with Google or Loqate? =

No. The free Lite plugin performs local pattern checks only. Provider-powered validation is available in Address Guard Pro.

= Does Lite send customer addresses to external services? =

No. Lite runs checks locally in PHP and does not transmit address data to third parties.

= Does this work with Checkout Blocks? =

Yes. Address Guard supports both WooCommerce Checkout Blocks and classic checkout.

= Can I warn instead of blocking checkout? =

Yes. Choose **Warn customer** or **Block checkout** under WooCommerce → Address Guard → General.

= What happens if Address Guard Pro is also installed? =

Lite detects Pro and shows an admin notice. Deactivate Lite to avoid duplicate checkout checks.

== Screenshots ==

1. General settings — enable plugin, checkout behavior, and address scope
2. Checks — toggle local address checks
3. Messages — customize customer-facing warning and error text
4. Upgrade — compare Lite and Pro features

== Changelog ==

= 1.0.0 =
* Local missing house number, PO box, parcel locker, and postcode format checks
* Checkout Blocks and classic checkout support
* Warn and block checkout modes
* Optional private order notes

== Upgrade Notice ==

= 1.0.0 =
Initial Lite release for WordPress.org.
