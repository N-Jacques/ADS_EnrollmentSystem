<?php
include '../connection.php';

$student_id = $_GET['student_id'] ?? '';
$sem_id     = $_GET['sem_id'] ?? '';

if (empty($student_id) || empty($sem_id)) {
    die("Missing parameters.");
}

// 1. Fetch Student Details (Cursor 1: Student Header)
$sql_student = "
    SELECT 
        s.student_id, s.firstname, s.lastname, s.middlename, s.yrlevel
        , p.program_name 
        , c.college_name 
        , st.status_name
    FROM students s
    LEFT JOIN programs p ON s.program_id = p.program_id
    LEFT JOIN colleges c ON p.college_id = c.college_id
    LEFT JOIN status st ON s.status_id = st.status_id
    WHERE s.student_id = ?
";
$stmt_student = execute_query($conn, $sql_student, "s", [$student_id]);
$student = $stmt_student->get_result()->fetch_assoc();

if (!$student) die("Student not found.");

// Format Name
$fullname = strtoupper($student['lastname'] . ', ' . $student['firstname'] . ' ' . substr($student['middlename'], 0, 1) . '.');

// 2. Fetch Schedule (Cursor 2: Schedule Rows)
// Reusing the logic from get_schedule.php to ensure consistency
$sql_sched = "
    SELECT 
        s.sub_code, s.title, s.units, es.section
        , sc.time_start, sc.time_end, sc.room, dd.day_name
    FROM enlistment e
    JOIN enlisted_subjects es ON e.enlistment_id = es.enlistment_id
    JOIN subjects s ON es.sub_code = s.sub_code
    LEFT JOIN schedule sc ON (es.sub_code = sc.sub_code AND es.section = sc.section AND e.sem_id = sc.sem_id)
    LEFT JOIN day_details dd ON sc.day_id = dd.day_id
    WHERE e.student_id = ? AND e.sem_id = ?
    ORDER BY s.sub_code
";
$stmt_sched = execute_query($conn, $sql_sched, "ss", [$student_id, $sem_id]);
$res_sched = $stmt_sched->get_result();

// Process schedule aggregation
$schedule_data = [];
while ($row = $res_sched->fetch_assoc()) {
    $key = $row['sub_code'];
    if (!isset($schedule_data[$key])) {
        $schedule_data[$key] = $row;
        $schedule_data[$key]['sched_parts'] = [];
    }
    if ($row['day_name']) {
        $start = substr($row['time_start'], 0, 5);
        $end   = substr($row['time_end'], 0, 5);
        $schedule_data[$key]['sched_parts'][] = "{$row['day_name']} $start-$end";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Schedule - <?php echo $student_id; ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 12px; padding: 40px; color: #000; }
        .header { text-align: center; margin-bottom: 30px; }
        .header img { width: 80px; margin-bottom: 10px; }
        .header h2 { margin: 5px 0; font-size: 18px; color: #b18819; }
        .header h3 { margin: 0; font-size: 14px; font-weight: normal; }
        
        .details-grid { 
            display: grid; 
            grid-template-columns: 100px 1fr 100px 1fr; 
            gap: 10px; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #b18819;
            padding-bottom: 15px;
        }
        .label { font-weight: bold; color: #555; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; color: #b18819; text-transform: uppercase; font-size: 11px; }
        td { font-size: 12px; }
        
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #777; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <img src="https://plm.edu.ph/assets/plm-logo.DLcRDINN.png" alt="PLM Logo">
        <h2>PAMANTASAN NG LUNGSOD NG MAYNILA</h2>
        <h3>Official Certificate of Registration</h3>
        <h3>Semester: <?php echo htmlspecialchars($sem_id); ?></h3>
    </div>

    <!-- Cursor Print: Student Details -->
    <div class="details-grid">
        <div class="label">Student ID:</div>
        <div><?php echo htmlspecialchars($student['student_id']); ?></div>
        
        <div class="label">Type/Status:</div>
        <div><?php echo htmlspecialchars($student['status_name']); ?></div>

        <div class="label">Name:</div>
        <div><?php echo $fullname; ?></div>
        
        <div class="label">Year Level:</div>
        <div><?php echo htmlspecialchars($student['yrlevel']); ?></div>

        <div class="label">College:</div>
        <div style="grid-column: span 3;"><?php echo htmlspecialchars($student['college_name']); ?></div>
        
        <div class="label">Course:</div>
        <div style="grid-column: span 3;"><?php echo htmlspecialchars($student['program_name']); ?></div>
    </div>

    <!-- Cursor Print: Schedule Table -->
    <table>
        <thead>
            <tr>
                <th>Subject Code</th>
                <th>Section</th>
                <th>Subject Title</th>
                <th>Units</th>
                <th>Schedule</th>
                <th>Room</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_units = 0;
            if (empty($schedule_data)): ?>
                <tr><td colspan="6">No subjects enrolled.</td></tr>
            <?php else: 
                foreach ($schedule_data as $sub): 
                    $sched_str = empty($sub['sched_parts']) ? 'TBA' : implode(" / ", $sub['sched_parts']);
                    $total_units += $sub['units'];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($sub['sub_code']); ?></td>
                    <td><?php echo htmlspecialchars($sub['section']); ?></td>
                    <td style="text-align:left;"><?php echo htmlspecialchars($sub['title']); ?></td>
                    <td><?php echo htmlspecialchars($sub['units']); ?></td>
                    <td><?php echo htmlspecialchars($sched_str); ?></td>
                    <td><?php echo htmlspecialchars($sub['room']); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            
            <tr style="background-color: #fafafa; font-weight: bold;">
                <td colspan="3" style="text-align: right;">TOTAL UNITS:</td>
                <td><?php echo $total_units; ?></td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>This is a system-generated report. Date Printed: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

</body>
</html>