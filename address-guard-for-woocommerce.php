<?php
/**
 * The plugin bootstrap file.
 *
 * @wordpress-plugin
 * Plugin Name:       Checkout Address Guard for WooCommerce
 * Plugin URI:        https://wpruby.com/plugin/address-guard-for-woocommerce/
 * Description:       Add Google address autocomplete and local checkout address checks for WooCommerce, including missing house number, PO box, and parcel locker detection.
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Requires at least: 5.6
 * Tested up to:      7.0
 * WC requires at least: 6.0
 * WC tested up to:   9.9
 * Author:            WPRuby
 * Author URI:        https://wpruby.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       checkout-address-guard-for-woocommerce
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
define( 'ADDRESS_GUARD_TEXT_DOMAIN', 'checkout-address-guard-for-woocommerce' );
define( 'ADDRESS_GUARD_BASENAME', plugin_basename( __FILE__ ) );

require_once ADDRESS_GUARD_PLUGIN_DIR . 'includes/functions.php';

/*
 * Bail before loading Lite classes when Pro is active. Both plugins share the
 * WPRuby\AddressGuard namespace; loading both would cause fatal class collisions
 * and duplicate checkout checks.
 */
if ( address_guard_pro_is_active() ) {
	add_action( 'admin_notices', __NAMESPACE__ . '\\address_guard_pro_conflict_notice' );

	return;
}

require_once ADDRESS_GUARD_PLUGIN_DIR . 'includes/autoload.php';

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
