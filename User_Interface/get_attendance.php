<?php
session_start();
include "db_connect.php";

header("Content-Type: application/json");

if (!isset($_SESSION['student_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Student not logged in"
    ]);
    exit();
}

$student_id = $_SESSION['student_id'];

$sql = "SELECT attendance_date,status
        FROM attendance_record
        WHERE student_id=?
        ORDER BY attendance_date";

$stmt = mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param($stmt,"s",$student_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data=[];

while($row=mysqli_fetch_assoc($result)){
    $data[]=$row;
}

echo json_encode([
    "success"=>true,
    "attendance"=>$data
]);
?>