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

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['trail_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $region = trim($_POST['region']);
    $difficulty = trim($_POST['difficulty']);
    $location_coords = trim($_POST['location_coords']);
    $image_url = trim($_POST['image_url']);
    $booking_required = isset($_POST['booking_required']) ? $_POST['booking_required'] : 'no';
    $booking_link = isset($_POST['booking_link']) ? trim($_POST['booking_link']) : '';

    if (empty($id) || empty($name) || empty($description) || empty($region) || empty($difficulty)) {
        $error_message = "נא למלא את כל שדות החובה.";
    } else {
        $stmt = $conn->prepare("
            UPDATE trails
            SET name = ?, 
                description = ?, 
                region = ?, 
                difficulty = ?, 
                location_coords = ?, 
                image_url = ?,
                booking_required = ?,
                booking_link = ?
            WHERE trail_id = ?
        ");

        $stmt->bind_param(
            "ssssssssi",
            $name,
            $description,
            $region,
            $difficulty,
            $location_coords,
            $image_url,
            $booking_required,
            $booking_link,
            $id
        );

        if ($stmt->execute()) {
            $delete_stmt = $conn->prepare("DELETE FROM trail_tags WHERE trail_id = ?");
            $delete_stmt->bind_param("i", $id);
            $delete_stmt->execute();

            if (!empty($_POST['existing_tags']) && is_array($_POST['existing_tags'])) {
                $tag_stmt = $conn->prepare("
                    INSERT IGNORE INTO trail_tags (trail_id, tag_id)
                    VALUES (?, ?)
                ");
                foreach ($_POST['existing_tags'] as $tag_id) {
                    $tag_id = intval($tag_id);
                    $tag_stmt->bind_param("ii", $id, $tag_id);
                    $tag_stmt->execute();
                }
            }

            if (!empty($_POST['new_tags'])) {
                $new_tags_array = explode(',', $_POST['new_tags']);
                $check_tag_stmt = $conn->prepare("SELECT tag_id FROM tags WHERE tag_name = ?");
                $insert_tag_stmt = $conn->prepare("INSERT INTO tags (tag_name) VALUES (?)");
                $connect_tag_stmt = $conn->prepare("INSERT IGNORE INTO trail_tags (trail_id, tag_id) VALUES (?, ?)");

                foreach ($new_tags_array as $tag_name) {
                    $tag_name = trim($tag_name);
                    if (!empty($tag_name)) {
                        $check_tag_stmt->bind_param("s", $tag_name);
                        $check_tag_stmt->execute();
                        $check_result = $check_tag_stmt->get_result();

                        if ($check_result->num_rows > 0) {
                            $tag_row = $check_result->fetch_assoc();
                            $tag_id = intval($tag_row['tag_id']);
                        } else {
                            $insert_tag_stmt->bind_param("s", $tag_name);
                            $insert_tag_stmt->execute();
                            $tag_id = $conn->insert_id;
                        }

                        $connect_tag_stmt->bind_param("ii", $id, $tag_id);
                        $connect_tag_stmt->execute();
                    }
                }
            }

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error_message = "אירעה שגיאה בעדכון המסלול.";
        }
    }
}

if (isset($_GET['id'])) {
    $trail_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM trails WHERE trail_id = ?");
    $stmt->bind_param("i", $trail_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $trail = $result->fetch_assoc();
    } else {
        header("Location: admin_dashboard.php");
        exit();
    }
} else {
    header("Location: admin_dashboard.php");
    exit();
}

$all_tags_query = $conn->query("SELECT * FROM tags ORDER BY tag_name ASC");
$current_tags_array = [];
$stmt = $conn->prepare("SELECT tag_id FROM trail_tags WHERE trail_id = ?");
$stmt->bind_param("i", $trail_id);
$stmt->execute();
$current_tags_result = $stmt->get_result();

while ($ct = $current_tags_result->fetch_assoc()) {
    $current_tags_array[] = intval($ct['tag_id']);
}

$page_title = "ערוך מסלול - TrailFinder";
include 'includes/header.php';
?>

<div class="container py-5 mt-5" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow-lg border-0 rounded-4 p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0 text-primary">
                        ערוך מסלול: <?php echo htmlspecialchars($trail['name']); ?>
                    </h3>
                    <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill fw-bold">
                        חזור ללוח הבקרה
                    </a>
                </div>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form action="edit_trail.php" method="POST">
                    <input type="hidden" name="trail_id" value="<?php echo intval($trail['trail_id']); ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">שם המסלול</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($trail['name']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">אזור</label>
                            <select name="region" class="form-select" required>
                                <option value="">בחר אזור</option>
                                <option value="צפון" <?php echo ($trail['region'] == 'צפון') ? 'selected' : ''; ?>>צפון</option>
                                <option value="מרכז" <?php echo ($trail['region'] == 'מרכז') ? 'selected' : ''; ?>>מרכז</option>
                                <option value="דרום" <?php echo ($trail['region'] == 'דרום') ? 'selected' : ''; ?>>דרום</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">רמת קושי</label>
                            <select name="difficulty" class="form-select" required>
                                <option value="">בחר רמת קושי</option>
                                <option value="קל" <?php echo ($trail['difficulty'] == 'קל') ? 'selected' : ''; ?>>קל</option>
                                <option value="בינוני" <?php echo ($trail['difficulty'] == 'בינוני') ? 'selected' : ''; ?>>בינוני</option>
                                <option value="קשה" <?php echo ($trail['difficulty'] == 'קשה') ? 'selected' : ''; ?>>קשה</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">קואורדינטות / מיקום</label>
                            <input type="text" name="location_coords" class="form-control" value="<?php echo htmlspecialchars($trail['location_coords']); ?>" placeholder="לדוגמה: 33.037,35.250">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">קישור לתמונה</label>
                            <input type="text" name="image_url" class="form-control" value="<?php echo htmlspecialchars($trail['image_url']); ?>" placeholder="לדוגמה: assets/images/name.jpg">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">האם נדרש תיאום מראש?</label>
                            <select name="booking_required" class="form-select">
                                <option value="no" <?php echo (isset($trail['booking_required']) && $trail['booking_required'] == 'no') ? 'selected' : ''; ?>>לא נדרש</option>
                                <option value="yes" <?php echo (isset($trail['booking_required']) && $trail['booking_required'] == 'yes') ? 'selected' : ''; ?>>כן, נדרש תיאום</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">קישור להזמנת מקום (אם נדרש)</label>
                            <input type="url" name="booking_link" class="form-control" value="<?php echo htmlspecialchars($trail['booking_link'] ?? ''); ?>" placeholder="https://...">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">תיאור המסלול</label>
                            <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($trail['description']); ?></textarea>
                        </div>

                        <div class="col-md-12 my-3">
                            <label class="form-label fw-bold d-block">ערוך תגיות למסלול זה:</label>
                            <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-3">
                                <?php if ($all_tags_query && $all_tags_query->num_rows > 0): ?>
                                    <?php while ($tag = $all_tags_query->fetch_assoc()): ?>
                                        <?php
                                            $tag_id = intval($tag['tag_id']);
                                            $checked = in_array($tag_id, $current_tags_array) ? 'checked' : '';
                                        ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="existing_tags[]" value="<?php echo $tag_id; ?>" id="tag_<?php echo $tag_id; ?>" <?php echo $checked; ?>>
                                            <label class="form-check-label" for="tag_<?php echo $tag_id; ?>">
                                                <?php echo htmlspecialchars($tag['tag_name']); ?>
                                            </label>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-muted mb-0">אין תגיות קיימות.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">הוסף תגיות חדשות</label>
                            <input type="text" name="new_tags" class="form-control" placeholder="לדוגמה: פריחה, נוף פנורמי">
                            <small class="text-muted">יש להפריד בין תגיות חדשות באמצעות פסיק.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill mt-4">
                        🔄 עדכן מסלול ותגיות
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>