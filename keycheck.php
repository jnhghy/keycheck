<?php

/**
 * Plugin Name:       Keycheck
 * Plugin URI:        
 * Description:       The plugin checks posts and tags for specific keys in a specific time interval and changes the post state according with the findings.
 * Version:           1.0.0
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'KEYCHECK', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_keycheck() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-keycheck-activator.php';
	Keycheck_Activator::activate();
}
/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_keycheck() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-keycheck-deactivator.php';
	Keycheck_Deactivator::deactivate();
}
register_activation_hook( __FILE__, 'activate_keycheck' );
register_deactivation_hook( __FILE__, 'deactivate_keycheck' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'class-keycheck.php';


/**
 * Begins execution of the plugin.
 *
 *
 * @since    1.0.0
 */
function run_keycheck() {
	$plugin = new Keycheck();
	$plugin->run();
}
run_keycheck();