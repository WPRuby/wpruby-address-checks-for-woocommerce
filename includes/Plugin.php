<?php
/**
 * Main plugin container and bootstrapper.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard;

use WPRuby\AddressGuard\Admin\AdminPage;
use WPRuby\AddressGuard\Domain\AddressValidator;
use WPRuby\AddressGuard\Infrastructure\Assets;
use WPRuby\AddressGuard\Infrastructure\Settings;
use WPRuby\AddressGuard\REST\SettingsController;
use WPRuby\AddressGuard\REST\ValidationController;
use WPRuby\AddressGuard\WooCommerce\CheckoutCompatibility;
use WPRuby\AddressGuard\WooCommerce\CheckoutValidation;
use WPRuby\AddressGuard\WooCommerce\ClassicCheckoutIntegration;
use WPRuby\AddressGuard\WooCommerce\OrderNotes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Acts as a lightweight service container and wires the modules together.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Settings accessor.
	 *
	 * @var Settings|null
	 */
	private $settings;

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor: wire everything up.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'render_missing_woocommerce_notice' ) );

			return;
		}

		$this->boot();
	}

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public static function activate(): void {
		$settings = new Settings();

		if ( false === get_option( Settings::OPTION_KEY, false ) ) {
			$settings->save( $settings->defaults() );
		}
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Reserved for future scheduled task cleanup.
	}

	/**
	 * Instantiate services and hook integrations.
	 *
	 * @return void
	 */
	private function boot(): void {
		$this->settings     = new Settings();
		$validator          = new AddressValidator( $this->settings );
		$order_notes        = new OrderNotes( $this->settings );
		$compatibility      = new CheckoutCompatibility();
		$classic_checkout   = new ClassicCheckoutIntegration( $this->settings, $validator, $compatibility, $order_notes );
		$checkout_validation = new CheckoutValidation( $this->settings, $validator, $compatibility, $order_notes );

		( new Assets( $this->settings, $classic_checkout, $checkout_validation ) )->register();
		( new SettingsController( $this->settings ) )->register();
		( new ValidationController( $this->settings, $validator ) )->register();

		if ( is_admin() ) {
			( new AdminPage() )->register();
		}

		$classic_checkout->register();
		$checkout_validation->register();
		$order_notes->register();
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'address-guard-for-woocommerce',
			false,
			dirname( ADDRESS_GUARD_BASENAME ) . '/languages'
		);
	}

	/**
	 * Whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Render an admin notice when WooCommerce is not active.
	 *
	 * @return void
	 */
	public function render_missing_woocommerce_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html__(
			'Address Guard for WooCommerce requires WooCommerce to be installed and active.',
			'address-guard-for-woocommerce'
		);
		echo '</p></div>';
	}

	/**
	 * Settings accessor.
	 *
	 * @return Settings|null
	 */
	public function settings(): ?Settings {
		return $this->settings;
	}
}
