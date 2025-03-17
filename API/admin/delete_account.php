<?php
// Admin endpoint to delete an account
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
    
    // Start transaction
    $conn->beginTransaction();
    
    // Check if account exists in accounts table
    $stmt = $conn->prepare("SELECT balance, first_name, last_name FROM accounts WHERE account_number = ?");
    $stmt->execute([$account_number]);
    
    if ($stmt->rowCount() == 0) {
        $conn->rollBack();
        json_response('error', 'Account not found');
    }
    
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // For security, enforce business rule: do not delete accounts with balance
    if ($account['balance'] > 0) {
        $conn->rollBack();
        json_response('error', 'Cannot delete account with non-zero balance. Please transfer funds first.');
    }
    
    // Delete any transaction records first (to maintain referential integrity)
    $stmt = $conn->prepare("DELETE FROM transactions WHERE account_number = ?");
    $stmt->execute([$account_number]);
    
    // Remove any tokens for this account
    $stmt = $conn->prepare("DELETE FROM account_tokens WHERE account_id IN (SELECT id FROM accounts WHERE account_number = ?)");
    $stmt->execute([$account_number]);
    
    // Save account data for logging before deletion
    $account_info = $account;
    
    // Delete account
    $stmt = $conn->prepare("DELETE FROM accounts WHERE account_number = ?");
    $result = $stmt->execute([$account_number]);
    
    if (!$result) {
        $conn->rollBack();
        json_response('error', 'Failed to delete account');
    }
    
    // Log the account deletion
    $stmt = $conn->prepare("INSERT INTO admin_logs (action, details, admin_token, created_at) VALUES (?, ?, ?, NOW())");
    $log_details = "Deleted account {$account_number} belonging to {$account_info['first_name']} {$account_info['last_name']}";
    $result = $stmt->execute(['delete_account', $log_details, $token]);
    
    if (!$result) {
        $conn->rollBack();
        json_response('error', 'Failed to log account deletion');
    }
    
    // Commit transaction
    $conn->commit();
    
    json_response('success', 'Account deleted successfully');
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Delete account error: " . $e->getMessage());
    json_response('error', 'Database error', ['debug' => $e->getMessage()]);
}
