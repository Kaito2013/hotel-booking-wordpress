/**
 * Hotel Booking Room Gallery JavaScript
 */

(function($) {
	'use strict';

	var hotelBookingGallery = {
		frame: null,
		gallery: [],

		/**
		 * Initialize gallery.
		 */
		init: function() {
			this.gallery = JSON.parse($('#hb_room_gallery').val()) || [];
			this.bindEvents();
			this.initSortable();
		},

		/**
		 * Bind events.
		 */
		bindEvents: function() {
			var self = this;

			// Add images button
			$('#hb-add-gallery-images').on('click', function(e) {
				e.preventDefault();
				self.openMediaUploader();
			});

			// Delete image
			$(document).on('click', '.hb-gallery-delete', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var item = $(this).closest('.hb-gallery-item');
				self.deleteImage(item);
			});

			// Set featured image
			$(document).on('click', '.hb-gallery-featured', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var item = $(this).closest('.hb-gallery-item');
				self.setFeaturedImage(item);
			});

			// View full size
			$(document).on('click', '.hb-gallery-view', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var fullUrl = $(this).data('full');
				window.open(fullUrl, '_blank');
			});

			// Clear all
			$(document).on('click', '#hb-gallery-clear-all', function(e) {
				e.preventDefault();
				if (confirm(hbGallery.strings.confirmClear)) {
					self.clearAll();
				}
			});
		},

		/**
		 * Initialize sortable.
		 */
		initSortable: function() {
			var self = this;

			$('#hb-gallery-grid').sortable({
				items: '.hb-gallery-item',
				placeholder: 'hb-gallery-item ui-sortable-placeholder',
				helper: 'clone',
				opacity: 0.8,
				cursor: 'move',
				tolerance: 'pointer',
				update: function(event, ui) {
					self.updateOrder();
				}
			}).disableSelection();
		},

		/**
		 * Open media uploader.
		 */
		openMediaUploader: function() {
			var self = this;

			// Create media frame if not exists
			if (this.frame) {
				this.frame.open();
				return;
			}

			this.frame = wp.media({
				title: 'Select Images for Gallery',
				button: {
					text: 'Add to Gallery'
				},
				multiple: true,
				library: {
					type: 'image'
				}
			});

			// On select
			this.frame.on('select', function() {
				var selection = self.frame.state().get('selection');
				var attachmentIds = [];

				selection.each(function(attachment) {
					attachmentIds.push(attachment.id);
				});

				self.uploadImages(attachmentIds);
			});

			this.frame.open();
		},

		/**
		 * Upload images.
		 */
		uploadImages: function(attachmentIds) {
			var self = this;
			var totalUploaded = 0;
			var totalToUpload = attachmentIds.length;

			// Show uploading state
			$('#hb-add-gallery-images').prop('disabled', true).html(
				'<span class="dashicons dashicons-update"></span> ' + hbGallery.strings.uploading
			);

			// Upload each image
			$.each(attachmentIds, function(index, attachmentId) {
				// Add to gallery immediately (WP already uploaded via media library)
				self.gallery.push(attachmentId);

				// Create item
				self.createGalleryItem(attachmentId);

				totalUploaded++;

				// Update counter
				if (totalUploaded === totalToUpload) {
					self.finishUpload();
				}
			});
		},

		/**
		 * Create gallery item.
		 */
		createGalleryItem: function(attachmentId) {
			var self = this;

			// Get image data via AJAX
			$.ajax({
				url: hbGallery.ajaxUrl,
				type: 'POST',
				data: {
					action: 'hb_upload_gallery_image',
					nonce: hbGallery.nonce,
					post_id: hbGallery.postId,
					attachment_id: attachmentId
				},
				success: function(response) {
					if (response.success) {
						// Remove empty state
						$('.hb-gallery-empty').remove();

						// Create item HTML
						var html = '<div class="hb-gallery-item" data-id="' + response.data.id + '">';
						html += '<img src="' + response.data.url + '" alt="">';
						html += '<div class="hb-gallery-overlay">';
						html += '<button type="button" class="hb-gallery-btn hb-gallery-featured" title="' + hbGallery.strings.setFeatured + '">';
						html += '<span class="dashicons dashicons-star-filled"></span>';
						html += '</button>';
						html += '<button type="button" class="hb-gallery-btn hb-gallery-view" title="' + hbGallery.strings.viewFull + '" data-full="' + response.data.full + '">';
						html += '<span class="dashicons dashicons-visibility"></span>';
						html += '</button>';
						html += '<button type="button" class="hb-gallery-btn hb-gallery-delete" title="' + hbGallery.strings.delete + '">';
						html += '<span class="dashicons dashicons-trash"></span>';
						html += '</button>';
						html += '</div>';
						html += '</div>';

						$('#hb-gallery-grid').append(html);

						// Update hidden input
						self.updateHiddenInput();

						// Show clear all button
						$('#hb-gallery-clear-all').show();
					}
				}
			});
		},

		/**
		 * Delete image.
		 */
		deleteImage: function(item) {
			var self = this;
			var attachmentId = item.data('id');

			if (!confirm(hbGallery.strings.confirmDelete)) {
				return;
			}

			// Show deleting state
			item.addClass('uploading');

			$.ajax({
				url: hbGallery.ajaxUrl,
				type: 'POST',
				data: {
					action: 'hb_delete_gallery_image',
					nonce: hbGallery.nonce,
					post_id: hbGallery.postId,
					attachment_id: attachmentId
				},
				success: function(response) {
					if (response.success) {
						// Remove item with animation
						item.fadeOut(300, function() {
							$(this).remove();

							// Update gallery array
							self.gallery = response.data.gallery;
							self.updateHiddenInput();
							self.updateCounter();

							// Show empty state if no images
							if (self.gallery.length === 0) {
								self.showEmptyState();
							}
						});
					}
				},
				error: function() {
					item.removeClass('uploading');
				}
			});
		},

		/**
		 * Set featured image.
		 */
		setFeaturedImage: function(item) {
			var self = this;
			var attachmentId = item.data('id');

			$.ajax({
				url: hbGallery.ajaxUrl,
				type: 'POST',
				data: {
					action: 'hb_set_featured_image',
					nonce: hbGallery.nonce,
					post_id: hbGallery.postId,
					attachment_id: attachmentId
				},
				success: function(response) {
					if (response.success) {
						// Remove featured class from all
						$('.hb-gallery-item').removeClass('featured');
						$('.hb-featured-badge').remove();

						// Add featured class to this item
						item.addClass('featured');
						item.append('<span class="hb-featured-badge">' + (hbGallery.strings.setFeatured ? 'Featured' : '') + '</span>');
					}
				}
			});
		},

		/**
		 * Update order after drag-and-drop.
		 */
		updateOrder: function() {
			var self = this;
			var order = [];

			$('#hb-gallery-grid .hb-gallery-item').each(function() {
				order.push($(this).data('id'));
			});

			$.ajax({
				url: hbGallery.ajaxUrl,
				type: 'POST',
				data: {
					action: 'hb_reorder_gallery',
					nonce: hbGallery.nonce,
					post_id: hbGallery.postId,
					order: order
				},
				success: function(response) {
					if (response.success) {
						self.gallery = response.data.gallery;
						self.updateHiddenInput();
					}
				}
			});
		},

		/**
		 * Clear all images.
		 */
		clearAll: function() {
			var self = this;

			// Delete all images
			$('.hb-gallery-item').each(function() {
				var attachmentId = $(this).data('id');

				$.ajax({
					url: hbGallery.ajaxUrl,
					type: 'POST',
					data: {
						action: 'hb_delete_gallery_image',
						nonce: hbGallery.nonce,
						post_id: hbGallery.postId,
						attachment_id: attachmentId
					}
				});
			});

			// Clear UI
			$('#hb-gallery-grid').empty();
			self.gallery = [];
			self.updateHiddenInput();
			self.showEmptyState();
			$('#hb-gallery-clear-all').hide();
		},

		/**
		 * Finish upload.
		 */
		finishUpload: function() {
			// Reset button
			$('#hb-add-gallery-images').prop('disabled', false).html(
				'<span class="dashicons dashicons-plus-alt2"></span> ' + (hbGallery.strings.success ? 'Add Images' : '')
			);

			// Update counter
			this.updateCounter();

			// Show clear all button
			if (this.gallery.length > 0) {
				$('#hb-gallery-clear-all').show();
			}
		},

		/**
		 * Update hidden input.
		 */
		updateHiddenInput: function() {
			$('#hb_room_gallery').val(JSON.stringify(this.gallery));
		},

		/**
		 * Update counter.
		 */
		updateCounter: function() {
			var count = this.gallery.length;
			var text = count + (count === 1 ? ' image' : ' images');
			$('.hb-gallery-count').text(text);
		},

		/**
		 * Show empty state.
		 */
		showEmptyState: function() {
			var html = '<div class="hb-gallery-empty">';
			html += '<span class="dashicons dashicons-format-gallery"></span>';
			html += '<p>' + hbGallery.strings.noImages + '</p>';
			html += '</div>';
			$('#hb-gallery-grid').html(html);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		if ($('#hb-gallery-grid').length) {
			hotelBookingGallery.init();
		}
	});

	// Make available globally
	window.hotelBookingGallery = hotelBookingGallery;

})(jQuery);
