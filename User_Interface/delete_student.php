<?php
session_start();
include "db_connect.php";

// Check Admin Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Check Student ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_students1.php");
    exit();
}

$student_id = mysqli_real_escape_string($conn, $_GET['id']);

// Check student exists
$check = mysqli_query($conn, "SELECT * FROM students WHERE student_id='$student_id'");

if (mysqli_num_rows($check) == 0) {

    echo "<script>
            alert('Student Not Found');
            window.location='manage_students1.php';
          </script>";
    exit();
}

// Delete Attendance Records First
mysqli_query($conn, "DELETE FROM attendance_record WHERE student_id='$student_id'");

// Delete Student
$delete = mysqli_query($conn, "DELETE FROM students WHERE student_id='$student_id'");

if ($delete) {

    echo "<script>
            alert('Student Deleted Successfully');
            window.location='manage_students1.php';
          </script>";
} else {

    echo "<script>
            alert('Unable to Delete Student');
            window.location='manage_students1.php';
          </script>";
}
