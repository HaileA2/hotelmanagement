<?php
class JwtHandler {
    private $secret_key = "YOUR_SECRET_KEY"; // Change this to a secure secret key
    private $issuer = "http://localhost/hotel-management-system";
    private $db;
    private $tokenBlacklist;

    public function __construct($db = null) {
        $this->db = $db;
        if ($db) {
            include_once __DIR__ . '/../classes/TokenBlacklist.php';
            $this->tokenBlacklist = new TokenBlacklist($db);
            // Clean up expired tokens on initialization
            $this->tokenBlacklist->cleanup();
        }
    }

    public function generateToken($user_id, $role) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'iss' => $this->issuer,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24), // Token valid for 24 hours
            'user_id' => $user_id,
            'role' => $role
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret_key, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function validateToken($token) {
        // Check if token is blacklisted first
        if ($this->db && $this->tokenBlacklist->isBlacklisted($token)) {
            return false;
        }

        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            return false;
        }

        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];
        // Check if token is expired
        $payloadData = json_decode($payload, true);
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return false;
        }
        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret_key, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        $signatureValid = ($base64UrlSignature === $signatureProvided);
        
        // If signature is valid, check if token is expired
        if ($signatureValid && isset($payloadData['exp'])) {
            return $payloadData['exp'] > time();
        } 
        return $signatureValid;
    }
    public function getTokenPayload($token) {
        try {
            // Log token for debugging (remove in production)
            error_log("Validating token: " . substr($token, 0, 10) . "...");
            
            // Check if token is blacklisted
            if ($this->db && $this->tokenBlacklist->isBlacklisted($token)) {
                error_log("Token is blacklisted");
                return null;
            }
            
            $tokenParts = explode('.', $token);
            if (count($tokenParts) !== 3) {
                error_log("Invalid token format: expected 3 parts");
                return null;
            }
            
            // Decode payload
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Failed to decode token payload: " . json_last_error_msg());
                return null;
            }
            
            // Check required claims
            $requiredClaims = ['exp', 'user_id', 'role'];
            foreach ($requiredClaims as $claim) {
                if (!isset($payload[$claim])) {
                    error_log("Missing required claim: $claim");
                    return null;
                }
            }
            
            // Check if token is expired
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                error_log("Token expired: " . date('Y-m-d H:i:s', $payload['exp']));
                return null;
            }
            
            // Log successful validation
            error_log("Token validated successfully for user ID: " . $payload['user_id']);
            error_log("User role: " . $payload['role']);
            
            return $payload;
            
        } catch (Exception $e) {
            error_log("Error in getTokenPayload: " . $e->getMessage());
            return null;
        }
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Add token to blacklist
     * @param string $token The JWT token to blacklist
     * @return bool True on success, false on failure
     */
    public function blacklistToken($token) {
        if (!$this->db) {
            return false;
        }
        
        $payload = $this->getTokenPayload($token);
        if (!$payload || !isset($payload['exp'])) {
            return false;
        }
        return $this->tokenBlacklist->add($token, $payload['exp']);
    }
}
?>
