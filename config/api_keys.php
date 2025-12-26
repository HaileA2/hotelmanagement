<?php
// config/api_keys.php
// API Keys configuration for external service access

// Load API keys from environment variables
$API_KEYS = [];

// Check for environment variables and build API keys array
if (getenv('HOTEL_API_KEY_1')) {
    $API_KEYS[getenv('HOTEL_API_KEY_1')] = [
        'name' => getenv('HOTEL_API_KEY_1_NAME') ?: 'External Booking Service 1',
        'permissions' => explode(',', getenv('HOTEL_API_KEY_1_PERMISSIONS') ?: 'create_booking'),
        'active' => getenv('HOTEL_API_KEY_1_ACTIVE') !== 'false'
    ];
}

if (getenv('HOTEL_API_KEY_2')) {
    $API_KEYS[getenv('HOTEL_API_KEY_2')] = [
        'name' => getenv('HOTEL_API_KEY_2_NAME') ?: 'External Booking Service 2',
        'permissions' => explode(',', getenv('HOTEL_API_KEY_2_PERMISSIONS') ?: 'create_booking'),
        'active' => getenv('HOTEL_API_KEY_2_ACTIVE') !== 'false'
    ];
}

// Fallback for development (remove in production)
if (empty($API_KEYS) && getenv('APP_ENV') !== 'production') {
    $API_KEYS['HOTEL_API_KEY_2024'] = [
        'name' => 'External Booking Service (Development)',
        'permissions' => ['create_booking'],
        'active' => true
    ];
    // Additional demo keys for testing
    $API_KEYS['demo-api'] = [
        'name' => 'Demo API Key for Testing',
        'permissions' => ['create_booking'],
        'active' => true
    ];
}

// Function to validate API key
function validateApiKey($apiKey) {
    global $API_KEYS;
    return isset($API_KEYS[$apiKey]) && $API_KEYS[$apiKey]['active'];
}

// Function to check if API key has permission
function apiKeyHasPermission($apiKey, $permission) {
    global $API_KEYS;
    if (!validateApiKey($apiKey)) {
        return false;
    }
    return in_array($permission, $API_KEYS[$apiKey]['permissions']);
}

// Function to get all active API keys (for admin interface)
function getActiveApiKeys() {
    global $API_KEYS;
    $activeKeys = [];
    foreach ($API_KEYS as $key => $config) {
        if ($config['active']) {
            $activeKeys[$key] = $config;
        }
    }
    return $activeKeys;
}
?>