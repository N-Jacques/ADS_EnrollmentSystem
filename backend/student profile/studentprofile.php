<?php
// Database Connection Configuration (Common XAMPP defaults)
$servername = "localhost";
$username = "root";         // Default XAMPP MySQL username
$password = "";             // Default XAMPP MySQL password (blank)
$dbname = "adsDB";       // <-- CHOOSE YOUR DATABASE NAME HERE

// Initialize variables for the view
$student_id_to_fetch = '202336039'; // <<< REMEMBER TO REPLACE WITH YOUR ACTUAL SESSION/GET/POST LOGIC
$student_name = '';
$program_name = '';
$yrlevel = '';
$status_name = '';
$student_id = $student_id_to_fetch;
$current_sem_id = null;
$subjects_result = null;

// Attempt to establish a database connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    // You might want to display a fatal error message on the page here
} else {
    // --- A. FETCH STUDENT INFORMATION (No change) ---
    // ... (Student fetch code remains here) ...
    $sql_student = "SELECT s.student_id
                           , s.firstname
						   , s.middlename
						   , s.lastname
                           , s.yrlevel
						   , p.program_name
						   , st.status_name
                    FROM students s
						JOIN programs p 
							ON s.program_id = p.program_id
						JOIN status st 
							ON s.status_id = st.status_id
                    WHERE s.student_id = ?";

    $stmt_student = $conn->prepare($sql_student);
    $stmt_student->bind_param("s", $student_id_to_fetch);
    $stmt_student->execute();
    $result_student = $stmt_student->get_result();

    if ($result_student->num_rows > 0) {
        $row = $result_student->fetch_assoc();
        $full_name = trim($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']);
        $student_name = htmlspecialchars($full_name);
        $program_name = htmlspecialchars($row['program_name']);
        $yrlevel = 'Year ' . htmlspecialchars($row['yrlevel']);
        $status_name = htmlspecialchars($row['status_name']);
        $student_id = htmlspecialchars($row['student_id']);
    }
    $stmt_student->close();

    // --- B. DETERMINE CURRENT ACTIVE SEMESTER ID (No change) ---
    $sql_sem = "SELECT sem_id
                FROM semester
                WHERE CURRENT_DATE() >= date_start AND CURRENT_DATE() <= date_end
                LIMIT 1";

    $result_sem = $conn->query($sql_sem);
    if ($result_sem && $result_sem->num_rows > 0) {
        $sem_row = $result_sem->fetch_assoc();
        $current_sem_id = $sem_row['sem_id'];
    }


	// ----------------------------------------------------------------
    // --- C. FETCH CURRENTLY ENROLLED SUBJECTS (REVISED LOGIC 2) ---
    // Assuming subjects_taken contains: student_id, sub_code, and sem_id
    // ----------------------------------------------------------------
    if ($current_sem_id) {
        $sql_subjects = "
            SELECT 
                ST.sub_code
				, T.title AS subject_title
            FROM subjects_taken AS ST
				JOIN subjects AS T 
					ON ST.sub_code = T.sub_code
            WHERE 
                ST.student_id = ?
                AND ST.sem_id = ?  /* <--- KEY CHANGE: Checking sem_id directly in subjects_taken */
            ORDER BY 
                ST.sub_code";

        $stmt_subjects = $conn->prepare($sql_subjects);
        
        // --- ADDED ERROR CHECKING ---
        if ($stmt_subjects === false) {
            // If preparation fails, output the specific MySQL error
            die("MySQL Prepare Error (C): " . $conn->error); 
        }
        // -----------------------------

        $stmt_subjects->bind_param("ss", $student_id_to_fetch, $current_sem_id);
        $stmt_subjects->execute();
        
        // ASSIGN THE RESULT OBJECT TO $subjects_result for use in the HTML file
        $subjects_result = $stmt_subjects->get_result(); 
        $stmt_subjects->close();
    }

    $conn->close();
}
?>
