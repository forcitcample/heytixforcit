<?php $triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : ''; ?>
<tr<?php echo $triggable; ?>>
	<th scope="row" colspan="2"><h2 class="cx-opts-title-box" style="margin:0"><?php echo $option['name']; ?></h2></th>
</tr>