<?php
/**
* Plugin Name: LearnPress Error Rating
* Description: Calculates error rating for each of the answer submitted by the learner.
* Version:     1.0.0
* Author:      Arsalan
* License:     GPL2
* GitHub Plugin URI:    https://github.com/
* GitHub Branch:    master
*/
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Core plugin class
 */
require plugin_dir_path( __FILE__ ) . 'classes/class-lp-quiz-error-rating.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_lp_quiz_modification() {
	$plugin = new LearnPress_Quiz_Error_Rating();
	$plugin->run();
}
run_lp_quiz_modification();