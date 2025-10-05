

<?php
// Test TCP connect (587 STARTTLS)
$r = @stream_socket_client('tcp://smtp.gmail.com:587', $errno, $errstr, 10);
var_export(['tcp-587' => [$r !== false, $errno, $errstr]]);
if ($r) fclose($r);
echo PHP_EOL;

// Test SSL connect (465 SMTPS)
$r2 = @stream_socket_client('ssl://smtp.gmail.com:465', $errno2, $errstr2, 10);
var_export(['ssl-465' => [$r2 !== false, $errno2, $errstr2]]);
if ($r2) fclose($r2);
echo PHP_EOL;