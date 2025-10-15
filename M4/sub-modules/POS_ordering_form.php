<?php
include("../../main_connection.php");

// Force JSON output
header('Content-Type: application/json');

function respond($status, $message, $extra = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

// =======================
// Database connections
// =======================
$conn_pos = $connections["rest_m4_pos"] ?? respond('error', 'Connection not found for POS');
$conn_billing = $connections["rest_m7_billing_payments"] ?? respond('error', 'Connection not found for Billing');
$conn_kot = $connections["rest_m6_kot"] ?? respond('error', 'Connection not found for KOT');

// =======================
// Collect form data
// =======================
$order_code     = $_POST['order_code'] ?? null;
$table_id       = $_POST['table_id'] ?? null;
$customer_name  = $_POST['customer_name'] ?? 'Walk-in Customer';
$order_type     = $_POST['order_type'] ?? 'dine-in';
$total_amount   = $_POST['total_amount'] ?? 0;
$mop            = $_POST['MOP'] ?? 'cash';
$notes          = $_POST['notes'] ?? '';
$orders_json    = $_POST['order_items_json'] ?? '[]';
$created_at     = date("Y-m-d H:i:s");

// Decode orders JSON safely
$order_items = json_decode($orders_json, true) ?? [];
$orders_json_str = json_encode($order_items); // full POS order for KOT as well

// =======================
// Step 1: Insert into POS.orders
// =======================
$status_pos = "Pending";
$sql_pos = "INSERT INTO orders 
    (order_code, table_id, customer_name, order_type, status, total_amount, MOP, notes, created_at, orders) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt_pos = $conn_pos->prepare($sql_pos);
$stmt_pos->bind_param(
    "sisssdssss",
    $order_code,
    $table_id,
    $customer_name,
    $order_type,
    $status_pos,
    $total_amount,
    $mop,
    $notes,
    $created_at,
    $orders_json_str
);
if (!$stmt_pos->execute()) {
    respond('error', 'POS insert error: ' . $stmt_pos->error);
}
$order_id = $stmt_pos->insert_id;
$stmt_pos->close();

// =======================
// Step 2: Insert into Billing
// =======================
$invoice_number = $order_code;
$invoice_date   = date("Y-m-d");
$status_billing = "Paid";
$description    = "Order for Table #{$table_id} - {$order_type}";
$quantity       = 1;
$unit_price     = $total_amount;
$payment_date   = date("Y-m-d"); 
$payment_amount = $total_amount;
$trans_ref      = strtoupper(uniqid("TXN"));
$updated_at     = $created_at;

$client_email   = $_POST['customer_email'] ?? '';
$client_contact = $_POST['customer_contact'] ?? '';

$sql_billing = "INSERT INTO billing_payments 
    (client_name, client_email, client_contact, invoice_number, invoice_date, status, description, quantity, unit_price, total_amount, payment_date, payment_amount, MOP, trans_ref, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt_billing = $conn_billing->prepare($sql_billing);
$stmt_billing->bind_param(
    "ssssssssddddssss", 
    $customer_name,
    $client_email,
    $client_contact,
    $invoice_number,
    $invoice_date,
    $status_billing,
    $description,
    $quantity,
    $unit_price,
    $total_amount,
    $payment_date,
    $payment_amount,
    $mop,
    $trans_ref,
    $created_at,
    $updated_at
);
if (!$stmt_billing->execute()) {
    respond('error', 'Billing insert error: ' . $stmt_billing->error);
}
$stmt_billing->close();

// =======================
// Step 3: Insert into KOT (exact same order as POS)
// =======================
$table_number = "T{$table_id}";
$kot_status   = "Pending";

$sql_kot = "INSERT INTO kot_orders 
    (order_id, table_number, item_name, quantity, special_instructions, status, created_at, updated_at, orders, table_id, menu_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt_kot = $conn_kot->prepare($sql_kot);

foreach ($order_items as $item) {
    $menu_id      = $item['menuId'] ?? 0;
    $item_name    = $item['name'] ?? '';
    $kot_quantity = $item['quantity'] ?? 1;

    $stmt_kot->bind_param(
        "ississssiii",
        $order_id,
        $table_number,
        $item_name,
        $kot_quantity,
        $notes,
        $kot_status,
        $created_at,
        $created_at,
        $orders_json_str, // send full order same as POS
        $table_id,
        $menu_id
    );

    if (!$stmt_kot->execute()) {
        respond('error', 'KOT insert error: ' . $stmt_kot->error);
    }
}
$stmt_kot->close();

// =======================
// Close connections
// =======================
// =======================
// Close connections
// =======================
$conn_pos->close();
$conn_billing->close();
$conn_kot->close();

// ✅ Redirect after successful order
header("Location: ../employee_main.php?order_id={$order_id}");
exit;
