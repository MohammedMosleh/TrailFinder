<?php
session_start();
include "includes/db_connect.php";

if (
    !isset($_SESSION['loggedin']) ||
    $_SESSION['loggedin'] !== true ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'user'
) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['review_id']) || !isset($_POST['trail_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$review_id = intval($_POST['review_id']);
$trail_id = intval($_POST['trail_id']);

$stmt = $conn->prepare("SELECT review_id FROM reviews WHERE review_id = ? AND status = 'approved'");
$stmt->bind_param("i", $review_id);
$stmt->execute();
$review_result = $stmt->get_result();

if ($review_result->num_rows == 0) {
    header("Location: trail_details.php?id=" . $trail_id);
    exit();
}

$stmt = $conn->prepare("
    SELECT like_id
    FROM review_likes
    WHERE review_id = ? AND user_id = ?
");
$stmt->bind_param("ii", $review_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $stmt = $conn->prepare("
        DELETE FROM review_likes
        WHERE review_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $review_id, $user_id);
    $stmt->execute();

} else {

    $stmt = $conn->prepare("
        INSERT INTO review_likes (review_id, user_id)
        VALUES (?, ?)
    ");
    $stmt->bind_param("ii", $review_id, $user_id);
    $stmt->execute();
}

header("Location: trail_details.php?id=" . $trail_id);
exit();
?>