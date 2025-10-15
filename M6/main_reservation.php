<?php
session_start();
include("../main_connection.php");

$db_name = "rest_m11_event";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

// Fetch event data
$events_query = "SELECT * FROM event_reservations ORDER BY event_date, event_time";
$events_result = mysqli_query($conn, $events_query);

// Count events by status
$confirmed_count = 0;
$queued_count = 0;
$cancelled_count = 0;
$today_count = 0;

// Get today's date
$today = date('Y-m-d');

// Process events
$upcoming_events = [];
if ($events_result && mysqli_num_rows($events_result) > 0) {
    while ($event = mysqli_fetch_assoc($events_result)) {
        // Count by status
        switch ($event['reservation_status']) {
            case 'Confirmed':
                $confirmed_count++;
                break;
            case 'Pending':
                $queued_count++;
                break;
            case 'Cancelled':
                $cancelled_count++;
                break;
        }
        
        // Count today's events
        if ($event['event_date'] == $today) {
            $today_count++;
        }
        
        // Get upcoming events (next 7 days)
        if ($event['event_date'] >= $today && $event['reservation_status'] != 'Cancelled') {
            $upcoming_events[] = $event;
        }
    }
    
    // Reset pointer for later use
    mysqli_data_seek($events_result, 0);
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
      <?php include '../header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Reservation Management</title>

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --glass-effect: rgba(255, 255, 255, 0.85);
        }

        .glass-effect {
            background: var(--glass-effect);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-card {
            transition: all 0.3s ease;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
        }

        .calendar-day {
            transition: all 0.2s ease;
            border-radius: 12px;
            cursor: pointer;
            position: relative;
        }

        .calendar-day:hover {
            background-color: #e0e7ff;
        }

        .calendar-day.active {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
        }

        .event-item {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .event-item:hover {
            transform: translateX(5px);
            border-left-color: #667eea;
            background-color: #f8faff;
        }

        .dashboard-header {
            background: var(--primary-gradient);
            border-radius: 20px;
            box-shadow: 0 10px 30px -5px rgba(102, 126, 234, 0.4);
        }

        .modal-box {
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .btn-modern {
            border-radius: 12px;
            transition: all 0.2s ease;
            font-weight: 500;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        }

        .table-modern {
            border-radius: 12px;
            overflow: hidden;
        }

        .table-modern th {
            background-color: #f8fafc;
            padding: 16px;
            font-weight: 600;
            color: #64748b;
        }

        .table-modern td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .empty-state {
            padding: 40px 20px;
            background-color: #f8fafc;
            border-radius: 16px;
            border: 2px dashed #e2e8f0;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
      
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .event-list {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>

        <div class="container mx-auto px-4 py-8">
        

            <!-- Stats Cards -->
            <div class="glass-effect p-6 mb-8">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                        <span class="p-2 mr-3 rounded-lg bg-blue-100 text-blue-600">
                            <i class="fas fa-chart-line"></i>
                        </span>
                        Reservations Dashboard
                    </h2>
                   
                </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Confirmed Reservations -->
    <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#F7B32B]">Confirmed</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $confirmed_count; ?></h3>
                <p class="text-xs opacity-70 mt-1">Events confirmed</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Queued Reservations -->
    <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#F7B32B]">Queued</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $queued_count; ?></h3>
                <p class="text-xs opacity-70 mt-1">Pending approval</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                <i class="fas fa-clock text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Cancelled Reservations -->
    <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#F7B32B]">Cancelled</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $cancelled_count; ?></h3>
                <p class="text-xs opacity-70 mt-1">Cancelled events</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                <i class="fas fa-times-circle text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Today's Reservations -->
    <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#F7B32B]">Today's</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $today_count; ?></h3>
                <p class="text-xs opacity-70 mt-1">Events today</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                <i class="fas fa-calendar-check text-2xl"></i>
            </div>
        </div>
    </div>
</div>

            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Calendar & Upcoming Events -->
                <div class="lg:col-span-2 space-y-6">
                  
                    
                    <!-- Events Table Section -->
                    <div class="glass-effect p-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                            <h2 class="text-xl font-semibold text-gray-800">Event Reservations</h2>
                             <!-- Trigger Button -->
  <label for="event-modal" class="btn btn-modern bg-white text-secondary hover:bg-gray-100 border-0 cursor-pointer">
    <i class="fas fa-plus mr-2"></i> New Event
  </label>
                          
                        </div>
                        
                        <div class="overflow-x-auto rounded-lg">
                            <table class="table table-modern w-full">
                                <thead>
                                    <tr>
                                        <th>Event Name</th>
                                        <th>Date & Time</th>
                                        <th>Guests</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($events_result && mysqli_num_rows($events_result) > 0): ?>
                                        <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                                            <tr class="hover:bg-base-200">
                                                <td class="font-medium"><?php echo htmlspecialchars($event['event_name']); ?></td>
                                                <td>
                                                    <?php 
                                                    $event_date = date('M j, Y', strtotime($event['event_date']));
                                                    $event_time = date('g:i A', strtotime($event['event_time']));
                                                    echo "$event_date at $event_time";
                                                    ?>
                                                </td>
                                                <td><?php echo $event['num_guests']; ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    switch ($event['reservation_status']) {
                                                        case 'Confirmed':
                                                            $status_class = 'status-confirmed';
                                                            break;
                                                        case 'Pending':
                                                            $status_class = 'status-pending';
                                                            break;
                                                        case 'Cancelled':
                                                            $status_class = 'status-cancelled';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo $event['reservation_status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="flex space-x-2">
                                                        <button class="btn btn-xs btn-outline">View</button>
                                                        <button class="btn btn-xs btn-primary">Edit</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-8">
                                                <div class="empty-state">
                                                    <i class="fas fa-calendar-plus text-4xl mb-3 opacity-30"></i>
                                                    <p class="text-gray-500 mb-4">No events scheduled yet</p>
                                                    <label for="event-modal" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-plus mr-2"></i> Create New Event
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Events -->
                <div class="space-y-6">
                    <!-- Upcoming Events -->
                    <div class="glass-effect p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-calendar-day mr-2 text-primary"></i>
                            Upcoming Events
                        </h3>
                        
                        <?php if (!empty($upcoming_events)): ?>
                            <div class="event-list space-y-4">
                                <?php foreach ($upcoming_events as $event): ?>
                                    <div class="event-item p-4 rounded-lg bg-base-100 border border-base-300">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-semibold"><?php echo htmlspecialchars($event['event_name']); ?></h4>
                                                <p class="text-sm text-gray-500">
                                                    <?php 
                                                    $event_date = date('M j, Y', strtotime($event['event_date']));
                                                    $event_time = date('g:i A', strtotime($event['event_time']));
                                                    echo "$event_date at $event_time";
                                                    ?>
                                                </p>
                                                <p class="text-sm mt-1"><?php echo $event['num_guests']; ?> guests</p>
                                            </div>
                                            <?php 
                                            $status_class = '';
                                            switch ($event['reservation_status']) {
                                                case 'Confirmed':
                                                    $status_class = 'status-confirmed';
                                                    break;
                                                case 'Pending':
                                                    $status_class = 'status-pending';
                                                    break;
                                                case 'Cancelled':
                                                    $status_class = 'status-cancelled';
                                                    break;
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?> text-xs">
                                                <?php echo $event['reservation_status']; ?>
                                            </span>
                                        </div>
                                        <div class="mt-2 flex justify-between items-center">
                                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars($event['venue']); ?></span>
                                            <button class="btn btn-xs btn-outline">Details</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state text-center py-8">
                                <i class="fas fa-calendar-day text-4xl mb-3 opacity-30"></i>
                                <p class="text-gray-500">No upcoming events</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                
                </div>
            </div>
        </div>
    </div>
<!-- Modal -->
<input type="checkbox" id="event-modal" class="modal-toggle" />
<div class="modal">
  <div class="modal-box max-w-5xl rounded-lg shadow-xl p-8 bg-base-100">
    <!-- Modal Header -->
    <div class="flex justify-between items-center mb-6">
      <h3 class="font-bold text-2xl text-primary">✨ New Event Reservation</h3>
      <div class="flex gap-2">
        <label for="event-modal" class="btn btn-sm btn-circle btn-ghost">✕</label>
      </div>
    </div>
    
    <!-- Form -->
    <form id="eventForm" action="sub-modules/add_event.php" method="POST" class="space-y-6">
      
      <!-- Customer Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="label"><span class="label-text font-medium">Customer Name</span></label>
          <input type="text" name="customer_name" class="input input-bordered w-full rounded-lg" placeholder="Enter customer name" required>
        </div>
        <div>
          <label class="label"><span class="label-text font-medium">Customer Email</span></label>
          <input type="email" name="customer_email" class="input input-bordered w-full rounded-lg" placeholder="Enter customer email" required>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="label"><span class="label-text font-medium">Customer Phone</span></label>
          <input type="text" name="customer_phone" class="input input-bordered w-full rounded-lg" placeholder="Enter customer phone" required>
        </div>
        <div>
          <label class="label"><span class="label-text font-medium">Event Name</span></label>
          <input type="text" name="event_name" class="input input-bordered w-full rounded-lg" placeholder="Enter event name" required>
        </div>
      </div>
      
      <!-- Event Info with Prices -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="label"><span class="label-text font-medium">Event Type</span></label>
          <select name="event_type" id="event_type" class="select select-bordered w-full rounded-lg" required>
            <option value="" disabled selected>Select event type</option>
            <option value="Wedding" data-price="500">Wedding (₱500)</option>
            <option value="Birthday" data-price="300">Birthday Party (₱300)</option>
            <option value="Corporate" data-price="400">Corporate Event (₱400)</option>
            <option value="Conference" data-price="600">Conference (₱600)</option>
            <option value="Seminar" data-price="350">Seminar (₱350)</option>
            <option value="Anniversary" data-price="450">Anniversary (₱450)</option>
            <option value="Baby Shower" data-price="250">Baby Shower (₱250)</option>
            <option value="Reunion" data-price="200">Family Reunion (₱200)</option>
            <option value="Gala" data-price="800">Gala Dinner (₱800)</option>
            <option value="Product Launch" data-price="700">Product Launch (₱700)</option>
            <option value="Other" data-price="350">Other (₱350)</option>
          </select>
        </div>
        <div>
          <label class="label"><span class="label-text font-medium">Venue</span></label>
          <select name="venue" id="venue" class="select select-bordered w-full rounded-lg" required>
            <option value="" disabled selected>Select venue</option>
            <option value="Grand Ballroom" data-price="1000">Grand Ballroom (₱1,000)</option>
            <option value="Garden Pavilion" data-price="800">Garden Pavilion (₱800)</option>
            <option value="Beachfront" data-price="1200">Beachfront Area (₱1,200)</option>
            <option value="Conference Hall A" data-price="600">Conference Hall A (₱600)</option>
            <option value="Conference Hall B" data-price="500">Conference Hall B (₱500)</option>
            <option value="Executive Room" data-price="400">Executive Room (₱400)</option>
            <option value="Rooftop Terrace" data-price="900">Rooftop Terrace (₱900)</option>
            <option value="Poolside" data-price="750">Poolside Area (₱750)</option>
            <option value="Main Restaurant" data-price="550">Main Restaurant (₱550)</option>
            <option value="Private Dining" data-price="450">Private Dining Room (₱450)</option>
            <option value="Other" data-price="500">Other Location (₱500)</option>
          </select>
        </div>
        <div>
          <label class="label"><span class="label-text font-medium">Event Package</span></label>
          <select name="event_package" id="event_package" class="select select-bordered w-full rounded-lg" required>
            <option value="" disabled selected>Select package</option>
            <option value="Basic" data-price="50">Basic Package (₱50/guest)</option>
            <option value="Standard" data-price="75">Standard Package (₱75/guest)</option>
            <option value="Premium" data-price="100">Premium Package (₱100/guest)</option>
            <option value="Platinum" data-price="150">Platinum Package (₱150/guest)</option>
            <option value="Corporate Basic" data-price="60">Corporate Basic (₱60/guest)</option>
            <option value="Corporate Premium" data-price="90">Corporate Premium (₱90/guest)</option>
            <option value="Wedding Essential" data-price="80">Wedding Essential (₱80/guest)</option>
            <option value="Wedding Deluxe" data-price="120">Wedding Deluxe (₱120/guest)</option>
            <option value="Birthday Basic" data-price="40">Birthday Basic (₱40/guest)</option>
            <option value="Birthday Celebration" data-price="65">Birthday Celebration (₱65/guest)</option>
            <option value="Custom" data-price="0">Custom Package (₱0/guest)</option>
          </select>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="label"><span class="label-text font-medium">Number of Guests</span></label>
          <input type="number" name="num_guests" id="num_guests" class="input input-bordered w-full rounded-lg" placeholder="Enter number of guests" required min="1">
        </div>
        <div class="bg-blue-50 p-4 rounded-lg">
          <div class="flex justify-between items-center mb-2">
            <span class="font-medium">Event Type:</span>
            <span id="event_type_price">₱0.00</span>
          </div>
          <div class="flex justify-between items-center mb-2">
            <span class="font-medium">Venue:</span>
            <span id="venue_price">₱0.00</span>
          </div>
          <div class="flex justify-between items-center mb-2">
            <span class="font-medium">Package (<span id="guest_count">0</span> guests):</span>
            <span id="package_price">₱0.00</span>
          </div>
          <div class="border-t pt-2 mt-2 flex justify-between items-center font-bold text-lg">
            <span>Total Amount:</span>
            <span id="total_amount">₱0.00</span>
          </div>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="label"><span class="label-text font-medium">Event Date</span></label>
          <input type="date" name="event_date" class="input input-bordered w-full rounded-lg" placeholder="Enter event date" required>
        </div>
        <div>
          <label class="label"><span class="label-text font-medium">Event Time</span></label>
          <input type="time" name="event_time" class="input input-bordered w-full rounded-lg" placeholder="Enter event start time" required>
        </div>
      </div>
      
      <div>
        <label class="label"><span class="label-text font-medium">Special Requests</span></label>
        <textarea name="special_requests" class="textarea textarea-bordered w-full rounded-lg" rows="3" placeholder="Any dietary restrictions, setup preferences, or additional notes..."></textarea>
      </div>
      
      <!-- Hidden fields to store calculated values -->
      <input type="hidden" name="calculated_total" id="calculated_total" value="0">
      
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <div class="flex items-end">
          <div class="alert alert-info">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Total will be calculated automatically</span>
          </div>
        </div>
      </div>

      <!-- Modal Actions -->
      <div class="modal-action flex justify-end gap-4 pt-4">
        <button type="submit" class="btn btn-primary px-6 rounded-lg">💾 Save Reservation</button>
        <label for="event-modal" class="btn btn-ghost rounded-lg">Cancel</label>
      </div>
    </form>
  </div>
</div>

<!-- Price Settings Modal -->
<div class="modal" id="price-settings-modal">
  <div class="modal-box max-w-3xl">
    <h3 class="font-bold text-lg">💰 Price Settings</h3>
    <p class="py-4">Set prices for event types, venues, and packages</p>
    
    <div class="overflow-x-auto">
      <table class="table table-zebra">
        <thead>
          <tr>
            <th>Category</th>
            <th>Option</th>
            <th>Price</th>
          </tr>
        </thead>
        <tbody>
          <!-- Event Types -->
          <tr>
            <td rowspan="11" class="font-bold">Event Types</td>
            <td>Wedding</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="event_type" data-value="Wedding" value="500"></td>
          </tr>
          <tr>
            <td>Birthday Party</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="event_type" data-value="Birthday" value="300"></td>
          </tr>
          <tr>
            <td>Corporate Event</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="event_type" data-value="Corporate" value="400"></td>
          </tr>
          <tr>
            <td>Conference</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="event_type" data-value="Conference" value="600"></td>
          </tr>
          <tr>
            <td>Seminar</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="event_type" data-value="Seminar" value="350"></td>
          </tr>
          <tr>
            <td>Anniversary</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="event_type" data-value="Anniversary" value="450"></td>
          </tr>
          <tr>
            <td>Baby Shower</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="event_type" data-value="Baby Shower" value="250"></td>
          </tr>
          <tr>
            <td>Family Reunion</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="event_type" data-value="Reunion" value="200"></td>
          </tr>
          <tr>
            <td>Gala Dinner</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="event_type" data-value="Gala" value="800"></td>
          </tr>
          <tr>
            <td>Product Launch</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="event_type" data-value="Product Launch" value="700"></td>
          </tr>
          <tr>
            <td>Other</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="event_type" data-value="Other" value="350"></td>
          </tr>
          
          <!-- Venues -->
          <tr>
            <td rowspan="11" class="font-bold">Venues</td>
            <td>Grand Ballroom</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="venue" data-value="Grand Ballroom" value="1000"></td>
          </tr>
          <tr>
            <td>Garden Pavilion</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="venue" data-value="Garden Pavilion" value="800"></td>
          </tr>
          <tr>
            <td>Beachfront Area</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="venue" data-value="Beachfront" value="1200"></td>
          </tr>
          <tr>
            <td>Conference Hall A</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="venue" data-value="Conference Hall A" value="600"></td>
          </tr>
          <tr>
            <td>Conference Hall B</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="venue" data-value="Conference Hall B" value="500"></td>
          </tr>
          <tr>
            <td>Executive Room</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="venue" data-value="Executive Room" value="400"></td>
          </tr>
          <tr>
            <td>Rooftop Terrace</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="venue" data-value="Rooftop Terrace" value="900"></td>
          </tr>
          <tr>
            <td>Poolside Area</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="venue" data-value="Poolside" value="750"></td>
          </tr>
          <tr>
            <td>Main Restaurant</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="venue" data-value="Main Restaurant" value="550"></td>
          </tr>
          <tr>
            <td>Private Dining Room</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="venue" data-value="Private Dining" value="450"></td>
          </tr>
          <tr>
            <td>Other Location</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="venue" data-value="Other" value="500"></td>
          </tr>
          
          <!-- Packages -->
          <tr>
            <td rowspan="11" class="font-bold">Packages</td>
            <td>Basic Package</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="package" data-value="Basic" value="50"></td>
          </tr>
          <tr>
            <td>Standard Package</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="package" data-value="Standard" value="75"></td>
          </tr>
          <tr>
            <td>Premium Package</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="package" data-value="Premium" value="100"></td>
          </tr>
          <tr>
            <td>Platinum Package</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="package" data-value="Platinum" value="150"></td>
          </tr>
          <tr>
            <td>Corporate Basic</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="package" data-value="Corporate Basic" value="60"></td>
          </tr>
          <tr>
            <td>Corporate Premium</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="package" data-value="Corporate Premium" value="90"></td>
          </tr>
          <tr>
            <td>Wedding Essential</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="package" data-value="Wedding Essential" value="80"></td>
          </tr>
          <tr>
            <td>Wedding Deluxe</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="package" data-value="Wedding Deluxe" value="120"></td>
          </tr>
          <tr>
            <td>Birthday Basic</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="package" data-value="Birthday Basic" value="40"></td>
          </tr>
          <tr>
            <td>Birthday Celebration</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="package" data-value="Birthday Celebration" value="65"></td>
          </tr>
          <tr>
            <td>Custom Package</td>
            <td><input type="number" class="input input-bordered input-sm price-input" data-category="package" data-value="Custom" value="0"></td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div class="modal-action">
      <label for="price-settings-modal" class="btn btn-primary">Save Prices</label>
      <label for="price-settings-modal" class="btn btn-ghost">Cancel</label>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Price configuration
  const prices = {
    event_type: {},
    venue: {},
    package: {}
  };
  
  // Initialize prices from data attributes
  function initializePrices() {
    document.querySelectorAll('select option[data-price]').forEach(option => {
      const category = option.parentElement.id;
      const value = option.value;
      const price = parseFloat(option.getAttribute('data-price'));
      
      if (category && value) {
        if (!prices[category]) prices[category] = {};
        prices[category][value] = price;
      }
    });
    
    // Also initialize from price settings inputs if available
    document.querySelectorAll('.price-input').forEach(input => {
      const category = input.getAttribute('data-category');
      const value = input.getAttribute('data-value');
      const price = parseFloat(input.value);
      
      if (category && value && !isNaN(price)) {
        if (!prices[category]) prices[category] = {};
        prices[category][value] = price;
        
        // Update the option in the select if it exists
        const option = document.querySelector(`#${category} option[value="${value}"]`);
        if (option) {
          option.setAttribute('data-price', price);
          const originalText = option.textContent.split(' (')[0];
          option.textContent = `${originalText} (₱${price}${category === 'package' ? '/guest' : ''})`;
        }
      }
    });
  }
  
  // Calculate total amount
  function calculateTotal() {
    const eventType = document.getElementById('event_type').value;
    const venue = document.getElementById('venue').value;
    const eventPackage = document.getElementById('event_package').value;
    const numGuests = parseInt(document.getElementById('num_guests').value) || 0;
    
    const eventTypePrice = prices.event_type[eventType] || 0;
    const venuePrice = prices.venue[venue] || 0;
    const packagePrice = prices.package[eventPackage] || 0;
    
    const packageTotal = packagePrice * numGuests;
    const total = eventTypePrice + venuePrice + packageTotal;
    
    // Update display
    document.getElementById('event_type_price').textContent = `₱${eventTypePrice.toFixed(2)}`;
    document.getElementById('venue_price').textContent = `₱${venuePrice.toFixed(2)}`;
    document.getElementById('package_price').textContent = `₱${packageTotal.toFixed(2)}`;
    document.getElementById('guest_count').textContent = numGuests;
    document.getElementById('total_amount').textContent = `₱${total.toFixed(2)}`;
    
    // Update hidden field
    document.getElementById('calculated_total').value = total.toFixed(2);
  }
  
  // Set up event listeners
  function setupEventListeners() {
    document.getElementById('event_type').addEventListener('change', calculateTotal);
    document.getElementById('venue').addEventListener('change', calculateTotal);
    document.getElementById('event_package').addEventListener('change', calculateTotal);
    document.getElementById('num_guests').addEventListener('input', calculateTotal);
    
    document.querySelectorAll('.price-input').forEach(input => {
      input.addEventListener('input', function() {
        const category = this.getAttribute('data-category');
        const value = this.getAttribute('data-value');
        const price = parseFloat(this.value) || 0;
        
        if (category && value) {
          prices[category][value] = price;
          
          const option = document.querySelector(`#${category} option[value="${value}"]`);
          if (option) {
            option.setAttribute('data-price', price);
            const originalText = option.textContent.split(' (')[0];
            option.textContent = `${originalText} (₱${price}${category === 'package' ? '/guest' : ''})`;
          }
          
          if (document.getElementById(category).value === value) {
            calculateTotal();
          }
        }
      });
    });
  }
  
  // Initialize
  initializePrices();
  setupEventListeners();
  calculateTotal();

  // ✅ Handle form submission + redirect
  const form = document.getElementById('event_form'); // change to your form's ID
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();

      fetch(form.action, {
        method: "POST",
        body: new FormData(form)
      })
      .then(res => res.json()) // make sure PHP echoes JSON like { success: true }
      .then(data => {
        if (data.success) {
          // SweetAlert before redirect
          Swal.fire({
            icon: 'success',
            title: 'Reservation saved!',
            showConfirmButton: false,
            timer: 1200
          }).then(() => {
            window.location.href = "main_reservation.php"; // your redirect page
          });
        } else {
          Swal.fire("Error", data.message || "Something went wrong!", "error");
        }
      })
      .catch(err => {
        Swal.fire("Error", "Request failed, please try again.", "error");
        console.error(err);
      });
    });
  }
});
</script>

</script>
    <script src="../JavaScript/sidebar.js"></script>

    <script>
        // Simple JavaScript for interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Calendar date selection
            const dates = document.querySelectorAll('.calendar-day');
            dates.forEach(date => {
                date.addEventListener('click', function() {
                    dates.forEach(d => d.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Add some sample events for demonstration
            const today = new Date();
            const dayOfMonth = today.getDate();
            
            // Mark today's date in the calendar
            dates.forEach(date => {
                if (parseInt(date.textContent) === dayOfMonth && date.textContent !== '') {
                    date.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>