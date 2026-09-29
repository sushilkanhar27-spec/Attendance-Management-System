<?php
include "db_connect.php";

$sql = "SELECT holiday_date, holiday_name
        FROM holidays
        ORDER BY holiday_date ASC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Holiday List</title>

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
        }

        .container {
            width: 800px;
            margin: 30px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #1565C0;
            color: white;
        }

        tr:nth-child(even) {
            background: #f8f8f8;
        }

        h2 {
            text-align: center;
        }
    </style>

    <link rel="stylesheet" href="Load.css">
</head>

<body>

    <div class="container">

        <h2>Government Holiday List</h2>

        <table>

            <tr>
                <th>Sl No.</th>
                <th>Date</th>
                <th>Holiday Name</th>
            </tr>
            <?php
            $i = 1;
            while ($row = mysqli_fetch_assoc($result)) {
            ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo date("d M Y (l)", strtotime($row['holiday_date'])); ?></td>
                    <td><?php echo htmlspecialchars($row['holiday_name']); ?></td>
                </tr>
            <?php
            }
            ?>
        </table>
    </div>
    <?php require_once __DIR__ . '/loader.php'; ?>
</body>

</html>