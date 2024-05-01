<?php
/**
 * Common utilities and functions
 *
 * @package PagePreview
 */

namespace PagePreview\Utils;

use const PagePreview\Constants\SCREENSHOT_ENDPOINT;
use const PagePreview\Constants\SETTING_OPTION;

/**
 * Get settings with defaults
 *
 * @return array
 * @since  1.0
 */
function get_settings() {
	$defaults = [
		'post_types'              => [
			'page',
		],
		'crop'                    => true,
		'delay'                   => 3,
		'zoom'                    => true,
		'featured_image_fallback' => true,
	];

	if ( PAGE_PREVIEW_IS_NETWORK ) {
		$settings = get_site_option( SETTING_OPTION, [] );
	} else {
		$settings = get_option( SETTING_OPTION, [] );
	}

	// Merge settings with defaults, ensuring new additions and nested arrays are included
	$settings = array_replace_recursive( $defaults, $settings );

	return $settings;
}

/**
 * Get the screenshot endpoint
 *
 * @return string
 */
function get_screenshot_endpoint() {
	if ( defined( 'PAGE_PREVIEW_SCREENSHOT_ENDPOINT' ) ) {
		return PAGE_PREVIEW_SCREENSHOT_ENDPOINT;
	}

	return SCREENSHOT_ENDPOINT;
}

/**
 * Is plugin activated network wide?
 *
 * @param string $plugin_file file path
 *
 * @return bool
 * @since 1.0
 */
function is_network_wide( $plugin_file ) {
	if ( ! is_multisite() ) {
		return false;
	}

	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}

	return is_plugin_active_for_network( plugin_basename( $plugin_file ) );
}


/**
 * Get the documentation url
 *
 * @param string $path     The path of documentation
 * @param string $fragment URL Fragment
 *
 * @return string final URL
 */
function get_doc_url( $path = null, $fragment = '' ) {
	$doc_base       = 'https://handyplugins.co/page-preview/docs/';
	$utm_parameters = '?utm_source=wp_admin&utm_medium=plugin&utm_campaign=settings_page';

	if ( ! empty( $path ) ) {
		$doc_base .= ltrim( $path, '/' );
	}

	$doc_url = trailingslashit( $doc_base ) . $utm_parameters;

	if ( ! empty( $fragment ) ) {
		$doc_url .= '#' . $fragment;
	}

	return $doc_url;
}

/**
 * Check weather current screen is page-preview settings page or not
 *
 * @return bool
 * @since 1.0
 */
function is_page_preview_settings_screen() {
	$current_screen = get_current_screen();

	if ( ! is_a( $current_screen, '\WP_Screen' ) ) {
		return false;
	}

	if ( false !== strpos( $current_screen->base, 'page-preview' ) ) {
		return true;
	}

	return false;
}

/**
 * Check if the given post excluded
 *
 * @param int $post_id Post ID
 *
 * @return bool
 */
function is_excluded_page( $post_id ) {
	$excluded_pages = apply_filters( 'page_preview_excluded_pages', [] );

	if ( in_array( $post_id, $excluded_pages, true ) ) {
		return true;
	}

	return false;
}


/**
 * Get filesystem
 *
 * @return \WP_Filesystem_Base
 */
function get_filesystem() {
	global $wp_filesystem;

	if ( ! $wp_filesystem ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}

	return $wp_filesystem;
}

/**
 * Get the preview base directory
 *
 * @return string
 */
function get_preview_base_dir() {
	$upload_dir  = wp_upload_dir();
	$upload_path = $upload_dir['basedir'] . '/page-previews';

	$upload_path = (string) apply_filters( 'page_preview_base_dir', $upload_path );

	return $upload_path;
}

/**
 * Get the preview base URL
 *
 * @return string
 */
function get_preview_base_url() {
	$upload_dir = wp_upload_dir();
	$base_url   = $upload_dir['baseurl'] . '/page-previews';
	$base_url   = (string) apply_filters( 'page_preview_base_url', $base_url );

	return $base_url;
}


/**
 * Get post types that are eligible for preview support.
 *
 * @return string[] Post types eligible for Page Preview.
 */
function get_eligible_post_types() {
	$post_types = get_post_types( [], 'names' );
	$post_types = array_filter( $post_types, 'is_post_type_viewable' );
	$post_types = array_values( $post_types );

	$attachment_key = array_search( 'attachment', $post_types, true );
	if ( false !== $attachment_key ) {
		unset( $post_types[ $attachment_key ] );
	}

	/**
	 * Filters the list of post types which may be supported for Page Preview.
	 * By default the list includes those which are public.
	 *
	 * @param string[] $post_types Post types.
	 *
	 * @since 1.0
	 */
	return array_values( (array) apply_filters( 'page_preview_supportable_post_types', $post_types ) );
}

/**
 * Get placeholder image URL
 *
 * @return mixed|null
 */
function get_placeholder_image_url() {
	$placeholder_image_url = PAGE_PREVIEW_URL . 'dist/images/placeholder.png';

	$placeholder_image_url = apply_filters( 'page_preview_placeholder_image_url', $placeholder_image_url );

	return $placeholder_image_url;
}


/**
 * If the site is a local site.
 *
 * @return bool
 * @since 1.0
 */
function is_local_site() {
	$site_url = site_url();

	// Check for localhost and sites using an IP only first.
	$is_local = $site_url && false === strpos( $site_url, '.' );

	// Use Core's environment check, if available. Added in 5.5.0 / 5.5.1 (for `local` return value).
	if ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() ) {
		$is_local = true;
	}

	// Then check for usual usual domains used by local dev tools.
	$known_local = array(
		'#\.local$#i',
		'#\.localhost$#i',
		'#\.test$#i',
		'#\.docksal$#i',      // Docksal.
		'#\.docksal\.site$#i', // Docksal.
		'#\.dev\.cc$#i',       // ServerPress.
		'#\.lndo\.site$#i',    // Lando.
	);

	if ( ! $is_local ) {
		foreach ( $known_local as $url ) {
			if ( preg_match( $url, $site_url ) ) {
				$is_local = true;
				break;
			}
		}
	}

	return $is_local;
}
