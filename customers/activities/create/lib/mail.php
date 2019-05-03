<?php

namespace Mailer;

ini_set( "display_errors", 0 );
ini_set( "error_reporting", E_ALL );

// Set the timezone
date_default_timezone_set( 'Asia/Kolkata' );
// Do not let this script timeout
set_time_limit( 0 );

// Pull dependencies
require_once __DIR__ . '/../../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;




/*
 *
 * Set constant values
 *
 */
class DATA {
	public static $username = 'google@lazaro.in';
	public static $password = 't34m,l4z4r0';
}





/*
 * -----
 * Send an email to someone regarding something
 * -----
 */
function send ( $to, $subject, $body ) {

	date_default_timezone_set( 'Asia/Kolkata' );

	// Create a new PHPMailer instance
	$mail = new PHPMailer( true );

	try {

		// Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$mail->SMTPDebug = 0;

		// Tell PHPMailer to use SMTP
		$mail->isSMTP();

		// Ask for HTML-friendly debug output
		$mail->Debugoutput = 'html';

		// Set the hostname of the mail server
		$mail->Host = 'smtp.gmail.com';

		// Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
		$mail->Port = 587;

		// Set the encryption system to use - ssl (deprecated) or tls
		$mail->SMTPSecure = 'tls';

		// Use SMTP authentication
		$mail->SMTPAuth = true;

		// Username to use for SMTP authentication - use full email address for gmail
		$mail->Username = DATA::$username;

		// Password to use for SMTP authentication
		$mail->Password = DATA::$password;

		// Set who the message is to be sent from
		$mail->setFrom( DATA::$username, 'Omega' );

		// Set who the message is to be sent to
		$mail->addAddress( $to, '' );
			// $mail->addCC( $additionalEmailAddress, '' );

		// Set an alternative reply-to address
		// $mail->addReplyTo('replyto@example.com', 'First Last');

		$mail->isHTML( true );

		// Set the subject line
		$mail->Subject = $subject;

		// Set the mail body
		$mail->Body = preg_replace( '/\R/', '<br>', $body );

		// Send the message
		$mail->send();

		return true;

	}
	catch ( \Exception $e ) {

		$errorMessage = 'The email could not be sent.' . PHP_EOL;
		$errorMessage .= $mail->ErrorInfo;
		throw new \Exception( $errorMessage, 5001 );

	}

}
