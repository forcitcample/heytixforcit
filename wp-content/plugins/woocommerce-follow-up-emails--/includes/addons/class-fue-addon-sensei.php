<?php

/**
 * Class FUE_Addon_Sensei
 */
class FUE_Addon_Sensei {

    /**
     * class constructor
     */
    public function __construct() {
        add_filter( 'fue_email_types', array($this, 'register_email_type') );

        add_filter( 'fue_script_locale', array($this, 'inject_nonce_values') );

        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20 );
        //add_action( 'fue_email_form_after_interval', array($this, 'email_form'), 10, 3 );

        add_action( 'fue_email_form_scripts', array($this, 'email_form_script') );
        add_action( 'fue_email_form_submit_script', array($this, 'email_form_validation_script') );


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
        add_action( 'fue_before_variable_replacements', array($this, 'register_variable_replacements'), 10, 4 );
        add_filter( 'fue_email_sensei_variables', array($this, 'email_variables'), 10, 4 );
        add_filter( 'fue_email_sensei_replacements', array($this, 'email_replacements'), 10, 4 );

    }

    /**
     * Display a message on Sensei's frontend
     *
     * @param string $message
     * @since 4.1
     */
    public static function add_message( $message ) {
        global $woothemes_sensei;
        $woothemes_sensei->frontend->messages .= '<div class="woo-sc-box info">'. esc_html( $message ) .'</div>';
    }

    /**
     * Checks if WooThemes Sensei is installed and activated
     * @return bool True if Sensei is installed
     */
    public static function is_installed() {
        require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

        if ( is_multisite() ) {
            return is_plugin_active_for_network( 'woothemes-sensei/woothemes-sensei.php' );
        } else {
            return is_plugin_active( 'woothemes-sensei/woothemes-sensei.php' );
        }
        
    }

    /**
     * Register custom email type
     *
     * @param array $types
     * @return array
     */
    public function register_email_type( $types ) {
        $triggers = array(
            'specific_answer'        => __('after selecting a specific answer', 'follow_up_emails'),
            'course_signup'          => __('after signed up to a course', 'follow_up_emails'),
            'course_completed'       => __('after course is completed', 'follow_up_emails'),
            'lesson_start'           => __('after lesson is started', 'follow_up_emails'),
            'lesson_completed'       => __('after lesson is completed', 'follow_up_emails'),
            'quiz_completed'         => __('after completing a quiz', 'follow_up_emails'),
            'quiz_passed'            => __('after passing a quiz', 'follow_up_emails'),
            'quiz_failed'            => __('after failing a quiz', 'follow_up_emails')
        );

        $props = array(
            'label'                 => __('Sensei Emails', 'follow_up_emails'),
            'singular_label'        => __('Sensei Email', 'follow_up_emails'),
            'triggers'              => $triggers,
            'durations'             => Follow_Up_Emails::$durations,
            'long_description'      => __('Sensei emails will send to a user based upon the quiz/course/lesson/test status you define when creating your emails. Below are the existing Sensei emails set up for your store. Use the priorities to define which emails are most important. These emails are selected first when sending the email to the customer if more than one criteria is met by multiple emails. Only one email is sent out to the customer (unless you enable the Always Send option when creating your emails), so prioritizing the emails for occasions where multiple criteria are met ensures you send the right email to the right customer at the time you choose. <a href="admin.php?page=followup-emails-settings&tab=documentation">Learn More</a>', 'follow_up_emails'),
            'short_description'     => __('Sensei emails will send to a user based upon the quiz/course/lesson/test status you define when creating your emails.', 'follow_up_emails')
        );
        $types[] = new FUE_Email_Type( 'sensei', $props );

        return $types;
    }

    /**
     * HTML for email form
     *
     * @param array $values
     */
    public function email_form( $values ) {
        $course_id  = (isset($values['meta']['sensei_course_id'])) ? $values['meta']['sensei_course_id'] : '';
        $lesson_id  = (isset($values['meta']['sensei_lesson_id'])) ? $values['meta']['sensei_lesson_id'] : '';
        $quiz_id    = (isset($values['meta']['sensei_quiz_id'])) ? $values['meta']['sensei_quiz_id'] : '';
        $question_id= (isset($values['meta']['sensei_question_id'])) ? $values['meta']['sensei_question_id'] : '';
        $answer     = (isset($values['meta']['sensei_answer'])) ? $values['meta']['sensei_answer'] : '';

        include FUE_TEMPLATES_DIR .'/email-form/sensei/selectors.php';
    }

    /**
     * Register the custom meta-box for selecting the course, lesson or quiz
     */
    public function add_meta_boxes() {
        add_meta_box( 'fue-email-sensei', __( 'Enable For', 'follow-up-email' ), 'FUE_Addon_Sensei::email_form_meta_box', 'follow_up_email', 'side', 'default' );
    }

    /**
     * HTML for the email form meta-box
     * @param WP_Post $post
     */
    public static function email_form_meta_box( $post ) {
        $email = new FUE_Email( $post->ID );

        // load the categories
        $course_id  = (isset($email->meta['sensei_course_id'])) ? $email->meta['sensei_course_id'] : '';
        $lesson_id  = (isset($email->meta['sensei_lesson_id'])) ? $email->meta['sensei_lesson_id'] : '';
        $quiz_id    = (isset($email->meta['sensei_quiz_id'])) ? $email->meta['sensei_quiz_id'] : '';
        $question_id= (isset($email->meta['sensei_question_id'])) ? $email->meta['sensei_question_id'] : '';
        $answer     = (isset($email->meta['sensei_answer'])) ? $email->meta['sensei_answer'] : '';

        include FUE_TEMPLATES_DIR .'/email-form/sensei/selectors.php';
    }

    /**
     * Register sensei nonce for the email form
     * @param array $locale
     * @return array
     */
    public function inject_nonce_values( $locale ) {
        $locale['sensei_search_courses']    = wp_create_nonce("search-courses");
        $locale['sensei_search_lessons']    = wp_create_nonce("search-lessons");
        $locale['sensei_search_quizzes']    = wp_create_nonce("search-quizzes");
        $locale['sensei_search_questions']  = wp_create_nonce("search-questions");

        return $locale;
    }

    /**
     * JS for email form
     */
    public function email_form_script() {
        wp_enqueue_script( 'fue-form-sensei', FUE_TEMPLATES_URL .'/js/email-form-sensei.js' );
    }

    /**
     * JS for validating sensei emails
     *
     * @param array $values
     */
    public function email_form_validation_script( $values ) {
        if ( $values['type'] != 'sensei' )
            return;
        ?>
        if ( jQuery("#interval_type").val() == "specific_answer" && jQuery("select#question_id").val() == "" ) {
            jQuery("#question_id").parents(".field").addClass("fue-error");
            error = true;
        }

        <?php
    }

    /**
     * Queue emails after a user signs up to a course
     *
     * @param int $user_id
     * @param int $course_id
     */
    public function course_sign_up( $user_id, $course_id ) {

        $emails = fue_get_emails( 'sensei', FUE_Email::STATUS_ACTIVE, array(
            'meta_query' => array(
                array(
                    'key'   => '_interval_type',
                    'value' => 'course_signup'
                )
            )
        ) );

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

                    FUE_Sending_Scheduler::queue_email( $values, $email );

                }

                continue;

            }

            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('course_id' => $course_id)
            );

            FUE_Sending_Scheduler::queue_email( $values, $email );

        }
    }

    /**
     * Queue emails after a course has been completed
     *
     * @param int $user_id
     * @param int $course_id
     */
    public function course_completed( $user_id, $course_id ) {

        $emails = fue_get_emails( 'sensei', FUE_Email::STATUS_ACTIVE, array(
            'meta_query' => array(
                array(
                    'key'   => '_interval_type',
                    'value' => 'course_completed'
                )
            )
        ) );

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

                    FUE_Sending_Scheduler::queue_email( $values, $email );

                }

                continue;

            }

            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('course_id' => $course_id)
            );

            FUE_Sending_Scheduler::queue_email( $values, $email );
        }
    }

    /**
     * Queue emails after a lesson starts
     *
     * @param int $user_id
     * @param int $lesson_id
     */
    public function lesson_start( $user_id, $lesson_id ) {

        $emails = fue_get_emails( 'sensei', FUE_Email::STATUS_ACTIVE, array(
            'meta_query' => array(
                array(
                    'key'   => '_interval_type',
                    'value' => 'lesson_start'
                )
            )
        ) );

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

                    FUE_Sending_Scheduler::queue_email( $values, $email );

                }

                continue;

            } else {
                $values = array(
                    'user_id'   => $user_id,
                    'meta'      => array('lesson_id' => $lesson_id)
                );

                FUE_Sending_Scheduler::queue_email( $values, $email );

            }

        }
    }

    /**
     * Queue emails after a lesson ends
     *
     * @param int $user_id
     * @param int $lesson_id
     */
    public function lesson_end( $user_id, $lesson_id ) {

        $emails = fue_get_emails( 'sensei', FUE_Email::STATUS_ACTIVE, array(
            'meta_query' => array(
                array(
                    'key'   => '_interval_type',
                    'value' => 'lesson_completed'
                )
            )
        ) );

        foreach ( $emails as $email ) {

            $meta = maybe_unserialize( $email->meta );

            if ( is_array( $meta ) && isset( $meta['sensei_lesson_id'] ) && $meta['sensei_lesson_id'] > 0 ) {
                // Only queue if the selected lesson matches
                if ( $lesson_id == $meta['sensei_lesson_id'] ) {
                    $values = array(
                        'user_id'   => $user_id,
                        'meta'      => array('lesson_id' => $lesson_id)
                    );

                    FUE_Sending_Scheduler::queue_email( $values, $email );

                }

                continue;

            }

            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('lesson_id' => $lesson_id)
            );

            FUE_Sending_Scheduler::queue_email( $values, $email );
        }
    }

    /**
     * Queue emails after a quiz has been graded
     *
     * @param int $user_id
     * @param int $quiz_id
     * @param float $grade
     * @param float $passmark
     */
    public function quiz_grade( $user_id, $quiz_id, $grade, $passmark ) {
        global $wpdb;

        $triggers = array( 'quiz_completed', 'quiz_passed', 'quiz_failed' );
        $emails = fue_get_emails( 'sensei', FUE_Email::STATUS_ACTIVE, array(
            'meta_query' => array(
                array(
                    'key'       => '_interval_type',
                    'value'     => $triggers,
                    'compare'   => 'IN'
                )
            )
        ) );

        foreach ( $emails as $email ) {

            if ( $email->trigger == 'quiz_passed' && $grade < $passmark ) {
                // failed the quiz
                continue;
            }

            if ( $email->trigger == 'quiz_failed' && $grade >= $passmark ) {
                // passed the quiz
                continue;
            }

            $meta = maybe_unserialize( $email->meta );

            if ( is_array( $meta ) && isset( $meta['sensei_quiz_id'] ) && $meta['sensei_quiz_id'] > 0 ) {
                // Only queue if the selected lesson matches
                if ( $quiz_id == $meta['sensei_quiz_id'] ) {
                    $values = array(
                        'user_id'   => $user_id,
                        'meta'      => array('quiz_id' => $quiz_id, 'grade' => $grade, 'passmark' => $passmark)
                    );

                    FUE_Sending_Scheduler::queue_email( $values, $email );

                }

                continue;

            }

            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('quiz_id' => $quiz_id, 'grade' => $grade, 'passmark' => $passmark)
            );

            FUE_Sending_Scheduler::queue_email( $values, $email );
        }
    }

    /**
     * Check if a specific answer has been submitted
     *
     * @param array $args
     * @param array $data
     */
    public function check_for_answer( $args, $data ) {
        global $wpdb;

        if ( $args['type'] != 'sensei_user_answer' )
            return;

        $question_id = $args['post_id'];

        $emails = fue_get_emails( 'sensei', FUE_Email::STATUS_ACTIVE, array(
            'meta_query' => array(
                array(
                    'key'   => '_interval_type',
                    'value' => 'specific_answer'
                )
            )
        ) );

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
                if ( is_array( $posted_answer ) ) {
                    $posted_answer = current($posted_answer);
                }
                if ( $email_answer != $posted_answer )
                    continue;

                $values = array(
                    'user_id'   => $args['user_id'],
                    'meta'      => array('question_id' => $question_id, 'answer' => $posted_answer)
                );

                FUE_Sending_Scheduler::queue_email( $values, $email );

            }

        }

    }

    /**
     * List of available variables
     * @param FUE_Email $email
     */
    public function email_variables_list( $email ) {
        if ( $email->type != 'sensei' ) {
            return;
        }
        ?>
        <li class="var hideable var_sensei var_sensei_course"><strong>{course_name}</strong> <img class="help_tip" title="<?php _e('The name of the course', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL ; ?>/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_sensei var_sensei_lesson"><strong>{lesson_name}</strong> <img class="help_tip" title="<?php _e('The name of the course', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL ; ?>/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_sensei var_sensei_grade"><strong>{quiz_grade}</strong> <img class="help_tip" title="<?php _e('The score the user got on the quiz', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL ; ?>/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_sensei var_sensei_passmark"><strong>{quiz_passmark}</strong> <img class="help_tip" title="<?php _e('The passing mark on the quiz taken', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL ; ?>/images/help.png" width="16" height="16" /></li>
        <?php
    }

    /**
     * Register subscription variables to be replaced
     *
     * @param FUE_Sending_Email_Variables   $var
     * @param array                 $email_data
     * @param FUE_Email             $email
     * @param object                $queue_item
     */
    public function register_variable_replacements( $var, $email_data, $email, $queue_item ) {
        $variables = array(
            'course_name'   => '',
            'lesson_name'   => '',
            'quiz_grade'    => '',
            'quiz_passmark' => ''
        );

        // use test data if the test flag is set
        if ( isset( $email_data['test'] ) && $email_data['test'] ) {
            $variables = $this->add_test_variable_replacements( $variables, $email_data, $email );
        } else {
            $variables = $this->add_variable_replacements( $variables, $email_data, $queue_item, $email );
        }

        $var->register( $variables );
    }

    /**
     * Scan through the keys of $variables and apply the replacement if one is found
     * @param array     $variables
     * @param array     $email_data
     * @param object    $queue_item
     * @param FUE_Email $email
     * @return array
     */
    protected function add_variable_replacements( $variables, $email_data, $queue_item, $email ) {

        $meta = maybe_unserialize( $queue_item->meta );

        if ( $email->trigger == 'course_signup' || $email->trigger == 'course_completed' ) {
            $variables['course_name'] = get_the_title( $meta['course_id'] );
        } elseif ( $email->trigger == 'lesson_start' || $email->trigger == 'lesson_completed' ) {
            $course_id = get_post_meta( $meta['lesson_id'], '_lesson_course', true );
            $variables['course_name'] = get_the_title( $course_id );
            $variables['lesson_name'] = get_the_title( $meta['lesson_id'] );
        } elseif (
            $email->trigger == 'quiz_completed' ||
            $email->trigger == 'quiz_passed' ||
            $email->trigger == 'quiz_failed'
        ) {
            $lesson_id      = get_post_meta( $meta['quiz_id'], '_quiz_lesson', true );
            $course_id      = get_post_meta( $meta['quiz_id'], '_lesson_course', true );
            $variables['course_name']   = get_the_title( $course_id );
            $variables['lesson_name']   = get_the_title( $lesson_id );
            $variables['quiz_grade']    = $meta['grade'];
            $variables['quiz_passmark'] = $meta['passmark'];
        }

        return $variables;
    }

    /**
     * Add variable replacements for test emails
     *
     * @param array     $variables
     * @param array     $email_data
     * @param FUE_Email $email
     *
     * @return array
     */
    protected function add_test_variable_replacements( $variables, $email_data, $email ) {
        $variables['course_name']   = 'Test Course';
        $variables['lesson_name']   = 'Test Lesson';
        $variables['quiz_grade']    = 87;
        $variables['quiz_passmark'] = 90;

        return $variables;
    }

    /**
     * Get sensei courses
     * @param array $args
     *
     * @return array
     */
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

    /**
     * Get sensei lessons under the give $course_id
     *
     * @param int $course_id
     * @param array $args
     *
     * @return array
     */
    public static function get_lessons( $course_id = 0, $args = array() ) {
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

    /**
     * Get quizzes under the give $lesson_id
     * @param int $lesson_id
     * @param array $args
     *
     * @return array
     */
    public static function get_quizzes( $lesson_id = 0, $args = array() ) {
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

    /**
     * Get questions under the given $quiz_id
     *
     * @param int $quiz_id
     * @param array $args
     *
     * @return array
     */
    public static function get_questions( $quiz_id = 0, $args = array() ) {
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

$GLOBALS['fue_sensei'] = new FUE_Addon_Sensei();
