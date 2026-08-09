<?php
session_start();
include "includes/db_connect.php";

require 'includes/PHPMailer/Exception.php';
require 'includes/PHPMailer/PHPMailer.php';
require 'includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generatePassword() {
    $newPassword = '';
    $str = "1234567890abcdefghijklnmopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*";
    $str_length = strlen($str);
    for ($i = 0; $i < 6; $i++) {
        $x = rand(0, $str_length - 1);
        $newPassword .= $str[$x];
    }
    return $newPassword;
}

$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forgot_btn'])) {
    $email = $conn->real_escape_string($_POST["email"]);
    $foundForReset = false;

    $user_query = $conn->query("SELECT * FROM users WHERE email = '$email'");
    
    if ($user_query->num_rows > 0) {
        $usr = $user_query->fetch_assoc();
        $foundForReset = true;
        
        $newPass = generatePassword();
        
        $conn->query("UPDATE users SET reset_token_hash = '$newPass', reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE email = '$email'");

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username = getenv('MAIL_USERNAME') ?: ''; 
            $mail->Password = getenv('MAIL_PASSWORD') ?: '';    
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom(getenv('MAIL_FROM_ADDRESS') ?: $mail->Username, 'TrailFinder');
            $mail->addAddress($email);

            $link = "http://localhost/TrailFinder/reset_password.php";

            $mail->isHTML(true);
            $mail->Subject = 'TrailFinder - Password Reset';
            
            $message = "<b>Password Reset</b>";
            $message .= "<h1>Your temporary password is: " . $newPass . "</h1>";
            $message .= "<p>Use this temporary password to verify your identity when updating your password.</p>";
            $message .= "<p>Click here to update your password:</p>";
            $message .= "<p><a href='" . htmlspecialchars($link) . "'>Update New Password</a></p>";
            
            $mail->Body = $message;

            $mail->send();
            
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_pass'] = $newPass;
            $_SESSION['reset_sent'] = 1;
            $_SESSION['pending_password_reset_' . $email] = true;

            header("Location: reset_password.php");
            exit;
        } catch (Exception $e) {
            $errorMsg = "<div class='alert alert-danger'>Error sending email: {$mail->ErrorInfo}</div>";
        }
    }

    if (!$foundForReset) {
        $errorMsg = "<div class='alert alert-warning text-center'>User not available</div>";
    }
}

$page_title = "Forgot Password - TrailFinder";
include "includes/header.php";
?>

<div class="container mt-5 py-5 text-end" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4 rounded-4">
                <h2 class="fw-bold mb-4">שכחת סיסמה?</h2>
                <p class="text-muted">הכנס את המייל שלך ונשלח לך סיסמה זמנית למייל.</p>
                
                <?php echo $errorMsg; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">דואר אלקטרוני</label>
                        <input type="email" name="email" class="form-control" required placeholder="name@example.com">
                    </div>
                    <button type="submit" name="forgot_btn" class="btn btn-success w-100 py-2 fw-bold">שלח סיסמה זמנית</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>