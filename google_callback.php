<?php
session_start();
include "includes/db_connect.php";

// مفاتيح جوجل الخاصة بمشروعك (أخذتها من الصورة)
$clientID = getenv('GOOGLE_CLIENT_ID') ?: '';
$clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '';
$redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/TrailFinder/google_callback.php';

if (isset($_GET['code'])) {
    // 1. طلب Access Token من جوجل باستخدام الكود
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/token");
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code' => $_GET['code'],
        'client_id' => $clientID,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // مهم جداً عشان يشتغل على Localhost
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, TRUE);

    if (isset($token_data['access_token'])) {
        // 2. جلب بيانات المستخدم (الاسم والإيميل)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v2/userinfo");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_data['access_token']]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $user_info_response = curl_exec($ch);
        curl_close($ch);

        $google_user = json_decode($user_info_response, TRUE);

        if (isset($google_user['email'])) {
            $email = $google_user['email'];
            $full_name = $google_user['name'];
            $oauth_uid = $google_user['id'];

            // 3. فحص هل المستخدم موجود بقاعدة البيانات؟
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // المستخدم موجود: تسجيل دخول
                $user = $result->fetch_assoc();
                
                // إذا كان مسجل قديماً بالطريقة العادية، بنربط حسابه مع جوجل
                if (empty($user['oauth_uid'])) {
                    $update_stmt = $conn->prepare("UPDATE users SET oauth_uid = ?, auth_provider = 'google' WHERE user_id = ?");
                    $update_stmt->bind_param("si", $oauth_uid, $user['user_id']);
                    $update_stmt->execute();
                }

                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
            } else {
                // مستخدم جديد: إنشاء حساب جديد فوراً بدون باسورد
                $insert_stmt = $conn->prepare("INSERT INTO users (full_name, email, oauth_uid, auth_provider, role) VALUES (?, ?, ?, 'google', 'user')");
                $insert_stmt->bind_param("sss", $full_name, $email, $oauth_uid);
                $insert_stmt->execute();

                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $insert_stmt->insert_id;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['role'] = 'user';
            }

            // توجيه للصفحة الرئيسية بنجاح!
            header("Location: index.php");
            exit();
        }
    }
}

// في حال رفض المستخدم إعطاء الصلاحية أو حدث خطأ
header("Location: login.php?error=google_failed");
exit();
?>