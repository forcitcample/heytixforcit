<?php
	require_once 'plivo.php';
	
	$response = new Response();
	$response->addSpeak('No body available now. Please leave your message after the tone, when you are done, press the pound key!');
	$response->addRecord(array('action' => 'http://skiephone.designveloper.com/plivo-1800/plivo-voicemail/confirm-input.php',
						   'method' => 'POST',
						   //'maxLength' => '60',
						   'finishOnKey' => '#',
						   'playBeep' => 'true'));
	$response->addSpeak('Recording not received');
	$response->addRedirect('http://skiephone.designveloper.com/plivo-1800/plivo-voicemail/get-input.php', array('method' => 'GET'));
	header('content-type: text/xml');
	echo($response->toXML());
?>
