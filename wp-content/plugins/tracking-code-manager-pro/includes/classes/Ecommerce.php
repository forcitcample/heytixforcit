<?php
if (!defined('ABSPATH')) exit;

class TCMP_Ecommerce {
    function __construct() {
        add_action('woocommerce_thankyou', array(&$this, 'wooCommerceThankYou'));
        add_action('edd_payment_receipt_after_table', array(&$this, 'eddThankYou'));
        add_action('wpsc_transaction_result_cart_item', array(&$this, 'eCommerceThankYou'));
    }

    public function getCustomPostType($pluginId) {
        $result='';
        switch (intval($pluginId)) {
            case TCMP_PLUGINS_WOOCOMMERCE:
                $result='product';
                break;
            case TCMP_PLUGINS_EDD:
                $result='download';
                break;
            case TCMP_PLUGINS_WP_ECOMMERCE:
                $result='wpsc-product';
                break;
        }
        return $result;
    }

    //WPSC_Purchase_Log_Customer_HTML_Notification
    function eCommerceThankYou($order) {
        global $tcmp;

        $orderId=intval($order['purchase_id']);
        $tcmp->Logger->debug('Ecommerce: ECOMMERCE THANKYOU');
        $tcmp->Logger->debug('Ecommerce: NEW ECOMMERCE ORDERID=%s', $orderId);

        $order=new WPSC_Purchase_Log($orderId);
        $items=$order->get_cart_contents();
        $productsIds=array();
        foreach ($items as $v) {
            if(isset($v->prodid)) {
                $k=intval($v->prodid);
                if($k) {
                    $v=$v->name;
                    $productsIds[]=$k;
                    $tcmp->Logger->debug('Ecommerce: ITEM %s=%s IN CART', $k, $v);
                }
            }
        }

        $args=array(
            'pluginId'=>TCMP_PLUGINS_WP_ECOMMERCE
            , 'productsIds'=>$productsIds
            , 'categoriesIds'=>array()
            , 'tagsIds'=>array()
        );
        $tcmp->Options->pushConversionSnippets($args);
        return '';
    }

    function eddThankYou($payment, $edd_receipt_args) {
        global $tcmp;

        $tcmp->Logger->debug('Ecommerce: EDD THANKYOU');
        $tcmp->Logger->debug('Ecommerce: NEW EDD ORDERID=%s', $payment->ID);
        $cart=edd_get_payment_meta_cart_details($payment->ID, TRUE);
        $productsIds=array();
        foreach ($cart as $key=>$item) {
            if(isset($item['id'])) {
                $k=intval($item['id']);
                if($k) {
                    $v=$item['name'];
                    $productsIds[]=$k;
                    $tcmp->Logger->debug('Ecommerce: ITEM %s=%s IN CART', $k, $v);
                }
            }
        }

        $args=array(
            'pluginId'=>TCMP_PLUGINS_EDD
            , 'productsIds'=>$productsIds
            , 'categoriesIds'=>array()
            , 'tagsIds'=>array()
        );
        $tcmp->Options->pushConversionSnippets($args);
    }
    function wooCommerceThankYou($orderId) {
        global $tcmp;
        $tcmp->Logger->debug('Ecommerce: WOOCOMMERCE THANKYOU');

        $order=new WC_Order($orderId);
        $items=$order->get_items();
        $tcmp->Logger->debug('Ecommerce: NEW WOOCOMMERCE ORDERID=%s', $orderId);
        $productsIds=array();
        foreach($items as $k=>$v) {
            $k=intval($v['product_id']);
            if($k>0) {
                $v=$v['name'];
                $tcmp->Logger->debug('Ecommerce: ITEM %s=%s IN CART', $k, $v);
                $productsIds[]=$k;
            }
        }

        $args=array(
            'pluginId'=>TCMP_PLUGINS_WOOCOMMERCE
            , 'productsIds'=>$productsIds
            , 'categoriesIds'=>array()
            , 'tagsIds'=>array()
        );
        $tcmp->Options->pushConversionSnippets($args);
    }

    function getActivePlugins() {
        return $this->getPlugins(TRUE);
    }
    function getPlugins($onlyActive=TRUE) {
        global $tcmp;

        $array=array();
        $array[]=TCMP_PLUGINS_WOOCOMMERCE;
        $array[]=TCMP_PLUGINS_EDD;
        $array[]=TCMP_PLUGINS_WP_ECOMMERCE;
        /*
        $array[]=TCMP_PLUGINS_WP_SPSC;
        $array[]=TCMP_PLUGINS_S2MEMBER;
        $array[]=TCMP_PLUGINS_MEMBERS;
        $array[]=TCMP_PLUGINS_CART66;
        $array[]=TCMP_PLUGINS_ESHOP;
        $array[]=TCMP_PLUGINS_JIGOSHOP;
        $array[]=TCMP_PLUGINS_MARKETPRESS;
        $array[]=TCMP_PLUGINS_SHOPP;
        $array[]=TCMP_PLUGINS_SIMPLE_WP_ECOMMERCE;
        $array[]=TCMP_PLUGINS_CF7;
        $array[]=TCMP_PLUGINS_GRAVITY;
        */

        $array=$tcmp->Plugin->getPlugins($array, $onlyActive);
        return $array;
    }
}
