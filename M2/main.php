<?php

session_start();

include("../main_connection.php");

$db_name = "rest_m2_inventory"; // ✅ pick the DB you want

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name]; // ✅ get the correct DB connection


$sql = "SELECT item_id, item_name, category, quantity, critical_level, created_at, updated_at, unit_price, notes, request_status, expiry_date, last_restock_date, location
        FROM inventory_and_stock";

$result_sql = $conn->query($sql);
if (!$result_sql) {
  die("SQL Error: " . $conn->error);
}


$query = "SELECT 
        (SELECT COUNT(*) FROM inventory_and_stock) AS total_items_count,
        (SELECT COUNT(*) FROM inventory_and_stock WHERE critical_level = 'In_stock') AS In_stock,
        (SELECT COUNT(*) FROM inventory_and_stock WHERE critical_level = 'low_stock') AS low,
        (SELECT COUNT(*) FROM inventory_and_stock WHERE critical_level = 'out_of_stock') AS out_of_stock,
        (SELECT COUNT(*) FROM inventory_and_stock WHERE critical_level = 'expire') AS expire,
        (SELECT COUNT(*) FROM inventory_and_stock WHERE critical_level = 'recently_added') AS recently_added

";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Count query failed: " . mysqli_error($conn));
}

// Fetch the counts
$row = mysqli_fetch_assoc($result);
$total_items_count = $row['total_items_count'];
$in_stock_count = $row['In_stock'];   
$low_stock_count = $row['low'];
$out_of_stock_count = $row['out_of_stock'];
$expired_count = $row['expire'];
$recently_added_count = $row['recently_added'];


// Query to fetch all reservations
$query = "SELECT * FROM `inventory_and_stock`";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Fetch query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
        <?php include '../header.php'; ?>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Inventory Management</title>
    
 

    <style>
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .stock-low {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        
        .stock-out {
            animation: pulse-red 2s infinite;
        }
        
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        /* Dropdown menu styling */
        .dropdown-content {
            display: none;
            position: absolute;
            z-index: 50;
        }

        .dropdown:hover .dropdown-content,
        .dropdown:focus-within .dropdown-content {
            display: block;
        }

        /* Active filter state */
        #stock-filter a.active {
            background-color: #3b82f6;
            color: white;
        }

        /* Glass effect for the section */
        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        /* Loading spinner styles */
.loading {
    display: inline-block;
    border: 2px solid #f3f3f3;
    border-radius: 50%;
    border-top: 2px solid #3498db;
    width: 20px;
    height: 20px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Alert styles */
.alert {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}

.alert-error {
    background-color: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.alert-success {
    background-color: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
    </style>
</head>
<body class="bg-base-100 min-h-screen bg-white">

  <div class="flex h-screen">
    <!-- Sidebar -->
    <div class="bg-[#1A2C5B] border-r border-blue-600 pt-5 pb-4 flex flex-col w-64 transition-all duration-300 ease-in-out shadow-xl relative overflow-hidden h-screen" id="sidebar">
        <!-- Sidebar content -->
        <?php include '../sidebarr.php'; ?>
    </div>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-4 md:p-6">
         <!-- Inventory Summary Cards -->
<section class="glass-effect p-6 rounded-2xl shadow-xl border border-gray-100/30 backdrop-blur-sm bg-white/70 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h2 class="text-2xl font-bold flex items-center" style="color:#F7B32B;">
        <span class="p-2 mr-3 rounded-lg" style="background:#F7B32B; color:#001f54;">
            <i data-lucide="package" class="w-5 h-5"></i>
        </span>
        Inventory Overview
    </h2>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 h-full">

    <!-- Total Items Card -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Total Items</p>
                <h3 class="text-3xl font-bold text-black mt-1"><?= $total_items_count ?? '0' ?></h3>
                <p class="text-xs text-gray-500 mt-1">Inventory count</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i data-lucide="boxes" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                <div class="h-full" style="width:100%; background:#F7B32B;"></div>
            </div>
            <div class="flex justify-between text-xs mt-1 text-gray-600">
                <span>Inventory</span>
                <span class="font-medium"><?= $total_items_count ?? '0' ?> items</span>
            </div>
        </div>
    </div>

    <!-- In Stock Card -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">In Stock</p>
                <h3 class="text-3xl font-bold text-black mt-1"><?= $in_stock_count ?? '0' ?></h3>
                <p class="text-xs text-gray-500 mt-1">Currently available</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i data-lucide="check-circle" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                <div class="h-full" style="width: <?= isset($in_stock_count) && $total_items_count ? ($in_stock_count/$total_items_count)*100 : 0 ?>%; background:#F7B32B;"></div>
            </div>
            <div class="flex justify-between text-xs mt-1 text-gray-600">
                <span>Availability</span>
                <span class="font-medium"><?= isset($in_stock_count) && $total_items_count ? round(($in_stock_count/$total_items_count)*100) : '0' ?>%</span>
            </div>
        </div>
    </div>

    <!-- Low Stock Card -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Low Stock</p>
                <h3 class="text-3xl font-bold text-black mt-1"><?= $low_stock_count ?? '0' ?></h3>
                <p class="text-xs text-gray-500 mt-1">Needs attention</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i data-lucide="alert-triangle" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                <div class="h-full" style="width: <?= isset($low_stock_count) && $total_items_count ? ($low_stock_count/$total_items_count)*100 : 0 ?>%; background:#F7B32B;"></div>
            </div>
            <div class="flex justify-between text-xs mt-1 text-gray-600">
                <span>Low Stock</span>
                <span class="font-medium"><?= isset($low_stock_count) && $total_items_count ? round(($low_stock_count/$total_items_count)*100) : '0' ?>%</span>
            </div>
        </div>
    </div>

    <!-- Out of Stock Card -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Out of Stock</p>
                <h3 class="text-3xl font-bold text-black mt-1"><?= $out_of_stock_count ?? '0' ?></h3>
                <p class="text-xs text-gray-500 mt-1">Urgent restock</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i data-lucide="x-octagon" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                <div class="h-full" style="width: <?= isset($out_of_stock_count) && $total_items_count ? ($out_of_stock_count/$total_items_count)*100 : 0 ?>%; background:#F7B32B;"></div>
            </div>
            <div class="flex justify-between text-xs mt-1 text-gray-600">
                <span>Out of Stock</span>
                <span class="font-medium"><?= isset($out_of_stock_count) && $total_items_count ? round(($out_of_stock_count/$total_items_count)*100) : '0' ?>%</span>
            </div>
        </div>
    </div>

    <!-- Expired Items Card -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Expired</p>
                <h3 class="text-3xl font-bold text-black mt-1"><?= $expired_count ?? '0' ?></h3>
                <p class="text-xs text-gray-500 mt-1">Needs disposal</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i data-lucide="calendar-x" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                <div class="h-full" style="width: <?= isset($expired_count) && $total_items_count ? ($expired_count/$total_items_count)*100 : 0 ?>%; background:#F7B32B;"></div>
            </div>
            <div class="flex justify-between text-xs mt-1 text-gray-600">
                <span>Expired</span>
                <span class="font-medium"><?= isset($expired_count) && $total_items_count ? round(($expired_count/$total_items_count)*100) : '0' ?>%</span>
            </div>
        </div>
    </div>

    <!-- Recently Added Card -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Recently Added</p>
                <h3 class="text-3xl font-bold text-black mt-1"><?= $recently_added_count ?? '0' ?></h3>
                <p class="text-xs text-gray-500 mt-1">This week</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i data-lucide="package-plus" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                <div class="h-full" style="width: <?= isset($recently_added_count) && $total_items_count ? ($recently_added_count/$total_items_count)*100 : 0 ?>%; background:#F7B32B;"></div>
            </div>
            <div class="flex justify-between text-xs mt-1 text-gray-600">
                <span>Increase</span>
                <span class="font-medium"><?= isset($recently_added_count) && $total_items_count ? round(($recently_added_count/$total_items_count)*100) : '0' ?>%</span>
            </div>
        </div>
    </div>

</div>



</section>

           <!-- Stock Management Section -->
<section class="glass-effect p-6 rounded-xl shadow-sm mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h2 class="text-xl font-bold flex items-center gap-2 text-gray-800">
            <i data-lucide="package" class="w-5 h-5 text-blue-500"></i>
            <span>Stock Items</span>
        </h2>
        <div class="flex flex-wrap gap-3 w-full md:w-auto">
           <!-- Add Stock Button -->
<label for="add-stock-modal" 
       class="btn px-4 py-2 rounded-xl flex items-center gap-2 shadow-md hover:shadow-lg cursor-pointer text-sm
              bg-[#001f54] text-[#F7B32B] hover:bg-[#F7B32B] hover:text-[#001f54] border border-[#F7B32B]">
    <i data-lucide="plus" class="w-4 h-4"></i>
    <span>Request new stock</span>
</label>

            
            <!-- Delete Selected Button -->
            <button id="delete-selected-btn" class="btn btn-error px-4 py-2 rounded-xl flex items-center gap-2 shadow-md hover:shadow-lg text-sm hidden">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>Delete Selected</span>
            </button>
            
            <!-- Filter Dropdown -->
            <div class="dropdown dropdown-end">
                <button class="btn btn-outline border-gray-300 text-gray-700 hover:bg-gray-100 px-4 py-2 rounded-xl flex items-center gap-2 text-sm">
                    <i data-lucide="filter" class="w-4 h-4"></i> 
                    <span>Filter</span>
                </button>
                <ul id="stock-filter" class="dropdown-content menu p-2 shadow bg-white text-black rounded-box w-52 mt-2 border border-gray-200">
                    <li><a href="#" data-filter="all">All Stock</a></li>
                    <li><a href="#" data-filter="food">Food Items</a></li>
                    <li><a href="#" data-filter="beverage">Beverages</a></li>
                    <li><a href="#" data-filter="supplies">Supplies</a></li>
                </ul>
            </div>
        </div>
    </div>
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-2 gap-4 p-4 rounded-lg" id="stock-grid">
    <!-- Stock cards will be dynamically rendered here by JS -->
</div>

</section>

<!-- Delete Confirmation Modal -->
<input type="checkbox" id="delete-confirm-modal" class="modal-toggle" />
<div class="modal">
    <div class="modal-box relative text-black rounded-lg">
        <!-- Close Button -->
        <label for="delete-confirm-modal" 
               class="btn btn-sm btn-circle absolute right-2 top-2 text-[#001f54] border-[#F7B32B] hover:bg-[#F7B32B]/20 hover:text-[#001f54]">
            ✕
        </label>

        <!-- Modal Title & Message -->
        <h3 class="text-lg font-bold text-[#001f54]">Confirm Deletion</h3>
        <p class="py-4 text-gray-700" id="delete-confirm-message">
            Are you sure you want to delete the selected items?
        </p>

        <!-- Footer Actions -->
        <div class="modal-action">
            <label for="delete-confirm-modal"
                   class="btn btn-outline border-[#F7B32B] text-[#F7B32B] hover:bg-[#F7B32B]/10 hover:text-[#001f54]">
                Cancel
            </label>
            <button id="confirm-delete-btn" 
                    class="btn bg-[#001f54] hover:bg-[#001a48] text-[#F7B32B]">
                Delete
            </button>
        </div>
    </div>
</div>

        </main>
    </div>
</div>

<!-- Add Stock Modal -->
<input type="checkbox" id="add-stock-modal" class="modal-toggle" />
<div class="modal modal-bottom sm:modal-middle">
  <div class="bg-white modal-box max-w-2xl p-6 rounded-lg shadow-2xl">
 <!-- Header -->
<div class="flex justify-between items-center mb-5">
  <h3 class="text-2xl font-bold text-[#001f54] flex items-center gap-2">
    <i data-lucide="package-plus" class="w-5 h-5 text-[#F7B32B]"></i>
    <span>Request New Stock</span>
  </h3>
  <label for="add-stock-modal" 
         class="btn btn-circle btn-ghost btn-sm text-[#001f54] hover:text-[#F7B32B] border-[#F7B32B]/30 hover:border-[#F7B32B]">
    <i data-lucide="x" class="w-5 h-5"></i>
  </label>
</div>


    <!-- Scrollable Form Body -->
    <div class="overflow-y-auto max-h-[70vh] 
                [scrollbar-width:none] 
                [-ms-overflow-style:none] 
                [&::-webkit-scrollbar]:hidden">

      <!-- Add Stock Form -->
      <form id="add-stock-form" action="sub-modules/add_stock_request.php" method="POST" class="space-y-4" autocomplete="off">
        
        <!-- Item Name -->
        <div class="form-control">
          <label class="label">
            <span class="label-text text-gray-700 font-medium">Item Name</span>
          </label>
          <input type="text" name="item_name" required placeholder="Enter item name"
            class="input input-bordered bg-white w-full" />
        </div>

        <!-- Category -->
        <div class="form-control">
          <label class="label">
            <span class="label-text text-gray-700 font-medium">Category</span>
          </label>
          <select name="category" required
            class="select select-bordered bg-white w-full">
            <option value="" disabled selected>Select Category</option>
            <option value="food">Food Items</option>
            <option value="beverage">Beverages</option>
            <option value="supplies">Supplies</option>
            <option value="other">Other</option>
          </select>
        </div>

        <!-- Location -->
        <div class="form-control">
          <label class="label">
            <span class="label-text text-gray-700 font-medium">Location</span>
          </label>
          <input type="text" name="location" required placeholder="Enter storage or branch location"
            class="input input-bordered bg-white w-full" />
        </div>

        <!-- Quantity & Critical Level -->
        <div class="grid grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text text-gray-700 font-medium">Quantity</span>
            </label>
            <input type="number" name="quantity" min="1" required placeholder="0"
              class="input input-bordered bg-white w-full" />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text text-gray-700 font-medium">Critical Level</span>
            </label>
            <input type="number" name="critical_level" min="1" required placeholder="1"
              class="input input-bordered bg-white w-full" />
          </div>
        </div>

        <!-- Unit Price & Expiry Date -->
        <div class="grid grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text text-gray-700 font-medium">Unit Price (₱)</span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
              <input type="number" name="unit_price" min="0" step="0.01" required placeholder="0.00"
                class="input input-bordered bg-white w-full pl-8" />
            </div>
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text text-gray-700 font-medium">Expiry Date</span>
            </label>
            <input type="date" name="expiry_date" class="input input-bordered bg-white w-full" />
          </div>
        </div>

        <!-- Last Restock Date -->
        <div class="form-control">
          <label class="label">
            <span class="label-text text-gray-700 font-medium">Last Restock Date</span>
          </label>
          <input type="date" name="last_restock_date" class="input input-bordered bg-white w-full" />
        </div>

        <!-- Notes -->
        <div class="form-control">
          <label class="label">
            <span class="label-text text-gray-700 font-medium">Notes (Optional)</span>
          </label>
          <textarea name="notes" rows="2" placeholder="Any additional information..."
            class="textarea textarea-bordered bg-white w-full"></textarea>
        </div>

        <!-- Hidden request_status -->
        <input type="hidden" name="request_status" value="pending" />

       <!-- Footer Actions -->
<div class="modal-action mt-6 flex justify-end gap-3">
  <label for="add-stock-modal"
    class="btn btn-outline border-[#F7B32B] text-[#F7B32B] hover:bg-[#F7B32B]/10 hover:border-[#F7B32B] hover:text-[#001f54]">
    Cancel
  </label>
  <button type="submit" class="btn bg-[#001f54] hover:bg-[#001a48] text-[#F7B32B] flex items-center">
    <i data-lucide="check" class="w-4 h-4 mr-1"></i>
    Add Stock
  </button>
</div>

      </form>
    </div>
  </div>
</div>


<!-- Stock Details Modal -->
<input type="checkbox" id="stock-details-modal" class="modal-toggle" />
<div class="modal">
  <div class="modal-box relative max-w-2xl">
    <label for="stock-details-modal" class="btn btn-sm btn-circle absolute right-2 top-2">✕</label>
    <h3 class="text-lg font-bold mb-4">Stock Details</h3>
    <div id="stock-details-content" class="space-y-4">
      <!-- Details will be loaded here -->
    </div>
  </div>
</div>

<script src="../JavaScript/sidebar.js"></script>
<script src="../JavaScript/soliera.js"></script>
<script src="../JavaScript/M2_JS/table.js"></script>
<script src="../JavaScript/M2_JS/add_table_message.js"></script>
<script src="../JavaScript/M2_JS/stock_cards.js"></script>



</body>
</html>