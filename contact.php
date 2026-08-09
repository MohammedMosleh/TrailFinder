<?php
session_start();
include "includes/db_connect.php";

$page_title = "צור קשר | TrailFinder";

$success_message = "";
$error_message = "";

$full_name = "";
$email = "";
$subject = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = isset($_POST["full_name"]) ? trim($_POST["full_name"]) : "";
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $subject = isset($_POST["subject"]) ? trim($_POST["subject"]) : "";
    $message = isset($_POST["message"]) ? trim($_POST["message"]) : "";

    if (empty($full_name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "נא למלא את כל השדות.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "נא להזין כתובת אימייל תקינה.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO contact_messages (full_name, email, subject, message)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param("ssss", $full_name, $email, $subject, $message);

        if ($stmt->execute()) {
            $success_message = "הודעתך נשלחה בהצלחה! תודה.";
            $full_name = "";
            $email = "";
            $subject = "";
            $message = "";
        } else {
            $error_message = "אירעה שגיאה בשליחת ההודעה. נסה שוב.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5 mt-5" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">

                    <h2 class="text-center text-success mb-4 fw-bold">
                        צור קשר
                    </h2>

                    <p class="text-center text-muted mb-4">
                        יש לכם שאלה? מצאתם טעות באתר? נשמח לשמוע מכם!
                    </p>

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

                    <form action="contact.php" method="POST">

                        <div class="mb-3">
                            <label class="form-label fw-bold">שם מלא</label>
                            <input
                                type="text"
                                name="full_name"
                                class="form-control"
                                placeholder="השם שלך"
                                value="<?php echo htmlspecialchars($full_name); ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">אימייל לחזרה</label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                placeholder="email@example.com"
                                value="<?php echo htmlspecialchars($email); ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">נושא</label>
                            <input
                                type="text"
                                name="subject"
                                class="form-control"
                                placeholder="לדוגמה: דיווח על טעות / שאלה כללית"
                                value="<?php echo htmlspecialchars($subject); ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">הודעה</label>
                            <textarea
                                name="message"
                                class="form-control"
                                rows="5"
                                placeholder="כתוב את הודעתך כאן..."
                                required
                            ><?php echo htmlspecialchars($message); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold rounded-pill py-2">
                            שלח הודעה
                        </button>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>