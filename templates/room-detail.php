<?php
/**
 * Room Detail Template
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $hb_room_data;

if ( ! $hb_room_data ) {
	return;
}

$room = get_post( $hb_room_data['id'] );
$check_in = $hb_room_data['check_in'];
$check_out = $hb_room_data['check_out'];
$guests = $hb_room_data['guests'];
$price_per_night = get_post_meta( $room->ID, '_hb_room_price', true );
$capacity = get_post_meta( $room->ID, '_hb_room_capacity', true );
$size = get_post_meta( $room->ID, '_hb_room_size', true );
$beds = get_post_meta( $room->ID, '_hb_room_beds', true );

$room_types = wp_get_post_terms( $room->ID, 'room_type', array( 'fields' => 'names' ) );
$amenities = wp_get_post_terms( $room->ID, 'room_amenity', array( 'fields' => 'names' ) );

// Get room gallery images
$gallery_images = get_post_meta( $room->ID, '_hb_room_gallery', true );
if ( ! $gallery_images ) {
	$gallery_images = array();
}

// Add featured image to gallery if not already there
$featured_id = get_post_thumbnail_id( $room->ID );
if ( $featured_id && ! in_array( $featured_id, $gallery_images ) ) {
	array_unshift( $gallery_images, $featured_id );
}

$main_image_url = $featured_id ? wp_get_attachment_image_url( $featured_id, 'large' ) : '';
?>

<div class="hb-room-detail">
	<div class="hb-room-detail-main">
		<!-- Gallery -->
		<div class="hb-room-gallery-section">
			<?php if ( $main_image_url ) : ?>
				<div class="hb-room-main-image">
					<img src="<?php echo esc_url( $main_image_url ); ?>" alt="<?php echo esc_attr( $room->post_title ); ?>" id="hb-main-room-image">
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $gallery_images ) ) : ?>
				<div class="hb-room-gallery">
					<?php foreach ( $gallery_images as $index => $image_id ) : ?>
						<?php $image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' ); ?>
						<?php $image_large = wp_get_attachment_image_url( $image_id, 'large' ); ?>
						<?php if ( $image_url ) : ?>
							<img
								src="<?php echo esc_url( $image_url ); ?>"
								alt="<?php echo esc_attr( $room->post_title . ' - Image ' . ( $index + 1 ) ); ?>"
								class="hb-gallery-thumb <?php echo $index === 0 ? 'active' : ''; ?>"
								data-large="<?php echo esc_url( $image_large ); ?>"
							>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Room Info -->
		<div class="hb-room-info">
			<h1 class="hb-room-title"><?php echo esc_html( $room->post_title ); ?></h1>

			<?php if ( ! empty( $room_types ) ) : ?>
				<div class="hb-room-types">
					<?php foreach ( $room_types as $type ) : ?>
						<span class="hb-room-type"><?php echo esc_html( $type ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="hb-room-meta-large">
				<?php if ( $capacity ) : ?>
					<div class="hb-meta-item">
						<span class="hb-meta-icon">👥</span>
						<span class="hb-meta-label">Capacity:</span>
						<span class="hb-meta-value"><?php echo esc_html( $capacity . ' ' . ( $capacity > 1 ? 'Guests' : 'Guest' ) ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $size ) : ?>
					<div class="hb-meta-item">
						<span class="hb-meta-icon">📐</span>
						<span class="hb-meta-label">Size:</span>
						<span class="hb-meta-value"><?php echo esc_html( $size ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $beds ) : ?>
					<div class="hb-meta-item">
						<span class="hb-meta-icon">🛏️</span>
						<span class="hb-meta-label">Beds:</span>
						<span class="hb-meta-value"><?php echo esc_html( $beds . ' ' . ( $beds > 1 ? 'Beds' : 'Bed' ) ); ?></span>
					</div>
				<?php endif; ?>
			</div>

			<div class="hb-room-description">
				<h2>Description</h2>
				<?php echo wp_kses_post( wpautop( $room->post_content ) ); ?>
			</div>

			<?php if ( ! empty( $amenities ) ) : ?>
				<div class="hb-room-amenities-large">
					<h2>Amenities</h2>
					<div class="hb-amenities-grid">
						<?php foreach ( $amenities as $amenity ) : ?>
							<div class="hb-amenity-item">
								<span class="hb-amenity-icon">✓</span>
								<span class="hb-amenity-name"><?php echo esc_html( $amenity ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Sidebar - Booking Form -->
	<aside class="hb-room-detail-sidebar">
		<div class="hb-booking-widget">
			<div class="hb-booking-widget-header">
				<h2>Book This Room</h2>
				<?php if ( $price_per_night ) : ?>
					<div class="hb-price-per-night">
						<?php echo esc_html( get_option( 'hb_currency_symbol', '$' ) . number_format( $price_per_night, 2 ) ); ?>
						<span>/night</span>
					</div>
				<?php endif; ?>
			</div>

			<form class="hb-room-booking-form" id="hb-room-booking-form">
				<input type="hidden" name="room_id" value="<?php echo esc_attr( $room->ID ); ?>">

				<div class="hb-form-group">
					<label for="room_check_in">Check-in Date *</label>
					<input
						type="date"
						id="room_check_in"
						name="check_in"
						value="<?php echo esc_attr( $check_in ); ?>"
						required
						min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"
					>
				</div>

				<div class="hb-form-group">
					<label for="room_check_out">Check-out Date *</label>
					<input
						type="date"
						id="room_check_out"
						name="check_out"
						value="<?php echo esc_attr( $check_out ); ?>"
						required
						min="<?php echo esc_attr( date( 'Y-m-d', strtotime( '+1 day' ) ) ); ?>"
					>
				</div>

				<div class="hb-form-group">
					<label for="room_guests">Guests</label>
					<select id="room_guests" name="guests">
						<?php
						$max_guests = $capacity ? $capacity : 10;
						for ( $i = 1; $i <= $max_guests; $i++ ) :
							?>
							<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, $guests ); ?>>
								<?php echo esc_html( $i . ' ' . ( $i > 1 ? 'Guests' : 'Guest' ) ); ?>
							</option>
						<?php endfor; ?>
					</select>
				</div>

				<div class="hb-booking-summary">
					<h3>Booking Summary</h3>
					<div class="hb-summary-row">
						<span>Room:</span>
						<span><?php echo esc_html( $room->post_title ); ?></span>
					</div>
					<div class="hb-summary-row">
						<span>Check-in:</span>
						<span id="summary-check-in"><?php echo esc_html( $check_in ); ?></span>
					</div>
					<div class="hb-summary-row">
						<span>Check-out:</span>
						<span id="summary-check-out"><?php echo esc_html( $check_out ); ?></span>
					</div>
					<div class="hb-summary-row">
						<span><span id="summary-nights">0</span> nights</span>
					</div>
					<div class="hb-summary-row total">
						<span>Total:</span>
						<span id="summary-total"><?php echo esc_html( get_option( 'hb_currency_symbol', '$' ) . '0.00' ); ?></span>
					</div>
				</div>

				<button type="submit" class="hb-submit-btn">Proceed to Booking</button>
			</form>
		</div>

		<!-- Availability Calendar Mini -->
		<div class="hb-availability-mini">
			<h3>Availability Calendar</h3>
			<div id="hb-mini-calendar">
				<div class="hb-loading"><div class="spinner"></div>Loading calendar...</div>
			</div>
		</div>
	</aside>
</div>
