<?php
session_start();
include("../main_connection.php");
require_once '../PHPMailer/PHPMailerAutoload.php';

// Database connection
$db_name = "rest_m1_trs";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}
$conn = $connections[$db_name];


$sql = "SELECT * FROM reservations";  // change to your actual table/query
$result_sql = $conn->query($sql);


$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$where_clause = "";
if ($filter_status != 'all') {
    $where_clause = "WHERE status = '" . $conn->real_escape_string($filter_status) . "'";
}


// Pagination setup
$limit = 10; // reservations per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM reservations $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Fetch records with filter + pagination
$sql = "SELECT * FROM reservations $where_clause LIMIT $limit OFFSET $offset";
$result_sql = $conn->query($sql);

// Fetch menu statistics
$query = "SELECT 
            (SELECT COUNT(*) FROM reservations) AS total_reservations,
            (SELECT COUNT(*) FROM reservations WHERE status = 'Queued') AS Queued,
            (SELECT COUNT(*) FROM reservations WHERE status = 'Confirmed') AS Confirmed,
            (SELECT COUNT(*) FROM reservations WHERE status = 'Denied') AS Denied";

$result = $conn->query($query);
if (!$result) {
    die("Count query failed: " . $conn->error);
}

// Get counts
$row = $result->fetch_assoc();
$total_reservations_count = $row['total_reservations'] ?? 0;
$queued_count = $row['Queued'] ?? 0;   
$confirmed_count = $row['Confirmed'] ?? 0;   
$denied_count = $row['Denied'] ?? 0;


?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restaurant Reservation System</title>
  <?php include '../header.php'; ?>
</head>
<body class="bg-base-100 min-h-screen bg-white">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-4 md:p-6">
          <!-- Dashboard Cards -->
          <div class="glass-effect p-6 rounded-2xl shadow-sm border border-gray-100/50 backdrop-blur-sm bg-white/70">

          
<div class="glass-effect p-6 rounded-2xl shadow-sm border border-gray-100/50 backdrop-blur-sm bg-white/70">
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
      <span class="p-2 mr-3 rounded-lg bg-blue-100/50 text-blue-600">
        <i data-lucide="activity" class="w-5 h-5"></i>
      </span>
      Dashboard
    </h2>
   
  </div>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

    <!-- Confirmed Reservations -->
    <div class="stat-card bg-white text-black shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Confirmed</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $confirmed_count; ?></h3>
                <p class="text-xs text-gray-500 mt-1">Events confirmed</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i class="fas fa-check-circle text-2xl text-[#F7B32B]"></i>
            </div>
        </div>
    </div>

    <!-- Queued Reservations -->
    <div class="stat-card bg-white text-black shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Queued</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $queued_count; ?></h3>
                <p class="text-xs text-gray-500 mt-1">Pending approval</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i class="fas fa-clock text-2xl text-[#F7B32B]"></i>
            </div>
        </div>
    </div>

    <!-- Cancelled Reservations -->
    <div class="stat-card bg-white text-black shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Cancelled</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $denied_count; ?></h3>
                <p class="text-xs text-gray-500 mt-1">Cancelled events</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i class="fas fa-times-circle text-2xl text-[#F7B32B]"></i>
            </div>
        </div>
    </div>

    <!-- Today's Reservations -->
    <div class="stat-card bg-white text-black shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Today's</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $total_reservations_count; ?></h3>
                <p class="text-xs text-gray-500 mt-1">Events today</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i class="fas fa-calendar-check text-2xl text-[#F7B32B]"></i>
            </div>
        </div>
    </div>

</div>


</div>




          
<!-- Filter Section -->
<div class="bg-white p-4 rounded-lg shadow-sm mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
  <h3 class="text-lg font-semibold text-gray-800">Filter Reservations</h3>
  
  <div class="flex items-center gap-3">
    <!-- Filter Dropdown -->
    <form method="GET">
      <select name="status" 
        class="bg-white text-black px-4 py-2 rounded-md border border-gray-300 focus:ring focus:ring-blue-200"
        onchange="this.form.submit()">
        <option value="all" <?php echo ($filter_status == 'all') ? 'selected' : ''; ?>>All Reservations</option>
        <option value="Confirmed" <?php echo ($filter_status == 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
        <option value="Denied" <?php echo ($filter_status == 'Denied') ? 'selected' : ''; ?>>Denied</option>
        <option value="Queued" <?php echo ($filter_status == 'Queued') ? 'selected' : ''; ?>>Queued</option>
        <option value="Cancelled" <?php echo ($filter_status == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
      </select>
    </form>

    <!-- New Reservation Button -->
    <button 
      class="btn btn-sm bg-[#F7B32B] text-black hover:bg-[#d99a22] transition-all duration-200 hover:scale-105"
      onclick="document.getElementById('reservations-modal').showModal()">
      <i class="bx bx-plus mr-1"></i> New
    </button>
  </div>
</div>

          
       <!-- Reservations Grid -->
<div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
  <?php if ($result_sql && $result_sql->num_rows > 0): ?>
    <?php while ($row = $result_sql->fetch_assoc()): ?>
      <div class="bg-[#001f54] border rounded-lg shadow-xl hover:shadow-xl transition-all duration-200 p-4 flex flex-col justify-between">
        <!-- Header -->
        <div>
          <h3 class="font-bold text-lg text-white">
            <i class="bx bx-user mr-1" style="color:#F7B32B;"></i>
            Customer: <?= htmlspecialchars($row['name']) ?>
          </h3>
          <p class="text-sm text-white">
            <i class="bx bx-phone mr-1" style="color:#F7B32B;"></i>
            <?= htmlspecialchars($row['contact']) ?>
          </p>

          <div class="mt-2 text-sm text-white space-y-1">
            <p><i class="bx bx-calendar mr-1" style="color:#F7B32B;"></i> <?= date('M j, Y', strtotime($row['reservation_date'])) ?></p>
            <p><i class="bx bx-time mr-1" style="color:#F7B32B;"></i> <?= date('g:i A', strtotime($row['start_time'])) ?> - <?= date('g:i A', strtotime($row['end_time'])) ?></p>
            <p><i class="bx bx-group mr-1" style="color:#F7B32B;"></i> <?= (int)$row['size'] ?> Guests</p>
            <p><i class="bx bx-restaurant mr-1" style="color:#F7B32B;"></i> <?= htmlspecialchars($row['type']) ?></p>
          </div>

          <!-- Status -->
          <div class="mt-3">
            <span class="px-3 py-1 text-xs font-semibold rounded-full 
              <?php 
                switch($row['status']) {
                  case 'Confirmed': echo 'bg-green-100 text-green-800'; break;
                  case 'Denied': echo 'bg-red-100 text-red-800'; break;
                  case 'Queued': echo 'bg-yellow-100 text-yellow-800'; break;
                  case 'Cancelled': echo 'bg-gray-100 text-gray-800'; break;
                  default: echo 'bg-gray-100 text-gray-800';
                }
              ?>">
              <i class="bx bx-info-circle mr-1" style="color:#F7B32B;"></i>
              <?= htmlspecialchars($row['status']) ?>
            </span>
          </div>
        </div>

      <!-- Actions -->
<div class="flex justify-end mt-4">
  <button 
    class="btn btn-sm bg-[#F7B32B] text-black hover:bg-[#d99a22] view-btn" 
    data-id="<?= $row['reservation_id'] ?>">
    <i class="bx bx-show mr-1"></i> View
  </button>
</div>


      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="col-span-full flex flex-col items-center justify-center py-10 text-gray-500">
      <i class="bx bx-calendar-x text-5xl mb-4" style="color:#F7B32B;"></i>
      No reservations found
      <?php if ($filter_status != 'all'): ?>
        <p class="text-sm mt-2">No <?= strtolower($filter_status); ?> reservations</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>




<!-- Modal (hidden by default) -->
<div id="view-modal" 
     class="fixed inset-0 flex items-center justify-center bg-black/50 z-50 hidden">
  <div class="modal-box w-11/12 max-w-3xl bg-[#001f54] text-black shadow-2xl border-2">
    <!-- Header -->
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-bold text-xl flex items-center">
        <i class="bx bx-detail mr-2 text-[#F7B32B] text-2xl"></i> 
        <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#F7B32B] to-[#ffd166]">
          Reservation Details
        </span>
      </h3>
      <button id="close-modal" class="btn btn-circle btn-ghost btn-sm text-white hover:bg-[#F7B32B] hover:text-black transition-all">
        <i class="bx bx-x text-xl"></i>
      </button>
    </div>

    <!-- Body -->
    <div id="reservation-details" class="py-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
      <!-- Loading -->
      <div id="loading-indicator" class="flex justify-center items-center py-8">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[#F7B32B]"></div>
      </div>
      <!-- Dynamic content -->
      <div id="reservation-content" class="hidden"></div>
    </div>

    <!-- Footer -->
    <div class="modal-action mt-6 pt-4 border-t border-[#F7B32B]/30">
      <button id="close-modal-footer" class="btn bg-[#6c757d] border-none text-white hover:bg-[#5a6268] transition-all">
        <i class="bx bx-x-circle mr-1"></i> Close
      </button>
    </div>
  </div>
</div>




   <!-- Reservations Modal (Create/Edit) -->
<dialog id="reservations-modal" class="modal backdrop-blur-sm">
  <div class="modal-box w-[95vw] max-w-[1200px] h-[85vh] max-h-[900px] p-0 overflow-hidden bg-white border border-gray-200 shadow-2xl rounded-xl transform transition-all duration-300 ease-out">
    <div class="flex flex-col h-full">
      <!-- Header -->
      <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
        <div class="flex items-center gap-3">
          <div class="p-2 rounded-lg bg-blue-100/80 text-blue-600">
            <i data-lucide="calendar-plus" class="w-6 h-6"></i>
          </div>
          <div>
            <h5 id="modal-date-title" class="text-2xl font-bold text-gray-800">New Reservation</h5>
            <p class="text-sm text-gray-500 mt-1">Fill in the details to create a new booking</p>
          </div>
        </div>
        <button id="close-modal" class="btn btn-sm btn-circle btn-ghost text-gray-500 hover:bg-gray-200/50 hover:text-gray-700 transition-all duration-200"
          onclick="document.getElementById('reservations-modal').close()">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <!-- Form Content -->
      <div class="flex-1 overflow-y-auto p-6 bg-white scroll-smooth [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
        <form id="reservation-form" action="create_reservation.php" method="POST" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left Column -->
            <div class="space-y-6">
              <!-- Guest Section -->
              <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 animate-fade-in-up delay-75">
                <h6 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                  <i data-lucide="user" class="w-5 h-5 text-blue-600"></i>
                  Guest Information
                </h6>
                
                <!-- Guest Name -->
                <div class="form-control mb-4">
                  <label class="label" for="name">
                    <span class="label-text font-medium text-gray-600">Full Name*</span>
                  </label>
                  <div class="relative">
                    <input type="text" id="name" name="name"
                      class="input input-bordered w-full bg-white border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 transition-all duration-200 hover:border-blue-300 pl-10"
                      placeholder="John Doe"
                      required>
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                      <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                    </div>
                  </div>
                </div>

                <!-- Contact -->
                <div class="form-control">
                  <label class="label" for="contact">
                    <span class="label-text font-medium text-gray-600">Contact Information*</span>
                  </label>
                  <div class="relative">
                    <input type="text" id="contact" name="contact"
                      class="input input-bordered w-full bg-white border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 transition-all duration-200 hover:border-blue-300 pl-10"
                      placeholder="09123456789"
                      required>
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                      <i data-lucide="phone" class="w-4 h-4 text-gray-400"></i>
                    </div>
                  </div>
                </div>

                 <div class="form-control">
                  <label class="label" for="contact">
                    <span class="label-text font-medium text-gray-600">Email*</span>
                  </label>
                  <div class="relative">
                    <input type="text" id="email" name="email"
                      class="input input-bordered w-full bg-white border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 transition-all duration-200 hover:border-blue-300 pl-10"
                      placeholder="Email address"
                      required>
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                      <i data-lucide="phone" class="w-4 h-4 text-gray-400"></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Reservation Details Section -->
              <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 animate-fade-in-up delay-150">
                <h6 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                  <i data-lucide="calendar-clock" class="w-5 h-5 text-blue-600"></i>
                  Reservation Details
                </h6>
                
                <div class="grid grid-cols-2 gap-4">
                  <!-- Date -->
                  <div class="form-control">
                    <label class="label" for="reservation_date">
                      <span class="label-text font-medium text-gray-600">Date*</span>
                    </label>
                    <div class="relative">
                      <input type="date" id="reservation_date" name="reservation_date"
                        class="input input-bordered w-full bg-white border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 transition-all duration-200 hover:border-blue-300 pl-10"
                        required>
                      <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
                      </div>
                    </div>
                  </div>

                  <!-- Party Size -->
                  <div class="form-control">
                    <label class="label" for="size">
                      <span class="label-text font-medium text-gray-600">Party Size*</span>
                    </label>
                    <div class="relative">
                      <input type="number" id="size" name="size" min="1"
                        class="input input-bordered w-full bg-white border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 transition-all duration-200 hover:border-blue-300 pl-10"
                        placeholder="2"
                        required>
                      <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <i data-lucide="users" class="w-4 h-4 text-gray-400"></i>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Time -->
                <div class="grid grid-cols-2 gap-4 mt-4">
                  <div class="form-control">
                    <label class="label" for="start_time">
                      <span class="label-text font-medium text-gray-600">Start Time*</span>
                    </label>
                    <div class="relative">
                      <input type="time" id="start_time" name="start_time"
                        class="input input-bordered w-full bg-white border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 transition-all duration-200 hover:border-blue-300 pl-10"
                        required>
                      <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <i data-lucide="clock" class="w-4 h-4 text-gray-400"></i>
                      </div>
                    </div>
                  </div>
                  <div class="form-control">
                    <label class="label" for="end_time">
                      <span class="label-text font-medium text-gray-600">End Time*</span>
                    </label>
                    <div class="relative">
                      <input type="time" id="end_time" name="end_time"
                        class="input input-bordered w-full bg-white border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 transition-all duration-200 hover:border-blue-300 pl-10"
                        required>
                      <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <i data-lucide="clock" class="w-4 h-4 text-gray-400"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
              <!-- Table Selection -->
              <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 animate-fade-in-up delay-250">
                <h6 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                  <i data-lucide="table" class="w-5 h-5 text-blue-600"></i>
                  Table Selection
                </h6>
                
                <div class="form-control relative">

  
                <div class="relative">
                  <select id="table_id" name="table_id"
                    class="pl-10 pr-10 py-3 w-full bg-white border border-gray-200 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all duration-200 appearance-none hover:border-blue-300 cursor-pointer"
                    required>
                    <option value="" disabled selected class="text-gray-400">-- Choose a Table --</option>
                    <?php
                      include('../conn_M1.php');
                      $query = "SELECT table_id, name, category, capacity, status FROM tables ORDER BY status, name";
                      $result = $conn->query($query);
                      while ($row = $result->fetch_assoc()):
                        $disabled = ($row['status'] !== 'Available') ? 'disabled' : '';
                        $class = ($row['status'] !== 'Available') 
                            ? 'bg-gray-50 text-gray-400 cursor-not-allowed' 
                            : 'hover:bg-blue-50 text-gray-700';
                    ?>
                      <option value="<?= $row['table_id']; ?>" class="<?= $class; ?>" <?= $disabled; ?> data-status="<?= $row['status']; ?>">
                        <?= htmlspecialchars($row['name']) ?> • <?= $row['category'] ?> • <?= $row['capacity'] ?> pax • 
                        <span class="<?= $row['status'] === 'Available' ? 'text-green-600 font-medium' : 'text-gray-500' ?>">
                          <?= $row['status'] ?>
                        </span>
                      </option>
                    <?php endwhile; ?>
                  </select>
                  
                  <!-- Leading Icon -->
                  <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="table" class="w-5 h-5"></i>
                  </div>
                  
                  <!-- Dropdown Arrow -->
                  <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="chevron-down" class="w-5 h-5"></i>
                  </div>
                </div>
                
                <!-- Status Legend -->
                <div class="flex flex-wrap gap-3 mt-2 text-xs">
                  <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="text-gray-500">Available</span>
                  </div>
                  <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span class="text-gray-500">Occupied</span>
                  </div>
                  <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                    <span class="text-gray-500">Unavailable</span>
                  </div>
                </div>
              </div>

      
                <div class="mt-4">
                  <label class="label" for="type">
                    <span class="label-text font-medium text-gray-600">Reservation Type*</span>
                  </label>
                  <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 cursor-pointer transition-all duration-200">
                      <input type="radio" name="type" value="breakfast" class="radio radio-sm radio-primary">
                      <span class="flex items-center gap-1">
                        <i data-lucide="sun" class="w-4 h-4 text-amber-500"></i>
                        Breakfast
                      </span>
                    </label>
                    <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 cursor-pointer transition-all duration-200">
                      <input type="radio" name="type" value="lunch" class="radio radio-sm radio-primary">
                      <span class="flex items-center gap-1">
                        <i data-lucide="sunrise" class="w-4 h-4 text-orange-500"></i>
                        Lunch
                      </span>
                    </label>
                    <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 cursor-pointer transition-all duration-200">
                      <input type="radio" name="type" value="dinner" class="radio radio-sm radio-primary">
                      <span class="flex items-center gap-1">
                        <i data-lucide="moon" class="w-4 h-4 text-indigo-500"></i>
                        Dinner
                      </span>
                    </label>
                   
                  </div>
                </div>
              </div>

              <!-- Special Requests -->
              <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 animate-fade-in-up delay-300">
                <h6 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                  <i data-lucide="message-square" class="w-5 h-5 text-blue-600"></i>
                  Special Requests
                </h6>
                <textarea id="request" name="request"
                  class="textarea textarea-bordered w-full h-32 bg-white border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 transition-all duration-200 hover:border-blue-300"
                  placeholder="Any dietary restrictions or special arrangements..."></textarea>
              </div>

              <!-- Additional Notes -->
              <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 animate-fade-in-up delay-350">
                <h6 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                  <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i>
                  Additional Notes
                </h6>
                <textarea id="note" name="note"
                  class="textarea textarea-bordered w-full h-32 bg-white border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 transition-all duration-200 hover:border-blue-300"
                  placeholder="Internal notes or comments..."></textarea>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="py-4 px-6 border-t border-gray-200 bg-gray-50 flex justify-between items-center animate-fade-in-up delay-400">
            <div class="text-sm text-gray-500 flex items-center gap-2">
              <i data-lucide="info" class="w-4 h-4"></i>
              <span>Fields marked with * are required</span>
            </div>
            <div class="flex gap-3">
              <button type="button" class="btn btn-ghost text-gray-700 hover:bg-gray-200/50 transition-all duration-200"
                  onclick="document.getElementById('reservations-modal').close()">
                  <i data-lucide="x" class="w-4 h-4 mr-2"></i> Cancel
              </button>
              <button type="submit" class="btn bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white transition-all duration-200 shadow hover:shadow-md">
                  <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Create Reservation
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>







<!-- Pagination -->
<div class="text-black flex justify-center items-center gap-2 mt-6">
  <?php if ($page > 1): ?>
    <a href="?status=<?php echo $filter_status; ?>&page=<?php echo $page-1; ?>" 
       class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Prev</a>
  <?php endif; ?>

  <span class="px-3 py-1">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>

  <?php if ($page < $total_pages): ?>
    <a href="?status=<?php echo $filter_status; ?>&page=<?php echo $page+1; ?>" 
       class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next</a>
  <?php endif; ?>
</div>

          <!-- ... (rest of your existing code for calendar and modals) ... -->
        </main>
    </div>
  </div>

  <script>

    // SweetAlert integration for status updates
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submissions with SweetAlert
    const statusForms = document.querySelectorAll('form[action*="update_reservation_status"]');
    
    statusForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const status = formData.get('status');
            const action = status === 'Confirmed' ? 'approve' : 'deny';
            
            Swal.fire({
                title: `${action.charAt(0).toUpperCase() + action.slice(1)} Reservation?`,
                text: `Are you sure you want to ${action} this reservation?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: status === 'Confirmed' ? '#3085d6' : '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${action} it!`,
                cancelButtonText: 'Cancel',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(
                            `Request failed: ${error}`
                        );
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: `${action.charAt(0).toUpperCase() + action.slice(1)}d!`,
                        text: `Reservation has been ${action}d successfully.`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Reload the page after successful update
                        window.location.reload();
                    });
                }
            });
        });
    });
    
    // Show SweetAlert notifications from PHP session
    <?php if (isset($_SESSION['alert_type']) && isset($_SESSION['alert_message'])): ?>
        Swal.fire({
            icon: '<?php echo $_SESSION['alert_type']; ?>',
            title: '<?php echo ucfirst($_SESSION['alert_type']); ?>',
            text: '<?php echo $_SESSION['alert_message']; ?>',
            timer: 3000,
            showConfirmButton: false
        });
        <?php
        // Clear the session messages
        unset($_SESSION['alert_type']);
        unset($_SESSION['alert_message']);
        ?>
    <?php endif; ?>
});
  </script>

  <script>

    // View reservation details with SweetAlert
document.querySelectorAll('.view-reservation').forEach(button => {
    button.addEventListener('click', function() {
        const reservationId = this.getAttribute('data-id');
        
        // Fetch reservation details via AJAX
        fetch(`get_reservation_details.php?id=${reservationId}`)
            .then(response => response.json())
            .then(data => {
                Swal.fire({
                    title: 'Reservation Details',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>Name:</strong> ${data.name}</p>
                            <p><strong>Contact:</strong> ${data.contact}</p>
                            <p><strong>Date:</strong> ${new Date(data.reservation_date).toLocaleDateString()}</p>
                            <p><strong>Time:</strong> ${data.start_time} - ${data.end_time}</p>
                            <p><strong>Party Size:</strong> ${data.size}</p>
                            <p><strong>Type:</strong> ${data.type}</p>
                            <p><strong>Status:</strong> <span class="badge badge-${getStatusClass(data.status)}">${data.status}</span></p>
                            ${data.request ? `<p><strong>Special Requests:</strong> ${data.request}</p>` : ''}
                            ${data.note ? `<p><strong>Notes:</strong> ${data.note}</p>` : ''}
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Close'
                });
            })
            .catch(error => {
                Swal.fire('Error', 'Could not load reservation details.', 'error');
            });
    });
});

function getStatusClass(status) {
    switch(status) {
        case 'Confirmed': return 'success';
        case 'Denied': return 'error';
        case 'Queued': return 'warning';
        case 'Cancelled': return 'secondary';
        default: return 'secondary';
    }
}
  </script>


<script>
document.querySelectorAll('.view-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    
    fetch('sub-modules/get_reservation_details.php?id=' + id)
      .then(res => res.text())
      .then(data => {
        document.getElementById('reservation-details').innerHTML = data;
        document.getElementById('view-modal').checked = true; // open modal
      })
      .catch(err => {
        document.getElementById('reservation-details').innerHTML = '<p class="text-red-500">Error loading details</p>';
        document.getElementById('view-modal').checked = true;
      });
  });
});
</script>


<script>
document.querySelectorAll('.view-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const modal = document.getElementById('view-modal');
    modal.classList.remove('hidden'); // show modal
    modal.classList.add('flex');

    // Load reservation details dynamically
    const reservationId = btn.getAttribute('data-id');
    document.getElementById('loading-indicator').style.display = 'flex';
    document.getElementById('reservation-content').classList.add('hidden');

    fetch(`get_reservation.php?id=${reservationId}`)
      .then(res => res.text())
      .then(html => {
        document.getElementById('loading-indicator').style.display = 'none';
        document.getElementById('reservation-content').innerHTML = html;
        document.getElementById('reservation-content').classList.remove('hidden');
      });
  });
});

// Close modal
document.getElementById('close-modal').addEventListener('click', closeModal);
document.getElementById('close-modal-footer').addEventListener('click', closeModal);

function closeModal() {
  const modal = document.getElementById('view-modal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}
</script>

  <script src="../JavaScript/calendar_crude.js"></script>
  <script src="../JavaScript/sidebar.js"></script>
  <script src="../JavaScript/soliera.js"></script>
</body>
</html>