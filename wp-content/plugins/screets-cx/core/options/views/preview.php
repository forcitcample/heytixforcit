<?php $triggable = ( !empty( $option['triggable'] ) ) ? ' data-triggable="' . $option['triggable'] . '" class="cx-opts-triggable hide-if-js"' : ''; ?>
<tr<?php echo $triggable; ?>>
	<td colspan="2">
		<div class="cxo_preview">
			
			<?php
			global $wp_version;

			// Show icons for WP 3.8+
			if( ( version_compare( $wp_version, '3.8', '>=' ) ) ): ?>
				<ul class="cx-preview-icons">
					<li id="ico_online_button" data-id="online_button" class="active" title="Button"><div class="dashicons dashicons-admin-links"></div></li>
					<!-- <li id="ico_offline_button"  data-id="offline_button" title="Button (Offline)"><div class="dashicons dashicons-editor-unlink"></div></li> -->
					<li id="ico_online"  data-id="online" title="Online Chat"><div class="dashicons dashicons-admin-comments"></div></li>
					<li id="ico_offline"  data-id="offline" title="Offline Form"><div class="dashicons dashicons-welcome-comments"></div></li>
				</ul>

				<div id="cx_preview_title">Button:</div>

			<?php 
			// Show select box for old WP versions
			else: ?>
				<select name="" id="" class="cx-preview">
					<option value="">-- None --</option>
					<option value="online_button" selected="selected">Button</option>
					<!-- <option value="offline_button">Button (Offline)</option> -->
					<option value="online">Online Chat</option>
					<option value="offline">Offline Form</option>
				</select>

			<?php endif; ?>
			
			<!-- Online Button -->
			<div id="CX_preview_online_button" class="cx-preview-box _visible">
				<div class="cx-chat-btn cx-online-btn">
					<div class="cx-ico cx-ico-chat"></div>
					<div class="cx-ico _ico-arrow"></div>
					<div class="cx-title"></div>
				</div>
			</div>

			<!-- Offline Button -->
			<!-- <div id="CX_preview_offline_button" class="cx-preview-box">
				<div class="cx-chat-btn cx-offline-btn">
					<div class="cx-ico cx-ico-chat"></div>
					<div class="cx-ico _ico-arrow"></div>
					<div class="cx-title"></div>
				</div>
			</div> -->


			<!-- Online -->
			<div id="CX_preview_online" class="cx-preview-box">
				
				<div class="cx-widget">
					<div class="cx-online">
						<!-- Header -->
						<div class="cx-header"><div class="cx-title"></div><div class="cx-ico cx-ico-arrow-down"></div></div>
						
						<div class="cx-body">
							
							<div id="CX_cnv_0">

								<!-- Reply form TOP -->
								<div id="CX_reply_top" class="cx-cnv-reply" style="display: none;">
									<div class="cx-cnv-input">
										<textarea name="" class="cx-reply-input cx-autosize" placeholder="<?php echo $option['reply_ph']; ?>"></textarea>
									</div>
								</div>

								<!-- Conversation TOP -->
								<div id="CX_cnv_top" class="cx-cnv" style="display: none;">

									<div class="cx-cnv-line">
										<div class="cx-avatar cx-img">
											<img src="<?php echo CX_URL; ?>/assets/img/default-avatar.png" class="cx-company-avatar">
										</div>
										<div class="cx-cnv-msg">
											<div title="3 September 2014 0:56" class="cx-cnv-time">0:56</div>
											<div class="cx-cnv-author">Screets, Inc.:</div> 
											<span class="cx-cnv-msg-detail">Yes. The OS X operation system isn't susceptible to the thousands of viruses plaguing Windows-based computers</span>
										</div>
									</div>
									<div class="cx-clear"></div>

									<div class="cx-cnv-line cx-you">
										<div class="cx-avatar cx-img">
											<img src="<?php echo CX_URL; ?>/assets/img/default-avatar.png">
										</div>
										<div class="cx-cnv-msg">
											<div title="3 September 2014 0:55" class="cx-cnv-time">0:55</div>
											<div class="cx-cnv-author">John Cash:</div> 
											<span class="cx-cnv-msg-detail">Hey! I wonder that a Mac is safe from PC viruses?</span>
										</div>
									</div>
									<div class="cx-clear"></div>
												
									<!-- Status message -->
									<!-- <div class="cx-cnv-line cx-cnv-status">screets has joined.</div> -->
									
								</div>
	
								<!-- Conversation BOTTOM -->
								<div id="CX_cnv_bottom" class="cx-cnv" style="display: none;">
									
									<div class="cx-cnv-line cx-you">
										<div class="cx-avatar cx-img">
											<img src="<?php echo CX_URL; ?>/assets/img/default-avatar.png">
										</div>
										<div class="cx-cnv-msg">
											<div title="3 September 2014 0:55" class="cx-cnv-time">0:55</div>
											<div class="cx-cnv-author">John Cash:</div> 
											<span class="cx-cnv-msg-detail">Hey! I wonder that a Mac is safe from PC viruses?</span>
										</div>
									</div>
									<div class="cx-clear"></div>

									<div class="cx-cnv-line">
										<div class="cx-avatar cx-img">
											<img src="<?php echo CX_URL; ?>/assets/img/default-avatar.png" class="cx-company-avatar">
										</div>
										<div class="cx-cnv-msg">
											<div title="3 September 2014 0:56" class="cx-cnv-time">0:56</div>
											<div class="cx-cnv-author">Screets, Inc.:</div> 
											<span class="cx-cnv-msg-detail">Yes. The OS X operation system isn't susceptible to the thousands of viruses plaguing Windows-based computers</span>
										</div>
									</div>
									<div class="cx-clear"></div>

									
									
								</div>

								<div class="cx-tools">
									<a href="">End chat</a>
								</div>

								<!-- Reply form BOTTOM -->
								<div id="CX_reply_bottom" class="cx-cnv-reply" style="display: none;">
									<form action="">
										<div class="cx-cnv-input">
											<textarea name=""  class="cx-reply-input cx-autosize" placeholder="<?php echo $option['reply_ph']; ?>"></textarea>
										</div>
									</form>
								</div>
							</div>
						</div>
				
					</div>
				</div>
			</div>

			<!-- Offline -->
			<div id="CX_preview_offline" class="cx-preview-box">
				
				<div class="cx-widget">
					<div class="cx-offline">
						<!-- Header -->
						<div class="cx-header"><div class="cx-title"></div><div class="cx-ico cx-ico-arrow-down"></div></div>

						<!-- Offline form -->
						<div class="cx-body cx-form cx-offline-form">
							<div class="cx-lead"></div>

							<form action="">
								<div id="CX_offline_row_name" class="cx-line">
									<label for="CX_offline_f_name"><span id="CX_offline_f_name_title" class="cx-title">Your name</span> <span class="cx-req">*</span>:</label>
									<input type="text" id="CX_offline_f_name" placeholder="Your name">
								</div>

								<div id="CX_offline_row_email" class="cx-line">
									<label for="CX_offline_f_email"><span id="CX_offline_f_email_title" class="cx-title">E-mail</span> <span class="cx-req">*</span>:</label>
									<input type="email" id="CX_offline_f_email" placeholder="E-mail">
								</div>

								<div id="CX_offline_row_phone" class="cx-line">
									<label for="CX_offline_f_phone"><span id="CX_offline_f_phone_title" class="cx-title">Phone</span> <span class="cx-req">*</span>:</label>
									<input type="email" id="CX_offline_f_phone" placeholder="Phone">
								</div>

								<div id="CX_offline_row_msg" class="cx-line">
									<label for="CX_offline_f_msg"><span  id="CX_offline_f_msg_title" class="cx-title">How can we help you?</span> <span class="cx-req">*</span>:</label>
									<textarea id="CX_offline_f_msg" class="cx-autosize" placeholder="How can we help you?"></textarea>
								</div>

								<div class="cx-send">
									<a href="javascript:void(0)" id="CX_offline_send" class="cx-form-btn">Send</a>
								</div>

							</form>
						</div>
					</div>
				</div>

			</div>

		<div class="cx-clear"></div>
		</div>
	</td>
</tr>