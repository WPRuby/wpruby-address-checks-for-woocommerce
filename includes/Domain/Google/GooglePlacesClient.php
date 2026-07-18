<?php
/**
 * Server-side Google Places API (New) HTTP client.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Domain\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GooglePlacesClient
 *
 * Makes authenticated requests to Google Places Autocomplete and Place Details.
 * Does not call Google Address Validation.
 */
class GooglePlacesClient {

	const PLACES_AUTOCOMPLETE_URL = 'https://places.googleapis.com/v1/places:autocomplete';
	const PLACES_DETAILS_BASE_URL = 'https://places.googleapis.com/v1/places/';
	const TIMEOUT                 = 12;

	/**
	 * Google API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Constructor.
	 *
	 * @param string $api_key Google Maps Platform API key.
	 */
	public function __construct( string $api_key ) {
		$this->api_key = trim( $api_key );
	}

	/**
	 * Whether an API key is configured.
	 *
	 * @return bool
	 */
	public function has_api_key(): bool {
		return '' !== $this->api_key;
	}

	/**
	 * Places Autocomplete (New) request.
	 *
	 * @param array<string,mixed> $body Request body.
	 *
	 * @return array<string,mixed>
	 *
	 * @throws GoogleApiException When the request fails.
	 */
	public function autocomplete( array $body ): array {
		/**
		 * Filter the Google Places autocomplete request body.
		 *
		 * @param array<string,mixed> $body Request body.
		 */
		$body = (array) apply_filters( 'address_guard_google_autocomplete_request', $body );

		$response = $this->request(
			'POST',
			self::PLACES_AUTOCOMPLETE_URL,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		/**
		 * Filter the Google Places autocomplete response.
		 *
		 * @param array<string,mixed> $response Decoded response.
		 * @param array<string,mixed> $body     Request body.
		 */
		return (array) apply_filters( 'address_guard_google_autocomplete_response', $response, $body );
	}

	/**
	 * Place Details (New) request.
	 *
	 * @param string $place_id Google place ID.
	 *
	 * @return array<string,mixed>
	 *
	 * @throws GoogleApiException When the request fails.
	 */
	public function place_details( string $place_id ): array {
		$place_id = sanitize_text_field( $place_id );
		if ( '' === $place_id ) {
			throw new GoogleApiException(
				'invalid_place_id',
				esc_html__( 'A valid place ID is required.', 'address-guard-for-woocommerce' )
			);
		}

		$url = self::PLACES_DETAILS_BASE_URL . rawurlencode( $place_id );

		/**
		 * Filter the Place Details request URL before it is sent.
		 *
		 * @param string $url      Request URL.
		 * @param string $place_id Place ID.
		 */
		$url = (string) apply_filters( 'address_guard_google_details_request', $url, $place_id );

		$response = $this->request(
			'GET',
			$url,
			array(
				'headers' => array(
					'X-Goog-FieldMask' => 'id,formattedAddress,addressComponents',
				),
			)
		);

		/**
		 * Filter the Google Place Details response.
		 *
		 * @param array<string,mixed> $response Decoded response.
		 * @param string              $place_id Place ID.
		 */
		return (array) apply_filters( 'address_guard_google_details_response', $response, $place_id );
	}

	/**
	 * Perform an HTTP request against Google Places APIs.
	 *
	 * @param string              $method  HTTP method.
	 * @param string              $url     Request URL.
	 * @param array<string,mixed> $options Request options.
	 *
	 * @return array<string,mixed>
	 *
	 * @throws GoogleApiException When the request fails.
	 */
	private function request( string $method, string $url, array $options = array() ): array {
		if ( ! $this->has_api_key() ) {
			throw new GoogleApiException(
				'missing_api_key',
				esc_html__( 'Google API key is missing.', 'address-guard-for-woocommerce' )
			);
		}

		/**
		 * Filter the Google API key used for Places requests.
		 *
		 * @param string $api_key API key.
		 */
		$api_key = (string) apply_filters( 'address_guard_google_api_key', $this->api_key );

		$headers = array(
			'X-Goog-Api-Key' => $api_key,
		);

		if ( isset( $options['headers'] ) && is_array( $options['headers'] ) ) {
			$headers = array_merge( $headers, $options['headers'] );
		}

		$args = array(
			'method'  => $method,
			'timeout' => self::TIMEOUT,
			'headers' => $headers,
		);

		if ( isset( $options['body'] ) ) {
			$args['body'] = $options['body'];
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			throw new GoogleApiException(
				'network_error',
				esc_html__( 'Could not reach Google APIs. Check your server network connection.', 'address-guard-for-woocommerce' ),
				array(
					'http_code' => 0,
				)
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		$data = is_array( $data ) ? $data : array();

		if ( $code >= 200 && $code < 300 ) {
			return $data;
		}

		$exception = GoogleApiException::from_response( $data, $code );
		throw $exception;
	}
}
