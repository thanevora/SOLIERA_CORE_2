<?php

include("../../main_connection.php");

if (!isset($_GET['id'])) {
  echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
  exit;
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM tables WHERE table_id = $id";
$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
  echo json_encode(['status' => 'success', 'table' => $row]);
} else {
  echo json_encode(['status' => 'error', 'message' => 'Table not found']);
}
?>
