<?php
session_start();
include "includes/db_connect.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $title = trim($_POST['title']);
    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO super_categories (title, status) VALUES (?, 'active')");
        $stmt->bind_param("s", $title);
        $stmt->execute();
        $new_id = $stmt->insert_id;
        
        if (!empty($_POST['trail_ids'])) {
            foreach ($_POST['trail_ids'] as $tid) {
                $stmt_link = $conn->prepare("INSERT INTO super_category_trails (category_id, trail_id) VALUES (?, ?)");
                $tid_int = intval($tid);
                $stmt_link->bind_param("ii", $new_id, $tid_int);
                $stmt_link->execute();
            }
        }
        header("Location: admin_categories.php?success=1");
        exit();
    }
}

if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM super_categories WHERE category_id = $del_id");
    header("Location: admin_categories.php?deleted=1");
    exit();
}

$page_title = "ניהול קטגוריות-על | TrailFinder";
include "includes/header.php";
?>

<div class="container py-4">
    
    <div class="d-flex justify-content-center mb-5 border-bottom pb-3">
        <a href="admin_dashboard.php" class="nav-link fw-bold mx-3" style="color: #6c757d;">סקירה כללית</a>
        <a href="admin_dashboard.php" class="nav-link fw-bold mx-3" style="color: #6c757d;">ניהול מסלולים</a>
        <a href="admin_categories.php" class="nav-link fw-bold mx-3 bg-primary text-white px-4 rounded-pill">ניהול קטגוריות-על</a>
        <a href="#" class="nav-link fw-bold mx-3" style="color: #6c757d;">הודעות נכנסות</a>
        <a href="#" class="nav-link fw-bold mx-3" style="color: #6c757d;">ביקורות</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
            הקטגוריה נוצרה בהצלחה!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <button class="btn btn-success fw-bold px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#addCatModal">
                + הוסף קטגוריה חדשה
            </button>
            <h3 class="fw-black m-0">רשימת קטגוריות-על</h3>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-center" dir="rtl">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>שם הקטגוריה</th>
                        <th>מסלולים משויכים</th>
                        <th>סטטוס</th>
                        <th>פעולות</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $existing_cats = $conn->query("
                        SELECT sc.*, COUNT(sct.trail_id) as trail_count 
                        FROM super_categories sc 
                        LEFT JOIN super_category_trails sct ON sc.category_id = sct.category_id 
                        GROUP BY sc.category_id 
                        ORDER BY sc.category_id DESC
                    ");
                    
                    if ($existing_cats->num_rows > 0) {
                        while($cat = $existing_cats->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td class='text-muted'>#{$cat['category_id']}</td>";
                            echo "<td class='fw-bold'>".htmlspecialchars($cat['title'])."</td>";
                            echo "<td><span class='badge bg-info text-dark rounded-pill'>{$cat['trail_count']} מסלולים</span></td>";
                            echo "<td><span class='badge bg-success'>פעיל</span></td>";
                            echo "<td>
                                    <div class='d-flex justify-content-center gap-2'>
                                        <a href='#' class='btn btn-sm btn-primary'>ערוך</a>
                                        <a href='admin_categories.php?delete_id={$cat['category_id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק קטגוריה זו?\");'>מחק</a>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center py-4 text-muted'>לא נמצאו קטגוריות</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addCatModal" tabindex="-1" aria-labelledby="addCatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom-0 bg-light">
                <h5 class="modal-title fw-bold" id="addCatModalLabel">הוסף קטגוריה חדשה</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="admin_categories.php">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label fw-bold">שם הקטגוריה <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="למשל: טיולי מים מרעננים" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">בחר מסלולים לשיוך (אופציונלי):</label>
                        <div class="p-3 bg-light rounded-3 border" style="max-height: 250px; overflow-y: auto;">
                            <?php
                            $all_trails = $conn->query("SELECT trail_id, name FROM trails ORDER BY name ASC");
                            while($t = $all_trails->fetch_assoc()) {
                                echo "<div class='form-check mb-2'>";
                                echo "<input class='form-check-input' type='checkbox' name='trail_ids[]' value='{$t['trail_id']}' id='modal_t{$t['trail_id']}'>";
                                echo "<label class='form-check-label' for='modal_t{$t['trail_id']}'>{$t['name']}</label>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ביטול</button>
                    <button type="submit" name="add_category" class="btn btn-success fw-bold rounded-pill px-4">שמור קטגוריה</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>