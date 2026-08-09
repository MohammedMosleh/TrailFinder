<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: 'trailfinder_db';
$port = (int) (getenv('DB_PORT') ?: 3307);

$conn = new mysqli($host, $user, $pass, $dbname,$port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to support Hebrew correctly
$conn->set_charset("utf8mb4");
?>