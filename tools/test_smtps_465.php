<?php

require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = getenv('GMAIL_USER');
$mail->Password = getenv('GMAIL_APP_PASSWORD');
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = 465;
$mail->SMTPDebug = 3;
$mail->Debugoutput = 'echo';
$mail->Timeout = 60;
$mail->setFrom(getenv('GMAIL_USER'));
$mail->addAddress(getenv('TEST_RECIPIENT'));
$mail->Subject = 'SMTPS 465 test';
$mail->Body = 'Test';
try { echo "Attempting...\n"; $mail->send(); echo "OK\n"; }
catch (Exception $e) { echo "ERROR: ".$mail->ErrorInfo."\n"; }