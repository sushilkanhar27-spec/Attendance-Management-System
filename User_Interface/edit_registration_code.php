<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$message = "";

if (isset($_POST['save'])) {

    $admin_code = mysqli_real_escape_string($conn, $_POST['admin_code']);
    $teacher_code = mysqli_real_escape_string($conn, $_POST['teacher_code']);

    mysqli_query($conn, "UPDATE registration_codes
                        SET reg_code='$admin_code'
                        WHERE role='admin'");

    mysqli_query($conn, "UPDATE registration_codes
                        SET reg_code='$teacher_code'
                        WHERE role='teacher'");

    $message = "Registration Codes Updated Successfully!";
}

$result = mysqli_query($conn, "SELECT * FROM registration_codes");

$codes = [];

while ($row = mysqli_fetch_assoc($result)) {
    $codes[$row['role']] = $row['reg_code'];
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Registration Codes</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f4f4;
        }

        .container {
            width: 450px;
            margin: 50px auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
        }

        button {
            padding: 10px 20px;
            background: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        .btn-back {
            background: #6c757d;
            text-decoration: none;
            padding: 10px 20px;
            color: #fff;
            border-radius: 5px;
            display: inline-block;
            margin-top: 10px;

        }

        .success {
            color: green;
            margin-bottom: 15px;
        }
    </style>

    <link rel="stylesheet" href="Load.css">
</head>

<body>

    <div class="container">

        <h2>Edit Registration Codes</h2>

        <?php if ($message != "") { ?>
            <p class="success"><?php echo $message; ?></p>
        <?php } ?>

        <form method="POST">

            <label>Admin Registration Code</label>

            <input type="text"
                name="admin_code"
                value="<?php echo $codes['admin']; ?>"
                required>

            <label>Teacher Registration Code</label>

            <input type="text"
                name="teacher_code"
                value="<?php echo $codes['teacher']; ?>"
                required>

            <button type="submit" name="save">
                Save Changes
            </button>

            <a href="admin_dashboard.php" class="btn-back">
                Back to Dashboard
            </a>

        </form>

    </div>

    <?php require_once __DIR__ . '/loader.php'; ?>
</body>

</html>