<?php
/**
 * Notification Manager Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notification Manager Class
 */
class Hotel_Booking_Notification_Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Notification_Manager
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Notification_Manager
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
		add_action( 'hb_booking_created', array( $this, 'send_confirmation_email' ), 10, 2 );
		add_action( 'hb_booking_status_updated', array( $this, 'send_status_update_email' ), 10, 2 );
		add_action( 'hb_payment_status_updated', array( $this, 'send_payment_email' ), 10, 2 );
	}

	/**
	 * Send confirmation email after booking.
	 *
	 * @param int   $booking_id Booking ID.
	 * @param array $data       Booking data.
	 * @return void
	 */
	public function send_confirmation_email( $booking_id, $data ) {
		if ( '1' !== get_option( 'hb_confirmation_email', '1' ) ) {
			return;
		}

		$to      = $data['email'];
		$subject = sprintf( __( 'Booking Confirmation - %s', 'hotel-booking' ), get_bloginfo( 'name' ) );
		$message = $this->get_confirmation_email_template( $booking_id, $data );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Get confirmation email template.
	 *
	 * @param int   $booking_id Booking ID.
	 * @param array $data       Booking data.
	 * @return string
	 */
	private function get_confirmation_email_template( $booking_id, $data ) {
		$room = get_post( $data['room_id'] );

		$message = '<html><body style="font-family: Arial, sans-serif;">';
		$message .= '<h2>' . __( 'Booking Confirmation', 'hotel-booking' ) . '</h2>';
		$message .= '<p>' . __( 'Thank you for your booking! Here are your details:', 'hotel-booking' ) . '</p>';
		$message .= '<table style="border-collapse: collapse; width: 100%;">';
		$message .= '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __( 'Booking ID:', 'hotel-booking' ) . '</strong></td><td style="padding: 10px; border: 1px solid #ddd;">#' . $booking_id . '</td></tr>';
		$message .= '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __( 'Room:', 'hotel-booking' ) . '</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . ( $room ? $room->post_title : '' ) . '</td></tr>';
		$message .= '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __( 'Check-in:', 'hotel-booking' ) . '</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . $data['check_in'] . '</td></tr>';
		$message .= '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __( 'Check-out:', 'hotel-booking' ) . '</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . $data['check_out'] . '</td></tr>';
		$message .= '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __( 'Guests:', 'hotel-booking' ) . '</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . $data['guests'] . '</td></tr>';
		$message .= '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __( 'Total Price:', 'hotel-booking' ) . '</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . get_option( 'hb_currency_symbol', '$' ) . number_format( $data['total_price'], 2 ) . '</td></tr>';
		$message .= '</table>';
		$message .= '<p style="margin-top: 20px;">' . __( 'We look forward to seeing you!', 'hotel-booking' ) . '</p>';
		$message .= '</body></html>';

		return $message;
	}

	/**
	 * Send status update email.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $status     New status.
	 * @return void
	 */
	public function send_status_update_email( $booking_id, $status ) {
		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$to      = $booking->email;
		$subject = sprintf( __( 'Booking Status Update - #%s', 'hotel-booking' ), $booking_id );
		$message = $this->get_status_update_email_template( $booking_id, $status, $booking );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Get status update email template.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $status     Status.
	 * @param object $booking    Booking object.
	 * @return string
	 */
	private function get_status_update_email_template( $booking_id, $status, $booking ) {
		$status_labels = array(
			'pending'   => __( 'Pending', 'hotel-booking' ),
			'confirmed' => __( 'Confirmed', 'hotel-booking' ),
			'cancelled' => __( 'Cancelled', 'hotel-booking' ),
			'completed' => __( 'Completed', 'hotel-booking' ),
		);

		$status_label = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : $status;

		$message = '<html><body style="font-family: Arial, sans-serif;">';
		$message .= '<h2>' . __( 'Booking Status Update', 'hotel-booking' ) . '</h2>';
		$message .= '<p>' . sprintf( __( 'Your booking #%s status has been updated to:', 'hotel-booking' ), $booking_id ) . '</p>';
		$message .= '<p style="font-size: 18px; font-weight: bold; color: ' . ( 'confirmed' === $status ? 'green' : ( 'cancelled' === $status ? 'red' : 'orange' ) . ';">' . $status_label . '</p>';
		$message .= '</body></html>';

		return $message;
	}

	/**
	 * Send payment status email.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $status     Payment status.
	 * @return void
	 */
	public function send_payment_email( $booking_id, $status ) {
		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$to      = $booking->email;
		$subject = sprintf( __( 'Payment Status Update - #%s', 'hotel-booking' ), $booking_id );

		if ( 'completed' === $status ) {
			$message = $this->get_payment_success_email_template( $booking_id, $booking );
		} else {
			$message = $this->get_payment_failed_email_template( $booking_id, $booking );
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Get payment success email template.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param object $booking    Booking object.
	 * @return string
	 */
	private function get_payment_success_email_template( $booking_id, $booking ) {
		$message = '<html><body style="font-family: Arial, sans-serif;">';
		$message .= '<h2>' . __( 'Payment Successful!', 'hotel-booking' ) . '</h2>';
		$message .= '<p>' . sprintf( __( 'Payment for booking #%s has been completed successfully.', 'hotel-booking' ), $booking_id ) . '</p>';
		$message .= '<p>' . __( 'Amount paid:', 'hotel-booking' ) . ' ' . get_option( 'hb_currency_symbol', '$' ) . number_format( $booking->total_price, 2 ) . '</p>';
		$message .= '</body></html>';

		return $message;
	}

	/**
	 * Get payment failed email template.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param object $booking    Booking object.
	 * @return string
	 */
	private function get_payment_failed_email_template( $booking_id, $booking ) {
		$message = '<html><body style="font-family: Arial, sans-serif;">';
		$message .= '<h2>' . __( 'Payment Failed', 'hotel-booking' ) . '</h2>';
		$message .= '<p>' . sprintf( __( 'Payment for booking #%s has failed. Please try again.', 'hotel-booking' ), $booking_id ) . '</p>';
		$message .= '</body></html>';

		return $message;
	}

	/**
	 * Send cancellation email.
	 *
	 * @param int $booking_id Booking ID.
	 * @return void
	 */
	public function send_cancellation_email( $booking_id ) {
		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$to      = $booking->email;
		$subject = sprintf( __( 'Booking Cancelled - #%s', 'hotel-booking' ), $booking_id );
		$message = $this->get_cancellation_email_template( $booking_id, $booking );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Get cancellation email template.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param object $booking    Booking object.
	 * @return string
	 */
	private function get_cancellation_email_template( $booking_id, $booking ) {
		$message = '<html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
		$message .= '<h2 style="color: #d63638;">' . __( 'Booking Cancelled', 'hotel-booking' ) . '</h2>';
		$message .= '<p>' . sprintf( __( 'Your booking #%s has been cancelled.', 'hotel-booking' ), $booking_id ) . '</p>';

		$room = get_post( $booking->room_id );
		if ( $room ) {
			$message .= '<h3>' . __( 'Room Details', 'hotel-booking' ) . '</h3>';
			$message .= '<p><strong>' . __( 'Room:', 'hotel-booking' ) . '</strong> ' . esc_html( $room->post_title ) . '</p>';
		}

		$message .= '<p><strong>' . __( 'Check-in:', 'hotel-booking' ) . '</strong> ' . esc_html( $booking->check_in ) . '</p>';
		$message .= '<p><strong>' . __( 'Check-out:', 'hotel-booking' ) . '</strong> ' . esc_html( $booking->check_out ) . '</p>';
		$message .= '<p><strong>' . __( 'Guests:', 'hotel-booking' ) . '</strong> ' . esc_html( $booking->guests ) . '</p>';
		$message .= '<p><strong>' . __( 'Total:', 'hotel-booking' ) . '</strong> ' . get_option( 'hb_currency_symbol', '$' ) . number_format( $booking->total_price, 2 ) . '</p>';

		if ( 'pending' === $booking->payment_status ) {
			$message .= '<p style="color: #856404; background: #fff3cd; padding: 15px; border-radius: 4px;">';
			$message .= '<strong>' . __( 'Note:', 'hotel-booking' ) . '</strong> ';
			$message .= __( 'Your payment was not processed, so no refund is needed.', 'hotel-booking' );
			$message .= '</p>';
		}

		$message .= '<p>' . __( 'We hope to see you again soon!', 'hotel-booking' ) . '</p>';
		$message .= '</body></html>';

		return $message;
	}
}
