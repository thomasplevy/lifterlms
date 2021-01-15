<?php
/**
 * Test LLMS_Staging class
 *
 * @package LifterLMS/Tests
 *
 * @group staging
 *
 * @since [version]
 */
class LLMS_Test_Staging extends LLMS_Unit_Test_Case {

	/**
	 * Setup before class
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public static function setupBeforeClass() {

		parent::setupBeforeClass();
		require_once LLMS_PLUGIN_DIR . 'includes/admin/class.llms.admin.notices.php';

	}

	/**
	 * Test clone_detected()
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_clone_detected() {

		LLMS_Site::update_feature( 'recurring_payments', true );

		LLMS_Staging::clone_detected();
		$this->assertFalse( LLMS_Site::get_feature( 'recurring_payments' ) );

	}

	/**
	 * Test handle_staging_notice_actions() when the method isn't called
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_handle_staging_notice_actions_not_called() {

		$this->assertNull( LLMS_Staging::handle_staging_notice_actions() );

	}

	/**
	 * Test handle_staging_notice_actions() with an invalid nonce.
	 *
	 * @since [version]
	 *
	 * @expectedException WPDieException
	 *
	 * @return void
	 */
	public function test_handle_staging_notice_actions_invalid_nonce() {

		$this->mockGetRequest( array(
			'llms-staging-status' => 'enable',
			'_llms_staging_nonce' => 'fake',
		) );

		LLMS_Staging::handle_staging_notice_actions();

	}

	/**
	 * Test handle_staging_notice_actions() with an invalid user.
	 *
	 * @since [version]
	 *
	 * @expectedException WPDieException
	 *
	 * @return void
	 */
	public function test_handle_staging_notice_actions_invalid_user() {

		$this->mockGetRequest( array(
			'llms-staging-status' => 'enable',
			'_llms_staging_nonce' => wp_create_nonce( 'llms_staging_status' ),
		) );

		LLMS_Staging::handle_staging_notice_actions();

	}

	/**
	 * Test handle_staging_notice_actions() when enabling recurring payments
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_handle_staging_notice_actions_enable() {

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$_SERVER['HTTP_REFERER'] = 'http://example.tld/wp-admin/?page=whatever';
		$original = get_site_url();
		update_option( 'siteurl', 'http://fakeurl.tld' );
		LLMS_Site::update_feature( 'recurring_payments', false );

		$this->mockGetRequest( array(
			'llms-staging-status' => 'enable',
			'_llms_staging_nonce' => wp_create_nonce( 'llms_staging_status' ),
		) );

		$this->expectException( LLMS_Unit_Test_Exception_Redirect::class );
		$this->expectExceptionMessage( $_SERVER['HTTP_REFERER'] . ' [302] YES' );

		try {

			LLMS_Staging::handle_staging_notice_actions();

		} catch( LLMS_Unit_Test_Exception_Redirect $exception ) {

			$this->assertEquals( get_option( 'llms_site_url' ), LLMS_Site::get_lock_url() );
			$this->assertTrue( LLMS_Site::get_feature( 'recurring_payments' ) );
			$this->assertFalse( LLMS_Admin_Notices::has_notice( 'maybe-staging' ) );

			update_option( 'siteurl', $original );
			unset( $_SERVER['HTTP_REFERER'] );

			throw $exception;
		}

	}

	/**
	 * Test handle_staging_notice_actions() when enabling recurring payments
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_handle_staging_notice_actions_disable() {

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$_SERVER['HTTP_REFERER'] = 'http://example.tld/wp-admin/?page=whatever';
		$original = get_site_url();
		update_option( 'siteurl', 'http://fakeurl.tld' );
		LLMS_Site::update_feature( 'recurring_payments', true );

		$this->mockGetRequest( array(
			'llms-staging-status' => 'disable',
			'_llms_staging_nonce' => wp_create_nonce( 'llms_staging_status' ),
		) );

		$this->expectException( LLMS_Unit_Test_Exception_Redirect::class );
		$this->expectExceptionMessage( $_SERVER['HTTP_REFERER'] . ' [302] YES' );

		try {

			LLMS_Staging::handle_staging_notice_actions();

		} catch( LLMS_Unit_Test_Exception_Redirect $exception ) {

			$this->assertEquals( '', get_option( 'llms_site_url' ) );
			$this->assertTrue( LLMS_Site::is_clone_ignored() );
			$this->assertFalse( LLMS_Site::get_feature( 'recurring_payments' ) );
			$this->assertFalse( LLMS_Admin_Notices::has_notice( 'maybe-staging' ) );

			update_option( 'siteurl', $original );
			unset( $_SERVER['HTTP_REFERER'] );

			throw $exception;
		}

	}

	/**
	 * Test the menu_warning() method
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_menu_warning() {

		$mock_menu = array(
			array(
				'Dashboard',
				'read',
				'index.php',
				'',
				'menu-top menu-top-first menu-icon-dashboard',
				'menu-dashboard',
				'dashicons-dashboard',
			),
			array(
				'Orders',
				'edit_posts',
				'edit.php?post_type=llms_order',
				'',
				'menu-top menu-icon-llms_order',
				'menu-posts-llms_order',
				'dashicons-cart',
			),
		);

		global $menu;
		$menu = $mock_menu;

		LLMS_Site::update_feature( 'recurring_payments', true );
		LLMS_Staging::menu_warning();
		$this->assertSame( $mock_menu, $menu );


		LLMS_Site::update_feature( 'recurring_payments', false );
		LLMS_Staging::menu_warning();
		$this->assertSame( $mock_menu[0], $menu[0] );

		$mock_menu[1][0] .= LLMS_Unit_Test_Util::call_method( 'LLMS_Staging', 'get_menu_warning_bubble' );
		$this->assertSame( $mock_menu[1], $menu[1] );

	}

	/**
	 * Test notice() method
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_notice() {

		LLMS_Admin_Notices::delete_notice( 'maybe-staging' );
		LLMS_Staging::notice();
		$this->assertTrue( LLMS_Admin_Notices::has_notice( 'maybe-staging' ) );
		LLMS_Admin_Notices::delete_notice( 'maybe-staging' );

	}

}
