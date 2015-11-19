<?php
//$ticket, $event, $ticket_confirmation_token
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	<title><?php _e( 'WELCOME TO HEYTIX', 'tribe-events-calendar' ); ?></title>
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
<body yahoo="fix" alink="#006caa" link="#006caa" text="#000000" bgcolor="#f5f5f5" style="width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0 auto; padding:20px 0 0 0; background:#ffffff; min-height:1000px;">
    <div style="background: #f5f5f5;margin:0; padding:0; width:100% !important; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size:14px; line-height:145%; text-align:left;">
        <center>
            <table class="content" align="center" width="600" cellspacing="0" cellpadding="0" border="0" bgcolor="#f5f5f5" style="margin:0 auto; padding:0;<?php echo $break; ?>">
                <tr>
                    <td valign="top" align="center" width="100%" style="padding: 25px 0 !important; margin:0 !important;">
                        <img src="<?php echo get_stylesheet_directory_uri() . '/images/Hey_Tix_Logo.png'; ?>" alt="HeyTix.com"/>
                    </td>
                </tr>
                <tr>
                    <td valign="top" align="center" width="100%" style="padding: 0 0  45px!important; margin:0 !important;">
                        <div style="width: 600px;border:1px solid #dcdcdc;border-radius: 10px;">
                            <h2 style="background:#013d61;border-radius: 10px 10px 0 0;padding: 35px;color: #ffffff;margin: 0;">WELCOME TO HEYTIX</h2>
                            <div style="border:1px solid #dcdcdc;border-radius: 0 0 10px 10px;background: #ffffff;margin: 0;padding:0 25px 0 30px;">
                                <p style="margin: 0px; padding: 30px 0 20px;text-align: left;"><?php  echo $first_name; ?>,</p>
                                <p style="margin: 0px; padding: 0 0 20px;text-align: left;">Thank you for registering with HeyTix. Your account is now active. To login please visit:</p>
                                <p style="margin: 0px; padding: 0 0 20px;text-align: left;"><a href="<?php echo site_url('profile/login/'); ?>" title="">HeyTix.com</a></p>
                                <p style="margin: 0px; padding: 0 0 10px;text-align: left;"><?php  echo 'Your account login is:';?></p>
                                <p style="margin: 0px; padding: 0 0 15px;text-align: left;">
                                    Email: <?php echo $email; ?><br/>
                                    Username: <?php echo $username; ?><br/>
                                    Password: <?php echo $password; ?>
                                </p>
                                <p style="margin: 0px; padding: 0 0 15px;text-align: left;">
                                    If you have any problems, please contact us at <a href="mailto:gettix@heytix.com" title="">gettix@heytix.com</a>.
                                </p>
                                <p style="margin: 0px; padding: 0 0 0;text-align: left;">Enjoy!</p>
                                <p style="margin: 0px; padding: 30px 0; font-size: 10px; color: #013d61;text-align: center"><span style="">HeyTix <?php echo date('Y'); ?></span></p>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </center>
    </div>
</body>
</html>