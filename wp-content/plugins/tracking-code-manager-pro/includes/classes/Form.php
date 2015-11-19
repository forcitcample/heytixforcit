<?php
/**
 * Created by PhpStorm.
 * User: alessio
 * Date: 28/03/2015
 * Time: 10:20
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TCMP_Form {
    var $prefix='Form';
    var $labels=TRUE;
    var $leftLabels=TRUE;
    var $newline;

    var $tags=TRUE;
    var $onlyPremium=TRUE;
    var $leftTags=FALSE;
    var $premium=FALSE;
    var $tagNew=FALSE;

    public function __construct() {
    }

    //args can be a string or an associative array if you want
    private function getTextArgs($args, $defaults, $excludes=array()) {
        $result=$args;
        if(is_array($result) && count($result)>0) {
            $result='';
            foreach($args as $k=>$v) {
                if(count($excludes)==0 || !in_array($k, $excludes)) {
                    $result.=' '.$k.'="'.$v.'"';
                }
            }
        } elseif(!$args) {
            $result='';
        }
        if(is_array($defaults) && count($defaults)>0) {
            foreach($defaults as $k=>$v) {
                if(count($excludes)==0 || !in_array($k, $excludes)) {
                    if(stripos($result, $k.'=')===FALSE) {
                        $result.=' '.$k.'="'.$v.'"';
                    }
                }
            }
        }
        return $result;
    }

    public function tag($overridePremium=FALSE) {
        global $tcmp;
	/*
        $premium=($overridePremium || $this->premium);
        if((!$overridePremium && !$this->tags) || $tcmp->License->hasPremium() || ($this->onlyPremium && !$premium)) return;

        $tagClass='tcmp-tag-free';
        $tagText='FREE';
        if($premium) {
            $tagClass='tcmp-tag-premium';
            $tagText='<a href="'.TCMP_PAGE_PREMIUM.'" target="_new">PRO</a>';
        }
	*/
	
	if(!$this->tags || !$this->tagNew) {
            return;
        }

        $tagClass='tcmp-tag-free';
        $tagText='NEW!';
        ?>
        <div style="float:left;" class="tcmp-tag <?php echo $tagClass?>"><?php echo $tagText?></div>
        <?php
    }

    public function label($name, $args='') {
        global $tcmp;
        $defaults=array('class'=>'');
        $otherText=$this->getTextArgs($args, $defaults, array('label', 'id'));

        $k=$this->prefix.'.'.$name;
        if(!is_array($args)) {
            $args=array();
        }
        if(isset($args['label']) && $args['label']) {
            $k=$args['label'];
        }
        $label=$tcmp->Lang->L($k);
        $for=(isset($args['id']) ? $args['id'] : $name);

        //check if is a mandatory field by checking the .txt language file
        $k=$this->prefix.'.'.$name.'.check';
        if($tcmp->Lang->H($k)) {
            $label.=' (*)';
        }

        $aClass='';
	/*
        if($this->premium && !$tcmp->License->hasPremium()) {
            $aClass='tcmp-label-disabled';
        }
	*/
        ?>
        <label for="<?php echo $for?>" <?php echo $otherText?> >
            <?php if($this->leftTags) {
                $this->tag();
            }?>
            <span style="float:left; margin-right:5px;" class="<?php echo $aClass?>"><?php echo $label?></span>
            <?php if(!$this->leftTags) {
                $this->tag();
            }?>
        </label>
    <?php }

    public function leftInput($name, $args='') {
        if(!$this->labels) return;
        if($this->leftLabels) {
            $this->label($name, $args);
        }

        if($this->newline) {
            $this->newline();
        }
    }

    public function newline() {
        ?><div class="tcmp-form-newline"></div><?php
    }

    public function rightInput($name, $args='') {
        if(!$this->labels) return;
        if (!$this->leftLabels) {
            $this->label($name, $args);
        }
        $this->newline();
    }

    public function formStarts($method='post', $action='', $args=NULL) {
        //$this->tags=FALSE;
        //$this->premium=FALSE;

        //$defaults=array('style'=>'margin:1em 0; padding:1px 1em; background:#fff; border:1px solid #ccc;'
        $defaults=array('class'=>'tcmp-form');
        $other=$this->getTextArgs($args, $defaults);
        ?>
        <form method="<?php echo $method?>" action="<?php echo $action?>" <?php echo $other?> >
    <?php }

    public function formEnds() { ?>
        </form>
    <?php }

    public function divStarts($args=array()) {
        $defaults=array();
        $other=$this->getTextArgs($args, $defaults);
        ?>
        <div <?php echo $other?>>
    <?php }
    public function divEnds() { ?>
        </div>
        <div style="clear:both;"></div>
    <?php }

    public function p($message, $v1=NULL, $v2=NULL, $v3=NULL, $v4=NULL, $v5=NULL) {
        global $tcmp;
        ?>
        <p style="font-weight:bold;">
            <?php
            $tcmp->Lang->P($message, $v1, $v2, $v3, $v4, $v5);
            if($tcmp->Lang->H($message.'Subtitle')) { ?>
                <br/>
                <span style="font-weight:normal;">
                    <?php $tcmp->Lang->P($message.'Subtitle', $v1, $v2, $v3, $v4, $v5)?>
                </span>
            <?php } ?>
        </p>
    <?php }

    public function textarea($name, $value='', $args=NULL) {
        if(is_array($value) && isset($value[$name])) {
            $value=$value[$name];
        }
        $defaults=array('rows'=>10, 'class'=>'tcmp-textarea');
        $other=$this->getTextArgs($args, $defaults);

        $args=array('class'=>'tcmp-label', 'style'=>'width:auto;');
        $this->newline=TRUE;
        $this->leftInput($name, $args);
        ?>
            <textarea dir="ltr" dirname="ltr" id="<?php echo $name ?>" name="<?php echo $name?>" <?php echo $other?> ><?php echo $value ?></textarea>
        <?php
        $this->newline=FALSE;
        $this->rightInput($name, $args);
    }

    public function text($name, $value='', $args=NULL) {
        if(is_array($value) && isset($value[$name])) {
            $value=$value[$name];
        }
        $defaults=array('class'=>'tcmp-text');
        $other=$this->getTextArgs($args, $defaults);

        $args=array('class'=>'tcmp-label');
        $this->leftInput($name, $args);
        ?>
            <input type="text" id="<?php echo $name ?>" name="<?php echo $name ?>" value="<?php echo $value ?>" <?php echo $other?> />
        <?php
        $this->rightInput($name, $args);
    }

    public function hidden($name, $value='', $args=NULL) {
        if(is_array($value) && isset($value[$name])) {
            $value=$value[$name];
        }
        $defaults=array();
        $other=$this->getTextArgs($args, $defaults);
        ?>
        <input type="hidden" id="<?php echo $name ?>" name="<?php echo $name ?>" value="<?php echo $value ?>" <?php echo $other?> />
    <?php }

    public function nonce($action=-1, $name='_wpnonce', $referer=true, $echo=true) {
        wp_nonce_field($action, $name, $referer, $echo);
    }

    public function select($name, $value, $options, $multiple, $args=NULL) {
        global $tcmp;
        if(is_array($value) && isset($value[$name])) {
            $value=$value[$name];
        }
        $defaults=array('class'=>'tcmp-select tcmTags');
        $other=$this->getTextArgs($args, $defaults);

        if(!is_array($value)) {
            $value=array($value);
        }
        if(is_string($options)) {
            $options=explode(',', $options);
        }
        if(is_array($options) && count($options)>0) {
            if(!isset($options[0]['id'])) {
                //this is a normal array so I use the values for "id" field and the "name" into the txt file
                $temp=array();
                foreach($options as $v) {
                    $temp[]=array('id'=>$v, 'name'=>$tcmp->Lang->L($this->prefix.'.'.$name.'.'.$v));
                }
                $options=$temp;
            }
        }

        $args=array('class'=>'tcmp-label');
        $this->leftInput($name, $args);
        ?>
            <select id="<?php echo $name ?>" name="<?php echo $name?><?php echo ($multiple ? '[]' : '')?>" <?php echo ($multiple ? 'multiple' : '')?> <?php echo $other?> >
                <?php
                foreach($options as $v) {
                    $selected='';
                    if(in_array($v['id'], $value)) {
                        $selected=' selected="selected"';
                    }
                    ?>
                    <option value="<?php echo $v['id']?>" <?php echo $selected?>><?php echo $v['name']?></option>
                <?php } ?>
            </select>
        <?php
        $this->rightInput($name, $args);
    }

    public function br() { ?>
        <br/>
    <?php }
    
    public function submit($value='', $args=NULL) {
        global $tcmp;
        $defaults=array();
        $other=$this->getTextArgs($args, $defaults);
        if($value=='') {
            $value='Send';
        }
        $this->newline();
        ?>
            <input type="submit" class="button-primary tcmp-button tcmp-submit" value="<?php $tcmp->Lang->P($value)?>" <?php echo $other?>/>
    <?php }

    public function delete($id, $action='delete', $args=NULL) {
        global $tcmp;
        $defaults=array();
        $other=$this->getTextArgs($args, $defaults);
        ?>
            <input type="button" class="button tcmp-button" value="<?php $tcmp->Lang->P('Delete?')?>" onclick="if (confirm('<?php $tcmp->Lang->P('Question.DeleteQuestion')?>') ) window.location='<?php echo TCMP_TAB_MANAGER_URI?>&action=<?php echo $action?>&id=<?php echo $id ?>&amp;tcmp_nonce=<?php echo esc_attr(wp_create_nonce('tcmp_delete')); ?>';" <?php echo $other?> />
            &nbsp;
        <?php
    }

    public function radio($name, $current=1, $value=1, $args=NULL) {
        if(!is_array($args)) {
            $args=array();
        }
        $args['radio']=TRUE;
        $args['id']=$name.'_'.$value;
        return $this->checkbox($name, $current, $value, $args);
    }
    public function checkbox($name, $current=1, $value=1, $args=NULL) {
        global $tcmp;
        if(is_array($current) && isset($current[$name])) {
            $current=$current[$name];
        }
	
	/*
        $defaults=array('class'=>'tcmp-checkbox', 'style'=>'margin:0px; margin-right:4px;');
        if($this->premium && !$tcmp->License->hasPremium()) {
            $defaults['disabled']='disabled';
            $value='';
        }
	*/
        if(!is_array($args)) {
            $args=array();
        }

        $label=$name;
        $type='checkbox';
        if(isset($args['radio']) && $args['radio']) {
            $type='radio';
            $label.='_'.$value;
        }

        $defaults=array(
            'class'=>'tcmp-checkbox'
            , 'style'=>'margin:0px; margin-right:4px;'
            , 'id'=>$name
        );
        $other=$this->getTextArgs($args, $defaults, array('radio', 'label'));
        $prev=$this->leftLabels;
        $this->leftLabels=FALSE;

        $label=(isset($args['label']) ? $args['label'] : $this->prefix.'.'.$label);
        $id=(isset($args['id']) ? $args['id'] : $name);
        $args=array(
            'class'=>''
            , 'style'=>'margin-top:-1px;'
            , 'label'=>$label
            , 'id'=>$id
        );
        $this->leftInput($name, $args);
        ?>
            <input type="<?php echo $type ?>" name="<?php echo $name?>" value="<?php echo $value?>" <?php echo($current==$value ? 'checked="checked"' : '') ?> <?php echo $other?> >
    <?php
        $this->rightInput($name, $args);
        $this->leftLabels=$prev;
    }

    public function checkText($nameActive, $nameText, $value) {
        global $tcmp;

        $args=array('class'=>'tcmp-hideShow tcmp-checkbox'
        , 'tcmp-hideIfTrue'=>'false'
        , 'tcmp-hideShow'=>$nameText.'Text');
        $this->checkbox($nameActive, $value, 1, $args);
        if($this->premium && !$tcmp->License->hasPremium()) {
            return;
        }
        ?>
        <div id="<?php echo $nameText?>Text" style="float:left;">
            <?php
            $prev=$this->labels;
            $this->labels=FALSE;
            $args=array();
            $this->text($nameText, $value, $args);
            $this->labels=$prev;
            ?>
        </div>
    <?php }

    //create a checkbox with a left select visible only when the checkbox is selected
    public function checkSelect($nameActive, $nameArray, $value, $values, $args=NULL) {
        global $tcmp;
        ?>
        <div id="<?php echo $nameArray?>Box" style="float:left;">
            <?php
            $defaults=array(
                'class'=>'tcmp-hideShow tcmp-checkbox'
                , 'tcmp-hideIfTrue'=>'false'
                , 'tcmp-hideShow'=>$nameArray.'Tags'
            );
            $args=$tcmp->Utils->parseArgs($args, $defaults);
            $this->checkbox($nameActive, $value, 1, $args);
            /*if(!$this->premium || $tcmp->License->hasPremium()) { ?>*/
            if(TRUE) { ?>
                <div id="<?php echo $nameArray?>Tags" style="float:left;">
                    <?php
                    $prev=$this->labels;
                    $this->labels=FALSE;
                    $args=array('class'=>'tcmp-select tcmLineTags');
                    $this->select($nameArray, $value, $values, TRUE, $args);
                    $this->labels=$prev;
                    ?>
                </div>
            <?php } ?>
        </div>
    <?php
        $this->newline();
    }
}