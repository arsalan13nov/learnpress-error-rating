<?php
class LearnPress_Quiz_Error_Rating {

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */

	public function __construct() {

		add_action( 'the_post', array( $this, 'add_answer_error_rating' ), 10 );
		add_action( 'learn-press/user/quiz-finished', array( $this, 'add_answer_error_rating_last_question' ), 12, 3 );	
		add_action( 'wp_enqueue_scripts', array( $this, 'lp_custom_style' ), 1001 );
		add_action( 'learn-press/quiz/after-complete-button', array( $this, 'hide_complete_btn' ) );

		// Resit Button
		add_action( 'learn-press/quiz-buttons', array( $this, '_learn_press_resit_button' ), 5 );
		
	}

	public function _learn_press_resit_button() {

		$user      = LP_Global::user();
		$quiz      = LP_Global::course_item_quiz();
		$quiz_data = $user->get_quiz_data( $quiz->get_id() );
		$user_quiz_grade = learn_press_get_user_item_meta( $quiz_data->get_user_item_id(), 'grade', true );
		
		$quiz_questions = $quiz->get_questions();
		$total_questions = count( $quiz_questions );
		
		if ( ! $user->has_quiz_status( 'completed', $quiz->get_id(), $course->get_id() ) || $total_questions < 11 ) {
			return;
		}
		
		if ( 'passed' != $user_quiz_grade ) {
			echo LearnPress_Quiz_Misc::get_resit_course_enroll_button();
		}
		
	}

	public function lp_custom_style() {

		if( is_admin() )
			return;
				
        /**
         * enqueue admin css
         */
        wp_enqueue_style( 'lp_custom_style', LP_ERR_ASSETS_URL . 'lp-custom-css.css', null, null, null );
    }

    /**
	 * Check if current Question is last or not
	 *
	 * @since    1.0.0
	 */
    public function check_last_question( $quiz ) { 
    	
    	if( $quiz ) {
    		// Get current question    		
			$current_question_id = ( LP_Request::get_int( 'question-id' ) != '' && LP_Request::get_int( 'question-id' ) != 0 ) ? LP_Request::get_int( 'question-id' ) : $quiz->get_viewing_question( 'id' );
			$next_id 			 = $quiz->get_next_question( $current_question_id );

			if( $next_id == '' )
				return true;
			else
				return false;
    	}
    }

    /**
	 * Hides Complete Quiz button unless its a last Q.
	 *
	 * @since    1.0.0
	 */
    public function hide_complete_btn() {

    	$quiz                = LP_Global::course_item_quiz();
		$is_last_q 			= $this->check_last_question( $quiz );

		// If last Q then display Complete button
		if( $is_last_q ) {
        ?>
        <style type="text/css">
			.complete-quiz {
			    display: inline-block !important;
			}
		</style>
        <?php
    	}
    }

	/**
	 * Hooks into learnpress navigation and quiz hooks to add error_rating.
	 *
	 * @since    1.0.0
	 */
	public function add_answer_error_rating_last_question( $quiz_id, $course_id, $obj_id ){
		
		// Get quiz data object
		$quiz_data = $this->get_quiz_data( $quiz_id, $course_id );
		$current_question_id = learn_press_get_user_item_meta( $quiz_data->get_user_item_id(), 'last_question_id', true );
		
		// Add error_rating for this final quiz
		$this->add_error_rating_meta( $current_question_id, $quiz_data );
	}

	public function get_quiz_data( $quiz_id, $course_id ) {

		// Get current user current quiz and course data
		$user   = learn_press_get_current_user();
		$course = learn_press_get_course( $course_id );
		$quiz   = learn_press_get_quiz( $quiz_id );

		// Get current question id which will be the final question of the quiz
		$course_data = $user->get_course_data( $course->get_id() );
		$quiz_data   = $course_data->get_item_quiz( $quiz->get_id() );

		return $quiz_data;
	}

	/**
	 * Add answer rating for each question user is submitting.
	 *
	 * @since    1.0.0
	 */
	public function add_answer_error_rating() {

		if( is_admin() )
			return;

		global $wpdb;
		// Get nave type and course,quiz & question IDs
		$course_id   = LP_Request::get_int( 'course-id' );
  		$quiz_id     = LP_Request::get_int( 'quiz-id' );
 		$question_id = LP_Request::get_int( 'question-id' );
		
			
		if( $course_id && $quiz_id )
			$quiz_data = $this->get_quiz_data( $quiz_id, $course_id );
		else
			return false;

		// Check for last Q
		$quiz                = LP_Global::course_item_quiz();
		$is_last_q 			= $this->check_last_question( $quiz );
		
		if( $is_last_q ){
			learn_press_update_user_item_meta( $quiz_data->get_user_item_id(), 'last_question_id',  $question_id );
			$question_id = $wpdb->get_var( $wpdb->prepare("SELECT question_id FROM ".$wpdb->prefix."learnpress_quiz_questions WHERE quiz_id = %d ORDER BY question_order DESC limit 1", $quiz_id ) );
		}
		
		// Check if an answer is attempted or skipped. Selecting an answer is mandatory.
		if( isset( $_REQUEST['question-data'] ) 
			&& $question_id 
			&& !isset( $_REQUEST[ 'learn-press-question-'.$question_id ] ) 
			&& $_REQUEST['nav-type'] != 'prev-question'
		) {	
			$redirect = $quiz->get_question_link( $question_id );
			$redirect = add_query_arg( 'q', 'error', $redirect );
			wp_safe_redirect( $redirect );
			exit;		
		}

		// Pass quiz data to meta update function
		$this->add_error_rating_meta( $question_id, $quiz_data );
		
		return true;
	}

	/**
	 * Add answer rating for each question user is submitting.
	 *
	 * @since    1.0.0
	 */
	public function add_error_rating_meta( $question_id, $quiz_data ) {
		
		global $wpdb;
		$query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."learnpress_question_answers WHERE question_id = %d", $question_id );
		
		$question_answers 			= $wpdb->get_results( $query, 'ARRAY_A' );
		$toatl_amswers				= count( $question_answers );
		$correct_ans_priority = $user_ans_priority	= 0;
		$_error_rating 				= 0;
		$priority_difference 		= 0;
		$error_rating_data 			= array();
		$error_rate_meta 			= array();
		$error_rate_set 			= false;


		// Loop through all answers of a question
		foreach( $question_answers as $q_answer ){
			
			$ans_priority = $q_answer['answer_order'];
			$answer_data = maybe_unserialize( $q_answer['answer_data'] );
			
			$user_answer = $_REQUEST['learn-press-question-'.$question_id];
			
			if( $answer_data['value'] == $user_answer ) {
				$user_ans_priority = $ans_priority;
			}
			if( $answer_data['is_true'] == 'yes' ) {
				$correct_ans_priority = $ans_priority;
			}

			$priority_difference = ( $user_ans_priority ) - $correct_ans_priority;
			if( $user_ans_priority && $correct_ans_priority ) {
				$_error_rating = $priority_difference * 5;
				$error_rate_set = true;
			}	
		}
		
		if( $error_rate_set ) {
			
			$error_rate_meta_array = array();
			$error_rate_meta_array = learn_press_get_user_item_meta(  $quiz_data->get_user_item_id(), '_error_rating', true );

			$error_rating_data[ $question_id ] = $_error_rating;
	
			if( is_array( $error_rate_meta_array ) && !empty( $error_rate_meta_array ) ){
				$error_rate_meta_array[ $question_id ] = $_error_rating;
				$error_rate_meta = $error_rate_meta_array;
			} else {
				$error_rate_meta = $error_rating_data;
			}
			
			if( is_array( $error_rate_meta ) ) {
				ksort($error_rate_meta);			
				learn_press_update_user_item_meta( $quiz_data->get_user_item_id(), '_error_rating',  $error_rate_meta );
			}			
		}
	}

}