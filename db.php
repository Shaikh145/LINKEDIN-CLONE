<?php
// Database connection
$host = "localhost";
$dbname = "dbul5nkrbalmwq";
$username = "uklz9ew3hrop3";
$password = "zyrbspyjlzjb";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper handling of special characters
$conn->set_charset("utf8mb4");
?>
