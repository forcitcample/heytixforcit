<?php
get_header();
?>

    <div class="row page-wrapper guest-list">
        <article class="page type-page hentry full">
            <div class="page-content">
                <div class="guest-list-form <?php echo htgl_wrapper_class() ?>">
                    <?php htgl_display_messages(); ?>
                    <?php ht_guest_list_template(); ?>
                </div>
            </div>
        </article>

	</div>
<?php get_footer();?>