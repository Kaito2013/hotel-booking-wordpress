# Email Template Customization Guide

## Overview

The Email Template Customization feature allows you to customize all email notifications sent by the Hotel Booking plugin through a visual editor in WordPress admin.

## Accessing the Editor

**Location:** Hotel Booking → Email Templates

## Available Templates

| Template | Description | Default Trigger |
|----------|-------------|-----------------|
| `booking_confirmation` | Confirmation email after booking | User creates booking |
| `booking_cancelled` | Cancellation notification | Booking cancelled |
| `payment_confirmation` | Payment received confirmation | Payment successful |
| `payment_failed` | Payment failure notification | Payment fails |
| `admin_new_booking` | New booking alert to admin | New booking created |
| `admin_payment_received` | Payment alert to admin | Payment received |

## Variables

Variables are dynamic values that get replaced with actual data. Use `#{variable_name}` syntax in templates.

### Common Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `#{booking_number}` | Booking ID | `#12345` |
| `#{guest_name}` | Guest's name | `John Doe` |
| `#{guest_email}` | Guest's email | `john@example.com` |
| `#{guest_phone}` | Guest's phone | `+1234567890` |
| `#{room_name}` | Room name | `Deluxe Room` |
| `#{check_in}` | Check-in date | `2024-03-15` |
| `#{check_out}` | Check-out date | `2024-03-18` |
| `#{guests}` | Number of guests | `2 Adults` |
| `#{total_amount}` | Total price with currency | `$350.00` |
| `#{hotel_name}` | Hotel/site name | `My Hotel` |

### Booking-Specific Variables

| Variable | Description |
|----------|-------------|
| `#{check_in_time}` | Default check-in time |
| `#{check_out_time}` | Default check-out time |
| `#{special_requests}` | Guest's special requests |
| `#{booking_url}` | Link to booking confirmation page |

### Payment-Specific Variables

| Variable | Description |
|----------|-------------|
| `#{payment_method}` | Payment method (Stripe/PayPal) |
| `#{transaction_id}` | Payment transaction ID |
| `#{admin_booking_url}` | Admin booking management URL |

## Editing Templates

### Step 1: Navigate to Email Templates
1. Go to **Hotel Booking → Email Templates**
2. Find the template you want to edit
3. Click **Edit**

### Step 2: Customize Content
- **Subject:** Edit the email subject line. Use variables for dynamic content.
- **Body:** Use the WordPress visual editor to customize HTML content.
- **Insert Variables:** Click on variable buttons to insert them at cursor position.

### Step 3: Preview
- See a live preview with sample data
- Variables will be highlighted
- Test layout and formatting

### Step 4: Save
- Click **Save Template**
- Changes take effect immediately for new emails

## Resetting Templates

To restore a template to default:

1. Go to **Hotel Booking → Email Templates**
2. Click **Reset** next to the template
3. Confirm the reset

⚠️ **Warning:** This will overwrite your custom changes!

## Email Design Tips

### HTML Structure

```html
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
  <div style="background: #ffffff; border-radius: 8px; padding: 30px;">
    <h1>Your Heading</h1>
    <p>Dear #{guest_name},</p>
    <!-- Your content here -->
  </div>
</body>
</html>
```

### Styling Recommendations

- Use **inline CSS** (some email clients ignore `<style>` tags)
- Max width: **600px** (email standard)
- Font: **Arial, sans-serif** (widely supported)
- Use **tables** for layout (better email compatibility)
- Avoid **external images** (may be blocked)

### Colors

| Purpose | Recommended Color |
|---------|-------------------|
| Primary/Links | `#2271b1` (WordPress blue) |
| Success | `#00a32a` (green) |
| Warning | `#dba617` (yellow) |
| Error | `#d63638` (red) |
| Background | `#f5f5f5` or `#f6f7f7` |

### Call-to-Action Buttons

```html
<a href="#{booking_url}" style="background: #2271b1; color: white; padding: 12px 24px; 
   text-decoration: none; border-radius: 4px; display: inline-block;">
   View Booking
</a>
```

## Testing Emails

### Test with Sample Data

The template editor shows a preview with sample data:

```php
$sample_variables = array(
    'booking_number' => '12345',
    'guest_name'     => 'John Doe',
    'room_name'      => 'Deluxe Room',
    // ... more variables
);
```

### Send Test Email

Add this to your theme's `functions.php`:

```php
add_action( 'admin_init', function() {
    if ( isset( $_GET['hb_test_email'] ) && current_user_can( 'manage_options' ) ) {
        $manager = Hotel_Booking_Email_Template_Manager::get_instance();
        $variables = array(
            'booking_number' => 'TEST-001',
            'guest_name'     => 'Test User',
            'guest_email'    => get_option( 'admin_email' ),
            'room_name'      => 'Test Room',
            'check_in'       => date( 'Y-m-d' ),
            'check_out'      => date( 'Y-m-d', strtotime( '+1 day' ) ),
            'guests'         => '2',
            'total_amount'   => '$100.00',
            'hotel_name'     => get_bloginfo( 'name' ),
        );
        
        $rendered = $manager->render_template( 'booking_confirmation', $variables );
        if ( $rendered ) {
            wp_mail( get_option( 'admin_email' ), $rendered['subject'], $rendered['body'], 
                     array( 'Content-Type: text/html; charset=UTF-8' ) );
            echo '<div class="notice notice-success"><p>Test email sent!</p></div>';
        }
    }
});
```

Then visit: `https://yoursite.com/wp-admin/?hb_test_email=1`

## Creating Custom Templates

### Option 1: Extend Existing Template

1. Edit an existing template
2. Add your custom HTML/content
3. Save

### Option 2: Add New Template Key

```php
add_action( 'plugins_loaded', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hb_email_templates';
    
    // Insert new template
    $wpdb->insert( $table_name, array(
        'template_key' => 'custom_template',
        'subject'      => 'Custom Subject - #{booking_number}',
        'body'         => '<h1>Custom Template</h1><p>...</p>',
        'variables'    => 'booking_number,guest_name',
        'is_active'    => 1,
    ) );
});
```

Then use it in your code:

```php
$template_manager = Hotel_Booking_Email_Template_Manager::get_instance();
$rendered = $template_manager->render_template( 'custom_template', $variables );
if ( $rendered ) {
    wp_mail( $to, $rendered['subject'], $rendered['body'], $headers );
}
```

## Troubleshooting

### Emails Not Sending

**Possible causes:**
1. Email notification disabled in Settings
2. SMTP not configured
3. WordPress `wp_mail()` not working

**Solutions:**
1. Go to **Hotel Booking → Settings → Email**
2. Enable: **Confirmation Email**
3. Install WP Mail SMTP plugin

### Variables Not Replaced

**Possible causes:**
1. Variable name misspelled
2. Variable not available in context

**Solutions:**
1. Check variable spelling (case-sensitive)
2. Use only variables listed in the template

### Email Looks Broken

**Possible causes:**
1. CSS not inline
2. HTML structure incompatible

**Solutions:**
1. Move CSS to inline `style` attributes
2. Use tables for layout
3. Test with email preview tools

### Template Not Saving

**Possible causes:**
1. WordPress capability issue
2. Database error

**Solutions:**
1. Ensure user has `manage_options` capability
2. Check database table exists
3. Check PHP error logs

## Email Client Compatibility

### Tested and Supported

| Client | Support |
|--------|---------|
| Gmail (Web) | ✅ Full |
| Gmail (Mobile) | ✅ Full |
| Outlook (Web) | ✅ Full |
| Outlook (Desktop) | ✅ Full |
| Apple Mail | ✅ Full |
| Yahoo Mail | ✅ Full |
| Thunderbird | ✅ Full |

### Known Limitations

| Issue | Workaround |
|-------|------------|
| Background images | Use background color instead |
| Custom fonts | Use web-safe fonts (Arial, etc.) |
| CSS animations | Not supported, use static design |
| JavaScript | Not supported in emails |

## Performance

- **Template loading:** < 1ms (cached)
- **Variable replacement:** < 5ms
- **Email sending:** Depends on SMTP configuration
- **Database queries:** 1 query per email

## Security

- **Input sanitization:** `sanitize_text_field()` for subject
- **HTML sanitization:** `wp_kses_post()` for body
- **SQL injection:** Prepared statements
- **XSS:** Output escaping with `esc_html()`
- **CSRF:** Nonce verification for all forms

## Multi-language Support

Templates use WordPress translation functions:
- Text domain: `hotel-booking`
- Use `esc_html_e()` for translatable text

To translate:
1. Use translation plugin (WPML, Polylang)
2. Or create `.mo`/`.po` files

## API Reference

### Hotel_Booking_Email_Template_Manager

```php
// Get instance
$manager = Hotel_Booking_Email_Template_Manager::get_instance();

// Get template
$template = $manager->get_template( 'booking_confirmation' );

// Get all templates
$templates = $manager->get_all_templates();

// Update template
$manager->update_template( 'booking_confirmation', 'New Subject', '<h1>...</h1>' );

// Render template with variables
$rendered = $manager->render_template( 'booking_confirmation', $variables );

// Reset to default
$manager->reset_to_default( 'booking_confirmation' );

// Toggle active status
$manager->toggle_template( 'booking_confirmation' );
```

## Future Enhancements

Planned features:
- [ ] Drag-and-drop email builder
- [ ] Template import/export
- [ ] A/B testing for email subjects
- [ ] Email analytics (open rate, click rate)
- [ ] Automated email sequences
- [ ] SMS notifications integration
- [ ] Multi-language email templates
- [ ] Conditional content blocks
