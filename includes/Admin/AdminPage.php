<?php
/**
 * Admin app page (Vue mount point).
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdminPage
 *
 * Registers the "WooCommerce > Address Checks" page and renders the single
 * mount point for the Vue admin application.
 */
class AdminPage {

	const PAGE_SLUG  = 'wpruby-address-checks';
	const CAPABILITY = 'manage_woocommerce';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_filter( 'plugin_action_links_' . WPRUBY_ADDRESS_CHECKS_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Add the submenu page under WooCommerce.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Address Checks', 'wpruby-address-checks-for-woocommerce' ),
			__( 'Address Checks', 'wpruby-address-checks-for-woocommerce' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Add a settings link on the plugins screen.
	 *
	 * @param array<int,string> $links Existing links.
	 *
	 * @return array<int,string>
	 */
	public function action_links( array $links ): array {
		$url  = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wpruby-address-checks-for-woocommerce' ) . '</a>';
		array_unshift( $links, $link );

		return $links;
	}

	/**
	 * Render the Vue mount point.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		echo '<div class="wrap wpruby-address-checks-wrap">';
		echo '<div id="wpruby-address-checks-admin" class="wpruby-address-checks-admin">';
		echo '<p class="wpruby-address-checks-admin__loading">' . esc_html__( 'Loading WPRuby Address Checks…', 'wpruby-address-checks-for-woocommerce' ) . '</p>';
		echo '</div>';
		echo '</div>';
	}
}
