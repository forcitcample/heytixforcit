<?php
//$ticket, $event, $ticket_confirmation_token
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	<title><?php _e( 'ONE LAST STEP TO GET ON THE GUEST LIST', 'tribe-events-calendar' ); ?></title>
	<meta name="viewport" content="width=device-width" />
	<style type="text/css">
		h1, h2, h3, h4, h5, h6 {
			color : #0a0a0e;
		}

		a, img {
			border  : 0;
			outline : 0;
		}

		#outlook a {
			padding : 0;
		}

		.ReadMsgBody, .ExternalClass {
			width : 100%
		}

		.yshortcuts, a .yshortcuts, a .yshortcuts:hover, a .yshortcuts:active, a .yshortcuts:focus {
			background-color : transparent !important;
			border           : none !important;
			color            : inherit !important;
		}

		body {
			background  : #ffffff;
			min-height  : 1000px;
			font-family : sans-serif;
			font-size   : 14px;
		}

		.appleLinks a {
			color           : #006caa;
			text-decoration : underline;
		}

		@media only screen and (max-width: 480px) {
			body, table, td, p, a, li, blockquote {
				-webkit-text-size-adjust : none !important;
			}

			body {
				width     : 100% !important;
				min-width : 100% !important;
			}

			body[yahoo] h2 {
				line-height : 120% !important;
				font-size   : 28px !important;
				margin      : 15px 0 10px 0 !important;
			}

			table.content,
			table.wrapper,
			table.inner-wrapper {
				width : 100% !important;
			}

			
			td.wrapper {
				width : 100% !important;
			}

			a[href^="tel"], a[href^="sms"] {
				text-decoration : none;
				color           : black;
				pointer-events  : none;
				cursor          : default;
			}

			.mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
				text-decoration : default;
				color           : #006caa !important;
				pointer-events  : auto;
				cursor          : default;
			}
		}

		@media only screen and (max-width: 320px) {
			td.ticket-venue h6,
			td.ticket-organizer h6,
			td.ticket-details h6 {
				font-size : 12px !important;
			}
		}

		@media print {
			.ticket-break {
				page-break-before : always !important;
			}
		}

		<?php do_action( 'tribe_tickets_ticket_email_styles' );?>

	</style>
</head>
<body yahoo="fix" alink="#006caa" link="#006caa" text="#000000" bgcolor="#ffffff" style="width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0 auto; padding:20px 0 0 0; background:#ffffff; min-height:1000px;">
    <div style="margin:0; padding:0; width:100% !important; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size:14px; line-height:145%; text-align:left;">
        <center>
            <table class="content" align="center" width="550" cellspacing="0" cellpadding="0" border="0" bgcolor="#f5f5f5" style="margin:0 auto; padding:0;<?php echo $break; ?>">
                <tr>
                    <td valign="top" align="center" width="100%" style="padding: 25px 0 !important; margin:0 !important;">
                        <img src="<?php echo get_stylesheet_directory_uri() . '/images/Hey_Tix_Logo.png'; ?>" alt="HeyTix.com"/>
                    </td>
                </tr>
                <tr>
                    <td valign="top" align="center" width="100%" style="padding: 0 0  45px!important; margin:0 !important;">
                        <div style="width: 460px;">
                            <h2 style="background:#013d61;border-radius: 10px 10px 0 0;padding: 25px;color: #ffffff;margin: 0;">GUEST LIST CONFIRMATION</h2>
                            <div style="border:1px solid #cdcdcd;border-radius: 0 0 10px 10px;background: #ffffff;margin: 0;">
                                <p style="margin: 0px; padding: 20px 0px 0px;">
                                    Thank you for registering for the <a style="text-decoration: none;color:#3cb0fd" href="<?php echo get_permalink($event); ?>"><?php echo get_the_title($event); ?></a> guest list.<br/>
                                    Please confirm your reservation by clicking the link bellow.
                                </p>
                                <p style="margin: 0px; padding: 20px 0px 0px;"><a href="<?php echo $confirm_link; ?>" target="_blank" style="background:#3cb0fd;color: #ffffff; display: inline-block; text-decoration: none; padding: 15px 25px; font-size: 18px; font-weight: 700; border-radius: 5px;">CONFIRM RESERVATION</a></p>
                                <p style="margin: 0px; padding: 69px 0px 5px; font-size: 10px; color: #666666;"><span style="">HeyTix <?php echo date('Y'); ?></span></p>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </center>
    </div>
</body>
</html>