<?php
class TribeWooTicketsExtended extends TribeWooTickets {
    private static $instance;
    /**
     * Get (and instantiate, if necessary) the instance of the class
     *
     * @static
     * @return TribeWooTicketsExtended
     */
    public static function get_instance() {
        if ( ! is_a( self::$instance, __CLASS__ ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get_attendee_list($event_id) {
        $attendees = $this->get_attendees($event_id);
        return $attendees;
    }
}