<?php
include '../connection.php';
header('Content-Type: application/json');

$student_id = $_GET['student_id'] ?? '';
$sem_id     = $_GET['sem_id'] ?? '';

if (empty($student_id) || empty($sem_id)) {
    echo json_encode(['is_enrolled' => false]);
    exit;
}

// Check if a record exists in the enlistment table for this student/semester
$sql = "SELECT enlistment_id FROM enlistment WHERE student_id = ? AND sem_id = ?";
$stmt = execute_query($conn, $sql, "ss", [$student_id, $sem_id]);
$result = $stmt->get_result();

// If row exists, they are officially enrolled
if ($result->num_rows > 0) {
    echo json_encode(['is_enrolled' => true]);
} else {
    echo json_encode(['is_enrolled' => false]);
}
?>