<?php
/**
 * Email Template Manager Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email Template Manager Class
 */
class Hotel_Booking_Email_Template_Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Email_Template_Manager
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Email_Template_Manager
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
		add_action( 'init', array( $this, 'create_table' ) );
	}

	/**
	 * Create email templates table.
	 *
	 * @return void
	 */
	public function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'hb_email_templates';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			template_key VARCHAR(100) NOT NULL,
			subject VARCHAR(255) NOT NULL,
			body TEXT NOT NULL,
			variables TEXT NOT NULL,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY template_key (template_key)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Insert default templates if table is empty
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

		if ( 0 === (int) $count ) {
			$this->insert_default_templates();
		}
	}

	/**
	 * Insert default email templates.
	 *
	 * @return void
	 */
	private function insert_default_templates() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hb_email_templates';

		$templates = array(
			array(
				'template_key' => 'booking_confirmation',
				'subject'      => 'Booking Confirmed - #{booking_number}',
				'body'         => $this->get_default_booking_confirmation_template(),
				'variables'    => 'booking_number,guest_name,guest_email,room_name,check_in,check_out,guests,total_amount,check_in_time,check_out_time,special_requests,hotel_name',
			),
			array(
				'template_key' => 'booking_cancelled',
				'subject'      => 'Booking Cancelled - #{booking_number}',
				'body'         => $this->get_default_booking_cancelled_template(),
				'variables'    => 'booking_number,guest_name,guest_email,room_name,check_in,check_out,total_amount,hotel_name',
			),
			array(
				'template_key' => 'payment_confirmation',
				'subject'      => 'Payment Received - Booking #{booking_number}',
				'body'         => $this->get_default_payment_confirmation_template(),
				'variables'    => 'booking_number,guest_name,guest_email,total_amount,payment_method,transaction_id,hotel_name',
			),
			array(
				'template_key' => 'payment_failed',
				'subject'      => 'Payment Failed - Booking #{booking_number}',
				'body'         => $this->get_default_payment_failed_template(),
				'variables'    => 'booking_number,guest_name,guest_email,total_amount,hotel_name',
			),
			array(
				'template_key' => 'admin_new_booking',
				'subject'      => 'New Booking #{booking_number}',
				'body'         => $this->get_default_admin_new_booking_template(),
				'variables'    => 'booking_number,guest_name,guest_email,guest_phone,room_name,check_in,check_out,guests,total_amount,special_requests,admin_booking_url,hotel_name',
			),
			array(
				'template_key' => 'admin_payment_received',
				'subject'      => 'Payment Received - Booking #{booking_number}',
				'body'         => $this->get_default_admin_payment_template(),
				'variables'    => 'booking_number,guest_name,guest_email,total_amount,payment_method,transaction_id,admin_booking_url,hotel_name',
			),
		);

		foreach ( $templates as $template ) {
			$wpdb->insert( $table_name, $template );
		}
	}

	/**
	 * Get template by key.
	 *
	 * @param string $template_key Template key.
	 * @return object|null
	 */
	public function get_template( $template_key ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hb_email_templates';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE template_key = %s AND is_active = 1",
				$template_key
			)
		);
	}

	/**
	 * Get all templates.
	 *
	 * @return array
	 */
	public function get_all_templates() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hb_email_templates';

		return $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY template_key ASC" );
	}

	/**
	 * Update template.
	 *
	 * @param string $template_key Template key.
	 * @param string $subject      Email subject.
	 * @param string $body         Email body.
	 * @return bool
	 */
	public function update_template( $template_key, $subject, $body ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hb_email_templates';

		return false !== $wpdb->update(
			$table_name,
			array(
				'subject' => sanitize_text_field( $subject ),
				'body'    => wp_kses_post( $body ),
			),
			array( 'template_key' => sanitize_text_field( $template_key ) )
		);
	}

	/**
	 * Toggle template active status.
	 *
	 * @param string $template_key Template key.
	 * @return bool
	 */
	public function toggle_template( $template_key ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hb_email_templates';

		$template = $this->get_template( $template_key );
		$new_status = ( $template && 1 === (int) $template->is_active ) ? 0 : 1;

		return false !== $wpdb->update(
			$table_name,
			array( 'is_active' => $new_status ),
			array( 'template_key' => sanitize_text_field( $template_key ) )
		);
	}

	/**
	 * Render template with variables.
	 *
	 * @param string $template_key Template key.
	 * @param array  $variables    Variables to replace.
	 * @return array|false Array with 'subject' and 'body', or false on failure.
	 */
	public function render_template( $template_key, $variables ) {
		$template = $this->get_template( $template_key );

		if ( ! $template ) {
			return false;
		}

		$subject = $template->subject;
		$body    = $template->body;

		foreach ( $variables as $key => $value ) {
			$subject = str_replace( '#{' . $key . '}', $value, $subject );
			$body    = str_replace( '#{' . $key . '}', $value, $body );
		}

		return array(
			'subject' => $subject,
			'body'    => $body,
		);
	}

	/**
	 * Reset template to default.
	 *
	 * @param string $template_key Template key.
	 * @return bool
	 */
	public function reset_to_default( $template_key ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hb_email_templates';

		$defaults = $this->get_default_template( $template_key );

		if ( ! $defaults ) {
			return false;
		}

		return false !== $wpdb->update(
			$table_name,
			array(
				'subject' => $defaults['subject'],
				'body'    => $defaults['body'],
			),
			array( 'template_key' => sanitize_text_field( $template_key ) )
		);
	}

	/**
	 * Get default template content.
	 *
	 * @param string $template_key Template key.
	 * @return array|null
	 */
	private function get_default_template( $template_key ) {
		$defaults = array(
			'booking_confirmation'    => array(
				'subject' => 'Booking Confirmed - #{booking_number}',
				'body'    => $this->get_default_booking_confirmation_template(),
			),
			'booking_cancelled'       => array(
				'subject' => 'Booking Cancelled - #{booking_number}',
				'body'    => $this->get_default_booking_cancelled_template(),
			),
			'payment_confirmation'    => array(
				'subject' => 'Payment Received - Booking #{booking_number}',
				'body'    => $this->get_default_payment_confirmation_template(),
			),
			'payment_failed'          => array(
				'subject' => 'Payment Failed - Booking #{booking_number}',
				'body'    => $this->get_default_payment_failed_template(),
			),
			'admin_new_booking'       => array(
				'subject' => 'New Booking #{booking_number}',
				'body'    => $this->get_default_admin_new_booking_template(),
			),
			'admin_payment_received'  => array(
				'subject' => 'Payment Received - Booking #{booking_number}',
				'body'    => $this->get_default_admin_payment_template(),
			),
		);

		return $defaults[ $template_key ] ?? null;
	}

	/**
	 * Default booking confirmation template.
	 *
	 * @return string
	 */
	private function get_default_booking_confirmation_template() {
		return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;">
<div style="background: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
	<h1 style="color: #2271b1; margin-top: 0;">Booking Confirmed!</h1>
	<p>Dear #{guest_name},</p>
	<p>Your booking has been confirmed. Here are the details:</p>
	<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Booking #:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{booking_number}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Room:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{room_name}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Check-in:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{check_in}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Check-out:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{check_out}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Guests:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{guests}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Total:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{total_amount}</td></tr>
	</table>
	<p>Thank you for choosing #{hotel_name}!</p>
	<p style="color: #666; font-size: 12px; margin-top: 30px;">If you have any questions, please contact us.</p>
</div>
</body>
</html>';
	}

	/**
	 * Default booking cancelled template.
	 *
	 * @return string
	 */
	private function get_default_booking_cancelled_template() {
		return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;">
<div style="background: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
	<h1 style="color: #d63638; margin-top: 0;">Booking Cancelled</h1>
	<p>Dear #{guest_name},</p>
	<p>Your booking <strong>#{booking_number}</strong> has been cancelled successfully.</p>
	<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Room:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{room_name}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Check-in:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{check_in}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Check-out:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{check_out}</td></tr>
	</table>
	<p>We hope to see you again at #{hotel_name}.</p>
</div>
</body>
</html>';
	}

	/**
	 * Default payment confirmation template.
	 *
	 * @return string
	 */
	private function get_default_payment_confirmation_template() {
		return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;">
<div style="background: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
	<h1 style="color: #00a32a; margin-top: 0;">Payment Received</h1>
	<p>Dear #{guest_name},</p>
	<p>We have received your payment. Thank you!</p>
	<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Booking #:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{booking_number}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Amount:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{total_amount}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Method:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{payment_method}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Transaction:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{transaction_id}</td></tr>
	</table>
	<p>Your booking is now confirmed. We look forward to welcoming you!</p>
</div>
</body>
</html>';
	}

	/**
	 * Default payment failed template.
	 *
	 * @return string
	 */
	private function get_default_payment_failed_template() {
		return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;">
<div style="background: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
	<h1 style="color: #d63638; margin-top: 0;">Payment Failed</h1>
	<p>Dear #{guest_name},</p>
	<p>Unfortunately, your payment for booking <strong>#{booking_number}</strong> could not be processed.</p>
	<p>Please try again or contact us for assistance.</p>
	<p>Thank you,<br>#{hotel_name}</p>
</div>
</body>
</html>';
	}

	/**
	 * Default admin new booking template.
	 *
	 * @return string
	 */
	private function get_default_admin_new_booking_template() {
		return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;">
<div style="background: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
	<h1 style="color: #2271b1; margin-top: 0;">New Booking</h1>
	<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Booking #:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{booking_number}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Guest:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{guest_name}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Email:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{guest_email}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Phone:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{guest_phone}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Room:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{room_name}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Check-in:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{check_in}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Check-out:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{check_out}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Guests:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{guests}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Total:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{total_amount}</td></tr>
	</table>
	<p><a href="#{admin_booking_url}" style="background: #2271b1; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">View Booking</a></p>
</div>
</body>
</html>';
	}

	/**
	 * Default admin payment template.
	 *
	 * @return string
	 */
	private function get_default_admin_payment_template() {
		return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;">
<div style="background: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
	<h1 style="color: #00a32a; margin-top: 0;">Payment Received</h1>
	<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Booking #:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{booking_number}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Guest:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{guest_name}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Amount:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{total_amount}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Method:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{payment_method}</td></tr>
		<tr><td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Transaction:</strong></td><td style="padding: 10px; border-bottom: 1px solid #eee;">#{transaction_id}</td></tr>
	</table>
	<p><a href="#{admin_booking_url}" style="background: #2271b1; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">View Booking</a></p>
</div>
</body>
</html>';
	}
}
