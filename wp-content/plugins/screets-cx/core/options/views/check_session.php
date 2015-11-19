<?php 

global $CX, $wpdb;

// Triggable?
$triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : ''; 


// Check PHP sessions work?
if( CX_PHP_SESSIONS ) {
	
	// Add test session variable
	$CX->session->set( 'CX_opt_test', 1 );

	$status = '<button id="CX_check_sessions" class="button">' . __( 'Check', 'cx' ) . '</button>'
			 .'<span id="cx_session_ntf" class="description" style="color:#999;">' . __( 'You are using <strong>PHP sessions</strong>. Please click "Check" button to ensure that sessions really work!', 'cx' ) . '<br>' . __( 'If you want to store session data to database, add the code below into <strong>wp-config.php</strong>.', 'cx' ) . "<br><code>define( 'CX_PHP_SESSIONS', false );</code></span>";

} else {

	$count = $wpdb->get_var( "SELECT count(*) FROM $wpdb->options WHERE option_name LIKE '_wp_session%'" );
	$status = '<button id="CX_clean_sessions" class="button">' . __( 'Clean sessions', 'cx' ) . " ($count)</button>"
			 .'<span id="cx_session_ntf" class="description" style="color:#999;">' . __( 'Session data storing to database.', 'cx' ) . '</span>';
}

?>

<tr<?php echo $triggable; ?>>
	<th scope="row"><label for="cx-opts-field-<?php echo $option['id']; ?>"><?php _e( 'Sessions', 'cx' ); ?></label></th>
	<td>
		<?php
		echo $status; 
		?>
	</td>
</tr>