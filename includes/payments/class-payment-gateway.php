<?php
/**
 * Payment Gateway Base Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;

}

/**
 * Payment Gateway Class
 */
abstract class Hotel_Booking_Payment_Gateway {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Gateway title.
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Gateway description.
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Payment_Gateway
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Payment_Gateway
	 */
	public static function get_instance() {
		return null; // This is abstract, each gateway implements its own singleton
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize gateway.
	 *
	 * @return void
	 */
	protected function init() {
		// Override in child classes
	}

	/**
	 * Get gateway ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get gateway title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Get gateway description.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Check if gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return false; // Override in child classes
	}

	/**
	 * Process payment.
	 *
	 * @param int   $booking_id Booking ID.
	 * @param array $data       Payment data.
	 * @return array|WP_Error
	 */
	abstract public function process_payment( $booking_id, $data );

	/**
	 * Process webhook/callback.
	 *
	 * @return void
	 */
	public function process_webhook() {
		// Override in child classes
	}
}
