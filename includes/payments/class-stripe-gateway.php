<?php
/**
 * Stripe Payment Gateway
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stripe Gateway Class
 */
class Hotel_Booking_Stripe_Gateway extends Hotel_Booking_Payment_Gateway {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	protected $id = 'stripe';

	/**
	 * Gateway title.
	 *
	 * @var string
	 */
	protected $title = 'Stripe';

	/**
	 * Gateway description.
	 *
	 * @var string
	 */
	protected $description = 'Pay with credit card via Stripe';

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Stripe_Gateway
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Stripe_Gateway
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
		add_action( 'wp_ajax_hb_stripe_create_payment_intent', array( $this, 'ajax_create_payment_intent' ) );
		add_action( 'wp_ajax_nopriv_hb_stripe_create_payment_intent', array( $this, 'ajax_create_payment_intent' ) );
		add_action( 'wp_ajax_hb_stripe_confirm_payment', array( $this, 'ajax_confirm_payment' ) );
		add_action( 'wp_ajax_nopriv_hb_stripe_confirm_payment', array( $this, 'ajax_confirm_payment' ) );
	}

	/**
	 * Check if gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return '1' === get_option( 'hb_stripe_enabled', '0' );
	}

	/**
	 * Get API key.
	 *
	 * @return string
	 */
	private function get_api_key() {
		$test_mode = '1' === get_option( 'hb_stripe_test_mode', '0' );

		if ( $test_mode ) {
			return get_option( 'hb_stripe_test_secret', '' );
		}

		return get_option( 'hb_stripe_live_secret', '' );
	}

	/**
	 * Process payment.
	 *
	 * @param int   $booking_id Booking ID.
	 * @param array $data       Payment data.
	 * @return array|WP_Error
	 */
	public function process_payment( $booking_id, $data ) {
		// Implementation requires Stripe SDK
		// This is a skeleton

		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			return new WP_Error( 'invalid_booking', __( 'Invalid booking', 'hotel-booking' ) );
		}

		// Create payment intent with Stripe
		// ... Stripe SDK integration

		return array(
			'success' => true,
			'message' => __( 'Payment processed', 'hotel-booking' ),
		);
	}

	/**
	 * Create payment intent via AJAX.
	 *
	 * @return void
	 */
	public function ajax_create_payment_intent() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;

		if ( ! $booking_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid booking ID', 'hotel-booking' ) ) );
		}

		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => __( 'Booking not found', 'hotel-booking' ) ) );
		}

		// Create Stripe Payment Intent
		// ... Stripe SDK integration

		wp_send_json_success( array(
			'client_secret' => 'stripe_client_secret_placeholder',
			'amount'        => $booking->total_price * 100, // Convert to cents
		) );
	}

	/**
	 * Confirm payment via AJAX.
	 *
	 * @return void
	 */
	public function ajax_confirm_payment() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		$booking_id  = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		$payment_id  = isset( $_POST['payment_id'] ) ? sanitize_text_field( $_POST['payment_id'] ) : '';

		if ( ! $booking_id || ! $payment_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'hotel-booking' ) ) );
		}

		// Update booking payment status
		$booking_manager = Hotel_Booking_Booking_Manager::get_instance();
		$booking_manager->update_payment_status( $booking_id, 'completed', $payment_id );
		$booking_manager->update_status( $booking_id, 'confirmed' );

		wp_send_json_success( array(
			'message' => __( 'Payment successful', 'hotel-booking' ),
		) );
	}
}
