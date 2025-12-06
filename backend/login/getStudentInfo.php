<?php
// --- DEBUGGING MODE: ON ---
// We turn these ON so we can see PHP errors (like missing semicolons)
error_reporting(E_ALL);
ini_set('display_errors', 1); 

header('Content-Type: application/json');

session_start();

// Database credentials
$host = 'localhost';
$dbname = 'adsDB'; 
$username = 'root';
$password = '';

// 2. LOGIC CHECK: URL ID first, then Session
$studentID = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $studentID = $_GET['id'];
} elseif (isset($_SESSION['student_id'])) {
    $studentID = $_SESSION['student_id'];
}

if (!$studentID) {
    echo json_encode([
        'status' => 'error', 
        'loggedIn' => false,
        'message' => 'No student ID provided.'
    ]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. QUERY with JOINS
    // Backticks added to handle reserved keywords like `status`
    $sql = "SELECT 
                students.* 
                , credentials.student_id as cred_id
                , programs.program_name
                , `status`.status_name
            FROM `students` 
            INNER JOIN `credentials` 
                ON `students`.student_id = `credentials`.student_id 
            LEFT JOIN `programs` 
                ON `students`.program_id = `programs`.program_id
            LEFT JOIN `status` 
                ON `students`.status_id = `status`.status_id
            WHERE `students`.student_id = :student_id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":student_id", $studentID);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode(array_merge($result, [
            'status' => 'success',
            'loggedIn' => isset($_SESSION['student_id'])
        ]));
    } else {
        echo json_encode([
            'status' => 'error', 
            'loggedIn' => isset($_SESSION['student_id']), 
            'message' => 'Student record not found in database.'
        ]);
    }

} catch(PDOException $e) {
    // --- DEBUGGING CHANGE ---
    // We removed http_response_code(500) so the browser accepts the JSON 
    // and you can read the actual error message in the Console.
    echo json_encode([
        'status' => 'error', 
        'loggedIn' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>