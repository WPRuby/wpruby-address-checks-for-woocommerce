<?php
/**
 * Pro product URL UTM attribution tests.
 *
 * @package WPRuby\AddressGuard\Tests\Unit
 */

namespace WPRuby\AddressGuard\Tests\Unit;

use WPRuby\AddressGuard\Infrastructure\ProUrls;
use WPRuby\AddressGuard\Tests\TestCase;

class ProUrlsTest extends TestCase {

	public function test_plugin_admin_url_uses_expected_campaign_parameters(): void {
		$url = ProUrls::get( 'upgrade_tab' );

		$this->assertStringStartsWith( WPRUBY_ADDRESS_CHECKS_PRO_URL, $url );
		$this->assert_utm(
			$url,
			array(
				'utm_source'   => 'address_guard_free',
				'utm_medium'   => 'plugin_admin',
				'utm_campaign' => 'free_to_pro',
				'utm_content'  => 'upgrade_tab',
			)
		);
	}

	public function test_different_cta_locations_get_different_utm_content(): void {
		$upgrade = ProUrls::get( 'upgrade_tab' );
		$compare = ProUrls::get( 'feature_comparison' );

		$this->assert_utm( $upgrade, array( 'utm_content' => 'upgrade_tab' ) );
		$this->assert_utm( $compare, array( 'utm_content' => 'feature_comparison' ) );
		$this->assertNotSame( $upgrade, $compare );
	}

	public function test_wordpress_org_url_uses_referral_campaign_parameters(): void {
		$url = ProUrls::for_wordpress_org( 'learn_more' );

		$this->assert_utm(
			$url,
			array(
				'utm_source'   => 'wordpress_org',
				'utm_medium'   => 'referral',
				'utm_campaign' => 'address_guard_free',
				'utm_content'  => 'learn_more',
			)
		);
	}

	public function test_readme_pro_links_use_wordpress_org_utm_scheme(): void {
		$readme = file_get_contents( dirname( __DIR__, 2 ) . '/readme.txt' );
		$this->assertNotFalse( $readme );

		preg_match_all(
			'#https://wpruby\.com/plugin/woocommerce-address-guard-pro/[^\s\)]+#',
			$readme,
			$matches
		);

		$this->assertNotEmpty( $matches[0], 'Expected Pro product links in readme.txt' );

		$contents = array();
		foreach ( $matches[0] as $url ) {
			$this->assert_utm(
				$url,
				array(
					'utm_source'   => 'wordpress_org',
					'utm_medium'   => 'referral',
					'utm_campaign' => 'address_guard_free',
				)
			);

			$query = array();
			parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
			$this->assertArrayHasKey( 'utm_content', $query );
			$contents[] = $query['utm_content'];
		}

		$this->assertContains( 'upgrade_to_pro', $contents );
		$this->assertContains( 'learn_more', $contents );
		$this->assertSame( count( $contents ), count( array_unique( $contents ) ) );
	}

	public function test_docs_knowledgebase_url_constant_is_unchanged(): void {
		$this->assertSame(
			'https://wpruby.com/knowledgebase_category/woocommerce-address-guard-pro/',
			WPRUBY_ADDRESS_CHECKS_DOCS_URL
		);
		$this->assertStringNotContainsString( 'utm_', WPRUBY_ADDRESS_CHECKS_DOCS_URL );
	}

	/**
	 * Assert UTM query parameters on a URL.
	 *
	 * @param string               $url      Full URL.
	 * @param array<string,string> $expected Expected query params.
	 *
	 * @return void
	 */
	private function assert_utm( string $url, array $expected ): void {
		$query = array();
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );

		foreach ( $expected as $key => $value ) {
			$this->assertArrayHasKey( $key, $query, "Missing {$key} in {$url}" );
			$this->assertSame( $value, $query[ $key ], "Unexpected {$key} in {$url}" );
		}
	}
}
