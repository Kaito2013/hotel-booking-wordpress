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
	 * Get API credentials.
	 *
	 * @return array
	 */
	private function get_api_credentials() {
		$test_mode = '1' === get_option( 'hb_paypal_test_mode', '0' );

		if ( $test_mode ) {
			return array(
				'client_id' => get_option( 'hb_paypal_sandbox_client_id', '' ),
				'secret'    => get_option( 'hb_paypal_sandbox_secret', '' ),
				'mode'      => 'sandbox',
			);
		}

		return array(
			'client_id' => get_option( 'hb_paypal_client_id', '' ),
			'secret'    => get_option( 'hb_paypal_secret', '' ),
			'mode'      => 'live',
		);
	}

	/**
	 * Get PayPal API context.
	 *
	 * @return object|null
	 */
	private function get_api_context() {
		if ( ! class_exists( '\\PayPal\\Rest\\ApiContext' ) ) {
			return null;
		}

		$creds = $this->get_api_credentials();

		if ( empty( $creds['client_id'] ) || empty( $creds['secret'] ) ) {
			return null;
		}

		$api_context = new \PayPal\Rest\ApiContext(
			new \PayPal\Auth\OAuthTokenCredential(
				$creds['client_id'],
				$creds['secret']
			)
		);

		$api_context->setConfig(
			array(
				'mode'           => $creds['mode'],
				'log.LogEnabled' => true,
				'log.FileName'   => WP_CONTENT_DIR . '/debug.log',
				'log.LogLevel'   => 'DEBUG',
			)
		);

		return $api_context;
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
		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			return new WP_Error( 'invalid_booking', __( 'Invalid booking', 'hotel-booking' ) );
		}

		// Check if PayPal SDK is available
		if ( ! class_exists( '\\PayPal\\Rest\\ApiContext' ) ) {
			return new WP_Error( 'paypal_not_available', __( 'PayPal SDK is not available. Please run composer install in the plugin directory.', 'hotel-booking' ) );
		}

		$api_context = $this->get_api_context();

		if ( ! $api_context ) {
			return new WP_Error( 'no_api_credentials', __( 'PayPal API credentials are not configured', 'hotel-booking' ) );
		}

		try {
			$payer = new \PayPal\Api\Payer();
			$payer->setPaymentMethod( 'paypal' );

			$amount = new \PayPal\Api\Amount();
			$amount->setTotal( number_format( $booking->total_price, 2, '.', '' ) );
			$amount->setCurrency( get_option( 'hb_currency', 'USD' ) );

			$transaction = new \PayPal\Api\Transaction();
			$transaction->setAmount( $amount );
			$transaction->setDescription( sprintf( __( 'Booking #%d', 'hotel-booking' ), $booking_id ) );
			$transaction->setCustom( $booking_id );

			$redirect_urls = new \PayPal\Api\RedirectUrls();
			$redirect_urls->setReturnUrl( admin_url( 'admin-ajax.php?action=hb_paypal_success&booking_id=' . $booking_id ) );
			$redirect_urls->setCancelUrl( admin_url( 'admin-ajax.php?action=hb_paypal_cancel&booking_id=' . $booking_id ) );

			$payment = new \PayPal\Api\Payment();
			$payment->setIntent( 'sale' );
			$payment->setPayer( $payer );
			$payment->setTransactions( array( $transaction ) );
			$payment->setRedirectUrls( $redirect_urls );

			$payment->create( $api_context );

			foreach ( $payment->getLinks() as $link ) {
				if ( 'approval_url' === $link->getRel() ) {
					return array(
						'success'      => true,
						'approval_url' => $link->getHref(),
						'payment_id'   => $payment->getId(),
						'amount'       => $booking->total_price,
						'currency'     => get_option( 'hb_currency', 'USD' ),
					);
				}
			}

			return new WP_Error( 'no_approval_url', __( 'Could not get PayPal approval URL', 'hotel-booking' ) );

		} catch ( Exception $e ) {
			return new WP_Error( 'paypal_error', $e->getMessage() );
		}
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

		// Check if PayPal SDK is available
		if ( ! class_exists( '\\PayPal\\Rest\\ApiContext' ) ) {
			wp_send_json_error( array(
				'message' => __( 'PayPal SDK is not available. Please run composer install in the plugin directory.', 'hotel-booking' ),
			) );
		}

		$api_context = $this->get_api_context();

		if ( ! $api_context ) {
			wp_send_json_error( array( 'message' => __( 'PayPal API credentials are not configured', 'hotel-booking' ) ) );
		}

		try {
			$payer = new \PayPal\Api\Payer();
			$payer->setPaymentMethod( 'paypal' );

			$amount = new \PayPal\Api\Amount();
			$amount->setTotal( number_format( $booking->total_price, 2, '.', '' ) );
			$amount->setCurrency( get_option( 'hb_currency', 'USD' ) );

			$transaction = new \PayPal\Api\Transaction();
			$transaction->setAmount( $amount );
			$transaction->setDescription( sprintf( __( 'Booking #%d', 'hotel-booking' ), $booking_id ) );
			$transaction->setCustom( $booking_id );

			$redirect_urls = new \PayPal\Api\RedirectUrls();
			$redirect_urls->setReturnUrl( admin_url( 'admin-ajax.php?action=hb_paypal_success&booking_id=' . $booking_id ) );
			$redirect_urls->setCancelUrl( admin_url( 'admin-ajax.php?action=hb_paypal_cancel&booking_id=' . $booking_id ) );

			$payment = new \PayPal\Api\Payment();
			$payment->setIntent( 'sale' );
			$payment->setPayer( $payer );
			$payment->setTransactions( array( $transaction ) );
			$payment->setRedirectUrls( $redirect_urls );

			$payment->create( $api_context );

			foreach ( $payment->getLinks() as $link ) {
				if ( 'approval_url' === $link->getRel() ) {
					wp_send_json_success( array(
						'order_id'     => $payment->getId(),
						'approval_url' => $link->getHref(),
						'amount'       => $booking->total_price,
						'currency'     => get_option( 'hb_currency', 'USD' ),
					) );
				}
			}

			wp_send_json_error( array( 'message' => __( 'Could not create PayPal order', 'hotel-booking' ) ) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Capture PayPal order via AJAX.
	 *
	 * @return void
	 */
	public function ajax_capture_order() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		$payment_id = isset( $_POST['payment_id'] ) ? sanitize_text_field( $_POST['payment_id'] ) : '';
		$payer_id   = isset( $_POST['payer_id'] ) ? sanitize_text_field( $_POST['payer_id'] ) : '';

		if ( ! $booking_id || ! $payment_id || ! $payer_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'hotel-booking' ) ) );
		}

		// Check if PayPal SDK is available
		if ( ! class_exists( '\\PayPal\\Rest\\ApiContext' ) ) {
			wp_send_json_error( array( 'message' => __( 'PayPal SDK is not available', 'hotel-booking' ) ) );
		}

		$api_context = $this->get_api_context();

		if ( ! $api_context ) {
			wp_send_json_error( array( 'message' => __( 'PayPal API credentials are not configured', 'hotel-booking' ) ) );
		}

		try {
			$payment = \PayPal\Api\Payment::get( $payment_id, $api_context );

			$execution = new \PayPal\Api\PaymentExecution();
			$execution->setPayerId( $payer_id );

			$result = $payment->execute( $execution, $api_context );

			if ( 'approved' !== $result->getState() ) {
				wp_send_json_error( array( 'message' => __( 'Payment not approved', 'hotel-booking' ) ) );
			}

			// Update booking payment status
			$booking_manager = Hotel_Booking_Booking_Manager::get_instance();
			$booking_manager->update_payment_status( $booking_id, 'completed', $payment_id );
			$booking_manager->update_status( $booking_id, 'confirmed' );

			// Send confirmation email
			$notification = Hotel_Booking_Notification_Manager::get_instance();
			$notification->send_confirmation_email( $booking_id );

			wp_send_json_success( array(
				'message'      => __( 'Payment successful', 'hotel-booking' ),
				'redirect_url' => '/booking-confirmation?booking_id=' . $booking_id,
			) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handle PayPal success return.
	 *
	 * @return void
	 */
	public function handle_paypal_success() {
		$booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;
		$payment_id = isset( $_GET['paymentId'] ) ? sanitize_text_field( $_GET['paymentId'] ) : '';
		$payer_id   = isset( $_GET['PayerID'] ) ? sanitize_text_field( $_GET['PayerID'] ) : '';

		if ( ! $booking_id || ! $payment_id || ! $payer_id ) {
			wp_die( __( 'Invalid parameters', 'hotel-booking' ), __( 'Error', 'hotel-booking' ), array( 'response' => 400 ) );
		}

		// Execute payment
		$api_context = $this->get_api_context();

		if ( ! $api_context ) {
			wp_die( __( 'PayPal API error', 'hotel-booking' ), __( 'Error', 'hotel-booking' ), array( 'response' => 500 ) );
		}

		try {
			$payment = \PayPal\Api\Payment::get( $payment_id, $api_context );

			$execution = new \PayPal\Api\PaymentExecution();
			$execution->setPayerId( $payer_id );

			$result = $payment->execute( $execution, $api_context );

			if ( 'approved' === $result->getState() ) {
				// Update booking payment status
				$booking_manager = Hotel_Booking_Booking_Manager::get_instance();
				$booking_manager->update_payment_status( $booking_id, 'completed', $payment_id );
				$booking_manager->update_status( $booking_id, 'confirmed' );

				// Send confirmation email
				$notification = Hotel_Booking_Notification_Manager::get_instance();
				$notification->send_confirmation_email( $booking_id );

				// Redirect to confirmation page
				wp_redirect( home_url( '/booking-confirmation?booking_id=' . $booking_id ) );
				exit;
			} else {
				wp_die( __( 'Payment not approved', 'hotel-booking' ), __( 'Payment Error', 'hotel-booking' ), array( 'response' => 400 ) );
			}

		} catch ( Exception $e ) {
			wp_die( $e->getMessage(), __( 'PayPal Error', 'hotel-booking' ), array( 'response' => 500 ) );
		}
	}

	/**
	 * Handle PayPal cancel return.
	 *
	 * @return void
	 */
	public function handle_paypal_cancel() {
		$booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;

		// Redirect to booking confirmation with cancelled status
		wp_redirect( home_url( '/my-bookings' ) . '?hb_message=payment_cancelled&booking_id=' . $booking_id );
		exit;
	}
}
