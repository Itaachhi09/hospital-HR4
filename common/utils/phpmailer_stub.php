<?php
/**
 * PHPMailer Stub for IDE Recognition
 * This file provides class stubs to help IDEs recognize PHPMailer classes
 */

namespace PHPMailer\PHPMailer;

/**
 * PHPMailer class stub for IDE recognition
 */
class PHPMailer {
    public function __construct($exceptions = false) {}
    public function isSMTP() {}
    public function setHost($host) {}
    public function setSMTPAuth($auth) {}
    public function setUsername($username) {}
    public function setPassword($password) {}
    public function setSMTPSecure($encryption) {}
    public function setPort($port) {}
    public function setFrom($address, $name = '') {}
    public function addAddress($address, $name = '') {}
    public function isHTML($isHtml = true) {}
    public function setSubject($subject) {}
    public function setBody($body) {}
    public function send() {}
    public $ErrorInfo;
    public $Body;
    
    // Constants
    const ENCRYPTION_SMTPS = 'ssl';
    const ENCRYPTION_STARTTLS = 'tls';
}

/**
 * PHPMailer Exception class stub for IDE recognition
 */
class Exception extends \Exception {
    public function __construct($message = '', $code = 0, ?\Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
