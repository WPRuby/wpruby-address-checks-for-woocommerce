<?php
/**
 * Address component formatter tests.
 *
 * @package WPRuby\AddressGuard\Tests\Unit
 */

namespace WPRuby\AddressGuard\Tests\Unit;

use WPRuby\AddressGuard\Domain\Address\AddressComponentFormatter;
use WPRuby\AddressGuard\Tests\TestCase;

class AddressComponentFormatterTest extends TestCase {

	private AddressComponentFormatter $formatter;

	protected function setUp(): void {
		parent::setUp();
		$this->formatter = new AddressComponentFormatter();
	}

	public function test_formats_us_style_number_before_route(): void {
		$this->assertSame(
			'123 Main Street',
			$this->formatter->format_street_line( '123', 'Main Street', 'US' )
		);
	}

	public function test_formats_german_style_route_before_number(): void {
		$this->assertSame(
			'Kölner Straße 54',
			$this->formatter->format_street_line( '54', 'Kölner Straße', 'DE' )
		);
	}
}
