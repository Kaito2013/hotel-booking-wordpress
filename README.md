# Hotel Booking WordPress Plugin

Complete hotel booking system for WordPress with room management, availability calendar, pricing, online booking, and payment integration (Stripe & PayPal).

## Features

### Core Features
- **Room Management**: Custom post type for hotel rooms with detailed information
- **Availability Management**: Real-time availability tracking and calendar view
- **Pricing Management**: Flexible pricing with date-based rules and adjustments
- **Booking System**: Complete booking workflow with status management
- **Payment Integration**: Stripe and PayPal payment gateways
- **Notification System**: Email notifications for booking confirmations and updates
- **Admin Dashboard**: Statistics and booking overview
- **Calendar View**: Visual availability calendar for all rooms
- **REST API**: Full REST API for frontend integration

### Admin Features
- Dashboard with key statistics
- Booking management and status updates
- Room availability calendar
- Payment settings and configuration
- Email notification settings
- Taxonomy management for room types and amenities

### Frontend Features
- Room search with filters
- Availability checking
- Price calculation
- Online booking form
- Payment processing
- Booking confirmation

## Requirements

- WordPress 6.4+
- PHP 8.0+
- MySQL 5.7+

## Installation

### Manual Installation

1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Select the zip file and click "Install Now"
4. Activate the plugin

### Installation from GitHub

```bash
cd wp-content/plugins/
git clone https://github.com/Kaito2013/hotel-booking-wordpress.git hotel-booking
```

Then activate the plugin from WordPress admin.

## Setup

### 1. Create Rooms

1. Go to **Hotel Booking → Rooms → Add New**
2. Enter room details:
   - Title: Room name
   - Description: Room description
   - Room Image: Featured image
   - Room Capacity: Number of guests
   - Room Price: Base price per night
   - Room Size: Size in sq ft/m
   - Number of Beds: Bed count
3. Select room type and amenities
4. Publish

### 2. Configure Settings

Go to **Hotel Booking → Settings**:

#### General Settings
- Currency: Select your currency
- Default Check-in Time: Set default check-in time
- Default Check-out Time: Set default check-out time

#### Payment Settings

**Stripe:**
1. Enable Stripe
2. Set test mode or live mode
3. Enter your Stripe API keys
4. Save settings

**PayPal:**
1. Enable PayPal
2. Set test mode or live mode
3. Enter your PayPal API credentials
4. Save settings

#### Email Settings
- Enable/disable confirmation emails
- Enable/disable reminder emails
- Enable/disable cancellation emails
- Enable/disable admin notifications

### 3. Integrate with Frontend

Use the REST API to integrate with your theme or custom frontend:

#### Get Available Rooms

```javascript
fetch('/wp-json/hotel-booking/v1/rooms?check_in=2024-05-01&check_out=2024-05-05&guests=2')
  .then(response => response.json())
  .then(data => console.log(data.rooms));
```

#### Check Availability

```javascript
fetch('/wp-json/hotel-booking/v1/availability?room_id=123&check_in=2024-05-01&check_out=2024-05-05')
  .then(response => response.json())
  .then(data => console.log(data.available));
```

#### Calculate Price

```javascript
fetch('/wp-json/hotel-booking/v1/pricing?room_id=123&check_in=2024-05-01&check_out=2024-05-05')
  .then(response => response.json())
  .then(data => console.log(data.price));
```

#### Create Booking

```javascript
fetch('/wp-json/hotel-booking/v1/bookings', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    room_id: 123,
    check_in: '2024-05-01',
    check_out: '2024-05-05',
    guests: 2,
    first_name: 'John',
    last_name: 'Doe',
    email: 'john@example.com',
    phone: '+1234567890',
    payment_method: 'stripe',
  })
})
  .then(response => response.json())
  .then(data => console.log(data));
```

## Database Schema

The plugin creates three custom database tables:

### `wp_hb_availability`
Stores room availability by date:
- `id`: Primary key
- `room_id`: Room ID
- `check_in`: Check-in date
- `check_out`: Check-out date
- `status`: Status (available/booked)
- `booking_id`: Associated booking ID
- `created_at`: Creation timestamp

### `wp_hb_pricing`
Stores room pricing by date:
- `id`: Primary key
- `room_id`: Room ID
- `start_date`: Price start date
- `end_date`: Price end date
- `price`: Price per night
- `min_nights`: Minimum nights
- `max_nights`: Maximum nights
- `created_at`: Creation timestamp

### `wp_hb_bookings`
Stores booking information:
- `id`: Primary key
- `room_id`: Room ID
- `user_id`: User ID (if logged in)
- `first_name`: Guest first name
- `last_name`: Guest last name
- `email`: Guest email
- `phone`: Guest phone
- `check_in`: Check-in date
- `check_out`: Check-out date
- `guests`: Number of guests
- `total_price`: Total booking price
- `payment_method`: Payment method (stripe/paypal)
- `payment_status`: Payment status (pending/completed/failed)
- `payment_id`: Payment transaction ID
- `booking_status`: Booking status (pending/confirmed/cancelled/completed)
- `notes`: Booking notes
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

## REST API Endpoints

### Rooms
- `GET /wp-json/hotel-booking/v1/rooms` - Get all rooms (with filters)
- `GET /wp-json/hotel-booking/v1/rooms/{id}` - Get room by ID

### Availability
- `GET /wp-json/hotel-booking/v1/availability` - Check room availability

### Pricing
- `GET /wp-json/hotel-booking/v1/pricing` - Calculate booking price

### Bookings
- `POST /wp-json/hotel-booking/v1/bookings` - Create new booking
- `GET /wp-json/hotel-booking/v1/bookings/{id}` - Get booking by ID (requires login)

### Settings
- `GET /wp-json/hotel-booking/v1/settings` - Get public settings

## Custom Post Types

### `hb_room`
Hotel rooms with the following metadata:
- `_hb_room_capacity`: Room capacity (number of guests)
- `_hb_room_price`: Base price per night
- `_hb_room_size`: Room size
- `_hb_room_beds`: Number of beds
- `_hb_pricing_rules`: Pricing rules array

### `hb_booking`
Bookings (admin view only)

## Taxonomies

### `room_type`
Hierarchical taxonomy for room types (e.g., Standard, Deluxe, Suite)

### `room_amenity`
Non-hierarchical taxonomy for room amenities (e.g., WiFi, AC, TV, Pool)

## Hooks and Filters

### Actions
- `hb_booking_created` - Fired after booking is created
- `hb_booking_status_updated` - Fired when booking status changes
- `hb_payment_status_updated` - Fired when payment status changes

### Usage Example

```php
add_action( 'hb_booking_created', 'my_custom_booking_handler', 10, 2 );

function my_custom_booking_handler( $booking_id, $data ) {
    // Do something with the booking
    error_log( "New booking created: #$booking_id" );
}
```

## Payment Gateways

### Stripe
- Requires Stripe PHP SDK
- Supports Payment Intents API
- Test mode and live mode
- Webhook handling

### PayPal
- Requires PayPal PHP SDK
- Supports Orders API
- Test mode and live mode
- Webhook handling

## Security

- All user inputs are sanitized
- Nonce verification for AJAX requests
- SQL injection prevention with prepared statements
- XSS protection with escaping
- Capability checks for admin actions

## Performance

- Optimized database queries with proper indexing
- Availability pre-generation for 365 days
- Caching-ready architecture
- Lazy loading for room images

## Testing

The plugin includes:
- Unit tests structure (in `/tests/` directory)
- REST API test endpoints
- Admin interface tests

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Changelog

### 1.0.0 (2024-04-18)
- Initial release
- Room management system
- Availability calendar
- Pricing management
- Booking system
- Stripe integration
- PayPal integration
- Email notifications
- Admin dashboard
- REST API
- Frontend search and booking

## License

GPL-2.0+

## Support

For support, please open an issue on GitHub or contact the author.

## Credits

Developed by [Kaito2013](https://github.com/Kaito2013)

## Donate

If you find this plugin useful, please consider donating to support continued development.

---

**Note**: This is an MVP release. More features coming soon including:
- Multi-language support
- Advanced reporting
- Coupon codes
- Seasonal pricing
- Room add-ons
- Reviews and ratings
- Frontend shortcode/widgets
- WooCommerce integration
