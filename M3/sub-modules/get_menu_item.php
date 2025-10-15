<?php
// get_menu_item.php
include("../../main_connection.php");

$db_name = "rest_m3_menu";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}
$conn = $connections[$db_name];

$menu_id = intval($_GET['menu_id'] ?? 0);

$sql = "SELECT * FROM menu WHERE menu_id = $menu_id LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc(); ?>
    
    <div class="space-y-3">
        <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($row['name']); ?></h2>
        <hr>
        <p class="text-gray-600"><?= nl2br(htmlspecialchars($row['description'])); ?></p>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500">Category</p>
                <p class="font-medium"><?= htmlspecialchars($row['category']); ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Variant</p>
                <p class="font-medium"><?= htmlspecialchars($row['variant']); ?></p>
            </div>
        </div>
        <hr>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500">Price</p>
                <p class="font-medium">₱ <?= number_format($row['price'], 2); ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Status</p>
                <p class="font-medium <?= $row['status'] === 'Available' ? 'text-green-600' : 'text-red-600'; ?>">
                    <?= htmlspecialchars($row['status']); ?>
                </p>
            </div>
        </div>

    </div>

<?php
} else {
    echo "<p class='text-red-600'>Item not found.</p>";
}
$conn->close();
