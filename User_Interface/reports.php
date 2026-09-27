<?php
session_start();
include "db_connect.php";

// Check Admin Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Get Admin Name
$admin_id = $_SESSION['admin_id'];
$result = mysqli_query($conn, "SELECT admin_name FROM admin WHERE admin_id='$admin_id'");
$admin = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Reports Dashboard</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="reports.css">
</head>

<body>

    <div class="sidebar">

        <h2>Attendance System</h2>

        <ul>

            <li><a href="admin_dashboard.php"><i class="fa fa-home"></i> Dashboard</a></li>

            <li><a href="manage_students1.php"><i class="fa fa-user-graduate"></i> Students</a></li>

            <li><a href="manage_teachers.php"><i class="fa fa-chalkboard-teacher"></i> Teachers</a></li>

            <li><a href="admin_view_attendance.php"><i class="fa fa-calendar-check"></i> Attendance</a></li>

            <li class="active"><a href="reports.php"><i class="fa fa-chart-line"></i> Reports</a></li>

            <li><a href="manage_departments.php"><i class="fa fa-building"></i> Departments</a></li>

            <li><a href="holidays.php"><i class="fa fa-calendar"></i> Holidays</a></li>

            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>

        </ul>

    </div>


    <div class="main">

        <div class="topbar">

            <h2>Welcome <?php echo htmlspecialchars($admin['admin_name']); ?></h2>

            <p><?php echo date("d M Y"); ?></p>

        </div>

        <h1 class="title">Attendance Reports</h1>

        <div class="cards">

            <a href="student_report.php" class="card blue">
                <i class="fa fa-user-graduate"></i>
                <h3>Student Report</h3>
                <p>Student Attendance</p>
            </a>

            <a href="daily_report.php" class="card green">
                <i class="fa fa-calendar-day"></i>
                <h3>Daily Report</h3>
                <p>Attendance by Date</p>
            </a>

            <a href="monthly_report.php" class="card orange">
                <i class="fa fa-calendar-alt"></i>
                <h3>Monthly Report</h3>
                <p>Monthly Attendance</p>
            </a>

            <a href="department_report.php" class="card purple">
                <i class="fa fa-building"></i>
                <h3>Department Report</h3>
                <p>Department Summary</p>
            </a>

            <a href="low_attendance.php" class="card red">
                <i class="fa fa-triangle-exclamation"></i>
                <h3>Low Attendance</h3>
                <p>Below 75%</p>
            </a>

            <a href="print_reports.php" class="card teal">
                <i class="fa fa-print"></i>
                <h3>Print Reports</h3>
                <p>Print Attendance</p>
            </a>

        </div>

    </div>

</body>

</html>