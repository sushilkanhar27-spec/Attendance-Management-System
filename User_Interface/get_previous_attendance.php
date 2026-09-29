<?php
session_start();
include "db_connect.php";



// Ensure teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    echo "<tr><td colspan='6'>Unauthorized: Teacher not logged in.</td></tr>";
    exit();
}

$teacher_id = mysqli_real_escape_string($conn, $_SESSION['teacher_id']);

// Fetch teacher branch
$q = mysqli_prepare($conn, "SELECT branch_name FROM teacher WHERE teacher_id = ?");
if ($q) {
    mysqli_stmt_bind_param($q, "s", $teacher_id);
    mysqli_stmt_execute($q);
    $res = mysqli_stmt_get_result($q);
    $t = mysqli_fetch_assoc($res);
    mysqli_stmt_close($q);
    $teacher_branch = $t['branch_name'] ?? '';
} else {
    echo "<tr><td colspan='6'>Database error: " . htmlspecialchars(mysqli_error($conn)) . "</td></tr>";
    exit();
}

$from = isset($_GET['from']) ? $_GET['from'] : "";
$to = isset($_GET['to']) ? $_GET['to'] : "";
$semester = isset($_GET['semester']) ? $_GET['semester'] : "";

// Build safe query using escaped values (dates and semester are simple values)
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
        WHERE s.branch_name='" . $branch_esc . "'";

if ($from !== "") {
    $from_esc = mysqli_real_escape_string($conn, $from);
    $sql .= " AND a.attendance_date >= '" . $from_esc . "'";
}

if ($to !== "") {
    $to_esc = mysqli_real_escape_string($conn, $to);
    $sql .= " AND a.attendance_date <= '" . $to_esc . "'";
}

if ($semester !== "") {
    $semester_esc = mysqli_real_escape_string($conn, $semester);
    $sql .= " AND s.semester='" . $semester_esc . "'";
}

$sql .= " ORDER BY a.attendance_date DESC, s.student_name ASC";

$result = mysqli_query($conn, $sql);

$sl = 1;

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $color = ($row['status'] === "Present") ? "green" : "red";
        echo "<tr>";
        echo "<td>" . $sl++ . "</td>";
        echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
        echo "<td>" . htmlspecialchars($row['attendance_date']) . "</td>";
        echo "<td style='color:$color;font-weight:bold'>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>No Attendance Found</td></tr>";
}
?>