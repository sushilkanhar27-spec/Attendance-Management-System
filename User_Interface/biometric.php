<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

/* -------------------------------------------------------
   Get logged-in teacher and his/her department
------------------------------------------------------- */
$teacher_id = $_SESSION['teacher_id'];

$stmt = mysqli_prepare(
    $conn,
    "SELECT teacher_name, teacher_id, branch_name
     FROM teacher
     WHERE teacher_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $teacher_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$teacher = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$teacher) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$teacher_branch = $teacher['branch_name'];

/* -------------------------------------------------------
   IMPORTANT:
   Only students belonging to the logged-in teacher's
   department/branch are loaded.
------------------------------------------------------- */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        student_id,
        student_name,
        branch_name,
        semester,
        mobile
     FROM students
     WHERE branch_name = ?
     ORDER BY semester, student_name"
);

mysqli_stmt_bind_param($stmt, "s", $teacher_branch);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$students = [];

while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

mysqli_stmt_close($stmt);

$total_students = count($students);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Biometric - Attendance Management System</title>

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
            width: min(1200px, 94%);
            margin: 25px auto 50px;
        }

        .topbar {
            background: linear-gradient(135deg, #172554, #2563eb);
            color: white;
            padding: 24px 28px;
            border-radius: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, .18);
        }

        .topbar h1 {
            margin: 0 0 7px;
            font-size: 27px;
        }

        .topbar p {
            margin: 0;
            opacity: .9;
        }

        .back-btn,
        .logout-btn {
            text-decoration: none;
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            font-weight: 600;
            display: inline-block;
        }

        .back-btn {
            background: rgba(255, 255, 255, .15);
            margin-right: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .info-card {
            background: white;
            border-radius: 15px;
            padding: 18px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .07);
            border: 1px solid #e5e7eb;
        }

        .info-card .label {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .info-card .value {
            font-size: 19px;
            font-weight: 700;
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
            min-width: 230px;
        }

        .toolbar input:focus,
        .toolbar select:focus {
            border-color: #2563eb;
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
            min-width: 850px;
        }

        th {
            background: #172554;
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
            color: #1d4ed8;
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
            transition: .2s;
        }

        .biometric-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 12px rgba(16, 185, 129, .25);
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
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 500px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .topbar h1 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="topbar">
        <div>
            <h1>🖐️ Add Student Biometric</h1>
            <p>
                <?php echo htmlspecialchars($teacher['teacher_name']); ?>
                · <?php echo htmlspecialchars($teacher_branch); ?> Department
            </p>
        </div>

        <div>
            <a href="Teacher.php" class="back-btn">← Teacher Dashboard</a>
        </div>
    </div>

    <div class="info-grid">

        <div class="info-card">
            <div class="label">Teacher</div>
            <div class="value">
                <?php echo htmlspecialchars($teacher['teacher_name']); ?>
            </div>
        </div>

        <div class="info-card">
            <div class="label">Teacher ID</div>
            <div class="value">
                <?php echo htmlspecialchars($teacher['teacher_id']); ?>
            </div>
        </div>

        <div class="info-card">
            <div class="label">Department</div>
            <div class="value">
                <?php echo htmlspecialchars($teacher_branch); ?>
            </div>
        </div>

        <div class="info-card">
            <div class="label">Students in Department</div>
            <div class="value" id="studentCount">
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
                                <!--
                                  Pass the selected student ID to the biometric
                                  enrollment page.
                                -->
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
                            No students found in your department.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="footer-note">
            🔒 Only students from your assigned department
            (<strong><?php echo htmlspecialchars($teacher_branch); ?></strong>)
            are displayed.
        </div>

    </div>

</div>

<script>
    const searchInput = document.getElementById("searchStudent");
    const semesterFilter = document.getElementById("semesterFilter");
    const rows = Array.from(document.querySelectorAll("#studentTable tr"));
    const studentCount = document.getElementById("studentCount");

    function filterStudents() {

        const search = searchInput.value.trim().toLowerCase();
        const semester = semesterFilter.value;

        let visible = 0;
        let serial = 1;

        rows.forEach(row => {

            if (!row.dataset.id) {
                return;
            }

            const studentId = row.dataset.id;
            const studentName = row.dataset.name;
            const studentSemester = row.dataset.semester;

            const matchesSearch =
                studentId.includes(search) ||
                studentName.includes(search);

            const matchesSemester =
                semester === "" ||
                studentSemester === semester;

            const show = matchesSearch && matchesSemester;

            row.style.display = show ? "" : "none";

            if (show) {
                const serialCell = row.querySelector(".serial");
                if (serialCell) {
                    serialCell.textContent = serial++;
                }
                visible++;
            }
        });

        studentCount.textContent = visible;
    }

    searchInput.addEventListener("input", filterStudents);
    semesterFilter.addEventListener("change", filterStudents);

    function addBiometric(studentId) {

        if (!studentId) {
            alert("Student ID is missing.");
            return;
        }

        /*
         * The enrollment page receives only the selected student's ID.
         * It must verify the teacher's department again on the server.
         */
        window.location.href =
            "add_biometric.php?student_id=" +
            encodeURIComponent(studentId);
    }
</script>

</body>
</html>
