<?php
require_once 'config/database.php';

// Database connection
$database = new Database();
$db = $database->getConnection();

// Admin user data
$email = 'admin@example.com';
$password = 'admin123';
$first_name = 'Admin';
$last_name = 'User';
$role = 'admin';

// Check if admin already exists
$query = "SELECT id FROM users WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindParam(":email", $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo "Admin user already exists.\n";
    exit;
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insert admin user
$query = "INSERT INTO users (email, password, first_name, last_name, role, created_at) 
          VALUES (:email, :password, :first_name, :last_name, :role, NOW())";
          
$stmt = $db->prepare($query);
$stmt->bindParam(":email", $email);
$stmt->bindParam(":password", $hashed_password);
$stmt->bindParam(":first_name", $first_name);
$stmt->bindParam(":last_name", $last_name);
$stmt->bindParam(":role", $role);

if ($stmt->execute()) {
    echo "Admin user created successfully.\n";
    echo "Email: admin@example.com\n";
    echo "Password: admin123\n";
} else {
    echo "Error creating admin user.\n";
    print_r($stmt->errorInfo());
}
?>
