<?php

//start session
session_start();

//database credentials
$host = 'localhost';
$dbname = 'adsDB';
$username = 'root';
$password = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentID = trim($_POST['studentid']);
    $passKey = trim($_POST['passkey_']);
    /*debugging line - remove in final
        echo "Trying to find student ID: [$studentID]<br>";
        echo "Trying to find passkey: [$passKey]<br>";
    */

    try {
        //connect to db
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        //handle db error
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        //prepare query then execute
        $stmt = $conn->prepare("SELECT * FROM credentials WHERE student_id = :studentid
                                AND passkey = :passkey_");
        $stmt->bindParam(":studentid", $studentID);
        $stmt->bindParam(":passkey_", $passKey);
        $stmt->execute();

        //check if student account exists
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            
            // Store data in session 
            $_SESSION['student_id'] = $row['student_id'];
            $_SESSION['user_role'] = 'student'; // Helpful for role-based access checks


            // Redirect to student profile page (html)
            header("Location: ../../frontend/studentProfile.html?id=" . urlencode($studentID));
            /*debugging line
            echo "Student found: " . $row['student_id'];*/
            exit();
        } else {
            // Student not found, show error message
            /*debugging line
            echo "Invalid Credentials! RowCount: " . $stmt->rowCount();*/
            echo "<script>
                alert('Invalid Credentials! Please try again.');
                window.location.href = '../../frontend/login_wPass.html';
            </script>";
            exit();
        }
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>