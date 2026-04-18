<?php
/**
 * My Bookings Template
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();

if ( ! $user_id ) {
	echo '<div class="hb-message error">You must be logged in to view your bookings.</div>';
	return;
}

$booking_manager = Hotel_Booking_Booking_Manager::get_instance();
$bookings = $booking_manager->get_user_bookings( $user_id );
?>

<div class="hb-my-bookings">
	<h1>My Bookings</h1>

	<?php if ( empty( $bookings ) ) : ?>
		<div class="hb-message info">
			<p>You don't have any bookings yet.</p>
			<a href="<?php echo esc_url( home_url( '/rooms' ) ); ?>" class="button">
				Browse Rooms
			</a>
		</div>
	<?php else : ?>

		<div class="hb-bookings-list">
			<?php foreach ( $bookings as $booking ) : ?>
				<?php
				$room = get_post( $booking->room_id );
				$status_labels = array(
					'pending'   => 'Pending',
					'confirmed' => 'Confirmed',
					'cancelled' => 'Cancelled',
					'completed' => 'Completed',
				);

				$status_class = 'hb-status-' . $booking->booking_status;
				$payment_status_class = 'hb-payment-' . $booking->payment_status;
				?>

				<div class="hb-booking-card" data-booking-id="<?php echo esc_attr( $booking->id ); ?>">
					<div class="hb-booking-card-header">
						<div class="hb-booking-number">
							<span class="hb-booking-label">Booking #</span>
							<span class="hb-booking-id"><?php echo esc_html( $booking->id ); ?></span>
						</div>
						<div class="hb-booking-status">
							<span class="hb-status <?php echo esc_attr( $status_class ); ?>">
								<?php echo esc_html( isset( $status_labels[ $booking->booking_status ] ) ? $status_labels[ $booking->booking_status ] : ucfirst( $booking->booking_status ) ); ?>
							</span>
						</div>
					</div>

					<div class="hb-booking-card-body">
						<div class="hb-booking-room">
							<?php if ( $room ) : ?>
								<h3><?php echo esc_html( $room->post_title ); ?></h3>
								<?php $room_image = get_the_post_thumbnail_url( $room->ID, 'medium' ); ?>
								<?php if ( $room_image ) : ?>
									<img src="<?php echo esc_url( $room_image ); ?>" alt="<?php echo esc_attr( $room->post_title ); ?>" class="hb-booking-room-image">
								<?php endif; ?>
							<?php else : ?>
								<h3>Room #<?php echo esc_html( $booking->room_id ); ?></h3>
							<?php endif; ?>
						</div>

						<div class="hb-booking-details">
							<div class="hb-booking-detail-row">
								<span class="hb-detail-label">Guest:</span>
								<span class="hb-detail-value">
									<?php echo esc_html( $booking->first_name . ' ' . $booking->last_name ); ?>
								</span>
							</div>

							<div class="hb-booking-detail-row">
								<span class="hb-detail-label">Check-in:</span>
								<span class="hb-detail-value"><?php echo esc_html( $booking->check_in ); ?></span>
							</div>

							<div class="hb-booking-detail-row">
								<span class="hb-detail-label">Check-out:</span>
								<span class="hb-detail-value"><?php echo esc_html( $booking->check_out ); ?></span>
							</div>

							<div class="hb-booking-detail-row">
								<span class="hb-detail-label">Guests:</span>
								<span class="hb-detail-value"><?php echo esc_html( $booking->guests ); ?></span>
							</div>

							<div class="hb-booking-detail-row">
								<span class="hb-detail-label">Total:</span>
								<span class="hb-detail-value hb-price">
									<?php echo esc_html( get_option( 'hb_currency_symbol', '$' ) . number_format( $booking->total_price, 2 ) ); ?>
								</span>
							</div>

							<div class="hb-booking-detail-row">
								<span class="hb-detail-label">Payment:</span>
								<span class="hb-detail-value hb-payment-status <?php echo esc_attr( $payment_status_class ); ?>">
									<?php echo esc_html( ucfirst( $booking->payment_status ) ); ?>
								</span>
							</div>

							<div class="hb-booking-detail-row">
								<span class="hb-detail-label">Booked on:</span>
								<span class="hb-detail-value"><?php echo esc_html( date( 'F j, Y', strtotime( $booking->created_at ) ) ); ?></span>
							</div>

							<?php if ( $booking->notes ) : ?>
								<div class="hb-booking-detail-row">
									<span class="hb-detail-label">Notes:</span>
									<span class="hb-detail-value"><?php echo esc_html( $booking->notes ); ?></span>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<div class="hb-booking-card-footer">
						<?php if ( 'pending' === $booking->booking_status ) : ?>
							<a href="#" class="button hb-cancel-booking" data-booking-id="<?php echo esc_attr( $booking->id ); ?>">
								Cancel Booking
							</a>
						<?php endif; ?>

						<a href="#" class="button hb-view-booking" data-booking-id="<?php echo esc_attr( $booking->id ); ?>">
							View Details
						</a>

						<?php if ( 'confirmed' === $booking->booking_status && 'completed' === $booking->payment_status ) : ?>
							<a href="#" class="button hb-download-receipt" data-booking-id="<?php echo esc_attr( $booking->id ); ?>">
								Download Receipt
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Pagination -->
		<?php if ( count( $bookings ) > 10 ) : ?>
			<div class="hb-pagination">
				<button class="button" id="hb-load-more-bookings">Load More</button>
			</div>
		<?php endif; ?>

	<?php endif; ?>
</div>

<!-- Cancel Booking Modal -->
<div class="hb-modal-overlay" id="hb-cancel-modal" style="display: none;">
	<div class="hb-modal">
		<div class="hb-modal-header">
			<h2 class="hb-modal-title">Cancel Booking</h2>
			<button class="hb-modal-close">&times;</button>
		</div>
		<form id="hb-cancel-booking-form">
			<input type="hidden" name="booking_id" id="cancel-booking-id">

			<div class="hb-form-group">
				<label for="cancel-reason">Reason for Cancellation</label>
				<textarea id="cancel-reason" name="reason" rows="4" required></textarea>
			</div>

			<div class="hb-cancel-warning">
				<p><strong>Warning:</strong> This action cannot be undone.</p>
				<p>Your payment may not be refunded depending on the cancellation policy.</p>
			</div>

			<button type="submit" class="hb-submit-btn hb-cancel-btn">Confirm Cancellation</button>
		</form>
	</div>
</div>
