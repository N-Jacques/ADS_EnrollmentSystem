<?php
include 'connection.php';
header('Content-Type: application/json');

$student_id = $_GET['student_id'] ?? '';

if (empty($student_id)) {
    echo json_encode(['success' => false, 'passed_subjects' => []]);
    exit;
}

// Check for subjects passed (Grades 1.00 to 3.00 are considered passed)
$sql = "SELECT sub_code FROM subjects_taken WHERE student_id = ? AND grade >= 1.00 AND grade <= 3.00";
$stmt = execute_query($conn, $sql, "s", [$student_id]);
$result = $stmt->get_result();

$passed_subjects = [];
while ($row = $result->fetch_assoc()) {
    $passed_subjects[] = $row['sub_code'];
}

echo json_encode(['success' => true, 'passed_subjects' => $passed_subjects]);
?>
