<?php
session_start();
require_once 'db_connect.php';
require_once 'attendance_functions.php';

if (!isset($_SESSION['student_id']) || trim($_SESSION['student_id']) === '') {
    http_response_code(403);
    exit('Student not logged in.');
}

$studentId = trim($_SESSION['student_id']);
$semester = getActiveSemester($conn);

if (!$semester) {
    http_response_code(404);
    exit('No active semester found.');
}

$stmt = mysqli_prepare($conn, "
    SELECT student_id, student_name, branch_name, semester
    FROM students
    WHERE student_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, 's', $studentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$student) {
    http_response_code(404);
    exit('Student not found.');
}

$holidays = [];
$stmt = mysqli_prepare($conn, "
    SELECT holiday_date, holiday_name
    FROM holidays
    WHERE holiday_date BETWEEN ? AND ?
");
mysqli_stmt_bind_param($stmt, 'ss', $semester['start_date'], $semester['end_date']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $holidays[$row['holiday_date']] = $row['holiday_name'];
}
mysqli_stmt_close($stmt);

$attendance = [];
$stmt = mysqli_prepare($conn, "
    SELECT attendance_date, status
    FROM attendance_record
    WHERE student_id = ?
      AND attendance_date BETWEEN ? AND ?
");
mysqli_stmt_bind_param($stmt, 'sss', $studentId, $semester['start_date'], $semester['end_date']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $attendance[$row['attendance_date']] = $row['status'];
}
mysqli_stmt_close($stmt);

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="Student_Attendance_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $studentId) . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
?>
<table border="1">
    <tr>
        <th colspan="9">Student Attendance Report</th>
    </tr>
    <tr>
        <td><b>Student ID</b></td><td><?= htmlspecialchars($student['student_id']) ?></td>
        <td><b>Student Name</b></td><td><?= htmlspecialchars($student['student_name']) ?></td>
        <td><b>Department</b></td><td><?= htmlspecialchars($student['branch_name']) ?></td>
        <td><b>Semester</b></td><td><?= htmlspecialchars($student['semester']) ?></td>
        <td></td>
    </tr>
    <tr>
        <td><b>Academic Year</b></td><td><?= htmlspecialchars($semester['academic_year']) ?></td>
        <td><b>Semester Start</b></td><td><?= htmlspecialchars($semester['start_date']) ?></td>
        <td><b>Semester End</b></td><td><?= htmlspecialchars($semester['end_date']) ?></td>
        <td colspan="3"></td>
    </tr>
    <tr>
        <th>Sl. No.</th>
        <th>Student ID</th>
        <th>Student Name</th>
        <th>Department</th>
        <th>Semester</th>
        <th>Academic Year</th>
        <th>Attendance Date</th>
        <th>Day</th>
        <th>Status</th>
    </tr>
<?php
$start = new DateTime($semester['start_date']);
$end = new DateTime($semester['end_date']);
$sl = 1;

while ($start <= $end) {
    $date = $start->format('Y-m-d');
    $dayName = $start->format('l');
    $status = '';

    if ((int)$start->format('w') === 0) {
        $status = 'Sunday';
    } elseif (isset($holidays[$date])) {
        $status = 'Holiday: ' . $holidays[$date];
    } elseif ($date > date('Y-m-d')) {
        $status = 'Upcoming';
    } elseif (isset($attendance[$date])) {
        $status = ucfirst(strtolower($attendance[$date]));
    } else {
        $status = 'Not Marked';
    }

    echo '<tr>';
    echo '<td>' . $sl++ . '</td>';
    echo '<td>' . htmlspecialchars($student['student_id']) . '</td>';
    echo '<td>' . htmlspecialchars($student['student_name']) . '</td>';
    echo '<td>' . htmlspecialchars($student['branch_name']) . '</td>';
    echo '<td>' . htmlspecialchars($student['semester']) . '</td>';
    echo '<td>' . htmlspecialchars($semester['academic_year']) . '</td>';
    echo '<td>' . htmlspecialchars($date) . '</td>';
    echo '<td>' . htmlspecialchars($dayName) . '</td>';
    echo '<td>' . htmlspecialchars($status) . '</td>';
    echo '</tr>';

    $start->modify('+1 day');
}
?>
</table>
