<?php
/**
 * Email Templates Admin View
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get action and template key
$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
$template_key = isset( $_GET['template'] ) ? sanitize_text_field( $_GET['template'] ) : '';

// Handle save action
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['save_template'] ) ) {
	$nonce = isset( $_POST['hb_email_template_nonce'] ) ? $_POST['hb_email_template_nonce'] : '';
	if ( wp_verify_nonce( $nonce, 'hb_save_email_template' ) ) {
		$template_key = sanitize_text_field( $_POST['template_key'] );
		$subject = sanitize_text_field( $_POST['template_subject'] );
		$body = wp_kses_post( $_POST['template_body'] );

		Hotel_Booking_Email_Template_Manager::get_instance()->update_template( $template_key, $subject, $body );

		echo '<div class="notice notice-success"><p>Template updated successfully.</p></div>';
	}
}

// Handle reset action
if ( 'reset' === $action && $template_key ) {
	$nonce = isset( $_GET['nonce'] ) ? $_GET['nonce'] : '';
	if ( wp_verify_nonce( $nonce, 'hb_reset_email_template' ) ) {
		Hotel_Booking_Email_Template_Manager::get_instance()->reset_to_default( $template_key );
		echo '<div class="notice notice-success"><p>Template reset to default.</p></div>';
		$action = 'list';
	}
}

// Get all templates
$templates = Hotel_Booking_Email_Template_Manager::get_instance()->get_all_templates();

// Get current template if editing
$current_template = null;
if ( 'edit' === $action && $template_key ) {
	$current_template = Hotel_Booking_Email_Template_Manager::get_instance()->get_template( $template_key );
}
?>

<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php if ( 'list' === $action ) : ?>
			<?php esc_html_e( 'Email Templates', 'hotel-booking' ); ?>
		<?php elseif ( 'edit' === $action ) : ?>
			<?php echo esc_html( sprintf( __( 'Edit Template: %s', 'hotel-booking' ), str_replace( '_', ' ', $template_key ) ) ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-booking-email-templates' ) ); ?>" class="page-title-action"><?php esc_html_e( '← Back to Templates', 'hotel-booking' ); ?></a>
		<?php endif; ?>
	</h1>

	<?php if ( 'list' === $action ) : ?>
		<!-- Template List -->
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th width="20%"><?php esc_html_e( 'Template', 'hotel-booking' ); ?></th>
					<th width="30%"><?php esc_html_e( 'Subject', 'hotel-booking' ); ?></th>
					<th width="20%"><?php esc_html_e( 'Variables', 'hotel-booking' ); ?></th>
					<th width="15%"><?php esc_html_e( 'Status', 'hotel-booking' ); ?></th>
					<th width="15%"><?php esc_html_e( 'Actions', 'hotel-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $templates as $template ) : ?>
					<?php
					$template_label = str_replace( '_', ' ', $template->template_key );
					$template_label = ucwords( $template_label );
					$variables = explode( ',', $template->variables );
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( $template_label ); ?></strong>
							<br><code><?php echo esc_html( $template->template_key ); ?></code>
						</td>
						<td><?php echo esc_html( $template->subject ); ?></td>
						<td>
							<?php
							foreach ( array_slice( $variables, 0, 4 ) as $var ) {
								echo '<code>{' . esc_html( trim( $var ) ) . '}</code> ';
							}
							if ( count( $variables ) > 4 ) {
								echo '<br><small>+' . ( count( $variables ) - 4 ) . ' more</small>';
							}
							?>
						</td>
						<td>
							<?php if ( '1' === (string) $template->is_active ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
								<?php esc_html_e( 'Active', 'hotel-booking' ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-dismiss" style="color: #d63638;"></span>
								<?php esc_html_e( 'Inactive', 'hotel-booking' ); ?>
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-booking-email-templates&action=edit&template=' . $template->template_key ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'hotel-booking' ); ?></a>
							<?php
							$reset_url = wp_nonce_url(
								admin_url( 'admin.php?page=hotel-booking-email-templates&action=reset&template=' . $template->template_key ),
								'hb_reset_email_template'
							);
							?>
							<a href="<?php echo esc_url( $reset_url ); ?>" class="button button-small" onclick="return confirm('Reset to default? Your changes will be lost.');"><?php esc_html_e( 'Reset', 'hotel-booking' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php elseif ( 'edit' === $action && $current_template ) : ?>
		<!-- Edit Template Form -->
		<form method="post" action="">
			<?php wp_nonce_field( 'hb_save_email_template', 'hb_email_template_nonce' ); ?>
			<input type="hidden" name="template_key" value="<?php echo esc_attr( $current_template->template_key ); ?>">

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="template_subject"><?php esc_html_e( 'Subject', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<input type="text" id="template_subject" name="template_subject" value="<?php echo esc_attr( $current_template->subject ); ?>" class="large-text" required>
						<p class="description"><?php esc_html_e( 'Email subject line. Use #{variable_name} for dynamic content.', 'hotel-booking' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Available Variables', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<?php
						$variables = explode( ',', $current_template->variables );
						foreach ( $variables as $var ) {
							$var = trim( $var );
							echo '<code style="display: inline-block; margin: 2px 4px 2px 0; padding: 4px 8px; background: #f0f0f0; border-radius: 3px; cursor: pointer;" onclick="insertVariable(\'' . esc_attr( $var ) . '\')" title="Click to insert">#{' . esc_html( $var ) . '}</code> ';
						}
						?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="template_body"><?php esc_html_e( 'Email Body', 'hotel-booking' ); ?></label>
					</th>
					<td>
						<?php
						$editor_id = 'template_body';
						$editor_settings = array(
							'textarea_name' => 'template_body',
							'textarea_rows' => 20,
							'media_buttons' => true,
							'teeny'         => false,
							'quicktags'     => true,
						);
						wp_editor( $current_template->body, $editor_id, $editor_settings );
						?>
						<p class="description"><?php esc_html_e( 'HTML email template. Use #{variable_name} for dynamic content.', 'hotel-booking' ); ?></p>
					</td>
				</tr>
			</table>

			<div class="submit-box">
				<input type="submit" name="save_template" class="button button-primary button-hero" value="<?php esc_attr_e( 'Save Template', 'hotel-booking' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-booking-email-templates' ) ); ?>" class="button button-secondary button-hero"><?php esc_html_e( 'Cancel', 'hotel-booking' ); ?></a>
			</div>
		</form>

		<!-- Preview Section -->
		<div style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
			<h3><?php esc_html_e( 'Template Preview', 'hotel-booking' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Preview shows the template with sample data. Variables will be replaced with actual values in real emails.', 'hotel-booking' ); ?></p>
			<div id="email-preview" style="border: 1px solid #ddd; padding: 20px; background: #f9f9f9; margin-top: 10px;">
				<?php
				$sample_variables = array(
					'booking_number'    => '12345',
					'guest_name'        => 'John Doe',
					'guest_email'       => 'john@example.com',
					'guest_phone'       => '+1234567890',
					'room_name'         => 'Deluxe Room',
					'check_in'          => date( 'Y-m-d', strtotime( '+7 days' ) ),
					'check_out'         => date( 'Y-m-d', strtotime( '+10 days' ) ),
					'guests'            => '2 Adults',
					'total_amount'      => get_option( 'hb_currency_symbol', '$' ) . '350.00',
					'check_in_time'     => get_option( 'hb_default_check_in', '14:00' ),
					'check_out_time'    => get_option( 'hb_default_check_out', '11:00' ),
					'special_requests'  => 'Late check-in please',
					'hotel_name'        => get_bloginfo( 'name' ),
					'payment_method'    => 'Stripe',
					'transaction_id'    => 'pi_1234567890',
					'admin_booking_url' => admin_url( 'admin.php?page=hotel-booking-bookings' ),
				);

				$rendered = Hotel_Booking_Email_Template_Manager::get_instance()->render_template( $current_template->template_key, $sample_variables );
				if ( $rendered ) {
					echo $rendered['body'];
				}
				?>
			</div>
		</div>

		<script>
		function insertVariable(variable) {
			var editor = document.getElementById('template_body');
			if (typeof tinyMCE !== 'undefined' && tinyMCE.get('template_body')) {
				tinyMCE.get('template_body').insertContent('#{' + variable + '}');
			} else {
				editor.value += '#{' + variable + '}';
			}
		}
		</script>
	<?php endif; ?>
</div>

<style>
.submit-box {
	margin: 20px 0;
	padding: 15px;
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
}
.submit-box .button {
	margin-right: 10px;
}
#email-preview pre {
	white-space: pre-wrap;
	word-wrap: break-word;
}
.wp-list-table code {
	font-size: 12px;
	background: #f0f0f0;
	padding: 2px 6px;
	border-radius: 3px;
}
</style>
