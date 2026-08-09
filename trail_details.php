<?php
session_start();
include "includes/db_connect.php";

function getOpenStatus($hours_str) {
    if (empty($hours_str)) return null;

    if (strpos($hours_str, '24/7') !== false) {
        return true; 
    }

    preg_match_all('/(\d{1,2}:\d{2})/', $hours_str, $matches);
    
    if (count($matches[0]) >= 2) {
        date_default_timezone_set('Asia/Jerusalem'); 
        $now = strtotime(date('H:i'));
        $open_time = strtotime($matches[0][0]);
        $close_time = strtotime($matches[0][1]);

        if ($now >= $open_time && $now <= $close_time) {
            return true;
        } else {
            return false;
        }
    }    
    
    return null; 
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$trail_id = intval($_GET['id']);

$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$is_admin = $is_logged_in && isset($_SESSION['role']) && $_SESSION['role'] === "admin";
$is_user = $is_logged_in && isset($_SESSION['role']) && $_SESSION['role'] === "user";

$stmt = $conn->prepare("SELECT * FROM trails WHERE trail_id = ?");
$stmt->bind_param("i", $trail_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "המסלול לא נמצא!";
    exit();
}

$trail = $result->fetch_assoc();

$weather_data = null;
$weather_api_key = "56d18c25f877fa6146d18e4e648698c9";

if (!empty($trail['location_coords'])) {
    $coords = explode(',', $trail['location_coords']);
    if (count($coords) == 2) {
        $lat = trim($coords[0]);
        $lon = trim($coords[1]);
        
        $weather_url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$weather_api_key}&units=metric&lang=he";
        
        $response = @file_get_contents($weather_url);
        if ($response) {
            $weather_data = json_decode($response, true);
        }
    }
}

$new_views = intval($trail['views']);

if (!isset($_SESSION['viewed_trails'])) {
    $_SESSION['viewed_trails'] = [];
}

if (!in_array($trail_id, $_SESSION['viewed_trails'])) {
    $new_views = intval($trail['views']) + 1;

    $stmt = $conn->prepare("UPDATE trails SET views = ? WHERE trail_id = ?");
    $stmt->bind_param("ii", $new_views, $trail_id);
    $stmt->execute();

    $_SESSION['viewed_trails'][] = $trail_id;
}

if ($is_user && isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);

    $stmt = $conn->prepare("SELECT history_id FROM history WHERE user_id = ? AND trail_id = ?");
    $stmt->bind_param("ii", $uid, $trail_id);
    $stmt->execute();
    $hist_check = $stmt->get_result();

    if ($hist_check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE history SET viewed_at = NOW() WHERE user_id = ? AND trail_id = ?");
        $stmt->bind_param("ii", $uid, $trail_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO history (user_id, trail_id, viewed_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $uid, $trail_id);
        $stmt->execute();
    }
}

$is_favorite_detail = false;

if ($is_user && isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);

    $stmt = $conn->prepare("SELECT fav_id FROM favorites WHERE user_id = ? AND trail_id = ?");
    $stmt->bind_param("ii", $uid, $trail_id);
    $stmt->execute();
    $fav_check = $stmt->get_result();

    if ($fav_check->num_rows > 0) {
        $is_favorite_detail = true;
    }
}

$stmt = $conn->prepare("
    SELECT tags.tag_name
    FROM tags
    JOIN trail_tags ON tags.tag_id = trail_tags.tag_id
    WHERE trail_tags.trail_id = ?
");
$stmt->bind_param("i", $trail_id);
$stmt->execute();
$tags_result = $stmt->get_result();

$current_user_id_for_likes = ($is_user && isset($_SESSION['user_id'])) ? intval($_SESSION['user_id']) : 0;

$stmt = $conn->prepare("
    SELECT 
        r.review_id,
        r.rating,
        r.comment,
        r.created_at,
        u.full_name,
        (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.review_id) AS likes_count,
        (SELECT COUNT(*) FROM review_likes rl2 WHERE rl2.review_id = r.review_id AND rl2.user_id = ?) AS user_liked
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.trail_id = ? AND r.status = 'approved'
    ORDER BY (r.rating >= 4) DESC, LENGTH(r.comment) DESC, r.created_at DESC
    LIMIT 3
");
$stmt->bind_param("ii", $current_user_id_for_likes, $trail_id);
$stmt->execute();
$relevant_reviews_result = $stmt->get_result();

$stmt = $conn->prepare("
    SELECT 
        r.review_id,
        r.rating,
        r.comment,
        r.created_at,
        u.full_name,
        (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.review_id) AS likes_count,
        (SELECT COUNT(*) FROM review_likes rl2 WHERE rl2.review_id = r.review_id AND rl2.user_id = ?) AS user_liked
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.trail_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
");
$stmt->bind_param("ii", $current_user_id_for_likes, $trail_id);
$stmt->execute();
$all_reviews_result = $stmt->get_result();

$stmt = $conn->prepare("
    SELECT AVG(rating) AS avg_rating, COUNT(review_id) AS total_reviews
    FROM reviews
    WHERE trail_id = ? AND status = 'approved'
");
$stmt->bind_param("i", $trail_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

$avg_rating = $stats['avg_rating'] ? round($stats['avg_rating'], 1) : 0;
$total_reviews = $stats['total_reviews'];


function renderReview($rev, $conn, $trail_id, $is_user) {
    $review_id = intval($rev['review_id']);
    ?>
    <div class="review-item mb-3 pb-3" style="border-bottom: 1px solid var(--outline-variant);">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0 text-success">
                <span class="material-symbols-outlined me-1" style="font-size: 1.1rem; color: var(--primary-color);">account_circle</span>
                <?php echo htmlspecialchars($rev['full_name']); ?>
            </h6>

            <div class="text-warning fw-bold">
                <?php echo intval($rev['rating']); ?> ★
            </div>
        </div>

        <p class="mb-2" style="color: #444;">
            <?php echo nl2br(htmlspecialchars($rev['comment'])); ?>
        </p>

        <?php
        $img_stmt = $conn->prepare("SELECT image_path FROM review_images WHERE review_id = ? ORDER BY uploaded_at ASC");
        $img_stmt->bind_param("i", $review_id);
        $img_stmt->execute();
        $images_result = $img_stmt->get_result();
        ?>

        <?php if ($images_result && $images_result->num_rows > 0): ?>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <?php while ($img = $images_result->fetch_assoc()): ?>
                    <a href="<?php echo htmlspecialchars($img['image_path']); ?>" target="_blank">
                        <img src="<?php echo htmlspecialchars($img['image_path']); ?>"
                             alt="תמונה מהטיול"
                             style="width: 110px; height: 90px; object-fit: cover; border-radius: 0.75rem; border: 1px solid #ddd;">
                    </a>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mt-2">
            <small class="text-muted">
                <?php echo date('d/m/Y H:i', strtotime($rev['created_at'])); ?>
            </small>

            <?php if ($is_user): ?>
                <form method="POST" action="toggle_review_like.php" class="m-0">
                    <input type="hidden" name="review_id" value="<?php echo $review_id; ?>">
                    <input type="hidden" name="trail_id" value="<?php echo intval($trail_id); ?>">

                    <button type="submit"
                            class="btn btn-sm <?php echo intval($rev['user_liked']) > 0 ? 'btn-success' : 'btn-outline-success'; ?> rounded-pill px-3">
                        👍 אהבתי
                        <span class="fw-bold"><?php echo intval($rev['likes_count']); ?></span>
                    </button>
                </form>
            <?php else: ?>
                <span class="badge bg-light text-dark border">
                    👍 <?php echo intval($rev['likes_count']); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?php
}


$page_title = $trail['name'] . " - TrailFinder";
include "includes/header.php";
?>

<div class="detail-hero">
    <div class="hero-bg">
        <?php if (!empty($trail['image_url'])): ?>
            <img alt="<?php echo htmlspecialchars($trail['name']); ?>" src="<?php echo htmlspecialchars($trail['image_url']); ?>"/>
        <?php else: ?>
            <div style="width:100%;height:100%;background:#2c3e50;"></div>
        <?php endif; ?>
        <div class="hero-overlay"></div>
    </div>

    <div class="container position-relative z-1 text-center text-white">
        <div class="mb-3">
            <span class="badge bg-success px-3 py-2 rounded-pill fs-6"><?php echo htmlspecialchars($trail['site_type'] ?? 'מסלול'); ?></span>
            
            <?php if(isset($trail['entry_fee']) && $trail['entry_fee'] == 'paid'): ?>
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6">כניסה בתשלום</span>
            <?php else: ?>
                <span class="badge bg-info text-dark px-3 py-2 rounded-pill fs-6">כניסה חינם</span>
            <?php endif; ?>
        </div>

        <h1 class="display-3 fw-black mb-3">
            <?php echo htmlspecialchars($trail['name']); ?>
        </h1>

        <p class="fs-5 opacity-90">
            <span class="material-symbols-outlined me-1">location_on</span>
            אזור <?php echo htmlspecialchars($trail['region']); ?>
        </p>
    </div>
</div>

<section class="container py-5">
    <div class="row g-4">

        <div class="col-lg-8">
            
            <?php if (isset($trail['booking_required']) && $trail['booking_required'] == 'yes'): ?>
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center p-4 mb-4" style="border-radius: 1rem; background-color: #fff3cd;">
                    <span class="material-symbols-outlined me-3" style="font-size: 2.5rem; color: #856404;">event_available</span>
                    <div>
                        <h5 class="fw-bold mb-1" style="color: #856404;">נדרש תיאום ביקור מראש</h5>
                        <p class="mb-2 text-dark">כדי להבטיח את מקומכם באתר זה, יש להזמין מקום מראש דרך האתר הרשמי.</p>
                        <?php if(!empty($trail['booking_link'])): ?>
                            <a href="<?php echo htmlspecialchars($trail['booking_link']); ?>" target="_blank" class="btn btn-dark fw-bold px-4 rounded-pill btn-sm">להזמנת מקום לחצו כאן</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card editorial-shadow p-0" style="border-radius: 1rem; overflow: hidden;">

                <?php if (!empty($trail['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($trail['image_url']); ?>"
                         alt="<?php echo htmlspecialchars($trail['name']); ?>"
                         style="width: 100%; height: 400px; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 100%; height: 400px; background-color: #bdc3c7; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; font-size: 1.5rem;">
                        אין תמונה זמינה
                    </div>
                <?php endif; ?>

                <div class="p-4">
                    <h2 class="fw-bold mb-3" style="color: var(--on-surface);">
                        אודות המסלול
                    </h2>

                    <p style="font-size: 1.05rem; line-height: 1.9; color: #555;">
                        <?php echo nl2br(htmlspecialchars($trail['description'])); ?>
                    </p>

                    <h3 class="fw-bold mt-4 mb-3" style="color: var(--on-surface);">
                        תגיות ומאפיינים
                    </h3>

                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($tags_result->num_rows > 0): ?>
                            <?php while ($tag = $tags_result->fetch_assoc()): ?>
                                <span class="custom-badge" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
                                    <?php echo htmlspecialchars($tag['tag_name']); ?>
                                </span>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">אין תגיות זמינות</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card editorial-shadow p-4 mt-4" style="border-radius: 1rem; border: none;">
                <h3 class="fw-bold mb-4" style="color: var(--on-surface); border-bottom: 2px solid var(--primary-color); padding-bottom: 0.75rem;">
                    <span class="material-symbols-outlined me-1" style="color: var(--primary-color);">star</span>
                    ביקורות מאושרות (<?php echo $total_reviews; ?>)

                    <?php if ($total_reviews > 0): ?>
                        <span class="ms-2 text-warning" style="font-size: 1.2rem;">
                            <?php echo $avg_rating; ?> ★
                        </span>
                    <?php endif; ?>
                </h3>

                <?php if (isset($_GET['review']) && $_GET['review'] == 'pending'): ?>
                    <div class="alert alert-success text-center">
                        הביקורת נשלחה בהצלחה וממתינה לאישור מנהל.
                    </div>
                <?php endif; ?>

                <?php if ($is_user): ?>

                   <form action="add_review.php" method="POST" enctype="multipart/form-data" class="mb-4 p-3 rounded" style="background-color: var(--surface-bright); border: 1px solid var(--outline-variant);">
                        <h5 class="fw-bold mb-3">הוסף ביקורת</h5>

                        <input type="hidden" name="trail_id" value="<?php echo $trail_id; ?>">

                        <div class="mb-3">
                            <label class="form-label fw-medium w-100">דירוג (1-5)</label>

                            <select name="rating" class="form-select w-auto d-inline-block" required>
                                <option value="5" selected>5 - מצוין</option>
                                <option value="4">4 - טוב מאוד</option>
                                <option value="3">3 - טוב</option>
                                <option value="2">2 - סביר</option>
                                <option value="1">1 - גרוע</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">תגובה</label>
                            <textarea name="comment" class="form-control" rows="3" required placeholder="שתף את החוויה שלך מהמסלול..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">תמונות מהטיול</label>
                            <input type="file" name="review_images[]" class="form-control" accept="image/*" multiple>
                            <small class="text-muted">אפשר להעלות כמה תמונות מהטיול.</small>
                        </div>

                        <button type="submit" class="btn btn-primary-custom">
                            שלח ביקורת
                        </button>
                    </form>

                <?php elseif ($is_admin): ?>

                    <div class="alert alert-info mb-4" style="border-radius: 0.75rem;">
                        <span class="material-symbols-outlined me-1" style="vertical-align: middle;">admin_panel_settings</span>
                        אתה מחובר כמנהל. מנהל לא מוסיף ביקורות, אלא מאשר או דוחה ביקורות בלוח הבקרה.
                    </div>

                <?php else: ?>

                    <div class="alert alert-warning mb-4" style="border-radius: 0.75rem;">
                        <span class="material-symbols-outlined me-1" style="vertical-align: middle;">lock</span>
                        עליך להיות <a href="login.php" class="alert-link fw-bold">מחובר</a> כדי להוסיף ביקורת.
                    </div>

                <?php endif; ?>

                <div class="reviews-section mt-4">

                    <?php if ($total_reviews > 0): ?>

                        <ul class="nav nav-tabs mb-4" id="reviewsTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold" id="relevant-tab" data-bs-toggle="tab" data-bs-target="#relevant" type="button" role="tab" style="color: var(--primary-color);">
                                    הכי רלוונטיות
                                </button>
                            </li>

                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold text-muted" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                                    כל הביקורות (<?php echo $total_reviews; ?>)
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="reviewsTabContent">

                            <div class="tab-pane fade show active" id="relevant" role="tabpanel">
                                <?php while ($rev = $relevant_reviews_result->fetch_assoc()): ?>
                                    <?php renderReview($rev, $conn, $trail_id, $is_user); ?>
                                <?php endwhile; ?>
                            </div>

                            <div class="tab-pane fade" id="all" role="tabpanel">
                                <?php while ($rev = $all_reviews_result->fetch_assoc()): ?>
                                    <?php renderReview($rev, $conn, $trail_id, $is_user); ?>
                                <?php endwhile; ?>
                            </div>

                        </div>

                    <?php else: ?>

                        <p class="text-muted">
                            אין עדיין ביקורות מאושרות למסלול זה.
                        </p>

                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="col-lg-4">

            <div class="card editorial-shadow p-4" style="border-radius: 1rem; border: none;">
                <h3 class="fw-bold mb-4" style="color: var(--on-surface); border-bottom: 2px solid var(--primary-color); padding-bottom: 0.75rem;">
                    <span class="material-symbols-outlined me-1" style="color: var(--primary-color);">info</span>
                    מידע מהיר
                </h3>

                <?php if ($weather_data): ?>
                    <?php 
                        $temp = round($weather_data['main']['temp']); 
                        $description = htmlspecialchars($weather_data['weather'][0]['description']); 
                        $icon = $weather_data['weather'][0]['icon']; 
                        $icon_url = "http://openweathermap.org/img/wn/{$icon}.png"; 
                        $is_bad_weather = ($temp > 35 || strpos($description, 'גשם') !== false || strpos($description, 'סערה') !== false);
                    ?>
                    <div class="info-item mb-2 pb-2 border-bottom">
                        <strong>
                            <span class="material-symbols-outlined me-1" style="font-size: 1rem; color: var(--primary-color);">thermostat</span>
                            מזג אוויר כעת
                        </strong>
                        <br>
                        <div class="ms-4 mt-1">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?php echo $icon_url; ?>" alt="weather icon" style="width: 35px; height: 35px; background-color: #f8f9fa; border-radius: 50%;">
                                <span class="fw-bold fs-6" dir="ltr"><?php echo $temp; ?>°C</span>
                                <span class="text-muted small">- <?php echo $description; ?></span>
                            </div>
                            
                            <?php if ($is_bad_weather): ?>
                                <div class="alert alert-danger mt-2 mb-0 p-2 text-center" style="font-size: 0.75rem; border-radius: 0.5rem; font-weight: bold;">
                                    <span class="material-symbols-outlined align-middle" style="font-size: 1rem;">warning</span>
                                    שימו לב: תנאי מזג האוויר אינם אידיאליים כעת
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="info-item mb-2">
                    <strong>
                        <span class="material-symbols-outlined me-1" style="font-size: 1rem; color: var(--primary-color);">schedule</span>
                        שעות פתיחה
                    </strong>
                    <br>
                    <div class="ms-4 d-flex align-items-center gap-2">
                        <span class="text-muted"><?php echo !empty($trail['opening_hours']) ? htmlspecialchars($trail['opening_hours']) : 'לא צוין'; ?></span>
                        
                        <?php 
                        if (!empty($trail['opening_hours'])) {
                            $isOpen = getOpenStatus($trail['opening_hours']);
                            if ($isOpen === true): ?>
                                <span class="badge bg-success text-white rounded-pill px-2 py-1" style="font-size: 0.75rem; color: #ffffff !important;">פתוח עכשיו</span>
                            <?php elseif ($isOpen === false): ?>
                                <span class="badge bg-danger text-white rounded-pill px-2 py-1" style="font-size: 0.75rem; color: #ffffff !important;">סגור עכשיו</span>
                            <?php endif; 
                        }
                        ?>
                    </div>
                </div>

                <div class="info-item mb-2">
                    <strong>
                        <span class="material-symbols-outlined me-1" style="font-size: 1rem; color: var(--primary-color);">payments</span>
                        עלות כניסה
                    </strong>
                    <br>
                    <span class="ms-4 text-muted"><?php echo (isset($trail['entry_fee']) && $trail['entry_fee'] == 'paid') ? 'בתשלום' : 'חינם'; ?></span>
                </div>

                <div class="info-item">
                    <strong>
                        <span class="material-symbols-outlined me-1" style="font-size: 1rem;">signal_cellular_alt</span>
                        רמת קושי
                    </strong>
                    <span><?php echo htmlspecialchars($trail['difficulty']); ?></span>
                </div>

                <div class="info-item">
                    <strong>
                        <span class="material-symbols-outlined me-1" style="font-size: 1rem;">map</span>
                        אזור
                    </strong>
                    <span><?php echo htmlspecialchars($trail['region']); ?></span>
                </div>

                <div class="info-item">
                    <strong>
                        <span class="material-symbols-outlined me-1" style="font-size: 1rem;">my_location</span>
                        קואורדינטות
                    </strong>
                    <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($trail['location_coords']); ?></span>
                </div>

                <div class="info-item">
                    <strong>
                        <span class="material-symbols-outlined me-1" style="font-size: 1rem;">visibility</span>
                        צפיות
                    </strong>
                    <span><?php echo $new_views; ?></span>
                </div>

                <div class="info-item">
                    <strong>
                        <span class="material-symbols-outlined me-1" style="font-size: 1rem;">update</span>
                        עודכן לאחרונה
                    </strong>

                    <?php if (isset($trail['updated_at']) && !empty($trail['updated_at'])): ?>
                        <span><?php echo date('d/m/Y H:i', strtotime($trail['updated_at'])); ?></span>
                    <?php elseif (isset($trail['created_at']) && !empty($trail['created_at'])): ?>
                        <span><?php echo date('d/m/Y H:i', strtotime($trail['created_at'])); ?></span>
                    <?php else: ?>
                        <span>לא זמין</span>
                    <?php endif; ?>
                </div>

                <div class="mt-4">

                    <?php if ($is_admin): ?>

                        <a href="edit_trail.php?id=<?php echo $trail_id; ?>" class="btn btn-warning w-100 py-3 fw-bold mb-2" style="border-radius: 0.75rem;">
                            <span class="material-symbols-outlined me-1">edit</span>
                            ערוך מסלול
                        </a>

                        <a href="delete_trail.php?id=<?php echo $trail_id; ?>"
                           class="btn btn-danger w-100 py-3 fw-bold"
                           style="border-radius: 0.75rem;"
                           onclick="return confirm('האם אתה בטוח שברצונך למחוק את המסלול?');">
                            <span class="material-symbols-outlined me-1">delete</span>
                            מחק מסלול
                        </a>

                    <?php elseif ($is_user): ?>

                        <form method="POST" action="toggle_favorite.php" class="w-100">
                            <input type="hidden" name="trail_id" value="<?php echo $trail_id; ?>">
                            <input type="hidden" name="return_url" value="trail_details.php?id=<?php echo $trail_id; ?>">

                            <?php if ($is_favorite_detail): ?>
                                <button type="submit" class="btn btn-outline-danger w-100 py-3 fw-bold" style="border-radius: 0.75rem;">
                                    <span class="material-symbols-outlined me-1" style="font-variation-settings: 'FILL' 1;">favorite</span>
                                    הסר ממועדפים
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary-custom w-100 py-3">
                                    <span class="material-symbols-outlined me-1" style="font-variation-settings: 'FILL' 0;">favorite_border</span>
                                    הוסף למועדפים
                                </button>
                            <?php endif; ?>
                        </form>

                        <div class="mt-3">
                            <a href="report_problem.php?trail_id=<?php echo $trail_id; ?>" class="btn btn-outline-warning w-100 py-2 fw-bold" style="border-radius: 0.75rem;">
                                <span class="material-symbols-outlined me-1" style="font-size: 1rem;">report</span>
                                דווח על טעות
                            </a>
                        </div>

                    <?php else: ?>

                        <div class="alert-custom-danger text-center">
                            <span class="material-symbols-outlined me-1" style="font-size: 1rem;">lock</span>
                            <a href="login.php" class="text-decoration-none" style="color: #c0392b; font-weight: 600;">התחבר</a>
                            כדי לשמור מסלול זה
                        </div>

                    <?php endif; ?>

                </div>

                <div class="mt-3">
                    <a href="search.php" class="btn btn-outline-custom w-100 py-2">
                        <span class="material-symbols-outlined me-1" style="font-size: 1rem;">arrow_forward</span>
                        חזרה למסלולים
                    </a>
                </div>

                <div class="mt-4 pt-3" style="border-top: 1px dashed var(--outline-variant);">
                    <p class="text-muted fw-bold mb-2 text-center" style="font-size: 0.9rem;">
                        שתף מסלול זה:
                    </p>

                    <div class="d-flex gap-2">
                        <a href="https://api.whatsapp.com/send?text=ממליץ לך על המסלול: <?php echo urlencode($trail['name']); ?> %0Aכנס לקישור: http://localhost/TrailFinder/trail_details.php?id=<?php echo $trail_id; ?>"
                           target="_blank"
                           class="btn text-white flex-grow-1 fw-bold"
                           style="background-color: #25D366; border-radius: 0.5rem; font-size: 0.9rem;">
                            <span class="material-symbols-outlined me-1" style="vertical-align: middle; font-size: 1.1rem;">chat</span>
                            וואטסאפ
                        </a>

                        <?php
                           $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                            $encoded_url = urlencode($current_url);
                        ?>

                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encoded_url; ?>" 
                             target="_blank" 
                             class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold" 
                             style="background-color: #1877F2; border-color: #1877F2;">
                             <span class="material-symbols-outlined">share</span>
                              שתף בפייסבוק
                         </a>

                        <button onclick="navigator.clipboard.writeText(window.location.href); alert('הקישור הועתק בהצלחה!');"
                                class="btn btn-outline-secondary flex-grow-1 fw-bold"
                                style="border-radius: 0.5rem; font-size: 0.9rem;">
                            <span class="material-symbols-outlined me-1" style="vertical-align: middle; font-size: 1.1rem;">content_copy</span>
                            העתק
                        </button>
                    </div>
                </div>

                <?php 
                if (!empty($trail['location_coords'])) { 
                    $coords = explode(',', $trail['location_coords']);
                    if (count($coords) == 2) {
                        $lat = trim($coords[0]);
                        $lng = trim($coords[1]);
                ?>
                    <div class="card shadow-sm border-0 mt-4 rounded-4">
                        <div class="card-body p-3 text-end">
                            <h6 class="fw-bold mb-3" style="color: var(--primary-color);">
                                <span class="material-symbols-outlined align-middle ms-1" style="font-size: 1.2rem;">map</span>
                                מיקום המסלול במפה
                            </h6>
                            <div id="map" style="width: 100%; height: 250px; border-radius: 10px; border: 1px solid #eee;"></div>
                        </div>
                    </div>

                    <script>
                        function initMap() {
                            var trailLocation = { lat: <?php echo $lat; ?>, lng: <?php echo $lng; ?> };
                            var map = new google.maps.Map(document.getElementById('map'), {
                                zoom: 14,
                                center: trailLocation,
                                mapTypeControl: false,
                                streetViewControl: false
                            });
                            var marker = new google.maps.Marker({
                                position: trailLocation,
                                map: map,
                                title: "<?php echo htmlspecialchars($trail['name']); ?>" 
                            });
                        }
                    </script>
                    
                    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= urlencode(getenv('GOOGLE_MAPS_API_KEY') ?: '') ?>&callback=initMap"></script>
                <?php 
                    } 
                } 
                ?>

            </div>
        </div>

    </div>
</section>

<?php include "includes/footer.php"; ?>