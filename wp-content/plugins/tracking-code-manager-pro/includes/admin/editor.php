<?php
function tcmp_ui_editor_check($snippet) {
    global $tcmp;

    $snippet['trackMode']=intval($snippet['trackMode']);
    $snippet['trackPage']=intval($snippet['trackPage']);

    $snippet['includeEverywhereActive']=0;
    if($snippet['trackPage']==TCMP_TRACK_PAGE_ALL) {
        $snippet['includeEverywhereActive']=1;
    }
    $snippet=$tcmp->Manager->sanitize($snippet['id'], $snippet);

    if ($snippet['name'] == '') {
        $tcmp->Options->pushErrorMessage('Please enter a unique name');
    } else {
        $exist=$tcmp->Manager->exists($snippet['name']);
        if ($exist && $exist['id'] != $snippet['id']) {
            //nonostante il tutto il nome deve essee univoco
            $tcmp->Options->pushErrorMessage('You have entered a name that already exists. IDs are NOT case-sensitive');
        }
    }
    if ($snippet['code'] == '') {
        $tcmp->Options->pushErrorMessage('Paste your HTML Tracking Code into the textarea');
    }

    if($snippet['trackMode']==TCMP_TRACK_MODE_CODE) {

        $types=$tcmp->Utils->query(TCMP_QUERY_POST_TYPES);
        if($snippet['trackPage']==TCMP_TRACK_PAGE_SPECIFIC) {
            foreach ($types as $v) {
                $includeActiveKey = 'includePostsOfType_' . $v['name'] . '_Active';
                $includeArrayKey = 'includePostsOfType_' . $v['name'];
                $exceptActiveKey = 'exceptPostsOfType_' . $v['name'] . '_Active';
                $exceptArrayKey = 'exceptPostsOfType_' . $v['name'];

                if ($snippet[$includeActiveKey] == 1 && $snippet[$exceptActiveKey] == 1) {
                    if (in_array(-1, $snippet[$includeArrayKey]) && in_array(-1, $snippet[$exceptArrayKey])) {
                        $tcmp->Options->pushErrorMessage('Error.IncludeExcludeAll', $v['name']);
                    }
                }
                if ($snippet[$includeActiveKey] == 1 && count($snippet[$includeArrayKey]) == 0) {
                    $tcmp->Options->pushErrorMessage('Error.IncludeSelectAtLeastOne', $v['name']);
                }
            }

            //second loop to respect the display order
            foreach ($types as $v) {
                $includeActiveKey = 'includePostsOfType_' . $v['name'] . '_Active';
                $includeArrayKey = 'includePostsOfType_' . $v['name'];
                $exceptActiveKey = 'exceptPostsOfType_' . $v['name'] . '_Active';
                $exceptArrayKey = 'exceptPostsOfType_' . $v['name'];

                if ($snippet[$includeActiveKey] == 1 && in_array(-1, $snippet[$includeArrayKey])) {
                    if ($snippet[$exceptActiveKey] == 1 && count($snippet[$exceptArrayKey]) == 0) {
                        $tcmp->Options->pushErrorMessage('Error.ExcludeSelectAtLeastOne', $v['name']);
                    }
                }
            }
        } else {
            foreach($types as $v) {
                $exceptActiveKey='exceptPostsOfType_'.$v['name'].'_Active';
                $exceptArrayKey='exceptPostsOfType_'.$v['name'];

                if($snippet[$exceptActiveKey]==1
                    && count($snippet[$exceptArrayKey])==0) {
                    $tcmp->Options->pushErrorMessage('Error.ExcludeSelectAtLeastOne', $v['name']);
                }
            }
        }
    }
}
function tcmp_ui_editor() {
    global $tcmp;

    $tcmp->Form->prefix = 'Editor';
    $id = intval($tcmp->Utils->qs('id', 0));
    $action = $tcmp->Utils->qs('action');
    $snippet = $tcmp->Manager->get($id, TRUE);
    //var_dump($snippet);

    if (wp_verify_nonce($tcmp->Utils->qs('tcmp_nonce'), 'tcmp_nonce')) {
        //var_dump($_POST);
        //var_dump($_GET);
        foreach ($snippet as $k => $v) {
            $snippet[$k] = $tcmp->Utils->qs($k);
            if (is_string($snippet[$k])) {
                $snippet[$k] = stripslashes($snippet[$k]);
            }
        }

	    tcmp_ui_editor_check($snippet);

        if (!$tcmp->Options->hasErrorMessages()) {
            $snippet = $tcmp->Manager->put($snippet['id'], $snippet);
            /*if ($id <= 0) {
                $tcmp->Options->pushSuccessMessage('Editor.Add', $snippet['id'], $snippet['name']);
                $snippet = $tcmp->Manager->get('', TRUE);
            } else {
                $tcmp->Utils->redirect(TCMP_PAGE_MANAGER.'&id='.$id);
                exit();
            }*/
            $id=$snippet['id'];
            $tcmp->Utils->redirect(TCMP_PAGE_MANAGER.'&id='.$id);        }
    }
    $tcmp->Options->writeMessages()
    ?>
    <script>
        jQuery(function(){
            var tcmPostTypes=[];

            <?php
            $types=$tcmp->Utils->query(TCMP_QUERY_POST_TYPES);
            foreach($types as $v) { ?>
                tcmPostTypes.push('<?php echo $v['name']?>');
            <?php } ?>

            //enable/disable some part of except creating coherence
            function tcmCheckVisible() {
                var showExceptCategories=true;
                var showExceptTags=true;
                var showExceptPostTypes={};
                jQuery.each(tcmPostTypes, function (i,v) {
                    showExceptPostTypes[v]=true;
                });

                var $mode=jQuery('[name=trackMode]:checked');
                var showTrackCode=false;
                var showTrackConversion=false;
                if($mode.length>0) {
                    if(parseInt($mode.val())!=<?php echo TCMP_TRACK_MODE_CODE ?>) {
                        showTrackConversion=true;
                        jQuery('[name=position]').val(<?php echo TCMP_POSITION_FOOTER?>);
                        jQuery('[name=position]').prop('disabled', true);

                        tcmShowHide('.box-track-conversion', false);
                        tcmShowHide('#box-track-conversion-'+$mode.val(), true);
                    } else {
                        showTrackCode=true;
                        jQuery('[name=position]').prop('disabled', false);
                    }
                }
                tcmShowHide('#box-track-conversion', showTrackConversion);
                tcmShowHide('#box-track-code', showTrackCode);

                var $all=jQuery('[name=trackPage]:checked');
                if($all.length>0 && parseInt($all.val())==<?php echo TCMP_TRACK_PAGE_SPECIFIC ?>) {
                    showExceptCategories=false;
                    showExceptTags=false;

                    jQuery.each(tcmPostTypes, function (i,v) {
                        isCheck=jQuery('#includePostsOfType_'+v+'_Active').is(':checked');
                        selection=jQuery('#includePostsOfType_'+v).select2("val");
                        found=false;
                        for(i=0; i<selection.length; i++) {
                            if(parseInt(selection[i])==-1){
                                found=true;
                            }
                        }

                        showExceptPostTypes[v]=false;
                        if(isCheck && found) {
                            showExceptPostTypes[v]=true;
                            if(v!='page') {
                                showExceptCategories=true;
                                showExceptTags=true;
                            }
                        }
                    });
                }

                //hide/show except post type if all the website is selected
                //or [All] is selected in a specific post type select
                var showExcept=false;
                jQuery.each(showExceptPostTypes, function (k,v) {
                    if(v) {
                        //at least one post type to show except
                        showExcept=true;
                    }
                    tcmShowHide('#exceptPostsOfType_'+k+'Box', v);
                });

                //tcmShowHide('#exceptCategoriesBox', showExceptCategories);
                //tcmShowHide('#exceptTagsBox', showExceptTags);
                showInclude=false;
                if($all.length==0) {
                    showExcept=false;
                } else {
                    showExcept=(showExcept || showExceptTags || showExceptCategories);
                    if(parseInt($all.val())==<?php echo TCMP_TRACK_PAGE_ALL ?>) {
                        showExcept=true;
                    } else {
                        showInclude=true;
                    }
                }
                tcmShowHide('#tcmp-except-div', showExcept);
                tcmShowHide('#tcmp-include-div', showInclude);
            }
            function tcmShowHide(selector, show) {
                $selector=jQuery(selector);
                if(show) {
                    $selector.show();
                } else {
                    $selector.hide();
                }
            }

            /*jQuery(".tcmTags").select2({
                placeholder: "Type here..."
                , theme: "classic"
            }).on('change', function() {
                tcmCheckVisible();
            });*/
            jQuery(".tcmLineTags").select2({
                placeholder: "Type here..."
                , theme: "classic"
                , width: '550px'
            });

            jQuery('.tcmp-hideShow').click(function() {
                tcmCheckVisible();
            });
            jQuery('.tcmp-hideShow, input[type=checkbox], input[type=radio]').change(function() {
                tcmCheckVisible();
            });
            jQuery('.tcmLineTags').on('change', function() {
                tcmCheckVisible();
            });
            tcmCheckVisible();
        });
    </script>
    <?php

    $tcmp->Form->formStarts();
    $tcmp->Form->hidden('id', $snippet);
    $tcmp->Form->checkbox('active', $snippet);
    $tcmp->Form->text('name', $snippet);
    $tcmp->Form->textarea('code', $snippet);
    $values = array(TCMP_POSITION_HEAD, TCMP_POSITION_BODY, TCMP_POSITION_FOOTER);
    $tcmp->Form->select('position', $snippet, $values, FALSE);

    $args=array('id'=>'box-track-mode');
    $tcmp->Form->divStarts($args);
    {
        $tcmp->Form->p('Where do you want to add this code?');
        $tcmp->Form->radio('trackMode', $snippet['trackMode'], TCMP_TRACK_MODE_CODE);
        $plugins=$tcmp->Ecommerce->getActivePlugins();
        if(count($plugins)==0) {
            $plugins=array('Ecommerce'=>array(
                'name'=>'Ecommerce'
                , 'id'=>TCMP_PLUGINS_NO_PLUGINS
                , 'version'=>'')
            );
        }
        $tcmp->Form->tagNew=TRUE;
        foreach($plugins as $k=>$v) {
            $ecommerce=$v['name'];
            if(isset($v['version']) && $v['version']!='') {
                $ecommerce.=' (v.'.$v['version'].')';
            }
            $args=array('label'=>$tcmp->Lang->L('Editor.trackMode_1', $ecommerce));
            $tcmp->Form->radio('trackMode', $snippet['trackMode'], $v['id'], $args);
        }
        $tcmp->Form->tagNew=FALSE;

    }
    $tcmp->Form->divEnds();

    $args=array('id'=>'box-track-conversion');
    $tcmp->Form->divStarts($args);
    {
        $tcmp->Form->p('In which products do you want to insert this code?');
        ?>
        <p style="font-style: italic;"><?php $tcmp->Lang->P('Editor.PositionBlocked') ?></p>
        <?php
        foreach($plugins as $k=>$v) {
            $args=array('id'=>'box-track-conversion-'.$v['id'], 'class'=>'box-track-conversion');
            $tcmp->Form->divStarts($args);
            {
                if($v['id']==TCMP_PLUGINS_NO_PLUGINS) {
                    $plugins=$tcmp->Ecommerce->getPlugins(FALSE);
                    $ecommerce='';
                    foreach($plugins as $k=>$v) {
                        if($ecommerce!='') {
                            $ecommerce.=', ';
                        }
                        $ecommerce.=$k;
                    }
                    $tcmp->Options->pushErrorMessage('Editor.NoEcommerceFound', $ecommerce);
                    $tcmp->Options->writeMessages();
                } else {
                    $postType=$tcmp->Ecommerce->getCustomPostType($v['id']);
                    $keyActive='CTC_'.$v['id'].'_Active';
                    $label=$tcmp->Lang->L('Editor.EcommerceCheck', $v['name'], $v['version']);

                    if($postType!='') {
                        $args = array('post_type' => $postType, 'all' => TRUE);
                        $values = $tcmp->Utils->query(TCMP_QUERY_POSTS_OF_TYPE, $args);
                        $keyArray='CTC_'.$v['id'].'_ProductsIds';
                        if(count($snippet[$keyArray])==0) {
                            //when enabled default selected -1
                            $snippet[$keyArray]=array(-1);
                        }

                        $args=array('label'=>$label, 'class'=>'tcmp-select tcmLineTags');
                        $tcmp->Form->labels=FALSE;
                        $tcmp->Form->select($keyArray, $snippet[$keyArray], $values, TRUE, $args);
                        $tcmp->Form->labels=TRUE;
                    } else {
                        $args=array('label'=>$label);
                        $tcmp->Form->checkbox($keyActive, $snippet[$keyActive], 1, $args);
                    }
                }
            }
            $tcmp->Form->divEnds();
        }
    }
    $tcmp->Form->divEnds();

    $args=array('id'=>'box-track-code');
    $tcmp->Form->divStarts($args);
    {
        $tcmp->Form->p('In which page do you want to insert this code?');
        $tcmp->Form->radio('trackPage', $snippet['trackPage'], TCMP_TRACK_PAGE_ALL);
        $tcmp->Form->radio('trackPage', $snippet['trackPage'], TCMP_TRACK_PAGE_SPECIFIC);

        //, 'style'=>'margin-top:10px;'
        $args=array('id'=>'tcmp-include-div');
        $tcmp->Form->divStarts($args);
        {
            $tcmp->Form->p('Include tracking code in which pages?');
            tcmp_formOptions('include', $snippet);
   	    $tcmp->Form->checkText('includeLastPostsActive', 'includeLastPosts', $snippet);
	}
        $tcmp->Form->divEnds();

        $args=array('id'=>'tcmp-except-div');
        $tcmp->Form->divStarts($args);
        {
            $tcmp->Form->p('Do you want to exclude some specific pages?');
            tcmp_formOptions('except', $snippet);
        }
        $tcmp->Form->divEnds();
    }
    $tcmp->Form->divEnds();

    $tcmp->Form->nonce('tcmp_nonce', 'tcmp_nonce');
    $tcmp->Form->submit('Save');
    if($id>0) {
        $tcmp->Form->delete($id);
    }
    $tcmp->Form->formEnds();
}

function tcmp_formOptions($prefix, $snippet) {
    global $tcmp;
    //$tcmp->Form->tags=TRUE;

    $types=$tcmp->Utils->query(TCMP_QUERY_POST_TYPES);
    foreach($types as $v) {
        $args = array('post_type' => $v['name'], 'all' => TRUE);
        $values = $tcmp->Utils->query(TCMP_QUERY_POSTS_OF_TYPE, $args);
        //$tcmp->Form->premium=!in_array($v['name'], array('post', 'page'));

        $keyActive=$prefix.'PostsOfType_'.$v['name'].'_Active';
        $keyArray=$prefix.'PostsOfType_'.$v['name'];
        if($snippet[$keyActive]==0 && count($snippet[$keyArray])==0 && $prefix!='except') {
            //when enabled default selected -1
            $snippet[$keyArray]=array(-1);
        }
        $tcmp->Form->checkSelect($keyActive, $keyArray, $snippet, $values);
    }

    //$tcmp->Form->premium=TRUE;
    $args = array('post_type' => '', 'all' => FALSE);
    $values = $tcmp->Utils->query(TCMP_QUERY_CATEGORIES, $args);
    $tcmp->Form->checkSelect($prefix.'CategoriesActive', $prefix.'Categories', $snippet, $values);

    $args = array('post_type' => '', 'all' => FALSE);
    $values = $tcmp->Utils->query(TCMP_QUERY_TAGS, $args);
    $tcmp->Form->checkSelect($prefix.'TagsActive', $prefix.'Tags', $snippet, $values);
    //$tcmp->Form->tags=FALSE;
}
