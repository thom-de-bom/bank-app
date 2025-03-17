<?php
// For debugging only - logs API requests
// Do not use in production!

$logFile = 'api_debug.log';

// Start logging
file_put_contents($logFile, "=== DEBUG LOG - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

// Log request method and URL
file_put_contents($logFile, "REQUEST: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND);

// Log headers
file_put_contents($logFile, "HEADERS: \n", FILE_APPEND);
foreach (getallheaders() as $name => $value) {
    file_put_contents($logFile, "$name: $value\n", FILE_APPEND);
}

// Log request body
$requestBody = file_get_contents("php://input");
file_put_contents($logFile, "\nREQUEST BODY:\n" . $requestBody . "\n", FILE_APPEND);

// Send a response
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Request logged. Check api_debug.log on the server.'
]);
?>