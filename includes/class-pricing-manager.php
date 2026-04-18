<?php
/**
 * Pricing Manager Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pricing Manager Class
 */
class Hotel_Booking_Pricing_Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Pricing_Manager
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Pricing_Manager
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
		add_action( 'save_post_hb_room', array( $this, 'save_default_pricing' ), 10, 2 );
	}

	/**
	 * Save default pricing when room is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_default_pricing( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Get base price from meta
		$base_price = get_post_meta( $post_id, '_hb_room_price', true );
		$base_price = $base_price ? floatval( $base_price ) : 100.00;

		// Get custom pricing rules
		$pricing_rules = get_post_meta( $post_id, '_hb_pricing_rules', true );
		$pricing_rules = $pricing_rules ? $pricing_rules : array();

		// Clear existing pricing for this room
		$this->clear_pricing( $post_id );

		// Add default pricing for next 365 days
		$start_date = new DateTime();
		$end_date   = new DateTime();
		$end_date->modify( '+365 days' );

		$this->generate_pricing_slots( $post_id, $start_date, $end_date, $base_price, $pricing_rules );
	}

	/**
	 * Generate pricing slots for a date range.
	 *
	 * @param int      $room_id       Room ID.
	 * @param DateTime $start_date    Start date.
	 * @param DateTime $end_date      End date.
	 * @param float    $base_price    Base price.
	 * @param array    $pricing_rules Pricing rules.
	 * @return void
	 */
	private function generate_pricing_slots( $room_id, $start_date, $end_date, $base_price, $pricing_rules ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'pricing';

		$current = clone $start_date;
		$interval = new DateInterval( 'P1D' );
		$period   = new DatePeriod( $current, $interval, $end_date );

		foreach ( $period as $date ) {
			$date_str = $date->format( 'Y-m-d' );
			$price    = $base_price;

			// Apply pricing rules
			foreach ( $pricing_rules as $rule ) {
				if ( $this->is_date_in_range( $date_str, $rule['start_date'], $rule['end_date'] ) ) {
					if ( 'fixed' === $rule['type'] ) {
						$price = floatval( $rule['price'] );
					} elseif ( 'percent' === $rule['type'] ) {
						$price = $price * ( 1 + floatval( $rule['adjustment'] ) / 100 );
					}
				}
			}

			$wpdb->insert(
				$table,
				array(
					'room_id'    => $room_id,
					'start_date' => $date_str,
					'end_date'   => $date_str,
					'price'      => round( $price, 2 ),
				),
				array( '%d', '%s', '%s', '%f' )
			);
		}
	}

	/**
	 * Check if date is in range.
	 *
	 * @param string $date       Date to check (Y-m-d).
	 * @param string $range_start Range start (Y-m-d).
	 * @param string $range_end   Range end (Y-m-d).
	 * @return bool
	 */
	private function is_date_in_range( $date, $range_start, $range_end ) {
		return $date >= $range_start && $date <= $range_end;
	}

	/**
	 * Clear pricing for a room.
	 *
	 * @param int $room_id Room ID.
	 * @return void
	 */
	private function clear_pricing( $room_id ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'pricing';

		$wpdb->delete(
			$table,
			array( 'room_id' => $room_id ),
			array( '%d' )
		);
	}

	/**
	 * Get price for a room on a specific date.
	 *
	 * @param int    $room_id Room ID.
	 * @param string $date    Date (Y-m-d).
	 * @return float|false
	 */
	public function get_price( $room_id, $date ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'hb_';
		$table  = $prefix . 'pricing';

		$price = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT price FROM $table
				WHERE room_id = %d
				AND start_date <= %s
				AND end_date >= %s
				ORDER BY start_date DESC
				LIMIT 1",
				$room_id,
				$date,
				$date
			)
		);

		return $price ? floatval( $price ) : false;
	}

	/**
	 * Calculate total price for a booking.
	 *
	 * @param int    $room_id   Room ID.
	 * @param string $check_in  Check-in date (Y-m-d).
	 * @param string $check_out Check-out date (Y-m-d).
	 * @return float
	 */
	public function calculate_total_price( $room_id, $check_in, $check_out ) {
		$check_in_date  = new DateTime( $check_in );
		$check_out_date = new DateTime( $check_out );
		$interval       = $check_in_date->diff( $check_out_date );
		$nights         = $interval->days;

		if ( $nights <= 0 ) {
			return 0;
		}

		$total = 0;
		$current = clone $check_in_date;

		for ( $i = 0; $i < $nights; $i++ ) {
			$date_str = $current->format( 'Y-m-d' );
			$price    = $this->get_price( $room_id, $date_str );

			if ( $price ) {
				$total += $price;
			}

			$current->modify( '+1 day' );
		}

		return round( $total, 2 );
	}
}
