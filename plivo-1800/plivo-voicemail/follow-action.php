<?php
	require_once 'plivo.php';

	$response = new Response();
	
	$input_digits = $_REQUEST['Digits'];
	if ($input_digits == '1') {
	    $response->addPlay($_REQUEST['RecordUrl']);
        $response->addRedirect('http://skiephone.designveloper.com/plivo-1800/plivo-voicemail/confirm-input.php', array('method' =>'GET'));
	} else if ($input_digits == '2') { 
        $response->addRedirect('http://skiephone.designveloper.com/plivo-1800/plivo-voicemail/get-input.php', array('method' =>'GET'));
    } else if ($input_digits == '3') { 
        $response->addSpeak('Your message is saved. Bye.');
        // do necessary actions to save the recording and call uuid mapping.
    } else {
        $response->addSpeak('Invalid input');
        $response->addRedirect('http://skiephone.designveloper.com/plivo-1800/plivo-voicemail/get-input.php', array('method' =>'GET'));
    }

    header('Content-Type: text/xml');
    echo($response->toXML());
?>
