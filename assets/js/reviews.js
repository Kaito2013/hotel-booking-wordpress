/**
 * Hotel Booking Reviews JavaScript
 */

(function($) {
	'use strict';

	var hotelBookingReviews = {
		/**
		 * Initialize reviews.
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind events.
		 */
		bindEvents: function() {
			var self = this;

			// Star rating click
			$(document).on('click', '.hb-star-rating .star', function() {
				var rating = $(this).data('rating');
				$('#hb-rating').val(rating);
				self.updateStars(rating);
			});

			// Star rating hover
			$(document).on('mouseenter', '.hb-star-rating .star', function() {
				var rating = $(this).data('rating');
				self.highlightStars(rating);
			});

			$(document).on('mouseleave', '.hb-star-rating', function() {
				var currentRating = $('#hb-rating').val();
				self.updateStars(currentRating);
			});

			// Submit review form
			$(document).on('submit', '#hb-review-form', function(e) {
				e.preventDefault();
				self.submitReview();
			});

			// Approve review
			$(document).on('click', '.hb-approve-review', function() {
				var $item = $(this).closest('.hb-review-item');
				self.approveReview($item);
			});

			// Delete review
			$(document).on('click', '.hb-delete-review', function() {
				var $item = $(this).closest('.hb-review-item');
				self.deleteReview($item);
			});
		},

		/**
		 * Update stars display.
		 */
		updateStars: function(rating) {
			$('.hb-star-rating .star').each(function() {
				var starRating = $(this).data('rating');
				if (starRating <= rating) {
					$(this).addClass('active');
				} else {
					$(this).removeClass('active');
				}
			});
		},

		/**
		 * Highlight stars on hover.
		 */
		highlightStars: function(rating) {
			$('.hb-star-rating .star').each(function() {
				var starRating = $(this).data('rating');
				if (starRating <= rating) {
					$(this).css('color', '#dba617');
				} else {
					$(this).css('color', '#ddd');
				}
			});
		},

		/**
		 * Submit review.
		 */
		submitReview: function() {
			var self = this;
			var $form = $('#hb-review-form');
			var $button = $form.find('.hb-submit-review-btn');
			var formData = new FormData($form[0]);

			formData.append('action', 'hb_submit_review');

			// Show loading
			$button.prop('disabled', true).text('Submitting...');

			$.ajax({
				url: hbReviews.ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					$button.prop('disabled', false).text('Submit Review');

					if (response.success) {
						alert(response.data.message);
						$form[0].reset();
						self.updateStars(0);
					} else {
						alert(response.data.message);
					}
				},
				error: function() {
					$button.prop('disabled', false).text('Submit Review');
					alert('Failed to submit review');
				}
			});
		},

		/**
		 * Approve review.
		 */
		approveReview: function($item) {
			var reviewId = $item.data('id');

			$.ajax({
				url: hbReviews.ajaxUrl,
				type: 'POST',
				data: {
					action: 'hb_approve_review',
					nonce: hbReviews.nonce,
					review_id: reviewId
				},
				success: function(response) {
					if (response.success) {
						$item.find('.hb-approve-review').replaceWith(
							'<span class="hb-approved-label">' + hbReviews.strings.approve + '</span>'
						);
						alert(response.data.message);
					}
				}
			});
		},

		/**
		 * Delete review.
		 */
		deleteReview: function($item) {
			if (!confirm(hbReviews.strings.confirm)) {
				return;
			}

			var reviewId = $item.data('id');

			$.ajax({
				url: hbReviews.ajaxUrl,
				type: 'POST',
				data: {
					action: 'hb_delete_review',
					nonce: hbReviews.nonce,
					review_id: reviewId
				},
				success: function(response) {
					if (response.success) {
						$item.fadeOut(300, function() {
							$(this).remove();
						});
					}
				}
			});
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		hotelBookingReviews.init();
	});

	// Make available globally
	window.hotelBookingReviews = hotelBookingReviews;

})(jQuery);
