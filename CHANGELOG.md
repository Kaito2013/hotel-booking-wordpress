# Changelog

All notable changes to the Hotel Booking WordPress Plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Stripe SDK full integration
- PayPal SDK full integration
- Frontend room detail page
- Booking confirmation page
- My Bookings page for users
- Admin booking management (edit, cancel, refund)
- Advanced calendar with drag-and-drop
- Coupon/discount system
- Multi-language support (WPML integration)
- Reviews and ratings system
- Reporting and analytics
- Export bookings to CSV/PDF
- Email template customization
- SMS notifications
- Webhook support for third-party integrations

## [1.0.0] - 2024-04-18

### Added
- **Core Features**
  - Room Management with Custom Post Type (`hb_room`)
  - Availability Management with calendar tracking
  - Pricing Management with date-based rules
  - Booking System with status management
  - Payment Gateway integration (Stripe & PayPal skeleton)
  - Email Notification System
  - REST API for frontend integration

- **Admin Interface**
  - Dashboard with statistics (total bookings, pending, confirmed, revenue)
  - Calendar view for room availability
  - Settings page with tabs (General, Payment, Email)
  - Room metaboxes for details and pricing rules
  - Recent bookings table

- **Frontend Interface**
  - Search form with date picker, guest selector, room type filter
  - Room cards with images, details, pricing, amenities
  - Booking modal with guest details and payment method selection
  - Responsive design for mobile devices

- **Shortcodes**
  - `[hotel_booking]` - Complete booking interface
  - `[hotel_booking_search]` - Search form only
  - `[hotel_booking_rooms]` - Room list only

- **Database**
  - `wp_hb_availability` table for room availability tracking
  - `wp_hb_pricing` table for pricing rules
  - `wp_hb_bookings` table for booking data

- **Taxonomies**
  - `room_type` (hierarchical) - e.g., Standard, Deluxe, Suite
  - `room_amenity` (non-hierarchical) - e.g., WiFi, AC, TV

- **Security**
  - Input sanitization
  - Nonce verification
  - Prepared SQL statements
  - Capability checks
  - XSS protection

- **REST API Endpoints**
  - `GET /wp-json/hotel-booking/v1/rooms` - Get rooms with filters
  - `GET /wp-json/hotel-booking/v1/rooms/{id}` - Get room by ID
  - `GET /wp-json/hotel-booking/v1/availability` - Check availability
  - `GET /wp-json/hotel-booking/v1/pricing` - Calculate price
  - `POST /wp-json/hotel-booking/v1/bookings` - Create booking
  - `GET /wp-json/hotel-booking/v1/bookings/{id}` - Get booking
  - `GET /wp-json/hotel-booking/v1/settings` - Get public settings

### Changed
- Initial release

### Known Issues
- Payment gateways require SDK integration (Stripe PHP SDK, PayPal PHP SDK)
- Frontend room detail page not implemented
- User "My Bookings" page not implemented
- Email templates are not customizable

### Tested On
- WordPress 6.4+
- PHP 8.0+
- MySQL 5.7+

---

## Version History

| Version | Date | Notes |
|---------|------|-------|
| 1.0.0 | 2024-04-18 | Initial MVP release |

---

## Upgrade Instructions

### From 1.0.0 to 1.1.0 (Future)
```bash
# Backup your database
mysqldump -u username -p database_name > backup.sql

# Backup plugin files
cp -r wp-content/plugins/hotel-booking ~/hotel-booking-backup

# Update plugin via WordPress admin or replace files

# Deactivate and reactivate plugin to run any updates
```

---

## Migration Notes

### Database Changes
When upgrading, the plugin will automatically:
- Create new database tables if they don't exist
- Update table structures if needed
- Migrate data from old format to new format (if applicable)

### Settings Migration
Plugin settings are preserved during upgrades. No manual migration is required.

---

## Breaking Changes

### Upcoming Breaking Changes
- None planned for v1.1.x releases
- Major v2.0 may introduce breaking changes for better architecture

---

## Deprecations

### Deprecated Features
- None

### Removal Timeline
- Deprecated features will be supported for at least one minor version before removal

---

## Security Advisories

### Security Vulnerabilities
If you discover a security vulnerability, please email security@example.com instead of using the issue tracker.

All security vulnerabilities will be promptly addressed.

---

## Support

- **GitHub Issues**: https://github.com/Kaito2013/hotel-booking-wordpress/issues
- **Documentation**: https://github.com/Kaito2013/hotel-booking-wordpress/blob/main/README.md
- **WordPress Plugin Directory**: (Coming soon)

---

## Contributors

- [@Kaito2013](https://github.com/Kaito2013) - Initial development

---

## License

This plugin is licensed under GPL-2.0+. See [LICENSE](LICENSE) for more information.
