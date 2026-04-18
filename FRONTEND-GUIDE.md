# Hotel Booking WordPress Plugin - Frontend Features Guide

## Overview

This guide covers the frontend features added in v1.1.0:
- Room Detail Page
- My Bookings Page
- Custom URL Rewriting
- Booking Cancellation

---

## Room Detail Page

### Accessing the Page

There are multiple ways to access the room detail page:

#### 1. Custom URL (Recommended)
```
https://yourdomain.com/room/{room-slug}
```
Example: `https://yourdomain.com/room/deluxe-ocean-view`

#### 2. Shortcode
```php
[hotel_booking_room_detail room_id="123" check_in="2024-05-01" check_out="2024-05-03" guests="2"]
```

### Features

#### Gallery
- Large main image display
- Thumbnail gallery below
- Click thumbnail to switch main image
- Active thumbnail highlighted

#### Room Information
- Room title and type badges
- Capacity, size, and beds display
- Full description with formatting
- Amenities grid with icons

#### Booking Widget (Sidebar)
- Check-in/out date pickers
- Guest count selector
- Real-time price calculation
- Booking summary with nights and total
- "Proceed to Booking" button

#### Mini Calendar
- Displays current month availability
- Green = Available
- Red = Booked
- Gray = Past dates
- Clickable available dates

### Usage Example

```php
// Link from room list to detail page
<a href="/room/<?php echo $room->post_name; ?>?check_in=2024-05-01&check_out=2024-05-03&guests=2">
    View Details
</a>
```

---

## My Bookings Page

### Accessing the Page

#### 1. Custom URL
```
https://yourdomain.com/my-bookings
```

#### 2. Shortcode
```php
[hotel_booking_my_bookings]
```

**Note:** User must be logged in to view this page.

### Features

#### Booking List
- All bookings for current user
- Sorted by creation date (newest first)
- Booking cards with complete information

#### Booking Card Display
- Booking number and status badge
- Room name and image
- Guest name and email
- Check-in/out dates
- Number of guests
- Total price
- Payment status
- Booking date
- Special notes (if any)

#### Booking Actions
- **Cancel Booking** - For pending bookings only
- **View Details** - Coming soon
- **Download Receipt** - For completed payments (coming soon)

#### Status Badges
- **Pending** (Yellow) - Not yet confirmed
- **Confirmed** (Green) - Booking confirmed
- **Cancelled** (Red) - Booking cancelled
- **Completed** (Blue) - Stay completed

### Usage Example

```php
// Add to user account menu
<a href="/my-bookings">My Bookings</a>

// Or embed in a page
[hotel_booking_my_bookings]
```

---

## URL Rewriting

### Rewritten URLs

The plugin uses WordPress rewrite rules for clean URLs:

| Pattern | Example | Description |
|---------|---------|-------------|
| `/room/{slug}` | `/room/deluxe-suite` | Room detail page |
| `/my-bookings` | `/my-bookings` | User bookings page |

### Query Parameters

Room detail page accepts query parameters:
- `check_in` - Check-in date (Y-m-d format)
- `check_out` - Check-out date (Y-m-d format)
- `guests` - Number of guests

Example:
```
/room/deluxe-suite?check_in=2024-05-01&check_out=2024-05-03&guests=2
```

### Flush Rewrite Rules

After activating the plugin, flush rewrite rules:

**Option 1: Via WP Admin**
1. Go to Settings → Permalinks
2. Click "Save Changes"

**Option 2: Via WP-CLI**
```bash
wp rewrite flush
```

**Option 3: Programmatically**
```php
flush_rewrite_rules();
```

---

## Booking Cancellation

### How It Works

1. User clicks "Cancel Booking" on My Bookings page
2. Modal appears with reason field
3. User enters reason and confirms
4. System validates:
   - User owns the booking
   - Booking is not already cancelled
   - Booking is not completed
5. System:
   - Updates booking status to "cancelled"
   - Releases room availability
   - Sends cancellation email
6. Page reloads to show updated status

### Cancellation Email Template

The cancellation email includes:
- Booking number
- Room name
- Check-in/out dates
- Number of guests
- Total amount
- Refund note (if applicable)

### REST API Endpoint

**Endpoint:** `POST /wp-json/hotel-booking/v1/bookings/{id}/cancel`

**Headers:**
```
X-WP-Nonce: {your_nonce}
Content-Type: application/json
```

**Body:**
```json
{
  "reason": "Change of plans"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Booking cancelled successfully"
}
```

### Security Checks

- User must be logged in
- User must own the booking (or check email for guest bookings)
- Booking must be in "pending" or "confirmed" status
- Nonce verification required

---

## Shortcodes Reference

### [hotel_booking]
Complete booking interface (search + rooms list).

```php
[hotel_booking]
```

### [hotel_booking_search]
Search form only.

```php
[hotel_booking_search]
```

### [hotel_booking_rooms]
Room list only (requires search parameters).

```php
[hotel_booking_rooms]
```

### [hotel_booking_room_detail]
Room detail page with booking form.

```php
[hotel_booking_room_detail room_id="123" check_in="2024-05-01" check_out="2024-05-03" guests="2"]
```

**Parameters:**
- `room_id` (required) - Room post ID
- `check_in` (optional) - Check-in date
- `check_out` (optional) - Check-out date
- `guests` (optional, default: 1) - Number of guests

### [hotel_booking_my_bookings]
User's booking history.

```php
[hotel_booking_my_bookings]
```

---

## CSS Classes Reference

### Room Detail Page
- `.hb-room-detail` - Main container
- `.hb-room-detail-main` - Left column (gallery + info)
- `.hb-room-detail-sidebar` - Right column (booking widget)
- `.hb-room-gallery-section` - Gallery container
- `.hb-room-main-image` - Large image container
- `.hb-room-gallery` - Thumbnail grid
- `.hb-gallery-thumb` - Individual thumbnail
- `.hb-room-info` - Room information
- `.hb-room-title` - Room title (h1)
- `.hb-room-types` - Room type badges
- `.hb-room-meta-large` - Capacity/size/beds
- `.hb-room-description` - Description section
- `.hb-room-amenities-large` - Amenities grid
- `.hb-booking-widget` - Booking form container
- `.hb-price-per-night` - Price display
- `.hb-room-booking-form` - Booking form
- `.hb-booking-summary` - Summary box
- `.hb-summary-row` - Summary row
- `.hb-availability-mini` - Mini calendar container
- `.hb-mini-calendar-grid` - Calendar grid
- `.hb-mini-day` - Individual day
- `.hb-mini-day.available` - Available day
- `.hb-mini-day.unavailable` - Booked day
- `.hb-mini-day.past` - Past date

### My Bookings Page
- `.hb-my-bookings` - Main container
- `.hb-bookings-list` - Booking cards container
- `.hb-booking-card` - Individual booking card
- `.hb-booking-card-header` - Card header (number + status)
- `.hb-booking-number` - Booking number
- `.hb-booking-id` - Booking ID display
- `.hb-booking-card-body` - Card body (room + details)
- `.hb-booking-room` - Room info section
- `.hb-booking-room-image` - Room thumbnail
- `.hb-booking-details` - Booking details section
- `.hb-booking-detail-row` - Detail row
- `.hb-booking-card-footer` - Action buttons

### Status Badges
- `.hb-status` - Base status class
- `.hb-status-pending` - Yellow badge
- `.hb-status-confirmed` - Green badge
- `.hb-status-cancelled` - Red badge
- `.hb-status-completed` - Blue badge

### Payment Status
- `.hb-payment-status` - Base payment status class
- `.hb-payment-completed` - Green text
- `.hb-payment-pending` - Red text
- `.hb-payment-failed` - Red text

---

## JavaScript Functions Reference

### Room Detail Page
- `initRoomDetailPage()` - Initialize room detail page
- `initRoomGallery()` - Initialize gallery click handlers
- `initRoomBookingForm()` - Initialize booking form
- `updateRoomBookingSummary()` - Update price calculation
- `calculateNights(checkIn, checkOut)` - Calculate number of nights
- `initMiniCalendar()` - Initialize mini calendar
- `loadMiniCalendar(roomId, month)` - Load calendar data
- `renderMiniCalendar(data, month)` - Render calendar HTML

### My Bookings Page
- `initMyBookingsPage()` - Initialize my bookings page
- `cancelBooking(bookingId, reason)` - Cancel booking via AJAX

### Utility Functions
- `showMessage(message, type)` - Display alert message

---

## Common Issues

### Rewrite Rules Not Working

**Problem:** `/room/{slug}` returns 404

**Solution:**
```bash
# Flush rewrite rules
wp rewrite flush

# Or via WP Admin
# Settings → Permalinks → Save Changes
```

### Mini Calendar Not Loading

**Problem:** Calendar shows "Loading..." indefinitely

**Solution:**
1. Check browser console for errors
2. Verify REST API endpoint is accessible
3. Check `hotelBookingSettings.restUrl` is correct
4. Ensure room ID is valid

### Cancel Booking Not Working

**Problem:** Clicking "Cancel" does nothing

**Solution:**
1. Ensure user is logged in
2. Verify user owns the booking
3. Check booking status is "pending" or "confirmed"
4. Verify nonce is being sent
5. Check browser console for AJAX errors

### Gallery Not Switching Images

**Problem:** Clicking thumbnails doesn't change main image

**Solution:**
1. Check jQuery is loaded
2. Verify thumbnail images have `data-large` attribute
3. Ensure main image has ID `#hb-main-room-image`
4. Check for JavaScript errors

---

## Customization

### Override Templates

Create a template in your theme to override the plugin template:

```php
// room-detail.php
// Place in: your-theme/hotel-booking/room-detail.php

<?php
global $hb_room_data;
// Your custom template code
```

### Add Custom CSS

```css
/* In your theme's style.css or Customizer */

/* Customize booking button */
.hb-submit-btn {
    background: #your-color !important;
}

/* Customize status badges */
.hb-status-confirmed {
    background: #your-color !important;
    color: #your-text-color !important;
}
```

### Add Custom JavaScript

```javascript
// In your theme's main.js file

jQuery(document).ready(function($) {
    // Hook into booking form submission
    $('#hb-room-booking-form').on('submit', function(e) {
        // Your custom logic
    });
});
```

---

## Support

For issues and feature requests:
- GitHub Issues: https://github.com/Kaito2013/hotel-booking-wordpress/issues
- Documentation: https://github.com/Kaito2013/hotel-booking-wordpress/blob/main/README.md
- Developer Guide: https://github.com/Kaito2013/hotel-booking-wordpress/blob/main/CONTRIBUTING.md

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.
