<?php
include "db_connect.php";

$admin_id = "ADMIN001";
$admin_name = "Principal";
$password = password_hash("admin123", PASSWORD_DEFAULT);

$sql = "INSERT INTO admin (admin_id, admin_name, password)
        VALUES ('$admin_id', '$admin_name', '$password')";

if (mysqli_query($conn, $sql)) {
    echo "Admin account created successfully!";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>