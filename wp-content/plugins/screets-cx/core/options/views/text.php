<?php 

global $CX;

$error_class = $error = '';

// Triggable?
$triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : ''; 

// Input type
$input_type = !empty( $option['input_type'] ) ? $option['input_type'] : 'text';

// Error?
if( !empty( $CX->admin_notices['fields'][$option['id']] ) ) {
	$error_class = 'cx-error-field';
	$error = '<div class="cx-error">' . $CX->admin_notices['fields'][$option['id']] . '</div>';
}

?>
<tr<?php echo $triggable; ?> id="cx_opt_row_<?php echo $option['id']; ?>">
	<th scope="row"><label for="cx-opts-field-<?php echo $option['id']; ?>"><?php echo $option['name']; ?></label></th>
	<td>
		<input type="<?php echo $input_type; ?>" value="<?php echo stripslashes( @$settings[$option['id']] ); ?>" name="<?php echo $option['id']; ?>" id="cx-opts-field-<?php echo $option['id']; ?>" class="regular-text <?php echo $error_class; ?>" placeholder="<?php echo (!empty($option['placeholder'])) ? $option['placeholder'] : ''; ?>" />
		<?php echo $error; ?>
		<p class="description"><?php echo $option['desc']; ?></p>
	</td>
</tr>