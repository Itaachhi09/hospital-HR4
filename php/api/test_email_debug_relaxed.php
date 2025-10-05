<?php
require __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

$gmailUser = getenv('GMAIL_USER') ?: 'deguroj@gmail.com';
$gmailAppPassword = getenv('GMAIL_APP_PASSWORD') ?: 'jrhdbqfhlrchushe';
$testRecipient = getenv('TEST_RECIPIENT') ?: 'johnpaulaustria321@gmail.com';

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = $gmailUser;
$mail->Password = $gmailAppPassword;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
$mail->Timeout = 60;

// CLI debug to stdout
$mail->SMTPDebug = 3;
$mail->Debugoutput = 'echo';

// TEMPORARY: relax cert checks for debugging only
$mail->SMTPOptions = [
  'ssl' => [
    'verify_peer' => false,
    'verify_peer_name' => false,
    'allow_self_signed' => true,
  ],
];

$mail->setFrom($gmailUser, 'HR Test');
$mail->addAddress($testRecipient);
$mail->Subject = 'PHPMailer TLS debug';
$mail->Body = 'Debug test';
try {
  echo "Attempting to send to: {$testRecipient}\n";
  $mail->send();
  echo "OK: email sent\n";
} catch (\Exception $e) {
  echo "ERROR: " . ($mail->ErrorInfo ?: $e->getMessage()) . "\n";
}