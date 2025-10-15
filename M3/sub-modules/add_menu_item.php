<?php
// add_menu_item.php
include("../../main_connection.php");

$db_name = "rest_m3_menu";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}
$conn = $connections[$db_name];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request method.');
}

// -------------------- Inputs --------------------
$name        = trim($_POST['name'] ?? '');
$category    = trim($_POST['category'] ?? '');
$price       = floatval($_POST['price'] ?? 0);
$prep_time   = intval($_POST['prep_time'] ?? 0);
$status      = trim($_POST['status'] ?? 'Under review');
$description = trim($_POST['description'] ?? '');
$variant     = trim($_POST['variant'] ?? '');

// Ingredient slots
$ingredient1_id  = intval($_POST['ingredient1_id'] ?? 0);
$ingredient1_qty = floatval($_POST['ingredient1_qty'] ?? 0);
$ingredient2_id  = intval($_POST['ingredient2_id'] ?? 0);
$ingredient2_qty = floatval($_POST['ingredient2_qty'] ?? 0);

if ($name === '' || $category === '' || $price <= 0 || $prep_time <= 0 || $description === '') {
    header("Location: ../main.php?error=missing_fields");
    exit;
}

// -------------------- Image Upload → menu_images --------------------
$menu_image_id = null;
if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
    $fileType = mime_content_type($_FILES['image_url']['tmp_name']);
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($fileType, $allowedTypes)) {
        header("Location: ../main.php?error=invalid_file_type");
        exit;
    }

    // Read binary image
    $imageData = file_get_contents($_FILES['image_url']['tmp_name']);

    // Insert into menu_images
    $stmtImg = $conn->prepare("INSERT INTO menu_images (image_data, mime_type, uploaded_at) VALUES (?, ?, NOW())");
    $null = NULL; // required for blob
    $stmtImg->bind_param("bs", $null, $fileType);
    $stmtImg->send_long_data(0, $imageData);

    if ($stmtImg->execute()) {
        $menu_image_id = $stmtImg->insert_id;
    } else {
        header("Location: ../main.php?error=image_insert_failed");
        exit;
    }
    $stmtImg->close();
}

// -------------------- Insert into menu --------------------
$query = "INSERT INTO menu 
          (name, category, price, description, variant, status, prep_time, menu_image_id,
           ingredient1_id, ingredient1_qty, ingredient2_id, ingredient2_qty,
           created_at, updated_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = $conn->prepare($query);
if (!$stmt) {
    header("Location: ../main.php?error=prepare_failed");
    exit;
}

$stmt->bind_param(
    "ssdsssiiidid", 
    $name, $category, $price, $description, $variant, $status, $prep_time, $menu_image_id,
    $ingredient1_id, $ingredient1_qty, $ingredient2_id, $ingredient2_qty
);

if ($stmt->execute()) {
    header("Location: ../main.php?success=1");
} else {
    header("Location: ../main.php?error=insert_failed");
}

$stmt->close();
$conn->close();
