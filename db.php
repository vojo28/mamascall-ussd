<?php
$host = "localhost";        // From phpMyAdmin
$username = "elevate_Mamascall";         // Your DB username
$password = "Vu2ij^1pn76@"; // Your DB password
$db_name = "elevate_Users";  // Your database name

$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
