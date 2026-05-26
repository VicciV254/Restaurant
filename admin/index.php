<?php
// admin/index.php - Admin dashboard with mPDF reports
session_name('admin_session');
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../dbconnect.php';

// Ensure database connection is available
if (!isset($conn)) {
    die('Database connection not established.');
}

// Composer autoload for mPDF
require_once __DIR__ . '/vendor/autoload.php';

// Handle admin actions
if (isset($_POST['update_availability'])) {
    $item_id = mysqli_real_escape_string($conn, $_POST['item_id']);
    $available = mysqli_real_escape_string($conn, $_POST['available']);
    mysqli_query($conn, "UPDATE menu_items SET available = '$available' WHERE id = '$item_id'");
    header("Location: index.php?tab=menu");
    exit();
}

if (isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $check_query = "SELECT status FROM orders WHERE id = '$order_id'";
    $check_result = mysqli_query($conn, $check_query);
    $current = mysqli_fetch_assoc($check_result);
    
    if ($current['status'] !== 'completed' && $current['status'] !== 'cancelled') {
        mysqli_query($conn, "UPDATE orders SET status = '$status' WHERE id = '$order_id'");
    }
    
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'status' => $status]);
        exit();
    } else {
        header("Location: index.php?tab=orders");
        exit();
    }
}

// Handle PDF download for All Orders Report using mPDF
if (isset($_GET['pdf_all_orders'])) {
    // Fetch all completed and cancelled orders directly from database
    $query = "SELECT * FROM orders WHERE status IN ('completed', 'cancelled') ORDER BY order_date DESC";
    $result = mysqli_query($conn, $query);
    $all_orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $all_orders[] = $row;
    }
    
    // Calculate total for completed orders only
    $query_completed = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'";
    $result_completed = mysqli_query($conn, $query_completed);
    $completed_total = mysqli_fetch_assoc($result_completed);
    $grand_total = $completed_total['total'] ?? 0;
    
    // Build HTML for PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>All Orders Report</title>
        <style>
            body {
                font-family: dejavusans, sans-serif;
                margin: 20px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            .logo {
                text-align: center;
                margin-bottom: 10px;
            }
            h1 {
                color: #b5835a;
                margin: 0;
            }
            .subtitle {
                color: #666;
                font-size: 12px;
                margin-top: 5px;
            }
            .date {
                color: #999;
                font-size: 11px;
                margin-top: 5px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            thead {
                display: table-header-group;
            }
            th {
                background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%);
                color: white;
                padding: 12px;
                text-align: left;
                font-weight: 600;
            }
            td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }
            tr {
                page-break-inside: avoid;
            }
            .text-right {
                text-align: right;
            }
            .footer {
                text-align: center;
                font-size: 10px;
                color: #999;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #eee;
            }
            .status-badge {
                display: inline-block;
                padding: 3px 10px;
                border-radius: 15px;
                font-size: 11px;
                font-weight: 500;
            }
            .status-completed {
                background: #e8f5e9;
                color: #4caf50;
            }
            .status-cancelled {
                background: #ffebee;
                color: #f44336;
            }
        </style>
    </head>
    <body>
        <div class="logo">
            <img src="../img/logo.png" alt="Joy Eateries" style="height: 60px;">
        </div>
        <div class="header">
            <h1>All Orders Report</h1>
            <div class="subtitle">Completed and Cancelled Orders</div>
            <div class="date">Generated on: ' . date('F d, Y h:i A') . '</div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Tracking Code</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th class="text-right">Total (KSh)</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($all_orders as $order) {
        $status_class = $order['status'] == 'completed' ? 'status-completed' : 'status-cancelled';
        $status_text = ucfirst($order['status']);
        $html .= '
                <tr>
                    <td>#' . $order['id'] . '</td>
                    <td>' . htmlspecialchars($order['tracking_code']) . '</td>
                    <td>' . htmlspecialchars($order['customer_name']) . '</td>
                    <td>' . htmlspecialchars($order['customer_phone']) . '</td>
                    <td>' . date('d M Y', strtotime($order['order_date'])) . '</td>
                    <td><span class="status-badge ' . $status_class . '">' . $status_text . '</span></td>
                    <td class="text-right">' . number_format($order['total_amount'], 0) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="footer">
            <p>Joy Eateries - Mombasa, Kenya | +254 726 492 303</p>
            <p>© ' . date('Y') . ' Joy Eateries - All Rights Reserved</p>
        </div>
    </body>
    </html>';
    
    // Create PDF using mPDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-L', // Landscape
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15,
        'margin_header' => 5,
        'margin_footer' => 5
    ]);
    
    $mpdf->SetTitle('All Orders Report - Joy Eateries');
    $mpdf->SetAuthor('Joy Eateries');
    $mpdf->WriteHTML($html);
    $mpdf->Output('all_orders_report_' . date('Y-m-d') . '.pdf', 'D');
    exit();
}

// Handle PDF download for Completed Orders Report using mPDF
if (isset($_GET['pdf_completed_orders'])) {
    // Fetch completed orders directly from database
    $query = "SELECT * FROM orders WHERE status = 'completed' ORDER BY order_date DESC";
    $result = mysqli_query($conn, $query);
    $completed_orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $completed_orders[] = $row;
    }
    
    // Calculate grand total
    $query_total = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'";
    $result_total = mysqli_query($conn, $query_total);
    $total_row = mysqli_fetch_assoc($result_total);
    $grand_total = $total_row['total'] ?? 0;
    
    // Build HTML for PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Completed Orders Report</title>
        <style>
            body {
                font-family: dejavusans, sans-serif;
                margin: 20px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            .logo {
                text-align: center;
                margin-bottom: 10px;
            }
            h1 {
                color: #b5835a;
                margin: 0;
            }
            .subtitle {
                color: #666;
                font-size: 12px;
                margin-top: 5px;
            }
            .date {
                color: #999;
                font-size: 11px;
                margin-top: 5px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            thead {
                display: table-header-group;
            }
            th {
                background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%);
                color: white;
                padding: 12px;
                text-align: left;
                font-weight: 600;
            }
            td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }
            tr {
                page-break-inside: avoid;
            }
            .text-right {
                text-align: right;
            }
            .footer {
                text-align: center;
                font-size: 10px;
                color: #999;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #eee;
            }
            .grand-total {
                background: #f5f5f5;
                font-weight: bold;
            }
            .grand-total td {
                padding: 12px;
            }
        </style>
    </head>
    <body>
        <div class="logo">
            <img src="../img/logo.png" alt="Joy Eateries" style="height: 60px;">
        </div>
        <div class="header">
            <h1>Completed Orders Report</h1>
            <div class="subtitle">All completed orders with total amounts</div>
            <div class="date">Generated on: ' . date('F d, Y h:i A') . '</div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Tracking Code</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Order Date</th>
                    <th class="text-right">Total (KSh)</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($completed_orders as $order) {
        $html .= '
                <tr>
                    <td>#' . $order['id'] . '</td>
                    <td>' . htmlspecialchars($order['tracking_code']) . '</td>
                    <td>' . htmlspecialchars($order['customer_name']) . '</td>
                    <td>' . htmlspecialchars($order['customer_phone']) . '</td>
                    <td>' . date('d M Y', strtotime($order['order_date'])) . '</td>
                    <td class="text-right">' . number_format($order['total_amount'], 0) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
            <tfoot>
                <tr class="grand-total">
                    <td colspan="5" class="text-right"><strong>GRAND TOTAL:</strong></td>
                    <td class="text-right"><strong>KSh ' . number_format($grand_total, 0) . '</strong></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="footer">
            <p>Joy Eateries - Mombasa, Kenya | +254 726 492 303</p>
            <p>© ' . date('Y') . ' Joy Eateries - All Rights Reserved</p>
        </div>
    </body>
    </html>';
    
    // Create PDF using mPDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-L', // Landscape
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15,
        'margin_header' => 5,
        'margin_footer' => 5
    ]);
    
    $mpdf->SetTitle('Completed Orders Report - Joy Eateries');
    $mpdf->SetAuthor('Joy Eateries');
    $mpdf->WriteHTML($html);
    $mpdf->Output('completed_orders_report_' . date('Y-m-d') . '.pdf', 'D');
    exit();
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

$category_icons = [
    'pizza' => '🍕',
    'burger' => '🍔',
    'juice' => '🧃',
    'soda' => '🥤',
    'side' => '🍟',
    'salad' => '🥗'
];

$category_names = [
    'pizza' => 'Pizzas',
    'burger' => 'Burgers',
    'juice' => 'Juices',
    'soda' => 'Sodas',
    'side' => 'Sides',
    'salad' => 'Salads'
];

// Fetch orders
$orders = [];
$orders_query = "SELECT * FROM orders ORDER BY order_date DESC";
$orders_result = mysqli_query($conn, $orders_query);
while ($row = mysqli_fetch_assoc($orders_result)) {
    $orders[] = $row;
}

$completed_orders = array_filter($orders, function($o) { return $o['status'] == 'completed'; });
$cancelled_orders = array_filter($orders, function($o) { return $o['status'] == 'cancelled'; });
$pending_orders_list = array_filter($orders, function($o) { return $o['status'] == 'pending'; });
$preparing_orders_list = array_filter($orders, function($o) { return $o['status'] == 'preparing'; });

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'menu';

$total_orders = count($orders);
$pending_orders = count($pending_orders_list);
$preparing_orders = count($preparing_orders_list);
$completed_orders_count = count($completed_orders);
$total_revenue = array_sum(array_column($completed_orders, 'total_amount'));

$all_processed = array_merge($completed_orders, $cancelled_orders);
usort($all_processed, function($a, $b) {
    return strtotime($b['order_date']) - strtotime($a['order_date']);
});
$report_total = array_sum(array_column($completed_orders, 'total_amount'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Joy Eateries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%); min-height: 100vh; }
        .joy-primary { background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%); }
        .btn-joy { background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%); }
        
        .tracking-code { font-family: monospace; background: #f5f5f5; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; display: inline-block; }
        
        .admin-stats { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; }
        .stat-card { text-align: center; padding: 10px; }
        .stat-number { font-size: 2rem; font-weight: 700; color: #d4a373; }
        .stat-label { color: #666; margin-top: 5px; font-size: 0.85rem; }
        
        .menu-card { border-radius: 15px; overflow: hidden; transition: transform 0.3s ease; height: 100%; }
        .menu-card:hover { transform: translateY(-5px); }
        .menu-card .card-content { padding: 20px; }
        .menu-card .card-title { font-size: 1.3rem; font-weight: 600; padding-bottom: 12px; border-bottom: 2px solid #d4a373; margin-bottom: 15px; }
        
        .menu-table-header { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f5f5f5; border-radius: 8px; margin-bottom: 10px; font-weight: 600; font-size: 0.8rem; color: #5a3e2b; }
        .header-name { flex: 2; }
        .header-price { flex: 1; text-align: right; }
        
        .menu-items-list { display: flex; flex-direction: column; gap: 8px; }
        .menu-item { background: #fafafa; border-radius: 10px; padding: 10px 12px; }
        .item-row-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 10px; }
        .item-name { font-weight: 500; font-size: 0.9rem; color: #333; flex: 2; }
        .item-price { font-weight: 600; font-size: 0.9rem; color: #b5835a; flex: 1; text-align: right; }
        .item-row-bottom { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .item-status { flex: 1; }
        .item-action { flex: 1; text-align: right; }
        
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 25px; font-size: 0.75rem; font-weight: 500; }
        .status-available { background: #e8f5e9; color: #4caf50; }
        .status-outofstock { background: #ffebee; color: #f44336; }
        
        .action-btn { border: none; padding: 5px 14px; border-radius: 25px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; font-size: 0.75rem; font-weight: 500; transition: all 0.3s ease; }
        .action-btn:hover { transform: scale(1.02); }
        .btn-disable { background: #f44336; color: white; }
        .btn-enable { background: #4caf50; color: white; }
        
        .orders-card { border-radius: 15px; overflow: hidden; }
        .orders-table-container { overflow-x: auto; }
        .orders-table { width: 100%; border-collapse: collapse; }
        .orders-table th { background: #f8f5f0; color: #5a3e2b; font-weight: 600; font-size: 0.85rem; padding: 14px 12px; text-align: left; border-bottom: 2px solid #d4a373; }
        .orders-table td { padding: 12px; font-size: 0.85rem; vertical-align: middle; border-bottom: 1px solid #eee; }
        .orders-table tr:hover { background: #fafaf5; }
        
        .order-status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 25px; font-size: 0.75rem; font-weight: 500; }
        .status-pending { background: #fff3e0; color: #ff9800; }
        .status-preparing { background: #e3f2fd; color: #2196f3; }
        .status-completed { background: #e8f5e9; color: #4caf50; }
        .status-cancelled { background: #ffebee; color: #f44336; }
        
        .update-btn { background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%); border: none; padding: 6px 14px; border-radius: 25px; color: white; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 0.75rem; transition: all 0.3s ease; }
        .update-btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .status-select { padding: 6px 10px; border-radius: 25px; border: 1px solid #ddd; background: white; font-size: 0.75rem; margin-right: 8px; min-width: 100px; }
        .action-cell { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        
        /* Report styles */
        .report-card { border-radius: 15px; overflow: hidden; height: 100%; display: flex; flex-direction: column; }
        .report-header { background: linear-gradient(135deg, #2c3e2f 0%, #1e2a22 100%); color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .report-header h5 { margin: 0; display: flex; align-items: center; gap: 8px; }
        .report-header p { margin: 5px 0 0 0; opacity: 0.8; font-size: 0.8rem; }
        .btn-download { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); border-radius: 25px; padding: 6px 15px; color: white; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 0.75rem; transition: all 0.3s ease; text-decoration: none; }
        .btn-download:hover { background: rgba(255,255,255,0.3); transform: scale(1.02); }
        .report-content { padding: 20px; flex: 1; max-height: 400px; overflow-y: auto; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th { background: #f5f5f5; padding: 10px; text-align: left; font-weight: 600; color: #5a3e2b; border-bottom: 2px solid #d4a373; position: sticky; top: 0; }
        .report-table td { padding: 8px 10px; border-bottom: 1px solid #eee; }
        .report-total { background: #f5f5f5; font-weight: bold; }
        
        .confirm-modal { max-width: 400px; border-radius: 16px; }
        .confirm-modal .modal-content { padding: 24px; text-align: center; }
        .confirm-modal .modal-content i { font-size: 56px; margin-bottom: 16px; }
        .confirm-modal .modal-footer { padding: 16px 24px; text-align: center; border-top: 1px solid #eee; }
        .confirm-btn { padding: 8px 24px; border-radius: 25px; margin: 0 8px; }
        
        footer { background: linear-gradient(135deg, #2c3e2f 0%, #1e2a22 100%); margin-top: 50px; color: #f0e6d8; padding: 20px 0; }
        
        @media (max-width: 900px) {
            .orders-table th, .orders-table td { padding: 8px 6px; font-size: 0.75rem; }
            .status-select { min-width: 80px; font-size: 0.7rem; padding: 4px 8px; }
            .update-btn { padding: 4px 10px; font-size: 0.7rem; }
        }
        @media (max-width: 700px) { .action-cell { flex-direction: column; align-items: flex-start; } .status-select { margin-right: 0; margin-bottom: 5px; width: 100%; } }
        @media (max-width: 550px) {
            .item-row-top { flex-direction: column; align-items: flex-start; }
            .item-price { text-align: left; }
            .item-row-bottom { flex-direction: column; align-items: flex-start; }
            .item-action { text-align: left; }
            .menu-table-header { display: none; }
            .stat-number { font-size: 1.3rem; }
            .orders-table th:nth-child(4), .orders-table td:nth-child(4),
            .orders-table th:nth-child(5), .orders-table td:nth-child(5) { display: none; }
        }
    </style>
</head>
<body>

<nav class="joy-primary" style="padding: 0 15px;">
    <div class="nav-wrapper">
        <a href="index.php" class="brand-logo left" style="font-size: 1.2rem;">
            <img src="../img/logo.png" alt="Joy Eateries" style="height: 45px; margin-top: 8px; vertical-align: middle;">
            <span class="hide-on-small-only" style="font-weight: 600;">Admin Panel</span>
        </a>
        <ul class="right hide-on-small-only">
            <li><a href="index.php?tab=menu"><i class="material-icons left">restaurant_menu</i>Menu</a></li>
            <li><a href="index.php?tab=orders"><i class="material-icons left">shopping_cart</i>Orders</a></li>
            <li><a href="index.php?tab=reports"><i class="material-icons left">assessment</i>Reports</a></li>
            <li><a href="logout.php" onclick="return confirm('Logout from admin panel?')"><i class="material-icons left">exit_to_app</i>Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container" style="margin-top: 20px; margin-bottom: 30px;">
    <div class="row">
        <div class="col s12">
            <div class="card-panel" style="background: #2c3e2f; color: white; border-radius: 15px;">
                <div class="row" style="margin-bottom: 0;">
                    <div class="col s12 m8">
                        <i class="material-icons left">admin_panel_settings</i>
                        <strong>Admin Dashboard</strong> - Manage menu items and track orders
                    </div>
                    <div class="col s12 m4 right-align">
                        <a href="../customer/index.php" target="_blank" class="btn white black-text waves-effect" style="border-radius: 25px;">
                            <i class="material-icons left">visibility</i>View Site
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="admin-stats">
                <div class="row" style="margin-bottom: 0;">
                    <div class="col s6 m3"><div class="stat-card"><i class="material-icons" style="color: #d4a373; font-size: 2rem;">receipt</i><div class="stat-number"><?= $total_orders ?></div><div class="stat-label">Total Orders</div></div></div>
                    <div class="col s6 m3"><div class="stat-card"><i class="material-icons" style="color: #ff9800; font-size: 2rem;">pending</i><div class="stat-number"><?= $pending_orders ?></div><div class="stat-label">Pending</div></div></div>
                    <div class="col s6 m3"><div class="stat-card"><i class="material-icons" style="color: #2196f3; font-size: 2rem;">kitchen</i><div class="stat-number"><?= $preparing_orders ?></div><div class="stat-label">Preparing</div></div></div>
                    <div class="col s6 m3"><div class="stat-card"><i class="material-icons" style="color: #4caf50; font-size: 2rem;">check_circle</i><div class="stat-number"><?= $completed_orders_count ?></div><div class="stat-label">Completed</div></div></div>
                </div>
                <div class="row" style="margin-top: 10px; margin-bottom: 0;">
                    <div class="col s12 center-align"><div class="stat-card"><i class="material-icons" style="color: #d4a373; font-size: 1.5rem;">attach_money</i><div class="stat-number" style="font-size: 1.5rem;">KSh <?= number_format($total_revenue, 0) ?></div><div class="stat-label">Total Revenue</div></div></div>
                </div>
            </div>
            
            <ul class="tabs" style="border-radius: 10px; overflow: hidden;">
                <li class="tab col s4"><a href="#menu-management" class="<?= $active_tab == 'menu' ? 'active' : '' ?>"><i class="material-icons left">restaurant_menu</i>Menu</a></li>
                <li class="tab col s4"><a href="#orders-management" class="<?= $active_tab == 'orders' ? 'active' : '' ?>"><i class="material-icons left">shopping_cart</i>Orders</a></li>
                <li class="tab col s4"><a href="#reports-management" class="<?= $active_tab == 'reports' ? 'active' : '' ?>"><i class="material-icons left">assessment</i>Reports</a></li>
            </ul>
        </div>
        
        <!-- Menu Management Tab -->
        <div id="menu-management" class="col s12" style="margin-top: 25px;">
            <div class="row">
                <?php foreach ($menu_categories as $category): ?>
                    <?php if (!empty($menu_items[$category])): ?>
                        <div class="col s12 m6 l4">
                            <div class="card menu-card">
                                <div class="card-content">
                                    <div class="card-title"><span><?= $category_icons[$category] ?></span><?= $category_names[$category] ?></div>
                                    <div class="menu-table-header"><div class="header-name">Item Name</div><div class="header-price">Price (KSh)</div></div>
                                    <div class="menu-items-list">
                                        <?php foreach ($menu_items[$category] as $item): ?>
                                            <div class="menu-item">
                                                <div class="item-row-top"><div class="item-name"><?= htmlspecialchars($item['name']) ?></div><div class="item-price"><?= number_format($item['price'], 0) ?></div></div>
                                                <div class="item-row-bottom">
                                                    <div class="item-status"><?php if ($item['available'] == '1'): ?><span class="status-badge status-available"><i class="material-icons" style="font-size: 0.8rem;">check_circle</i>Available</span><?php else: ?><span class="status-badge status-outofstock"><i class="material-icons" style="font-size: 0.8rem;">block</i>Out of Stock</span><?php endif; ?></div>
                                                    <div class="item-action"><form method="POST" style="display: inline;"><input type="hidden" name="item_id" value="<?= $item['id'] ?>"><input type="hidden" name="available" value="<?= $item['available'] == '1' ? '0' : '1' ?>"><?php if ($item['available'] == '1'): ?><button type="submit" name="update_availability" class="action-btn btn-disable"><i class="material-icons">block</i>Disable</button><?php else: ?><button type="submit" name="update_availability" class="action-btn btn-enable"><i class="material-icons">check_circle</i>Enable</button><?php endif; ?></form></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Orders Management Tab -->
        <div id="orders-management" class="col s12" style="margin-top: 25px;">
            <div class="card orders-card">
                <div class="card-content" style="padding: 0;">
                    <div class="orders-table-container">
                        <table class="orders-table">
                            <thead><tr><th>Order #</th><th>Tracking Code</th><th>Customer</th><th>Phone</th><th>Total</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr><td colspan="8" class="center-align" style="padding: 40px;"><i class="material-icons" style="font-size: 48px; color: #ccc;">inbox</i><p class="grey-text">No orders yet</p></td></tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr id="order-row-<?= $order['id'] ?>">
                                            <td><a href="../customer/order-tracking.php?code=<?= $order['tracking_code'] ?>" target="_blank" style="color: #d4a373; font-weight: 500;">#<?= $order['id'] ?></a></td>
                                            <td><code class="tracking-code"><?= $order['tracking_code'] ?></code></td>
                                            <td><strong><?= htmlspecialchars($order['customer_name']) ?></strong></td>
                                            <td><?= htmlspecialchars($order['customer_phone']) ?></td>
                                            <td class="price-text"><strong>KSh <?= number_format($order['total_amount'], 0) ?></strong></td>
                                            <td><?= date('d M Y', strtotime($order['order_date'])) ?><br><small class="grey-text"><?= date('h:i A', strtotime($order['order_date'])) ?></small></td>
                                            <td><span class="order-status-badge status-<?= $order['status'] ?>" id="status-badge-<?= $order['id'] ?>"><?php if ($order['status'] == 'pending'): ?><i class="material-icons" style="font-size: 0.7rem;">schedule</i><?php elseif ($order['status'] == 'preparing'): ?><i class="material-icons" style="font-size: 0.7rem;">kitchen</i><?php elseif ($order['status'] == 'completed'): ?><i class="material-icons" style="font-size: 0.7rem;">check_circle</i><?php else: ?><i class="material-icons" style="font-size: 0.7rem;">cancel</i><?php endif; ?><?= ucfirst($order['status']) ?></span></td>
                                            <td class="action-cell"><?php if ($order['status'] == 'completed' || $order['status'] == 'cancelled'): ?><select class="status-select" disabled><option value="pending">Pending</option><option value="preparing">Preparing</option><option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option><option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option></select><button class="update-btn" disabled><i class="material-icons" style="font-size: 0.8rem;">lock</i>Locked</button><?php else: ?><select class="status-select" data-order-id="<?= $order['id'] ?>"><option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option><option value="preparing" <?= $order['status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select><button class="update-btn update-status-btn" data-order-id="<?= $order['id'] ?>"><i class="material-icons" style="font-size: 0.8rem;">update</i>Update</button><?php endif; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reports Management Tab -->
        <div id="reports-management" class="col s12" style="margin-top: 25px;">
            <div class="row">
                <div class="col s12 m6">
                    <div class="card report-card">
                        <div class="report-header">
                            <div><h5><i class="material-icons left">receipt</i>All Orders Report</h5><p>Completed and cancelled orders</p></div>
                            <a href="?pdf_all_orders=1" class="btn-download"><i class="material-icons" style="font-size: 1rem;">picture_as_pdf</i>Download PDF</a>
                        </div>
                        <div class="report-content">
                            <?php if (empty($all_processed)): ?>
                                <p class="center-align grey-text">No completed or cancelled orders yet</p>
                            <?php else: ?>
                                <div style="overflow-x: auto;">
                                    <table class="report-table">
                                        <thead><tr><th>Order #</th><th>Tracking Code</th><th>Customer</th><th>Date</th><th>Status</th><th class="right-align">Total (KSh)</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($all_processed as $order): ?>
                                                <tr>
                                                    <td>#<?= $order['id'] ?></td>
                                                    <td><code class="tracking-code"><?= $order['tracking_code'] ?></code></td>
                                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                                    <td><?= date('d M Y', strtotime($order['order_date'])) ?></td>
                                                    <td><span class="order-status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                                                    <td class="right-align"><?= number_format($order['total_amount'], 0) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col s12 m6">
                    <div class="card report-card">
                        <div class="report-header">
                            <div><h5><i class="material-icons left">check_circle</i>Completed Orders Report</h5><p>All completed orders with total amounts</p></div>
                            <a href="?pdf_completed_orders=1" class="btn-download"><i class="material-icons" style="font-size: 1rem;">picture_as_pdf</i>Download PDF</a>
                        </div>
                        <div class="report-content">
                            <?php if (empty($completed_orders)): ?>
                                <p class="center-align grey-text">No completed orders yet</p>
                            <?php else: ?>
                                <div style="overflow-x: auto;">
                                    <table class="report-table">
                                        <thead><tr><th>Order #</th><th>Tracking Code</th><th>Customer</th><th>Date</th><th class="right-align">Total (KSh)</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($completed_orders as $order): ?>
                                                <tr>
                                                    <td>#<?= $order['id'] ?></td>
                                                    <td><code class="tracking-code"><?= $order['tracking_code'] ?></code></td>
                                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                                    <td><?= date('d M Y', strtotime($order['order_date'])) ?></td>
                                                    <td class="right-align"><?= number_format($order['total_amount'], 0) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="report-total">
                                                <td colspan="4" class="right-align"><strong>GRAND TOTAL:</strong></td>
                                                <td class="right-align"><strong>KSh <?= number_format($report_total, 0) ?></strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="status-confirm-modal" class="modal confirm-modal">
    <div class="modal-content"><i class="material-icons" id="confirm-icon" style="color: #ff9800;">warning</i><h5 id="confirm-title">Confirm Status Change</h5><p id="confirm-message">Are you sure you want to change this order's status?</p></div>
    <div class="modal-footer"><button class="modal-close waves-effect waves-red btn-flat confirm-btn">Cancel</button><button id="confirm-action" class="btn waves-effect waves-light confirm-btn" style="background: #4caf50;">Confirm</button></div>
</div>

<footer class="page-footer" style="padding-top: 20px;">
    <div class="container">
        <div class="row">
            <div class="col s12 m4"><img src="../img/logo.png" alt="Joy Eateries" style="height: 50px;"><p>Admin Panel</p></div>
            <div class="col s12 m4"><h6>Quick Links</h6><p><a href="index.php?tab=menu" style="color: white;">Manage Menu</a><br><a href="index.php?tab=orders" style="color: white;">View Orders</a><br><a href="index.php?tab=reports" style="color: white;">View Reports</a><br><a href="../customer/index.php" target="_blank" style="color: white;">View Customer Site</a></p></div>
            <div class="col s12 m4"><h6>Admin Info</h6><p>Logged in as: Administrator</p><p><small>Joy Eateries Management System</small></p></div>
        </div>
    </div>
    <div class="footer-copyright"><div class="container center">© 2026 Joy Eateries - Admin Panel</div></div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script>
    let pendingUpdate = null;
    
    function openConfirmModal(orderId, newStatus, button) {
        const modal = M.Modal.getInstance(document.getElementById('status-confirm-modal'));
        const confirmIcon = document.getElementById('confirm-icon');
        const confirmTitle = document.getElementById('confirm-title');
        const confirmMessage = document.getElementById('confirm-message');
        const confirmBtn = document.getElementById('confirm-action');
        if (newStatus === 'completed') {
            confirmIcon.innerHTML = 'check_circle'; confirmIcon.style.color = '#4caf50';
            confirmTitle.textContent = 'Mark as Completed?';
            confirmMessage.textContent = 'Once marked as completed, this order cannot be changed again.';
            confirmBtn.style.background = '#4caf50';
        } else if (newStatus === 'cancelled') {
            confirmIcon.innerHTML = 'cancel'; confirmIcon.style.color = '#f44336';
            confirmTitle.textContent = 'Cancel Order?';
            confirmMessage.textContent = 'Are you sure you want to cancel this order?';
            confirmBtn.style.background = '#f44336';
        } else {
            confirmIcon.innerHTML = 'update'; confirmIcon.style.color = '#ff9800';
            confirmTitle.textContent = `Change to ${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}?`;
            confirmMessage.textContent = `Are you sure you want to change this order's status?`;
            confirmBtn.style.background = '#d4a373';
        }
        pendingUpdate = { orderId, newStatus, button };
        modal.open();
    }
    
    document.getElementById('confirm-action').addEventListener('click', function() {
        if (pendingUpdate) {
            performStatusUpdate(pendingUpdate.orderId, pendingUpdate.newStatus, pendingUpdate.button);
            M.Modal.getInstance(document.getElementById('status-confirm-modal')).close();
            pendingUpdate = null;
        }
    });
    
    function performStatusUpdate(orderId, newStatus, button) {
        const select = document.querySelector(`.status-select[data-order-id="${orderId}"]`);
        const statusBadge = document.getElementById(`status-badge-${orderId}`);
        const statusConfig = { 'pending': { class: 'status-pending', text: 'Pending', icon: 'schedule' }, 'preparing': { class: 'status-preparing', text: 'Preparing', icon: 'kitchen' }, 'completed': { class: 'status-completed', text: 'Completed', icon: 'check_circle' }, 'cancelled': { class: 'status-cancelled', text: 'Cancelled', icon: 'cancel' } };
        button.disabled = true;
        button.innerHTML = '<i class="material-icons" style="font-size: 0.8rem;">hourglass_empty</i>Updating...';
        fetch('index.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: `update_order_status=1&order_id=${orderId}&status=${newStatus}` })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusBadge.className = `order-status-badge ${statusConfig[newStatus].class}`;
                statusBadge.innerHTML = `<i class="material-icons" style="font-size: 0.7rem;">${statusConfig[newStatus].icon}</i> ${statusConfig[newStatus].text}`;
                if (newStatus === 'completed' || newStatus === 'cancelled') { select.disabled = true; button.disabled = true; button.innerHTML = '<i class="material-icons" style="font-size: 0.8rem;">lock</i> Locked'; }
                else { button.disabled = false; button.innerHTML = '<i class="material-icons" style="font-size: 0.8rem;">update</i> Update'; }
                M.toast({ html: `Order #${orderId} updated to ${statusConfig[newStatus].text}`, classes: 'rounded green', displayLength: 2000 });
                setTimeout(() => { location.reload(); }, 1000);
            }
        })
        .catch(error => { M.toast({ html: 'Error updating order', classes: 'rounded red' }); button.disabled = false; button.innerHTML = '<i class="material-icons" style="font-size: 0.8rem;">update</i> Update'; });
    }
    
    document.querySelectorAll('.update-status-btn').forEach(btn => { btn.addEventListener('click', function(e) { const button = e.currentTarget; const orderId = button.getAttribute('data-order-id'); const select = document.querySelector(`.status-select[data-order-id="${orderId}"]`); const newStatus = select.value; openConfirmModal(orderId, newStatus, button); }); });
    
    document.addEventListener('DOMContentLoaded', function() { var tabs = document.querySelectorAll('.tabs'); M.Tabs.init(tabs); var selects = document.querySelectorAll('select'); M.FormSelect.init(selects); var modals = document.querySelectorAll('.modal'); M.Modal.init(modals); });
</script>

<?php mysqli_close($conn); ?>
</body>
</html>