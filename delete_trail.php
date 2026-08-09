<?php
session_start();
include 'includes/db_connect.php';

if (
    !isset($_SESSION['loggedin']) ||
    $_SESSION['loggedin'] !== true ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$trail_id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM trails WHERE trail_id = ?");
$stmt->bind_param("i", $trail_id);
$stmt->execute();

header("Location: admin_dashboard.php");
exit();
?>