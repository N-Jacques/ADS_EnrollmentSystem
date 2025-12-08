<?php
include '../connection.php';
header('Content-Type: text/html');

$query = $_POST['query'] ?? '';
$subtype = $_POST['subtype'] ?? '';
$semester = $_POST['semester'] ?? '20251'; // Default to current sem

// SQL: Join subjects, schedule, and day_details
// We order by sub_code and section to make grouping easier
$sql = "
SELECT 
    s.sub_code, 
    s.title, 
    s.units, 
    sc.section, 
    sc.sem_id, 
    dd.day_name, 
    sc.time_start, 
    sc.time_end, 
    sc.room, 
    sc.slots
FROM subjects s
JOIN schedule sc ON s.sub_code = sc.sub_code
JOIN day_details dd ON sc.day_id = dd.day_id
WHERE (s.sub_code LIKE ? OR s.title LIKE ?)
AND sc.sem_id = ?
";

$sql_types = "sss";
$params = ["%$query%", "%$query%", $semester];

// Filter by subtype (Major/Minor) if provided
// Note: Your schema joins subjects -> subject_type. 
// If 'subtype' input is 'MAJ' or 'MIN', ensure it matches IDs in your subject_type table.
if (!empty($subtype)) {
    $sql .= " AND s.subtype_id = ?";
    $sql_types .= "s";
    $params[] = $subtype;
}

$sql .= " ORDER BY s.sub_code, sc.section";

$stmt = execute_query($conn, $sql, $sql_types, $params);
$result = $stmt ? $stmt->get_result() : false;

$subjects = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Create a unique key per Section (Code + Section)
        // This handles cases where one section has multiple schedule rows (e.g. Mon and Wed)
        $key = $row['sub_code'] . '-' . $row['section'];
        
        if (!isset($subjects[$key])) {
            $subjects[$key] = [
                'sub_code' => $row['sub_code'],
                'section'  => $row['section'], // From schedule table
                'title'    => $row['title'],
                'units'    => $row['units'],
                'room'     => $row['room'],
                'slots'    => $row['slots'],
                'schedules'=> []
            ];
        }
        
        // Format time (remove seconds if present, e.g., 07:00:00 -> 07:00)
        $start = substr($row['time_start'], 0, 5);
        $end   = substr($row['time_end'], 0, 5);
        
        // Add specific day schedule to array
        $subjects[$key]['schedules'][] = $row['day_name'] . " " . $start . "-" . $end;
    }
}

// Generate HTML
$output = "";
if (empty($subjects)) {
    $output = "<tr><td colspan='8' style='text-align:center; padding:20px; color:#777;'>No subjects found.</td></tr>";
} else {
    foreach ($subjects as $sub) {
        // Combine multiple days into one string: "Monday 07:00-10:00 / Wednesday 07:00-10:00"
        $schedString = implode(" / ", $sub['schedules']);
        
        $isFull = $sub['slots'] <= 0;
        $btnClass = $isFull ? 'btn-disabled' : 'btn-add';
        $btnText = $isFull ? 'FULL' : 'ADD';
        $action = $isFull ? 'disabled' : "onclick=\"addSubject(this)\"";

        // HTML Safe Output
        $code    = htmlspecialchars($sub['sub_code']);
        $section = htmlspecialchars($sub['section']);
        $title   = htmlspecialchars($sub['title']);
        $units   = htmlspecialchars($sub['units']);
        $sched   = htmlspecialchars($schedString);
        $room    = htmlspecialchars($sub['room']);
        $slots   = htmlspecialchars($sub['slots']);

        $output .= "
        <tr>
            <td>
                <button class='{$btnClass}' 
                    data-code='{$code}'
                    data-section='{$section}'
                    data-title='{$title}'
                    data-units='{$units}'
                    data-schedule='{$sched}'
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