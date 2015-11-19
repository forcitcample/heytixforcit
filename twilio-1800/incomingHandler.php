<?php

// Set your first number
$firstNumber = "12672076067";
// Ser your second number here
$secondNumber = "12677849785";

$dialedNumber = $_REQUEST["DialedTo"];
$callStatus = $_REQUEST["DialCallStatus"];


if ($callStatus == "busy" || $callStatus == "no-answer" || $callStatus == "failed") {
    if ($dialedNumber == $firstNumber) {
        $TwiMLResponse = "<Dial timeout='12' action='./incomingHandler.php?DialedTo=".$secondNumber."'>"
                       . "<Number>". $secondNumber ."</Number>"
                       . "</Dial>";
    }
    else if ($dialedNumber == $secondNumber) {
        $TwiMLResponse = " <Say>I am sorry. No one is around at the moment to take your call."
                       . " Please leave your message after the beep, when done press pound key! </Say>"
                       . " <Record action=\"./recordingHandler.php\" finishOnKey=\"#\" />"
                       . " <Say>I did not receive a recording</Say>";
    }
}
else if ($callStatus == "completed") {
    $TwiMLResponse = "<Hangup/>";
}
else {
    
    $TwiMLResponse = "<Dial timeout='15' action='./incomingHandler.php?DialedTo=".$firstNumber."'>"
                   . "<Number>". $firstNumber ."</Number>"
                   . "</Dial>";
}


header("content-type: text/xml"); 
?>

<Response><?php echo $TwiMLResponse; ?></Response>