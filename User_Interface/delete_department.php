<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_departments.php");
    exit();
}

$id = (int)$_GET['id'];

$department = mysqli_query($conn, "SELECT * FROM departments WHERE branch_id='$id'");

if (mysqli_num_rows($department) == 0) {
    die("Department not found.");
}

$data = mysqli_fetch_assoc($department);

$message = "";

if (isset($_POST['delete_department'])) {

    $password = $_POST['password'];

    $admin_id = $_SESSION['admin_id'];

    $admin = mysqli_query($conn, "SELECT * FROM admin WHERE admin_id='$admin_id'");

    $adminData = mysqli_fetch_assoc($admin);

    // If password is hashed use:
    // $valid = password_verify($password,$adminData['password']);

    // If password is plain text use:
    // If password is stored as plain text
    $valid = password_verify($password, $adminData['password']);

    // If later you use password_hash(), replace it with:
    // $valid = password_verify($password, $adminData['password']);

    if (!$valid) {

        $message = "Incorrect Admin Password.";
    } else {

        mysqli_begin_transaction($conn);

        try {

            $dept = mysqli_real_escape_string($conn, $data['branch_name']);

            $deleteAttendance = mysqli_query($conn, "
DELETE attendance_record
FROM attendance_record
INNER JOIN students
ON attendance_record.student_id = students.student_id
WHERE students.branch_name = '$dept'
");

            if (!$deleteAttendance) {
                throw new Exception(mysqli_error($conn));
            }

            $deleteStudents = mysqli_query($conn, "
DELETE FROM students
WHERE branch_name='$dept'
");

            if (!$deleteStudents) {
                throw new Exception(mysqli_error($conn));
            }

            $deleteDepartment = mysqli_query($conn, "
DELETE FROM departments
WHERE branch_id='$id'
");

            if (!$deleteDepartment) {
                throw new Exception(mysqli_error($conn));
            }

            mysqli_commit($conn);

            echo "<script>

            alert('Department and all related data deleted successfully.');

            window.location='manage_departments.php';

            </script>";

            exit();
        } catch (Exception $e) {

            mysqli_rollback($conn);

             $message = "Delete failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Delete Department</title>

    <link rel="stylesheet" href="admin_dashboard.css">

    <style>
        body {

            background: #f5f5f5;

            font-family: Arial;

        }

        .box {

            width: 550px;

            margin: 60px auto;

            background: white;

            padding: 30px;

            border-radius: 12px;

            box-shadow: 0 0 15px rgba(167, 36, 36, 0.2);

        }

        .warning {

            background: #fff3cd;

            border-left: 6px solid red;

            padding: 18px;

            margin-bottom: 20px;

        }

        .warning h2 {

            color: red;

            margin-top: 0;

        }

        .warning ul {

            margin: 10px 0 0 20px;

        }

        input[type=password] {

            width: 100%;

            padding: 12px;

            font-size: 16px;

            margin-top: 10px;

            margin-bottom: 20px;

        }

        .btn {

            padding: 12px 20px;

            border: none;

            cursor: pointer;

            font-size: 16px;

            border-radius: 5px;

        }

        .delete {

            background: red;

            color: white;

        }

        .cancel {

            background: #777;

            color: white;

            text-decoration: none;

            padding: 12px 20px;

            border-radius: 5px;

            margin-left: 10px;

        }

        .error {

            background: #f8d7da;

            padding: 12px;

            margin-bottom: 15px;

            color: #721c24;

        }
    </style>

    <link rel="stylesheet" href="Load.css">
</head>

<body>

    <div class="box">

        <div class="warning">

            <h2>⚠ Warning</h2>

            <p>

                You are about to permanently delete

                <b><?php echo htmlspecialchars($data['branch_name']); ?></b>

            </p>

            <ul>

                <li>All students of this department</li>

                <li>All attendance records</li>

                <li>This department entry</li>

                <li>This action cannot be undone</li>

            </ul>

        </div>

        <?php

        if ($message != "") {

            echo "<div class='error'>$message</div>";
        }

        ?>

        <form method="POST"

            onsubmit="return confirm('Are you absolutely sure? This action cannot be undone.');">

            <label>

                <b>Enter Admin Password</b>

            </label>

            <input

                type="password"

                name="password"

                required>

            <button

                class="btn delete"

                name="delete_department">

                Delete Forever

            </button>

            <a

                href="manage_departments.php"

                class="cancel">

                Cancel

            </a>

        </form>

    </div>

    <?php require_once __DIR__ . '/loader.php'; ?>
</body>

</html>