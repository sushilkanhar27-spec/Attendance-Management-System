<?php
session_start();
include "db_connect.php";
// Require teacher login
if (!isset($_SESSION['teacher_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo "Unauthorized: Teacher not logged in.";
    exit();
}

$teacher_id = mysqli_real_escape_string($conn, $_SESSION['teacher_id']);

// Get teacher branch
$q = mysqli_prepare($conn, "SELECT branch_name FROM teacher WHERE teacher_id = ?");
if ($q) {
    mysqli_stmt_bind_param($q, "s", $teacher_id);
    mysqli_stmt_execute($q);
    $res = mysqli_stmt_get_result($q);
    $t = mysqli_fetch_assoc($res);
    mysqli_stmt_close($q);
    $teacher_branch = $t['branch_name'] ?? '';
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo "Database error: " . htmlspecialchars(mysqli_error($conn));
    exit();
}

$from = $_GET['from'] ?? "";
$to = $_GET['to'] ?? "";
$semester = $_GET['semester'] ?? "";

$branch_esc = mysqli_real_escape_string($conn, $teacher_branch);
$sql = "SELECT
a.student_id,
s.student_name,
s.semester,
a.attendance_date,
a.status
FROM attendance_record a
INNER JOIN students s
ON a.student_id = s.student_id
WHERE s.branch_name = '" . $branch_esc . "'";

if ($from !== "") {
    $sql .= " AND a.attendance_date >= '" . mysqli_real_escape_string($conn, $from) . "'";
}

if ($to !== "") {
    $sql .= " AND a.attendance_date <= '" . mysqli_real_escape_string($conn, $to) . "'";
}

if ($semester !== "") {
    $sql .= " AND s.semester='" . mysqli_real_escape_string($conn, $semester) . "'";
}

$sql .= " ORDER BY a.attendance_date DESC";

$result = mysqli_query($conn, $sql);

// Check if attendance records exist
if (!$result || mysqli_num_rows($result) == 0) {
    echo "<script>
            alert('No attendance records found.');
            window.history.back();
          </script>";
    exit();
}

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Attendance_Report.xls");

echo "SL No.\t";
echo "Student ID\t";
echo "Student Name\t";
echo "Semester\t";
echo "Attendance Date\t";
echo "Status\n";

$sl = 1;

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // sanitize values to remove tabs/newlines which would break the TSV
        $student_id = str_replace(["\t", "\n", "\r"], ' ', $row['student_id']);
        $student_name = str_replace(["\t", "\n", "\r"], ' ', $row['student_name']);
        $semester_val = str_replace(["\t", "\n", "\r"], ' ', $row['semester']);
        $attendance_date = str_replace(["\t", "\n", "\r"], ' ', $row['attendance_date']);
        $status = str_replace(["\t", "\n", "\r"], ' ', $row['status']);

        echo $sl++ . "\t";
        echo $student_id . "\t";
        echo $student_name . "\t";
        echo $semester_val . "\t";
        echo $attendance_date . "\t";
        echo $status . "\n";
    }
}

exit;
