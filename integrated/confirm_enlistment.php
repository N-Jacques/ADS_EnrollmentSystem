<?php
include 'connection.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$student_id = $data['student_id'] ?? '';
$sem_id = $data['sem_id'] ?? '';
$subjects = $data['subjects'] ?? [];

if (empty($student_id) || empty($sem_id) || empty($subjects)) {
    echo json_encode(['success' => false, 'error' => 'No subjects selected.']);
    exit;
}

// Start Transaction
if (!$conn->query("START TRANSACTION")) {
    echo json_encode(['success' => false, 'error' => 'Transaction failed.']);
    exit;
}

try {
    // 1. CHECK IF ALREADY HAS AN ENLISTMENT RECORD
    // If they already submitted a form for this sem, stop them.
    $check_enlistment = execute_query($conn, 
        "SELECT count(*) as cnt FROM enlistment WHERE student_id = ? AND sem_id = ?", 
        "ss", [$student_id, $sem_id]);
    
    if ($check_enlistment->get_result()->fetch_assoc()['cnt'] > 0) {
        throw new Exception("You have already submitted an enlistment for this semester.");
    }

    // 2. SECURITY CHECK: ALREADY PASSED SUBJECTS
    $stmt_check_grade = $conn->prepare("SELECT grade FROM subjects_taken WHERE student_id = ? AND sub_code = ? AND grade BETWEEN 1.00 AND 3.00");
    
    foreach ($subjects as $sub) {
        $stmt_check_grade->bind_param("ss", $student_id, $sub['code']);
        $stmt_check_grade->execute();
        $res_grade = $stmt_check_grade->get_result();
        
        if ($res_grade->num_rows > 0) {
            throw new Exception("EXCEPTION: You have already passed subject " . $sub['code']);
        }
    }

    // 3. EXCEPTION CHECK: CONFLICTS (Backend Verification)
    $subject_times = [];
    foreach ($subjects as $sub) {
        $res = execute_query($conn, "SELECT day_id, time_start, time_end FROM schedule WHERE sub_code=? AND section=? AND sem_id=?", "sss", [$sub['code'], $sub['section'], $sem_id]);
        $rows = $res->get_result();
        while($r = $rows->fetch_assoc()) {
            $r['code'] = $sub['code'];
            $subject_times[] = $r;
        }
    }

    // Compare time blocks
    for ($i = 0; $i < count($subject_times); $i++) {
        for ($j = $i + 1; $j < count($subject_times); $j++) {
            $a = $subject_times[$i];
            $b = $subject_times[$j];
            
            if ($a['day_id'] == $b['day_id']) {
                if ($a['time_start'] < $b['time_end'] && $a['time_end'] > $b['time_start']) {
                    throw new Exception("EXCEPTION: Schedule Conflict detected between {$a['code']} and {$b['code']}.");
                }
            }
        }
    }

    // 4. GENERATE ENLISTMENT ID (E0001 format)
    $res = $conn->query("SELECT CONCAT('E', LPAD(COALESCE(MAX(CAST(SUBSTRING(enlistment_id, 2) AS UNSIGNED)), 0) + 1, 4, '0')) as new_id FROM enlistment FOR UPDATE");
    $enlistment_id = $res->fetch_assoc()['new_id'];

    // 5. INSERT INTO ENLISTMENT (This creates the 'Cart' in the database)
    execute_query($conn, "INSERT INTO enlistment (enlistment_id, student_id, sem_id, date_created) VALUES (?, ?, ?, CURDATE())", "sss", [$enlistment_id, $student_id, $sem_id]);

    // 6. INSERT SUBJECTS & DEDUCT SLOTS
    $stmt_insert = $conn->prepare("INSERT INTO enlisted_subjects (enlistment_id, sub_code, section) VALUES (?, ?, ?)");
    $stmt_deduct = $conn->prepare("UPDATE schedule SET slots = slots - 1 WHERE sub_code = ? AND section = ? AND sem_id = ? AND slots > 0");

    foreach ($subjects as $sub) {
        $code = $sub['code'];
        $sect = $sub['section'];

        // Deduct slots
        $stmt_deduct->bind_param("sss", $code, $sect, $sem_id);
        $stmt_deduct->execute();
        
        if ($stmt_deduct->affected_rows === 0) {
            throw new Exception("EXCEPTION: Slot for $code ($sect) is full (0 slots).");
        }

        // Insert into enlisted_subjects
        $stmt_insert->bind_param("sss", $enlistment_id, $code, $sect);
        if (!$stmt_insert->execute()) {
            throw new Exception("Failed to enlist $code.");
        }
    }

    // ==========================================================
    // REMOVED: INSERT INTO ENROLLED
    // REMOVED: UPDATE STUDENT_STATUS
    // ==========================================================

    $conn->query("COMMIT");
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($conn) @$conn->query("ROLLBACK");
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
