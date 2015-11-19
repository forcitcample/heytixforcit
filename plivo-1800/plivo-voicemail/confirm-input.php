<?php
	require_once 'plivo.php';
	
	$record_url = $_REQUEST['RecordUrl'];
	$response = new Response();
	$getdigits = $response->addGetDigits(array('action' => 'http://skiephone.designveloper.com/plivo-1800/plivo-voicemail/follow-action.php?RecordUrl=' . $record_url, 'method' => 'GET'));
	$getdigits->addSpeak('Press 1 to play your recording');
	$getdigits->addSpeak('Press 2 to start over');
	$getdigits->addSpeak('Press 3 to save and exit');

	header('content-type: text/xml');
	echo($response->toXML());
?>
