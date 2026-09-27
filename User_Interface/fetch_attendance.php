<?php
session_start();
include "db_connect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(["success" => false, "message" => "Teacher not logged in"]);
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$date = isset($_GET['date']) ? $_GET['date'] : '';

if ($date === '') {
    echo json_encode(["success" => false, "message" => "Date required"]);
    exit();
}

$sql = "SELECT student_id, status FROM attendance_record WHERE teacher_id = ? AND attendance_date = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    exit();
}

mysqli_stmt_bind_param($stmt, "ss", $teacher_id, $date);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$data = [];
while ($row = mysqli_fetch_assoc($res)) {
    $data[$row['student_id']] = $row['status'];
}

$is_locked = !empty($data);

mysqli_stmt_close($stmt);

echo json_encode([
    "success" => true,
    "attendance_record" => $data,
    "locked" => $is_locked
]);

?>
