<?php
/**
 * Booking Manager Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Booking Manager Class
 */
class Hotel_Booking_Booking_Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Booking_Manager
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Booking_Manager
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
		// Hooks for booking lifecycle
	}

	/**
	 * Create a new booking.
	 *
	 * @param array $data Booking data.
	 * @return int|WP_Error Booking ID or error.
	 */
	public function create_booking( $data ) {
		global $wpdb;

		// Validate required fields
		$required = array( 'room_id', 'check_in', 'check_out', 'first_name', 'last_name', 'email' );
		foreach ( $required as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return new WP_Error( 'missing_field', sprintf( __( 'Missing required field: %s', 'hotel-booking' ), $field ) );
			}
		}

		// Sanitize data
		$room_id   = absint( $data['room_id' ] );
		$check_in  = sanitize_text_field( $data['check_in'] );
		$check_out = sanitize_text_field( $data['check_out'] );
		$guests    = isset( $data['guests'] ) ? absint( $data['guests'] ) : 1;

		// Validate dates
		if ( ! $this->validate_dates( $check_in, $check_out ) ) {
			return new WP_Error( 'invalid_dates', __( 'Invalid date range', 'hotel-booking' ) );
		}

		// Check availability
		$availability = Hotel_Booking_Availability_Manager::get_instance();
		if ( ! $availability->is_available( $room_id, $check_in, $check_out ) ) {
			return new WP_Error( 'not_available', __( 'Room is not available for the selected dates', 'hotel-booking' ) );
		}

		// Calculate price
		$pricing = Hotel_Booking_Pricing_Manager::get_instance();
		$total_price = $pricing->calculate_total_price( $room_id, $check_in, $check_out );

		if ( $total_price <= 0 ) {
			return new WP_Error( 'invalid_price', __( 'Unable to calculate price', 'hotel-booking' ) );
		}

		// Get user ID if logged in
		$user_id = get_current_user_id();

		// Insert booking into database
		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'bookings';

		$insert_data = array(
			'room_id'        => $room_id,
			'user_id'        => $user_id ? $user_id : null,
			'first_name'     => sanitize_text_field( $data['first_name'] ),
			'last_name'      => sanitize_text_field( $data['last_name'] ),
			'email'          => sanitize_email( $data['email'] ),
			'phone'          => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : null,
			'check_in'       => $check_in,
			'check_out'      => $check_out,
			'guests'         => $guests,
			'total_price'    => $total_price,
			'payment_method' => isset( $data['payment_method'] ) ? sanitize_text_field( $data['payment_method'] ) : 'stripe',
			'payment_status' => 'pending',
			'booking_status' => 'pending',
			'notes'          => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : null,
		);

		$result = $wpdb->insert( $table, $insert_data );

		if ( ! $result ) {
			return new WP_Error( 'db_error', __( 'Failed to create booking', 'hotel-booking' ) );
		}

		$booking_id = $wpdb->insert_id;

		// Create booking post for admin view
		$this->create_booking_post( $booking_id, $insert_data );

		// Trigger booking created action
		do_action( 'hb_booking_created', $booking_id, $insert_data );

		return $booking_id;
	}

	/**
	 * Validate date range.
	 *
	 * @param string $check_in  Check-in date (Y-m-d).
	 * @param string $check_out Check-out date (Y-m-d).
	 * @return bool
	 */
	private function validate_dates( $check_in, $check_out ) {
		$check_in_date  = DateTime::createFromFormat( 'Y-m-d', $check_in );
		$check_out_date = DateTime::createFromFormat( 'Y-m-d', $check_out );

		if ( ! $check_in_date || ! $check_out_date ) {
			return false;
		}

		// Check out must be after check in
		if ( $check_out_date <= $check_in_date ) {
			return false;
		}

		// Check in must be today or in the future
		$today = new DateTime();
		$today->setTime( 0, 0, 0 );

		if ( $check_in_date < $today ) {
			return false;
		}

		return true;
	}

	/**
	 * Create booking post for admin view.
	 *
	 * @param int   $booking_id Booking ID.
	 * @param array $data       Booking data.
	 * @return void
	 */
	private function create_booking_post( $booking_id, $data ) {
		$post_title = sprintf(
			'%s %s - %s',
			$data['first_name'],
			$data['last_name'],
			$data['check_in']
		);

		$post_id = wp_insert_post(
			array(
				'post_title'  => $post_title,
				'post_type'   => 'hb_booking',
				'post_status' => 'publish',
				'post_author' => 1,
			)
		);

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_hb_booking_id', $booking_id );
		}
	}

	/**
	 * Update booking status.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $status     New status.
	 * @return bool
	 */
	public function update_status( $booking_id, $status ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'bookings';

		$result = $wpdb->update(
			$table,
			array( 'booking_status' => $status ),
			array( 'id' => $booking_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( $result === false ) {
			return false;
		}

		// Trigger status update action
		do_action( 'hb_booking_status_updated', $booking_id, $status );

		return true;
	}

	/**
	 * Update payment status.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $status     New payment status.
	 * @param string $payment_id Optional payment ID.
	 * @return bool
	 */
	public function update_payment_status( $booking_id, $status, $payment_id = null ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'bookings';

		$update_data = array( 'payment_status' => $status );
		if ( $payment_id ) {
			$update_data['payment_id'] = $payment_id;
		}

		$result = $wpdb->update(
			$table,
			$update_data,
			array( 'id' => $booking_id ),
			array_merge( array( '%s' ), $payment_id ? array( '%s' ) : array() ),
			array( '%d' )
		);

		if ( $result === false ) {
			return false;
		}

		// Trigger payment status update action
		do_action( 'hb_payment_status_updated', $booking_id, $status );

		return true;
	}

	/**
	 * Get booking by ID.
	 *
	 * @param int $booking_id Booking ID.
	 * @return object|false Booking data or false.
	 */
	public function get_booking( $booking_id ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'bookings';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$booking_id
			)
		);
	}

	/**
	 * Get bookings for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public function get_user_bookings( $user_id ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'bookings';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC",
				$user_id
			)
		);
	}

	/**
	 * Get all bookings (for admin).
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_bookings( $args = array() ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'bookings';

		$where  = '1=1';
		$params = array();

		if ( isset( $args['status'] ) ) {
			$where .= ' AND booking_status = %s';
			$params[] = $args['status'];
		}

		if ( isset( $args['room_id'] ) ) {
			$where .= ' AND room_id = %d';
			$params[] = $args['room_id'];
		}

		$limit = isset( $args['limit'] ) ? absint( $args['limit'] ) : 20;
		$offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;

		$sql = "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}
}
