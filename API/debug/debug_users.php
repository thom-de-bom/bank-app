<?php
// For debugging only - show all users in the database
// Access this file directly in your browser to see the users

header('Content-Type: text/html');
include('db.php');

echo "<h1>Users in Database (FOR DEBUGGING ONLY)</h1>";
echo "<p>This file should be removed in production!</p>";

try {
    $stmt = $pdo->query("SELECT id, account_number, first_name, last_name, role, status, token FROM users");
    $users = $stmt->fetchAll();
    
    echo "<table border='1'>
    <tr>
        <th>ID</th>
        <th>Account Number</th>
        <th>Name</th>
        <th>Role</th>
        <th>Status</th>
        <th>Has Token</th>
    </tr>";
    
    foreach ($users as $user) {
        echo "<tr>
            <td>" . $user['id'] . "</td>
            <td>" . $user['account_number'] . "</td>
            <td>" . $user['first_name'] . " " . $user['last_name'] . "</td>
            <td>" . $user['role'] . "</td>
            <td>" . $user['status'] . "</td>
            <td>" . (!empty($user['token']) ? 'Yes' : 'No') . "</td>
        </tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>