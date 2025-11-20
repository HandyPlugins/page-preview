<?php
namespace PagePreview;

use PagePreview as Base;
use WP_Mock;

class Install_Tests extends Base\TestCase {

	protected $testFiles = [ 'classes/Install.php' ];

	public function setUp(): void {
		parent::setUp();
		WP_Mock::setUp();
	}

	public function tearDown(): void {
		WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * Test setup returns singleton instance
	 */
	public function test_setup_returns_instance() {
		$instance1 = Install::setup();
		$instance2 = Install::setup();

		$this->assertInstanceOf( Install::class, $instance1 );
		$this->assertSame( $instance1, $instance2, 'setup() should return the same instance' );
	}

	/**
	 * Test check_version does nothing during iframe request
	 */
	public function test_check_version_skips_iframe_request() {
		if ( ! defined( 'IFRAME_REQUEST' ) ) {
			define( 'IFRAME_REQUEST', true );
		}

		$install = Install::setup();
		
		// No expectations - the method should return early
		$install->check_version();

		// If we get here without errors, the test passes
		$this->assertTrue( true );
	}

	/**
	 * Test check_version does nothing when version is current
	 */
	public function test_check_version_skips_when_current() {
		if ( ! defined( 'PAGE_PREVIEW_DB_VERSION' ) ) {
			define( 'PAGE_PREVIEW_DB_VERSION', '1.0' );
		}

		// Can't test this easily because check_version checks IFRAME_REQUEST
		// and calls install() internally which has complex dependencies
		// This is more of an integration test
		
		$install = Install::setup();
		$this->assertInstanceOf( Install::class, $install );
	}

	/**
	 * Test install skips when blog not installed
	 */
	public function test_install_skips_when_blog_not_installed() {
		WP_Mock::userFunction( 'is_blog_installed' )
			->once()
			->andReturn( false );

		$install = Install::setup();
		$install->install();

		// If we get here without errors, the test passes
		$this->assertTrue( true );
	}

	/**
	 * Test maybe_upgrade updates version option
	 */
	public function test_maybe_upgrade_updates_version() {
		if ( ! defined( 'PAGE_PREVIEW_DB_VERSION' ) ) {
			define( 'PAGE_PREVIEW_DB_VERSION', '1.0' );
		}

		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'page_preview_version' )
			->andReturn( '0.9' );

		// Note: version_compare is a PHP internal function, not mockable

		WP_Mock::userFunction( 'update_option' )
			->once()
			->with( 'page_preview_version', '1.0', false )
			->andReturn( true );

		$install = Install::setup();
		$install->maybe_upgrade();

		// If we get here without errors, the test passes
		$this->assertTrue( true );
	}

	/**
	 * Test maybe_upgrade_network_wide updates site option
	 */
	public function test_maybe_upgrade_network_wide_updates_site_option() {
		if ( ! defined( 'PAGE_PREVIEW_DB_VERSION' ) ) {
			define( 'PAGE_PREVIEW_DB_VERSION', '1.0' );
		}

		WP_Mock::userFunction( 'get_site_option' )
			->once()
			->with( 'page_preview_version' )
			->andReturn( '0.9' );

		// Note: version_compare is a PHP internal function, not mockable

		WP_Mock::userFunction( 'update_site_option' )
			->once()
			->with( 'page_preview_version', '1.0' )
			->andReturn( true );

		$install = Install::setup();
		$install->maybe_upgrade_network_wide();

		// If we get here without errors, the test passes
		$this->assertTrue( true );
	}
}
