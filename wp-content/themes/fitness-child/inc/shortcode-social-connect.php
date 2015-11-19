<?php

	add_shortcode('userpro_social_connect2', 'userpro_social_connect_add2' );
	function userpro_social_connect_add2( $args=array() ) {
		global $userpro;
		
		ob_start();
		
		$defaults = array(
			'width' => 'auto',
			'size' => 'normal',
			'facebook' => 1,
			'facebook_title' => __('Sign-up with Facebook','userpro'),
			'facebook_redirect' => 'profile',
			'twitter' => 1,
			'twitter_title' => __('Login with Twitter','userpro'),
			'google' => 1,
			'google_title' => __('Login with Google+','userpro'),
			'vk' => 1,
			'vk_title' => __('ВКонтакте','userpro'),
                        'event_id' => '',
                        'ticket_id' => '',
		);
		$args = wp_parse_args( $args, $defaults );
		extract( $args, EXTR_SKIP );
		
		echo '<div class="userpro-social-big">';
		
			if ( $facebook == 1 && userpro_get_option('facebook_app_id') != '' && userpro_get_option('facebook_connect') == 1) {
				?>
				
				<div id="fb-root" class="userpro-column"></div>
				<script>
				window.fbAsyncInit = function() {
					
					FB.init({
						appId      : "<?php echo userpro_get_option('facebook_app_id'); ?>", // Set YOUR APP ID
						status     : true, // check login status
						cookie     : true, // enable cookies to allow the server to access the session
						xfbml      : true,  // parse XFBML
						version    : "v2.0"
					});
			 
					FB.Event.subscribe('auth.authResponseChange', function(response)
					{
					if (response.status === 'connected')
					{
					//SUCCESS
			 
					}   
					else if (response.status === 'not_authorized')
					{
					//FAILED
					
					} else
					{
					//UNKNOWN ERROR
					
					}
					});
			 
				};
			 
				// Login user
				function Login(element){
					
					var form = jQuery(element).parents('.userpro').find('form'),
                                            email_permision = true;
                                    
					userpro_init_load( form );
					
					if ( element.data('redirect')) {
						var redirect = element.data('redirect');
					} else {
						var redirect = '';
					}
					if ( element.data('eventid')) {
						var eventid = element.data('eventid');
					} else {
						var eventid = '';
					}
					if ( element.data('ticketid')) {
						var ticketid = element.data('ticketid');
					} else {
						var ticketid = '';
					}

					FB.login(function(response) {
                                            if (!response.authResponse){
                                                alert( 'Unauthorized or cancelled' );
                                                userpro_end_load( form );
                                                return false;
                                            }
                                            //Check if email permission is grant
                                            if(response.authResponse.grantedScopes.indexOf('email') === -1){
                                                email_permision = false;
                                            }
                                            // get profile picture
                                            FB.api('/me/picture?type=large', function(response) {
                                                profilepicture = response.data.url;
                                            });

                                            // get profile data and connect via facebook
                                            FB.api('/me', function(response) {
                                                jQuery.ajax({
                                                        url: userpro_ajax_url,
                                                        data: {
                                                            action: 'guestlist_fbconnect',
                                                            id: response.id,
                                                            username: response.username,
                                                            first_name: response.first_name,
                                                            last_name: response.last_name,
                                                            gender: response.gender,
                                                            email: email_permision?response.email:'',
                                                            link: response.link,
                                                            profilepicture: profilepicture,
                                                            redirect: redirect,
                                                            eventid: eventid,
                                                            ticketid: ticketid

                                                        },
                                                        dataType: 'JSON',
                                                        type: 'POST',
                                                        success:function(data){

                                                                userpro_end_load( form );

                                                                /* custom message */
                                                                if (data.custom_message){
                                                                    form.parents('.userpro').find('.userpro-body').prepend( data.custom_message );
                                                                }

                                                                /* redirect after form */
                                                                if (data.redirect_uri){
                                                                    if (data.redirect_uri =='refresh') {
                                                                            document.location.href=jQuery(location).attr('href');
                                                                    } else {
                                                                            document.location.href=data.redirect_uri;
                                                                    }
                                                                }

                                                        },
                                                        error: function(){
                                                                alert('Something wrong happened.');
                                                        }
                                                });

                                            });
					},{scope: 'email' , return_scopes: true});
			 
				}
				
				// Logout
				function Logout(){
					FB.logout(function(){document.location.reload();});
				}
			 
				// Load the SDK asynchronously
				// Load the SDK asynchronously
				(function(d,s,id){
					var js, fjs = d.getElementsByTagName(s)[0];
					if (d.getElementById(id)) {return;}
					js = d.createElement(s); js.id = id;
					js.src = "//connect.facebook.net/en_US/sdk.js";					
					fjs.parentNode.insertBefore(js, fjs);				 	
				}(document, 'script', 'facebook-jssdk'));
			 
				</script>

				<a href="#" class="userpro-social-facebook" data-eventid="<?php echo $event_id; ?>" data-ticketid="<?php echo $ticket_id; ?>" data-redirect="<?php echo $facebook_redirect; ?>" title="<?php $facebook_title; ?>"><i class="userpro-icon-facebook"></i><?php echo $facebook_title; ?></a>

				<?php
			}
			
			/* TWITTER */
			if ( $twitter == 1 && userpro_get_option('twitter_connect') == 1 && userpro_get_option('twitter_consumer_key') && userpro_get_option('twitter_consumer_secret') ) {
				$url = $userpro->get_twitter_auth_url();
				?>
			
				<a href="<?php echo $url; ?>" class="userpro-social-twitter" title="<?php echo $twitter_title; ?>"><i class="userpro-icon-twitter"></i><?php echo $twitter_title; ?></a>
		
				<?php
			}
			
			/* GOOGLE */
			if ( $google == 1 && userpro_get_option('google_connect') == 1 && userpro_get_option('google_client_id') && userpro_get_option('google_client_secret') && userpro_get_option('google_redirect_uri') ) {
				$url = $userpro->get_google_auth_url();
				?>
			
				<a href="<?php echo $url; ?>" class="userpro-social-google" title="<?php echo $google_title; ?>"><i class="userpro-icon-google-plus"></i><?php echo $google_title; ?></a>
				
				<?php
			}
if ( userpro_get_option('linkedin_connect') == 1 && userpro_get_option('linkedin_app_key') && userpro_get_option('linkedin_Secret_Key') ) {
			
				include("linkedinPanel.php");
				?>
			      <a id="wplLiLoginBtn"  class="userpro-social-linkedin" title="<?php echo Linkedin; ?>"><?php echo Linkedin ?></a>
						<?php
	}
	
	/* Instagram */
	
	if ( userpro_get_option('instagram_connect') == 1 && userpro_get_option('instagram_app_key') && userpro_get_option('instagram_Secret_Key') ) {
			
		include("instagramPanel.php");
		?>
				      <a id="wpInLoginBtn"  class="userpro-social-instagram" title="<?php echo Instagram; ?>"><?php echo Instagram ?></a>
							<?php
		}
			/* VK */
			if ( $vk == 1 && class_exists('userpro_vk_api') && userpro_vk_get_option('vk_connect') == 1 && userpro_vk_get_option('vk_api_id') && userpro_vk_get_option('vk_api_secret')  ) {
				global $userpro_vk;
				$url = $userpro_vk->getAuthorizeURL();
				?>
				
				<a href="<?php echo $userpro_vk->getAuthorizeURL(); ?>" class="userpro-social-vk" title="<?php echo $vk_title; ?>"><i class="userpro-icon-vk"></i><?php echo $vk_title; ?></a>
				
				<?php
			}
			
		do_action('userpro_social_connect_buttons_large');
			
		echo '</div><div class="userpro-clear"></div>';
		
		?>
		
		<style type="text/css">
		div.userpro-social-big {
			margin: 0 auto;
			width: <?php echo $width; ?>;
		}
		
		<?php if ($size == 'normal') { ?>
		div.userpro-social-big a {
			padding: 12px 20px;
			font-size: 16px;
		}
		<?php } ?>
		
		<?php if ($size == 'big') { ?>
		div.userpro-social-big a {
			padding: 20px 20px;
			font-size: 19px;
		}
		<?php } ?>
		
		</style>
		
		<?php
		
		$output = ob_get_contents();
		ob_end_clean();
		
		return $output;
		
	}
/**
 * Overriding user pro ajax call for fb connect
 */
/* Facebook Connect */
add_action('wp_ajax_nopriv_guestlist_fbconnect', 'guestlist_fbconnect');
add_action('wp_ajax_guestlist_fbconnect', 'guestlist_fbconnect');
function guestlist_fbconnect(){
    global $userpro;
    $output = '';

    if (!isset($_POST) || ($_POST['action'] != 'guestlist_fbconnect') ||  empty($_POST['id']) || empty($_POST['redirect']) || empty($_POST['eventid']) || empty($_POST['ticketid']) ){
        die();
    }
    
    ob_start();
    extract($_POST);
    
    
    if (!isset($username) || $username == '' || $username == 'undefined')
        $username = $email;
    
    
    /* Check if facebook uid exists */
    if (isset($id) && $id != '' && $id != 'undefined') {
        $users = get_users(array(
            'meta_key' => 'userpro_facebook_id',
            'meta_value' => $id,
            'meta_compare' => '='
        ));
        if (isset($users[0]->ID) && is_numeric($users[0]->ID)) {
            $returning            = $users[0]->ID;
            $returning_user_login = $users[0]->user_login;
        } //isset($users[0]->ID) && is_numeric($users[0]->ID)
        else {
            $returning = '';
        }
    } //isset($id) && $id != '' && $id != 'undefined'
    else {
        $returning = '';
    }
    
    /* Check if user is logged in */
    
    if (userpro_is_logged_in()) {
        //user is logged in. so add the fb data to users account
        $userpro->update_fb_id(get_current_user_id(), $id);
        //Redirect to add to guest list page
        $output['redirect_uri'] = add_query_arg(array('glevent_id' => $eventid, 'glticket_id' => $ticketid, 'glaction'=>'add'), $redirect);
    }
    else {
        //user is not logged in
        if ($returning != '') {
            //Login for returning fb user
            userpro_auto_login($returning_user_login, true);
            //Redirect to add to guest list page
            $output['redirect_uri'] = add_query_arg(array('glevent_id' => $eventid, 'glticket_id' => $ticketid, 'glaction'=>'add'), $redirect);
        }elseif ($email != '' && email_exists($email)) {
            //It is not a returning fb user 
            //But this fb user has an account with same email address
            //Connect them together
            $user_id       = email_exists($email);
            $user          = get_userdata($user_id);
            //Login the existing user
            userpro_auto_login($user->user_login, true);
            //Add fb data to users data
            $userpro->update_fb_id($user_id, $id);

            //Redirect to add to guest list page
            $output['redirect_uri'] = add_query_arg(array('glevent_id' => $eventid, 'glticket_id' => $ticketid, 'glaction'=>'add'), $redirect);

        }else if ($username != '' && username_exists($username)) {
            //Username of FB is same with an existing account
            //Connect them together
            $user_id = username_exists($username);
            $user    = get_userdata($user_id);
            //Login the existing user
            userpro_auto_login($user->user_login, true);
            //Add fb data to users data
            $userpro->update_fb_id($user_id, $id);

            //Redirect to add to guest list page
            $output['redirect_uri'] = add_query_arg(array('glevent_id' => $eventid, 'glticket_id' => $ticketid, 'glaction'=>'add'), $redirect);
            
        }else {
            //FBID not found, email/user not found - fresh user
            if ($email !== 'undefined') {
                //We'll create user after email verification
                //So we need to save the FB user data to eventmeta
                //Prepare the data
                $fb_users_data = get_post_meta($eventid, '_gl_user_preregi_data', true);
                if(!is_array($fb_users_data)){
                    $fb_users_data = array();
                }
                $fb_users_data[$id] = array(
                    'password' => wp_generate_password($length = 12, $include_standard_special_chars = false),
                    'username' => $username,
                    'email'    => $email,
                    'fb_data'  => $_POST,
                );
                update_post_meta($eventid, '_gl_user_preregi_data', $fb_users_data);
                
                $output['redirect_uri'] = add_query_arg(array('glevent_id' => $eventid, 'glticket_id' => $ticketid, 'glaction'=>'fb_form', 'identifier' => $id, 'gl_email' => $email), $redirect);
                
            }
        }
    }
    ob_clean();
    $output = json_encode($output);
    if (is_array($output)) {
        print_r($output);
    } 
    else {
        echo $output;
    }
    die();
}

function htgl_new_user($user_login, $user_password, $user_email, $form, $fb=false) {
    global $wpdb, $userpro;

    $user_id = wp_insert_user( array(
                    'user_login'   => $user_login,
                    'user_pass'    => $user_password,
                    'display_name' => sanitize_title( $user_login ),
                    'user_email'   => $user_email
    ) ); 
    if(empty( $user_id )){
        
        return array('message' => '<strong>ERROR</strong>: Couldn&#8217;t register you. Please contact the webmaster.');
    }
    if ( is_wp_error( $user_id ) ) {
        return array('message' => $user_id->get_error_message());
    }
    

    $userpro->default_role($user_id, $form);
    if($fb){
        userpro_update_profile_via_facebook($user_id, $form );
        $userpro->facebook_save_profile_pic( $user_id, $form['profilepicture'] );
    }else{
        $first_name = (isset($form['first_name'])) ? $form['first_name'] : '';
        $last_name = (isset($form['last_name'])) ? $form['last_name'] : '';
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        if ($first_name || $last_name ) {
            update_user_meta($user_id, 'display_name', sanitize_title($first_name . ' ' . $last_name));
        } else if ($user_email) {
            update_user_meta($user_id, 'display_name', $user_email);
        } 
    }

    $subject = 'Welcome to HeyTix!';
    $headers = "Content-Type: text/html\r\n";

    $first_name = (isset($form['first_name'])) ? $form['first_name'] : 0;
    $last_name = (isset($form['last_name'])) ? $form['last_name'] : 0;

    if(!$first_name){
        $first_name = $user_login;
    }


    $templates_vars = array(
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'email' => $user_email,
        'username' => $user_login,
        'password' => $user_password

    );
    $content = htgl_template_html_output('templates/guestlist/email-welcome.php', $templates_vars);

    wp_mail( $user_email, $subject, $content, $headers );

    return array('user_id'=>$user_id);
}
/**
 * get html output generated by a php template.
 */
function htgl_template_html_output($template, $vars=array()){
    if(is_array($vars)){
        extract($vars);
    }
    ob_start();
    include locate_template($template);
    return ob_get_clean();
}

        