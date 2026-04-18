<?php
/**
 * Booking Confirmation Template
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;

if ( ! $booking_id ) {
	echo '<div class="hb-message error">Invalid booking ID.</div>';
	return;
}

$booking_manager = Hotel_Booking_Booking_Manager::get_instance();
$booking = $booking_manager->get_booking( $booking_id );

if ( ! $booking ) {
	echo '<div class="hb-message error">Booking not found.</div>';
	return;
}

$room = get_post( $booking->room_id );
$status_labels = array(
	'pending'   => 'Pending',
	'confirmed' => 'Confirmed',
	'cancelled' => 'Cancelled',
	'completed' => 'Completed',
);
?>

<div class="hb-confirmation-container">
	<div class="hb-confirmation-box">
		<!-- Success Icon -->
		<div class="hb-confirmation-icon">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<circle cx="12" cy="12" r="10" />
				<path d="M9 12l2 2 4-4" />
			</svg>
		</div>

		<!-- Success Message -->
		<h1 class="hb-confirmation-title">
			<?php esc_html_e( 'Booking Confirmed!', 'hotel-booking' ); ?>
		</h1>
		<p class="hb-confirmation-message">
			<?php printf(
				esc_html__( 'Your booking #%s has been successfully created.', 'hotel-booking' ),
				esc_html( $booking_id )
			); ?>
		</p>

		<!-- Booking Status -->
		<div class="hb-confirmation-status">
			<span class="hb-status hb-status-<?php echo esc_attr( $booking->booking_status ); ?>">
				<?php echo esc_html( isset( $status_labels[ $booking->booking_status ] ) ? $status_labels[ $booking->booking_status ] : ucfirst( $booking->booking_status ) ); ?>
			</span>
		</div>

		<!-- Booking Details -->
		<div class="hb-confirmation-details">
			<h2><?php esc_html_e( 'Booking Details', 'hotel-booking' ); ?></h2>

			<div class="hb-confirmation-grid">
				<div class="hb-detail-item">
					<span class="hb-detail-label"><?php esc_html_e( 'Booking Number', 'hotel-booking' ); ?></span>
					<span class="hb-detail-value">#<?php echo esc_html( $booking_id ); ?></span>
				</div>

				<div class="hb-detail-item">
					<span class="hb-detail-label"><?php esc_html_e( 'Room', 'hotel-booking' ); ?></span>
					<span class="hb-detail-value">
						<?php echo $room ? esc_html( $room->post_title ) : esc_html__( 'Room #' . $booking->room_id, 'hotel-booking' ); ?>
					</span>
				</div>

				<div class="hb-detail-item">
					<span class="hb-detail-label"><?php esc_html_e( 'Check-in', 'hotel-booking' ); ?></span>
					<span class="hb-detail-value"><?php echo esc_html( $booking->check_in ); ?></span>
				</div>

				<div class="hb-detail-item">
					<span class="hb-detail-label"><?php esc_html_e( 'Check-out', 'hotel-booking' ); ?></span>
					<span class="hb-detail-value"><?php echo esc_html( $booking->check_out ); ?></span>
				</div>

				<div class="hb-detail-item">
					<span class="hb-detail-label"><?php esc_html_e( 'Guests', 'hotel-booking' ); ?></span>
					<span class="hb-detail-value"><?php echo esc_html( $booking->guests ); ?></span>
				</div>

				<div class="hb-detail-item">
					<span class="hb-detail-label"><?php esc_html_e( 'Guest Name', 'hotel-booking' ); ?></span>
					<span class="hb-detail-value">
						<?php echo esc_html( $booking->first_name . ' ' . $booking->last_name ); ?>
					</span>
				</div>

				<div class="hb-detail-item">
					<span class="hb-detail-label"><?php esc_html_e( 'Email', 'hotel-booking' ); ?></span>
					<span class="hb-detail-value"><?php echo esc_html( $booking->email ); ?></span>
				</div>

				<?php if ( $booking->phone ) : ?>
					<div class="hb-detail-item">
						<span class="hb-detail-label"><?php esc_html_e( 'Phone', 'hotel-booking' ); ?></span>
						<span class="hb-detail-value"><?php echo esc_html( $booking->phone ); ?></span>
					</div>
				<?php endif; ?>

				<div class="hb-detail-item hb-total-item">
					<span class="hb-detail-label"><?php esc_html_e( 'Total Amount', 'hotel-booking' ); ?></span>
					<span class="hb-detail-value hb-price">
						<?php echo esc_html( get_option( 'hb_currency_symbol', '$' ) . number_format( $booking->total_price, 2 ) ); ?>
					</span>
				</div>

				<div class="hb-detail-item">
					<span class="hb-detail-label"><?php esc_html_e( 'Payment Status', 'hotel-booking' ); ?></span>
					<span class="hb-detail-value hb-payment-status hb-payment-<?php echo esc_attr( $booking->payment_status ); ?>">
						<?php echo esc_html( ucfirst( $booking->payment_status ) ); ?>
					</span>
				</div>

				<div class="hb-detail-item">
					<span class="hb-detail-label"><?php esc_html_e( 'Booked On', 'hotel-booking' ); ?></span>
					<span class="hb-detail-value">
						<?php echo esc_html( date( 'F j, Y \a\t g:i A', strtotime( $booking->created_at ) ) ); ?>
					</span>
				</div>
			</div>

			<?php if ( $booking->notes ) : ?>
				<div class="hb-confirmation-notes">
					<h3><?php esc_html_e( 'Special Requests', 'hotel-booking' ); ?></h3>
					<p><?php echo esc_html( $booking->notes ); ?></p>
				</div>
			<?php endif; ?>
		</div>

		<!-- What's Next -->
		<div class="hb-whats-next">
			<h2><?php esc_html_e( 'What Happens Next?', 'hotel-booking' ); ?></h2>
			<div class="hb-steps">
				<div class="hb-step">
					<div class="hb-step-number">1</div>
					<div class="hb-step-content">
						<h4><?php esc_html_e( 'Confirmation Email', 'hotel-booking' ); ?></h4>
						<p><?php esc_html_e( 'We have sent a confirmation email to your email address with all the booking details.', 'hotel-booking' ); ?></p>
					</div>
				</div>

				<?php if ( 'pending' === $booking->payment_status ) : ?>
					<div class="hb-step">
						<div class="hb-step-number">2</div>
						<div class="hb-step-content">
							<h4><?php esc_html_e( 'Complete Payment', 'hotel-booking' ); ?></h4>
							<p><?php esc_html_e( 'Please complete your payment to confirm your booking. You can pay via the link in the email.', 'hotel-booking' ); ?></p>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( 'completed' === $booking->payment_status ) : ?>
					<div class="hb-step">
						<div class="hb-step-number">2</div>
						<div class="hb-step-content">
							<h4><?php esc_html_e( 'Payment Confirmed', 'hotel-booking' ); ?></h4>
							<p><?php esc_html_e( 'Your payment has been successfully processed. Your booking is confirmed!', 'hotel-booking' ); ?></p>
						</div>
					</div>
				<?php endif; ?>

				<div class="hb-step">
					<div class="hb-step-number"><?php echo 'pending' === $booking->payment_status ? '3' : '3'; ?></div>
					<div class="hb-step-content">
						<h4><?php esc_html_e( 'Check-in', 'hotel-booking' ); ?></h4>
						<p>
							<?php printf(
								esc_html__( 'Check-in starts at %s on %s. Please bring a valid ID for verification.', 'hotel-booking' ),
								esc_html( get_option( 'hb_default_check_in', '14:00' ) ),
								esc_html( $booking->check_in )
							); ?>
						</p>
					</div>
				</div>

				<div class="hb-step">
					<div class="hb-step-number"><?php echo 'pending' === $booking->payment_status ? '4' : '4'; ?></div>
					<div class="hb-step-content">
						<h4><?php esc_html_e( 'Enjoy Your Stay!', 'hotel-booking' ); ?></h4>
						<p><?php esc_html_e( 'We look forward to welcoming you. If you have any questions, feel free to contact us.', 'hotel-booking' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- Action Buttons -->
		<div class="hb-confirmation-actions">
			<a href="<?php echo esc_url( home_url( '/my-bookings' ) ); ?>" class="hb-button hb-button-primary">
				<?php esc_html_e( 'View My Bookings', 'hotel-booking' ); ?>
			</a>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hb-button hb-button-secondary">
				<?php esc_html_e( 'Back to Home', 'hotel-booking' ); ?>
			</a>
		</div>

		<!-- Contact Info -->
		<div class="hb-confirmation-contact">
			<h3><?php esc_html_e( 'Need Help?', 'hotel-booking' ); ?></h3>
			<p><?php esc_html_e( 'If you have any questions or need to make changes to your booking, please contact us:', 'hotel-booking' ); ?></p>
			<div class="hb-contact-info">
				<?php
				$contact_email = get_option( 'hb_contact_email', get_bloginfo( 'admin_email' ) );
				$contact_phone = get_option( 'hb_contact_phone', '' );
				?>
				<?php if ( $contact_email ) : ?>
					<div class="hb-contact-item">
						<span class="hb-contact-icon">📧</span>
						<a href="mailto:<?php echo esc_attr( $contact_email ); ?>">
							<?php echo esc_html( $contact_email ); ?>
						</a>
					</div>
				<?php endif; ?>

				<?php if ( $contact_phone ) : ?>
					<div class="hb-contact-item">
						<span class="hb-contact-icon">📞</span>
						<a href="tel:<?php echo esc_attr( $contact_phone ); ?>">
							<?php echo esc_html( $contact_phone ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
