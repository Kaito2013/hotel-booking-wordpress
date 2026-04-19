<?php
/**
 * Plugin Name: Hotel Booking System
 * Plugin URI:  https://github.com/Kaito2013/hotel-booking-wordpress
 * Description: Complete hotel booking system for WordPress with room management, availability calendar, pricing, online booking, and payment integration (Stripe & PayPal).
 * Version:     1.0.0
 * Author:      Kaito2013
 * Author URI:  https://github.com/Kaito2013
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: hotel-booking
 * Domain Path: /languages
 * Requires PHP: 8.0
 * Requires at least: 6.4
 *
 * @package Hotel_Booking
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants.
define( 'HOTEL_BOOKING_VERSION', '1.0.0' );
define( 'HOTEL_BOOKING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HOTEL_BOOKING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HOTEL_BOOKING_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Hotel Booking Plugin Class
 *
 * @since 1.0.0
 */
final class Hotel_Booking {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking
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
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files.
	 *
	 * @return void
	 */
	private function includes() {
		// Core classes
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/class-post-types.php';
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/class-availability-manager.php';
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/class-pricing-manager.php';
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/class-booking-manager.php';

		// Admin classes
		if ( is_admin() ) {
			require_once HOTEL_BOOKING_PLUGIN_DIR . 'admin/class-admin.php';
			require_once HOTEL_BOOKING_PLUGIN_DIR . 'admin/class-admin-settings.php';
			require_once HOTEL_BOOKING_PLUGIN_DIR . 'admin/class-admin-dashboard.php';
			require_once HOTEL_BOOKING_PLUGIN_DIR . 'admin/class-admin-calendar.php';
			require_once HOTEL_BOOKING_PLUGIN_DIR . 'admin/class-room-metaboxes.php';
			require_once HOTEL_BOOKING_PLUGIN_DIR . 'admin/class-room-gallery.php';
			require_once HOTEL_BOOKING_PLUGIN_DIR . 'admin/class-admin-reports.php';
		}

		// Payment gateways
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/payments/class-payment-gateway.php';
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/payments/class-stripe-gateway.php';
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/payments/class-paypal-gateway.php';

		// Email templates
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/class-email-template-manager.php';

		// Notifications
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/class-notification-manager.php';

		// REST API
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/class-rest-api.php';

		// Frontend
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/class-frontend.php';

		// Coupon Manager
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/class-coupon-manager.php';

		// Reviews Manager
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/class-reviews-manager.php';

		// Seasonal Pricing
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/class-seasonal-pricing.php';

		// Export Manager
		require_once HOTEL_BOOKING_PLUGIN_DIR . 'includes/class-export-manager.php';
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Activation/Deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Initialize plugin
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		// Create database tables
		$this->create_tables();

		// Set default options
		$this->set_default_options();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Create custom database tables.
	 *
	 * @return void
	 */
	private function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix . 'hb_';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Availability table
		$table_availability = $prefix . 'availability';
		$sql_availability   = "CREATE TABLE $table_availability (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			room_id bigint(20) UNSIGNED NOT NULL,
			check_in date NOT NULL,
			check_out date NOT NULL,
			status varchar(20) DEFAULT 'available',
			booking_id bigint(20) UNSIGNED DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY room_id (room_id),
			KEY check_in (check_in),
			KEY check_out (check_out),
			KEY status (status)
		) $charset_collate;";

		// Pricing table
		$table_pricing = $prefix . 'pricing';
		$sql_pricing   = "CREATE TABLE $table_pricing (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			room_id bigint(20) UNSIGNED NOT NULL,
			start_date date NOT NULL,
			end_date date NOT NULL,
			price decimal(10,2) NOT NULL,
			min_nights int(11) DEFAULT 1,
			max_nights int(11) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY room_id (room_id),
			KEY date_range (start_date, end_date)
		) $charset_collate;";

		// Bookings table
		$table_bookings = $prefix . 'bookings';
		$sql_bookings   = "CREATE TABLE $table_bookings (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			room_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			first_name varchar(100) NOT NULL,
			last_name varchar(100) NOT NULL,
			email varchar(100) NOT NULL,
			phone varchar(20) DEFAULT NULL,
			check_in date NOT NULL,
			check_out date NOT NULL,
			guests int(11) DEFAULT 1,
			total_price decimal(10,2) NOT NULL,
			payment_method varchar(50) DEFAULT 'stripe',
			payment_status varchar(20) DEFAULT 'pending',
			payment_id varchar(100) DEFAULT NULL,
			booking_status varchar(20) DEFAULT 'pending',
			notes text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY room_id (room_id),
			KEY user_id (user_id),
			KEY booking_status (booking_status),
			KEY payment_status (payment_status),
			KEY check_in (check_in),
			KEY check_out (check_out)
		) $charset_collate;";

		dbDelta( $sql_availability );
		dbDelta( $sql_pricing );
		dbDelta( $sql_bookings );
	}

	/**
	 * Set default plugin options.
	 *
	 * @return void
	 */
	private function set_default_options() {
		$defaults = array(
			'hb_currency'           => 'USD',
			'hb_currency_symbol'    => '$',
			'hb_default_check_in'   => '14:00',
			'hb_default_check_out'  => '11:00',
			'hb_confirmation_email' => '1',
			'hb_reminder_email'     => '1',
		);

		foreach ( $defaults as $key => $value ) {
			if ( get_option( $key ) === false ) {
				update_option( $key, $value );
			}
		}
	}

	/**
	 * Initialize plugin.
	 *
	 * @return void
	 */
	public function init() {
		// Initialize classes
		Hotel_Booking_Post_Types::get_instance();
		Hotel_Booking_Availability_Manager::get_instance();
		Hotel_Booking_Pricing_Manager::get_instance();
		Hotel_Booking_Booking_Manager::get_instance();

		if ( is_admin() ) {
			Hotel_Booking_Admin::get_instance();
			Hotel_Booking_Admin_Settings::get_instance();
			Hotel_Booking_Admin_Dashboard::get_instance();
			Hotel_Booking_Admin_Calendar::get_instance();
		}

		Hotel_Booking_Payment_Gateway::get_instance();
		Hotel_Booking_Email_Template_Manager::get_instance();
		Hotel_Booking_Notification_Manager::get_instance();
		Hotel_Booking_REST_API::get_instance();
		Hotel_Booking_Frontend::get_instance();

		// Initialize payment gateways
		Hotel_Booking_Stripe_Gateway::get_instance();
		Hotel_Booking_PayPal_Gateway::get_instance();

		// Register PayPal return handlers
		add_action( 'wp_ajax_hb_paypal_success', array( Hotel_Booking_PayPal_Gateway::get_instance(), 'handle_paypal_success' ) );
		add_action( 'wp_ajax_nopriv_hb_paypal_success', array( Hotel_Booking_PayPal_Gateway::get_instance(), 'handle_paypal_success' ) );
		add_action( 'wp_ajax_hb_paypal_cancel', array( Hotel_Booking_PayPal_Gateway::get_instance(), 'handle_paypal_cancel' ) );
		add_action( 'wp_ajax_nopriv_hb_paypal_cancel', array( Hotel_Booking_PayPal_Gateway::get_instance(), 'handle_paypal_cancel' ) );

		// Register Stripe webhook handler
		add_action( 'init', array( Hotel_Booking_Stripe_Gateway::get_instance(), 'register_webhook_endpoint' ), 0 );
		add_action( 'template_redirect', array( Hotel_Booking_Stripe_Gateway::get_instance(), 'handle_webhook_request' ) );
	}

	/**
	 * Load plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'hotel-booking',
			false,
			dirname( HOTEL_BOOKING_PLUGIN_BASENAME ) . '/languages'
		);
	}
}

/**
 * Initialize the plugin.
 */
function hotel_booking() {
	return Hotel_Booking::get_instance();
}

// Start the plugin.
hotel_booking();
