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
	public function send_confirmation_email( $booking_id, $data = array() ) {
		if ( '1' !== get_option( 'hb_confirmation_email', '1' ) ) {
			return;
		}

		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$room = get_post( $booking->room_id );

		$variables = array(
			'booking_number'   => $booking_id,
			'guest_name'       => $booking->guest_name,
			'guest_email'      => $booking->email,
			'room_name'        => $room ? $room->post_title : '',
			'check_in'         => $booking->check_in,
			'check_out'        => $booking->check_out,
			'guests'           => $booking->guests,
			'total_amount'     => get_option( 'hb_currency_symbol', '$' ) . number_format( $booking->total_price, 2 ),
			'check_in_time'    => get_option( 'hb_default_check_in', '14:00' ),
			'check_out_time'   => get_option( 'hb_default_check_out', '11:00' ),
			'special_requests' => $booking->special_requests ?? '',
			'hotel_name'       => get_bloginfo( 'name' ),
		);

		$rendered = Hotel_Booking_Email_Template_Manager::get_instance()->render_template( 'booking_confirmation', $variables );

		if ( $rendered ) {
			$to      = $booking->email;
			$subject = $rendered['subject'];
			$message = $rendered['body'];
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			wp_mail( $to, $subject, $message, $headers );
		}
	}

	/**
	 * Send admin notification for new booking.
	 *
	 * @param int $booking_id Booking ID.
	 * @return void
	 */
	public function send_admin_new_booking_notification( $booking_id ) {
		if ( '1' !== get_option( 'hb_admin_notifications', '1' ) ) {
			return;
		}

		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$room = get_post( $booking->room_id );

		$variables = array(
			'booking_number'    => $booking_id,
			'guest_name'        => $booking->guest_name,
			'guest_email'       => $booking->email,
			'guest_phone'       => $booking->phone ?? '',
			'room_name'         => $room ? $room->post_title : '',
			'check_in'          => $booking->check_in,
			'check_out'         => $booking->check_out,
			'guests'            => $booking->guests,
			'total_amount'      => get_option( 'hb_currency_symbol', '$' ) . number_format( $booking->total_price, 2 ),
			'special_requests'  => $booking->special_requests ?? '',
			'admin_booking_url' => admin_url( 'admin.php?page=hotel-booking-bookings' ),
			'hotel_name'        => get_bloginfo( 'name' ),
		);

		$rendered = Hotel_Booking_Email_Template_Manager::get_instance()->render_template( 'admin_new_booking', $variables );

		if ( $rendered ) {
			$to      = get_option( 'admin_email' );
			$subject = $rendered['subject'];
			$message = $rendered['body'];
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			wp_mail( $to, $subject, $message, $headers );
		}
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

		$room = get_post( $booking->room_id );

		if ( 'cancelled' === $status ) {
			$template_key = 'booking_cancelled';
		} else {
			return; // Only send email for cancellations
		}

		$variables = array(
			'booking_number' => $booking_id,
			'guest_name'     => $booking->guest_name,
			'guest_email'    => $booking->email,
			'room_name'      => $room ? $room->post_title : '',
			'check_in'       => $booking->check_in,
			'check_out'      => $booking->check_out,
			'total_amount'   => get_option( 'hb_currency_symbol', '$' ) . number_format( $booking->total_price, 2 ),
			'hotel_name'     => get_bloginfo( 'name' ),
		);

		$rendered = Hotel_Booking_Email_Template_Manager::get_instance()->render_template( $template_key, $variables );

		if ( $rendered ) {
			$to      = $booking->email;
			$subject = $rendered['subject'];
			$message = $rendered['body'];
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			wp_mail( $to, $subject, $message, $headers );
		}
	}

	/**
	 * Send payment status email.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $status     Payment status.
	 * @param string $transaction_id Transaction ID (optional).
	 * @param string $payment_method Payment method (optional).
	 * @return void
	 */
	public function send_payment_email( $booking_id, $status, $transaction_id = '', $payment_method = '' ) {
		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$template_key = 'completed' === $status ? 'payment_confirmation' : 'payment_failed';

		$variables = array(
			'booking_number'  => $booking_id,
			'guest_name'      => $booking->guest_name,
			'guest_email'     => $booking->email,
			'total_amount'    => get_option( 'hb_currency_symbol', '$' ) . number_format( $booking->total_price, 2 ),
			'payment_method'  => $payment_method,
			'transaction_id'  => $transaction_id,
			'hotel_name'      => get_bloginfo( 'name' ),
			'booking_url'     => home_url( '/booking-confirmation?booking_id=' . $booking_id ),
		);

		$rendered = Hotel_Booking_Email_Template_Manager::get_instance()->render_template( $template_key, $variables );

		if ( $rendered ) {
			$to      = $booking->email;
			$subject = $rendered['subject'];
			$message = $rendered['body'];
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			wp_mail( $to, $subject, $message, $headers );
		}
	}

	/**
	 * Send admin payment notification.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $transaction_id Transaction ID.
	 * @param string $payment_method Payment method.
	 * @return void
	 */
	public function send_admin_payment_notification( $booking_id, $transaction_id = '', $payment_method = '' ) {
		if ( '1' !== get_option( 'hb_admin_notifications', '1' ) ) {
			return;
		}

		$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$variables = array(
			'booking_number'    => $booking_id,
			'guest_name'        => $booking->guest_name,
			'guest_email'       => $booking->email,
			'total_amount'      => get_option( 'hb_currency_symbol', '$' ) . number_format( $booking->total_price, 2 ),
			'payment_method'    => $payment_method,
			'transaction_id'    => $transaction_id,
			'admin_booking_url' => admin_url( 'admin.php?page=hotel-booking-bookings' ),
			'hotel_name'        => get_bloginfo( 'name' ),
		);

		$rendered = Hotel_Booking_Email_Template_Manager::get_instance()->render_template( 'admin_payment_received', $variables );

		if ( $rendered ) {
			$to      = get_option( 'admin_email' );
			$subject = $rendered['subject'];
			$message = $rendered['body'];
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			wp_mail( $to, $subject, $message, $headers );
		}
	}
}
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
