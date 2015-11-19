<?php

	/* add custom badges */
	add_filter('userpro_show_badges', 'userpro_badges_show');
	function userpro_badges_show($user_id){
		global $userpro_badges;
		$output = null;
		
		/* Find user badges (get_user_meta - _userpro_badges) */
		$get_badges = $userpro_badges->get_badges($user_id);
		if (is_array($get_badges)){
			foreach($get_badges as $key => $badge) {
				if (isset($badge['badge_url'])) {
					$sanitized = preg_replace('/\s*/', '', $badge['badge_title'] );
					$sanitized = strtolower($sanitized);
					$output .= '<img class="userpro-profile-badge userpro-profile-badge-'.$sanitized.'" src="'.$badge['badge_url'].'" alt="" title="'.$badge['badge_title'].'" />';
				}
			}
		}
		
		return $output;
	
	}
add_action('userpro_after_new_registration', "default_badge_for_registration");
	function default_badge_for_registration($user_id)
	{
		$result=get_option( 'userpro_defaultbadge' );
		if($result['defaultbadge']=='1')
		{
		$badges = get_user_meta($user_id, '_userpro_badges', true);
		
		// find if that badge exists
		if (is_array($badges)){
			foreach($badges as $k => $badge){
				if ( $badge['badge_url'] == $badge_url ) {
					unset($badges[$k]);
				}
				if ( $badge['badge_title'] == $badge_title ) {
					unset($badges[$k]);
				}
			}
			update_user_meta($user_id, '_userpro_badges', true);
		}
		
		// add new badge to user
		$badges[] = array(
				'badge_url' => $result['badge_url'],
				'badge_title' => $result['badge_title'],
				'badge_default'=>'yes'

		);
		update_user_meta($user_id, '_userpro_badges', $badges);
		
		}
	}
	
	add_action('admin_enqueue_scripts', 'userpro_badges_admin_scripts');
	
	function userpro_badges_admin_scripts() {
		if (isset($_GET['page']) && $_GET['page'] == 'userpro-badges') {
			wp_enqueue_media();
			wp_register_script('my-admin-js', WP_PLUGIN_URL.'/my-plugin/my-admin.js', array('jquery'));
			wp_enqueue_script('my-admin-js');
		}
	}
