<?php
session_start();
header('Content-Type: application/json');
require_once("../../main_connection.php");

// POS DB (has `tables` + `notification_m1`)
$db_name_pos = "rest_m1_trs"; 
if (!isset($connections[$db_name_pos])) {
    die(json_encode(['status' => 'error', 'message' => "POS DB connection not found"]));
}
$conn_pos = $connections[$db_name_pos];

// Table turnover DB
$db_name_turnover = "rest_m8_table_turnover";
if (!isset($connections[$db_name_turnover])) {
    die(json_encode(['status' => 'error', 'message' => "Table turnover DB connection not found"]));
}
$conn_turnover = $connections[$db_name_turnover];

// Audit DB
$db_name_audit = "rest_core_2_usm";
if (!isset($connections[$db_name_audit])) {
    die(json_encode(['status' => 'error', 'message' => "Audit DB connection not found"]));
}
$conn_audit = $connections[$db_name_audit];

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// Inputs
$table_name = trim($_POST['name'] ?? '');
$category   = trim($_POST['category'] ?? '');
$capacity   = isset($_POST['capacity']) ? (int) $_POST['capacity'] : 0;
$status     = trim($_POST['status'] ?? 'Available');

if ($table_name === '' || $category === '' || $capacity <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

try {
    // Begin transactions in ALL DBs
    $conn_pos->begin_transaction();
    $conn_turnover->begin_transaction();
    $conn_audit->begin_transaction();

    // 1️⃣ Insert into tables (POS DB)
    $stmt = $conn_pos->prepare("INSERT INTO tables (name, category, capacity, status) VALUES (?, ?, ?, ?)");
    if (!$stmt) throw new Exception("Prepare failed for tables");
    $stmt->bind_param("ssis", $table_name, $category, $capacity, $status);
    if (!$stmt->execute()) throw new Exception("Execution failed for tables");
    $table_id = $stmt->insert_id;
    $stmt->close();

    // 2️⃣ Insert into table_metrics (turnover DB)
    $metricsStmt = $conn_turnover->prepare("
        INSERT INTO table_metrics (table_id, turnover_count, avg_wait_time_minutes, record_date)
        VALUES (?, 0, 0, NOW())
    ");
    if (!$metricsStmt) throw new Exception("Prepare failed for metrics");
    $metricsStmt->bind_param("i", $table_id);
    if (!$metricsStmt->execute()) throw new Exception("Execution failed for metrics");
    $metricsStmt->close();

    // 3️⃣ Insert notification (POS DB)
    $notification_title   = "New Table Added";
    $notification_message = "A new table named <strong>$table_name</strong> was added.";
    $notification_status  = "Unread";
    $date_sent            = date("Y-m-d H:i:s");

    $senderName     = $_SESSION['Name'] ?? 'System';
    $senderRole     = $_SESSION['Role'] ?? 'Admin';
    $targetUserID   = (int) ($_SESSION['User_ID'] ?? 0);
    $recipient_role = $senderRole;
    $module         = "Table Reservation & Seating";

    $notifQuery = $conn_pos->prepare("
        INSERT INTO notification_m1 (title, message, status, date_sent, sent_by, User_ID, recipient_role, module)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$notifQuery) throw new Exception("Prepare failed for notifications");
    $notifQuery->bind_param(
        "sssssiis",
        $notification_title,
        $notification_message,
        $notification_status,
        $date_sent,
        $senderName,
        $targetUserID,
        $recipient_role,
        $module
    );
    if (!$notifQuery->execute()) throw new Exception("Execution failed for notifications");
    $notifQuery->close();

    // 4️⃣ Get department details from department_accounts (Audit DB)
    $employee_id = $_SESSION['employee_id'] ?? 0;

    $deptQuery = $conn_audit->prepare("
        SELECT Dept_id, dept_name, role, employee_name, employee_id
        FROM department_accounts 
        WHERE employee_id = ?
        LIMIT 1
    ");
    if (!$deptQuery) throw new Exception("Prepare failed for department_accounts");
    $deptQuery->bind_param("i", $employee_id);
    if (!$deptQuery->execute()) throw new Exception("Execution failed for department_accounts");

    $deptResult = $deptQuery->get_result();
    if ($deptResult->num_rows > 0) {
        $deptRow        = $deptResult->fetch_assoc();
        $dept_id        = $deptRow['Dept_id'];
        $dept_name      = trim($deptRow['dept_name']);
        $role           = $deptRow['role'];
        $employee_name  = $deptRow['employee_name'];
        $employee_id    = $deptRow['employee_id'];
    } else {
        $dept_id        = $_SESSION['Dept_id'] ?? 0;
        $dept_name      = $_SESSION['dept_name'] ?? 'Unknown';
        $role           = $_SESSION['Role'] ?? 'Admin';
        $employee_name  = $_SESSION['employee_name'] ?? 'System';
        $employee_id    = $_SESSION['employee_id'] ?? 0;
    }
    $deptQuery->close();

    // 5️⃣ Insert into dept_audit_transc (Audit DB)
    $modules_cover = "Table Management";
    $action        = "Table added";
    $activity      = "Added new table: $table_name";
    $audit_date    = date("Y-m-d H:i:s");

    $auditStmt = $conn_audit->prepare("
        INSERT INTO dept_audit_transc 
        (dept_id, dept_name, modules_cover, action, activity, employee_name, employee_id, role, date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$auditStmt) throw new Exception("Prepare failed for dept_audit_transc");
    $auditStmt->bind_param(
        "isssssiss",
        $dept_id,
        $dept_name,
        $modules_cover,
        $action,
        $activity,
        $employee_name,
        $employee_id,
        $role,
        $audit_date
    );
    if (!$auditStmt->execute()) throw new Exception("Execution failed for dept_audit_transc");
    $auditStmt->close();

    // ✅ Commit all DBs
    $conn_pos->commit();
    $conn_turnover->commit();
    $conn_audit->commit();

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $conn_pos->rollback();
    $conn_turnover->rollback();
    $conn_audit->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Close connections
$conn_pos->close();
$conn_turnover->close();
$conn_audit->close();
