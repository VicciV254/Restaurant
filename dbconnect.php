<?php
// Database configuration for InfinityFree
$host = "sql307.infinityfree.com";      // Your MySQL hostname (from vPanel)
$user = "if0_42039279";                    // Your database username
$pass = "1vns8PJ59ChoC4j";        // Your database password
$db   = "if0_42039279_restaurant_db";      // Your database name

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// Your existing functions (sanitize, generateTrackingCode)
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($data)));
}

function generateTrackingCode($conn) {
    $query = "SELECT MAX(id) as last_id FROM orders";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $next_id = ($row['last_id'] + 1);
    return 'JOY' . str_pad($next_id, 5, '0', STR_PAD_LEFT);
}
?>