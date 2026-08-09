<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = intval($_SESSION["user_id"]);

$sql = "SELECT t.*, h.viewed_at 
        FROM trails t 
        INNER JOIN history h ON t.trail_id = h.trail_id 
        WHERE h.user_id = $user_id 
        ORDER BY h.viewed_at DESC";

$result = $conn->query($sql);

$page_title = "היסטוריית צפיות | TrailFinder";
include "includes/header.php";
?>

<div class="page-header" style="background-color: #f0f4f8;">
    <div class="container">
        <span class="text-primary fw-bold text-uppercase small d-block mb-2" style="letter-spacing: 2px;">פעילות אחרונה</span>
        <h1 class="fw-black display-5 mb-2">היסטוריית צפיות</h1>
        <p class="text-muted fs-5">המסלולים האחרונים שביקרת בהם.</p>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-9">
            <?php 
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) { 
                    $img_url = htmlspecialchars($row['image_url']);
            ?>
                <div class="result-card position-relative">
                    <div class="result-card-img" style="background-image: url('<?php echo $img_url; ?>');">
                        <?php if(empty($row['image_url'])): ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;background:#bdc3c7;">אין תמונה</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="result-card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h3 class="h5 fw-bold mb-0" style="color: var(--primary-color);"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <span class="text-muted small">
                                <span class="material-symbols-outlined fs-6" style="vertical-align: middle;">schedule</span>
                                צפית ב-<?php echo date('d/m/Y', strtotime($row['viewed_at'])); ?>
                            </span>
                        </div>
                        
                        <p class="small text-muted mb-2">
                            <span class="material-symbols-outlined fs-6 me-1">location_on</span>
                            <?php echo htmlspecialchars($row['region']); ?>
                        </p>
                        
                        <p class="text-muted small mb-3 text-truncate-2">
                            <?php echo htmlspecialchars($row['description']); ?>
                        </p>
                        
                        <div class="text-start">
                            <a href="trail_details.php?id=<?php echo $row['trail_id']; ?>" class="btn btn-outline-custom px-4 py-2">ביקור חוזר</a>
                        </div>
                    </div>
                </div>
            <?php 
                } 
            } else {
            ?>
                <div class="text-center py-5">
                    <span class="material-symbols-outlined mb-3" style="font-size: 4rem; color: var(--outline-variant);">history</span>
                    <h3 class="fw-bold mb-2">ההיסטוריה שלך ריקה</h3>
                    <p class="text-muted mb-4">עדיין לא ביקרת במסלולים. התחל לחקור עכשיו!</p>
                    <a href="search.php" class="btn btn-primary-custom px-5 py-2">גלה מסלולים</a>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>