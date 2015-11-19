<?php $triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : ''; ?>
<tr<?php echo $triggable; ?>>
	<td colspan="2">

		<div class="cx-help">
			<h3>Configuring Firebase</h3>
			
			<div class="cx-lead">First of all, you will want to create new free Firebase account here: <a href="http://www.firebase.com/signup" target="_blank">www.firebase.com/signup</a> </div>
			
			<p>* After sign up Firebase, just “Create New App” on Firebase Dashboard.<br>
				* Enter your <i>“App Name”</i> into <strong>App URL</strong> in Chat Settings > Advanced tab.<br>
				* Final step is finding your <i>“Secret”</i> key in Firebase dashboard (found in “Secret” menu):<br>
				* Click <i>“Show”</i> button and copy your key. Then you will want to paste it into <strong>“App Token”</strong> found in Chat Setting > Advanced tab.<br>
				* After clicking “Save Changes”, then Firebase is ready for CX.</p>
			
			<h3>Finishing Touches</h3>
			
			<p>Offline tab in chat settings is important for you. Don’t forget to add your contact email(s) to <strong>Where should offline messages go?</strong> field and offline email fields like “Site Name”, “Site URL” and especially “Email Footer”.</p>
		</div>

	</td>
</tr>