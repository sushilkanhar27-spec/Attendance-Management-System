<?php
date_default_timezone_set('Asia/Kolkata');

$host = "localhost";
$user = "root";
$password = "";
$database = "attendance";
$port = 3306;

$conn = mysqli_connect($host, $user, $password, $database, $port);
if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

mysqli_query($conn, "SET time_zone = '+05:30'");
