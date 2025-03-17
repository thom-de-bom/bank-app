<?php
// Admin endpoint to validate a token
header('Content-Type: application/json');

// Include database configuration
require_once '../db_config.php';

// Check for authorization token
$headers = getallheaders();
$token = isset($headers['Authorization']) ? $headers['Authorization'] : null;

if (!$token) {
    json_response('error', 'Authorization token required');
}

// Validate token
if (validate_admin_token($token)) {
    // If token is valid, return admin details
    $conn = get_db_connection();
    if (!$conn) {
        json_response('error', 'Database connection failed');
    }
    
    try {
        // Get admin info from token
        $stmt = $conn->prepare("
            SELECT a.id, a.username, a.email, at.created_at, at.expires_at 
            FROM admin_tokens at
            JOIN admins a ON at.admin_id = a.id
            WHERE at.token = ? AND at.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() > 0) {
            $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Remove sensitive data
            unset($admin_data['id']);
            
            json_response('success', 'Token is valid', [
                'admin' => $admin_data,
                'valid_until' => $admin_data['expires_at']
            ]);
        } else {
            json_response('error', 'Invalid or expired token');
        }
    } catch (PDOException $e) {
        error_log("Token validation error: " . $e->getMessage());
        json_response('error', 'Database error', ['debug' => $e->getMessage()]);
    }
} else {
    json_response('error', 'Invalid or expired token');
}