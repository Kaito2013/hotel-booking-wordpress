<?php
/**
 * Payment Modal Template
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

$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );

if ( ! $booking ) {
	echo '<div class="hb-message error">Booking not found.</div>';
	return;
}

$stripe_enabled = '1' === get_option( 'hb_stripe_enabled', '0' );
$paypal_enabled = '1' === get_option( 'hb_paypal_enabled', '0' );

$currency_symbol = get_option( 'hb_currency_symbol', '$' );
$currency = get_option( 'hb_currency', 'USD' );
?>

<div class="hb-payment-modal" id="hb-payment-modal">
	<div class="hb-modal-overlay" id="hb-modal-overlay"></div>
	<div class="hb-modal-content">
		<div class="hb-modal-header">
			<h2><?php esc_html_e( 'Complete Your Payment', 'hotel-booking' ); ?></h2>
			<button class="hb-modal-close" id="hb-modal-close">&times;</button>
		</div>

		<div class="hb-modal-body">
			<!-- Booking Summary -->
			<div class="hb-payment-summary">
				<h3><?php esc_html_e( 'Booking Summary', 'hotel-booking' ); ?></h3>
				<div class="hb-summary-row">
					<span><?php esc_html_e( 'Booking #', 'hotel-booking' ); ?></span>
					<strong><?php echo esc_html( $booking_id ); ?></strong>
				</div>
				<div class="hb-summary-row">
					<span><?php esc_html_e( 'Room', 'hotel-booking' ); ?></span>
					<strong><?php echo esc_html( get_the_title( $booking->room_id ) ); ?></strong>
				</div>
				<div class="hb-summary-row">
					<span><?php esc_html_e( 'Check-in', 'hotel-booking' ); ?></span>
					<strong><?php echo esc_html( $booking->check_in ); ?></strong>
				</div>
				<div class="hb-summary-row">
					<span><?php esc_html_e( 'Check-out', 'hotel-booking' ); ?></span>
					<strong><?php echo esc_html( $booking->check_out ); ?></strong>
				</div>
				<div class="hb-summary-row">
					<span><?php esc_html_e( 'Guests', 'hotel-booking' ); ?></span>
					<strong><?php echo esc_html( $booking->guests ); ?></strong>
				</div>
				<div class="hb-summary-row total">
					<span><?php esc_html_e( 'Total Amount', 'hotel-booking' ); ?></span>
					<strong class="hb-total-amount">
						<?php echo esc_html( $currency_symbol . number_format( $booking->total_price, 2 ) ); ?>
					</strong>
				</div>
			</div>

			<!-- Payment Methods -->
			<div class="hb-payment-methods">
				<h3><?php esc_html_e( 'Select Payment Method', 'hotel-booking' ); ?></h3>

				<?php if ( $stripe_enabled || $paypal_enabled ) : ?>
					<div class="hb-payment-options">
						<?php if ( $stripe_enabled ) : ?>
							<div class="hb-payment-option" data-method="stripe">
								<label>
									<input type="radio" name="payment_method" value="stripe" <?php checked( $stripe_enabled && ! $paypal_enabled ); ?>>
									<span class="hb-payment-icon hb-icon-stripe">Stripe</span>
									<span><?php esc_html_e( 'Credit Card', 'hotel-booking' ); ?></span>
								</label>
							</div>
						<?php endif; ?>

						<?php if ( $paypal_enabled ) : ?>
							<div class="hb-payment-option" data-method="paypal">
								<label>
									<input type="radio" name="payment_method" value="paypal" <?php checked( $paypal_enabled && ! $stripe_enabled ); ?>>
									<span class="hb-payment-icon hb-icon-paypal">PayPal</span>
									<span><?php esc_html_e( 'PayPal', 'hotel-booking' ); ?></span>
								</label>
							</div>
						<?php endif; ?>
					</div>

					<!-- Stripe Payment Form -->
					<div class="hb-payment-form hb-stripe-form" id="hb-stripe-form" style="display: none;">
						<div class="hb-form-group">
							<label for="card-element"><?php esc_html_e( 'Card Details', 'hotel-booking' ); ?></label>
							<div id="card-element" class="stripe-card-element">
								<!-- Stripe Elements injects here -->
							</div>
							<div id="card-errors" class="stripe-card-errors" role="alert"></div>
						</div>
						<button type="button" class="hb-button hb-pay-stripe-btn" id="hb-pay-stripe-btn">
							<?php echo esc_html( sprintf( __( 'Pay %s', 'hotel-booking' ), $currency_symbol . number_format( $booking->total_price, 2 ) ) ); ?>
						</button>
					</div>

					<!-- PayPal Payment Form -->
					<div class="hb-payment-form hb-paypal-form" id="hb-paypal-form" style="display: none;">
						<div class="hb-paypal-container" id="hb-paypal-button-container">
							<!-- PayPal SDK injects button here -->
						</div>
						<p class="hb-paypal-notice">
							<?php esc_html_e( 'You will be redirected to PayPal to complete your payment securely.', 'hotel-booking' ); ?>
						</p>
					</div>

					<!-- No Payment Method Available -->
				<?php else : ?>
					<div class="hb-message error">
						<?php esc_html_e( 'No payment methods are currently available. Please contact us to complete your booking.', 'hotel-booking' ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Loading State -->
		<div class="hb-loading-overlay" id="hb-loading-overlay" style="display: none;">
			<div class="hb-spinner"></div>
			<p><?php esc_html_e( 'Processing payment...', 'hotel-booking' ); ?></p>
		</div>
	</div>
</div>

<!-- Hidden inputs for payment processing -->
<input type="hidden" id="hb-booking-id" value="<?php echo esc_attr( $booking_id ); ?>">
<input type="hidden" id="hb-payment-amount" value="<?php echo esc_attr( $booking->total_price ); ?>">
<input type="hidden" id="hb-currency" value="<?php echo esc_attr( $currency ); ?>">
<input type="hidden" id="hb-stripe-publishable-key" value="<?php echo esc_attr( get_option( 'hb_stripe_test_mode', '0' ) ? get_option( 'hb_stripe_test_public', '' ) : get_option( 'hb_stripe_live_public', '' ) ); ?>">
<input type="hidden" id="hb-paypal-client-id" value="<?php echo esc_attr( get_option( 'hb_paypal_test_mode', '0' ) ? get_option( 'hb_paypal_sandbox_client_id', '' ) : get_option( 'hb_paypal_client_id', '' ) ); ?>">
