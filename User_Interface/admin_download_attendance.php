<?php
session_start();
include "db_connect.php";

// Uncomment if admin login is required
/*
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized Access");
}
*/

$department = $_GET['department'] ?? "";
$semester   = $_GET['semester'] ?? "";
$from       = $_GET['from'] ?? "";
$to         = $_GET['to'] ?? "";

// Build Query
$sql = "SELECT
            a.student_id,
            s.student_name,
            s.branch_name,
            s.semester,
            a.attendance_date,
            a.status
        FROM attendance_record a
        INNER JOIN students s
            ON a.student_id = s.student_id
        WHERE 1=1";

if (!empty($department)) {
    $department = mysqli_real_escape_string($conn, $department);
    $sql .= " AND s.branch_name='$department'";
}

if (!empty($semester)) {
    $semester = mysqli_real_escape_string($conn, $semester);
    $sql .= " AND s.semester='$semester'";
}

if (!empty($from)) {
    $from = mysqli_real_escape_string($conn, $from);
    $sql .= " AND a.attendance_date >= '$from'";
}

if (!empty($to)) {
    $to = mysqli_real_escape_string($conn, $to);
    $sql .= " AND a.attendance_date <= '$to'";
}

$sql .= " ORDER BY
            a.attendance_date DESC,
            s.branch_name ASC,
            s.semester ASC,
            s.student_name ASC";

$result = mysqli_query($conn, $sql);

// Check if attendance records exist
if (!$result || mysqli_num_rows($result) == 0) {
    echo "<script>
            alert('No attendance records found for the selected filters.');
            window.history.back();
          </script>";
    exit();
}

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Admin_Attendance_Report.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Report Title
echo "ADMIN ATTENDANCE REPORT\n\n";

// Applied Filters
echo "Department\t" . ($department == "" ? "All" : $department) . "\n";
echo "Semester\t" . ($semester == "" ? "All" : $semester) . "\n";
echo "From Date\t" . ($from == "" ? "-" : $from) . "\n";
echo "To Date\t" . ($to == "" ? "-" : $to) . "\n\n";

// Table Header
echo "SL No.\t";
echo "Student ID\t";
echo "Student Name\t";
echo "Department\t";
echo "Semester\t";
echo "Attendance Date\t";
echo "Status\n";

$sl = 1;

while ($row = mysqli_fetch_assoc($result)) {

    echo $sl++ . "\t";

    echo str_replace(array("\t", "\n", "\r"), " ", $row['student_id']) . "\t";

    echo str_replace(array("\t", "\n", "\r"), " ", $row['student_name']) . "\t";

    echo str_replace(array("\t", "\n", "\r"), " ", $row['branch_name']) . "\t";

    echo str_replace(array("\t", "\n", "\r"), " ", $row['semester']) . "\t";

    echo date("d-m-Y", strtotime($row['attendance_date'])) . "\t";

    echo str_replace(array("\t", "\n", "\r"), " ", $row['status']) . "\n";
}

exit();
