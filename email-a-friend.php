<?php
require('wp-load.php');

function check_captcha($response) {
    $ip = (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $fields_string = 'secret=6Lcx9wkTAAAAAP5Hd6MlZGv5MlrlVBc9MGGyD7b6&remoteip='.$ip.'&response='.$response;
    //open connection
    $ch = curl_init();
    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, 3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    //execute post
    $result = curl_exec($ch);
    //close connection
    curl_close($ch);
    return json_decode($result);
}

if(isset($_POST['submit']) && !empty($_POST['submit'])) {
    $event_id = urldecode($_POST['event_id']);
    $event = get_post($event_id);
    $event_name = $event->post_title;
    $event_url = get_site_url().'/event/'.$event->post_name.'/';
    $logo_image = get_stylesheet_directory_uri() . '/images/Hey_Tix_Logo.png';

    // process the form, return success message, js closes window.
    $sender = trim(htmlspecialchars($_POST['sender']));
    $names = explode(' ', $sender);
    $first_name = $names[0];
    $subject = $sender .' has invited you to join them at '.$event_name;
    $recipient = trim(htmlspecialchars($_POST['email']));
    $email = trim($_POST['email']);
    $message = trim(stripslashes(htmlspecialchars($_POST['message'])));

    $event_date = date('l, F jS, Y', strtotime(get_post_meta($event_id, '_EventStartDate', true)));

    $year = date('Y');
    $email = <<<EMAIL
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
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
            }\
        </style>
    </head>
    <body yahoo="fix" alink="#006caa" link="#006caa" text="#000000" bgcolor="#ffffff" style="width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0 auto; padding:20px 0 0 0; background:#ffffff; min-height:1000px;">
    <div style="margin:0; padding:0; width:100% !important; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size:14px; line-height:145%; text-align:left;">
        <center>
            <table class="content" align="center" width="550" cellspacing="0" cellpadding="0" border="0" bgcolor="#f5f5f5" style="margin:0 auto; padding:0;">
                <tr>
                    <td valign="top" align="center" width="100%" style="padding: 25px 0 !important; margin:0 !important;">
                        <img src="$logo_image" alt="HeyTix.com"/>
                    </td>
                </tr>
                <tr>
                    <td valign="top" align="center" width="100%" style="padding: 0 0  45px!important; margin:0 !important;">
                        <div style="width: 460px;">
                            <h2 style="background:#013d61;border-radius: 10px 10px 0 0;padding: 25px;color: #ffffff;margin: 0;">JOIN YOUR FRIENDS</h2>
                            <div style="border:1px solid #cdcdcd;border-radius: 0 0 10px 10px;background: #ffffff;margin: 0;">
                                <p style="margin: 0px; padding: 20px;">
                                    $sender is going to see $event_name on $event_date. Click the link below to join!<br/>
                                </p>
                                <p style="margin: 0px; padding: 20px;">
                                    Personal Message from $first_name:<br/><br/> $message
                                </p>
                                <p style="margin: 0px; padding: 20px;"><a href="$event_url" target="_blank" style="background:#3cb0fd;color: #ffffff; display: inline-block; text-decoration: none; padding: 15px 25px; font-size: 18px; font-weight: 700; border-radius: 5px;">GET EVENT DETAILS</a></p>
                                <p style="margin: 0px; padding: 69px 0px 5px; font-size: 10px; color: #666666;"><span style="">HeyTix $year</span></p>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </center>
    </div>
    </body>
    </html>
EMAIL;

    $error = false;
    if(!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        $error = true;
    }
    $captcha_result = check_captcha($_POST['g-recaptcha-response']);

    if($captcha_result->success === false) {
        $error = true;
    }

    $result = null;
    if($error === false) {

        add_filter('wp_mail_content_type', 'set_html_content_type');
        $result = wp_mail($recipient, $subject, $email);

        // Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
        remove_filter('wp_mail_content_type', 'set_html_content_type');
        if ($result === false) {
            echo 'An Error Occurred!';
        } else {
            echo 'We have sent the email, thank you!';
        }
        exit;
    } else {
        echo 'An Error Occurred!';
        exit;
    }
}

$sender = (isset($_GET['sender']) && array_key_exists('sender', $_GET)) ? urldecode($_GET['sender']) : '';
$event_id = (isset($_GET['event_id']) && array_key_exists('event_id', $_GET)) ? urldecode($_GET['event_id']) : '';

if(!isset($event_id) || empty($event_id)) {
    echo 'Unable to load form.';
    exit;
}

?>
<html>
<head>
    <title>Invite a Friend</title>
    <link href='http://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link href='<?php echo get_stylesheet_directory_uri();?>/style.css' rel='stylesheet' type='text/css'>
    <style type="text/css">
        body {
            background-color: #efefef;
            margin: 0;
        }

        input[type=text],
        textarea {
            display: block;
            padding: 10px;
            margin-bottom: 15px;
            width: 100%;
            background-color: #ffffff;
            color: #808080;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
            position: relative;
            outline: none;
            border: 1px solid #E8E8E8;
            box-shadow: none !important;
            font-size: 12px;
            border-radius: 0;
            font-family: "Lato";
        }

        .header {

            padding: 25px 0 25px 0;
            width: 100%;
            margin-bottom: 25px;
            text-align: center;
            background-color: #0a3c5f;
            color: #fffdf8;
            font: normal 32px/38px "Lato";
        }

        .captcha-row {
            display: table;
            width: 100%;
        }
        .g-recaptcha {
            display: table-cell;
            float: left;
            vertical-align: middle;
        }

        .button-area {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
            width: 60%
        }

        .submit-button {
            background-color: rgb(1, 178, 255);
            line-height: 54px;
            cursor: pointer;
            color: white;
            border-radius: 5px;
            width: 100%;
            font-size: 22px;
            vertical-align: middle;
            border: 0px;
        }

        form {
            margin: 12px;
        }


        button {
            font: 99% sans-serif;
            margin-top: 15px;
        }
    </style>
    <link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri(); ?>/style.css" media="screen" />
    <script type="text/javascript">
        function validateEmail(email) {
            var re = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
            return re.test(email);
        }
        function validateForm() {
            var element = document.getElementById('email');
            var result = validateEmail(element.value);
            if(result == false) {
                element.style['border'] = '1px solid red';
            }
            return result;
        }
    </script>
    <script src='https://www.google.com/recaptcha/api.js'></script>
</head>
<body>
<div class="header">INVITE A FRIEND</div>
<form method="post" action="#" onsubmit="return validateForm();">
    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
    <input type="hidden" name="sender" value="<?php echo $sender; ?>" />
    <input type="text" class="input-text" id="email" name="email" placeholder="Email Address" />
    <textarea placeholder="Message" name="message" rows="8" cols="40"></textarea>
    <div class="captcha-row">
        <div class="g-recaptcha" data-size="compact" data-sitekey="6Lcx9wkTAAAAAJSXdnRyT9AxO-Eu0jyyvyvtAce7"></div>
        <div class="button-area">
            <input id="submit" class="submit-button" type="submit" name="submit" value="Send Invite""/>
        </div>
    </div>
</form>
</body>
</html>


<?php
function set_html_content_type() {
    return 'text/html';
}