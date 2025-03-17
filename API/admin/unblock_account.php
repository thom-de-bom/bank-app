<?php
// Admin endpoint to unblock an account
header('Content-Type: application/json');

// Include database configuration
require_once '../db_config.php';

// Check for authorization token
$headers = getallheaders();
$token = isset($headers['Authorization']) ? $headers['Authorization'] : null;

if (!$token) {
    json_response('error', 'Authorization token required');
}

// Validate token
if (!validate_admin_token($token)) {
    json_response('error', 'Invalid or expired token');
}

// Get JSON input
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate input
if (!isset($data['account_number'])) {
    json_response('error', 'Account number is required');
}

// Extract account number
$account_number = trim($data['account_number']);

// Connect to database
$conn = get_db_connection();
if (!$conn) {
    json_response('error', 'Database connection failed');
}

try {
    // Get admin ID for logging
    $stmt = $conn->prepare("SELECT id FROM users WHERE token = ? AND role = 'admin'");
    $stmt->execute([$token]);
    $admin_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    
    // Check if account exists
    $stmt = $conn->prepare("SELECT status FROM users WHERE account_number = ? AND role = 'user'");
    $stmt->execute([$account_number]);
    
    if ($stmt->rowCount() == 0) {
        json_response('error', 'Account not found');
    }
    
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if account is already active
    if ($account['status'] === 'active') {
        json_response('error', 'Account is already active');
    }
    
    // Unblock the account
    $stmt = $conn->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE account_number = ? AND role = 'user'");
    $result = $stmt->execute([$account_number]);
    
    if ($result) {
        // Log the account unblocking
        $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $log_details = "Unblocked account {$account_number}";
        $stmt->execute([$admin_id, 'unblock_account', $log_details]);
        
        json_response('success', 'Account unblocked successfully');
    } else {
        json_response('error', 'Failed to unblock account');
    }
    
} catch (PDOException $e) {
    error_log("Unblock account error: " . $e->getMessage());
    json_response('error', 'Database error', ['debug' => $e->getMessage()]);
}
