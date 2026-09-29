<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location:index.php");
    exit();
}

// Delete Teacher
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    mysqli_query($conn, "DELETE FROM teacher WHERE teacher_id='$id'");

    header("Location:manage_teachers.php");
    exit();
}

// Fetch Teachers
$result = mysqli_query($conn, "SELECT * FROM teacher ORDER BY teacher_name ASC");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Teachers</title>

    <link rel="stylesheet" href="admin_dashboard.css">

    <style>
        .container {
            width: 95%;
            margin: auto;
            margin-top: 30px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .add-btn {
            background: #007bff;
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        table th {
            background: #343a40;
            color: white;
        }

        .edit {
            background: green;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
        }

        .delete {
            background: red;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>

    <link rel="stylesheet" href="Load.css">
</head>

<body>

    <div class="container">

        <div class="top">

            <h2>Manage Teachers</h2>

            <a href="add_teacher.php" class="add-btn">
                + Add Teacher
            </a>

        </div>

        <table>

            <tr>

                <th>ID</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>Department</th>
                <th>Action</th>

            </tr>

            <?php

            while ($row = mysqli_fetch_assoc($result)) {

            ?>

                <tr>

                    <td><?php echo $row['teacher_id']; ?></td>

                    <td><?php echo $row['teacher_name']; ?></td>

                    <td><?php echo $row['mobile']; ?></td>

                    <td><?php echo $row['branch_name']; ?></td>

                    <td>

                        <a class="edit"
                            href="edit_teacher.php?id=<?php echo $row['teacher_id']; ?>">
                            Edit
                        </a>

                        <a class="delete"
                            onclick="return confirm('Delete this teacher?')"
                            href="?delete=<?php echo $row['teacher_id']; ?>">
                            Delete
                        </a>

                    </td>

                </tr>

            <?php
            }
            ?>

        </table>

    </div>

    <?php require_once __DIR__ . '/loader.php'; ?>
</body>

</html>