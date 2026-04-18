<?php
/**
 * Frontend Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Class
 */
class Hotel_Booking_Frontend {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Frontend
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Frontend
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_shortcode( 'hotel_booking', array( $this, 'render_shortcode' ) );
		add_shortcode( 'hotel_booking_search', array( $this, 'render_search_form' ) );
		add_shortcode( 'hotel_booking_rooms', array( $this, 'render_rooms_list' ) );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'hotel-booking-frontend',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			HOTEL_BOOKING_VERSION
		);

		wp_enqueue_script(
			'hotel-booking-frontend',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			HOTEL_BOOKING_VERSION,
			true
		);

		wp_localize_script(
			'hotel-booking-frontend',
			'hotelBookingSettings',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'restUrl'        => rest_url( 'hotel-booking/v1/' ),
				'currency'       => get_option( 'hb_currency', 'USD' ),
				'currencySymbol' => get_option( 'hb_currency_symbol', '$' ),
			)
		);
	}

	/**
	 * Render main shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'room_type' => '',
			),
			$atts
		);

		ob_start();
		?>
		<div class="hb-booking-container">
			<?php echo $this->render_search_form( $atts ); ?>
			<?php echo $this->render_rooms_list( $atts ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render search form.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render_search_form( $atts ) {
		$room_types = get_terms(
			array(
				'taxonomy'   => 'room_type',
				'hide_empty' => false,
			)
		);

		ob_start();
		?>
		<div class="hb-search-form">
			<h2>Find Your Perfect Room</h2>
			<form>
				<div class="hb-search-form-fields">
					<div class="hb-form-group">
						<label for="check_in">Check-in Date *</label>
						<input type="date" id="check_in" name="check_in" required min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
					</div>
					<div class="hb-form-group">
						<label for="check_out">Check-out Date *</label>
						<input type="date" id="check_out" name="check_out" required min="<?php echo esc_attr( date( 'Y-m-d', strtotime( '+1 day' ) ) ); ?>">
					</div>
					<div class="hb-form-group">
						<label for="guests">Guests</label>
						<select id="guests" name="guests">
							<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
								<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, 1 ); ?>>
									<?php echo esc_html( $i . ' ' . ( $i > 1 ? 'Guests' : 'Guest' ) ); ?>
								</option>
							<?php endfor; ?>
						</select>
					</div>
					<?php if ( ! empty( $room_types ) && ! is_wp_error( $room_types ) ) : ?>
						<div class="hb-form-group">
							<label for="room_type">Room Type</label>
							<select id="room_type" name="room_type">
								<option value="">All Types</option>
								<?php foreach ( $room_types as $room_type ) : ?>
									<option value="<?php echo esc_attr( $room_type->slug ); ?>">
										<?php echo esc_html( $room_type->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>
				</div>
				<button type="submit">Search Rooms</button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render rooms list.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render_rooms_list( $atts ) {
		ob_start();
		?>
		<div class="hb-room-list">
			<div class="hb-message info">
				Please select check-in and check-out dates to search for available rooms.
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
