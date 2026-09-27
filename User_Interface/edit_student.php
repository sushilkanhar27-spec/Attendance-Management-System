<?php
session_start();
include "db_connect.php";

// Fetch Departments
$departments = [];

$dept_result = mysqli_query($conn, "SELECT branch_name FROM departments ORDER BY branch_name ASC");

while ($dept = mysqli_fetch_assoc($dept_result)) {
    $departments[] = $dept;
}

// Check Admin Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Check Student ID
if (!isset($_GET['id'])) {
    header("Location: manage_students.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$result = mysqli_query($conn, "SELECT * FROM students WHERE student_id='$id'");

if (mysqli_num_rows($result) == 0) {
    die("Student Not Found.");
}

$row = mysqli_fetch_assoc($result);

// Update Student
if (isset($_POST['update'])) {

    $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $mobile       = mysqli_real_escape_string($conn, $_POST['mobile']);
    $branch       = mysqli_real_escape_string($conn, $_POST['branch_name']);
    $semester     = mysqli_real_escape_string($conn, $_POST['semester']);
    $password     = mysqli_real_escape_string($conn, $_POST['password']);

    $update = mysqli_query($conn, "
        UPDATE students
        SET
        student_name='$student_name',
        mobile='$mobile',
        branch_name='$branch',
        semester='$semester',
        password='$password'
        WHERE student_id='$id'
    ");

    if ($update) {

        echo "<script>
        alert('Student Updated Successfully');
        window.location='manage_students.php';
        </script>";
        exit();
    } else {

        echo "<script>
        alert('Update Failed');
        </script>";
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Edit Student</title>

    <link rel="stylesheet" href="admin_dashboard.css">

    <style>
        body {

            background: #f5f5f5;

        }

        .container {

            width: 700px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, .2);

        }

        h2 {

            text-align: center;
            margin-bottom: 25px;

        }

        table {

            width: 100%;

        }

        td {

            padding: 10px;

        }

        input,
        select {

            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;

        }

        .btn {

            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            font-size: 15px;

        }

        .update {

            background: #28a745;

        }

        .back {

            background: #007bff;

        }

        .readonly {

            background: #eeeeee;

        }
    </style>

</head>

<body>

    <div class="container">

        <h2>Edit Student</h2>

        <form method="POST">

            <table>

                <tr>

                    <td>Student ID</td>

                    <td>

                        <input
                            type="text"
                            value="<?php echo $row['student_id']; ?>"
                            readonly
                            class="readonly">

                    </td>

                </tr>

                <tr>

                    <td>Student Name</td>

                    <td>

                        <input
                            type="text"
                            name="student_name"
                            value="<?php echo $row['student_name']; ?>"
                            required>

                    </td>

                </tr>

                <tr>

                    <td>Mobile Number</td>

                    <td>

                        <input
                            type="text"
                            name="mobile"
                            maxlength="10"
                            pattern="[0-9]{10}"
                            value="<?php echo $row['mobile']; ?>"
                            required>

                    </td>

                </tr>

                <tr>

                    <td>Department</td>

                    <td>

                        <select name="branch_name" required>

                            <option value="">Select Department</option>

                            <?php foreach ($departments as $dept) { ?>

                                <option
                                    value="<?php echo htmlspecialchars($dept['branch_name']); ?>"
                                    <?php if ($row['branch_name'] == $dept['branch_name']) echo "selected"; ?>>

                                    <?php echo htmlspecialchars($dept['branch_name']); ?>

                                </option>

                            <?php } ?>

                        </select>

                    </td>

                </tr>

                <tr>

                    <td>Semester</td>

                    <td>

                        <select name="semester" required>

                            <option value="1" <?php if ($row['semester'] == "1") echo "selected"; ?>>1st Semester</option>

                            <option value="2" <?php if ($row['semester'] == "2") echo "selected"; ?>>2nd Semester</option>

                            <option value="3" <?php if ($row['semester'] == "3") echo "selected"; ?>>3rd Semester</option>

                            <option value="4" <?php if ($row['semester'] == "4") echo "selected"; ?>>4th Semester</option>

                            <option value="5" <?php if ($row['semester'] == "5") echo "selected"; ?>>5th Semester</option>

                            <option value="6" <?php if ($row['semester'] == "6") echo "selected"; ?>>6th Semester</option>

                        </select>

                    </td>

                </tr>

                <tr>

                    <td>Password</td>

                    <td>

                        <input
                            type="text"
                            name="password"
                            value="<?php echo $row['password']; ?>"
                            required>

                    </td>

                </tr>

                <tr>

                    <td colspan="2" align="center">

                        <button
                            type="submit"
                            name="update"
                            class="btn update">

                            Update Student

                        </button>

                       <a href="manage_students1.php" class="btn back">
                        Back
                       </a>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</body>
</html>