<?php
// Admin endpoint to edit an existing account
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
    $stmt = $conn->prepare("SELECT COUNT(*) FROM accounts WHERE account_number = ?");
    $stmt->execute([$account_number]);
    
    if ($stmt->fetchColumn() == 0) {
        json_response('error', 'Account not found');
    }
    
    // Build update query dynamically based on provided fields
    $updateFields = [];
    $params = [];
    
    // Check which fields are provided and add them to the update
    if (isset($data['first_name']) && !empty($data['first_name'])) {
        $updateFields[] = "first_name = ?";
        $params[] = trim($data['first_name']);
    }
    
    if (isset($data['last_name']) && !empty($data['last_name'])) {
        $updateFields[] = "last_name = ?";
        $params[] = trim($data['last_name']);
    }
    
    if (isset($data['balance'])) {
        $updateFields[] = "balance = ?";
        $params[] = floatval($data['balance']);
    }
    
    if (isset($data['status'])) {
        $status = strtolower(trim($data['status']));
        // Validate status
        if ($status !== 'active' && $status !== 'blocked') {
            json_response('error', 'Status must be either "active" or "blocked"');
        }
        $updateFields[] = "status = ?";
        $params[] = $status;
    }
    
    if (isset($data['pin_code']) && !empty($data['pin_code'])) {
        // Validate PIN code
        $pin_code = trim($data['pin_code']);
        if (!preg_match('/^\d{4,6}$/', $pin_code)) {
            json_response('error', 'PIN code must be 4-6 digits');
        }
        $updateFields[] = "pin_code = ?";
        $params[] = password_hash($pin_code, PASSWORD_DEFAULT);
    }
    
    // If no fields to update
    if (empty($updateFields)) {
        json_response('error', 'No fields to update');
    }
    
    // Add account_number to params for WHERE clause
    $params[] = $account_number;
    
    // Update account
    $sql = "UPDATE accounts SET " . implode(", ", $updateFields) . ", updated_at = NOW() WHERE account_number = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        // Log the account update using admin_token
        $stmt = $conn->prepare("INSERT INTO admin_logs (action, details, admin_token, created_at) VALUES (?, ?, ?, NOW())");
        $log_details = "Updated account {$account_number}";
        if (isset($data['status'])) {
            $log_details .= " (Status: {$data['status']})";
        }
        $stmt->execute(['edit_account', $log_details, $token]);
        
        json_response('success', 'Account updated successfully');
    } else {
        json_response('error', 'Failed to update account');
    }
    
} catch (PDOException $e) {
    error_log("Edit account error: " . $e->getMessage());
    json_response('error', 'Database error', ['debug' => $e->getMessage()]);
}
