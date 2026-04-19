<?php
/**
 * Admin Reports Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Reports Class
 */
class Hotel_Booking_Admin_Reports {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Admin_Reports
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Admin_Reports
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
		add_action( 'admin_menu', array( $this, 'add_reports_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_reports_scripts' ) );
		add_action( 'wp_ajax_hb_get_report_data', array( $this, 'ajax_get_report_data' ) );
		add_action( 'wp_ajax_hb_export_report', array( $this, 'ajax_export_report' ) );
	}

	/**
	 * Add reports menu.
	 *
	 * @return void
	 */
	public function add_reports_menu() {
		add_submenu_page(
			'hotel-booking',
			__( 'Reports', 'hotel-booking' ),
			__( 'Reports', 'hotel-booking' ),
			'manage_options',
			'hotel-booking-reports',
			array( $this, 'render_reports_page' )
		);
	}

	/**
	 * Enqueue reports scripts.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_reports_scripts( $hook ) {
		if ( 'hotel-booking_page_hotel-booking-reports' !== $hook ) {
			return;
		}

		// Chart.js
		wp_enqueue_script(
			'chart-js',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
			array(),
			'4.4.0',
			true
		);

		// Reports CSS
		wp_enqueue_style(
			'hotel-booking-reports',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/css/reports.css',
			array(),
			HOTEL_BOOKING_VERSION
		);

		// Reports JS
		wp_enqueue_script(
			'hotel-booking-reports',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/js/reports.js',
			array( 'jquery', 'chart-js' ),
			HOTEL_BOOKING_VERSION,
			true
		);

		// Localize script
		wp_localize_script( 'hotel-booking-reports', 'hbReports', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'hotel-booking-nonce' ),
			'currency'  => get_option( 'hb_currency_symbol', '$' ),
			'strings'   => array(
				'loading'  => esc_html__( 'Loading...', 'hotel-booking' ),
				'noData'   => esc_html__( 'No data available for this period', 'hotel-booking' ),
				'export'   => esc_html__( 'Export CSV', 'hotel-booking' ),
			),
		) );
	}

	/**
	 * Render reports page.
	 *
	 * @return void
	 */
	public function render_reports_page() {
		$period = isset( $_GET['period'] ) ? sanitize_text_field( $_GET['period'] ) : '30days';

		// Get summary data
		$summary = $this->get_summary_data( $period );

		?>
		<div class="wrap hotel-booking-reports">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Reports & Analytics', 'hotel-booking' ); ?>
			</h1>

			<!-- Period Filter -->
			<div class="hb-reports-header">
				<form method="get" class="hb-period-filter">
					<input type="hidden" name="page" value="hotel-booking-reports">
					<select name="period">
						<option value="7days" <?php selected( $period, '7days' ); ?>><?php esc_html_e( 'Last 7 Days', 'hotel-booking' ); ?></option>
						<option value="30days" <?php selected( $period, '30days' ); ?>><?php esc_html_e( 'Last 30 Days', 'hotel-booking' ); ?></option>
						<option value="90days" <?php selected( $period, '90days' ); ?>><?php esc_html_e( 'Last 90 Days', 'hotel-booking' ); ?></option>
						<option value="year" <?php selected( $period, 'year' ); ?>><?php esc_html_e( 'This Year', 'hotel-booking' ); ?></option>
					</select>
					<button type="submit" class="button"><?php esc_html_e( 'Apply', 'hotel-booking' ); ?></button>
				</form>
				<div class="hb-reports-actions">
					<button type="button" class="button" id="hb-export-csv">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Export CSV', 'hotel-booking' ); ?>
					</button>
				</div>
			</div>

			<!-- Summary Cards -->
			<div class="hb-reports-summary">
				<div class="hb-summary-card">
					<div class="hb-card-icon hb-icon-bookings">
						<span class="dashicons dashicons-calendar-alt"></span>
					</div>
					<div class="hb-card-content">
						<h3><?php echo esc_html( $summary['total_bookings'] ); ?></h3>
						<p><?php esc_html_e( 'Total Bookings', 'hotel-booking' ); ?></p>
					</div>
				</div>
				<div class="hb-summary-card">
					<div class="hb-card-icon hb-icon-revenue">
						<span class="dashicons dashicons-chart-bar"></span>
					</div>
					<div class="hb-card-content">
						<h3><?php echo esc_html( get_option( 'hb_currency_symbol', '$' ) . number_format( $summary['total_revenue'], 2 ) ); ?></h3>
						<p><?php esc_html_e( 'Total Revenue', 'hotel-booking' ); ?></p>
					</div>
				</div>
				<div class="hb-summary-card">
					<div class="hb-card-icon hb-icon-occupancy">
						<span class="dashicons dashicons-building"></span>
					</div>
					<div class="hb-card-content">
						<h3><?php echo esc_html( $summary['occupancy_rate'] . '%' ); ?></h3>
						<p><?php esc_html_e( 'Occupancy Rate', 'hotel-booking' ); ?></p>
					</div>
				</div>
				<div class="hb-summary-card">
					<div class="hb-card-icon hb-icon-average">
						<span class="dashicons dashicons-money-alt"></span>
					</div>
					<div class="hb-card-content">
						<h3><?php echo esc_html( get_option( 'hb_currency_symbol', '$' ) . number_format( $summary['average_booking_value'], 2 ) ); ?></h3>
						<p><?php esc_html_e( 'Avg. Booking Value', 'hotel-booking' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Charts -->
			<div class="hb-reports-charts">
				<div class="hb-chart-container">
					<h3><?php esc_html_e( 'Revenue Over Time', 'hotel-booking' ); ?></h3>
					<canvas id="hb-revenue-chart" width="400" height="200"></canvas>
				</div>
				<div class="hb-chart-container">
					<h3><?php esc_html_e( 'Bookings Over Time', 'hotel-booking' ); ?></h3>
					<canvas id="hb-bookings-chart" width="400" height="200"></canvas>
				</div>
			</div>

			<!-- Top Rooms -->
			<div class="hb-reports-section">
				<h3><?php esc_html_e( 'Top Performing Rooms', 'hotel-booking' ); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th width="40%"><?php esc_html_e( 'Room', 'hotel-booking' ); ?></th>
							<th width="20%"><?php esc_html_e( 'Bookings', 'hotel-booking' ); ?></th>
							<th width="20%"><?php esc_html_e( 'Revenue', 'hotel-booking' ); ?></th>
							<th width="20%"><?php esc_html_e( 'Occupancy', 'hotel-booking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $summary['top_rooms'] as $room ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $room['name'] ); ?></strong></td>
								<td><?php echo esc_html( $room['bookings'] ); ?></td>
								<td><?php echo esc_html( get_option( 'hb_currency_symbol', '$' ) . number_format( $room['revenue'], 2 ) ); ?></td>
								<td><?php echo esc_html( $room['occupancy'] . '%' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Booking Status Distribution -->
			<div class="hb-reports-section">
				<h3><?php esc_html_e( 'Booking Status Distribution', 'hotel-booking' ); ?></h3>
				<div class="hb-status-distribution">
					<?php foreach ( $summary['status_distribution'] as $status => $data ) : ?>
						<div class="hb-status-item">
							<div class="hb-status-bar" style="width: <?php echo esc_attr( $data['percentage'] ); ?>%; background: <?php echo esc_attr( $data['color'] ); ?>;"></div>
							<div class="hb-status-info">
								<span class="hb-status-label"><?php echo esc_html( ucfirst( $status ) ); ?></span>
								<span class="hb-status-count"><?php echo esc_html( $data['count'] ); ?></span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Recent Activity -->
			<div class="hb-reports-section">
				<h3><?php esc_html_e( 'Recent Activity', 'hotel-booking' ); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th width="15%"><?php esc_html_e( 'Date', 'hotel-booking' ); ?></th>
							<th width="25%"><?php esc_html_e( 'Guest', 'hotel-booking' ); ?></th>
							<th width="25%"><?php esc_html_e( 'Room', 'hotel-booking' ); ?></th>
							<th width="15%"><?php esc_html_e( 'Amount', 'hotel-booking' ); ?></th>
							<th width="20%"><?php esc_html_e( 'Status', 'hotel-booking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $summary['recent_bookings'] as $booking ) : ?>
							<tr>
								<td><?php echo esc_html( $booking['date'] ); ?></td>
								<td><?php echo esc_html( $booking['guest'] ); ?></td>
								<td><?php echo esc_html( $booking['room'] ); ?></td>
								<td><?php echo esc_html( get_option( 'hb_currency_symbol', '$' ) . number_format( $booking['amount'], 2 ) ); ?></td>
								<td>
									<span class="hb-status-badge hb-status-<?php echo esc_attr( $booking['status'] ); ?>">
										<?php echo esc_html( ucfirst( $booking['status'] ) ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Hidden inputs for JS -->
			<input type="hidden" id="hb-report-period" value="<?php echo esc_attr( $period ); ?>">
		</div>
		<?php
	}

	/**
	 * Get summary data.
	 *
	 * @param string $period Period.
	 * @return array
	 */
	private function get_summary_data( $period ) {
		global $wpdb;

		$date_range = $this->get_date_range( $period );

		// Total bookings
		$total_bookings = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}hb_bookings WHERE check_in BETWEEN %s AND %s",
			$date_range['start'],
			$date_range['end']
		) );

		// Total revenue
		$total_revenue = $wpdb->get_var( $wpdb->prepare(
			"SELECT COALESCE(SUM(total_price), 0) FROM {$wpdb->prefix}hb_bookings WHERE check_in BETWEEN %s AND %s AND payment_status = 'completed'",
			$date_range['start'],
			$date_range['end']
		) );

		// Occupancy rate
		$occupancy_rate = $this->calculate_occupancy_rate( $date_range['start'], $date_range['end'] );

		// Average booking value
		$average_booking_value = $total_bookings > 0 ? $total_revenue / $total_bookings : 0;

		// Top rooms
		$top_rooms = $wpdb->get_results( $wpdb->prepare(
			"SELECT r.post_title as name, COUNT(b.id) as bookings, COALESCE(SUM(b.total_price), 0) as revenue
			FROM {$wpdb->prefix}hb_bookings b
			INNER JOIN {$wpdb->posts} r ON b.room_id = r.ID
			WHERE b.check_in BETWEEN %s AND %s
			GROUP BY b.room_id
			ORDER BY revenue DESC
			LIMIT 5",
			$date_range['start'],
			$date_range['end']
		), ARRAY_A );

		// Calculate occupancy for each room
		foreach ( $top_rooms as &$room ) {
			$room['occupancy'] = $this->calculate_room_occupancy( $room['name'], $date_range['start'], $date_range['end'] );
		}

		// Status distribution
		$status_counts = $wpdb->get_results( $wpdb->prepare(
			"SELECT booking_status, COUNT(*) as count FROM {$wpdb->prefix}hb_bookings WHERE check_in BETWEEN %s AND %s GROUP BY booking_status",
			$date_range['start'],
			$date_range['end']
		), ARRAY_A );

		$status_colors = array(
			'pending'   => '#dba617',
			'confirmed' => '#2271b1',
			'cancelled' => '#d63638',
			'completed' => '#00a32a',
		);

		$status_distribution = array();
		foreach ( $status_counts as $row ) {
			$status = $row['booking_status'];
			$status_distribution[ $status ] = array(
				'count'      => (int) $row['count'],
				'percentage' => $total_bookings > 0 ? round( ( $row['count'] / $total_bookings ) * 100 ) : 0,
				'color'      => $status_colors[ $status ] ?? '#646970',
			);
		}

		// Recent bookings
		$recent_bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT b.check_in as date, b.guest_name as guest, r.post_title as room, b.total_price as amount, b.booking_status as status
			FROM {$wpdb->prefix}hb_bookings b
			INNER JOIN {$wpdb->posts} r ON b.room_id = r.ID
			ORDER BY b.created_at DESC
			LIMIT 10",
			$date_range['start'],
			$date_range['end']
		), ARRAY_A );

		return array(
			'total_bookings'        => (int) $total_bookings,
			'total_revenue'         => (float) $total_revenue,
			'occupancy_rate'        => $occupancy_rate,
			'average_booking_value' => $average_booking_value,
			'top_rooms'             => $top_rooms,
			'status_distribution'   => $status_distribution,
			'recent_bookings'       => $recent_bookings,
		);
	}

	/**
	 * Get date range.
	 *
	 * @param string $period Period.
	 * @return array
	 */
	private function get_date_range( $period ) {
		$end = date( 'Y-m-d' );

		switch ( $period ) {
			case '7days':
				$start = date( 'Y-m-d', strtotime( '-7 days' ) );
				break;
			case '30days':
				$start = date( 'Y-m-d', strtotime( '-30 days' ) );
				break;
			case '90days':
				$start = date( 'Y-m-d', strtotime( '-90 days' ) );
				break;
			case 'year':
				$start = date( 'Y-01-01' );
				break;
			default:
				$start = date( 'Y-m-d', strtotime( '-30 days' ) );
		}

		return array(
			'start' => $start,
			'end'   => $end,
		);
	}

	/**
	 * Calculate occupancy rate.
	 *
	 * @param string $start Start date.
	 * @param string $end   End date.
	 * @return int
	 */
	private function calculate_occupancy_rate( $start, $end ) {
		global $wpdb;

		$total_rooms = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'hb_room' AND post_status = 'publish'"
		);

		if ( ! $total_rooms ) {
			return 0;
		}

		$days_in_period = ( strtotime( $end ) - strtotime( $start ) ) / DAY_IN_SECONDS;
		$total_room_days = $total_rooms * $days_in_period;

		$booked_days = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(DATEDIFF(check_out, check_in)) FROM {$wpdb->prefix}hb_bookings WHERE check_in BETWEEN %s AND %s AND booking_status IN ('confirmed', 'completed')",
			$start,
			$end
		) );

		$booked_days = $booked_days ? (int) $booked_days : 0;

		return $total_room_days > 0 ? round( ( $booked_days / $total_room_days ) * 100 ) : 0;
	}

	/**
	 * Calculate room occupancy.
	 *
	 * @param string $room_name Room name.
	 * @param string $start     Start date.
	 * @param string $end       End date.
	 * @return int
	 */
	private function calculate_room_occupancy( $room_name, $start, $end ) {
		global $wpdb;

		$room_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'hb_room'",
			$room_name
		) );

		if ( ! $room_id ) {
			return 0;
		}

		$days_in_period = ( strtotime( $end ) - strtotime( $start ) ) / DAY_IN_SECONDS;

		$booked_days = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(DATEDIFF(check_out, check_in)) FROM {$wpdb->prefix}hb_bookings WHERE room_id = %d AND check_in BETWEEN %s AND %s AND booking_status IN ('confirmed', 'completed')",
			$room_id,
			$start,
			$end
		) );

		$booked_days = $booked_days ? (int) $booked_days : 0;

		return $days_in_period > 0 ? round( ( $booked_days / $days_in_period ) * 100 ) : 0;
	}

	/**
	 * Get chart data via AJAX.
	 *
	 * @return void
	 */
	public function ajax_get_report_data() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		$period = isset( $_POST['period'] ) ? sanitize_text_field( $_POST['period'] ) : '30days';
		$date_range = $this->get_date_range( $period );

		global $wpdb;

		// Get revenue by date
		$revenue_data = $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE(check_in) as date, SUM(total_price) as revenue
			FROM {$wpdb->prefix}hb_bookings
			WHERE check_in BETWEEN %s AND %s AND payment_status = 'completed'
			GROUP BY DATE(check_in)
			ORDER BY date ASC",
			$date_range['start'],
			$date_range['end']
		), ARRAY_A );

		// Get bookings by date
		$bookings_data = $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE(check_in) as date, COUNT(*) as bookings
			FROM {$wpdb->prefix}hb_bookings
			WHERE check_in BETWEEN %s AND %s
			GROUP BY DATE(check_in)
			ORDER BY date ASC",
			$date_range['start'],
			$date_range['end']
		), ARRAY_A );

		// Fill missing dates
		$revenue_filled = $this->fill_missing_dates( $revenue_data, $date_range['start'], $date_range['end'], 'revenue' );
		$bookings_filled = $this->fill_missing_dates( $bookings_data, $date_range['start'], $date_range['end'], 'bookings' );

		wp_send_json_success( array(
			'revenue' => $revenue_filled,
			'bookings' => $bookings_filled,
		) );
	}

	/**
	 * Fill missing dates in data.
	 *
	 * @param array  $data      Data array.
	 * @param string $start     Start date.
	 * @param string $end       End date.
	 * @param string $value_key Value key.
	 * @return array
	 */
	private function fill_missing_dates( $data, $start, $end, $value_key ) {
		$dates = array();
		$values = array();

		// Convert to lookup array
		$lookup = array();
		foreach ( $data as $row ) {
			$lookup[ $row['date'] ] = $row[ $value_key ];
		}

		// Fill dates
		$current = strtotime( $start );
		$end_ts = strtotime( $end );

		while ( $current <= $end_ts ) {
			$date = date( 'Y-m-d', $current );
			$dates[] = $date;
			$values[] = isset( $lookup[ $date ] ) ? (float) $lookup[ $date ] : 0;
			$current = strtotime( '+1 day', $current );
		}

		return array(
			'labels' => $dates,
			'values' => $values,
		);
	}

	/**
	 * Export report via AJAX.
	 *
	 * @return void
	 */
	public function ajax_export_report() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$period = isset( $_POST['period'] ) ? sanitize_text_field( $_POST['period'] ) : '30days';
		$date_range = $this->get_date_range( $period );

		global $wpdb;

		// Get bookings
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT b.id, b.check_in, b.check_out, b.guest_name, b.email, b.phone, b.guests, b.total_price, b.booking_status, b.payment_status, r.post_title as room_name
			FROM {$wpdb->prefix}hb_bookings b
			INNER JOIN {$wpdb->posts} r ON b.room_id = r.ID
			WHERE b.check_in BETWEEN %s AND %s
			ORDER BY b.check_in ASC",
			$date_range['start'],
			$date_range['end']
		), ARRAY_A );

		// Generate CSV
		$filename = 'hotel-booking-report-' . $period . '-' . date( 'Y-m-d' ) . '.csv';
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
		) );

		// Data
		foreach ( $bookings as $booking ) {
			fputcsv( $handle, array(
				$booking['id'],
				$booking['room_name'],
				$booking['guest_name'],
				$booking['email'],
				$booking['phone'],
				$booking['check_in'],
				$booking['check_out'],
				$booking['guests'],
				$booking['total_price'],
				ucfirst( $booking['booking_status'] ),
				ucfirst( $booking['payment_status'] ),
			) );
		}

		fclose( $handle );

		// Return download URL
		$download_url = content_url( '/uploads/' . $filename );

		wp_send_json_success( array(
			'message' => __( 'Report exported successfully', 'hotel-booking' ),
			'url'     => $download_url,
			'filename' => $filename,
		) );
	}
}
