<?php
	$triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : '';
	$checked = ( @$settings[$option['id']] == 'on' ) ? ' checked="checked"' : '';
?>
<tr<?php echo $triggable; ?>>
	<th scope="row"><label for="cx-opts-field-<?php echo $option['id']; ?>"><?php echo $option['name']; ?></label></th>
	<td>
		<label><input type="checkbox" name="<?php echo $option['id']; ?>" id="cx-opts-field-<?php echo $option['id']; ?>"<?php echo $checked; ?> /> <?php echo $option['label']; ?></label>
		<?php if( !empty( $option['desc'] ) ): ?>
			<span class="description" style="color:#999"><?php echo $option['desc']; ?></span>
		<?php endif; ?>
	</td>
</tr>