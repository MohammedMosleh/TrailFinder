<?php
session_start();
include "includes/db_connect.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_btn'])) {
    $email = $conn->real_escape_string($_POST["email"]);
    $temp_pass = $conn->real_escape_string(trim($_POST["temp_pass"]));
    $new_pass = $_POST["new_pass"];
    $confirm_pass = $_POST["confirm_pass"];

    if ($new_pass !== $confirm_pass) {
        $message = "<div class='alert alert-danger'>הסיסמאות אינן תואמות</div>";
    } else {
        $sql = "SELECT * FROM users WHERE email = '$email' AND reset_token_hash = '$temp_pass' AND reset_token_expires_at > NOW()";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            
            $update_sql = "UPDATE users SET 
                            password = '$hashed_password', 
                            reset_token_hash = NULL, 
                            reset_token_expires_at = NULL 
                           WHERE email = '$email'";
            
            if ($conn->query($update_sql)) {
                $message = "<div class='alert alert-success'>הסיסמה שונתה בהצלחה! מייד תועבר לדף ההתחברות.</div>";
                header("refresh:3;url=login.php");
            }
        } else {
            $message = "<div class='alert alert-danger'>קוד זמני שגוי או פג תוקף</div>";
        }
    }
}

$page_title = "איפוס סיסמה | TrailFinder";
include "includes/header.php";
?>

<div class="container mt-5 py-5 text-end" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4 rounded-4">
                <h2 class="fw-bold mb-4">איפוס סיסמה חדשה</h2>
                
                <?php echo $message; ?>

                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label">דואר אלקטרוני</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $_SESSION['reset_email'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3 text-start">
                        <label class="form-label">קוד זמני (מהמייל)</label>
                        <input type="text" name="temp_pass" class="form-control" maxlength="6" required placeholder="123456">
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label">סיסמה חדשה</label>
                        <input type="password" name="new_pass" class="form-control" required minlength="6">
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label">אימות סיסמה חדשה</label>
                        <input type="password" name="confirm_pass" class="form-control" required minlength="6">
                    </div>

                    <button type="submit" name="reset_btn" class="btn btn-primary w-100 py-2 fw-bold">עדכן סיסמה</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>