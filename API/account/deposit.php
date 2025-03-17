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
error_log("Deposit request with token: " . $token);

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (!isset($data['amount']) || !isset($data['account_number'])) {
    echo json_encode(["status" => "error", "message" => "Amount and account number are required"]);
    exit;
}

$amount = (float) $data['amount'];
$account_number = trim($data['account_number']);

// Validate amount
if ($amount <= 0) {
    echo json_encode(["status" => "error", "message" => "Amount must be greater than zero"]);
    exit;
}

try {
    // Create account_tokens table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS account_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL
    )");

    // First verify the token
    $stmt = $pdo->prepare("SELECT at.account_id
                          FROM account_tokens at
                          JOIN accounts a ON at.account_id = a.id
                          WHERE at.token = ? AND at.expires_at > NOW()
                          AND a.account_number = ? AND a.status = 'active'");
    $stmt->execute([$token, $account_number]);
    
    if ($stmt->rowCount() == 0) {
        error_log("Invalid token or account number for deposit. Token: $token, Account: $account_number");
        echo json_encode(["status" => "error", "message" => "Invalid token or account number"]);
        exit;
    }
    
    $account_data = $stmt->fetch();
    $account_id = $account_data['account_id'];
    
    // Start a transaction
    $pdo->beginTransaction();
    
    // Update balance in accounts table
    $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_number = ?");
    $stmt->execute([$amount, $account_number]);
    
    // Record transaction in transactions table
    $stmt = $pdo->prepare("INSERT INTO transactions (type, account_number, amount, created_at) 
                         VALUES ('deposit', ?, ?, NOW())");
    $stmt->execute([$account_number, $amount]);
    
    // Get the new balance
    $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE account_number = ?");
    $stmt->execute([$account_number]);
    $balance = $stmt->fetch()['balance'];
    
    // Commit transaction
    $pdo->commit();
    
    error_log("Deposit successful for account: $account_number, Amount: $amount");
    
    // Return success
    echo json_encode([
        "status" => "success",
        "message" => "Deposit successful",
        "new_balance" => number_format((float)$balance, 2, '.', '')
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Deposit error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred while processing the deposit"]);
}
