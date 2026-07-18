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

This plugin provides **address autocomplete and checkout address checks**. It does not claim full postal deliverability validation.

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

= Does this plugin include full address validation? =

No. This plugin includes Google Places Autocomplete and local checkout address checks such as missing house numbers, PO boxes, and parcel locker detection. It does not perform provider-powered postal deliverability validation.

= Do I need a Google API key? =

Google Autocomplete requires your own Google Maps Platform API key. Local checkout checks can work without Google Autocomplete.

= Does the plugin send address data to Google? =

Only when Google Autocomplete is enabled. Customer address search input is sent to Google Places to return suggestions.

= Does this support Checkout Blocks? =

Yes. Address Guard supports WooCommerce Checkout Blocks and the classic checkout shortcode.

= Can I warn instead of blocking checkout? =

Yes. Choose **Warn customer** or **Block checkout** under WooCommerce → Address Guard → General.

== Screenshots ==

1. General settings — enable plugin, checkout behavior, and address scope
2. Autocomplete — Google Places API key and country limits
3. Checks — toggle local address checks
4. Messages — customize customer-facing warning and error text

== Changelog ==

= 1.0.0 =
* Google Places Autocomplete for Checkout Blocks and classic checkout
* Local missing house number, PO box, parcel locker, and postcode format checks
* Warn and block checkout modes
* Optional private order notes

== Upgrade Notice ==

= 1.0.0 =
Initial release with Google Places Autocomplete and local checkout checks.
