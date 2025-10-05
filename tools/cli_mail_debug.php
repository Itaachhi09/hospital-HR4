<?php

require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$gmailUser = getenv('GMAIL_USER') ?: '';
$gmailAppPassword = getenv('GMAIL_APP_PASSWORD') ?: '';
$to = getenv('TEST_RECIPIENT') ?: $gmailUser;

if (!$gmailUser || !$gmailAppPassword) {
    fwrite(STDERR, "Missing GMAIL_USER or GMAIL_APP_PASSWORD\n");
    exit(1);
}

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = $gmailUser;
$mail->Password = $gmailAppPassword;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
$mail->Timeout = 60;
$mail->SMTPDebug = 3;
$mail->Debugoutput = 'echo';

// secure, explicit cafile
$mail->SMTPOptions = [
  'ssl' => [
    'verify_peer' => true,
    'verify_peer_name' => true,
    'allow_self_signed' => false,
    'cafile' => 'C:\\NEWXAMPP\\php\\extras\\ssl\\cacert.pem',
    'peer_name' => 'smtp.gmail.com',
  ],
];

$mail->setFrom($gmailUser, 'HR Test');
$mail->addAddress($to);
$mail->Subject = 'CLI debug';
$mail->Body = 'CLI debug';

try {
    echo "Attempting to send to: {$to}\n";
    $mail->send();
    echo "OK: email sent\n";
} catch (Exception $e) {
    echo "ERROR: " . ($mail->ErrorInfo ?: $e->getMessage()) . "\n";
    exit(1);
}