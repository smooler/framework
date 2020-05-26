<?php
namespace Smooler\Tasks;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Email
{
    public function sendLog($subject, $body)
    {
    	$mail = new PHPMailer(true);
    	try {
    		global $app;
    		$host = $app->config->get('app.email_host');
    		$username = $app->config->get('app.email_username');
    		$password = $app->config->get('app.email_password');
    		$fromAddress = $app->config->get('app.email_from_address');
    		$toAddresses = $app->config->get('app.email_to_addresses');
    		if ($host && $username && $password && $fromAddress && $toAddresses) {
			    //Server settings
			    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
			    $mail->isSMTP();                                            // Send using SMTP
			    $mail->Host       = $host; //'smtp1.example.com';                    // Set the SMTP server to send through
			    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
			    $mail->Username   = $username; //'user@example.com';                     // SMTP username
			    $mail->Password   = $password; //'secret';                               // SMTP password
			    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
			    $mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

			    //Recipients
			    // $mail->setFrom('from@example.com', 'Mailer');
			    $mail->setFrom($fromAddress);

				foreach ($toAddresses as $value) {
				    // $mail->addAddress('joe@example.net', 'Joe User');     // Add a recipient
				    $mail->addAddress($value);               // Name is optional
				}
			    // $mail->addReplyTo('info@example.com', 'Information');
			    // $mail->addCC('cc@example.com');
			    // $mail->addBCC('bcc@example.com');

			    // Attachments
			    // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
			    // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

			    // Content
			    $mail->isHTML(true);                                  // Set email format to HTML
			    $mail->Subject = $subject; // 'Here is the subject';
			    $mail->Body    = $body; //'This is the HTML message body <b>in bold!</b>';
			    // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

			    $mail->send();
			    echo 'email has been sent';
    		} else {
			    echo 'email config error';
    		}
		} catch (Exception $e) {
		    echo "email could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}
    }
}