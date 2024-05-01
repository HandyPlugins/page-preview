<?php
/**
 * Background process for cache purging
 *
 * @package PagePreview
 */

namespace PagePreview\Async;

use \Page_Preview_WP_Background_Process as Page_Preview_WP_Background_Process;
use PagePreview\PagePreviewer;


/**
 * Class CachePurger
 */
class ScreenshotProcessor extends Page_Preview_WP_Background_Process {

	/**
	 * Plugin settings
	 *
	 * @var $settings
	 */
	protected $settings;

	/**
	 * string
	 *
	 * @var $action
	 */
	protected $action = 'page_preview_screenshot';

	/**
	 * Task
	 * Perform Preload Tasks
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		$post_id = $item;
		PagePreviewer::generate_preview_image( $post_id );
		return false;
	}


	/**
	 * Complete
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		parent::complete();
	}

	/**
	 * Sometimes canceling a process is glitchy
	 * Try to cancel all items in the queue up to $max_attempt
	 */
	public function cancel_process() {
		parent::cancel();
	}

	/**
	 * Whether the process running or not
	 *
	 * @return bool
	 */
	public function is_process_running() { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::is_processing();
	}

	/**
	 * Return an instance of the current class
	 *
	 * @return ScreenshotProcessor
	 */
	public static function factory() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

}
