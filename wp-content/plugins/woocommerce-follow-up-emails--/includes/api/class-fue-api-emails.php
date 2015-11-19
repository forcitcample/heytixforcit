<?php
/**
 * FUE API Emails Class
 *
 * Handles requests to the /emails endpoint
 *
 * @author      75nineteen
 * @since       4.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class FUE_API_Emails extends FUE_API_Resource {

    /** @var string $base the route base */
    protected $base = '/emails';

    /**
     * Register the routes for this class
     *
     * GET /emails
     *
     * @since 4.1
     * @param array $routes
     * @return array
     */
    public function register_routes( $routes ) {

        # GET /emails
        $routes[ $this->base ] = array(
            array( array( $this, 'get_emails' ),    FUE_API_Server::READABLE ),
            array( array( $this, 'create_email' ),  FUE_API_SERVER::CREATABLE | FUE_API_Server::ACCEPT_DATA ),
        );

        # GET /emails/<id>
        $routes[ $this->base . '/(?P<id>\d+)' ] = array(
            array( array( $this, 'get_email' ),    FUE_API_SERVER::READABLE ),
            array( array( $this, 'edit_email' ),   FUE_API_SERVER::EDITABLE | FUE_API_SERVER::ACCEPT_DATA ),
            array( array( $this, 'delete_email' ), FUE_API_SERVER::DELETABLE ),
        );

        # GET /emails/templates
        $routes[ $this->base . '/templates' ] = array(
            array( array( $this, 'get_templates' ), FUE_API_Server::READABLE )
        );

        return $routes;
    }

    /**
     * Get a simple listing of available emails
     *
     * @since 4.1
     * @param array $filter
     * @param int $page
     * @return array
     */
    public function get_emails( $filter = array(), $page = 1 ) {
        $filter['page']         = $page;
        $filter['limit']        = ( !empty( $filter['limit'] ) ) ? absint( $filter['limit'] ) : get_option('posts_per_page');
        $filter['post_status']  = array( FUE_Email::STATUS_ACTIVE, FUE_Email::STATUS_ARCHIVED, FUE_Email::STATUS_INACTIVE );
        $filter['posts_per_page'] = $filter['limit'];
        unset( $filter['limit'] );

        if ( !empty( $filter['type'] ) ) {
            $filter['tax_query'][] = array(
                'taxonomy'  => 'follow_up_email_type',
                'field'     => 'slug',
                'terms'     => $filter['type']
            );
            unset($filter['type']);
        }

        if ( !empty( $filter['campaign'] ) ) {
            $campaign = $filter['campaign'];
            unset($filter['campaign']);

            $filter['tax_query'][] = array(
                'taxonomy'  => 'follow_up_email_campaign',
                'field'     => 'slug',
                'terms'     => $campaign
            );
        }

        if ( !empty( $filter['status'] ) ) {
            $filter['post_status'] = $this->fix_status_string( $filter['status'] );
            unset( $filter['status'] );
        } else {

        }

        $filter['fields'] = 'ids';

        /**
         * fue_get_emails is not really useful here since it uses get_posts
         * and we need to get the total number of rows that only WP_Query provides
         */
        $filter['nopaging'] = false;
        $filter['post_type'] = 'follow_up_email';

        $query  = new WP_Query( $filter );
        $result = array();

        foreach ( $query->posts as $email_id ) {
            $result[] = $this->get_email( $email_id );
        }

        // set the pagination data
        $query = array(
            'page'        => $page,
            'single'      => count( $query->posts ) == 1,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages
        );
        $this->server->add_pagination_headers( $query );

        return $result;

    }

    /**
     * Get a single email
     *
     * @since 4.1
     * @param int $id
     * @param array $fields
     * @return array
     */
    public function get_email( $id, $fields = array() ) {
        // validate the email ID
        $id = $this->validate_request( $id, 'follow_up_email', 'read' );

        // Return the validate error.
        if ( is_wp_error( $id ) ) {
            return $id;
        }

        $email = new FUE_Email( $id );

        $email_data = array(
            'id'            => $email->id,
            'created_at'    => $email->post->post_date,
            'type'          => $email->get_type(),
            'template'      => $email->template,
            'name'          => $email->name,
            'subject'       => $email->subject,
            'message'       => $email->message,
            'status'        => $this->fix_status_string( $email->status, true ),
            'trigger'       => $email->trigger,
            'trigger_string'=> $email->get_trigger_string(),
            'interval'      => $email->interval,
            'duration'      => $email->duration,
            'always_send'   => $email->always_send,
            'product_id'    => $email->product_id,
            'category_id'   => $email->category_id,
            'campaigns'     => wp_get_object_terms( $email->id, 'follow_up_email_campaign', array('fields' => 'slugs') )
        );

        return array( 'email' => apply_filters( 'fue_api_email_response', $email_data, $email, $fields, $this->server ) );
    }

    /**
     * Create a new follow-up email
     *
     * @since 4.1
     * @param array $data
     * @return array
     * @throws FUE_API_Exception
     */
    public function create_email( $data ) {

        $data   = apply_filters( 'fue_api_create_email_data', $data, $this );

        if ( !empty( $data['status'] ) ) {
            $data['status'] = $this->fix_status_string( $data['status'] );
        }

        $id = fue_create_email( $data );

        // Checks for an error in the email creation.
        if ( is_wp_error( $id ) ) {
            throw new FUE_API_Exception( 'fue_api_cannot_create_email', $id->get_error_message(), 400 );
        }

        do_action( 'fue_api_created_email', $id, $data );

        $this->server->send_status( 201 );

        return $this->get_email( $id );
    }

    /**
     * Edit an email
     *
     * @since 4.1
     * @param int $id
     * @param array $data
     * @return array
     * @throws FUE_API_Exception
     */
    public function edit_email( $id, $data ) {
        // validate the email ID
        $id = $this->validate_request( $id, 'follow_up_email', 'edit' );

        // Return the validate error.
        if ( is_wp_error( $id ) ) {
            return $id;
        }

        if ( !isset($data['id']) && !isset($data['ID']) ) {
            $data['id'] = $id;
        }

        if ( !empty( $data['status'] ) ) {
            $data['status'] = $this->fix_status_string( $data['status'] );
        }

        $data = apply_filters( 'fue_api_edit_email_data', $data, $this );

        $id = fue_update_email( $data );

        // Checks for an error in the email creation.
        if ( is_wp_error( $id ) ) {
            throw new FUE_API_Exception( 'fue_api_cannot_edit_email', $id->get_error_message(), 400 );
        }

        do_action( 'fue_api_edited_email', $id, $data );

        $this->server->send_status( 201 );

        return $this->get_email( $id );
    }

    /**
     * Delete an email
     * @param int $id
     * @return array
     */
    public function delete_email( $id ) {
        $id = $this->validate_request( $id, 'follow_up_email', 'delete' );

        // Return the validate error.
        if ( is_wp_error( $id ) ) {
            return $id;
        }

        do_action( 'fue_api_delete_email', $id, $this );

        return $this->delete( $id, 'follow_up_email' );
    }

    /**
     * Get a list of the installed templates
     * @return array
     */
    public function get_templates() {
        $templates  = fue_get_installed_templates();
        $output     = array();

        foreach ( $templates as $template ) {
            $tpl = new FUE_Email_Template( $template );

            $output[] = array(
                'template' => array(
                    'id'        => basename( $template ),
                    'name'      => $tpl->name,
                    'sections'  => $tpl->get_sections()
                )
            );
        }

        return $output;
    }

    /**
     * Normalize the status string by appending the 'fue-' prefix if necessary
     *
     * @param string $status
     * @param bool $trim Whether to trim or append the 'fue-' prefix
     * @return string
     */
    protected function fix_status_string( $status, $trim = false ) {
        if ( $trim ) {
            $status = ltrim( $status, 'fue-' );
        } else {
            if ( strpos( $status, 'fue-' ) === false )
                $status = 'fue-'. $status;
        }


        return $status;
    }
}
