<?php
/**
 * Uninstall functionalities
 * Deletes all plugin related data and configurations
 *
 * @package PagePreview
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}


require_once 'page-preview.php';

// clean up preview data
if ( is_multisite() ) {
	$sites = get_sites();
	foreach ( $sites as $site ) {
		switch_to_blog( $site->blog_id );
		\PagePreview\PagePreviewer::delete_all_preview_data();
		restore_current_blog();
	}
} else {
	\PagePreview\PagePreviewer::delete_all_preview_data();
}

// clean up settings
if ( PAGE_PREVIEW_IS_NETWORK ) {
	delete_site_option( \PagePreview\Constants\SETTING_OPTION );
} else {
	delete_option( \PagePreview\Constants\SETTING_OPTION );
}
