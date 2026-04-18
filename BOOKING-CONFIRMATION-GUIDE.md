# Booking Confirmation Feature Guide

## Overview

The Booking Confirmation page displays a thank you message with all booking details after a user successfully creates a booking.

## Accessing the Page

### 1. Automatic Redirect
After a user submits a booking form, they are automatically redirected to:
```
/booking-confirmation?booking_id={booking_id}
```

### 2. Manual URL
```
https://yourdomain.com/booking-confirmation?booking_id=123
```

### 3. Shortcode
```php
[hotel_booking_confirmation booking_id="123"]
```

## Page Sections

### 1. Success Icon & Message
- Animated green checkmark icon
- "Booking Confirmed!" title
- Booking number display
- Status badge (Pending, Confirmed, Cancelled, Completed)

### 2. Booking Details
Grid layout showing:
- Booking Number
- Room Name
- Check-in Date
- Check-out Date
- Number of Guests
- Guest Name
- Email Address
- Phone Number (if provided)
- Total Amount (highlighted)
- Payment Status
- Booking Date/Time
- Special Requests (if provided)

### 3. What's Next Section
Dynamic steps based on payment status:

**If Payment Pending:**
1. Confirmation Email
2. Complete Payment
3. Check-in
4. Enjoy Your Stay!

**If Payment Completed:**
1. Confirmation Email
2. Payment Confirmed
3. Check-in
4. Enjoy Your Stay!

### 4. Action Buttons
- **View My Bookings** - Links to `/my-bookings`
- **Back to Home** - Links to home page

### 5. Contact Information
Displays:
- Contact Email (with mailto: link)
- Contact Phone (with tel: link)

Configured in: Hotel Booking → Settings → General

## Customization

### Override Template

Create a file in your theme:
```
your-theme/hotel-booking/booking-confirmation.php
```

### Customize CSS

```css
/* Change success icon color */
.hb-confirmation-icon {
    background: linear-gradient(135deg, #your-color 0%, #your-darker-color 100%);
}

/* Change button colors */
.hb-button-primary {
    background: #your-color !important;
}

/* Adjust spacing */
.hb-confirmation-box {
    padding: 40px !important;
}
```

### Add Custom Steps

Edit `templates/booking-confirmation.php` and modify the "What's Next" section:

```php
<div class="hb-step">
    <div class="hb-step-number">5</div>
    <div class="hb-step-content">
        <h4>Your Custom Step</h4>
        <p>Your custom message here.</p>
    </div>
</div>
```

## Configuration

### Contact Information

Go to: **Hotel Booking → Settings → General**

- **Contact Email** - Email displayed on confirmation page (default: WordPress admin email)
- **Contact Phone** - Phone number displayed on confirmation page (optional)

### Check-in/Check-out Times

Configure in: **Hotel Booking → Settings → General**

- **Default Check-in Time** - Shown in "What's Next" section
- **Default Check-out Time** - For reference

## User Flow

```
1. User searches rooms
   ↓
2. User selects room and views details
   ↓
3. User fills booking form
   ↓
4. User submits booking
   ↓
5. AJAX request creates booking
   ↓
6. Redirect to /booking-confirmation?booking_id={id}
   ↓
7. Confirmation page displays:
   - Success message
   - Booking details
   - Next steps
   - Contact info
   ↓
8. User views bookings or returns home
```

## Technical Details

### Database Query
Page queries `wp_hb_bookings` table for booking details:
```php
$booking = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}hb_bookings WHERE id = %d",
        $booking_id
    )
);
```

### AJAX Redirect
JavaScript redirects after successful booking creation:
```javascript
success: function(response) {
    if (response.success) {
        var confirmationUrl = '/booking-confirmation?booking_id=' + response.booking_id;
        window.location.href = confirmationUrl;
    }
}
```

### Rewrite Rule
```php
add_rewrite_rule(
    '^booking-confirmation/?$',
    'index.php?hb_page=booking-confirmation',
    'top'
);
```

## Troubleshooting

### Page Shows 404

**Solution:** Flush rewrite rules
```bash
wp rewrite flush
# Or via WP Admin: Settings → Permalinks → Save Changes
```

### Booking Not Found

**Possible causes:**
1. Invalid booking_id in URL
2. Booking was deleted from database
3. Database connection issue

**Solution:** Check URL parameter and verify booking exists in database

### Contact Info Not Showing

**Possible causes:**
1. Contact email/phone not configured in settings
2. Settings not saved

**Solution:** Go to Hotel Booking → Settings → General and configure contact info

### Animations Not Working

**Possible causes:**
1. CSS file not loaded
2. Browser compatibility issue
3. JavaScript conflict

**Solution:** Check browser console for errors, ensure `frontend.css` is enqueued

## Security

- **Input Sanitization:** Booking ID is sanitized with `absint()`
- **Output Escaping:** All output is escaped with `esc_html()`
- **Access Control:** No special permissions needed (public page)
- **SQL Injection:** Uses prepared statements

## Browser Support

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- IE11: Animations not supported (graceful degradation)

## Mobile Responsiveness

### Desktop (> 960px)
- 2-column booking details grid
- Horizontal action buttons
- Horizontal contact info

### Tablet (601px - 960px)
- 1-column booking details grid
- Stacked action buttons
- Stacked contact info
- Reduced padding

### Mobile (≤ 600px)
- Single column layout
- Full-width buttons
- Stacked contact info
- Smaller fonts

## Performance

- **Page Load:** < 1 second (excluding images)
- **Animations:** CSS-based (GPU accelerated)
- **Database Queries:** 1 query per page
- **JavaScript:** Minimal (~2KB gzipped)

## SEO Considerations

- **Meta Tags:** Use WordPress SEO plugins to add meta tags
- **Canonical URL:** Not applicable (dynamic page)
- **Structured Data:** Can be added with plugins or custom code

## Analytics Integration

### Track Booking Confirmations

Add to your analytics plugin:

```javascript
// Google Analytics
window.dataLayer = window.dataLayer || [];
dataLayer.push({
    'event': 'booking_confirmation',
    'booking_id': '<?php echo $booking_id; ?>',
    'value': '<?php echo $booking->total_price; ?>'
});
```

### Track Button Clicks

```javascript
jQuery('.hb-button').on('click', function() {
    // Track button click
});
```

## Email Integration

The confirmation page displays that a confirmation email was sent. Ensure email settings are configured:

1. Go to: **Hotel Booking → Settings → Email**
2. Enable: **Confirmation Email**
3. Configure SMTP if needed (use plugin like WP Mail SMTP)

## Multi-language Support

The confirmation page uses WordPress translation functions:
- `esc_html_e()` for translatable text
- Text domain: `hotel-booking`

To translate:
1. Use a translation plugin (WPML, Polylang)
2. Or create `.mo`/`.po` files in `/languages/`

## Future Enhancements

Potential features for future versions:
- [ ] PDF receipt download
- [ ] Share booking on social media
- [ ] Add to calendar (Google, Outlook, iCal)
- [ ] Map integration (hotel location)
- [ ] Weather forecast for stay dates
- [ ] Local attractions/recommendations
- [ ] Pre-arrival questionnaire
- [ ] Upgrade room option

## Support

For issues and questions:
- GitHub Issues: https://github.com/Kaito2013/hotel-booking-wordpress/issues
- Documentation: See `/FRONTEND-GUIDE.md` and `/CONTRIBUTING.md`
