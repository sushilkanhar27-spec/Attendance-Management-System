<?php
session_start();
include "db_connect.php";

// Check Admin Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch Departments
$departments = mysqli_query($conn, "SELECT * FROM departments ORDER BY branch_name ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Departments</title>

    <link rel="stylesheet" href="admin_dashboard.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .page-box {
            width: 95%;
            margin: 25px auto;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, .12);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .page-header h2 {
            color: #333;
        }

        .add-btn {
            background: #28a745;
            color: #fff;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        .add-btn:hover {
            background: #218838;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #0d6efd;
            color: #fff;
            padding: 12px;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
        }

        .delete-btn:hover {
            background: #bb2d3b;
        }

        .empty {
            text-align: center;
            color: #777;
            padding: 25px;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
        }

        .back-btn:hover {
            background: #555;
        }
    </style>

</head>

<body>

    <div class="sidebar">

        <h2>Attendance System</h2>

        <ul>

            <li><a href="admin_dashboard.php"><i class="fa fa-home"></i> Dashboard</a></li>

            <li><a href="manage_students.php"><i class="fa fa-user-graduate"></i> Students</a></li>

            <li><a href="manage_teachers.php"><i class="fa fa-chalkboard-teacher"></i> Teachers</a></li>

            <li><a href="manage_departments.php"><i class="fa fa-building"></i> Departments</a></li>

            <li><a href="admin_view_attendance.php"><i class="fa fa-calendar-check"></i> Attendance</a></li>

            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>

        </ul>

    </div>

    <div class="main">

        <div class="page-box">

            <div class="page-header">

                <h2><i class="fa fa-building"></i> Manage Departments</h2>

                <div>

                    <a href="admin_dashboard.php" class="back-btn">
                        <i class="fa fa-arrow-left"></i> Dashboard
                    </a>

                    <a href="add_department.php" class="add-btn">
                        <i class="fa fa-plus"></i> Add Department
                    </a>

                </div>

            </div>

            <table>

                <tr>
                    <th>SL No.</th>
                    <th>Department Name</th>
                    <th>Created Date</th>
                    <th>Action</th>
                </tr>

                <?php

                if (mysqli_num_rows($departments) > 0) {

                    $i = 1;

                    while ($row = mysqli_fetch_assoc($departments)) {
                ?>

                        <tr>

                            <td><?php echo $i++; ?></td>

                            <td><?php echo htmlspecialchars($row['branch_name']); ?></td>

                            <td><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>

                            <td>

                                <a class="delete-btn"
                                    href="delete_department.php?id=<?php echo $row['branch_id']; ?>">
                                    <i class="fa fa-trash"></i>
                                    Delete
                                </a>

                            </td>

                        </tr>

                    <?php
                    }
                } else {
                    ?>

                    <tr>

                        <td colspan="4" class="empty">

                            No Departments Found

                        </td>

                    </tr>

                <?php
                }
                ?>

            </table>

        </div>

    </div>

</body>

</html>