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
	 * Register webhook endpoint.
	 *
	 * @return void
	 */
	public function register_webhook_endpoint() {
		add_rewrite_rule(
			'^hb-stripe-webhook/?$',
			'index.php?hb_webhook=stripe',
			'top'
		);

		add_rewrite_tag( '%hb_webhook%', '([^&]+)' );
	}

	/**
	 * Handle webhook request.
	 *
	 * @return void
	 */
	public function handle_webhook_request() {
		if ( get_query_var( 'hb_webhook' ) !== 'stripe' ) {
			return;
		}

		$this->handle_webhook();
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
		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			return new WP_Error( 'invalid_booking', __( 'Invalid booking', 'hotel-booking' ) );
		}

		// Check if Stripe SDK is available
		if ( ! class_exists( '\\Stripe\\StripeClient' ) ) {
			return new WP_Error( 'stripe_not_available', __( 'Stripe SDK is not available. Please run composer install in the plugin directory.', 'hotel-booking' ) );
		}

		try {
			$api_key = $this->get_api_key();

			if ( empty( $api_key ) ) {
				return new WP_Error( 'no_api_key', __( 'Stripe API key is not configured', 'hotel-booking' ) );
			}

			$stripe = new \Stripe\StripeClient( $api_key );

			// Create payment intent
			$payment_intent = $stripe->paymentIntents->create( array(
				'amount'               => (int) round( $booking->total_price * 100 ), // Convert to cents
				'currency'             => strtolower( get_option( 'hb_currency', 'USD' ) ),
				'metadata'             => array(
					'booking_id' => $booking_id,
					'email'      => $booking->email,
				),
				'description'          => sprintf( __( 'Booking #%d for %s', 'hotel-booking' ), $booking_id, $booking->email ),
				'automatic_payment_methods' => array(
					'enabled' => true,
				),
			) );

			return array(
				'success'       => true,
				'client_secret' => $payment_intent->client_secret,
				'payment_id'    => $payment_intent->id,
				'amount'        => $booking->total_price,
				'currency'      => get_option( 'hb_currency', 'USD' ),
			);

		} catch ( Exception $e ) {
			return new WP_Error( 'stripe_error', $e->getMessage() );
		}
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

		// Check if Stripe SDK is available
		if ( ! class_exists( '\\Stripe\\StripeClient' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Stripe SDK is not available. Please run composer install in the plugin directory.', 'hotel-booking' ),
			) );
		}

		try {
			$api_key = $this->get_api_key();

			if ( empty( $api_key ) ) {
				wp_send_json_error( array( 'message' => __( 'Stripe API key is not configured', 'hotel-booking' ) ) );
			}

			$stripe = new \Stripe\StripeClient( $api_key );

			// Create payment intent
			$payment_intent = $stripe->paymentIntents->create( array(
				'amount'               => (int) round( $booking->total_price * 100 ), // Convert to cents
				'currency'             => strtolower( get_option( 'hb_currency', 'USD' ) ),
				'metadata'             => array(
					'booking_id' => $booking_id,
					'email'      => $booking->email,
				),
				'description'          => sprintf( __( 'Booking #%d for %s', 'hotel-booking' ), $booking_id, $booking->email ),
				'automatic_payment_methods' => array(
					'enabled' => true,
				),
			) );

			wp_send_json_success( array(
				'client_secret' => $payment_intent->client_secret,
				'payment_id'    => $payment_intent->id,
				'amount'        => $booking->total_price * 100, // Convert to cents
				'currency'      => strtolower( get_option( 'hb_currency', 'USD' ) ),
			) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
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

		// Check if Stripe SDK is available
		if ( ! class_exists( '\\Stripe\\StripeClient' ) ) {
			wp_send_json_error( array( 'message' => __( 'Stripe SDK is not available', 'hotel-booking' ) ) );
		}

		try {
			$api_key = $this->get_api_key();

			if ( empty( $api_key ) ) {
				wp_send_json_error( array( 'message' => __( 'Stripe API key is not configured', 'hotel-booking' ) ) );
			}

			$stripe = new \Stripe\StripeClient( $api_key );

			// Retrieve payment intent to verify status
			$payment_intent = $stripe->paymentIntents->retrieve( $payment_id );

			if ( 'succeeded' !== $payment_intent->status ) {
				wp_send_json_error( array( 'message' => __( 'Payment not successful', 'hotel-booking' ) ) );
			}

			// Update booking payment status
			$booking_manager = Hotel_Booking_Booking_Manager::get_instance();
			$booking_manager->update_payment_status( $booking_id, 'completed', $payment_id );
			$booking_manager->update_status( $booking_id, 'confirmed' );

			// Send confirmation email
			$notification = Hotel_Booking_Notification_Manager::get_instance();
			$notification->send_confirmation_email( $booking_id );

			wp_send_json_success( array(
				'message' => __( 'Payment successful', 'hotel-booking' ),
				'redirect_url' => '/booking-confirmation?booking_id=' . $booking_id,
			) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handle Stripe webhook.
	 *
	 * @return void
	 */
	public function handle_webhook() {
		$payload = @file_get_contents( 'php://input' );
		$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

		if ( ! class_exists( '\\Stripe\\StripeClient' ) ) {
			status_header( 500 );
			echo 'Stripe SDK not available';
			exit;
		}

		try {
			$api_key = $this->get_api_key();

			if ( empty( $api_key ) ) {
				status_header( 500 );
				echo 'API key not configured';
				exit;
			}

			$stripe = new \Stripe\StripeClient( $api_key );

			$webhook_secret = get_option( 'hb_stripe_webhook_secret', '' );

			if ( empty( $webhook_secret ) ) {
				// For development, skip signature verification
				$event = json_decode( $payload );
			} else {
				$event = \Stripe\Webhook::constructEvent(
					$payload,
					$sig_header,
					$webhook_secret
				);
			}

			// Handle the event
			switch ( $event->type ) {
				case 'payment_intent.succeeded':
					$this->handle_payment_succeeded( $event->data->object );
					break;

				case 'payment_intent.payment_failed':
					$this->handle_payment_failed( $event->data->object );
					break;

				default:
					// Unexpected event type
					break;
			}

			status_header( 200 );
			echo 'Webhook handled';

		} catch ( Exception $e ) {
			status_header( 500 );
			echo 'Webhook error: ' . $e->getMessage();
		}

		exit;
	}

	/**
	 * Handle payment succeeded webhook.
	 *
	 * @param object $payment_intent Payment intent object.
	 * @return void
	 */
	private function handle_payment_succeeded( $payment_intent ) {
		$booking_id = $payment_intent->metadata->booking_id ?? 0;

		if ( ! $booking_id ) {
			return;
		}

		$booking_manager = Hotel_Booking_Booking_Manager::get_instance();
		$booking_manager->update_payment_status( $booking_id, 'completed', $payment_intent->id );
		$booking_manager->update_status( $booking_id, 'confirmed' );

		// Send confirmation email
		$notification = Hotel_Booking_Notification_Manager::get_instance();
		$notification->send_confirmation_email( $booking_id );
	}

	/**
	 * Handle payment failed webhook.
	 *
	 * @param object $payment_intent Payment intent object.
	 * @return void
	 */
	private function handle_payment_failed( $payment_intent ) {
		$booking_id = $payment_intent->metadata->booking_id ?? 0;

		if ( ! $booking_id ) {
			return;
		}

		$booking_manager = Hotel_Booking_Booking_Manager::get_instance();
		$booking_manager->update_payment_status( $booking_id, 'failed' );

		// Send payment failed email
		$notification = Hotel_Booking_Notification_Manager::get_instance();
		$notification->send_payment_failed_email( $booking_id );
	}
}
