<?php
include 'connection.php';
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
    // 1. Check if the student is already officially enrolled for this semester
    $sql_check_enrollment = "SELECT is_enrolled FROM Student_Status WHERE student_id = ? AND sem_id = ? FOR UPDATE";
    $stmt_check_enrollment = execute_query($conn, $sql_check_enrollment, "ss", [$student_id, $sem_id]);
    $status_result = $stmt_check_enrollment ? $stmt_check_enrollment->get_result() : false;
    $status = $status_result ? $status_result->fetch_assoc() : null;

    if ($status && $status['is_enrolled']) {
        throw new Exception('You are already officially enrolled. Changes are locked.');
    }
    $stmt_check_enrollment->close();


    // 2. Check for duplicate enlistment
    $sql_check = "SELECT * FROM Enlistment WHERE student_id = ? AND sub_code = ? AND section = ? AND sem_id = ?";
    $stmt_check = execute_query($conn, $sql_check, "ssss", [$student_id, $sub_code, $section, $sem_id]);
    $check_result = $stmt_check ? $stmt_check->get_result() : false;

    if ($check_result && $check_result->num_rows > 0) {
        throw new Exception('Subject is already enlisted.');
    }
    $stmt_check->close();

    // 3. Check for slot availability (with lock for concurrency safety)
    $sql_slots = "
    SELECT s.slots, COUNT(e.student_id) as enlisted_count
    FROM Subjects s
    LEFT JOIN Enlistment e ON s.sub_code = e.sub_code AND s.section = e.section AND s.sem_id = e.sem_id
    WHERE s.sub_code = ? AND s.section = ? AND s.sem_id = ?
    GROUP BY s.slots
    FOR UPDATE
    ";
    $stmt_slots = execute_query($conn, $sql_slots, "sss", [$sub_code, $section, $sem_id]);
    $slots_result = $stmt_slots->get_result();
    $subject_info = $slots_result ? $slots_result->fetch_assoc() : null;
    if ($stmt_slots) $stmt_slots->close();

    if ($subject_info === null) {
        throw new Exception("Subject section not found.");
    }
    
    $remaining_slots = $subject_info['slots'] - $subject_info['enlisted_count'];

    if ($remaining_slots <= 0) {
        throw new Exception("Subject section is already full. Remaining slots: 0");
    }

    // 4. Insert into Enlistment
    $sql_insert = "INSERT INTO Enlistment (student_id, sub_code, section, sem_id) VALUES (?, ?, ?, ?)";
    $stmt_insert = execute_query($conn, $sql_insert, "ssss", [$student_id, $sub_code, $section, $sem_id]);

    if ($stmt_insert === false || $stmt_insert->affected_rows === 0) {
        throw new Exception("Failed to insert enlistment record.");
    }
    $stmt_insert->close();

    // 5. Ensure Student_Status exists
    $sql_upsert_status = "
        INSERT INTO Student_Status (student_id, sem_id, is_enrolled) 
        VALUES (?, ?, FALSE) 
        ON DUPLICATE KEY UPDATE is_enrolled=is_enrolled"; 
    $stmt_upsert = execute_query($conn, $sql_upsert_status, "ss", [$student_id, $sem_id]);
    $stmt_upsert->close();

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
