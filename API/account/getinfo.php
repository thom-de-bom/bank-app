<?php
header('Content-Type: application/json');
include('../db.php');

// Check for Authorization header
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(["status" => "error", "message" => "Authorization token required"]);
    exit;
}

$token = $headers['Authorization'];
error_log("Getinfo request with token: " . $token);

try {
    // First verify the token in the account_tokens table
    $pdo->exec("CREATE TABLE IF NOT EXISTS account_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL
    )");
    
    $stmt = $pdo->prepare("SELECT account_id FROM account_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() == 0) {
        error_log("Token not found or expired: " . $token);
        echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
        exit;
    }
    
    $token_data = $stmt->fetch();
    $account_id = $token_data['account_id'];
    
    // Get account information
    $stmt = $pdo->prepare("SELECT account_number, first_name, last_name, balance, status 
                          FROM accounts 
                          WHERE id = ? AND status = 'active'");
    $stmt->execute([$account_id]);
    
    if ($stmt->rowCount() == 0) {
        error_log("Account not found or not active for token: " . $token);
        echo json_encode(["status" => "error", "message" => "Account not found or inactive"]);
        exit;
    }
    
    $account = $stmt->fetch();
    $account_number = $account['account_number'];
    $balance = number_format((float)$account['balance'], 2, '.', '');
    
    error_log("Account found: " . $account_number);
    
    // Get transactions from the transactions table
    $stmt = $pdo->prepare("SELECT type, amount, created_at AS time
                          FROM transactions 
                          WHERE account_number = ? 
                          ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$account_number]);
    $transactions = $stmt->fetchAll();
    
    // Format amounts for consistency
    foreach ($transactions as &$transaction) {
        $transaction['amount'] = number_format((float)$transaction['amount'], 2, '.', '');
    }
    
    error_log("Found " . count($transactions) . " transactions");
    
    // Prepare response
    echo json_encode([
        "status" => "success",
        "balance" => $balance,
        "first_name" => $account['first_name'],
        "last_name" => $account['last_name'],
        "recent_transactions" => $transactions
    ]);
    
} catch (PDOException $e) {
    error_log("Get account info error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred while retrieving account information"]);
}
