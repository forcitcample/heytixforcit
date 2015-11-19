<?php
	$trigger = ( !empty( $option['trigger'] ) ) ? ' data-trigger="true" data-trigger-type="radio"' : '';
	$triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : '';
?>
<tr<?php echo $trigger, $triggable; ?>>
	<th scope="row"><label for="cx-opts-field-<?php echo $option['id']; ?>"><?php echo $option['name']; ?></label></th>
	<td class="cx-opts-radio-group">
		<?php
			foreach ( $option['options'] as $value => $label ) {
				$checked = ( @$settings[$option['id']] == $value ) ? ' checked="checked"' : '';
				?>
				<label>
					<input type="radio" name="<?php echo $option['id']; ?>" value="<?php echo $value; ?>"<?php echo $checked; ?> /> 
					<?php echo $label; ?>
				</label>

				<?php
			}
		?>
		<small class="description"><?php echo $option['desc']; ?></small>
	</td>
</tr>