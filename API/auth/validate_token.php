<?php
// Token validation endpoint 
header('Content-Type: application/json');
include('../db.php');

// Check for Authorization header
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(["status" => "error", "message" => "Authorization token required"]);
    exit;
}

$token = $headers['Authorization'];

try {
    // Create account_tokens table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS account_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL
    )");
    
    // Query to get account info using token
    $stmt = $pdo->prepare("
        SELECT a.account_number, a.first_name, a.last_name, a.status, t.expires_at
        FROM account_tokens t
        JOIN accounts a ON t.account_id = a.id
        WHERE t.token = ? AND t.expires_at > NOW() AND a.status = 'active'
    ");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
        exit;
    }
    
    $account = $stmt->fetch();
    
    // Return account info
    echo json_encode([
        "status" => "success",
        "message" => "Token is valid",
        "account_number" => $account['account_number'],
        "first_name" => $account['first_name'],
        "last_name" => $account['last_name'],
        "expires_at" => $account['expires_at']
    ]);
    
} catch (PDOException $e) {
    error_log("Token validation error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred during token validation"]);
}
?>