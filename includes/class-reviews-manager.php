<?php
/**
 * Reviews Manager Class
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reviews Manager Class
 */
class Hotel_Booking_Reviews_Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var Hotel_Booking_Reviews_Manager
	 */
	private static $instance = null;

	/**
	 * Reviews table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Get instance of the class.
	 *
	 * @return Hotel_Booking_Reviews_Manager
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
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'hb_reviews';

		add_action( 'init', array( $this, 'create_table' ) );
		add_action( 'wp_ajax_hb_submit_review', array( $this, 'ajax_submit_review' ) );
		add_action( 'wp_ajax_nopriv_hb_submit_review', array( $this, 'ajax_submit_review' ) );
		add_action( 'wp_ajax_hb_approve_review', array( $this, 'ajax_approve_review' ) );
		add_action( 'wp_ajax_hb_delete_review', array( $this, 'ajax_delete_review' ) );
		add_action( 'admin_menu', array( $this, 'add_reviews_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_shortcode( 'hotel_booking_reviews', array( $this, 'render_reviews_shortcode' ) );
		add_shortcode( 'hotel_booking_review_form', array( $this, 'render_review_form_shortcode' ) );
	}

	/**
	 * Create reviews table.
	 *
	 * @return void
	 */
	public function create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			room_id BIGINT(20) UNSIGNED NOT NULL,
			booking_id BIGINT(20) UNSIGNED DEFAULT NULL,
			user_id BIGINT(20) UNSIGNED DEFAULT NULL,
			guest_name VARCHAR(100) NOT NULL,
			guest_email VARCHAR(100) NOT NULL,
			rating TINYINT(1) NOT NULL,
			title VARCHAR(255) DEFAULT NULL,
			content TEXT NOT NULL,
			is_approved TINYINT(1) NOT NULL DEFAULT 0,
			is_verified TINYINT(1) NOT NULL DEFAULT 0,
			helpful_count INT(11) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY room_id (room_id),
			KEY user_id (user_id),
			KEY is_approved (is_approved),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Add reviews menu.
	 *
	 * @return void
	 */
	public function add_reviews_menu() {
		add_submenu_page(
			'hotel-booking',
			__( 'Reviews', 'hotel-booking' ),
			__( 'Reviews', 'hotel-booking' ),
			'manage_options',
			'hotel-booking-reviews',
			array( $this, 'render_reviews_page' )
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'hotel-booking_page_hotel-booking-reviews' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'hotel-booking-reviews',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/css/reviews.css',
			array(),
			HOTEL_BOOKING_VERSION
		);

		wp_enqueue_script(
			'hotel-booking-reviews',
			HOTEL_BOOKING_PLUGIN_URL . 'assets/js/reviews.js',
			array( 'jquery' ),
			HOTEL_BOOKING_VERSION,
			true
		);

		wp_localize_script( 'hotel-booking-reviews', 'hbReviews', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'hotel-booking-nonce' ),
			'strings' => array(
				'approve' => esc_html__( 'Approve', 'hotel-booking' ),
				'delete'  => esc_html__( 'Delete', 'hotel-booking' ),
				'confirm' => esc_html__( 'Are you sure?', 'hotel-booking' ),
			),
		) );
	}

	/**
	 * Render reviews page.
	 *
	 * @return void
	 */
	public function render_reviews_page() {
		global $wpdb;

		$status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';

		$where = '';
		if ( 'pending' === $status ) {
			$where = 'WHERE r.is_approved = 0';
		} elseif ( 'approved' === $status ) {
			$where = 'WHERE r.is_approved = 1';
		}

		$reviews = $wpdb->get_results(
			"SELECT r.*, p.post_title as room_name
			FROM {$this->table_name} r
			LEFT JOIN {$wpdb->posts} p ON r.room_id = p.ID
			{$where}
			ORDER BY r.created_at DESC"
		);

		$pending_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE is_approved = 0"
		);

		$approved_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE is_approved = 1"
		);

		$avg_rating = $wpdb->get_var(
			"SELECT AVG(rating) FROM {$this->table_name} WHERE is_approved = 1"
		);

		?>

		<div class="wrap hotel-booking-reviews">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Reviews', 'hotel-booking' ); ?>
			</h1>

			<!-- Stats -->
			<div class="hb-reviews-stats">
				<div class="hb-stat-card">
					<span class="dashicons dashicons-star-filled"></span>
					<div class="hb-stat-content">
						<h3><?php echo esc_html( number_format( $avg_rating, 1 ) ); ?></h3>
						<p><?php esc_html_e( 'Average Rating', 'hotel-booking' ); ?></p>
					</div>
				</div>
				<div class="hb-stat-card">
					<span class="dashicons dashicons-yes-alt"></span>
					<div class="hb-stat-content">
						<h3><?php echo esc_html( $approved_count ); ?></h3>
						<p><?php esc_html_e( 'Approved', 'hotel-booking' ); ?></p>
					</div>
				</div>
				<div class="hb-stat-card">
					<span class="dashicons dashicons-clock"></span>
					<div class="hb-stat-content">
						<h3><?php echo esc_html( $pending_count ); ?></h3>
						<p><?php esc_html_e( 'Pending', 'hotel-booking' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Filters -->
			<div class="hb-reviews-filters">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-booking-reviews' ) ); ?>" class="button <?php echo 'all' === $status ? 'button-primary' : ''; ?>">
					<?php esc_html_e( 'All', 'hotel-booking' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-booking-reviews&status=pending' ) ); ?>" class="button <?php echo 'pending' === $status ? 'button-primary' : ''; ?>">
					<?php echo esc_html( sprintf( __( 'Pending (%d)', 'hotel-booking' ), $pending_count ) ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-booking-reviews&status=approved' ) ); ?>" class="button <?php echo 'approved' === $status ? 'button-primary' : ''; ?>">
					<?php echo esc_html( sprintf( __( 'Approved (%d)', 'hotel-booking' ), $approved_count ) ); ?>
				</a>
			</div>

			<!-- Reviews List -->
			<div class="hb-reviews-list">
				<?php if ( empty( $reviews ) ) : ?>
					<div class="hb-no-reviews">
						<span class="dashicons dashicons-format-chat"></span>
						<p><?php esc_html_e( 'No reviews found', 'hotel-booking' ); ?></p>
					</div>
				<?php else : ?>
					<?php foreach ( $reviews as $review ) : ?>
						<div class="hb-review-item" data-id="<?php echo esc_attr( $review->id ); ?>">
							<div class="hb-review-header">
								<div class="hb-review-meta">
									<strong class="hb-review-author"><?php echo esc_html( $review->guest_name ); ?></strong>
									<span class="hb-review-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ) ); ?></span>
									<span class="hb-review-room">
										<?php echo esc_html( $review->room_name ? $review->room_name : __( 'Unknown Room', 'hotel-booking' ) ); ?>
									</span>
									<?php if ( $review->is_verified ) : ?>
										<span class="hb-verified-badge"><?php esc_html_e( 'Verified Guest', 'hotel-booking' ); ?></span>
									<?php endif; ?>
								</div>
								<div class="hb-review-rating">
									<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
										<span class="dashicons dashicons-star-filled <?php echo $i <= $review->rating ? 'filled' : ''; ?>"></span>
									<?php endfor; ?>
								</div>
							</div>
							<div class="hb-review-content">
								<?php if ( $review->title ) : ?>
									<h4><?php echo esc_html( $review->title ); ?></h4>
								<?php endif; ?>
								<p><?php echo esc_html( $review->content ); ?></p>
							</div>
							<div class="hb-review-actions">
								<?php if ( ! $review->is_approved ) : ?>
									<button class="button button-primary hb-approve-review">
										<span class="dashicons dashicons-yes-alt"></span>
										<?php esc_html_e( 'Approve', 'hotel-booking' ); ?>
									</button>
								<?php else : ?>
									<span class="hb-approved-label"><?php esc_html_e( 'Approved', 'hotel-booking' ); ?></span>
								<?php endif; ?>
								<button class="button hb-delete-review">
									<span class="dashicons dashicons-trash"></span>
									<?php esc_html_e( 'Delete', 'hotel-booking' ); ?>
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<?php
	}

	/**
	 * Submit review via AJAX.
	 *
	 * @return void
	 */
	public function ajax_submit_review() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		$room_id    = isset( $_POST['room_id'] ) ? absint( $_POST['room_id'] ) : 0;
		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		$rating     = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;
		$title      = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
		$content    = isset( $_POST['content'] ) ? sanitize_textarea_field( $_POST['content'] ) : '';
		$name       = isset( $_POST['guest_name'] ) ? sanitize_text_field( $_POST['guest_name'] ) : '';
		$email      = isset( $_POST['guest_email'] ) ? sanitize_email( $_POST['guest_email'] ) : '';

		// Validation
		if ( ! $room_id || ! $rating || empty( $content ) || empty( $name ) || empty( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please fill in all required fields', 'hotel-booking' ) ) );
		}

		if ( $rating < 1 || $rating > 5 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid rating', 'hotel-booking' ) ) );
		}

		// Check if verified guest
		$is_verified = false;
		if ( $booking_id ) {
			$booking = Hotel_Booking_Booking_Manager::get_instance()->get_booking( $booking_id );
			if ( $booking && $booking->email === $email ) {
				$is_verified = true;
			}
		}

		global $wpdb;

		$wpdb->insert(
			$this->table_name,
			array(
				'room_id'      => $room_id,
				'booking_id'   => $booking_id,
				'user_id'      => get_current_user_id() ?: null,
				'guest_name'   => $name,
				'guest_email'  => $email,
				'rating'       => $rating,
				'title'        => $title,
				'content'      => $content,
				'is_approved'  => 0,
				'is_verified'  => $is_verified ? 1 : 0,
			)
		);

		wp_send_json_success( array(
			'message' => __( 'Review submitted successfully! It will be visible after approval.', 'hotel-booking' ),
		) );
	}

	/**
	 * Approve review via AJAX.
	 *
	 * @return void
	 */
	public function ajax_approve_review() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$review_id = isset( $_POST['review_id'] ) ? absint( $_POST['review_id'] ) : 0;

		if ( ! $review_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid review ID', 'hotel-booking' ) ) );
		}

		global $wpdb;

		$wpdb->update(
			$this->table_name,
			array( 'is_approved' => 1 ),
			array( 'id' => $review_id )
		);

		wp_send_json_success( array(
			'message' => __( 'Review approved', 'hotel-booking' ),
		) );
	}

	/**
	 * Delete review via AJAX.
	 *
	 * @return void
	 */
	public function ajax_delete_review() {
		check_ajax_referer( 'hotel-booking-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'hotel-booking' ) ) );
		}

		$review_id = isset( $_POST['review_id'] ) ? absint( $_POST['review_id'] ) : 0;

		if ( ! $review_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid review ID', 'hotel-booking' ) ) );
		}

		global $wpdb;

		$wpdb->delete( $this->table_name, array( 'id' => $review_id ) );

		wp_send_json_success( array(
			'message' => __( 'Review deleted', 'hotel-booking' ),
		) );
	}

	/**
	 * Get reviews for a room.
	 *
	 * @param int $room_id Room ID.
	 * @param int $limit  Number of reviews.
	 * @return array
	 */
	public function get_room_reviews( $room_id, $limit = 10 ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE room_id = %d AND is_approved = 1 ORDER BY created_at DESC LIMIT %d",
			$room_id,
			$limit
		) );
	}

	/**
	 * Get room average rating.
	 *
	 * @param int $room_id Room ID.
	 * @return float
	 */
	public function get_room_avg_rating( $room_id ) {
		global $wpdb;

		$avg = $wpdb->get_var( $wpdb->prepare(
			"SELECT AVG(rating) FROM {$this->table_name} WHERE room_id = %d AND is_approved = 1",
			$room_id
		) );

		return $avg ? round( $avg, 1 ) : 0;
	}

	/**
	 * Get room review count.
	 *
	 * @param int $room_id Room ID.
	 * @return int
	 */
	public function get_room_review_count( $room_id ) {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE room_id = %d AND is_approved = 1",
			$room_id
		) );
	}

	/**
	 * Render reviews shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_reviews_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'room_id' => 0,
			'limit'   => 10,
		), $atts, 'hotel_booking_reviews' );

		$room_id = absint( $atts['room_id'] );
		$limit   = absint( $atts['limit'] );

		if ( ! $room_id ) {
			$room_id = get_the_ID();
		}

		$reviews = $this->get_room_reviews( $room_id, $limit );
		$avg_rating = $this->get_room_avg_rating( $room_id );
		$count = $this->get_room_review_count( $room_id );

		ob_start();

		if ( empty( $reviews ) ) {
			echo '<div class="hb-no-reviews">';
			echo '<p>' . esc_html__( 'No reviews yet. Be the first to review!', 'hotel-booking' ) . '</p>';
			echo '</div>';
		} else {
			echo '<div class="hb-reviews-container">';
			echo '<div class="hb-reviews-summary">';
			echo '<div class="hb-avg-rating">' . esc_html( $avg_rating ) . '</div>';
			echo '<div class="hb-stars">';
			for ( $i = 1; $i <= 5; $i++ ) {
				echo '<span class="dashicons dashicons-star-filled ' . ( $i <= $avg_rating ? 'filled' : '' ) . '"></span>';
			}
			echo '</div>';
			echo '<div class="hb-review-count">' . esc_html( sprintf( _n( '%d review', '%d reviews', $count, 'hotel-booking' ), $count ) ) . '</div>';
			echo '</div>';

			foreach ( $reviews as $review ) {
				echo '<div class="hb-review-card">';
				echo '<div class="hb-review-header">';
				echo '<strong>' . esc_html( $review->guest_name ) . '</strong>';
				echo '<span class="hb-review-date">' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ) ) . '</span>';
				if ( $review->is_verified ) {
					echo '<span class="hb-verified-badge">' . esc_html__( 'Verified Guest', 'hotel-booking' ) . '</span>';
				}
				echo '</div>';
				echo '<div class="hb-review-rating">';
				for ( $i = 1; $i <= 5; $i++ ) {
					echo '<span class="dashicons dashicons-star-filled ' . ( $i <= $review->rating ? 'filled' : '' ) . '"></span>';
				}
				echo '</div>';
				if ( $review->title ) {
					echo '<h4>' . esc_html( $review->title ) . '</h4>';
				}
				echo '<p>' . esc_html( $review->content ) . '</p>';
				echo '</div>';
			}
			echo '</div>';
		}

		return ob_get_clean();
	}

	/**
	 * Render review form shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_review_form_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'room_id'    => 0,
			'booking_id' => 0,
		), $atts, 'hotel_booking_review_form' );

		$room_id    = absint( $atts['room_id'] );
		$booking_id = absint( $atts['booking_id'] );

		if ( ! $room_id ) {
			$room_id = get_the_ID();
		}

		ob_start();

		$user_name  = '';
		$user_email = '';

		if ( is_user_logged_in() ) {
			$user       = wp_get_current_user();
			$user_name  = $user->display_name;
			$user_email = $user->user_email;
		}
		?>

		<div class="hb-review-form-container">
			<h3><?php esc_html_e( 'Write a Review', 'hotel-booking' ); ?></h3>
			<form class="hb-review-form" id="hb-review-form">
				<?php wp_nonce_field( 'hotel-booking-nonce', 'nonce' ); ?>
				<input type="hidden" name="room_id" value="<?php echo esc_attr( $room_id ); ?>">
				<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking_id ); ?>">

				<div class="hb-form-group">
					<label for="hb-rating"><?php esc_html_e( 'Rating', 'hotel-booking' ); ?> *</label>
					<div class="hb-star-rating" id="hb-star-rating">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<span class="dashicons dashicons-star-filled star" data-rating="<?php echo esc_attr( $i ); ?>"></span>
						<?php endfor; ?>
						<input type="hidden" name="rating" id="hb-rating" value="0" required>
					</div>
				</div>

				<div class="hb-form-group">
					<label for="hb-review-title"><?php esc_html_e( 'Title (optional)', 'hotel-booking' ); ?></label>
					<input type="text" id="hb-review-title" name="title" class="hb-form-input">
				</div>

				<div class="hb-form-group">
					<label for="hb-review-content"><?php esc_html_e( 'Review', 'hotel-booking' ); ?> *</label>
					<textarea id="hb-review-content" name="content" class="hb-form-textarea" rows="5" required></textarea>
				</div>

				<div class="hb-form-row">
					<div class="hb-form-group">
						<label for="hb-guest-name"><?php esc_html_e( 'Your Name', 'hotel-booking' ); ?> *</label>
						<input type="text" id="hb-guest-name" name="guest_name" value="<?php echo esc_attr( $user_name ); ?>" class="hb-form-input" required>
					</div>
					<div class="hb-form-group">
						<label for="hb-guest-email"><?php esc_html_e( 'Your Email', 'hotel-booking' ); ?> *</label>
						<input type="email" id="hb-guest-email" name="guest_email" value="<?php echo esc_attr( $user_email ); ?>" class="hb-form-input" required>
					</div>
				</div>

				<button type="submit" class="hb-submit-review-btn">
					<?php esc_html_e( 'Submit Review', 'hotel-booking' ); ?>
				</button>
			</form>
		</div>

		<?php
		return ob_get_clean();
	}
}
