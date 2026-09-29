<?php
session_start();

// Show errors while debugging — remove or disable in production
ini_set('display_errors', 1);
error_reporting(E_ALL);

include "db_connect.php";

if (!isset($_SESSION['student_id'])) {
    die("Unauthorized");
}

$student_id = $_SESSION['student_id'];

if (!$conn) {
    die("DB Connection error: " . mysqli_connect_error());
}

// Get student details
$sql = "SELECT student_name, semester, branch_name
        FROM students
        WHERE student_id=?";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Prepare failed (student): " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "s", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    die("Student not found for ID: " . htmlspecialchars($student_id));
}

// Send download headers only after we've confirmed data exists
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=My_Attendance.xls");

echo "Student ID\t";
echo "Student Name\t";
echo "Branch\t";
echo "Semester\t";
echo "Date\t";
echo "Status\n";

$sql = "SELECT attendance_date,status
        FROM attendance_record
        WHERE student_id=?
        ORDER BY attendance_date";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    // If headers already sent, output a simple message in the file
    die("Prepare failed (attendance): " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "s", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);


// Check if attendance records exist
if (mysqli_num_rows($result) == 0) {
    echo "<script>
            alert('No attendance records found.');
            window.history.back();
          </script>";
    exit();
}

while ($row = mysqli_fetch_assoc($result)) {
    echo $student_id . "\t";
    echo $student['student_name'] . "\t";
    echo $student['branch_name'] . "\t";
    echo $student['semester'] . "\t";
    echo $row['attendance_date'] . "\t";
    echo $row['status'] . "\n";
}

exit();
?>