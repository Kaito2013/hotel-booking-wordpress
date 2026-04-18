/**
 * Hotel Booking Frontend JavaScript
 */

(function($) {
	'use strict';

	// Plugin settings
	var hotelBooking = {
		ajaxUrl: '',
		nonce: '',
		restUrl: '',
		currency: '$',
		currencySymbol: '$'
	};

	// Initialize plugin
	function init() {
		// Get plugin settings from window if available
		if (window.hotelBookingSettings) {
			hotelBooking = window.hotelBookingSettings;
		}

		initSearchForm();
		initBookingModal();
		initBookingForm();
	}

	// Search Form
	function initSearchForm() {
		var $searchForm = $('.hb-search-form');

		if (!$searchForm.length) return;

		$searchForm.on('submit', function(e) {
			e.preventDefault();

			var checkIn = $(this).find('[name="check_in"]').val();
			var checkOut = $(this).find('[name="check_out"]').val();
			var guests = $(this).find('[name="guests"]').val();
			var roomType = $(this).find('[name="room_type"]').val();

			if (!checkIn || !checkOut) {
				showMessage('Please select check-in and check-out dates', 'error');
				return;
			}

			searchRooms(checkIn, checkOut, guests, roomType);
		});
	}

	function searchRooms(checkIn, checkOut, guests, roomType) {
		var $container = $('.hb-room-list');
		$container.html('<div class="hb-loading"><div class="spinner"></div>Searching rooms...</div>');

		var apiUrl = hotelBooking.restUrl + 'rooms?check_in=' + encodeURIComponent(checkIn) +
			'&check_out=' + encodeURIComponent(checkOut);

		if (guests) {
			apiUrl += '&guests=' + guests;
		}

		if (roomType) {
			apiUrl += '&room_type=' + encodeURIComponent(roomType);
		}

		$.get(apiUrl, function(response) {
			if (response.rooms && response.rooms.length > 0) {
				renderRooms(response.rooms);
			} else {
				$container.html('<div class="hb-message info">No rooms available for the selected dates.</div>');
			}
		}).fail(function() {
			$container.html('<div class="hb-message error">Error loading rooms. Please try again.</div>');
		});
	}

	function renderRooms(rooms) {
		var $container = $('.hb-room-list');
		var html = '';

		rooms.forEach(function(room) {
			var amenitiesHtml = '';
			if (room.amenities && room.amenities.length > 0) {
				amenitiesHtml = '<div class="hb-room-amenities">' +
					room.amenities.map(function(amenity) {
						return '<span>' + amenity + '</span>';
					}).join('') +
					'</div>';
			}

			html += '<div class="hb-room-card" data-room-id="' + room.id + '">';

			if (room.image) {
				html += '<img src="' + room.image + '" alt="' + room.title + '" class="hb-room-image">';
			}

			html += '<div class="hb-room-content">';
			html += '<h3 class="hb-room-title">' + room.title + '</h3>';

			if (room.description) {
				html += '<p class="hb-room-description">' + room.description + '</p>';
			}

			html += '<div class="hb-room-meta">';

			if (room.capacity) {
				html += '<div class="hb-room-meta-item">';
				html += '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
				html += room.capacity + ' guests';
				html += '</div>';
			}

			if (room.size) {
				html += '<div class="hb-room-meta-item">';
				html += '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 5h-3.17L15 3H9L7.17 5H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 14H4V7h4.05l1.83-2h4.24l1.83 2H20v12z"/></svg>';
				html += room.size;
				html += '</div>';
			}

			if (room.beds) {
				html += '<div class="hb-room-meta-item">';
				html += '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z"/></svg>';
				html += room.beds + ' bed' + (room.beds > 1 ? 's' : '');
				html += '</div>';
			}

			html += '</div>'; // End hb-room-meta

			if (room.price) {
				html += '<div class="hb-room-price">' + hotelBooking.currencySymbol + room.price + '<span>/night</span></div>';
			}

			html += amenitiesHtml;

			html += '<button class="hb-book-btn" data-room-id="' + room.id + '">Book Now</button>';
			html += '</div>'; // End hb-room-content
			html += '</div>'; // End hb-room-card
		});

		$container.html(html);

		// Initialize book buttons
		$('.hb-book-btn').on('click', function() {
			var roomId = $(this).data('room-id');
			openBookingModal(roomId);
		});
	}

	// Booking Modal
	function initBookingModal() {
		// Close modal on overlay click
		$(document).on('click', '.hb-modal-overlay', function(e) {
			if ($(e.target).hasClass('hb-modal-overlay')) {
				closeBookingModal();
			}
		});

		// Close modal on close button click
		$(document).on('click', '.hb-modal-close', function() {
			closeBookingModal();
		});
	}

	function openBookingModal(roomId) {
		// Get booking data from search form
		var checkIn = $('[name="check_in"]').val();
		var checkOut = $('[name="check_out"]').val();
		var guests = $('[name="guests"]').val() || 1;

		if (!checkIn || !checkOut) {
			showMessage('Please select check-in and check-out dates', 'error');
			return;
		}

		// Get room details
		var room = $('.hb-room-card[data-room-id="' + roomId + '"]');
		var roomTitle = room.find('.hb-room-title').text();
		var roomPrice = room.find('.hb-room-price').text().replace(/[^0-9.]/g, '');

		// Calculate total price
		var price = calculatePrice(roomId, checkIn, checkOut);

		// Create modal HTML
		var modalHtml = '<div class="hb-modal-overlay">';
		modalHtml += '<div class="hb-modal">';
		modalHtml += '<div class="hb-modal-header">';
		modalHtml += '<h2 class="hb-modal-title">Book ' + roomTitle + '</h2>';
		modalHtml += '<button class="hb-modal-close">&times;</button>';
		modalHtml += '</div>';

		modalHtml += '<form class="hb-booking-form">';
		modalHtml += '<input type="hidden" name="room_id" value="' + roomId + '">';
		modalHtml += '<input type="hidden" name="check_in" value="' + checkIn + '">';
		modalHtml += '<input type="hidden" name="check_out" value="' + checkOut + '">';
		modalHtml += '<input type="hidden" name="guests" value="' + guests + '">';

		modalHtml += '<div class="hb-booking-summary">';
		modalHtml += '<h3>Booking Summary</h3>';
		modalHtml += '<div class="hb-summary-row"><span>Room:</span><span>' + roomTitle + '</span></div>';
		modalHtml += '<div class="hb-summary-row"><span>Check-in:</span><span>' + checkIn + '</span></div>';
		modalHtml += '<div class="hb-summary-row"><span>Check-out:</span><span>' + checkOut + '</span></div>';
		modalHtml += '<div class="hb-summary-row"><span>Guests:</span><span>' + guests + '</span></div>';
		modalHtml += '<div class="hb-summary-row total"><span>Total:</span><span>' + hotelBooking.currencySymbol + price.toFixed(2) + '</span></div>';
		modalHtml += '</div>';

		modalHtml += '<div class="hb-form-group">';
		modalHtml += '<label for="first_name">First Name *</label>';
		modalHtml += '<input type="text" id="first_name" name="first_name" required>';
		modalHtml += '</div>';

		modalHtml += '<div class="hb-form-group">';
		modalHtml += '<label for="last_name">Last Name *</label>';
		modalHtml += '<input type="text" id="last_name" name="last_name" required>';
		modalHtml += '</div>';

		modalHtml += '<div class="hb-form-group">';
		modalHtml += '<label for="email">Email *</label>';
		modalHtml += '<input type="email" id="email" name="email" required>';
		modalHtml += '</div>';

		modalHtml += '<div class="hb-form-group">';
		modalHtml += '<label for="phone">Phone</label>';
		modalHtml += '<input type="tel" id="phone" name="phone">';
		modalHtml += '</div>';

		modalHtml += '<div class="hb-form-group">';
		modalHtml += '<label for="notes">Special Requests</label>';
		modalHtml += '<textarea id="notes" name="notes" rows="3"></textarea>';
		modalHtml += '</div>';

		modalHtml += '<div class="hb-payment-methods">';
		modalHtml += '<label style="display:block; margin-bottom:10px; font-weight:600;">Payment Method *</label>';
		modalHtml += '<div class="hb-payment-method" data-method="stripe">';
		modalHtml += '<input type="radio" name="payment_method" value="stripe" id="payment_stripe" required>';
		modalHtml += '<label for="payment_stripe" class="hb-payment-method-label">Credit Card</label>';
		modalHtml += '<span class="hb-payment-method-icon">💳</span>';
		modalHtml += '</div>';
		modalHtml += '<div class="hb-payment-method" data-method="paypal">';
		modalHtml += '<input type="radio" name="payment_method" value="paypal" id="payment_paypal">';
		modalHtml += '<label for="payment_paypal" class="hb-payment-method-label">PayPal</label>';
		modalHtml += '<span class="hb-payment-method-icon">🅿️</span>';
		modalHtml += '</div>';
		modalHtml += '</div>';

		modalHtml += '<button type="submit" class="hb-submit-btn">Complete Booking</button>';
		modalHtml += '</form>';
		modalHtml += '</div>';
		modalHtml += '</div>';

		// Append modal to body
		$('body').append(modalHtml);

		// Initialize payment method selection
		$('.hb-payment-method').on('click', function() {
			$('.hb-payment-method').removeClass('selected');
			$(this).addClass('selected');
			$(this).find('input').prop('checked', true);
		});

		// Prevent body scroll
		$('body').css('overflow', 'hidden');
	}

	function closeBookingModal() {
		$('.hb-modal-overlay').remove();
		$('body').css('overflow', '');
	}

	function calculatePrice(roomId, checkIn, checkOut) {
		// This should ideally call the API, but for now use a simple calculation
		var checkInDate = new Date(checkIn);
		var checkOutDate = new Date(checkOut);
		var nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));

		var room = $('.hb-room-card[data-room-id="' + roomId + '"]');
		var pricePerNight = parseFloat(room.find('.hb-room-price').text().replace(/[^0-9.]/g, ''));

		return pricePerNight * nights;
	}

	// Booking Form
	function initBookingForm() {
		$(document).on('submit', '.hb-booking-form', function(e) {
			e.preventDefault();

			var $form = $(this);
			var $button = $form.find('.hb-submit-btn');
			var data = $form.serialize();

			// Validate required fields
			var required = ['first_name', 'last_name', 'email', 'payment_method'];
			var isValid = true;

			required.forEach(function(field) {
				if (!$form.find('[name="' + field + '"]').val()) {
					isValid = false;
				}
			});

			if (!isValid) {
				showMessage('Please fill in all required fields', 'error');
				return;
			}

			// Disable button and show loading
			$button.prop('disabled', true).text('Processing...');

			// Create booking via API
			$.ajax({
				url: hotelBooking.restUrl + 'bookings',
				type: 'POST',
				headers: {
					'X-WP-Nonce': hotelBooking.nonce
				},
				data: JSON.stringify($form.serializeArray().reduce(function(obj, item) {
					obj[item.name] = item.value;
					return obj;
				}, {})),
				contentType: 'application/json',
				success: function(response) {
					if (response.success) {
						closeBookingModal();
						showMessage('Booking created successfully! You will receive a confirmation email.', 'success');
						// Refresh room list
						$('.hb-search-form').trigger('submit');
					} else {
						showMessage('Error creating booking: ' + (response.message || 'Unknown error'), 'error');
					}
				},
				error: function(xhr) {
					var errorMsg = 'Error creating booking';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMsg = xhr.responseJSON.message;
					}
					showMessage(errorMsg, 'error');
				},
				complete: function() {
					$button.prop('disabled', false).text('Complete Booking');
				}
			});
		});
	}

	// Show message
	function showMessage(message, type) {
		// Remove existing messages
		$('.hb-message').remove();

		var messageHtml = '<div class="hb-message ' + type + '">' + message + '</div>';

		if ($('.hb-room-list').length) {
			$('.hb-room-list').before(messageHtml);
		} else {
			$('.hb-booking-container').prepend(messageHtml);
		}

		// Auto-remove after 5 seconds
		setTimeout(function() {
			$('.hb-message').fadeOut(function() {
				$(this).remove();
			});
		}, 5000);
	}

	// Initialize on document ready
	$(document).ready(function() {
		init();
	});

})(jQuery);
