<?php
session_start();
include("../main_connection.php");

// Database configuration
$db_name = "rest_m3_menu";

// Check database connection
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

// Fetch menu items
$sql = "SELECT menu_id, name, category, description, variant, price, status, created_at, updated_at FROM menu";
$result_sql = $conn->query($sql);
if (!$result_sql) {
    die("SQL Error: " . $conn->error);
}

// Fetch menu statistics
$query = "SELECT 
            (SELECT COUNT(*) FROM menu) AS total_menu,
            (SELECT COUNT(DISTINCT category) FROM menu) AS total_categories,
            (SELECT COUNT(*) FROM menu WHERE category = 'seasonal') AS seasonal,
            (SELECT COUNT(*) FROM menu WHERE category = 'popular') AS popular";

$result = $conn->query($query);
if (!$result) {
    die("Count query failed: " . $conn->error);
}

// Get counts
$row = $result->fetch_assoc();
$total_menu_count = $row['total_menu'] ?? 0;
$total_categories_count = $row['total_categories'] ?? 0;
$seasonal_count = $row['seasonal'] ?? 0;   
$popular_count = $row['popular'] ?? 0;

// Fetch inventory items for the form
$inv_query = "SELECT item_id, item_name FROM inventory_and_stock";
$inv_result = $connections['rest_m2_inventory']->query($inv_query);
$inventory_items = [];
while ($inv = $inv_result->fetch_assoc()) {
    $inventory_items[] = $inv;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Menu Management | Soliera Restaurant</title>
    <?php include '../header.php'; ?>
    <style>
        .menu-grid {
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
        
        .menu-item-card {
            transition: all 0.3s ease;
        }
        
        .menu-item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .popular-item {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modal-box {
                margin: 1rem;
                padding: 1rem;
                width: calc(100% - 2rem);
            }
            
            .ingredient-row {
                flex-direction: column;
            }
            
            .ingredient-qty {
                width: 100% !important;
            }
        }
        
        /* Form styling */
        .form-control {
            margin-bottom: 1rem;
        }
        
        .label {
            margin-bottom: 0.5rem;
        }
        
        .ingredient-row {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            align-items: center;
        }
        
        .ingredient-qty {
            width: 100px;
        }
    </style>
</head>
<body class="bg-base-100 min-h-screen bg-white">

<div class="flex h-screen">
    <!-- Sidebar -->
    <div class="bg-[#1A2C5B] border-r border-blue-600 pt-5 pb-4 flex flex-col w-64 transition-all duration-300 ease-in-out shadow-xl relative overflow-hidden h-screen" id="sidebar">
        <?php include '../sidebarr.php'; ?>
    </div>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-4 md:p-6">
            <!-- Menu Summary Cards -->
            <section class="glass-effect p-6 rounded-2xl shadow-sm mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                        <span class="p-2 mr-3 rounded-lg bg-blue-100/50 text-blue-600">
                            <i data-lucide="utensils" class="w-5 h-5"></i>
                        </span>
                        Menu Overview
                    </h2>
                </div>

              <!-- Menu Dashboard Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 h-full">
    
    <!-- Total Menu Items Card -->
    <div class="p-5 rounded-xl shadow-lg border border-gray-100 bg-white hover:shadow-xl transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium" style="color:#001f54;">Total Items</p>
                <h3 class="text-3xl font-bold mt-1 text-gray-800">
                    <?php echo $total_menu_count; ?>
                </h3>
            </div>
            <div class="p-3 rounded-full" style="background:#F7B32B1A; color:#F7B32B;">
                <i data-lucide="list" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full" style="background:#F7B32B; width: 100%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>Active items</span>
                <span class="font-medium"><?php echo $total_menu_count; ?> items</span>
            </div>
        </div>
    </div>

    <!-- Popular Items Card -->
    <div class="p-5 rounded-xl shadow-lg border border-gray-100 bg-white hover:shadow-xl transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium" style="color:#001f54;">Popular Items</p>
                <h3 class="text-3xl font-bold mt-1 text-gray-800">
                    <?php echo $popular_count; ?>
                </h3>
            </div>
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i data-lucide="star" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full" style="width: <?php echo $total_menu_count > 0 ? round(($popular_count/$total_menu_count)*100) : 0; ?>%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>Top sellers</span>
                <span class="font-medium"><?php echo $total_menu_count > 0 ? round(($popular_count/$total_menu_count)*100) : 0; ?>% of menu</span>
            </div>
        </div>
    </div>

    <!-- Categories Card -->
    <div class="p-5 rounded-xl shadow-lg border border-gray-100 bg-white hover:shadow-xl transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium" style="color:#001f54;">Categories</p>
                <h3 class="text-3xl font-bold mt-1 text-gray-800">
                    <?php echo $total_categories_count; ?>
                </h3>
            </div>
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i data-lucide="tags" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-purple-500 rounded-full" style="width: <?php echo $total_categories_count > 0 ? round(($total_categories_count/10)*100) : 0; ?>%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>Menu sections</span>
                <span class="font-medium"><?php echo $total_categories_count; ?> categories</span>
            </div>
        </div>
    </div>

    <!-- Seasonal Items Card -->
    <div class="p-5 rounded-xl shadow-lg border border-gray-100 bg-white hover:shadow-xl transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium" style="color:#001f54;">Seasonal Items</p>
                <h3 class="text-3xl font-bold mt-1 text-gray-800">
                    <?php echo $seasonal_count; ?>
                </h3>
            </div>
            <div class="p-3 rounded-full bg-amber-100 text-amber-600">
                <i data-lucide="sun" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-amber-500 rounded-full" style="width: <?php echo $total_menu_count > 0 ? round(($seasonal_count/$total_menu_count)*100) : 0; ?>%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>Limited time</span>
                <span class="font-medium"><?php echo $total_menu_count > 0 ? round(($seasonal_count/$total_menu_count)*100) : 0; ?>% of menu</span>
            </div>
        </div>
    </div>

</div>

            </section>

            <!-- Menu Management Section -->
            <section class="glass-effect p-6 rounded-xl shadow-sm">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <h2 class="text-xl font-bold flex items-center gap-2 text-gray-800">
                        <i data-lucide="clipboard-list" class="w-5 h-5 text-blue-500"></i>
                        <span>Menu Items</span>
                    </h2>
                    <div class="flex flex-wrap gap-3 w-full md:w-auto">
                        <!-- Add Menu Item Button -->
                        <label for="add-menu-item-modal" class="btn btn-primary px-4 py-2 rounded-xl flex items-center gap-2 shadow-md hover:shadow-lg cursor-pointer text-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Add Menu Item</span>
                        </label>
                        

                    </div>
                </div>
                
                
                <!-- Menu Items Grid -->
                <div class="menu-grid" id="menu-items-grid">
                    <?php while($row = $result_sql->fetch_assoc()): ?>
                    <div class="menu-item-card relative rounded-lg bg-[#001f54] border p-5 shadow-sm transition-all duration-300 hover:shadow-md border-gray-200">
                        <div class="flex items-start justify-between gap-4 mb-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="p-2 rounded-lg bg-gray-100 text-gray-600">
                                    <i data-lucide="utensils" class="w-5 h-5 text-[#F7B32B]"></i>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-lg font-semibold text-[#F7B32B] truncate"><?php echo htmlspecialchars($row['name']); ?></h3>
                                    <p class="text-xs text-[#F7B32B] mt-0.5 truncate">ID: <?php echo htmlspecialchars($row['menu_id']); ?></p>
                                </div>
                            </div>
                            <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 font-medium">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </div>

                   

                        <div class="grid grid-cols-2 gap-4 mt-2">
                            <div class="flex items-center gap-2 text-sm text-gray-600 min-w-0">
                                <div class="min-w-0">
                                    <p class="text-xs text-white truncate">Price</p>
                                    <p class="font-medium truncate text-white">₱ <?php echo htmlspecialchars($row['price']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-600 min-w-0">
                                <i data-lucide="tag" class="w-4 h-4 text-gray-500 shrink-0"></i>
                                <div class="min-w-0">
                                    <p class="text-xs text-white truncate">Category</p>
                                    <p class="font-medium truncate text-white"><?php echo htmlspecialchars($row['category']); ?></p>
                                </div>
                            </div>
                        </div>

                        <p class="text-sm text-white mt-3 line-clamp-2"><?php echo htmlspecialchars($row['description']); ?></p>

                        <div class="mt-auto pt-3 border-t border-gray-200/50 flex justify-between items-center">
      <button onclick="showMenuItemDetails(<?= (int)$row['menu_id'] ?>)" 
        class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1 transition-colors shrink-0">
    View details
    <i data-lucide="chevron-right" class="w-4 h-4"></i>
</button>


                            <span class="text-xs text-white">
                                Updated: <?php echo date('M j, Y', strtotime($row['updated_at'])); ?>
                            </span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
             




                <!-- Pagination -->
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-semibold">1-<?php echo $result_sql->num_rows; ?></span> of <span class="font-semibold"><?php echo $result_sql->num_rows; ?></span> items
                    </div>
                    <div class="join">
                        <button class="join-item btn btn-sm btn-outline border-gray-300">Previous</button>
                        <button class="join-item btn btn-sm btn-outline border-gray-300 bg-blue-50 text-blue-600">1</button>
                        <button class="join-item btn btn-sm btn-outline border-gray-300">Next</button>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
<input type="checkbox" id="menu-item-details-modal" class="modal-toggle" />
<div class="modal">
  <div class="modal-box max-w-2xl rounded-lg">
    <h3 class="font-bold text-lg mb-4 text-black">Menu Item Details</h3>
    <div id="menu-item-details-content" class="space-y-4 text-sm text-gray-700">
      <!-- Item details will load here -->
    </div>
    <div class="modal-action">
      <label for="menu-item-details-modal" 
             class="btn border-0 px-4 py-2 rounded-lg"
             style="background-color: #F7B32B; color: #001f54;">
        Close
      </label>
    </div>
  </div>
</div>

<!-- Add Menu Item Modal -->
<input type="checkbox" id="add-menu-item-modal" class="modal-toggle" />
<div class="modal modal-bottom sm:modal-middle">
    <div class="bg-white modal-box max-w-4xl p-6 rounded-lg shadow-2xl">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-5 h-5 text-blue-500"></i>
                <span>Add Menu Item</span>
            </h3>
            <label for="add-menu-item-modal" class="btn btn-circle btn-ghost btn-sm">
                <i data-lucide="x" class="w-5 h-5"></i>
            </label>
        </div>

        <!-- Add Menu Item Form -->
        <form id="add-menu-item-form" action="sub-modules/add_menu_item.php" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Item Name -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Item Name</span></label>
                    <input type="text" name="name" required placeholder="e.g. Truffle Pasta"
                           class="input input-bordered bg-white w-full" />
                </div>

                <!-- Category -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Category</span></label>
                    <select name="category" required class="select select-bordered bg-white w-full">
                        <option value="" disabled selected>Select Category</option>
                        <option value="appetizers">Appetizers</option>
                        <option value="mains">Main Courses</option>
                        <option value="desserts">Desserts</option>
                        <option value="drinks">Drinks</option>
                        <option value="specials">Specials</option>
                        <option value="sides">Sides</option>
                        <option value="bundle">Bundle</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Price -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Price</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                        <input type="number" name="price" min="0" step="0.01" required 
                               class="input input-bordered bg-white w-full pl-8" />
                    </div>
                </div>

                <!-- Preparation Time -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Prep Time</span></label>
                    <div class="relative">
                        <input type="number" name="prep_time" min="1" required 
                               class="input input-bordered bg-white w-full" />
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">min</span>
                    </div>
                </div>

                <!-- Status -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Status</span></label>
                    <select name="status" class="select select-bordered bg-white w-full">
                        <option value="Available">Available</option>
                        <option value="Unavailable">Unavailable</option>
                        <option value="Seasonal">Seasonal</option>
                    </select>
                </div>
            </div>

            <!-- Description -->
            <div class="form-control">
                <label class="label"><span class="label-text">Description</span></label>
                <textarea name="description" rows="3" required placeholder="Describe the menu item..."
                          class="textarea textarea-bordered bg-white w-full"></textarea>
            </div>

            <!-- Variant -->
            <div class="form-control">
                <label class="label"><span class="label-text">Variant</span></label>
                <input type="text" name="variant" placeholder="e.g. Spicy, Mild" 
                       class="input input-bordered bg-white w-full" />
            </div>

            <!-- Ingredients Section -->
            <div class="form-control">
                <label class="label"><span class="label-text">Ingredients</span></label>
                <div id="ingredients-container" class="space-y-3">
                    <div class="ingredient-row">
                        <select name="ingredients[]" class="select select-bordered bg-white flex-grow">
                            <option value="" disabled selected>Select Ingredient</option>
                            <?php foreach ($inventory_items as $inv): ?>
                                <option value="<?= $inv['item_id'] ?>"><?= htmlspecialchars($inv['item_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="ingredient_qty[]" placeholder="Qty" min="0.01" step="0.01"
                               class="input input-bordered bg-white ingredient-qty" />
                    </div>
                </div>
                <button type="button" onclick="addIngredientRow()" 
                        class="btn btn-sm mt-2 bg-blue-100 text-blue-600 hover:bg-blue-200">
                    + Add Ingredient
                </button>
            </div>

            <!-- Image Upload -->
            <div class="form-control">
                <label class="label"><span class="label-text">Upload Image</span></label>
                <input type="file" name="image_url" accept="image/*"
                       class="file-input file-input-bordered w-full bg-white" />
            </div>

            <!-- Action Buttons -->
            <div class="modal-action mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                <label for="add-menu-item-modal" class="btn btn-outline border-gray-300 text-center sm:text-left">
                    Cancel
                </label>
                <button type="submit" class="btn bg-blue-500 hover:bg-blue-600 text-white">
                    <i data-lucide="check" class="w-4 h-4 mr-1"></i>
                    Add Item
                </button>
            </div>
        </form>

   



        <script>
        function addIngredientRow() {
            const container = document.getElementById('ingredients-container');
            const row = document.createElement('div');
            row.className = 'ingredient-row';
            row.innerHTML = `
                <select name="ingredients[]" class="select select-bordered bg-white flex-grow">
                    <option value="" disabled selected>Select Ingredient</option>
                    <?php foreach ($inventory_items as $inv): ?>
                        <option value="<?= $inv['item_id'] ?>"><?= htmlspecialchars($inv['item_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="ingredient_qty[]" placeholder="Qty" min="0.01" step="0.01"
                       class="input input-bordered bg-white ingredient-qty" />
                <button type="button" onclick="this.parentElement.remove()" class="btn btn-sm btn-error">
                    Remove
                </button>
            `;
            container.appendChild(row);
        }
        </script>
    </div>
</div>

<script>
function showMenuItemDetails(menuId) {
    fetch('sub-modules/get_menu_item.php?menu_id=' + menuId)
        .then(res => res.text())
        .then(html => {
            document.getElementById('menu-item-details-content').innerHTML = html;
            document.getElementById('menu-item-details-modal').checked = true; // open modal
        })
        .catch(err => console.error(err));
}
</script>

    


<script src="../JavaScript/sidebar.js"></script>
<script src="../JavaScript/soliera.js"></script>

</body>
</html>