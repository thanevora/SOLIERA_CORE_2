<?php
header('Content-Type: application/json');
include("../../main_connection.php");

$db_name = "rest_m1_trs"; // ✅ pick the DB you want

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name]; // ✅ get the correct DB connection

$query = "SELECT table_id, name, category, capacity, status FROM tables ORDER BY name ASC";
$result = mysqli_query($conn, $query);

$tables = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tables[] = $row;
    }
    echo json_encode(['status' => 'success', 'tables' => $tables]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch tables']);
}
