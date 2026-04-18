<?php
/**
 * Admin Settings View
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings
$general_settings = array(
	'currency'            => get_option( 'hb_currency', 'USD' ),
	'currency_symbol'     => get_option( 'hb_currency_symbol', '$' ),
	'default_check_in'    => get_option( 'hb_default_check_in', '14:00' ),
	'default_check_out'   => get_option( 'hb_default_check_out', '11:00' ),
);

$payment_settings = array(
	'stripe_enabled'        => get_option( 'hb_stripe_enabled', '0' ),
	'stripe_test_mode'      => get_option( 'hb_stripe_test_mode', '0' ),
	'stripe_test_public'    => get_option( 'hb_stripe_test_public', '' ),
	'stripe_test_secret'    => get_option( 'hb_stripe_test_secret', '' ),
	'stripe_live_public'    => get_option( 'hb_stripe_live_public', '' ),
	'stripe_live_secret'    => get_option( 'hb_stripe_live_secret', '' ),
	'paypal_enabled'        => get_option( 'hb_paypal_enabled', '0' ),
	'paypal_test_mode'      => get_option( 'hb_paypal_test_mode', '0' ),
	'paypal_client_id'      => get_option( 'hb_paypal_client_id', '' ),
	'paypal_secret'         => get_option( 'hb_paypal_secret', '' ),
);

$email_settings = array(
	'confirmation_email'    => get_option( 'hb_confirmation_email', '1' ),
	'reminder_email'        => get_option( 'hb_reminder_email', '1' ),
	'cancellation_email'    => get_option( 'hb_cancellation_email', '1' ),
	'admin_notification'    => get_option( 'hb_admin_notification', '1' ),
);
?>

<div class="wrap hotel-booking-wrap">
	<h1><?php esc_html_e( 'Hotel Booking Settings', 'hotel-booking' ); ?></h1>

	<!-- Settings Tabs -->
	<h2 class="nav-tab-wrapper">
		<a href="#general" class="nav-tab nav-tab-active"><?php esc_html_e( 'General', 'hotel-booking' ); ?></a>
		<a href="#payment" class="nav-tab"><?php esc_html_e( 'Payment', 'hotel-booking' ); ?></a>
		<a href="#email" class="nav-tab"><?php esc_html_e( 'Email', 'hotel-booking' ); ?></a>
	</h2>

	<!-- General Settings -->
	<div id="general" class="hb-settings-panel">
		<form id="hb-general-settings-form">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="hb-currency"><?php esc_html_e( 'Currency', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<select id="hb-currency" name="hb_currency">
							<option value="USD" <?php selected( $general_settings['currency'], 'USD' ); ?>>USD ($)</option>
							<option value="EUR" <?php selected( $general_settings['currency'], 'EUR' ); ?>>EUR (€)</option>
							<option value="GBP" <?php selected( $general_settings['currency'], 'GBP' ); ?>>GBP (£)</option>
							<option value="JPY" <?php selected( $general_settings['currency'], 'JPY' ); ?>>JPY (¥)</option>
							<option value="VND" <?php selected( $general_settings['currency'], 'VND' ); ?>>VND (₫)</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hb-currency-symbol"><?php esc_html_e( 'Currency Symbol', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<input type="text" id="hb-currency-symbol" name="hb_currency_symbol" value="<?php echo esc_attr( $general_settings['currency_symbol'] ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hb-default-check-in"><?php esc_html_e( 'Default Check-in Time', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<input type="time" id="hb-default-check-in" name="hb_default_check_in" value="<?php echo esc_attr( $general_settings['default_check_in'] ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hb-default-check-out"><?php esc_html_e( 'Default Check-out Time', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<input type="time" id="hb-default-check-out" name="hb_default_check_out" value="<?php echo esc_attr( $general_settings['default_check_out'] ); ?>" class="regular-text">
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Save Settings', 'hotel-booking' ); ?>
				</button>
			</p>
		</form>
	</div>

	<!-- Payment Settings -->
	<div id="payment" class="hb-settings-panel" style="display: none;">
		<h3><?php esc_html_e( 'Stripe', 'hotel-booking' ); ?></h3>
		<form id="hb-stripe-settings-form">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="hb-stripe-enabled"><?php esc_html_e( 'Enable Stripe', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" id="hb-stripe-enabled" name="hb_stripe_enabled" value="1" <?php checked( $payment_settings['stripe_enabled'], '1' ); ?>>
							<?php esc_html_e( 'Enable Stripe payments', 'hotel-booking' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hb-stripe-test-mode"><?php esc_html_e( 'Test Mode', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" id="hb-stripe-test-mode" name="hb_stripe_test_mode" value="1" <?php checked( $payment_settings['stripe_test_mode'], '1' ); ?>>
							<?php esc_html_e( 'Use test mode', 'hotel-booking' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hb-stripe-test-public"><?php esc_html_e( 'Test Publishable Key', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<input type="text" id="hb-stripe-test-public" name="hb_stripe_test_public" value="<?php echo esc_attr( $payment_settings['stripe_test_public'] ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hb-stripe-test-secret"><?php esc_html_e( 'Test Secret Key', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<input type="password" id="hb-stripe-test-secret" name="hb_stripe_test_secret" value="<?php echo esc_attr( $payment_settings['stripe_test_secret'] ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hb-stripe-live-public"><?php esc_html_e( 'Live Publishable Key', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<input type="text" id="hb-stripe-live-public" name="hb_stripe_live_public" value="<?php echo esc_attr( $payment_settings['stripe_live_public'] ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hb-stripe-live-secret"><?php esc_html_e( 'Live Secret Key', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<input type="password" id="hb-stripe-live-secret" name="hb_stripe_live_secret" value="<?php echo esc_attr( $payment_settings['stripe_live_secret'] ); ?>" class="regular-text">
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Save Stripe Settings', 'hotel-booking' ); ?>
				</button>
			</p>
		</form>

		<hr>

		<h3><?php esc_html_e( 'PayPal', 'hotel-booking' ); ?></h3>
		<form id="hb-paypal-settings-form">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="hb-paypal-enabled"><?php esc_html_e( 'Enable PayPal', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" id="hb-paypal-enabled" name="hb_paypal_enabled" value="1" <?php checked( $payment_settings['paypal_enabled'], '1' ); ?>>
							<?php esc_html_e( 'Enable PayPal payments', 'hotel-booking' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hb-paypal-test-mode"><?php esc_html_e( 'Test Mode', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" id="hb-paypal-test-mode" name="hb_paypal_test_mode" value="1" <?php checked( $payment_settings['paypal_test_mode'], '1' ); ?>>
							<?php esc_html_e( 'Use sandbox mode', 'hotel-booking' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hb-paypal-client-id"><?php esc_html_e( 'Client ID', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<input type="text" id="hb-paypal-client-id" name="hb_paypal_client_id" value="<?php echo esc_attr( $payment_settings['paypal_client_id'] ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="hb-paypal-secret"><?php esc_html_e( 'Secret', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<input type="password" id="hb-paypal-secret" name="hb_paypal_secret" value="<?php echo esc_attr( $payment_settings['paypal_secret'] ); ?>" class="regular-text">
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Save PayPal Settings', 'hotel-booking' ); ?>
				</button>
			</p>
		</form>
	</div>

	<!-- Email Settings -->
	<div id="email" class="hb-settings-panel" style="display: none;">
		<form id="hb-email-settings-form">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Confirmation Email', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="hb_confirmation_email" value="1" <?php checked( $email_settings['confirmation_email'], '1' ); ?>>
							<?php esc_html_e( 'Send confirmation email after booking', 'hotel-booking' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Reminder Email', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="hb_reminder_email" value="1" <?php checked( $email_settings['reminder_email'], '1' ); ?>>
							<?php esc_html_e( 'Send reminder email before check-in', 'hotel-booking' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Cancellation Email', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="hb_cancellation_email" value="1" <?php checked( $email_settings['cancellation_email'], '1' ); ?>>
							<?php esc_html_e( 'Send email when booking is cancelled', 'hotel-booking' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Admin Notification', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="hb_admin_notification" value="1" <?php checked( $email_settings['admin_notification'], '1' ); ?>>
							<?php esc_html_e( 'Notify admin when new booking is created', 'hotel-booking' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Save Email Settings', 'hotel-booking' ); ?>
				</button>
			</p>
		</form>
	</div>
</div>
