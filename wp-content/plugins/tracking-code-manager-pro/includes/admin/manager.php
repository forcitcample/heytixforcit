<?php
//column renderer
function tcmp_ui_manager_column($active, $values=NULL, $hide=FALSE) {
    global $tcmp;
    ?>
    <td style="text-align:center;">
        <?php
        if($hide) {
            $text='-';
        } else {
            if($active) {
                $text='<span style="font-weight:bold; color:green">'.$tcmp->Lang->L('Yes').'</span>';
            } else {
                $text='<span style="font-weight:bold; color:red">'.$tcmp->Lang->L('No').'</span>';
            }
            if($active && $values) {
                if(!is_array($values)) {
                    $text.='&nbsp;{'.$values.'}';
                } elseif(count($values)>0) {
                    $what=implode(',', $values);
                    if($what!='') {
                        $text.='&nbsp;['.$what.']';
                    }
                }
            }
        }
        echo $text;
        ?>
    </td>
<?php
}

function tcmp_ui_manager() {
    global $tcmp;

    $id=intval($tcmp->Utils->qs('id', 0));
    if ($tcmp->Utils->qs('action')=='delete' && $id>0 && wp_verify_nonce($tcmp->Utils->qs('tcmp_nonce'), 'tcmp_delete')) {
        $snippet=$tcmp->Manager->get($id);
        if ($tcmp->Manager->remove($id)) {
            $tcmp->Options->pushSuccessMessage('CodeDeleteNotice', $id, $snippet['name']);
        }
    } else if($id!='') {
        $snippet=$tcmp->Manager->get($id);
	if($tcmp->Utils->is('action', 'toggle') && $id>0 && wp_verify_nonce($tcmp->Utils->qs('tcmp_nonce'), 'tcmp_toggle')) {
            $snippet['active']=($snippet['active']==0 ? 1 : 0);
            $tcmp->Manager->put($snippet['id'], $snippet);
        }
        $tcmp->Options->pushSuccessMessage('CodeUpdateNotice', $id, $snippet['name']);
    }

    $tcmp->Options->writeMessages();
    //controllo che faccio per essere retrocompatibile con la prima versione
    //dove non avevo un id e salvavo tutto con il con il nome quindi una stringa
    $snippets=$tcmp->Manager->keys();
    foreach($snippets as $v) {
        $snippet=$tcmp->Manager->get($v, FALSE, TRUE);
        if(!$snippet) {
            $tcmp->Manager->remove($v);
        } elseif(!is_numeric($v)) {
            $tcmp->Manager->remove($v);
            $tcmp->Manager->put('', $snippet);
        }
    }
    $snippets=$tcmp->Manager->values();
    if (count($snippets)>0) { ?>
        <div style="float:left;">
            <form method="get" action="" style="margin:5px; float:left;">
                <input type="hidden" name="page" value="<?php echo TCMP_PLUGIN_NAME?>" />
                <input type="hidden" name="tab" value="<?php echo TCMP_TAB_EDITOR?>" />
                <input type="submit" class="button-primary" value="<?php $tcmp->Lang->P('Button.Add')?>" />
            </form>
        </div>
        <div style="clear:both;"></div>

        <style>
            .widefat th {
                font-weight: bold!important;
            }
        </style>
        <table class="widefat fixed" style="width:auto;">
            <thead>
                <tr>
                    <th>#N</th>
                    <th><?php $tcmp->Lang->P('Name')?></th>
                    <th><?php $tcmp->Lang->P('Position')?></th>
                    <th style="text-align:center;"><?php $tcmp->Lang->P('Active?')?></th>
		    <th><?php $tcmp->Lang->P('Where?')?></th>
                    <th style="text-align:center;"><?php $tcmp->Lang->P('Each pages?')?></th>
                    <th><?php $tcmp->Lang->P('Shortcode')?></th>
                    <th style="text-align:center;"><?php $tcmp->Lang->P('Actions')?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $i=1;
            foreach ($snippets as $snippet) { ?>
                <tr>
                    <td>#<?php echo $i++ ?></td>
                    <td><?php echo $snippet['name']?></td>
                    <td><?php $tcmp->Lang->P('Editor.position.'.$snippet['position'])?></td>
		    <td style="text-align:center;">
                        <?php
                        $color='red';
                        $text='No';
                        $question='QuestionActiveOn';
                        if($snippet['active']==1) {
                            $color='green';
                            $text='Yes';
                            $question='QuestionActiveOff';
                        }
                        $text='<span style="font-weight:bold; color:'.$color.'">'.$tcmp->Lang->L($text).'</span>';
                        ?>
                        <a onclick="return confirm('<?php echo $tcmp->Lang->L($question)?>');" href="<?php echo TCMP_TAB_MANAGER_URI?>&tcmp_nonce=<?php echo esc_attr(wp_create_nonce('tcmp_toggle')); ?>&action=toggle&id=<?php echo $snippet['id'] ?>">
                            <?php echo $text?>
                        </a>
                    </td>
		    <td>
                        <?php
                        $text='Standard';
                        if($snippet['trackMode']!=TCMP_TRACK_MODE_CODE) {
                            $text=$tcmp->Plugin->getName($snippet['trackMode']);
                        }
                        $tcmp->Lang->P($text);
                        ?>
                    </td>
                    <?php
                    $hide=!$snippet['active'];
                    $active=($snippet['trackMode']==TCMP_TRACK_MODE_CODE
                        && $snippet['trackPage']==TCMP_TRACK_PAGE_ALL);
                    tcmp_ui_manager_column($active, NULL, $hide);
                    ?>
                    <td>
                        <code>
                        [tcm id="<?php echo esc_html($snippet['id']); ?>"]
                        </code>
                    </td>
                    <td style="text-align:center;">
                        <a href="<?php echo TCMP_TAB_EDITOR_URI?>&id=<?php echo $snippet['id'] ?>">
                            <?php echo $tcmp->Lang->L('Edit')?>
                        </a>
                        &nbsp;|&nbsp;
                        <span class="trash">
                            <a onclick="return confirm('<?php echo $tcmp->Lang->L('Question.DeleteQuestion')?>');" href="<?php echo TCMP_TAB_MANAGER_URI?>&tcmp_nonce=<?php echo esc_attr(wp_create_nonce('tcmp_delete')); ?>&action=delete&id=<?php echo $snippet['id'] ?>">
                                <?php echo $tcmp->Lang->L('Delete')?>
                            </a>
                        </span>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <h2><?php $tcmp->Lang->P('EmptyTrackingList', TCMP_TAB_EDITOR_URI)?></h2>
    <?php }
}