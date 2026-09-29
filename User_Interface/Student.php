<?php
session_start();

require_once 'db_connect.php';
require_once 'attendance_functions.php';

if (!isset($_SESSION['student_id']) || trim($_SESSION['student_id']) === '') {
    header('Location: login.php');
    exit;
}

$studentId = trim($_SESSION['student_id']);

/* -------------------------------------------------------
   1. Logged-in student details
------------------------------------------------------- */
$stmt = mysqli_prepare($conn, "
    SELECT student_id, student_name, mobile, branch_name, semester
    FROM students
    WHERE student_id = ?
    LIMIT 1
");

if (!$stmt) {
    die('Database error: ' . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt, 's', $studentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$student) {
    session_destroy();
    header('Location: login.php');
    exit;
}

/* -------------------------------------------------------
   2. Active semester from semester_settings
------------------------------------------------------- */
$semester = getActiveSemester($conn);

if (!$semester) {
    die('No active semester is configured by the administrator.');
}

$semesterStart = $semester['start_date'];
$semesterEnd   = $semester['end_date'];
$today         = date('Y-m-d');

// For attendance calculation, future days are not class days yet.
$calculationEnd = ($today < $semesterEnd) ? $today : $semesterEnd;
$semesterClosed = ($today > $semesterEnd);

/* -------------------------------------------------------
   3. Holidays inside this semester
------------------------------------------------------- */
$holidays = [];

$stmt = mysqli_prepare($conn, "
    SELECT holiday_date, holiday_name
    FROM holidays
    WHERE holiday_date BETWEEN ? AND ?
    ORDER BY holiday_date
");

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ss', $semesterStart, $semesterEnd);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $holidays[$row['holiday_date']] = $row['holiday_name'];
    }
    mysqli_stmt_close($stmt);
}

/* -------------------------------------------------------
   4. Student attendance inside active semester
------------------------------------------------------- */
$attendance = [];

$stmt = mysqli_prepare($conn, "
    SELECT attendance_date, status
    FROM attendance_record
    WHERE student_id = ?
      AND attendance_date BETWEEN ? AND ?
    ORDER BY attendance_date
");

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'sss', $studentId, $semesterStart, $semesterEnd);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $attendance[$row['attendance_date']] = $row['status'];
    }
    mysqli_stmt_close($stmt);
}

/* -------------------------------------------------------
   5. Generate semester months automatically
------------------------------------------------------- */
$months = [];
$monthCursor = new DateTime(date('Y-m-01', strtotime($semesterStart)));
$lastMonth   = new DateTime(date('Y-m-01', strtotime($semesterEnd)));

while ($monthCursor <= $lastMonth) {
    $months[] = [
        'year' => (int)$monthCursor->format('Y'),
        'month' => (int)$monthCursor->format('n'),
        'label' => $monthCursor->format('F Y')
    ];

    $monthCursor->modify('+1 month');
}

/* -------------------------------------------------------
   6. Calculate TOTAL CLASS DAYS for the FULL SEMESTER
      Semester Start → Semester End
      excluding Sundays and database holidays.
------------------------------------------------------- */

$totalClassDays = 0;
$presentDays = 0;
$absentDays = 0;

/*
 * Total class days are calculated for the
 * complete semester, NOT only up to today.
 */
$cursor = new DateTime($semesterStart);
$endCursor = new DateTime($semesterEnd);

while ($cursor <= $endCursor) {

    $date = $cursor->format('Y-m-d');

    // 0 = Sunday
    $dayOfWeek = (int)$cursor->format('w');

    /*
     * Sunday and holidays are not class days.
     */
    if (
        $dayOfWeek !== 0 &&
        !isset($holidays[$date])
    ) {
        $totalClassDays++;
    }

    $cursor->modify('+1 day');
}


/*
 * Present and absent are counted only from
 * attendance records that have actually been saved.
 */
foreach ($attendance as $date => $status) {

    /*
     * Ignore Sundays and holidays.
     */
    $attendanceDate = new DateTime($date);

    if (
        (int)$attendanceDate->format('w') === 0 ||
        isset($holidays[$date])
    ) {
        continue;
    }

    /*
     * Do not count future attendance.
     */
    if ($date > $today) {
        continue;
    }

    $status = strtolower(trim((string)$status));

    if ($status === 'present') {
        $presentDays++;
    } elseif ($status === 'absent') {
        $absentDays++;
    }
}


/*
 * Attendance percentage:
 *
 * Present Days ÷ Total Semester Class Days × 100
 */
$attendancePercentage = $totalClassDays > 0
    ? round(($presentDays / $totalClassDays) * 100, 2)
    : 0;
/* -------------------------------------------------------
   7. Complete calendar data for JavaScript
------------------------------------------------------- */
$calendarData = [
    'student' => [
        'student_id' => $student['student_id'],
        'student_name' => $student['student_name'],
        'mobile' => $student['mobile'],
        'branch_name' => $student['branch_name'],
        'semester' => $student['semester']
    ],
    'semester' => [
        'semester_name' => $semester['semester_name'],
        'academic_year' => $semester['academic_year'],
        'start_date' => $semesterStart,
        'end_date' => $semesterEnd,
        'status' => $semester['status']
    ],
    'today' => $today,
    'months' => $months,
    'holidays' => $holidays,
    'attendance' => $attendance
];

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance</title>
    <link rel="stylesheet" href="Student1.css">
    <link rel="stylesheet" href="Load.css">
</head>

<body>

    <div class="page-shell">

        <header class="topbar">
            <div>
                <h1>Student Attendance</h1>
                <p>Attendance Management System</p>
            </div>
            <a href="index.php" class="logout-btn">Logout</a>
        </header>

        <!-- Logged-in student -->
        <section class="student-card">
            <div class="student-avatar">
                <?= e(strtoupper(substr($student['student_name'], 0, 1))) ?>
            </div>

            <div class="student-info">
                <h2><?= e($student['student_name']) ?></h2>
                <div class="student-details">
                    <span><b>ID:</b> <?= e($student['student_id']) ?></span>
                    <span><b>Department:</b> <?= e($student['branch_name']) ?></span>
                    <span><b>Semester:</b> <?= e($student['semester']) ?></span>
                </div>
            </div>
        </section>

        <!-- Active semester -->
        <section class="semester-card">
            <div>
                <span class="small-label">ACTIVE SEMESTER</span>
                <h2><?= e($semester['semester_name']) ?></h2>
            </div>
            <div class="semester-date">
                <b><?= e($semester['academic_year']) ?></b>
                <span><?= e(date('d M Y', strtotime($semesterStart))) ?> - <?= e(date('d M Y', strtotime($semesterEnd))) ?></span>
            </div>
        </section>

        <!-- Attendance summary -->
        <section class="summary-grid">
            <div class="summary-card">
                <span>Total Class Days</span>
                <strong id="totalClassDays"><?= $totalClassDays ?></strong>
                <small>From semester start to today</small>
            </div>

            <div class="summary-card present-summary">
                <span>Present</span>
                <strong id="presentDays"><?= $presentDays ?></strong>
                <small>Present days</small>
            </div>

            <div class="summary-card absent-summary">
                <span>Absent</span>
                <strong id="absentDays"><?= $absentDays ?></strong>
                <small>Absent days</small>
            </div>

            <div class="summary-card percentage-summary">
                <span>Attendance</span>
                <strong id="attendancePercentage"><?= number_format($attendancePercentage, 2) ?>%</strong>
                <small>Present ÷ Total Class Days</small>
            </div>
        </section>

        <section class="actions-row">
            <button type="button" id="downloadExcelBtn" class="download-btn">
                Download Attendance Excel
            </button>
            <span id="downloadMessage" class="download-message"></span>
        </section>

        <!-- Legend -->
        <section class="legend">
            <div><span class="legend-box present"></span> Present</div>
            <div><span class="legend-box absent"></span> Absent</div>
            <div><span class="legend-box sunday"></span> Sunday</div>
            <div><span class="legend-box holiday"></span> Holiday</div>
            <div><span class="legend-box upcoming"></span> Upcoming</div>
            <div><span class="legend-box not-marked"></span> Not Marked</div>
        </section>

        <main id="calendarContainer" class="calendar-container"></main>

        <footer class="footer-note">
            <span>Semester: <?= e($semesterStart) ?> to <?= e($semesterEnd) ?></span>
            <span>Today: <?= e($today) ?></span>
        </footer>
    </div>

    <script>
        window.STUDENT_PAGE_DATA = <?= json_encode(
                                        $calendarData,
                                        JSON_UNESCAPED_UNICODE |
                                            JSON_UNESCAPED_SLASHES |
                                            JSON_HEX_TAG |
                                            JSON_HEX_AMP |
                                            JSON_HEX_APOS |
                                            JSON_HEX_QUOT
                                    ) ?>;
    </script>
    <script src="Student.js"></script>
    <?php require_once __DIR__ . '/loader.php'; ?>
</body>

</html>