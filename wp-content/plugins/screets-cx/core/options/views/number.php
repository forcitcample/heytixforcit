<tr>
	<th scope="row">
		<label for="cx-opts-field-<?php echo $option['id']; ?>"><?php echo $option['name']; ?></label></th>
	<td>
		<input type="number" min="<?php echo $option['min']; ?>" max="<?php echo $option['max']; ?>" value="<?php echo $settings[$option['id']]; ?>" name="<?php echo $option['id']; ?>" id="cx-opts-field-<?php echo $option['id']; ?>" class="regular-text" style="width:80px" /> 
		<span class="cx-opts-units"><?php echo $option['units']; ?></span>
		<span class="description"><?php echo $option['desc']; ?></span>
	</td>
</tr>