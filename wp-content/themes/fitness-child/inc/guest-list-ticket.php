<?php
class HT_Guest_List{
    private static $instance;
    public $page_title = 'Guest List Confirmation';
    public $page_url;
    public $action;
    public $event_id;
    public $ticket_id;
    public $email;
    public $message;
    public $template;
    public $subheader_title;
    public $wrapper_class;
    private $_fb_tracking_pixel;
    private $_share_buttons;
    /**
     * Constructor of this class
     * Don't allow user to call this directly
     */
    private function __construct() {
        $this->page_url = $this->page_url();
        //Init the system
        add_action('init', array($this, 'init'));
        //Clear guest list when user is deleted
        add_action('deleted_user', array($this, 'deleted_user_guest_list'));
        //Include the base template for guest list
        add_filter('template_include', array($this, 'template_include'));
		//Link the affiliate reerence column to order
		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );
    }
    /**
     * 
     * @return type
     */
    static function get_instance(){
        if(empty(self::$instance)){
            self::$instance = new HT_Guest_List();
        }
        return self::$instance;
    }
    /**
     * Initiate the guest list
     */
    public function init(){
        $this->setup_share_buttons();
        $this->setup_fb_tracking_pixel();

        $this->fix_glaction();
        $this->perfom_actions();
        
    }

    public function setup_fb_tracking_pixel() {
        $this->_fb_tracking_pixel = <<<PIXEL
<!-- Facebook Conversion Code for Guest List Registration -->
<script>(function() {
var _fbq = window._fbq || (window._fbq = []);
if (!_fbq.loaded) {
var fbds = document.createElement('script');
fbds.async = true;
fbds.src = '//connect.facebook.net/en_US/fbds.js';
var s = document.getElementsByTagName('script')[0];
s.parentNode.insertBefore(fbds, s);
_fbq.loaded = true;
}
})();
window._fbq = window._fbq || [];
window._fbq.push(['track', '6024935171338', {'value':'0.00','currency':'USD'}]);
</script>
<noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=6024935171338&amp;cd[value]=0.00&amp;cd[currency]=USD&amp;noscript=1" /></noscript>
PIXEL;
    }

    public function setup_share_buttons() {
        $this->_share_buttons = <<<SHARE
<div class="checkout-social-buttons">
<script>
function fbShare(url, title, descr, image, winWidth, winHeight) {
    var winTop = (screen.height / 2) - (winHeight / 2);
    var winLeft = (screen.width / 2) - (winWidth / 2);
    window.open('http://www.facebook.com/sharer.php?s=100&p[title]=' + title + '&p[summary]=' + descr + '&p[url]=' + url + '&p[images][0]=' + image, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
}

function twitterShare(message, url, winWidth, winHeight) {
    var winTop = (screen.height / 2) - (winHeight / 2);
    var winLeft = (screen.width / 2) - (winWidth / 2);
    window.open('https://twitter.com/share?url=' + url + '&text=' + message + '&hashtags=heytix', 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,location=0,width=' + winWidth + ',height=' + winHeight);
}

function emailShare(sender, event_id, winWidth, winHeight) {
    var winTop = (screen.height / 2) - (winHeight / 2);
    var winLeft = (screen.width / 2) - (winWidth / 2);
    window.open('{SITE_URL}/email-a-friend.php?sender=' + sender + '&event_id=' + event_id, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,location=0,width=' + winWidth + ',height=' + winHeight);
}
</script>
<h2 class="checkout_titles">Share with Friends</h2>
<a class="facebook-btn social_btns fb_check" href="javascript:fbShare('{EVENT_URL}', '{EVENT_TITLE}', '{MESSAGE}', '{IMAGE}', 520, 350)"><i class="fa fa-facebook"></i><span class="check_txt">Facebook</span></a>
<a href="javascript:twitterShare('{MESSAGE}', '{EVENT_URL}', 520, 350)" class="social_btns tw_check"><i class="fa fa-twitter"></i><span class="check_txt">Tweet</span></a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
<a class="facebook-btn social_btns em_check" href="javascript:emailShare('{GUESTLIST_NAME}', '{EVENT_ID}', 520, 485)"><i class="fa fa-envelope"></i><span class="check_txt">Email</span></a>

</div>
SHARE;
    }

    function replace_share_button_placeholders($data) {
        $string = $this->_share_buttons;
        foreach($data as $key => $value){
            $string = str_replace('{'.strtoupper($key).'}', $value, $string);
        }
        return $string;
    }
    /**
     * Fix get/post ans set action. 
     * @return boolean
     */
    public function fix_glaction(){
        if(!empty($_REQUEST['gl_token'])){
            $this->action = 'verify_email';
            return;
        }
        if(empty($_REQUEST['glaction'])){
            return false;
        }
        
        $this->ticket_id    = (int)$_REQUEST['glticket_id'];
        $this->event_id     = (int)$_REQUEST['glevent_id'];
        $this->action       = $_REQUEST['glaction'];
    }

    /**
     * perform some actions before sending any header. These actions might need redirections 
     */
    public function perfom_actions(){
        switch ($this->action){
            case 'add':         $this->addto_guest_list();
                                break;
            case 'fb_form':     $this->show_fb_confirmation_form();
                                break;
            case 'login_form':  $this->show_login_form();
                                break;
            case 'login':       $this->process_login();
                                break;
            case 'send_confirmation':  $this->confirm_email();
                                break;
            case 'verify_email':  $this->verify_email();
                                break;
            case 'signup_form':  $this->show_signup_form();
                                break;
            case 'signup':  $this->signup();
                                break;
            default : break;
        }
    }
    /**
     * Show facebook email confirmation form
     */
    public function show_fb_confirmation_form(){
        if(is_user_logged_in()){
            $this->message[] = 'You are already logged in';
            return false;
        }
        if(empty($_REQUEST['identifier']) || empty($this->event_id) || empty($this->ticket_id)){
            $this->message[] = 'Invalid Request';
            return false;
        }
        
        $this->set_template_data('email-confirmation-form', 'GUEST LIST CONFIRMATION', 'gl-email-confirm-wrap glfb-email-confirm-wrap');
    }
    public function show_signup_form(){
        if(is_user_logged_in()){
            $this->message[] = 'You are already logged in';
            return false;
        }
        if(empty($this->event_id) || empty($this->ticket_id)){
            $this->message[] = 'Invalid Request';
            return false;
        }
        
        $this->set_template_data('signup-form', 'HEYTIX MEMBER GUEST LIST', 'gl-signup-wrap');
    }
    
    /**
     * Show login form
     * @return boolean
     */
    public function show_login_form(){
        if(is_user_logged_in()){
            $this->message[] = 'You are already logged in';
            return false;
        }
        if(empty($this->event_id) || empty($this->ticket_id)){
            $this->message[] = 'Invalid Request';
            return false;
        }
        if(is_user_logged_in()){
            $this->addto_guest_list();
            return;
        }
        $this->set_template_data('login-form', 'GUEST LIST CONFIRMATION', 'gl-login-wrap');
    }
    /**
     * Process user login
     */
    public function process_login(){
        if(empty($this->event_id) || empty($this->ticket_id)){
            $this->message[] = 'Invalid Request';
            return false;
        }
        if(!$this->is_login_data_ready()){
            $this->set_template_data('login-form', 'GUEST LIST CONFIRMATION', 'gl-login-wrap');
            return false;
        }
        $credentials = array();
	$credentials['user_login'] = $_POST['gl_username'];
	$credentials['user_password'] = $_POST['gl_password'];
        $credentials['remember'] = true;
        $user = wp_signon($credentials, false );
        if (is_wp_error($user)){
            $this->message[] =  $user->get_error_message();
            $this->set_template_data('login-form', 'GUEST LIST CONFIRMATION', 'gl-login-wrap');
            return false;
        }
        
        wp_set_current_user( $user->ID, $user->data->user_login );
        $this->addto_guest_list();
    }
    
    /**
     * Check if users login form data is OK
     * @return boolean
     */
    public function is_login_data_ready(){
        if( empty($_POST['gl_ticketid']) || empty($_POST['gl_eventid']) || empty($_POST['gl_username']) || empty($_POST['gl_password']) || empty($_POST['_glnonce'])){
            $this->message[] = 'Please fill all the fields.';
            return false;
        }
        if(!wp_verify_nonce($_POST['_glnonce'], 'gl_login_'.$_POST['gl_ticketid'].$_POST['gl_eventid'])){
            $this->message[] = 'Some thing happened worng. Please try again.';
            return false;
        }
        $this->ticket_id = (int)$_POST['gl_ticketid'];
        $this->event_id  = (int)$_POST['gl_eventid'];
        
        $ticket = get_post($this->ticket_id);
        $event = get_post($this->event_id);
        if(!$ticket){
            $this->message[] = 'Ticket does not exist.';
        }
        if(!$event){
            $this->message[] = 'Event does not exist.';
        }
        if(!$ticket || !$event){
            return false;
        }
        return true;
    }
 
    /**
     * get url to the confirmation page
     * if there is not page created then create new one first
     * @return string
     */
    public function page_url(){
        if(!empty($this->page_url)){
            return $this->page_url;
        }
        $page = get_page_by_title($this->page_title);
        if($page && !is_wp_error($page)){
            return get_permalink($page->ID);
        }else{
            $page_id = wp_insert_post(array(
                'post_type'     => 'page',
                'post_title'    => $this->page_title,
                'post_status'   =>'publish'
            ));
            if($page_id && !is_wp_error($page_id)){
                return get_permalink($page_id);
            }
        }
        return home_url();
    }
    
    /**
     * Set templates data
     * @param type $template
     * @param type $subheader_title
     * @param type $wrapper_class
     */
    public function set_template_data($template, $subheader_title='', $wrapper_class=''){
        //Set page subheader Title
        if($subheader_title){
            $this->subheader_title = $subheader_title;
        }
        //Set wrapper class
        if($wrapper_class){
            $this->wrapper_class = $wrapper_class;
        }
        //Set template
        $this->template = $template;
    }
    /**
     * 
     * @param type $template
     * @return type
     */
    public function template_include($template){
        if($this->is_guest_list()){
            return locate_template('templates/guestlist/base.php');
        }
        return $template;
    }
    /**
     * Get proper template for current action
     * @param type $temp
     * @return boolean
     */
    public function get_template($temp=false){
        if(!$temp){
            $temp = $this->template;
        }
        if($temp && locate_template('templates/guestlist/'.$temp.'.php')){
            $guestlist_ticket_id = (int)$this->ticket_id;
            $guestlist_event_id = (int)  $this->event_id;
             $template = locate_template('templates/guestlist/'.$temp . '.php');
             include $template;
             return true;
        }
        return false;
    }
    /**
     * Get wrapper title
     * @return string
     */
    public function subheader_title(){
        if(!empty($this->subheader_title)){
            return $this->subheader_title;
        }
        
        return 'GUEST LIST CONFIRMATION';
    }
    /**
     * 
     * @return string
     */
    public function wrapper_class(){
        if(!empty($this->wrapper_class)){
            return $this->wrapper_class;
        }
    }
    /**
     * 
     * @return type
     */
    public function is_guest_list(){
        return !empty($_REQUEST['glaction']) || !empty($this->action);
    }
    /**
     * display messages
     */
    public function display_messages(){
        if(is_array($this->message)){
            echo '<div class="htgl-messages">';
            foreach($this->message as $m){
                echo '<p>' . $m . '</p>';
            }
            echo '</div>';
        }
    }
    public function signup(){
        if(is_user_logged_in()){
            $this->message[] = 'You are already logged in';
            return false;
        }
        if(!$this->is_signup_data_ready()){
            $this->set_template_data('signup-form', 'HEYTIX MEMBER GUEST LIST', 'gl-signup-wrap');
            return false;
        }
        $gl_firstname = !empty($_POST['gl_firstname'])?$_POST['gl_firstname']:'';
        $gl_lastname = !empty($_POST['gl_lastname'])?$_POST['gl_lastname']:'';
        
        //Prepare user data to save for later user when user verify email
        $user_data = get_post_meta($eventid, '_gl_user_preregi_data', true);
        if(!is_array($fb_users_data)){
            $fb_users_data = array();
        }
        $identifier = str_replace('_', '', base64_encode($this->email));
        
        $user_data[$identifier] = array(
            'password' => wp_generate_password(),
            'username' => $this->email,
            'email'    => $this->email,
            'data'  => array(
                'first_name' => $gl_firstname,
                'last_name' => $gl_lastname,
                'email' => $this->email,
                'username' => $this->email,
            ),
        );
        update_post_meta($this->event_id, '_gl_user_preregi_data', $user_data);
        //Now send email with confirmation
        $this->send_email($identifier);
    }
    /**
     * 
     * @return boolean
     */
    public function is_signup_data_ready(){
        if( empty($_POST['gl_ticketid']) || empty($_POST['gl_eventid']) || empty($_POST['gl_firstname']) || empty($_POST['gl_lastname']) || empty($_POST['gl_email']) || empty($_POST['_glnonce'])){
            $this->message[] = 'Please fill all the fields.';
            return false;
        }
        if(!wp_verify_nonce($_POST['_glnonce'], 'gl_sinup_'.$_POST['gl_ticketid'].$_POST['gl_eventid'])){
            $this->message[] = 'Some thing happened worng. Please try again.';
            return false;
        }
        $this->ticket_id = (int)$_POST['gl_ticketid'];
        $this->event_id  = (int)$_POST['gl_eventid'];
        $this->email = $_POST['gl_email'];
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->message[] = 'Please enter a valid email address.';
            return false;
        }

        $ticket = get_post($this->ticket_id);
        $event = get_post($this->event_id);
        if(!$ticket){
            $this->message[] = 'Ticket does not exist.';
        }
        if(!$event){
            $this->message[] = 'Event does not exist.';
        }
        if(!$ticket || !$event){
            return false;
        }
        if(email_exists($this->email)){
            $this->message[] = 'An account with this email address already exists.';
            $this->message[] = 'Please login <a href="' . add_query_arg(array('glevent_id' => $this->event_id, 'glticket_id'=>$this->ticket_id, 'glaction'=>'login_form', 'gl_username' => $this->email), $this->page_url() ) . '">here</a>.';
            return false;
        }
        return true;
    }
  
    /**
     * 
     * @return boolean
     */
    public function is_email_data_ready(){
        if( empty($_POST['gl_ticketid']) || empty($_POST['gl_eventid']) || empty($_POST['gl_email']) || empty($_POST['_glnonce'])){
            $this->message[] = 'Please fill all the fields.';
            return false;
        }
        //Verify nonce
        if(!wp_verify_nonce($_POST['_glnonce'], 'gl_confirm_' . $_POST['gl_eventid'])){
            $this->message[] = 'Some thing happened worng. Please try again.';
            return false;
        }
        $this->ticket_id = (int)$_POST['gl_ticketid'];
        $this->event_id  = (int)$_POST['gl_eventid'];
        $this->email = $_POST['gl_email'];
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->message[] = 'Please enter a valid email address.';
            return false;
        }

        $ticket = get_post($this->ticket_id);
        $event = get_post($this->event_id);
        if(!$ticket){
            $this->message[] = 'Ticket does not exist.';
        }
        if(!$event){
            $this->message[] = 'Event does not exist.';
        }
        if(!$ticket || !$event){
            return false;
        }
        return true;
    }
    /**
     * 
     * @return boolean
     */
    public function confirm_email(){
        if(empty($_POST['identifier'])){
            $this->message[] = 'Invalid Request';
            return false;
        }
        if(!$this->is_email_data_ready()){
            $this->set_template_data('email-confirmation-form', 'GUEST LIST CONFIRMATION', 'gl-email-confirm-wrap glfb-email-confirm-wrap');
            return false;
        }
        $this->send_email($_POST['identifier']);
    }
    /**
     * 
     * @return boolean
     */
    public function send_email($identifier){
        $ticket = get_post($this->ticket_id);
        $event = get_post($this->event_id);
        $ticket_confirmation_token = $identifier . '_'. $this->ticket_id . '_' . $this->event_id . '___' . md5($this->ticket_id . $this->event_id);
        $ticket_tokens = get_post_meta($this->ticket_id, '_gl_email_tockens', true);
        if(!is_array($ticket_tokens)){
            $ticket_tokens = array();
        }
        $ticket_tokens[$identifier . '_'. $this->ticket_id . '_' . $this->event_id] = array(
            'token'     => $ticket_confirmation_token,
            'event_id'  => $this->event_id,
            'email'     => $this->email,
        );

        $subject = 'ONE LAST STEP TO GET ON THE GUEST LIST';
        $headers = "Content-Type: text/html\r\n";
        $content = $this->email_body($ticket, $event, $ticket_confirmation_token);
        $return = wp_mail( $this->email, $subject, $content, $headers );
        if($return){
            update_post_meta($this->ticket_id, '_gl_email_tockens', $ticket_tokens);
            $this->set_template_data('email-sent', 'PLEASE CHECK YOUR EMAIL');
        }else{
            $this->message[] = 'Something happened worng. Please try later.';
        }
    }
    /**
     * 
     * @param type $ticket
     * @param type $event
     * @param type $ticket_confirmation_token
     * @return type
     */
    public function email_body($ticket, $event, $ticket_confirmation_token){
        ob_start();
        $confirm_link = add_query_arg('gl_token',$ticket_confirmation_token, $this->page_url);
        include locate_template('templates/guestlist/email-body.php');;
        return ob_get_clean();
    }
    /**
     * 
     */
    public function verify_email(){
        if(empty($_GET['gl_token'])){
            return false;
        }
        $token = $_GET['gl_token'];
        $meta_array_key = substr($token, 0, strpos($token, '___'));
        $key_parts = explode('_', $meta_array_key);
        $ticket_id = $event_id = $identifier = false;
        
        if(!empty($key_parts[1])){
            $identifier = $key_parts[0];
        }
        if(!empty($key_parts[1])){
            $ticket_id = (int)$key_parts[1];
        }
        if(!empty($key_parts[2])){
            $event_id = (int)$key_parts[2];
        }

        if(
                !$identifier 
                || !$event_id 
                || !$ticket_id
                || !($preregidata = get_post_meta($event_id, '_gl_user_preregi_data', true)) 
                || !is_array($preregidata)
                || !array_key_exists($identifier, $preregidata)
                || !($token_meta = get_post_meta($ticket_id, '_gl_email_tockens', true)) 
                || !is_array($token_meta)
                || !array_key_exists($meta_array_key, $token_meta)
                || empty($token_meta[$meta_array_key]['token'])
                || ($token_meta[$meta_array_key]['token'] != $token)
        ){
            $this->message[] = 'This guest list request has been fulfilled or expired. If you feel you got this message in error please contact <a href="mailto:gettix@heytix.com" title="">gettix@heytix.com</a>';
            return false;
        }
        //Check if the fb user allow email permission
        if($preregidata[$identifier]['email']){
            $user_email = $preregidata[$identifier]['email'];
        }else{
            $user_email = $token_meta[$meta_array_key]['email'];
        }
        //Same check for fb user name
        if($preregidata[$identifier]['username']){
            $user_name = $preregidata[$identifier]['username'];
        }else{
            $user_name = $token_meta[$meta_array_key]['email'];
        }
        
        //Everything is ok
        //Now create user and send welcome email
        //and add them to guestlist
        if(isset($preregidata[$identifier]['fb_data'])){
            $user_data = htgl_new_user($user_name, $preregidata[$identifier]['password'], $user_email, $preregidata[$identifier]['fb_data'], true);
        }else{
            $user_data = htgl_new_user($user_name, $preregidata[$identifier]['password'], $user_email, $preregidata[$identifier]['data'], false);
        }
        $user_confirm_email = $token_meta[$meta_array_key]['email'];

        $credentials = array();
	$credentials['user_login'] = $user_name;
	$credentials['user_password'] = $preregidata[$identifier]['password'];
        $credentials['remember'] = true;
        //Update user preregi data
        unset($preregidata[$identifier]);
        update_post_meta($event_id, '_gl_user_preregi_data', $preregidata);
        //Update token meta
        unset($token_meta[$meta_array_key]);
        update_post_meta($ticket_id, '_gl_email_tockens', $token_meta);

        if(empty($user_data['user_id'])){
            $this->message[] = $user_data['message'];
            return false;
        }
        update_user_meta($user_data['user_id'], '_gl_confirmation_email', $user_confirm_email);

        $user = wp_signon($credentials, false );

        if (is_wp_error($user)){
            $this->message[] =  $user->get_error_message();
            return false;
        }
        wp_set_current_user( $user->ID, $user->data->user_login );
        if($order_id = $this->create_order($ticket_id, $event_id, $user->ID)){
            add_post_meta($event_id, '_guest_list', $user->ID);
            $this->message[] = 'Thank you for registering. Enjoy the event.';
            $this->message[] = $this->_fb_tracking_pixel;

            $event = get_post($event_id);
            $image = '';
            if (has_post_thumbnail( $this->event_id ) ) {
                $image = wp_get_attachment_image_src( get_post_thumbnail_id( $this->event_id ), 'single-post-thumbnail' );
                $image = $image[0];
            }
            $data = array(
                'event_title' => urlencode($event->post_title),
                'message' => urlencode("I am going to see ".$event->post_title.", you should too, get your ticket now and join me for a fun night out!"),
                'event_url' => urlencode(get_site_url().'/event/'.$event->post_name.'/'),
                'guestlist_name' => trim(get_user_meta($user->ID, 'first_name', true).' '.get_user_meta($user->ID, 'last_name', true)),
                'image' => $image,
                'site_url' => get_site_url(),
            );


            $this->message[] = $this->replace_share_button_placeholders($data);

			//Affiliate conversion
			$this->convert_affilate($order_id, $event_id);
        }
    }
    
    /**
     * Add to guest list
     */
    public function addto_guest_list(){
        $current_user = wp_get_current_user();
        $ticket = get_post($this->ticket_id);
        $event = get_post($this->event_id);
        if(!$ticket){
            $this->message[] = 'Ticket does not exist.';
        }
        if(!$event){
            $this->message[] = 'Event does not exist.';
        }
        if(!$current_user || !$ticket || !$event){
            return false;
        }
        $event_guest_ids = get_post_meta($this->event_id, '_guest_list', false);
        if(in_array($current_user->ID, $event_guest_ids)){
            $this->message[] = 'You are already signed up for this guest list. Enjoy the event.';
        }elseif($order_id = $this->create_order($this->ticket_id, $this->event_id, $current_user->ID)){
            add_post_meta($this->event_id, '_guest_list', $current_user->ID);

            $this->message[] = 'Thank you for registering. Enjoy the event.';
            $this->message[] = $this->_fb_tracking_pixel;

            $event = get_post($this->event_id);
            $image = '';
            if (has_post_thumbnail( $this->event_id ) ) {
                $image = wp_get_attachment_image_src( get_post_thumbnail_id( $this->event_id ), 'single-post-thumbnail' );
                $image = $image[0];
            }

            $data = array(
                'event_title' => urlencode($event->post_title),
                'message' => urlencode("I am going to see ".$event->post_title.", you should too, get your ticket now and join me for a fun night out!"),
                'event_id' => $event->ID,
                'guestlist_name' => trim(get_user_meta($current_user->ID, 'first_name', true).' '.get_user_meta($current_user->ID, 'last_name', true)),
                'image' => $image,
                'site_url' => get_site_url(),
            );
            $this->message[] = $this->replace_share_button_placeholders($data);

            //Affiliate conversion
			$this->convert_affilate($order_id, $this->event_id);
        }
    }
    
    /**
     * 
     * @param type $ticket_id
     * @param type $event_id
     * @param type $user_id
     * @return boolean
     */
    public function create_order($ticket_id, $event_id, $user_id){
        $args = array('customer_id'   => $user_id);
        $order = wc_create_order($args);
        if($order){
            $product = new WC_Product($ticket_id);
            $user_data  = get_userdata($user_id);
            $billing_email = $user_data->user_email;
            $user_confirmation_email = get_user_meta($user_id, '_gl_confirmation_email', true);
            if($user_confirmation_email){
                $billing_email = $user_confirmation_email;
            }
            update_post_meta($order->id, '_billing_first_name', $user_data->user_firstname);
            update_post_meta($order->id, '_billing_last_name', $user_data->user_lastname);
            update_post_meta($order->id, '_billing_email', $billing_email);
            $order->add_product($product, 1);
            
            add_filter('woocommerce_email_enabled_customer_completed_order', '__return_false', 20);
            add_filter('wootickets-tickets-email-enabled', '__return_false', 20);
            $order->update_status('completed');
            remove_filter('woocommerce_email_enabled_customer_completed_order', '__return_false', 20);
            remove_filter('wootickets-tickets-email-enabled', '__return_false', 20);
            return $order->id;
        }
        return false;
    }
    
    public function deleted_user_guest_list($user_id){
        delete_postmeta_by_key_value('_guest_list', $user_id);
    }
    
    /**
     * Display links on ticket form
     * @global type $woocommerce
     * @param type $post_id
     * @return string
     */
    public function ticket($post_id='', $template=''){
        global $woocommerce;

        $template = ($template == '') ? 'templates/guestlist/ticket.php' : $template;

        //Get the event in action
        if($post_id){
            $post = get_post($post_id);
        }else{
            $post = get_post();
            $post_id = $post->ID;
        }
        //Get tickets associated this event
        $wootickets = TribeWooTickets::get_instance();
        $ticket_ids = $wootickets->get_Tickets_ids( $post->ID );

        if(empty($ticket_ids)){
            return '';
        }
        //We have tickets
        //Now check if there is any free guest list(sku - GL) ticket
        $guestlist_ticket_id = '';
        foreach($ticket_ids as $ticket_id){
            if(class_exists('WC_Product_Simple')){
                $product = new WC_Product_Simple($ticket_id);
            } else {
                $product = new WC_Product($ticket_id);
            }

            //We will check the SKU and the price
            //Only free ticket with SKU starting with GL
            if( (strpos($product->get_sku(), 'GL') === 0) && ( is_null($product->get_price()) || (0 == (float)$product->get_price()) ) ) {
                $guestlist_ticket_id = $product->id;
                break;
            }
        }

        //If no geust list ticket found good to stop here
        if(!$guestlist_ticket_id){
            return '';
        }

        //We get the guest list ticket
        ob_start();
        $template = locate_template($template);
        if($template){
            $guest_list_page_url = $this->page_url;
            include $template;
        }
        return ob_get_clean();
    }
	/**
	 * Affiliate WP referal converion
	 * @param type $order_id
	 * @param type $event_id
	 * @return boolean
	 */
	public function convert_affilate($order_id = false, $event_id = false){
		if ( !function_exists( 'affiliate_wp' ) || !$order_id || !$event_id ) {
			return false;
		}
		//Check if refered
		if ( !affiliate_wp()->tracking->was_referred() ) {
			return false;
		}
		//Get the affiliate ID
		$affiliate_id	 = affiliate_wp()->tracking->get_affiliate_id();
		$visit_id		 = affiliate_wp()->tracking->get_visit_id();
		$amount			 = get_post_meta( $event_id, 'htmh_event_glrf_amount', true );
		$description	 = 'Guest List Sign Up';
		$description	 = get_the_title( $event_id );

		// Store the visit in the DB
		$referal_id		 = affiliate_wp()->referrals->add( array(
			'affiliate_id'	 => $affiliate_id,
			'amount'		 => $amount,
			'status'		 => 'unpaid',
			'description'	 => $description,
			'context'		 => 'Guest List',
			'reference'		 => $order_id,
			'visit_id'		 => $visit_id,
			) );
		if( $referal_id ) {
			affiliate_wp()->visits->update( $visit_id, array( 'referral_id' => $referal_id ) );
			setcookie( 'affwp_ref', $visit_id, strtotime( '-2 days' ), COOKIEPATH, COOKIE_DOMAIN );
			setcookie( 'affwp_ref_visit_id', $visit_id, strtotime( '-2 days' ), COOKIEPATH, COOKIE_DOMAIN );
			if(  function_exists('affwp_currency_filter')){
				$amount = affwp_currency_filter( affwp_format_amount( $amount ) );
			}
			$name   = affiliate_wp()->affiliates->get_affiliate_name( $this->affiliate_id );
			$order = new WC_Order( $order_id );
			$order->add_order_note( sprintf( __( 'Referral #%d for %s recorded for %s', 'affiliate-wp' ), $referral_id, $amount, $name ) );

		}
	}
	/**
	 * Setup the reference link
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function reference_link( $reference = 0, $referral ) {

		if( empty( $referral->context ) || 'Guest List' != $referral->context ) {

			return $reference;

		}
		
		$url = get_edit_post_link( $reference );

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
	}

}

/**
 *Utility Function used in guest list 
 * 
 */

/**
 * Get page url for guest list signup
 * @return string
 */
function htgl_get_page_url(){
    $instance = HT_Guest_List::get_instance();
    return $instance->page_url();
}
/**
 * Get Ticket template
 * @param type $post_id
 * @return string
 */
function ht_guest_list_ticket($post_id=''){
    return HT_Guest_List::get_instance()->ticket($post_id);
    
}

/**
 * Get current template for guest list action
 * @param type $temp
 */
function ht_guest_list_template($temp=false){
    $instance = HT_Guest_List::get_instance();
    $instance->get_template();
}

/**
 * Check if guest list page is bieng viewed
 * @return bool
 */
function ht_is_guest_lsit_page(){
    return HT_Guest_List::get_instance()->is_guest_list();
}
/**
 * Get subheader title for current action
 * @return string
 */
function htgl_subheader_title(){
    $instance = HT_Guest_List::get_instance();
    return $instance->subheader_title();
}
/*
 * Display messages generated by the system
 */
function htgl_display_messages(){
    $instance = HT_Guest_List::get_instance();
    $instance->display_messages();
}
/**
 * Get Container class for the current action
 */
function htgl_wrapper_class(){
    $instance = HT_Guest_List::get_instance();
    return $instance->wrapper_class();
}
/**
 * Check to show signup links
 * @param type $event_id
 * @return boolean
 */
function htgl_show_siggnup_links($event_id){
    if(!is_user_logged_in()) return true;
    $user_id = get_current_user_id();
    if(!$user_id)return true;
    if(!_htgl_user_has_ticket($user_id, $event_id)){
        return true;
    }
    return false;
}
/**
 * Check if the user has already enlisted in guest list
 * @param type $user_id
 * @param type $event_id
 * @return type
 */
function _htgl_user_has_ticket($user_id, $event_id){
    $event_guest_ids = get_post_meta($event_id, '_guest_list', false);
    
    return is_array($event_guest_ids) && in_array($user_id, $event_guest_ids);
}
/**
 * Delete all meta row with a optional value for all posts
 * @param type $meta_key
 * @param type $meta_value
 * @return type null
 */
function delete_postmeta_by_key_value($meta_key, $meta_value = '') {
    if(!$meta_value){
        return delete_metadata( 'post', null, $meta_key, '', true );
    }else{
        delete_metadata( 'post', null, $meta_key, $meta_value, true );
    }
    
}
/**
 * Loader for the Guest List System
 */
function htgl_class_loader(){
    return HT_Guest_List::get_instance();
}
//Load the system
htgl_class_loader();