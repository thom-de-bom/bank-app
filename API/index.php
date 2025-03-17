<?php
// Main entry point for the API
header('Content-Type: application/json');

// Include database configuration
require_once 'db_config.php';

// Basic API info
$api_info = [
    'name' => 'Bank API',
    'version' => '1.0.0',
    'status' => 'online',
    'endpoints' => [
        'auth/user_login.php' => 'User login endpoint',
        'auth/login.php' => 'Admin login endpoint',
        'account/getinfo.php' => 'Get account information',
        'account/deposit.php' => 'Make a deposit',
        'account/withdraw.php' => 'Make a withdrawal',
        'admin/dashboard.php' => 'Get user accounts and transactions',
        'admin/add_account.php' => 'Add a new account',
        'admin/edit_account.php' => 'Edit an existing account',
        'admin/block_account.php' => 'Block an account',
        'admin/delete_account.php' => 'Delete an account',
        'admin/search_accounts.php' => 'Search for accounts',
    ],
    'note' => 'All secure endpoints require an Authorization header with a valid token'
];

// Return API info
echo json_encode([
    'status' => 'success',
    'message' => 'API is online',
    'api' => $api_info
]);
