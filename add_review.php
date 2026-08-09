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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['trail_id'])) {
    header("Location: index.php");
    exit();
}

$trail_id = intval($_POST['trail_id']);
$user_id = intval($_SESSION['user_id']);
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : "";

if ($rating < 1 || $rating > 5 || empty($comment)) {
    header("Location: trail_details.php?id=" . $trail_id);
    exit();
}

$stmt = $conn->prepare("SELECT trail_id FROM trails WHERE trail_id = ?");
$stmt->bind_param("i", $trail_id);
$stmt->execute();
$trail_result = $stmt->get_result();

if ($trail_result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$status = "pending";

$stmt = $conn->prepare("
    INSERT INTO reviews (user_id, trail_id, rating, comment, status)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param("iiiss", $user_id, $trail_id, $rating, $comment, $status);

if ($stmt->execute()) {

    $new_review_id = $conn->insert_id;

    if (!empty($_FILES['review_images']['name'][0])) {

        $upload_dir = "uploads/reviews/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = array("image/jpeg", "image/png", "image/jpg", "image/webp");

        $img_stmt = $conn->prepare("
            INSERT INTO review_images (review_id, image_path)
            VALUES (?, ?)
        ");

        for ($i = 0; $i < count($_FILES['review_images']['name']); $i++) {

            if ($_FILES['review_images']['error'][$i] == 0) {

                $tmp_name = $_FILES['review_images']['tmp_name'][$i];
                $file_type = $_FILES['review_images']['type'][$i];
                $file_size = $_FILES['review_images']['size'][$i];

                if (in_array($file_type, $allowed_types) && $file_size <= 5000000) {

                    $ext = pathinfo($_FILES['review_images']['name'][$i], PATHINFO_EXTENSION);
                    $new_file_name = "review_" . $new_review_id . "_" . time() . "_" . $i . "." . $ext;
                    $target_path = $upload_dir . $new_file_name;

                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $img_stmt->bind_param("is", $new_review_id, $target_path);
                        $img_stmt->execute();
                    }
                }
            }
        }
    }

    header("Location: trail_details.php?id=" . $trail_id . "&review=pending");
    exit();

} else {
    header("Location: trail_details.php?id=" . $trail_id);
    exit();
}
?>