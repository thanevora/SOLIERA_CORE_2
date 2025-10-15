<?php
session_start();
include("../../main_connection.php");

// Database connection
$db_name = "rest_m1_trs";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}
$conn = $connections[$db_name];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = $_POST['reservation_id'];
    $status = $_POST['status'];
    
    // Update reservation status
    $sql = "UPDATE reservations SET status = ?, modify_at = NOW() WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $reservation_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Reservation status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating reservation: " . $conn->error;
    }
    
    $stmt->close();
    
    // Redirect back to the reservations page
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
?>