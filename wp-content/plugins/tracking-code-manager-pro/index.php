<?php
/*
Plugin Name: Tracking Code Manager PRO
Plugin URI: http://intellywp.com/tracking-code-manager/
Description: A plugin to manage ALL your tracking code and conversion pixels, simply. Compatible with Facebook Ads, Google Adwords, WooCommerce, Easy Digital Downloads, WP eCommerce.
Author: IntellyWP
Author URI: http://intellywp.com/
Email: aleste@intellywp.com
Version: 1.6.8
*/
define('TCMP_PLUGIN_PREFIX', 'TCMP_');
define('TCMP_PLUGIN_FILE',__FILE__);
define('TCMP_PLUGIN_NAME', 'tracking-code-manager-pro');
define('TCMP_PLUGIN_VERSION', '1.6.8');
define('TCMP_PLUGIN_AUTHOR', 'IntellyWP');
define('TCMP_PLUGIN_ROOT', dirname(__FILE__).'/');
define('TCMP_PLUGIN_IMAGES', plugins_url( 'assets/images/', __FILE__ ));
define('TCMP_PLUGIN_ASSETS', plugins_url( 'assets/', __FILE__ ));

define('TCMP_LOGGER', FALSE);

define('TCMP_QUERY_POSTS_OF_TYPE', 1);
define('TCMP_QUERY_POST_TYPES', 2);
define('TCMP_QUERY_CATEGORIES', 3);
define('TCMP_QUERY_TAGS', 4);
define('TCMP_QUERY_CONVERSION_PLUGINS', 5);

define('TCMP_INTELLYWP_SITE', 'http://www.intellywp.com/');
define('TCMP_INTELLYWP_RECEIVER', TCMP_INTELLYWP_SITE.'wp-content/plugins/intellywp-manager/data.php');
define('TCMP_PAGE_FAQ', TCMP_INTELLYWP_SITE.'tracking-code-manager');
define('TCMP_PAGE_PREMIUM', TCMP_INTELLYWP_SITE.'tracking-code-manager');
define('TCMP_PAGE_MANAGER', admin_url().'options-general.php?page='.TCMP_PLUGIN_NAME);

define('TCMP_POSITION_HEAD', 0);
define('TCMP_POSITION_BODY', 1);
define('TCMP_POSITION_FOOTER', 2);
define('TCMP_POSITION_CONVERSION', 3);

define('TCMP_TAB_EDITOR', 'editor');
define('TCMP_TAB_EDITOR_URI', TCMP_PAGE_MANAGER.'&tab='.TCMP_TAB_EDITOR);
define('TCMP_TAB_MANAGER', 'manager');
define('TCMP_TAB_MANAGER_URI', TCMP_PAGE_MANAGER.'&tab='.TCMP_TAB_MANAGER);
define('TCMP_TAB_SETTINGS', 'settings');
define('TCMP_TAB_SETTINGS_URI', TCMP_PAGE_MANAGER.'&tab='.TCMP_TAB_SETTINGS);
define('TCMP_TAB_FAQ', 'faq');
define('TCMP_TAB_FAQ_URI', TCMP_PAGE_MANAGER.'&tab='.TCMP_TAB_FAQ);
define('TCMP_TAB_ABOUT', 'about');
define('TCMP_TAB_ABOUT_URI', TCMP_PAGE_MANAGER.'&tab='.TCMP_TAB_ABOUT);
define('TCMP_TAB_WHATS_NEW', 'whatsnew');
define('TCMP_TAB_WHATS_NEW_URI', TCMP_PAGE_MANAGER.'&tab='.TCMP_TAB_WHATS_NEW);

include_once(dirname(__FILE__).'/autoload.php');
tcmp_include_php(dirname(__FILE__).'/includes/');

global $tcmp;
$tcmp=new TCMP_Singleton();
$tcmpTabs=new TCMP_Tabs();

class TCMP_Singleton {
    var $Lang;
    var $Utils;
    var $Form;
    var $Check;
    var $Options;
    var $Logger;
    var $Cron;
    var $Tracking;
    var $License;
    var $Manager;
    var $Ecommerce;
    var $Plugin;

    function __construct() {
        $this->Lang=new TCMP_Language();
        $this->Lang->load('tcmp', TCMP_PLUGIN_ROOT.'languages/Lang.txt');

        $this->Utils=new TCMP_Utils();
        $this->Form=new TCMP_Form();
        $this->Check=new TCMP_Check();
        $this->Options=new TCMP_Options();
        $this->Logger=new TCMP_Logger();
        $this->Cron=new TCMP_Cron();
        $this->Tracking=new TCMP_Tracking();
        $this->License=new TCMP_License();
        $this->Manager=new TCMP_Manager();
        $this->Ecommerce=new TCMP_Ecommerce();
	$this->Plugin=new TCMP_Plugin();
    }
}
//from Settings_API_Tabs_Demo_Plugin
class TCMP_Tabs {
    private $tabs = array();

    function __construct() {
        global $tcmp;
        if($tcmp->Utils->isAdminUser()) {
            add_action('admin_menu', array(&$this, 'attachMenu'));
            add_filter('plugin_action_links', array(&$this, 'pluginActions'), 10, 2);
            add_action('admin_enqueue_scripts', array(&$this, 'enqueueScripts'));
        }
    }

    function attachMenu() {
        global $tcmp;

        $name='Tracking Code Manager PRO';
        add_submenu_page('options-general.php'
            , $name, $name
            , 'edit_posts', TCMP_PLUGIN_NAME, array(&$this, 'showTabPage'));
        $tcmp->License->pluginUpdater();
    }
    function pluginActions($links, $file) {
        global $tcmp;
        if($file==TCMP_PLUGIN_NAME.'/index.php'){
            if(!$tcmp->License->hasPremium()) {
                $settings = "<a href='".TCMP_TAB_SETTINGS_URI."'>" . $tcmp->Lang->L('Insert License') . '</a> ';
            } else {
                $settings = "<a href='".TCMP_TAB_MANAGER_URI."'>" . $tcmp->Lang->L('Settings') . '</a> ';
            }

            //$premium = "<a href='".TCMP_PAGE_PREMIUM."'>" . $tcmp->Lang->L('PREMIUM') . '</a> ';
            $links = array_merge(array($settings), $links);
        }
        return $links;
    }
    function enqueueScripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('suggest');
        wp_enqueue_script('jquery-ui-autocomplete');

	//to create a different name each version to bypass cache problem
        $p='?p='.TCMP_PLUGIN_VERSION;
        wp_enqueue_style('tcmp-css', plugins_url('assets/css/style.css', __FILE__ ));
        wp_enqueue_style('tcmp-select2-css', plugins_url('assets/deps/select2-3.5.2/select2.css', __FILE__ ).$p);

        wp_enqueue_script('tcmp-select2-js', plugins_url('assets/deps/select2-3.5.2/select2.min.js', __FILE__ ).$p);
        wp_enqueue_script('tcmp-starrr-js', plugins_url('assets/deps/starrr/starrr.js', __FILE__ ).$p);

        wp_register_script('tcmp-autocomplete', plugins_url('assets/js/tcmp-autocomplete.js', __FILE__ ).$p, array('jquery', 'jquery-ui-autocomplete'), '1.0', FALSE);
        wp_localize_script('tcmp-autocomplete', 'TCMAutocomplete', array('url' => admin_url('admin-ajax.php')
        ));
        wp_enqueue_script('tcmp-autocomplete');
    }

    function showTabPage() {
        global $tcmp;

        $message='';
        $success=FALSE;
        $licenseKey='';
        if($tcmp->Check->nonce('tcmp_license')) {
            $licenseKey=$tcmp->Utils->qs('key', '');
            if($licenseKey=='' && $tcmp->Options->getLicenseKey()!='') {
                $licenseKey=$tcmp->Options->getLicenseKey();
                $success=$tcmp->License->deactivate();
                $message='Deactivate';
            } elseif($licenseKey!='' && $licenseKey!=$tcmp->Options->getLicenseKey()) {
                $success=$tcmp->License->activate($licenseKey);
                $message='Activate';
            }
        }

        $id=intval($tcmp->Utils->qs('id', 0));
        $defaultTab=TCMP_TAB_MANAGER;
        if(!$tcmp->License->hasPremium()) {
            $defaultTab=TCMP_TAB_SETTINGS;
        }
        $tab=$tcmp->Utils->qs('tab', $defaultTab);

	if($tcmp->Options->isShowWhatsNew()) {
            $tab=TCMP_TAB_WHATS_NEW;
	        $defaultTab=$tab;
            $this->tabs[TCMP_TAB_WHATS_NEW]=$tcmp->Lang->L('What\'s New');
            //$this->tabs[TCMP_TAB_MANAGER]=$tcmp->Lang->L('Start using the plugin!');
        } else {
	        if(!$tcmp->License->hasPremium()) {
	            $this->tabs[TCMP_TAB_SETTINGS]=$tcmp->Lang->L('License');
	        } else {
	            if($id>0 || $tcmp->Manager->rc()>0) {
	                $this->tabs[TCMP_TAB_EDITOR]=$tcmp->Lang->L($id>0 && $tab==TCMP_TAB_EDITOR ? 'Edit' : 'Add new');
	            } elseif($tab==TCMP_TAB_EDITOR) {
	                $tab = TCMP_TAB_MANAGER;
	            }

	            $this->tabs[TCMP_TAB_MANAGER]=$tcmp->Lang->L('Manager');
	            $this->tabs[TCMP_TAB_SETTINGS]=$tcmp->Lang->L('Settings');
	            $this->tabs[TCMP_TAB_FAQ]=$tcmp->Lang->L('FAQ & Video');
	            $this->tabs[TCMP_TAB_ABOUT]=$tcmp->Lang->L('About');
	        }
	}

        ?>
        <div class="wrap" style="margin:5px;">
            <?php
            $this->showTabs($defaultTab);
            $header='';
            switch ($tab) {
                case TCMP_TAB_EDITOR:
                    $header=($id>0 ? 'Edit' : 'Add');
                    break;
		case TCMP_TAB_WHATS_NEW:
                    $header='';
                    break;
                case TCMP_TAB_MANAGER:
                    $header='Manager';
                    break;
                case TCMP_TAB_SETTINGS:
                    $header='Settings';
                    break;
                case TCMP_TAB_FAQ:
                    $header='Faq';
                    break;
                case TCMP_TAB_ABOUT:
                    $header='About';
                    break;
            }

            if($tcmp->Lang->H($header.'Title')) { ?>
                <h2><?php $tcmp->Lang->P($header . 'Title', TCMP_PLUGIN_VERSION) ?></h2>
                <?php if ($tcmp->Lang->H($header . 'Subtitle')) { ?>
                    <div><?php $tcmp->Lang->P($header . 'Subtitle') ?></div>
                <?php } ?>
                <br/>
            <?php }

            tcmp_ui_first_time();
            if($message!='') {
                $tcmp->Options->pushMessage($success, $message, $licenseKey);
                $tcmp->Options->writeMessages();
            }

            switch ($tab) {
		case TCMP_TAB_WHATS_NEW:
                    tcmp_ui_whats_new();
                    break;
                case TCMP_TAB_EDITOR:
                    tcmp_ui_editor();
                    break;
                case TCMP_TAB_MANAGER:
                    tcmp_ui_manager();
                    break;
                case TCMP_TAB_SETTINGS:
                    tcmp_ui_track();
                    tcmp_ui_settings();
                    break;
                case TCMP_TAB_FAQ:
                    tcmp_ui_track();
                    tcmp_ui_faq();
                    break;
                case TCMP_TAB_ABOUT:
                    tcmp_ui_about();
                    tcmp_ui_feedback();
                    break;
            } ?>
        </div>
    <?php }

    function showTabs($defaultTab) {
        global $tcmp;
        $tab=$tcmp->Check->of('tab', $defaultTab);
	if($tcmp->Options->isShowWhatsNew()) {
            $tab=TCMP_TAB_WHATS_NEW;
        }

        ?>
        <h2 class="nav-tab-wrapper" style="float:left; width:97%;">
            <?php
            foreach ($this->tabs as $k=>$v) {
                $active = ($tab==$k ? 'nav-tab-active' : '');
		$style='';
                if($tcmp->Options->isShowWhatsNew() && $k==TCMP_TAB_MANAGER) {
                    $active='';
                    $style='background-color:#F2E49B';
                }
                ?>
                <a style="float:left; <?php echo $style?>" class="nav-tab <?php echo $active?>" href="?page=<?php echo TCMP_PLUGIN_NAME?>&tab=<?php echo $k?>"><?php echo $v?></a>
            <?php
            }
            ?>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.2.0/css/font-awesome.min.css">
            <style>
                .starrr {display:inline-block}
                .starrr i{font-size:16px;padding:0 1px;cursor:pointer;color:#2ea2cc;}
            </style>
            <div style="float:right; display:none;" id="rate-box">
                <span style="font-weight:700; font-size:13px; color:#555;"><?php $tcmp->Lang->P('Rate us')?></span>
                <div id="tcmp-rate" class="starrr" data-connected-input="tcmp-rate-rank"></div>
                <input type="hidden" id="tcmp-rate-rank" name="tcmp-rate-rank" value="5" />

            </div>
            <script>
                jQuery(function() {
                    jQuery(".starrr").starrr();
                    jQuery('#tcmp-rate').on('starrr:change', function(e, value){
                        var url='https://wordpress.org/support/view/plugin-reviews/tracking-code-manager?rate=5#postform';
                        window.open(url);
                    });
                    jQuery('#rate-box').show();
                });
            </script>
        </h2>
        <div style="clear:both;"></div>
    <?php }
}


