<?php
session_start();
include "db_connect.php";

// Check Admin Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Get Departments
$departmentQuery = mysqli_query($conn, "SELECT DISTINCT branch_name FROM students ORDER BY branch_name");

// Selected Filters
$branch = isset($_GET['branch_name']) ? $_GET['branch_name'] : "";
$semester = isset($_GET['semester']) ? $_GET['semester'] : "";

// Student Query
// The students table in this project stores semester values such as
// "1st Semester", "2nd Semester", etc., so the filter must use those
// exact database values.
$sql = "SELECT * FROM students WHERE 1";

if ($branch !== "") {
    $branchEscaped = mysqli_real_escape_string($conn, $branch);
    $sql .= " AND branch_name='$branchEscaped'";
}

if ($semester !== "") {
    $semesterEscaped = mysqli_real_escape_string($conn, $semester);
    $sql .= " AND semester='$semesterEscaped'";
}

$sql .= " ORDER BY semester, student_name";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Student query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Manage Students</title>


    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #f4f7fb;
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2937;
        }

        .main {
            max-width: 1400px;
            margin: 0 auto;
            background: #ffffff;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .main h2 {
            margin: 0 0 22px;
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }

        form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding: 18px;
            margin-bottom: 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        form label {
            font-weight: 600;
            color: #334155;
        }

        form select {
            min-width: 180px;
            padding: 10px 38px 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: #334155;
            font-size: 14px;
            outline: none;
            cursor: pointer;
        }

        form select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        form input[type="submit"],
        form a button {
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        form input[type="submit"] {
            background: #2563eb;
            color: #fff;
        }

        form input[type="submit"]:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        form a {
            text-decoration: none;
        }

        form a button {
            background: #16a34a;
            color: #fff;
        }

        form a button:hover {
            background: #15803d;
            transform: translateY(-1px);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
        }

        table th {
            padding: 14px 12px;
            background: #1e293b;
            color: #fff;
            text-align: left;
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
        }

        table td {
            padding: 13px 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            vertical-align: middle;
        }

        table tr:hover td {
            background: #f8fafc;
        }

        table td:first-child {
            font-weight: 600;
        }

        table td:last-child {
            white-space: nowrap;
        }

        table td a {
            text-decoration: none;
            display: inline-block;
            margin: 2px;
        }

        table td button {
            border: none;
            border-radius: 6px;
            padding: 8px 11px;
            font-size: 12px;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: 0.2s ease;
        }

        table td button:hover {
            transform: translateY(-1px);
            opacity: 0.92;
        }

        table td a:nth-child(1) button {
            background: #2563eb;
        }

        table td a:nth-child(2) button {
            background: #dc2626;
        }

        table td a:nth-child(3) button {
            background: #7c3aed;
        }

        @media (max-width: 900px) {
            body {
                padding: 15px;
            }

            .main {
                padding: 18px;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            form {
                align-items: stretch;
            }

            form select,
            form input[type="submit"],
            form a button {
                width: 100%;
            }
        }

        @media (max-width: 600px) {
            .main h2 {
                font-size: 22px;
            }

            body {
                padding: 8px;
            }

            .main {
                padding: 14px;
                border-radius: 10px;
            }

            form {
                padding: 14px;
            }
        }
    </style>

    <link rel="stylesheet" href="Load.css">
</head>

<body>

    <div class="main">

        <h2>Manage Students</h2>

        <br>

        <form method="GET">

            <label>Department</label>

            <select name="branch_name">

                <option value="">All Departments</option>

                <?php
                while ($dept = mysqli_fetch_assoc($departmentQuery)) {
                ?>

                    <option value="<?php echo $dept['branch_name']; ?>"
                        <?php if ($branch == $dept['branch_name']) echo "selected"; ?>>

                        <?php echo $dept['branch_name']; ?>

                    </option>

                <?php
                }
                ?>

            </select>

            &nbsp;&nbsp;

            <label>Semester</label>

            <select name="semester">

                <option value="">All Semester</option>

                <option value="1st Semester" <?php if ($semester == "1st Semester") echo "selected"; ?>>1st Semester</option>
                <option value="2nd Semester" <?php if ($semester == "2nd Semester") echo "selected"; ?>>2nd Semester</option>
                <option value="3rd Semester" <?php if ($semester == "3rd Semester") echo "selected"; ?>>3rd Semester</option>
                <option value="4th Semester" <?php if ($semester == "4th Semester") echo "selected"; ?>>4th Semester</option>
                <option value="5th Semester" <?php if ($semester == "5th Semester") echo "selected"; ?>>5th Semester</option>
                <option value="6th Semester" <?php if ($semester == "6th Semester") echo "selected"; ?>>6th Semester</option>

            </select>

            &nbsp;&nbsp;

            <input type="submit" value="Search">

            <a href="add_student.php">
                <button type="button">Add Student</button>
            </a>

        </form>

        <table border="1" width="100%" cellpadding="10">

            <tr>

                <th>ID</th>

                <th>Name</th>

                <th>Mobile</th>

                <th>Department</th>

                <th>Semester</th>

                <th>Action</th>

            </tr>

            <?php

            while ($row = mysqli_fetch_assoc($result)) {

            ?>

                <tr>

                    <td><?php echo $row['student_id']; ?></td>

                    <td><?php echo $row['student_name']; ?></td>

                    <td><?php echo $row['mobile']; ?></td>

                    <td><?php echo $row['branch_name']; ?></td>

                    <td><?php echo $row['semester']; ?></td>

                    <td>

                        <a href="edit_student.php?id=<?php echo $row['student_id']; ?>">

                            <button>Edit</button>

                        </a>

                        <a href="delete_student.php?id=<?php echo $row['student_id']; ?>"
                            onclick="return confirm('Delete this student?')">

                            <button>Delete</button>

                        </a>

                        <a href="admin_manage_attendance.php?id=<?php echo $row['student_id']; ?>">

                            <button>Edit Attendance</button>

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

<br><br>