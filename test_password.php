<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

// Include User class
require_once __DIR__ . '/classes/User.php';

// Test user credentials
$testEmail = 'test@example.com'; // Replace with an actual email from your database
$testPassword = 'test123'; // Try with the password you're testing

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Test user lookup
    $user = new User($db);
    $user->email = $testEmail;
    
    echo "Testing login for email: " . htmlspecialchars($testEmail) . "<br>\n";
    
    if ($user->emailExists()) {
        echo "User found in database.<br>\n";
        echo "Stored password hash: " . htmlspecialchars($user->password) . "<br>\n";
        
        // Test password verification
        $isValid = password_verify($testPassword, $user->password);
        echo "Password verification: " . ($isValid ? "SUCCESS" : "FAILED") . "<br>\n";
        
        if (!$isValid) {
            echo "Password verification failed. Possible reasons:<br>\n";
            echo "1. The password you entered is incorrect<br>\n";
            echo "2. The password in the database is not properly hashed<br>\n";
            
            // Test if the password needs rehashing
            if (password_needs_rehash($user->password, PASSWORD_DEFAULT)) {
                echo "WARNING: Password needs rehashing. The stored hash is using an outdated algorithm.<br>\n";
            }
        }
    } else {
        echo "User not found in database.<br>\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>\n";
}

echo "<hr>\n";
echo "<h3>User Registration Test</h3>\n";

// Test user registration
$newUser = [
    'email' => 'test_' . time() . '@example.com',
    'password' => 'test123',
    'first_name' => 'Test',
    'last_name' => 'User',
    'role' => 'guest'
];

try {
    $testUser = new User($db);
    $testUser->email = $newUser['email'];
    $testUser->password = $newUser['password'];
    $testUser->first_name = $newUser['first_name'];
    $testUser->last_name = $newUser['last_name'];
    $testUser->role = $newUser['role'];
    
    if ($testUser->create()) {
        echo "Test user created successfully.<br>\n";
        echo "Email: " . htmlspecialchars($newUser['email']) . "<br>\n";
        echo "Password: " . htmlspecialchars($newUser['password']) . "<br>\n";
        
        // Now try to log in with this user
        $loginUser = new User($db);
        $loginUser->email = $newUser['email'];
        
        if ($loginUser->emailExists()) {
            $isValid = password_verify($newUser['password'], $loginUser->password);
            echo "Login test with new user: " . ($isValid ? "SUCCESS" : "FAILED") . "<br>\n";
            
            if (!$isValid) {
                echo "Stored hash: " . htmlspecialchars($loginUser->password) . "<br>\n";
                echo "This indicates a problem with password hashing during user creation.<br>\n";
            }
        }
    } else {
        echo "Failed to create test user.<br>\n";
    }
} catch (Exception $e) {
    echo "Error creating test user: " . $e->getMessage() . "<br>\n";
}
?>
