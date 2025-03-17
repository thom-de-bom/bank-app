<?php
header('Content-Type: application/json');
include('../db.php');

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode(["status" => "error", "message" => "Username and password are required"]);
    exit;
}

$username = trim($data['username']);
$password = trim($data['password']);

// Admin account uses the account_number field with same structure as normal accounts
// But has role = 'admin'
try {
    $stmt = $pdo->prepare("SELECT id, account_number, first_name, pin_code FROM users WHERE account_number = ? AND role = 'admin'");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
        exit;
    }
    
    $admin = $stmt->fetch();
    
    // Check if PIN (password) is correct
    if (!password_verify($password, $admin['pin_code'])) {
        echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
        exit;
    }
    
    // Generate token and set expiry (8 hours)
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+8 hours'));
    
    // Update admin with token
    $stmt = $pdo->prepare("UPDATE users SET token = ?, token_expiry = ? WHERE id = ?");
    $stmt->execute([$token, $expiry, $admin['id']]);
    
    // Log admin login
    $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'login', ?)");
    $details = "Admin login from " . $_SERVER['REMOTE_ADDR'];
    $stmt->execute([$admin['id'], $details]);
    
    // Return success with token
    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "token" => $token,
        "username" => $username
    ]);
    
} catch (PDOException $e) {
    error_log("Admin login error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred during login"]);
}
