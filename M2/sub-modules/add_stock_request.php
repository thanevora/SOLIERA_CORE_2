<?php
include '../../main_connection.php';

$db_name = "rest_m2_inventory"; // ✅ pick the DB you want

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name]; // ✅ get the correct DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name         = $_POST['item_name'] ?? '';
    $category          = $_POST['category'] ?? '';
    $location          = $_POST['location'] ?? '';
    $quantity          = (int) ($_POST['quantity'] ?? 0);
    $critical_level    = (int) ($_POST['critical_level'] ?? 0);
    $unit_price        = (float) ($_POST['unit_price'] ?? 0);
    $notes             = $_POST['notes'] ?? '';
    $expiry_date       = $_POST['expiry_date'] ?: null;
    $last_restock_date = $_POST['last_restock_date'] ?: null;

    $created_at = date('Y-m-d H:i:s');
    $updated_at = $created_at;
    $request_status = 'For procurement approval';

    $stmt = $conn->prepare("
        INSERT INTO inventory_and_stock (
            item_name, category, location, quantity, critical_level,
            unit_price, notes, expiry_date, last_restock_date,
            created_at, updated_at, request_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssiiissssss",
        $item_name, $category, $location, $quantity, $critical_level,
        $unit_price, $notes, $expiry_date, $last_restock_date,
        $created_at, $updated_at, $request_status
    );

    if ($stmt->execute()) {
        // Redirect on success
        header("Location: ../main.php?success=1");
        exit();
    } else {
        // Redirect on error with error flag
        header("Location: ../main.php?error=1");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    // Invalid request, redirect
    header("Location: ../main.php?invalid=1");
    exit();
}
