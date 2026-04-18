<?php
/**
 * Custom Post Types for Hotel Booking
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Types Class
 */
class Hotel_Booking_Post_Types {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Post_Types
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Post_Types
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
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
	}

	/**
	 * Register custom post types.
	 *
	 * @return void
	 */
	public function register_post_types() {
		// Room Post Type
		$labels = array(
			'name'                  => _x( 'Rooms', 'Post Type General Name', 'hotel-booking' ),
			'singular_name'         => _x( 'Room', 'Post Type Singular Name', 'hotel-booking' ),
			'menu_name'             => __( 'Rooms', 'hotel-booking' ),
			'name_admin_bar'        => __( 'Room', 'hotel-booking' ),
			'archives'              => __( 'Room Archives', 'hotel-booking' ),
			'attributes'            => __( 'Room Attributes', 'hotel-booking' ),
			'parent_item_colon'     => __( 'Parent Room:', 'hotel-booking' ),
			'all_items'             => __( 'All Rooms', 'hotel-booking' ),
			'add_new_item'          => __( 'Add New Room', 'hotel-booking' ),
			'add_new'               => __( 'Add New', 'hotel-booking' ),
			'new_item'              => __( 'New Room', 'hotel-booking' ),
			'edit_item'             => __( 'Edit Room', 'hotel-booking' ),
			'update_item'           => __( 'Update Room', 'hotel-booking' ),
			'view_item'             => __( 'View Room', 'hotel-booking' ),
			'view_items'            => __( 'View Rooms', 'hotel-booking' ),
			'search_items'          => __( 'Search Room', 'hotel-booking' ),
			'not_found'             => __( 'Not found', 'hotel-booking' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'hotel-booking' ),
			'featured_image'        => __( 'Room Image', 'hotel-booking' ),
			'set_featured_image'    => __( 'Set room image', 'hotel-booking' ),
			'remove_featured_image' => __( 'Remove room image', 'hotel-booking' ),
			'use_featured_image'    => __( 'Use as room image', 'hotel-booking' ),
		);

		$args = array(
			'label'                 => __( 'Room', 'hotel-booking' ),
			'description'           => __( 'Hotel rooms', 'hotel-booking' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'taxonomies'            => array( 'room_type', 'room_amenity' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => 'hotel-booking',
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-building',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'post',
			'show_in_rest'          => true,
			'rest_base'             => 'rooms',
		);

		register_post_type( 'hb_room', $args );

		// Booking Post Type (for admin view)
		$booking_labels = array(
			'name'                  => _x( 'Bookings', 'Post Type General Name', 'hotel-booking' ),
			'singular_name'         => _x( 'Booking', 'Post Type Singular Name', 'hotel-booking' ),
			'menu_name'             => __( 'Bookings', 'hotel-booking' ),
			'name_admin_bar'        => __( 'Booking', 'hotel-booking' ),
			'all_items'             => __( 'All Bookings', 'hotel-booking' ),
			'add_new_item'          => __( 'Add New Booking', 'hotel-booking' ),
			'add_new'               => __( 'Add New', 'hotel-booking' ),
			'new_item'              => __( 'New Booking', 'hotel-booking' ),
			'edit_item'             => __( 'Edit Booking', 'hotel-booking' ),
			'update_item'           => __( 'Update Booking', 'hotel-booking' ),
			'view_item'             => __( 'View Booking', 'hotel-booking' ),
			'view_items'            => __( 'View Bookings', 'hotel-booking' ),
			'search_items'          => __( 'Search Booking', 'hotel-booking' ),
			'not_found'             => __( 'Not found', 'hotel-booking' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'hotel-booking' ),
		);

		$booking_args = array(
			'label'                 => __( 'Booking', 'hotel-booking' ),
			'description'           => __( 'Hotel bookings', 'hotel-booking' ),
			'labels'                => $booking_labels,
			'supports'              => array( 'title' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => 'hotel-booking',
			'menu_position'         => 10,
			'menu_icon'             => 'dashicons-calendar-alt',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
			'show_in_rest'          => true,
			'rest_base'             => 'bookings',
		);

		register_post_type( 'hb_booking', $booking_args );
	}

	/**
	 * Register custom taxonomies.
	 *
	 * @return void
	 */
	public function register_taxonomies() {
		// Room Type Taxonomy
		$labels = array(
			'name'                       => _x( 'Room Types', 'Taxonomy General Name', 'hotel-booking' ),
			'singular_name'              => _x( 'Room Type', 'Taxonomy Singular Name', 'hotel-booking' ),
			'menu_name'                  => __( 'Room Types', 'hotel-booking' ),
			'all_items'                  => __( 'All Room Types', 'hotel-booking' ),
			'parent_item'                => __( 'Parent Room Type', 'hotel-booking' ),
			'parent_item_colon'          => __( 'Parent Room Type:', 'hotel-booking' ),
			'new_item_name'              => __( 'New Room Type Name', 'hotel-booking' ),
			'add_new_item'               => __( 'Add New Room Type', 'hotel-booking' ),
			'edit_item'                  => __( 'Edit Room Type', 'hotel-booking' ),
			'update_item'                => __( 'Update Room Type', 'hotel-booking' ),
			'view_item'                  => __( 'View Room Type', 'hotel-booking' ),
			'separate_items_with_commas' => __( 'Separate room types with commas', 'hotel-booking' ),
			'add_or_remove_items'        => __( 'Add or remove room types', 'hotel-booking' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'hotel-booking' ),
			'popular_items'              => __( 'Popular Room Types', 'hotel-booking' ),
			'search_items'               => __( 'Search Room Types', 'hotel-booking' ),
			'not_found'                  => __( 'Not Found', 'hotel-booking' ),
			'no_terms'                   => __( 'No room types', 'hotel-booking' ),
			'items_list'                 => __( 'Room Types list', 'hotel-booking' ),
			'items_list_navigation'     => __( 'Room Types list navigation', 'hotel-booking' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
		);

		register_taxonomy( 'room_type', array( 'hb_room' ), $args );

		// Room Amenity Taxonomy
		$amenity_labels = array(
			'name'                       => _x( 'Amenities', 'Taxonomy General Name', 'hotel-booking' ),
			'singular_name'              => _x( 'Amenity', 'Taxonomy Singular Name', 'hotel-booking' ),
			'menu_name'                  => __( 'Amenities', 'hotel-booking' ),
			'all_items'                  => __( 'All Amenities', 'hotel-booking' ),
			'parent_item'                => __( 'Parent Amenity', 'hotel-booking' ),
			'parent_item_colon'          => __( 'Parent Amenity:', 'hotel-booking' ),
			'new_item_name'              => __( 'New Amenity Name', 'hotel-booking' ),
			'add_new_item'               => __( 'Add New Amenity', 'hotel-booking' ),
			'edit_item'                  => __( 'Edit Amenity', 'hotel-booking' ),
			'update_item'                => __( 'Update Amenity', 'hotel-booking' ),
			'view_item'                  => __( 'View Amenity', 'hotel-booking' ),
			'separate_items_with_commas' => __( 'Separate amenities with commas', 'hotel-booking' ),
			'add_or_remove_items'        => __( 'Add or remove amenities', 'hotel-booking' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'hotel-booking' ),
			'popular_items'              => __( 'Popular Amenities', 'hotel-booking' ),
			'search_items'               => __( 'Search Amenities', 'hotel-booking' ),
			'not_found'                  => __( 'Not Found', 'hotel-booking' ),
			'no_terms'                   => __( 'No amenities', 'hotel-booking' ),
			'items_list'                 => __( 'Amenities list', 'hotel-booking' ),
			'items_list_navigation'     => __( 'Amenities list navigation', 'hotel-booking' ),
		);

		$amenity_args = array(
			'labels'            => $amenity_labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
		);

		register_taxonomy( 'room_amenity', array( 'hb_room' ), $amenity_args );
	}

	/**
	 * Custom updated messages.
	 *
	 * @param array $messages Post updated messages.
	 * @return array
	 */
	public function updated_messages( $messages ) {
		global $post;

		$permalink = get_permalink( $post );
		$preview_url = get_preview_post_link( $post );

		$messages['hb_room'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( __( 'Room updated. <a href="%s">View room</a>', 'hotel-booking' ), esc_url( $permalink ) ),
			2  => __( 'Custom field updated.', 'hotel-booking' ),
			3  => __( 'Custom field deleted.', 'hotel-booking' ),
			4  => __( 'Room updated.', 'hotel-booking' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Room restored to revision from %s', 'hotel-booking' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( 'Room published. <a href="%s">View room</a>', 'hotel-booking' ), esc_url( $permalink ) ),
			7  => __( 'Room saved.', 'hotel-booking' ),
			8  => sprintf( __( 'Room submitted. <a target="_blank" href="%s">Preview room</a>', 'hotel-booking' ), esc_url( $preview_url ) ),
			9  => sprintf( __( 'Room scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview room</a>', 'hotel-booking' ), date_i18n( __( 'M j, Y @ G:i', 'hotel-booking' ), strtotime( $post->post_date ) ), esc_url( $permalink ) ),
			10 => sprintf( __( 'Room draft updated. <a target="_blank" href="%s">Preview room</a>', 'hotel-booking' ), esc_url( $preview_url ) ),
		);

		$messages['hb_booking'] = array(
			0  => '',
			1  => __( 'Booking updated.', 'hotel-booking' ),
			2  => __( 'Custom field updated.', 'hotel-booking' ),
			3  => __( 'Custom field deleted.', 'hotel-booking' ),
			4  => __( 'Booking updated.', 'hotel-booking' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Booking restored to revision from %s', 'hotel-booking' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Booking published.', 'hotel-booking' ),
			7  => __( 'Booking saved.', 'hotel-booking' ),
			8  => __( 'Booking submitted.', 'hotel-booking' ),
			9  => sprintf( __( 'Booking scheduled for: <strong>%1$s</strong>.', 'hotel-booking' ), date_i18n( __( 'M j, Y @ G:i', 'hotel-booking' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Booking draft updated.', 'hotel-booking' ),
		);

		return $messages;
	}
}
