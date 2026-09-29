<?php
session_start();
include "db_connect.php";

// Uncomment if admin login is required
/*
if (!isset($_SESSION['admin_id'])) {
    echo "<tr><td colspan='7'>Unauthorized Access</td></tr>";
    exit();
}
*/

$department = $_GET['department'] ?? "";
$semester   = $_GET['semester'] ?? "";
$from       = $_GET['from'] ?? "";
$to         = $_GET['to'] ?? "";

// Base Query
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

// Department Filter
if (!empty($department)) {
    $department = mysqli_real_escape_string($conn, $department);
    $sql .= " AND s.branch_name='$department'";
}

// Semester Filter
if (!empty($semester)) {
    $semester = mysqli_real_escape_string($conn, $semester);
    $sql .= " AND s.semester='$semester'";
}

// From Date
if (!empty($from)) {
    $from = mysqli_real_escape_string($conn, $from);
    $sql .= " AND a.attendance_date >= '$from'";
}

// To Date
if (!empty($to)) {
    $to = mysqli_real_escape_string($conn, $to);
    $sql .= " AND a.attendance_date <= '$to'";
}

$sql .= " ORDER BY a.attendance_date DESC,
                  s.branch_name ASC,
                  s.semester ASC,
                  s.student_name ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "<tr>
            <td colspan='7'>
                Database Error : " . htmlspecialchars(mysqli_error($conn)) . "
            </td>
          </tr>";
    exit();
}

if (mysqli_num_rows($result) == 0) {

    echo "<tr>
            <td colspan='7'>
                No Attendance Record Found
            </td>
          </tr>";
    exit();
}

$sl = 1;

while ($row = mysqli_fetch_assoc($result)) {

    $statusClass = strtolower($row['status']) == "present"
        ? "present"
        : "absent";

    echo "<tr>";

    echo "<td>" . $sl++ . "</td>";

    echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";

    echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";

    echo "<td>" . htmlspecialchars($row['branch_name']) . "</td>";

    echo "<td>" . htmlspecialchars($row['semester']) . "</td>";

    echo "<td>" . date("d-m-Y", strtotime($row['attendance_date'])) . "</td>";

    echo "<td class='$statusClass'>"
        . htmlspecialchars($row['status']) .
        "</td>";

    echo "</tr>";
}
