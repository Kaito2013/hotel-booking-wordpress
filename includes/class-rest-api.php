<?php
/**
 * REST API Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Class
 */
class Hotel_Booking_REST_API {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_REST_API
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_REST_API
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		$namespace = 'hotel-booking/v1';

		// Rooms routes
		register_rest_route(
			$namespace,
			'/rooms',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_rooms' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$namespace,
			'/rooms/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_room' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		// Availability routes
		register_rest_route(
			$namespace,
			'/availability',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'check_availability' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		// Pricing routes
		register_rest_route(
			$namespace,
			'/pricing',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'calculate_price' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		// Booking routes
		register_rest_route(
			$namespace,
			'/bookings',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_booking' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$namespace,
			'/bookings/(?P<id>\\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_booking' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);

		register_rest_route(
			$namespace,
			'/bookings/(?P<id>\\d+)/cancel',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cancel_booking' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);

		// Settings routes
		register_rest_route(
			$namespace,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);
	}

	/**
	 * Get rooms.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_rooms( $request ) {
		$availability = Hotel_Booking_Availability_Manager::get_instance();

		$check_in  = $request->get_param( 'check_in' );
		$check_out = $request->get_param( 'check_out' );
		$guests    = $request->get_param( 'guests' );
		$room_type = $request->get_param( 'room_type' );

		$args = array();

		if ( $guests ) {
			$args['guests'] = absint( $guests );
		}

		if ( $room_type ) {
			$args['room_type'] = sanitize_text_field( $room_type );
		}

		if ( $check_in && $check_out ) {
			$rooms = $availability->get_available_rooms( $check_in, $check_out, $args );
		} else {
			$rooms = get_posts(
				array(
					'post_type'      => 'hb_room',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
				)
			);
		}

		$data = array();

		foreach ( $rooms as $room ) {
			$data[] = $this->prepare_room_data( $room );
		}

		return new WP_REST_Response( array( 'rooms' => $data ), 200 );
	}

	/**
	 * Get room by ID.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_room( $request ) {
		$room_id = $request->get_param( 'id' );
		$room    = get_post( $room_id );

		if ( ! $room || 'hb_room' !== $room->post_type ) {
			return new WP_Error( 'room_not_found', __( 'Room not found', 'hotel-booking' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( array( 'room' => $this->prepare_room_data( $room ) ), 200 );
	}

	/**
	 * Check availability.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function check_availability( $request ) {
		$room_id   = $request->get_param( 'room_id' );
		$check_in  = $request->get_param( 'check_in' );
		$check_out = $request->get_param( 'check_out' );

		if ( ! $room_id || ! $check_in || ! $check_out ) {
			return new WP_Error( 'missing_params', __( 'Missing required parameters', 'hotel-booking' ), array( 'status' => 400 ) );
		}

		$availability = Hotel_Booking_Availability_Manager::get_instance();
		$is_available = $availability->is_available( $room_id, $check_in, $check_out );

		return new WP_REST_Response(
			array(
				'available' => $is_available,
				'room_id'   => $room_id,
				'check_in'  => $check_in,
				'check_out' => $check_out,
			),
			200
		);
	}

	/**
	 * Calculate price.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function calculate_price( $request ) {
		$room_id   = $request->get_param( 'room_id' );
		$check_in  = $request->get_param( 'check_in' );
		$check_out = $request->get_param( 'check_out' );

		if ( ! $room_id || ! $check_in || ! $check_out ) {
			return new WP_Error( 'missing_params', __( 'Missing required parameters', 'hotel-booking' ), array( 'status' => 400 ) );
		}

		$pricing = Hotel_Booking_Pricing_Manager::get_instance();
		$price   = $pricing->calculate_total_price( $room_id, $check_in, $check_out );

		return new WP_REST_Response(
			array(
				'price'      => $price,
				'currency'   => get_option( 'hb_currency', 'USD' ),
				'symbol'     => get_option( 'hb_currency_symbol', '$' ),
				'room_id'    => $room_id,
				'check_in'   => $check_in,
				'check_out'  => $check_out,
			),
			200
		);
	}

	/**
	 * Create booking.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_booking( $request ) {
		$data = json_decode( $request->get_body(), true );

		$booking_manager = Hotel_Booking_Booking_Manager::get_instance();
		$booking_id      = $booking_manager->create_booking( $data );

		if ( is_wp_error( $booking_id ) ) {
			return $booking_id;
		}

		$booking = $booking_manager->get_booking( $booking_id );

		return new WP_REST_Response(
			array(
				'success'     => true,
				'booking_id'  => $booking_id,
				'booking'     => $booking,
				'message'     => __( 'Booking created successfully', 'hotel-booking' ),
			),
			201
		);
	}

	/**
	 * Get booking by ID.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_booking( $request ) {
		$booking_id = $request->get_param( 'id' );
		$user_id    = get_current_user_id();

		$booking_manager = Hotel_Booking_Booking_Manager::get_instance();
		$booking         = $booking_manager->get_booking( $booking_id );

		if ( ! $booking ) {
			return new WP_Error( 'booking_not_found', __( 'Booking not found', 'hotel-booking' ), array( 'status' => 404 ) );
		}

		// Check if user owns this booking or is admin
		if ( $booking->user_id != $user_id && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'permission_denied', __( 'Permission denied', 'hotel-booking' ), array( 'status' => 403 ) );
		}

		return new WP_REST_Response( array( 'booking' => $booking ), 200 );
	}

	/**
	 * Get settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_settings( $request ) {
		$settings = array(
			'currency'        => get_option( 'hb_currency', 'USD' ),
			'currency_symbol' => get_option( 'hb_currency_symbol', '$' ),
			'default_check_in'  => get_option( 'hb_default_check_in', '14:00' ),
			'default_check_out' => get_option( 'hb_default_check_out', '11:00' ),
		);

		return new WP_REST_Response( array( 'settings' => $settings ), 200 );
	}

	/**
	 * Prepare room data for API response.
	 *
	 * @param WP_Post $room Room post object.
	 * @return array
	 */
	private function prepare_room_data( $room ) {
		$room_id = $room->ID;

		$data = array(
			'id'          => $room_id,
			'title'       => $room->post_title,
			'description' => $room->post_content,
			'excerpt'     => $room->post_excerpt,
			'image'       => get_the_post_thumbnail_url( $room_id, 'large' ),
			'capacity'    => get_post_meta( $room_id, '_hb_room_capacity', true ),
			'price'       => get_post_meta( $room_id, '_hb_room_price', true ),
			'size'        => get_post_meta( $room_id, '_hb_room_size', true ),
			'beds'        => get_post_meta( $room_id, '_hb_room_beds', true ),
			'amenities'   => wp_get_post_terms( $room_id, 'room_amenity', array( 'fields' => 'names' ) ),
			'types'       => wp_get_post_terms( $room_id, 'room_type', array( 'fields' => 'names' ) ),
		);

		return $data;
	}

	/**
	 * Cancel booking.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function cancel_booking( $request ) {
		$booking_id = $request->get_param( 'id' );
		$user_id = get_current_user_id();
		$body = json_decode( $request->get_body(), true );
		$reason = isset( $body['reason'] ) ? sanitize_text_field( $body['reason'] ) : '';

		// Get booking
		global $wpdb;
		$table_name = $wpdb->prefix . 'hb_bookings';
		$booking = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE id = %d",
				$booking_id
			)
		);

		if ( ! $booking ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Booking not found',
				),
				404
			);
		}

		// Check if booking belongs to user
		// Note: For guest bookings, we might check email instead
		if ( $booking->user_id && $booking->user_id != $user_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'You do not have permission to cancel this booking',
				),
				403
			);
		}

		// Check if booking can be cancelled
		if ( 'cancelled' === $booking->booking_status ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Booking is already cancelled',
				),
				400
			);
		}

		if ( 'completed' === $booking->booking_status ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Cannot cancel a completed booking',
				),
				400
			);
		}

		// Update booking status
		$updated = $wpdb->update(
			$table_name,
			array(
				'booking_status' => 'cancelled',
				'notes'          => $reason ? $booking->notes . "\n\nCancellation reason: " . $reason : $booking->notes,
			),
			array( 'id' => $booking_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Error cancelling booking',
				),
				500
			);
		}

		// Release room availability
		$availability = Hotel_Booking_Availability_Manager::get_instance();
		$availability->release_dates( $booking_id );

		// Send cancellation notification
		$notification = Hotel_Booking_Notification_Manager::get_instance();
		$notification->send_cancellation_email( $booking_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Booking cancelled successfully',
			),
			200
		);
	}
}
