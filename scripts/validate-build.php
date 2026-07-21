#!/usr/bin/env php
<?php
/**
 * Validate a production ZIP build.
 *
 * Usage: php scripts/validate-build.php path/to/plugin.zip
 */

if ( $argc < 2 ) {
	fwrite( STDERR, "Usage: php scripts/validate-build.php <zip-file>\n" );
	exit( 1 );
}

$zip_path = $argv[1];
if ( ! is_readable( $zip_path ) ) {
	fwrite( STDERR, "ZIP file not found: {$zip_path}\n" );
	exit( 1 );
}

$zip = new ZipArchive();
if ( true !== $zip->open( $zip_path ) ) {
	fwrite( STDERR, "Unable to open ZIP: {$zip_path}\n" );
	exit( 1 );
}

$entries = array();
for ( $i = 0; $i < $zip->numFiles; $i++ ) {
	$entries[] = (string) $zip->getNameIndex( $i );
}
$zip->close();

$slug = 'wpruby-address-checks-for-woocommerce';
$main_file = 'wpruby-address-checks-for-woocommerce.php';

$required = array(
	"{$slug}/{$main_file}",
	"{$slug}/readme.txt",
	"{$slug}/includes/Plugin.php",
	"{$slug}/assets/admin/dist/app.js",
	"{$slug}/assets/admin/dist/app.css",
	"{$slug}/assets/checkout/validation.js",
	"{$slug}/assets/checkout/autocomplete.js",
);

$forbidden_patterns = array(
	'#(^|/)tests/#',
	'#(^|/)node_modules/#',
	'#(^|/)vendor/#',
	'#(^|/)\.git(/|$)#',
	'#(^|/)\.github/#',
	'#(^|/)\.idea/#',
	'#(^|/)\.vscode/#',
	'#(^|/)phpunit\.xml#',
	'#(^|/)package\.json$#',
	'#(^|/)package-lock\.json$#',
	'#(^|/)vite\.config\.js$#',
	'#(^|/)Makefile$#',
	'#(^|/)assets/admin/vue/#',
	'#(^|/)composer\.(json|lock)$#',
	'#(^|/)scripts/#',
	'#(^|/)docs/#',
	'#(^|/)assets/frontend/#',
	'#(^|/)includes/Licensing/#',
	'#(^|/)includes/Domain/Providers/#',
	'#(^|/)includes/Domain/Rules/#',
	'#\.DS_Store$#',
	'#\.map$#',
);

$errors = array();

foreach ( $required as $path ) {
	$found = false;
	foreach ( $entries as $entry ) {
		if ( $entry === $path || 0 === strpos( $entry, $path ) ) {
			$found = true;
			break;
		}
	}
	if ( ! $found ) {
		$errors[] = "Missing required path: {$path}";
	}
}

foreach ( $entries as $entry ) {
	foreach ( $forbidden_patterns as $pattern ) {
		if ( preg_match( $pattern, $entry ) ) {
			$errors[] = "Forbidden path in ZIP: {$entry}";
			break;
		}
	}

	// ZIP must nest under the plugin slug only.
	if ( "{$slug}/" !== substr( $entry, 0, strlen( "{$slug}/" ) )
		&& $slug !== $entry ) {
		$errors[] = "Unexpected top-level path in ZIP: {$entry}";
	}
}

// Confirm plugin header identity strings.
$main = "{$slug}/{$main_file}";
$tmp  = tempnam( sys_get_temp_dir(), 'agzip' );
$zip  = new ZipArchive();
if ( true === $zip->open( $zip_path ) ) {
	$contents = $zip->getFromName( $main );
	$zip->close();
	if ( is_string( $contents ) ) {
		if ( false === strpos( $contents, 'Plugin Name:       WPRuby Address Checks for WooCommerce' ) ) {
			$errors[] = 'Plugin Name header mismatch.';
		}
		if ( false === strpos( $contents, 'Text Domain:       wpruby-address-checks-for-woocommerce' ) ) {
			$errors[] = 'Text Domain header mismatch.';
		}
		if ( false !== strpos( $contents, 'Plugin Name:       WooCommerce Address Checks' ) ) {
			$errors[] = 'Plugin Name must not use WooCommerce-first branding.';
		}
	} else {
		$errors[] = "Unable to read {$main} from ZIP.";
	}
}
unset( $tmp );

if ( ! empty( $errors ) ) {
	fwrite( STDERR, "Build validation failed for {$zip_path}:\n" );
	foreach ( array_unique( $errors ) as $error ) {
		fwrite( STDERR, " - {$error}\n" );
	}
	exit( 1 );
}

fwrite( STDOUT, 'Build validation passed for ' . $zip_path . ' (' . count( $entries ) . " entries)\n" );
exit( 0 );
