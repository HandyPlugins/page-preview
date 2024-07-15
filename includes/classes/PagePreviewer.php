<?php
/**
 * PagePreviewer class.
 *
 * @package AccessibilityToolkit
 */

namespace PagePreview;

use PagePreview\Async\ScreenshotProcessor;
use function PagePreview\Utils\get_eligible_post_types;
use function PagePreview\Utils\get_filesystem;
use function PagePreview\Utils\get_placeholder_image_url;
use function PagePreview\Utils\get_preview_base_dir;
use function PagePreview\Utils\get_preview_base_url;
use function PagePreview\Utils\get_screenshot_endpoint;
use function PagePreview\Utils\is_excluded_page;
use const PagePreview\Constants\PREVIEW_URL_META_KEY;
use \WP_Error as WP_Error;

/**
 * PagePreviewer class.
 */
class PagePreviewer {
	/**
	 * Instance of ScreenShotProcessor
	 *
	 * @var ScreenShotProcessor
	 */
	private $screenshot_processor;

	/**
	 * Settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * placeholder
	 *
	 * @since 1.0
	 */
	public function __construct() {
	}

	/**
	 * Factory method to get the instance of the class.
	 *
	 * @return false|self
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}

	/**
	 * Setup the hooks
	 *
	 * @return void
	 */
	public function setup() {
		$this->screenshot_processor = ScreenshotProcessor::factory();
		$this->settings             = \PagePreview\Utils\get_settings();

		$supported_post_types = $this->settings['post_types'];

		foreach ( $supported_post_types as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", [ $this, 'add_preview_column' ] );
			add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'show_preview_column' ], 10, 2 );
			add_filter( "bulk_actions-edit-$post_type", [ $this, 'register_bulk_actions' ] );
			add_filter( "handle_bulk_actions-edit-$post_type", [ $this, 'bulk_action_handler' ], 10, 3 );
		}

		add_action( 'admin_notices', [ $this, 'bulk_action_admin_notice' ] );
		add_action( 'before_delete_post', [ $this, 'delete_preview' ] );
		add_action( 'save_post', [ $this, 'generate_preview_on_post_save' ], 10, 2 );
	}

	/**
	 * Delete the preview image when the post is deleted
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function delete_preview( $post_id ) {
		$preview_urls = get_post_meta( $post_id, PREVIEW_URL_META_KEY, true );
		if ( $preview_urls ) {
			$upload_path = get_preview_base_dir();
			$pattern     = $upload_path . '/' . $post_id . '-*.png';
			$filesystem  = get_filesystem();
			foreach ( glob( $pattern ) as $file_path ) {
				$filesystem->delete( $file_path );
			}
			delete_post_meta( $post_id, PREVIEW_URL_META_KEY );
		}
	}

	/**
	 * Register the bulk actions
	 *
	 * @param array $bulk_actions Current bulk actions.
	 *
	 * @return mixed
	 */
	public function register_bulk_actions( $bulk_actions ) {
		$bulk_actions['create-page-preview'] = __( 'Create Page Preview', 'page-preview' );
		$bulk_actions['delete-page-preview'] = __( 'Delete Page Preview', 'page-preview' );

		return $bulk_actions;
	}

	/**
	 * Handle the bulk action
	 *
	 * @param string $redirect_to URL to redirect to.
	 * @param string $doaction    The action being taken.
	 * @param int[]  $post_ids    The post IDs to take the action on.
	 *
	 * @return mixed|string
	 */
	public function bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
		if ( ! in_array( $doaction, [ 'create-page-preview', 'delete-page-preview' ], true ) ) {
			return $redirect_to;
		}

		if ( 'create-page-preview' === $doaction ) {
			foreach ( $post_ids as $post_id ) {
				$this->screenshot_processor->push_to_queue( $post_id );
			}

			$this->screenshot_processor->save()->dispatch();

			$redirect_to = add_query_arg( 'bulk_page_preview', count( $post_ids ), $redirect_to );
		}

		if ( 'delete-page-preview' === $doaction ) {
			foreach ( $post_ids as $post_id ) {
				$this->delete_preview( $post_id );
			}

			$redirect_to = add_query_arg( 'delete_bulk_page_preview', count( $post_ids ), $redirect_to );
		}

		return $redirect_to;
	}

	/**
	 * Add preview column to the post list
	 *
	 * @param array $columns Post Table Columns.
	 *
	 * @return array
	 */
	public function add_preview_column( $columns ) {
		$new_columns = [];
		$add_key     = 'page-preview';
		$add_value   = __( 'Preview', 'page-preview' );
		$target_key  = 'title';

		foreach ( $columns as $key => $value ) {
			// Add old item to new array
			$new_columns[ $key ] = $value;

			// Add new item after target
			if ( $key === $target_key ) {
				$new_columns[ $add_key ] = $add_value;
			}
		}

		return $new_columns;
	}


	/**
	 * Show the preview column
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id Post ID.
	 *
	 * @return mixed
	 */
	public function show_preview_column( $column_name, $post_id ) {
		$class = 'page-preview-img ';

		if ( 'page-preview' === $column_name ) {
			$preview_urls = get_post_meta( $post_id, PREVIEW_URL_META_KEY, true );
			$srcset       = '';
			$src          = get_placeholder_image_url();
			if ( is_array( $preview_urls ) ) {
				if ( $this->settings['zoom'] ) {
					$class .= ' zoom';
				}

				$src = reset( $preview_urls );
				foreach ( $preview_urls as $resolution => $url ) {
					// Split the resolution into width and height
					list( $width, $height ) = explode( 'x', $resolution );
					// Append "w" to the width and construct the srcset string
					$srcset .= $url . ' ' . $width . 'w, ';
				}
				// Remove the trailing comma and space
				$srcset = rtrim( $srcset, ', ' );

				$srcset    = rtrim( $srcset, ', ' );
				$image_tag = sprintf(
					'<img class="%s" src="%s" srcset="%s" >',
					esc_attr( $class ),
					esc_url( $src ),
					esc_attr( $srcset )
				);
			} else {
				if ( $this->settings['featured_image_fallback'] ) {
					$thumbnail_id = get_post_thumbnail_id( $post_id );
					if ( $thumbnail_id ) {
						$class .= ' page-preview-fallback-thumbnail';
						$src    = wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' );
					} else {
						$class .= ' page-preview-placeholder';
					}
				}

				$image_tag = sprintf(
					'<img class="%s" src="%s">',
					esc_attr( $class ),
					esc_url( $src )
				);

			}

			printf(
				'<a href="%s" target="_blank" class="page-preview-item">%s</a>',
				esc_url( get_permalink( $post_id ) ),
				$image_tag // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		return $column_name;
	}

	/**
	 * Generate the preview image on post save
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post Post object.
	 *
	 * @return void
	 */
	public function generate_preview_on_post_save( $post_id, $post ) {
		$post_id = absint( $post_id );

		if ( empty( $post_id ) || empty( $post ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$settings             = \PagePreview\Utils\get_settings();
		$supported_post_types = $settings['post_types'];

		if ( ! in_array( $post->post_type, $supported_post_types, true ) ) {
			return;
		}

		$this->screenshot_processor->push_to_queue( $post_id );
		$this->screenshot_processor->save()->dispatch();
	}

	/**
	 * Display admin notice after bulk action
	 *
	 * @return void
	 */
	public function bulk_action_admin_notice() {
		$bulk_page_preview        = ! empty( $_GET['bulk_page_preview'] ) ? intval( wp_unslash( $_GET['bulk_page_preview'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$delete_bulk_page_preview = ! empty( $_GET['delete_bulk_page_preview'] ) ? intval( wp_unslash( $_GET['delete_bulk_page_preview'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $bulk_page_preview ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( __( 'Previews are being generated and will appear shortly. Please refresh after a few moments.', 'page-preview' ) ); ?></p>
			</div>
			<?php
		}

		if ( $delete_bulk_page_preview ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( __( 'Previews have been deleted.', 'page-preview' ) ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Generate the preview image
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return WP_Error|array
	 */
	public static function generate_preview_image( $post_id ) {
		$settings = \PagePreview\Utils\get_settings();

		// check if the post type is publicly viewable
		$post                 = get_post( $post_id );
		$supported_post_types = $settings['post_types'];

		if ( ! in_array( $post->post_type, $supported_post_types, true ) ) {
			return new WP_Error( 'invalid_post_type', esc_html__( 'Invalid post type', 'page-preview' ) );
		}

		if ( ! is_post_publicly_viewable( $post_id ) ) {
			return new WP_Error( 'post_not_public', esc_html__( 'Post is not publicly viewable', 'page-preview' ) );
		}

		if ( is_excluded_page( $post_id ) ) {
			return new WP_Error( 'excluded_page', esc_html__( 'Page is excluded', 'page-preview' ) );
		}

		// get the post URL
		$post_url = get_permalink( $post_id );

		$args = [
			'url'       => $post_url,
			'sizes'     => [ '1920x1080', '800x360', '320x560' ],
			'crop'      => $settings['crop'],
			'userAgent' => 'HandyPlugins Page Previewer',
			'delay'     => $settings['delay'],
		];

		$args = apply_filters( 'page_preview_request_args', $args, $post_id );

		// Use wp_remote_post to make the POST request
		$response = wp_remote_post(
			get_screenshot_endpoint(),
			[
				'method'  => 'POST',
				'headers' => [ 'Content-Type' => 'application/json; charset=utf-8' ],
				'body'    => wp_json_encode( $args ),
				'timeout' => 15,
			]
		);

		// Check if the request was successful
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $response_data['success'] ) {
			return new WP_Error( 'service_error', $response_data['message'] );
		}

		$upload_path = get_preview_base_dir();
		$upload_url  = get_preview_base_url();
		$filesystem  = get_filesystem();

		// check if the directory exists
		if ( ! file_exists( $upload_path ) ) {
			$filesystem->mkdir( $upload_path, 0755, true );
		}

		$images       = $response_data['images'];
		$preview_urls = [];

		foreach ( $images as $size => $image_data ) {
			// save the image
			$filename      = $post_id . '-' . strtolower( $size ) . '.png';
			$file_path     = $upload_path . '/' . $filename;
			$decoded_image = base64_decode( $image_data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$result        = $filesystem->put_contents( $file_path, $decoded_image ); // use wp_filesystem instead of file_put_contents

			if ( $result ) {
				$preview_url = $upload_url . '/' . $filename;
				// add timestamp to the filename to avoid caching
				$preview_url                         = esc_url( add_query_arg( 't', time(), $preview_url ) );
				$preview_urls[ strtolower( $size ) ] = $preview_url;
			}
		}

		if ( $preview_urls ) {
			// save the attachment id to the post meta
			update_post_meta( $post_id, PREVIEW_URL_META_KEY, $preview_urls );

			return $preview_urls;
		}

		return new WP_Error( 'no_preview_generated', esc_html__( 'No preview generated', 'page-preview' ) );
	}


	/**
	 * Delete all preview data
	 * This is used when the plugin is uninstalled or reset from the settings
	 *
	 * @return void
	 */
	public static function delete_all_preview_data() {
		global $wpdb;
		$preview_dir = get_preview_base_dir();
		$filesystem  = get_filesystem();
		$filesystem->rmdir( $preview_dir, true );

		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->postmeta,
			[
				'meta_key' => PREVIEW_URL_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			]
		);
	}
}
