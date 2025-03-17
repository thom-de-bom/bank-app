<?php
// Admin endpoint to block an account
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
    // Get admin ID from token for logging
    $stmt = $conn->prepare("SELECT admin_id FROM admin_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $admin_id = $result ? $result['admin_id'] : null;
    
    // Check if account exists in accounts table
    $stmt = $conn->prepare("SELECT status FROM accounts WHERE account_number = ?");
    $stmt->execute([$account_number]);
    
    if ($stmt->rowCount() == 0) {
        json_response('error', 'Account not found');
    }
    
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if account is already blocked
    if ($account['status'] === 'blocked') {
        json_response('error', 'Account is already blocked');
    }
    
    // Block the account
    $stmt = $conn->prepare("UPDATE accounts SET status = 'blocked', updated_at = NOW() WHERE account_number = ?");
    $result = $stmt->execute([$account_number]);
    
    if ($result) {
        // Invalidate any existing tokens for the blocked account in account_tokens table
        $stmt = $conn->prepare("DELETE FROM account_tokens WHERE account_id IN (SELECT id FROM accounts WHERE account_number = ?)");
        $stmt->execute([$account_number]);
        
        // Log the account blocking
        $stmt = $conn->prepare("INSERT INTO admin_logs (action, details, admin_token, created_at) VALUES (?, ?, ?, NOW())");
        $log_details = "Blocked account {$account_number}";
        $stmt->execute(['block_account', $log_details, $token]);
        
        json_response('success', 'Account blocked successfully');
    } else {
        json_response('error', 'Failed to block account');
    }
    
} catch (PDOException $e) {
    error_log("Block account error: " . $e->getMessage());
    json_response('error', 'Database error', ['debug' => $e->getMessage()]);
}
