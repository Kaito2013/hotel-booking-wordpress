<?php
/**
 * Admin Settings Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Settings Class
 */
class Hotel_Booking_Admin_Settings {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Admin_Settings
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Admin_Settings
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
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_hb_save_settings', array( $this, 'ajax_save_settings' ) );
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		// General Settings
		register_setting( 'hb_general_settings', 'hb_currency' );
		register_setting( 'hb_general_settings', 'hb_currency_symbol' );
		register_setting( 'hb_general_settings', 'hb_default_check_in' );
		register_setting( 'hb_general_settings', 'hb_default_check_out' );
		register_setting( 'hb_general_settings', 'hb_contact_email' );
		register_setting( 'hb_general_settings', 'hb_contact_phone' );

		// Payment Settings
		register_setting( 'hb_payment_settings', 'hb_stripe_enabled' );
		register_setting( 'hb_payment_settings', 'hb_stripe_test_mode' );
		register_setting( 'hb_payment_settings', 'hb_stripe_test_public' );
		register_setting( 'hb_payment_settings', 'hb_stripe_test_secret' );
		register_setting( 'hb_payment_settings', 'hb_stripe_live_public' );
		register_setting( 'hb_payment_settings', 'hb_stripe_live_secret' );

		register_setting( 'hb_payment_settings', 'hb_paypal_enabled' );
		register_setting( 'hb_payment_settings', 'hb_paypal_test_mode' );
		register_setting( 'hb_payment_settings', 'hb_paypal_client_id' );
		register_setting( 'hb_payment_settings', 'hb_paypal_secret' );

		// Email Settings
		register_setting( 'hb_email_settings', 'hb_confirmation_email' );
		register_setting( 'hb_email_settings', 'hb_reminder_email' );
		register_setting( 'hb_email_settings', 'hb_cancellation_email' );
		register_setting( 'hb_email_settings', 'hb_admin_notification' );
	}

	/**
	 * Save settings via AJAX.
	 *
	 * @return void
	 */
	public function ajax_save_settings() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$section = isset( $_POST['section'] ) ? sanitize_text_field( $_POST['section'] ) : '';
		$data    = isset( $_POST['data'] ) ? $_POST['data'] : array();

		if ( empty( $section ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing section', 'hotel-booking' ) ) );
		}

		foreach ( $data as $key => $value ) {
			update_option( $key, sanitize_text_field( $value ) );
		}

		wp_send_json_success( array( 'message' => __( 'Settings saved', 'hotel-booking' ) ) );
	}
}
