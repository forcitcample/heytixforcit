<div class="wrap">
    <h2>
        <?php _e('Manage Subscribers', 'follow_up_emails'); ?>
    </h2>

    <?php if (isset($_GET['deleted']) && $_GET['deleted'] > 0): ?>
        <div id="message" class="updated"><p><?php printf( _n('1 email has been deleted', '%d emails have been deleted', intval($_GET['deleted']), 'follow_up_emails'), intval($_GET['deleted'])); ?></p></div>
    <?php endif; ?>

    <?php if (isset($_GET['added'])): ?>
        <div id="message" class="updated"><p><?php printf(__('<em>%s</em> has been added', 'follow_up_emails'), strip_tags($_GET['added'])); ?></p></div>
    <?php endif; ?>

    <?php if (isset($_GET['imported'])): ?>
        <div id="message" class="updated"><p><?php printf( _n('1 has been added', '%d emails have been added', intval($_GET['imported']), 'follow_up_emails'), intval($_GET['imported'])); ?></p></div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) ): ?>
        <div id="message" class="error"><p><?php echo esc_html($_GET['error']); ?></p></div>
    <?php endif; ?>

    <form action="admin-post.php" method="post" enctype="multipart/form-data">

        <div class="tablenav top">

            <div class="alignleft actions bulkactions">
                <input type="email" name="email" placeholder="Add email" />
                <input type="submit" name="button_add" id="post-query-submit" class="button" value="<?php _e('Add', 'follow_up_emails'); ?>">
            </div>
            <br class="clear">
        </div>
        <table class="wp-list-table widefat fixed posts manual-table" style="width: 600px;">
            <thead>
            <tr>
                <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></th>
                <th scope="col" class="manage-column column-email_address" style=""><?php _e('Email Address', 'follow_up_emails'); ?></th>
                <th scope="col" class="manage-column column-date" style="width:200px;"><?php _e('Date', 'follow_up_emails'); ?></th>
                <th scope="col" class="manage-column column-actions" style="width: 50px;">&nbsp;</th>
            </tr>
            </thead>
            <tbody id="the_list">
            <?php
            $date_format = get_option('date_format') .' '. get_option('time_format');
            $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_subscribers ORDER BY date_added DESC");

            if ( empty($rows) ):
                ?>
                <tr>
                    <td colspan="4" align="center"><?php _e('No rows found', 'followup_emails'); ?></td>
                </tr>
            <?php else:
                foreach ($rows as $row):
                    ?>
                    <tr>
                        <th class="check-column"><input type="checkbox" id="cb-select-<?php echo esc_attr($row->id); ?>"  name="email[]" value="<?php echo esc_attr($row->id); ?>" /></th>
                        <td><?php echo esc_html($row->email); ?></td>
                        <td><?php echo date( $date_format, strtotime($row->date_added) ); ?></td>
                        <td></td>
                    </tr>
                <?php
                endforeach;
            endif;
            ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="action2">
                    <option value="delete"><?php _e('Remove emails', 'follow_up_emails'); ?></option>
                </select>
                <input type="submit" name="button_delete" id="doaction2" class="button action" value="Apply">
            </div>
        </div>

        <h3><?php _e('Upload CSV of Emails', 'follow_up_emails'); ?></h3>

        <input type="file" name="csv" />
        <br/>
        <input type="submit" name="button_csv" class="button" value="<?php _e('Upload CSV', 'follow_up_emails'); ?>" />

        <input type="hidden" name="action" value="fue_subscribers_manage" />
    </form>
</div>