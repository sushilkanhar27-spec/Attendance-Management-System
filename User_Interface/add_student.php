<?php
session_start();
include "db_connect.php";

// Get all departments
$departments = [];

$result = mysqli_query($conn, "SELECT branch_name FROM departments ORDER BY branch_name");

while ($row = mysqli_fetch_assoc($result)) {
    $departments[] = $row;
}

// Check Admin Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $student_id   = trim($_POST['student_id']);
    $student_name = trim($_POST['student_name']);
    $mobile       = trim($_POST['mobile']);
    $branch       = trim($_POST['branch_name']);
    $semester     = trim($_POST['semester']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if Student ID already exists
    $check = mysqli_query($conn, "SELECT * FROM students WHERE student_id='$student_id'");

    if (mysqli_num_rows($check) > 0) {

        $message = "<div style='color:red;font-weight:bold;text-align:center;'>
                        Student ID already exists.
                    </div>";
    } else {

        $branch_name = trim($_POST['branch_name']);

        $sql = "INSERT INTO students
(student_id, student_name, mobile, branch_name, semester, password)
VALUES
('$student_id','$student_name','$mobile','$branch_name','$semester','$password')";

        if (mysqli_query($conn, $sql)) {

            echo "<script>
                    alert('Student Added Successfully');
                    window.location='add_student.php';
                  </script>";
            exit();
        } else {

            $message = "<div style='color:red;text-align:center;'>
                        Error : " . mysqli_error($conn) . "
                      </div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Add Student</title>

    <link rel="stylesheet" href="admin_dashboard.css">

    <style>
        .container {

            width: 700px;
            margin: 40px auto;
            background: #fff;
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

        }

        .btn {

            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            font-size: 15px;

        }

        .save {

            background: #28a745;

        }

        .back {

            background: #007bff;

        }
    </style>

</head>

<body>

    <div class="container">

        <h2>Add New Student</h2>

        <?php echo $message; ?>

        <form method="POST">

            <table>

                <tr>

                    <td>Student ID</td>

                    <td>
                        <input
                            type="text"
                            name="student_id"
                            required>
                    </td>

                </tr>

                <tr>

                    <td>Student Name</td>

                    <td>
                        <input
                            type="text"
                            name="student_name"
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
                            required>
                    </td>

                </tr>

                <tr>

                    <td>Department</td>

                    <td>

                        <select name="branch_name" required>

                            <option value="">Select Department</option>

                            <?php foreach ($departments as $dept) { ?>

                                <option value="<?php echo $dept['branch_name']; ?>">
                                    <?php echo $dept['branch_name']; ?>
                                </option>

                            <?php } ?>

                        </select>

                    </td>

                </tr>

                <tr>

                    <td>Semester</td>

                    <td>

                        <select name="semester" required>

                            <option value="">Select Semester</option>

                            <option value="1">1st Semester</option>

                            <option value="2">2nd Semester</option>

                            <option value="3">3rd Semester</option>

                            <option value="4">4th Semester</option>

                            <option value="5">5th Semester</option>

                            <option value="6">6th Semester</option>

                        </select>

                    </td>

                </tr>

                <tr>

                    <td>Password</td>

                    <td>

                        <input
                            type="password"
                            name="password"
                            required>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="center">
                        <button
                            type="submit"
                            class="btn save">
                            Save Student
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