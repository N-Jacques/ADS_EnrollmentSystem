<?php
include '../connection.php';
header('Content-Type: application/json');

// 1. Get Input Data
$input = json_decode(file_get_contents('php://input'), true);
$student_id = $input['student_id'] ?? '';
$sem_id     = $input['sem_id'] ?? '';
$subjects   = $input['subjects'] ?? [];

if (empty($student_id) || empty($sem_id) || empty($subjects)) {
    echo json_encode(['success' => false, 'error' => 'Missing student ID, semester, or subjects.']);
    exit;
}

// 2. Start Transaction (Critical for Data Integrity)
$conn->begin_transaction();

try {
    // A. Check if already enlisted
    $check_sql = "SELECT enlistment_id FROM enlistment WHERE student_id = ? AND sem_id = ?";
    $stmt_check = execute_query($conn, $check_sql, "ss", [$student_id, $sem_id]);
    
    if ($stmt_check->get_result()->num_rows > 0) {
        throw new Exception("You are already enlisted for this semester.");
    }
    $stmt_check->close();

    // B. Generate new Enlistment ID (Random 5-char string based on your VARCHAR(5) schema)
    $enlistment_id = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
    $date_created = date('Y-m-d');

    // C. Insert Header (Enlistment Table)
    $sql_header = "INSERT INTO enlistment (enlistment_id, student_id, sem_id, date_created) VALUES (?, ?, ?, ?)";
    $stmt_header = execute_query($conn, $sql_header, "ssss", [$enlistment_id, $student_id, $sem_id, $date_created]);
    
    if (!$stmt_header) {
        throw new Exception("Failed to create enlistment record.");
    }
    $stmt_header->close();

    // D. Process Subjects
    $sql_detail = "INSERT INTO enlisted_subjects (enlistment_id, sub_code, section) VALUES (?, ?, ?)";
    
    // NOTE: We update ALL schedule rows for this section/subject/sem combination.
    // Since 'schedule' has a composite PK including day_id, a single section (e.g. Block 1) might have 2 rows (Mon, Thu).
    // Decrementing slots for the section implies decrementing it for all days associated with that section.
    $sql_update_slots = "UPDATE schedule SET slots = slots - 1 WHERE sub_code = ? AND section = ? AND sem_id = ? AND slots > 0";

    foreach ($subjects as $sub) {
        $sub_code = $sub['code'];
        $section  = $sub['section'];

        // D1. Insert into enlisted_subjects
        $stmt_detail = execute_query($conn, $sql_detail, "sss", [$enlistment_id, $sub_code, $section]);
        if (!$stmt_detail) {
            throw new Exception("Failed to record subject: " . $sub_code);
        }
        $stmt_detail->close();

        // D2. Deduct Slots
        $stmt_slots = execute_query($conn, $sql_update_slots, "sss", [$sub_code, $section, $sem_id]);
        
        // If 0 rows affected, it means the section is invalid OR slots were 0 (Full)
        if ($stmt_slots->affected_rows === 0) {
            // Check specifically why it failed to give a better error
            $check_full = execute_query($conn, "SELECT slots FROM schedule WHERE sub_code=? AND section=? AND sem_id=?", "sss", [$sub_code, $section, $sem_id]);
            $res_full = $check_full->get_result();
            
            if ($res_full->num_rows > 0) {
                throw new Exception("Failed to enlist: $sub_code (Section $section) is full.");
            } else {
                throw new Exception("Failed to enlist: Schedule not found for $sub_code (Section $section).");
            }
        }
        $stmt_slots->close();
    }

    // 3. Commit
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // 4. Rollback on Error
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>