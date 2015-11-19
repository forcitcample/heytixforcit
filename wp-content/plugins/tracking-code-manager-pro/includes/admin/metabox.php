<?php
function tcmp_ui_metabox($post) {
    global $tcmp;
    // Add an nonce field so we can check for it later.
    wp_nonce_field('tcmp_meta_box', 'tcmp_meta_box_nonce');

    $args=array('metabox'=>TRUE, 'field'=>'id');
    $ids=$tcmp->Manager->getCodes(-1, $post, $args);
    $snippets=$tcmp->Manager->values();
    ?>
    <div>
        <?php $tcmp->Lang->P('Select existing Tracking Code')?>..
    </div>
    <input type="hidden" name="tcmp_previous_ids" value="<?php echo implode(',', $ids)?>" />

    <div>
        <?php
        $postType=$post->post_type;
        foreach($snippets as $snippet) {
            $id=$snippet['id'];
            $disabled='';
            $checked='';

            if($snippet['active']==0) {
                $disabled=' DISABLED';
            } elseif($snippet['exceptPostsOfType_'.$postType.'_Active']>0 && in_array(-1, $snippet['exceptPostsOfType_'.$postType])) {
                //the user have excluded all the posts of this type from code definition
                $disabled=' DISABLED';
            } else {
                if(in_array($id, $ids)) {
                    $checked=' checked';
                    //$active=($snippet['includePostsOfType_'.$postType.'_Active']>0);
                    //if(!$active) {
                    //    $checked='';
                    //}
                }
            }
            ?>
            <input type="checkbox" class="tcmp-checkbox" name="tcmp_ids[]" value="<?php echo $id?>" <?php echo $checked ?> <?php echo $disabled ?> />
            <a href="<?php echo TCMP_TAB_EDITOR_URI?>&id=<?php echo $id?>" target="_blank"><?php echo $snippet['name']?></a>
            <br/>
        <?php } ?>
    </div>

    <br/>
    <?php if($tcmp->Manager->rc()>0) { ?>
        <div>
            <label for="tcmp_name"><?php $tcmp->Lang->P('Or add a name')?></label>
            <br/>
            <input type="text" name="tcmp_name" value="" style="width:100%"/>
        </div>
        <div>
            <label for="code"><?php $tcmp->Lang->P('and paste HTML code here')?></label>
            <br/>
            <textarea dir="ltr" dirname="ltr" name="tcmp_code" class="tcmp-textarea" style="width:100%; height:175px;"></textarea>
        </div>
    <?php } else { ?>
        <span style="color:red;font-weight:bold;"><?php $tcmp->Lang->P('FreeLicenseReached')?></span>
    <?php }
}

//si aggancia per creare i metabox in post e page
add_action('add_meta_boxes', 'tcmp_add_meta_box');
function tcmp_add_meta_box() {
    global $tcmp;

    $free=array('post', 'page');
    $options=$tcmp->Options->getMetaboxPostTypes();
    $screens=array();
    foreach($options as $k=>$v) {
        if(intval($v)>0 && ($tcmp->License->hasPremium() || in_array($k, $free))) {
            $screens[]=$k;
        }
    }
    if(count($screens)>0) {
        foreach ($screens as $screen) {
            add_meta_box(
                'tcmp_sectionid'
                , $tcmp->Lang->L('Tracking Code PRO by IntellyWP')
                , 'tcmp_ui_metabox'
                , $screen
                , 'side'
            );
        }
    }
}
//si aggancia a quando un post viene salvato per salvare anche gli altri dati del metabox
add_action('save_post', 'tcmp_save_meta_box_data');
function tcmp_save_meta_box_data($postId) {
    global $tcmp;

    //in case of custom post type edit_ does not exist
    //if (!current_user_can('edit_'.$postType, $postId)) {
    //    return;
    //}

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['tcmp_meta_box_nonce']) || !isset($_POST['post_type'])) {
        return;
    }
    // Verify that the nonce is valid.
    if (!wp_verify_nonce( $_POST['tcmp_meta_box_nonce'], 'tcmp_meta_box')) {
        return;
    }

    $postType=$_POST['post_type'];
    $previousIds=explode(',', $tcmp->Utils->qs('tcmp_previous_ids'));
    $currentIds=$tcmp->Utils->qs('tcmp_ids', array());
    $keyArray='PostsOfType_'.$postType;
    $keyActive=$keyArray.'_Active';

    if($previousIds!=$currentIds) {
        //first remove by ids from old snippets
        foreach($previousIds as $id) {
            $id=intval($id);
            if($id>0 && !in_array($id, $currentIds)) {
                $snippet=$tcmp->Manager->get($id);
                if($snippet!=NULL) {
                    //remove my id from post type includes
                    $snippet['include'.$keyArray] = array_diff($snippet['include'.$keyArray], array($postId));
                    $snippet['include'.$keyArray] = array_unique($snippet['include'.$keyArray]);
                    $snippet['include'.$keyActive]=(count($snippet['include'.$keyArray])>0 ? 1 : 0);
                    //include it in post type exception
                    if($snippet['except'.$keyActive]==0) {
                        $snippet['except'.$keyArray]=array();
                    }
                    $snippet['except'.$keyArray] = array_merge($snippet['except'.$keyArray], array($postId));
                    $snippet['except'.$keyArray] = array_unique($snippet['except'.$keyArray]);
                    $snippet['except'.$keyActive]=1;
                }
                $tcmp->Manager->put($id, $snippet);
            }
        }
        //after insert by id in the snippets selected
        foreach($currentIds as $id) {
            $id=intval($id);
            if($id>0 && !in_array($id, $previousIds)) {
                $snippet = $tcmp->Manager->get($id);
                if ($snippet) {
                    //include my id in post type includes
                    if($snippet['include'.$keyActive]==0) {
                        $snippet['include'.$keyArray]=array();
                    }
                    $snippet['include'.$keyArray] = array_merge($snippet['include'.$keyArray], array($postId));
                    $snippet['include'.$keyArray] = array_unique($snippet['include'.$keyArray]);
                    $snippet['include'.$keyActive]=1;
                    //remove it from post type exception
                    $snippet['except'.$keyArray] = array_diff($snippet['except'.$keyArray], array($postId));
                    $snippet['except'.$keyArray] = array_unique($snippet['except'.$keyArray]);
                    $snippet['except'.$keyActive]=(count($snippet['except'.$keyArray])>0 ? 1 : 0);
                }
                $tcmp->Manager->put($id, $snippet);
            }
        }
    }

    $name=stripslashes($tcmp->Utils->qs('tcmp_name'));
    $code=$tcmp->Utils->qs('tcmp_code');
    if($name!='' && $code!='' && !$tcmp->Manager->exists($name) && $tcmp->Manager->rc()>0) {
        $snippet=array(
            'active'=>1
            , 'name'=>$name
            , 'code'=>$code
        );
        $snippet['include'.$keyActive]=1;
        $snippet['include'.$keyArray]=array($postId);
        $snippet=$tcmp->Manager->put('', $snippet);
        $tcmp->Logger->debug("NEW SNIPPET REGISTRED=%s", $snippet);
    }
}
