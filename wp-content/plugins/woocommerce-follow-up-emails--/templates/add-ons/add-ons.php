<div class="wrap fue-addons-wrap">
    <h2><?php _e('Follow-Up Emails Add-Ons', 'follow_up_emails'); ?></h2>

    <?php include FUE_TEMPLATES_DIR .'/add-ons/notifications.php'; ?>

    <?php if ( isset($_GET['action']) && $_GET['action'] == 'install_template' ):
        include FUE_TEMPLATES_DIR . '/add-ons/install.php';
    elseif ( isset($_GET['action']) && $_GET['action'] == 'uninstall_template' ):
        include FUE_TEMPLATES_DIR . '/add-ons/uninstall.php';
    else: ?>
        <h3><?php _e('Apps', 'follow_up_emails'); ?></h3>

        <ul class="fue-addons">
            <?php
            $fue_addons = new FUE_Addons( Follow_Up_Emails::instance() );
            $addons = $fue_addons->get_addons();

            foreach ( $addons as $id => $addon ):
                ?>
                <li><?php include FUE_TEMPLATES_DIR .'/add-ons/add-on-item.php'; ?></li>
            <?php
            endforeach;
            ?>
        </ul>

        <h3><?php _e('Templates', 'follow_up_emails'); ?></h3>

        <ul class="fue-templates">
            <?php
            $templates = $fue_addons->get_templates();
            $installed_templates = array_map( 'basename', fue_get_installed_templates() );

            if ( empty( $templates ) ) {
                echo '<li>'. __('No templates available', 'follow_up_emails') .'</li>';
            } else {
                foreach ( $templates as $id => $template ):
                    ?>
                    <li><?php include FUE_TEMPLATES_DIR .'/add-ons/template-item.php'; ?></li>
                <?php
                endforeach;
            }
            ?>
        </ul>
    <?php endif; ?>

</div>