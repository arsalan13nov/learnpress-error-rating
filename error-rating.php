<?php
/**
* Plugin Name: Error Rating
* Description: Calculates error rating for each of the answer submitted by the learner.
* Version:     1.0
* Author:      Arsalan
* Author URI:  https://www.fiverr.com/arsalankhan416
* License:     GPL2
* GitHub Plugin URI:    https://github.com/arsalan13nov/learnpress-error-rating
* GitHub Branch:    master
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

register_activation_hook( __FILE__, 'lp_error_rate_activation' );
register_deactivation_hook( __FILE__, 'lp_error_rate_deactivation' );
define( 'VERSION', '1.0' );
/**
 * URLS
 */
define( 'LP_ERR_PLUGIN_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );
define( 'LP_ERR_ASSETS_URL', trailingslashit( LP_ERR_PLUGIN_URL . 'assets' ) );
/**
 * Activation function hook
 *
 * @return void
 */
function lp_error_rate_activation() {

	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	
	if ( ! lp_error_rate_required_plugins() ) {
		return ;
	}

	update_option( 'ld_err_rate_version', VERSION );
}

/**
 * Deactivation function hook
 *
 * @return void
 */
function lp_error_rate_deactivation() {
	delete_option( 'ld_err_rate_version' );
}

function lp_error_rate_required_plugins() {

    if ( ! class_exists( 'LearnPress' ) ) {
		deactivate_plugins ( plugin_basename ( __FILE__ ), true );
        $class = "notice is-dismissible error";
        $message = __( "Error Rating plugin add-on requires LearnPress plugin to be activated.", "lp_err" );
        printf ( "<div id='message' class='%s'> <p>%s</p></div>", $class, $message ); 
        return false;     
	}

    return true;
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
	$lp_error_rating = new LearnPress_Quiz_Error_Rating();
}

add_action( 'plugins_loaded', function(){
	run_lp_quiz_modification();
}, 99 );
