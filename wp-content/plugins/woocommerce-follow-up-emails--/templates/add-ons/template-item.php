<div>
    <h3><?php echo $template->name; ?></h3>

    <a class="thickbox" href="<?php echo $template->image; ?>?TB_iframe=true&width=600&height=600"><img src="<?php echo $template->thumbnail; ?>" /></a>
    <p><?php echo $template->description; ?></p>

    <?php
    $parts = explode('?', $template->url);
    parse_str( $parts[1], $url );
    $template_file = $url['template'] . '.html';

    if ( in_array( $template_file, $installed_templates ) ): ?>
        <p class="installed"><span class="dashicons dashicons-yes"></span> <?php _e('Installed', 'follow_up_emails'); ?></p>
        <p class="uninstall">
            <a href="<?php echo wp_nonce_url( 'admin.php?page=followup-emails-addons&action=uninstall_template&template='. rawurlencode( $template_file ), 'template_uninstall' ); ?>">
                <span class="dashicons dashicons-no"></span> <?php _e('Uninstall', 'follow_up_emails'); ?>
            </a>
        </p>
    <?php else: ?>
        <a class="button" href="<?php echo wp_nonce_url( 'admin.php?page=followup-emails-addons&action=install_template&template='. rawurlencode($template->url), 'template_install' ); ?>"><?php _e('Download', 'follow_up_emails'); ?></a>
    <?php endif; ?>
    <p class="downloads" title="<?php printf( __('%d downloads', 'follow_up_emails'), $template->downloads ); ?>">
        <span class="dashicons dashicons-download"></span> <?php echo number_format( $template->downloads, 0 ); ?>
    </p>
</div>
