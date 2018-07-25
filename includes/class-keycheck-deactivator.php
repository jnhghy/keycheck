<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 */
class Keycheck_Deactivator {
	/**
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
			wp_clear_scheduled_hook('run_keycheck_event');
	}
}