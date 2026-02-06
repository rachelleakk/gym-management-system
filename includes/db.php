<?php
// includes/db.php
// Adjust these settings if your XAMPP uses different credentials
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "gym_system";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    // In production do not echo DB errors — log them instead.
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
