<?php
session_start();
include "includes/db_connect.php";

/* Only normal users can report problems */
if (
    !isset($_SESSION['loggedin']) ||
    $_SESSION['loggedin'] !== true ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'user'
) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['trail_id']) || empty($_GET['trail_id'])) {
    header("Location: index.php");
    exit();
}

$trail_id = intval($_GET['trail_id']);
$user_id = intval($_SESSION['user_id']);

$success_message = "";
$error_message = "";
$report_type = "";
$message = "";

/* Get trail info */
$stmt = $conn->prepare("SELECT trail_id, name FROM trails WHERE trail_id = ?");
$stmt->bind_param("i", $trail_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$trail = $result->fetch_assoc();

/* Save report */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $report_type = isset($_POST['report_type']) ? trim($_POST['report_type']) : "";
    $message = isset($_POST['message']) ? trim($_POST['message']) : "";

    if (empty($report_type) || empty($message)) {

        $error_message = "נא לבחור סוג דיווח ולכתוב פירוט.";

    } else {

        /* Check if same user already reported same type for same trail */
        $check_stmt = $conn->prepare("
            SELECT report_id, status
            FROM reports
            WHERE user_id = ? AND trail_id = ? AND report_type = ?
            LIMIT 1
        ");

        $check_stmt->bind_param("iis", $user_id, $trail_id, $report_type);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {

            $existing_report = $check_result->fetch_assoc();

            $error_message = "כבר שלחת דיווח מסוג זה עבור המסלול הזה. צוות האתר יבדוק את הדיווח.";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO reports (user_id, trail_id, report_type, message)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param("iiss", $user_id, $trail_id, $report_type, $message);

            if ($stmt->execute()) {
                $success_message = "הדיווח נשלח בהצלחה. תודה שעזרת לנו לשפר את המידע באתר.";
                $report_type = "";
                $message = "";
            } else {
                $error_message = "אירעה שגיאה בשליחת הדיווח. נסה שוב.";
            }
        }
    }
}

$page_title = "דווח על טעות | TrailFinder";
include "includes/header.php";
?>

<div class="container py-5 mt-5" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">

                    <div class="text-center mb-4">
                        <span class="material-symbols-outlined mb-2" style="font-size: 3rem; color: #f0ad4e;">report</span>

                        <h2 class="fw-bold text-warning mb-2">
                            דווח על טעות
                        </h2>

                        <p class="text-muted mb-0">
                            מסלול:
                            <strong><?php echo htmlspecialchars($trail['name']); ?></strong>
                        </p>
                    </div>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success text-center">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger text-center">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <form action="report_problem.php?trail_id=<?php echo $trail_id; ?>" method="POST">

                        <div class="mb-3">
                            <label class="form-label fw-bold">סוג הדיווח</label>

                            <select name="report_type" class="form-select" required>
                                <option value="">בחר סוג דיווח</option>

                                <option value="מידע שגוי" <?php echo ($report_type == 'מידע שגוי') ? 'selected' : ''; ?>>
                                    מידע שגוי
                                </option>

                                <option value="פרטים חסרים" <?php echo ($report_type == 'פרטים חסרים') ? 'selected' : ''; ?>>
                                    פרטים חסרים
                                </option>

                                <option value="תמונה לא מתאימה" <?php echo ($report_type == 'תמונה לא מתאימה') ? 'selected' : ''; ?>>
                                    תמונה לא מתאימה
                                </option>

                                <option value="ביקורת פוגענית" <?php echo ($report_type == 'ביקורת פוגענית') ? 'selected' : ''; ?>>
                                    ביקורת פוגענית
                                </option>

                                <option value="אחר" <?php echo ($report_type == 'אחר') ? 'selected' : ''; ?>>
                                    אחר
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">פירוט הדיווח</label>

                            <textarea
                                name="message"
                                class="form-control"
                                rows="5"
                                required
                                placeholder="כתוב כאן מה הבעיה שמצאת..."
                            ><?php echo htmlspecialchars($message); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold rounded-pill py-3">
                            שלח דיווח
                        </button>

                    </form>

                    <div class="text-center mt-4">
                        <a href="trail_details.php?id=<?php echo $trail_id; ?>" class="text-muted text-decoration-none fw-bold">
                            חזרה לדף המסלול
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>