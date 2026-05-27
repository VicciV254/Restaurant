<?php
// dbconnect.php - Database configuration only (NO SESSIONS)

// Database connection for AwardSpace
$host = "fdb1032.awardspace.net";      // Your database host
$user = "4762922_restaurant";           // Your database username (adjust if different)
$pass = "0OiZeF/17Nx:54}T";            // Your database password
$db   = "4762922_restaurant";           // Your database name (adjust if different)

$conn = mysqli_connect($host, $user, $pass, $db);

// Check connection
if(!$conn){
    die('Connection error: ' . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// Function to sanitize input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($data)));
}

// Function to generate tracking code
function generateTrackingCode($conn) {
    $query = "SELECT MAX(id) as last_id FROM orders";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $next_id = ($row['last_id'] + 1);
    return 'JOY' . str_pad($next_id, 5, '0', STR_PAD_LEFT);
}
?>