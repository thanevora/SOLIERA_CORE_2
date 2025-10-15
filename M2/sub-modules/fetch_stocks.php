<?php
header('Content-Type: application/json');
include("../../main_connection.php");

$db_name = "rest_m2_inventory"; // Updated to match your database name

if (!isset($connections[$db_name])) {
    die(json_encode(['status' => 'error', 'message' => "❌ Connection not found for $db_name"]));
}

$conn = $connections[$db_name];

// Get all columns from the inventory_and_stock table
$query = "SELECT item_id, item_name, category, quantity, critical_level, unit_price, expiry_date FROM inventory_and_stock ORDER BY item_name ASC";
$result = mysqli_query($conn, $query);

$stocks = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $stocks[] = $row;
    }
    echo json_encode(['status' => 'success', 'stocks' => $stocks]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch stocks: ' . mysqli_error($conn)]);
}
?>