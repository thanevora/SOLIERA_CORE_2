<?php

session_start();
include("../../main_connection.php");

$db_name = "rest_m1_trs"; // ✅ pick the DB you want

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name]; // ✅ get the correct DB connection

// Query to get all tables
$sql = "SELECT table_id, name, category, capacity, status FROM tables";
$result_sql = $conn->query($sql);
if (!$result_sql) {
    die("SQL Error: " . $conn->error);
}

// Query to get table counts by status
$query = "SELECT 
            (SELECT COUNT(*) FROM tables) AS total_tables,
            (SELECT COUNT(*) FROM tables WHERE status = 'Available') AS Available,
            (SELECT COUNT(*) FROM tables WHERE status = 'Queued') AS Queued,
            (SELECT COUNT(*) FROM tables WHERE status = 'Occupied') AS Occupied,
            (SELECT COUNT(*) FROM tables WHERE status = 'Reserved') AS Reserved,
            (SELECT COUNT(*) FROM tables WHERE status = 'Maintenance') AS maintenance";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Count query failed: " . mysqli_error($conn));
}

// Fetch the counts
$row = mysqli_fetch_assoc($result);
$total_tables_count = $row['total_tables'];
$Queued_count = $row['Queued'];   
$available_count = $row['Available'];
$occupied_count = $row['Occupied'];
$reserved_count = $row['Reserved'];
$maintenance_count = $row['maintenance'];
?>

<!DOCTYPE html>
<html lang="en">
        <?php include '../../header.php'; ?>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Table management</title>
    <style>
        .side-by-side-container {
            display: flex;
            flex-direction: row;
            gap: 1.5rem;
        }
        
        .chart-container {
            flex: 1;
            min-width: 0;
        }
        
        .status-cards-container {
            flex: 1;
            min-width: 0;
        }
        
        @media (max-width: 1024px) {
            .side-by-side-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="bg-base-100 min-h-screen bg-white">

  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../../sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../../navbar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-4 md:p-6 bg-white">
            <!-- Combined Chart and Status Cards Section -->
            <section class="glass-effect p-6 rounded-2xl shadow-xl border border-gray-100/30 backdrop-blur-sm bg-white/70 mb-6">
                <div class="side-by-side-container">
                  

<!-- Status Cards Container -->
<div class="status-cards-container">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-3 h-full">

        <!-- Total Tables Card -->
        <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 h-full transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Total Tables</p>
                    <h3 class="text-3xl font-bold mt-1 text-[#001f54]"><?php echo $total_tables_count ?? '0'; ?></h3>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                    <i data-lucide="table" class="w-5 h-5 text-[#F7B32B]"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-[#F7B32B] rounded-full" style="width: 100%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>Capacity</span>
                    <span class="font-medium"><?php echo $total_tables_count ?? '0'; ?> tables</span>
                </div>
            </div>
        </div>

        <!-- Available Tables Card -->
        <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 h-full transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Available</p>
                    <h3 class="text-3xl font-bold mt-1 text-[#001f54]"><?php echo $available_count ?? '0'; ?></h3>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                    <i data-lucide="check-circle" class="w-5 h-5 text-[#F7B32B]"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-[#F7B32B] rounded-full" style="width: <?php echo isset($available_count) ? ($available_count/$total_tables_count)*100 : '0'; ?>%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>Availability</span>
                    <span class="font-medium"><?php echo isset($available_count) ? round(($available_count/$total_tables_count)*100) : '0'; ?>%</span>
                </div>
            </div>
        </div>

        <!-- Queued Reservations Card -->
        <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 h-full transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Queued</p>
                    <h3 class="text-3xl font-bold mt-1 text-[#001f54]"><?php echo $Queued_count ?? '0'; ?></h3>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                    <i data-lucide="clock" class="w-5 h-5 text-[#F7B32B]"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-[#F7B32B] rounded-full" style="width: <?php echo isset($Queued_count) ? ($Queued_count/$total_tables_count)*100 : '0'; ?>%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>New requests</span>
                    <span class="font-medium"><?php echo rand(1, 5); ?> today</span>
                </div>
            </div>
        </div>

        <!-- Occupied Tables Card -->
        <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 h-full transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Occupied</p>
                    <h3 class="text-3xl font-bold mt-1 text-[#001f54]"><?php echo $occupied_count ?? '0'; ?></h3>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                    <i data-lucide="users" class="w-5 h-5 text-[#F7B32B]"></i>
                </div>
            </div>
        </div>

        <!-- Reserved Tables Card -->
        <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 h-full transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Reserved</p>
                    <h3 class="text-3xl font-bold mt-1 text-[#001f54]"><?php echo $reserved_count ?? '0'; ?></h3>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                    <i data-lucide="calendar-clock" class="w-5 h-5 text-[#F7B32B]"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-[#F7B32B] rounded-full" style="width: <?php echo isset($reserved_count) ? ($reserved_count/$total_tables_count)*100 : '0'; ?>%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>Upcoming</span>
                    <span class="font-medium"><?php echo rand(1, 8); ?> today</span>
                </div>
            </div>
        </div>

        <!-- Maintenance Tables Card -->
        <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 h-full transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Maintenance</p>
                    <h3 class="text-3xl font-bold mt-1 text-[#001f54]"><?php echo $maintenance_count ?? '0'; ?></h3>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                    <i data-lucide="wrench" class="w-5 h-5 text-[#F7B32B]"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-[#F7B32B] rounded-full" style="width: <?php echo isset($maintenance_count) ? ($maintenance_count/$total_tables_count)*100 : '0'; ?>%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>Estimated repair</span>
                    <span class="font-medium"><?php echo rand(1, 3); ?> days</span>
                </div>
            </div>
        </div>

    </div>
</div>

                </div>
            </section>

            <!-- Table Grid Section -->
            <section class="glass-effect p-6 rounded-xl shadow-xl mt-6">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <h2 class="text-xl font-bold flex items-center gap-2 text-gray-800">
                        <i data-lucide="layout-grid" class="w-5 h-5 text-blue-500"></i>
                        <span>All Tables</span>
                    </h2>
                    <div class="flex flex-wrap gap-3 w-full md:w-auto">
                        <!-- Add Table Button -->
                        <label for="add-table-modal" class="btn btn-primary px-4 py-2 rounded-xl flex items-center gap-2 shadow-md hover:shadow-lg cursor-pointer text-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Add Table</span>
                        </label>

<!-- Filter + Search Controls -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
  
  <!-- Filter Dropdown -->
  <div class="dropdown dropdown-end">
    <button class="btn btn-outline border-gray-300 text-gray-700 hover:bg-gray-100 px-4 py-2 rounded-xl flex items-center gap-2 text-sm">
      <i data-lucide="filter" class="w-4 h-4"></i> 
      <span>Filter</span>
    </button>
    <ul id="table-filter" class="dropdown-content menu p-2 shadow bg-white text-black rounded-box w-52 mt-2 border border-gray-200">
      <li><a href="#" data-filter="all">All Tables</a></li>
      <li><a href="#" data-filter="available">Available</a></li>
      <li><a href="#" data-filter="queued">Queued</a></li>
      <li><a href="#" data-filter="occupied">Occupied</a></li>
      <li><a href="#" data-filter="maintenance">Maintenance</a></li>
    </ul>
  </div>

  <!-- Search Bar -->
  <input 
    type="text" 
    id="table-search" 
    placeholder="Search tables..." 
    class="w-full sm:w-64 px-4 py-2 border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm text-black"
  />
</div>

                    </div>
                </div>

  <!-- Dynamic Table Grid with Theme Styling -->
<div 
  class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 
         gap-4 p-4 rounded-lg text-white shadow-lg"
  id="tables-grid">
  <!-- Table cards will be rendered here dynamically -->
</div>


                <!-- Pagination -->
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-600" id="pagination-info">
                        Showing <span class="font-semibold">0</span> of <span class="font-semibold">0</span> tables
                    </div>
                    <div class="join" id="pagination-controls">
                        <!-- Page buttons will be injected dynamically -->
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>






<!-- Add Table Modal -->
<input type="checkbox" id="add-table-modal" class="modal-toggle" />
<div class="modal modal-bottom sm:modal-middle">
  <div class="modal-box max-w-md p-6 rounded-lg shadow-2xl bg-white">
    <!-- Modal Header -->
    <div class="flex justify-between items-center mb-5">
      <h3 class="text-2xl font-bold flex items-center gap-2 text-[#001f54]">
        <i data-lucide="plus-circle" class="w-6 h-6 text-[#F7B32B]"></i>
        <span>Add New Table</span>
      </h3>
      <label for="add-table-modal" class="btn btn-circle btn-ghost btn-sm text-[#001f54]">
        <i data-lucide="x" class="w-5 h-5"></i>
      </label>
    </div>

    <!-- Add Table Form -->
    <form id="add-table-form" class="space-y-4">
      <!-- Name Field -->
      <div class="form-control">
        <label class="label">
          <span class="label-text font-medium text-[#001f54]">Table Name</span>
        </label>
        <input
          type="text"
          name="name"
          required
          placeholder="e.g. Table 5, Booth B"
          class="input input-bordered bg-white w-full focus:border-[#F7B32B] focus:ring-2 focus:ring-[#F7B32B]/40 text-[#001f54]"
        />
      </div>

      <!-- Category Field -->
      <div class="form-control">
        <label class="label">
          <span class="label-text font-medium text-[#001f54]">Category</span>
        </label>
        <select
          name="category"
          required
          class="select select-bordered bg-white w-full focus:border-[#F7B32B] focus:ring-2 focus:ring-[#F7B32B]/40 text-[#001f54]"
        >
          <option value="" disabled selected>Select Category</option>
          <option value="Regular">Regular</option>
          <option value="VIP">VIP</option>
        </select>
      </div>

      <!-- Capacity Field -->
      <div class="form-control">
        <label class="label">
          <span class="label-text font-medium text-[#001f54]">Seating Capacity</span>
        </label>
        <input
          type="number"
          name="capacity"
          required
          min="1"
          max="20"
          placeholder="e.g. 4"
          class="input input-bordered bg-white w-full focus:border-[#F7B32B] focus:ring-2 focus:ring-[#F7B32B]/40 text-[#001f54]"
        />
      </div>

      <!-- Action Buttons -->
      <div class="modal-action mt-6 flex justify-end gap-3">
        <label
          for="add-table-modal"
          class="btn btn-outline border-[#001f54] text-[#001f54] hover:border-[#F7B32B] hover:text-[#F7B32B]"
        >
          Cancel
        </label>
        <button
          type="submit"
          class="btn bg-[#F7B32B] hover:bg-[#001f54] hover:text-white text-[#001f54] border-none"
        >
          <i data-lucide="check" class="w-4 h-4 mr-1"></i>
          Add Table
        </button>
      </div>
    </form>
  </div>
</div>


<!-- Table Details Modal -->
<input type="checkbox" id="table-details-modal" class="modal-toggle" />
<div class="modal modal-bottom sm:modal-middle">
    <div class="modal-box max-w-md p-6 bg-white rounded-lg shadow-2xl">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i data-lucide="table" class="w-5 h-5 text-blue-500"></i>
                <span>Table Details</span>
            </h3>
            <label for="table-details-modal" class="btn btn-circle btn-ghost btn-sm">
                <i data-lucide="x" class="w-5 h-5"></i>
            </label>
        </div>

        <div class="space-y-4">
            <div class="flex justify-between">
                <div>
                    <h4 class="text-lg font-semibold text-gray-800" id="detail-table-name"></h4>
                    <p class="text-gray-600 text-sm" id="detail-table-location"></p>
                </div>
                <span class="text-xs font-semibold px-2 py-1 rounded-full" id="detail-table-status"></span>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-gray-500 text-sm">Type</p>
                    <p class="font-medium" id="detail-table-type"></p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-gray-500 text-sm">Capacity</p>
                    <p class="font-medium" id="detail-table-capacity"></p>
                </div>
            </div>

            <div class="bg-gray-50 p-3 rounded-lg">
                <p class="text-gray-500 text-sm">Current Reservation</p>
                <p class="font-medium" id="detail-table-reservation"></p>
            </div>

            <div class="flex gap-3 mt-6">
                <button class="btn btn-outline border-gray-300 hover:border-blue-500 hover:text-blue-600 flex-1">
                    <i data-lucide="edit" class="w-4 h-4 mr-1"></i> Edit
                </button>
                <button class="btn btn-primary flex-1">
                    <i data-lucide="calendar-plus" class="w-4 h-4 mr-1"></i> Reserve
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('tableSearch');
    const tableCards = document.querySelectorAll('.table-card'); 
    // 👆 Make sure your table cards have the class "table-card"

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            tableCards.forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});

</script>

<script src="../../JavaScript/M1_JS/table.js"></script>
<script src="../../JavaScript/M1_JS/add_table.js"></script>
<script src="../../JavaScript/M1_JS/fetch_table.js"></script>
<script src="../../JavaScript/sidebar.js"></script>
<script src="../../JavaScript/soliera.js"></script>

</body>
</html>