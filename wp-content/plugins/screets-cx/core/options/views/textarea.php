<?php 

global $CX;

$error_class = $error = '';

$triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : '';

// Error?
if( !empty( $CX->admin_notices['fields'][$option['id']] ) ) {
	$error_class = 'cx-error-field';
	$error = '<div class="cx-error">' . $CX->admin_notices['fields'][$option['id']] . '</div>';
}

?>
<tr<?php echo $triggable; ?>>
	<th scope="row"><label for="cx-opts-field-<?php echo $option['id']; ?>"><?php echo $option['name']; ?></label></th>
	<td>
		<textarea name="<?php echo $option['id']; ?>" id="cx-opts-field-<?php echo $option['id']; ?>" class="regular-text <?php echo $error_class; ?> cx-opts-textarea" rows="<?php echo ( isset( $option['rows'] ) ) ? $option['rows'] : 5; ?>"><?php echo stripslashes( $settings[$option['id']] ); ?></textarea>
		
		<?php echo $error; ?>

		<p class="description"><?php echo $option['desc']; ?></p>
	</td>
</tr>