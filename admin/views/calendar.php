<?php
/**
 * Admin Calendar View
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get all rooms
$rooms = get_posts(
	array(
		'post_type'      => 'hb_room',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	)
);
?>

<div class="wrap hotel-booking-wrap">
	<h1><?php esc_html_e( 'Availability Calendar', 'hotel-booking' ); ?></h1>

	<!-- Room Selector -->
	<div class="hb-calendar-controls">
		<label for="hb-room-select"><?php esc_html_e( 'Select Room:', 'hotel-booking' ); ?></label>
		<select id="hb-room-select">
			<option value=""><?php esc_html_e( 'All Rooms', 'hotel-booking' ); ?></option>
			<?php foreach ( $rooms as $room ) : ?>
				<option value="<?php echo esc_attr( $room->ID ); ?>">
					<?php echo esc_html( $room->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for="hb-month-select"><?php esc_html_e( 'Month:', 'hotel-booking' ); ?></label>
		<select id="hb-month-select">
			<?php for ( $i = 0; $i < 12; $i++ ) : ?>
				<?php $date = new DateTime( "+{$i} months" ); ?>
				<option value="<?php echo esc_attr( $date->format( 'Y-m' ) ?>">
					<?php echo esc_html( $date->format( 'F Y' ) ); ?>
				</option>
			<?php endfor; ?>
		</select>

		<button id="hb-load-calendar" class="button button-primary">
			<?php esc_html_e( 'Load Calendar', 'hotel-booking' ); ?>
		</button>
	</div>

	<!-- Calendar Container -->
	<div id="hb-calendar-container">
		<div class="hb-calendar-loading">
			<span class="spinner is-active"></span>
			<?php esc_html_e( 'Loading calendar...', 'hotel-booking' ); ?>
		</div>
	</div>

	<!-- Legend -->
	<div class="hb-calendar-legend">
		<h3><?php esc_html_e( 'Legend:', 'hotel-booking' ); ?></h3>
		<div class="hb-legend-item">
			<span class="hb-legend-color hb-available"></span>
			<span><?php esc_html_e( 'Available', 'hotel-booking' ); ?></span>
		</div>
		<div class="hb-legend-item">
			<span class="hb-legend-color hb-booked"></span>
			<span><?php esc_html_e( 'Booked', 'hotel-booking' ); ?></span>
		</div>
	</div>
</div>
