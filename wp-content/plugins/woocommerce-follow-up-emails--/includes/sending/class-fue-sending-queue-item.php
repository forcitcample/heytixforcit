<?php

/**
 * Class FUE_Sending_Queue_Item
 */
class FUE_Sending_Queue_Item {

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $email_id = 0;

    /**
     * @var int
     */
    public $user_id = 0;

    /**
     * @var string
     */
    public $user_email = '';

    /**
     * @var int
     */
    public $order_id = 0;

    /**
     * @var int
     */
    public $product_id = 0;

    /**
     * @var int
     */
    public $send_on = '';

    /**
     * @var int
     */
    public $is_cart = 0;

    /**
     * @var int
     */
    public $is_sent = 0;

    /**
     * @var string
     */
    public $date_sent = '';

    /**
     * @var string
     */
    public $email_trigger = '';

    /**
     * @var array
     */
    public $meta = array();

    /**
     * @var int
     */
    public $status = 1;

    /**
     * Class constructor. Load a queue item row based on the given $id
     * @param int $id
     */
    public function __construct( $id = null ) {
        if ( !is_null( $id ) ) {
            $this->populate( $id );
        }
    }

    public function populate( $id ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT *
            FROM {$wpdb->prefix}followup_email_orders
            WHERE id = %d",
            $id
        ), ARRAY_A );

        if ( $row ) {
            foreach ( $row as $key => $value ) {

                if ( $key == 'meta' ) {
                    $value = maybe_unserialize( $value );
                    $value = maybe_unserialize( $value );
                }

                $this->$key = $value;
            }
        }
    }

    /**
     * Check if the queue item exists in the database
     * @return bool
     */
    public function exists() {
        if ( !$this->id ) {
            return false;
        }

        $items = Follow_Up_Emails::instance()->scheduler->get_items( array(
            'id'    => $this->id
        ) );

        if ( count( $items ) == 0 ) {
            return false;
        }

        return true;
    }

    /**
     * Append a note to the queue item
     * @param string $message
     */
    public function add_note( $message ) {

        // do not add if queue item doesn't exist (deleted)
        if ( !$this->exists() ) {
            return;
        }

        $this->populate( $this->id );

        $this->meta = maybe_unserialize( $this->meta );

        $note = array(
            'date'      => current_time('mysql'),
            'message'   => $message
        );

        if ( !isset( $this->meta['notes'] ) ) {
            $this->meta['notes'] = array();
        }

        $this->meta['notes'][] = $note;

        // write the change to the DB
        $this->save();
    }

    /**
     * Write the current values to the database
     * @return int The ID of the queue item
     */
    public function save() {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $fields = get_object_vars( $this );
        $data   = array();
        $id     = 0;

        if ( $this->id ) {
            $id = $this->id;
        }

        foreach ( $fields as $field => $value ) {
            if ( $field == 'meta' ) {
                $data[ $field ] = maybe_serialize( $this->$field );
            } else {
                $data[ $field ] = $this->$field;
            }
        }

        if ( $id ) {
            // updating
            unset($data['id']);

            $wpdb->update(
                $wpdb->prefix .'followup_email_orders',
                $data,
                array('id' => $id)
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix .'followup_email_orders',
                $data
            );

            $this->id = $wpdb->insert_id;
            $id = $this->id;
        }

        return $id;

    }

}
