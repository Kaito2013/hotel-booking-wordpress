<?php
/**
 * Seasonal Pricing Manager Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seasonal Pricing Manager Class
 */
class Hotel_Booking_Seasonal_Pricing {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Seasonal_Pricing
	 */
	private static $instance = null;

	/**
	 * Seasons table name.
	 *
	 * @var string
	 */
	private $seasons_table;

	/**
	 * Seasonal rates table name.
	 *
	 * @var string
	 */
	private $rates_table;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Seasonal_Pricing
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
		global $wpdb;
		$this->seasons_table = $wpdb->prefix . 'hb_seasons';
		$this->rates_table   = $wpdb->prefix . 'hb_seasonal_rates';

		add_action( 'init', array( $this, 'create_tables' ) );
		add_action( 'admin_menu', array( $this, 'add_seasons_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_hb_save_season', array( $this, 'ajax_save_season' ) );
		add_action( 'wp_ajax_hb_delete_season', array( $this, 'ajax_delete_season' ) );
		add_action( 'wp_ajax_hb_save_seasonal_rate', array( $this, 'ajax_save_seasonal_rate' ) );
		add_action( 'wp_ajax_hb_get_seasonal_price', array( $this, 'ajax_get_seasonal_price' ) );
	}

	/**
	 * Create tables.
	 *
	 * @return void
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Seasons table
		$sql_seasons = "CREATE TABLE IF NOT EXISTS {$this->seasons_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(100) NOT NULL,
			start_date DATE NOT NULL,
			end_date DATE NOT NULL,
			color VARCHAR(7) DEFAULT '#2271b1',
			description TEXT DEFAULT NULL,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY start_date (start_date),
			KEY end_date (end_date),
			KEY is_active (is_active)
		) {$charset_collate};";

		// Seasonal rates table
		$sql_rates = "CREATE TABLE IF NOT EXISTS {$this->rates_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			season_id BIGINT(20) UNSIGNED NOT NULL,
			room_id BIGINT(20) UNSIGNED NOT NULL,
			price_adjustment DECIMAL(10,2) NOT NULL DEFAULT 0,
			adjustment_type ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed',
			min_stay INT(11) DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY season_room (season_id, room_id),
			KEY season_id (season_id),
			KEY room_id (room_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_seasons );
		dbDelta( $sql_rates );
	}

	/**
	 * Add seasons menu.
	 *
	 * @return void
	 */
	public function add_seasons_menu() {
		add_submenu_page(
			'hotel-booking',
			__( 'Seasonal Pricing', 'hotel-booking' ),
			__( 'Seasonal Pricing', 'hotel-booking' ),
			'manage_options',
			'hotel-booking-seasons',
			array( $this, 'render_seasons_page' )
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'hotel-booking_page_hotel-booking-seasons' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'hotel-booking-seasons',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/css/seasons.css',
			array(),
			HOTEL_BOOKING_VERSION
		);

		wp_enqueue_script(
			'hotel-booking-seasons',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/js/seasons.js',
			array( 'jquery', 'jquery-ui-datepicker', 'wp-color-picker' ),
			HOTEL_BOOKING_VERSION,
			true
		);

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'jquery-ui-datepicker', 'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css' );

		wp_localize_script( 'hotel-booking-seasons', 'hbSeasons', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'hotel-booking-nonce' ),
			'strings' => array(
				'saving'  => esc_html__( 'Saving...', 'hotel-booking' ),
				'deleting' => esc_html__( 'Deleting...', 'hotel-booking' ),
				'confirm' => esc_html__( 'Are you sure you want to delete this season?', 'hotel-booking' ),
			),
		) );
	}

	/**
	 * Render seasons page.
	 *
	 * @return void
	 */
	public function render_seasons_page() {
		global $wpdb;

		$seasons = $wpdb->get_results(
			"SELECT * FROM {$this->seasons_table} ORDER BY start_date ASC"
		);

		$rooms = get_posts( array(
			'post_type'      => 'hb_room',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		?>

		<div class="wrap hotel-booking-seasons">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Seasonal Pricing', 'hotel-booking' ); ?>
			</h1>
			<button type="button" class="page-title-action" id="hb-add-season">
				<?php esc_html_e( 'Add New Season', 'hotel-booking' ); ?>
			</button>

			<!-- Season Form (Hidden by default) -->
			<div class="hb-season-form" id="hb-season-form" style="display: none;">
				<h2 id="hb-season-form-title"><?php esc_html_e( 'Add New Season', 'hotel-booking' ); ?></h2>
				<form id="hb-season-form-element">
					<?php wp_nonce_field( 'hotel-booking-nonce', 'nonce' ); ?>
					<input type="hidden" name="season_id" id="hb-season-id" value="">

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="hb-season-name"><?php esc_html_e( 'Season Name', 'hotel-booking' ); ?></label>
							</th>
							<td>
								<input type="text" id="hb-season-name" name="name" class="regular-text" required>
								<p class="description"><?php esc_html_e( 'E.g., Summer 2024, Christmas, Weekend', 'hotel-booking' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hb-season-start"><?php esc_html_e( 'Start Date', 'hotel-booking' ); ?></label>
							</th>
							<td>
								<input type="date" id="hb-season-start" name="start_date" required>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hb-season-end"><?php esc_html_e( 'End Date', 'hotel-booking' ); ?></label>
							</th>
							<td>
								<input type="date" id="hb-season-end" name="end_date" required>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hb-season-color"><?php esc_html_e( 'Color', 'hotel-booking' ); ?></label>
							</th>
							<td>
								<input type="text" id="hb-season-color" name="color" value="#2271b1" class="hb-color-picker">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hb-season-description"><?php esc_html_e( 'Description', 'hotel-booking' ); ?></label>
							</th>
							<td>
								<textarea id="hb-season-description" name="description" class="large-text" rows="3"></textarea>
							</td>
						</tr>
					</table>

					<div class="hb-form-actions">
						<button type="submit" class="button button-primary" id="hb-save-season">
							<?php esc_html_e( 'Save Season', 'hotel-booking' ); ?>
						</button>
						<button type="button" class="button" id="hb-cancel-season">
							<?php esc_html_e( 'Cancel', 'hotel-booking' ); ?>
						</button>
					</div>
				</form>
			</div>

			<!-- Seasons List -->
			<div class="hb-seasons-list">
				<?php if ( empty( $seasons ) ) : ?>
					<div class="hb-no-seasons">
						<span class="dashicons dashicons-calendar-alt"></span>
						<p><?php esc_html_e( 'No seasons defined yet. Click "Add New Season" to create one.', 'hotel-booking' ); ?></p>
					</div>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th width="25%"><?php esc_html_e( 'Season', 'hotel-booking' ); ?></th>
								<th width="20%"><?php esc_html_e( 'Dates', 'hotel-booking' ); ?></th>
								<th width="15%"><?php esc_html_e( 'Status', 'hotel-booking' ); ?></th>
								<th width="25%"><?php esc_html_e( 'Room Rates', 'hotel-booking' ); ?></th>
								<th width="15%"><?php esc_html_e( 'Actions', 'hotel-booking' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $seasons as $season ) : ?>
								<?php
								$rates = $wpdb->get_results( $wpdb->prepare(
									"SELECT r.*, p.post_title as room_name
									FROM {$this->rates_table} r
									LEFT JOIN {$wpdb->posts} p ON r.room_id = p.ID
									WHERE r.season_id = %d",
									$season->id
								) );

								$today = date( 'Y-m-d' );
								$is_current = $today >= $season->start_date && $today <= $season->end_date;
								$is_future = $today < $season->start_date;
								$is_past = $today > $season->end_date;
								?>
								<tr data-id="<?php echo esc_attr( $season->id ); ?>">
									<td>
										<div class="hb-season-name" style="border-left: 4px solid <?php echo esc_attr( $season->color ); ?>; padding-left: 10px;">
											<strong><?php echo esc_html( $season->name ); ?></strong>
											<?php if ( $season->description ) : ?>
												<br><small><?php echo esc_html( $season->description ); ?></small>
											<?php endif; ?>
										</div>
									</td>
									<td>
										<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $season->start_date ) ) ); ?>
										<br>
										<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $season->end_date ) ) ); ?>
									</td>
									<td>
										<?php if ( $is_current ) : ?>
											<span class="hb-season-status current"><?php esc_html_e( 'Current', 'hotel-booking' ); ?></span>
										<?php elseif ( $is_future ) : ?>
											<span class="hb-season-status upcoming"><?php esc_html_e( 'Upcoming', 'hotel-booking' ); ?></span>
										<?php else : ?>
											<span class="hb-season-status past"><?php esc_html_e( 'Past', 'hotel-booking' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( empty( $rates ) ) : ?>
											<span class="hb-no-rates"><?php esc_html_e( 'No rates set', 'hotel-booking' ); ?></span>
										<?php else : ?>
											<ul class="hb-rates-list">
												<?php foreach ( $rates as $rate ) : ?>
													<li>
														<?php echo esc_html( $rate->room_name ); ?>:
														<?php
														if ( 'percentage' === $rate->adjustment_type ) {
															echo $rate->price_adjustment > 0 ? '+' : '';
															echo esc_html( $rate->price_adjustment . '%' );
														} else {
															echo $rate->price_adjustment > 0 ? '+' : '';
															echo esc_html( get_option( 'hb_currency_symbol', '$' ) . $rate->price_adjustment );
														}
														?>
													</li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									</td>
									<td>
										<button type="button" class="button hb-edit-season">
											<?php esc_html_e( 'Edit', 'hotel-booking' ); ?>
										</button>
										<button type="button" class="button hb-edit-rates">
											<?php esc_html_e( 'Rates', 'hotel-booking' ); ?>
										</button>
										<button type="button" class="button hb-delete-season">
											<?php esc_html_e( 'Delete', 'hotel-booking' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Seasonal Rates Modal -->
			<div class="hb-rates-modal" id="hb-rates-modal" style="display: none;">
				<div class="hb-modal-overlay"></div>
				<div class="hb-modal-content">
					<h3><?php esc_html_e( 'Seasonal Rates', 'hotel-booking' ); ?></h3>
					<p class="hb-modal-season-name"></p>

					<form id="hb-rates-form">
						<?php wp_nonce_field( 'hotel-booking-nonce', 'rates_nonce' ); ?>
						<input type="hidden" name="season_id" id="hb-rates-season-id">

						<table class="hb-rates-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Room', 'hotel-booking' ); ?></th>
									<th><?php esc_html_e( 'Adjustment Type', 'hotel-booking' ); ?></th>
									<th><?php esc_html_e( 'Amount', 'hotel-booking' ); ?></th>
									<th><?php esc_html_e( 'Min Stay', 'hotel-booking' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $rooms as $room ) : ?>
									<tr data-room-id="<?php echo esc_attr( $room->ID ); ?>">
										<td><?php echo esc_html( $room->post_title ); ?></td>
										<td>
											<select name="adjustment_type[<?php echo esc_attr( $room->ID ); ?>]">
												<option value="fixed"><?php esc_html_e( 'Fixed Amount', 'hotel-booking' ); ?></option>
												<option value="percentage"><?php esc_html_e( 'Percentage', 'hotel-booking' ); ?></option>
											</select>
										</td>
										<td>
											<input type="number" name="price_adjustment[<?php echo esc_attr( $room->ID ); ?>]" step="0.01" class="small-text" placeholder="0.00">
										</td>
										<td>
											<input type="number" name="min_stay[<?php echo esc_attr( $room->ID ); ?>]" class="small-text" placeholder="1">
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<div class="hb-modal-actions">
							<button type="submit" class="button button-primary" id="hb-save-rates">
								<?php esc_html_e( 'Save Rates', 'hotel-booking' ); ?>
							</button>
							<button type="button" class="button" id="hb-close-rates">
								<?php esc_html_e( 'Close', 'hotel-booking' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Save season via AJAX.
	 *
	 * @return void
	 */
	public function ajax_save_season() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$season_id    = isset( $_POST['season_id'] ) ? absint( $_POST['season_id'] ) : 0;
		$name         = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$start_date   = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date     = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
		$color        = isset( $_POST['color'] ) ? sanitize_hex_color( $_POST['color'] ) : '#2271b1';
		$description  = isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '';

		if ( empty( $name ) || empty( $start_date ) || empty( $end_date ) ) {
			wp_send_json_error( array( 'message' => __( 'Please fill in all required fields', 'hotel-booking' ) ) );
		}

		global $wpdb;

		$data = array(
			'name'        => $name,
			'start_date'  => $start_date,
			'end_date'    => $end_date,
			'color'       => $color,
			'description' => $description,
			'is_active'   => 1,
		);

		if ( $season_id ) {
			$wpdb->update( $this->seasons_table, $data, array( 'id' => $season_id ) );
			$message = __( 'Season updated successfully', 'hotel-booking' );
		} else {
			$wpdb->insert( $this->seasons_table, $data );
			$season_id = $wpdb->insert_id;
			$message = __( 'Season created successfully', 'hotel-booking' );
		}

		wp_send_json_success( array(
			'message'   => $message,
			'season_id' => $season_id,
		) );
	}

	/**
	 * Delete season via AJAX.
	 *
	 * @return void
	 */
	public function ajax_delete_season() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$season_id = isset( $_POST['season_id'] ) ? absint( $_POST['season_id'] ) : 0;

		if ( ! $season_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid season ID', 'hotel-booking' ) ) );
		}

		global $wpdb;

		// Delete rates first
		$wpdb->delete( $this->rates_table, array( 'season_id' => $season_id ) );

		// Delete season
		$wpdb->delete( $this->seasons_table, array( 'id' => $season_id ) );

		wp_send_json_success( array(
			'message' => __( 'Season deleted successfully', 'hotel-booking' ),
		) );
	}

	/**
	 * Save seasonal rates via AJAX.
	 *
	 * @return void
	 */
	public function ajax_save_seasonal_rate() {
		check_ajax_referer( 'hotel-booking-nonce', 'rates_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$season_id = isset( $_POST['season_id'] ) ? absint( $_POST['season_id'] ) : 0;

		if ( ! $season_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid season ID', 'hotel-booking' ) ) );
		}

		global $wpdb;

		// Get all rooms
		$rooms = get_posts( array(
			'post_type'      => 'hb_room',
			'posts_per_page' => -1,
		) );

		foreach ( $rooms as $room ) {
			$adjustment_type  = isset( $_POST['adjustment_type'][ $room->ID ] ) ? sanitize_text_field( $_POST['adjustment_type'][ $room->ID ] ) : 'fixed';
			$price_adjustment = isset( $_POST['price_adjustment'][ $room->ID ] ) ? floatval( $_POST['price_adjustment'][ $room->ID ] ) : 0;
			$min_stay         = isset( $_POST['min_stay'][ $room->ID ] ) ? absint( $_POST['min_stay'][ $room->ID ] ) : null;

			// Check if rate exists
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$this->rates_table} WHERE season_id = %d AND room_id = %d",
				$season_id,
				$room->ID
			) );

			if ( $existing ) {
				$wpdb->update(
					$this->rates_table,
					array(
						'adjustment_type'  => $adjustment_type,
						'price_adjustment' => $price_adjustment,
						'min_stay'         => $min_stay,
					),
					array( 'id' => $existing )
				);
			} else {
				$wpdb->insert(
					$this->rates_table,
					array(
						'season_id'        => $season_id,
						'room_id'          => $room->ID,
						'adjustment_type'  => $adjustment_type,
						'price_adjustment' => $price_adjustment,
						'min_stay'         => $min_stay,
					)
				);
			}
		}

		wp_send_json_success( array(
			'message' => __( 'Seasonal rates saved successfully', 'hotel-booking' ),
		) );
	}

	/**
	 * Get seasonal price via AJAX.
	 *
	 * @return void
	 */
	public function ajax_get_seasonal_price() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		$room_id  = isset( $_POST['room_id'] ) ? absint( $_POST['room_id'] ) : 0;
		$check_in = isset( $_POST['check_in'] ) ? sanitize_text_field( $_POST['check_in'] ) : '';

		if ( ! $room_id || ! $check_in ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'hotel-booking' ) ) );
		}

		$base_price = get_post_meta( $room_id, '_hb_price', true );
		$seasonal_price = $this->get_seasonal_price( $room_id, $check_in );

		wp_send_json_success( array(
			'base_price'     => $base_price,
			'seasonal_price' => $seasonal_price,
			'is_seasonal'    => $seasonal_price !== $base_price,
		) );
	}

	/**
	 * Get seasonal price for a room on a date.
	 *
	 * @param int    $room_id Room ID.
	 * @param string $date    Date.
	 * @return float
	 */
	public function get_seasonal_price( $room_id, $date ) {
		global $wpdb;

		$base_price = get_post_meta( $room_id, '_hb_price', true );
		if ( ! $base_price ) {
			return 0;
		}

		$season = $wpdb->get_row( $wpdb->prepare(
			"SELECT s.*, r.adjustment_type, r.price_adjustment
			FROM {$this->seasons_table} s
			LEFT JOIN {$this->rates_table} r ON s.id = r.season_id AND r.room_id = %d
			WHERE s.is_active = 1
			AND %s BETWEEN s.start_date AND s.end_date
			LIMIT 1",
			$room_id,
			$date
		) );

		if ( ! $season || ! $season->price_adjustment ) {
			return $base_price;
		}

		if ( 'percentage' === $season->adjustment_type ) {
			return $base_price + ( $base_price * $season->price_adjustment / 100 );
		}

		return $base_price + $season->price_adjustment;
	}

	/**
	 * Get active seasons for a date range.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @return array
	 */
	public function get_seasons_for_range( $start_date, $end_date ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->seasons_table}
			WHERE is_active = 1
			AND (
				(start_date <= %s AND end_date >= %s)
				OR (start_date <= %s AND end_date >= %s)
				OR (start_date >= %s AND end_date <= %s)
			)
			ORDER BY start_date ASC",
			$start_date, $start_date,
			$end_date, $end_date,
			$start_date, $end_date
		) );
	}
}
