<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include necessary files
require_once '../../config/database.php';
require_once '../../classes/User.php';
require_once '../../helpers/email.php'; // We'll create this next

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Function to send JSON response
function sendResponse($status, $message, $data = null) {
    http_response_code($status);
    $response = ['message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit();
}

try {
    // Validate required fields
    $required_fields = ['email', 'password', 'first_name', 'last_name'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($data->$field)) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        sendResponse(400, 'Missing required fields: ' . implode(', ', $missing_fields));
    }

    // Validate email format
    if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(400, 'Invalid email format.');
    }

    // Validate password strength
    if (strlen($data->password) < 8) {
        sendResponse(400, 'Password must be at least 8 characters long.');
    }
    if (!preg_match("/[^a-zA-Z0-9]/", $data->password)) {
        sendResponse(400, 'Password must contain at least one special character.');
    }

    // Set default role to 'customer' if not provided
    $data->role = isset($data->role) ? strtolower($data->role) : 'customer';
    
    // Validate role
    $valid_roles = ['admin', 'manager', 'customer'];
    if (!in_array($data->role, $valid_roles)) {
        sendResponse(400, "Invalid role specified. Must be one of: " . implode(', ', $valid_roles));
    }

    // Validate professional details for managers
    if ($data->role === 'manager' && empty($data->professional_details)) {
        sendResponse(400, 'Professional details are required for manager role');
    }

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if email already exists
    $user = new User($db);
    $user->email = $data->email;
    
    if ($user->emailExists()) {
        sendResponse(400, 'Email already exists.');
    }

    // Set user properties
    $user->first_name = $data->first_name;
    $user->last_name = $data->last_name;
    $user->password = $data->password;
    $user->role = $data->role;
    $user->professional_details = $data->professional_details ?? null;

    // Create the user
    if ($user->create()) {
        // Send verification email
        $verification_link = "https://yourdomain.com/api/auth/verify.php?token=" . $user->verification_token;
        $email_sent = sendVerificationEmail($user->email, $user->first_name, $verification_link);
        
        // Prepare user data for response (exclude sensitive data)
        $user_data = [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'role' => $user->role,
            'email_verified' => (bool)$user->email_verified
        ];
        
        // Include professional details if present
        if ($user->professional_details) {
            $user_data['professional_details'] = json_decode($user->professional_details);
        }
        
        $message = 'User registered successfully' . 
                  ($email_sent ? '. Verification email sent.' : '. Failed to send verification email.');
        
        sendResponse(201, $message, ['user' => $user_data]);
    } else {
        sendResponse(500, 'Unable to register user. Please try again.');
    }
} catch (Exception $e) {
    // Log the error for debugging
    error_log('Registration error: ' . $e->getMessage());
    
    // Send a generic error message to the client
    sendResponse(500, 'An error occurred during registration. Please try again.');
}

// The sendVerificationEmail function is now in helpers/email.php
?>
