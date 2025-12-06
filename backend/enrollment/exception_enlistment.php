<?php
session_start();

// Get logged-in student
$student_id = $_SESSION['student_id'] ?? '202250051';

// Get POST data from enrollment form
$sub_code = $_POST['sub_code'] ?? '';
$section = $_POST['section'] ?? '';
$sem_id = $_POST['sem_id'] ?? '';
$enlistment_id = $_POST['enlistment_id'] ?? '';
$validate_only = $_POST['validate_only'] ?? 'false'; // Check if we're just validating

// Validate inputs
if (empty($sub_code) || empty($section) || empty($sem_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields.'
    ]);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'adsDB';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ================================================
    // VALIDATION 1: Check if subject already PASSED
    // ================================================
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM subjects_taken
        WHERE student_id = ?
          AND sub_code = ?
          AND grade >= 1.00
          AND grade <= 3.00
    ");
    $stmt->execute([$student_id, $sub_code]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot enlist: You have already passed this subject.'
        ]);
        exit;
    }
    
    // ================================================
    // VALIDATION 2: Check if slots are available
    // ================================================
    $stmt = $conn->prepare("
        SELECT slots
        FROM schedule
        WHERE sub_code = ?
          AND section = ?
          AND sem_id = ?
        LIMIT 1
    ");
    $stmt->execute([$sub_code, $section, $sem_id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode([
            'success' => false,
            'message' => 'Schedule not found.'
        ]);
        exit;
    }
    
    if ($schedule['slots'] <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot enlist: No slots available for this schedule.'
        ]);
        exit;
    }
    
    // ================================================
    // VALIDATION 3: Check for schedule conflicts
    // ================================================
    $stmt = $conn->prepare("
        SELECT COUNT(*) as conflict_count
        FROM enlisted_subjects es
        JOIN schedule s1 ON es.sub_code = s1.sub_code
        JOIN schedule s2 ON s2.sub_code = ? AND s2.section = ? AND s2.sem_id = ?
        WHERE es.enlistment_id IN (
            SELECT enlistment_id 
            FROM enlistment 
            WHERE student_id = ? AND sem_id = ?
        )
        AND s1.day_id = s2.day_id
        AND (
            (s1.time_start < s2.time_end AND s1.time_end > s2.time_start)
        )
    ");
    $stmt->execute([$sub_code, $section, $sem_id, $student_id, $sem_id]);
    $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conflict['conflict_count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot enlist: Schedule conflicts with your existing subjects.'
        ]);
        exit;
    }
    
    // ================================================
    // ALL VALIDATIONS PASSED
    // ================================================
    
    // If we're only validating (not actually enrolling yet), return success
    if ($validate_only === 'true') {
        echo json_encode([
            'success' => true,
            'message' => 'Subject can be enlisted!'
        ]);
        exit;
    }
    
    // Otherwise, proceed with actual enrollment
    // INSERT THE SUBJECT
    
    // Insert into enlisted_subjects
    $stmt = $conn->prepare("
        INSERT INTO enlisted_subjects (enlistment_id, sub_code)
        VALUES (?, ?)
    ");
    $stmt->execute([$enlistment_id, $sub_code]);
    
    // Decrease available slots
    $stmt = $conn->prepare("
        UPDATE schedule
        SET slots = slots - 1
        WHERE sub_code = ?
          AND section = ?
          AND sem_id = ?
    ");
    $stmt->execute([$sub_code, $section, $sem_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Subject successfully enlisted!'
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>