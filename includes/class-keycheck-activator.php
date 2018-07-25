<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 *
 */

class Keycheck_Activator {

	/**
	 *
	 * @since    1.0.0
	 */

	public static function activate() {
		if (! wp_next_scheduled ( 'run_keycheck_event' )) {
			wp_schedule_event( time(), 'keycheck_dynamic', 'run_keycheck_event' );
    }
	}

}