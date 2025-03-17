<?php
// Admin endpoint to search for accounts
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

// Get search parameters from query string
$account_number = isset($_GET['account_number']) ? $_GET['account_number'] : '';
$last_name = isset($_GET['last_name']) ? $_GET['last_name'] : '';
$first_name = isset($_GET['first_name']) ? $_GET['first_name'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Validate that at least one search parameter is provided
if (empty($account_number) && empty($last_name) && empty($first_name) && empty($status)) {
    json_response('error', 'At least one search parameter (account_number, first_name, last_name, or status) is required');
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
    
    // Build search query based on provided parameters
    $sql = "SELECT account_number, first_name, last_name, balance, status, created_at 
            FROM accounts WHERE 1=1";
    $params = [];
    
    if (!empty($account_number)) {
        $sql .= " AND account_number LIKE ?";
        $params[] = "%{$account_number}%";
    }
    
    if (!empty($first_name)) {
        $sql .= " AND first_name LIKE ?";
        $params[] = "%{$first_name}%";
    }
    
    if (!empty($last_name)) {
        $sql .= " AND last_name LIKE ?";
        $params[] = "%{$last_name}%";
    }
    
    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    // Limit results for performance
    $sql .= " ORDER BY account_number LIMIT 50";
    
    // Execute query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format balance for each account
    foreach ($accounts as &$account) {
        $account['balance'] = number_format((float)$account['balance'], 2, '.', '');
    }
    
    // Log the search
    $stmt = $conn->prepare("INSERT INTO admin_logs (action, details, admin_token, created_at) VALUES (?, ?, ?, NOW())");
    $search_details = "Searched accounts with criteria: " . 
                     (!empty($account_number) ? "account_number={$account_number} " : "") . 
                     (!empty($first_name) ? "first_name={$first_name} " : "") . 
                     (!empty($last_name) ? "last_name={$last_name} " : "") . 
                     (!empty($status) ? "status={$status}" : "");
    $stmt->execute(['search_accounts', $search_details, $token]);
    
    // Return search results
    json_response('success', 'Search completed successfully', ['accounts' => $accounts]);
    
} catch (PDOException $e) {
    error_log("Search accounts error: " . $e->getMessage());
    json_response('error', 'Database error', ['debug' => $e->getMessage()]);
}
