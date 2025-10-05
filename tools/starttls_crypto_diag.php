<?php
// Minimal STARTTLS crypto diagnostic
$host = 'smtp.gmail.com';
$port = 587;
$timeout = 10;

echo "PHP: " . PHP_VERSION . " OpenSSL: " . OPENSSL_VERSION_TEXT . PHP_EOL;
echo "stream transports: "; var_export(stream_get_transports()); echo PHP_EOL;
echo "openssl cert locations: "; var_export(openssl_get_cert_locations()); echo PHP_EOL;

$fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout);
echo "socket open: " . var_export($fp !== false, true) . " errno={$errno} errstr=" . var_export($errstr, true) . PHP_EOL;
if (!$fp) exit(1);

stream_set_timeout($fp, 30);
echo "BANNER: " . rtrim(fgets($fp)) . PHP_EOL;

fwrite($fp, "EHLO diag.local\r\n");
while (($l = fgets($fp)) !== false) {
    echo "S: " . rtrim($l) . PHP_EOL;
    if (substr($l,0,3) === '250' && substr($l,3,1) === ' ') break;
}

fwrite($fp, "STARTTLS\r\n");
echo "STARTTLS RESP: " . rtrim(fgets($fp)) . PHP_EOL;

// Try enabling crypto with a set of methods (only those defined)
$methods = [
    'STREAM_CRYPTO_METHOD_TLS_CLIENT',
    'STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT',
    'STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT',
    'STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT',
    'STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT',
    'STREAM_CRYPTO_METHOD_ANY_CLIENT',
];

foreach ($methods as $name) {
    if (!defined($name)) { echo "$name not defined\n"; continue; }
    $const = constant($name);
    echo "Trying $name ($const)...\n";
    $ok = @stream_socket_enable_crypto($fp, true, $const);
    echo "$name => " . var_export($ok, true) . PHP_EOL;
    // print OpenSSL errors
    while ($e = openssl_error_string()) {
        echo "openssl_error: $e" . PHP_EOL;
    }
    $err = error_get_last();
    if ($err) echo "last_error: " . json_encode($err) . PHP_EOL;
    // if succeeded, break
    if ($ok) {
        echo "Crypto enabled with $name\n";
        break;
    }
}

@fclose($fp);
echo "diagnostic complete\n";