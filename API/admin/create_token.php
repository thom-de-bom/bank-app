<?php
// Admin utility to create a test token (for development/testing only)
// This should be secured or disabled in production!
header('Content-Type: application/json');

// Include database configuration
require_once '../db_config.php';

// IMPORTANT: In production, this file should be protected or removed!
// For security, use basic IP restriction (localhost only)
$client_ip = $_SERVER['REMOTE_ADDR'];
if ($client_ip !== '127.0.0.1' && $client_ip !== '::1') {
    json_response('error', 'This tool can only be accessed from the local machine');
}

// Get admin username from query string
$username = isset($_GET['username']) ? $_GET['username'] : '';

if (empty($username)) {
    json_response('error', 'Username parameter is required');
}

// Connect to database
$conn = get_db_connection();
if (!$conn) {
    json_response('error', 'Database connection failed');
}

try {
    // Check if admin exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE account_number = ? AND role = 'admin'");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() == 0) {
        json_response('error', 'Admin user not found');
    }
    
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $admin_id = $admin['id'];
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    
    // Calculate expiration (24 hours from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store token in database
    $stmt = $conn->prepare("UPDATE users SET token = ?, token_expiry = ? WHERE id = ?");
    $stmt->execute([$token, $expires_at, $admin_id]);
    
    // Log token creation
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
    $details = "Created test token for {$username}, expires {$expires_at}";
    $stmt->execute([$admin_id, 'create_test_token', $details]);
    
    json_response('success', 'Test token created successfully', [
        'token' => $token,
        'expires_at' => $expires_at,
        'note' => 'This token is for testing only. Keep it secure!'
    ]);
    
} catch (PDOException $e) {
    error_log("Create token error: " . $e->getMessage());
    json_response('error', 'Database error', ['debug' => $e->getMessage()]);
}
