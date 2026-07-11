<?php
/**
 * WordPress and WooCommerce stubs for PHPUnit.
 *
 * @package WPRuby\AddressGuard\Tests
 */

$GLOBALS['wpruby_ag_test_filters']  = array();
$GLOBALS['wpruby_ag_test_actions']  = array();
$GLOBALS['wpruby_ag_test_user_caps'] = array();
$GLOBALS['wpruby_ag_test_nonces']   = array();
$GLOBALS['wpruby_ag_test_wc']       = null;
$GLOBALS['wpruby_ag_test_transients'] = array();
$GLOBALS['wpruby_ag_test_http_responses'] = array();
$GLOBALS['wpruby_ag_test_http_requests']  = array();

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		unset( $domain );
		return esc_html( $text );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return (int) ( $GLOBALS['wpruby_ag_test_user_id'] ?? 1 );
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		return $GLOBALS['wpruby_ag_test_transients'][ $transient ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) {
		unset( $expiration );
		$GLOBALS['wpruby_ag_test_transients'][ $transient ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) {
		unset( $GLOBALS['wpruby_ag_test_transients'][ $transient ] );
		return true;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $value ) {
		return trim( preg_replace( '/[\r\n\t ]+/', ' ', wp_strip_all_tags( (string) $value ) ) );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		unset( $options, $depth );
		return json_encode( $data );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		return ! empty( $GLOBALS['wpruby_ag_test_user_caps'][ $capability ] );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action ) {
		unset( $action );
		return in_array( (string) $nonce, $GLOBALS['wpruby_ag_test_nonces'], true );
	}
}

if ( ! function_exists( 'determine_locale' ) ) {
	function determine_locale() {
		return (string) ( $GLOBALS['wpruby_ag_test_locale'] ?? 'en_US' );
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook_name, ...$args ) {
		unset( $hook_name, $args );
	}
}

if ( ! function_exists( 'load_plugin_textdomain' ) ) {
	function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {
		unset( $domain, $deprecated, $plugin_rel_path );
		return true;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return ! empty( $GLOBALS['wpruby_ag_test_is_admin'] );
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	function register_rest_route( $namespace, $route, $args = array(), $override = false ) {
		unset( $override );
		$GLOBALS['wpruby_ag_test_rest_routes'][] = array(
			'namespace' => $namespace,
			'route'     => $route,
			'args'      => $args,
		);
		return true;
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' ) . '/';
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		private $params  = array();
		private $headers = array();

		public function __construct( $method = 'GET', $route = '', $params = array() ) {
			unset( $method, $route );
			$this->params = is_array( $params ) ? $params : array();
		}

		public function set_param( $key, $value ) {
			$this->params[ $key ] = $value;
		}

		public function get_param( $key ) {
			return $this->params[ $key ] ?? null;
		}

		public function get_params() {
			return $this->params;
		}

		public function get_json_params() {
			return $this->params;
		}

		public function set_header( $key, $value ) {
			$this->headers[ strtolower( $key ) ] = $value;
		}

		public function get_header( $key ) {
			return $this->headers[ strtolower( $key ) ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		private $data;
		private $status;

		public function __construct( $data = null, $status = 200 ) {
			$this->data   = $data;
			$this->status = (int) $status;
		}

		public function get_data() {
			return $this->data;
		}

		public function get_status() {
			return $this->status;
		}
	}
}

if ( ! defined( 'WP_REST_Server' ) ) {
	class WP_REST_Server {
		const READABLE   = 'GET';
		const CREATABLE  = 'POST';
		const EDITABLE   = 'POST, PUT, PATCH';
		const DELETABLE  = 'DELETE';
		const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
	}
}

if ( ! class_exists( 'WC_Order' ) ) {
	class WC_Order {
		private $meta = array();
		private $addresses = array(
			'billing'  => array(),
			'shipping' => array(),
		);
		private $notes = array();

		public function get_id() {
			return 123;
		}

		public function update_meta_data( $key, $value ) {
			$this->meta[ $key ] = $value;
		}

		public function get_meta( $key, $single = true ) {
			unset( $single );
			return $this->meta[ $key ] ?? '';
		}

		public function save() {
			return $this->get_id();
		}

		public function set_address_field( $type, $field, $value ) {
			$this->addresses[ $type ][ $field ] = $value;
		}

		public function get_billing_address_1() {
			return (string) ( $this->addresses['billing']['address_1'] ?? '' );
		}

		public function get_billing_address_2() {
			return (string) ( $this->addresses['billing']['address_2'] ?? '' );
		}

		public function get_billing_city() {
			return (string) ( $this->addresses['billing']['city'] ?? '' );
		}

		public function get_billing_state() {
			return (string) ( $this->addresses['billing']['state'] ?? '' );
		}

		public function get_billing_postcode() {
			return (string) ( $this->addresses['billing']['postcode'] ?? '' );
		}

		public function get_billing_country() {
			return (string) ( $this->addresses['billing']['country'] ?? '' );
		}

		public function get_shipping_address_1() {
			return (string) ( $this->addresses['shipping']['address_1'] ?? '' );
		}

		public function get_shipping_address_2() {
			return (string) ( $this->addresses['shipping']['address_2'] ?? '' );
		}

		public function get_shipping_city() {
			return (string) ( $this->addresses['shipping']['city'] ?? '' );
		}

		public function get_shipping_state() {
			return (string) ( $this->addresses['shipping']['state'] ?? '' );
		}

		public function get_shipping_postcode() {
			return (string) ( $this->addresses['shipping']['postcode'] ?? '' );
		}

		public function get_shipping_country() {
			return (string) ( $this->addresses['shipping']['country'] ?? '' );
		}

		public function add_order_note( $note, $is_customer_note = false, $added_by_user = false ) {
			$this->notes[] = array(
				'content'           => (string) $note,
				'is_customer_note'  => (bool) $is_customer_note,
				'added_by_user'     => (bool) $added_by_user,
			);
		}

		public function get_notes() {
			return $this->notes;
		}
	}
}

if ( ! function_exists( 'wc_get_order' ) ) {
	function wc_get_order( $order_id ) {
		unset( $order_id );
		return isset( $GLOBALS['wpruby_ag_test_wc_order'] ) ? $GLOBALS['wpruby_ag_test_wc_order'] : false;
	}
}

/**
 * Enable the WooCommerce class stub for tests that boot the plugin.
 *
 * @return void
 */
function wpruby_ag_test_enable_woocommerce(): void {
	if ( ! class_exists( 'WooCommerce', false ) ) {
		class WooCommerce {}
	}
}

/**
 * Install a minimal WooCommerce stub with optional required address fields.
 *
 * @param array<int,string> $required_fields Field keys without prefix.
 *
 * @return void
 */
function wpruby_ag_test_setup_wc( array $required_fields = array() ): void {
	$countries = new class( $required_fields ) {
		private $required;

		public function __construct( array $required ) {
			$this->required = $required;
		}

		public function get_address_fields( $country, $prefix ) {
			unset( $country );
			$fields = array();
			foreach ( $this->required as $field ) {
				$fields[ $prefix . $field ] = array(
					'required' => true,
					'hidden'   => false,
				);
			}
			return $fields;
		}
	};

	$wc              = new stdClass();
	$wc->countries   = $countries;
	$wc->cart        = null;
	$wc->session     = null;
	$GLOBALS['wpruby_ag_test_wc'] = $wc;

	wpruby_ag_test_enable_woocommerce();

	if ( ! function_exists( 'WC' ) ) {
		function WC() {
			return $GLOBALS['wpruby_ag_test_wc'];
		}
	}
}

if ( ! function_exists( 'rest_authorization_required_code' ) ) {
	function rest_authorization_required_code() {
		return 401;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $content ) {
		return wp_strip_all_tags( (string) $content );
	}
}

if ( ! function_exists( 'wc_add_notice' ) ) {
	function wc_add_notice( $message, $notice_type = 'notice' ) {
		unset( $notice_type );
		$GLOBALS['wpruby_ag_test_wc_notices'][] = (string) $message;
	}
}

if ( ! function_exists( 'is_checkout' ) ) {
	function is_checkout() {
		return ! empty( $GLOBALS['wpruby_ag_test_is_checkout'] );
	}
}

/**
 * Install a WooCommerce cart stub for checkout validation tests.
 *
 * @return void
 */
function wpruby_ag_test_setup_cart(): void {
	wpruby_ag_test_setup_wc();

	$cart = new class() {
		public function needs_shipping_address() {
			return true;
		}
	};

	$GLOBALS['wpruby_ag_test_wc']->cart = $cart;
}

/**
 * Install a WooCommerce session stub.
 *
 * @return void
 */
function wpruby_ag_test_setup_wc_session(): void {
	wpruby_ag_test_setup_wc();

	$session = new class() {
		private $data = array();

		public function get( $key, $default = null ) {
			return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default;
		}

		public function set( $key, $value ) {
			$this->data[ $key ] = $value;
		}
	};

	$GLOBALS['wpruby_ag_test_wc']->session = $session;
}

/**
 * Create a test order stub.
 *
 * @return WC_Order
 */
function wpruby_ag_test_create_order(): WC_Order {
	$order = new WC_Order();
	$GLOBALS['wpruby_ag_test_wc_order'] = $order;

	return $order;
}

/**
 * Queue a mocked HTTP response (or WP_Error) for the next wp_remote_* call.
 *
 * @param mixed $response Either a wp_remote_* style array, a WP_Error, or a callable( $url, $args ).
 *
 * @return void
 */
function wpruby_ag_test_queue_http_response( $response ): void {
	$GLOBALS['wpruby_ag_test_http_responses'][] = $response;
}

/**
 * Return the raw request log recorded by the HTTP stubs (url + args pairs).
 *
 * @return array<int,array{url:string,args:array}>
 */
function wpruby_ag_test_http_requests(): array {
	return $GLOBALS['wpruby_ag_test_http_requests'] ?? array();
}

/**
 * Resolve the next queued mock HTTP response.
 *
 * @param string $url  Request URL.
 * @param array  $args Request args.
 *
 * @return array|WP_Error
 */
function wpruby_ag_test_dequeue_http_response( string $url, array $args ) {
	$GLOBALS['wpruby_ag_test_http_requests'][] = array(
		'url'  => $url,
		'args' => $args,
	);

	$queue = &$GLOBALS['wpruby_ag_test_http_responses'];
	if ( empty( $queue ) ) {
		return new WP_Error( 'http_request_failed', 'No mocked HTTP response queued.' );
	}

	$next = array_shift( $queue );
	if ( is_callable( $next ) ) {
		return $next( $url, $args );
	}

	return $next;
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) {
		return wpruby_ag_test_dequeue_http_response( $url, $args );
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args = array() ) {
		return wpruby_ag_test_dequeue_http_response( $url, $args );
	}
}

if ( ! function_exists( 'wp_remote_request' ) ) {
	function wp_remote_request( $url, $args = array() ) {
		return wpruby_ag_test_dequeue_http_response( $url, $args );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return 0;
		}

		return (int) ( $response['response']['code'] ?? 0 );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return '';
		}

		return (string) ( $response['body'] ?? '' );
	}
}

if ( ! function_exists( 'has_block' ) ) {
	function has_block( $block_name, $post = null ) {
		unset( $post );

		if ( 'woocommerce/classic-shortcode' === $block_name ) {
			return ! empty( $GLOBALS['wpruby_ag_test_checkout_classic_block'] );
		}

		if ( 'woocommerce/checkout' === $block_name ) {
			return ! empty( $GLOBALS['wpruby_ag_test_checkout_blocks'] );
		}

		return false;
	}
}

if ( ! function_exists( 'wc_get_page_id' ) ) {
	function wc_get_page_id( $page ) {
		unset( $page );
		return (int) ( $GLOBALS['wpruby_ag_test_checkout_page_id'] ?? 10 );
	}
}

if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
	function is_wc_endpoint_url( $endpoint = '' ) {
		return ( $GLOBALS['wpruby_ag_test_wc_endpoint'] ?? '' ) === $endpoint;
	}
}

if ( ! function_exists( 'has_shortcode' ) ) {
	function has_shortcode( $content, $tag ) {
		unset( $content );
		return ! empty( $GLOBALS['wpruby_ag_test_has_checkout_shortcode'] ) && 'woocommerce_checkout' === $tag;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post = null ) {
		unset( $post );
		return $GLOBALS['wpruby_ag_test_checkout_post'] ?? false;
	}
}
