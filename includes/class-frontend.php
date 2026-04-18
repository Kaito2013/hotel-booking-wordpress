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
		add_shortcode( 'hotel_booking_room_detail', array( $this, 'render_room_detail' ) );
		add_shortcode( 'hotel_booking_my_bookings', array( $this, 'render_my_bookings' ) );
		add_shortcode( 'hotel_booking_confirmation', array( $this, 'render_booking_confirmation' ) );
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
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

		// Payment CSS (only on pages that need it)
		if ( is_page() && ( get_query_var( 'hb_page' ) || get_query_var( 'hb_room_id' ) ) ) {
			wp_enqueue_style(
				'hotel-booking-payment',
				HOTEL_BOOKING_PLUGIN_URL . 'assets/css/payment.css',
				array(),
				HOTEL_BOOKING_VERSION
			);
		}

		// Payment JS (only on pages that need it)
		if ( is_page() && ( get_query_var( 'hb_page' ) || get_query_var( 'hb_room_id' ) ) ) {
			wp_enqueue_script(
				'hotel-booking-payment',
				HOTEL_BOOKING_PLUGIN_URL . 'assets/js/payment.js',
				array( 'jquery' ),
				HOTEL_BOOKING_VERSION,
				true
			);
		}

		// Stripe SDK (if enabled)
		if ( '1' === get_option( 'hb_stripe_enabled', '0' ) ) {
			$stripe_key = '1' === get_option( 'hb_stripe_test_mode', '0' )
				? get_option( 'hb_stripe_test_public', '' )
				: get_option( 'hb_stripe_live_public', '' );

			if ( $stripe_key ) {
				wp_enqueue_script(
					'stripe-js',
					'https://js.stripe.com/v3/',
					array(),
					null,
					true
				);
				wp_script_add_data( 'stripe-js', 'async', true );
			}
		}

		// PayPal SDK (if enabled)
		if ( '1' === get_option( 'hb_paypal_enabled', '0' ) ) {
			$paypal_client_id = '1' === get_option( 'hb_paypal_test_mode', '0' )
				? get_option( 'hb_paypal_sandbox_client_id', '' )
				: get_option( 'hb_paypal_client_id', '' );

			if ( $paypal_client_id ) {
				wp_enqueue_script(
					'paypal-js',
					'https://www.paypal.com/sdk/js?client-id=' . $paypal_client_id . '&currency=' . get_option( 'hb_currency', 'USD' ),
					array(),
					null,
					true
				);
				wp_script_add_data( 'paypal-js', 'async', true );
			}
		}

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

	/**
	 * Add rewrite rules for custom URLs.
	 *
	 * @return void
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule(
			'^room/([^/]+)/?$',
			'index.php?hb_room_id=$matches[1]',
			'top'
		);

		add_rewrite_rule(
			'^my-bookings/?$',
			'index.php?hb_page=my-bookings',
			'top'
		);

		add_rewrite_rule(
			'^booking-confirmation/?$',
			'index.php?hb_page=booking-confirmation',
			'top'
		);

		add_rewrite_tag( '%hb_room_id%', '([^&]+)' );
		add_rewrite_tag( '%hb_page%', '([^&]+)' );
	}

	/**
	 * Template redirect handler.
	 *
	 * @return void
	 */
	public function template_redirect() {
		$room_id = get_query_var( 'hb_room_id' );
		$page = get_query_var( 'hb_page' );

		if ( $room_id ) {
			$this->render_room_detail_page( $room_id );
			exit;
		}

		if ( 'my-bookings' === $page ) {
			$this->render_my_bookings_page();
			exit;
		}

		if ( 'booking-confirmation' === $page ) {
			$this->render_booking_confirmation_page();
			exit;
		}
	}

	/**
	 * Render room detail page.
	 *
	 * @param string $room_id Room ID or slug.
	 * @return void
	 */
	private function render_room_detail_page( $room_id ) {
		// Try to get room by slug first, then by ID
		$room = get_page_by_path( $room_id, OBJECT, 'hb_room' );

		if ( ! $room ) {
			$room = get_post( absint( $room_id ) );
		}

		if ( ! $room || 'hb_room' !== $room->post_type ) {
			wp_die( 'Room not found', 'Room Not Found', array( 'response' => 404 ) );
		}

		// Get query parameters
		$check_in = isset( $_GET['check_in'] ) ? sanitize_text_field( $_GET['check_in'] ) : '';
		$check_out = isset( $_GET['check_out'] ) ? sanitize_text_field( $_GET['check_out'] ) : '';
		$guests = isset( $_GET['guests'] ) ? absint( $_GET['guests'] ) : 1;

		// Set global data for template
		global $hb_room_data;
		$hb_room_data = array(
			'id'        => $room->ID,
			'check_in'  => $check_in,
			'check_out' => $check_out,
			'guests'    => $guests,
		);

		// Load template
		$template_path = HOTEL_BOOKING_PLUGIN_DIR . 'templates/room-detail.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			wp_die( 'Template not found', 'Template Not Found', array( 'response' => 500 ) );
		}
	}

	/**
	 * Render my bookings page.
	 *
	 * @return void
	 */
	private function render_my_bookings_page() {
		$template_path = HOTEL_BOOKING_PLUGIN_DIR . 'templates/my-bookings.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			wp_die( 'Template not found', 'Template Not Found', array( 'response' => 500 ) );
		}
	}

	/**
	 * Render booking confirmation page.
	 *
	 * @return void
	 */
	private function render_booking_confirmation_page() {
		$template_path = HOTEL_BOOKING_PLUGIN_DIR . 'templates/booking-confirmation.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			wp_die( 'Template not found', 'Template Not Found', array( 'response' => 500 ) );
		}
	}

	/**
	 * Render room detail shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_room_detail( $atts ) {
		$atts = shortcode_atts(
			array(
				'room_id'   => 0,
				'check_in'  => '',
				'check_out' => '',
				'guests'    => 1,
			),
			$atts
		);

		$room_id = absint( $atts['room_id'] );

		if ( ! $room_id ) {
			return '<div class="hb-message error">Room ID is required.</div>';
		}

		$room = get_post( $room_id );

		if ( ! $room || 'hb_room' !== $room->post_type ) {
			return '<div class="hb-message error">Room not found.</div>';
		}

		// Set global data for template
		global $hb_room_data;
		$hb_room_data = array(
			'id'        => $room_id,
			'check_in'  => $atts['check_in'],
			'check_out' => $atts['check_out'],
			'guests'    => absint( $atts['guests'] ),
		);

		ob_start();
		include HOTEL_BOOKING_PLUGIN_DIR . 'templates/room-detail.php';
		return ob_get_clean();
	}

	/**
	 * Render my bookings shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_my_bookings( $atts ) {
		if ( ! is_user_logged_in() ) {
			ob_start();
			?>
			<div class="hb-message error">
				<p>You must be logged in to view your bookings.</p>
				<a href="<?php echo esc_url( wp_login_url() ); ?>" class="button">Login</a>
			</div>
			<?php
			return ob_get_clean();
		}

		ob_start();
		include HOTEL_BOOKING_PLUGIN_DIR . 'templates/my-bookings.php';
		return ob_get_clean();
	}

	/**
	 * Render booking confirmation shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_booking_confirmation( $atts ) {
		$atts = shortcode_atts(
			array(
				'booking_id' => 0,
			),
			$atts
		);

		$booking_id = absint( $atts['booking_id'] );

		if ( ! $booking_id ) {
			$booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;
		}

		if ( ! $booking_id ) {
			return '<div class="hb-message error">Booking ID is required.</div>';
		}

		ob_start();
		include HOTEL_BOOKING_PLUGIN_DIR . 'templates/booking-confirmation.php';
		return ob_get_clean();
	}
}
