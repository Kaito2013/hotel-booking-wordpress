<?php
/**
 * Availability Manager Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Availability Manager Class
 */
class Hotel_Booking_Availability_Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Availability_Manager
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Availability_Manager
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
		add_action( 'save_post_hb_room', array( $this, 'update_room_availability' ), 10, 2 );
		add_action( 'hb_booking_created', array( $this, 'mark_dates_booked' ), 10, 1 );
		add_action( 'hb_booking_cancelled', array( $this, 'release_dates' ), 10, 1 );
	}

	/**
	 * Update room availability when room is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function update_room_availability( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Get room capacity
		$capacity = get_post_meta( $post_id, '_hb_room_capacity', true );
		$capacity = $capacity ? absint( $capacity ) : 2;

		// Update availability for next 365 days
		$start_date = new DateTime();
		$end_date   = new DateTime();
		$end_date->modify( '+365 days' );

		$this->generate_availability_slots( $post_id, $start_date, $end_date );
	}

	/**
	 * Generate availability slots for a date range.
	 *
	 * @param int      $room_id   Room ID.
	 * @param DateTime $start_date Start date.
	 * @param DateTime $end_date   End date.
	 * @return void
	 */
	private function generate_availability_slots( $room_id, $start_date, $end_date ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'availability';

		$current = clone $start_date;
		$interval = new DateInterval( 'P1D' );
		$period   = new DatePeriod( $current, $interval, $end_date );

		foreach ( $period as $date ) {
			$date_str = $date->format( 'Y-m-d' );

			// Check if slot exists
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $table WHERE room_id = %d AND check_in = %s",
					$room_id,
					$date_str
				)
			);

			if ( ! $exists ) {
				$wpdb->insert(
					$table,
					array(
						'room_id'    => $room_id,
						'check_in'   => $date_str,
						'check_out'  => $date_str,
						'status'     => 'available',
					),
					array( '%d', '%s', '%s', '%s' )
				);
			}
		}
	}

	/**
	 * Mark dates as booked.
	 *
	 * @param int $booking_id Booking ID.
	 * @return void
	 */
	public function mark_dates_booked( $booking_id ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'availability';

		// Get booking details
		$booking = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT room_id, check_in, check_out FROM {$prefix}bookings WHERE id = %d",
				$booking_id
			)
		);

		if ( ! $booking ) {
			return;
		}

		// Update availability status
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $table SET status = 'booked', booking_id = %d
				WHERE room_id = %d
				AND check_in >= %s
				AND check_out < %s",
				$booking_id,
				$booking->room_id,
				$booking->check_in,
				$booking->check_out
			)
		);
	}

	/**
	 * Release dates when booking is cancelled.
	 *
	 * @param int $booking_id Booking ID.
	 * @return void
	 */
	public function release_dates( $booking_id ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'availability';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $table SET status = 'available', booking_id = NULL
				WHERE booking_id = %d",
				$booking_id
			)
		);
	}

	/**
	 * Check room availability for a date range.
	 *
	 * @param int    $room_id   Room ID.
	 * @param string $check_in  Check-in date (Y-m-d).
	 * @param string $check_out Check-out date (Y-m-d).
	 * @return bool
	 */
	public function is_available( $room_id, $check_in, $check_out ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'availability';

		// Check if any dates in range are booked
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table
				WHERE room_id = %d
				AND status = 'booked'
				AND check_in >= %s
				AND check_out < %s",
				$room_id,
				$check_in,
				$check_out
			)
		);

		return $count == 0;
	}

	/**
	 * Get available rooms for a date range.
	 *
	 * @param string $check_in  Check-in date (Y-m-d).
	 * @param string $check_out Check-out date (Y-m-d).
	 * @param array  $args      Additional arguments (guests, room_type, etc).
	 * @return array
	 */
	public function get_available_rooms( $check_in, $check_out, $args = array() ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'availability';

		// Get all rooms that are available for the date range
		$room_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT room_id FROM $table
				WHERE status = 'available'
				AND check_in >= %s
				AND check_out < %s",
				$check_in,
				$check_out
			)
		);

		if ( empty( $room_ids ) ) {
			return array();
		}

		// Build query args
		$query_args = array(
			'post_type'      => 'hb_room',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'post__in'       => $room_ids,
		);

		// Filter by guests
		if ( isset( $args['guests'] ) && $args['guests'] > 0 ) {
			$query_args['meta_query'] = array(
				array(
					'key'     => '_hb_room_capacity',
					'value'   => $args['guests'],
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			);
		}

		// Filter by room type
		if ( isset( $args['room_type'] ) && ! empty( $args['room_type'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'room_type',
					'field'    => 'slug',
					'terms'    => $args['room_type'],
				),
			);
		}

		$rooms = get_posts( $query_args );

		return $rooms;
	}

	/**
	 * Get availability calendar data for a room.
	 *
	 * @param int   $room_id Room ID.
	 * @param int   $months  Number of months to return.
	 * @return array
	 */
	public function get_calendar_data( $room_id, $months = 3 ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'availability';

		$start_date = new DateTime();
		$end_date   = new DateTime();
		$end_date->modify( "+{$months} months" );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT check_in, status, booking_id FROM $table
				WHERE room_id = %d
				AND check_in >= %s
				AND check_in <= %s
				ORDER BY check_in ASC",
				$room_id,
				$start_date->format( 'Y-m-d' ),
				$end_date->format( 'Y-m-d' )
			)
		);

		$calendar = array();
		foreach ( $results as $row ) {
			$calendar[ $row->check_in ] = array(
				'status'     => $row->status,
				'booking_id' => $row->booking_id,
			);
		}

		return $calendar;
	}
}
