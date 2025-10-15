<?php
include("../../main_connection.php");

if (!isset($_GET['id'])) {
    echo "<p class='text-red-500'>Invalid reservation ID.</p>";
    exit;
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM reservations WHERE reservation_id = $id LIMIT 1";
$res = $connections['rest_m1_trs']->query($sql);

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();

    echo "<div class='space-y-2'>";
    echo "<p><strong>Name:</strong> " . htmlspecialchars($row['name']) . "</p>";
    echo "<p><strong>Contact:</strong> " . htmlspecialchars($row['contact']) . "</p>";
    echo "<p><strong>Date:</strong> " . date('M j, Y', strtotime($row['reservation_date'])) . "</p>";
    echo "<p><strong>Time:</strong> " . date('g:i A', strtotime($row['start_time'])) . " - " . date('g:i A', strtotime($row['end_time'])) . "</p>";
    echo "<p><strong>Size:</strong> " . (int)$row['size'] . "</p>";
    echo "<p><strong>Type:</strong> " . htmlspecialchars($row['type']) . "</p>";
    echo "<p><strong>Status:</strong> " . htmlspecialchars($row['status']) . "</p>";
    echo "</div>";

    // CRUD buttons
    echo "<div class='flex space-x-2 mt-4'>";
    if ($row['status'] == 'Queued' || $row['status'] == 'Confirmed') {
        echo "<form action='sub-modules/update_reservation_status.php' method='POST' class='inline'>
                <input type='hidden' name='reservation_id' value='{$row['reservation_id']}'>
                <input type='hidden' name='status' value='Confirmed'>
                <button type='submit' class='btn btn-sm btn-success'>
                  <i data-lucide=\"check\" class=\"w-4 h-4\"></i> Confirm
                </button>
              </form>";
        echo "<form action='sub-modules/update_reservation_status.php' method='POST' class='inline'>
                <input type='hidden' name='reservation_id' value='{$row['reservation_id']}'>
                <input type='hidden' name='status' value='Denied'>
                <button type='submit' class='btn btn-sm btn-error'>
                  <i data-lucide=\"x\" class=\"w-4 h-4\"></i> Reject
                </button>
              </form>";
    }
    echo "</div>";
} else {
    echo "<p class='text-gray-500'>Reservation not found.</p>";
}
