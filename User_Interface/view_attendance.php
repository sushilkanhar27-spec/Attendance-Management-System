<?php
include "db_connect.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Previous Attendance</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f4f4;
            margin: 20px;
        }

        .container {
            width: 95%;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
        }

        h2 {
            text-align: center;
        }

        .date-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter {
            margin-bottom: 20px;
        }

        .filter {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .filter input,
        .filter select,
        .filter button {
            padding: 10px;
            margin-right: 0;
            margin-bottom: 0;
        }

        .filter .form-group {
            display: flex;
            flex-direction: column;
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

        th {

            background: #667eea;
            color: white;

        }

        /* Keep container layout consistent */
        .container {
            width: 95%;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
        }

        .present {

            color: green;
            font-weight: bold;

        }

        .absent {

            color: red;
            font-weight: bold;

        }
        .download-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #8798e5;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>

</head>

<body>

    <div class="container">

        <h2>Previous Attendance</h2>

        <div class="filter">

            <div class="date-container">
                <!-- From Date Input -->
                <div class="form-group">
                    <label for="fromDate">From:</label>
                    <input type="date" id="fromDate" name="from_date">
                </div>

                <!-- To Date Input -->
                <div class="form-group">
                    <label for="toDate">To:</label>
                    <input type="date" id="toDate" name="to_date">
                </div>
            </div>

            <select id="semester">

                <option value="">All Semester</option>
                <option value="1st Semester">1st Semester</option>
                <option value="2nd Semester">2nd Semester</option>
                <option value="3rd Semester">3rd Semester</option>
                <option value="4th Semester">4th Semester</option>
                <option value="5th Semester">5th Semester</option>
                <option value="6th Semester">6th Semester</option>

            </select>

            <button onclick="loadAttendance()">Search</button>

        </div>
    </div>
    <div class="container">

        <table>
            <thead>
                <tr>
                    <th>SL No.</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Semester</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="attendanceTable"></tbody>
        </table>
    </div>

    <button onclick="downloadExcel()" class="download-btn">
        📥 Download Excel
    </button>

    <script>
        function loadAttendance() {
            const fromDate = document.getElementById("fromDate").value;
            const toDate = document.getElementById("toDate").value;
            const semester = document.getElementById("semester").value;

            const params = new URLSearchParams();
            if (fromDate) params.append('from', fromDate);
            if (toDate) params.append('to', toDate);
            if (semester) params.append('semester', semester);

            fetch("get_previous_attendance.php?" + params.toString())
                .then(res => {
                    console.log('get_previous_attendance.php status', res.status);
                    return res.text();
                })
                .then(data => {
                    const tbody = document.getElementById("attendanceTable");
                    if (!data || !data.trim()) {
                        tbody.innerHTML = '<tr><td colspan="6">No records found</td></tr>';
                    } else {
                        tbody.innerHTML = data;
                    }
                })
                .catch(err => {
                    console.error('Failed to load attendance', err);
                    const tbody = document.getElementById("attendanceTable");
                    tbody.innerHTML = '<tr><td colspan="6">Error loading data</td></tr>';
                });
        }

        function downloadExcel() {

            const fromDate = document.getElementById("fromDate").value;
            const toDate = document.getElementById("toDate").value;
            const semester = document.getElementById("semester").value;

            let url = "download_excel.php?";

            if (fromDate != "")
                url += "from=" + encodeURIComponent(fromDate) + "&";

            if (toDate != "")
                url += "to=" + encodeURIComponent(toDate) + "&";

            if (semester != "")
                url += "semester=" + encodeURIComponent(semester);

            window.location = url;

        }

        window.onload = loadAttendance;
    </script>

</body>

</html>