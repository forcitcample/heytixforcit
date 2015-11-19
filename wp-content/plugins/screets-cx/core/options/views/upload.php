<?php 
$triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : ''; 

$preview = ( !empty( $settings[$option['id']] ) ) ? $settings[$option['id']] : @$option['avatar'];
$width = ( empty( $preview ) ) ? 300 : 355;

?>
<tr<?php echo $triggable; ?>>
	<th scope="row"><label for="cx-opts-field-<?php echo $option['id']; ?>"><?php echo $option['name']; ?></label></th>
	<td>
		<div style="width: <?php echo $width; ?>px">
			<?php if( !empty( $preview ) ) {
				
				echo '<img id="cx-opts-field-avatar-' . @$option['id'] .'" src="' . $preview . '" style="float:left; margin-right: 15px; width:40px;">';
			} ?>

			<input type="text" value="<?php echo @$settings[$option['id']]; ?>" name="<?php echo @$option['id']; ?>" id="cx-opts-field-<?php echo @$option['id']; ?>" class="regular-text" style="width:230px" />
			<a href="#" rel="<?php echo $option['id']; ?>" class="button alignright cx-opts-upload-button hide-if-no-js" style="width:60px;text-align:center;overflow:hidden;padding-left:0;padding-right:0"><?php _e( 'Upload', $this->textdomain ); ?></a>
		</div>
		<span class="description"><?php echo $option['desc']; ?></span>
	</td>
</tr>