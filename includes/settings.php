<?php
/**
 * Settings Page
 *
 * @package PagePreview
 */

namespace PagePreview\Settings;

use PagePreview\PagePreviewer;
use function PagePreview\Utils\get_eligible_post_types;
use function PagePreview\Utils\is_local_site;
use function PagePreview\Utils\is_page_preview_settings_screen;
use const PagePreview\Constants\BLOG_URL;
use const PagePreview\Constants\FAQ_URL;
use const PagePreview\Constants\GITHUB_URL;
use const PagePreview\Constants\SETTING_OPTION;
use const PagePreview\Constants\SUPPORT_URL;
use const PagePreview\Constants\TWITTER_URL;

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
// phpcs:disable Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed
// phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	if ( PAGE_PREVIEW_IS_NETWORK ) {
		add_action( 'network_admin_menu', __NAMESPACE__ . '\\admin_menu' );
		add_action( 'network_admin_notices', __NAMESPACE__ . '\\local_site_notice' );
	} else {
		add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu' );
		add_action( 'admin_notices', __NAMESPACE__ . '\\local_site_notice' );
	}

	add_action( 'admin_init', __NAMESPACE__ . '\\save_settings' );
	add_filter( 'admin_body_class', __NAMESPACE__ . '\\add_sui_admin_body_class' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );
}

/**
 * Add required class for shared UI
 *
 * @param string $classes css classes for admin area
 *
 * @return string
 * @see https://wpmudev.github.io/shared-ui/installation/
 */
function add_sui_admin_body_class( $classes ) {
	$classes .= ' sui-2-12-24 ';

	return $classes;
}

/**
 * Enqueue scripts
 *
 * @return void
 */
function enqueue_scripts() {
	wp_enqueue_style( 'page-preview-admin', PAGE_PREVIEW_URL . 'dist/css/admin-style.css', [], PAGE_PREVIEW_VERSION );
}

/**
 * Add menu item
 */
function admin_menu() {
	$parent = PAGE_PREVIEW_IS_NETWORK ? 'settings.php' : 'options-general.php';

	add_submenu_page(
		$parent,
		esc_html__( 'Page Preview', 'page-preview' ),
		esc_html__( 'Page Preview', 'page-preview' ),
		apply_filters( 'page_preview_admin_menu_cap', 'manage_options' ),
		'page-preview',
		__NAMESPACE__ . '\settings_page'
	);
}

/**
 * Settings page
 */
function settings_page() {
	$settings = \PagePreview\Utils\get_settings();
	?>
	<?php if ( is_network_admin() ) : ?>
		<?php settings_errors(); ?>
	<?php endif; ?>

	<main class="sui-wrap">
		<div class="sui-header">
			<h1 class="sui-header-title">
				<?php esc_html_e( 'Page Preview', 'page-preview' ); ?>
			</h1>
		</div>

		<form method="post" action="">
			<?php wp_nonce_field( 'page_preview_settings', 'page_preview_settings' ); ?>
			<section class="sui-row-with-sidenav">

				<!-- TAB: Regular -->
				<div class="sui-box" data-tab="basic-options">

					<div class="sui-box-header">
						<h2 class="sui-box-title">
							<?php esc_html_e( 'Settings', 'page-preview' ); ?>
						</h2>
						<div class="sui-actions-right sui-hidden-important" style="display: none;">
							<button type="submit" class="sui-button sui-button-blue" id="page-preview-save-settings-top" data-msg="">
								<i class="sui-icon-save" aria-hidden="true"></i>
								<?php esc_html_e( 'Update settings', 'page-preview' ); ?>
							</button>
						</div>
					</div>

					<div class="sui-box-body sui-upsell-items">
						<!-- Post Type Control -->
						<div class="sui-box-settings-row">
							<div class="sui-box-settings-col-1">
								<span class="sui-settings-label">
									<?php esc_html_e( 'Post Types', 'page-preview' ); ?>
								</span>
							</div>

							<div class="sui-box-settings-col-2">
								<div class="sui-form-field">
									<?php
									$post_types = get_eligible_post_types();
									foreach ( $post_types as $post_type ) :
										?>
										<label for="post_types_<?php echo esc_attr( $post_type ); ?>" class="sui-checkbox sui-checkbox-stacked">
											<input type="checkbox"
												   value="<?php echo esc_attr( $post_type ); ?>"
												   name="post_types[]"
												   id="post_types_<?php echo esc_attr( $post_type ); ?>"
												<?php checked( in_array( $post_type, $settings['post_types'], true ) ); ?>
											>
											<span class="sui-toggle-slider" aria-hidden="true"></span>
											<span id="post_types_<?php echo esc_attr( $post_type ); ?>_label" class="sui-toggle-label">
												<?php echo esc_html( ucfirst( $post_type ) ); ?>
											</span>
										</label>
									<?php endforeach; ?>

									<span class="sui-description">
										<?php esc_html_e( 'Select the post types that you want to enable Page Preview for.', 'page-preview' ); ?>
								</div>
							</div>

						</div>

						<!-- Crop Control -->
						<div class="sui-box-settings-row">
							<div class="sui-box-settings-col-1">
								<span class="sui-settings-label">
									<?php esc_html_e( 'Crop Screenshots', 'page-preview' ); ?>
								</span>
							</div>

							<div class="sui-box-settings-col-2">
								<div class="sui-form-field">

									<label for="crop" class="sui-toggle">
										<input type="checkbox"
											   value="1"
											   name="crop"
											   id="crop"
											<?php checked( $settings['crop'] ); ?>
										>
										<span class="sui-toggle-slider" aria-hidden="true"></span>
										<span id="crop_label" class="sui-toggle-label">
											<?php esc_html_e( 'Crop preview images.', 'page-preview' ); ?>
										</span>
									</label>

									<span class="sui-description">
										<?php esc_html_e( 'When this option is enabled, it crops the screenshot based on the height of the preview dimension. If you want to capture a full page screenshot keep this option disabled.', 'page-preview' ); ?>
									</span>
								</div>
							</div>
						</div>

						<!-- Zoom control -->
						<div class="sui-box-settings-row">
							<div class="sui-box-settings-col-1">
								<span class="sui-settings-label">
									<?php esc_html_e( 'Zoom', 'page-preview' ); ?>
								</span>
							</div>

							<div class="sui-box-settings-col-2">
								<div class="sui-form-field">
									<label for="zoom" class="sui-toggle">
										<input type="checkbox"
											   value="1"
											   name="zoom"
											   id="zoom"
											<?php checked( $settings['zoom'] ); ?>
										>
										<span class="sui-toggle-slider" aria-hidden="true"></span>
										<span id="zoom_label" class="sui-toggle-label">
											<?php esc_html_e( 'Enable zoom on hovering preview images.', 'page-preview' ); ?>
										</span>
									</label>

									<span class="sui-description">
										<?php esc_html_e( 'Enable this option to allow images to be enlarged when hovered over. If disabled, hovering will not affect the image size.', 'page-preview' ); ?>
									</span>
								</div>
							</div>
						</div>

						<!-- Delay Control   -->
						<div class="sui-box-settings-row">
							<div class="sui-box-settings-col-1">
								<span class="sui-settings-label">
									<?php esc_html_e( 'Delay', 'page-preview' ); ?>
								</span>
							</div>

							<div class="sui-box-settings-col-2">
								<div class="sui-form-field">
									<input type="number"
										   value="<?php echo esc_attr( $settings['delay'] ); ?>"
										   name="delay"
										   id="delay"
										   min="1"
										   max="10"
										   class="sui-form-control sui-input-sm"
									>
									<span class="sui-description">
										<?php esc_html_e( 'Delay in seconds before capturing the screenshot. This is useful if your site has animations or changes that occur after the initial load.', 'page-preview' ); ?>
									</span>
								</div>
							</div>
						</div>

						<!-- Featured Image Fallback (it's toggle) -->
						<div class="sui-box-settings-row">
							<div class="sui-box-settings-col-1">
								<span class="sui-settings-label">
									<?php esc_html_e( 'Featured Image Fallback', 'page-preview' ); ?>
								</span>
							</div>

							<div class="sui-box-settings-col-2">
								<div class="sui-form-field">
									<label for="featured_image_fallback" class="sui-toggle">
										<input type="checkbox"
											   value="1"
											   name="featured_image_fallback"
											   id="featured_image_fallback"
											<?php checked( $settings['featured_image_fallback'] ); ?>
										>
										<span class="sui-toggle-slider" aria-hidden="true"></span>
										<span class="sui-toggle-label">
											<?php esc_html_e( 'Use featured image as fallback.', 'page-preview' ); ?>
										</span>
									</label>
									<span class="sui-description">
										<?php esc_html_e( 'Enable this option to display the featured image when the page is unpublished or the page preview image does not exist. Applies only if a featured image exists.', 'page-preview' ); ?>
									</span>
								</div>
							</div>
						</div>




					</div>

					<div class="sui-box-footer">
						<div class="sui-actions-left">
							<button type="submit" class="sui-button sui-button-blue" id="page-preview-save-settings" data-msg="">
								<i class="sui-icon-save" aria-hidden="true"></i>
								<?php esc_html_e( 'Update settings', 'page-preview' ); ?>
							</button>
							<button type="submit" form="delete-preview-data-form" class="sui-button sui-button-red sui-button-ghost" id="page-preview-reset-settings">
								<i class="sui-icon-undo" aria-hidden="true"></i>
								<?php esc_html_e( 'Delete all preview data', 'page-preview' ); ?>
							</button>
						</div>
					</div>

				</div>
			</section>

		</form>

		<form method="post" action="" id="delete-preview-data-form">
			<?php wp_nonce_field( 'page_preview_settings', 'page_preview_settings' ); ?>
			<input type="hidden" name="reset" value="1">
		</form>

		<!-- ELEMENT: The Brand -->
		<div class="sui-footer">
			<?php
			echo wp_kses_post(
				sprintf(
				/* translators: %s: HandyPlugins URL */
					__( 'Made with <i class="sui-icon-heart"></i> by <a href="%s" rel="noopener" target="_blank">HandyPlugins</a>', 'page-preview' ),
					'https://handyplugins.co/'
				)
			);
			?>
		</div>

		<footer>
			<!-- ELEMENT: Navigation -->
			<ul class="sui-footer-nav">
				<li><a href="<?php echo esc_url( FAQ_URL ); ?>" target="_blank"><?php esc_html_e( 'FAQ', 'page-preview' ); ?></a></li>
				<li><a href="<?php echo esc_url( BLOG_URL ); ?>" target="_blank"><?php esc_html_e( 'Blog', 'page-preview' ); ?></a></li>
				<li><a href="<?php echo esc_url( SUPPORT_URL ); ?>" target="_blank"><?php esc_html_e( 'Support', 'page-preview' ); ?></a></li>
			</ul>

			<!-- ELEMENT: Social Media -->
			<ul class="sui-footer-social">
				<li><a href="<?php echo esc_url( GITHUB_URL ); ?>" target="_blank" aria-label="<?php esc_attr_e( 'HandyPlugins GitHub URL', 'page-preview' ); ?>">
						<i class="sui-icon-social-github" aria-hidden="true"></i>
						<span class="sui-screen-reader-text">GitHub</span>
					</a></li>
				<li><a href="<?php echo esc_url( TWITTER_URL ); ?>" target="_blank" aria-label="<?php esc_attr_e( 'HandyPlugins Twitter URL', 'page-preview' ); ?>">
						<i class="sui-icon-social-twitter" aria-hidden="true"></i></a>
					<span class="sui-screen-reader-text">Twitter</span>
				</li>
			</ul>
		</footer>

	</main>

	<?php
}

/**
 * Save settings
 */
function save_settings() {

	if ( ! is_user_logged_in() ) {
		return;
	}

	$nonce = filter_input( INPUT_POST, 'page_preview_settings', FILTER_SANITIZE_SPECIAL_CHARS );
	if ( wp_verify_nonce( $nonce, 'page_preview_settings' ) ) {

		if ( ! empty( $_POST['reset'] ) ) {
			if ( PAGE_PREVIEW_IS_NETWORK ) {
				$sites = get_sites();
				foreach ( $sites as $site ) {
					switch_to_blog( $site->blog_id );
					PagePreviewer::delete_all_preview_data();
					restore_current_blog();
				}
			} else {
				PagePreviewer::delete_all_preview_data();
			}

			add_settings_error(
				SETTING_OPTION,
				'page-preview',
				esc_html__( 'All preview files and associated metadata have been successfully deleted!', 'page-preview' ),
				'success'
			);

			return;
		}

		$settings   = [];
		$post_types = [];

		if ( ! empty( $_POST['post_types'] ) ) {
			$post_types = array_map( 'sanitize_text_field', wp_unslash( $_POST['post_types'] ) );
		}

		$settings['crop']                    = ! empty( $_POST['crop'] );
		$settings['zoom']                    = ! empty( $_POST['zoom'] );
		$settings['featured_image_fallback'] = ! empty( $_POST['featured_image_fallback'] );
		$settings['post_types']              = $post_types;
		$settings['delay']                   = absint( filter_input( INPUT_POST, 'delay', FILTER_SANITIZE_SPECIAL_CHARS ) );

		if ( PAGE_PREVIEW_IS_NETWORK ) {
			update_site_option( SETTING_OPTION, $settings );
		} else {
			update_option( SETTING_OPTION, $settings, false );
		}

		add_settings_error( SETTING_OPTION, 'page-preview', esc_html__( 'Settings saved.', 'page-preview' ), 'success' );
	}
}

/**
 * Display notice for local site
 */
function local_site_notice() {
	if ( ! is_page_preview_settings_screen() ) {
		return;
	}

	if ( ! is_local_site() ) {
		return;
	}

	$capability = PAGE_PREVIEW_IS_NETWORK ? 'manage_network_options' : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		return;
	}

	?>
	<div class="notice notice-warning">
		<p><?php esc_html_e( 'You cannot use page previews on localhost. Screenshot capturing service only works for the public accessible domains.', 'page-preview' ); ?></p>
	</div>

	<?php
}


