<?php
class LearnPress_Quiz_Error_Rating {
	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'learnpress-error-rating';
		$this->version = '1.0.0';
	}

	/**
	 * Hooks into learnpress navigation and quiz hooks to add error_rating.
	 *
	 * @since    1.0.0
	 */
	public function run(){
		add_action( 'learn_press_request_handler_lp-nav-question-quiz', array( $this, 'add_answer_error_rating' ), 11 );
		add_action( 'learn-press/user/quiz-finished', array( $this, 'add_answer_error_rating_last_question' ), 11, 3 );
	}

	/**
	 * Hooks into learnpress navigation and quiz hooks to add error_rating.
	 *
	 * @since    1.0.0
	 */
	public function add_answer_error_rating_last_question( $quiz_id, $course_id, $obj_id ){

		// Get quiz data object
		$quiz_data = $this->get_quiz_data( $quiz_id, $course_id );
		$current_question_id = learn_press_get_user_item_meta( $quiz_data->get_user_item_id(), '_current_question', true );
		
		// Add error_rating for this final quiz
		add_error_rating_meta( $current_question_id, $quiz_data );
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
	
		// Get nave type and course,quiz & question IDs
		$nav_type = LP_Request::get_string( 'nav-type' );

		$course_id   = LP_Request::get_int( 'course-id' );
		$quiz_id     = LP_Request::get_int( 'quiz-id' );
		$question_id = LP_Request::get_int( 'question-id' );

		// Get quiz data object
		$quiz_data = $this->get_quiz_data( $quiz_id, $course_id );
		
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
		//$query = 'Select * from '.$wpdb->prefix.'learnpress_question_answers where question_id = '.$question_id;
		
		$question_answers 			= $wpdb->get_results( $query, 'ARRAY_A' );
		$toatl_amswers				= count( $question_answers );
		$correct_ans_priority 		= 0;
		$_error_rating 				= '';
		$priority_difference 		= 0;
		$error_rating_data 			= '';
		$error_rate_meta 			= array();
		$error_rate_set 			= false;

		// Loop through all answers of a question
		foreach( $question_answers as $q_answer ){
			
			$ans_priority = $q_answer['answer_order'];
			$answer_data = maybe_unserialize( $q_answer['answer_data'] );
			
			$user_answer = $quiz_data->get_question_answer( $question_id );
			
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
			
			$error_rate_meta_array = learn_press_get_user_item_meta(  $quiz_data->get_user_item_id(), '_error_rating', true );

			$error_rating_data[ $question_id ] = $_error_rating;

			if( !empty( $error_rate_meta_array ) ){
				$error_rate_meta = $error_rating_data + $error_rate_meta_array;
			} else {
				$error_rate_meta = $error_rating_data;
			}
			
			// print_r( $error_rate_meta_array ); 
			// print_r( $error_rate_meta ); 
			// print_r( $error_rating_data );	exit;
			ksort($error_rate_meta);			
			learn_press_update_user_item_meta( $quiz_data->get_user_item_id(), '_error_rating',  $error_rate_meta );
		}
	}

}