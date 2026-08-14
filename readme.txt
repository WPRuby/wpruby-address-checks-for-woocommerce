=== WPRuby Address Checks for WooCommerce ===
Contributors: wprubyplugins, waseem_senjer
Tags: woocommerce, address autocomplete, address check, checkout, google places
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add Google address autocomplete and local checkout address checks for WooCommerce, including missing house number, PO box, and parcel locker checks.

== Description ==

WPRuby Address Checks for WooCommerce helps customers enter addresses faster with Google Places Autocomplete and catches common checkout address issues such as missing house numbers, PO boxes, and parcel locker addresses.

WPRuby Address Checks for WooCommerce is an independent plugin by WPRuby and is not affiliated with, endorsed by, or sponsored by WooCommerce, Automattic, WordPress, or Google.

This plugin provides address autocomplete and checkout address checks. It does not perform full postal deliverability validation.

**Works with WooCommerce**

* Supports WooCommerce Checkout Blocks
* Supports classic `[woocommerce_checkout]` shortcode

**Google Places Autocomplete support**

* Optional Google Places Autocomplete at checkout
* Bring-your-own Google Maps Platform API key
* Works with shipping and billing address line 1

**Local checkout checks**

* Missing house or building number detection
* PO box detection
* Parcel locker / Packstation detection
* Basic postcode format checks for selected countries
* Warn customer or block checkout modes
* Optional private order notes

Local checkout checks run on your server and do not require Google.

**Upgrade to Pro**

Need deeper address protection? [Upgrade to Pro](https://wpruby.com/plugin/woocommerce-address-guard-pro/?utm_source=wordpress_org&utm_medium=referral&utm_campaign=address_guard_free&utm_content=upgrade_to_pro) for provider-powered address validation, correction suggestions, advanced rules, an address tester, logs, and order review tools.

Pro adds:

* Google Address Validation
* Loqate Address Verify
* Mapbox Address Autofill
* Loqate Address Capture
* Correction suggestions
* Advanced checkout rules and rule presets
* Address tester
* Order review panel
* Logs and diagnostics
* Multi-provider setup
* Priority support

The free plugin does not include provider-powered postal deliverability validation. Learn more at [WPRuby Address Checks Pro](https://wpruby.com/plugin/woocommerce-address-guard-pro/?utm_source=wordpress_org&utm_medium=referral&utm_campaign=address_guard_free&utm_content=learn_more).

== External services ==

When Google Places Autocomplete is enabled, address search queries entered by customers at checkout are sent to Google Places through the store owner's configured Google Maps Platform account. This is used to return address suggestions.

This plugin does not include bundled Google API usage. Store owners must use their own Google Maps Platform API key.

Service provider: Google Maps Platform
Service documentation: https://developers.google.com/maps/documentation/places
Terms: https://cloud.google.com/maps-platform/terms
Privacy Policy: https://policies.google.com/privacy

== Source code ==

Compiled admin assets live in `assets/admin/dist/`. Human-readable Vue source is available in the public repository linked below.

Public repository: https://github.com/WPRuby/wpruby-address-checks-for-woocommerce

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wpruby-address-checks-for-woocommerce`, or install through the WordPress plugins screen.
2. Activate the plugin through the Plugins screen.
3. Ensure WooCommerce is installed and active.
4. Go to WooCommerce → Address Checks to configure autocomplete, checks, and messages.
5. Optional: enable Google Places Autocomplete and enter your Google Maps Platform API key.

== Frequently Asked Questions ==

= Does this plugin include full address validation? =

No. This plugin includes Google Places Autocomplete and local checkout address checks such as missing house numbers, PO boxes, and parcel locker detection. It does not perform provider-powered postal deliverability validation.

= Do I need a Google API key? =

Google Places Autocomplete requires your own Google Maps Platform API key. Local checkout checks can work without Google Autocomplete.

= Does the plugin send address data to Google? =

Only when Google Places Autocomplete is enabled. Customer address search input is sent to Google Places to return suggestions. Local checks stay on your server.

= Does this support Checkout Blocks? =

Yes. WPRuby Address Checks for WooCommerce supports WooCommerce Checkout Blocks and the classic checkout shortcode.

= Can I warn instead of blocking checkout? =

Yes. Choose **Warn customer** or **Block checkout** under WooCommerce → Address Checks → General.

= Is this an official WooCommerce or Google plugin? =

No. WPRuby Address Checks for WooCommerce is an independent plugin by WPRuby and is not affiliated with, endorsed by, or sponsored by WooCommerce, Automattic, WordPress, or Google.

== Screenshots ==

1. General settings — enable plugin, checkout behavior, and address scope
2. Autocomplete — Google Places API key and country limits
3. Checks — toggle local address checks
4. Messages — customize customer-facing warning and error text
5. Google Autocomplete

== Changelog ==

= 1.0.2 =
* Add consistent UTM tracking parameters to Address Guard Pro upgrade links in the plugin admin and WordPress.org readme
* Add WordPress 7.1 compatibility. 
* Add WooCommerce 11.0 compatibility. 

= 1.0.1 =
* Add an Upgrade to Pro tab in the settings UI with a clear Free vs Pro feature comparison
* Add icons to admin settings tabs for clearer navigation
* Clarify Lite feature positioning and documentation for local checkout checks
* Exclude Vue source and build tooling from the production ZIP; point to the public repository for human-readable source

= 1.0.0 =
* Google Places Autocomplete for Checkout Blocks and classic checkout
* Local missing house number, PO box, parcel locker, and postcode format checks
* Warn and block checkout modes
* Optional private order notes

== Upgrade Notice ==

= 1.0.0 =
Initial release with Google Places Autocomplete and local checkout checks.
