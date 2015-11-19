<?php 

global $CX;

// Triggable?
$triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : ''; 

// Cacxulate total size
$size_bytes = cx_get_total_size_data();

if( $size_bytes < 1048576 )
	$size = number_format( $size_bytes / 1024, 2 ) . 'KB';
else
	$size = number_format( $size_bytes / 1048576, 2 ) . 'MB';

?>

<tr<?php echo $triggable; ?>>
	<th scope="row"><label for="cx-opts-field-<?php echo $option['id']; ?>"><?php _e( 'Realtime Data', 'cx' ); ?></label></th>
	<td>

		<button id="CX_clean_data" class="button"><strong><?php _e( 'Clean realtime data', 'cx' ); ?></strong> ( <?php echo $size; ?> )</button>
		<span class="description"><?php _e( 'All data in your realtime app platform will be cleaned and saved into your database!', 'cx' ); ?></span>
		<span class="description" style="color:#999"><strong>(!)</strong> <?php _e( 'Logout from chat console before clean data!', 'cx' ); ?></span>

	</td>
</tr>