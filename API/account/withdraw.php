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
error_log("Withdrawal request with token: " . $token);

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

// Maximum withdrawal amount per transaction
if ($amount > 500.00) {
    echo json_encode(["status" => "error", "message" => "Withdrawal amount cannot exceed €500"]);
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
        error_log("Invalid token or account number for withdrawal. Token: $token, Account: $account_number");
        echo json_encode(["status" => "error", "message" => "Invalid token or account number"]);
        exit;
    }
    
    $account_data = $stmt->fetch();
    $account_id = $account_data['account_id'];
    
    // Start a transaction
    $pdo->beginTransaction();
    
    // Check daily withdrawal limits from transactions table
    
    // 1. Count withdrawals today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions 
                          WHERE account_number = ? 
                          AND type = 'withdrawal' 
                          AND DATE(created_at) = CURDATE()");
    $stmt->execute([$account_number]);
    $withdrawal_count = $stmt->fetchColumn();
    
    if ($withdrawal_count >= 3) {
        $pdo->rollBack();
        error_log("Daily withdrawal limit reached for account: $account_number");
        echo json_encode(["status" => "error", "message" => "Daily withdrawal limit of 3 transactions reached"]);
        exit;
    }
    
    // 2. Check total amount withdrawn today
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions 
                          WHERE account_number = ? 
                          AND type = 'withdrawal' 
                          AND DATE(created_at) = CURDATE()");
    $stmt->execute([$account_number]);
    $total_withdrawn_today = (float) $stmt->fetchColumn();
    
    if (($total_withdrawn_today + $amount) > 1500.00) {
        $pdo->rollBack();
        error_log("Daily withdrawal amount limit exceeded for account: $account_number");
        echo json_encode(["status" => "error", "message" => "Daily withdrawal limit of €1500 exceeded"]);
        exit;
    }
    
    // 3. Check if user has sufficient balance
    $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE account_number = ? FOR UPDATE");
    $stmt->execute([$account_number]);
    $balance = (float) $stmt->fetchColumn();
    
    if ($balance < $amount) {
        $pdo->rollBack();
        error_log("Insufficient funds for account: $account_number, Balance: $balance, Requested: $amount");
        echo json_encode(["status" => "error", "message" => "Insufficient funds"]);
        exit;
    }
    
    // Process withdrawal
    $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE account_number = ?");
    $stmt->execute([$amount, $account_number]);
    
    // Record transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (type, account_number, amount, created_at) 
                         VALUES ('withdrawal', ?, ?, NOW())");
    $stmt->execute([$account_number, $amount]);
    
    // Get the new balance
    $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE account_number = ?");
    $stmt->execute([$account_number]);
    $new_balance = (float) $stmt->fetchColumn();
    
    // Commit transaction
    $pdo->commit();
    
    error_log("Withdrawal successful for account: $account_number, Amount: $amount");
    
    // Return success
    echo json_encode([
        "status" => "success",
        "message" => "Withdrawal successful",
        "new_balance" => number_format($new_balance, 2, '.', '')
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Withdrawal error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred while processing the withdrawal"]);
}
