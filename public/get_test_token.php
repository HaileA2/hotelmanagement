<?php
// Temporary helper for local testing only - do not commit or leave in production
require_once __DIR__ . '/../helpers/jwt_helper.php';
$jwt = new JwtHandler();
// Generate token for user_id=1 with admin role
echo $jwt->generateToken(1, 'admin');

?>
