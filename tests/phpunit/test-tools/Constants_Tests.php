<?php
namespace PagePreview;

use PagePreview as Base;
use WP_Mock;
use PagePreview\Constants;

class Constants_Tests extends Base\TestCase {

	protected $testFiles = [];

	public function setUp(): void {
		parent::setUp();
		WP_Mock::setUp();
	}

	public function tearDown(): void {
		WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * Test that SETTING_OPTION constant is defined
	 */
	public function test_setting_option_constant_defined() {
		$this->assertTrue( defined( 'PagePreview\Constants\SETTING_OPTION' ) );
		$this->assertEquals( 'page_preview_settings', Constants\SETTING_OPTION );
	}

	/**
	 * Test that DB_VERSION_OPTION constant is defined
	 */
	public function test_db_version_option_constant_defined() {
		$this->assertTrue( defined( 'PagePreview\Constants\DB_VERSION_OPTION' ) );
		$this->assertEquals( 'page_preview_version', Constants\DB_VERSION_OPTION );
	}

	/**
	 * Test that PREVIEW_URL_META_KEY constant is defined
	 */
	public function test_preview_url_meta_key_constant_defined() {
		$this->assertTrue( defined( 'PagePreview\Constants\PREVIEW_URL_META_KEY' ) );
		$this->assertEquals( 'page_preview_url', Constants\PREVIEW_URL_META_KEY );
	}

	/**
	 * Test that SCREENSHOT_ENDPOINT constant is defined
	 */
	public function test_screenshot_endpoint_constant_defined() {
		$this->assertTrue( defined( 'PagePreview\Constants\SCREENSHOT_ENDPOINT' ) );
		$this->assertEquals( 'https://screenshot.handyplugins.co/take', Constants\SCREENSHOT_ENDPOINT );
	}

	/**
	 * Test that URL constants are defined
	 */
	public function test_url_constants_defined() {
		$this->assertTrue( defined( 'PagePreview\Constants\BLOG_URL' ) );
		$this->assertTrue( defined( 'PagePreview\Constants\FAQ_URL' ) );
		$this->assertTrue( defined( 'PagePreview\Constants\SUPPORT_URL' ) );
		$this->assertTrue( defined( 'PagePreview\Constants\GITHUB_URL' ) );
		$this->assertTrue( defined( 'PagePreview\Constants\TWITTER_URL' ) );
	}

	/**
	 * Test that URL constants have valid URLs
	 */
	public function test_url_constants_are_valid_urls() {
		$this->assertStringStartsWith( 'https://', Constants\BLOG_URL );
		$this->assertStringStartsWith( 'https://', Constants\FAQ_URL );
		$this->assertStringStartsWith( 'https://', Constants\SUPPORT_URL );
		$this->assertStringStartsWith( 'https://', Constants\GITHUB_URL );
		$this->assertStringStartsWith( 'https://', Constants\TWITTER_URL );
	}
}
