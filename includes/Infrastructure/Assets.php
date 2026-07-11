<?php
/**
 * Asset registration.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Infrastructure;

use WPRuby\AddressGuard\Admin\AdminPage;
use WPRuby\AddressGuard\WooCommerce\CheckoutValidation;
use WPRuby\AddressGuard\WooCommerce\ClassicCheckoutIntegration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Assets
 *
 * Registers admin and frontend CSS/JS.
 */
class Assets {

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Classic checkout integration helper.
	 *
	 * @var ClassicCheckoutIntegration
	 */
	private $classic_checkout;

	/**
	 * Checkout Blocks validation integration.
	 *
	 * @var CheckoutValidation
	 */
	private $checkout_validation;

	/**
	 * Constructor.
	 *
	 * @param Settings                   $settings             Settings accessor.
	 * @param ClassicCheckoutIntegration $classic_checkout     Classic checkout integration.
	 * @param CheckoutValidation         $checkout_validation  Checkout Blocks validation integration.
	 */
	public function __construct(
		Settings $settings,
		ClassicCheckoutIntegration $classic_checkout,
		CheckoutValidation $checkout_validation
	) {
		$this->settings            = $settings;
		$this->classic_checkout    = $classic_checkout;
		$this->checkout_validation = $checkout_validation;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout' ), 20 );
	}

	/**
	 * Enqueue admin assets on plugin pages only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin( string $hook_suffix ): void {
		unset( $hook_suffix );

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page check.

		if ( AdminPage::PAGE_SLUG !== $page ) {
			return;
		}

		$dist_dir = ADDRESS_GUARD_PLUGIN_DIR . 'assets/admin/dist/';
		$dist_url = ADDRESS_GUARD_PLUGIN_URL . 'assets/admin/dist/';
		$js_file  = $dist_dir . 'app.js';
		$css_file = $dist_dir . 'app.css';

		if ( ! is_readable( $js_file ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>';
					echo esc_html__(
						'Address Guard: the admin app bundle is missing. Run "npm install && npm run build" inside the plugin folder.',
						'address-guard-for-woocommerce'
					);
					echo '</p></div>';
				}
			);

			return;
		}

		$version = (string) ADDRESS_GUARD_VERSION . '.' . (string) filemtime( $js_file );

		if ( is_readable( $css_file ) ) {
			wp_enqueue_style(
				'address-guard-admin-app',
				$dist_url . 'app.css',
				array(),
				$version
			);
		}

		wp_enqueue_script(
			'address-guard-admin-app',
			$dist_url . 'app.js',
			array( 'wp-i18n' ),
			$version,
			true
		);

		wp_localize_script(
			'address-guard-admin-app',
			'addressGuardAdmin',
			array(
				'restUrl'         => esc_url_raw( rest_url( 'address-guard/v1/' ) ),
				'restNonce'       => wp_create_nonce( 'wp_rest' ),
				'version'         => (string) ADDRESS_GUARD_VERSION,
				'adminUrl'        => esc_url_raw( admin_url() ),
				'settings'        => $this->settings->for_app(),
				'defaultMessages' => $this->settings->defaults()['messages'],
				'logo'            => ADDRESS_GUARD_PLUGIN_URL . 'assets/admin/images/logo.png',
				'proUrl'          => esc_url_raw( 'https://wpruby.com/plugin/woocommerce-address-guard-pro/' ),
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'address-guard-admin-app', 'address-guard-for-woocommerce' );
		}
	}

	/**
	 * Enqueue checkout validation assets when enabled.
	 *
	 * @return void
	 */
	public function enqueue_checkout(): void {
		if ( ! $this->checkout_validation->should_enqueue() && ! $this->classic_checkout->should_enqueue_validation() ) {
			return;
		}

		$js_file  = ADDRESS_GUARD_PLUGIN_DIR . 'assets/checkout/validation.js';
		$css_file = ADDRESS_GUARD_PLUGIN_DIR . 'assets/checkout/validation.css';

		if ( ! is_readable( $js_file ) ) {
			return;
		}

		$version = (string) ADDRESS_GUARD_VERSION . '.' . (string) filemtime( $js_file );

		if ( is_readable( $css_file ) ) {
			wp_enqueue_style(
				'address-guard-validation',
				ADDRESS_GUARD_PLUGIN_URL . 'assets/checkout/validation.css',
				array(),
				$version
			);
		}

		wp_enqueue_script(
			'address-guard-validation',
			ADDRESS_GUARD_PLUGIN_URL . 'assets/checkout/validation.js',
			array( 'jquery' ),
			$version,
			true
		);

		wp_localize_script(
			'address-guard-validation',
			'addressGuardCheckout',
			$this->checkout_validation->frontend_config()
		);
	}
}
