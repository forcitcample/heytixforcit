<?php $triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : ''; ?>
<tr<?php echo $triggable; ?>>
	<th scope="row"><label for="cx-opts-field-<?php echo $option['id']; ?>"><?php echo $option['name']; ?></label></th>
	<td>
		<textarea name="<?php echo $option['id']; ?>" id="cx-opts-field-<?php echo $option['id']; ?>" class="regular-text cx-opts-code" rows="<?php echo ( isset( $option['rows'] ) ) ? $option['rows'] : 5; ?>" placeholder="<?php _e( 'Insert code', $this->textdomain ); ?>"><?php echo stripslashes( @$settings[$option['id']] ); ?></textarea>
		<span class="description"><?php echo $option['desc']; ?></span>
	</td>
</tr>