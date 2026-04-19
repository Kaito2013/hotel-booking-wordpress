/**
 * Hotel Booking Export JavaScript
 */

(function($) {
	'use strict';

	var hotelBookingExport = {
		/**
		 * Initialize export.
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind events.
		 */
		bindEvents: function() {
			var self = this;

			// Export buttons
			$(document).on('click', '.hb-export-btn', function(e) {
				e.preventDefault();
				var action = $(this).data('action');
				self.exportData(action, $(this));
			});
		},

		/**
		 * Export data.
		 */
		exportData: function(action, $button) {
			var self = this;
			var originalText = $button.html();

			// Show loading
			$button.prop('disabled', true).html(
				'<span class="dashicons dashicons-update"></span> ' + hbExport.strings.exporting
			);

			$('#hb-export-status').show();

			// Get filters based on action
			var data = {
				action: action,
				nonce: hbExport.nonce
			};

			if (action === 'hb_export_bookings_csv' || action === 'hb_export_bookings_pdf') {
				data.period = $('#hb-booking-period').val();
				data.status = $('#hb-booking-status').val();
			} else if (action === 'hb_export_revenue_csv') {
				data.period = $('#hb-revenue-period').val();
			}

			$.ajax({
				url: hbExport.ajaxUrl,
				type: 'POST',
				data: data,
				success: function(response) {
					$button.prop('disabled', false).html(originalText);
					$('#hb-export-status').hide();

					if (response.success) {
						// Trigger download
						window.location.href = response.data.url;
						self.showSuccess(response.data.message);
					} else {
						self.showError(response.data.message);
					}
				},
				error: function() {
					$button.prop('disabled', false).html(originalText);
					$('#hb-export-status').hide();
					self.showError(hbExport.strings.error);
				}
			});
		},

		/**
		 * Show success message.
		 */
		showSuccess: function(message) {
			this.showMessage(message, 'success');
		},

		/**
		 * Show error message.
		 */
		showError: function(message) {
			this.showMessage(message, 'error');
		},

		/**
		 * Show message.
		 */
		showMessage: function(message, type) {
			var html = '<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>';
			$('.wrap.hotel-booking-export h1').after(html);

			setTimeout(function() {
				$('.notice.is-dismissible').fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		hotelBookingExport.init();
	});

	// Make available globally
	window.hotelBookingExport = hotelBookingExport;

})(jQuery);
