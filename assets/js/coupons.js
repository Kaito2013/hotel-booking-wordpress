/**
 * Hotel Booking Coupons JavaScript
 */

(function($) {
	'use strict';

	var hotelBookingCoupons = {
		/**
		 * Initialize coupons.
		 */
		init: function() {
			this.bindEvents();
			this.initSelect2();
		},

		/**
		 * Bind events.
		 */
		bindEvents: function() {
			var self = this;

			// Apply coupon
			$(document).on('click', '.hb-coupon-btn', function(e) {
				e.preventDefault();
				self.applyCoupon();
			});

			// Remove coupon
			$(document).on('click', '.hb-coupon-remove', function(e) {
				e.preventDefault();
				self.removeCoupon();
			});

			// Enter key on coupon input
			$(document).on('keypress', '.hb-coupon-input', function(e) {
				if (e.which === 13) {
					e.preventDefault();
					self.applyCoupon();
				}
			});
		},

		/**
		 * Initialize Select2.
		 */
		initSelect2: function() {
			if (typeof $.fn.select2 !== 'undefined') {
				$('#hb_room_ids').select2({
					placeholder: 'Select rooms...',
					allowClear: true
				});
			}
		},

		/**
		 * Apply coupon.
		 */
		applyCoupon: function() {
			var self = this;
			var couponCode = $('.hb-coupon-input').val().trim();
			var $button = $('.hb-coupon-btn');
			var $message = $('.hb-coupon-message');

			if (!couponCode) {
				self.showMessage('Please enter a coupon code', 'error');
				return;
			}

			// Show loading
			$button.prop('disabled', true).text(hbCoupons.strings.applying);

			// Get room ID and total price from page
			var roomId = $('#hb-booking-room-id').val() || 0;
			var totalPrice = $('#hb-booking-total-price').val() || 0;

			$.ajax({
				url: hbCoupons.ajaxUrl,
				type: 'POST',
				data: {
					action: 'hb_apply_coupon',
					nonce: hbCoupons.nonce,
					coupon_code: couponCode,
					room_id: roomId,
					total_price: totalPrice
				},
				success: function(response) {
					$button.prop('disabled', false).text('Apply Coupon');

					if (response.success) {
						// Show success
						self.showAppliedCoupon(response.data.coupon_code, response.data.discount);

						// Update total price
						if (typeof updateTotalPrice === 'function') {
							updateTotalPrice(response.data.total_price);
						}

						self.showMessage(response.data.message, 'success');
					} else {
						self.showMessage(response.data.message, 'error');
					}
				},
				error: function() {
					$button.prop('disabled', false).text('Apply Coupon');
					self.showMessage('Failed to apply coupon', 'error');
				}
			});
		},

		/**
		 * Remove coupon.
		 */
		removeCoupon: function() {
			var self = this;

			$.ajax({
				url: hbCoupons.ajaxUrl,
				type: 'POST',
				data: {
					action: 'hb_remove_coupon',
					nonce: hbCoupons.nonce
				},
				success: function(response) {
					if (response.success) {
						// Remove applied coupon UI
						$('.hb-coupon-applied').remove();
						$('.hb-coupon-form').show();

						// Restore original price
						if (typeof restoreOriginalPrice === 'function') {
							restoreOriginalPrice();
						}

						self.showMessage(response.data.message, 'success');
					}
				}
			});
		},

		/**
		 * Show applied coupon.
		 */
		showAppliedCoupon: function(code, discount) {
			var html = '<div class="hb-coupon-applied">';
			html += '<div class="hb-coupon-info">';
			html += '<span class="dashicons dashicons-yes-alt"></span>';
			html += '<span class="hb-coupon-code">' + code + '</span>';
			html += '<span class="hb-coupon-discount">-' + hbCoupons.currency + discount.toFixed(2) + '</span>';
			html += '</div>';
			html += '<button class="hb-coupon-remove">Remove</button>';
			html += '</div>';

			$('.hb-coupon-form').hide();
			$('.hb-coupon-form').after(html);
		},

		/**
		 * Show message.
		 */
		showMessage: function(message, type) {
			// Remove existing messages
			$('.hb-coupon-message').remove();

			var html = '<div class="hb-coupon-message ' + type + '">' + message + '</div>';
			$('.hb-coupon-form').after(html);

			// Auto-remove after 5 seconds
			setTimeout(function() {
				$('.hb-coupon-message').fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		hotelBookingCoupons.init();
	});

	// Make available globally
	window.hotelBookingCoupons = hotelBookingCoupons;

})(jQuery);
