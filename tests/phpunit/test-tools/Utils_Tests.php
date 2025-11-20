<?php
namespace PagePreview;

use PagePreview as Base;
use WP_Mock;

class Utils_Tests extends Base\TestCase {

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
	 * Test get_settings returns default settings when no option exists
	 */
	public function test_get_settings_returns_defaults() {
		if ( ! defined( 'PAGE_PREVIEW_IS_NETWORK' ) ) {
			define( 'PAGE_PREVIEW_IS_NETWORK', false );
		}

		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'page_preview_settings', [] )
			->andReturn( [] );

		$settings = \PagePreview\Utils\get_settings();

		$this->assertIsArray( $settings );
		$this->assertArrayHasKey( 'post_types', $settings );
		$this->assertArrayHasKey( 'crop', $settings );
		$this->assertArrayHasKey( 'delay', $settings );
		$this->assertArrayHasKey( 'zoom', $settings );
		$this->assertArrayHasKey( 'featured_image_fallback', $settings );
		$this->assertEquals( [ 'page' ], $settings['post_types'] );
		$this->assertTrue( $settings['crop'] );
		$this->assertEquals( 3, $settings['delay'] );
		$this->assertTrue( $settings['zoom'] );
		$this->assertTrue( $settings['featured_image_fallback'] );
	}

	/**
	 * Test get_settings merges custom settings with defaults
	 */
	public function test_get_settings_merges_with_defaults() {
		if ( ! defined( 'PAGE_PREVIEW_IS_NETWORK' ) ) {
			define( 'PAGE_PREVIEW_IS_NETWORK', false );
		}

		$custom_settings = [
			'post_types' => [ 'page', 'post' ],
			'delay'      => 5,
		];

		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'page_preview_settings', [] )
			->andReturn( $custom_settings );

		$settings = \PagePreview\Utils\get_settings();

		$this->assertEquals( [ 'page', 'post' ], $settings['post_types'] );
		$this->assertEquals( 5, $settings['delay'] );
		$this->assertTrue( $settings['crop'] );
		$this->assertTrue( $settings['zoom'] );
	}

	/**
	 * Test get_screenshot_endpoint returns constant value
	 */
	public function test_get_screenshot_endpoint_returns_constant() {
		$endpoint = \PagePreview\Utils\get_screenshot_endpoint();

		$this->assertIsString( $endpoint );
		$this->assertEquals( 'https://screenshot.handyplugins.co/take', $endpoint );
	}

	/**
	 * Test get_screenshot_endpoint returns custom endpoint when defined
	 */
	public function test_get_screenshot_endpoint_returns_custom() {
		if ( ! defined( 'PAGE_PREVIEW_SCREENSHOT_ENDPOINT' ) ) {
			define( 'PAGE_PREVIEW_SCREENSHOT_ENDPOINT', 'https://custom.endpoint.com/take' );
		}

		$endpoint = \PagePreview\Utils\get_screenshot_endpoint();

		$this->assertEquals( 'https://custom.endpoint.com/take', $endpoint );
	}

	/**
	 * Test is_network_wide returns false for non-multisite
	 */
	public function test_is_network_wide_returns_false_for_non_multisite() {
		WP_Mock::userFunction( 'is_multisite' )
			->once()
			->andReturn( false );

		$result = \PagePreview\Utils\is_network_wide( 'plugin-file.php' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_doc_url returns correct base URL
	 */
	public function test_get_doc_url_returns_base_url() {
		WP_Mock::userFunction( 'trailingslashit' )
			->once()
			->with( 'https://handyplugins.co/page-preview/docs/' )
			->andReturn( 'https://handyplugins.co/page-preview/docs/' );

		$url = \PagePreview\Utils\get_doc_url();

		$this->assertStringContainsString( 'https://handyplugins.co/page-preview/docs/', $url );
		$this->assertStringContainsString( 'utm_source=wp_admin', $url );
	}

	/**
	 * Test get_doc_url with custom path
	 */
	public function test_get_doc_url_with_path() {
		WP_Mock::userFunction( 'trailingslashit' )
			->once()
			->with( 'https://handyplugins.co/page-preview/docs/custom-path' )
			->andReturn( 'https://handyplugins.co/page-preview/docs/custom-path/' );

		$url = \PagePreview\Utils\get_doc_url( 'custom-path' );

		$this->assertStringContainsString( 'custom-path', $url );
	}

	/**
	 * Test get_doc_url with fragment
	 */
	public function test_get_doc_url_with_fragment() {
		WP_Mock::userFunction( 'trailingslashit' )
			->once()
			->with( 'https://handyplugins.co/page-preview/docs/' )
			->andReturn( 'https://handyplugins.co/page-preview/docs/' );

		$url = \PagePreview\Utils\get_doc_url( null, 'section' );

		$this->assertStringContainsString( '#section', $url );
	}

	/**
	 * Test is_excluded_page returns false for non-excluded page
	 */
	public function test_is_excluded_page_returns_false() {
		WP_Mock::onFilter( 'page_preview_excluded_pages' )
			->with( [] )
			->reply( [] );

		$result = \PagePreview\Utils\is_excluded_page( 123 );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_excluded_page returns true for excluded page
	 */
	public function test_is_excluded_page_returns_true() {
		WP_Mock::onFilter( 'page_preview_excluded_pages' )
			->with( [] )
			->reply( [ 123, 456 ] );

		$result = \PagePreview\Utils\is_excluded_page( 123 );

		$this->assertTrue( $result );
	}

	/**
	 * Test get_preview_base_dir returns correct path
	 */
	public function test_get_preview_base_dir() {
		$upload_dir = [
			'basedir' => '/var/www/html/wp-content/uploads',
			'baseurl' => 'http://example.com/wp-content/uploads',
		];

		WP_Mock::userFunction( 'wp_upload_dir' )
			->once()
			->andReturn( $upload_dir );

		WP_Mock::onFilter( 'page_preview_base_dir' )
			->with( '/var/www/html/wp-content/uploads/page-previews' )
			->reply( '/var/www/html/wp-content/uploads/page-previews' );

		$path = \PagePreview\Utils\get_preview_base_dir();

		$this->assertEquals( '/var/www/html/wp-content/uploads/page-previews', $path );
	}

	/**
	 * Test get_preview_base_url returns correct URL
	 */
	public function test_get_preview_base_url() {
		$upload_dir = [
			'basedir' => '/var/www/html/wp-content/uploads',
			'baseurl' => 'http://example.com/wp-content/uploads',
		];

		WP_Mock::userFunction( 'wp_upload_dir' )
			->once()
			->andReturn( $upload_dir );

		WP_Mock::onFilter( 'page_preview_base_url' )
			->with( 'http://example.com/wp-content/uploads/page-previews' )
			->reply( 'http://example.com/wp-content/uploads/page-previews' );

		$url = \PagePreview\Utils\get_preview_base_url();

		$this->assertEquals( 'http://example.com/wp-content/uploads/page-previews', $url );
	}

	/**
	 * Test get_eligible_post_types filters out attachments
	 */
	public function test_get_eligible_post_types() {
		$post_types = [ 'post', 'page', 'attachment', 'custom' ];

		WP_Mock::userFunction( 'get_post_types' )
			->once()
			->with( [], 'names' )
			->andReturn( $post_types );

		WP_Mock::userFunction( 'is_post_type_viewable' )
			->times( 4 )
			->andReturnUsing( function( $post_type ) {
				return in_array( $post_type, [ 'post', 'page', 'attachment', 'custom' ] );
			} );

		WP_Mock::onFilter( 'page_preview_supportable_post_types' )
			->with( [ 'post', 'page', 'custom' ] )
			->reply( [ 'post', 'page', 'custom' ] );

		$result = \PagePreview\Utils\get_eligible_post_types();

		$this->assertIsArray( $result );
		$this->assertNotContains( 'attachment', $result );
	}

	/**
	 * Test get_placeholder_image_url returns default URL
	 */
	public function test_get_placeholder_image_url() {
		if ( ! defined( 'PAGE_PREVIEW_URL' ) ) {
			define( 'PAGE_PREVIEW_URL', 'http://example.com/wp-content/plugins/page-preview/' );
		}

		WP_Mock::onFilter( 'page_preview_placeholder_image_url' )
			->with( PAGE_PREVIEW_URL . 'dist/images/placeholder.png' )
			->reply( PAGE_PREVIEW_URL . 'dist/images/placeholder.png' );

		$url = \PagePreview\Utils\get_placeholder_image_url();

		$this->assertStringContainsString( 'dist/images/placeholder.png', $url );
	}

	/**
	 * Test is_local_site returns true for localhost
	 */
	public function test_is_local_site_returns_true_for_localhost() {
		WP_Mock::userFunction( 'site_url' )
			->once()
			->andReturn( 'http://localhost' );

		$result = \PagePreview\Utils\is_local_site();

		$this->assertTrue( $result );
	}

	/**
	 * Test is_local_site returns false for production site
	 */
	public function test_is_local_site_returns_false_for_production() {
		WP_Mock::userFunction( 'site_url' )
			->once()
			->andReturn( 'https://example.com' );

		WP_Mock::userFunction( 'wp_get_environment_type' )
			->once()
			->andReturn( 'production' );

		$result = \PagePreview\Utils\is_local_site();

		$this->assertFalse( $result );
	}

	/**
	 * Test is_local_site returns true for .local domain
	 */
	public function test_is_local_site_returns_true_for_local_domain() {
		WP_Mock::userFunction( 'site_url' )
			->once()
			->andReturn( 'http://example.local' );

		WP_Mock::userFunction( 'wp_get_environment_type' )
			->once()
			->andReturn( 'production' );

		$result = \PagePreview\Utils\is_local_site();

		$this->assertTrue( $result );
	}
}
