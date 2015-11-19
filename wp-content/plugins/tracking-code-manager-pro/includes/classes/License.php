<?php
/**
 * Created by PhpStorm.
 * User: alessio
 * Date: 24/03/2015
 * Time: 08:45
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

define('TCMP_LICENSE_SERVER', 'https://intellywp.com');
define('TCMP_EDD_NAME', 'Tracking Code Manager PRO');
//if(!class_exists('TCMP_EDD_SL_Plugin_Updater')) {
//    include(dirname(__FILE__) . '/TCMP_EDD_SL_Plugin_Updater.php');
//}

class TCMP_License {

    public function __construct() {
        //add_filter( 'cron_schedules', array( $this, 'add_schedules'   ) );
        //add_action( 'wp',             array( $this, 'schedule_Events' ) );
        add_action('admin_init', array($this, 'pluginUpdater'));
        add_action('tcmp_daily_scheduled_events', array($this, 'checkLicense'));
    }

    function pluginUpdater() {
        global $tcmp;
        $tcmp->Logger->debug('pluginUpdater');
        $args=array(
            'version' => TCMP_PLUGIN_VERSION
            , 'license'=> $tcmp->Options->getLicenseKey()
            , 'item_name' => TCMP_EDD_NAME
            , 'author' 	=> TCMP_PLUGIN_AUTHOR
        );
        $tcmpUpdater = new TCMP_EDD_SL_Plugin_Updater(TCMP_LICENSE_SERVER, TCMP_PLUGIN_FILE, $args);
    }

    //activate the new license key
    function activate($key) {
        global $tcmp;

        $api_params = array(
            'edd_action'=> 'activate_license'
            , 'license'=> $key
            , 'item_name'=> urlencode(TCMP_EDD_NAME)
            , 'url'=> home_url()
        );

        $args=array('timeout' => 15, 'sslverify' => false);
        $response = wp_remote_get(add_query_arg($api_params, TCMP_LICENSE_SERVER), $args);

        // make sure the response came back okay
        $result=FALSE;
        if (!is_wp_error($response)) {
            // decode the license data
            $license_data=json_decode(wp_remote_retrieve_body($response));
            // $license_data->license will be either "valid" or "invalid"
            $result=strtolower($license_data->license)=='valid';
            $tcmp->Options->setLicenseSuccess($result);
            if($result) {
                $tcmp->Options->setLicenseKey($key);
            }
        }
        return $result;
    }

    //deactivate the installed license
    function deactivate() {
        global $tcmp;

        // data to send in our API request
        $api_params = array(
            'edd_action'=> 'deactivate_license'
            , 'license'=> $tcmp->Options->getLicenseKey()
            , 'item_name' => urlencode(TCMP_EDD_NAME) // the name of our product in EDD
            , 'url' => home_url()
        );

        $args=array('timeout' => 15, 'sslverify' => false);
        $response = wp_remote_get(add_query_arg($api_params, TCMP_LICENSE_SERVER), $args);

        // make sure the response came back okay
        $result=FALSE;
        if (!is_wp_error($response)) {
            // decode the license data
            $data = json_decode(wp_remote_retrieve_body($response));
            // $license_data->license will be either "deactivated" or "failed"
            //if($license_data->license == 'deactivated') {
            $tcmp->Options->setLicenseKey('');
            $tcmp->Options->setLicenseSuccess(FALSE);
            $result=TRUE;
        }
        return $result;
    }

    //retrieve if the current license used is stil valid or not
    public function hasPremium($override=FALSE) {
        global $tcmp;

        $result=FALSE;
        if($tcmp->Options->getLicenseKey()=='') {
            $result=FALSE;
        } else {
            $check=$tcmp->Options->getLicenseLastCheck();
            if(!$override && $check>strtotime('-1 day')) {
                $result=$tcmp->Options->isLicenseSuccess();
            } else {
                $result=$this->check();
            }
        }
        return $result;
    }
    public function check() {
        global $tcmp;

        $result=FALSE;
        if($tcmp->Options->getLicenseKey()=='') {
            $result=FALSE;
        } else {
            $api_params = array(
                'edd_action' => 'check_license'
                , 'license' => $tcmp->Options->getLicenseKey()
                , 'item_name' => urlencode(TCMP_EDD_NAME)
                , 'url' => home_url()
            );

            $args=array('timeout' => 15, 'sslverify' => false);
            $response = wp_remote_get(add_query_arg($api_params, TCMP_LICENSE_SERVER), $args);

            if (is_wp_error($response))
                return false;

            $license_data = json_decode(wp_remote_retrieve_body($response));
            if($license_data->license == 'valid') {
                $tcmp->Options->setLicenseSuccess(TRUE);
                $result=TRUE;
            } else {
                $tcmp->Options->setLicenseSuccess(FALSE);
            }
            $tcmp->Options->setLicenseLastCheck(time());
        }

        return $result;
    }
}