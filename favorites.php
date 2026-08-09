<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = intval($_SESSION["user_id"]);

$sql = "SELECT t.* FROM trails t 
        INNER JOIN favorites f ON t.trail_id = f.trail_id 
        WHERE f.user_id = $user_id
        ORDER BY f.created_at DESC";

$result = $conn->query($sql);

$page_title = "המועדפים שלי | TrailFinder";
include "includes/header.php";
?>

<div class="page-header" style="background-color: #fbfbe2;">
    <div class="container">
        <span class="text-success fw-bold text-uppercase small d-block mb-2" style="letter-spacing: 2px;">שמור</span>
        <h1 class="fw-black display-5 mb-2">המועדפים שלי</h1>
        <p class="text-muted fs-5">המסלולים ששמרת לזמן הנכון.</p>
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
                            <div class="d-flex align-items-center gap-2">
                                <span class="custom-badge"><?php echo htmlspecialchars($difficulty_map[$row['difficulty']] ?? $row['difficulty']); ?></span>
                                
                                <form method="POST" action="toggle_favorite.php" class="m-0">
                                    <input type="hidden" name="trail_id" value="<?php echo $row['trail_id']; ?>">
                                    <input type="hidden" name="return_url" value="favorites.php">
                                    <button type="submit" class="btn btn-sm text-danger border-0 p-0 shadow-none bg-transparent" title="הסר ממועדפים">
                                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 1.8rem;">favorite</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <p class="small text-muted mb-2">
                            <span class="material-symbols-outlined fs-6 me-1">location_on</span>
                            <?php echo htmlspecialchars($region_map[$row['region']] ?? $row['region']); ?>
                            &nbsp;&bull;&nbsp;
                            <span class="material-symbols-outlined fs-6 me-1">visibility</span>
                            <?php echo $row['views']; ?> צפיות
                        </p>
                        
                        <p class="text-muted small mb-3" style="line-height: 1.7; flex: 1;">
                            <?php echo htmlspecialchars($row['description']); ?>
                        </p>
                        
                        <div class="text-start">
                            <a href="trail_details.php?id=<?php echo $row['trail_id']; ?>" class="btn btn-outline-custom px-4 py-2">לפרטים נוספים</a>
                        </div>
                    </div>
                </div>
            <?php 
                } 
            } else {
            ?>
                <div class="text-center py-5" style="background: var(--surface-container-lowest); border-radius: 1rem; box-shadow: 0 20px 40px rgba(47,79,79,0.06);">
                    <span class="material-symbols-outlined mb-3" style="font-size: 4rem; color: var(--outline-variant);">favorite_border</span>
                    <h3 class="fw-bold mb-2">אין מסלולים מועדפים</h3>
                    <p class="text-muted mb-4">לא שמרת עדיין שום מסלול למועדפים שלך.</p>
                    <a href="search.php" class="btn btn-primary-custom px-5 py-2">חפש מסלולים</a>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
