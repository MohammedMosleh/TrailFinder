<?php
session_start();
include "includes/db_connect.php";

/* Only normal logged-in users can access profile */
if (
    !isset($_SESSION["loggedin"]) ||
    $_SESSION["loggedin"] !== true ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "user" ||
    !isset($_SESSION["user_id"])
) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION["user_id"]);
$success = "";
$error = "";

/* Update full name */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_name"])) {

    $full_name = isset($_POST["full_name"]) ? trim($_POST["full_name"]) : "";

    if (empty($full_name)) {
        $error = "נא להזין שם מלא.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        $stmt->bind_param("si", $full_name, $user_id);

        if ($stmt->execute()) {
            $_SESSION["full_name"] = $full_name;
            $success = "השם עודכן בהצלחה.";
        } else {
            $error = "שגיאה בעדכון השם.";
        }
    }
}

/* Update password */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_password"])) {

    $current_password = isset($_POST["current_password"]) ? $_POST["current_password"] : "";
    $new_password = isset($_POST["new_password"]) ? $_POST["new_password"] : "";
    $confirm_password = isset($_POST["confirm_password"]) ? $_POST["confirm_password"] : "";

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "נא למלא את כל שדות הסיסמה.";
    } elseif ($new_password !== $confirm_password) {
        $error = "הסיסמאות החדשות אינן תואמות.";
    } elseif (strlen($new_password) < 6) {
        $error = "הסיסמה החדשה חייבת להכיל לפחות 6 תווים.";
    } else {

        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $pass_result = $stmt->get_result();

        if ($pass_result->num_rows > 0) {
            $user_pass = $pass_result->fetch_assoc();

            if (password_verify($current_password, $user_pass["password"])) {

                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);

                if ($update_stmt->execute()) {
                    $success = "הסיסמה עודכנה בהצלחה.";
                } else {
                    $error = "שגיאה בעדכון הסיסמה.";
                }

            } else {
                $error = "הסיסמה הנוכחית שגויה.";
            }
        } else {
            $error = "המשתמש לא נמצא.";
        }
    }
}

/* Get user details */
$stmt = $conn->prepare("
    SELECT full_name, email, created_at
    FROM users
    WHERE user_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows == 0) {
    header("Location: logout.php");
    exit();
}

$profile_user = $user_result->fetch_assoc();

/* Get last favorites */
$fav_stmt = $conn->prepare("
    SELECT t.trail_id, t.name, t.region, t.difficulty, t.image_url, f.created_at
    FROM favorites f
    INNER JOIN trails t ON f.trail_id = t.trail_id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
    LIMIT 3
");
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$fav_result = $fav_stmt->get_result();

/* Get last history */
$history_stmt = $conn->prepare("
    SELECT t.trail_id, t.name, t.region, t.difficulty, t.image_url, h.viewed_at
    FROM history h
    INNER JOIN trails t ON h.trail_id = t.trail_id
    WHERE h.user_id = ?
    ORDER BY h.viewed_at DESC
    LIMIT 3
");
$history_stmt->bind_param("i", $user_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

$page_title = "הפרופיל שלי | TrailFinder";
include "includes/header.php";
?>

<div class="page-header" style="background-color: #fbfbe2;">
    <div class="container">
        <span class="text-success fw-bold text-uppercase small d-block mb-2" style="letter-spacing: 2px;">אזור אישי</span>
        <h1 class="fw-black display-5 mb-2">הפרופיל שלי</h1>
        <p class="text-muted fs-5">ניהול פרטים אישיים, מועדפים והיסטוריית צפייה.</p>
    </div>
</div>

<div class="container pb-5" dir="rtl">

    <?php if (!empty($success)): ?>
        <div class="alert alert-success text-center fw-bold">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center fw-bold">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4 p-4 h-100">
                <div class="text-center mb-4">
                    <span class="material-symbols-outlined text-success" style="font-size: 5rem;">account_circle</span>

                    <h3 class="fw-bold mt-2">
                        <?php echo htmlspecialchars($profile_user["full_name"]); ?>
                    </h3>

                    <p class="text-muted mb-1">
                        <?php echo htmlspecialchars($profile_user["email"]); ?>
                    </p>

                    <p class="text-muted small">
                        נרשם בתאריך:
                        <?php echo date("d/m/Y", strtotime($profile_user["created_at"])); ?>
                    </p>
                </div>

                <div class="d-grid gap-2">
                    <a href="favorites.php" class="btn btn-outline-success rounded-pill">
                        המועדפים שלי
                    </a>

                    <a href="history.php" class="btn btn-outline-primary rounded-pill">
                        היסטוריית צפיות
                    </a>

                    <a href="search.php" class="btn btn-primary-custom rounded-pill">
                        חפש מסלולים
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">

            <div class="card shadow-sm border-0 rounded-4 p-4 mb-4">
                <h4 class="fw-bold mb-3">
                    <span class="material-symbols-outlined text-success">edit</span>
                    עדכון שם משתמש
                </h4>

                <form method="POST" action="profile.php">
                    <div class="mb-3">
                        <label class="form-label">שם מלא</label>
                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            value="<?php echo htmlspecialchars($profile_user["full_name"]); ?>"
                            required
                        >
                    </div>

                    <button type="submit" name="update_name" class="btn btn-success px-4">
                        שמור שם
                    </button>
                </form>
            </div>

            <div class="card shadow-sm border-0 rounded-4 p-4">
                <h4 class="fw-bold mb-3">
                    <span class="material-symbols-outlined text-success">lock_reset</span>
                    שינוי סיסמה
                </h4>

                <form method="POST" action="profile.php">
                    <div class="mb-3">
                        <label class="form-label">סיסמה נוכחית</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">סיסמה חדשה</label>
                        <input type="password" name="new_password" class="form-control" minlength="6" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">אימות סיסמה חדשה</label>
                        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                    </div>

                    <button type="submit" name="update_password" class="btn btn-success px-4">
                        עדכן סיסמה
                    </button>
                </form>
            </div>

        </div>
    </div>

    <div class="row g-4 mt-4">

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">
                        <span class="material-symbols-outlined text-danger">favorite</span>
                        מועדפים אחרונים
                    </h4>

                    <a href="favorites.php" class="text-success fw-bold text-decoration-none">
                        הצג הכל
                    </a>
                </div>

                <?php if ($fav_result->num_rows > 0): ?>
                    <?php while ($fav = $fav_result->fetch_assoc()): ?>
                        <a href="trail_details.php?id=<?php echo intval($fav["trail_id"]); ?>" class="text-decoration-none text-dark">
                            <div class="d-flex gap-3 align-items-center border-bottom py-3">
                                <img
                                    src="<?php echo htmlspecialchars($fav["image_url"]); ?>"
                                    alt="<?php echo htmlspecialchars($fav["name"]); ?>"
                                    style="width: 85px; height: 65px; object-fit: cover; border-radius: 12px;"
                                >

                                <div>
                                    <h6 class="fw-bold mb-1">
                                        <?php echo htmlspecialchars($fav["name"]); ?>
                                    </h6>

                                    <p class="text-muted small mb-0">
                                        <?php echo htmlspecialchars($fav["region"]); ?>
                                        |
                                        <?php echo htmlspecialchars($fav["difficulty"]); ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">אין עדיין מסלולים במועדפים.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">
                        <span class="material-symbols-outlined text-primary">history</span>
                        היסטוריה אחרונה
                    </h4>

                    <a href="history.php" class="text-success fw-bold text-decoration-none">
                        הצג הכל
                    </a>
                </div>

                <?php if ($history_result->num_rows > 0): ?>
                    <?php while ($hist = $history_result->fetch_assoc()): ?>
                        <a href="trail_details.php?id=<?php echo intval($hist["trail_id"]); ?>" class="text-decoration-none text-dark">
                            <div class="d-flex gap-3 align-items-center border-bottom py-3">
                                <img
                                    src="<?php echo htmlspecialchars($hist["image_url"]); ?>"
                                    alt="<?php echo htmlspecialchars($hist["name"]); ?>"
                                    style="width: 85px; height: 65px; object-fit: cover; border-radius: 12px;"
                                >

                                <div>
                                    <h6 class="fw-bold mb-1">
                                        <?php echo htmlspecialchars($hist["name"]); ?>
                                    </h6>

                                    <p class="text-muted small mb-0">
                                        צפית ב-
                                        <?php echo date("d/m/Y", strtotime($hist["viewed_at"])); ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">אין עדיין היסטוריית צפייה.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include "includes/footer.php"; ?>