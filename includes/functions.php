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
 * Whether WPRuby Address Checks Pro is active.
 *
 * Checked before Lite class loading to avoid namespace collisions when both
 * plugins are installed. Detection uses the Pro bootstrap constant when
 * available, then falls back to known plugin basenames.
 *
 * @return bool
 */
function wpruby_address_checks_pro_is_active(): bool {
	if ( defined( 'WPRUBY_AG_ITEM_ID' ) && WPRUBY_AG_ITEM_ID ) {
		return true;
	}

	$candidates = array(
		'address-guard-pro/address-guard-for-woocommerce.php',
		'woocommerce-address-guard-pro/address-guard-for-woocommerce.php',
		'address-guard-for-woocommerce-pro/address-guard-for-woocommerce.php',
	);

	if ( function_exists( 'is_plugin_active' ) ) {
		foreach ( $candidates as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				return true;
			}
		}

		return false;
	}

	// During early bootstrap, inspect the active plugins option directly.
	$active = (array) get_option( 'active_plugins', array() );
	foreach ( $candidates as $plugin ) {
		if ( in_array( $plugin, $active, true ) ) {
			return true;
		}
	}

	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		$network = (array) get_site_option( 'active_sitewide_plugins', array() );
		foreach ( $candidates as $plugin ) {
			if ( isset( $network[ $plugin ] ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Render an admin notice when Pro is active alongside this plugin.
 *
 * @return void
 */
function wpruby_address_checks_pro_conflict_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__(
		'WPRuby Address Checks Pro is active. Please deactivate WPRuby Address Checks for WooCommerce to avoid duplicate checkout address checks.',
		'wpruby-address-checks-for-woocommerce'
	);
	echo '</p></div>';
}
