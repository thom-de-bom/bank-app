<?php
// getinfo.php

header("Content-Type: application/json");

include("../db.php");

// Maak verbinding met de database
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Controleer de verbinding
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// Haal de Authorization header op
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(["status" => "error", "message" => "Missing Authorization header"]);
    exit;
}

$token = $conn->real_escape_string($headers['Authorization']);

// Log ontvangen token voor debugging (verwijder dit in productie)
file_put_contents('getinfo_debug.log', "Received token: $token\n", FILE_APPEND);

// Valideer de token door deze te vergelijken met de tokens in de users tabel
$sql = "SELECT account_number, balance, first_name FROM users WHERE token='$token' AND token_expiry > UNIX_TIMESTAMP() AND status='active'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $account_number = $user['account_number'];
    $balance = number_format((float)$user['balance'], 2, '.', ''); // Zorg ervoor dat het formaat correct is

    // Haal recente transacties op (de laatste 10 deposits en withdrawals)
    $sql_deposits = "SELECT amount, deposit_time AS time, 'deposit' AS type FROM deposits WHERE account_number='$account_number' ORDER BY deposit_time DESC LIMIT 5";
    $sql_withdrawals = "SELECT amount, withdrawal_time AS time, 'withdrawal' AS type FROM withdrawals WHERE account_number='$account_number' ORDER BY withdrawal_time DESC LIMIT 5";

    $result_deposits = $conn->query($sql_deposits);
    $result_withdrawals = $conn->query($sql_withdrawals);

    $transactions = [];

    // Voeg deposits toe
    if ($result_deposits->num_rows > 0) {
        while ($deposit = $result_deposits->fetch_assoc()) {
            $deposit['amount'] = number_format((float)$deposit['amount'], 2, '.', '');
            $transactions[] = $deposit;
        }
    }

    // Voeg withdrawals toe
    if ($result_withdrawals->num_rows > 0) {
        while ($withdrawal = $result_withdrawals->fetch_assoc()) {
            $withdrawal['amount'] = number_format((float)$withdrawal['amount'], 2, '.', '');
            $transactions[] = $withdrawal;
        }
    }

    // Sorteer transacties op tijd, meest recent eerst
    usort($transactions, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });

    // Beperk het aantal transacties tot de laatste 10
    $recent_transactions = array_slice($transactions, 0, 10);

    // Bereid de response voor
    $response = [
        "status" => "success",
        "balance" => $balance,
        "recent_transactions" => $recent_transactions
    ];

    // Log succesvolle token validatie (verwijder dit in productie)
    file_put_contents('getinfo_debug.log', "Valid token for account_number: " . $user['account_number'] . "\n", FILE_APPEND);

    echo json_encode($response);
} else {
    // Log mislukte token validatie (verwijder dit in productie)
    file_put_contents('getinfo_debug.log', "Invalid or expired token: $token\n", FILE_APPEND);

    echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
}

$conn->close();
?>
