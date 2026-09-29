<?php
session_start();
include "db_connect.php";
include "attendance_functions.php";

if (!isset($_SESSION['teacher_id'])) {
    header('Location: index.php');
    exit();
}

$teacher_id = mysqli_real_escape_string($conn, $_SESSION['teacher_id']);
$teacher = null;
$stmt = mysqli_prepare($conn, "SELECT teacher_name, teacher_id, branch_name FROM teacher WHERE teacher_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $teacher_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $teacher = mysqli_fetch_assoc($res);
        $teacher_branch = $teacher['branch_name'];
    }
    mysqli_stmt_close($stmt);
}

if (!$teacher) {
    header('Location: index.php');
    exit();
}

$students = [];
$stmt = mysqli_prepare(
    $conn,
    "SELECT student_name AS name,
            student_id AS id,
            semester,
            branch_name AS branch,
            mobile
     FROM students
     WHERE branch_name = ?
     ORDER BY student_name"
);

mysqli_stmt_bind_param($stmt, "s", $teacher_branch);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$students = [];

while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Attendance System</title>
    <link rel="stylesheet" href="Teacher.css">
    <style>
        /* minimal inline styles in case Teacher.css missing */
        .container {
            max-width: 100%;
            margin: 20px auto;
            padding: 20px
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .teacher-info {
            display: flex;
            gap: 12px;
            margin: 12px 0
        }

        .info-box {
            flex: 1
        }

        input[readonly] {
            background: #f5f5f5;
            padding: 8px;
            width: 100%
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left
        }

        .present {
            color: green
        }

        .absent {
            color: red
        }

        .not-marked {
            color: #777;
        }

        .action-buttons {
            margin-top: 12px
        }

        .date-wrapper {
            display: inline-flex;
            flex-direction: column;
            max-width: 240px;
        }

        input[type="date"]:invalid {
            border-color: #e00;
        }

        .biometric-control {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            min-width: 300px;
            padding: 10px 14px;
            border-radius: 12px;
            background: #f1f5f9;
            border: 1px solid #dbe3ef
        }

        .biometric-control-text {
            display: flex;
            align-items: center;
            gap: 10px
        }

        .biometric-icon {
            font-size: 25px
        }

        .biometric-control-text strong {
            display: block;
            font-size: 13px
        }

        .biometric-control-text small {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 11px
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 54px;
            height: 30px;
            flex: 0 0 auto
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0
        }

        .slider {
            position: absolute;
            inset: 0;
            cursor: pointer;
            background: #94a3b8;
            border-radius: 999px;
            transition: .25s
        }

        .slider:before {
            content: "";
            position: absolute;
            width: 22px;
            height: 22px;
            left: 4px;
            top: 4px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .2);
            transition: .25s
        }

        .switch input:checked+.slider {
            background: #16a34a
        }

        .switch input:checked+.slider:before {
            transform: translateX(24px)
        }

        .biometric-control.enabled {
            background: #ecfdf5;
            border-color: #86efac
        }

        .biometric-control.disabled {
            background: #f8fafc
        }
    </style>
    <link rel="stylesheet" href="Load.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📚 Teacher Dashboard Govt.Polytechnic Kandhamal</h1>
            <a class="logoutBtn" href="index.php">Logout</a>
        </div>

        <div class="teacher-info">
            <div class="info-box">
                <label>Teacher Name</label>
                <input type="text" id="teacherName" readonly value="<?php echo htmlspecialchars($teacher['teacher_name'] ?? ''); ?>">
            </div>
            <div class="info-box">
                <label>Teacher ID</label>
                <input type="text" id="teacherId" readonly value="<?php echo htmlspecialchars($teacher['teacher_id'] ?? $teacher_id); ?>">
            </div>
            <div class="info-box">
                <label>Department</label>
                <input type="text" id="department" readonly value="<?php echo htmlspecialchars($teacher['branch_name'] ?? ''); ?>">
            </div>
        </div>

        <div class="controls">
            <div class="date-wrapper">
                <input type="date" id="currentDate" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                <!-- <div id="dateHelp" class="date-help"></div>
                <div id="disabledDates" class="disabled-dates"></div> -->
            </div>
            <input type="text" id="search" placeholder="Search by Name or ID">
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

        <div class="stats">
            <div class="stat-card">
                <div class="label">Total Students</div>
                <div class="value" id="totalStudents">0</div>
            </div>
            <div class="stat-card">
                <div class="label">Present</div>
                <div class="value" id="presentCount">0</div>
            </div>
            <div class="stat-card">
                <div class="label">Absent</div>
                <div class="value" id="absentCount">0</div>
            </div>
        </div>

        <div id="attendanceLockMessage" class="attendance-lock-message" hidden>
            Attendance has already been saved for today and cannot be edited.
        </div>

        <div class="action-buttons">
            <button class="saveBtn" id="saveAttendance" name="save_attendance">💾 Save Attendance</button>
            <button class="resetBtn" id="resetAttendance">🔄 Reset</button>
            <button class="ViewBtn" onclick="window.location.href='view_attendance.php'">
                🖨️ View Attendance
            </button>
            <button class="CountBtn"
                onclick="window.location.href='count_attendance.php'">
                📊 Count Attendance
            </button>
            <button class="ViewBtn" onclick="window.location.href='view_holidays.php'">
                📅 View Holidays
            </button>
            <div class="biometric-control">
                <div class="biometric-control-text">
                    <span class="biometric-icon">🖐️</span>
                    <div>
                        <strong>Biometric Attendance</strong>
                        <small id="biometricStatusText">OFF — Fingerprint attendance is disabled</small>
                    </div>
                </div>
                <label class="switch" title="Allow biometric attendance">
                    <input type="checkbox" id="biometricAttendanceToggle">
                    <span class="slider"></span>
                </label>
            </div>

            <button class="ViewBtn" onclick="window.location.href='biometric.php'">
                🖐️ Add Biometric
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>SL No.</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Semester</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="studentTable"></tbody>
        </table>

    </div>
    <script src="holidays.js"></script>

    <script>
        // ============================
        // Student Data from PHP
        // ============================

        let students = <?php echo json_encode($students); ?>;

        const table = document.getElementById("studentTable");
        const searchInput = document.getElementById("search");
        const semesterSelect = document.getElementById("semester");

        const totalStudents = document.getElementById("totalStudents");
        const presentCount = document.getElementById("presentCount");
        const absentCount = document.getElementById("absentCount");
        const serverCurrentDate = <?php echo json_encode(date('Y-m-d')); ?>;
        // ========================================
        // AUTO LOAD STUDENTS WHEN TEACHER LOGS IN
        // ========================================

        document.addEventListener("DOMContentLoaded", function() {

            // Make sure "All Semesters" is selected
            semesterSelect.value = "";

            // Automatically display all students
            // belonging to the teacher's department
            loadStudents();

        });

        const attendance = {};
        const lockedStudents = new Set();
        let lastValidDate = "";
        let attendanceLocked = false;

        function updateAttendanceLock(locked) {
            attendanceLocked = locked;
            document.getElementById("attendanceLockMessage").hidden = !locked;
            document.getElementById("saveAttendance").disabled = locked;
            document.getElementById("resetAttendance").disabled = locked;
            document.querySelectorAll(".presentBtn, .absentBtn").forEach(button => {
                button.disabled = locked || lockedStudents.has(button.dataset.studentId);
            });
        }

        function clearAttendanceTable() {
            table.innerHTML = "";
            totalStudents.innerHTML = 0;
            presentCount.innerHTML = 0;
            absentCount.innerHTML = 0;
        }

        function checkAttendanceDate(date) {
            if (!date) {
                document.getElementById("saveAttendance").disabled = true;
                return false;
            }

            const isHoliday = Array.isArray(holidays) && holidays.includes(date);
            const day = new Date(date + 'T00:00:00').getDay();

            if (isHoliday) {
                alert("Attendance cannot be taken on a holiday.");
                document.getElementById("currentDate").value = lastValidDate || "";
                clearAttendanceTable();
                document.getElementById("saveAttendance").disabled = true;
                return false;
            }

            if (day === 0) {
                alert("Attendance cannot be taken on Sunday.");
                document.getElementById("currentDate").value = lastValidDate || "";
                clearAttendanceTable();
                document.getElementById("saveAttendance").disabled = true;
                return false;
            }

            lastValidDate = date;
            document.getElementById("saveAttendance").disabled = false;

            const dateHelp = document.getElementById("dateHelp");
            if (dateHelp) {
                dateHelp.textContent = "";
            }

            return true;
        }

        function isDateDisabled(date) {
            if (!date) return false;
            const day = new Date(date + 'T00:00:00').getDay();
            return day === 0 || (Array.isArray(holidays) && holidays.includes(date));
        }

        function formatDisabledDateList(maxItems = 20) {
            if (!Array.isArray(holidays) || holidays.length === 0) {
                return 'Only Sundays are disabled.';
            }
            const visible = holidays.slice(0, maxItems);
            const more = holidays.length > maxItems ? ` and ${holidays.length - maxItems} more` : '';
            return `Disabled dates: Sundays plus holidays ${visible.join(', ')}${more}.`;
        }

        function updateDisabledDatesText() {
            const disabled = document.getElementById('disabledDates');
            disabled.textContent = formatDisabledDateList(10);
        }

        // ============================
        // Reset attendance for selected date
        // ============================

        function resetAttendanceForDate() {

            students.forEach(student => {
                if (!lockedStudents.has(student.id)) {
                    attendance[student.id] = "Not marked";
                }
            });

            loadStudents();

        }

        // ============================
        // Draw Student Table
        // ============================

        function loadStudents() {

            table.innerHTML = "";

            let search = searchInput.value.toLowerCase();
            let semester = semesterSelect.value;

            let filtered = students.filter(student => {

                let matchSearch =
                    student.name.toLowerCase().includes(search) ||
                    student.id.toLowerCase().includes(search);

                let matchSemester =
                    semester === "" || student.semester === semester;

                return matchSearch && matchSemester;
            });


            // ========================================
            // SHOW "NOT FOUND" IF NO STUDENT EXISTS
            // ========================================

            if (filtered.length === 0) {

                table.innerHTML = `
            <tr>
                <td colspan="7" style="
                    text-align: center;
                    padding: 30px;
                    font-size: 18px;
                    font-weight: 600;
                    color: #777;
                ">
                    Student data not found
                </td>
            </tr>
        `;

                totalStudents.innerHTML = 0;
                presentCount.innerHTML = 0;
                absentCount.innerHTML = 0;

                return;
            }


            // ========================================
            // LOAD STUDENT ROWS
            // ========================================

            filtered.forEach((student, index) => {

                if (!attendance[student.id]) {
                    attendance[student.id] = "Not marked";
                }

                let row = `
            <tr>

                <td>${index + 1}</td>

                <td>${student.id}</td>

                <td>${student.name}</td>

                <td>${student.mobile}</td>

                <td>${student.semester}</td>

                <td>
                    <span
                        id="status-${student.id}"
                        class="status ${attendance[student.id]
                            .toLowerCase()
                            .replace(/\s+/g,'-')}">
                        ${attendance[student.id]}
                    </span>
                </td>

                <td>

                    <button
                        class="presentBtn"
                        data-student-id="${student.id}"
                        ${attendanceLocked ? 'disabled' : ''}
                        onclick="markAttendance('${student.id}','Present')">
                        Present
                    </button>

                    <button
                        class="absentBtn"
                        data-student-id="${student.id}"
                        ${attendanceLocked ? 'disabled' : ''}
                        onclick="markAttendance('${student.id}','Absent')">
                        Absent
                    </button>

                </td>

            </tr>
        `;

                table.innerHTML += row;

            });

            updateStats(filtered);
        }

        // ============================
        // Mark Attendance
        // ============================

        function markAttendance(studentId, status) {

            if (lockedStudents.has(studentId)) {
                alert("You can't edit previous attendance.");
                return;
            }

            if (attendanceLocked) {
                return;
            }

            attendance[studentId] = status;

            const badge = document.getElementById("status-" + studentId);

            if (badge) {
                badge.innerHTML = status;
                const cls = status.toLowerCase().replace(/\s+/g, '-');
                badge.className = 'status ' + cls;
            }

            // Update counts and re-render
            updateStats(students.filter(s => {
                let search = searchInput.value.toLowerCase();
                return (s.name.toLowerCase().includes(search) || s.id.toLowerCase().includes(search)) && (semesterSelect.value === '' || s.semester === semesterSelect.value);
            }));

        }

        // ============================
        // Statistics
        // ============================

        function updateStats(list) {

            let present = 0;
            let absent = 0;

            list.forEach(student => {

                if (attendance[student.id] === "Present")
                    present++;
                else if (attendance[student.id] === "Absent")
                    absent++;

            });

            totalStudents.innerHTML = list.length;
            presentCount.innerHTML = present;
            absentCount.innerHTML = absent;

        }

        // ============================
        // Search & Filter
        // ============================

        searchInput.addEventListener("keyup", loadStudents);

        semesterSelect.addEventListener("change", loadStudents);

        // When date changes, fetch attendance for that date
        document.getElementById("currentDate").addEventListener("input", function() {
            const dateValue = this.value;

            if (dateValue > serverCurrentDate) {
                alert("Future attendance cannot be marked.");
                this.value = serverCurrentDate;
                this.setCustomValidity("Future attendance cannot be marked.");
                lockedStudents.clear();
                document.getElementById("saveAttendance").disabled = true;
                return;
            }

            this.setCustomValidity('');

            if (isDateDisabled(dateValue)) {
                this.setCustomValidity('Invalid date');
            } else {
                this.setCustomValidity('');
            }

            if (!checkAttendanceDate(dateValue)) {
                return;
            }

            fetchAttendanceForDate(dateValue);
        });

        function checkAttendanceDate(date) {
            if (!date) return false;

            const day = new Date(date + "T00:00:00").getDay();

            if (day === 0) {
                alert("Sunday attendance cannot be marked.");
                document.getElementById("currentDate").value = "";
                lockedStudents.clear();
                document.getElementById("saveAttendance").disabled = true;
                return false;
            }

            if (Array.isArray(holidays) && holidays.includes(date)) {
                alert("Holiday attendance cannot be marked.");
                document.getElementById("currentDate").value = "";
                lockedStudents.clear();
                document.getElementById("saveAttendance").disabled = true;
                return false;
            }

            document.getElementById("saveAttendance").disabled = false;
            return true;
        }

        function fetchAttendanceForDate(date) {
            if (!date) {
                resetAttendanceForDate();
                return;
            }

            fetch('fetch_attendance.php?date=' + encodeURIComponent(date))
                .then(resp => resp.json())
                .then(data => {
                    if (data.success && data.attendance_record) {
                        updateAttendanceLock(false);
                        lockedStudents.clear();
                        students.forEach(s => attendance[s.id] = 'Not marked');
                        Object.keys(data.attendance_record).forEach(sid => {
                            attendance[sid] = data.attendance_record[sid];
                            lockedStudents.add(sid);
                        });
                        loadStudents();
                    } else {
                        updateAttendanceLock(false);
                        lockedStudents.clear();
                        resetAttendanceForDate();
                    }
                })
                .catch(err => {
                    console.error(err);
                    lockedStudents.clear();
                    resetAttendanceForDate();
                });
        }

        // ============================
        // Save Attendance
        // ============================

        document.getElementById("saveAttendance").addEventListener("click", function() {

            const currentDateInput = document.getElementById("currentDate");
            const selectedDate = currentDateInput ? currentDateInput.value : "";

            if (selectedDate > serverCurrentDate) {
                alert("Future attendance cannot be marked.");
                return;
            }

            if (!checkAttendanceDate(selectedDate)) {
                return;
            }


            // ========================================
            // ONE-TIME ATTENDANCE WARNING
            // ========================================

            const confirmSave = confirm(
                "⚠️ Remember!\n\n" +
                "You will only be able to mark attendance ONE TIME for all students on this date.\n\n" +
                "After saving, the attendance cannot be edited again.\n\n" +
                "Do you want to continue?"
            );

            if (!confirmSave) {
                return;
            }


            // ========================================
            // PREPARE ATTENDANCE DATA
            // ========================================

            const attendanceData = [];

            students.forEach(student => {

                if (!lockedStudents.has(student.id)) {

                    attendanceData.push({
                        student_id: student.id,
                        status: attendance[student.id] === 'Not marked' ?
                            'Absent' :
                            attendance[student.id]
                    });

                }

            });


            if (attendanceData.length === 0) {
                alert("All students already have attendance saved for this date.");
                return;
            }


            // ========================================
            // SAVE ATTENDANCE
            // ========================================

            fetch("save_attendance.php", {

                    method: "POST",

                    headers: {
                        "Content-Type": "application/json"
                    },

                    body: JSON.stringify({
                        attendance_date: selectedDate,
                        attendance_record: attendanceData
                    })

                })

                .then(response => response.json())

                .then(result => {

                    if (result.success) {

                        fetchAttendanceForDate(selectedDate);

                        alert(
                            result.message +
                            "\nSaved : " +
                            result.saved_records
                        );

                    } else {

                        alert(result.message);

                    }

                })

                .catch(error => {

                    console.error(error);
                    alert("Server Error");

                });

        });

        // ============================
        // Reset
        // ============================

        document.getElementById("resetAttendance").addEventListener("click", function() {

            if (!confirm("Reset attendance for this date?"))
                return;

            students.forEach(student => {
                if (!lockedStudents.has(student.id)) {
                    attendance[student.id] = "Not marked";
                }
            });

            loadStudents();

        });

        // ============================

        const biometricToggle = document.getElementById("biometricAttendanceToggle");
        const biometricControl = document.querySelector(".biometric-control");
        const biometricStatusText = document.getElementById("biometricStatusText");

        function updateBiometricUI(enabled) {
            biometricToggle.checked = enabled;
            biometricControl.classList.toggle("enabled", enabled);
            biometricControl.classList.toggle("disabled", !enabled);
            biometricStatusText.textContent = enabled ?
                "ON — Fingerprint attendance is allowed" :
                "OFF — Fingerprint attendance is disabled";
        }

        async function loadBiometricAttendanceControl() {
            try {
                const r = await fetch("biometric_attendance_control.php?action=status&_=" + Date.now(), {
                    cache: "no-store"
                });
                const d = await r.json();
                if (d.success) updateBiometricUI(d.enabled === true);
            } catch (e) {
                console.error(e);
                updateBiometricUI(false);
            }
        }

        biometricToggle.addEventListener("change", async function() {
            const enabled = this.checked;
            updateBiometricUI(enabled);
            try {
                const r = await fetch("biometric_attendance_control.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        enabled: enabled
                    })
                });
                const d = await r.json();
                if (!d.success) {
                    updateBiometricUI(!enabled);
                    alert(d.message || "Unable to change biometric attendance mode.");
                    return;
                }
                updateBiometricUI(d.enabled === true);
            } catch (e) {
                console.error(e);
                updateBiometricUI(!enabled);
                alert("Server Error: biometric attendance mode was not changed.");
            }
        });

        loadBiometricAttendanceControl();

        updateDisabledDatesText();
        semesterSelect.value = "";

        loadStudents();
        fetchAttendanceForDate(serverCurrentDate);
    </script>
    <?php require_once __DIR__ . '/loader.php'; ?>
</body>

</html>