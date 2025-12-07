<?php
include '../connection.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$student_id = $data['student_id'] ?? '';
$sub_code = $data['sub_code'] ?? '';
$section = $data['section'] ?? '';
$sem_id = $data['sem_id'] ?? '20251';

if (empty($student_id) || empty($sub_code) || empty($section) || empty($sem_id)) {
    echo json_encode(['success' => false, 'error' => 'Missing required data.']);
    exit;
}

// FIX: Using raw SQL START TRANSACTION
if (!$conn->query("START TRANSACTION")) {
    echo json_encode(['success' => false, 'error' => 'Failed to start transaction (Raw SQL failed).']);
    exit;
}

try {
    // Check if the student is already officially enrolled for this semester
    $sql_check_enrollment = "SELECT is_enrolled FROM Student_Status WHERE student_id = ? AND sem_id = ? FOR UPDATE";
    $stmt_check_enrollment = execute_query($conn, $sql_check_enrollment, "ss", [$student_id, $sem_id]);
    $status_result = $stmt_check_enrollment ? $stmt_check_enrollment->get_result() : false;
    $status = $status_result ? $status_result->fetch_assoc() : null;

    if ($status && $status['is_enrolled']) {
        throw new Exception('Cannot remove subject; enrollment is finalized.');
    }
    if ($stmt_check_enrollment) $stmt_check_enrollment->close();

    // Delete the enlistment record
    $sql = "DELETE FROM Enlistment WHERE student_id = ? AND sub_code = ? AND section = ? AND sem_id = ?";
    $stmt = execute_query($conn, $sql, "ssss", [$student_id, $sub_code, $section, $sem_id]);

    if ($stmt->affected_rows === 0) {
        throw new Exception('Subject not found in your enlisted list.');
    }

    // COMMIT
    $conn->query("COMMIT");
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // ROLLBACK
    if (isset($conn) && $conn) { 
        @$conn->query("ROLLBACK"); 
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// REMOVED $conn->close();
?>