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

$all_tags_query = $conn->query("SELECT * FROM tags ORDER BY tag_name ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $region = trim($_POST['region']);
    $difficulty = trim($_POST['difficulty']);
    $location_coords = trim($_POST['location_coords']);
    $image_url = trim($_POST['image_url']);

    if (empty($name) || empty($description) || empty($region) || empty($difficulty)) {
        $error_message = "נא למלא את כל שדות החובה.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO trails 
            (name, description, region, difficulty, location_coords, image_url, views) 
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ");

        $stmt->bind_param(
            "ssssss",
            $name,
            $description,
            $region,
            $difficulty,
            $location_coords,
            $image_url
        );

        if ($stmt->execute()) {

            $new_trail_id = $conn->insert_id;

            if (!empty($_POST['existing_tags']) && is_array($_POST['existing_tags'])) {

                $tag_stmt = $conn->prepare("
                    INSERT IGNORE INTO trail_tags (trail_id, tag_id) 
                    VALUES (?, ?)
                ");

                foreach ($_POST['existing_tags'] as $tag_id) {
                    $tag_id = intval($tag_id);
                    $tag_stmt->bind_param("ii", $new_trail_id, $tag_id);
                    $tag_stmt->execute();
                }
            }

            if (!empty($_POST['new_tags'])) {

                $new_tags_array = explode(',', $_POST['new_tags']);

                $check_tag_stmt = $conn->prepare("
                    SELECT tag_id 
                    FROM tags 
                    WHERE tag_name = ?
                ");

                $insert_tag_stmt = $conn->prepare("
                    INSERT INTO tags (tag_name) 
                    VALUES (?)
                ");

                $connect_tag_stmt = $conn->prepare("
                    INSERT IGNORE INTO trail_tags (trail_id, tag_id) 
                    VALUES (?, ?)
                ");

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

                        $connect_tag_stmt->bind_param("ii", $new_trail_id, $tag_id);
                        $connect_tag_stmt->execute();
                    }
                }
            }

            header("Location: admin_dashboard.php");
            exit();

        } else {
            $error_message = "אירעה שגיאה בשמירת המסלול.";
        }
    }
}

$page_title = "הוסף מסלול | TrailFinder";
include 'includes/header.php';
?>

<div class="container py-5 mt-5" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-md-9">

            <div class="card shadow-lg border-0 rounded-4 p-4 p-md-5">

                <h3 class="fw-bold mb-4 text-success border-bottom pb-2">
                    הוספת מסלול וניהול תגיות
                </h3>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form action="add_trail.php" method="POST">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-bold">שם המסלול</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">אזור</label>
                            <select name="region" class="form-select" required>
                                <option value="">בחר אזור</option>
                                <option value="צפון">צפון</option>
                                <option value="מרכז">מרכז</option>
                                <option value="דרום">דרום</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">רמת קושי</label>
                            <select name="difficulty" class="form-select" required>
                                <option value="">בחר רמת קושי</option>
                                <option value="קל">קל</option>
                                <option value="בינוני">בינוני</option>
                                <option value="קשה">קשה</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">קואורדינטות / מיקום</label>
                            <input type="text" name="location_coords" class="form-control" placeholder="לדוגמה: 33.037,35.250">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">קישור לתמונה</label>
                            <input type="text" name="image_url" class="form-control" placeholder="לדוגמה: assets/images/name.jpg">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">תיאור המסלול</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>

                        <div class="col-md-12 my-3">
                            <label class="form-label fw-bold d-block">בחר תגיות קיימות:</label>

                            <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-3">
                                <?php if ($all_tags_query && $all_tags_query->num_rows > 0): ?>
                                    <?php while ($tag = $all_tags_query->fetch_assoc()): ?>
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="existing_tags[]"
                                                value="<?php echo intval($tag['tag_id']); ?>"
                                                id="tag_<?php echo intval($tag['tag_id']); ?>"
                                            >

                                            <label class="form-check-label" for="tag_<?php echo intval($tag['tag_id']); ?>">
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
                            <label class="form-label fw-bold">
                                הוסף תגיות חדשות
                            </label>

                            <input
                                type="text"
                                name="new_tags"
                                class="form-control"
                                placeholder="לדוגמה: פריחה, נוף פנורמי, מומלץ לילדים"
                            >

                            <small class="text-muted">
                                יש להפריד בין תגיות חדשות באמצעות פסיק.
                            </small>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-pill">
                                💾 שמור מסלול וקשר תגיות
                            </button>
                        </div>

                    </div>

                </form>

            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>