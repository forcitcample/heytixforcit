<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

define('TCMP_TRACK_MODE_CODE', 0);
//others track mode are plugin enumeration
define('TCMP_TRACK_PAGE_ALL', 0);
define('TCMP_TRACK_PAGE_SPECIFIC', 1);

class TCMP_Manager {
    public function __construct() {

    }

    public function exists($name) {
        $snippets = $this->values();
        $result = NULL;
        $name=strtoupper($name);
        if (isset($snippets[$name])) {
            $result=$snippets[$name];
        }
        return $result;
    }

    //get a code snippet
    public function get($id, $new = FALSE) {
        global $tcmp;

        $snippet=$tcmp->Options->getSnippet($id);
        if (!$snippet && $new) {
            $snippet=array();
            $snippet['active']=1;
            $snippet['trackMode']=-1;
            $snippet['trackPage']=-1;
        }

        $snippet=$this->sanitize($id, $snippet);
        return $snippet;
    }

    public function sanitize($id, $snippet) {
        global $tcmp;
        if($snippet==NULL || !is_array($snippet)) return;

        $page=0;
        if(isset($snippet['includeEverywhereActive'])) {
            $page=(intval($snippet['includeEverywhereActive']==1) ? 0 : 1);
        }
        $defaults=array(
            'id'=>$id
            , 'active'=>0
            , 'name'=>''
            , 'code'=>''
            , 'position'=>TCMP_POSITION_HEAD
            , 'trackMode'=>TCMP_TRACK_MODE_CODE
            , 'trackPage'=>$page
            , 'includeEverywhereActive'=>0
            , 'includeCategoriesActive'=>0
            , 'includeCategories'=>array()
            , 'includeTagsActive'=>0
            , 'includeTags'=>array()
            , 'includeLastPostsActive'=>0
            , 'includeLastPosts'=>5
            , 'exceptCategoriesActive'=>0
            , 'exceptCategories'=>array()
            , 'exceptTagsActive'=>0
            , 'exceptTags'=>array()
        );

        $types=$tcmp->Utils->query(TCMP_QUERY_POST_TYPES);
        foreach($types as $v) {
            $defaults['includePostsOfType_'.$v['name'].'_Active']=0;
            $defaults['includePostsOfType_'.$v['name']]=array();
            $defaults['exceptPostsOfType_'.$v['name'].'_Active']=0;
            $defaults['exceptPostsOfType_'.$v['name']]=array();
        }

        $types=$tcmp->Utils->query(TCMP_QUERY_CONVERSION_PLUGINS);
        foreach($types as $v) {
            //CP stands for ConversionTrackingCode
            //$defaults['CTC_'.$v['id'].'_Active']=0;
            $defaults['CTC_'.$v['id'].'_ProductsIds']=array();
            $defaults['CTC_'.$v['id'].'_CategoriesIds']=array();
            $defaults['CTC_'.$v['id'].'_TagsIds']=array();
        }
        $snippet=$tcmp->Utils->parseArgs($snippet, $defaults);
        //$snippet['includeLastPosts'] = intval($snippet['includeLastPosts']);

        foreach ($snippet as $k => $v) {
            if (stripos($k, 'active') !== FALSE) {
                $snippet[$k]=intval($v);
            } elseif (is_array($v)) {
                switch ($k) {
                    /*
                    case 'includePostsTypes':
                    case 'excludePostsTypes':
                        //keys are string and not number
                        $result = $this->uarray($snippet, $k, FALSE);
                        break;
                    */
                    default:
                        //keys are number
                        $result = $this->uarray($snippet, $k, TRUE);
                        break;
                }
            }
        }
        $snippet['code']=trim($snippet['code']);
        $snippet['position']=intval($snippet['position']);
        $snippet['includeLastPosts']=intval($snippet['includeLastPosts']);
	if($snippet['trackMode']==='') {
            $snippet['trackMode']=TCMP_TRACK_MODE_CODE;
        } else {
            $snippet['trackMode']=intval($snippet['trackMode']);
        }
        if($snippet['trackPage']==='') {
            $snippet['trackPage']=$page;
        } else {
            $snippet['trackPage']=intval($snippet['trackPage']);
        }

        $snippet['includeEverywhereActive']=0;
        if($snippet['trackPage']==TCMP_TRACK_PAGE_ALL) {
            $snippet['includeEverywhereActive']=1;
        }

        $code=strtolower($snippet['code']);
        $cnt=substr_count($code, '<iframe')+substr_count($code, '<script');
        if($cnt<=0) {
            $cnt=1;
        }
        $snippet['codesCount']=$cnt;
        return $snippet;
    }
    private function uarray($snippet, $key, $isInteger = TRUE) {
        $array = $snippet[$key];
        if (!is_array($array)) {
            $array = explode(',', $array);
        }

        if ($isInteger) {
            for ($i = 0; $i < count($array); $i++) {
                $array[$i] = intval($array[$i]);
            }
        }

        $array = array_unique($array);
        $snippet[$key] = $array;
        return $snippet;
    }

    public function rc() {
        global $tcmp;
        $result = 6-$this->codesCount();
        if($tcmp->License->hasPremium()) {
            $result=1000;
        }
        return $result;
    }

    //add or update a snippet (html tracking code)
    public function put($id, $snippet) {
        global $tcmp;

        if ($id == '' || intval($id) <= 0) {
            //if is a new code create a new unique id
            $id = $this->getLastId() + 1;
            $snippet['id'] = $id;
        }
        $snippet=$this->sanitize($id, $snippet);
        $tcmp->Options->setSnippet($id, $snippet);

        $keys = $this->keys();
        if (is_array($keys) && !in_array($id, $keys)) {
            $keys[] = $id;
            $this->keys($keys);
        }
        return $snippet;
    }

    //remove the id snippet
    public function remove($id) {
        global $tcmp;
        $tcmp->Options->removeSnippet($id);
        $keys=$this->keys();
        $result = FALSE;
        if (is_array($keys) && in_array($id, $keys)) {
            $keys = array_diff($keys, array($id));
            $this->keys($keys);
            $result = TRUE;
        }
        return $result;
    }

    //verify if match with this snippet
    private function matchSnippet($postId, $postType, $categoriesIds, $tagsIds, $prefix, $snippet) {
        global $tcmp;

        $include=FALSE;
        $postId=intval($postId);
        if($postId>0) {
            if(!$include && $snippet[$prefix.'CategoriesActive'] && $tcmp->Utils->inArray($categoriesIds, $snippet[$prefix.'Categories'])) {
                $tcmp->Logger->debug('MATCH=%s SNIPPET=%s[%s] DUE TO POST=%s CATEGORIES [%s] IN [%s]'
                    , $prefix, $snippet['id'], $snippet['name'], $postId, $categoriesIds, $snippet[$prefix.'Categories']);
                $include=TRUE;
            }
            if(!$include && $snippet[$prefix.'TagsActive'] && $tcmp->Utils->inArray($tagsIds, $snippet[$prefix.'Tags'])) {
                $tcmp->Logger->debug('MATCH=%s SNIPPET=%s[%s] DUE TO POST=%s TAGS [%s] IN [%s]'
                    , $prefix, $snippet['id'], $snippet['name'], $postId, $tagsIds, $snippet[$prefix.'Tags']);
                $include=TRUE;
            }
            $what=$prefix.'PostsOfType_'.$postType;
            if(!$include && isset($snippet[$what.'_Active']) && isset($snippet[$what])&& $snippet[$what.'_Active'] && $tcmp->Utils->inArray($postId, $snippet[$what])) {
                $tcmp->Logger->debug('MATCH=%s SNIPPET=%s[%s] DUE TO POST=%s OF TYPE=%s IN [%s]'
                    , $prefix, $snippet['id'], $snippet['name'], $postId, $postType, $snippet[$what]);
                $include=TRUE;
            }
        }

        return $include;
    }

    public function writeCodes($position) {
        global $tcmp;

        $text='';
        switch ($position) {
            case TCMP_POSITION_HEAD:
                $text='HEAD';
                break;
            case TCMP_POSITION_BODY:
                $text='BODY';
                break;
            case TCMP_POSITION_FOOTER:
                $text='FOOTER';
                break;
            case TCMP_POSITION_CONVERSION:
                $text='CONVERSION';
                break;
        }

        $post=$tcmp->Options->getPostShown();
        $args=array('field'=>'code');
        $codes=$tcmp->Manager->getCodes($position, $post, $args);
        if(is_array($codes) && count($codes)>0) {
            echo "\n<!--BEGIN: TRACKING CODE MANAGER BY INTELLYWP.COM IN $text//-->";
            foreach($codes as $v) {
                echo "\n$v";
            }
            echo "\n<!--END: https://wordpress.org/plugins/tracking-code-manager IN $text//-->";
        }
    }

    //return snippets that match with options
    public function getConversionSnippets($options=NULL) {
        global $tcmp;

        $defaults=array(
            'pluginId'=>0
            , 'categoriesIds'=>array()
            , 'productsIds'=>array()
            , 'tagsIds'=>array()
        );
        $options=$tcmp->Utils->parseArgs($options, $defaults);

        $result=array();
        $pluginId=intval($options['pluginId']);
        $ids=$this->keys();

        foreach($ids as $id) {
            $snippet=$this->get($id);
            if($snippet && $snippet['trackMode']>0 && $snippet['trackMode']==$pluginId) {
                $match=FALSE;

                $match=($match || $this->matchConversion($snippet, $pluginId, 'ProductsIds', $options['productsIds']));
                $match=($match || $this->matchConversion($snippet, $pluginId, 'CategoriesIds', $options['categoriesIds']));
                $match=($match || $this->matchConversion($snippet, $pluginId, 'TagsIds', $options['tagsIds']));
                if(!$match) {
                    //no selected so..all match! :)
                    if(count($snippet['CTC_'.$pluginId.'_ProductsIds'])==0
                        && count($snippet['CTC_'.$pluginId.'_CategoriesIds'])==0
                        && count($snippet['CTC_'.$pluginId.'_TagsIds'])==0) {
                        $match=TRUE;
                    }
                }

                if($match) {
                    $result[]=$snippet;
                }
            }
        }
        return $result;
    }
    private function matchConversion($snippet, $pluginId, $suffix, $array) {
        global $tcmp;

        $v='CTC_'.$pluginId.'_'.$suffix;
        if(isset($snippet[$v])) {
            $v=$snippet[$v];
        } else {
            $v=array();
        }

        $result=$tcmp->Utils->inArray($array, $v);
        return $result;
    }

    //from a post retrieve the html code that is needed to insert into the page code
    public function getCodes($position, $post, $args=array()) {
        global $tcmp;

        $defaults=array('field'=>'code');
        $args=$tcmp->Utils->parseArgs($args, $defaults);

        $postId=0;
        $postType='page';
        $tagsIds=array();
        $categoriesIds=array();
        if($post) {
            $postId = $post->ID;
            $postType = $post->post_type;

            $options = array('orderby' => 'name', 'order' => 'ASC', 'fields' => 'ids');
            $tagsIds = wp_get_post_tags($post->ID, $options);
            $categoriesIds = wp_get_post_categories($post->ID);
        }

        $tcmp->Options->clearSnippetsWritten();
	    if($position==TCMP_POSITION_CONVERSION) {
            //write snippets previously appended
            $ids=$tcmp->Options->getConversionSnippetIds();
            foreach($ids as $id) {
                $snippet=$tcmp->Manager->get($id);
                if($snippet) {
                    $tcmp->Options->pushSnippetWritten($snippet);
                }
            }
        } else {
	        $keys=$this->keys();
	        foreach ($keys as $id) {
	            $v=$this->get($id);
	            if(!$v || ($position>-1 && $v['position']!=$position) || $v['code']=='' || !$v['active']) {
	                continue;
	            }
                if ($v['trackMode']!=TCMP_TRACK_MODE_CODE) {
                    continue;
                }
	            if($tcmp->Options->hasSnippetWritten($v)) {
	                $tcmp->Logger->debug('SKIPPED SNIPPET=%s[%s] DUE TO ALREADY WRITTEN', $v['id'], $v['name']);
	                continue;
	            }

	            /*query db
	            $args = array(
	                'category__and' => $categoriesIds
	                , 'tag__and' => $tagsIds
	                , 'post__in'=> ''
	                , 'post_type'=> ''
	                , 'post_status' => 'publish'
	                , 'orderby' => 'post_date'
	                , 'order' => 'DESC'
	                , 'posts_per_page' => -1
	            );
	            $post=query_posts($args);
	            $ids=array();
	            foreach ($post as $p) {
	                $ids[]=$p['ID'];
	            }
	            wp_reset_query();
	            */

	            //when i use this function to get metabox information i need only to compare with post information
	            //$v['includeEverywhereActive']=(!$args['metabox'] && $v['includeEverywhereActive']);
	            //$v['includeCategoriesActive']=(!$args['metabox'] && $v['includeCategoriesActive']);
	            //$v['includeTagsActive']=(!$args['metabox'] && $v['includeTagsActive']);

	            $match=FALSE;
                if (!$match && ($v['trackPage']==TCMP_TRACK_PAGE_ALL || $v['includeEverywhereActive'])) {
                    $tcmp->Logger->debug('INCLUDED SNIPPET=%s[%s] DUE TO EVERYWHERE', $v['id'], $v['name']);
	                $match=TRUE;
	            }
	            if(!$match && $postId>0 && $this->matchSnippet($postId, $postType, $categoriesIds, $tagsIds, 'include', $v)) {
	                $match=TRUE;
	            }
	            if(!$match && $postId>0 && $v['includeLastPostsActive'] && $v['includeLastPosts']>0) {
	                $options = array(
	                    'numberposts' => $v['includeLastPosts']
	                    , 'category' => 0
	                    , 'orderby' => 'post_date'
	                    , 'order' => 'DESC'
	                    , 'post_type' => 'post'
	                    , 'post_status' => 'publish'
	                );
	                $post = wp_get_recent_posts($options);
	                $ids=array();
	                foreach ($post as $p) {
	                    $ids[]=$p['ID'];
	                }
	                if(in_array($postId, $ids)) {
	                    $tcmp->Logger->debug('INCLUDED SNIPPET=%s[%s] DUE TO IN LAST POSTS=%s', $postId, $ids);
	                    $match=TRUE;
	                }
	            }

	            if($match && $postId>0) {
	                if($this->matchSnippet($postId, $postType, $categoriesIds, $tagsIds, 'except', $v)) {
	                    $tcmp->Logger->debug('FOUND AT LEAST ON EXCEPT TO EXCLUDE SNIPPET=%s [%s]', $v['id'], $v['name']);
	                    $match=FALSE;
	                }
	            }

	            if ($match) {
	                $tcmp->Options->pushSnippetWritten($v);
	            }
	        }
	}

        //obtain result as snippets or array of one field (tipically "id")
        $result=$tcmp->Options->getSnippetsWritten();
        if ($args['field']!='all') {
            $array=array();
            foreach($result as $k=>$v) {
                $k=$args['field'];
                if(isset($v[$k])) {
                    $array[]=$v[$k];
                } else {
                    $tcmp->Logger->error('SNIPPET=%s [%s] WITHOUT FIELD=%s', $v['id'], $v['name'], $k);
                }
            }
            $result=$array;
        }
        return $result;
    }

    //ottiene o salva tutte le chiavi dei tracking code utilizzati ordinati per id
    public function keys($keys=NULL) {
        global $tcmp;

        if (is_array($keys)) {
            $tcmp->Options->setSnippetList($keys);
            $result=$keys;
        } else {
            $result=$tcmp->Options->getSnippetList();
        }

        if (!is_array($result)) {
            $result = array();
        } else {
            sort($result);
        }
        return $result;
    }

    //ottiene il conteggio attuale dei tracking code
    public function count() {
        $result = count($this->keys());
        return $result;
    }
    public function codesCount() {
        $result=0;
        $ids=$this->keys();
        foreach($ids as $id) {
            $snippet=$this->get($id);
            if($snippet) {
                $result+=1;
                /*
                if($snippet['codesCount']>0) {
                    $result+=intval($snippet['codesCount']);
                } else {
                    $result+=1;
                }
                */
            }
        }
        return $result;
    }
    public function getLastId() {
        $result = 0;
        $list = $this->keys();
        foreach ($list as $v) {
            $v = intval($v);
            if ($v > $result) {
                $result = $v;
            }
        }
        return $result;
    }

    //ottiene tutti i tracking code ordinati per nome
    public function values()  {
        $keys = $this->keys();
        $result = array();
        foreach ($keys as $k) {
            $v = $this->get($k);
            $result[strtoupper($v['name'])] = $v;
        }
        ksort($result);
        return $result;
    }
}