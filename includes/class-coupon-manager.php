<?php
/**
 * Coupon Manager Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupon Manager Class
 */
class Hotel_Booking_Coupon_Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Coupon_Manager
	 */
	private static $instance = null;

	/**
	 * Coupon CPT slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'hb_coupon';

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Coupon_Manager
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
		add_action( 'init', array( $this, 'register_coupon_cpt' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_coupon_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_coupon' ), 10, 2 );
		add_action( 'wp_ajax_hb_apply_coupon', array( $this, 'ajax_apply_coupon' ) );
		add_action( 'wp_ajax_hb_remove_coupon', array( $this, 'ajax_remove_coupon' ) );
		add_action( 'wp_ajax_hb_validate_coupon', array( $this, 'ajax_validate_coupon' ) );
		add_action( 'admin_menu', array( $this, 'add_coupons_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Register coupon CPT.
	 *
	 * @return void
	 */
	public function register_coupon_cpt() {
		register_post_type( self::POST_TYPE, array(
			'labels'             => array(
				'name'               => __( 'Coupons', 'hotel-booking' ),
				'singular_name'      => __( 'Coupon', 'hotel-booking' ),
				'add_new'            => __( 'Add New Coupon', 'hotel-booking' ),
				'add_new_item'       => __( 'Add New Coupon', 'hotel-booking' ),
				'edit_item'          => __( 'Edit Coupon', 'hotel-booking' ),
				'view_item'          => __( 'View Coupon', 'hotel-booking' ),
				'all_items'          => __( 'All Coupons', 'hotel-booking' ),
				'search_items'       => __( 'Search Coupons', 'hotel-booking' ),
				'not_found'          => __( 'No coupons found', 'hotel-booking' ),
				'not_found_in_trash' => __( 'No coupons found in Trash', 'hotel-booking' ),
			),
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'supports'           => array( 'title' ),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		) );
	}

	/**
	 * Add coupons menu.
	 *
	 * @return void
	 */
	public function add_coupons_menu() {
		add_submenu_page(
			'hotel-booking',
			__( 'Coupons', 'hotel-booking' ),
			__( 'Coupons', 'hotel-booking' ),
			'manage_options',
			'edit.php?post_type=' . self::POST_TYPE
		);
	}

	/**
	 * Add coupon meta box.
	 *
	 * @return void
	 */
	public function add_coupon_meta_box() {
		add_meta_box(
			'hotel-booking-coupon-details',
			__( 'Coupon Details', 'hotel-booking' ),
			array( $this, 'render_coupon_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render coupon meta box.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_coupon_meta_box( $post ) {
		wp_nonce_field( 'hotel-booking-coupon', 'hb_coupon_nonce' );

		$discount_type     = get_post_meta( $post->ID, '_hb_discount_type', true ) ?: 'percentage';
		$discount_value    = get_post_meta( $post->ID, '_hb_discount_value', true ) ?: '';
		$min_amount       = get_post_meta( $post->ID, '_hb_min_amount', true ) ?: '';
		$max_discount     = get_post_meta( $post->ID, '_hb_max_discount', true ) ?: '';
		$usage_limit      = get_post_meta( $post->ID, '_hb_usage_limit', true ) ?: '';
		$used_count       = get_post_meta( $post->ID, '_hb_used_count', true ) ?: 0;
		$expiry_date      = get_post_meta( $post->ID, '_hb_expiry_date', true ) ?: '';
		$room_ids         = get_post_meta( $post->ID, '_hb_room_ids', true ) ?: array();
		$is_active        = get_post_meta( $post->ID, '_hb_is_active', true );
		$is_active        = $is_active !== '' ? $is_active : '1';

		// Get all rooms
		$rooms = get_posts( array(
			'post_type'      => 'hb_room',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="hb_coupon_code"><?php esc_html_e( 'Coupon Code', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<input type="text" id="hb_coupon_code" name="hb_coupon_code" value="<?php echo esc_attr( $post->post_title ); ?>" class="regular-text" required>
					<p class="description"><?php esc_html_e( 'Enter a unique coupon code (e.g., SUMMER2024). This is what customers will enter at checkout.', 'hotel-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hb_discount_type"><?php esc_html_e( 'Discount Type', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<select id="hb_discount_type" name="hb_discount_type">
						<option value="percentage" <?php selected( $discount_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage Discount', 'hotel-booking' ); ?></option>
						<option value="fixed" <?php selected( $discount_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount Discount', 'hotel-booking' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hb_discount_value"><?php esc_html_e( 'Discount Value', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<input type="number" id="hb_discount_value" name="hb_discount_value" value="<?php echo esc_attr( $discount_value ); ?>" class="small-text" min="0" step="0.01" required>
					<span id="hb_discount_unit"><?php echo 'percentage' === $discount_type ? '%' : get_option( 'hb_currency_symbol', '$' ); ?></span>
					<p class="description"><?php esc_html_e( 'Enter the discount amount. For percentage, enter a number between 0-100. For fixed, enter the amount.', 'hotel-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hb_min_amount"><?php esc_html_e( 'Minimum Booking Amount', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<input type="number" id="hb_min_amount" name="hb_min_amount" value="<?php echo esc_attr( $min_amount ); ?>" class="small-text" min="0" step="0.01">
					<p class="description"><?php esc_html_e( 'Minimum booking amount required to use this coupon. Leave empty for no minimum.', 'hotel-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hb_max_discount"><?php esc_html_e( 'Maximum Discount', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<input type="number" id="hb_max_discount" name="hb_max_discount" value="<?php echo esc_attr( $max_discount ); ?>" class="small-text" min="0" step="0.01">
					<p class="description"><?php esc_html_e( 'Maximum discount amount. Useful for percentage discounts. Leave empty for no limit.', 'hotel-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hb_usage_limit"><?php esc_html_e( 'Usage Limit', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<input type="number" id="hb_usage_limit" name="hb_usage_limit" value="<?php echo esc_attr( $usage_limit ); ?>" class="small-text" min="0">
					<span class="description"><?php echo esc_html( sprintf( __( 'Used: %d times', 'hotel-booking' ), $used_count ) ); ?></span>
					<p class="description"><?php esc_html_e( 'How many times this coupon can be used. Leave empty for unlimited.', 'hotel-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hb_expiry_date"><?php esc_html_e( 'Expiry Date', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<input type="date" id="hb_expiry_date" name="hb_expiry_date" value="<?php echo esc_attr( $expiry_date ); ?>">
					<p class="description"><?php esc_html_e( 'Coupon expiration date. Leave empty for no expiration.', 'hotel-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hb_room_ids"><?php esc_html_e( 'Applicable Rooms', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<select id="hb_room_ids" name="hb_room_ids[]" multiple="multiple" style="width: 100%; height: 150px;">
						<?php foreach ( $rooms as $room ) : ?>
							<option value="<?php echo esc_attr( $room->ID ); ?>" <?php selected( in_array( $room->ID, $room_ids, true ) ); ?>>
								<?php echo esc_html( $room->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Select specific rooms this coupon applies to. Leave empty for all rooms.', 'hotel-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'Status', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" name="hb_is_active" value="1" <?php checked( $is_active, '1' ); ?>>
						<?php esc_html_e( 'Active', 'hotel-booking' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<script>
		jQuery(document).ready(function($) {
			// Update discount unit when type changes
			$('#hb_discount_type').on('change', function() {
				var type = $(this).val();
				var unit = type === 'percentage' ? '%' : '<?php echo esc_js( get_option( 'hb_currency_symbol', '$' ) ); ?>';
				$('#hb_discount_unit').text(unit);
			});
		});
		</script>

		<?php
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		global $post_type;

		if ( self::POST_TYPE !== $post_type ) {
			return;
		}

		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		// Enqueue select2 for multi-select
		wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/select2.min.css' );
		wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/select2.min.js', array( 'jquery' ), '4.1.0', true );

		// Enqueue coupon CSS
		wp_enqueue_style(
			'hotel-booking-coupons',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/css/coupons.css',
			array(),
			HOTEL_BOOKING_VERSION
		);

		// Enqueue coupon JS
		wp_enqueue_script(
			'hotel-booking-coupons',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/js/coupons.js',
			array( 'jquery', 'select2' ),
			HOTEL_BOOKING_VERSION,
			true
		);

		// Localize script
		wp_localize_script( 'hotel-booking-coupons', 'hbCoupons', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'hotel-booking-nonce' ),
			'strings' => array(
				'applying' => esc_html__( 'Applying...', 'hotel-booking' ),
				'removing' => esc_html__( 'Removing...', 'hotel-booking' ),
				'applied'  => esc_html__( 'Coupon applied!', 'hotel-booking' ),
				'removed'  => esc_html__( 'Coupon removed', 'hotel-booking' ),
			),
		) );
	}

	/**
	 * Save coupon.
	 *
	 * @param int      $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_coupon( $post_id, $post ) {
		// Verify nonce
		if ( ! isset( $_POST['hb_coupon_nonce'] ) || ! wp_verify_nonce( $_POST['hb_coupon_nonce'], 'hotel-booking-coupon' ) ) {
			return;
		}

		// Check if autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sanitize and save fields
		$fields = array(
			'hb_discount_type'  => 'sanitize_text_field',
			'hb_discount_value' => 'floatval',
			'hb_min_amount'     => 'floatval',
			'hb_max_discount'   => 'floatval',
			'hb_usage_limit'    => 'absint',
			'hb_expiry_date'    => 'sanitize_text_field',
			'hb_is_active'      => 'absint',
		);

		foreach ( $fields as $field => $sanitize_callback ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = call_user_func( $sanitize_callback, $_POST[ $field ] );
				update_post_meta( $post_id, '_' . $field, $value );
			}
		}

		// Save room IDs
		if ( isset( $_POST['hb_room_ids'] ) && is_array( $_POST['hb_room_ids'] ) ) {
			$room_ids = array_map( 'absint', $_POST['hb_room_ids'] );
			update_post_meta( $post_id, '_hb_room_ids', $room_ids );
		} else {
			update_post_meta( $post_id, '_hb_room_ids', array() );
		}

		// Set empty values
		if ( ! isset( $_POST['hb_is_active'] ) ) {
			update_post_meta( $post_id, '_hb_is_active', '0' );
		}
	}

	/**
	 * Apply coupon via AJAX.
	 *
	 * @return void
	 */
	public function ajax_apply_coupon() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		$coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( strtoupper( $_POST['coupon_code'] ) ) : '';
		$room_id     = isset( $_POST['room_id'] ) ? absint( $_POST['room_id'] ) : 0;
		$total_price = isset( $_POST['total_price'] ) ? floatval( $_POST['total_price'] ) : 0;

		if ( empty( $coupon_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a coupon code', 'hotel-booking' ) ) );
		}

		$result = $this->validate_coupon( $coupon_code, $room_id, $total_price );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Store in session
		if ( ! session_id() ) {
			session_start();
		}
		$_SESSION['hb_coupon'] = array(
			'code'    => $coupon_code,
			'discount' => $result['discount'],
			'type'    => $result['type'],
			'value'   => $result['value'],
		);

		wp_send_json_success( array(
			'message'      => __( 'Coupon applied successfully!', 'hotel-booking' ),
			'discount'     => $result['discount'],
			'total_price'  => $total_price - $result['discount'],
			'coupon_code'  => $coupon_code,
		) );
	}

	/**
	 * Remove coupon via AJAX.
	 *
	 * @return void
	 */
	public function ajax_remove_coupon() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! session_id() ) {
			session_start();
		}
		unset( $_SESSION['hb_coupon'] );

		wp_send_json_success( array(
			'message' => __( 'Coupon removed', 'hotel-booking' ),
		) );
	}

	/**
	 * Validate coupon via AJAX.
	 *
	 * @return void
	 */
	public function ajax_validate_coupon() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		$coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( strtoupper( $_POST['coupon_code'] ) ) : '';

		if ( empty( $coupon_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a coupon code', 'hotel-booking' ) ) );
		}

		$coupon = $this->get_coupon_by_code( $coupon_code );

		if ( ! $coupon ) {
			wp_send_json_error( array( 'message' => __( 'Invalid coupon code', 'hotel-booking' ) ) );
		}

		$discount_type = get_post_meta( $coupon->ID, '_hb_discount_type', true );
		$discount_value = get_post_meta( $coupon->ID, '_hb_discount_value', true );

		wp_send_json_success( array(
			'message' => sprintf(
				__( 'Valid! %s discount', 'hotel-booking' ),
				'percentage' === $discount_type ? $discount_value . '%' : get_option( 'hb_currency_symbol', '$' ) . $discount_value
			),
			'type'    => $discount_type,
			'value'   => $discount_value,
		) );
	}

	/**
	 * Validate coupon.
	 *
	 * @param string $coupon_code Coupon code.
	 * @param int    $room_id     Room ID.
	 * @param float  $total_price Total price.
	 * @return array|WP_Error
	 */
	public function validate_coupon( $coupon_code, $room_id = 0, $total_price = 0 ) {
		$coupon = $this->get_coupon_by_code( $coupon_code );

		if ( ! $coupon ) {
			return new WP_Error( 'invalid_coupon', __( 'Invalid coupon code', 'hotel-booking' ) );
		}

		// Check if active
		$is_active = get_post_meta( $coupon->ID, '_hb_is_active', true );
		if ( '1' !== $is_active ) {
			return new WP_Error( 'inactive_coupon', __( 'This coupon is not active', 'hotel-booking' ) );
		}

		// Check expiry
		$expiry_date = get_post_meta( $coupon->ID, '_hb_expiry_date', true );
		if ( $expiry_date && strtotime( $expiry_date ) < strtotime( 'today' ) ) {
			return new WP_Error( 'expired_coupon', __( 'This coupon has expired', 'hotel-booking' ) );
		}

		// Check usage limit
		$usage_limit = get_post_meta( $coupon->ID, '_hb_usage_limit', true );
		$used_count  = get_post_meta( $coupon->ID, '_hb_used_count', true ) ?: 0;
		if ( $usage_limit && $used_count >= $usage_limit ) {
			return new WP_Error( 'usage_limit', __( 'This coupon has reached its usage limit', 'hotel-booking' ) );
		}

		// Check minimum amount
		$min_amount = get_post_meta( $coupon->ID, '_hb_min_amount', true );
		if ( $min_amount && $total_price < $min_amount ) {
			return new WP_Error(
				'min_amount',
				sprintf(
					__( 'Minimum booking amount of %s%s required', 'hotel-booking' ),
					get_option( 'hb_currency_symbol', '$' ),
					number_format( $min_amount, 2 )
				)
			);
		}

		// Check room restrictions
		$room_ids = get_post_meta( $coupon->ID, '_hb_room_ids', true );
		if ( ! empty( $room_ids ) && ! in_array( $room_id, $room_ids, true ) ) {
			return new WP_Error( 'room_restriction', __( 'This coupon is not valid for this room', 'hotel-booking' ) );
		}

		// Calculate discount
		$discount_type  = get_post_meta( $coupon->ID, '_hb_discount_type', true );
		$discount_value = get_post_meta( $coupon->ID, '_hb_discount_value', true );
		$max_discount   = get_post_meta( $coupon->ID, '_hb_max_discount', true );

		if ( 'percentage' === $discount_type ) {
			$discount = ( $total_price * $discount_value ) / 100;
			if ( $max_discount && $discount > $max_discount ) {
				$discount = $max_discount;
			}
		} else {
			$discount = $discount_value;
		}

		// Ensure discount doesn't exceed total
		if ( $discount > $total_price ) {
			$discount = $total_price;
		}

		return array(
			'discount' => $discount,
			'type'     => $discount_type,
			'value'    => $discount_value,
			'coupon_id' => $coupon->ID,
		);
	}

	/**
	 * Get coupon by code.
	 *
	 * @param string $code Coupon code.
	 * @return WP_Post|null
	 */
	public function get_coupon_by_code( $code ) {
		$coupons = get_posts( array(
			'post_type'      => self::POST_TYPE,
			'title'          => $code,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		) );

		return ! empty( $coupons ) ? $coupons[0] : null;
	}

	/**
	 * Increment coupon usage.
	 *
	 * @param string $code Coupon code.
	 * @return void
	 */
	public function increment_usage( $code ) {
		$coupon = $this->get_coupon_by_code( $code );

		if ( ! $coupon ) {
			return;
		}

		$used_count = get_post_meta( $coupon->ID, '_hb_used_count', true ) ?: 0;
		update_post_meta( $coupon->ID, '_hb_used_count', $used_count + 1 );
	}

	/**
	 * Get all active coupons.
	 *
	 * @return array
	 */
	public function get_active_coupons() {
		return get_posts( array(
			'post_type'      => self::POST_TYPE,
			'meta_query'     => array(
				array(
					'key'     => '_hb_is_active',
					'value'   => '1',
					'compare' => '=',
				),
			),
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );
	}
}
