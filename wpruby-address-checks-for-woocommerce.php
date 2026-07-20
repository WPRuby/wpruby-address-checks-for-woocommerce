<?php
/**
 * The plugin bootstrap file.
 *
 * @wordpress-plugin
 * Plugin Name:       WPRuby Address Checks for WooCommerce
 * Plugin URI:        https://wpruby.com/
 * Description:       Add Google address autocomplete and local checkout address checks for WooCommerce, including missing house number, PO box, and parcel locker detection.
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Requires at least: 6.5
 * Tested up to:      7.0
 * WC requires at least: 8.0
 * WC tested up to:   9.0
 * Author:            WPRuby
 * Author URI:        https://wpruby.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpruby-address-checks-for-woocommerce
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPRUBY_ADDRESS_CHECKS_VERSION', '1.0.0' );
define( 'WPRUBY_ADDRESS_CHECKS_PLUGIN_FILE', __FILE__ );
define( 'WPRUBY_ADDRESS_CHECKS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPRUBY_ADDRESS_CHECKS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPRUBY_ADDRESS_CHECKS_TEXT_DOMAIN', 'wpruby-address-checks-for-woocommerce' );
define( 'WPRUBY_ADDRESS_CHECKS_BASENAME', plugin_basename( __FILE__ ) );

require_once WPRUBY_ADDRESS_CHECKS_PLUGIN_DIR . 'includes/functions.php';

/*
 * Bail before loading Lite classes when Pro is active. Both plugins share the
 * WPRuby\AddressGuard namespace; loading both would cause fatal class collisions
 * and duplicate checkout checks.
 */
if ( wpruby_address_checks_pro_is_active() ) {
	add_action( 'admin_notices', __NAMESPACE__ . '\\wpruby_address_checks_pro_conflict_notice' );

	return;
}

require_once WPRUBY_ADDRESS_CHECKS_PLUGIN_DIR . 'includes/autoload.php';

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

/**
 * Declare compatibility with WooCommerce features (HPOS, cart/checkout blocks).
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', WPRUBY_ADDRESS_CHECKS_PLUGIN_FILE, true );
			FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WPRUBY_ADDRESS_CHECKS_PLUGIN_FILE, true );
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
