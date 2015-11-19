<form action="admin-post.php" method="post" enctype="multipart/form-data">

    <h3><?php _e('Permissions', 'follow_up_emails'); ?></h3>

    <?php /* @todo Text */ ?>
    <p><?php _e('Select the User Roles that will be given permission to manage Follow-Up Emails', 'follow_up_emails'); ?></p>

    <table class="form-table">
        <tbody>
        <tr valign="top">
            <th><label for="roles"><?php _e('Roles', 'follow_up_emails'); ?></label></th>
            <td>
                <select name="roles[]" id="roles" multiple style="width: 400px;">
                    <?php
                    $roles = get_editable_roles();
                    foreach ( $roles as $key => $role ) {
                        $selected = false;
                        $readonly = '';
                        if (array_key_exists('manage_follow_up_emails', $role['capabilities'])) {
                            $selected = true;

                            if ( $key == 'administrator' ) {
                                $readonly = 'readonly';
                            }
                        }
                        echo '<option value="'. $key .'" '. selected($selected, true, false) .'>'. $role['name'] .'</option>';

                    }
                    ?>
                </select>
                <script>jQuery("#roles").select2();</script>
            </td>
        </tr>
        </tbody>
    </table>

    <?php do_action( 'fue_settings_email' ); ?>

    <p class="submit">
        <input type="hidden" name="action" value="fue_followup_save_settings" />
        <input type="hidden" name="section" value="<?php echo $tab; ?>" />
        <input type="submit" name="save" value="<?php _e('Save Settings', 'follow_up_emails'); ?>" class="button-primary" />
    </p>

</form>