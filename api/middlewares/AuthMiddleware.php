<?php
/**
 * AuthMiddleware
 *
 * Lightweight JWT (HS256) + session fallback authenticator.
 * Provides helpers to check roles and access current user info.
 */

class AuthMiddleware {
    private $currentUser = null;
    private $jwtSecret;
    private $jwtExpirySeconds;
    private $readOnly;

    public function __construct() {
        $config = $GLOBALS['api_config'] ?? [];
        $this->jwtSecret = $config['jwt']['secret'] ?? 'change-me';
        $this->jwtExpirySeconds = $config['jwt']['expiry'] ?? (24 * 60 * 60);
        $this->readOnly = (bool)($config['app']['read_only'] ?? false);
    }

    /**
     * Main entry: returns true if request is authenticated.
     */
    public function authenticate() {
        $token = $this->getBearerToken();
        if ($token) {
            $claims = $this->decodeJWT($token);
            if ($claims && $this->validateClaims($claims)) {
                $this->currentUser = [
                    'user_id' => $claims['uid'] ?? null,
                    'employee_id' => $claims['eid'] ?? null,
                    'username' => $claims['username'] ?? null,
                    'role_id' => $claims['role_id'] ?? null,
                    'role_name' => $claims['role_name'] ?? null,
                ];
                return true;
            }
        }

        // Legacy session fallback
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (!empty($_SESSION['user_id'])) {
            $this->currentUser = [
                'user_id' => $_SESSION['user_id'],
                'employee_id' => $_SESSION['employee_id'] ?? null,
                'username' => $_SESSION['username'] ?? null,
                'role_id' => $_SESSION['role_id'] ?? null,
                'role_name' => $_SESSION['role_name'] ?? null,
            ];
            return true;
        }

        return false;
    }

    /** Generate JWT token for a user */
    public function generateToken($userId, $employeeId, $username, $roleId, $roleName) {
        $header = [ 'typ' => 'JWT', 'alg' => 'HS256' ];
        $issuedAt = time();
        $payload = [
            'iss' => 'hospital-hr4-api',
            'iat' => $issuedAt,
            'exp' => $issuedAt + (int)$this->jwtExpirySeconds,
            'sub' => (string)$userId,
            'uid' => (int)$userId,
            'eid' => $employeeId ? (int)$employeeId : null,
            'username' => $username,
            'role_id' => $roleId ? (int)$roleId : null,
            'role_name' => $roleName,
        ];

        $segments = [];
        $segments[] = $this->b64Url(json_encode($header));
        $segments[] = $this->b64Url(json_encode($payload));
        $signature = hash_hmac('sha256', implode('.', $segments), $this->jwtSecret, true);
        $segments[] = $this->b64Url($signature);
        return implode('.', $segments);
    }

    /** Current user claims */
    public function getCurrentUser() {
        return $this->currentUser;
    }

    /** Role check by role names (case-insensitive) */
    public function hasAnyRole(array $roleNames) {
        $role = $this->currentUser['role_name'] ?? null;
        if (!$role) return false;
        foreach ($roleNames as $rn) {
            if (strcasecmp($rn, $role) === 0) return true;
        }
        return false;
    }

    /** Returns true when HR Core is configured to be read-only */
    public function isReadOnly(): bool {
        return (bool)$this->readOnly;
    }

    private function getBearerToken() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $auth = $headers['Authorization'] ?? ($headers['authorization'] ?? null);
        if ($auth && stripos($auth, 'Bearer ') === 0) {
            return trim(substr($auth, 7));
        }
        return null;
    }

    private function decodeJWT($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;
        [$h, $p, $s] = $parts;
        $headerJson = $this->b64UrlDecode($h);
        $payloadJson = $this->b64UrlDecode($p);
        if ($headerJson === false || $payloadJson === false) return null;
        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);
        if (!$header || !$payload) return null;
        if (($header['alg'] ?? 'none') !== 'HS256') return null;
        $expected = $this->b64Url(hash_hmac('sha256', "$h.$p", $this->jwtSecret, true));
        if (!hash_equals($expected, $s)) return null;
        return $payload;
    }

    private function validateClaims($claims) {
        $now = time();
        if (isset($claims['exp']) && $now > (int)$claims['exp']) return false;
        return true;
    }

    private function b64Url($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) $data .= str_repeat('=', 4 - $remainder);
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
