<?php
header('Content-Type: application/json');
session_start(); // start session to get logged-in student

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

// Database Connection Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "adsDB";

// Use the student ID from the session
$student_id_to_fetch = $_SESSION['student_id'];

$student_id = '';
$student_name = '';
$program_name = '';
$yrlevel = '';
$status_name = '';
$enlistment_id = null;
$subjects = [];

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

// --- A. FETCH STUDENT INFO ---
$sql_student = "SELECT s.student_id, s.firstname, s.middlename, s.lastname,
                       s.yrlevel, p.program_name, st.status_name
                FROM students s
                JOIN programs p ON s.program_id = p.program_id
                JOIN status st ON s.status_id = st.status_id
                WHERE s.student_id = ?";

$stmt_student = $conn->prepare($sql_student);
$stmt_student->bind_param("s", $student_id_to_fetch);
$stmt_student->execute();
$result_student = $stmt_student->get_result();

if ($row = $result_student->fetch_assoc()) {
    $student_id = $row['student_id'];
    $student_name = trim($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']);
    $program_name = $row['program_name'];
    $yrlevel = $row['yrlevel'];
    $status_name = $row['status_name'];
}
$stmt_student->close();

// --- B. FETCH ENLISTMENT ID ---
$sql_enlistment = "
    SELECT enlistment_id 
    FROM enlistment
    WHERE student_id = ?
    ORDER BY date_created DESC
    LIMIT 1
";

$stmt_enlistment = $conn->prepare($sql_enlistment);
$stmt_enlistment->bind_param("s", $student_id_to_fetch);
$stmt_enlistment->execute();
$result_enlistment = $stmt_enlistment->get_result();

if ($result_enlistment && $result_enlistment->num_rows > 0) {
    $enlistment_id = $result_enlistment->fetch_assoc()['enlistment_id'];
}
$stmt_enlistment->close();

// --- C. FETCH SUBJECTS USING ENLISTMENT ---
if ($enlistment_id) {
    $sql_subjects = "
        SELECT ES.sub_code, S.title AS subject_title
        FROM enlisted_subjects ES
        JOIN subjects S ON ES.sub_code = S.sub_code
        WHERE ES.enlistment_id = ?
        ORDER BY ES.sub_code
    ";
    
    $stmt_subjects = $conn->prepare($sql_subjects);
    $stmt_subjects->bind_param("s", $enlistment_id);
    $stmt_subjects->execute();
    
    $result_subjects = $stmt_subjects->get_result();
    while ($row = $result_subjects->fetch_assoc()) {
        $subjects[] = $row;
    }
    $stmt_subjects->close();
}

$conn->close();

// --- OUTPUT JSON ---
echo json_encode([
    "student_id"   => $student_id,
    "student_name" => $student_name,
    "program_name" => $program_name,
    "yrlevel"      => $yrlevel,
    "status_name"  => $status_name,
    "subjects"     => $subjects // empty array if no subjects
]);
?>
