<?php
/**
 * Admin Calendar Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Calendar Class
 */
class Hotel_Booking_Admin_Calendar {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Admin_Calendar
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Admin_Calendar
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
		add_action( 'wp_ajax_hb_get_calendar_data', array( $this, 'ajax_get_calendar_data' ) );
	}

	/**
	 * Get calendar data via AJAX.
	 *
	 * @return void
	 */
	public function ajax_get_calendar_data() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$room_id = isset( $_GET['room_id'] ) ? absint( $_GET['room_id'] ) : 0;
		$months  = isset( $_GET['months'] ) ? absint( $_GET['months'] ) : 3;

		if ( ! $room_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid room ID', 'hotel-booking' ) ) );
		}

		$availability = Hotel_Booking_Availability_Manager::get_instance();
		$calendar_data = $availability->get_calendar_data( $room_id, $months );

		wp_send_json_success( $calendar_data );
	}
}
