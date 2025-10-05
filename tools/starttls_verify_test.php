<?php

function doTest($verifyPeer) {
    $host = 'smtp.gmail.com';
    $port = 587;
    $timeout = 10;
    $cafile = 'C:\\NEWXAMPP\\php\\extras\\ssl\\cacert.pem';
    $opts = [
        'ssl' => [
            'verify_peer'      => $verifyPeer,
            'verify_peer_name' => $verifyPeer,
            'allow_self_signed'=> false,
            'cafile'           => $cafile,
        ],
    ];
    $ctx = stream_context_create($opts);
    echo "=== STARTTLS test (verify_peer=" . ($verifyPeer ? 'true' : 'false') . ") ===\n";
    $fp = @stream_socket_client("tcp://$host:$port", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    var_export(['connect' => $fp !== false, 'errno' => $errno, 'errstr' => $errstr]);
    echo "\n";
    if (!$fp) { echo "FAILED to open TCP socket\n\n"; return; }

    stream_set_timeout($fp, 30);
    // read banner
    $line = fgets($fp);
    echo "BANNER: " . var_export($line, true) . "\n";

    // send EHLO and read until a blank line or 250 lines end
    fwrite($fp, "EHLO localhost\r\n");
    while (($l = fgets($fp)) !== false) {
        echo "S: " . rtrim($l, "\r\n") . "\n";
        if (preg_match('/^250[ -]/', $l) && strpos($l, "STARTTLS") !== false) {
            // continue reading remaining 250-lines
            // but we break only when next line not starting with 250-
        }
        if (substr($l,0,3) === '250' && substr($l,3,1) === ' ') { break; }
        // safety: stop if blank or timeout
    }

    fwrite($fp, "STARTTLS\r\n");
    $resp = fgets($fp);
    echo "STARTTLS RESP: " . var_export($resp, true) . "\n";

    // Enable crypto
    $crypto = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    var_export(['stream_socket_enable_crypto' => $crypto]);
    echo "\n";

    if ($crypto) {
        echo "Crypto enabled, sending EHLO again\n";
        fwrite($fp, "EHLO localhost\r\n");
        while (($l = fgets($fp)) !== false) {
            echo "S(secure): " . rtrim($l, "\r\n") . "\n";
            if (substr($l,0,3) === '250' && substr($l,3,1) === ' ') break;
        }
    } else {
        echo "Crypto enable failed\n";
    }

    fclose($fp);
    echo "=== END test (verify_peer=" . ($verifyPeer ? 'true' : 'false') . ") ===\n\n";
}

// Run verified test first, then relaxed test
doTest(true);
doTest(false);