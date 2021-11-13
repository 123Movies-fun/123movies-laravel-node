<?php
session_cache_limiter('nocache');
header('Expires: ' . gmdate('r', 0));
header('Content-type: application/json');

$Recipient = 'set_your_email_here@domain.com'; // <-- Set your email here

if($Recipient) {

	$Name = $_POST['name'];
	$Email = $_POST['email'];
	$Subject = $_POST['subject'];
	$Message = $_POST['message'];
	if (isset($_POST['guests'])) {
		$Guests = $_POST['guests'];
	} else {
		$Guests = "";
	}
	if (isset($_POST['events'])) {
		$Events = $_POST['events'];
	} else {
		$Events = "";
	}
	if (isset($_POST['category'])) {
		$Category = $_POST['category'];
	} else {
		$Category = "";
	}

	$Email_body = "";
	$Email_body .= "From: " . $Name . "\n" .
				   "Email: " . $Email . "\n" .
				   "Subject: " . $Subject . "\n" .
				   "Message: " . $Message . "\n" .
				   "No Of Guests: " . $Guests . "\n" .
				   "Event: " . $Events . "\n" .
				   "Category: " . $Category . "\n";

	$Email_headers = "";
	$Email_headers .= 'From: ' . $Name . ' <' . $Email . '>' . "\r\n".
					  "Reply-To: " .  $Email . "\r\n";

	$sent = mail($Recipient, $Subject, $Email_body, $Email_headers);

	if ($sent){
		$emailResult = array ('sent'=>'yes');
	} else{
		$emailResult = array ('sent'=>'no');
	}

	echo json_encode($emailResult);

} else {

	$emailResult = array ('sent'=>'no');
	echo json_encode($emailResult);

}
?>
