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
    header("Location:index.php");
    exit();
}

// Check Teacher ID
if (!isset($_GET['id'])) {
    header("Location:manage_teachers.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch Teacher Data
$result = mysqli_query($conn, "SELECT * FROM teacher WHERE teacher_id='$id'");

if (mysqli_num_rows($result) == 0) {
    die("Teacher not found.");
}

$row = mysqli_fetch_assoc($result);

$message = "";

// Update Teacher
if (isset($_POST['update_teacher'])) {
    $teacher_name = mysqli_real_escape_string($conn, $_POST['teacher_name']);
    $mobile       = mysqli_real_escape_string($conn, $_POST['mobile']);
    $password     = mysqli_real_escape_string($conn, $_POST['password']);
    $branch = mysqli_real_escape_string($conn, $_POST['branch_name']);

    $update = mysqli_query($conn, "
    UPDATE teacher
SET
    teacher_name='$teacher_name',
    mobile='$mobile',
    password='$password',
    branch_name='$branch'
WHERE teacher_id='$id'
    ");

    if ($update) {
        header("Location:manage_teachers.php?updated=1");
        exit();
    } else {
        $message = "Update Failed!";
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Edit Teacher</title>

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
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #218838;
        }

        .error {
            color: red;
            text-align: center;
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

        <h2>Edit Teacher</h2>

        <?php
        if ($message != "") {
            echo "<p class='error'>$message</p>";
        }
        ?>

        <form method="POST">

            <label>Teacher ID</label>

            <input
                type="text"
                value="<?php echo $row['teacher_id']; ?>"
                readonly>

            <label>Teacher Name</label>

            <input
                type="text"
                name="teacher_name"
                value="<?php echo $row['teacher_name']; ?>"
                required>

            <label>Mobile</label>

            <input
                type="text"
                name="mobile"
                maxlength="10"
                value="<?php echo $row['mobile']; ?>"
                required>

            <label>Password</label>

            <input
                type="text"
                name="password"
                value="<?php echo $row['password']; ?>"
                required>

            <label>Department</label>
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


            <button type="submit" name="update_teacher">
                Update Teacher
            </button>

        </form>

        <a class="back" href="manage_teachers.php">
            ← Back to Manage Teachers
        </a>

    </div>

    <?php require_once __DIR__ . '/loader.php'; ?>
</body>

</html>