<?php

session_start();
include("../main_connection.php");

// Connect to databases
$conn_pos = $connections["rest_m4_pos"] ?? die("❌ Connection not found for m4_pos");
$conn_menu = $connections["rest_m3_menu"] ?? die("❌ Connection not found for m3_menu");
$conn_tables = $connections["rest_m1_trs"] ?? die("❌ Connection not found for m1_tr&s");

// Fetch available tables
$sql_tables = "SELECT table_id, name, capacity, category, status FROM tables WHERE status = 'Available'";
$result_tables = $conn_tables->query($sql_tables) or die("❌ SQL Error (tables): " . $conn_tables->error);

// Pagination settings
$itemsPerPage = 6;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Fetch menu items with filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'all';

$whereClauses = [];
if ($category !== 'all') {
    $whereClauses[] = "category = '" . $conn_menu->real_escape_string($category) . "'";
}
if (!empty($search)) {
    $whereClauses[] = "name LIKE '%" . $conn_menu->real_escape_string($search) . "%'";
}

$whereSQL = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);

// Get total count for pagination
$countSQL = "SELECT COUNT(*) as total FROM menu $whereSQL";
$countResult = $conn_menu->query($countSQL);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Fetch menu items with pagination
$sql_menu = "SELECT menu_id, name, category, description, variant, price, status, image_url, created_at, updated_at 
             FROM menu 
             $whereSQL 
             ORDER BY category, name
             LIMIT $offset, $itemsPerPage";
$result_menu = $conn_menu->query($sql_menu) or die("❌ SQL Error (menu): " . $conn_menu->error);

// Fetch distinct categories for tabs
$sql_categories = "SELECT DISTINCT category FROM menu WHERE category IS NOT NULL";
$result_categories = $conn_menu->query($sql_categories);
?>

<!DOCTYPE html>
<html lang="en">
      <?php include '../header.php'; ?>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Order | Soliera Restaurant</title>

        <link rel="stylesheet" href="../CSS/M4/menu.css">

</head>
<body class="bg-base-100 min-h-screen bg-white">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../navbar.php'; ?>

      <!-- Order Content -->
      <main class="p-4 md:p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Menu Section -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h2 class="text-xl font-bold text-gray-800">Menu</h2>
          </div>

          <!-- Category Tabs -->
          <div class="flex overflow-x-auto pb-2 mb-6 gap-1">
            <a href="?category=all&search=<?= urlencode($search) ?>"
               class="category-tab px-4 py-2 whitespace-nowrap <?= $category === 'all' ? 'active' : '' ?>">
              All Items
            </a>
            <?php 
              $result_categories->data_seek(0);
              while ($cat = $result_categories->fetch_assoc()):
            ?>
              <a href="?category=<?= urlencode($cat['category']) ?>&search=<?= urlencode($search) ?>"
                 class="category-tab px-4 py-2 whitespace-nowrap <?= $category === $cat['category'] ? 'active' : '' ?>">
                <?= htmlspecialchars(ucfirst($cat['category'])) ?>
              </a>
            <?php endwhile; ?>
          </div>

          <!-- Menu Grid -->
          <div class="menu-grid grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-1 gap-2" id="menu-items-grid">
            <?php if ($result_menu->num_rows === 0): ?>
              <div class="col-span-full text-center py-10">
                <i data-lucide="frown" class="w-12 h-12 mx-auto text-gray-400"></i>
                <p class="text-gray-500 mt-2">No menu items found matching your criteria.</p>
                <a href="?" class="btn btn-ghost mt-4">Reset Filters</a>
              </div>
            <?php endif; ?>

            <?php while ($row = $result_menu->fetch_assoc()): ?>
              <div class="menu-item-card relative rounded-xl border p-5 shadow-sm transition-all duration-300 hover:shadow-md border-gray-200 bg-white flex flex-col h-full"
                   data-category="<?= htmlspecialchars($row['category']) ?>">

             
              

                <!-- Name and ID -->
                <div class="flex items-start justify-between gap-4 mb-3">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="p-2 rounded-lg bg-gray-100 text-gray-600">
                      <i data-lucide="utensils" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                      <h3 class="text-lg font-semibold text-gray-800 truncate"><?= htmlspecialchars($row['name']) ?></h3>
                      <p class="text-xs text-gray-500 mt-0.5 truncate">ID: <?= $row['menu_id'] ?></p>
                    </div>
                  </div>
                  <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 font-medium">
                    <?= htmlspecialchars($row['status']) ?>
                  </span>
                </div>

                <!-- Price and Category -->
                <div class="grid grid-cols-2 gap-4 mt-2">
                  <div class="text-sm text-gray-600 min-w-0">
                    <p class="text-xs text-gray-500 truncate">Price</p>
                    <p class="font-medium truncate">₱ <?= number_format($row['price'], 2) ?></p>
                  </div>
                  <div class="text-sm text-gray-600 min-w-0">
                    <p class="text-xs text-gray-500 truncate">Category</p>
                    <p class="font-medium truncate"><?= htmlspecialchars($row['category']) ?></p>
                  </div>
                </div>

                <!-- Description -->
                <p class="text-sm text-gray-600 mt-3 line-clamp-2"><?= htmlspecialchars($row['description']) ?></p>

                <!-- Footer -->
                <div class="mt-auto pt-3 border-t border-gray-200/50 flex justify-between items-center">
       <button
    type="button"
    onclick="addToOrder(<?= (int)$row['menu_id'] ?>, `<?= addslashes($row['name']) ?>`, <?= $row['price'] ?>)"
    class="bg-[#F7B32B] text-[#001f54] hover:bg-[#e09c22] btn btn-sm btn-primary">
    Add Order
</button>

                </div>
              </div>
            <?php endwhile; ?>
          </div>

          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <div class="flex justify-center mt-8">
              <div class="join pagination">
                <?php if ($currentPage > 1): ?>
                  <a href="?page=<?= $currentPage - 1 ?>&category=<?= urlencode($category) ?>&search=<?= urlencode($search) ?>" class="join-item btn">Previous</a>
                <?php endif; ?>

                <?php 
                  $startPage = max(1, $currentPage - 2);
                  $endPage = min($totalPages, $currentPage + 2);

                  if ($startPage > 1): ?>
                    <a href="?page=1&category=<?= urlencode($category) ?>&search=<?= urlencode($search) ?>" 
                       class="join-item btn <?= 1 === $currentPage ? 'active' : '' ?>">1</a>
                    <?php if ($startPage > 2): ?>
                      <span class="join-item btn btn-disabled">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                  <a href="?page=<?= $i ?>&category=<?= urlencode($category) ?>&search=<?= urlencode($search) ?>" 
                     class="join-item btn <?= $i === $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                  <?php if ($endPage < $totalPages - 1): ?>
                    <span class="join-item btn btn-disabled">...</span>
                  <?php endif; ?>
                  <a href="?page=<?= $totalPages ?>&category=<?= urlencode($category) ?>&search=<?= urlencode($search) ?>" 
                     class="join-item btn <?= $totalPages === $currentPage ? 'active' : '' ?>"><?= $totalPages ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                  <a href="?page=<?= $currentPage + 1 ?>&category=<?= urlencode($category) ?>&search=<?= urlencode($search) ?>" class="join-item btn">Next</a>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Order Summary Section -->
        <form action="sub-modules/POS_ordering_form.php" method="POST" id="pos-order-form" class="bg-white rounded-xl shadow-sm p-6 floating-cart">
          <!-- Header -->
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">Order Summary</h2>
            <div class="badge badge-primary" id="current-table-badge">No Table Selected</div>
          </div>

          <!-- Hidden Inputs -->
          <input type="hidden" name="table_id" id="table-id-input">
          <input type="hidden" name="order_code" value="<?= uniqid('ORD-') ?>">
          <input type="hidden" name="total_amount" id="total-amount-input">
          <input type="hidden" name="MOP" id="mop-input" value="cash">
          <input type="hidden" name="order_type" value="dine-in">

 <!-- Table Selection -->
<div class="mb-6">
  <h3 class="font-medium text-[#001f54] mb-2">Select Table</h3>
  
  <!-- Trigger Button -->
  <button type="button" 
          class="flex items-center gap-2 px-4 py-2 rounded-lg shadow-md font-medium
                 bg-[#F7B32B] text-[#001f54] hover:bg-[#e09c22] transition-all"
          onclick="document.getElementById('table-modal').showModal()">
    <i data-lucide="utensils-crossed" class="w-5 h-5"></i>
    Choose Table
  </button>
</div>





          <!-- Customer Name -->
          <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
              <h3 class="font-medium text-gray-700">Customer Name</h3>
            </div>
            <div class="relative">
              <i data-lucide="user" class="absolute left-3 top-3.5 text-gray-400 w-5 h-5"></i>
              <input type="text" 
                     name="customer_name" 
                     id="customer-name-input" 
                     class="bg-black text-white input input-bordered w-full pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                     placeholder="Enter customer's name" required />
            </div>
          </div>

          <!-- Order Items -->
          <div class="mb-4">
            <h3 class="font-medium text-gray-700 mb-3">Items (<span id="item-count">0</span>)</h3>
            <div class="space-y-3 max-h-64 overflow-y-auto pr-2" id="order-items-container">
              <div class="text-center py-8 text-gray-400" id="empty-cart-message">
                <i data-lucide="shopping-cart" class="w-8 h-8 mx-auto"></i>
                <p class="mt-2">Your cart is empty</p>
                <p class="text-sm">Add items from the menu</p>
              </div>
            </div>
          </div>

          <!-- Order Notes -->
          <div class="mb-6">
            <label class="label">
              <span class="label-text text-gray-700">Notes</span>
            </label>
            <textarea class="textarea textarea-bordered w-full bg-white" 
                      placeholder="Special requests, allergies, etc." 
                      id="order-notes" name="notes"></textarea>
          </div>

          <!-- Order Summary -->
          <div class="space-y-3 mb-6" id="order-summary">
            <div class="flex justify-between">
              <span class="text-gray-600">Subtotal</span>
              <span class="font-medium" id="subtotal">₱ 0.00</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Service Charge (2%)</span>
              <span class="font-medium" id="service-charge">₱ 0.00</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">VAT (12%)</span>
              <span class="font-medium" id="tax">₱ 0.00</span>
            </div>
            <div class="border-t pt-3 flex justify-between">
              <span class="text-lg font-bold">Total</span>
              <span class="text-lg font-bold text-blue-600" id="total">₱ 0.00</span>
            </div>
          </div>

       <!-- Payment Options -->
<div class="mb-6">
  <h3 class="font-medium text-[#001f54] mb-2">Payment Method</h3>
  <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" id="payment-methods">
    
    <!-- Cash -->
    <button type="button" 
            class="btn btn-outline btn-sm payment-method active flex items-center justify-center gap-2" 
            data-method="cash">
      <i data-lucide="wallet" class="w-4 h-4 text-[#F7B32B]"></i> Cash
    </button>

   <!-- GCash -->
<button type="button" 
        class="btn btn-outline btn-sm payment-method flex items-center justify-center gap-2" 
        data-method="gcash">
  <img src="../images/Gcash.png" alt="GCash" 
       class="h-6 w-6 object-contain">
  GCash
</button>

<!-- Maya -->
<button type="button" 
        class="btn btn-outline btn-sm payment-method flex items-center justify-center gap-2" 
        data-method="maya">
  <img src="../images/Maya.png" alt="Maya" 
       class="h-6 w-6 object-contain">
  Maya
</button>


    <!-- Debit Card -->
    <button type="button" 
            class="btn btn-outline btn-sm payment-method flex items-center justify-center gap-2" 
            data-method="debit">
      <i data-lucide="credit-card" class="w-4 h-4 text-[#F7B32B]"></i> Debit
    </button>

    <!-- Credit Card -->
    <button type="button" 
            class="btn btn-outline btn-sm payment-method flex items-center justify-center gap-2" 
            data-method="credit">
      <i data-lucide="credit-card" class="w-4 h-4 text-[#F7B32B]"></i> Credit
    </button>

  </div>
</div>


          <!-- Action Buttons -->
          <div class="space-y-2">
            <button type="submit" class="btn btn-primary w-full" id="submit-order-btn">
              <i data-lucide="send" class="w-4 h-4 mr-2"></i> Send to Kitchen
            </button>
          
            <button type="reset" class="btn btn-ghost text-red-500 w-full" id="clear-order-btn">
              <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i> Clear Order
            </button>
          </div>

          <input type="hidden" name="order_items_json" id="order-items-json">

        </form>


      </main>
    </div>
  </div>

  <!-- Add this modal HTML at the bottom of your page -->
<dialog id="receipt-modal" class="modal">
  <form method="dialog" class="modal-box max-w-lg w-full p-6">
    <h3 class="font-bold text-lg mb-4">Order Receipt</h3>
    <div id="receipt-content" class="overflow-auto max-h-96 text-sm"></div>
    <div class="modal-action mt-4">
      <button type="button" class="btn btn-primary" id="print-receipt-btn">Print Receipt</button>
      <button type="submit" class="btn">Close</button>
    </div>
  </form>
</dialog>

<!-- Table Selection Modal -->
<dialog id="table-modal" class="modal">
  <div class="modal-box max-w-3xl bg-white rounded-xl shadow-lg p-6">
    
    <!-- Header -->
    <div class="flex justify-between items-center border-b border-gray-200 pb-3">
      <h3 class="text-xl font-bold text-[#001f54]">Select a Table</h3>
      <button class="btn btn-sm btn-circle btn-ghost hover:bg-gray-100"
              onclick="document.getElementById('table-modal').close()">
        <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
      </button>
    </div>

    <!-- Table Grid -->
    <div class="my-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 gap-3" id="table-selection-grid">
        <?php 
          $result_tables->data_seek(0);
          while ($table = $result_tables->fetch_assoc()):
        ?>
          <button class="rounded-xl p-4 shadow-md flex flex-col justify-center items-center text-center transition-all duration-200
                         <?php if($table['status'] == 'occupied'): ?>
                           bg-gray-300 text-gray-500 cursor-not-allowed
                         <?php else: ?>
                           bg-[#001f54] text-white hover:bg-[#F7B32B] hover:text-[#001f54]
                         <?php endif; ?>"
                  onclick="selectTable(<?= $table['table_id'] ?>, '<?= htmlspecialchars($table['name']) ?>', <?= $table['capacity'] ?>)"
                  <?= $table['status'] == 'occupied' ? 'disabled' : '' ?>>
            <span class="font-semibold"><?= htmlspecialchars($table['name']) ?></span>
            <span class="text-xs">Seats: <?= $table['capacity'] ?></span>
            <span class="text-xs mt-1"><?= $table['status'] == 'occupied' ? 'Occupied' : 'Available' ?></span>
          </button>
        <?php endwhile; ?>
      </div>
    </div>

    <!-- Actions -->
    <div class="modal-action">
      <button class="px-5 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition"
              onclick="document.getElementById('table-modal').close()">
        Cancel
      </button>
    </div>
  </div>
</dialog>

  <!-- Customer Selection Modal -->
  <dialog id="customer-modal" class="modal">
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg">Select Customer</h3>
      <div class="my-4">
        <div class="flex gap-2 mb-4">
          <input type="text" placeholder="Search customers..." class="input input-bordered flex-1" id="customer-search">
          <button class="btn btn-primary">Search</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="customer-list">
          <!-- Customer options will be inserted here -->
          <div class="customer-option border rounded-lg p-4 cursor-pointer hover:bg-gray-50" 
               onclick="selectCustomer('Walk-in', 'No special requests')">
            <h4 class="font-medium">Walk-in Customer</h4>
            <p class="text-sm text-gray-500">No account</p>
          </div>
          <div class="customer-option border rounded-lg p-4 cursor-pointer hover:bg-gray-50"
               onclick="selectCustomer('John Doe', 'VIP, prefers window seat')">
            <h4 class="font-medium">John Doe</h4>
            <p class="text-sm text-gray-500">VIP Member</p>
          </div>
        </div>
      </div>
      <div class="modal-action">
        <button class="btn" onclick="document.getElementById('customer-modal').close()">Cancel</button>
      </div>
    </div>
  </dialog>

<!-- Table Selection Modal -->
<dialog id="table-modal" class="modal">
  <div class="modal-box max-w-3xl">
    <h3 class="font-bold text-lg text-[#001f54] flex items-center gap-2">
      <i data-lucide="utensils" class="w-5 h-5 text-[#F7B32B]"></i>
      Select Table
    </h3>

    <div class="my-6">
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3" id="table-selection-grid">
        <?php 
          $result_tables->data_seek(0);
          while ($table = $result_tables->fetch_assoc()):
            $isOccupied = $table['status'] == 'occupied';
        ?>
          <button type="button"
                  class="rounded-xl shadow-md p-4 h-24 flex flex-col justify-center items-center transition-all duration-200
                         <?php if($isOccupied): ?>
                           bg-gray-300 text-gray-500 cursor-not-allowed
                         <?php else: ?>
                           bg-[#001f54] text-white hover:bg-[#F7B32B] hover:text-[#001f54]
                         <?php endif; ?>"
    onclick="selectTable(<?= $table['table_id'] ?>, '<?= htmlspecialchars($table['name']) ?>', <?= $table['capacity'] ?>)"
                  <?= $isOccupied ? 'disabled' : '' ?>>
            
            <span class="font-semibold"><?= htmlspecialchars($table['name']) ?></span>
            <span class="text-xs mt-1">Seats: <?= $table['capacity'] ?></span>
            <span class="text-xs mt-1 flex items-center gap-1">
              <?php if($isOccupied): ?>
                <i data-lucide="lock" class="w-3 h-3"></i> Occupied
              <?php else: ?>
                <i data-lucide="check-circle" class="w-3 h-3"></i> Available
              <?php endif; ?>
            </span>
          </button>
        <?php endwhile; ?>
      </div>
    </div>

    <div class="modal-action">
      <button class="btn" onclick="document.getElementById('table-modal').close()">Cancel</button>
    </div>
  </div>
</dialog>



<!-- Menu Item Details Modal -->
<div id="menu-details-modal" class="modal">
  <div class="modal-box max-w-2xl bg-white rounded-xl shadow-lg p-6">

    <!-- Header -->
    <div class="flex justify-between items-center border-b border-gray-200 pb-3">
      <h3 class="text-xl font-bold text-[#001f54]" id="menu-item-name">Item Name</h3>
      <button class="btn btn-sm btn-circle btn-ghost hover:bg-gray-100" onclick="closeMenuModal()">
        <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
      </button>
    </div>

    <!-- Content -->
    <div class="grid grid-cols-1 md:grid-cols-1 gap-6 mt-5">
      <!-- Info -->
      <div class="flex flex-col justify-between">

        <!-- Item Info -->
        <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
          <div>
            <p class="text-gray-500">Price</p>
            <p class="font-semibold text-[#001f54]" id="menu-item-price">₱ 0.00</p>
          </div>
          <div>
            <p class="text-gray-500">Category</p>
            <p class="font-semibold text-[#001f54]" id="menu-item-category">Category</p>
          </div>
          <div>
            <p class="text-gray-500">Status</p>
            <p class="font-semibold text-green-600" id="menu-item-status">Available</p>
          </div>
          <div>
            <p class="text-gray-500">Last Updated</p>
            <p class="font-semibold text-[#001f54]" id="menu-item-updated">Date</p>
          </div>
        </div>

        <!-- Description -->
        <div class="mb-4">
          <p class="text-gray-500 text-sm">Description</p>
          <p class="font-medium text-gray-800" id="menu-item-description">Description</p>
        </div>

        <!-- Quantity + Action -->
        <div class="flex items-center justify-between gap-4">
          <!-- Quantity Control -->
          <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden shadow-sm">
            <button class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-lg font-bold text-gray-700"
              onclick="adjustQuantity(-1)">-</button>
            <input type="number" class="w-12 text-center border-x border-gray-300 focus:outline-none" 
                   value="1" min="1" id="item-quantity">
            <button class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-lg font-bold text-gray-700"
              onclick="adjustQuantity(1)">+</button>
          </div>

          <!-- Add to Order Button -->
          <button class="flex items-center gap-2 px-5 py-2 font-semibold text-white rounded-lg shadow-md hover:shadow-lg transition-all"
                  style="background:#F7B32B;"
                  onmouseover="this.style.background='#e09c22'"
                  onmouseout="this.style.background='#F7B32B'"
                  id="add-to-order-from-modal">
            <i data-lucide="shopping-cart" class="w-5 h-5"></i>
            Add to Order
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const orderForm = document.getElementById("pos-order-form");
    const orderItemsContainer = document.getElementById("order-items-container");
    const orderItemsJsonInput = document.getElementById("order-items-json");
    const totalAmountInput = document.getElementById("total-amount-input");
    const subtotalEl = document.getElementById("subtotal");
    const serviceChargeEl = document.getElementById("service-charge");
    const taxEl = document.getElementById("tax");
    const totalEl = document.getElementById("total");
    const itemCountEl = document.getElementById("item-count");
    const mopInput = document.getElementById("mop-input");

    // Global order array
    // window.orderItems = [];

    // ========================
    // Add item to order (used by menu grid & modal)
    // ========================
    // window.addToOrder = function(id, name, price, quantity = 1) {
    //     const existingIndex = window.orderItems.findIndex(i => i.id === id);
    //     if (existingIndex > -1) {
    //         window.orderItems[existingIndex].quantity += quantity;
    //     } else {
    //         window.orderItems.push({id, name, price, quantity});
    //     }
    //     renderOrderItems();
    // };

    // ========================
    // Render cart
    // ========================
    // function renderOrderItems() {
    //     orderItemsJsonInput.value = JSON.stringify(window.orderItems);
    //     orderItemsContainer.innerHTML = "";

    //     if (window.orderItems.length === 0) {
    //         document.getElementById("empty-cart-message").classList.remove("hidden");
    //     } else {
    //         document.getElementById("empty-cart-message").classList.add("hidden");
    //         window.orderItems.forEach(item => {
    //             const div = document.createElement("div");
    //             div.className = "flex justify-between items-center p-2 border-b border-gray-200";
    //             div.innerHTML = `
    //                 <div>
    //                     <span class="font-semibold">${item.name}</span> x ${item.quantity}
    //                 </div>
    //                 <div>₱ ${(item.price * item.quantity).toFixed(2)}</div>
    //             `;
    //             orderItemsContainer.appendChild(div);
    //         });
    //     }

    //     updateTotals();
    // }

    // // ========================
    // // Update totals
    // // ========================
    // function updateTotals() {
    //     let subtotal = window.orderItems.reduce((sum, i) => sum + i.price * i.quantity, 0);
    //     let serviceCharge = subtotal * 0.02;
    //     let tax = subtotal * 0.12;
    //     let total = subtotal + serviceCharge + tax;

    //     subtotalEl.textContent = `₱ ${subtotal.toFixed(2)}`;
    //     serviceChargeEl.textContent = `₱ ${serviceCharge.toFixed(2)}`;
    //     taxEl.textContent = `₱ ${tax.toFixed(2)}`;
    //     totalEl.textContent = `₱ ${total.toFixed(2)}`;
    //     totalAmountInput.value = total.toFixed(2);
    //     itemCountEl.textContent = window.orderItems.length;
    // }

    // ========================
    // Table selection
    // ========================
    // window.selectTable = (tableId, tableName) => {
    //     document.getElementById("table-id-input").value = tableId;
    //     document.getElementById("current-table-badge").textContent = tableName;
    //     document.getElementById("table-modal").close();
    // };

    // ========================
    // Payment method selection
    // ========================
    // const paymentButtons = document.querySelectorAll(".payment-method");
    // paymentButtons.forEach(btn => {
    //     btn.addEventListener("click", () => {
    //         paymentButtons.forEach(b => b.classList.remove("active"));
    //         btn.classList.add("active");
    //         mopInput.value = btn.dataset.method;
    //     });
    // });

    // ========================
    // Submit order
    // ========================
    // orderForm.addEventListener("submit", (e) => {
    //     e.preventDefault();

    //     if (!document.getElementById("table-id-input").value) {
    //         alert("Please select a table first!");
    //         return;
    //     }
    //     if (window.orderItems.length === 0) {
    //         alert("Please add items to the order!");
    //         return;
    //     }

    //     fetch(orderForm.action, { method: "POST", body: new FormData(orderForm) })
    //     .then(res => res.json())
    //     .then(data => {
    //         if (data.status === "success") {
    //             alert("Order sent to kitchen!");
    //             orderForm.reset();
    //             window.orderItems = [];
    //             renderOrderItems();
    //             document.getElementById("current-table-badge").textContent = "No Table Selected";
    //         } else {
    //             alert("Error: " + data.message);
    //         }
    //     })
    //     .catch(err => { console.error(err); alert("Something went wrong!"); });
    // });

    // ========================
    // Open menu modal
    // ========================
    // window.openMenuModal = function(item) {
    //     const modal = document.getElementById('menu-details-modal');
    //     modal.querySelector('#menu-item-name').textContent = item.name;
    //     modal.querySelector('#menu-item-price').textContent = `₱ ${item.price.toFixed(2)}`;
    //     modal.querySelector('#menu-item-category').textContent = item.category;
    //     modal.querySelector('#menu-item-status').textContent = item.status;
    //     modal.querySelector('#menu-item-description').textContent = item.description;
    //     modal.querySelector('#item-quantity').value = 1;

    //     // Set data attributes for Add-to-Order button
    //     const addBtn = document.getElementById('add-to-order-from-modal');
    //     addBtn.dataset.menuId = item.id;
    //     addBtn.dataset.name = item.name;
    //     addBtn.dataset.price = item.price;

    //     modal.showModal();
    // };

    // ========================
    // Add-to-order from modal
    // ========================
    // document.getElementById('add-to-order-from-modal').addEventListener('click', function() {
    //     const menuId = parseInt(this.dataset.menuId);
    //     const name = this.dataset.name;
    //     const price = parseFloat(this.dataset.price);
    //     const quantity = parseInt(document.getElementById('item-quantity').value);

    //     addToOrder(menuId, name, price, quantity);
    //     document.getElementById('menu-details-modal').close();
    // });

    // ========================
    // Modal quantity adjust
    // ========================
    // window.adjustQuantity = function(delta) {
    //     const input = document.getElementById('item-quantity');
    //     let value = parseInt(input.value) || 1;
    //     value = Math.max(1, value + delta);
    //     input.value = value;
    // };
});

</script>

<script src="../JavaScript/sidebar.js"></script>
<script src="../JavaScript/soliera.js"></script>
<script src="../JavaScript/M4_JS/POS.js"></script>


</body>
</html>