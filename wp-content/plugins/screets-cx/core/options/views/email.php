<?php $triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : ''; ?>
<tr<?php echo $triggable; ?>>
	<th scope="row"><label for="cx-opts-field-<?php echo $option['id']; ?>"><?php echo $option['name']; ?></label></th>
	<td>
		<input type="email" value="<?php echo $settings[$option['id']]; ?>" name="<?php echo $option['id']; ?>" id="cx-opts-field-<?php echo $option['id']; ?>" class="regular-text" placeholder="<?php echo (!empty($option['placeholder'])) ? $option['placeholder'] : ''; ?>" />
		<p class="description"><?php echo $option['desc']; ?></p>
	</td>
</tr>