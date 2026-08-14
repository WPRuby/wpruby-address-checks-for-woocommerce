<?php
/**
 * PHPUnit bootstrap.
 *
 * @package WPRuby\AddressGuard\Tests
 */

if ( ! defined( 'WPRUBY_ADDRESS_CHECKS_TESTING' ) ) {
	define( 'WPRUBY_ADDRESS_CHECKS_TESTING', true );
}

$plugin_root = dirname( __DIR__ );

$composer = $plugin_root . '/vendor/autoload.php';
if ( is_readable( $composer ) ) {
	require_once $composer;
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $plugin_root . '/tests/wordpress/' );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

if ( ! defined( 'WPRUBY_ADDRESS_CHECKS_VERSION' ) ) {
	define( 'WPRUBY_ADDRESS_CHECKS_VERSION', '1.0.1' );
	define( 'WPRUBY_ADDRESS_CHECKS_PLUGIN_FILE', $plugin_root . '/wpruby-address-checks-for-woocommerce.php' );
	define( 'WPRUBY_ADDRESS_CHECKS_PLUGIN_DIR', $plugin_root . '/' );
	define( 'WPRUBY_ADDRESS_CHECKS_PLUGIN_URL', 'https://example.test/wp-content/plugins/wpruby-address-checks-for-woocommerce/' );
	define( 'WPRUBY_ADDRESS_CHECKS_TEXT_DOMAIN', 'wpruby-address-checks-for-woocommerce' );
	define( 'WPRUBY_ADDRESS_CHECKS_BASENAME', 'wpruby-address-checks-for-woocommerce/wpruby-address-checks-for-woocommerce.php' );
	define( 'WPRUBY_ADDRESS_CHECKS_PRO_URL', 'https://wpruby.com/plugin/woocommerce-address-guard-pro/' );
	define( 'WPRUBY_ADDRESS_CHECKS_DOCS_URL', 'https://wpruby.com/knowledgebase_category/woocommerce-address-guard-pro/' );
}

$GLOBALS['wpruby_address_checks_test_options'] = array(
	'date_format'     => 'F j, Y',
	'time_format'     => 'g:i a',
	'timezone_string' => 'UTC',
);

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		} elseif ( ! is_array( $args ) ) {
			parse_str( (string) $args, $args );
		}

		return array_merge( $defaults, $args );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook_name, $value ) {
		unset( $hook_name );
		return $value;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook_name, $callback = null, $priority = 10, $accepted_args = 1 ) {
		unset( $hook_name, $callback, $priority, $accepted_args );
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook_name, $callback = null, $priority = 10, $accepted_args = 1 ) {
		unset( $hook_name, $callback, $priority, $accepted_args );
		return true;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return filter_var( (string) $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * Minimal WordPress add_query_arg stub for unit tests.
	 *
	 * @param mixed ...$args Key/value pair + URL, or associative array + URL.
	 *
	 * @return string
	 */
	function add_query_arg( ...$args ) {
		if ( 3 === count( $args ) ) {
			$key   = (string) $args[0];
			$value = $args[1];
			$url   = (string) $args[2];
			$params = array( $key => $value );
		} elseif ( 2 === count( $args ) && is_array( $args[0] ) ) {
			$params = $args[0];
			$url    = (string) $args[1];
		} else {
			return (string) ( $args[0] ?? '' );
		}

		$parts    = wp_parse_url( $url );
		$base     = '';
		$query    = array();
		$fragment = '';

		if ( ! empty( $parts['scheme'] ) && ! empty( $parts['host'] ) ) {
			$base = $parts['scheme'] . '://' . $parts['host'];
			if ( ! empty( $parts['port'] ) ) {
				$base .= ':' . $parts['port'];
			}
		}

		if ( isset( $parts['path'] ) ) {
			$base .= $parts['path'];
		}

		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
		}

		foreach ( $params as $key => $value ) {
			if ( false === $value || null === $value ) {
				unset( $query[ $key ] );
				continue;
			}
			$query[ $key ] = $value;
		}

		if ( ! empty( $parts['fragment'] ) ) {
			$fragment = '#' . $parts['fragment'];
		}

		$query_string = http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );

		return $base . ( '' !== $query_string ? '?' . $query_string : '' ) . $fragment;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		return trim( preg_replace( '/[\r\n\t ]+/', ' ', wp_strip_all_tags( (string) $value ) ) );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( (string) $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $key ) );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return strip_tags( (string) $text );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return array_key_exists( $option, $GLOBALS['wpruby_address_checks_test_options'] ) ? $GLOBALS['wpruby_address_checks_test_options'][ $option ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		unset( $autoload );
		$GLOBALS['wpruby_address_checks_test_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	function add_option( $option, $value = '', $deprecated = '', $autoload = 'yes' ) {
		unset( $deprecated, $autoload );
		if ( array_key_exists( $option, $GLOBALS['wpruby_address_checks_test_options'] ) ) {
			return false;
		}
		$GLOBALS['wpruby_address_checks_test_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		unset( $GLOBALS['wpruby_address_checks_test_options'][ $option ] );
		return true;
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	function wp_timezone() {
		$timezone = (string) get_option( 'timezone_string', 'UTC' );
		return new DateTimeZone( '' !== $timezone ? $timezone : 'UTC' );
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( $format, $timestamp = null, $timezone = null ) {
		$timezone = $timezone instanceof DateTimeZone ? $timezone : wp_timezone();
		$date     = new DateTimeImmutable( '@' . ( null === $timestamp ? time() : (int) $timestamp ) );
		return $date->setTimezone( $timezone )->format( (string) $format );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		$timezone = $gmt ? new DateTimeZone( 'UTC' ) : wp_timezone();
		$now      = new DateTimeImmutable( 'now', $timezone );
		return 'timestamp' === $type ? $now->getTimestamp() : $now->format( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return 'https://example.test' . $path;
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( (string) $url, $component );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;
		private $data;
		private $errors = array();

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
			if ( '' !== $code ) {
				$this->add( $code, $message, $data );
			}
		}

		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return $codes ? $codes[0] : $this->code;
		}

		public function get_error_message( $code = '' ) {
			if ( '' === $code ) {
				$code = $this->get_error_code();
			}
			if ( isset( $this->errors[ $code ] ) ) {
				return $this->errors[ $code ][0]['message'];
			}
			return $this->message;
		}

		public function get_error_messages() {
			$messages = array();
			foreach ( $this->errors as $entries ) {
				foreach ( $entries as $entry ) {
					$messages[] = $entry['message'];
				}
			}
			return $messages;
		}

		public function get_error_data( $code = '' ) {
			if ( '' === $code ) {
				$code = $this->get_error_code();
			}
			if ( isset( $this->errors[ $code ] ) ) {
				return $this->errors[ $code ][0]['data'];
			}
			return $this->data;
		}

		public function add( $code, $message, $data = '' ) {
			$this->errors[ $code ][] = array(
				'message' => $message,
				'data'    => $data,
			);
		}

		public function add_data( $data ) {
			$this->data = $data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

require_once __DIR__ . '/support/stubs.php';
require_once $plugin_root . '/includes/autoload.php';
