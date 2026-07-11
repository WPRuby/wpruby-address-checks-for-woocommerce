<?php
/**
 * Self-contained PSR-4 autoloader for the WPRuby\AddressGuard namespace.
 *
 * Keeps the plugin runnable from a plain checkout/zip without a Composer build step.
 *
 * @package WPRuby\AddressGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	static function ( $class ) {
		$prefix   = 'WPRuby\\AddressGuard\\';
		$base_dir = __DIR__ . '/';

		$len = strlen( $prefix );
		if ( 0 !== strncmp( $prefix, $class, $len ) ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$relative_path  = str_replace( '\\', '/', $relative_class ) . '.php';
		$file           = $base_dir . $relative_path;

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);
