<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
session_start();

// Database credentials
$host = 'localhost';
$dbname = 'adsDB'; 
$username = 'root';
$password = '';

// 2. LOGIC CHECK: URL ID first, then Session
// This allows you to view grades by ID in URL (?id=...) or defaults to logged-in user
$studentID = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $studentID = $_GET['id'];
} elseif (isset($_SESSION['student_id'])) {
    $studentID = $_SESSION['student_id'];
}

if (!$studentID) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'No student ID provided.'
    ]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. QUERY
    // Join subjects_taken with subjects to get the title
    // Added backticks ` ` for safety
    $sql = "SELECT st.sub_code, s.title, st.grade
            FROM `subjects_taken` st
            JOIN `subjects` s ON st.sub_code = s.sub_code
            WHERE st.student_id = :studentid";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":studentid", $studentID);
    $stmt->execute();
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. PROCESS REMARKS
    // We iterate through the results to add the 'remarks' logic
    foreach ($result as &$row) {
        // Logic: Grade <= 3.00 is PASSED, otherwise FAILED
        $row['remarks'] = ($row['grade'] <= 3.00) ? "PASSED" : "FAILED";
    }
    
    // Return the data
    echo json_encode([
        'status' => 'success',
        'data' => $result
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>