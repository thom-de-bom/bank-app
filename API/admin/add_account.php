<?php
// Admin endpoint to add a new account
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
if (!isset($data['account_number']) || !isset($data['first_name']) || !isset($data['last_name']) || !isset($data['pin_code'])) {
    json_response('error', 'Account number, first name, last name, and PIN code are required');
}

// Extract and validate data
$account_number = trim($data['account_number']);
$first_name = trim($data['first_name']);
$last_name = trim($data['last_name']);
$balance = isset($data['balance']) ? floatval($data['balance']) : 0.00;
$status = isset($data['status']) ? strtolower($data['status']) : 'active';
$pin_code = trim($data['pin_code']);

// Basic validation
if (empty($account_number) || empty($first_name) || empty($last_name) || empty($pin_code)) {
    json_response('error', 'Account number, first name, last name, and PIN code cannot be empty');
}

// Validate PIN code (numeric, 4-6 digits)
if (!preg_match('/^\d{4,6}$/', $pin_code)) {
    json_response('error', 'PIN code must be 4-6 digits');
}

// Validate status (must be 'active' or 'blocked')
if ($status !== 'active' && $status !== 'blocked') {
    json_response('error', 'Status must be either "active" or "blocked"');
}

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
    
    // Check if account number already exists in accounts table
    $stmt = $conn->prepare("SELECT COUNT(*) FROM accounts WHERE account_number = ?");
    $stmt->execute([$account_number]);
    
    if ($stmt->fetchColumn() > 0) {
        json_response('error', 'Account number already exists');
    }
    
    // Hash PIN code for security
    $hashed_pin = password_hash($pin_code, PASSWORD_DEFAULT);
    
    // Insert new account into accounts table
    $stmt = $conn->prepare("INSERT INTO accounts (account_number, first_name, last_name, pin_code, balance, status, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([$account_number, $first_name, $last_name, $hashed_pin, $balance, $status]);
    
    if ($result) {
        // Log the account creation using admin_token instead of admin_id
        $stmt = $conn->prepare("INSERT INTO admin_logs (action, details, admin_token, created_at) VALUES (?, ?, ?, NOW())");
        $log_details = "Created account {$account_number} for {$first_name} {$last_name}";
        $stmt->execute(['add_account', $log_details, $token]);
        
        json_response('success', 'Account created successfully');
    } else {
        json_response('error', 'Failed to create account');
    }
    
} catch (PDOException $e) {
    error_log("Add account error: " . $e->getMessage());
    json_response('error', 'Database error', ['debug' => $e->getMessage()]);
}
