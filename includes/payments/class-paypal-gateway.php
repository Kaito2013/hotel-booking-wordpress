<?php
/**
 * PayPal Payment Gateway
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PayPal Gateway Class
 */
class Hotel_Booking_PayPal_Gateway extends Hotel_Booking_Payment_Gateway {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	protected $id = 'paypal';

	/**
	 * Gateway title.
	 *
	 * @var string
	 */
	protected $title = 'PayPal';

	/**
	 * Gateway description.
	 *
	 * @var string
	 */
	protected $description = 'Pay with PayPal';

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_PayPal_Gateway
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_PayPal_Gateway
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize gateway.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'wp_ajax_hb_paypal_create_order', array( $this, 'ajax_create_order' ) );
		add_action( 'wp_ajax_nopriv_hb_paypal_create_order', array( $this, 'ajax_create_order' ) );
		add_action( 'wp_ajax_hb_paypal_capture_order', array( $this, 'ajax_capture_order' ) );
		add_action( 'wp_ajax_nopriv_hb_paypal_capture_order', array( $this, 'ajax_capture_order' ) );
	}

	/**
	 * Check if gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return '1' === get_option( 'hb_paypal_enabled', '0' );
	}

	/**
	 * Process payment.
	 *
	 * @param int   $booking_id Booking ID.
	 * @param array $data       Payment data.
	 * @return array|WP_Error
	 */
	public function process_payment( $booking_id, $data ) {
		// Implementation requires PayPal SDK
		// This is a skeleton

		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			return new WP_Error( 'invalid_booking', __( 'Invalid booking', 'hotel-booking' ) );
		}

		// Create PayPal order
		// ... PayPal SDK integration

		return array(
			'success' => true,
			'message' => __( 'Payment processed', 'hotel-booking' ),
		);
	}

	/**
	 * Create PayPal order via AJAX.
	 *
	 * @return void
	 */
	public function ajax_create_order() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;

		if ( ! $booking_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid booking ID', 'hotel-booking' ) ) );
		}

		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => __( 'Booking not found', 'hotel-booking' ) ) );
		}

		// Create PayPal Order
		// ... PayPal SDK integration

		wp_send_json_success( array(
			'order_id' => 'paypal_order_id_placeholder',
		) );
	}

	/**
	 * Capture PayPal order via AJAX.
	 *
	 * @return void
	 */
	public function ajax_capture_order() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		$order_id   = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '';

		if ( ! $booking_id || ! $order_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'hotel-booking' ) ) );
		}

		// Capture PayPal order
		// ... PayPal SDK integration

		// Update booking payment status
		$booking_manager = Hotel_Booking_Booking_Manager::get_instance();
		$booking_manager->update_payment_status( $booking_id, 'completed', $order_id );
		$booking_manager->update_status( $booking_id, 'confirmed' );

		wp_send_json_success( array(
			'message' => __( 'Payment successful', 'hotel-booking' ),
		) );
	}
}
