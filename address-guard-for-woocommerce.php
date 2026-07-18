<?php
/**
 * The plugin bootstrap file.
 *
 * @wordpress-plugin
 * Plugin Name:       Address Guard for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/address-guard-for-woocommerce/
 * Description:       Address autocomplete and checkout address checks for WooCommerce — Google Places Autocomplete plus missing house number, PO box, and parcel locker detection.
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Requires at least: 5.6
 * Tested up to:      7.0
 * WC requires at least: 6.0
 * WC tested up to:   9.9
 * Author:            WPRuby
 * Author URI:        https://wpruby.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       address-guard-for-woocommerce
 * Domain Path:       /languages
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ADDRESS_GUARD_VERSION', '1.0.0' );
define( 'ADDRESS_GUARD_PLUGIN_FILE', __FILE__ );
define( 'ADDRESS_GUARD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADDRESS_GUARD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ADDRESS_GUARD_TEXT_DOMAIN', 'address-guard-for-woocommerce' );
define( 'ADDRESS_GUARD_BASENAME', plugin_basename( __FILE__ ) );

require_once ADDRESS_GUARD_PLUGIN_DIR . 'includes/autoload.php';
require_once ADDRESS_GUARD_PLUGIN_DIR . 'includes/functions.php';

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

/**
 * Declare compatibility with WooCommerce features (HPOS, cart/checkout blocks).
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', ADDRESS_GUARD_PLUGIN_FILE, true );
			FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', ADDRESS_GUARD_PLUGIN_FILE, true );
		}
	}
);

/**
 * Boot the plugin once all plugins are loaded so WooCommerce availability can be detected.
 */
add_action(
	'plugins_loaded',
	static function () {
		Plugin::get_instance();
	},
	20
);
