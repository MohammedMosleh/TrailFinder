<!DOCTYPE html>

<html dir="rtl" lang="he"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>TrailFinder | מצא את ההרפתקה הבאה שלך</title>
<!-- Bootstrap 5 CSS (RTL) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet"/>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700;900&amp;family=Inter:wght@400;500;700&amp;display=swap" rel="stylesheet"/>
<!-- Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
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

        /* Glass Navbar Custom Style */
        .glass-nav {
            background: rgba(251, 251, 226, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        /* Hero Image & Overlay */
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

        /* Search Bar Customization */
        .search-container {
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

        /* Card Styling */
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

        /* Custom Buttons & Badges */
        .btn-primary-custom {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.6rem 1.5rem;
            font-weight: 700;
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

        /* Footer */
        footer {
            background-color: #1b1d0e;
            color: #fbfbe2;
        }

        /* CTA Section Overlay */
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
    </style>
</head>
<body class="bg-light">
<!-- TopNavBar -->
<nav class="navbar navbar-expand-lg fixed-top glass-nav py-3">
<div class="container-fluid px-lg-5">
<a class="navbar-brand brand-font fw-black fs-3 text-success" href="#" style="color: var(--primary-color) !important;">
                TrailFinder
            </a>
<button class="navbar-toggler" data-bs-target="#navbarNav" data-bs-toggle="collapse" type="button">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse justify-content-between" id="navbarNav">
<ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-4">
<li class="nav-item">
<a class="nav-link active fw-bold border-bottom border-2 border-success text-success" href="#">דף הבית</a>
</li>
<li class="nav-item">
<a class="nav-link fw-bold text-secondary" href="#">מסלולים</a>
</li>
<li class="nav-item">
<a class="nav-link fw-bold text-secondary" href="#">אודות</a>
</li>
</ul>
<div class="d-flex align-items-center gap-3">
<div class="d-none d-xl-flex align-items-center bg-body-secondary rounded-pill px-3 py-1">
<span class="material-symbols-outlined fs-6 text-muted">search</span>
<small class="text-muted ms-2">חיפוש...</small>
</div>
<button class="btn btn-primary-custom px-4 py-2">התחברות</button>
</div>
</div>
</div>
</nav>
<!-- Hero Section -->
<header class="hero-section">
<div class="hero-bg">
<img alt="Mountains" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDkAzSAG7qy5EympjDFhpjjjHm_kSh_CpO2LJ52Bq40dFdx0TGVnRKwfY3IqbwITZ70UOq7YKSq9sHsCbXCsK15IcXspE7PPHIxk73SdrjUjAeAiWZVFfJtY0hFbAl63RP8NHppS_obzz3QuVjmsmmlPuW__TGgc5d0tmNVw1XUpOXuylDgnuea6JJn3WcX_HWpJV2BFGHy0BCpvFD2m6kw_f1AqehlEKeGsIOd7VUo_ren-kZ0DR9_IvmM1V1l6W-iSsy4CCAFmw"/>
<div class="hero-overlay"></div>
</div>
<div class="container position-relative z-1 text-center">
<h1 class="display-2 fw-black text-white mb-4 tracking-tighter shadow-sm">מצא את ההרפתקה הבאה שלך</h1>
<p class="lead text-white opacity-90 mb-5 mx-auto" style="max-width: 600px;">גלה פארקים, חופים ומסלולים המותאמים בדיוק עבורך.</p>
<div class="search-container d-flex flex-column flex-md-row align-items-center">
<div class="search-input-group">
<span class="material-symbols-outlined" style="color: var(--primary-color)">search</span>
<input class="form-control" placeholder="חפש פארק, מסלול, אזור..." type="text"/>
</div>
<button class="btn btn-primary-custom btn-lg w-100 w-md-auto mt-2 mt-md-0 px-5">חפש</button>
</div>
</div>
</header>
<!-- Recommended Destinations Section -->
<section class="py-5" style="background-color: #fbfbe2;">
<div class="container py-lg-5">
<div class="d-flex justify-content-between align-items-end mb-5">
<div>
<span class="text-success fw-bold text-uppercase small tracking-widest d-block mb-2" style="letter-spacing: 2px;">גילוי</span>
<h2 class="fw-black display-5 mb-0">יעדים מומלצים עבורך</h2>
</div>
<a class="text-success fw-bold text-decoration-none d-none d-md-flex align-items-center" href="#">
<span>צפה בכל המסלולים</span>
<span class="material-symbols-outlined me-1">arrow_back</span>
</a>
</div>
<div class="row g-4">
<!-- Card 1 -->
<div class="col-12 col-md-6 col-lg-4">
<div class="card h-100 editorial-shadow">
<div class="card-img-wrapper">
<img alt="Nahal Kziv" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCIV4BQRQR14QHilfW2iw521-x_eZrZhTiycFFQCke575RDoCj7AaBgUDNCYCPb_TfBzCzEba144WGPDQofCqydiSsdpIVLrT9tRi_1WtJ5obEh90S8iAN11IB-vA_c0v3jSbACuLUAykwYwWHPwn7iGf24aPbC_Z3pGW7oDQ_loTWvWwtEbMA4y54lcMicxhhZG-08SIzjJ5DUiV6sYYYbd7ethcpRYt3DHLr-vemyyHQwgcqLfbucIEywV8iKeDHW26EDvaH7-Q"/>
<div class="rating-badge d-flex align-items-center gap-1 shadow-sm">
<span class="text-warning">4.8</span>
<span class="material-symbols-outlined text-warning fs-6" style="font-variation-settings: 'FILL' 1;">star</span>
</div>
</div>
<div class="card-body p-4">
<div class="d-flex align-items-center text-muted small fw-medium mb-2">
<span class="material-symbols-outlined fs-6 me-1">location_on</span>
                                גליל עליון
                            </div>
<h3 class="h4 fw-bold mb-3">נחל כזיב</h3>
<div class="d-flex flex-wrap gap-2 mb-4">
<span class="custom-badge">מים</span>
<span class="custom-badge">מתאים למשפחות</span>
</div>
<button class="btn btn-outline-custom w-100 py-3">לכל הפרטים</button>
</div>
</div>
</div>
<!-- Card 2 -->
<div class="col-12 col-md-6 col-lg-4">
<div class="card h-100 editorial-shadow">
<div class="card-img-wrapper">
<img alt="Mount Carmel" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDEySfZ-gnQC8ibo5QV8niRStmjurHQLJPwm9fiwEixYydfcng4GfyFk6yVdNFsZURulqLyFplhvkNNqUorMNeaKfmAZ0umxD-o37lXZ05twq5ppp5qDnOhINCANoNgcM8ISaRPsF_dyrllzIWX-6PIemhhhNi8GHxAjSiLlXP36qqASzf2l_ycWnuMSm4kSwENolWVLJKh9ojmZ2rqPijGynquC5wIKYfsB-jNZtl-lop0Ir7gnpBpQ_eXsc37G5Usw9CIPEN56w"/>
<div class="rating-badge d-flex align-items-center gap-1 shadow-sm">
<span class="text-warning">4.7</span>
<span class="material-symbols-outlined text-warning fs-6" style="font-variation-settings: 'FILL' 1;">star</span>
</div>
</div>
<div class="card-body p-4">
<div class="d-flex align-items-center text-muted small fw-medium mb-2">
<span class="material-symbols-outlined fs-6 me-1">location_on</span>
                                חיפה והסביבה
                            </div>
<h3 class="h4 fw-bold mb-3">הר הכרמל</h3>
<div class="d-flex flex-wrap gap-2 mb-4">
<span class="custom-badge">חינם</span>
<span class="custom-badge">נוף</span>
</div>
<button class="btn btn-outline-custom w-100 py-3">לכל הפרטים</button>
</div>
</div>
</div>
<!-- Card 3 -->
<div class="col-12 col-md-6 col-lg-4">
<div class="card h-100 editorial-shadow">
<div class="card-img-wrapper">
<img alt="Ramon Crater" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDU0t0jj7v9MO4DtuQ-AzpsK9PXhOsx9DKN6W_Vw2wFIk-w7Chg9BlIW85sopZVxljeaeACkfoRvJPMuoTtg4z1uW_wzx795LcIT0favsro0Vri_EKs2jAAo7i1VLYm3GuuXEAMlgThqrlq4IivdTMrnqK4m4erqzF4UYdrDqdD8Rf5FDP94J4cDtTwAI2QDsZosNwjnfwa246LlkZE9gMHnGh-OO8Qp2EPQwDOay4HObA2ZKqnD3ZZ6LLWyqPANvSOownckjYmAA"/>
<div class="rating-badge d-flex align-items-center gap-1 shadow-sm">
<span class="text-warning">4.9</span>
<span class="material-symbols-outlined text-warning fs-6" style="font-variation-settings: 'FILL' 1;">star</span>
</div>
</div>
<div class="card-body p-4">
<div class="d-flex align-items-center text-muted small fw-medium mb-2">
<span class="material-symbols-outlined fs-6 me-1">location_on</span>
                                נגב
                            </div>
<h3 class="h4 fw-bold mb-3">מכתש רמון</h3>
<div class="d-flex flex-wrap gap-2 mb-4">
<span class="custom-badge">מדבר</span>
<span class="custom-badge">תצפית</span>
</div>
<button class="btn btn-outline-custom w-100 py-3">לכל הפרטים</button>
</div>
</div>
</div>
</div>
</div>
</section>
<!-- CTA Section -->
<section class="container mb-5 py-5">
<div class="cta-banner">
<div class="row align-items-center g-5 position-relative z-1">
<div class="col-lg-7">
<h2 class="display-4 fw-black mb-4 lh-1">הטבע מחכה לך, בוא נצא לדרך.</h2>
<p class="fs-5 opacity-75 mb-5">הצטרף לקהילת המטיילים שלנו וקבל גישה למסלולים סודיים וטיפים מהשטח.</p>
<button class="btn btn-light text-success fw-black fs-5 py-3 px-5 rounded-3 shadow-lg">הצטרף עכשיו חינם</button>
</div>
<div class="col-lg-5">
<div class="cta-img-container">
<img alt="Hiker" class="img-fluid" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD1dyu9-NvnDNYgPxGwE1BdUy9H1qUIEzjZcGwHJbmrf7xHt7vPY-4wlJT4vt92FpeVZAwNC8GVB8sEXcxuaLdz20YbfJE0qrvSbI69AoWplwa0Wfv9JqJ1SyI1SJnsZ6FT-3gardN9-Vm4QUya3CTfVKRiyrTh8Li6w-bXWGZe8C3HZeG1khFjpQ2F9yPesZ_HxPYKc2jt1z88Xe9m1r1uUbeft7ytgQn-4ngcAcfw2R9WYSr4byppz5FhfFWq4ddMKmaq8BIwKg"/>
</div>
</div>
</div>
<!-- Decorative circle -->
<div class="position-absolute bg-white opacity-10 rounded-circle" style="width: 300px; height: 300px; top: -150px; right: -150px;"></div>
</div>
</section>
<!-- Footer -->
<footer class="py-5 mt-auto">
<div class="container text-center">
<div class="h4 fw-bold mb-4">TrailFinder</div>
<div class="d-flex justify-content-center gap-4 mb-4 small text-secondary">
<a class="text-light text-decoration-none border-bottom border-success pb-1" href="#">תנאי שימוש</a>
<a class="text-light opacity-50 text-decoration-none" href="#">מדיניות פרטיות</a>
<a class="text-light opacity-50 text-decoration-none" href="#">צור קשר</a>
</div>
<p class="small opacity-50 mb-4">© 2026 TrailFinder. כל הזכויות שמורות.</p>
<div class="d-flex justify-content-center gap-3 opacity-50">
<span class="material-symbols-outlined">public</span>
<span class="material-symbols-outlined">map</span>
<span class="material-symbols-outlined">landscape</span>
</div>
</div>
</footer>
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body></html>