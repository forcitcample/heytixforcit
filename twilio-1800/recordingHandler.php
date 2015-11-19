<?php 

$TwiMLResponse =  " <Say>Thanks you, Good bye!</Say>";

$TwiMLResponse .= "<Hangup/>";

header("content-type: text/xml"); 
?>

<Response><?php echo $TwiMLResponse; ?></Response>