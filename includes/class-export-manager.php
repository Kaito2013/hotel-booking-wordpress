<?php
/**
 * Export Manager Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export Manager Class
 */
class Hotel_Booking_Export_Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Export_Manager
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Export_Manager
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
		add_action( 'admin_menu', array( $this, 'add_export_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_hb_export_bookings_csv', array( $this, 'ajax_export_bookings_csv' ) );
		add_action( 'wp_ajax_hb_export_bookings_pdf', array( $this, 'ajax_export_bookings_pdf' ) );
		add_action( 'wp_ajax_hb_export_rooms_csv', array( $this, 'ajax_export_rooms_csv' ) );
		add_action( 'wp_ajax_hb_export_revenue_csv', array( $this, 'ajax_export_revenue_csv' ) );
	}

	/**
	 * Add export menu.
	 *
	 * @return void
	 */
	public function add_export_menu() {
		add_submenu_page(
			'hotel-booking',
			__( 'Export', 'hotel-booking' ),
			__( 'Export', 'hotel-booking' ),
			'manage_options',
			'hotel-booking-export',
			array( $this, 'render_export_page' )
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'hotel-booking_page_hotel-booking-export' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'hotel-booking-export',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/css/export.css',
			array(),
			HOTEL_BOOKING_VERSION
		);

		wp_enqueue_script(
			'hotel-booking-export',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/js/export.js',
			array( 'jquery' ),
			HOTEL_BOOKING_VERSION,
			true
		);

		wp_localize_script( 'hotel-booking-export', 'hbExport', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'hotel-booking-nonce' ),
			'strings' => array(
				'exporting' => esc_html__( 'Exporting...', 'hotel-booking' ),
				'success'   => esc_html__( 'Export completed!', 'hotel-booking' ),
				'error'     => esc_html__( 'Export failed', 'hotel-booking' ),
			),
		) );
	}

	/**
	 * Render export page.
	 *
	 * @return void
	 */
	public function render_export_page() {
		?>

		<div class="wrap hotel-booking-export">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Export Data', 'hotel-booking' ); ?>
			</h1>

			<!-- Export Options -->
			<div class="hb-export-grid">
				<!-- Bookings Export -->
				<div class="hb-export-card">
					<div class="hb-export-icon hb-icon-bookings">
						<span class="dashicons dashicons-calendar-alt"></span>
					</div>
					<h3><?php esc_html_e( 'Bookings Export', 'hotel-booking' ); ?></h3>
					<p><?php esc_html_e( 'Export all booking data including guest details, dates, room info, and payment status.', 'hotel-booking' ); ?></p>

					<div class="hb-export-filters">
						<label for="hb-booking-period"><?php esc_html_e( 'Period:', 'hotel-booking' ); ?></label>
						<select id="hb-booking-period">
							<option value="all"><?php esc_html_e( 'All Time', 'hotel-booking' ); ?></option>
							<option value="7days"><?php esc_html_e( 'Last 7 Days', 'hotel-booking' ); ?></option>
							<option value="30days"><?php esc_html_e( 'Last 30 Days', 'hotel-booking' ); ?></option>
							<option value="90days"><?php esc_html_e( 'Last 90 Days', 'hotel-booking' ); ?></option>
							<option value="year"><?php esc_html_e( 'This Year', 'hotel-booking' ); ?></option>
						</select>

						<label for="hb-booking-status"><?php esc_html_e( 'Status:', 'hotel-booking' ); ?></label>
						<select id="hb-booking-status">
							<option value="all"><?php esc_html_e( 'All Statuses', 'hotel-booking' ); ?></option>
							<option value="pending"><?php esc_html_e( 'Pending', 'hotel-booking' ); ?></option>
							<option value="confirmed"><?php esc_html_e( 'Confirmed', 'hotel-booking' ); ?></option>
							<option value="completed"><?php esc_html_e( 'Completed', 'hotel-booking' ); ?></option>
							<option value="cancelled"><?php esc_html_e( 'Cancelled', 'hotel-booking' ); ?></option>
						</select>
					</div>

					<div class="hb-export-actions">
						<button type="button" class="button button-primary hb-export-btn" data-action="hb_export_bookings_csv">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Export CSV', 'hotel-booking' ); ?>
						</button>
						<button type="button" class="button hb-export-btn" data-action="hb_export_bookings_pdf">
							<span class="dashicons dashicons-pdf"></span>
							<?php esc_html_e( 'Export PDF', 'hotel-booking' ); ?>
						</button>
					</div>
				</div>

				<!-- Rooms Export -->
				<div class="hb-export-card">
					<div class="hb-export-icon hb-icon-rooms">
						<span class="dashicons dashicons-building"></span>
					</div>
					<h3><?php esc_html_e( 'Rooms Export', 'hotel-booking' ); ?></h3>
					<p><?php esc_html_e( 'Export room details including pricing, capacity, amenities, and availability.', 'hotel-booking' ); ?></p>

					<div class="hb-export-actions">
						<button type="button" class="button button-primary hb-export-btn" data-action="hb_export_rooms_csv">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Export CSV', 'hotel-booking' ); ?>
						</button>
					</div>
				</div>

				<!-- Revenue Export -->
				<div class="hb-export-card">
					<div class="hb-export-icon hb-icon-revenue">
						<span class="dashicons dashicons-chart-bar"></span>
					</div>
					<h3><?php esc_html_e( 'Revenue Export', 'hotel-booking' ); ?></h3>
					<p><?php esc_html_e( 'Export financial data including revenue, payments, and payment methods.', 'hotel-booking' ); ?></p>

					<div class="hb-export-filters">
						<label for="hb-revenue-period"><?php esc_html_e( 'Period:', 'hotel-booking' ); ?></label>
						<select id="hb-revenue-period">
							<option value="30days"><?php esc_html_e( 'Last 30 Days', 'hotel-booking' ); ?></option>
							<option value="90days"><?php esc_html_e( 'Last 90 Days', 'hotel-booking' ); ?></option>
							<option value="year"><?php esc_html_e( 'This Year', 'hotel-booking' ); ?></option>
							<option value="all"><?php esc_html_e( 'All Time', 'hotel-booking' ); ?></option>
						</select>
					</div>

					<div class="hb-export-actions">
						<button type="button" class="button button-primary hb-export-btn" data-action="hb_export_revenue_csv">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Export CSV', 'hotel-booking' ); ?>
						</button>
					</div>
				</div>

				<!-- Coupons Export -->
				<div class="hb-export-card">
					<div class="hb-export-icon hb-icon-coupons">
						<span class="dashicons dashicons-tickets"></span>
					</div>
					<h3><?php esc_html_e( 'Coupons Export', 'hotel-booking' ); ?></h3>
					<p><?php esc_html_e( 'Export coupon usage data and discount information.', 'hotel-booking' ); ?></p>

					<div class="hb-export-actions">
						<button type="button" class="button button-primary hb-export-btn" data-action="hb_export_coupons_csv">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Export CSV', 'hotel-booking' ); ?>
						</button>
					</div>
				</div>

				<!-- Reviews Export -->
				<div class="hb-export-card">
					<div class="hb-export-icon hb-icon-reviews">
						<span class="dashicons dashicons-star-filled"></span>
					</div>
					<h3><?php esc_html_e( 'Reviews Export', 'hotel-booking' ); ?></h3>
					<p><?php esc_html_e( 'Export guest reviews and ratings data.', 'hotel-booking' ); ?></p>

					<div class="hb-export-actions">
						<button type="button" class="button button-primary hb-export-btn" data-action="hb_export_reviews_csv">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Export CSV', 'hotel-booking' ); ?>
						</button>
					</div>
				</div>

				<!-- Guests Export -->
				<div class="hb-export-card">
					<div class="hb-export-icon hb-icon-guests">
						<span class="dashicons dashicons-admin-users"></span>
					</div>
					<h3><?php esc_html_e( 'Guests Export', 'hotel-booking' ); ?></h3>
					<p><?php esc_html_e( 'Export guest contact information and booking history.', 'hotel-booking' ); ?></p>

					<div class="hb-export-actions">
						<button type="button" class="button button-primary hb-export-btn" data-action="hb_export_guests_csv">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Export CSV', 'hotel-booking' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Export Status -->
			<div class="hb-export-status" id="hb-export-status" style="display: none;">
				<div class="hb-export-progress">
					<span class="spinner is-active"></span>
					<span class="hb-export-message"><?php esc_html_e( 'Exporting...', 'hotel-booking' ); ?></span>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Export bookings CSV via AJAX.
	 *
	 * @return void
	 */
	public function ajax_export_bookings_csv() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$period = isset( $_POST['period'] ) ? sanitize_text_field( $_POST['period'] ) : 'all';
		$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'all';

		$where = $this->get_date_where( $period );

		if ( 'all' !== $status ) {
			$where .= $wpdb->prepare( " AND booking_status = %s", $status );
		}

		global $wpdb;

		$bookings = $wpdb->get_results(
			"SELECT b.*, r.post_title as room_name
			FROM {$wpdb->prefix}hb_bookings b
			LEFT JOIN {$wpdb->posts} r ON b.room_id = r.ID
			{$where}
			ORDER BY b.check_in DESC"
		);

		$filename = 'bookings-export-' . date( 'Y-m-d' ) . '.csv';
		$filepath = WP_CONTENT_DIR . '/uploads/' . $filename;

		$handle = fopen( $filepath, 'w' );

		// Add BOM for UTF-8
		fprintf( $handle, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Headers
		fputcsv( $handle, array(
			'Booking ID',
			'Room',
			'Guest Name',
			'Email',
			'Phone',
			'Check-in',
			'Check-out',
			'Guests',
			'Total Amount',
			'Booking Status',
			'Payment Status',
			'Booking Date',
			'Special Requests',
		) );

		// Data
		foreach ( $bookings as $booking ) {
			fputcsv( $handle, array(
				$booking->id,
				$booking->room_name,
				$booking->guest_name,
				$booking->email,
				$booking->phone,
				$booking->check_in,
				$booking->check_out,
				$booking->guests,
				$booking->total_price,
				ucfirst( $booking->booking_status ),
				ucfirst( $booking->payment_status ),
				$booking->created_at,
				$booking->special_requests,
			) );
		}

		fclose( $handle );

		wp_send_json_success( array(
			'message'  => __( 'Export completed!', 'hotel-booking' ),
			'url'      => content_url( '/uploads/' . $filename ),
			'filename' => $filename,
		) );
	}

	/**
	 * Export bookings PDF via AJAX.
	 *
	 * @return void
	 */
	public function ajax_export_bookings_pdf() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		// Simple HTML to PDF using browser print
		// For production, use a library like TCPDF or mPDF

		$period = isset( $_POST['period'] ) ? sanitize_text_field( $_POST['period'] ) : 'all';
		$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'all';

		$where = $this->get_date_where( $period );

		if ( 'all' !== $status ) {
			$where .= $wpdb->prepare( " AND booking_status = %s", $status );
		}

		global $wpdb;

		$bookings = $wpdb->get_results(
			"SELECT b.*, r.post_title as room_name
			FROM {$wpdb->prefix}hb_bookings b
			LEFT JOIN {$wpdb->posts} r ON b.room_id = r.ID
			{$where}
			ORDER BY b.check_in DESC"
		);

		$filename = 'bookings-export-' . date( 'Y-m-d' ) . '.html';
		$filepath = WP_CONTENT_DIR . '/uploads/' . $filename;

		$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Bookings Export</title>';
		$html .= '<style>
			body { font-family: Arial, sans-serif; margin: 20px; }
			table { width: 100%; border-collapse: collapse; margin-top: 20px; }
			th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
			th { background: #f6f7f7; font-weight: 600; }
			h1 { color: #2271b1; }
			.badge { padding: 2px 6px; border-radius: 3px; font-size: 11px; }
			.pending { background: #fff3cd; }
			.confirmed { background: #cce5ff; }
			.completed { background: #d4edda; }
			.cancelled { background: #f8d7da; }
		</style></head><body>';
		$html .= '<h1>Hotel Booking - Bookings Export</h1>';
		$html .= '<p>Generated: ' . date( 'Y-m-d H:i:s' ) . '</p>';
		$html .= '<p>Total Bookings: ' . count( $bookings ) . '</p>';
		$html .= '<table><thead><tr>';
		$html .= '<th>ID</th><th>Room</th><th>Guest</th><th>Email</th><th>Check-in</th><th>Check-out</th><th>Total</th><th>Status</th>';
		$html .= '</tr></thead><tbody>';

		foreach ( $bookings as $booking ) {
			$html .= '<tr>';
			$html .= '<td>' . $booking->id . '</td>';
			$html .= '<td>' . $booking->room_name . '</td>';
			$html .= '<td>' . $booking->guest_name . '</td>';
			$html .= '<td>' . $booking->email . '</td>';
			$html .= '<td>' . $booking->check_in . '</td>';
			$html .= '<td>' . $booking->check_out . '</td>';
			$html .= '<td>' . get_option( 'hb_currency_symbol', '$' ) . number_format( $booking->total_price, 2 ) . '</td>';
			$html .= '<td><span class="badge ' . $booking->booking_status . '">' . ucfirst( $booking->booking_status ) . '</span></td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table>';
		$html .= '</body></html>';

		file_put_contents( $filepath, $html );

		wp_send_json_success( array(
			'message'  => __( 'PDF export completed! (HTML format for printing)', 'hotel-booking' ),
			'url'      => content_url( '/uploads/' . $filename ),
			'filename' => $filename,
		) );
	}

	/**
	 * Export rooms CSV via AJAX.
	 *
	 * @return void
	 */
	public function ajax_export_rooms_csv() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		global $wpdb;

		$rooms = $wpdb->get_results(
			"SELECT p.*, pm.meta_value as price
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_hb_price'
			WHERE p.post_type = 'hb_room' AND p.post_status = 'publish'
			ORDER BY p.post_title ASC"
		);

		$filename = 'rooms-export-' . date( 'Y-m-d' ) . '.csv';
		$filepath = WP_CONTENT_DIR . '/uploads/' . $filename;

		$handle = fopen( $filepath, 'w' );

		// Add BOM for UTF-8
		fprintf( $handle, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Headers
		fputcsv( $handle, array(
			'Room ID',
			'Room Name',
			'Price',
			'Status',
			'Created Date',
		) );

		// Data
		foreach ( $rooms as $room ) {
			fputcsv( $handle, array(
				$room->ID,
				$room->post_title,
				$room->price ?: '0',
				$room->post_status,
				$room->post_date,
			) );
		}

		fclose( $handle );

		wp_send_json_success( array(
			'message'  => __( 'Rooms export completed!', 'hotel-booking' ),
			'url'      => content_url( '/uploads/' . $filename ),
			'filename' => $filename,
		) );
	}

	/**
	 * Export revenue CSV via AJAX.
	 *
	 * @return void
	 */
	public function ajax_export_revenue_csv() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$period = isset( $_POST['period'] ) ? sanitize_text_field( $_POST['period'] ) : '30days';

		global $wpdb;

		$where = $this->get_date_where( $period );

		$revenue = $wpdb->get_results(
			"SELECT DATE(check_in) as date, COUNT(*) as bookings, SUM(total_price) as revenue, payment_status
			FROM {$wpdb->prefix}hb_bookings
			{$where}
			GROUP BY DATE(check_in), payment_status
			ORDER BY date ASC"
		);

		$filename = 'revenue-export-' . date( 'Y-m-d' ) . '.csv';
		$filepath = WP_CONTENT_DIR . '/uploads/' . $filename;

		$handle = fopen( $filepath, 'w' );

		// Add BOM for UTF-8
		fprintf( $handle, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Headers
		fputcsv( $handle, array(
			'Date',
			'Bookings',
			'Revenue',
			'Payment Status',
		) );

		// Data
		foreach ( $revenue as $row ) {
			fputcsv( $handle, array(
				$row->date,
				$row->bookings,
				$row->revenue,
				ucfirst( $row->payment_status ),
			) );
		}

		fclose( $handle );

		wp_send_json_success( array(
			'message'  => __( 'Revenue export completed!', 'hotel-booking' ),
			'url'      => content_url( '/uploads/' . $filename ),
			'filename' => $filename,
		) );
	}

	/**
	 * Get date WHERE clause.
	 *
	 * @param string $period Period.
	 * @return string
	 */
	private function get_date_where( $period ) {
		global $wpdb;

		switch ( $period ) {
			case '7days':
				return $wpdb->prepare( "WHERE check_in >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)" );
			case '30days':
				return $wpdb->prepare( "WHERE check_in >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)" );
			case '90days':
				return $wpdb->prepare( "WHERE check_in >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)" );
			case 'year':
				return $wpdb->prepare( "WHERE YEAR(check_in) = YEAR(CURDATE())" );
			default:
				return '';
		}
	}
}
