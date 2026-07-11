<?php
/**
 * Plugin helper functions.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether Address Guard Pro is active.
 *
 * @return bool
 */
function address_guard_pro_is_active(): bool {
	if ( defined( 'WPRUBY_AG_ITEM_ID' ) && WPRUBY_AG_ITEM_ID ) {
		return true;
	}

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	return is_plugin_active( 'woocommerce-address-guard-pro/address-guard-for-woocommerce.php' );
}

/**
 * Render an admin notice when Pro is active alongside Lite.
 *
 * @return void
 */
function address_guard_lite_pro_conflict_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__(
		'Address Guard Pro is active. Please deactivate Address Guard for WooCommerce Lite to avoid duplicate checkout checks.',
		'address-guard-for-woocommerce'
	);
	echo '</p></div>';
}
