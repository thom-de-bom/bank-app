<?php
// For testing only - creates a test user account
// Access this file directly in your browser to create a test account

header('Content-Type: text/html');
include('db.php');

echo "<h1>Create Test User Account</h1>";
echo "<p>This file should be removed in production!</p>";

try {
    // Check if the test account already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE account_number = ?");
    $stmt->execute(['1001']);
    $exists = $stmt->fetchColumn();
    
    if ($exists) {
        echo "<p>Test account already exists (account_number: 1001)</p>";
    } else {
        // Create a test user account
        $hashed_pin = password_hash('1234', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (account_number, first_name, last_name, pin_code, balance, status, role, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['1001', 'John', 'Doe', $hashed_pin, 1000.00, 'active', 'user']);
        
        echo "<p>Test account created successfully:</p>";
        echo "<ul>
            <li>Account Number: 1001</li>
            <li>PIN Code: 1234</li>
            <li>Name: John Doe</li>
            <li>Initial Balance: €1000.00</li>
        </ul>";
    }
    
    // Also check the admin account
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE account_number = ? AND role = 'admin'");
    $stmt->execute(['ADMIN001']);
    $adminExists = $stmt->fetchColumn();
    
    if (!$adminExists) {
        echo "<p>Admin account does not exist. Creating it now...</p>";
        $admin_hashed_pin = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (account_number, first_name, last_name, pin_code, role, created_at) 
                              VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['ADMIN001', 'Admin', 'User', $admin_hashed_pin, 'admin']);
        echo "<p>Admin account created successfully (ADMIN001/admin123)</p>";
    } else {
        echo "<p>Admin account already exists (ADMIN001)</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>