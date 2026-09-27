<?php
include "db_connect.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $name = trim($_POST['holiday_name'] ?? '');
    $date = trim($_POST['holiday_date'] ?? '');

    if ($name === '' || $date === '') {
        $message = "Please enter both holiday name and date.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO holidays (holiday_name, holiday_date) VALUES (?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $name, $date);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Holiday added successfully.";
            } else {
                $message = "Failed to add holiday: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Database error: " . mysqli_error($conn);
        }
    }
}

$result = mysqli_query($conn, "SELECT id, holiday_name, holiday_date FROM holidays ORDER BY holiday_date");
$holidays = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $holidays[] = $row;
    }
} else {
    $message = "Unable to load holidays: " . mysqli_error($conn);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Holidays</title>
    <style>
        /* ===========================
   General
=========================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f4f6f9;
            padding: 30px;
        }

        /* ===========================
   Main Container
=========================== */

        .container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .15);
        }

        /* ===========================
   Heading
=========================== */

        h2 {
            text-align: center;
            color: #007bff;
            margin-bottom: 25px;
        }

        /* ===========================
   Message
=========================== */

        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* ===========================
   Form
=========================== */

        form {
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #444;
        }

        input[type=text],
        input[type=date] {

            width: 100%;
            padding: 12px;
            margin-bottom: 18px;

            border: 1px solid #ccc;
            border-radius: 6px;

            font-size: 15px;
        }

        input:focus {

            outline: none;

            border-color: #007bff;

            box-shadow: 0 0 5px rgba(0, 123, 255, .3);

        }

        /* ===========================
   Button
=========================== */

        button {

            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 6px;

            background: #007bff;

            color: white;

            font-size: 16px;

            cursor: pointer;

            transition: .3s;

        }

        button:hover {

            background: #0056b3;

        }

        /* ===========================
   Table
=========================== */

        table {

            width: 100%;

            border-collapse: collapse;

            margin-top: 20px;

        }

        table th {

            background: #007bff;

            color: white;

            padding: 14px;

        }

        table td {

            padding: 12px;

            text-align: center;

            border-bottom: 1px solid #ddd;

        }

        table tr:nth-child(even) {

            background: #f8f9fa;

        }

        table tr:hover {

            background: #eef5ff;

        }

        /* ===========================
   Delete Button
=========================== */

        .delete-btn {

            display: inline-block;

            padding: 8px 15px;

            background: #dc3545;

            color: white;

            text-decoration: none;

            border-radius: 5px;

            transition: .3s;

        }

        .delete-btn:hover {

            background: #b52b38;

        }

        /* ===========================
   Responsive
=========================== */

        @media(max-width:768px) {

            .container {

                width: 95%;
                padding: 20px;

            }

            table {

                font-size: 14px;

            }

            button {

                font-size: 15px;

            }

        }
    </style>
</head>

<body>
    <h2>Manage Holidays</h2>

    <?php if ($message !== '') { ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php } ?>

    <form method="POST">
        <label>Holiday Name</label><br>
        <input type="text" name="holiday_name" required><br><br>

        <label>Holiday Date</label><br>
        <input type="date" name="holiday_date" required><br><br>

        <button type="submit" name="save">Add Holiday</button>
    </form>

    <hr>

    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>Name</th>
            <th>Date</th>
            <th>Delete</th>
        </tr>

        <?php foreach ($holidays as $row) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['holiday_name']); ?></td>
                <td><?php echo htmlspecialchars($row['holiday_date']); ?></td>
                <td><a href="delete_holiday.php?id=<?php echo (int)$row['id']; ?>">Delete</a></td>
            </tr>
        <?php } ?>
    </table>
</body>

</html>