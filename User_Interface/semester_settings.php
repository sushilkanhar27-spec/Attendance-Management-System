<?php

include "db_connect.php";

if (isset($_POST['save'])) {

    $semester = $_POST['semester_name'];

    $year = $_POST['academic_year'];

    $start = $_POST['start_date'];

    $end = $_POST['end_date'];

    mysqli_query($conn, "UPDATE semester_settings SET status='Inactive'");

    mysqli_query($conn, "INSERT INTO semester_settings
    (
        semester_name,
        academic_year,
        start_date,
        end_date,
        status
    )
    VALUES
    (
        '$semester',
        '$year',
        '$start',
        '$end',
        'Active'
    )");

    echo "<script>alert('Semester Saved Successfully');</script>";
}

?>
<!DOCTYPE html>
<html>

<head>

    <title>Semester Settings</title>

    <link rel="stylesheet" href="semester_settings.css">

</head>

<body>

    <div class="container">

        <div class="card">

            <a href="admin_dashboard.php" class="back-btn">
                ← Back to Dashboard
            </a>

            <h2>Semester Settings</h2>

            <form method="POST">

                <div class="form-group">

                    <label>Semester</label>

                    <select name="semester_name" required>

                        <option value="">Select Semester</option>

                        <option value="1st Semester">1st Semester</option>

                        <option value="2nd Semester">2nd Semester</option>

                        <option value="3rd Semester">3rd Semester</option>

                        <option value="4th Semester">4th Semester</option>

                        <option value="5th Semester">5th Semester</option>

                        <option value="6th Semester">6th Semester</option>

                    </select>

                </div>

                <div class="form-group">
                    <label>Academic Year</label>
                    <input
                        type="text"
                        name="academic_year"
                        placeholder="Example : 2026-27"
                        required>
                </div>

                <div class="form-group">
                    <label>Attendance Start Date</label>
                    <input
                        type="date"
                        name="start_date"
                        required>
                </div>

                <div class="form-group">
                    <label>Attendance Close Date</label>
                    <input
                        type="date"
                        name="end_date"
                        required>
                </div>

                <button class="btn" type="submit" name="save">
                    Save Semester
                </button>

            </form>

        </div>

    </div>

</body>

</html>