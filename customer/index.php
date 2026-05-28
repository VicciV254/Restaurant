<?php
// customer/index.php - New Home Page with Hero Video
session_name('customer_session');
session_start();

require_once __DIR__ . '/../dbconnect.php';
if (!isset($conn) || !$conn) {
    die('Database connection failed.');
}

// Fetch menu items for preview (optional)
$menu_categories = ['pizza', 'burger', 'juice', 'soda', 'side', 'salad'];
$featured_items = [];

foreach ($menu_categories as $category) {
    $query = "SELECT * FROM menu_items WHERE category = '$category' AND available = '1' LIMIT 2";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $featured_items[] = $row;
    }
}
shuffle($featured_items);
$featured_items = array_slice($featured_items, 0, 6);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Joy Eateries - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            min-height: 100vh;
        }
        
        /* Navbar Styles */
        .joy-primary {
            background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0 20px;
        }
        
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        .brand-logo img {
            height: 45px;
            margin-top: 8px;
            vertical-align: middle;
        }
        
        .brand-logo span {
            font-size: 1.2rem;
            white-space: nowrap;
        }
        
        /* Hero Section with Video */
        .hero-section {
            position: relative;
            height: 85vh;
            min-height: 550px;
            overflow: hidden;
        }
        
        .hero-video-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }
        
        .hero-video {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: translate(-50%, -50%);
        }
        
        .hero-video-wrapper iframe {
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2;
        }
        
        .hero-content {
            position: relative;
            z-index: 3;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 20px;
        }
        
        .hero-logo {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            animation: fadeInUp 1s ease;
        }
        
        .hero-logo img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease 0.2s both;
        }
        
        .hero-content p {
            font-size: 1.3rem;
            font-style: italic;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease 0.4s both;
        }
        
        .hero-btn {
            margin-top: 30px;
            animation: fadeInUp 1s ease 0.6s both;
        }
        
        .hero-btn .btn {
            background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%);
            border-radius: 50px;
            padding: 12px 35px;
            font-size: 1.1rem;
            text-transform: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        /* Story & Mission Section - Side by Side */
        .story-mission-section {
            padding: 60px 0;
            background: #f9f6f0;
        }
        
        .info-card {
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            min-height: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-10px);
        }
        
        .info-video-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }
        
        .info-video {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: translate(-50%, -50%);
        }
        
        .info-video-wrapper iframe {
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .info-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2;
        }
        
        .info-content {
            position: relative;
            z-index: 3;
            padding: 40px 30px;
            color: white;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .info-content h3 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 20px;
            border-left: 4px solid #d4a373;
            padding-left: 15px;
        }
        
        .info-content p {
            font-size: 1rem;
            line-height: 1.7;
            opacity: 0.95;
        }
        
        /* Featured Items Section */
        .featured-section {
            padding: 60px 0;
            background: white;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            color: #5a3e2b;
            font-weight: 600;
        }
        
        .section-title .divider {
            width: 80px;
            height: 3px;
            background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%);
            margin: 15px auto 0;
            border-radius: 5px;
        }
        
        .food-card {
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .food-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .food-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .food-card .card-content {
            padding: 15px;
        }
        
        .food-card .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .food-price {
            color: #b5835a;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .view-menu-btn {
            background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%);
            border-radius: 50px;
            padding: 12px 30px;
            text-transform: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        /* Footer */
        footer {
            background: linear-gradient(135deg, #2c3e2f 0%, #1e2a22 100%);
            color: #f0e6d8;
            padding: 40px 0 20px;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Mobile Navigation */
        .sidenav {
            background: linear-gradient(135deg, #2c3e2f 0%, #1e2a22 100%);
        }
        
        .sidenav li a {
            color: white;
        }
        
        .sidenav .logo-container {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .sidenav .logo-container img {
            height: 60px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-content p {
                font-size: 1.1rem;
            }
            
            .hero-logo {
                width: 90px;
                height: 90px;
            }
            
            .hero-logo img {
                width: 75px;
                height: 75px;
            }
            
            .info-card {
                margin-bottom: 30px;
            }
            
            .info-content h3 {
                font-size: 1.6rem;
            }
            
            .brand-logo span {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 600px) {
            .hero-section {
                height: 70vh;
                min-height: 450px;
            }
            
            .hero-content h1 {
                font-size: 1.8rem;
            }
            
            .hero-content p {
                font-size: 0.9rem;
            }
            
            .hero-logo {
                width: 70px;
                height: 70px;
            }
            
            .hero-logo img {
                width: 55px;
                height: 55px;
            }
            
            .info-content {
                padding: 30px 20px;
            }
            
            .info-content h3 {
                font-size: 1.4rem;
            }
            
            .info-content p {
                font-size: 0.85rem;
            }
            
            .section-title h2 {
                font-size: 1.8rem;
            }
            
            .brand-logo span {
                font-size: 0.9rem;
            }
            
            .brand-logo img {
                height: 35px;
            }
        }
    </style>
</head>
<body>

<!-- Desktop Navigation -->
<nav class="joy-primary">
    <div class="nav-wrapper">
        <a href="index.php" class="brand-logo left">
            <img src="../img/logo.png" alt="Joy Eateries">
            <span>Joy Eateries</span>
        </a>
        <ul class="right hide-on-med-and-down">
            <li class="active"><a href="index.php"><i class="material-icons left">home</i>Home</a></li>
            <li><a href="menu.php"><i class="material-icons left">restaurant_menu</i>Menu</a></li>
            <li><a href="order-tracking.php"><i class="material-icons left">track_changes</i>Track Order</a></li>
        </ul>
        <a href="#" data-target="mobile-nav" class="sidenav-trigger right hide-on-large-only">
            <i class="material-icons white-text">menu</i>
        </a>
    </div>
</nav>

<!-- Mobile Navigation -->
<ul class="sidenav" id="mobile-nav">
    <li class="logo-container">
        <img src="../img/logo.png" alt="Joy Eateries">
        <h5 style="color: white; margin-top: 10px;">Joy Eateries</h5>
        <p style="color: #d4a373; font-size: 0.8rem;">Selling a Dining Experience</p>
    </li>
    <li><a href="index.php"><i class="material-icons left">home</i>Home</a></li>
    <li><a href="menu.php"><i class="material-icons left">restaurant_menu</i>Menu</a></li>
    <li><a href="order-tracking.php"><i class="material-icons left">track_changes</i>Track Order</a></li>
</ul>

<!-- Hero Section with Video -->
<section class="hero-section">
    <div class="hero-video-wrapper">
        <iframe class="hero-video"
            src="https://www.youtube.com/embed/H4msDNMapq0?autoplay=1&mute=1&loop=1&playlist=H4msDNMapq0&controls=0&modestbranding=1&rel=0&showinfo=0&iv_load_policy=3"
            title="Joy Eateries Home Video"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen>
        </iframe>
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <div class="hero-logo">
            <img src="../img/logo.png" alt="Joy Eateries">
        </div>
        <h1>Joy Eateries</h1>
        <p>Selling a Dining Experience</p>
        <div class="hero-btn">
            <a href="menu.php" class="btn waves-effect waves-light">
                Explore Our Menu <i class="material-icons right">arrow_forward</i>
            </a>
        </div>
    </div>
</section>

<!-- Our Story & Our Mission - Side by Side -->
<section class="story-mission-section">
    <div class="container">
        <div class="row">
            <!-- Our Story Box -->
            <div class="col s12 m6">
                <div class="info-card">
                    <div class="info-video-wrapper">
                        <iframe class="info-video"
                            src="https://www.youtube.com/embed/yNhX7lJeHEs?autoplay=1&mute=1&loop=1&playlist=yNhX7lJeHEs&controls=0&modestbranding=1&rel=0&showinfo=0&iv_load_policy=3"
                            title="Joy Eateries Story Video"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen>
                        </iframe>
                    </div>
                    <div class="info-overlay"></div>
                    <div class="info-content">
                        <h3>Our Story</h3>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                        <p style="margin-top: 15px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.</p>
                    </div>
                </div>
            </div>
            
            <!-- Our Mission Box -->
            <div class="col s12 m6">
                <div class="info-card">
                    <div class="info-video-wrapper">
                        <iframe class="info-video"
                            src="https://www.youtube.com/embed/-s2TfH12yDw?autoplay=1&mute=1&loop=1&playlist=-s2TfH12yDw&controls=0&modestbranding=1&rel=0&showinfo=0&iv_load_policy=3"
                            title="Joy Eateries Mission Video"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen>
                        </iframe>
                    </div>
                    <div class="info-overlay"></div>
                    <div class="info-content">
                        <h3>Our Mission</h3>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                        <p style="margin-top: 15px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Menu Items Section -->
<section class="featured-section">
    <div class="container">
        <div class="section-title">
            <h2>Featured Delights</h2>
            <div class="divider"></div>
            <p class="grey-text" style="margin-top: 10px;">Discover our most popular dishes</p>
        </div>
        
        <div class="row">
            <?php foreach ($featured_items as $item): ?>
                <div class="col s12 m6 l4">
                    <div class="card food-card">
                        <div class="card-image">
                            <img src="../img/<?= $item['image'] ?>" alt="<?= $item['name'] ?>" onerror="this.src='../img/placeholder.jpg'">
                        </div>
                        <div class="card-content">
                            <span class="card-title"><?= htmlspecialchars($item['name']) ?></span>
                            <p class="food-price">KSh <?= number_format($item['price'], 0) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="center-align" style="margin-top: 30px;">
            <a href="menu.php" class="btn view-menu-btn waves-effect waves-light">
                View Full Menu <i class="material-icons right">restaurant_menu</i>
            </a>
        </div>
    </div>
</section>

<footer class="page-footer">
    <div class="container">
        <div class="row">
            <div class="col s12 m4">
                <img src="../img/logo.png" alt="Joy Eateries" style="height: 60px;">
                <p style="margin-top: 10px;">Selling a Dining Experience</p>
            </div>
            <div class="col s12 m4">
                <h6>Contact Us</h6>
                <p><i class="material-icons tiny">phone</i> +254 726 492 303<br>
                <i class="material-icons tiny">email</i> info@joyeateries.com<br>
                <i class="material-icons tiny">location_on</i> Mombasa, Kenya</p>
            </div>
            <div class="col s12 m4">
                <h6>Hours</h6>
                <p>Mon - Sun: 10:00 AM - 10:00 PM</p>
            </div>
        </div>
    </div>
    <div class="footer-copyright">
        <div class="container center">
            © 2026 Joy Eateries - All Rights Reserved
        </div>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize mobile sidenav
        var elems = document.querySelectorAll('.sidenav');
        var instances = M.Sidenav.init(elems);
    });
</script>

<?php mysqli_close($conn); ?>
</body>
</html>