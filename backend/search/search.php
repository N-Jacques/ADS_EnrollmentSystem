<?php
$host = 'localhost';
$dbname = 'ads';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get search term and filters
$search = $_POST['query'] ?? '';
$searchTerm = "%" . $search . "%";
$subtypeFilter = $_POST['subtype'] ?? '';
$semesterFilter = $_POST['semester'] ?? '';

// Fetch all schedules for matching subjects
$sql = "
SELECT 
    s.sub_code,
    s.title,
    st.subtype_id AS subtype,
    s.units,
    sc.section,
    sc.sem_id AS semester,
    sc.day_id,
    sc.time_start,
    sc.time_end,
    sc.room,
    sc.slots
FROM subjects s
LEFT JOIN subject_type st ON s.subtype_id = st.subtype_id
LEFT JOIN schedule sc ON s.sub_code = sc.sub_code
WHERE (s.sub_code LIKE :search OR s.title LIKE :search)
AND sc.slots > 0
";

if(!empty($subtypeFilter)) {
    $sql .= " AND st.subtype_id = :subtype";
}

if(!empty($semesterFilter)) {
    $sql .= " AND sc.sem_id = :semester";
}

$sql .= " ORDER BY s.sub_code, sc.section, sc.day_id, sc.time_start";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":search", $searchTerm, PDO::PARAM_STR);

if(!empty($subtypeFilter)) {
    $stmt->bindParam(":subtype", $subtypeFilter, PDO::PARAM_STR);
}
if(!empty($semesterFilter)) {
    $stmt->bindParam(":semester", $semesterFilter, PDO::PARAM_STR);
}

$stmt->execute();

// Group schedules by subject code
$subjects = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sub_code = $row['sub_code'];
    if (!isset($subjects[$sub_code])) {
        $subjects[$sub_code] = [
            'sub_code' => $sub_code,
            'title' => $row['title'],
            'subtype' => $row['subtype'],
            'units' => $row['units'],
            'semester' => $row['semester'],
            'schedules' => []
        ];
    }
    $subjects[$sub_code]['schedules'][] = [
        'section' => $row['section'],
        'day_time' => $row['day_id'] . ' ' . $row['time_start'] . '–' . $row['time_end'],
        'room' => $row['room'],
        'slots' => $row['slots']
    ];
}

// Output table rows
foreach ($subjects as $subject) {
    $subtype = htmlspecialchars($subject['subtype']);
    $semester = htmlspecialchars($subject['semester']);
    $sub_code = htmlspecialchars($subject['sub_code']);
    $title = htmlspecialchars($subject['title']);
    $units = (int)$subject['units'];

// Main row (one per subject, no units)
echo "<tr class='subject-row' data-subtype='$subtype' data-semester='$semester'>
        <td><button class='toggle-schedules'>+</button></td>
        <td>$sub_code</td>
        <td colspan='6'>$title</td>
      </tr>";


// Hidden schedule rows
foreach ($subject['schedules'] as $sched) {
    $section = htmlspecialchars($sched['section']);
    $day_time = htmlspecialchars($sched['day_time']);
    $room = htmlspecialchars($sched['room']);
    $slots = (int)$sched['slots'];
    $units = (int)$subject['units']; // add units here

    echo "<tr class='schedule-row' style='display:none;' 
            data-code='$sub_code' 
            data-section='$section' 
            data-title='$title' 
            data-units='$units'
            data-daytime='$day_time' 
            data-room='$room' 
            data-slots='$slots'>
            <td><button class='btn-add' onclick='addScheduleToApproval(this)'>ADD</button></td>
            <td>$sub_code</td>
            <td>$section</td>
            <td>$title</td>
            <td>$units</td>
            <td>$day_time</td>
            <td>$room</td>
            <td>$slots</td>
        </tr>";

}


}
?>
Footer
© 2025 GitHub, Inc.
Footer navigation
Terms
Privacy
Security
Status
Community
Docs
Contact
Manage cookies
