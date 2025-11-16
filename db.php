<?php
$host = "premium187.web-hosting.com"; // MySQL host from phpMyAdmin
$username = "elevate_Mamascall"; // Your DB username
$password = "Vu2ij^1pn76@";     // Your DB password
$db_name = "elevate_Users";      // Your database name

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    // Set error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected successfully";
} catch(PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage();
    exit;
}
?>
