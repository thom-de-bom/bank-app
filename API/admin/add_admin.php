<?php
// Initial setup script for creating the first admin user
// This should be secured or disabled after first use in production!
header('Content-Type: application/json');

// Include database configuration
require_once '../db_config.php';

// IMPORTANT: In production, this file should be protected or removed after initial setup!
// For security, we'll use basic IP restriction (localhost only)
$client_ip = $_SERVER['REMOTE_ADDR'];
if ($client_ip !== '127.0.0.1' && $client_ip !== '::1') {
    json_response('error', 'This setup script can only be accessed from the local machine');
}

// Get JSON input
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate input
if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
    json_response('error', 'Username, password, and email are required');
}

$username = $data['username'];
$password = $data['password'];
$email = $data['email'];

// Validate username and password length
if (strlen($username) < 4) {
    json_response('error', 'Username must be at least 4 characters');
}

if (strlen($password) < 8) {
    json_response('error', 'Password must be at least 8 characters');
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response('error', 'Invalid email format');
}

// Connect to database
$conn = get_db_connection();
if (!$conn) {
    json_response('error', 'Database connection failed');
}

try {
    // Create tables if they don't exist
    
    // Admins table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL
        )
    ");
    
    // Admin tokens table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS admin_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            FOREIGN KEY (admin_id) REFERENCES admins(id)
        )
    ");
    
    // Admin logs table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(50) NOT NULL,
            details TEXT NULL,
            admin_token VARCHAR(255) NULL,
            created_at DATETIME NOT NULL
        )
    ");
    
    // Accounts table (if not exists)
    $conn->exec("
        CREATE TABLE IF NOT EXISTS accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account_number VARCHAR(20) NOT NULL UNIQUE,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            balance DECIMAL(10,2) DEFAULT 0.00,
            status ENUM('active', 'blocked') DEFAULT 'active',
            pin_code VARCHAR(10) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL
        )
    ");
    
    // Transactions table (if not exists)
    $conn->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type ENUM('deposit', 'withdrawal', 'transfer') NOT NULL,
            account_number VARCHAR(20) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            reference_number VARCHAR(50) NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (account_number) REFERENCES accounts(account_number)
        )
    ");
    
    // Check if admin already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetchColumn() > 0) {
        json_response('error', 'Admin user already exists');
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert admin user
    $stmt = $conn->prepare("INSERT INTO admins (username, password_hash, email, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$username, $password_hash, $email]);
    
    // Log admin creation
    $stmt = $conn->prepare("INSERT INTO admin_logs (action, details, created_at) VALUES (?, ?, NOW())");
    $stmt->execute(['add_admin', "Created admin user {$username}"]);
    
    json_response('success', 'Admin user created successfully', [
        'username' => $username,
        'note' => 'For security, consider removing or securing this setup script.'
    ]);
    
} catch (PDOException $e) {
    error_log("Add admin error: " . $e->getMessage());
    json_response('error', 'Database error', ['debug' => $e->getMessage()]);
}