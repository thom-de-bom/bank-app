<?php
// Database configuration
$DB_HOST = 'localhost';
$DB_NAME = 'geld_db';
$DB_USER = 'root'; // Change to your DB username
$DB_PASS = ''; // Change to your DB password

// Function to establish database connection
function get_db_connection() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    
    try {
        $conn = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME", $DB_USER, $DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

// Helper function to return JSON responses
function json_response($status, $message, $data = null) {
    $response = [
        'status' => $status,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Function to validate admin token (using your original admin_tokens table)
function validate_admin_token($token) {
    // Check if token is provided
    if (!$token) {
        return false;
    }
    
    // Connect to database
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }
    
    // Query to verify token in admin_tokens table
    $stmt = $conn->prepare("SELECT * FROM admin_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    
    return $stmt->rowCount() > 0;
}

// Function to validate user token (using account_tokens table)
function validate_user_token($token) {
    // Check if token is provided
    if (!$token) {
        return false;
    }
    
    // Connect to database
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }
    
    // Make sure account_tokens table exists
    $conn->exec("CREATE TABLE IF NOT EXISTS account_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL
    )");
    
    // Query to verify token in account_tokens table
    $stmt = $conn->prepare("
        SELECT at.*, a.status 
        FROM account_tokens at
        JOIN accounts a ON at.account_id = a.id
        WHERE at.token = ? AND at.expires_at > NOW() AND a.status = 'active'
    ");
    $stmt->execute([$token]);
    
    return $stmt->rowCount() > 0;
}
