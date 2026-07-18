<?php
/**
 * Google Places Autocomplete service for Lite.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Domain\Google;

use WPRuby\AddressGuard\Domain\Autocomplete\AutocompleteCountryContext;
use WPRuby\AddressGuard\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GooglePlacesService
 *
 * Server-side Google Places Autocomplete and Place Details for checkout.
 */
class GooglePlacesService {

	const PROVIDER_ID = 'google_places';
	const TEST_QUERY  = '1600 Amphitheatre Parkway';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Address mapper.
	 *
	 * @var GoogleAddressMapper
	 */
	private $mapper;

	/**
	 * Optional injected HTTP client (tests).
	 *
	 * @var GooglePlacesClient|null
	 */
	private $client;

	/**
	 * Constructor.
	 *
	 * @param Settings                  $settings Settings accessor.
	 * @param GoogleAddressMapper|null  $mapper   Optional mapper.
	 * @param GooglePlacesClient|null   $client   Optional client override.
	 */
	public function __construct( Settings $settings, ?GoogleAddressMapper $mapper = null, ?GooglePlacesClient $client = null ) {
		$this->settings = $settings;
		$this->mapper   = $mapper ?? new GoogleAddressMapper();
		$this->client   = $client;
	}

	/**
	 * Search for address suggestions.
	 *
	 * Autocomplete query = typed text only.
	 * Autocomplete context = selected checkout country (preferred) or configured countries.
	 *
	 * @param string              $query   Customer typed query.
	 * @param array<string,mixed> $context Context with optional country / preferred_countries.
	 *
	 * @return array<int,array<string,mixed>>
	 *
	 * @throws GoogleApiException When Google returns an error.
	 */
	public function search( string $query, array $context = array() ): array {
		$query = trim( sanitize_text_field( $query ) );
		if ( '' === $query || ! $this->client()->has_api_key() ) {
			return array();
		}

		$body = array(
			'input'        => $query,
			'languageCode' => $this->language_code(),
		);

		$regions = AutocompleteCountryContext::region_codes( $context );
		if ( ! empty( $regions ) ) {
			$body['includedRegionCodes'] = $regions;
		}

		$response = $this->client()->autocomplete( $body );

		return $this->normalize_suggestions( $response, AutocompleteCountryContext::primary_country( $context ) );
	}

	/**
	 * Resolve place details into normalized WooCommerce fields.
	 *
	 * @param string $place_id Google place ID.
	 *
	 * @return array<string,mixed>
	 *
	 * @throws GoogleApiException When Google returns an error.
	 */
	public function get_details( string $place_id ): array {
		if ( ! $this->client()->has_api_key() ) {
			throw new GoogleApiException(
				'missing_api_key',
				esc_html( GoogleApiException::admin_message_for_code( 'missing_api_key' ) )
			);
		}

		$response   = $this->client()->place_details( $place_id );
		$components = isset( $response['addressComponents'] ) && is_array( $response['addressComponents'] )
			? $response['addressComponents']
			: array();

		if ( empty( $components ) ) {
			throw new GoogleApiException(
				'no_address_components',
				esc_html( GoogleApiException::admin_message_for_code( 'no_address_components' ) )
			);
		}

		$formatted = (string) ( $response['formattedAddress'] ?? '' );
		$mapped    = $this->mapper->from_places_components(
			$components,
			$formatted,
			(string) ( $response['id'] ?? $place_id )
		);

		$address = $this->mapper->to_woocommerce_address( $mapped );

		return array_merge(
			$address,
			array(
				'formatted'   => $mapped['formatted'],
				'provider'    => self::PROVIDER_ID,
				'provider_id' => $mapped['place_id'],
				'place_id'    => $mapped['place_id'],
				'address'     => $address,
			)
		);
	}

	/**
	 * Lightweight connection test for the admin UI.
	 *
	 * @return array{success:bool,message:string}
	 */
	public function test_connection(): array {
		if ( ! $this->client()->has_api_key() ) {
			return array(
				'success' => false,
				'message' => __( 'Google Places Autocomplete could not be reached. Check your API key and enabled Google APIs.', 'checkout-address-guard-for-woocommerce' ),
			);
		}

		try {
			$body = array(
				'input'        => self::TEST_QUERY,
				'languageCode' => $this->language_code(),
			);

			$regions = AutocompleteCountryContext::region_codes(
				array(
					'preferred_countries' => $this->settings->autocomplete_countries(),
				)
			);
			if ( ! empty( $regions ) ) {
				$body['includedRegionCodes'] = $regions;
			}

			$response    = $this->client()->autocomplete( $body );
			$suggestions = $this->normalize_suggestions( $response, '' );

			if ( empty( $suggestions ) ) {
				return array(
					'success' => false,
					'message' => __( 'Google Places Autocomplete could not be reached. Check your API key and enabled Google APIs.', 'checkout-address-guard-for-woocommerce' ),
				);
			}

			return array(
				'success' => true,
				'message' => __( 'Google Places Autocomplete is connected.', 'checkout-address-guard-for-woocommerce' ),
			);
		} catch ( GoogleApiException $exception ) {
			unset( $exception );

			return array(
				'success' => false,
				'message' => __( 'Google Places Autocomplete could not be reached. Check your API key and enabled Google APIs.', 'checkout-address-guard-for-woocommerce' ),
			);
		}
	}

	/**
	 * Normalize Google autocomplete suggestions.
	 *
	 * @param array<string,mixed> $response Google response.
	 * @param string              $country  Optional country meta.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_suggestions( array $response, string $country = '' ): array {
		$suggestions = isset( $response['suggestions'] ) && is_array( $response['suggestions'] )
			? $response['suggestions']
			: array();

		$normalized = array();
		$country    = strtoupper( sanitize_text_field( $country ) );

		foreach ( $suggestions as $suggestion ) {
			if ( ! is_array( $suggestion ) ) {
				continue;
			}

			$prediction = isset( $suggestion['placePrediction'] ) && is_array( $suggestion['placePrediction'] )
				? $suggestion['placePrediction']
				: $suggestion;

			$place_id = (string) ( $prediction['placeId'] ?? $prediction['place_id'] ?? '' );
			$label    = '';

			if ( isset( $prediction['text'] ) && is_array( $prediction['text'] ) ) {
				$label = (string) ( $prediction['text']['text'] ?? '' );
			} elseif ( isset( $prediction['description'] ) ) {
				$label = (string) $prediction['description'];
			}

			$label = sanitize_text_field( $label );
			if ( '' === $place_id || '' === $label ) {
				continue;
			}

			$item = array(
				'id'                => $place_id,
				'label'             => $label,
				'provider'          => self::PROVIDER_ID,
				'requires_details'  => true,
				'meta'              => array(),
			);

			if ( preg_match( '/^[A-Z]{2}$/', $country ) ) {
				$item['meta']['country'] = $country;
			}

			$normalized[] = $item;
		}

		return $normalized;
	}

	/**
	 * Resolve language code for Google requests.
	 *
	 * @return string
	 */
	private function language_code(): string {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$locale = str_replace( '_', '-', (string) $locale );
		$parts  = explode( '-', $locale );

		return sanitize_text_field( strtolower( (string) ( $parts[0] ?? 'en' ) ) );
	}

	/**
	 * Resolve the HTTP client.
	 *
	 * @return GooglePlacesClient
	 */
	private function client(): GooglePlacesClient {
		if ( null === $this->client ) {
			$this->client = new GooglePlacesClient( $this->settings->google_api_key() );
		}

		return $this->client;
	}
}
