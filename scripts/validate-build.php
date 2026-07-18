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

$required = array(
	'address-guard-for-woocommerce/address-guard-for-woocommerce.php',
	'address-guard-for-woocommerce/includes/Plugin.php',
	'address-guard-for-woocommerce/assets/admin/dist/app.js',
	'address-guard-for-woocommerce/assets/admin/dist/app.css',
	'address-guard-for-woocommerce/assets/checkout/validation.js',
	'address-guard-for-woocommerce/assets/checkout/autocomplete.js',
);

$forbidden_patterns = array(
	'#(^|/)tests/#',
	'#(^|/)node_modules/#',
	'#(^|/)assets/admin/vue/#',
	'#(^|/)\.git(/|$)#',
	'#(^|/)\.github/#',
	'#(^|/)phpunit\.xml#',
	'#(^|/)package(-lock)?\.json$#',
	'#(^|/)vite\.config\.js$#',
	'#(^|/)Makefile$#',
	'#(^|/)composer\.(json|lock)$#',
	'#(^|/)scripts/#',
	'#(^|/)includes/Licensing/#',
	'#(^|/)includes/Domain/Providers/#',
	'#(^|/)includes/Domain/Rules/#',
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
}

if ( ! empty( $errors ) ) {
	fwrite( STDERR, "Build validation failed for {$zip_path}:\n" );
	foreach ( $errors as $error ) {
		fwrite( STDERR, " - {$error}\n" );
	}
	exit( 1 );
}

fwrite( STDOUT, "Build validation passed for {$zip_path}\n" );
exit( 0 );
