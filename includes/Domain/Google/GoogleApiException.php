<?php
/**
 * Google API error wrapper.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Domain\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GoogleApiException
 */
class GoogleApiException extends \Exception {

	/**
	 * Safe error code for logging/admin display.
	 *
	 * @var string
	 */
	private $error_code;

	/**
	 * Extra non-sensitive context.
	 *
	 * @var array<string,mixed>
	 */
	private $context;

	/**
	 * Constructor.
	 *
	 * @param string              $error_code Safe error code.
	 * @param string              $message    Admin-safe message.
	 * @param array<string,mixed> $context    Optional context.
	 */
	public function __construct( string $error_code, string $message, array $context = array() ) {
		parent::__construct( $message );
		$this->error_code = sanitize_key( $error_code );
		$this->context    = $context;
	}

	/**
	 * Safe error code.
	 *
	 * @return string
	 */
	public function get_error_code(): string {
		return $this->error_code;
	}

	/**
	 * Non-sensitive context.
	 *
	 * @return array<string,mixed>
	 */
	public function get_context(): array {
		return $this->context;
	}

	/**
	 * Build an exception from a Google error response.
	 *
	 * @param array<string,mixed> $data      Decoded response body.
	 * @param int                 $http_code HTTP status code.
	 *
	 * @return self
	 */
	public static function from_response( array $data, int $http_code ): self {
		$status  = isset( $data['error']['status'] ) ? (string) $data['error']['status'] : '';
		$message = isset( $data['error']['message'] ) ? (string) $data['error']['message'] : '';
		$reason  = '';

		if ( isset( $data['error']['details'] ) && is_array( $data['error']['details'] ) ) {
			foreach ( $data['error']['details'] as $detail ) {
				if ( ! is_array( $detail ) ) {
					continue;
				}
				if ( isset( $detail['reason'] ) && '' !== (string) $detail['reason'] ) {
					$reason = (string) $detail['reason'];
					break;
				}
			}
		}

		$code = self::map_error_code( $status, $reason, $http_code, $message );

		return new self(
			$code,
			self::admin_message_for_code( $code ),
			array(
				'http_code' => $http_code,
				'status'    => sanitize_key( $status ),
				'reason'    => sanitize_key( $reason ),
			)
		);
	}

	/**
	 * Map Google error signals to internal codes.
	 *
	 * @param string $status    Google status string.
	 * @param string $reason    Google reason string.
	 * @param int    $http_code HTTP status code.
	 * @param string $message   Raw Google message (not returned to customers).
	 *
	 * @return string
	 */
	private static function map_error_code( string $status, string $reason, int $http_code, string $message ): string {
		unset( $message );

		$haystack = strtolower( $status . ' ' . $reason );

		if ( 429 === $http_code || false !== strpos( $haystack, 'quota' ) || false !== strpos( $haystack, 'rate' ) ) {
			return 'quota_exceeded';
		}

		if ( false !== strpos( $haystack, 'api_key' ) || ( false !== strpos( $haystack, 'invalid' ) && false !== strpos( $haystack, 'key' ) ) ) {
			return 'invalid_api_key';
		}

		if ( false !== strpos( $haystack, 'permission' ) || false !== strpos( $haystack, 'denied' ) || false !== strpos( $haystack, 'disabled' ) ) {
			return 'api_not_enabled';
		}

		if ( 400 === $http_code || false !== strpos( $haystack, 'invalid_argument' ) ) {
			return 'invalid_request';
		}

		if ( $http_code >= 500 ) {
			return 'upstream_error';
		}

		return 'request_failed';
	}

	/**
	 * Admin-safe message for an internal error code.
	 *
	 * @param string $code Internal error code.
	 *
	 * @return string
	 */
	public static function admin_message_for_code( string $code ): string {
		switch ( $code ) {
			case 'missing_api_key':
				return __( 'Google API key is missing.', 'address-guard-for-woocommerce' );
			case 'invalid_api_key':
				return __( 'Google request failed: API key is invalid or the API is not enabled.', 'address-guard-for-woocommerce' );
			case 'api_not_enabled':
				return __( 'Google request failed: required API is not enabled for this key.', 'address-guard-for-woocommerce' );
			case 'quota_exceeded':
				return __( 'Google request failed: quota exceeded or rate limit reached.', 'address-guard-for-woocommerce' );
			case 'invalid_request':
				return __( 'Google request failed: invalid request parameters.', 'address-guard-for-woocommerce' );
			case 'network_error':
				return __( 'Google request failed: network error.', 'address-guard-for-woocommerce' );
			case 'invalid_place_id':
				return __( 'Google Place Details request failed: invalid place ID.', 'address-guard-for-woocommerce' );
			case 'no_suggestions':
				return __( 'Google Places returned no autocomplete suggestions.', 'address-guard-for-woocommerce' );
			case 'no_address_components':
				return __( 'Google Place Details returned no usable address components.', 'address-guard-for-woocommerce' );
			case 'malformed_response':
				return __( 'Google response could not be parsed.', 'address-guard-for-woocommerce' );
			default:
				return __( 'Google request failed.', 'address-guard-for-woocommerce' );
		}
	}
}
