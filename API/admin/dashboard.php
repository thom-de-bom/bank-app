<?php
// Admin dashboard endpoint - returns user accounts and transactions
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

// Connect to database
$conn = get_db_connection();
if (!$conn) {
    json_response('error', 'Database connection failed');
}

try {
    // Get admin ID from admin_tokens table for logging
    $stmt = $conn->prepare("SELECT admin_id FROM admin_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $admin_id = $result ? $result['admin_id'] : null;
    
    // Fetch user accounts from accounts table (limited to 100 most recent for performance)
    $stmt = $conn->prepare("SELECT account_number, first_name, last_name, balance, status, pin_code, created_at 
                          FROM accounts 
                          ORDER BY id DESC 
                          LIMIT 100");
    $stmt->execute();
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For security, mask PIN codes in the response
    foreach ($accounts as &$account) {
        $account['pin_code'] = '****'; // Mask PIN code
        $account['balance'] = number_format((float)$account['balance'], 2, '.', ''); // Format balance
    }
    
    // Fetch recent transactions from transactions table
    $stmt = $conn->prepare("SELECT type, account_number, amount, created_at as time
                          FROM transactions
                          ORDER BY created_at DESC
                          LIMIT 100");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format amounts for consistency
    foreach ($transactions as &$transaction) {
        $transaction['amount'] = number_format((float)$transaction['amount'], 2, '.', '');
    }
    
    // Log admin dashboard access - use admin_token instead of admin_id if needed
    $stmt = $conn->prepare("INSERT INTO admin_logs (action, details, admin_token, created_at) VALUES (?, ?, ?, NOW())");
    $details = "Admin accessed dashboard";
    $stmt->execute(['view_dashboard', $details, $token]);
    
    // Return success with data
    json_response('success', 'Data retrieved successfully', [
        'users' => $accounts,
        'transactions' => $transactions
    ]);
    
} catch (PDOException $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    json_response('error', 'Database error', ['debug' => $e->getMessage()]);
}
