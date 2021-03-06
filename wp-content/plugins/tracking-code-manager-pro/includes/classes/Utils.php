<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TCMP_Utils {

    function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    function endsWith($haystack, $needle) {
        $length = strlen($needle);
        $start = $length * -1; //negative
        return (substr($haystack, $start) === $needle);
    }

    function isAdminUser() {
        //https://wordpress.org/support/topic/how-to-check-admin-right-without-include-pluggablephp
        return TRUE;
        /*
        if (!function_exists('wp_get_current_user')) {
            require_once(ABSPATH . 'wp-includes/pluggable.php');
        }
        return (is_multisite() || current_user_can('manage_options'));
        */
    }

    //verifica se il parametro needle è un elemento dell'array haystack
    //se il parametro needle è a sua volta un array verifica che almeno un elemento
    //sia contenuto all'interno dell'array haystack
    function inArray($needle, $haystack) {
        if (is_string($haystack)) {
            //from string to numeric array
            $temp = explode(',', $haystack);
            $haystack = array();
            foreach ($temp as $v) {
                $v = trim($v);
                $v = intval($v);
                if ($v > 0) {
                    $haystack[] = $v;
                }
            }
        }

        $result = FALSE;
        foreach ($haystack as $v) {
            $v = intval($v);
            //if one element of the array have -1 value means i select "all" option
            if ($v < 0) {
                $result = TRUE;
                break;
            }
        }

        if ($result) {
            return TRUE;
        }

        $result = FALSE;
        if (is_array($needle)) {
            foreach ($needle as $v) {
                $v = trim($v);
                $v = intval($v);
                if (in_array($v, $haystack)) {
                    $result = TRUE;
                    break;
                }
            }
        } else {
            //built-in comparison
            $result = in_array($needle, $haystack);
        }
        return $result;
    }

    function is($name, $compare, $default='', $ignoreCase=TRUE) {
        $what=$this->qs($name, $default);
        $result=FALSE;
        if(is_string($compare)) {
            $compare=explode(',', $compare);
        }
        if($ignoreCase){
            $what=strtolower($what);
        }

        foreach($compare as $v) {
            if($ignoreCase){
                $v=strtolower($v);
            }
            if($what==$v) {
                $result=TRUE;
                break;
            }
        }
        return $result;
    }

    public function twitter($name) {
        ?>
        <a href="https://twitter.com/<?php echo $name?>" class="twitter-follow-button" data-show-count="false" data-dnt="true">Follow @<?php echo $name?></a>
        <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
    <?php
    }

    //per ottenere un campo dal $_GET oppure dal $_POST
    function qs($name, $default = '') {
        $result = $default;
        if (isset($_GET[$name])) {
            $result = $_GET[$name];
        } elseif (isset($_POST[$name])) {
            $result = $_POST[$name];
        }

        if (is_string($result)) {
            //$result = urldecode($result);
            $result = trim($result);
        }

        return $result;
    }

    function query($query, $args = NULL) {
        global $tcmp;

        $defaults = array(
            'post_type' => ''
            , 'all' => FALSE

        );
        $args = $tcmp->Utils->parseArgs($args, $defaults);

	if($query==TCMP_QUERY_CONVERSION_PLUGINS) {
            $array=$tcmp->Ecommerce->getPlugins(FALSE);
            $result=array();
            foreach($array as $k=>$v) {
                $result[]=$v;
            }
        } else {
	        $result = $tcmp->Options->getCache('Query', $query . '_' . $args['post_type']);
	        if (!is_array($result) || count($result) == 0) {
	            $q = NULL;
	            $id = 'ID';
	            $name = 'post_title';
	            switch ($query) {
	                case TCMP_QUERY_POSTS_OF_TYPE:
	                    $options = array('posts_per_page' => -1, 'post_type' => $args['post_type']);
	                    $q = get_posts($options);
	                    break;
	                case TCMP_QUERY_CATEGORIES:
	                    $options = array('posts_per_page' => -1);
	                    $q = get_categories($options);
	                    $id = 'cat_ID';
	                    $name = 'cat_name';
	                    break;
	                case TCMP_QUERY_TAGS:
	                    $q = get_tags();
	                    $id = 'term_id';
	                    $name = 'name';
	                    break;
	            }

	            $result = array();
	            if ($q) {
	                foreach ($q as $v) {
	                    $result[] = array('id' => $v->$id, 'name' => $v->$name);
	                }
	            } elseif ($query == TCMP_QUERY_POST_TYPES) {
	                $options = array('public' => TRUE, '_builtin' => FALSE);
	                $q = get_post_types($options, 'names');
	                $q = array_merge($q, array('post', 'page'));
	                sort($q);
	                foreach ($q as $v) {
	                    $result[] = array('id' => $v, 'name' => $v);
	                }
	            }

	            $tcmp->Options->setCache('Query', $query . '_' . $args['post_type'], $result);
	        }

	        if ($args['all']) {
	            $first = array();
	            $first[] = array('id' => -1, 'name' => '[' . $tcmp->Lang->L('All') . ']');
	            $result = array_merge($first, $result);
	        }
	}

        return $result;
    }

    //send remote request to our server to store tracking and feedback
    function remotePost($action, $data = '') {
        global $tcmp;

        $data['secret'] = 'WYSIWYG';
        $response = wp_remote_post(TCMP_INTELLYWP_RECEIVER . '?iwpm_action=' . $action, array(
            'method' => 'POST'
            , 'timeout' => 20
            , 'redirection' => 5
            , 'httpversion' => '1.1'
            , 'blocking' => TRUE
            , 'body' => $data
            , 'user-agent' => 'TCM/' . TCMP_PLUGIN_VERSION . '; ' . get_bloginfo('url')
        ));
        $data = json_decode(wp_remote_retrieve_body($response), TRUE);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200
            || !isset($data['success']) || !$data['success']
        ) {
            $tcmp->Logger->error('ERRORS SENDING REMOTE-POST ACTION=%s DUE TO REASON=%s', $action, $response);
            $data = FALSE;
        } else {
            $tcmp->Logger->debug('SUCCESSFULLY SENT REMOTE-POST ACTION=%s RESPONSE=%s', $action, $data);
        }
        return $data;
    }

    //wp_parse_args with null correction
    function parseArgs($args, $defaults) {
        if (is_null($args) || !is_array($args)) {
            $args = array();
        }
        foreach ($args as $k => $v) {
            if (is_null($args[$k])) {
                //so can take the default value
                unset($args[$k]);
            } elseif (is_string($args[$k]) && $args[$k] == '' && isset($defaults[$k]) && is_array($defaults[$k])) {
                //a very strange case, i have a blank string for rappresenting an empty array
                unset($args[$k]);
            }
        }
        $result = wp_parse_args($args, $defaults);
        return $result;
    }

    function redirect($location) {
        //seems that if you have installed xdebug (or some version of it) doesnt work so js added
        if(!headers_sent()) {
            wp_redirect($location);
        }
        ?>
        <script> window.location.replace('<?php echo $location?>'); </script>
        <?php
        exit();
        ?>
    <?php }
}
