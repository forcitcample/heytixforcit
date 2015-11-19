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
	<th scope="row">
		<label for="cx-opts-field-<?php echo $option['id']; ?>"><?php echo $option['name']; ?></label></th>
	<td>

		<?php if( !empty( $option['prefix'] ) ): ?>
			<small style="color:silver;" class="cx-opts-units"><?php echo $option['prefix']; ?></small>
		<?php endif; ?>
		
		<input type="text" value="<?php echo @$settings[$option['id']]; ?>" name="<?php echo $option['id']; ?>" id="cx-opts-field-<?php echo $option['id']; ?>" class="regular-text <?php echo $error_class; ?>" style="width:170px" placeholder="<?php echo $option['placeholder']; ?>" /> 
		
		<?php if( !empty( $option['suffix'] ) ): ?>
			<span class="cx-opts-units"><?php echo $option['suffix']; ?></span>
		<?php endif; ?>

		<?php echo $error; ?>

		<span class="description"><?php echo $option['desc']; ?></span>
	</td>
</tr>