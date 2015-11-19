<?php
require 'plivo.php';

$dst = $_REQUEST['DialedTo'];
if(! $dst)
    $dst = $_REQUEST['To'];
$src = $_REQUEST['CLID'];
if(! $src)
    $src = $_REQUEST['From'] or "";
$cname = $_REQUEST['CallerName'] or "";
$hangup = $_REQUEST['HangupCause'];
$dial_music = $_REQUEST['DialMusic'];
$dial_status = $_REQUEST['DialStatus'];
    

$r = new Response();
//if($dst == '12672076067' && $dial_status != 'completed') {
if($dst == '1234567890' && $dial_status != 'completed') {
    //re-assign dst
    $dst = '84979820611';
    $dial_params = array();
    if($src)
        $dial_params['callerId'] = $src;
    if($cname)
        $dial_params['callerName'] = $cname;
    // handle call end
    $dial_params["action"] = "http://skiephone.designveloper.com/plivo-1800/plivo_callend.php?DialedTo=".$dst; 
    $d = $r->addDial($dial_params);
    $d->addNumber($dst);
 } 
 else if ($dst == '84979820611' && $dial_status != 'completed') {
    $r->addRedirect('http://skiephone.designveloper.com/plivo-1800/plivo-voicemail/get-input.php', array('method' => 'GET'));
 }
 else {
     $r->addHangup();
 }

header("Content-Type: text/xml");
echo($r->toXML());
?>