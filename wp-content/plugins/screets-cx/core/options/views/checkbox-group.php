<tr>
	<th scope="row"><?php echo $option['name']; ?></th>
	<td id="cx-<?php echo $option['id']; ?>" class="cx-opts-checkbox-group">
		<?php
			foreach ( $option['options'] as $checkbox => $label ) {
				$checked = ( !empty( $settings[$option['id']][$checkbox] ) ) ? ' checked="checked"' : '';
				$field_id = 'cx-opts-field-' . $option['id'] . '-' . $checkbox;
				?>
				<label for="<?php echo $field_id; ?>">
					<input type="checkbox" data-name="<?php echo $checkbox; ?>" name="<?php echo $option['id']; ?>[<?php echo $checkbox; ?>]" id="<?php echo $field_id; ?>"<?php echo $checked; ?> /> <?php echo $label; ?>
				</label><br/>
				<?php
			}
		?>
		<span class="description" style="color:#999;"><?php echo $option['desc']; ?></span>
	</td>
</tr>