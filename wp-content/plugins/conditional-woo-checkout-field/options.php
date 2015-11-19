<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&appId=637680869644861&version=v2.0";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<script type="text/javascript">
	function showDetails(id) {
		document.getElementById(id).style.display = 'block';
	}
	function hideDetails(id) {
		document.getElementById(id).style.display = 'none';
	}
</script>
<style>
.upgrade {
	-moz-box-shadow: 0px 10px 14px -7px #3e7327;
	-webkit-box-shadow: 0px 10px 14px -7px #3e7327;
	box-shadow: 0px 10px 14px -7px #3e7327;
	background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #77b55a), color-stop(1, #72b352));
	background:-moz-linear-gradient(top, #77b55a 5%, #72b352 100%);
	background:-webkit-linear-gradient(top, #77b55a 5%, #72b352 100%);
	background:-o-linear-gradient(top, #77b55a 5%, #72b352 100%);
	background:-ms-linear-gradient(top, #77b55a 5%, #72b352 100%);
	background:linear-gradient(to bottom, #77b55a 5%, #72b352 100%);
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#77b55a', endColorstr='#72b352',GradientType=0);
	background-color:#77b55a;
	-moz-border-radius:4px;
	-webkit-border-radius:4px;
	border-radius:4px;
	border:1px solid #4b8f29;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Verdana;
	font-size:15px;
	font-weight:bold;
	padding:9px 12px;
	text-decoration:none;
	text-shadow:0px 1px 0px #5b8a3c;
}
.upgrade:hover {
	background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #72b352), color-stop(1, #77b55a));
	background:-moz-linear-gradient(top, #72b352 5%, #77b55a 100%);
	background:-webkit-linear-gradient(top, #72b352 5%, #77b55a 100%);
	background:-o-linear-gradient(top, #72b352 5%, #77b55a 100%);
	background:-ms-linear-gradient(top, #72b352 5%, #77b55a 100%);
	background:linear-gradient(to bottom, #72b352 5%, #77b55a 100%);
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#72b352', endColorstr='#77b55a',GradientType=0);
	background-color:#72b352;
}
.upgrade:active {
	position:relative;
	top:1px;
}
</style>
<div class="wrap">
	<div class="postbox">
		<h2><?php _e('Conditional Checkout Field', 'conditional-woo-checkout-field'); ?></h2>
		<p><?php _e('Enter in the details for a field to be displayed at checkout provided a certain product is in the cart.', 'conditional-woo-checkout-field'); ?></p>
		<form method='post' action='options.php'>
			<?php wp_nonce_field( 'update-options' ); ?>
			<?php settings_fields( 'oizuled_conditional_fields_option-group' ); ?>
			<table class="wp-list-table widefat fixed posts">
				<thead>
					<tr>
						<th scope="col" id="oizuled_conditional_fields_pid" class="manage-column"><?php _e('Product ID', 'conditional-woo-checkout-field'); ?></th>
						<th scope="col" id="oizuled_conditional_fields_title" class="manage-column"><?php _e('Title', 'conditional-woo-checkout-field'); ?></th>
						<th scope="col" id="oizuled_conditional_fields_type" class="manage-column"><?php _e('Input Type', 'conditional-woo-checkout-field'); ?></th>
						<th scope="col" id="oizuled_conditional_fields_label" class="manage-column"><?php _e('Field Label', 'conditional-woo-checkout-field'); ?></th>
						<th scope="col" id="oizuled_conditional_fields_placeholder" class="manage-column"><?php _e('Placeholder Text', 'conditional-woo-checkout-field'); ?></th>
						<th scope="col" id="oizuled_conditional_fields_class" class="manage-column"><?php _e('Class', 'conditional-woo-checkout-field'); ?></th>
						<th scope="col" id="oizuled_conditional_fields_required" class="manage-column"><?php _e('Required Field?', 'conditional-woo-checkout-field'); ?></th>
						<th scope="col" id="oizuled_conditional_fields_requiredtext" class="manage-column"><?php _e('Error Message', 'conditional-woo-checkout-field'); ?></th>
						<th scope="col" id="oizuled_conditional_fields_addemail" class="manage-column"><?php _e('Add to Order Email/Invoice?', 'conditional-woo-checkout-field'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><input type="text" name="oizuled_conditional_fields_pid" size="4" value="<?php echo get_option('oizuled_conditional_fields_pid'); ?>" /></td>
						<td><input type="text" name="oizuled_conditional_fields_title" size="10" value="<?php echo get_option('oizuled_conditional_fields_title'); ?>" /></td>
						<td>
							<select name="oizuled_conditional_fields_type">
								<option value="text" <?php if (get_option('oizuled_conditional_fields_type') == 'text') { echo 'selected'; } ?>><?php _e('Text Box', 'conditional-woo-checkout-field'); ?></option>
								<option value="textarea" <?php if (get_option('oizuled_conditional_fields_type') == 'textarea') { echo 'selected'; } ?>><?php _e('Text Area', 'conditional-woo-checkout-field'); ?></option>
								<option value="select" <?php if (get_option('oizuled_conditional_fields_type') == 'select') { echo 'selected'; } ?>><?php _e('Select Menu', 'conditional-woo-checkout-field'); ?></option>
							</select>
								<hr />
								<?php _e('Select Options', 'conditional-woo-checkout-field'); ?><br />
								<textarea name="oizuled_conditional_fields_options" rows="3" cols="15"><?php echo get_option('oizuled_conditional_fields_options'); ?></textarea>
						</td>
						<td><input type="text" name="oizuled_conditional_fields_label" size="10" value="<?php echo get_option('oizuled_conditional_fields_label'); ?>" /></td>
						<td><input type="text" name="oizuled_conditional_fields_placeholder" size="10" value="<?php echo get_option('oizuled_conditional_fields_placeholder'); ?>" /></td>
						<td><input type="text" name="oizuled_conditional_fields_class" size="10" value="<?php echo get_option('oizuled_conditional_fields_class'); ?>" /></td>
						<td>
							<input type="radio" name="oizuled_conditional_fields_required" value="yes" <?php if (get_option('oizuled_conditional_fields_required') == 'yes') { echo 'checked'; } ?> /> <?php _e('Yes', 'conditional-woo-checkout-field'); ?><br />
							<input type="radio" name="oizuled_conditional_fields_required" value="no" <?php if (get_option('oizuled_conditional_fields_required') == 'no') { echo 'checked'; } ?> /> <?php _e('No', 'conditional-woo-checkout-field'); ?>
						</td>
						<td><input type="text" name="oizuled_conditional_fields_requiredtext" size="10" value="<?php echo get_option('oizuled_conditional_fields_requiredtext'); ?>" /></td>
						<td>
							<input type="checkbox" name="oizuled_conditional_fields_addemail" value="yes" <?php if (get_option('oizuled_conditional_fields_addemail') == 'yes') { echo 'checked'; } ?> /> <?php _e('Order Email', 'conditional-woo-checkout-field'); ?><br />
							<input type="checkbox" name="oizuled_conditional_fields_addinvoice" value="yes" <?php if (get_option('oizuled_conditional_fields_addinvoice') == 'yes') { echo 'checked'; } ?> /> <?php _e('Order Invoice', 'conditional-woo-checkout-field'); ?>
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="6"><input type="hidden" name="action" value="update" /><?php submit_button(); ?></td>
						<td><a href="http://wordpress.org/support/view/plugin-reviews/conditional-woo-checkout-field-pro"><?php _e('Enjoy this plugin? Give it a 5 star rating.', 'conditional-woo-checkout-field-pro'); ?></a></td>
						<td><a href="https://twitter.com/surprisewebsvc" class="twitter-follow-button" data-show-count="false" data-lang="en">Follow @surprisewebsvc</a><script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p></td>
						<td><div class="fb-like" data-href="https://www.facebook.com/SurpriseAZWebServices" data-layout="button" data-action="like" data-show-faces="false" data-share="false"></div></td>
					</tr>
					<tr>
						<th colspan="2">Upgrade to Pro!</th>
						<th colspan="4">
							Upgrade to have unlimited conditional fields, and make each field display for any number of products.<br />
							Bonus - Pro version comes with an editor for the default checkout fields. Choose whether to make a field required, hide it, and more.<br />
							<a href="https://surpriseazwebservices.com/plugins/conditional-woo-checkout-field-pro/" target="_blank" class="upgrade">Upgrade Now!</a>
						</th>
						<th colspan="3"></th>
					</tr>
				</tfoot>
			</table>
		</form>
		<a href="#" onclick="showDetails('help'); return false;"><?php _e('Show Help', 'conditional-woo-checkout-field'); ?></a>
		<a href="#" onclick="hideDetails('help'); return false;"><?php _e('Hide Help', 'conditional-woo-checkout-field'); ?></a>
		<span id="help" style="display:none">
			<p><strong><?php _e('How to use these fields:', 'conditional-woo-checkout-field'); ?></strong><br /><?php _e('All fields are required unless otherwise indicated below.', 'conditional-woo-checkout-field'); ?></p>
			<ul>
				<li><strong><?php _e('Product ID:', 'conditional-woo-checkout-field'); ?></strong>
					<ul>
						<li><?php _e('This is the ID number of the product, which if it is in the customer\'s cart, should trigger the custom field to display at checkout. You can get the ID number by viewing your', 'conditional-woo-checkout-field'); ?> <a href="<?php admin_url( 'edit.php?post_type=product' ); ?>"><?php _e('product admin page', 'conditional-woo-checkout-field'); ?></a><?php _e(', and hovering your mouse over the product. The ID number will be displayed under the product name, next to the Edit links.', 'conditional-woo-checkout-field'); ?></li>
					</ul>
				</li>
				<li><strong><?php _e('Title:', 'conditional-woo-checkout-field'); ?></strong>
					<ul>
						<li><?php _e('It is important that you do not change this after orders are made using this custom field. If changed, older orders may not show the customer\'s information entered into this field, as the script uses the "Title" name to lookup the value to be displayed. Changing this field would result in older orders not containing the correct "Title" that the script will use to pull the customer\'s information from. If it becomes necessary to change this title, and you wish to display the data along with the older orders, you can change the custom field\'s Name in each order to the new Title, and the information will continue to be displayed. Note, changing the title will not result in a loss of data, rather it will simply not display older data using an outdated field title.', 'conditional-woo-checkout-field'); ?></li>
					</ul>
				</li>
				<li><strong><?php _e('Input Type:', 'conditional-woo-checkout-field'); ?></strong>
					<ul>
						<li><?php _e('Identify what you would like the customer to use as the input field. Short responses, such as a name, date, etc. might only need a text box. Longer responses might need a text area. For a pre-defined list of choices, use Select, and enter the choices in the box below the Select option with one option on each line (hit enter on your keyboard after each option).', 'conditional-woo-checkout-field'); ?></li>
					</ul>
				</li>
				<li><strong><?php _e('Field Label (optional but recommended):', 'conditional-woo-checkout-field'); ?></strong>
					<ul>
						<li><?php _e('Use this to instruct the customer what to enter in the field. This is the text that is in between the Title and the input field. If the field is set to be required, a red asterisk (*) by default will show next to this label.', 'conditional-woo-checkout-field'); ?></li>
					</ul>
				</li>
				<li><strong><?php _e('Placeholder Text (optional):', 'conditional-woo-checkout-field'); ?></strong>
					<ul>
						<li><?php _e('Often times using placeholder text is a useful way to guide customers to enter information in the correct format. The information entered here will appear in the input field (if it is a text box or text area). It will look "greyed out" until the customer enters some information in the field.', 'conditional-woo-checkout-field'); ?></li>
					</ul>
				</li>
				<li><strong><?php _e('Class (optional):', 'conditional-woo-checkout-field'); ?></strong>
					<ul>
						<li><?php _e('If you wish to edit the CSS class of the input field, use this to enter the class.', 'conditional-woo-checkout-field'); ?></li>
					</ul>
				</li>
				<li><strong><?php _e('Required Field?:', 'conditional-woo-checkout-field'); ?></strong>
					<ul>
						<li><?php _e('Simply indicates whether or not the customer must enter information into the field. If required, a red asterisk will be placed immediately following the field label, and an error will be displayed if the customer leaves the field empty.', 'conditional-woo-checkout-field'); ?></li>
					</ul>
				</li>
				<li><strong><?php _e('Error Message (optional unless required field is set to Yes):', 'conditional-woo-checkout-field'); ?></strong>
					<ul>
						<li><?php _e('If the field is set to be required, this will be the error message displayed to the customer if they do not enter any information. Use this to give the customer information as to how to complete the field, such as "Please enter your customization text in the field below", etc. If you leave this error message blank and the customer does not fill out the required field, an error box will be displayed at the top of the page, but it will not give the customer any useful information on how to clear this error.', 'conditional-woo-checkout-field'); ?></li>
					</ul>
				</li>
				<li><strong><?php _e('Add to Order Email/Invoice:', 'conditional-woo-checkout-field'); ?></strong>
					<ul>
						<li><?php _e('If you want the information the customer enters to be included in their order emails or order invoice check the appropriate boxes. The order invoice is found in the customer\'s', 'conditional-woo-checkout-field'); ?> <a href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>" title="<?php _e('My Account','conditional-woo-checkout-field'); ?>"><?php _e('My Account','conditional-woo-checkout-field'); ?></a> <?php _e('page on your website.','conditional-woo-checkout-field'); ?></li>
					</ul>
				</li>
			</ul>
		</span>
	</div>
</div>