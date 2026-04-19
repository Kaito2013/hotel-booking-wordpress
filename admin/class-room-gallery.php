<?php
/**
 * Room Gallery Metabox Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Room Gallery Metabox Class
 */
class Hotel_Booking_Room_Gallery {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Room_Gallery
	 */
	private static $instance = null;

	/**
	 * Gallery meta key.
	 *
	 * @var string
	 */
	const META_KEY = '_hb_room_gallery';

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Room_Gallery
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_hb_room', array( $this, 'save_meta_box' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_hb_upload_gallery_image', array( $this, 'ajax_upload_gallery_image' ) );
		add_action( 'wp_ajax_hb_delete_gallery_image', array( $this, 'ajax_delete_gallery_image' ) );
		add_action( 'wp_ajax_hb_reorder_gallery', array( $this, 'ajax_reorder_gallery' ) );
		add_action( 'wp_ajax_hb_set_featured_image', array( $this, 'ajax_set_featured_image' ) );
	}

	/**
	 * Add meta box.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		add_meta_box(
			'hotel-booking-room-gallery',
			__( 'Room Gallery', 'hotel-booking' ),
			array( $this, 'render_meta_box' ),
			'hb_room',
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'hotel-booking-room-gallery', 'hb_room_gallery_nonce' );

		$gallery = get_post_meta( $post->ID, self::META_KEY, true );
		$gallery = is_array( $gallery ) ? $gallery : array();

		$featured_image = get_post_thumbnail_id( $post->ID );
		?>

		<div class="hb-room-gallery-container">
			<!-- Gallery Header -->
			<div class="hb-gallery-header">
				<p class="hb-gallery-description">
					<?php esc_html_e( 'Upload and manage room images. Drag to reorder. Click the star to set as featured image.', 'hotel-booking' ); ?>
				</p>
				<div class="hb-gallery-actions">
					<button type="button" class="button button-primary" id="hb-add-gallery-images">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'Add Images', 'hotel-booking' ); ?>
					</button>
					<button type="button" class="button" id="hb-gallery-clear-all" style="display: none;">
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Clear All', 'hotel-booking' ); ?>
					</button>
				</div>
			</div>

			<!-- Gallery Grid -->
			<div class="hb-gallery-grid" id="hb-gallery-grid">
				<?php if ( ! empty( $gallery ) ) : ?>
					<?php foreach ( $gallery as $index => $image_id ) : ?>
						<?php
						$image_url = wp_get_attachment_image_url( $image_id, 'medium' );
						$full_url  = wp_get_attachment_image_url( $image_id, 'full' );
						$is_featured = ( $featured_image == $image_id );
						?>
						<div class="hb-gallery-item <?php echo $is_featured ? 'featured' : ''; ?>" data-id="<?php echo esc_attr( $image_id ); ?>">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ); ?>">
							<div class="hb-gallery-overlay">
								<button type="button" class="hb-gallery-btn hb-gallery-featured" title="<?php esc_attr_e( 'Set as Featured', 'hotel-booking' ); ?>">
									<span class="dashicons dashicons-star-filled"></span>
								</button>
								<button type="button" class="hb-gallery-btn hb-gallery-view" title="<?php esc_attr_e( 'View Full Size', 'hotel-booking' ); ?>" data-full="<?php echo esc_url( $full_url ); ?>">
									<span class="dashicons dashicons-visibility"></span>
								</button>
								<button type="button" class="hb-gallery-btn hb-gallery-delete" title="<?php esc_attr_e( 'Delete', 'hotel-booking' ); ?>">
									<span class="dashicons dashicons-trash"></span>
								</button>
							</div>
							<?php if ( $is_featured ) : ?>
								<span class="hb-featured-badge"><?php esc_html_e( 'Featured', 'hotel-booking' ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="hb-gallery-empty">
						<span class="dashicons dashicons-format-gallery"></span>
						<p><?php esc_html_e( 'No images yet. Click "Add Images" to upload.', 'hotel-booking' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<!-- Hidden input for saving -->
			<input type="hidden" name="hb_room_gallery" id="hb_room_gallery" value="<?php echo esc_attr( json_encode( $gallery ) ); ?>">

			<!-- Image Counter -->
			<div class="hb-gallery-footer">
				<span class="hb-gallery-count">
					<?php
					$count = count( $gallery );
					echo esc_html( sprintf(
						_n( '%d image', '%d images', $count, 'hotel-booking' ),
						$count
					) );
					?>
				</span>
				<?php if ( ! empty( $gallery ) ) : ?>
					<button type="button" class="button" id="hb-gallery-clear-all">
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Clear All', 'hotel-booking' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>

		<?php
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'hb_room' !== $screen->post_type ) {
			return;
		}

		// jQuery UI Sortable
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Gallery CSS
		wp_enqueue_style(
			'hotel-booking-room-gallery',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/css/room-gallery.css',
			array(),
			HOTEL_BOOKING_VERSION
		);

		// Gallery JS
		wp_enqueue_script(
			'hotel-booking-room-gallery',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/js/room-gallery.js',
			array( 'jquery', 'jquery-ui-sortable', 'wp-backbone' ),
			HOTEL_BOOKING_VERSION,
			true
		);

		// Localize script
		wp_localize_script( 'hotel-booking-room-gallery', 'hbGallery', array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'hotel-booking-nonce' ),
			'postId'   => get_the_ID(),
			'strings'  => array(
				'confirmDelete'  => esc_html__( 'Are you sure you want to delete this image?', 'hotel-booking' ),
				'confirmClear'   => esc_html__( 'Are you sure you want to delete all images?', 'hotel-booking' ),
				'uploading'      => esc_html__( 'Uploading...', 'hotel-booking' ),
				'error'          => esc_html__( 'Error uploading image', 'hotel-booking' ),
				'success'        => esc_html__( 'Image uploaded successfully', 'hotel-booking' ),
				'deleting'       => esc_html__( 'Deleting...', 'hotel-booking' ),
				'noImages'       => esc_html__( 'No images yet. Click "Add Images" to upload.', 'hotel-booking' ),
				'viewFull'       => esc_html__( 'View Full Size', 'hotel-booking' ),
				'setFeatured'    => esc_html__( 'Set as Featured', 'hotel-booking' ),
				'delete'         => esc_html__( 'Delete', 'hotel-booking' ),
			),
		) );

		// Thickbox for image preview
		add_thickbox();
	}

	/**
	 * Save meta box.
	 *
	 * @param int      $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_meta_box( $post_id, $post ) {
		// Verify nonce
		if ( ! isset( $_POST['hb_room_gallery_nonce'] ) || ! wp_verify_nonce( $_POST['hb_room_gallery_nonce'], 'hotel-booking-room-gallery' ) ) {
			return;
		}

		// Check if autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save gallery
		if ( isset( $_POST['hb_room_gallery'] ) ) {
			$gallery = json_decode( stripslashes( $_POST['hb_room_gallery'] ), true );
			$gallery = is_array( $gallery ) ? array_map( 'absint', $gallery ) : array();

			update_post_meta( $post_id, self::META_KEY, $gallery );
		}
	}

	/**
	 * Upload gallery image via AJAX.
	 *
	 * @return void
	 */
	public function ajax_upload_gallery_image() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'hotel-booking' ) ) );
		}

		// Check if file was uploaded
		if ( ! isset( $_FILES['image'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No image uploaded', 'hotel-booking' ) ) );
		}

		// Upload file
		$upload = wp_handle_upload( $_FILES['image'], array( 'test_form' => false ) );

		if ( isset( $upload['error'] ) ) {
			wp_send_json_error( array( 'message' => $upload['error'] ) );
		}

		// Create attachment
		$attachment = array(
			'post_mime_type' => $upload['type'],
			'post_title'     => sanitize_file_name( $_FILES['image']['name'] ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );

		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		// Add to gallery
		$gallery = get_post_meta( $post_id, self::META_KEY, true );
		$gallery = is_array( $gallery ) ? $gallery : array();
		$gallery[] = $attachment_id;

		update_post_meta( $post_id, self::META_KEY, $gallery );

		// Return image data
		wp_send_json_success( array(
			'id'      => $attachment_id,
			'url'     => wp_get_attachment_image_url( $attachment_id, 'medium' ),
			'full'    => wp_get_attachment_image_url( $attachment_id, 'full' ),
			'gallery' => $gallery,
		) );
	}

	/**
	 * Delete gallery image via AJAX.
	 *
	 * @return void
	 */
	public function ajax_delete_gallery_image() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'delete_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$post_id      = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

		if ( ! $post_id || ! $attachment_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'hotel-booking' ) ) );
		}

		// Remove from gallery
		$gallery = get_post_meta( $post_id, self::META_KEY, true );
		$gallery = is_array( $gallery ) ? $gallery : array();
		$gallery = array_diff( $gallery, array( $attachment_id ) );

		update_post_meta( $post_id, self::META_KEY, array_values( $gallery ) );

		// Delete attachment
		wp_delete_attachment( $attachment_id, true );

		wp_send_json_success( array(
			'message' => __( 'Image deleted successfully', 'hotel-booking' ),
			'gallery' => array_values( $gallery ),
		) );
	}

	/**
	 * Reorder gallery via AJAX.
	 *
	 * @return void
	 */
	public function ajax_reorder_gallery() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$order   = isset( $_POST['order'] ) ? $_POST['order'] : array();

		if ( ! $post_id || empty( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'hotel-booking' ) ) );
		}

		// Sanitize order array
		$order = array_map( 'absint', $order );

		// Update gallery order
		update_post_meta( $post_id, self::META_KEY, $order );

		wp_send_json_success( array(
			'message' => __( 'Gallery reordered successfully', 'hotel-booking' ),
			'gallery' => $order,
		) );
	}

	/**
	 * Set featured image via AJAX.
	 *
	 * @return void
	 */
	public function ajax_set_featured_image() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'edit_post', absint( $_POST['post_id'] ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$post_id      = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

		if ( ! $post_id || ! $attachment_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'hotel-booking' ) ) );
		}

		// Set as featured image
		set_post_thumbnail( $post_id, $attachment_id );

		wp_send_json_success( array(
			'message'       => __( 'Featured image updated', 'hotel-booking' ),
			'attachment_id' => $attachment_id,
		) );
	}
}
