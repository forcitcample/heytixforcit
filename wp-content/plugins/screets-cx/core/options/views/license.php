<?php 

// Triggable?
$triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : ''; 

// Input type
$input_type = !empty( $option['input_type'] ) ? $option['input_type'] : 'text';

$l = get_option( 'cx_c9f1a6384b1c466d4612f513bd8e13ea' );
$e = get_option( 'cx_error' );
$msg = ( !empty( $l ) ) ? "<div style='color:green'>$l</div>" : "<div style='color:red'>$e</div>";
$valid = ( !empty( $l ) ) ? 'valid' : 'invalid';
$p_btn = ( empty( $l ) ) ? '<a href="http://codecanyon.net/item/chat-x-wordpress-chat-plugin-for-sales-support/6639389" target="_blank" class="button">' . __( 'Purchase', 'cx' ) . '</a>' : '';

?>
<tr<?php echo $triggable; ?>>
	<th scope="row"><label for="cx-opts-field-<?php echo $option['id']; ?>"><?php echo $option['name']; ?></label></th>
	<td>
		<p><?php echo $msg; ?></p>
		<?php echo $p_btn; ?>
		<input type="<?php echo $input_type; ?>" value="<?php echo stripslashes( @$settings[$option['id']] ); ?>" name="<?php echo $option['id']; ?>" id="cx-opts-field-<?php echo $option['id']; ?>" class="regular-text" data-valid="<?php echo $valid; ?>" placeholder="<?php echo (!empty($option['placeholder'])) ? $option['placeholder'] : ''; ?>" />
		<p class="description"><?php echo $option['desc']; ?></p>

	</td>
</tr>
