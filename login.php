<?php
session_start();
include "includes/db_connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password = isset($_POST['password']) ? $_POST['password'] : "";

    if (empty($email) || empty($password)) {
        $error = "נא למלא אימייל וסיסמה.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "נא להזין כתובת אימייל תקינה.";
    } else {

        $stmt = $conn->prepare("
            SELECT user_id, full_name, email, password, role
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $row = $result->fetch_assoc();

            if (password_verify($password, $row['password'])) {

                session_regenerate_id(true);

                $_SESSION["loggedin"] = true;
                $_SESSION["user_id"] = $row['user_id'];
                $_SESSION["full_name"] = $row['full_name'];
                $_SESSION["role"] = $row['role'];

                if ($row["role"] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: index.php");
                }

                exit();

            } else {
                $error = "הסיסמה שגויה.";
            }

        } else {
            $error = "כתובת האימייל לא נמצאה.";
        }
    }
}

$page_title = "התחברות - TrailFinder";
include "includes/header.php";


$clientID = getenv('GOOGLE_CLIENT_ID') ?: '';
$redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/TrailFinder/google_callback.php';
$google_login_url = "https://accounts.google.com/o/oauth2/auth?client_id=" . $clientID . "&redirect_uri=" . $redirectUri . "&response_type=code&scope=email profile&prompt=select_account";

?>

<div class="auth-card">

    <div class="text-center mb-4">
        <span class="material-symbols-outlined mb-2" style="font-size: 3rem; color: var(--primary-color);">lock_open</span>
        <h2 class="fw-black mb-1">ברוכים השבים</h2>
        <p class="text-muted">התחבר כדי לשמור מסלולים אהובים</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-custom-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <div class="mb-3">
            <label class="form-label">כתובת אימייל</label>
            <input
                type="email"
                name="email"
                class="form-control"
                required
                placeholder="הכנס את האימייל שלך"
                value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
            >
        </div>

        <div class="mb-3">
            <label class="form-label">סיסמה</label>
            <input
                type="password"
                name="password"
                class="form-control"
                required
                placeholder="הכנס את הסיסמה שלך"
            >
        </div>

        <div class="text-start mb-3">
         <a href="forgot_password.php" class="text-decoration-none text-primary small fw-bold">שכחת סיסמה?</a>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 py-3 fs-5 mt-2">
            התחברות
        </button>

        <div class="d-flex align-items-center my-3">
             <hr class="flex-grow-1 text-muted" style="opacity: 0.2;">
            <span class="mx-3 text-muted small fw-bold">או</span>
            <hr class="flex-grow-1 text-muted" style="opacity: 0.2;">
        </div>

        <a href="<?php echo $google_login_url; ?>" class="btn w-100 fw-bold py-2 mb-3 d-flex justify-content-center align-items-center gap-2" style="background-color: #fff; color: #db4437; border: 2px solid #db4437; border-radius: 0.75rem; transition: 0.3s;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-google" viewBox="0 0 16 16">
                <path d="M15.545 6.558a9.42 9.42 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.689 7.689 0 0 1 5.352 2.082l-2.284 2.284A4.347 4.347 0 0 0 8 3.166c-2.087 0-3.86 1.408-4.492 3.304a4.792 4.792 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.702 3.702 0 0 0 1.599-2.431H8v-3.08h7.545z"/>
            </svg>
             התחבר באמצעות Google
        </a>

    </form>

    <div class="text-center mt-4">
        <span class="text-muted">אין לך חשבון?</span>
        <a href="register.php" class="text-success fw-bold text-decoration-none me-1">
            הירשם כאן
        </a>
    </div>

</div>

<?php include "includes/footer.php"; ?>