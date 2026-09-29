<?php
session_start();
include "db_connect.php";

// Fetch departments
$departments = [];

$dept_result = mysqli_query($conn, "SELECT branch_name FROM departments ORDER BY branch_name ASC");

while ($dept = mysqli_fetch_assoc($dept_result)) {
    $departments[] = $dept;
}

// Check Admin Login
if (!isset($_SESSION['admin_id'])) {
    header("Location:index.php");
    exit();
}

$message = "";

if (isset($_POST['add_teacher'])) {
    $teacher_id   = mysqli_real_escape_string($conn, $_POST['teacher_id']);
    $teacher_name = mysqli_real_escape_string($conn, $_POST['teacher_name']);
    $mobile       = mysqli_real_escape_string($conn, $_POST['mobile']);
    $password     = mysqli_real_escape_string($conn, $_POST['password']);
    $branch_name = mysqli_real_escape_string($conn, $_POST['branch_name']);

    // Check duplicate Teacher ID
    $check = mysqli_query($conn, "SELECT * FROM teacher WHERE teacher_id='$teacher_id'");

    if (mysqli_num_rows($check) > 0) {
        $message = "Teacher ID already exists!";
    } else {
        $insert = mysqli_query($conn, "
        INSERT INTO teacher
(
teacher_id,
teacher_name,
mobile,
password,
branch_name
)
VALUES
(
'$teacher_id',
'$teacher_name',
'$mobile',
'$password',
'$branch_name'
)
        ");

        if ($insert) {
            $message = "Teacher Added Successfully!";
        } else {
            $message = "Failed to Add Teacher!";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Add Teacher</title>

    <link rel="stylesheet" href="admin_dashboard.css">

    <style>
        .container {
            width: 500px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, .2);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #0056b3;
        }

        .success {
            color: green;
            text-align: center;
            margin-bottom: 15px;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
    </style>

    <link rel="stylesheet" href="Load.css">
</head>

<body>

    <div class="container">

        <h2>Add Teacher</h2>

        <?php

        if ($message != "") {
            if ($message == "Teacher Added Successfully!") {
                echo "<p class='success'>$message</p>";
            } else {
                echo "<p class='error'>$message</p>";
            }
        }

        ?>

        <form method="POST">

            <input
                type="text"
                name="teacher_id"
                placeholder="Teacher ID"
                required>

            <input
                type="text"
                name="teacher_name"
                placeholder="Teacher Name"
                required>

            <input
                type="text"
                name="mobile"
                placeholder="Mobile Number"
                maxlength="10"
                required>

            <input
                type="password"
                name="password"
                placeholder="Password"
                required>

            <select name="branch_name" required>

                <option value="">Select Department</option>

                <?php foreach ($departments as $dept) { ?>

                    <option value="<?php echo htmlspecialchars($dept['branch_name']); ?>">
                        <?php echo htmlspecialchars($dept['branch_name']); ?>
                    </option>

                <?php } ?>

            </select>

            <button type="submit" name="add_teacher">
                Add Teacher
            </button>

        </form>

        <a class="back" href="manage_teachers.php">
            ← Back to Manage Teachers
        </a>

    </div>

    <?php require_once __DIR__ . '/loader.php'; ?>
</body>

</html>