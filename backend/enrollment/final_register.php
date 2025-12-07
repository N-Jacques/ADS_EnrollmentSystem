<?php
header("Content-Type: application/json");

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../connection.php'; // adjust path if needed


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "error" => "Invalid request method"]);
    exit;
}


$raw = file_get_contents("php://input");
$data = json_decode($raw, true);


if (!isset($data['student_id'], $data['sem_id'], $data['subjects'])) {
    echo json_encode(["success" => false, "error" => "Missing fields"]);
    exit;
}

$student_id = $conn->real_escape_string($data['student_id']);
$sem_id = $conn->real_escape_string($data['sem_id']);
$subjects = $data['subjects'];


$checkStudent = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$checkStudent->bind_param("s", $student_id);
$checkStudent->execute();
$resultStudent = $checkStudent->get_result();

if ($resultStudent->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "error" => "Student does not exist. Sent ID: $student_id"
    ]);
    exit;
}


$enlistment_id = rand(100000, 999999);


$stmt = $conn->prepare("INSERT INTO enlistment (enlistment_id, student_id, sem_id, date_created) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("sss", $enlistment_id, $student_id, $sem_id);

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "error" => "Failed to save enlistment header"]);
    exit;
}


$stmt2 = $conn->prepare("INSERT INTO enlisted_subjects (enlistment_id, sub_code) VALUES (?, ?)");

foreach ($subjects as $sub) {

    $sub_code = $conn->real_escape_string($sub['code']);


    $checkSub = $conn->prepare("SELECT * FROM subjects WHERE sub_code = ?");
    $checkSub->bind_param("s", $sub_code);
    $checkSub->execute();
    $resSub = $checkSub->get_result();

    if ($resSub->num_rows === 0) {
        echo json_encode([
            "success" => false,
            "error" => "Subject '$sub_code' does not exist in the database"
        ]);
        exit;
    }


    $stmt2->bind_param("ss", $enlistment_id, $sub_code);
    if (!$stmt2->execute()) {
        echo json_encode(["success" => false, "error" => "Failed inserting subject $sub_code"]);
        exit;
    }
}


echo json_encode([
    "success" => true,
    "message" => "Enrollment saved successfully!",
    "enlistment_id" => $enlistment_id
]);
exit;
?>
