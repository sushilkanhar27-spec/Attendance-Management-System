<?php
session_start();
include "db_connect.php";

// Check Admin Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$message = "";
$messageType = "";

if (isset($_POST['add_department'])) {

    $department = trim($_POST['branch_name']);

    if ($department == "") {

        $message = "Department name is required.";
        $messageType = "error";
    } else {

        // Check Duplicate
        $check = mysqli_query($conn, "SELECT * FROM departments WHERE branch_name='$department'");

        if (mysqli_num_rows($check) > 0) {

            $message = "Department already exists.";
            $messageType = "error";
        } else {

            $insert = mysqli_query($conn, "INSERT INTO departments(branch_name) VALUES('$department')");

            if ($insert) {

                echo "<script>
                        alert('Department added successfully.');
                        window.location='manage_departments.php';
                      </script>";
                exit();
            } else {

                $message = "Something went wrong.";
                $messageType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Add Department</title>

    <link rel="stylesheet" href="admin_dashboard.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .form-box {
            width: 500px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
        }

        .form-box h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn:hover {
            background: #218838;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            color: #0d6efd;
            font-weight: bold;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>

    <link rel="stylesheet" href="Load.css">
</head>

<body>

    <div class="form-box">

        <h2>
            <i class="fa fa-building"></i>
            Add Department
        </h2>

        <?php
        if ($message != "") {
        ?>

            <div class="<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>

        <?php
        }
        ?>

        <form method="POST">

            <div class="form-group">

                <label>Department Name</label>

                <input
                    type="text"
                    name="branch_name"
                    placeholder="Enter Department Name"
                    required>

            </div>

            <button class="btn" name="add_department">

                <i class="fa fa-plus"></i>

                Add Department

            </button>

        </form>

        <a class="back" href="manage_departments.php">

            <i class="fa fa-arrow-left"></i>

            Back to Manage Departments

        </a>

    </div>

    <?php require_once __DIR__ . '/loader.php'; ?>
</body>

</html>