<?php
// Debugging tool for login issues
// DO NOT USE IN PRODUCTION - THIS IS FOR TROUBLESHOOTING ONLY!

header('Content-Type: text/html');
include('db.php');

echo "<h1>Login Debugging Tool</h1>";
echo "<p style='color:red;'><strong>WARNING: FOR DEVELOPMENT USE ONLY - REMOVE BEFORE DEPLOYMENT!</strong></p>";

echo "<h2>All Accounts</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Account Number</th><th>Name</th><th>PIN</th><th>PIN Length</th><th>Status</th></tr>";

try {
    $stmt = $pdo->query("SELECT id, account_number, first_name, last_name, pin_code, status FROM accounts");
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['account_number'] . "</td>";
        echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
        echo "<td>" . (strlen($row['pin_code']) > 10 ? substr($row['pin_code'], 0, 10) . "..." : $row['pin_code']) . "</td>";
        echo "<td>" . strlen($row['pin_code']) . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "</table>";

echo "<h2>Account Tokens</h2>";
echo "<table border='1'>";
echo "<tr><th>Token ID</th><th>Account ID</th><th>Token</th><th>Created</th><th>Expires</th></tr>";

try {
    // Check if account_tokens table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'account_tokens'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT id, account_id, token, created_at, expires_at FROM account_tokens ORDER BY created_at DESC LIMIT 10");
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['account_id'] . "</td>";
            echo "<td>" . substr($row['token'], 0, 15) . "...</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "<td>" . $row['expires_at'] . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5'>account_tokens table doesn't exist yet</td></tr>";
    }
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "</table>";

echo "<h2>Fix Plain Text PINs</h2>";
echo "<p>This tool will let you reset PINs to plain text for testing, or hash them for security.</p>";

// Process PIN update if submitted
if (isset($_POST['update_pin'])) {
    $account_id = $_POST['account_id'];
    $new_pin = $_POST['new_pin'];
    $hash_pin = isset($_POST['hash_pin']) && $_POST['hash_pin'] == '1';
    
    try {
        if ($hash_pin) {
            $pin_to_store = password_hash($new_pin, PASSWORD_DEFAULT);
        } else {
            $pin_to_store = $new_pin;
        }
        
        $stmt = $pdo->prepare("UPDATE accounts SET pin_code = ? WHERE id = ?");
        $result = $stmt->execute([$pin_to_store, $account_id]);
        
        if ($result) {
            echo "<p style='color:green;'>PIN updated successfully for account ID {$account_id}.</p>";
        } else {
            echo "<p style='color:red;'>Failed to update PIN.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
}

// Form for updating PINs
echo "<form method='post'>";
echo "<label>Account ID: <input type='number' name='account_id' required></label><br>";
echo "<label>New PIN: <input type='text' name='new_pin' required></label><br>";
echo "<label><input type='checkbox' name='hash_pin' value='1'> Hash PIN (for security)</label><br>";
echo "<input type='submit' name='update_pin' value='Update PIN'>";
echo "</form>";

echo "<h2>PHP and Environment Info</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Password Hash Functions Available: " . (function_exists('password_hash') ? 'Yes' : 'No') . "</p>";
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";
?>