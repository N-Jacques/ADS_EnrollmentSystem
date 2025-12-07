<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "adsDB"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => "Database connection failed: " . $conn->connect_error]));
}

function execute_query($conn, $sql, $types = "", $params = []) {
    if ($conn->connect_error) throw new Exception("Database connection lost.");
    $stmt = $conn->prepare($sql);
    if ($stmt === false) throw new Exception("SQL Error: " . $conn->error);
    if ($types && $params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt;
}
?>