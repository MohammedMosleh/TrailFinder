<?php
session_start();
include "includes/db_connect.php";

$is_logged_in = isset($_SESSION['user_id']);
$current_user = $is_logged_in ? intval($_SESSION['user_id']) : 0;

if ($is_logged_in) {
    $algo_sql = "
        SELECT t.*, 
               ((t.views * 1) + (IFNULL(global_fav.fav_count, 0) * 5) + (IFNULL(global_rev.avg_rating, 0) * 2)) AS base_score,
               (IFNULL(personal_match.matching_tags, 0) * 1000) AS personalization_bonus,
               (((t.views * 1) + (IFNULL(global_fav.fav_count, 0) * 5) + (IFNULL(global_rev.avg_rating, 0) * 2)) + (IFNULL(personal_match.matching_tags, 0) * 1000)) AS algo_score
        FROM trails t
        LEFT JOIN (SELECT trail_id, COUNT(fav_id) as fav_count FROM favorites GROUP BY trail_id) global_fav ON t.trail_id = global_fav.trail_id
        LEFT JOIN (SELECT trail_id, AVG(rating) as avg_rating FROM reviews GROUP BY trail_id) global_rev ON t.trail_id = global_rev.trail_id
        LEFT JOIN (
            SELECT target_tt.trail_id, COUNT(DISTINCT target_tt.tag_id) as matching_tags
            FROM trail_tags target_tt
            INNER JOIN (
                SELECT DISTINCT tt.tag_id
                FROM trail_tags tt
                LEFT JOIN favorites f ON tt.trail_id = f.trail_id AND f.user_id = $current_user
                LEFT JOIN reviews r ON tt.trail_id = r.trail_id AND r.user_id = $current_user AND r.rating >= 4
                LEFT JOIN history h ON tt.trail_id = h.trail_id AND h.user_id = $current_user
                WHERE f.user_id IS NOT NULL OR r.user_id IS NOT NULL OR h.user_id IS NOT NULL
            ) user_prefs ON target_tt.tag_id = user_prefs.tag_id
            GROUP BY target_tt.trail_id
        ) personal_match ON t.trail_id = personal_match.trail_id
        WHERE t.trail_id NOT IN (SELECT trail_id FROM favorites WHERE user_id = $current_user)
        ORDER BY algo_score DESC 
        LIMIT 6
    ";
} else {
    $algo_sql = "
        SELECT t.*, 
               ((t.views * 1) + (IFNULL(fav.fav_count, 0) * 5) + (IFNULL(rev.avg_rating, 0) * 2)) AS algo_score
        FROM trails t
        LEFT JOIN (SELECT trail_id, COUNT(fav_id) as fav_count FROM favorites GROUP BY trail_id) fav ON t.trail_id = fav.trail_id
        LEFT JOIN (SELECT trail_id, AVG(rating) as avg_rating FROM reviews GROUP BY trail_id) rev ON t.trail_id = rev.trail_id
        ORDER BY algo_score DESC 
        LIMIT 6
    ";
}
$result = $conn->query($algo_sql);

$current_month = intval(date('n'));
$season_name = "";
$season_tags = "";
$season_icon = "";
$season_message = "";

if ($current_month >= 6 && $current_month <= 9) {
    $season_name = "קיץ";
    $season_tags = "('מים', 'מסלול במים', 'מוצל')"; 
    $season_icon = "light_mode";
    $season_message = "חם בחוץ? מצאנו עבורך את המסלולים הרטובים והמוצלים ביותר לעונה.";
} elseif ($current_month == 12 || $current_month <= 2) {
    $season_name = "חורף";
    $season_tags = "('תצפית נוף', 'עתיקות והיסטוריה', 'חיות בר')"; 
    $season_icon = "ac_unit";
    $season_message = "העונה המושלמת לתצפיות נוף פנורמי וטיולים במדבר.";
} elseif ($current_month >= 3 && $current_month <= 5) {
    $season_name = "אביב";
    $season_tags = "('פריחה עונתית', 'מתאים למשפחות', 'מתאים לפיקניק', 'מסלול קל')"; 
    $season_icon = "local_florist";
    $season_message = "הטבע בשיאו! זמן מושלם למסלולי פריחה ופיקניק משפחתי.";
} else {
    $season_name = "סתיו";
    $season_tags = "('תצפית נוף', 'שקט', 'מושלם לשקיעה')"; 
    $season_icon = "air";
    $season_message = "מזג אוויר נעים, בדיוק הזמן לטיול קליל מול תצפיות נוף מרהיבות.";
}

$seasonal_sql = "
    SELECT t.* FROM trails t
    JOIN trail_tags tt ON t.trail_id = tt.trail_id
    JOIN tags tg ON tt.tag_id = tg.tag_id
    WHERE tg.tag_name IN $season_tags
    GROUP BY t.trail_id
    ORDER BY t.views DESC 
    LIMIT 3
";
$seasonal_result = $conn->query($seasonal_sql);

$map_trails_query = $conn->query("SELECT trail_id, name, location_coords FROM trails WHERE location_coords IS NOT NULL AND location_coords != ''");
$map_trails = [];
while ($row = $map_trails_query->fetch_assoc()) {
    $coords = explode(',', $row['location_coords']);
    if (count($coords) == 2) {
        $map_trails[] = [
            'id' => $row['trail_id'],
            'name' => $row['name'],
            'lat' => trim($coords[0]),
            'lng' => trim($coords[1])
        ];
    }
}
$map_trails_json = json_encode($map_trails);

$page_title = "TrailFinder | מצא את ההרפתקה הבאה שלך";
include "includes/header.php";
?>

<header class="hero-section">
    <div class="hero-bg">
        <img alt="Mountains" src="assets/images/heroo.jpeg" />
        <div class="hero-overlay"></div>
    </div>

    <div class="container position-relative z-1 text-center">
        <h1 class="display-2 fw-black text-white mb-4">מצא את ההרפתקה הבאה שלך</h1>

        <p class="lead text-white opacity-90 mb-5 mx-auto" style="max-width: 600px;">
            גלה פארקים, חופים ומסלולים המותאמים בדיוק עבורך.
        </p>

        <form action="search.php" method="GET">
            <div class="search-container-hero d-flex align-items-center">
                <div class="search-input-group w-100">
                    <span class="material-symbols-outlined" style="color: var(--primary-color)">search</span>
                    <input class="form-control w-100" name="q" placeholder="חפש פארק, מסלול, אזור..." type="text"/>
                </div>
                <button type="submit" class="btn btn-primary-custom btn-lg px-4 ms-2" style="flex-shrink: 0; min-width: 100px;">
                    חפש
                </button>
            </div>
        </form>
    </div>
</header>

<section class="py-5" style="background-color: #fbfbe2;">
    <div class="container py-lg-5">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <span class="text-success fw-bold text-uppercase small d-block mb-2" style="letter-spacing: 2px;">גילוי</span>
                <h2 class="fw-black display-5 mb-0">יעדים מומלצים עבורך</h2>
            </div>
            <a class="text-success fw-bold text-decoration-none d-none d-md-flex align-items-center" href="search.php">
                <span>צפה בכל המסלולים</span>
                <span class="material-symbols-outlined me-1">arrow_back</span>
            </a>
        </div>
        <div class="row g-4">
            <?php if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) { ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 editorial-shadow" style="border-radius: 0.75rem;">
                            <div class="card-img-wrapper">
                                <?php if (!empty($row['image_url'])): ?>
                                    <img alt="<?php echo htmlspecialchars($row['name']); ?>" src="<?php echo htmlspecialchars($row['image_url']); ?>"/>
                                <?php else: ?>
                                    <div style="width:100%;height:100%;background:#bdc3c7;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;">אין תמונה</div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center text-muted small fw-medium mb-2">
                                    <span class="material-symbols-outlined fs-6 me-1">location_on</span>
                                    <?php echo htmlspecialchars($row['region']); ?>
                                </div>
                                <h3 class="h4 fw-bold mb-3"><?php echo htmlspecialchars($row['name']); ?></h3>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="custom-badge"><?php echo htmlspecialchars($row['difficulty']); ?></span>
                                </div>
                                <p class="text-muted small mb-4" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </p>
                                <a href="trail_details.php?id=<?php echo $row['trail_id']; ?>" class="btn btn-outline-custom w-100 py-3">לכל הפרטים</a>
                            </div>
                        </div>
                    </div>
            <?php } } ?>
        </div>
    </div>
</section>

<section class="container py-5 mb-5" style="background-color: #f0fdf4; border-radius: 1.5rem; border: 1px solid #dcfce7;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 px-3">
        <div>
            <span class="badge bg-success mb-2 px-3 py-2 rounded-pill fs-6">
                <span class="material-symbols-outlined align-middle me-1" style="font-size: 1.1rem;"><?php echo $season_icon; ?></span>
                מתאים לעונת ה-<?php echo $season_name; ?>
            </span>
            <h2 class="fw-black display-6 mb-1">מתאים בדיוק לעכשיו</h2>
            <p class="text-muted fs-5 mb-0"><?php echo $season_message; ?></p>
        </div>
    </div>
    <div class="row g-4 px-3">
        <?php if ($seasonal_result && $seasonal_result->num_rows > 0): ?>
            <?php while ($row = $seasonal_result->fetch_assoc()): ?>
                <div class="col-12 col-md-4">
                    <div class="card h-100 editorial-shadow border-0" style="border-radius: 1rem; overflow: hidden;">
                        <div class="position-relative" style="height: 200px;">
                            <?php if (!empty($row['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-secondary w-100 h-100 d-flex justify-content-center align-items-center text-white fw-bold">אין תמונה</div>
                            <?php endif; ?>
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-white text-success fw-bold shadow-sm">🔥 מומלץ לעונה</span>
                            </div>
                        </div>
                        <div class="card-body p-4 bg-white">
                            <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($row['name']); ?></h4>
                            <p class="text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </p>
                            <a href="trail_details.php?id=<?php echo $row['trail_id']; ?>" class="btn btn-outline-success w-100 fw-bold rounded-pill">צא לטייל</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center w-100">לא נמצאו מסלולים מתאימים לעונה זו כרגע.</p>
        <?php endif; ?>
    </div>
</section>

<section class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <span class="text-success fw-bold text-uppercase small d-block mb-1" style="letter-spacing: 2px;">חקור לפי סוג</span>
            <h2 class="fw-black mb-0">קטגוריות ראשיות</h2>
        </div>
    </div>
    <div class="row g-4 justify-content-center">
        <div class="col-6 col-md-3">
            <a href="search.php?site_type[]=פארק" class="text-decoration-none">
                <div class="card border-0 shadow-sm text-center py-4 category-card" style="border-radius: 1rem;">
                    <div class="mb-3">
                        <span class="material-symbols-outlined text-success" style="font-size: 3rem;">park</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-0">פארקים</h5>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="search.php?site_type[]=מסלול" class="text-decoration-none">
                <div class="card border-0 shadow-sm text-center py-4 category-card" style="border-radius: 1rem;">
                    <div class="mb-3">
                        <span class="material-symbols-outlined text-warning" style="font-size: 3rem;">hiking</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-0">מסלולי הליכה</h5>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="search.php?site_type[]=חוף" class="text-decoration-none">
                <div class="card border-0 shadow-sm text-center py-4 category-card" style="border-radius: 1rem;">
                    <div class="mb-3">
                        <span class="material-symbols-outlined text-info" style="font-size: 3rem;">beach_access</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-0">חופי ים</h5>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="search.php?site_type[]=אתר היסטורי" class="text-decoration-none">
                <div class="card border-0 shadow-sm text-center py-4 category-card" style="border-radius: 1rem;">
                    <div class="mb-3">
                        <span class="material-symbols-outlined text-danger" style="font-size: 3rem;">account_balance</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-0">אתרים היסטוריים</h5>
                </div>
            </a>
        </div>
    </div>
</section>

<section class="container py-5 mb-5 border-top">
    <div class="row">
        <div class="col-12 text-center mb-4">
            <h2 class="fw-black mb-0">כל המסלולים במפה אחת</h2>
        </div>
        <div class="card editorial-shadow border-0 rounded-4 p-2">
            <div id="mainMap" style="width: 100%; height: 500px; border-radius: 12px;"></div>
        </div>
    </div>
</section>

<script>
    function initMainMap() {
        var map = new google.maps.Map(document.getElementById('mainMap'), {
            zoom: 7,
            center: { lat: 31.5, lng: 34.8 }
        });
        var trails = <?php echo $map_trails_json; ?>;
        var infoWindow = new google.maps.InfoWindow();

        trails.forEach(function(trail) {
            var marker = new google.maps.Marker({
                position: { lat: parseFloat(trail.lat), lng: parseFloat(trail.lng) },
                map: map,
                title: trail.name
            });
            marker.addListener('click', function() {
                infoWindow.setContent('<div style="text-align: right; direction: rtl;"><h6>' + trail.name + '</h6><a href="trail_details.php?id=' + trail.id + '" class="btn btn-sm btn-success">לפרטים</a></div>');
                infoWindow.open(map, marker);
            });
        });
    }
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= urlencode(getenv('GOOGLE_MAPS_API_KEY') ?: '') ?>&callback=initMainMap"></script>
<?php if (!$is_logged_in): ?>
<section class="container mb-5 py-5">
    <div class="cta-banner" style="background: linear-gradient(135deg, #27ae60, #2ecc71); border-radius: 1.5rem; overflow: hidden; position: relative;">
        <div class="row align-items-center p-5 position-relative z-1">
            <div class="col-lg-7 text-white">
                <h2 class="display-5 fw-black mb-4 lh-1 text-white">הטבע מחכה לך, בוא נצא לדרך.</h2>
                <p class="fs-5 opacity-75 mb-4 text-white">הצטרף לקהילת המטיילים שלנו וקבל גישה למסלולים סודיים וטיפים מהשטח.</p>
                <a href="register.php" class="btn btn-light text-success fw-bold fs-5 py-3 px-5 rounded-pill shadow-sm transition-all">
                    הצטרף עכשיו חינם
                </a>
            </div>

            <div class="col-lg-5 d-none d-lg-block text-center">
                <span class="material-symbols-outlined text-white" style="font-size: 12rem; opacity: 0.8;">hiking</span>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include "includes/footer.php"; ?>