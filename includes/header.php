<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/db_connect.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo isset($page_title) ? $page_title : 'TrailFinder'; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700;900&family=Inter:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <style>
        :root {
            --primary-color: #006c0c;
            --primary-container: #1c871e;
            --surface-bright: #fbfbe2;
            --on-surface: #1b1d0e;
            --secondary: #446464;
            --secondary-container: #c6e9e9;
            --on-secondary-container: #4a6a6a;
            --surface-container-lowest: #ffffff;
            --surface-variant: #e4e4cc;
            --outline-variant: #becab7;
        }

        body {
            background-color: #fbfbe2;
            color: var(--on-surface);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        h1, h2, h3, .brand-font, .nav-link {
            font-family: 'Be Vietnam Pro', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }

        .glass-nav {
            background: rgba(251, 251, 226, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .hero-section {
            position: relative;
            min-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding-top: 80px;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
        }

        .hero-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, transparent 60%, #fbfbe2 100%);
        }

        .search-container-hero {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 0.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .search-input-group {
            display: flex;
            align-items: center;
            flex-grow: 1;
            padding: 0 1rem;
        }

        .search-input-group input {
            border: none;
            background: transparent;
            box-shadow: none !important;
            font-size: 1.1rem;
        }

        .editorial-shadow {
            box-shadow: 0 20px 40px rgba(47, 79, 79, 0.06);
            border: none;
            transition: transform 0.3s ease;
        }

        .editorial-shadow:hover {
            transform: translateY(-8px);
        }

        .card-img-wrapper {
            position: relative;
            height: 250px;
            overflow: hidden;
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
        }

        .card-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .editorial-shadow:hover .card-img-wrapper img {
            transform: scale(1.1);
        }

        .rating-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(255, 255, 255, 0.9);
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.6rem 1.5rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary-custom:hover {
            background-color: var(--primary-container);
            color: white;
        }

        .btn-outline-custom {
            border: 1px solid var(--outline-variant);
            color: var(--primary-color);
            border-radius: 0.75rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
        }

        .btn-outline-custom:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .custom-badge {
            background-color: var(--secondary-container);
            color: var(--on-secondary-container);
            border-radius: 50px;
            font-size: 0.75rem;
            padding: 0.35rem 0.8rem;
            font-weight: 700;
            text-decoration: none;
        }

        footer {
            background-color: #1b1d0e;
            color: #fbfbe2;
        }

        .cta-banner {
            background-color: var(--primary-container);
            border-radius: 2rem;
            padding: 4rem 3rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .cta-img-container {
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            transform: rotate(2deg);
        }

        .auth-card {
            max-width: 480px;
            margin: 120px auto 60px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            border-radius: 1.5rem;
            padding: 3rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.12);
        }

        .auth-card .form-control {
            border-radius: 0.75rem;
            padding: 0.8rem 1rem;
            border: 1px solid var(--outline-variant);
            font-size: 1rem;
        }

        .auth-card .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 108, 12, 0.15);
        }

        .auth-card label {
            font-weight: 600;
            color: var(--on-surface);
            margin-bottom: 0.4rem;
        }

        .page-header {
            padding: 120px 0 40px;
            text-align: center;
        }

        .filter-sidebar-card {
            background: var(--surface-container-lowest);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 20px 40px rgba(47, 79, 79, 0.06);
            position: sticky;
            top: 100px;
            border: none;
        }

        .filter-sidebar-card .form-control,
        .filter-sidebar-card .form-select {
            border-radius: 0.75rem;
            border: 1px solid var(--outline-variant);
            padding: 0.6rem 1rem;
        }

        .filter-sidebar-card .form-control:focus,
        .filter-sidebar-card .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 108, 12, 0.15);
        }

        .filter-group {
            margin-bottom: 1.5rem;
        }

        .filter-group h5 {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 0.6rem;
            color: var(--on-surface);
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            color: #555;
            font-size: 0.9rem;
        }

        .checkbox-label input[type="checkbox"] {
            accent-color: var(--primary-color);
            width: 16px;
            height: 16px;
        }

        .detail-hero {
            position: relative;
            min-height: 50vh;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            overflow: hidden;
            padding-top: 80px;
            padding-bottom: 3rem;
        }

        .detail-hero .hero-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .detail-hero .hero-overlay {
            background: linear-gradient(to bottom, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.7) 100%);
        }

        .info-item {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--surface-variant);
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-item strong {
            color: var(--on-surface);
            display: block;
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }

        .info-item span {
            color: #555;
        }

        .alert-custom-danger {
            background-color: #fce4e4;
            color: #c0392b;
            border: 1px solid #f5c6cb;
            border-radius: 0.75rem;
            padding: 0.8rem 1.2rem;
            margin-bottom: 1rem;
        }

        .alert-custom-success {
            background-color: #e8f5e9;
            color: var(--primary-color);
            border: 1px solid #c8e6c9;
            border-radius: 0.75rem;
            padding: 0.8rem 1.2rem;
            margin-bottom: 1rem;
        }

        .result-card {
            display: flex;
            background: var(--surface-container-lowest);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(47, 79, 79, 0.06);
            margin-bottom: 1.5rem;
            border: none;
            transition: transform 0.3s ease;
        }

        .result-card:hover {
            transform: translateY(-5px);
        }

        .result-card-img {
            width: 280px;
            min-height: 220px;
            background-size: cover;
            background-position: center;
            background-color: #ccc;
            flex-shrink: 0;
        }

        .result-card-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 768px) {
            .result-card {
                flex-direction: column;
            }

            .result-card-img {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg fixed-top glass-nav py-3">
    <div class="container-fluid px-lg-5">

        <a class="navbar-brand brand-font fw-black fs-3" href="index.php" style="color: var(--primary-color) !important;">
            TrailFinder
        </a>

        <button class="navbar-toggler" data-bs-target="#navbarNav" data-bs-toggle="collapse" type="button">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-between" id="navbarNav">

            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-4">

                <li class="nav-item">
                    <a class="nav-link fw-bold <?php echo ($current_page == 'index.php') ? 'border-bottom border-2 border-success text-success' : 'text-secondary'; ?>" href="index.php">
                        דף הבית
                    </a>
                </li>

                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>

                    <li class="nav-item">
                        <a class="nav-link fw-bold <?php echo ($current_page == 'admin_dashboard.php') ? 'border-bottom border-2 border-success text-success' : 'text-secondary'; ?>" href="admin_dashboard.php">
                            לוח בקרה
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link fw-bold <?php echo ($current_page == 'add_trail.php') ? 'border-bottom border-2 border-success text-success' : 'text-secondary'; ?>" href="add_trail.php">
                            הוסף מסלול
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link fw-bold <?php echo ($current_page == 'search.php') ? 'border-bottom border-2 border-success text-success' : 'text-secondary'; ?>" href="search.php">
                            צפייה במסלולים
                        </a>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link fw-bold <?php echo ($current_page == 'search.php') ? 'border-bottom border-2 border-success text-success' : 'text-secondary'; ?>" href="search.php">
                            מסלולים
                        </a>
                    </li>

                  <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>

    <li class="nav-item">
        <a class="nav-link fw-bold <?php echo in_array($current_page, ['profile.php', 'favorites.php', 'history.php']) ? 'border-bottom border-2 border-success text-success' : 'text-secondary'; ?>" href="profile.php">
            פרופיל
        </a>
    </li>

<?php endif; ?>
                <?php endif; ?>

            </ul>

            <div class="d-flex align-items-center gap-3">

                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>

                    <span class="fw-bold text-secondary">
                        <span class="material-symbols-outlined fs-6 me-1">person</span>
                        <?php echo htmlspecialchars($_SESSION["full_name"]); ?>

                        <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                            <span class="badge bg-success ms-2">Admin</span>
                        <?php endif; ?>
                    </span>

                    <a href="logout.php" class="btn btn-outline-custom px-4 py-2">
                        התנתקות
                    </a>

                <?php else: ?>

                    <a href="login.php" class="btn btn-primary-custom px-4 py-2">
                        התחברות
                    </a>

                    <a href="register.php" class="btn btn-outline-custom px-4 py-2">
                        הרשמה
                    </a>

                <?php endif; ?>

            </div>

        </div>
    </div>
</nav>