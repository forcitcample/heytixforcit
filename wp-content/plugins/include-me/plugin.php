<?php

/*
  Plugin Name: Include Me
  Plugin URI: http://www.satollo.net/plugins/include-me
  Description: Include external HTML or PHP in any post or page.
  Version: 1.1.1
  Author: Stefano Lissa
  Author URI: http://www.satollo.net
 */

if (is_admin()) {
    include dirname(__FILE__) . '/admin.php';
} else {

    function includeme_call($attrs, $content = null) {

        if (isset($attrs['file'])) {
            $file = strip_tags($attrs['file']);
            if ($file[0] != '/')
                $file = ABSPATH . $file;

            ob_start();
            include($file);
            $buffer = ob_get_clean();
            $options = get_option('includeme', array());
            if (isset($options['shortcode'])) {
                $buffer = do_shortcode($buffer);
            }
        } else {
            $tmp = '';
            foreach ($attrs as $key => $value) {
                if ($key == 'src') {
                    $value = strip_tags($value);
                }
                $value = str_replace('&amp;', '&', $value);
                if ($key == 'src') {
                    $value = strip_tags($value);
                }
                $tmp .= ' ' . $key . '="' . $value . '"';
            }
            $buffer = '<iframe' . $tmp . '></iframe>';
        }
        return $buffer;
    }

    // Here because the funciton MUST be define before the "add_shortcode" since
    // "add_shortcode" check the function name with "is_callable".
    add_shortcode('includeme', 'includeme_call');
}
