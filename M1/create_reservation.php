<?php
// create_reservation.php
session_start();

include("../main_connection.php");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db_name = "rest_m1_trs"; // ✅ Database name

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}
$conn = $connections[$db_name]; // ✅ DB connection

// Function to sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Get form data
$name = sanitizeInput($_POST['name'] ?? '');
$contact = sanitizeInput($_POST['contact'] ?? '');
$reservation_date = sanitizeInput($_POST['reservation_date'] ?? '');
$start_time = sanitizeInput($_POST['start_time'] ?? '');
$end_time = sanitizeInput($_POST['end_time'] ?? '');
$size = sanitizeInput($_POST['size'] ?? '');
$table_id = sanitizeInput($_POST['table_id'] ?? '');
$type = sanitizeInput($_POST['type'] ?? '');
$request = sanitizeInput($_POST['request'] ?? '');
$note = sanitizeInput($_POST['note'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');

// Insert reservation with status = Queue
$insert_sql = "
    INSERT INTO reservations (
        name, contact, reservation_date, 
        start_time, end_time, size, table_id, type, 
        request, note, status, created_at, email
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Queue', NOW(), ?)
";

$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param(
    "sssssiissss", 
    $name, $contact, $reservation_date, 
    $start_time, $end_time, $size, $table_id, $type, 
    $request, $note, $email
);

if ($insert_stmt->execute()) {
    // ✅ Redirect after success
    header("Location: calendar.php"); 
    exit;
} else {
    // ✅ Show error if insert fails
    echo "Error creating reservation: " . $conn->error;
}

// Cleanup
$insert_stmt->close();
$conn->close();
?>
