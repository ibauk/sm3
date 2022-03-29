<?php
/*
 * emailer.php
 *
 *
 */

require_once("common.php"); 
require_once("vendor\autoload.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/PHPMailer/PHPMailer-master/src/Exception.php';
require 'vendor/PHPMailer/PHPMailer-master/src/PHPMailer.php';
require 'vendor/PHPMailer/PHPMailer-master/src/SMTP.php';


$mail = new PHPMailer();
$mail->IsSMTP();
$mail->Mailer = "smtp";

$mail->SMTPDebug  = 1;  
$mail->SMTPAuth   = TRUE;
$mail->SMTPSecure = "tls";
$mail->Port       = 587;
$mail->Host       = "smtp.gmail.com";
$mail->Username   = "ibaukebc@gmail.com";
$mail->Password   = "9s&Nx#PTT";

$mail->IsHTML(true);
$mail->AddAddress("stammers.bob@gmail.com", "Bob Stammers");
$mail->SetFrom("ibaukebc@gmail.com", "The Rally Team");
//$mail->AddReplyTo("reply-to-email@domain", "reply-to-name");
//$mail->AddCC("cc-recipient-email@domain", "cc-recipient-name");
$mail->Subject = "Test is Test Email sent via Gmail SMTP Server using PHP Mailer";
$content = "<b>This is a Test Email sent via Gmail SMTP Server using PHP mailer class.</b>";
 
$mail->MsgHTML($content); 
if(!$mail->Send()) {
  echo "Error while sending Email.";
  var_dump($mail);
} else {
  echo "Email sent successfully";
}
?>
