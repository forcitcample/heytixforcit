<?php
$key = ( !empty( $_GET['key'] ) ) ? $_GET['key'] : '';

if ( empty( $key ) ) {
    wp_die('Invalid request. Please try again.');
}

$data = get_transient( 'fue_manual_email_'. $key );

if ( !$data ) {
    wp_die('Invalid request. Please try again.');
}

?>
<script>var key = '<?php echo $key; ?>';</script>
<style>
    .ui-progressbar {
        position: relative;
    }
    .ui-progressbar-value {
        border: 1px solid #fff;
        background: #ededed;
    }
    .progress-label {
        position: absolute;
        left: 10px;
        top: 4px;
        font-weight: bold;
        text-shadow: 1px 1px 0 #fff;
        color: #a9a9a9;
    }
    #log {
        max-height: 300px;
        overflow: auto;
    }
    #log p.success {
        color: green;
    }
    #log p.failure {
        color: #ff0000;
    }
</style>
<div class="wrap">
    <h2><?php _e('Send Emails', 'follow_up_emails'); ?></h2>

    <p id="total-recipients-label"><?php printf( __('Sending %d emails', 'follow_up_emails'), count($data['recipients']) ); ?></p>
    <div id="progressbar"><div class="progress-label">Loading...</div></div>

    <div id="log">

    </div>
</div>