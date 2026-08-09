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

$report_id = intval($_GET['id']);
$status = $_GET['status'];

$allowed_statuses = ['new', 'checked', 'closed'];

if (!in_array($status, $allowed_statuses)) {
    header("Location: admin_dashboard.php");
    exit();
}

$stmt = $conn->prepare("
    UPDATE reports
    SET status = ?
    WHERE report_id = ?
");

$stmt->bind_param("si", $status, $report_id);
$stmt->execute();

header("Location: admin_dashboard.php");
exit();
?>