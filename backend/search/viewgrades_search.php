<?php
session_start();

// Logged-in student (from session)
$student_id = $_SESSION['student_id'] ?? '202250051';

// Get semester IDs from POST
$semester_ids = $_POST['semester_ids'] ?? '';
$semester_array = array_filter(array_map('trim', explode(",", $semester_ids)));

if (empty($semester_array)) {
    echo "<tr><td colspan='4'>No semesters selected.</td></tr>";
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
} catch(PDOException $e) {
    die("<tr><td colspan='4'>Connection failed: " . htmlspecialchars($e->getMessage()) . "</td></tr>");
}

// Prepare query: fetch subjects with grades for selected semesters
$placeholders = implode(',', array_fill(0, count($semester_array), '?'));
$sql = "
SELECT st.sub_code, s.title AS sub_title, st.grade, st.sem_id
FROM subjects_taken st
JOIN subjects s ON st.sub_code = s.sub_code
WHERE st.student_id = ?
AND st.sem_id IN ($placeholders)
ORDER BY st.sem_id, st.sub_code
";

$stmt = $conn->prepare($sql);

// Bind parameters: first student_id, then semester IDs
$params = array_merge([$student_id], $semester_array);
$stmt->execute($params);

// Fetch results
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "<tr><td colspan='4'>No grades found for the selected semester(s).</td></tr>";
} else {
    foreach ($rows as $row) {
        $sub_code  = htmlspecialchars($row['sub_code']);
        $sub_title = htmlspecialchars($row['sub_title']);
        $grade     = htmlspecialchars($row['grade']);
        $sem_id    = htmlspecialchars($row['sem_id']);

        // Determine remarks
        if ($grade >= 1.00 && $grade <= 3.00) {
            $remarks = "Passed";
        } elseif ($grade == 5.00) {
            $remarks = "Failed";
        } else {
            $remarks = "-";
        }

        echo "<tr data-semester-id='{$sem_id}'>
                <td>{$sub_code}</td>
                <td>{$sub_title}</td>
                <td>{$grade}</td>
                <td>{$remarks}</td>
              </tr>";
    }
}

?>

