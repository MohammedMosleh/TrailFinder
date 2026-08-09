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

$total_trails = $conn->query("SELECT COUNT(*) as count FROM trails")->fetch_assoc()['count'];
$total_messages = $conn->query("SELECT COUNT(*) as count FROM contact_messages")->fetch_assoc()['count'];
$total_reports = $conn->query("SELECT COUNT(*) as count FROM reports")->fetch_assoc()['count'];
$new_reports = $conn->query("SELECT COUNT(*) as count FROM reports WHERE status = 'new'")->fetch_assoc()['count'];
$pending_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'")->fetch_assoc()['count'];
$total_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$north_trails = $conn->query("SELECT COUNT(*) as count FROM trails WHERE region = 'צפון'")->fetch_assoc()['count'];
$center_trails = $conn->query("SELECT COUNT(*) as count FROM trails WHERE region = 'מרכז'")->fetch_assoc()['count'];
$south_trails = $conn->query("SELECT COUNT(*) as count FROM trails WHERE region = 'דרום'")->fetch_assoc()['count'];

$easy_trails = $conn->query("SELECT COUNT(*) as count FROM trails WHERE difficulty = 'קל'")->fetch_assoc()['count'];
$medium_trails = $conn->query("SELECT COUNT(*) as count FROM trails WHERE difficulty = 'בינוני'")->fetch_assoc()['count'];
$hard_trails = $conn->query("SELECT COUNT(*) as count FROM trails WHERE difficulty = 'קשה'")->fetch_assoc()['count'];
$top_trails_result = $conn->query("SELECT trail_id, name, views FROM trails ORDER BY views DESC LIMIT 5");
$trails_result = $conn->query("SELECT * FROM trails ORDER BY trail_id DESC");
$messages_result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");

$reports_result = $conn->query("
    SELECT 
        r.report_id,
        r.report_type,
        r.message,
        r.status,
        r.created_at,
        u.full_name,
        u.email,
        t.name AS trail_name,
        t.trail_id
    FROM reports r
    JOIN users u ON r.user_id = u.user_id
    JOIN trails t ON r.trail_id = t.trail_id
    ORDER BY r.created_at DESC
");

$reviews_result = $conn->query("
    SELECT 
        r.review_id,
        r.rating,
        r.comment,
        r.status,
        r.created_at,
        u.full_name,
        u.email,
        t.name AS trail_name,
        t.trail_id
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN trails t ON r.trail_id = t.trail_id
    ORDER BY 
        CASE 
            WHEN r.status = 'pending' THEN 1
            WHEN r.status = 'approved' THEN 2
            ELSE 3
        END,
        r.created_at DESC
");

$page_title = "לוח בקרה למנהל - TrailFinder";
include 'includes/header.php';
?>

<div class="container py-5 mt-4" dir="rtl">

    <h2 class="mb-4 fw-bold" style="color: var(--primary-color);">
        לוח בקרה - מנהל מערכת 👑
    </h2>

    <ul class="nav nav-pills mb-4 gap-2" id="adminTabs" role="tablist">

        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold px-4 rounded-pill" data-bs-toggle="pill" data-bs-target="#overview" type="button">
                סקירה כללית
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4 rounded-pill" data-bs-toggle="pill" data-bs-target="#trails" type="button">
                ניהול מסלולים
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <a href="admin_categories.php" class="nav-link fw-bold px-4 rounded-pill">
                ניהול קטגוריות-על
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4 rounded-pill" data-bs-toggle="pill" data-bs-target="#messages" type="button">
                הודעות נכנסות
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4 rounded-pill" data-bs-toggle="pill" data-bs-target="#reports" type="button">
                דיווחים
                <?php if ($new_reports > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $new_reports; ?></span>
                <?php endif; ?>
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4 rounded-pill" data-bs-toggle="pill" data-bs-target="#reviews" type="button">
                ביקורות
                <?php if ($pending_reviews > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $pending_reviews; ?></span>
                <?php endif; ?>
            </button>
        </li>

    </ul>

    <div class="tab-content" id="adminTabsContent">

        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 rounded-4" style="background: linear-gradient(135deg, #27ae60, #2ecc71); color: white;">
                        <div class="card-body p-4 text-center">
                            <h5 class="fw-bold mb-3">מסלולים</h5>
                            <h1 class="display-4 fw-black"><?php echo $total_trails; ?></h1>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow-sm border-0 rounded-4" style="background: linear-gradient(135deg, #f39c12, #f1c40f); color: white;">
                        <div class="card-body p-4 text-center">
                            <h5 class="fw-bold mb-3">הודעות</h5>
                            <h1 class="display-4 fw-black"><?php echo $total_messages; ?></h1>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow-sm border-0 rounded-4" style="background: linear-gradient(135deg, #c0392b, #e74c3c); color: white;">
                        <div class="card-body p-4 text-center">
                            <h5 class="fw-bold mb-3">דיווחים חדשים</h5>
                            <h1 class="display-4 fw-black"><?php echo $new_reports; ?></h1>
                            <p class="mb-0">סה"כ: <?php echo $total_reports; ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow-sm border-0 rounded-4" style="background: linear-gradient(135deg, #2980b9, #3498db); color: white;">
                        <div class="card-body p-4 text-center">
                            <h5 class="fw-bold mb-3">ביקורות לאישור</h5>
                            <h1 class="display-4 fw-black"><?php echo $pending_reviews; ?></h1>
                            <p class="mb-0">סה"כ: <?php echo $total_reviews; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 rounded-4 p-4 mt-4">
                                <h4 class="fw-bold mb-4" style="color: var(--primary-color);">
                    נתוני אנליטיקס
                </h4>

                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded-4 text-center" style="background-color: #f8f9fa;">
                            <h6 class="fw-bold text-muted mb-2">משתמשים רשומים</h6>
                            <h3 class="fw-bold text-success mb-0"><?php echo $total_users; ?></h3>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded-4 text-center" style="background-color: #f8f9fa;">
                            <h6 class="fw-bold text-muted mb-2">מסלולים בצפון</h6>
                            <h3 class="fw-bold text-success mb-0"><?php echo $north_trails; ?></h3>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded-4 text-center" style="background-color: #f8f9fa;">
                            <h6 class="fw-bold text-muted mb-2">מסלולים במרכז</h6>
                            <h3 class="fw-bold text-success mb-0"><?php echo $center_trails; ?></h3>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded-4 text-center" style="background-color: #f8f9fa;">
                            <h6 class="fw-bold text-muted mb-2">מסלולים בדרום</h6>
                            <h3 class="fw-bold text-success mb-0"><?php echo $south_trails; ?></h3>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 rounded-4 text-center" style="background-color: #f8f9fa;">
                            <h6 class="fw-bold text-muted mb-2">מסלולים קלים</h6>
                            <h3 class="fw-bold text-primary mb-0"><?php echo $easy_trails; ?></h3>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded-4 text-center" style="background-color: #f8f9fa;">
                            <h6 class="fw-bold text-muted mb-2">מסלולים בינוניים</h6>
                            <h3 class="fw-bold text-warning mb-0"><?php echo $medium_trails; ?></h3>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded-4 text-center" style="background-color: #f8f9fa;">
                            <h6 class="fw-bold text-muted mb-2">מסלולים קשים</h6>
                            <h3 class="fw-bold text-danger mb-0"><?php echo $hard_trails; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 rounded-4 p-4 mt-4">
                                <h4 class="fw-bold mb-4" style="color: var(--primary-color);">
                    המסלולים הפופולריים ביותר
                </h4>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>מקום</th>
                                <th>שם המסלול</th>
                                <th>צפיות</th>
                                <th>פעולה</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($top_trails_result && $top_trails_result->num_rows > 0): ?>
                                <?php $rank = 1; ?>
                                <?php while ($top = $top_trails_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold text-success">
                                            #<?php echo $rank; ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?php echo htmlspecialchars($top['name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                <?php echo intval($top['views']); ?> צפיות
                                            </span>
                                        </td>
                                        <td>
                                            <a href="trail_details.php?id=<?php echo intval($top['trail_id']); ?>" class="btn btn-sm btn-outline-success fw-bold">
                                                צפייה
                                            </a>
                                        </td>
                                    </tr>
                                    <?php $rank++; ?>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        אין נתוני צפיות להצגה
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card shadow-sm border-0 rounded-4 p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="fw-bold mb-1">גיבוי נתונים</h4>
                        <p class="text-muted mb-0">
                            הורדת קובץ SQL מלא של מסד הנתונים לצורך שמירה ושחזור במקרה הצורך.
                        </p>
                    </div>
                    
                    <a href="admin_backup.php" class="btn btn-dark fw-bold rounded-pill px-4 py-2">
                        <span class="material-symbols-outlined me-1" style="vertical-align: middle;">download</span>
                        גיבוי מסד נתונים
                    </a>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="trails" role="tabpanel">
            <div class="card shadow-sm border-0 rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">רשימת מסלולים</h4>
                    <a href="add_trail.php" class="btn btn-success fw-bold rounded-pill px-4">
                        + הוסף מסלול חדש
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>שם המסלול</th>
                                <th>אזור</th>
                                <th>צפיות</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($trails_result->num_rows > 0): ?>
                                <?php while ($row = $trails_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            #<?php echo intval($row['trail_id']); ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($row['region']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                <?php echo intval($row['views']); ?> צפיות
                                            </span>
                                        </td>
                                        <td>
                                            <a href="trail_details.php?id=<?php echo intval($row['trail_id']); ?>" class="btn btn-sm btn-outline-success fw-bold">
                                                צפייה
                                            </a>
                                            <a href="edit_trail.php?id=<?php echo intval($row['trail_id']); ?>" class="btn btn-sm btn-primary fw-bold">
                                                ערוך
                                            </a>
                                            <a href="delete_trail.php?id=<?php echo intval($row['trail_id']); ?>"
                                               class="btn btn-sm btn-danger fw-bold"
                                               onclick="return confirm('האם אתה בטוח שברצונך למחוק מסלול זה?');">
                                                מחק
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        אין מסלולים במערכת
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="messages" role="tabpanel">
            <div class="card shadow-sm border-0 rounded-4 p-4">
                <h4 class="fw-bold mb-4">הודעות מהגולשים</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>שם השולח</th>
                                <th>אימייל</th>
                                <th>נושא</th>
                                <th>התוכן</th>
                                <th>תאריך</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($messages_result->num_rows > 0): ?>
                                <?php while ($msg = $messages_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold text-success">
                                            <?php echo htmlspecialchars($msg['full_name']); ?>
                                        </td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($msg['email']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($msg['subject']); ?>
                                        </td>
                                        <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($msg['message']); ?>">
                                            <?php echo htmlspecialchars($msg['message']); ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        אין הודעות חדשות
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="reports" role="tabpanel">
            <div class="card shadow-sm border-0 rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">דיווחים ממשתמשים</h4>
                    <span class="badge bg-danger fs-6">
                        חדשים: <?php echo $new_reports; ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>משתמש</th>
                                <th>מסלול</th>
                                <th>סוג דיווח</th>
                                <th>הודעה</th>
                                <th>סטטוס</th>
                                <th>תאריך</th>
                                <th>פעולה</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($reports_result->num_rows > 0): ?>
                                <?php while ($rep = $reports_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            #<?php echo intval($rep['report_id']); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($rep['full_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($rep['email']); ?></small>
                                        </td>
                                        <td>
                                            <a href="trail_details.php?id=<?php echo intval($rep['trail_id']); ?>" class="text-decoration-none fw-bold">
                                                <?php echo htmlspecialchars($rep['trail_name']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                <?php echo htmlspecialchars($rep['report_type']); ?>
                                            </span>
                                        </td>
                                        <td style="max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($rep['message']); ?>">
                                            <?php echo htmlspecialchars($rep['message']); ?>
                                        </td>
                                        <td>
                                            <?php if ($rep['status'] === 'new'): ?>
                                                <span class="badge bg-danger">חדש</span>
                                            <?php elseif ($rep['status'] === 'checked'): ?>
                                                <span class="badge bg-primary">נבדק</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">סגור</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?php echo date('d/m/Y H:i', strtotime($rep['created_at'])); ?>
                                        </td>
                                        <td>
                                            <a href="admin_update_report.php?id=<?php echo intval($rep['report_id']); ?>&status=checked"
                                               class="btn btn-sm btn-outline-primary fw-bold">
                                                סמן כנבדק
                                            </a>
                                            <a href="admin_update_report.php?id=<?php echo intval($rep['report_id']); ?>&status=closed"
                                               class="btn btn-sm btn-outline-secondary fw-bold">
                                                סגור
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        אין דיווחים במערכת
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="reviews" role="tabpanel">
            <div class="card shadow-sm border-0 rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">ניהול ביקורות</h4>
                    <span class="badge bg-danger fs-6">
                        ממתינות לאישור: <?php echo $pending_reviews; ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>משתמש</th>
                                <th>מסלול</th>
                                <th>דירוג</th>
                                <th>תגובה</th>
                                <th>סטטוס</th>
                                <th>תאריך</th>
                                <th>פעולה</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($reviews_result->num_rows > 0): ?>
                                <?php while ($rev = $reviews_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            #<?php echo intval($rev['review_id']); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($rev['full_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($rev['email']); ?></small>
                                        </td>
                                        <td>
                                            <a href="trail_details.php?id=<?php echo intval($rev['trail_id']); ?>" class="text-decoration-none fw-bold">
                                                <?php echo htmlspecialchars($rev['trail_name']); ?>
                                            </a>
                                        </td>
                                        <td class="text-warning fw-bold">
                                            <?php echo intval($rev['rating']); ?> ★
                                        </td>
                                        <td style="max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($rev['comment']); ?>">
                                            <?php echo htmlspecialchars($rev['comment']); ?>
                                        </td>
                                        <td>
                                            <?php if ($rev['status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">ממתינה</span>
                                            <?php elseif ($rev['status'] === 'approved'): ?>
                                                <span class="badge bg-success">מאושרת</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">נדחתה</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?php echo date('d/m/Y H:i', strtotime($rev['created_at'])); ?>
                                        </td>
                                        <td>
                                            <a href="admin_update_review.php?id=<?php echo intval($rev['review_id']); ?>&status=approved"
                                               class="btn btn-sm btn-outline-success fw-bold">
                                                אשר
                                            </a>
                                            <a href="admin_update_review.php?id=<?php echo intval($rev['review_id']); ?>&status=rejected"
                                               class="btn btn-sm btn-outline-danger fw-bold">
                                                דחה
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        אין ביקורות במערכת
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>