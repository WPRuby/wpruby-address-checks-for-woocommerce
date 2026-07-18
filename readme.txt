=== Address Guard for WooCommerce ===
Contributors: wpruby
Tags: woocommerce, checkout, address, autocomplete, google places
Requires at least: 5.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add Google address autocomplete and local checkout address checks for WooCommerce, including missing house number, PO box, and parcel locker detection.

== Description ==

Address Guard for WooCommerce helps customers enter addresses faster with Google Places Autocomplete and catches common checkout address issues such as missing house numbers, PO boxes, and parcel locker addresses.

This free Lite plugin provides **address autocomplete and checkout address checks**. It does not claim full postal deliverability validation. Provider-powered address validation and correction suggestions are available in Address Guard Pro.

**Google Places Autocomplete:**

* Optional Google Places Autocomplete at checkout
* Bring-your-own Google Maps Platform API key
* Works with shipping and billing address line 1
* Supports WooCommerce Checkout Blocks and classic checkout

**Local checkout checks:**

* Missing house or building number detection
* PO box detection
* Parcel locker / Packstation detection
* Basic postcode format checks for selected countries
* Warn customer or block checkout modes
* Optional private order notes when a check triggers

**Checkout support:**

* WooCommerce Checkout Blocks
* Classic `[woocommerce_checkout]` shortcode

**Need more?** Upgrade to [Address Guard Pro](https://wpruby.com/plugin/woocommerce-address-guard-pro/) for provider-powered address validation, correction suggestions, Loqate, Mapbox, advanced rules, an address tester, logs, and order review tools.

== External services ==

When Google Places Autocomplete is enabled, address search queries are sent to Google through your configured Google Maps Platform account. The plugin does not include bundled Google API usage.

* **Service:** Google Places API (Google Maps Platform)
* **Data sent:** The customer’s typed address search text and optional country context used to return suggestions
* **Why:** To show address autocomplete suggestions at checkout
* **Terms:** [Google Maps Platform Terms of Service](https://cloud.google.com/maps-platform/terms)
* **Privacy:** [Google Privacy Policy](https://policies.google.com/privacy)

Local checkout checks always run on your server and do not require Google.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/address-guard-for-woocommerce`, or install through the WordPress plugins screen.
2. Activate the plugin through the Plugins screen.
3. Ensure WooCommerce is installed and active.
4. Go to WooCommerce → Address Guard to configure autocomplete, checks, and messages.
5. Optional: enable Google Places Autocomplete and enter your Google Maps Platform API key.

== Frequently Asked Questions ==

= Does the free plugin include address validation? =

The free plugin includes Google Places Autocomplete and local checkout address checks. Provider-powered address validation and correction suggestions are available in Address Guard Pro.

= Do I need a Google API key? =

Google Autocomplete requires your own Google Maps Platform API key. Local checkout checks can work without Google Autocomplete.

= Does the plugin send address data to Google? =

Only when Google Autocomplete is enabled. Customer address search input is sent to Google Places to return suggestions.

= Does Lite support Checkout Blocks? =

Yes, Lite supports WooCommerce Checkout Blocks and the classic checkout shortcode.

= Can I warn instead of blocking checkout? =

Yes. Choose **Warn customer** or **Block checkout** under WooCommerce → Address Guard → General.

= What happens if Address Guard Pro is also installed? =

Lite detects Pro and shows an admin notice. Deactivate Lite to avoid duplicate checkout checks.

== Screenshots ==

1. General settings — enable plugin, checkout behavior, and address scope
2. Autocomplete — Google Places API key and country limits
3. Checks — toggle local address checks
4. Messages — customize customer-facing warning and error text
5. Upgrade — compare Lite and Pro features

== Changelog ==

= 1.0.0 =
* Google Places Autocomplete for Checkout Blocks and classic checkout
* Local missing house number, PO box, parcel locker, and postcode format checks
* Warn and block checkout modes
* Optional private order notes

== Upgrade Notice ==

= 1.0.0 =
Initial Lite release for WordPress.org with Google Places Autocomplete and local checkout checks.
