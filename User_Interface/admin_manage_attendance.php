<?php
session_start();
include "db_connect.php";

// =========================
// Check Admin Login
// =========================
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// =========================
// Check Student ID
// =========================
if (!isset($_GET['id'])) {
    header("Location: manage_students.php");
    exit();
}

$student_id = mysqli_real_escape_string($conn, $_GET['id']);

// =========================
// Get Student Details
// =========================
$student = mysqli_query(
    $conn,
    "SELECT * FROM students WHERE student_id='$student_id'"
);

if (mysqli_num_rows($student) == 0) {
    die("Student not found.");
}

$student = mysqli_fetch_assoc($student);

// =========================
// Save / Update Attendance
// =========================
$message = "";

if (isset($_POST['save'])) {

    $attendance_date = $_POST['attendance_date'];
    $status = $_POST['status'];

    // Sunday check
    if (date('w', strtotime($attendance_date)) == 0) {
        echo "<script>
    alert('Attendance cannot be marked on Sunday.');
    window.location='admin_manage_attendance.php?id=$student_id';
    </script>";
        exit();
    }

    $holidayCheck = mysqli_query(
        $conn,
        "SELECT id FROM holidays WHERE holiday_date='$attendance_date'"
    );

    if (mysqli_num_rows($holidayCheck) > 0) {
        echo "<script>
    alert('Attendance cannot be marked on Holiday.');
    window.location='admin_manage_attendance.php?id=$student_id';
    </script>";
        exit();
    }

    $holidayCheck = mysqli_query(
        $conn,
        "SELECT id FROM holidays WHERE holiday_date='$attendance_date'"
    );

    if (mysqli_num_rows($holidayCheck) > 0) {

        echo "<script>
        alert('Attendance cannot be marked on Holiday.');
        window.history.back();
    </script>";
        exit();
    }

    $attendance_date = $_POST['attendance_date'];
    $status          = $_POST['status'];

    // Check attendance exists
    $check = mysqli_query($conn, "
    SELECT * FROM attendance_record
    WHERE student_id='$student_id'
    AND attendance_date='$attendance_date'
    ");

    if (mysqli_num_rows($check) > 0) {

        mysqli_query($conn, "
        UPDATE attendance_record
        SET status='$status'
        WHERE student_id='$student_id'
        AND attendance_date='$attendance_date'
        ");

        $message = "<div class='success'>
        Attendance Updated Successfully.
        </div>";
    } else {

        $teacher_id = NULL;

        mysqli_query($conn, "
INSERT INTO attendance_record
(student_id,teacher_id,attendance_date,status)
VALUES
('$student_id',NULL,'$attendance_date','$status')
");

        $message = "<div class='success'>
        Attendance Saved Successfully.
        </div>";
    }
}

// =========================
 // Attendance Summary
 // =========================

/*
 * USE THE SAME SEMESTER CLASS-DAY LOGIC AS count_attendance.php
 *
 * Total Classes =
 * admin start_date -> end_date
 * minus Sundays
 * minus holidays
 *
 * The student's own semester is used.
 * Example: student semester = "5th Semester"
 *          -> semester_settings.semester_name = "5th Semester"
 */

// All semester names used by the system.
$semester_names = [
    '1st Semester',
    '2nd Semester',
    '3rd Semester',
    '4th Semester',
    '5th Semester',
    '6th Semester'
];

// Default total for every semester.
$semester_class_days = array_fill_keys($semester_names, 0);

// Store the semester's configured dates.
$semester_dates = array_fill_keys($semester_names, null);

// Load semester settings exactly like Count Attendance.
// If configured more than once, newest ID is used.
$semester_settings = [];

$settings_result = mysqli_query(
    $conn,
    "SELECT id, semester_name, academic_year, start_date, end_date, status
     FROM semester_settings
     ORDER BY id DESC"
);

if ($settings_result) {

    while ($setting = mysqli_fetch_assoc($settings_result)) {

        $name = trim((string)$setting['semester_name']);

        if (
            isset($semester_class_days[$name]) &&
            !isset($semester_settings[$name])
        ) {
            $semester_settings[$name] = $setting;
        }
    }
}

// Load all holidays once.
$holiday_dates = [];

$holiday_result = mysqli_query(
    $conn,
    "SELECT holiday_date FROM holidays"
);

if ($holiday_result) {

    while ($holiday = mysqli_fetch_assoc($holiday_result)) {

        $holiday_dates[$holiday['holiday_date']] = true;
    }
}

// Calculate total class days for every semester.
foreach ($semester_names as $semester_name) {

    if (!isset($semester_settings[$semester_name])) {
        continue;
    }

    $setting = $semester_settings[$semester_name];

    $start_date = $setting['start_date'];
    $end_date   = $setting['end_date'];

    $semester_dates[$semester_name] = [
        'start' => $start_date,
        'end'   => $end_date,
        'academic_year' => $setting['academic_year']
    ];

    // Invalid date range protection.
    if (
        empty($start_date) ||
        empty($end_date) ||
        $start_date > $end_date
    ) {
        continue;
    }

    $cursor = new DateTime($start_date);
    $end_cursor = new DateTime($end_date);

    while ($cursor <= $end_cursor) {

        $date = $cursor->format('Y-m-d');

        // 0 = Sunday.
        $dayOfWeek = (int)$cursor->format('w');

        // Exclude Sunday and holidays.
        if (
            $dayOfWeek !== 0 &&
            !isset($holiday_dates[$date])
        ) {
            $semester_class_days[$semester_name]++;
        }

        $cursor->modify('+1 day');
    }
}

// IMPORTANT:
// Use the selected student's semester.
// Do NOT use semester_name from the students table.
$student_semester = trim((string)($student['semester'] ?? ''));

$totalClasses = 0;

if (
    isset($semester_class_days[$student_semester]) &&
    isset($semester_settings[$student_semester])
) {
    $totalClasses = (int)$semester_class_days[$student_semester];
}

$present = mysqli_query($conn, "
SELECT COUNT(*) total
FROM attendance_record
WHERE student_id='$student_id'
AND status='Present'
");

$present = mysqli_fetch_assoc($present)['total'];

$absent = mysqli_query($conn, "
SELECT COUNT(*) total
FROM attendance_record
WHERE student_id='$student_id'
AND status='Absent'
");

$absent = mysqli_fetch_assoc($absent)['total'];

$percentage = 0;

if ($totalClasses > 0) {

    $percentage = ($present / $totalClasses) * 100;
}

$status = ($percentage >= 75) ? "Eligible" : "Not Eligible";

//========================
// Delete Attendance
//========================

if (isset($_GET['delete'])) {

    $attendance_id = (int)$_GET['delete'];

    mysqli_query($conn, "
DELETE FROM attendance_record
WHERE attendance_id='$attendance_id'
");

    echo "<script>

    alert('Attendance Deleted');

    window.location='admin_manage_attendance.php?id=$student_id';

    </script>";

    exit();
}


//========================
// Date Filter
//========================

$where = "";

if (isset($_GET['from']) && isset($_GET['to'])) {

    $from = $_GET['from'];
    $to = $_GET['to'];

    if ($from != "" && $to != "") {

        $where = " AND attendance_date
        BETWEEN '$from'
        AND '$to'";
    }
}


$history = mysqli_query($conn, "

SELECT *

FROM attendance_record

WHERE student_id='$student_id'

$where

ORDER BY attendance_date DESC

");

?>


<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Manage Attendance</title>

    <link rel="stylesheet" href="admin_dashboard.css">
    <script src="holidays.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {

            background: #f4f6f9;
            font-family: Arial;

        }

        .container {

            width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);

        }

        h2 {

            text-align: center;
            margin-bottom: 20px;

        }

        .info {

            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;

        }

        .card {

            background: #6080e7;
            padding: 12px;
            border-left: 5px solid #e93f0bfb;
            font-size: 16px;

        }

        .summary {

            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin: 25px 0;

        }

        .box {

            background: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;

        }

        .box h3 {

            margin: 0;

        }

        .box p {

            font-size: 22px;
            font-weight: bold;
            margin-top: 10px;

        }

        form {

            margin-top: 30px;

        }

        table {

            width: 100%;

        }

        td {

            padding: 12px;

        }

        input[type=date] {

            width: 100%;
            padding: 10px;

        }

        select {

            width: 100%;
            padding: 10px;

        }

        .btn {

            padding: 12px 25px;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 6px;
            font-size: 15px;

        }

        .save {

            background: #28a745;

        }

        .back {

            background: #007bff;

        }

        .success {

            background: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;

        }

        .eligible {

            color: green;
            font-weight: bold;

        }

        .noteligible {

            color: red;
            font-weight: bold;

        }

        .history {
            margin-top: 40px;
        }

        .history table {
            width: 100%;
            border-collapse: collapse;
        }

        .history th {
            background: #007bff;
            color: white;
            padding: 12px;
        }

        .history td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .present {
            color: green;
            font-weight: bold;
        }

        .absent {
            color: red;
            font-weight: bold;
        }

        .editBtn {

            background: #ffc107;
            color: black;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 5px;

        }

        .deleteBtn {

            background: #dc3545;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;

        }

        .filter {

            margin-top: 30px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;

        }

        .filter input {

            padding: 10px;

        }

        .filter button {

            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;

        }
    </style>

</head>

<body>

    <div class="container">

        <h2>Manage Student Attendance</h2>

        <?php echo $message; ?>

        <div class="info">

            <div class="card">
                <b>Student ID</b><br>
                <?php echo $student['student_id']; ?>
            </div>

            <div class="card">
                <b>Student Name</b><br>
                <?php echo $student['student_name']; ?>
            </div>

            <div class="card">
                <b>Department</b><br>
                <?php echo $student['branch_name']; ?>
            </div>

            <div class="card">
                <b>Semester</b><br>
                <?php echo $student['semester']; ?>
            </div>

        </div>

        <div class="summary">

            <div class="box">
                <h3>Total Classes</h3>
                <p><?php echo $totalClasses; ?></p>
            </div>

            <div class="box">
                <h3>Present</h3>
                <p><?php echo $present; ?></p>
            </div>

            <div class="box">
                <h3>Absent</h3>
                <p><?php echo $absent; ?></p>
            </div>

            <div class="box">
                <h3>Percentage</h3>
                <p><?php echo number_format($percentage, 2); ?>%</p>
            </div>

            <div class="box">
                <h3>Status</h3>

                <p class="<?php echo ($percentage >= 75) ? 'eligible' : 'noteligible'; ?>">

                    <?php echo $status; ?>

                </p>

            </div>

        </div>

        <form method="POST">

            <table>

                <tr>

                    <td width="30%">
                        Attendance Date
                    </td>

                    <td>

                        <input
                            type="date"
                            name="attendance_date"
                            id="attendance_date"
                            required
                            value="<?php echo date('Y-m-d'); ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                            onkeydown="return false;">

                    </td>

                </tr>

                <tr>

                    <td>
                        Attendance Status
                    </td>

                    <td>

                        <select name="status">

                            <option value="Present">Present</option>

                            <option value="Absent">Absent</option>

                        </select>

                    </td>

                </tr>

                <tr>

                    <td colspan="2" align="center">

                        <button
                            class="btn save"
                            name="save">

                            Save / Update Attendance

                        </button>

                        <a href="manage_students1.php" class="btn back">
                            Back
                        </a>
                    </td>
                </tr>
            </table>
        </form>

        <hr>
        <div class="history">
            <h2>Attendance History</h2>
            <form method="GET" class="filter">
                <input type="hidden"
                    name="id"
                    value="<?php echo $student_id; ?>">
                <label>From</label>
                <input
                    type="date"
                    name="from"
                    value="<?php echo $_GET['from'] ?? ''; ?>">

                <label>To</label>

                <input
                    type="date"
                    name="to"
                    value="<?php echo $_GET['to'] ?? ''; ?>" max="<?php echo date('Y-m-d'); ?>">

                <button>

                    Search

                </button>

            </form>

            <table>

                <tr>

                    <th>SL</th>

                    <th>Date</th>

                    <th>Status</th>

                    <th>Action</th>

                </tr>

                <?php

                $sl = 1;

                if (mysqli_num_rows($history) == 0) {
                    ?>
                    <tr>
                        <td colspan="4" style="text-align:center; color:#6b7280; font-weight:bold; padding:20px;">
                            Result not found
                        </td>
                    </tr>
                    <?php
                } else {
                    while ($row = mysqli_fetch_assoc($history)) {
                    ?>

                        <tr>

                            <td><?php echo $sl++; ?></td>

                            <td><?php echo $row['attendance_date']; ?></td>

                            <td>

                                <span class="<?php echo strtolower($row['status']); ?>">

                                    <?php echo $row['status']; ?>

                                </span>

                            </td>

                            <td>

                                <a
                                    class="deleteBtn"

                                    onclick="return confirm('Delete Attendance?')"

                                    href="admin_manage_attendance.php?id=<?php echo $student_id; ?>&delete=<?php echo $row['attendance_id']; ?>">

                                    Delete

                                </a>

                            </td>

                        </tr>

                    <?php
                    }
                }

                ?>

            </table>

        </div>

    </div>

    <script>
        let holidays = [];

        document.addEventListener("DOMContentLoaded", function() {

            const attendanceDate = document.getElementById("attendance_date");
            const attendanceForm = document.querySelector("form[method='POST']");
            const saveBtn = document.querySelector("button[name='save']");

            fetch("get_holidays.php")
                .then(res => res.json())
                .then(data => {
                    holidays = data;
                });

            function checkDate() {

                const selected = attendanceDate.value;

                if (selected == "") {
                    saveBtn.disabled = true;
                    return false;
                }

                const d = new Date(selected);
                d.setHours(0, 0, 0, 0);

                const today = new Date();
                today.setHours(0, 0, 0, 0);

                // Future Date
                if (d > today) {

                    Swal.fire({
                        icon: 'warning',
                        title: 'Future Date',
                        text: 'Future dates are not allowed.',
                        confirmButtonColor: '#3085d6'
                    });

                    attendanceDate.value = "";
                    saveBtn.disabled = true;
                    return false;
                }

                // Sunday
                if (d.getDay() === 0) {

                    Swal.fire({
                        icon: 'warning',
                        title: 'Sunday',
                        text: 'Attendance cannot be marked on Sunday.'
                    });

                    attendanceDate.value = "";
                    saveBtn.disabled = true;
                    return false;
                }

                // Holiday
                if (holidays.includes(selected)) {

                    Swal.fire({
                        icon: 'warning',
                        title: 'Holiday',
                        text: 'Attendance cannot be marked on a Holiday.'
                    });

                    attendanceDate.value = "";
                    saveBtn.disabled = true;
                    return false;
                }

                saveBtn.disabled = false;
                return true;
            }

            attendanceDate.addEventListener("change", checkDate);

            attendanceForm.addEventListener("submit", function(e) {

                if (!checkDate()) {
                    e.preventDefault();
                }

            });

        });
    </script>

</body>

</html>