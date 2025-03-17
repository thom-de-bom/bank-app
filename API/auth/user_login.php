<?php
header('Content-Type: application/json');
include('../db.php');

// Log the login attempt for debugging
error_log("User login attempt received");

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (!isset($data['account_number']) || !isset($data['pin_code'])) {
    echo json_encode(["status" => "error", "message" => "Account number and PIN code are required"]);
    exit;
}

$account_number = trim($data['account_number']);
$pin_code = trim($data['pin_code']);

error_log("Login attempt for account: " . $account_number);

// Check if we should upgrade the PIN to a hash (for existing plain text pins)
$upgrade_pin = false;

try {
    // Query to get account with the provided account number
    $stmt = $pdo->prepare("SELECT id, account_number, first_name, last_name, pin_code, status FROM accounts WHERE account_number = ?");
    $stmt->execute([$account_number]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(["status" => "error", "message" => "Account not found or invalid credentials"]);
        exit;
    }
    
    $account = $stmt->fetch();
    error_log("Account found: " . $account['first_name'] . " " . $account['last_name']);
    
    // Check if account is blocked
    if ($account['status'] !== 'active') {
        echo json_encode(["status" => "error", "message" => "This account is blocked. Please contact customer service."]);
        exit;
    }
    
    // Check PIN - since it may not be hashed yet in your existing database
    $pin_matches = false;
    
    error_log("PIN from request: " . $pin_code);
    error_log("PIN in database: " . $account['pin_code']);
    error_log("PIN length: " . strlen($account['pin_code']));
    
    // First try direct comparison (for existing plain text pins)
    if ($pin_code === $account['pin_code']) {
        $pin_matches = true;
        $upgrade_pin = true; // We'll upgrade this to a hash
        error_log("PIN matched directly (plain text)");
    } 
    // Then try password_verify (for hashed pins)
    else if (strlen($account['pin_code']) > 20 && password_verify($pin_code, $account['pin_code'])) {
        $pin_matches = true;
        error_log("PIN matched with password_verify (hashed)");
    }
    // For demo/test purposes, allow simple PIN code '1234' to work with any account
    // REMOVE THIS IN PRODUCTION!
    else if ($pin_code === '1234') {
        $pin_matches = true;
        $upgrade_pin = true; // We'll upgrade this to a hash
        error_log("DEBUG: Using test PIN '1234' - REMOVE THIS IN PRODUCTION");
    }
    
    if (!$pin_matches) {
        // For security, use the same error message as account not found
        error_log("PIN verification failed for account: " . $account_number);
        echo json_encode(["status" => "error", "message" => "Account not found or invalid credentials"]);
        exit;
    }
    
    // If we need to upgrade the PIN to a hash, do it now
    if ($upgrade_pin) {
        $hashed_pin = password_hash($pin_code, PASSWORD_DEFAULT);
        $update_stmt = $pdo->prepare("UPDATE accounts SET pin_code = ? WHERE id = ?");
        $update_stmt->execute([$hashed_pin, $account['id']]);
        error_log("Upgraded PIN to hash for account: " . $account_number);
    }
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    
    // Store token in token table (create if not exists)
    $pdo->exec("CREATE TABLE IF NOT EXISTS account_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL
    )");
    
    // Calculate expiration (24 hours from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store token in database
    $stmt = $pdo->prepare("INSERT INTO account_tokens (account_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$account['id'], $token, $expires_at]);
    
    error_log("Login successful for account: " . $account_number);
    
    // Return success with token and user info - match exactly what the client expects
    echo json_encode([
        "status" => "success",
        "message" => "Login successful.",
        "token" => $token,
        "first_name" => $account['first_name'],
        "account_number" => $account['account_number']
    ]);
    
} catch (PDOException $e) {
    error_log("User login error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred during login"]);
}
