# Developer Guide

This guide is for developers who want to contribute to or extend the Hotel Booking WordPress Plugin.

## Development Environment

### Requirements
- WordPress 6.4+
- PHP 8.0+
- MySQL 5.7+
- Node.js & npm (for building assets, if needed)
- Git

### Local Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/Kaito2013/hotel-booking-wordpress.git
   cd hotel-booking-wordpress
   ```

2. **Install WordPress locally**
   ```bash
   # Using WP-CLI
   wp core download
   wp config create --dbname=hotel_booking_dev --dbuser=root --dbpass= --dbhost=localhost
   wp core install --url=http://localhost/hotel-booking --title="Hotel Booking Dev" --admin_user=admin --admin_password=admin --admin_email=admin@example.com
   ```

3. **Symlink plugin to WordPress**
   ```bash
   ln -s /path/to/hotel-booking-wordpress /path/to/wordpress/wp-content/plugins/hotel-booking
   ```

4. **Activate plugin**
   ```bash
   wp plugin activate hotel-booking
   ```

## Project Structure

```
hotel-booking-wordpress/
├── admin/
│   ├── class-admin.php              # Main admin class
│   ├── class-admin-settings.php    # Settings page handler
│   ├── class-admin-dashboard.php   # Dashboard statistics
│   ├── class-admin-calendar.php    # Calendar view
│   ├── class-room-metaboxes.php    # Room post type metaboxes
│   └── views/
│       ├── dashboard.php            # Dashboard template
│       ├── calendar.php             # Calendar template
│       └── settings.php             # Settings template
├── assets/
│   ├── css/
│   │   ├── admin.css                # Admin styles
│   │   └── frontend.css             # Frontend styles
│   └── js/
│       ├── admin.js                 # Admin scripts
│       └── frontend.js              # Frontend scripts
├── includes/
│   ├── class-post-types.php         # Custom post types
│   ├── class-availability-manager.php # Availability logic
│   ├── class-pricing-manager.php    # Pricing logic
│   ├── class-booking-manager.php    # Booking logic
│   ├── class-notification-manager.php # Email notifications
│   ├── class-rest-api.php           # REST API endpoints
│   ├── class-frontend.php           # Frontend shortcodes
│   └── payments/
│       ├── class-payment-gateway.php  # Base payment gateway
│       ├── class-stripe-gateway.php    # Stripe integration
│       └── class-paypal-gateway.php    # PayPal integration
├── languages/                       # Translation files
├── templates/                       # Frontend templates
├── tests/                           # Unit tests
├── hotel-booking.php                # Main plugin file
├── README.md                        # User documentation
├── CHANGELOG.md                     # Changelog
└── LICENSE                          # GPL-2.0+ license
```

## Coding Standards

### PHP
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Use proper namespace for all classes
- Add PHPDoc comments for all functions and methods
- Use `wp_enqueue_*` for scripts and styles
- Use `wp_localize_script()` for passing data to JavaScript

### Example PHPDoc:
```php
/**
 * Get room availability for a date range.
 *
 * @param int    $room_id   Room ID.
 * @param string $check_in  Check-in date (Y-m-d).
 * @param string $check_out Check-out date (Y-m-d).
 * @return bool True if available, false otherwise.
 */
public function is_available( $room_id, $check_in, $check_out ) {
    // Implementation
}
```

### JavaScript
- Use strict mode
- Wrap in IIFE to avoid global scope pollution
- Use jQuery (already included in WordPress)
- Add comments for complex logic

### CSS
- Use BEM-like naming convention
- Use CSS variables for colors and spacing
- Ensure responsive design
- Prefix all classes with `hb-` to avoid conflicts

### Example:
```css
.hb-room-card { /* Block */ }
.hb-room-card__image { /* Element */ }
.hb-room-card--featured { /* Modifier */ }
```

## Security Best Practices

### 1. Input Sanitization
```php
// Bad
$title = $_POST['title'];

// Good
$title = sanitize_text_field( $_POST['title'] );
```

### 2. Output Escaping
```php
// Bad
echo $room->title;

// Good
echo esc_html( $room->title );
// or
echo wp_kses_post( $room->description );
```

### 3. Nonce Verification
```php
// Verify nonce before processing form
if ( ! isset( $_POST['hb_room_metabox_nonce'] ) ||
     ! wp_verify_nonce( $_POST['hb_room_metabox_nonce'], 'hb_room_metabox' ) ) {
    return;
}
```

### 4. SQL Injection Prevention
```php
// Bad
$sql = "SELECT * FROM $table WHERE id = $id";

// Good
$sql = $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id );
```

### 5. Capability Checks
```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have permission to access this page.', 'hotel-booking' ) );
}
```

## Adding New Features

### 1. Create a New Admin Page

```php
// Step 1: Add menu item in class-admin.php
add_submenu_page(
    'hotel-booking',
    'My Page',
    'My Page',
    'manage_options',
    'hotel-booking-my-page',
    array( $this, 'render_my_page' )
);

// Step 2: Render method
public function render_my_page() {
    include HOTEL_BOOKING_PLUGIN_DIR . 'admin/views/my-page.php';
}

// Step 3: Create template file
// admin/views/my-page.php
```

### 2. Add a New REST API Endpoint

```php
// In class-rest-api.php

register_rest_route(
    'hotel-booking/v1',
    '/my-endpoint',
    array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'my_endpoint_handler' ),
            'permission_callback' => '__return_true',
        ),
    )
);

public function my_endpoint_handler( $request ) {
    // Your logic here
    return new WP_REST_Response( $data, 200 );
}
```

### 3. Add a New Shortcode

```php
// In class-frontend.php

add_shortcode( 'hotel_booking_my_shortcode', array( $this, 'render_my_shortcode' ) );

public function render_my_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'param1' => 'default',
        ),
        $atts
    );

    ob_start();
    // Your template here
    return ob_get_clean();
}
```

## Testing

### Manual Testing Checklist

- [ ] Create a room with all details
- [ ] Set pricing rules
- [ ] Search for rooms via shortcode
- [ ] Create a booking via frontend
- [ ] Check booking in admin dashboard
- [ ] Verify email notifications sent
- [ ] Test calendar availability
- [ ] Update booking status
- [ ] Test settings pages

### Automated Testing (Planned)

```bash
# Run PHPUnit tests
phpunit

# Run JavaScript tests (if implemented)
npm test
```

## Database Queries

### Get Available Rooms
```sql
SELECT DISTINCT room_id
FROM wp_hb_availability
WHERE status = 'available'
AND check_in >= '2024-05-01'
AND check_out < '2024-05-05';
```

### Get Bookings for a Room
```sql
SELECT * FROM wp_hb_bookings
WHERE room_id = 123
AND booking_status != 'cancelled'
ORDER BY check_in DESC;
```

## Debugging

### Enable Debug Mode
Add to `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### Log Messages
```php
error_log( 'My debug message: ' . print_r( $data, true ) );
```

### Use Query Monitor Plugin
Install [Query Monitor](https://wordpress.org/plugins/query-monitor/) for:
- SQL query analysis
- HTTP request debugging
- Hook and action inspection
- Conditional tag debugging

## Contributing

### Pull Request Process

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Add tests (if applicable)
5. Update documentation
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to branch (`git push origin feature/amazing-feature`)
8. Create a Pull Request

### Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Example:**
```
feat(booking): add cancellation feature

Allows users to cancel bookings from frontend with confirmation modal.

Closes #123
```

## Release Process

1. Update version in `hotel-booking.php`
2. Update `CHANGELOG.md`
3. Update `README.md` if needed
4. Create Git tag: `git tag v1.0.0`
5. Push tag: `git push origin v1.0.0`
6. Create GitHub release
7. Commit to WordPress.org plugin repository (if published)

## Resources

- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [WP-CLI Commands](https://wp-cli.org/commands/)

## Support

For development questions:
- Open a GitHub Issue
- Email: dev@example.com
- Discord: (coming soon)

---

Happy coding! 🚀
