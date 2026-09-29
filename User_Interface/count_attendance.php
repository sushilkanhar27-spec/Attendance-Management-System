<?php
session_start();
include "db_connect.php";

/*
 * Count Attendance
 * Shows attendance totals only for the logged-in teacher's department.
 * Present/Absent counts are calculated from attendance_record up to today's date.
 */

if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$teacher = null;
$teacher_branch = "";

$stmt = mysqli_prepare(
    $conn,
    "SELECT teacher_name, teacher_id, branch_name
     FROM teacher
     WHERE teacher_id = ?
     LIMIT 1"
);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $teacher_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $teacher = mysqli_fetch_assoc($result);
        $teacher_branch = $teacher['branch_name'];
    }

    mysqli_stmt_close($stmt);
}

if (!$teacher || $teacher_branch === "") {
    header("Location: index.php");
    exit();
}

/*
 * IMPORTANT:
 * The department restriction is applied using students.branch_name,
 * not attendance_record.branch_name, because existing attendance rows
 * may have branch_name = NULL.
 *
 * DISTINCT attendance_date makes each student/date count once.
 */
$students = [];

$sql = "
    SELECT
        s.student_id,
        s.student_name,
        s.mobile,
        s.semester,

        COUNT(DISTINCT CASE
            WHEN ar.status = 'Present'
            THEN ar.attendance_date
        END) AS present_count,

        COUNT(DISTINCT CASE
            WHEN ar.status = 'Absent'
            THEN ar.attendance_date
        END) AS absent_count

    FROM students s

    LEFT JOIN attendance_record ar
        ON ar.student_id = s.student_id
        AND ar.attendance_date <= CURDATE()

    WHERE s.branch_name = ?

    GROUP BY
        s.student_id,
        s.student_name,
        s.mobile,
        s.semester

    ORDER BY
        s.student_name ASC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database query preparation failed: " . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt, "s", $teacher_branch);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $present = (int)$row['present_count'];
    $absent  = (int)$row['absent_count'];
    $total   = $present + $absent;

    // Percentage is calculated later using the student's
    // specific semester's total class days.
    $row['present_count'] = $present;
    $row['absent_count'] = $absent;
    $row['total_count'] = $total;
    $row['percentage'] = null;
    $row['percentage_configured'] = false;

    $students[] = $row;
}

mysqli_stmt_close($stmt);

/*
 * TOTAL CLASS DAYS FOR EACH SEMESTER
 * ----------------------------------
 * IMPORTANT:
 * Do NOT calculate class days from attendance_record.
 * Class days are determined by the administrator's
 * start_date and end_date in semester_settings.
 *
 * Same rule as Student.php:
 *   - count every date from start_date through end_date
 *   - exclude Sundays
 *   - exclude dates stored in holidays table
 *
 * This means the value in each semester box will
 * automatically change whenever the admin changes
 * that semester's start/end dates.
 */
$semester_names = [
    '1st Semester',
    '2nd Semester',
    '3rd Semester',
    '4th Semester',
    '5th Semester',
    '6th Semester'
];

$semester_class_days = array_fill_keys($semester_names, 0);
$semester_dates = array_fill_keys($semester_names, null);

/*
 * Load all semester settings.
 * If a semester has been configured more than once,
 * the newest record (highest id) is used.
 */
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

/*
 * Load all holidays once. We use the holiday dates
 * while calculating each semester.
 */
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

/*
 * Calculate class days separately for every semester.
 */
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

    /*
     * Protect against an invalid date range.
     */
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

        // 0 = Sunday, same rule used in Student.php.
        $dayOfWeek = (int)$cursor->format('w');

        if (
            $dayOfWeek !== 0 &&
            !isset($holiday_dates[$date])
        ) {
            $semester_class_days[$semester_name]++;
        }

        $cursor->modify('+1 day');
    }
}

/*
 * CALCULATE EACH STUDENT'S ATTENDANCE PERCENTAGE
 * -----------------------------------------------
 * IMPORTANT:
 * Attendance % is NOT Present / (Present + Absent).
 *
 * Correct formula:
 *   Present Days / Total Semester Class Days * 100
 *
 * The denominator comes from the student's own semester.
 * If that semester has no valid admin configuration, the
 * percentage is shown as "Not configured".
 */
foreach ($students as &$student) {

    $student_semester = trim((string)($student['semester'] ?? ''));

    if (
        isset($semester_class_days[$student_semester]) &&
        isset($semester_settings[$student_semester]) &&
        $semester_class_days[$student_semester] > 0
    ) {
        $total_semester_class_days = (int)$semester_class_days[$student_semester];
        $present_days = (int)$student['present_count'];

        $student['percentage'] = round(
            ($present_days / $total_semester_class_days) * 100,
            2
        );
        $student['percentage_configured'] = true;
    } else {
        $student['percentage'] = null;
        $student['percentage_configured'] = false;
    }
}
unset($student);

$today = date("d-m-Y");
$today_db = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Count Attendance | Attendance Management System</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            min-height: 100vh;
            color: #172033;
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .88), rgba(30, 64, 175, .72)),
                url("background.jpg") center/cover fixed no-repeat;
        }

        .page {
            width: min(1500px, 96%);
            margin: 28px auto;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 22px 26px;
            color: #fff;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 22px 22px 0 0;
            background: rgba(15, 23, 42, .82);
            backdrop-filter: blur(14px);
            box-shadow: 0 15px 45px rgba(0,0,0,.25);
        }

        .title-area h1 {
            margin: 0;
            font-size: 26px;
        }

        .title-area p {
            margin: 7px 0 0;
            color: #cbd5e1;
            font-size: 14px;
        }

        .top-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn {
            border: 0;
            border-radius: 10px;
            padding: 11px 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: .2s;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-dashboard {
            background: #fff;
            color: #172033;
        }

        .btn-print {
            background: #2563eb;
            color: #fff;
        }

        .content {
            background: rgba(255,255,255,.96);
            padding: 25px;
            border-radius: 0 0 22px 22px;
            box-shadow: 0 15px 45px rgba(0,0,0,.25);
        }

        .department-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 20px;
            margin-bottom: 20px;
            border: 1px solid #dbe4f0;
            border-radius: 16px;
            background: linear-gradient(135deg, #f8fafc, #eef4ff);
        }

        .department-card .label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .department-card .value {
            margin-top: 5px;
            font-size: 20px;
            font-weight: 800;
            color: #1e3a8a;
        }

        .date-info {
            color: #475569;
            font-size: 13px;
            text-align: right;
        }

        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin: 4px 0 12px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: 18px;
            color: #172033;
        }

        .section-heading p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        .stat {
            padding: 18px;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
            background: #fff;
            box-shadow: 0 5px 18px rgba(15,23,42,.07);
        }

        .stat .label {
            font-size: 12px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
        }

        .stat .number {
            margin-top: 7px;
            font-size: 27px;
            font-weight: 800;
        }

        .class-day-range {
            margin-top: 7px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.4;
        }

        .stat.total .number { color: #1d4ed8; }
        .stat.present .number { color: #15803d; }
        .stat.absent .number { color: #dc2626; }
        .stat.records .number { color: #7c3aed; }

        .filters {
            display: grid;
            grid-template-columns: 1fr 220px;
            gap: 14px;
            margin-bottom: 18px;
        }

        .filters input,
        .filters select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            padding: 12px 14px;
            font-size: 14px;
            outline: none;
            background: #fff;
        }

        .filters input:focus,
        .filters select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #dbe3ef;
            border-radius: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        thead th {
            padding: 14px 12px;
            background: #172554;
            color: #fff;
            font-size: 13px;
            white-space: nowrap;
            text-align: left;
        }

        tbody td {
            padding: 13px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .present-badge,
        .absent-badge,
        .percentage {
            display: inline-block;
            min-width: 48px;
            padding: 5px 9px;
            border-radius: 8px;
            font-weight: 800;
            text-align: center;
        }

        .present-badge {
            color: #166534;
            background: #dcfce7;
        }

        .absent-badge {
            color: #991b1b;
            background: #fee2e2;
        }

        .percentage {
            color: #1e40af;
            background: #dbeafe;
        }

        .percentage.not-configured {
            color: #64748b;
            background: #f1f5f9;
            min-width: auto;
        }

        .no-data {
            text-align: center;
            padding: 35px;
            color: #64748b;
        }

        .footer-note {
            margin-top: 14px;
            color: #64748b;
            font-size: 12px;
        }

        @media (max-width: 900px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .department-card {
                align-items: flex-start;
                flex-direction: column;
            }

            .date-info {
                text-align: left;
            }
        }

        @media (max-width: 650px) {
            .page {
                width: 100%;
                margin: 0;
            }

            .topbar,
            .content {
                border-radius: 0;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .top-actions {
                justify-content: flex-start;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                width: 100%;
                margin: 0;
            }

            .topbar {
                color: #000;
                background: #fff;
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .topbar p,
            .top-actions,
            .filters,
            .stats,
            .footer-note {
                display: none !important;
            }

            .content {
                box-shadow: none;
                border: 0;
            }

            table {
                min-width: 0;
            }
        }
    </style>
    <link rel="stylesheet" href="Load.css">
</head>

<body>

<div class="page">

    <div class="topbar">
        <div class="title-area">
            <h1>📊 Count Attendance</h1>
            <p>Student-wise attendance summary with semester class-day totals</p>
        </div>

        <div class="top-actions">
            <a href="Teacher.php" class="btn btn-dashboard">← Dashboard</a>
            <button class="btn btn-print" onclick="window.print()">🖨 Print</button>
        </div>
    </div>

    <div class="content">

        <div class="department-card">
            <div>
                <div class="label">Teacher</div>
                <div class="value">
                    <?php echo htmlspecialchars($teacher['teacher_name']); ?>
                </div>
            </div>

            <div>
                <div class="label">Department</div>
                <div class="value">
                    <?php echo htmlspecialchars($teacher_branch); ?>
                </div>
            </div>

            <div class="date-info">
                <strong>Attendance counted up to</strong><br>
                <?php echo htmlspecialchars($today); ?>
            </div>
        </div>
        <div class="section-heading">
            <div>
                <h2>Total Class Days</h2>
                <p>Automatically calculated from each semester's admin-set start and end dates, excluding Sundays and holidays.</p>
            </div>
        </div>

        <div class="stats">
            <div class="stat records">
                <div class="label">1st Semester</div>
                <div class="number" id="1stSemesterCount">
                    <?php echo $semester_class_days['1st Semester']; ?>
                </div>
                <?php if ($semester_dates['1st Semester']): ?>
                    <div class="class-day-range">
                        <?php
                        echo htmlspecialchars(
                            date('d M Y', strtotime($semester_dates['1st Semester']['start'])) .
                            ' - ' .
                            date('d M Y', strtotime($semester_dates['1st Semester']['end']))
                        );
                        ?>
                    </div>
                <?php else: ?>
                    <div class="class-day-range">Not configured</div>
                <?php endif; ?>
            </div>

            <div class="stat records">
                <div class="label">2nd Semester</div>
                <div class="number" id="2ndSemesterCount">
                    <?php echo $semester_class_days['2nd Semester']; ?>
                </div>
                <?php if ($semester_dates['2nd Semester']): ?>
                    <div class="class-day-range">
                        <?php
                        echo htmlspecialchars(
                            date('d M Y', strtotime($semester_dates['2nd Semester']['start'])) .
                            ' - ' .
                            date('d M Y', strtotime($semester_dates['2nd Semester']['end']))
                        );
                        ?>
                    </div>
                <?php else: ?>
                    <div class="class-day-range">Not configured</div>
                <?php endif; ?>
            </div>

            <div class="stat records">
                <div class="label">3rd Semester</div>
                <div class="number" id="3rdSemesterCount">
                    <?php echo $semester_class_days['3rd Semester']; ?>
                </div>
                <?php if ($semester_dates['3rd Semester']): ?>
                    <div class="class-day-range">
                        <?php
                        echo htmlspecialchars(
                            date('d M Y', strtotime($semester_dates['3rd Semester']['start'])) .
                            ' - ' .
                            date('d M Y', strtotime($semester_dates['3rd Semester']['end']))
                        );
                        ?>
                    </div>
                <?php else: ?>
                    <div class="class-day-range">Not configured</div>
                <?php endif; ?>
            </div>

            <div class="stat records">
                <div class="label">4th Semester</div>
                <div class="number" id="4thSemesterCount">
                    <?php echo $semester_class_days['4th Semester']; ?>
                </div>
                <?php if ($semester_dates['4th Semester']): ?>
                    <div class="class-day-range">
                        <?php
                        echo htmlspecialchars(
                            date('d M Y', strtotime($semester_dates['4th Semester']['start'])) .
                            ' - ' .
                            date('d M Y', strtotime($semester_dates['4th Semester']['end']))
                        );
                        ?>
                    </div>
                <?php else: ?>
                    <div class="class-day-range">Not configured</div>
                <?php endif; ?>
            </div>

            <div class="stat records">
                <div class="label">5th Semester</div>
                <div class="number" id="5thSemesterCount">
                    <?php echo $semester_class_days['5th Semester']; ?>
                </div>
                <?php if ($semester_dates['5th Semester']): ?>
                    <div class="class-day-range">
                        <?php
                        echo htmlspecialchars(
                            date('d M Y', strtotime($semester_dates['5th Semester']['start'])) .
                            ' - ' .
                            date('d M Y', strtotime($semester_dates['5th Semester']['end']))
                        );
                        ?>
                    </div>
                <?php else: ?>
                    <div class="class-day-range">Not configured</div>
                <?php endif; ?>
            </div>

            <div class="stat records">
                <div class="label">6th Semester</div>
                <div class="number" id="6thSemesterCount">
                    <?php echo $semester_class_days['6th Semester']; ?>
                </div>
                <?php if ($semester_dates['6th Semester']): ?>
                    <div class="class-day-range">
                        <?php
                        echo htmlspecialchars(
                            date('d M Y', strtotime($semester_dates['6th Semester']['start'])) .
                            ' - ' .
                            date('d M Y', strtotime($semester_dates['6th Semester']['end']))
                        );
                        ?>
                    </div>
                <?php else: ?>
                    <div class="class-day-range">Not configured</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="filters">
            <input
                type="text"
                id="search"
                placeholder="🔎 Search by Student ID, Name or Mobile..."
                autocomplete="off"
            >

            <select id="semester">
                <option value="">All Semesters</option>
                <option value="1st Semester">1st Semester</option>
                <option value="2nd Semester">2nd Semester</option>
                <option value="3rd Semester">3rd Semester</option>
                <option value="4th Semester">4th Semester</option>
                <option value="5th Semester">5th Semester</option>
                <option value="6th Semester">6th Semester</option>
            </select>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>SL No.</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Semester</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>

                <tbody id="attendanceTable">
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="9" class="no-data">
                                No students found in your department.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $index => $student): ?>
                            <tr
                                data-name="<?php echo htmlspecialchars(strtolower($student['student_name'])); ?>"
                                data-id="<?php echo htmlspecialchars(strtolower($student['student_id'])); ?>"
                                data-mobile="<?php echo htmlspecialchars(strtolower($student['mobile'] ?? '')); ?>"
                                data-semester="<?php echo htmlspecialchars($student['semester']); ?>"
                            >
                                <td class="sl"><?php echo $index + 1; ?></td>

                                <td>
                                    <strong><?php echo htmlspecialchars($student['student_id']); ?></strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student['student_name']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student['mobile'] ?? ''); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student['semester']); ?>
                                </td>

                                <td>
                                    <span class="present-badge">
                                        <?php echo $student['present_count']; ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="absent-badge">
                                        <?php echo $student['absent_count']; ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($student['percentage_configured']): ?>
                                        <span class="percentage">
                                            <?php echo number_format((float)$student['percentage'], 2); ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="percentage not-configured">
                                            Not configured
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="footer-note">
            Total Class Days are calculated from each semester's
            <strong>semester_settings.start_date</strong> through
            <strong>semester_settings.end_date</strong>, excluding Sundays
            and dates in the <strong>holidays</strong> table.
            Present and Absent values continue to come from the
            <strong>attendance_record</strong> table up to
            <?php echo htmlspecialchars($today_db); ?>.
            Attendance % is calculated separately for each student's semester as
            <strong>Present Days ÷ Total Semester Class Days × 100</strong>.
            If that semester is not configured, Attendance % shows
            <strong>Not configured</strong>.
            Only students whose <strong>students.branch_name</strong> matches
            the logged-in teacher's department are displayed.
        </div>

    </div>
</div>

<script>
    const searchInput = document.getElementById("search");
    const semesterSelect = document.getElementById("semester");
    const tableBody = document.getElementById("attendanceTable");

    function filterTable() {
        const search = searchInput.value.trim().toLowerCase();
        const semester = semesterSelect.value;

        const rows = Array.from(tableBody.querySelectorAll("tr[data-name]"));
        let visible = 0;
        let present = 0;
        let absent = 0;
        let records = 0;

        rows.forEach(row => {
            const name = row.dataset.name || "";
            const id = row.dataset.id || "";
            const mobile = row.dataset.mobile || "";
            const rowSemester = row.dataset.semester || "";

            const searchMatch =
                name.includes(search) ||
                id.includes(search) ||
                mobile.includes(search);

            const semesterMatch =
                semester === "" || rowSemester === semester;

            const show = searchMatch && semesterMatch;

            row.style.display = show ? "" : "none";

            if (show) {
                visible++;

                const presentValue = parseInt(
                    row.children[5].innerText.trim()
                ) || 0;

                const absentValue = parseInt(
                    row.children[6].innerText.trim()
                ) || 0;

                present += presentValue;
                absent += absentValue;
                records += presentValue + absentValue;
            }
        });

        let serial = 1;

        rows.forEach(row => {
            if (row.style.display !== "none") {
                row.querySelector(".sl").textContent = serial++;
            }
        });

        const totalStudentsEl = document.getElementById("totalStudents");
        const totalPresentEl = document.getElementById("totalPresent");
        const totalAbsentEl = document.getElementById("totalAbsent");
        const totalRecordsEl = document.getElementById("totalRecords");

        if (totalStudentsEl) totalStudentsEl.textContent = visible;
        if (totalPresentEl) totalPresentEl.textContent = present;
        if (totalAbsentEl) totalAbsentEl.textContent = absent;
        if (totalRecordsEl) totalRecordsEl.textContent = records;

        let noResult = document.getElementById("noResult");

        if (visible === 0 && rows.length > 0) {
            if (!noResult) {
                noResult = document.createElement("tr");
                noResult.id = "noResult";
                noResult.innerHTML =
                    '<td colspan="9" class="no-data">No matching students found.</td>';
                tableBody.appendChild(noResult);
            }
        } else if (noResult) {
            noResult.remove();
        }
    }

    searchInput.addEventListener("input", filterTable);
    semesterSelect.addEventListener("change", filterTable);

    filterTable();
</script>

    <?php require_once __DIR__ . '/loader.php'; ?>
</body>
</html>
