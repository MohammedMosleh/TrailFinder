<?php
session_start();
include "includes/db_connect.php";

$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
$is_admin = $is_logged_in && isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
$is_user = $is_logged_in && isset($_SESSION["role"]) && $_SESSION["role"] === "user";

$tags_query = "SELECT * FROM tags ORDER BY tag_name ASC";
$tags_result = $conn->query($tags_query);

$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$region_filter = isset($_GET['region']) ? trim($_GET['region']) : '';
$difficulty_filter = isset($_GET['difficulty']) ? trim($_GET['difficulty']) : '';
$entry_fee_filter = isset($_GET['entry_fee']) ? trim($_GET['entry_fee']) : '';
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'popularity';

$selected_site_types = isset($_GET['site_type']) && is_array($_GET['site_type']) ? $_GET['site_type'] : [];
$selected_tags = isset($_GET['tags']) && is_array($_GET['tags']) ? $_GET['tags'] : [];

$sql = "
    SELECT t.*,
           IFNULL(review_stats.avg_rating, 0) AS avg_rating,
           IFNULL(review_stats.review_count, 0) AS review_count
    FROM trails t
    LEFT JOIN (
        SELECT trail_id, AVG(rating) AS avg_rating, COUNT(review_id) AS review_count
        FROM reviews
        WHERE status = 'approved'
        GROUP BY trail_id
    ) review_stats ON t.trail_id = review_stats.trail_id
    WHERE 1=1
";

$params = [];
$types = "";

if (!empty($search_term)) {
    $sql .= " AND (t.name LIKE ? OR t.description LIKE ?)";
    $like_search = "%" . $search_term . "%";
    $params[] = $like_search;
    $params[] = $like_search;
    $types .= "ss";
}

if (!empty($region_filter)) {
    $sql .= " AND t.region = ?";
    $params[] = $region_filter;
    $types .= "s";
}

if (!empty($difficulty_filter)) {
    $sql .= " AND t.difficulty = ?";
    $params[] = $difficulty_filter;
    $types .= "s";
}

if (!empty($selected_site_types)) {
    $safe_types = [];
    foreach ($selected_site_types as $st) {
        $st = trim($st);
        if (!empty($st)) {
            $safe_types[] = $st;
        }
    }
    if (!empty($safe_types)) {
        $placeholders = implode(",", array_fill(0, count($safe_types), "?"));
        $sql .= " AND t.site_type IN ($placeholders)";
        foreach ($safe_types as $st) {
            $params[] = $st;
            $types .= "s";
        }
    }
}

if (!empty($entry_fee_filter)) {
    $sql .= " AND t.entry_fee = ?";
    $params[] = $entry_fee_filter;
    $types .= "s";
}

if (!empty($selected_tags)) {
    $safe_tags = [];
    foreach ($selected_tags as $tag_id) {
        $tag_id = intval($tag_id);
        if ($tag_id > 0) {
            $safe_tags[] = $tag_id;
        }
    }
    if (!empty($safe_tags)) {
        $num_tags = count($safe_tags);
        $placeholders = implode(",", array_fill(0, $num_tags, "?"));
        $sql .= " AND t.trail_id IN (
                    SELECT trail_id
                    FROM trail_tags
                    WHERE tag_id IN ($placeholders)
                    GROUP BY trail_id
                    HAVING COUNT(DISTINCT tag_id) = $num_tags
                  )";
        foreach ($safe_tags as $tag_id) {
            $params[] = $tag_id;
            $types .= "i";
        }
    }
}

if ($sort_by == 'rating') {
    $sql .= " ORDER BY avg_rating DESC, review_count DESC, t.views DESC";
} elseif ($sort_by == 'latest') {
    $sql .= " ORDER BY t.created_at DESC";
} elseif ($sort_by == 'name') {
    $sql .= " ORDER BY t.name ASC";
} else {
    $sql .= " ORDER BY t.views DESC";
}

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$user_favorites = [];

if ($is_user && isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $fav_stmt = $conn->prepare("SELECT trail_id FROM favorites WHERE user_id = ?");
    $fav_stmt->bind_param("i", $uid);
    $fav_stmt->execute();
    $fav_q = $fav_stmt->get_result();
    while ($f = $fav_q->fetch_assoc()) {
        $user_favorites[] = intval($f['trail_id']);
    }
}

$map_results = [];
$page_title = "חיפוש מסלולים | TrailFinder";
include "includes/header.php";
?>

<div class="page-header" style="background-color: #fbfbe2;">
    <div class="container">
        <span class="text-success fw-bold text-uppercase small d-block mb-2" style="letter-spacing: 2px;">חקור</span>
        <h1 class="fw-black display-5 mb-2">חיפוש מסלולים</h1>
        <p class="text-muted fs-5">
            נמצאו <?php echo $result->num_rows; ?> מסלולים התואמים את החיפוש שלך.
        </p>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="filter-sidebar-card">
                <h4 class="fw-bold mb-4" style="color: var(--on-surface);">
                    <span class="material-symbols-outlined me-1" style="color: var(--primary-color);">tune</span>
                    סנן את הטיול שלך
                </h4>

                <form action="search.php" method="GET">
                    
                    <div class="filter-group mb-4">
                        <label class="form-label fw-bold" style="color: var(--primary-color);">
                            <span class="material-symbols-outlined align-middle me-1" style="font-size: 1.1rem;">sort</span>
                            מיין לפי:
                        </label>
                        <select name="sort_by" class="form-select border-success shadow-sm" onchange="this.form.submit()">
                            <option value="popularity" <?php echo ($sort_by == 'popularity') ? 'selected' : ''; ?>>פופולריות (הכי נצפים)</option>
                            <option value="rating" <?php echo ($sort_by == 'rating') ? 'selected' : ''; ?>>דירוג גולשים (גבוה לנמוך)</option>
                            <option value="latest" <?php echo ($sort_by == 'latest') ? 'selected' : ''; ?>>הכי חדש</option>
                            <option value="name" <?php echo ($sort_by == 'name') ? 'selected' : ''; ?>>שם (א-ת)</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <h5>חיפוש לפי שם</h5>
                        <input type="text" name="q" class="form-control" placeholder="לדוגמה: כזיב, מערה..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>

                    <div class="filter-group">
                        <h5>אזור</h5>
                        <select name="region" class="form-select">
                            <option value="">כל האזורים</option>
                            <option value="צפון" <?php if ($region_filter == 'צפון') echo 'selected'; ?>>צפון</option>
                            <option value="מרכז" <?php if ($region_filter == 'מרכז') echo 'selected'; ?>>מרכז</option>
                            <option value="דרום" <?php if ($region_filter == 'דרום') echo 'selected'; ?>>דרום</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <h5>רמת קושי</h5>
                        <select name="difficulty" class="form-select">
                            <option value="">כל הרמות</option>
                            <option value="קל" <?php if ($difficulty_filter == 'קל') echo 'selected'; ?>>קל</option>
                            <option value="בינוני" <?php if ($difficulty_filter == 'בינוני') echo 'selected'; ?>>בינוני</option>
                            <option value="קשה" <?php if ($difficulty_filter == 'קשה') echo 'selected'; ?>>קשה</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <h5>עלות כניסה</h5>
                        <select name="entry_fee" class="form-select">
                            <option value="">הכל</option>
                            <option value="free" <?php if ($entry_fee_filter == 'free') echo 'selected'; ?>>חינם בלבד</option>
                            <option value="paid" <?php if ($entry_fee_filter == 'paid') echo 'selected'; ?>>בתשלום</option>
                        </select>
                    </div>

                    <div class="filter-group mb-4">
                        <h5 class="fw-bold" style="color: var(--primary-color); font-size: 1.1rem;">סוג האתר</h5>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <?php
                            $all_site_types = ['פארק', 'מסלול', 'חוף', 'אתר היסטורי'];
                            foreach ($all_site_types as $index => $type) {
                                $is_checked = in_array($type, $selected_site_types) ? 'checked' : '';
                                $id = "site_type_" . $index;
                                echo '<input type="checkbox" class="btn-check" name="site_type[]" value="' . htmlspecialchars($type) . '" id="' . $id . '" autocomplete="off" ' . $is_checked . '>';
                                echo '<label class="btn btn-outline-success rounded-pill btn-sm fw-bold" for="' . $id . '">' . htmlspecialchars($type) . '</label>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="filter-group mb-3">
                        <h5 class="fw-bold text-dark" style="font-size: 1.1rem;">מאפיינים ותגיות</h5>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <?php
                            if ($tags_result && $tags_result->num_rows > 0) {
                                $tags_result->data_seek(0);
                                while ($tag = $tags_result->fetch_assoc()) {
                                    $tag_id = intval($tag['tag_id']);
                                    $tag_name = $tag['tag_name'];
                                    $is_checked = in_array($tag_id, array_map('intval', $selected_tags)) ? 'checked' : '';
                                    echo '<input type="checkbox" class="btn-check" name="tags[]" value="' . $tag_id . '" id="tag_' . $tag_id . '" autocomplete="off" ' . $is_checked . '>';
                                    echo '<label class="btn btn-outline-success rounded-pill btn-sm fw-bold" for="tag_' . $tag_id . '">' . htmlspecialchars($tag_name) . '</label>';
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-3 mb-2">
                        <span class="material-symbols-outlined me-1" style="font-size: 1.1rem;">search</span>
                        חפש
                    </button>

                    <a href="search.php" class="d-block text-center text-muted text-decoration-none small mt-2">נקה את כל הסינונים</a>
                </form>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card editorial-shadow border-0 rounded-4 p-2 mb-4">
                <div id="searchMap" style="width: 100%; height: 350px; border-radius: 12px; background-color: #f8f9fa;"></div>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                    $trail_id = intval($row['trail_id']);
                    $img_url = !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : '';
                    $is_favorite = in_array($trail_id, $user_favorites);
                    if (!empty($row['location_coords'])) {
                        $coords = explode(',', $row['location_coords']);
                        if (count($coords) == 2) {
                            $map_results[] = [
                                'id' => $trail_id,
                                'name' => $row['name'],
                                'lat' => trim($coords[0]),
                                'lng' => trim($coords[1])
                            ];
                        }
                    }
                    ?>

                    <div class="result-card">
                        <?php if (!empty($img_url)): ?>
                            <div class="result-card-img" style="background-image: url('<?php echo $img_url; ?>');"></div>
                        <?php else: ?>
                            <div class="result-card-img">
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;background:#bdc3c7;">אין תמונה</div>
                            </div>
                        <?php endif; ?>

                        <div class="result-card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-success mb-2"><?php echo htmlspecialchars($row['site_type'] ?? 'מסלול'); ?></span>
                                    <h3 class="h5 fw-bold mb-0" style="color: var(--primary-color);"><?php echo htmlspecialchars($row['name']); ?></h3>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="custom-badge"><?php echo htmlspecialchars($row['difficulty'] ?? ''); ?></span>
                                    <?php if ($is_user): ?>
                                        <form method="POST" action="toggle_favorite.php" class="m-0">
                                            <input type="hidden" name="trail_id" value="<?php echo $trail_id; ?>">
                                            <input type="hidden" name="return_url" value="search.php?<?php echo htmlspecialchars($_SERVER['QUERY_STRING'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-sm text-danger border-0 p-0 shadow-none bg-transparent" title="<?php echo $is_favorite ? 'הסר ממועדפים' : 'הוסף למועדפים'; ?>">
                                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' <?php echo $is_favorite ? '1' : '0'; ?>; font-size: 1.8rem;">
                                                    <?php echo $is_favorite ? 'favorite' : 'favorite_border'; ?>
                                                </span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <p class="small text-muted mb-2">
                                <span class="material-symbols-outlined fs-6 me-1">location_on</span> <?php echo htmlspecialchars($row['region'] ?? ''); ?> &nbsp;&bull;&nbsp;
                                <span class="material-symbols-outlined fs-6 me-1">payments</span> <?php echo (isset($row['entry_fee']) && $row['entry_fee'] == 'free') ? 'חינם' : 'בתשלום'; ?> &nbsp;&bull;&nbsp;
                                <span class="material-symbols-outlined fs-6 me-1">visibility</span> <?php echo intval($row['views'] ?? 0); ?> צפיות &nbsp;&bull;&nbsp;
                                <span class="material-symbols-outlined fs-6 me-1">star</span> <?php echo round(floatval($row['avg_rating'] ?? 0), 1); ?> דירוג
                            </p>

                            <p class="text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.7; flex: 1;">
                                <?php echo htmlspecialchars($row['description'] ?? ''); ?>
                            </p>

                            <div class="text-start">
                                <a href="trail_details.php?id=<?php echo $trail_id; ?>" class="btn btn-outline-custom px-4 py-2">לפרטים נוספים</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5" style="background: var(--surface-container-lowest); border-radius: 1rem; box-shadow: 0 20px 40px rgba(47,79,79,0.06);">
                    <span class="material-symbols-outlined mb-3" style="font-size: 4rem; color: var(--outline-variant);">search_off</span>
                    <h3 class="fw-bold mb-2">לא נמצאו מסלולים</h3>
                    <p class="text-muted mb-4">לא מצאנו מסלולים שתואמים את כל הסינונים שבחרת.</p>
                    <a href="search.php" class="btn btn-primary-custom px-5 py-2">נקה סינון</a>
                </div>
            <?php endif; ?>
            <?php $map_json = json_encode($map_results); ?>
        </div>
    </div>
</div>

<script>
    function initSearchMap() {
        var map = new google.maps.Map(document.getElementById('searchMap'), {
            mapTypeControl: false,
            streetViewControl: false
        });
        var trails = <?php echo isset($map_json) ? $map_json : '[]'; ?>;
        var infoWindow = new google.maps.InfoWindow();
        var bounds = new google.maps.LatLngBounds();

        if (trails.length === 0) {
            map.setCenter({ lat: 31.5, lng: 34.8 });
            map.setZoom(7);
            return;
        }

        trails.forEach(function(trail) {
            var position = { lat: parseFloat(trail.lat), lng: parseFloat(trail.lng) };
            var marker = new google.maps.Marker({
                position: position,
                map: map,
                title: trail.name,
                animation: google.maps.Animation.DROP
            });
            bounds.extend(position);
            marker.addListener('click', function() {
                var contentString = '<div style="text-align: right; direction: rtl; padding: 5px;"><h6 style="margin-bottom: 8px; font-weight: bold; color: #2c3e50;">' + trail.name + '</h6><a href="trail_details.php?id=' + trail.id + '" class="btn btn-sm btn-success text-white" style="text-decoration: none;">לפרטים</a></div>';
                infoWindow.setContent(contentString);
                infoWindow.open(map, marker);
            });
        });
        map.fitBounds(bounds);
        var listener = google.maps.event.addListener(map, "idle", function () {
            if (map.getZoom() > 13) { map.setZoom(13); }
            google.maps.event.removeListener(listener);
        });
    }
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= urlencode(getenv('GOOGLE_MAPS_API_KEY') ?: '') ?>&callback=initSearchMap"></script>
<?php include "includes/footer.php"; ?>