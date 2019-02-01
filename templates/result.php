<?php
/**
 * Template for displaying quiz result.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/result.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
$user      = LP_Global::user();
$quiz      = LP_Global::course_item_quiz();
$quiz_data = $user->get_quiz_data( $quiz->get_id() );
$result    = $quiz_data->get_results( false );
$error_rating = learn_press_get_user_item_meta( $quiz_data->get_user_item_id(), '_error_rating', true );
$count = 0;

$quiz_questions = $quiz->get_questions();
$total_questions = count( $quiz_questions );

if ( $quiz_data->is_review_questions() ) {
    return;
} ?>

<div class="quiz-result <?php echo esc_attr( $result['grade'] ); ?>">

    <h3><?php _e( 'Your Result', 'learnpress' ); ?></h3>    

    <ul class="result-statistic">
      
        <li class="result-statistic-field">
            <label><?php echo _x( 'Questions', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $quiz->count_questions(); ?></p>
        </li>
        <?php 
            // Calculation variables
            $pos_counters       = 0;
            $neg_counters       = 0;
            $sum_of_positive    = 0;
            $sum_of_negative    = 0;
            $value_of_errors    = 0;
            $the_difference     = 0;
            $sys_error          = 0;
            $diff_counter       = 0;
            $mean_deviation     = 0;
            $quiz_result        = 0;

            // arrays
            $all_positives       = array();
            $all_negatives       = array();

        if( is_array( $error_rating ) && !empty( $error_rating ) && $total_questions <= 10 ) : ?>
            <?php 
                // error_rating loop for each question
                foreach( $error_rating as $q_id => $err_rating ) : 
                    $question = LP_Question::get_question( $q_id );                   

                    if( $err_rating < 0 ){
                        $neg_counters++;
                        $all_positives[] = $err_rating;
                    }
                    if( $err_rating > 0 ){
                        $pos_counters++;
                        $all_negatives[] = abs( $err_rating );
                    }

                    $q_text = $question->get_title( 'display' );
                    $count++;
            ?>
                <li class="result-statistic-field">
                    <label><?php echo _x( sprintf( 'Question %s', $count ), 'quiz-result', 'learnpress' ); ?></label>
                    <p><?php echo $err_rating; ?></p>
                </li>
            <?php endforeach; ?>            
        <?php endif; ?>

        <?php if( is_array( $error_rating ) && !empty( $error_rating ) && $total_questions > 10 ) {

                foreach( $error_rating as $q_id => $err_rating ) : 
                    $question = LP_Question::get_question( $q_id );                   

                    if( $err_rating < 0 ){
                        $neg_counters++;
                        $all_positives[] = $err_rating;
                    }
                    if( $err_rating > 0 ){
                        $pos_counters++;
                        $all_negatives[] = abs( $err_rating );
                    }
                endforeach;
        } ?>
        <?php 
            
            if( !empty( $all_positives ) || !empty( $all_negatives ) ) {

                // Caclculation of mean deviation and quiz result
                $sum_of_positives = array_sum( $all_positives );
                $sum_of_negatives = array_sum( $all_negatives );

                $value_of_errors = $sum_of_positives + $sum_of_negatives;
                $the_difference = $sum_of_positives - $sum_of_negatives;
                $sys_error  = (float) ( $the_difference / $total_questions );

                $diff_counter = abs( $neg_counters - $pos_counters );
                $mean_deviation = (float) round( ( $value_of_errors + ( $diff_counter * $sys_error ) ) / $total_questions, 2 );
                $quiz_result = ( $mean_deviation <= 5 && $mean_deviation >= -5 ) 
                ? '<strong style="color:green">Pass</strong>' : '<strong style="color:red">Fail</strong>';

                if( $mean_deviation <= 5 && $mean_deviation >= -5 && $total_questions > 10 ) {

                    learn_press_update_user_item_meta( $quiz_data->get_user_item_id(), 'grade',  'passed' );
                    ?>
                    <style>
                        .form-button-finish-course { display: inline-block; }                    
                        #learn-press-finish-course {
                            display: inline-block !important;
                        }
                    </style>
                    <?php
                }

            } else {
                $mean_deviation = 'Could not be calculated!';
            }
            
        ?>
        <?php if( is_array( $error_rating ) && !empty( $error_rating ) ) : ?>
        <li class="result-statistic-field" style="border-bottom: 2px solid #000;">
            <label><?php echo _x( 'Mean Deviation', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $mean_deviation; ?></p>
        </li>
        <li class="result-statistic-field" style="border-bottom: 2px solid #000;">
            <label><?php echo _x( 'Result', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $quiz_result; ?></p>
        </li>
        <?php endif; ?>
        <li class="result-statistic-field">
            <label><?php echo _x( 'Correct', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $result['question_correct']; ?></p>
        </li>
       <!--  <li class="result-statistic-field">
            <label><?php echo _x( 'Wrong', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $result['question_wrong']; ?></p>
        </li>
        <li class="result-statistic-field">
            <label><?php echo _x( 'Skipped', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $result['question_empty']; ?></p>
        </li>
        <li class="result-statistic-field">
            <label><?php echo _x( 'This is a test', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo $result['question_empty']; ?></p>
        </li>
        
        <li class="result-statistic-field">
            <label><?php echo _x( 'Total Negative Answers', 'quiz-result', 'learnpress' ); ?></label>
            <p><?php echo "{$result['pos_num']}"; ?></p>
        </li> -->

    </ul>

</div>