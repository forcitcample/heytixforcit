<?php 

global $CX;

// Triggable?
$triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : ''; 

// Get last security update
$last_update = get_option( 'cx_security_last_update' );

// Check 
if(  empty( $last_update ) || version_compare( CX_VERSION, $last_update, '>' ) ) {
	$security_status = '<p><button class="button" id="CX_upd_security">' . __( 'Update now', 'cx' ) . '</button></p> <span class="cx-error">' . __( "Security rules couldn't be updated.", 'cx' ) . '</span>';

} else {
	$security_status = '<p style="color:green;">' . __( 'Up-to-date', 'cx' ) . '</p>';
}

?>
<tr<?php echo $triggable; ?>>
	<th scope="row"><label for="cx-opts-field-<?php echo $option['id']; ?>"><?php _e( 'Security', 'cx' ); ?></label></th>
	<td>
		<?php echo $security_status; ?>

		<p>
			<small class="description" style="color:#999;font-style:italic;">CX protects your data by adding security rules in your real time application automatically.</small>
		</p>
	</td>
</tr>