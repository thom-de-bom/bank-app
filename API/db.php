<?php
// Database connection configuration
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "geld_db";

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $db_username, $db_password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Initialize account_tokens table if needed
    // This is the only table we're adding to the existing database
    $pdo->exec("CREATE TABLE IF NOT EXISTS account_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL
    )");
    
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}
