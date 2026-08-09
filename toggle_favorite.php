<?php
session_start();

include "includes/db_connect.php";

/* Only normal users can use favorites */
if (
    !isset($_SESSION['loggedin']) ||
    $_SESSION['loggedin'] !== true ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'user'
) {
    header("Location: index.php");
    exit();
}

/* Check request */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['trail_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$trail_id = intval($_POST['trail_id']);
$return_url = isset($_POST['return_url']) ? $_POST['return_url'] : 'index.php';

/* Small protection: allow redirect only inside our project */
if (
    strpos($return_url, 'http://') === 0 ||
    strpos($return_url, 'https://') === 0 ||
    strpos($return_url, '//') === 0
) {
    $return_url = 'index.php';
}

/* Make sure the trail exists */
$stmt = $conn->prepare("SELECT trail_id FROM trails WHERE trail_id = ?");
$stmt->bind_param("i", $trail_id);
$stmt->execute();
$trail_result = $stmt->get_result();

if ($trail_result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

/* Check if already favorite */
$stmt = $conn->prepare("
    SELECT fav_id 
    FROM favorites 
    WHERE user_id = ? AND trail_id = ?
");
$stmt->bind_param("ii", $user_id, $trail_id);
$stmt->execute();
$result = $stmt->get_result();

/* Toggle favorite */
if ($result && $result->num_rows > 0) {

    $stmt = $conn->prepare("
        DELETE FROM favorites 
        WHERE user_id = ? AND trail_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $trail_id);
    $stmt->execute();

} else {

    $stmt = $conn->prepare("
        INSERT IGNORE INTO favorites (user_id, trail_id) 
        VALUES (?, ?)
    ");
    $stmt->bind_param("ii", $user_id, $trail_id);
    $stmt->execute();

}

header("Location: " . $return_url);
exit();
?>