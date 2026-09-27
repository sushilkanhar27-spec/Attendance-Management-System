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
| Get admin name
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT admin_name FROM admin WHERE admin_id = ? LIMIT 1"
);

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
| ALL STUDENTS
|
| Admin can see students from every department.
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        student_id,
        student_name,
        branch_name,
        semester,
        mobile
     FROM students
     ORDER BY branch_name, semester, student_name"
);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$students = [];

while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| Department count
|--------------------------------------------------------------------------
*/
$department_count = [];

foreach ($students as $student) {
    $branch = $student['branch_name'];

    if (!isset($department_count[$branch])) {
        $department_count[$branch] = 0;
    }

    $department_count[$branch]++;
}

$total_students = count($students);
$total_departments = count($department_count);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin - All Student Biometric</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
            color: #1f2937;
        }

        .container {
            width: min(1250px, 94%);
            margin: 25px auto 50px;
        }

        .topbar {
            background: linear-gradient(135deg, #0d47a1, #1976d2);
            color: white;
            padding: 24px 28px;
            border-radius: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: 0 10px 30px rgba(25, 118, 210, .20);
        }

        .topbar h1 {
            margin: 0 0 7px;
            font-size: 27px;
        }

        .topbar p {
            margin: 0;
            opacity: .9;
        }

        .top-actions a {
            text-decoration: none;
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            font-weight: 600;
            display: inline-block;
        }

        .dashboard-btn {
            background: rgba(255, 255, 255, .16);
            margin-right: 8px;
        }

        .logout-btn {
            background: #dc2626;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 18px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .07);
        }

        .stat-card .label {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .stat-card .value {
            font-size: 25px;
            font-weight: 700;
            color: #0d47a1;
        }

        .toolbar {
            background: white;
            padding: 18px;
            border-radius: 15px;
            margin-bottom: 15px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .06);
        }

        .toolbar input,
        .toolbar select {
            padding: 11px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            outline: none;
            font-size: 14px;
        }

        .toolbar input {
            flex: 1;
            min-width: 240px;
        }

        .toolbar input:focus,
        .toolbar select:focus {
            border-color: #1976d2;
        }

        .table-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(15, 23, 42, .08);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th {
            background: #0d47a1;
            color: white;
            padding: 14px 12px;
            text-align: center;
            font-size: 14px;
        }

        td {
            padding: 13px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .student-id {
            font-weight: 700;
            color: #1565c0;
        }

        .branch-badge {
            background: #dbeafe;
            color: #1d4ed8;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .semester-badge {
            background: #f1f5f9;
            color: #334155;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .biometric-btn {
            border: none;
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            padding: 10px 15px;
            border-radius: 9px;
            cursor: pointer;
            font-weight: 700;
        }

        .biometric-btn:hover {
            box-shadow: 0 5px 12px rgba(16, 185, 129, .25);
            transform: translateY(-1px);
        }

        .empty {
            padding: 40px;
            text-align: center;
            color: #64748b;
        }

        .footer-note {
            padding: 16px 20px;
            color: #64748b;
            font-size: 13px;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 800px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="topbar">
        <div>
            <h1>🖐️ All Student Biometric</h1>
            <p>
                Admin: <?php echo htmlspecialchars($admin['admin_name']); ?>
                · All Departments
            </p>
        </div>

        <div class="top-actions">
            <a href="admin_dashboard.php" class="dashboard-btn">
                ← Dashboard
            </a>

            <a href="logout.php" class="logout-btn">
                Logout
            </a>
        </div>
    </div>

    <div class="stats">

        <div class="stat-card">
            <div class="label">Total Students</div>
            <div class="value" id="studentCount">
                <?php echo $total_students; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="label">Departments</div>
            <div class="value">
                <?php echo $total_departments; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="label">Visible Records</div>
            <div class="value" id="visibleCount">
                <?php echo $total_students; ?>
            </div>
        </div>

    </div>

    <div class="toolbar">

        <input
            type="text"
            id="searchStudent"
            placeholder="🔎 Search Student ID or Name..."
        >

        <select id="departmentFilter">
            <option value="">All Departments</option>

            <?php foreach ($department_count as $branch => $count): ?>
                <option value="<?php echo htmlspecialchars($branch); ?>">
                    <?php echo htmlspecialchars($branch); ?>
                </option>
            <?php endforeach; ?>

        </select>

        <select id="semesterFilter">
            <option value="">All Semesters</option>
            <option value="1st Semester">1st Semester</option>
            <option value="2nd Semester">2nd Semester</option>
            <option value="3rd Semester">3rd Semester</option>
            <option value="4th Semester">4th Semester</option>
            <option value="5th Semester">5th Semester</option>
            <option value="6th Semester">6th Semester</option>
        </select>

    </div>

    <div class="table-card">

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
                        <th>Add Biometric</th>
                    </tr>
                </thead>

                <tbody id="studentTable">

                <?php if ($total_students > 0): ?>

                    <?php foreach ($students as $index => $student): ?>

                        <tr
                            data-name="<?php echo htmlspecialchars(strtolower($student['student_name'])); ?>"
                            data-id="<?php echo htmlspecialchars(strtolower($student['student_id'])); ?>"
                            data-branch="<?php echo htmlspecialchars($student['branch_name']); ?>"
                            data-semester="<?php echo htmlspecialchars($student['semester']); ?>"
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
                                    <?php echo htmlspecialchars($student['branch_name']); ?>
                                </span>
                            </td>

                            <td>
                                <span class="semester-badge">
                                    <?php echo htmlspecialchars($student['semester']); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student['mobile']); ?>
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="biometric-btn"
                                    onclick="addBiometric('<?php echo htmlspecialchars($student['student_id'], ENT_QUOTES); ?>')"
                                >
                                    🖐️ Add Biometric
                                </button>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty">
                            No students found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="footer-note">
            🔐 Admin access: all students from all departments are visible on this page.
        </div>

    </div>

</div>

<script>
    const searchInput = document.getElementById("searchStudent");
    const departmentFilter = document.getElementById("departmentFilter");
    const semesterFilter = document.getElementById("semesterFilter");

    const rows = Array.from(
        document.querySelectorAll("#studentTable tr[data-id]")
    );

    const visibleCount = document.getElementById("visibleCount");

    function filterStudents() {

        const search = searchInput.value.trim().toLowerCase();
        const department = departmentFilter.value;
        const semester = semesterFilter.value;

        let visible = 0;
        let serial = 1;

        rows.forEach(row => {

            const studentId = row.dataset.id;
            const studentName = row.dataset.name;
            const branch = row.dataset.branch;
            const studentSemester = row.dataset.semester;

            const matchesSearch =
                studentId.includes(search) ||
                studentName.includes(search);

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

    function addBiometric(studentId) {

        if (!studentId) {
            alert("Student ID is missing.");
            return;
        }

        /*
         * Admin can enroll any student.
         *
         * add_biometric.php should still verify the logged-in
         * user before performing the actual enrollment.
         */
        window.location.href =
            "add_biometric.php?student_id=" +
            encodeURIComponent(studentId) +
            "&source=admin";
    }
</script>

</body>
</html>
