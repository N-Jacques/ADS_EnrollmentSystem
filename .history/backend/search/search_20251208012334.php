<?php
include '../connection.php';
header('Content-Type: text/html');

$query = $_POST['query'] ?? '';
$subtype = $_POST['subtype'] ?? '';
$semester = $_POST['semester'] ?? '20251'; 

$sql = "
SELECT 
    s.sub_code, s.title, s.units, sc.section, 
    sc.sem_id, dd.day_name, sc.time_start, sc.time_end, sc.room, sc.slots
FROM subjects s
LEFT JOIN subject_type st ON s.subtype_id = st.subtype_id
LEFT JOIN schedule sc ON s.sub_code = sc.sub_code
LEFT JOIN day_details dd ON sc.day_id = dd.day_id
WHERE (s.sub_code LIKE ? OR s.title LIKE ?)
AND sc.sem_id = ?
";

$sql_types = "sss";
$params = ["%$query%", "%$query%", $semester];

if (!empty($subtype)) {
    $sql .= " AND st.subtype_id = ?";
    $sql_types .= "s";
    $params[] = $subtype;
}

$sql .= " ORDER BY s.sub_code, sc.section";

$stmt = execute_query($conn, $sql, $sql_types, $params);
$result = $stmt ? $stmt->get_result() : false;

$subjects = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $key = $row['sub_code'] . '-' . $row['section'];
        if (!isset($subjects[$key])) {
            $subjects[$key] = $row;
            $subjects[$key]['schedule_text'] = [];
        }
        $subjects[$key]['schedule_text'][] = $row['day_name'] . " " . $row['time_start'] . "-" . $row['time_end'];
    }
}

$output = "";
if (empty($subjects)) {
    $output = "<tr><td colspan='8' style='text-align:center; padding: 20px;'>No subjects found.</td></tr>";
} else {
    foreach ($subjects as $sub) {
        $sched = implode(" / ", $sub['schedule_text']);
        $isFull = $sub['slots'] <= 0;
        
        $btnClass = $isFull ? 'btn-disabled' : 'btn-add';
        $btnText = $isFull ? 'FULL' : 'ADD';
        $action = $isFull ? 'disabled' : "onclick=\"addSubject(this)\"";

        // Escape variables for HTML safety
        $code = htmlspecialchars($sub['sub_code']);
        $section = htmlspecialchars($sub['section'] ?? '');
        $title = htmlspecialchars($sub['title']);
        $units = htmlspecialchars($sub['units']);
        $safeSched = htmlspecialchars($sched);
        $slots = htmlspecialchars($sub['slots']);
        $room = htmlspecialchars($sub['room']);

        $output .= "
        <tr>
            <td>
                <button class='{$btnClass}' 
                    data-code='{$code}'
                    data-section='{$section}'
                    data-title='{$title}'
                    data-units='{$units}'
                    data-schedule='{$safeSched}'
                    data-slots='{$slots}'
                    {$action}>
                    {$btnText}
                </button>
            </td>
            <td>{$code}</td>
            <td>{$section}</td>
            <td>{$title}</td>
            <td>{$units}</td>
            <td>{$sched}</td>
            <td>{$room}</td>
            <td>{$slots}</td>
        </tr>";
    }
}

if ($stmt) $stmt->close();
echo $output;
?>