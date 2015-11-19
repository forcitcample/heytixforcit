<?php
if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'save')) {
    if (isset($_POST['options'])) {
        $options = stripslashes_deep($_POST['options']);
        update_option('includeme', $options);
    } else {
        update_option('includeme', array());
    }
} else {
    $options = get_option('includeme', array());
}
?>
<div class="wrap">
    <div id="satollo-header">
        <a href="http://www.satollo.net/plugins/include-me" target="_blank">Get Help</a>
        <a href="http://www.satollo.net/forums" target="_blank">Forum</a>

        <form style="display: inline; margin: 0;" action="http://www.satollo.net/wp-content/plugins/newsletter/do/subscribe.php" method="post" target="_blank">
            Subscribe to satollo.net <input type="email" name="ne" required placeholder="Your email">
            <input type="hidden" name="nr" value="include-me">
            <input type="submit" value="Go">
        </form>
        <!--
        <a href="https://www.facebook.com/satollo.net" target="_blank"><img style="vertical-align: bottom" src="http://www.satollo.net/images/facebook.png"></a>
        -->
        <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5PHGDGNHAYLJ8" target="_blank"><img style="vertical-align: bottom" src="http://www.satollo.net/images/donate.png"></a>
        <a href="http://www.satollo.net/donations" target="_blank">Even <b>2$</b> helps: read more</a>
    </div>

    <h2>Include Me</h2>

    <p>
        Read
        <a href="http://www.satollo.net/plugins/include-me" target="_blank">the official documentation</a>
        on how to use the short tag [includeme] in your posts or pages.
    </p>

    <form action="" method="post">
        <?php wp_nonce_field('save') ?>
        <table class="form-table">
            <tr>
                <th>Execute shortcodes on included files</th>
                <td>
                    <input type="checkbox" name="options[shortcode]" value="1" <?php echo isset($options['shortcode']) ? 'checked' : ''; ?>>
                    <p class="description">
                        if checked short codes (like [gallery]) contained in included files will be executed as if they where inside the
                        post or page body content. Probably usage of this feature is very rare.
                    </p>
                </td>
            </tr>    
        </table>
        <p class="submit">
            <input class="button button-primary" type="submit" name="save" value="Save"/>
        </p>
    </form>

</div>
