<?php
session_start();
include "db_connect.php";

/*
|--------------------------------------------------------------------------
| ADMIN ONLY
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

/*
|--------------------------------------------------------------------------
| GET ADMIN DETAILS
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT admin_name FROM admin WHERE admin_id = ? LIMIT 1"
);

if (!$stmt) {
    die("Database error: " . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt, "s", $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$admin) {
    session_destroy();
    header("Location: index.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| SEMESTERS
|--------------------------------------------------------------------------
*/
$semester_names = [
    '1st Semester',
    '2nd Semester',
    '3rd Semester',
    '4th Semester',
    '5th Semester',
    '6th Semester'
];

/*
|--------------------------------------------------------------------------
| LOAD LATEST SETTINGS FOR EACH SEMESTER
|--------------------------------------------------------------------------
*/
$semester_settings = [];
$semester_class_days = array_fill_keys($semester_names, 0);
$semester_dates = array_fill_keys($semester_names, null);

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
            in_array($name, $semester_names, true) &&
            !isset($semester_settings[$name])
        ) {
            $semester_settings[$name] = $setting;
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD HOLIDAYS
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| CALCULATE TOTAL CLASS DAYS FOR EACH SEMESTER
|
| Same logic as Student.php:
| - start_date through end_date
| - exclude Sundays
| - exclude holidays
|--------------------------------------------------------------------------
*/
foreach ($semester_names as $semester_name) {

    if (!isset($semester_settings[$semester_name])) {
        continue;
    }

    $setting = $semester_settings[$semester_name];

    $start_date = $setting['start_date'];
    $end_date = $setting['end_date'];

    if (
        empty($start_date) ||
        empty($end_date) ||
        $start_date > $end_date
    ) {
        continue;
    }

    $semester_dates[$semester_name] = [
        'start' => $start_date,
        'end' => $end_date,
        'academic_year' => $setting['academic_year']
    ];

    $cursor = new DateTime($start_date);
    $end_cursor = new DateTime($end_date);

    while ($cursor <= $end_cursor) {

        $date = $cursor->format('Y-m-d');
        $day_of_week = (int)$cursor->format('w');

        if (
            $day_of_week !== 0 &&
            !isset($holiday_dates[$date])
        ) {
            $semester_class_days[$semester_name]++;
        }

        $cursor->modify('+1 day');
    }
}

/*
|--------------------------------------------------------------------------
| ALL STUDENTS
|
| Unlike teacher count_attendance.php, there is NO branch restriction.
|--------------------------------------------------------------------------
*/
$students = [];

$sql = "
    SELECT
        s.student_id,
        s.student_name,
        s.branch_name,
        s.semester,
        s.mobile,

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

    GROUP BY
        s.student_id,
        s.student_name,
        s.branch_name,
        s.semester,
        s.mobile

    ORDER BY
        s.branch_name ASC,
        s.semester ASC,
        s.student_name ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Student attendance query failed: " . htmlspecialchars(mysqli_error($conn)));
}

while ($row = mysqli_fetch_assoc($result)) {

    $present = (int)$row['present_count'];
    $absent = (int)$row['absent_count'];
    $semester = trim((string)($row['semester'] ?? ''));

    /*
     * Attendance percentage is based on the student's
     * OWN semester total class days.
     *
     * Present Days ÷ Total Semester Class Days × 100
     */
    $class_days = $semester_class_days[$semester] ?? 0;

    if (
        isset($semester_dates[$semester]) &&
        $class_days > 0
    ) {
        $percentage = round(($present / $class_days) * 100, 2);
        $percentage_configured = true;
    } else {
        $percentage = null;
        $percentage_configured = false;
    }

    $row['present_count'] = $present;
    $row['absent_count'] = $absent;
    $row['total_class_days'] = $class_days;
    $row['percentage'] = $percentage;
    $row['percentage_configured'] = $percentage_configured;

    $students[] = $row;
}

$total_students = count($students);

/*
|--------------------------------------------------------------------------
| DEPARTMENT LIST
|--------------------------------------------------------------------------
*/
$departments = [];

foreach ($students as $student) {
    $branch = trim((string)($student['branch_name'] ?? ''));

    if ($branch !== '' && !in_array($branch, $departments, true)) {
        $departments[] = $branch;
    }
}

sort($departments);

$today = date("d-m-Y");
$today_db = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Count Attendance | Attendance Management System</title>

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
                linear-gradient(135deg, rgba(15, 23, 42, .90), rgba(30, 64, 175, .75)),
                url("background.jpg") center/cover fixed no-repeat;
        }

        .page {
            width: min(1550px, 96%);
            margin: 25px auto 45px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 22px 26px;
            color: #fff;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 20px 20px 0 0;
            background: rgba(15, 23, 42, .86);
            backdrop-filter: blur(14px);
            box-shadow: 0 15px 45px rgba(0,0,0,.25);
        }

        .title-area h1 {
            margin: 0;
            font-size: 27px;
        }

        .title-area p {
            margin: 7px 0 0;
            color: #cbd5e1;
            font-size: 14px;
        }

        .top-actions {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
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

        .dashboard-btn {
            background: #fff;
            color: #172033;
        }

        .print-btn {
            background: #2563eb;
            color: #fff;
        }

        .content {
            background: rgba(255,255,255,.97);
            padding: 24px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 15px 45px rgba(0,0,0,.25);
        }

        .admin-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 17px 20px;
            margin-bottom: 22px;
            border: 1px solid #dbe4f0;
            border-radius: 15px;
            background: linear-gradient(135deg, #f8fafc, #eef4ff);
        }

        .label {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .admin-name {
            margin-top: 4px;
            color: #1e3a8a;
            font-size: 20px;
            font-weight: 800;
        }

        .date-info {
            color: #475569;
            font-size: 13px;
            text-align: right;
        }

        .section-title {
            margin: 0 0 5px;
            font-size: 19px;
        }

        .section-description {
            margin: 0 0 13px;
            color: #64748b;
            font-size: 12px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 13px;
            margin-bottom: 21px;
        }

        .stat {
            min-height: 116px;
            padding: 17px;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 5px 18px rgba(15,23,42,.07);
        }

        .stat .number {
            margin-top: 7px;
            color: #2563eb;
            font-size: 27px;
            font-weight: 900;
        }

        .stat:nth-child(2) .number { color: #059669; }
        .stat:nth-child(3) .number { color: #dc2626; }
        .stat:nth-child(4) .number { color: #7c3aed; }
        .stat:nth-child(5) .number { color: #2563eb; }
        .stat:nth-child(6) .number { color: #059669; }

        .range {
            margin-top: 7px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.4;
        }

        .not-configured {
            color: #64748b !important;
            font-size: 18px !important;
        }

        .filters {
            display: grid;
            grid-template-columns: minmax(250px, 1fr) 220px 220px;
            gap: 12px;
            margin: 5px 0 17px;
        }

        .filters input,
        .filters select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            background: #fff;
            outline: none;
            font-size: 14px;
        }

        .filters input:focus,
        .filters select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }

        .table-card {
            overflow: hidden;
            border: 1px solid #dbe3ef;
            border-radius: 15px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1120px;
            border-collapse: collapse;
        }

        thead th {
            padding: 14px 12px;
            background: #172554;
            color: #fff;
            text-align: left;
            font-size: 13px;
            white-space: nowrap;
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

        .student-id {
            font-weight: 800;
        }

        .branch-badge,
        .semester-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .branch-badge {
            color: #1d4ed8;
            background: #dbeafe;
        }

        .semester-badge {
            color: #475569;
            background: #f1f5f9;
        }

        .present-badge,
        .absent-badge,
        .percentage-badge {
            display: inline-block;
            min-width: 48px;
            padding: 5px 9px;
            border-radius: 8px;
            text-align: center;
            font-weight: 800;
        }

        .present-badge {
            color: #166534;
            background: #dcfce7;
        }

        .absent-badge {
            color: #991b1b;
            background: #fee2e2;
        }

        .percentage-badge {
            color: #1e40af;
            background: #dbeafe;
        }

        .percentage-badge.not-configured-badge {
            min-width: 120px;
            color: #64748b;
            background: #f1f5f9;
        }

        .empty {
            padding: 45px 20px;
            text-align: center;
            color: #64748b;
        }

        .table-footer {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 14px 17px;
            color: #64748b;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
        }

        @media (max-width: 1050px) {
            .stats {
                grid-template-columns: repeat(3, 1fr);
            }

            .filters {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 700px) {
            .page {
                width: 100%;
                margin: 0;
            }

            .topbar,
            .content {
                border-radius: 0;
            }

            .topbar,
            .admin-card {
                align-items: flex-start;
                flex-direction: column;
            }

            .date-info {
                text-align: left;
            }

            .stats {
                grid-template-columns: 1fr 1fr;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .table-footer {
                flex-direction: column;
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
                border: 1px solid #ddd;
                box-shadow: none;
            }

            .title-area p,
            .top-actions,
            .stats,
            .filters,
            .table-footer {
                display: none !important;
            }

            .content {
                box-shadow: none;
                border: 0;
            }

            .table-card {
                border: 0;
            }

            table {
                min-width: 0;
            }
        }
    </style>
</head>

<body>

<div class="page">

    <header class="topbar">
        <div class="title-area">
            <h1>📊 Admin Count Attendance</h1>
            <p>Complete student attendance summary · All Departments</p>
        </div>

        <div class="top-actions">
            <a href="admin_dashboard.php" class="btn dashboard-btn">
                ← Dashboard
            </a>

            <button type="button" class="btn print-btn" onclick="window.print()">
                🖨 Print
            </button>
            
        </div>
    </header>

    <main class="content">

        <section class="admin-card">
            <div>
                <div class="label">Administrator</div>
                <div class="admin-name">
                    <?php echo htmlspecialchars($admin['admin_name']); ?>
                </div>
            </div>

            <div class="date-info">
                <strong>Attendance counted up to</strong><br>
                <?php echo htmlspecialchars($today); ?>
            </div>
        </section>

        <h2 class="section-title">Total Class Days</h2>
        <p class="section-description">
            Each semester is calculated from its administrator-set start date
            through end date, excluding Sundays and holidays.
        </p>

        <section class="stats">

            <?php foreach ($semester_names as $index => $semester_name): ?>
                <div class="stat">
                    <div class="label">
                        <?php echo htmlspecialchars(strtoupper($semester_name)); ?>
                    </div>

                    <?php if ($semester_dates[$semester_name]): ?>

                        <div class="number">
                            <?php echo $semester_class_days[$semester_name]; ?>
                        </div>

                        <div class="range">
                            <?php
                            echo htmlspecialchars(
                                date(
                                    'd M Y',
                                    strtotime($semester_dates[$semester_name]['start'])
                                )
                                . ' - ' .
                                date(
                                    'd M Y',
                                    strtotime($semester_dates[$semester_name]['end'])
                                )
                            );
                            ?>
                        </div>

                    <?php else: ?>

                        <div class="number not-configured">
                            Not configured
                        </div>

                        <div class="range">
                            Configure this semester in Semester Settings
                        </div>

                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        </section>

        <section class="filters">

            <input
                type="text"
                id="searchStudent"
                placeholder="🔎 Search Student ID, Name or Mobile..."
                autocomplete="off"
            >

            <select id="departmentFilter">
                <option value="">All Departments</option>

                <?php foreach ($departments as $department): ?>
                    <option value="<?php echo htmlspecialchars($department); ?>">
                        <?php echo htmlspecialchars($department); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="semesterFilter">
                <option value="">All Semesters</option>

                <?php foreach ($semester_names as $semester_name): ?>
                    <option value="<?php echo htmlspecialchars($semester_name); ?>">
                        <?php echo htmlspecialchars($semester_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

        </section>

        <section class="table-card">

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>SL No.</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Mobile</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>

                    <tbody id="studentTable">

                    <?php if ($total_students > 0): ?>

                        <?php foreach ($students as $index => $student): ?>

                            <tr
                                data-id="<?php echo htmlspecialchars(strtolower($student['student_id'])); ?>"
                                data-name="<?php echo htmlspecialchars(strtolower($student['student_name'])); ?>"
                                data-mobile="<?php echo htmlspecialchars(strtolower($student['mobile'] ?? '')); ?>"
                                data-branch="<?php echo htmlspecialchars($student['branch_name'] ?? ''); ?>"
                                data-semester="<?php echo htmlspecialchars($student['semester'] ?? ''); ?>"
                            >

                                <td class="serial">
                                    <?php echo $index + 1; ?>
                                </td>

                                <td class="student-id">
                                    <?php echo htmlspecialchars($student['student_id']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student['student_name']); ?>
                                </td>

                                <td>
                                    <span class="branch-badge">
                                        <?php echo htmlspecialchars($student['branch_name'] ?? ''); ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="semester-badge">
                                        <?php echo htmlspecialchars($student['semester'] ?? ''); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student['mobile'] ?? ''); ?>
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

                                        <span class="percentage-badge">
                                            <?php echo number_format((float)$student['percentage'], 2); ?>%
                                        </span>

                                    <?php else: ?>

                                        <span class="percentage-badge not-configured-badge">
                                            Not configured
                                        </span>

                                    <?php endif; ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="empty">
                                No students found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="table-footer">
                <span>
                    Showing <strong id="visibleCount"><?php echo $total_students; ?></strong>
                    of <strong><?php echo $total_students; ?></strong> students
                </span>

                <span>
                    Attendance % =
                    <strong>Present Days ÷ Total Semester Class Days × 100</strong>
                </span>
            </div>

        </section>

    </main>

</div>

<script>
    const searchInput = document.getElementById("searchStudent");
    const departmentFilter = document.getElementById("departmentFilter");
    const semesterFilter = document.getElementById("semesterFilter");
    const studentTable = document.getElementById("studentTable");
    const visibleCount = document.getElementById("visibleCount");

    function filterStudents() {

        const search = searchInput.value.trim().toLowerCase();
        const department = departmentFilter.value;
        const semester = semesterFilter.value;

        const rows = Array.from(
            studentTable.querySelectorAll("tr[data-id]")
        );

        let visible = 0;
        let serial = 1;

        rows.forEach(row => {

            const studentId = row.dataset.id || "";
            const studentName = row.dataset.name || "";
            const mobile = row.dataset.mobile || "";
            const branch = row.dataset.branch || "";
            const studentSemester = row.dataset.semester || "";

            const matchesSearch =
                studentId.includes(search) ||
                studentName.includes(search) ||
                mobile.includes(search);

            const matchesDepartment =
                department === "" ||
                branch === department;

            const matchesSemester =
                semester === "" ||
                studentSemester === semester;

            const show =
                matchesSearch &&
                matchesDepartment &&
                matchesSemester;

            row.style.display = show ? "" : "none";

            if (show) {

                const serialCell = row.querySelector(".serial");

                if (serialCell) {
                    serialCell.textContent = serial++;
                }

                visible++;
            }
        });

        visibleCount.textContent = visible;
    }

    searchInput.addEventListener("input", filterStudents);
    departmentFilter.addEventListener("change", filterStudents);
    semesterFilter.addEventListener("change", filterStudents);
</script>

</body>
</html>
