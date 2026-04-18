<?php
/**
 * Admin Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Class
 */
class Hotel_Booking_Admin {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Admin
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Hotel Booking', 'hotel-booking' ),
			__( 'Hotel Booking', 'hotel-booking' ),
			'manage_options',
			'hotel-booking',
			array( $this, 'render_dashboard' ),
			'dashicons-admin-home',
			30
		);

		add_submenu_page(
			'hotel-booking',
			__( 'Dashboard', 'hotel-booking' ),
			__( 'Dashboard', 'hotel-booking' ),
			'manage_options',
			'hotel-booking',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'hotel-booking',
			__( 'Calendar', 'hotel-booking' ),
			__( 'Calendar', 'hotel-booking' ),
			'manage_options',
			'hotel-booking-calendar',
			array( $this, 'render_calendar' )
		);

		add_submenu_page(
			'hotel-booking',
			__( 'Settings', 'hotel-booking' ),
			__( 'Settings', 'hotel-booking' ),
			'manage_options',
			'hotel-booking-settings',
			array( $this, 'render_settings' )
		);

		add_submenu_page(
			'hotel-booking',
			__( 'Email Templates', 'hotel-booking' ),
			__( 'Email Templates', 'hotel-booking' ),
			'manage_options',
			'hotel-booking-email-templates',
			array( $this, 'render_email_templates' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( false === strpos( $hook, 'hotel-booking' ) ) {
			return;
		}

		wp_enqueue_style(
			'hotel-booking-admin',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			HOTEL_BOOKING_VERSION
		);

		wp_enqueue_script(
			'hotel-booking-admin',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-api' ),
			HOTEL_BOOKING_VERSION,
			true
		);

		wp_localize_script(
			'hotel-booking-admin',
			'hotelBooking',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hotel-booking-nonce' ),
				'restUrl' => rest_url( 'hotel-booking/v1/' ),
			)
		);
	}

	/**
	 * Render dashboard.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		include HOTEL_BOOKING_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render calendar.
	 *
	 * @return void
	 */
	public function render_calendar() {
		include HOTEL_BOOKING_PLUGIN_DIR . 'admin/views/calendar.php';
	}

	/**
	 * Render settings.
	 *
	 * @return void
	 */
	public function render_settings() {
		include HOTEL_BOOKING_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Render email templates.
	 *
	 * @return void
	 */
	public function render_email_templates() {
		include HOTEL_BOOKING_PLUGIN_DIR . 'admin/views/email-templates.php';
	}
}
