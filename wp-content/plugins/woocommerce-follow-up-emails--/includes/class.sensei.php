<?php

class FUE_Sensei {

    public function __construct() {
        add_filter( 'fue_trigger_types', array($this, 'trigger_types'), 10, 2 );
        add_filter( 'fue_email_types', array($this, 'email_types') );
        add_filter( 'fue_email_type_long_descriptions', array($this, 'email_type_long_descriptions') );
        add_filter( 'fue_email_type_short_descriptions', array($this, 'email_type_short_descriptions') );
        add_filter( 'fue_email_type_triggers', array($this, 'email_type_triggers') );

        add_filter( 'fue_email_type_is_valid', array($this, 'valid_type'), 10, 2 );

        add_action( 'fue_email_form_script', array($this, 'add_script') );
        add_action( 'fue_email_form_submit_script', array($this, 'validation_script') );

        add_action( 'fue_email_form_after_interval', array($this, 'email_form'), 10, 3 );

        add_action( 'wp_ajax_sensei_courses', array($this, 'search_courses') );
        add_action( 'wp_ajax_sensei_lessons', array($this, 'search_lessons') );
        add_action( 'wp_ajax_sensei_quizzes', array($this, 'search_quizzes') );
        add_action( 'wp_ajax_sensei_questions', array($this, 'search_questions') );

        // course
        add_action( 'sensei_user_course_start', array($this, 'course_sign_up'), 10, 2 );
        add_action( 'sensei_user_course_end', array($this, 'course_completed'), 10, 2 );

        // lesson
        add_action( 'sensei_user_lesson_start', array($this, 'lesson_start'), 10, 2 );
        add_action( 'sensei_user_lesson_end', array($this, 'lesson_end'), 10, 2 );

        // quiz score
        add_action( 'sensei_user_quiz_grade', array($this, 'quiz_grade'), 10, 4 );

        // specific answer action
        add_action( 'sensei_log_activity_after', array($this, 'check_for_answer'), 10, 2 );

        // email form variables
        add_action( 'fue_email_variables_list', array($this, 'email_variables_list') );

        // variable replacements
        add_filter( 'fue_email_sensei_variables', array($this, 'email_variables'), 10, 4 );
        add_filter( 'fue_email_sensei_replacements', array($this, 'email_replacements'), 10, 4 );

    }

    public function trigger_types( $triggers = array(), $email_type = '' ) {
        $triggers['specific_answer']        = __('after selecting a specific answer', 'follow_up_emails');
        $triggers['course_signup']          = __('after signed up to a course', 'follow_up_emails');
        $triggers['course_completed']       = __('after course is completed', 'follow_up_emails');
        $triggers['lesson_start']           = __('after lesson is started', 'follow_up_emails');
        $triggers['lesson_completed']       = __('after lesson is completed', 'follow_up_emails');
        $triggers['quiz_completed']         = __('after completing a quiz', 'follow_up_emails');
        $triggers['quiz_passed']            = __('after passing a quiz', 'follow_up_emails');
        $triggers['quiz_failed']            = __('after failing a quiz', 'follow_up_emails');

        return $triggers;

    }

    public function email_types( $types ) {
        $types['sensei']    = __('Sensei Email', 'follow_up_emails');

        return $types;
    }

    public function email_type_long_descriptions( $descriptions ) {
        $descriptions['sensei']    = __('Sensei emails will send to a user based upon the quiz/course/lesson/test status you define when creating your emails. Below are the existing Sensei emails set up for your store. Use the priorities to define which emails are most important. These emails are selected first when sending the email to the customer if more than one criteria is met by multiple emails. Only one email is sent out to the customer (unless you enable the Always Send option when creating your emails), so prioritizing the emails for occasions where multiple criteria are met ensures you send the right email to the right customer at the time you choose.', 'follow_up_emails');

        return $descriptions;
    }

    public function email_type_short_descriptions( $descriptions ) {
        $descriptions['sensei']    = __('Sensei emails will send to a user based upon the quiz/course/lesson/test status you define when creating your emails.', 'follow_up_emails');

        return $descriptions;
    }

    public function email_type_triggers( $type_triggers ) {
        $type_triggers['sensei'] = array(
            'specific_answer', 'course_signup', 'course_completed',
            'lesson_start', 'lesson_completed',
            'quiz_completed', 'quiz_passed', 'quiz_failed'
        );

        return $type_triggers;
    }

    public function valid_type($valid, $data) {
        if ($data['type'] == 'sensei') {
            $valid = true;
        }

        return $valid;
    }

    public function add_script() {
        ?>
        jQuery("body").bind("fue_email_type_changed", function(evt, type) {
            sensei_toggle_fields( type );
        });

        jQuery("body").bind("fue_interval_type_changed", function(evt, type) {
            sensei_toggle_interval_type_fields( type );
        });

        function sensei_toggle_interval_type_fields( type ) {
            var show = [];
            var hide = ['.sensei-courses', '.sensei-lessons', '.sensei-quizzes', '.sensei-answers'];

            switch (type) {
                case 'course_signup':
                case 'course_completed':
                    show = ['.sensei-courses'];
                    break;

                case 'lesson_start':
                case 'lesson_signup':
                case 'lesson_completed':
                    show = ['.sensei-lessons'];
                    break;

                case 'quiz_completed':
                case 'quiz_failed':
                case 'quiz_passed':
                    show = ['.sensei-quizzes'];
                    break;

                case 'specific_answer':
                    show = ['.sensei-answers'];
                    break;
            }

            for (x = 0; x < hide.length; x++) {
                jQuery(hide[x]).hide();
            }

            for (x = 0; x < show.length; x++) {
                jQuery(show[x]).show();
            }

        }

        function sensei_toggle_fields( type ) {
            if (type == "sensei") {
                var val  = jQuery("#interval_type").val();
                var show = ['.sensei'];
                var hide = ['.interval_type_option', '.always_send_tr', '.signup_description', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.custom_field_tr', '.var_item_name', '.var_item_category', '.var_item_names', '.var_item_categories', '.var_item_name', '.var_item_category', '.interval_type_after_last_purchase', '.interval_duration_date', '.var_customer', '.var_order'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }

                for (x = 0; x < show.length; x++) {
                    jQuery(show[x]).show();
                }

                // triggers
                jQuery(".interval_type_option").remove();

                if ( email_intervals && email_intervals.sensei.length > 0 ) {
                    for (var x = 0; x < email_intervals.sensei.length; x++) {
                        var int_key = email_intervals.sensei[x];
                        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ int_key +'" id="interval_type_option_'+ int_key +'" value="'+ int_key +'">'+ interval_types[int_key] +'</option>');
                    }

                    jQuery("#interval_type").val(val);
                }

                jQuery("option.interval_duration_date").attr("disabled", true);

                jQuery(".interval_duration_date").hide();

                jQuery("#interval_type").change();
            } else {
                var hide = ['.course_tr', '.sensei'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }
            }
        }

        jQuery(document).ready(function() {
            sensei_toggle_fields( jQuery("#email_type").val() );

            jQuery("select.sensei_courses").ajaxChosen({
                method:     'GET',
                url:        ajaxurl,
                dataType:   'json',
                afterTypeDelay: 100,
                data:       {
                    action:         'sensei_courses',
                    security:       '<?php echo wp_create_nonce("search-courses"); ?>'
                }
            }, function (data) {
                var terms = {};

                jQuery.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });

            jQuery("select.sensei_lessons").ajaxChosen({
                method:     'GET',
                url:        ajaxurl,
                dataType:   'json',
                afterTypeDelay: 100,
                data:       {
                    action:         'sensei_lessons',
                    security:       '<?php echo wp_create_nonce("search-lessons"); ?>'
                }
            }, function (data) {
                var terms = {};

                jQuery.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });

            jQuery("select.sensei_quizzes").ajaxChosen({
                method:     'GET',
                url:        ajaxurl,
                dataType:   'json',
                afterTypeDelay: 100,
                data:       {
                    action:         'sensei_quizzes',
                    security:       '<?php echo wp_create_nonce("search-quizzes"); ?>'
                }
            }, function (data) {
                var terms = {};

                jQuery.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });

            jQuery("select.sensei_questions").ajaxChosen({
                method:     'GET',
                url:        ajaxurl,
                dataType:   'json',
                afterTypeDelay: 100,
                data:       {
                    action:         'sensei_questions',
                    security:       '<?php echo wp_create_nonce("search-questions"); ?>'
                }
            }, function (data) {
                var terms = {};

                jQuery.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });
        });
        <?php
    }

    public function validation_script( $values ) {
        if ( $values['type'] != 'sensei' )
            return;
        ?>
        if ( jQuery("#interval_type").val() == "specific_answer" && jQuery("select#question_id").val() == "" ) {
            jQuery("#question_id").parents(".field").addClass("fue-error");
            error = true;
        }

        <?php
    }

    public function email_form( $values ) {
        $course_id  = (isset($values['meta']['sensei_course_id'])) ? $values['meta']['sensei_course_id'] : '';
        $lesson_id  = (isset($values['meta']['sensei_lesson_id'])) ? $values['meta']['sensei_lesson_id'] : '';
        $quiz_id    = (isset($values['meta']['sensei_quiz_id'])) ? $values['meta']['sensei_quiz_id'] : '';
        $question_id= (isset($values['meta']['sensei_question_id'])) ? $values['meta']['sensei_question_id'] : '';
        $answer     = (isset($values['meta']['sensei_answer'])) ? $values['meta']['sensei_answer'] : '';
        ?>

        <div class="sensei sensei-courses">
            <div class="field non-generic non-signup hideable <?php do_action('fue_form_course_tr_class', $values); ?> course_tr">
                <label for="course_id"><?php _e('Course', 'follow_up_emails'); ?></label>
                <select id="course_id" name="meta[sensei_course_id]" class="chzn-select sensei_courses" data-placeholder="<?php _e('Search for a course&hellip;', 'woocommerce'); ?>" style="width: 400px">
                    <option value=""><?php _e('Any Course', 'follow_up_emails'); ?></option>
                    <?php if ( $course_id ): ?>
                        <option value="<?php echo $course_id; ?>" selected><?php echo get_the_title( $course_id ); ?></option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="sensei sensei-lessons">
            <div class="field non-generic non-signup hideable <?php do_action('fue_form_lesson_tr_class', $values); ?> lesson_tr">
                <label for="lesson_id"><?php _e('Lesson', 'follow_up_emails'); ?></label>
                <select id="lesson_id" name="meta[sensei_lesson_id]" class="chzn-select sensei_lessons" data-placeholder="<?php _e('Search for a lesson&hellip;', 'woocommerce'); ?>" style="width: 400px">
                    <option value=""><?php _e('Any Lesson', 'follow_up_emails'); ?></option>
                    <?php if ( $lesson_id ): ?>
                        <option value="<?php echo $lesson_id; ?>" selected><?php echo get_the_title( $lesson_id ); ?></option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="sensei sensei-quizzes">
            <div class="field non-generic non-signup hideable <?php do_action('fue_form_quiz_tr_class', $values); ?> quiz_tr">
                <label for="quiz_id"><?php _e('Quiz', 'follow_up_emails'); ?></label>
                <select id="quiz_id" name="meta[sensei_quiz_id]" class="chzn-select sensei_quizzes" data-placeholder="<?php _e('Search for a quiz&hellip;', 'woocommerce'); ?>" style="width: 400px">
                    <option value=""><?php _e('Any Quiz', 'follow_up_emails'); ?></option>
                    <?php if ( $quiz_id ): ?>
                        <option value="<?php echo $quiz_id; ?>" selected><?php echo get_the_title( $quiz_id ); ?></option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="sensei sensei-answers">
            <div class="field non-generic non-signup hideable <?php do_action('fue_form_question_tr_class', $values); ?> question_tr">
                <label for="question_id"><?php _e('Question', 'follow_up_emails'); ?></label>
                <select id="question_id" name="meta[sensei_question_id]" class="chzn-select sensei_questions" style="width: 400px;">
                    <option value=""><?php _e('Search for a Question', 'follow_up_emails'); ?></option>
                    <?php if ( $question_id ): ?>
                    <option value="<?php echo $question_id; ?>" selected><?php echo get_the_title( $question_id ); ?></option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="field non-generic non-signup hideable <?php do_action('fue_form_answer_tr_class', $values); ?> answer_tr">
                <label for="answer"><?php _e('Answer', 'follow_up_emails'); ?></label>
                <input type="text" class="input-text" name="meta[sensei_answer]" id="answer" value="<?php echo esc_attr($answer); ?>" style="width: 400px;" />
            </div>
        </div>

    <?php
    }

    public function search_courses() {
        ob_start();

        check_ajax_referer( 'search-courses', 'security' );

        $term = (string) wc_clean( stripslashes( $_GET['term'] ) );

        if ( empty( $term ) ) {
            die();
        }

        $args = array(
            'post_type'      => 'course',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            's'              => $term,
            'fields'         => 'ids'
        );

        $posts = get_posts( $args );

        $found_products = array();

        if ( $posts ) {
            foreach ( $posts as $post ) {
                $found_products[ $post ] = get_the_title( $post );
            }
        }

        wp_send_json( $found_products );
    }

    public function search_lessons() {
        ob_start();

        check_ajax_referer( 'search-lessons', 'security' );

        $term = (string) wc_clean( stripslashes( $_GET['term'] ) );

        if ( empty( $term ) ) {
            die();
        }

        $args = array(
            'post_type'      => 'lesson',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            's'              => $term,
            'fields'         => 'ids'
        );

        $posts = get_posts( $args );

        $found_products = array();

        if ( $posts ) {
            foreach ( $posts as $post ) {
                $found_products[ $post ] = get_the_title( $post );
            }
        }

        wp_send_json( $found_products );
    }

    public function search_quizzes() {
        ob_start();

        check_ajax_referer( 'search-quizzes', 'security' );

        $term = (string) wc_clean( stripslashes( $_GET['term'] ) );

        if ( empty( $term ) ) {
            die();
        }

        $args = array(
            'post_type'      => 'quiz',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            's'              => $term,
            'fields'         => 'ids'
        );

        $posts = get_posts( $args );

        $found_products = array();

        if ( $posts ) {
            foreach ( $posts as $post ) {
                $found_products[ $post ] = get_the_title( $post );
            }
        }

        wp_send_json( $found_products );
    }

    public function search_questions() {
        ob_start();

        check_ajax_referer( 'search-questions', 'security' );

        $term = (string) wc_clean( stripslashes( $_GET['term'] ) );

        if ( empty( $term ) ) {
            die();
        }

        $args = array(
            'post_type'      => 'question',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            's'              => $term,
            'fields'         => 'ids'
        );

        $posts = get_posts( $args );

        $found_products = array();

        if ( $posts ) {
            foreach ( $posts as $post ) {
                $found_products[ $post ] = get_the_title( $post );
            }
        }

        wp_send_json( $found_products );
    }

    public function course_sign_up( $user_id, $course_id ) {
        global $wpdb;

        $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'course_signup' AND status = ". FUE::STATUS_ACTIVE);

        foreach ( $emails as $email ) {
            $meta = maybe_unserialize( $email->meta );

            if ( is_array( $meta ) && isset( $meta['sensei_course_id'] ) && $meta['sensei_course_id'] > 0 ) {
                // A specific course has been selected for this email.
                // Only queue if the course signed up for matches with the selected course
                if ( $course_id == $meta['sensei_course_id'] ) {
                    $values = array(
                        'user_id'   => $user_id,
                        'meta'      => array('course_id' => $course_id)
                    );

                    FUE::queue_email( $values, $email );

                }

                continue;

            }

            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('course_id' => $course_id)
            );

            FUE::queue_email( $values, $email );

        }
    }

    public function course_completed( $user_id, $course_id ) {
        global $wpdb;

        $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'course_completed'");

        foreach ( $emails as $email ) {

            $meta = maybe_unserialize( $email->meta );

            if ( is_array( $meta ) && isset( $meta['sensei_course_id'] ) && $meta['sensei_course_id'] > 0 ) {
                // A specific course has been selected for this email.
                // Only queue if the completed course matches with the selected course
                if ( $course_id == $meta['sensei_course_id'] ) {
                    $values = array(
                        'user_id'   => $user_id,
                        'meta'      => array('course_id' => $course_id)
                    );

                    FUE::queue_email( $values, $email );

                }

                continue;

            }

            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('course_id' => $course_id)
            );

            FUE::queue_email( $values, $email );
        }
    }

    public function lesson_start( $user_id, $lesson_id ) {
        global $wpdb;

        $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'lesson_start' AND status = ". FUE::STATUS_ACTIVE);

        foreach ( $emails as $email ) {

            $meta = maybe_unserialize( $email->meta );

            if ( is_array( $meta ) && isset( $meta['sensei_lesson_id'] ) && $meta['sensei_lesson_id'] > 0 ) {
                // A specific lesson has been selected for this email.
                // Only queue if the lesson started matches the selected lesson
                if ( $lesson_id == $meta['sensei_lesson_id'] ) {
                    $values = array(
                        'user_id'   => $user_id,
                        'meta'      => array('lesson_id' => $lesson_id)
                    );

                    FUE::queue_email( $values, $email );

                }

                continue;

            }

            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('lesson_id' => $lesson_id)
            );

            FUE::queue_email( $values, $email );
        }
    }

    public function lesson_end( $user_id, $lesson_id ) {
        global $wpdb;

        $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'lesson_completed' AND status = ". FUE::STATUS_ACTIVE);

        foreach ( $emails as $email ) {

            $meta = maybe_unserialize( $email->meta );

            if ( is_array( $meta ) && isset( $meta['sensei_lesson_id'] ) && $meta['sensei_lesson_id'] > 0 ) {
                // Only queue if the selected lesson matches
                if ( $lesson_id == $meta['sensei_lesson_id'] ) {
                    $values = array(
                        'user_id'   => $user_id,
                        'meta'      => array('lesson_id' => $lesson_id)
                    );

                    FUE::queue_email( $values, $email );

                }

                continue;

            }

            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('lesson_id' => $lesson_id)
            );

            FUE::queue_email( $values, $email );
        }
    }

    public function quiz_grade( $user_id, $quiz_id, $grade, $passmark ) {
        global $wpdb;

        $types = "'quiz_completed'";

        if ( $grade >= $passmark ) {
            $types .= ",'quiz_passed'";
        } else {
            $types .= ",'quiz_failed'";
        }

        $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type IN ($types) AND status = ". FUE::STATUS_ACTIVE);

        foreach ( $emails as $email ) {

            $meta = maybe_unserialize( $email->meta );

            if ( is_array( $meta ) && isset( $meta['sensei_quiz_id'] ) && $meta['sensei_quiz_id'] > 0 ) {
                // Only queue if the selected lesson matches
                if ( $quiz_id == $meta['sensei_quiz_id'] ) {
                    $values = array(
                        'user_id'   => $user_id,
                        'meta'      => array('quiz_id' => $quiz_id, 'grade' => $grade, 'passmark' => $passmark)
                    );

                    FUE::queue_email( $values, $email );

                }

                continue;

            }

            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('quiz_id' => $quiz_id, 'grade' => $grade, 'passmark' => $passmark)
            );

            FUE::queue_email( $values, $email );
        }
    }

    public function check_for_answer( $args, $data ) {
        global $wpdb;

        if ( $args['type'] != 'sensei_user_answer' )
            return;

        $question_id = $args['post_id'];

        $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'specific_answer' AND status = ". FUE::STATUS_ACTIVE);

        foreach ( $emails as $email ) {

            $meta = maybe_unserialize( $email->meta );

            if ( is_array( $meta ) ) {

                $email_question_id  = (isset( $meta['sensei_question_id'] ) ) ? $meta['sensei_question_id'] : '';
                $email_answer       = (isset( $meta['sensei_answer']) ) ? $meta['sensei_answer'] : '';

                // The answer to check for is required
                if ( empty( $email_answer ) )
                    continue;

                // Question IDs must match
                if ( $email_question_id != $question_id )
                    continue;

                $posted_answer = maybe_unserialize( base64_decode($args['data']) );

                // answers do not match
                if ( $email_answer != $posted_answer )
                    continue;

                $values = array(
                    'user_id'   => $args['user_id'],
                    'meta'      => array('question_id' => $question_id, 'answer' => $posted_answer)
                );

                FUE::queue_email( $values, $email );

            }

        }

    }

    public function email_variables_list( $defaults ) {
        switch ( $defaults['interval_type'] ) {

            case 'course_signup':
            case 'course_completed':
                echo '<li class="var hideable var_sensei var_sensei_course"><strong>{course_name}</strong> <img class="help_tip" title="'. __('The name of the course', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                break;

            case 'lesson_start':
            case 'lesson_completed':
                echo '<li class="var hideable var_sensei var_sensei_course"><strong>{course_name}</strong> <img class="help_tip" title="'. __('The name of the course', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                echo '<li class="var hideable var_sensei var_sensei_lesson"><strong>{lesson_name}</strong> <img class="help_tip" title="'. __('The name of the course', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                break;

            case 'quiz_completed':
            case 'quiz_passed':
            case 'quiz_failed':
                echo '<li class="var hideable var_sensei var_sensei_course"><strong>{course_name}</strong> <img class="help_tip" title="'. __('The name of the course', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                echo '<li class="var hideable var_sensei var_sensei_lesson"><strong>{lesson_name}</strong> <img class="help_tip" title="'. __('The name of the course', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                echo '<li class="var hideable var_sensei var_sensei_grade"><strong>{quiz_grade}</strong> <img class="help_tip" title="'. __('The score the user got on the quiz', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                echo '<li class="var hideable var_sensei var_sensei_passmark"><strong>{quiz_passmark}</strong> <img class="help_tip" title="'. __('The passing mark on the quiz taken', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                break;
        }

    }

    public function email_variables( $vars, $email_data, $email_order, $email ) {
        switch ( $email->interval_type ) {

            case 'course_signup':
            case 'course_completed':
                $vars[] = '{course_name}';
                break;

            case 'lesson_signup':
            case 'lesson_completed':
                $vars[] = '{course_name}';
                $vars[] = '{lesson_name}';
                break;

            case 'quiz_completed':
            case 'quiz_passed':
            case 'quiz_failed':
                $vars[] = '{course_name}';
                $vars[] = '{lesson_name}';
                $vars[] = '{quiz_grade}';
                $vars[] = '{quiz_passmark}';
                break;

        }

        $vars = array_merge($vars, array('{customer_username}', '{customer_first_name}', '{customer_name}', '{customer_email}') );

        return $vars;
    }

    public function email_replacements( $replacements, $email_data, $email_order, $email ) {

        if ( $email->email_type == 'sensei' ) {

            $meta = maybe_unserialize( $email_order->meta );

            if ( $email->interval_type == 'course_signup' || $email->interval_type == 'course_completed' ) {
                $replacements[] = get_the_title( $meta['course_id'] );
            } elseif ( $email->interval_type == 'lesson_signup' || $email->interval_type == 'lesson_completed' ) {
                $course_id      = get_post_meta( $meta['lesson_id'], '_lesson_course', true );
                $replacements[] = get_the_title( $course_id );
                $replacements[] = get_the_title( $meta['lesson_id'] );
            } elseif ( $email->interval_type == 'quiz_completed' || $email->interval_type == 'quiz_passed' || $email->interval_type == 'quiz_failed' ) {
                $lesson_id      = get_post_meta( $meta['quiz_id'], '_quiz_lesson', true );
                $course_id      = get_post_meta( $meta['quiz_id'], '_lesson_course', true );
                $replacements[] = get_the_title( $course_id );
                $replacements[] = get_the_title( $lesson_id );
                $replacements[] = $meta['grade'];
                $replacements[] = $meta['passmark'];
            }

            $replacements[] = $email_data['username'];
            $replacements[] = $email_data['first_name'];
            $replacements[] = $email_data['cname'];
            $replacements[] = $email_data['email_to'];
        }



        return $replacements;
    }

    public static function get_courses( $args = array() ) {
        $default = array(
            'post_type'     => 'course',
            'posts_per_page'=> -1,
            'post_status'   => array('publish', 'private', 'draft'),
            'tax_query'			=> array(
                array(
                    'taxonomy'	=> 'product_type',
                    'field'		=> 'slug',
                    'terms'		=> array( 'variable', 'grouped' ),
                    'operator'	=> 'NOT IN'
                )
            )
        );
        $args = array_merge( $default, $args );

        return get_posts( $args );
    }

    public static function get_lessons( $course_id = '', $args = array() ) {
        $default = array(
            'post_type'     => 'lesson',
            'posts_per_page'=> -1,
            'post_status'   => array('publish', 'private', 'draft')
        );

        if ( $course_id ) {
            $default['meta_query'][] = array(
                'meta_key'      => '_lesson_course',
                'meta_value'    => $course_id
            );
        }

        $args = array_merge( $default, $args );

        return get_posts( $args );
    }

    public static function get_quizzes( $lesson_id = '', $args = array() ) {
        $default = array(
            'post_type'     => 'quiz',
            'posts_per_page'=> -1,
            'post_status'   => array('publish', 'private', 'draft')
        );

        if ( $lesson_id ) {
            $default['meta_query'][] = array(
                'meta_key'      => '_quiz_lesson',
                'meta_value'    => $lesson_id
            );
        }

        $args = array_merge( $default, $args );

        return get_posts( $args );
    }

    public static function get_questions( $quiz_id = '', $args = array() ) {
        $default = array(
            'post_type'     => 'question',
            'posts_per_page'=> -1,
            'post_status'   => array('publish', 'private', 'draft')
        );

        if ( $quiz_id ) {
            $default['meta_query'][] = array(
                'meta_key'      => '_quiz_id',
                'meta_value'    => $quiz_id
            );
        }

        $args = array_merge( $default, $args );

        return get_posts( $args );
    }

}

$GLOBALS['fue_sensei'] = new FUE_Sensei();
