<?php
function tcmp_ui_about() {
    global $tcmp;

    $tcmp->Options->pushSuccessMessage($tcmp->Lang->L('AboutNotice'));
    $tcmp->Options->writeMessages();

    ?>
    <div><?php $tcmp->Lang->P('AboutText')?></div>
    <style>
        ul li {
            padding:2px;
        }
    </style>
    <ul>
        <li>
            <img style="float:left; margin-right:10px;" src="<?php echo TCMP_PLUGIN_IMAGES?>email.png" />
            <a href="mailto:aleste@intellywp.com">aleste@intellywp.com</a>
        </li>
        <li>
            <img style="float:left; margin-right:10px;" src="<?php echo TCMP_PLUGIN_IMAGES?>twitter.png" />
            <?php $tcmp->Utils->twitter('intellywp')?>
        </li>
        <li>
            <img style="float:left; margin-right:10px;" src="<?php echo TCMP_PLUGIN_IMAGES?>internet.png" />
            <a href="http://intellywp.com/?utm_source=pro-users&utm_medium=tcm-about&utm_campaign=TCM" target="_new">IntellyWP.com</a>
        </li>
    </ul>
    <?php
}