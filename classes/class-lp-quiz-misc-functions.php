<?php
class LearnPress_Quiz_Misc {

	public static function get_resit_course_enroll_button ( $course_id = 8764 ) {
    	ob_start();
   	?>
	    <form name="enroll-course" class="enroll-course" method="post" enctype="multipart/form-data">
		    <input type="hidden" name="enroll-course" value="<?php echo $course_id; ?>"/>
		    <input type="hidden" name="enroll-course-nonce"
		           value="<?php echo esc_attr( LP_Nonce_Helper::create_course( 'enroll' ) ); ?>"/>

		    <button type="submit" class="lp-button button button-enroll-course">
		        <?php echo esc_html( apply_filters( 'learn-press/enroll-course-button-text', __( 'Resit', 'learnpress' ) ) ); ?>
		    </button>
	   </form>
   <?php 
   		$enroll_btn = ob_get_clean();
   		return $enroll_btn;
 	}

}