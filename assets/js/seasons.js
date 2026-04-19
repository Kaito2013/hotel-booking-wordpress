/**
 * Hotel Booking Seasonal Pricing JavaScript
 */

(function($) {
	'use strict';

	var hotelBookingSeasons = {
		/**
		 * Initialize seasons.
		 */
		init: function() {
			this.bindEvents();
			this.initColorPicker();
			this.initDatepickers();
		},

		/**
		 * Bind events.
		 */
		bindEvents: function() {
			var self = this;

			// Add season
			$('#hb-add-season').on('click', function() {
				self.showSeasonForm();
			});

			// Cancel season
			$('#hb-cancel-season').on('click', function() {
				self.hideSeasonForm();
			});

			// Save season
			$('#hb-season-form-element').on('submit', function(e) {
				e.preventDefault();
				self.saveSeason();
			});

			// Edit season
			$(document).on('click', '.hb-edit-season', function() {
				var $row = $(this).closest('tr');
				self.editSeason($row);
			});

			// Delete season
			$(document).on('click', '.hb-delete-season', function() {
				var $row = $(this).closest('tr');
				self.deleteSeason($row);
			});

			// Edit rates
			$(document).on('click', '.hb-edit-rates', function() {
				var $row = $(this).closest('tr');
				self.showRatesModal($row);
			});

			// Save rates
			$('#hb-rates-form').on('submit', function(e) {
				e.preventDefault();
				self.saveRates();
			});

			// Close rates modal
			$('#hb-close-rates, .hb-modal-overlay').on('click', function() {
				self.hideRatesModal();
			});
		},

		/**
		 * Initialize color picker.
		 */
		initColorPicker: function() {
			if (typeof $.fn.wpColorPicker !== 'undefined') {
				$('.hb-color-picker').wpColorPicker();
			}
		},

		/**
		 * Initialize datepickers.
		 */
		initDatepickers: function() {
			$('#hb-season-start, #hb-season-end').datepicker({
				dateFormat: 'yy-mm-dd',
				changeMonth: true,
				changeYear: true
			});
		},

		/**
		 * Show season form.
		 */
		showSeasonForm: function() {
			$('#hb-season-form').slideDown();
			$('#hb-season-form-title').text('Add New Season');
			$('#hb-season-id').val('');
			$('#hb-season-form-element')[0].reset();
			$('#hb-season-color').wpColorPicker('color', '#2271b1');
		},

		/**
		 * Hide season form.
		 */
		hideSeasonForm: function() {
			$('#hb-season-form').slideUp();
		},

		/**
		 * Edit season.
		 */
		editSeason: function($row) {
			var seasonId = $row.data('id');
			var name = $row.find('.hb-season-name strong').text();
			var dates = $row.find('td:nth-child(2)').text().split('\n');
			var color = $row.find('.hb-season-name').css('border-left-color');

			// Convert RGB to HEX
			if (color && color.startsWith('rgb')) {
				color = this.rgbToHex(color);
			}

			$('#hb-season-id').val(seasonId);
			$('#hb-season-name').val(name.trim());
			$('#hb-season-start').val(this.formatDate(dates[0].trim()));
			$('#hb-season-end').val(this.formatDate(dates[1].trim()));
			$('#hb-season-color').wpColorPicker('color', color || '#2271b1');

			$('#hb-season-form-title').text('Edit Season');
			$('#hb-season-form').slideDown();
		},

		/**
		 * Save season.
		 */
		saveSeason: function() {
			var self = this;
			var $button = $('#hb-save-season');
			var formData = new FormData($('#hb-season-form-element')[0]);

			formData.append('action', 'hb_save_season');

			$button.prop('disabled', true).text(hbSeasons.strings.saving);

			$.ajax({
				url: hbSeasons.ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					$button.prop('disabled', false).text('Save Season');

					if (response.success) {
						alert(response.data.message);
						location.reload();
					} else {
						alert(response.data.message);
					}
				},
				error: function() {
					$button.prop('disabled', false).text('Save Season');
					alert('Failed to save season');
				}
			});
		},

		/**
		 * Delete season.
		 */
		deleteSeason: function($row) {
			if (!confirm(hbSeasons.strings.confirm)) {
				return;
			}

			var seasonId = $row.data('id');

			$.ajax({
				url: hbSeasons.ajaxUrl,
				type: 'POST',
				data: {
					action: 'hb_delete_season',
					nonce: hbSeasons.nonce,
					season_id: seasonId
				},
				success: function(response) {
					if (response.success) {
						$row.fadeOut(300, function() {
							$(this).remove();
						});
						alert(response.data.message);
					}
				}
			});
		},

		/**
		 * Show rates modal.
		 */
		showRatesModal: function($row) {
			var seasonId = $row.data('id');
			var seasonName = $row.find('.hb-season-name strong').text();

			$('#hb-rates-season-id').val(seasonId);
			$('.hb-modal-season-name').text(seasonName);

			// Load existing rates
			this.loadRates(seasonId);

			$('#hb-rates-modal').fadeIn();
		},

		/**
		 * Hide rates modal.
		 */
		hideRatesModal: function() {
			$('#hb-rates-modal').fadeOut();
		},

		/**
		 * Load rates for season.
		 */
		loadRates: function(seasonId) {
			// Rates are already loaded in the page
			// We just need to populate the form with existing data
		},

		/**
		 * Save rates.
		 */
		saveRates: function() {
			var self = this;
			var $button = $('#hb-save-rates');
			var formData = new FormData($('#hb-rates-form')[0]);

			formData.append('action', 'hb_save_seasonal_rate');

			$button.prop('disabled', true).text(hbSeasons.strings.saving);

			$.ajax({
				url: hbSeasons.ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					$button.prop('disabled', false).text('Save Rates');

					if (response.success) {
						alert(response.data.message);
						self.hideRatesModal();
						location.reload();
					} else {
						alert(response.data.message);
					}
				},
				error: function() {
					$button.prop('disabled', false).text('Save Rates');
					alert('Failed to save rates');
				}
			});
		},

		/**
		 * Format date for display.
		 */
		formatDate: function(dateStr) {
			// Try to parse various date formats
			var date = new Date(dateStr);
			if (isNaN(date.getTime())) {
				return dateStr;
			}
			return date.toISOString().split('T')[0];
		},

		/**
		 * Convert RGB to HEX.
		 */
		rgbToHex: function(rgb) {
			if (!rgb) return '#2271b1';

			var result = rgb.match(/\d+/g);
			if (!result || result.length < 3) return '#2271b1';

			return '#' + result.map(function(x) {
				var hex = parseInt(x).toString(16);
				return hex.length === 1 ? '0' + hex : hex;
			}).join('');
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		hotelBookingSeasons.init();
	});

	// Make available globally
	window.hotelBookingSeasons = hotelBookingSeasons;

})(jQuery);
