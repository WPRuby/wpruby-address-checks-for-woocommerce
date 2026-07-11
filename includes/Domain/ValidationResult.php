<?php
/**
 * Address validation result value object.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ValidationResult
 *
 * Captures the outcome of an address validation attempt.
 */
class ValidationResult {

	const STATUS_VALID          = 'valid';
	const STATUS_INVALID        = 'invalid';
	const STATUS_UNVERIFIED     = 'unverified';
	const STATUS_CORRECTED      = 'corrected';
	const STATUS_PROVIDER_ERROR = 'provider_error';
	const STATUS_SKIPPED        = 'skipped';

	/**
	 * Result data.
	 *
	 * @var array<string,mixed>
	 */
	private $data;

	/**
	 * Constructor.
	 *
	 * @param array<string,mixed> $data Result data.
	 */
	public function __construct( array $data = array() ) {
		$this->data = wp_parse_args( $data, self::defaults() );
		$this->data['timestamp'] = $this->normalize_timestamp( $this->data['timestamp'] );
	}

	/**
	 * Default result shape.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'status'               => self::STATUS_SKIPPED,
			'confidence'           => null,
			'normalized_address'   => null,
			'original_address'     => null,
			'warnings'             => array(),
			'errors'               => array(),
			'provider'             => '',
			'raw_response_summary' => '',
			'timestamp'            => gmdate( 'c' ),
			'code'                 => '',
			'message'              => '',
			'suggested_address'    => null,
			'issues'               => array(),
		);
	}

	/**
	 * Get the raw data array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return $this->data;
	}

	/**
	 * Get a safe array for REST and frontend consumers.
	 *
	 * @return array<string,mixed>
	 */
	public function to_public_array(): array {
		$data = $this->data;

		unset(
			$data['raw_response_summary'],
			$data['errors'],
			$data['warnings'],
			$data['issues'],
			$data['rules']
		);

		return $data;
	}

	/**
	 * Validation status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return (string) $this->data['status'];
	}

	/**
	 * Customer-facing message.
	 *
	 * @return string
	 */
	public function get_message(): string {
		return (string) $this->data['message'];
	}

	/**
	 * Whether validation was skipped.
	 *
	 * @return bool
	 */
	public function is_skipped(): bool {
		return self::STATUS_SKIPPED === $this->get_status();
	}

	/**
	 * Whether the address is considered deliverable.
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return self::STATUS_VALID === $this->get_status();
	}

	/**
	 * Whether checkout should be blocked in block mode.
	 *
	 * @return bool
	 */
	public function should_block_checkout(): bool {
		return self::STATUS_INVALID === $this->get_status();
	}

	/**
	 * Whether checkout should show a soft warning in warn mode.
	 *
	 * @return bool
	 */
	public function should_warn(): bool {
		return in_array(
			$this->get_status(),
			array(
				self::STATUS_INVALID,
				self::STATUS_UNVERIFIED,
				self::STATUS_CORRECTED,
				self::STATUS_PROVIDER_ERROR,
			),
			true
		);
	}

	/**
	 * Whether a corrected/suggested address is available.
	 *
	 * @return bool
	 */
	public function has_suggestion(): bool {
		$suggested = $this->data['normalized_address'] ?? $this->data['suggested_address'] ?? null;

		return is_array( $suggested ) && ! empty( $suggested );
	}

	/**
	 * Get the suggested or normalized address array.
	 *
	 * @return array<string,string>|null
	 */
	public function get_suggested_address(): ?array {
		$suggested = $this->data['normalized_address'] ?? $this->data['suggested_address'] ?? null;

		return is_array( $suggested ) ? $suggested : null;
	}

	/**
	 * Normalize provider results into the domain shape.
	 *
	 * @param ValidationResult $result  Provider result.
	 * @param Address          $address Original address.
	 *
	 * @return self
	 */
	public static function from_provider_result( ValidationResult $result, Address $address ): self {
		$data = $result->to_array();

		if ( empty( $data['original_address'] ) ) {
			$data['original_address'] = $address->to_array();
		}

		if ( ! empty( $data['suggested_address'] ) && empty( $data['normalized_address'] ) ) {
			$data['normalized_address'] = $data['suggested_address'];
		}

		if ( self::STATUS_CORRECTED === $data['status'] && empty( $data['normalized_address'] ) ) {
			$data['status'] = self::STATUS_UNVERIFIED;
		}

		return new self( $data );
	}

	/**
	 * Normalize a timestamp value.
	 *
	 * @param mixed $timestamp Raw timestamp.
	 *
	 * @return string
	 */
	private function normalize_timestamp( $timestamp ): string {
		if ( is_numeric( $timestamp ) ) {
			return gmdate( 'c', (int) $timestamp );
		}

		$timestamp = sanitize_text_field( (string) $timestamp );
		if ( '' === $timestamp ) {
			return gmdate( 'c' );
		}

		return $timestamp;
	}
}
