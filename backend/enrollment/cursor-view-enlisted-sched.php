<?php
header('Content-Type: application/json');
require_once '../connection.php';

$data = json_decode(file_get_contents('php://input'), true);

// load parameters
$student_id = $data['student_id'] ?? '';
$sem_id     = $data['sem_id']     ?? '';

try {
    $stmt = $conn->prepare("
        SELECT sub_code, subject_title, section, 
        GROUP_CONCAT(schedule_detail ORDER BY schedule_detail SEPARATOR '\n') AS schedule_detail
        FROM vw_enlisted_schedule
        WHERE student_id =  :studentId AND sem_id = :semId
        GROUP BY sub_code, subject_title, section
    ");
    $stmt->bindParam(':studentId', $student_id);
    $stmt->bindParam(':semId', $sem_id);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $results]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
