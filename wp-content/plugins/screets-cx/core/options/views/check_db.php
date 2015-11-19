<?php 

global $CX;

// Triggable?
$triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : ''; 

// Input type
$input_type = !empty( $option['input_type'] ) ? $option['input_type'] : 'text';

// Check database tables
if( !empty( $CX->admin_notices['no_db_tables'] ) ) {
	$db_status = '<p><button class="button" id="CX_create_db">' . __( 'Create Databases', 'cx' ) . '</button></p> <span class="cx-error">' . __( "No databases found. It is required for archiving your chat logs and more.", 'cx' ) . '</span>';

} else {
	$db_status = '<p style="color:green;">' . __( 'Good', 'cx' ) . '</p>';
}

?>
<tr<?php echo $triggable; ?>>
	<th scope="row"><label for="cx-opts-field-<?php echo $option['id']; ?>"><?php _e( 'Database status', 'cx' ); ?></label></th>
	<td>
		<?php echo $db_status; ?>
		<p>
			<small class="description" style="color:#999;font-style:italic;">CX uses database tables, because it doesn’t keep your user and chat data into Firebase. When visitor or operator clicks “End Chat” button, CX removes all data from Firebase and save it into your server (into databases).</small>
		</p>
	</td>
</tr>