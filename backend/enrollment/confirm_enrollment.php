<?php
header('Content-Type: application/json');
require_once '../connection.php';

$data = json_decode(file_get_contents('php://input'), true);

$student_id = $data['student_id'] ?? '';
$sem_id = $data['sem_id'] ?? '20251';

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        SELECT enlistment_id 
        FROM enlistment 
        WHERE student_id = ? AND sem_id = ?
    ");
    $stmt->execute([$student_id, $sem_id]);
    $enlistment_id = $stmt->fetchColumn();

    if (!$enlistment_id) {
        echo json_encode(['error' => 'No enlistment found']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM enrolled 
        WHERE enlistment_id = ?
    ");
    $stmt->execute([$enlistment_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['error' => 'Already enrolled']);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO enrolled (enlistment_id, date_created) 
        VALUES (?, NOW())
    ");
    $stmt->execute([$enlistment_id]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Enrollment confirmed successfully'
    ]);

} catch(PDOException $e) {
    $conn->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}