<?php
include '../connection.php';
header('Content-Type: application/json');

$student_id = $_GET['student_id'] ?? '';
$sem_id     = $_GET['sem_id'] ?? '';

if (empty($student_id) || empty($sem_id)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters.']);
    exit;
}

// SQL: Get enlisted subjects linked to the official enlistment record
// We join enlistment -> enlisted_subjects -> subjects -> schedule -> day_details
$sql = "
SELECT 
    s.sub_code
    , s.title
    , s.units
    , es.section
    , sc.time_start
    , sc.time_end
    , sc.room
    , dd.day_name
FROM enlistment e
JOIN enlisted_subjects es ON e.enlistment_id = es.enlistment_id
JOIN subjects s ON es.sub_code = s.sub_code
LEFT JOIN schedule sc ON (es.sub_code = sc.sub_code AND es.section = sc.section AND e.sem_id = sc.sem_id)
LEFT JOIN day_details dd ON sc.day_id = dd.day_id
WHERE e.student_id = ? AND e.sem_id = ?
ORDER BY s.sub_code
";

$stmt = execute_query($conn, $sql, "ss", [$student_id, $sem_id]);
$result = $stmt->get_result();

$schedule = [];

while ($row = $result->fetch_assoc()) {
    $key = $row['sub_code'];
    
    // Group by subject code because one subject might have multiple schedule rows (Mon/Thu)
    if (!isset($schedule[$key])) {
        $schedule[$key] = [
            'code'    => $row['sub_code'],
            'section' => $row['section'],
            'title'   => $row['title'],
            'units'   => $row['units'],
            'room'    => $row['room'], 
            'sched_parts' => []
        ];
    }

    // Format Time (e.g., 13:00:00 -> 13:00)
    if ($row['day_name']) {
        $start = substr($row['time_start'], 0, 5);
        $end   = substr($row['time_end'], 0, 5);
        $schedule[$key]['sched_parts'][] = "{$row['day_name']} $start-$end";
    }
}

// Flatten schedule string (e.g., "Monday 13:00-15:00 / Thursday 13:00-15:00")
foreach ($schedule as &$item) {
    $item['schedule'] = empty($item['sched_parts']) ? 'TBA' : implode(" / ", $item['sched_parts']);
    unset($item['sched_parts']);
}

echo json_encode(['success' => true, 'data' => array_values($schedule)]);
?>