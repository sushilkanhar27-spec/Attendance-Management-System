<?php
include "db_connect.php";

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: holidays.php");
    exit();
}

$id = (int) $_GET['id'];
$stmt = mysqli_prepare($conn, "DELETE FROM holidays WHERE id = ?");

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header("Location: holidays.php");
exit();