<?php
session_start();
include "db_connect.php";

// Fetch departments
$departments = [];

$dept_result = mysqli_query($conn, "SELECT branch_name FROM departments ORDER BY branch_name ASC");

while ($dept = mysqli_fetch_assoc($dept_result)) {
    $departments[] = $dept;
}

// Uncomment if you have admin login
// if(!isset($_SESSION['admin_id'])){
//     header("Location:index.php");
//     exit();
// }
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Admin Attendance Management</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            margin: 0;
        }

        .container {

            width: 95%;
            margin: 30px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .1);

        }

        h2 {

            text-align: center;
            margin-bottom: 20px;
            color: #333;

        }

        .filter {

            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
            margin-bottom: 20px;

        }

        .filter div {

            display: flex;
            flex-direction: column;

        }

        label {

            margin-bottom: 5px;
            font-weight: bold;

        }

        select,
        input {

            padding: 10px;
            width: 180px;

        }

        button {

            padding: 10px 20px;
            border: none;
            cursor: pointer;
            color: white;
            border-radius: 5px;

        }

        .search-btn {

            background: #007bff;

        }

        .download-btn {

            background: #28a745;

        }

        table {

            width: 100%;
            border-collapse: collapse;

        }

        table th,
        table td {

            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;

        }

        table th {

            background: #007bff;
            color: white;

        }

        .present {

            color: green;
            font-weight: bold;

        }

        .absent {

            color: red;
            font-weight: bold;

        }
    </style>

    <link rel="stylesheet" href="Load.css">
</head>

<body>

    <div class="container">

        <h2>Admin Attendance Management</h2>

        <div class="filter">

            <div>

                <label>Department</label>

                <select id="department" name="branch_name" required>

                    <option value="">All Department</option>

                    <?php foreach ($departments as $dept) { ?>

                        <option value="<?php echo htmlspecialchars($dept['branch_name']); ?>">
                            <?php echo htmlspecialchars($dept['branch_name']); ?>
                        </option>

                    <?php } ?>

                </select>

            </div>

            <div>

                <label>Semester</label>

                <select id="semester">

                    <option value="">All Semester</option>

                    <option value="1st Semester">1st Semester</option>

                    <option value="2nd Semester">2nd Semester</option>

                    <option value="3rd Semester">3rd Semester</option>

                    <option value="4th Semester">4th Semester</option>

                    <option value="5th Semester">5th Semester</option>

                    <option value="6th Semester">6th Semester</option>

                </select>

            </div>

            <div>

                <label>From Date</label>

                <input type="date" id="from">

            </div>

            <div>

                <label>To Date</label>

                <input type="date" id="to" max="<?php echo date('Y-m-d'); ?>">

            </div>

            <div>

                <button class="search-btn" onclick="loadAttendance()">

                    Search

                </button>

            </div>

            <div>

                <button class="download-btn" onclick="downloadExcel()">

                    Download Excel

                </button>

            </div>

        </div>

        <table>

            <thead>

                <tr>

                    <th>SL</th>

                    <th>Student ID</th>

                    <th>Name</th>

                    <th>Department</th>

                    <th>Semester</th>

                    <th>Date</th>

                    <th>Status</th>

                </tr>

            </thead>

            <tbody id="attendanceTable">

                <tr>

                    <td colspan="7">

                        Select Filters and Click Search

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <script>
        function loadAttendance() {

            let department = document.getElementById("department").value;
            let semester = document.getElementById("semester").value;
            let from = document.getElementById("from").value;
            let to = document.getElementById("to").value;

            let url = "admin_get_attendance.php?";

            url += "department=" + encodeURIComponent(department);
            url += "&semester=" + encodeURIComponent(semester);
            url += "&from=" + encodeURIComponent(from);
            url += "&to=" + encodeURIComponent(to);

            fetch(url)

                .then(res => res.text())

                .then(data => {

                    document.getElementById("attendanceTable").innerHTML = data;

                });

        }

        function downloadExcel() {

            let department = document.getElementById("department").value;
            let semester = document.getElementById("semester").value;
            let from = document.getElementById("from").value;
            let to = document.getElementById("to").value;

            // Date validation
            if (from === "" || to === "") {
                alert("Please select From Date and To Date.");
                return;
            }

            // Invalid date range
            if (from > to) {
                alert("From Date cannot be greater than To Date.");
                return;
            }

            // Check whether attendance data exists for the selected filters
            let checkUrl = "admin_get_attendance.php?"
                + "department=" + encodeURIComponent(department)
                + "&semester=" + encodeURIComponent(semester)
                + "&from=" + encodeURIComponent(from)
                + "&to=" + encodeURIComponent(to);

            fetch(checkUrl)
                .then(res => res.text())
                .then(data => {

                    // Remove HTML tags and check the returned table content.
                    let plainText = data
                        .replace(/<[^>]*>/g, " ")
                        .replace(/&nbsp;/g, " ")
                        .trim()
                        .toLowerCase();

                    // admin_get_attendance.php returns no-data text when
                    // there is no attendance record for the selected filters.
                    if (
                        plainText === "" ||
                        plainText.includes("no attendance") ||
                        plainText.includes("no data") ||
                        plainText.includes("data is empty") ||
                        plainText.includes("no records found")
                    ) {
                        alert("Data is empty.");
                        return;
                    }

                    // Data exists -> download Excel.
                    window.location = "admin_download_attendance.php?department="
                        + encodeURIComponent(department)
                        + "&semester=" + encodeURIComponent(semester)
                        + "&from=" + encodeURIComponent(from)
                        + "&to=" + encodeURIComponent(to);

                })
                .catch(error => {
                    console.error("Attendance check failed:", error);
                    alert("Unable to check attendance data.");
                });
        }
    </script>

    <?php require_once __DIR__ . '/loader.php'; ?>
</body>

</html>