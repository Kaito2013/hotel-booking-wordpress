<?php
/**
 * Admin Dashboard Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Dashboard Class
 */
class Hotel_Booking_Admin_Dashboard {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Admin_Dashboard
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Admin_Dashboard
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
		add_action( 'wp_ajax_hb_get_dashboard_stats', array( $this, 'ajax_get_stats' ) );
	}

	/**
	 * Get dashboard statistics.
	 *
	 * @return array
	 */
	public function get_stats() {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'bookings';

		// Total bookings
		$total_bookings = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );

		// Pending bookings
		$pending_bookings = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE booking_status = 'pending'" );

		// Confirmed bookings
		$confirmed_bookings = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE booking_status = 'confirmed'" );

		// Total revenue
		$total_revenue = $wpdb->get_var( "SELECT SUM(total_price) FROM $table WHERE payment_status = 'completed'" );

		// This month revenue
		$this_month_revenue = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(total_price) FROM $table
				WHERE payment_status = 'completed'
				AND YEAR(created_at) = YEAR(CURDATE())
				AND MONTH(created_at) = MONTH(CURDATE())"
			)
		);

		return array(
			'total_bookings'     => absint( $total_bookings ),
			'pending_bookings'   => absint( $pending_bookings ),
			'confirmed_bookings' => absint( $confirmed_bookings ),
			'total_revenue'      => $total_revenue ? floatval( $total_revenue ) : 0,
			'this_month_revenue' => $this_month_revenue ? floatval( $this_month_revenue ) : 0,
		);
	}

	/**
	 * Get dashboard stats via AJAX.
	 *
	 * @return void
	 */
	public function ajax_get_stats() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		wp_send_json_success( $this->get_stats() );
	}
}
