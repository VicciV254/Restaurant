<?php
// customer/menu.php - Menu and Ordering Page
session_name('customer_session');
session_start();

require_once '../dbconnect.php';

// Handle order submission
if (isset($_POST['submit_order'])) {
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $customer_email = mysqli_real_escape_string($conn, $_POST['customer_email']);
    $customer_phone = mysqli_real_escape_string($conn, $_POST['customer_phone']);
    $order_items = json_decode($_POST['order_data'], true);
    $total_amount = floatval($_POST['total_amount']);
    
    $order_date = date('Y-m-d H:i:s');
    $tracking_code = generateTrackingCode($conn);
    
    $query = "INSERT INTO orders (tracking_code, customer_name, customer_email, customer_phone, total_amount, order_date, status) 
              VALUES ('$tracking_code', '$customer_name', '$customer_email', '$customer_phone', '$total_amount', '$order_date', 'pending')";
    
    if (mysqli_query($conn, $query)) {
        $order_id = mysqli_insert_id($conn);
        
        foreach ($order_items as $item) {
            $item_name = mysqli_real_escape_string($conn, $item['name']);
            $item_price = floatval($item['price']);
            $item_quantity = intval($item['quantity']);
            $item_subtotal = $item_price * $item_quantity;
            
            $query_item = "INSERT INTO order_items (order_id, item_name, item_price, quantity, subtotal) 
                           VALUES ('$order_id', '$item_name', '$item_price', '$item_quantity', '$item_subtotal')";
            mysqli_query($conn, $query_item);
        }
        
        $_SESSION['order_success'] = "Order placed successfully! Your tracking code: $tracking_code";
        $_SESSION['last_tracking_code'] = $tracking_code;
        
        session_write_close();
        
        header("Location: order-tracking.php?code=" . $tracking_code);
        exit();
    }
}

// Fetch menu items
$menu_categories = ['pizza', 'burger', 'juice', 'soda', 'side', 'salad'];
$menu_items = [];

foreach ($menu_categories as $category) {
    $query = "SELECT * FROM menu_items WHERE category = '$category' ORDER BY name";
    $result = mysqli_query($conn, $query);
    $menu_items[$category] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $menu_items[$category][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Menu - Joy Eateries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .joy-primary {
            background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%);
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
        
        .btn-joy {
            background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%);
            box-shadow: 0 4px 15px rgba(180, 120, 80, 0.3);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-joy:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(180, 120, 80, 0.4);
        }
        
        .category-title {
            border-left: 5px solid #d4a373;
            padding-left: 15px;
            margin: 30px 0 20px 0;
            font-weight: 600;
            color: #5a3e2b;
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
            height: 180px;
            object-fit: cover;
            width: 100%;
            background-color: #f9f3e8;
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
        
        .availability-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        
        /* Cart Sidebar */
        .cart-sidebar {
            position: fixed;
            bottom: 0;
            right: 0;
            width: 380px;
            max-width: 90vw;
            background: white;
            border-radius: 20px 20px 0 0;
            box-shadow: -5px -5px 25px rgba(0,0,0,0.15);
            padding: 20px;
            z-index: 1000;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .cart-sidebar.open {
            transform: translateY(0);
        }
        
        .cart-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%);
            border-radius: 50%;
            width: 65px;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 1001;
            transition: all 0.3s ease;
        }
        
        .cart-toggle:hover {
            transform: scale(1.05);
        }
        
        .cart-toggle i {
            font-size: 32px;
        }
        
        .badge-cart {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            border-radius: 50%;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: bold;
            min-width: 24px;
            text-align: center;
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #d4a373;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .close-cart {
            cursor: pointer;
            font-size: 24px;
            color: #999;
        }
        
        .close-cart:hover {
            color: #333;
        }
        
        .order-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        
        .total-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #b5835a;
        }
        
        .cart-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }
        
        .cart-overlay.active {
            display: block;
        }
        
        footer {
            background: linear-gradient(135deg, #2c3e2f 0%, #1e2a22 100%);
            margin-top: auto;
            color: #f0e6d8;
        }
        
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
        
        @media (min-width: 993px) {
            .cart-sidebar {
                width: 420px;
            }
            .cart-toggle {
                width: 70px;
                height: 70px;
            }
            .cart-toggle i {
                font-size: 35px;
            }
        }
        
        @media (max-width: 992px) {
            .brand-logo span {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 600px) {
            .cart-sidebar {
                width: 100%;
            }
            .cart-toggle {
                width: 55px;
                height: 55px;
            }
            .cart-toggle i {
                font-size: 28px;
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
            <li><a href="index.php"><i class="material-icons left">home</i>Home</a></li>
            <li class="active"><a href="menu.php"><i class="material-icons left">restaurant_menu</i>Menu</a></li>
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

<!-- Cart Toggle -->
<div class="cart-toggle" onclick="toggleCart()">
    <i class="material-icons white-text">shopping_cart</i>
    <span class="badge-cart white-text" id="cart-count-badge">0</span>
</div>

<!-- Cart Overlay & Sidebar -->
<div class="cart-overlay" id="cart-overlay" onclick="closeCart()"></div>
<div class="cart-sidebar" id="cart-sidebar">
    <div class="cart-header">
        <h5><i class="material-icons left">shopping_cart</i>Your Order</h5>
        <i class="material-icons close-cart" onclick="closeCart()">close</i>
    </div>
    <div id="cart-items">
        <p class="grey-text center">Your cart is empty</p>
    </div>
    <div id="cart-total" style="border-top: 2px solid #eee; margin-top: 15px; padding-top: 15px;">
        <div class="row" style="margin-bottom: 5px;">
            <div class="col s6"><strong>Total:</strong></div>
            <div class="col s6 right-align total-price">KSh 0</div>
        </div>
    </div>
    <button class="btn btn-joy waves-effect waves-light full-width" style="width: 100%; margin-top: 15px;" onclick="openCheckoutModal()" id="checkout-btn" disabled>
        <i class="material-icons left">payment</i>Checkout
    </button>
</div>

<div class="container" style="margin-top: 30px; margin-bottom: 30px;">
    <div class="row">
        <div class="col s12">
            <?php if (isset($_SESSION['order_success'])): ?>
                <div class="card-panel green lighten-4 green-text text-darken-2">
                    <i class="material-icons left">check_circle</i>
                    <?= $_SESSION['order_success']; unset($_SESSION['order_success']); ?>
                </div>
            <?php endif; ?>
            
            <?php
            $category_names = [
                'pizza' => '🍕 Pizzas',
                'burger' => '🍔 Burgers',
                'juice' => '🥤 Juices',
                'soda' => '🥤 Sodas',
                'side' => '🍟 Sides',
                'salad' => '🥗 Salads'
            ];
            
            foreach ($menu_categories as $category):
                if (!empty($menu_items[$category])):
            ?>
                <h5 class="category-title"><?= $category_names[$category] ?></h5>
                <div class="row">
                    <?php foreach ($menu_items[$category] as $item): ?>
                        <div class="col s12 m6 l4">
                            <div class="card food-card">
                                <div class="card-image">
                                    <img src="../img/<?= $item['image'] ?>" alt="<?= $item['name'] ?>" onerror="this.src='../img/placeholder.jpg'">
                                    <?php if ($item['available'] == '0'): ?>
                                        <span class="availability-badge badge red white-text">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-content">
                                    <span class="card-title"><?= $item['name'] ?></span>
                                    <p class="food-price">KSh <?= number_format($item['price'], 0) ?></p>
                                    <?php if ($item['available'] == '1'): ?>
                                        <div class="input-field" style="margin-top: 10px;">
                                            <input type="number" id="qty_<?= $item['id'] ?>" value="1" min="1" max="20" class="center" style="width: 70px; display: inline-block;">
                                            <button class="btn btn-joy waves-effect waves-light" onclick="addToCart(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>', <?= $item['price'] ?>)">
                                                <i class="material-icons left">add_shopping_cart</i>Add
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn grey lighten-2" disabled>Unavailable</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div id="checkout-modal" class="modal">
    <div class="modal-content">
        <h4>Complete Your Order</h4>
        <form id="order-form" method="POST">
            <div class="row">
                <div class="input-field col s12">
                    <i class="material-icons prefix">person</i>
                    <input type="text" id="customer_name" name="customer_name" required>
                    <label for="customer_name">Full Name</label>
                    <span id="name-helper" class="helper-text" style="display:none; color:#e53935; font-size:0.82rem;"></span>
                </div>
                <div class="input-field col s12 m6">
                    <i class="material-icons prefix">email</i>
                    <input type="email" id="customer_email" name="customer_email" required>
                    <label for="customer_email">Email</label>
                    <span id="email-helper" class="helper-text" style="display:none; color:#e53935; font-size:0.82rem;"></span>
                </div>
                <div class="input-field col s12 m6">
                    <i class="material-icons prefix">phone</i>
                    <input type="tel" id="customer_phone" name="customer_phone" placeholder="+254 7XX XXX XXX" required>
                    <label for="customer_phone">Phone Number (+254)</label>
                    <span id="phone-helper" class="helper-text" style="display:none; color:#e53935; font-size:0.82rem;"></span>
                </div>
            </div>
            <input type="hidden" name="order_data" id="order_data">
            <input type="hidden" name="total_amount" id="total_amount">
            <input type="hidden" name="submit_order" value="1">
        </form>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancel</a>
        <button type="submit" form="order-form" class="btn btn-joy waves-effect waves-light">Place Order</button>
    </div>
</div>

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
    let cart = [];
    
    function addToCart(id, name, price) {
        const qtyInput = document.getElementById(`qty_${id}`);
        const quantity = parseInt(qtyInput.value) || 1;
        
        const existingItem = cart.find(item => item.id === id);
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            cart.push({ id, name, price, quantity });
        }
        updateCartDisplay();
        M.toast({ html: `${name} added to cart!`, classes: 'rounded' });
        
        if (window.innerWidth <= 992) {
            openCart();
        }
    }
    
    function updateCartDisplay() {
        const cartContainer = document.getElementById('cart-items');
        const totalContainer = document.getElementById('cart-total');
        const checkoutBtn = document.getElementById('checkout-btn');
        const cartCountBadge = document.getElementById('cart-count-badge');
        
        let total = 0;
        let itemCount = 0;
        
        if (cart.length === 0) {
            cartContainer.innerHTML = '<p class="grey-text center">Your cart is empty</p>';
            totalContainer.innerHTML = `<div class="row" style="margin-bottom: 5px;"><div class="col s6"><strong>Total:</strong></div><div class="col s6 right-align total-price">KSh 0</div></div>`;
            checkoutBtn.disabled = true;
            if(cartCountBadge) cartCountBadge.innerText = '0';
            return;
        }
        
        let itemsHtml = '';
        cart.forEach((item, index) => {
            const subtotal = item.price * item.quantity;
            total += subtotal;
            itemCount += item.quantity;
            itemsHtml += `
                <div class="order-item">
                    <div class="row" style="margin-bottom: 0; align-items: center;">
                        <div class="col s6">${item.name}</div>
                        <div class="col s2 center-align">x${item.quantity}</div>
                        <div class="col s3 right-align">KSh ${subtotal}</div>
                        <div class="col s1 center-align">
                            <i class="material-icons red-text remove-item" style="font-size: 18px; cursor: pointer;" onclick="removeFromCart(${index})">close</i>
                        </div>
                    </div>
                </div>
            `;
        });
        
        cartContainer.innerHTML = itemsHtml;
        totalContainer.innerHTML = `
            <div class="row" style="margin-bottom: 5px;">
                <div class="col s6"><strong>Total:</strong></div>
                <div class="col s6 right-align total-price">KSh ${total}</div>
            </div>
        `;
        checkoutBtn.disabled = false;
        if(cartCountBadge) cartCountBadge.innerText = itemCount;
    }
    
    function removeFromCart(index) {
        cart.splice(index, 1);
        updateCartDisplay();
    }
    
    function openCheckoutModal() {
        if (cart.length === 0) return;
        closeCart();
        const modal = M.Modal.getInstance(document.getElementById('checkout-modal'));
        modal.open();
    }
    
    function toggleCart() {
        const sidebar = document.getElementById('cart-sidebar');
        const overlay = document.getElementById('cart-overlay');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        
        if (sidebar.classList.contains('open')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
    
    function openCart() {
        const sidebar = document.getElementById('cart-sidebar');
        const overlay = document.getElementById('cart-overlay');
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeCart() {
        const sidebar = document.getElementById('cart-sidebar');
        const overlay = document.getElementById('cart-overlay');
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    document.getElementById('order-form')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const nameField  = document.getElementById('customer_name');
        const emailField = document.getElementById('customer_email');
        const phoneField = document.getElementById('customer_phone');

        let valid = true;

        // --- Name: letters and spaces only ---
        const nameVal = nameField.value.trim();
        const nameHelper = document.getElementById('name-helper');
        if (!/^[A-Za-z\s]+$/.test(nameVal) || nameVal === '') {
            nameHelper.textContent = 'Only use letters';
            nameHelper.style.display = 'block';
            nameField.classList.add('invalid');
            valid = false;
        } else {
            nameHelper.style.display = 'none';
            nameField.classList.remove('invalid');
        }

        // --- Email: basic email format ---
        const emailVal = emailField.value.trim();
        const emailHelper = document.getElementById('email-helper');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailVal)) {
            emailHelper.textContent = 'Enter valid email address';
            emailHelper.style.display = 'block';
            emailField.classList.add('invalid');
            valid = false;
        } else {
            emailHelper.style.display = 'none';
            emailField.classList.remove('invalid');
        }

        // --- Phone: valid Kenyan number ---
        // Accepts: 07XXXXXXXX, 01XXXXXXXX, +2547XXXXXXXX, +2541XXXXXXXX, 2547XXXXXXXX
        const phoneVal = phoneField.value.trim();
        const phoneHelper = document.getElementById('phone-helper');
        const phoneRegex = /^(\+?254|0)(7|1)\d{8}$/;
        if (!phoneRegex.test(phoneVal.replace(/\s/g, ''))) {
            phoneHelper.textContent = 'Enter valid phone number';
            phoneHelper.style.display = 'block';
            phoneField.classList.add('invalid');
            valid = false;
        } else {
            phoneHelper.style.display = 'none';
            phoneField.classList.remove('invalid');
        }

        if (!valid) return;

        document.getElementById('order_data').value = JSON.stringify(cart.map(item => ({
            name: item.name,
            price: item.price,
            quantity: item.quantity
        })));
        document.getElementById('total_amount').value = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        this.submit();
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        var modals = document.querySelectorAll('.modal');
        M.Modal.init(modals);
        
        var elems = document.querySelectorAll('.sidenav');
        var instances = M.Sidenav.init(elems);
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCart();
        }
    });
</script>

<?php mysqli_close($conn); ?>
</body>
</html>