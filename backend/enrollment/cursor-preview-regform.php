<?php
// Turn off error display in production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

// --- Standalone DB connection ---
$host = 'localhost';
$db   = 'adsDB';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// --- Read input JSON ---
$data = json_decode(file_get_contents('php://input'), true);
$student_id = $data['student_id'] ?? null;
$sem_id     = $data['sem_id'] ?? null;

if (!$student_id || !$sem_id) {
    echo json_encode(['error' => 'Missing required parameters: student_id or sem_id']);
    exit;
}

try {
    $sql = "
        SELECT
            stu.student_id,
            CONCAT(stu.lastname, ', ', stu.firstname, ' ', COALESCE(stu.middlename, '')) AS full_name,
            stu.yrlevel AS year_level,
            pr.program_name AS course,
            cl.college_name AS college,

            en.enlistment_id,
            en.date_created AS enlistment_date,

            sem.sem_id,
            sem.date_start AS semester_start,
            sem.date_end AS semester_end,

            subj.sub_code,
            subj.title AS subject_title,
            subj.units,
            st.subtype_desc,

            sch.section,
            dd.day_name,
            sch.time_start,
            sch.time_end,
            sch.room

        FROM enlistment en
        JOIN students stu
            ON stu.student_id = en.student_id
        JOIN programs pr
            ON pr.program_id = stu.program_id
        JOIN colleges cl
            ON cl.college_id = pr.college_id
        JOIN semester sem
            ON sem.sem_id = en.sem_id
        JOIN enlisted_subjects es
            ON es.enlistment_id = en.enlistment_id
        JOIN subjects subj
            ON subj.sub_code = es.sub_code
        JOIN subject_type st
            ON st.subtype_id = subj.subtype_id
        LEFT JOIN schedule sch
            ON sch.sub_code = es.sub_code
            AND sch.section = es.section
            AND sch.sem_id = en.sem_id
        LEFT JOIN day_details dd
            ON dd.day_id = sch.day_id
        WHERE en.student_id = :student_id
          AND en.sem_id = :sem_id
        ORDER BY subj.sub_code, sch.section, sch.day_id, sch.time_start
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':student_id' => $student_id,
        ':sem_id'     => $sem_id
    ]);

    $header = null;
    $subjects = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($header === null) {
            $header = [
                'student_id' => $row['student_id'],
                'full_name' => $row['full_name'],
                'year_level' => $row['year_level'],
                'course' => $row['course'],
                'college' => $row['college'],
                'enlistment_id' => $row['enlistment_id'],
                'enlistment_date' => $row['enlistment_date'],
                'sem_id' => $row['sem_id'],
                'semester_start' => $row['semester_start'],
                'semester_end' => $row['semester_end']
            ];
        }

        // Ensure empty schedule fields are null instead of missing
        $subjects[] = [
            'sub_code' => $row['sub_code'] ?? '',
            'subject_title' => $row['subject_title'] ?? '',
            'units' => $row['units'] ?? '',
            'subtype_desc' => $row['subtype_desc'] ?? '',
            'section' => $row['section'] ?? '',
            'day_name' => $row['day_name'] ?? '',
            'time_start' => $row['time_start'] ?? '',
            'time_end' => $row['time_end'] ?? '',
            'room' => $row['room'] ?? ''
        ];
    }

    if ($header === null) {
        echo json_encode(['error' => 'No enrollment records found for this student in the selected semester']);
        exit;
    }

    // Return JSON
    echo json_encode([
        'header' => $header,
        'subjects' => $subjects
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
    exit;
}
?>