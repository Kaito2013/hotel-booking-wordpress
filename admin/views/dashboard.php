<?php
/**
 * Admin Dashboard View
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get stats
$dashboard = Hotel_Booking_Admin_Dashboard::get_instance();
$stats     = $dashboard->get_stats();

// Get recent bookings
$booking_manager = Hotel_Booking_Booking_Manager::get_instance();
$recent_bookings = $booking_manager->get_bookings( array( 'limit' => 10 ) );
?>

<div class="wrap hotel-booking-wrap">
	<h1><?php esc_html_e( 'Hotel Booking Dashboard', 'hotel-booking' ); ?></h1>

	<!-- Stats Cards -->
	<div class="hb-dashboard-stats">
		<div class="hb-stat-card">
			<div class="hb-stat-icon">📊</div>
			<div class="hb-stat-content">
				<h3><?php echo esc_html( $stats['total_bookings'] ); ?></h3>
				<p><?php esc_html_e( 'Total Bookings', 'hotel-booking' ); ?></p>
			</div>
		</div>

		<div class="hb-stat-card">
			<div class="hb-stat-icon">⏳</div>
			<div class="hb-stat-content">
				<h3><?php echo esc_html( $stats['pending_bookings'] ); ?></h3>
				<p><?php esc_html_e( 'Pending Bookings', 'hotel-booking' ); ?></p>
			</div>
		</div>

		<div class="hb-stat-card">
			<div class="hb-stat-icon">✅</div>
			<div class="hb-stat-content">
				<h3><?php echo esc_html( $stats['confirmed_bookings'] ); ?></h3>
				<p><?php esc_html_e( 'Confirmed Bookings', 'hotel-booking' ); ?></p>
			</div>
		</div>

		<div class="hb-stat-card">
			<div class="hb-stat-icon">💰</div>
			<div class="hb-stat-content">
				<h3><?php echo esc_html( get_option( 'hb_currency_symbol', '$' ) . number_format( $stats['total_revenue'], 2 ) ); ?></h3>
				<p><?php esc_html_e( 'Total Revenue', 'hotel-booking' ); ?></p>
			</div>
		</div>

		<div class="hb-stat-card">
			<div class="hb-stat-icon">📈</div>
			<div class="hb-stat-content">
				<h3><?php echo esc_html( get_option( 'hb_currency_symbol', '$' ) . number_format( $stats['this_month_revenue'], 2 ) ); ?></h3>
				<p><?php esc_html_e( 'This Month', 'hotel-booking' ); ?></p>
			</div>
		</div>
	</div>

	<!-- Quick Actions -->
	<div class="hb-quick-actions">
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=hb_room' ) ); ?>" class="button button-primary">
			<?php esc_html_e( '+ Add New Room', 'hotel-booking' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=hb_booking' ) ); ?>" class="button">
			<?php esc_html_e( 'View All Bookings', 'hotel-booking' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-booking-calendar' ) ); ?>" class="button">
			<?php esc_html_e( 'View Calendar', 'hotel-booking' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-booking-settings' ) ); ?>" class="button">
			<?php esc_html_e( 'Settings', 'hotel-booking' ); ?>
		</a>
	</div>

	<!-- Recent Bookings -->
	<div class="hb-recent-bookings">
		<h2><?php esc_html_e( 'Recent Bookings', 'hotel-booking' ); ?></h2>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Booking ID', 'hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Guest', 'hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Room', 'hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Check-in', 'hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Check-out', 'hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Amount', 'hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Status', 'hotel-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $recent_bookings ) ) : ?>
					<?php foreach ( $recent_bookings as $booking ) : ?>
						<?php $room = get_post( $booking->room_id ); ?>
						<tr>
							<td>#<?php echo esc_html( $booking->id ); ?></td>
							<td>
								<?php echo esc_html( $booking->first_name . ' ' . $booking->last_name ); ?><br>
								<small><?php echo esc_html( $booking->email ); ?></small>
							</td>
							<td><?php echo $room ? esc_html( $room->post_title ) : '-'; ?></td>
							<td><?php echo esc_html( $booking->check_in ); ?></td>
							<td><?php echo esc_html( $booking->check_out ); ?></td>
							<td><?php echo esc_html( get_option( 'hb_currency_symbol', '$' ) . number_format( $booking->total_price, 2 ) ); ?></td>
							<td>
								<span class="hb-status hb-status-<?php echo esc_attr( $booking->booking_status ); ?>">
									<?php echo esc_html( ucfirst( $booking->booking_status ) ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="7" style="text-align: center;">
							<?php esc_html_e( 'No bookings yet.', 'hotel-booking' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
