<?php
// customer/order-tracking.php - Customer order tracking with cancel option
session_name('customer_session');
session_start();

require_once '../dbconnect.php';

$tracking_code = isset($_GET['code']) ? sanitize($_GET['code']) : '';
$tracking_input = isset($_GET['tracking']) ? sanitize($_GET['tracking']) : '';

$order = null;
$order_items = [];
$multiple_orders = [];

if (!empty($tracking_code)) {
    $query = "SELECT * FROM orders WHERE tracking_code = '$tracking_code'";
    $result = mysqli_query($conn, $query);
    $order = mysqli_fetch_assoc($result);
}
else if (!empty($tracking_input)) {
    if (strtoupper(substr($tracking_input, 0, 3)) === 'JOY') {
        $query = "SELECT * FROM orders WHERE tracking_code = '$tracking_input'";
        $result = mysqli_query($conn, $query);
        $order = mysqli_fetch_assoc($result);
    } else {
        $query = "SELECT * FROM orders WHERE customer_phone = '$tracking_input' ORDER BY order_date DESC";
        $result = mysqli_query($conn, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            $multiple_orders[] = $row;
        }
        
        if (count($multiple_orders) == 1) {
            $order = $multiple_orders[0];
            $multiple_orders = [];
        }
    }
}

if ($order && isset($order['id'])) {
    $items_query = "SELECT * FROM order_items WHERE order_id = '{$order['id']}'";
    $items_result = mysqli_query($conn, $items_query);
    while ($row = mysqli_fetch_assoc($items_result)) {
        $order_items[] = $row;
    }
}

// Handle customer cancel request
if (isset($_POST['cancel_customer_order'])) {
    $cancel_order_id    = intval($_POST['order_id']);
    $cancel_track_code  = sanitize($_POST['tracking_code']);

    // Only allow cancellation if status is pending or preparing
    $check_q = "SELECT status FROM orders WHERE id = '$cancel_order_id' AND tracking_code = '$cancel_track_code'";
    $check_r = mysqli_query($conn, $check_q);
    $check_row = mysqli_fetch_assoc($check_r);

    if ($check_row && in_array($check_row['status'], ['pending', 'preparing'])) {
        $upd_q = "UPDATE orders SET status = 'cancelled' WHERE id = '$cancel_order_id' AND tracking_code = '$cancel_track_code'";
        if (mysqli_query($conn, $upd_q)) {
            $_SESSION['cancel_success'] = 'Your order has been successfully cancelled.';
        } else {
            $_SESSION['cancel_error'] = 'Could not cancel the order. Please try again.';
        }
    } else {
        $_SESSION['cancel_error'] = 'This order cannot be cancelled at its current status.';
    }

    session_write_close();
    header("Location: order-tracking.php?code=" . $cancel_track_code);
    exit();
}

$cancel_success = isset($_SESSION['cancel_success']) ? $_SESSION['cancel_success'] : '';
$cancel_error = isset($_SESSION['cancel_error']) ? $_SESSION['cancel_error'] : '';
unset($_SESSION['cancel_success']);
unset($_SESSION['cancel_error']);

$status_info = [
    'pending' => ['color' => 'orange', 'icon' => 'schedule', 'message' => 'Your order has been received and is pending confirmation.', 'step' => 1, 'can_cancel' => true],
    'preparing' => ['color' => 'blue', 'icon' => 'kitchen', 'message' => 'Your order is being prepared by our chefs.', 'step' => 2, 'can_cancel' => true],
    'completed' => ['color' => 'green', 'icon' => 'check_circle', 'message' => 'Your order is ready for pickup/delivery!', 'step' => 3, 'can_cancel' => false],
    'cancelled' => ['color' => 'red', 'icon' => 'cancel', 'message' => 'Your order has been cancelled.', 'step' => 0, 'can_cancel' => false]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking - Joy Eateries</title>
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-cancel { 
            background: #f44336;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-cancel:hover { 
            background: #d32f2f; 
        }
        
        .status-timeline { 
            position: relative; 
            padding: 20px 0; 
        }
        
        .timeline-step { 
            display: flex; 
            align-items: center; 
            margin-bottom: 30px; 
            position: relative; 
        }
        
        .step-icon { 
            width: 50px; 
            height: 50px; 
            border-radius: 50%; 
            background: #e0e0e0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin-right: 20px; 
            z-index: 2; 
            transition: all 0.3s ease; 
        }
        
        .step-icon.active { 
            background: #d4a373; 
            color: white; 
            box-shadow: 0 0 0 5px rgba(212, 163, 115, 0.2); 
        }
        
        .step-icon.completed { 
            background: #4caf50; 
            color: white; 
        }
        
        .step-content { 
            flex: 1; 
        }
        
        .step-title { 
            font-weight: 600; 
            margin-bottom: 5px; 
        }
        
        .step-description { 
            color: #666; 
            font-size: 0.9rem; 
        }
        
        .timeline-line { 
            position: absolute; 
            left: 24px; 
            top: 50px; 
            bottom: 30px; 
            width: 2px; 
            background: #e0e0e0; 
            z-index: 1; 
        }
        
        .order-card { 
            border-radius: 15px; 
            overflow: hidden; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.08); 
        }
        
        .status-badge { 
            padding: 8px 16px; 
            border-radius: 20px; 
            font-weight: 500; 
        }
        
        .tracking-search { 
            background: white; 
            border-radius: 15px; 
            padding: 30px; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.08); 
        }
        
        .tracking-code-badge { 
            background: #2c3e2f; 
            padding: 8px 15px; 
            border-radius: 25px; 
            font-family: monospace; 
            font-size: 1.2rem; 
            letter-spacing: 1px; 
            display: inline-block; 
        }
        
        footer { 
            background: linear-gradient(135deg, #2c3e2f 0%, #1e2a22 100%); 
            margin-top: 50px; 
            color: #f0e6d8; 
        }
        
        .order-card-link { 
            transition: all 0.3s ease; 
            cursor: pointer; 
        }
        
        .order-card-link:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 10px 25px rgba(0,0,0,0.15); 
        }
        
        /* Confirmation modal */
        .confirm-modal {
            max-width: 400px;
            border-radius: 16px;
        }
        
        .confirm-modal .modal-content {
            padding: 24px;
            text-align: center;
        }
        
        .confirm-modal .modal-content i {
            font-size: 56px;
            margin-bottom: 16px;
        }
        
        .confirm-modal .modal-content h5 {
            margin-bottom: 8px;
        }
        
        .confirm-modal .modal-footer {
            padding: 16px 24px;
            text-align: center;
            border-top: 1px solid #eee;
        }
        
        .confirm-btn {
            padding: 8px 24px;
            border-radius: 25px;
            margin: 0 8px;
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
            .brand-logo span {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 600px) {
            .brand-logo span {
                font-size: 0.9rem;
            }
            .brand-logo img {
                height: 35px;
            }
            .tracking-code-badge {
                font-size: 0.9rem;
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
            <li><a href="menu.php"><i class="material-icons left">restaurant_menu</i>Menu</a></li>
            <li class="active"><a href="order-tracking.php"><i class="material-icons left">track_changes</i>Track Order</a></li>
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

<div class="container" style="margin-top: 40px; margin-bottom: 40px;">
    <?php if (count($multiple_orders) > 1): ?>
        <div class="row">
            <div class="col s12 m8 offset-m2">
                <div class="card">
                    <div class="card-content">
                        <h5><i class="material-icons left">list</i>Multiple Orders Found</h5>
                        <p>We found <?= count($multiple_orders) ?> orders associated with this phone number. Please select which order you want to track:</p>
                        <div class="row" style="margin-top: 20px;">
                            <?php foreach ($multiple_orders as $ord): ?>
                            <div class="col s12 m6">
                                <div class="card order-card-link" onclick="window.location.href='order-tracking.php?code=<?= $ord['tracking_code'] ?>'">
                                    <div class="card-content">
                                        <div class="row" style="margin-bottom: 0;">
                                            <div class="col s8">
                                                <strong>Order #<?= $ord['id'] ?></strong><br>
                                                <small><i class="material-icons tiny">event</i> <?= date('d M Y', strtotime($ord['order_date'])) ?></small>
                                            </div>
                                            <div class="col s4 right-align">
                                                <span class="badge <?= $ord['status'] == 'completed' ? 'green' : ($ord['status'] == 'cancelled' ? 'red' : 'orange') ?> white-text">
                                                    <?= ucfirst($ord['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="divider" style="margin: 10px 0;"></div>
                                        <div class="center-align">
                                            <code class="tracking-code-badge" style="background: #f5f5f5; color: #333;"><?= $ord['tracking_code'] ?></code>
                                            <p class="grey-text" style="margin-top: 10px;">Total: KSh <?= number_format($ord['total_amount'], 0) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="center-align" style="margin-top: 20px;">
                            <a href="order-tracking.php" class="btn-flat">← Track Another Order</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($order && $order['id']): ?>
        <div class="row">
            <div class="col s12 m8 offset-m2">
                <?php if ($cancel_success): ?>
                    <div class="card-panel green lighten-4 green-text">
                        <i class="material-icons left">check_circle</i>
                        <?= $cancel_success ?>
                    </div>
                <?php endif; ?>
                <?php if ($cancel_error): ?>
                    <div class="card-panel red lighten-4 red-text">
                        <i class="material-icons left">error</i>
                        <?= $cancel_error ?>
                    </div>
                <?php endif; ?>
                
                <div class="card order-card">
                    <div class="card-content">
                        <div class="row" style="margin-bottom: 0;">
                            <div class="col s12 m6">
                                <h5>Order #<?= $order['id'] ?></h5>
                                <p><i class="material-icons tiny">event</i> <?= date('F d, Y h:i A', strtotime($order['order_date'])) ?></p>
                            </div>
                            <div class="col s12 m6 right-align">
                                <div class="tracking-code-badge" style="background: #d4a373; color: white; cursor: pointer;" onclick="copyTrackingCode()" title="Click to copy tracking code">
                                    <i class="material-icons tiny">code</i> <?= $order['tracking_code'] ?> <i class="material-icons tiny" style="margin-left: 5px;">content_copy</i>
                                </div>
                                <br>
                                <span class="status-badge <?= $order['status'] == 'completed' ? 'green' : ($order['status'] == 'cancelled' ? 'red' : 'orange') ?> white-text" style="margin-top: 10px; display: inline-block;">
                                    <?= strtoupper($order['status']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="divider" style="margin: 20px 0;"></div>
                        <div class="row">
                            <div class="col s12 m4">
                                <strong><i class="material-icons tiny">person</i> Customer</strong>
                                <p><?= htmlspecialchars($order['customer_name']) ?></p>
                            </div>
                            <div class="col s12 m4">
                                <strong><i class="material-icons tiny">phone</i> Phone</strong>
                                <p><?= htmlspecialchars($order['customer_phone']) ?></p>
                            </div>
                            <div class="col s12 m4">
                                <strong><i class="material-icons tiny">email</i> Email</strong>
                                <p><?= htmlspecialchars($order['customer_email']) ?></p>
                            </div>
                        </div>
                        <div class="divider" style="margin: 20px 0;"></div>
                        <h6><i class="material-icons tiny">shopping_cart</i> Order Items</h6>
                        <table class="striped">
                            <thead>
                                <tr><th>Item</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>KSh <?= number_format($item['item_price'], 0) ?></td>
                                    <td>KSh <?= number_format($item['subtotal'], 0) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight: bold;">
                                    <td colspan="3" class="right-align">Total:</td>
                                    <td>KSh <?= number_format($order['total_amount'], 0) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="divider" style="margin: 20px 0;"></div>
                        <h6><i class="material-icons tiny">timeline</i> Order Status</h6>
                        <div class="status-timeline">
                            <div class="timeline-line"></div>
                            <?php
                            $steps = [
                                'pending' => ['title' => 'Order Received', 'description' => 'Your order has been received and is waiting for confirmation'],
                                'preparing' => ['title' => 'Preparing', 'description' => 'Our chefs are preparing your delicious meal'],
                                'completed' => ['title' => 'Ready', 'description' => 'Your order is ready for pickup or delivery']
                            ];
                            $current_step = $status_info[$order['status']]['step'] ?? 0;
                            $step_num = 1;
                            foreach ($steps as $status_key => $step):
                                $is_completed = ($current_step >= $step_num);
                                $is_active = ($order['status'] == $status_key);
                            ?>
                            <div class="timeline-step">
                                <div class="step-icon <?= $is_completed ? 'completed' : ($is_active ? 'active' : '') ?>">
                                    <i class="material-icons"><?= $status_key == 'pending' ? 'schedule' : ($status_key == 'preparing' ? 'kitchen' : 'check_circle') ?></i>
                                </div>
                                <div class="step-content">
                                    <div class="step-title"><?= $step['title'] ?></div>
                                    <div class="step-description"><?= $step['description'] ?></div>
                                    <?php if ($is_active): ?>
                                        <span class="badge orange white-text" style="margin-top: 5px;">Current Status</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php $step_num++; endforeach; ?>
                            <?php if ($order['status'] == 'cancelled'): ?>
                            <div class="timeline-step">
                                <div class="step-icon completed red"><i class="material-icons">cancel</i></div>
                                <div class="step-content">
                                    <div class="step-title">Order Cancelled</div>
                                    <div class="step-description">This order has been cancelled.</div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-panel <?= $order['status'] == 'completed' ? 'green lighten-4' : ($order['status'] == 'cancelled' ? 'red lighten-4' : 'orange lighten-4') ?>">
                            <i class="material-icons left">info_outline</i>
                            <?= $status_info[$order['status']]['message'] ?>
                        </div>
                        
                        <?php if ($status_info[$order['status']]['can_cancel']): ?>
                            <div class="center-align" style="margin-top: 15px;">
                                <button class="btn btn-cancel waves-effect waves-light" onclick="openCancelModal()">
                                    <i class="material-icons left">cancel</i>Cancel Order
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="center-align" style="margin-top: 15px;">
                            <a href="order-tracking.php" class="btn-flat">← Track Another Order</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cancel Confirmation Modal -->
        <div id="cancel-modal" class="modal confirm-modal">
            <div class="modal-content">
                <i class="material-icons" style="color: #f44336;">warning</i>
                <h5>Cancel Order?</h5>
                <p>Are you sure you want to cancel this order? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="tracking_code" value="<?= $order['tracking_code'] ?>">
                    <button type="button" class="modal-close waves-effect waves-red btn-flat confirm-btn">No, Go Back</button>
                    <button type="submit" name="cancel_customer_order" class="btn btn-cancel waves-effect waves-light confirm-btn">Yes, Cancel Order</button>
                </form>
            </div>
        </div>
        
    <?php else: ?>
        <div class="row">
            <div class="col s12 m6 offset-m3">
                <div class="tracking-search">
                    <div class="center-align">
                        <i class="material-icons" style="font-size: 64px; color: #d4a373;">track_changes</i>
                        <h5>Track Your Order</h5>
                        <p>Enter your tracking code (e.g., JOY00001) or phone number</p>
                    </div>
                    <?php if (!empty($tracking_input) && !$order && empty($multiple_orders)): ?>
                        <div class="card-panel red lighten-4 red-text">
                            <i class="material-icons left">error</i>
                            No order found with the provided information.
                        </div>
                    <?php endif; ?>
                    <form method="GET" action="order-tracking.php">
                        <div class="input-field">
                            <i class="material-icons prefix">receipt</i>
                            <input type="text" name="tracking" id="tracking" required>
                            <label for="tracking">Tracking Code or Phone Number</label>
                        </div>
                        <button type="submit" class="btn btn-joy waves-effect waves-light full-width">
                            <i class="material-icons left">search</i>Track Order
                        </button>
                    </form>
                    <div class="center-align" style="margin-top: 20px;">
                        <a href="index.php" class="btn-flat">← Back to Menu</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
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
    function openCancelModal() {
        const modal = M.Modal.getInstance(document.getElementById('cancel-modal'));
        modal.open();
    }
    
    function copyTrackingCode() {
        var trackingCode = '<?= $order['tracking_code'] ?>';
        navigator.clipboard.writeText(trackingCode).then(function() {
            M.toast({html: 'Tracking code copied to clipboard!', classes: 'rounded green'});
        }, function() {
            M.toast({html: 'Failed to copy tracking code.', classes: 'rounded red'});
        });
    }
    
    <?php if ($order && $order['id']): ?>
    setTimeout(function() { location.reload(); }, 30000);
    <?php endif; ?>
    
    document.addEventListener('DOMContentLoaded', function() {
        var modals = document.querySelectorAll('.modal');
        M.Modal.init(modals);
        
        var elems = document.querySelectorAll('.sidenav');
        var instances = M.Sidenav.init(elems);
    });
</script>

<?php mysqli_close($conn); ?>
</body>
</html>