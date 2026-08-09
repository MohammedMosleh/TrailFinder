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

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$review_id = intval($_GET['id']);
$status = $_GET['status'];

$allowed_statuses = ['pending', 'approved', 'rejected'];

if (!in_array($status, $allowed_statuses)) {
    header("Location: admin_dashboard.php");
    exit();
}

$stmt = $conn->prepare("
    UPDATE reviews
    SET status = ?
    WHERE review_id = ?
");

$stmt->bind_param("si", $status, $review_id);
$stmt->execute();

header("Location: admin_dashboard.php");
exit();
?>