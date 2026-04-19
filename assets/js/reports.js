/**
 * Hotel Booking Admin Reports JavaScript
 */

(function($) {
	'use strict';

	var hotelBookingReports = {
		revenueChart: null,
		bookingsChart: null,
		period: '30days',

		/**
		 * Initialize reports.
		 */
		init: function() {
			this.period = $('#hb-report-period').val() || '30days';
			this.loadChartData();
			this.bindEvents();
		},

		/**
		 * Bind events.
		 */
		bindEvents: function() {
			var self = this;

			// Export CSV
			$('#hb-export-csv').on('click', function(e) {
				e.preventDefault();
				self.exportCSV();
			});

			// Period change
			$('select[name="period"]').on('change', function() {
				self.period = $(this).val();
			});
		},

		/**
		 * Load chart data.
		 */
		loadChartData: function() {
			var self = this;

			// Show loading
			$('#hb-revenue-chart, #hb-bookings-chart').css('opacity', '0.5');

			$.ajax({
				url: hbReports.ajaxUrl,
				type: 'POST',
				data: {
					action: 'hb_get_report_data',
					nonce: hbReports.nonce,
					period: this.period
				},
				success: function(response) {
					if (response.success) {
						self.renderRevenueChart(response.data.revenue);
						self.renderBookingsChart(response.data.bookings);
					}
				},
				error: function() {
					console.error('Failed to load report data');
				}
			});
		},

		/**
		 * Render revenue chart.
		 */
		renderRevenueChart: function(data) {
			var ctx = document.getElementById('hb-revenue-chart').getContext('2d');

			// Destroy existing chart
			if (this.revenueChart) {
				this.revenueChart.destroy();
			}

			this.revenueChart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: data.labels,
					datasets: [{
						label: 'Revenue',
						data: data.values,
						borderColor: '#2271b1',
						backgroundColor: 'rgba(34, 113, 177, 0.1)',
						borderWidth: 2,
						fill: true,
						tension: 0.4,
						pointBackgroundColor: '#2271b1',
						pointBorderColor: '#fff',
						pointBorderWidth: 2,
						pointRadius: 4,
						pointHoverRadius: 6
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							backgroundColor: 'rgba(0, 0, 0, 0.8)',
							titleColor: '#fff',
							bodyColor: '#fff',
							borderColor: 'rgba(255, 255, 255, 0.1)',
							borderWidth: 1,
							padding: 12,
							displayColors: false,
							callbacks: {
								label: function(context) {
									return hbReports.currency + context.parsed.y.toFixed(2);
								}
							}
						}
					},
					scales: {
						x: {
							grid: {
								display: false
							},
							ticks: {
								color: '#646970',
								maxTicksLimit: 10
							}
						},
						y: {
							grid: {
								color: 'rgba(0, 0, 0, 0.05)'
							},
							ticks: {
								color: '#646970',
								callback: function(value) {
									return hbReports.currency + value.toFixed(0);
								}
							}
						}
					},
					interaction: {
						intersect: false,
						mode: 'index'
					}
				}
			});

			$('#hb-revenue-chart').css('opacity', '1');
		},

		/**
		 * Render bookings chart.
		 */
		renderBookingsChart: function(data) {
			var ctx = document.getElementById('hb-bookings-chart').getContext('2d');

			// Destroy existing chart
			if (this.bookingsChart) {
				this.bookingsChart.destroy();
			}

			this.bookingsChart = new Chart(ctx, {
				type: 'bar',
				data: {
					labels: data.labels,
					datasets: [{
						label: 'Bookings',
						data: data.values,
						backgroundColor: 'rgba(34, 113, 177, 0.8)',
						borderColor: '#2271b1',
						borderWidth: 1,
						borderRadius: 4,
						borderSkipped: false
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							backgroundColor: 'rgba(0, 0, 0, 0.8)',
							titleColor: '#fff',
							bodyColor: '#fff',
							borderColor: 'rgba(255, 255, 255, 0.1)',
							borderWidth: 1,
							padding: 12,
							displayColors: false,
							callbacks: {
								label: function(context) {
									return context.parsed.y + ' bookings';
								}
							}
						}
					},
					scales: {
						x: {
							grid: {
								display: false
							},
							ticks: {
								color: '#646970',
								maxTicksLimit: 10
							}
						},
						y: {
							grid: {
								color: 'rgba(0, 0, 0, 0.05)'
							},
							ticks: {
								color: '#646970',
								stepSize: 1
							},
							beginAtZero: true
						}
					}
				}
			});

			$('#hb-bookings-chart').css('opacity', '1');
		},

		/**
		 * Export CSV.
		 */
		exportCSV: function() {
			var $button = $('#hb-export-csv');
			var originalText = $button.html();

			// Show loading
			$button.prop('disabled', true).html(
				'<span class="dashicons dashicons-update"></span> ' + hbReports.strings.loading
			);

			$.ajax({
				url: hbReports.ajaxUrl,
				type: 'POST',
				data: {
					action: 'hb_export_report',
					nonce: hbReports.nonce,
					period: this.period
				},
				success: function(response) {
					if (response.success) {
						// Trigger download
						window.location.href = response.data.url;

						// Show success message
						$button.html(
							'<span class="dashicons dashicons-yes"></span> ' + hbReports.strings.export
						);

						setTimeout(function() {
							$button.prop('disabled', false).html(originalText);
						}, 2000);
					} else {
						alert(response.data.message);
						$button.prop('disabled', false).html(originalText);
					}
				},
				error: function() {
					alert('Export failed');
					$button.prop('disabled', false).html(originalText);
				}
			});
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		if ($('.hotel-booking-reports').length) {
			hotelBookingReports.init();
		}
	});

	// Make available globally
	window.hotelBookingReports = hotelBookingReports;

})(jQuery);
