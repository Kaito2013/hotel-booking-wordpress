<?php
/**
 * Room Metaboxes
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
/**
 * Room Metaboxes Class
 */
class Hotel_Booking_Room_Metaboxes {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Room_Metaboxes
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Room_Metaboxes
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
		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post_hb_room', array( $this, 'save_metaboxes' ), 10, 2 );
	}

	/**
	 * Add metaboxes.
	 *
	 * @return void
	 */
	public function add_metaboxes() {
		add_meta_box(
			'hb_room_details',
			__( 'Room Details', 'hotel-booking' ),
			array( $this, 'render_room_details_metabox' ),
			'hb_room',
			'normal',
			'high'
		);

		add_meta_box(
			'hb_pricing_rules',
			__( 'Pricing Rules', 'hotel-booking' ),
			array( $this, 'render_pricing_rules_metabox' ),
			'hb_room',
			'normal',
			'default'
		);
	}

	/**
	 * Render room details metabox.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_room_details_metabox( $post ) {
		wp_nonce_field( 'hb_room_metabox', 'hb_room_metabox_nonce' );

		$capacity = get_post_meta( $post->ID, '_hb_room_capacity', true );
		$price    = get_post_meta( $post->ID, '_hb_room_price', true );
		$size     = get_post_meta( $post->ID, '_hb_room_size', true );
		$beds     = get_post_meta( $post->ID, '_hb_room_beds', true );
		?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="hb-room-capacity"><?php esc_html_e( 'Capacity (Guests)', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<input type="number" id="hb-room-capacity" name="hb_room_capacity" value="<?php echo esc_attr( $capacity ? $capacity : 2 ); ?>" min="1" max="20" class="small-text">
					<p class="description"><?php esc_html_e( 'Maximum number of guests this room can accommodate.', 'hotel-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hb-room-price"><?php esc_html_e( 'Base Price (per night)', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<input type="number" id="hb-room-price" name="hb_room_price" value="<?php echo esc_attr( $price ? $price : 100 ); ?>" min="0" step="0.01" class="small-text">
					<p class="description"><?php esc_html_e( 'Default price per night. You can set date-specific prices in the Pricing Rules section.', 'hotel-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hb-room-size"><?php esc_html_e( 'Room Size', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<input type="text" id="hb-room-size" name="hb_room_size" value="<?php echo esc_attr( $size ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., 30 sqm or 320 sqft', 'hotel-booking' ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hb-room-beds"><?php esc_html_e( 'Number of Beds', 'hotel-booking' ); ?></label>
				</th>
				<td>
					<input type="number" id="hb-room-beds" name="hb_room_beds" value="<?php echo esc_attr( $beds ? $beds : 1 ); ?>" min="1" max="10" class="small-text">
				</td>
			</tr>
		</table>

		<?php
	}

	/**
	 * Render pricing rules metabox.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_pricing_rules_metabox( $post ) {
		$pricing_rules = get_post_meta( $post->ID, '_hb_pricing_rules', true );
		$pricing_rules = $pricing_rules ? $pricing_rules : array();
		?>

		<div id="hb-pricing-rules-container">
			<?php if ( ! empty( $pricing_rules ) ) : ?>
				<?php foreach ( $pricing_rules as $index => $rule ) : ?>
					<div class="hb-pricing-rule" data-index="<?php echo esc_attr( $index ); ?>">
						<table class="form-table">
							<tr>
								<th scope="row">
									<label><?php esc_html_e( 'Date Range', 'hotel-booking' ); ?></label>
								</th>
								<td>
									<input type="date" name="hb_pricing_rules[<?php echo esc_attr( $index ); ?>][start_date]" value="<?php echo esc_attr( $rule['start_date'] ); ?>" class="medium-text">
									<span><?php esc_html_e( 'to', 'hotel-booking' ); ?></span>
									<input type="date" name="hb_pricing_rules[<?php echo esc_attr( $index ); ?>][end_date]" value="<?php echo esc_attr( $rule['end_date'] ); ?>" class="medium-text">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php esc_html_e( 'Rule Type', 'hotel-booking' ); ?></label>
								</th>
								<td>
									<select name="hb_pricing_rules[<?php echo esc_attr( $index ); ?>][type]" class="medium-text">
										<option value="fixed" <?php selected( $rule['type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed Price', 'hotel-booking' ); ?></option>
										<option value="percent" <?php selected( $rule['type'], 'percent' ); ?>><?php esc_html_e( 'Percentage Adjustment', 'hotel-booking' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php esc_html_e( 'Value', 'hotel-booking' ); ?></label>
								</th>
								<td>
									<input type="number" name="hb_pricing_rules[<?php echo esc_attr( $index ); ?>][price]" value="<?php echo esc_attr( $rule['price'] ); ?>" step="0.01" class="small-text">
									<?php if ( 'percent' === $rule['type'] ) : ?>
										<span>%</span>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php esc_html_e( 'Actions', 'hotel-booking' ); ?></label>
								</th>
								<td>
									<button type="button" class="button hb-remove-rule">
										<?php esc_html_e( 'Remove Rule', 'hotel-booking' ); ?>
									</button>
								</td>
							</tr>
						</table>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<button type="button" id="hb-add-pricing-rule" class="button">
			<?php esc_html_e( '+ Add Pricing Rule', 'hotel-booking' ); ?>
		</button>

		<p class="description">
			<?php esc_html_e( 'Add date-specific pricing rules. Rules with later dates override earlier ones.', 'hotel-booking' ); ?>
		</p>

		<?php
	}

	/**
	 * Save metaboxes.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_metaboxes( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['hb_room_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['hb_room_metabox_nonce'], 'hb_room_metabox' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save room details
		if ( isset( $_POST['hb_room_capacity'] ) ) {
			update_post_meta( $post_id, '_hb_room_capacity', absint( $_POST['hb_room_capacity'] ) );
		}

		if ( isset( $_POST['hb_room_price'] ) ) {
			update_post_meta( $post_id, '_hb_room_price', floatval( $_POST['hb_room_price'] ) );
		}

		if ( isset( $_POST['hb_room_size'] ) ) {
			update_post_meta( $post_id, '_hb_room_size', sanitize_text_field( $_POST['hb_room_size'] ) );
		}

		if ( isset( $_POST['hb_room_beds'] ) ) {
			update_post_meta( $post_id, '_hb_room_beds', absint( $_POST['hb_room_beds'] ) );
		}

		// Save pricing rules
		if ( isset( $_POST['hb_pricing_rules'] ) ) {
			$pricing_rules = array();

			foreach ( $_POST['hb_pricing_rules'] as $rule ) {
				if ( ! empty( $rule['start_date'] ) && ! empty( $rule['end_date'] ) && ! empty( $rule['price'] ) ) {
					$pricing_rules[] = array(
						'start_date' => sanitize_text_field( $rule['start_date'] ),
						'end_date'   => sanitize_text_field( $rule['end_date'] ),
						'type'       => sanitize_text_field( $rule['type'] ),
						'price'      => floatval( $rule['price'] ),
					);
				}
			}

			update_post_meta( $post_id, '_hb_pricing_rules', $pricing_rules );
		}
	}
}

// Initialize metaboxes
Hotel_Booking_Room_Metaboxes::get_instance();
