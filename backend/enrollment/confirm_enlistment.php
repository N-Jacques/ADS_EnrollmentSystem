<?php
include '../connection.php'; // Ensure this path is correct
header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$student_id = $input['student_id'] ?? '';
$sem_id     = $input['sem_id'] ?? '';
$subjects   = $input['subjects'] ?? [];

if (empty($student_id) || empty($sem_id) || empty($subjects)) {
    echo json_encode(['success' => false, 'error' => 'Missing student ID, semester, or subjects.']);
    exit;
}

// Start Transaction
if (!$conn->begin_transaction()) {
    echo json_encode(['success' => false, 'error' => 'Failed to start transaction.']);
    exit;
}

try {
    // 1. Check if already enlisted/enrolled for this semester
    // We check the 'enlistment' table.
    $check_sql = "SELECT enlistment_id FROM enlistment WHERE student_id = ? AND sem_id = ?";
    $stmt_check = execute_query($conn, $check_sql, "ss", [$student_id, $sem_id]);
    
    if ($stmt_check->get_result()->num_rows > 0) {
        throw new Exception("You are already enlisted for this semester.");
    }
    $stmt_check->close();

    // 2. Generate new Enlistment ID (Simple random 5-char string or logic)
    // Adjust logic if you have a specific ID format (e.g., auto-increment)
    $enlistment_id = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);

    // 3. Insert into 'enlistment' header table
    $date_created = date('Y-m-d');
    $sql_header = "INSERT INTO enlistment (enlistment_id, student_id, sem_id, date_created) VALUES (?, ?, ?, ?)";
    $stmt_header = execute_query($conn, $sql_header, "ssss", [$enlistment_id, $student_id, $sem_id, $date_created]);
    
    if (!$stmt_header) {
        throw new Exception("Failed to create enlistment record.");
    }
    $stmt_header->close();

    // 4. Process Subjects
    $sql_detail = "INSERT INTO enlisted_subjects (enlistment_id, sub_code) VALUES (?, ?)";
    $sql_update_slots = "UPDATE schedule SET slots = slots - 1 WHERE sub_code = ? AND section = ? AND sem_id = ? AND slots > 0";

    foreach ($subjects as $sub) {
        $sub_code = $sub['code'];
        $section  = $sub['section'];

        // A. Insert into enlisted_subjects
        // NOTE: We do NOT insert 'section' here because your DB table doesn't have that column.
        $stmt_detail = execute_query($conn, $sql_detail, "ss", [$enlistment_id, $sub_code]);
        if (!$stmt_detail) {
            throw new Exception("Failed to add subject: " . $sub_code);
        }
        $stmt_detail->close();

        // B. Deduct Slots in Schedule
        // We use 'section' here to find the correct schedule to update
        $stmt_slots = execute_query($conn, $sql_update_slots, "sss", [$sub_code, $section, $sem_id]);
        
        if ($stmt_slots->affected_rows === 0) {
            // Rollback if no slots were updated (meaning slots were 0 or subject didn't exist)
            throw new Exception("Failed to reserve slot for {$sub_code} (Section {$section}). It might be full.");
        }
        $stmt_slots->close();
    }

    // 5. Commit
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>