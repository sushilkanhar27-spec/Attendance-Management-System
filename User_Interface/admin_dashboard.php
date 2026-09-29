<?php
session_start();
include "db_connect.php";
include "attendance_functions.php";


// Check Admin Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

$adminQuery = mysqli_query($conn, "SELECT admin_name FROM admin WHERE admin_id='$admin_id'");
$adminData = mysqli_fetch_assoc($adminQuery);

$admin_name = $adminData['admin_name'];

// Total Students
$studentQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM students");
if (!$studentQuery) {
    die("Student query failed: " . mysqli_error($conn));
}
$students = mysqli_fetch_assoc($studentQuery)['total'];

// Total Teachers
$teacherQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM teacher");
if (!$teacherQuery) {
    die("Teacher query failed: " . mysqli_error($conn));
}
$teachers = mysqli_fetch_assoc($teacherQuery)['total'];

// Total Departments
$deptQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM departments"
);
if (!$deptQuery) {
    die("Department query failed: " . mysqli_error($conn));
}
$departments = mysqli_fetch_assoc($deptQuery)['total'];

// Today's Attendance
$date = date("Y-m-d");

$attendanceQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM attendance_record WHERE attendance_date='$date'"
);
if (!$attendanceQuery) {
    die("Attendance query failed: " . mysqli_error($conn));
}
$attendance = mysqli_fetch_assoc($attendanceQuery)['total'];
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="admin_dashboard.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="Load.css">
</head>

<body>

    <div class="sidebar">

        <h2>Attendance System</h2>

        <ul>

            <li><a href="admin_dashboard.php"><i class="fa fa-home"></i> Dashboard</a></li>

            <li><a href="manage_students1.php"><i class="fa fa-user-graduate"></i> Students</a></li>

            <li><a href="manage_teachers.php"><i class="fa fa-chalkboard-teacher"></i> Teachers</a></li>

            <li><a href="view_attendance.php"><i class="fa fa-calendar-check"></i> Attendance</a></li>
            <li>
                <a href="edit_registration_code.php">
                    <i class="fa fa-key"></i>
                    Registration Code
                </a>
            </li>

            <li><a href="index.php"><i class="fa fa-sign-out-alt"></i>Logout</a></li>

        </ul>

    </div>


    <div class="main">

        <div class="topbar">

            <h2>Welcome Admin <?php echo htmlspecialchars($admin_name); ?></h2>

            <p><?php echo date("d M Y"); ?></p>

        </div>

        <div class="cards">

            <div class="card blue">

                <i class="fa fa-user-graduate"></i>

                <h1><?php echo $students; ?></h1>

                <p>Total Students</p>

            </div>

            <div class="card green">

                <i class="fa fa-chalkboard-teacher"></i>

                <h1><?php echo $teachers; ?></h1>

                <p>Total Teachers</p>

            </div>

            <div class="card orange">

                <i class="fa fa-building"></i>

                <h1><?php echo $departments; ?></h1>

                <p>Departments</p>

            </div>

            <div class="card red">

                <i class="fa fa-calendar-check"></i>

                <h1><?php echo $attendance; ?></h1>

                <p>Today's Attendance</p>

            </div>

        </div>


        <div class="menu">

            <a href="manage_students1.php" class="box">

                <i class="fa fa-user-graduate"></i>

                <h3>Manage Students</h3>

            </a>

            <a href="manage_teachers.php" class="box">

                <i class="fa fa-chalkboard-teacher"></i>

                <h3>Manage Teachers</h3>

            </a>

            <a href="admin_view_attendance.php" class="box">

                <i class="fa fa-calendar-check"></i>

                <h3>View Attendance</h3>

            </a>

            <a href="admin_count_attendance.php" class="box">

                <i class="fa fa-bar-chart"></i>

                <h3>Count Attendance</h3>

            </a>

            <a href="manage_departments.php" class="box">

                <i class="fa fa-building"></i>

                <h3>Manage Departments</h3>

            </a>

            <a href="admin_biometric.php" class="box">

                <i class="fa fa-id-card"></i>

                <h3>Manage Biometric</h3>

            </a>

            <a href="holidays.php" class="box">

                <i class="fa fa-calendar"></i>

                <h3>Manage Holidays</h3>

            </a>

            <a href="semester_settings.php" class="box">

                <i class="fa fa-cog"></i>

                <h3> Semester Settings</h3>

            </a>
        </div>

    </div>

    <?php require_once __DIR__ . '/loader.php'; ?>
</body>

</html>