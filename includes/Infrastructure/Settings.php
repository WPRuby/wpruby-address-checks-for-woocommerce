<?php
/**
 * Settings accessor.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Infrastructure;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 *
 * Reads and writes Lite plugin settings stored in a single option.
 */
class Settings {

	const OPTION_KEY   = 'address_guard_lite_settings';
	const MASKED_VALUE = '••••••••';

	/**
	 * Allowed validation modes.
	 *
	 * @var string[]
	 */
	public const VALIDATION_MODES = array( 'warn', 'block' );

	/**
	 * Cached settings array.
	 *
	 * @var array<string,mixed>|null
	 */
	private $cache = null;

	/**
	 * Return the default settings (filterable).
	 *
	 * @return array<string,mixed>
	 */
	public function defaults(): array {
		$defaults = array(
			'plugin_enabled'               => 'yes',
			'validation_mode'              => 'warn',
			'validate_shipping_address'    => 'yes',
			'validate_billing_address'     => 'no',
			'autocomplete_enabled'         => 'no',
			'google_api_key'               => '',
			'autocomplete_countries'       => array(),
			'check_missing_house_number'   => 'yes',
			'check_po_box'                 => 'yes',
			'check_parcel_locker'          => 'yes',
			'check_postcode_format'        => 'yes',
			'messages'                     => array(
				'po_box_blocked'            => __( 'PO box addresses are not allowed for this order.', 'address-guard-for-woocommerce' ),
				'locker_blocked'            => __( 'Parcel locker addresses are not allowed for this order.', 'address-guard-for-woocommerce' ),
				'missing_house_number'      => __( 'Please include a house or building number in your {address_type}.', 'address-guard-for-woocommerce' ),
				'country_postcode_mismatch' => __( 'The postcode {postcode} does not match the selected country {country}.', 'address-guard-for-woocommerce' ),
				'validation_blocked'        => __( 'We cannot complete checkout with this address. Please update your {address_type}.', 'address-guard-for-woocommerce' ),
				'validation_warning'        => __( 'Your address was accepted with a warning. Please review your {address_type} before placing your order.', 'address-guard-for-woocommerce' ),
			),
			'order_add_validation_notes'   => 'yes',
		);

		/**
		 * Filter the default plugin settings before they are merged with stored values.
		 *
		 * @param array<string,mixed> $defaults Default settings.
		 */
		return (array) apply_filters( 'address_guard_default_settings', $defaults );
	}

	/**
	 * Return the full settings array (stored values merged over defaults).
	 *
	 * @return array<string,mixed>
	 */
	public function all(): array {
		if ( null === $this->cache ) {
			$stored = get_option( self::OPTION_KEY, array() );
			$stored = is_array( $stored ) ? $stored : array();
			$this->cache = $this->normalize( wp_parse_args( $stored, $this->defaults() ) );
		}

		return $this->cache;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value if not set.
	 *
	 * @return mixed
	 */
	public function get( string $key, $default = null ) {
		$all = $this->all();

		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Persist a settings array (already sanitized by the caller).
	 *
	 * @param array<string,mixed> $values Sanitized settings.
	 *
	 * @return void
	 */
	public function save( array $values ): void {
		$merged      = $this->normalize( wp_parse_args( $values, $this->defaults() ) );
		$this->cache = $merged;
		update_option( self::OPTION_KEY, $merged, false );
	}

	/**
	 * Whether the plugin is enabled globally.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return 'yes' === $this->get( 'plugin_enabled', 'yes' );
	}

	/**
	 * Whether address validation is active.
	 *
	 * @return bool
	 */
	public function is_validation_enabled(): bool {
		return $this->is_enabled();
	}

	/**
	 * Whether Google Places Autocomplete is enabled.
	 *
	 * @return bool
	 */
	public function is_autocomplete_enabled(): bool {
		return $this->is_enabled()
			&& 'yes' === $this->get( 'autocomplete_enabled', 'no' )
			&& '' !== $this->google_api_key();
	}

	/**
	 * Whether autocomplete is toggled on (even if key is missing).
	 *
	 * @return bool
	 */
	public function is_autocomplete_toggle_enabled(): bool {
		return $this->is_enabled() && 'yes' === $this->get( 'autocomplete_enabled', 'no' );
	}

	/**
	 * Stored Google API key (never log or return to the admin app unmasked).
	 *
	 * @return string
	 */
	public function google_api_key(): string {
		return (string) $this->get( 'google_api_key', '' );
	}

	/**
	 * Autocomplete country restriction list.
	 *
	 * @return string[]
	 */
	public function autocomplete_countries(): array {
		$countries = $this->get( 'autocomplete_countries', array() );

		return is_array( $countries ) ? Sanitizer::country_codes( $countries ) : array();
	}

	/**
	 * Resolve the active validation mode.
	 *
	 * @return string warn|block
	 */
	public function validation_mode(): string {
		$mode = (string) $this->get( 'validation_mode', 'warn' );

		return in_array( $mode, self::VALIDATION_MODES, true ) ? $mode : 'warn';
	}

	/**
	 * Whether shipping addresses should be validated.
	 *
	 * @return bool
	 */
	public function is_validate_shipping_enabled(): bool {
		return $this->is_validation_enabled() && 'yes' === $this->get( 'validate_shipping_address', 'yes' );
	}

	/**
	 * Whether billing addresses should be validated.
	 *
	 * @return bool
	 */
	public function is_validate_billing_enabled(): bool {
		return $this->is_validation_enabled() && 'yes' === $this->get( 'validate_billing_address', 'no' );
	}

	/**
	 * Whether a local check is enabled.
	 *
	 * @param string $key Check setting key.
	 *
	 * @return bool
	 */
	public function is_check_enabled( string $key ): bool {
		return 'yes' === $this->get( $key, 'no' );
	}

	/**
	 * Whether private order notes should be added when a check triggers.
	 *
	 * @return bool
	 */
	public function should_add_order_validation_notes(): bool {
		return 'yes' === $this->get( 'order_add_validation_notes', 'yes' );
	}

	/**
	 * Resolve a customer-facing message template.
	 *
	 * @param string $key Message key.
	 *
	 * @return string
	 */
	public function message( string $key ): string {
		$messages = $this->get( 'messages', array() );
		if ( ! is_array( $messages ) ) {
			$messages = array();
		}

		$fallbacks = array(
			'missing_house_number'      => 'validation_blocked',
			'country_postcode_mismatch' => 'validation_blocked',
			'po_box_blocked'            => 'validation_blocked',
			'locker_blocked'            => 'validation_blocked',
			'parcel_locker_detected'    => 'locker_blocked',
		);

		if ( isset( $messages[ $key ] ) && '' !== (string) $messages[ $key ] ) {
			return (string) $messages[ $key ];
		}

		if ( isset( $fallbacks[ $key ], $messages[ $fallbacks[ $key ] ] ) ) {
			return (string) $messages[ $fallbacks[ $key ] ];
		}

		return '';
	}

	/**
	 * Format a message template with placeholder values.
	 *
	 * @param string               $key     Message key.
	 * @param array<string,string> $context Placeholder values.
	 *
	 * @return string
	 */
	public function format_message( string $key, array $context = array() ): string {
		return MessageFormatter::format( $this->message( $key ), $context );
	}

	/**
	 * Normalize stored settings to a consistent shape.
	 *
	 * @param array<string,mixed> $settings Raw settings.
	 *
	 * @return array<string,mixed>
	 */
	public function normalize( array $settings ): array {
		$defaults = $this->defaults();
		$messages = isset( $settings['messages'] ) && is_array( $settings['messages'] )
			? wp_parse_args( $settings['messages'], $defaults['messages'] )
			: $defaults['messages'];

		$settings['messages'] = array_map(
			static function ( $message ) {
				return sanitize_textarea_field( (string) $message );
			},
			$messages
		);

		$mode = isset( $settings['validation_mode'] ) ? (string) $settings['validation_mode'] : 'warn';
		if ( ! in_array( $mode, self::VALIDATION_MODES, true ) ) {
			$settings['validation_mode'] = 'warn';
		}

		foreach ( array(
			'plugin_enabled',
			'validate_shipping_address',
			'validate_billing_address',
			'autocomplete_enabled',
			'check_missing_house_number',
			'check_po_box',
			'check_parcel_locker',
			'check_postcode_format',
			'order_add_validation_notes',
		) as $checkbox ) {
			$settings[ $checkbox ] = Sanitizer::checkbox( $settings[ $checkbox ] ?? 'no' );
		}

		$settings['google_api_key']         = Sanitizer::credential( $settings['google_api_key'] ?? '' );
		$settings['autocomplete_countries'] = Sanitizer::country_codes( $settings['autocomplete_countries'] ?? array() );

		return $settings;
	}

	/**
	 * Return settings safe for the admin app (credentials masked).
	 *
	 * @return array<string,mixed>
	 */
	public function for_app(): array {
		return $this->mask_credentials( $this->all() );
	}

	/**
	 * Whether a posted credential value is a masked placeholder.
	 *
	 * @param mixed $value Posted value.
	 *
	 * @return bool
	 */
	public static function is_masked_value( $value ): bool {
		$value = (string) $value;

		if ( self::MASKED_VALUE === $value || '********' === $value ) {
			return true;
		}

		return '' !== $value && (bool) preg_match( '/^[•*]+$/u', $value );
	}

	/**
	 * Mask stored credentials for admin responses.
	 *
	 * @param array<string,mixed> $settings Settings.
	 *
	 * @return array<string,mixed>
	 */
	private function mask_credentials( array $settings ): array {
		$key = (string) ( $settings['google_api_key'] ?? '' );
		$settings['google_api_key'] = '' !== $key ? self::MASKED_VALUE : '';

		return $settings;
	}
}
