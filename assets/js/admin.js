/**
 * Hotel Booking Admin JavaScript
 */

(function($) {
	'use strict';

	// Settings Tabs
	function initSettingsTabs() {
		$('.nav-tab-wrapper .nav-tab').on('click', function(e) {
			e.preventDefault();

			// Remove active class from all tabs
			$('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');

			// Add active class to clicked tab
			$(this).addClass('nav-tab-active');

			// Hide all panels
			$('.hb-settings-panel').hide();

			// Show target panel
			var target = $(this).attr('href');
			$(target).show();
		});
	}

	// Settings Forms
	function initSettingsForms() {
		// General Settings
		$('#hb-general-settings-form').on('submit', function(e) {
			e.preventDefault();

			var data = {
				action: 'hb_save_settings',
				nonce: hotelBooking.nonce,
				section: 'general',
				data: $(this).serialize()
			};

			$.ajax({
				url: hotelBooking.ajaxUrl,
				type: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
					}
				}
			});
		});

		// Stripe Settings
		$('#hb-stripe-settings-form').on('submit', function(e) {
			e.preventDefault();

			var data = {
				action: 'hb_save_settings',
				nonce: hotelBooking.nonce,
				section: 'stripe',
				data: $(this).serialize()
			};

			$.ajax({
				url: hotelBooking.ajaxUrl,
				type: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
					}
				}
			});
		});

		// PayPal Settings
		$('#hb-paypal-settings-form').on('submit', function(e) {
			e.preventDefault();

			var data = {
				action: 'hb_save_settings',
				nonce: hotelBooking.nonce,
				section: 'paypal',
				data: $(this).serialize()
			};

			$.ajax({
				url: hotelBooking.ajaxUrl,
				type: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
					}
				}
			});
		});

		// Email Settings
		$('#hb-email-settings-form').on('submit', function(e) {
			e.preventDefault();

			var data = {
				action: 'hb_save_settings',
				nonce: hotelBooking.nonce,
				section: 'email',
				data: $(this).serialize()
			};

			$.ajax({
				url: hotelBooking.ajaxUrl,
				type: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
					}
				}
			});
		});
	}

	// Calendar
	function initCalendar() {
		if (!$('#hb-load-calendar').length) return;

		$('#hb-load-calendar').on('click', function() {
			var roomId = $('#hb-room-select').val();
			var month = $('#hb-month-select').val();

			if (!month) {
				alert('Please select a month');
				return;
			}

			loadCalendar(roomId, month);
		});

		// Auto-load calendar on page load
		$('#hb-load-calendar').trigger('click');
	}

	function loadCalendar(roomId, month) {
		var $container = $('#hb-calendar-container');
		$container.html('<div class="hb-calendar-loading"><span class="spinner is-active"></span>Loading calendar...</div>');

		var data = {
			action: 'hb_get_calendar_data',
			nonce: hotelBooking.nonce,
			room_id: roomId,
			month: month
		};

		$.ajax({
			url: hotelBooking.ajaxUrl,
			type: 'GET',
			data: data,
			success: function(response) {
				if (response.success) {
					renderCalendar(response.data, month);
				} else {
					$container.html('<p>Error loading calendar</p>');
				}
			}
		});
	}

	function renderCalendar(data, month) {
		var $container = $('#hb-calendar-container');
		var date = new Date(month + '-01');
		var year = date.getFullYear();
		var monthNum = date.getMonth();

		var firstDay = new Date(year, monthNum, 1);
		var lastDay = new Date(year, monthNum + 1, 0);

		var daysInMonth = lastDay.getDate();
		var startDay = firstDay.getDay();

		var dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

		var html = '<div class="hb-calendar-grid">';

		// Header
		for (var i = 0; i < 7; i++) {
			html += '<div class="hb-calendar-header">' + dayNames[i] + '</div>';
		}

		// Empty cells before first day
		for (var i = 0; i < startDay; i++) {
			html += '<div class="hb-calendar-day empty"></div>';
		}

		// Days
		for (var day = 1; day <= daysInMonth; day++) {
			var dateStr = year + '-' + String(monthNum + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
			var status = data[dateStr] ? data[dateStr].status : 'available';
			var statusClass = status === 'booked' ? 'booked' : 'available';

			html += '<div class="hb-calendar-day ' + statusClass + '">';
			html += '<span class="hb-calendar-day-number">' + day + '</span>';

			if (data[dateStr] && data[dateStr].booking_id) {
				html += '<span class="booking-count">1</span>';
			}

			html += '</div>';
		}

		html += '</div>';

		$container.html(html);
	}

	// Pricing Rules
	function initPricingRules() {
		if (!$('#hb-add-pricing-rule').length) return;

		$('#hb-add-pricing-rule').on('click', function() {
			var index = $('.hb-pricing-rule').length;
			var ruleHtml = getPricingRuleHtml(index);
			$('#hb-pricing-rules-container').append(ruleHtml);
		});

		$(document).on('click', '.hb-remove-rule', function() {
			$(this).closest('.hb-pricing-rule').remove();
		});

		$(document).on('change', 'select[name^="hb_pricing_rules"][name$="[type]"]', function() {
			var $row = $(this).closest('tr');
			var type = $(this).val();
			var $input = $row.find('input[name$="[price]"]');

			if (type === 'percent') {
				if (!$input.next('.percent-sign').length) {
					$input.after('<span class="percent-sign">%</span>');
				}
			} else {
				$input.next('.percent-sign').remove();
			}
		});
	}

	function getPricingRuleHtml(index) {
		return `
			<div class="hb-pricing-rule" data-index="${index}">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label>Date Range</label>
						</th>
						<td>
							<input type="date" name="hb_pricing_rules[${index}][start_date]" value="" class="medium-text">
							<span>to</span>
							<input type="date" name="hb_pricing_rules[${index}][end_date]" value="" class="medium-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label>Rule Type</label>
						</th>
						<td>
							<select name="hb_pricing_rules[${index}][type]" class="medium-text">
								<option value="fixed">Fixed Price</option>
								<option value="percent">Percentage Adjustment</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label>Value</label>
						</th>
						<td>
							<input type="number" name="hb_pricing_rules[${index}][price]" value="" step="0.01" class="small-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label>Actions</label>
						</th>
						<td>
							<button type="button" class="button hb-remove-rule">
								Remove Rule
							</button>
						</td>
					</tr>
				</table>
			</div>
		`;
	}

	// Dashboard Stats Refresh
	function initDashboardRefresh() {
		if (!$('.hb-dashboard-stats').length) return;

		// Refresh stats every 60 seconds
		setInterval(function() {
			refreshDashboardStats();
		}, 60000);
	}

	function refreshDashboardStats() {
		$.ajax({
			url: hotelBooking.ajaxUrl,
			type: 'GET',
			data: {
				action: 'hb_get_dashboard_stats',
				nonce: hotelBooking.nonce
			},
			success: function(response) {
				if (response.success) {
					updateDashboardStats(response.data);
				}
			}
		});
	}

	function updateDashboardStats(stats) {
		var $cards = $('.hb-stat-card');
		$cards.each(function() {
			var $content = $(this).find('.hb-stat-content h3');
			var $label = $(this).find('.hb-stat-content p');

			if ($label.text().includes('Total Bookings')) {
				$content.text(stats.total_bookings);
			} else if ($label.text().includes('Pending Bookings')) {
				$content.text(stats.pending_bookings);
			} else if ($label.text().includes('Confirmed Bookings')) {
				$content.text(stats.confirmed_bookings);
			} else if ($label.text().includes('Total Revenue')) {
				$content.text('$' + stats.total_revenue.toFixed(2));
			} else if ($label.text().includes('This Month')) {
				$content.text('$' + stats.this_month_revenue.toFixed(2));
			}
		});
	}

	// Initialize on document ready
	$(document).ready(function() {
		initSettingsTabs();
		initSettingsForms();
		initCalendar();
		initPricingRules();
		initDashboardRefresh();
	});

})(jQuery);
