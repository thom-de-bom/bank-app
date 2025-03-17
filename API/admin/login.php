<?php
// Admin login endpoint
header('Content-Type: application/json');

// Include database configuration (using your original version)
require_once '../db_config.php';

// Get JSON input
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate input
if (!isset($data['username']) || !isset($data['password'])) {
    json_response('error', 'Username and password are required');
}

$username = $data['username'];
$password = $data['password'];

// Connect to database
$conn = get_db_connection();
if (!$conn) {
    json_response('error', 'Database connection failed');
}

try {
    // Query admin user
    $stmt = $conn->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() === 0) {
        // For security, don't disclose that the username doesn't exist
        json_response('error', 'Invalid username or password');
    }
    
    // Verify password
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!password_verify($password, $admin['password_hash'])) {
        json_response('error', 'Invalid username or password');
    }
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $admin_id = $admin['id'];
    
    // Store token in database (expires in 8 hours)
    $expires_at = date('Y-m-d H:i:s', strtotime('+8 hours'));
    
    $stmt = $conn->prepare("INSERT INTO admin_tokens (admin_id, token, created_at, expires_at) 
                          VALUES (?, ?, NOW(), ?)");
    $stmt->execute([$admin_id, $token, $expires_at]);
    
    // Return success with token
    json_response('success', 'Login successful', ['token' => $token]);
    
} catch (PDOException $e) {
    error_log("Admin login error: " . $e->getMessage());
    json_response('error', 'Database error', ['debug' => $e->getMessage()]);
}
