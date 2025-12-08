<?php
include '../connection.php';
header('Content-Type: application/json');

$student_id = $_GET['student_id'] ?? '';
$sem_id = $_GET['sem_id'] ?? '20251'; 

if (empty($student_id)) exit;

// Check if enrolled
$sql_check = "
SELECT e.enlistment_id, (SELECT count(*) FROM enrolled en WHERE en.enlistment_id = e.enlistment_id) as is_enrolled
FROM enlistment e
WHERE e.student_id = ? AND e.sem_id = ?";

$stmt = execute_query($conn, $sql_check, "ss", [$student_id, $sem_id]);
$res = $stmt->get_result()->fetch_assoc();

$enlistment_id = $res['enlistment_id'] ?? null;
$is_enrolled = ($res['is_enrolled'] ?? 0) > 0;

$enlisted = [];

if ($enlistment_id) {
    // Fetch details
    $sql_details = "
    SELECT 
        es.sub_code, 
        es.section, 
        s.title, 
        s.units, 
        sc.day_id, 
        dd.day_name, 
        sc.time_start, 
        sc.time_end
    FROM enlisted_subjects es
    JOIN subjects s ON es.sub_code = s.sub_code
    JOIN schedule sc ON es.sub_code = sc.sub_code AND es.section = sc.section AND sc.sem_id = ?
    JOIN day_details dd ON sc.day_id = dd.day_id
    WHERE es.enlistment_id = ?";
    
    $stmt_d = execute_query($conn, $sql_details, "ss", [$sem_id, $enlistment_id]);
    $result = $stmt_d->get_result();

    while($row = $result->fetch_assoc()) {
        $key = $row['sub_code'] . '-' . $row['section'];
        if (!isset($enlisted[$key])) {
            $enlisted[$key] = [
                'sub_code' => $row['sub_code'],
                'section' => $row['section'],
                'title' => $row['title'],
                'units' => $row['units'],
                'schedule_arr' => []
            ];
        }
        $enlisted[$key]['schedule_arr'][] = $row['day_name'] . " " . $row['time_start'] . "-" . $row['time_end'];
    }
    
    // Flatten schedule array to string
    foreach($enlisted as &$item) {
        $item['schedule'] = implode(" / ", $item['schedule_arr']);
        unset($item['schedule_arr']);
    }
}

echo json_encode([
    'enlisted' => array_values($enlisted), 
    'is_enrolled' => $is_enrolled
]);
?>