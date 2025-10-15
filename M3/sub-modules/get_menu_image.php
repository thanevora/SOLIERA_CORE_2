<?php
// get_menu_image.php
include("../main_connection.php");

$db_name = "rest_m3_menu";
$conn = $connections[$db_name];

$menu_image_id = intval($_GET['id'] ?? 0);
if ($menu_image_id <= 0) {
    http_response_code(400);
    exit("Invalid image request");
}

$stmt = $conn->prepare("SELECT image_data, mime_type FROM menu_images WHERE menu_image_id = ?");
$stmt->bind_param("i", $menu_image_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($imageData, $mimeType);
    $stmt->fetch();

    header("Content-Type: $mimeType");
    echo $imageData;
} else {
    http_response_code(404);
    readfile("../assets/no-image.png");
}

$stmt->close();
$conn->close();
