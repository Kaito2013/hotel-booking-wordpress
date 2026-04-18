/**
 * Hotel Booking Payment JavaScript
 */

(function($) {
	'use strict';

	var hotelBookingPayment = {
		bookingId: 0,
		amount: 0,
		currency: 'USD',
		stripeKey: '',
		paypalClientId: '',
		stripe: null,
		elements: null,
		cardElement: null,

		/**
		 * Initialize payment.
		 */
		init: function() {
			// Get data from hidden inputs
			this.bookingId = $('#hb-booking-id').val();
			this.amount = parseFloat($('#hb-payment-amount').val());
			this.currency = $('#hb-currency').val();
			this.stripeKey = $('#hb-stripe-publishable-key').val();
			this.paypalClientId = $('#hb-paypal-client-id').val();

			// Initialize Stripe if key available
			if (this.stripeKey && typeof Stripe !== 'undefined') {
				this.initStripe();
			}

			// Initialize PayPal if client ID available
			if (this.paypalClientId && typeof paypal !== 'undefined') {
				this.initPayPal();
			}

			// Bind events
			this.bindEvents();
		},

		/**
		 * Initialize Stripe.
		 */
		initStripe: function() {
			this.stripe = Stripe(this.stripeKey);

			// Create Stripe Elements
			this.elements = this.stripe.elements({
				locale: 'auto'
			});

			// Create card element
			this.cardElement = this.elements.create('card', {
				style: {
					base: {
						fontSize: '16px',
						color: '#32325d',
						fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
						fontSmoothing: 'antialiased',
						'::placeholder': {
							color: '#aab7c4'
						}
					},
					invalid: {
						color: '#fa755a',
						iconColor: '#fa755a'
					}
				}
			});

			// Mount card element
			this.cardElement.mount('#card-element');

			// Handle card errors
			this.cardElement.on('change', function(event) {
				var displayError = document.getElementById('card-errors');
				if (event.error) {
					displayError.textContent = event.error.message;
				} else {
					displayError.textContent = '';
				}
			});
		},

		/**
		 * Initialize PayPal.
		 */
		initPayPal: function() {
			var self = this;

			paypal.Buttons({
				style: {
					layout: 'vertical',
					color:  'blue',
					shape:  'rect',
					label:  'pay'
				},
				createOrder: function(data, actions) {
					return $.ajax({
						url: hotelBooking.restUrl + 'payments/paypal/create-order',
						type: 'POST',
						headers: {
							'X-WP-Nonce': hotelBooking.nonce
						},
						data: JSON.stringify({
							booking_id: self.bookingId
						}),
						contentType: 'application/json',
						beforeSend: function() {
							self.showLoading();
						},
						success: function(response) {
							self.hideLoading();
							if (response.success) {
								return response.order_id;
							} else {
								throw new Error(response.message);
							}
						},
						error: function(xhr) {
							self.hideLoading();
							var errorMsg = 'Error creating PayPal order';
							if (xhr.responseJSON && xhr.responseJSON.message) {
								errorMsg = xhr.responseJSON.message;
							}
							throw new Error(errorMsg);
						}
					});
				},
				onApprove: function(data, actions) {
					return $.ajax({
						url: hotelBooking.restUrl + 'payments/paypal/capture-order',
						type: 'POST',
						headers: {
							'X-WP-Nonce': hotelBooking.nonce
						},
						data: JSON.stringify({
							booking_id: self.bookingId,
							order_id: data.orderID,
							payer_id: data.payerID
						}),
						contentType: 'application/json',
						beforeSend: function() {
							self.showLoading();
						},
						success: function(response) {
							self.hideLoading();
							if (response.success) {
								// Redirect to confirmation page
								window.location.href = response.redirect_url;
							} else {
								self.showError(response.message);
							}
						},
						error: function(xhr) {
							self.hideLoading();
							var errorMsg = 'Error capturing PayPal payment';
							if (xhr.responseJSON && xhr.responseJSON.message) {
								errorMsg = xhr.responseJSON.message;
							}
							self.showError(errorMsg);
						}
					});
				},
				onCancel: function(data) {
					// User cancelled payment
					self.showInfo('Payment cancelled');
				},
				onError: function(err) {
					self.showError('PayPal error: ' + err.message);
				}
			}).render('#hb-paypal-button-container');
		},

		/**
		 * Bind events.
		 */
		bindEvents: function() {
			var self = this;

			// Payment method selection
			$('.hb-payment-option').on('click', function() {
				$('.hb-payment-option').removeClass('selected');
				$(this).addClass('selected');

				var method = $(this).data('method');
				$('.hb-payment-form').removeClass('active');
				$('#hb-' + method + '-form').addClass('active');
			});

			// Stripe pay button
			$(document).on('click', '#hb-pay-stripe-btn', function(e) {
				e.preventDefault();
				self.processStripePayment();
			});

			// Close modal
			$('#hb-modal-close, #hb-modal-overlay').on('click', function() {
				self.closeModal();
			});
		},

		/**
		 * Process Stripe payment.
		 */
		processStripePayment: function() {
			var self = this;

			// Show loading
			this.showLoading();

			// Create payment intent
			$.ajax({
				url: hotelBooking.restUrl + 'payments/stripe/create-intent',
				type: 'POST',
				headers: {
					'X-WP-Nonce': hotelBooking.nonce
				},
				data: JSON.stringify({
					booking_id: this.bookingId
				}),
				contentType: 'application/json',
				success: function(response) {
					if (response.success) {
						self.confirmStripePayment(response.client_secret, response.payment_id);
					} else {
						self.hideLoading();
						self.showError(response.message);
					}
				},
				error: function(xhr) {
					self.hideLoading();
					var errorMsg = 'Error creating payment intent';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMsg = xhr.responseJSON.message;
					}
					self.showError(errorMsg);
				}
			});
		},

		/**
		 * Confirm Stripe payment.
		 */
		confirmStripePayment: function(clientSecret, paymentId) {
			var self = this;

			this.stripe.confirmCardPayment(clientSecret, {
				payment_method: {
					card: this.cardElement,
					billing_details: {
						name: 'Customer'
					}
				}
			}).then(function(result) {
				self.hideLoading();

				if (result.error) {
					self.showError(result.error.message);
				} else {
					// Payment succeeded
					self.completeStripePayment(paymentId);
				}
			});
		},

		/**
		 * Complete Stripe payment.
		 */
		completeStripePayment: function(paymentId) {
			var self = this;

			$.ajax({
				url: hotelBooking.restUrl + 'payments/stripe/confirm-payment',
				type: 'POST',
				headers: {
					'X-WP-Nonce': hotelBooking.nonce
				},
				data: JSON.stringify({
					booking_id: this.bookingId,
					payment_id: paymentId
				}),
				contentType: 'application/json',
				beforeSend: function() {
					self.showLoading();
				},
				success: function(response) {
					self.hideLoading();
					if (response.success) {
						window.location.href = response.redirect_url;
					} else {
						self.showError(response.message);
					}
				},
				error: function(xhr) {
					self.hideLoading();
					var errorMsg = 'Error confirming payment';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMsg = xhr.responseJSON.message;
					}
					self.showError(errorMsg);
				}
			});
		},

		/**
		 * Show loading state.
		 */
		showLoading: function() {
			$('#hb-loading-overlay').show();
		},

		/**
		 * Hide loading state.
		 */
		hideLoading: function() {
			$('#hb-loading-overlay').hide();
		},

		/**
		 * Show error message.
		 */
		showError: function(message) {
			this.showMessage(message, 'error');
		},

		/**
		 * Show info message.
		 */
		showInfo: function(message) {
			this.showMessage(message, 'info');
		},

		/**
		 * Show message.
		 */
		showMessage: function(message, type) {
			// Remove existing messages
			$('.hb-modal-body .hb-message').remove();

			var messageHtml = '<div class="hb-message ' + type + '">' + message + '</div>';
			$('.hb-modal-body').prepend(messageHtml);

			// Auto-remove after 5 seconds
			setTimeout(function() {
				$('.hb-modal-body .hb-message').fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
		},

		/**
		 * Close modal.
		 */
		closeModal: function() {
			$('.hb-payment-modal').removeClass('active');
			setTimeout(function() {
				$('.hb-payment-modal').hide();
			}, 300);
		},

		/**
		 * Open modal.
		 */
		openModal: function() {
			$('.hb-payment-modal').show();
			setTimeout(function() {
				$('.hb-payment-modal').addClass('active');
			}, 10);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		// Initialize if payment modal exists
		if ($('.hb-payment-modal').length) {
			hotelBookingPayment.init();
		}

		// Make available globally
		window.hotelBookingPayment = hotelBookingPayment;
	});

})(jQuery);
