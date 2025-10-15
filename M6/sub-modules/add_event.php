<?php
include("../../main_connection.php");
require_once '../../PHPMailer/PHPMailerAutoload.php';

$db_name = "rest_m11_event"; // ✅ Event DB

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name]; // ✅ DB connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // --- Collect Event Inputs ---
        $customer_name     = $_POST['customer_name'] ?? '';
        $customer_email    = $_POST['customer_email'] ?? '';
        $customer_phone    = $_POST['customer_phone'] ?? '';
        $event_name        = $_POST['event_name'] ?? '';
        $event_type        = $_POST['event_type'] ?? '';
        $event_date        = $_POST['event_date'] ?? '';
        $event_time        = $_POST['event_time'] ?? '';
        $venue             = $_POST['venue'] ?? '';
        $num_guests        = (int) ($_POST['num_guests'] ?? 0);
        $special_requests  = $_POST['special_requests'] ?? '';
        $event_package     = $_POST['event_package'] ?? '';
        $reservation_status = "Pending";
        $payment_status     = "Pending";

        // --- Validation ---
        if (empty($customer_name) || empty($customer_email) || empty($event_name) || empty($event_date) || empty($event_time)) {
            throw new Exception("⚠️ Required fields missing.");
        }

        // --- Financials ---
        $total_amount = isset($_POST['calculated_total']) ? floatval($_POST['calculated_total']) : 0;
        $amount_paid = $total_amount * 0.20; // ✅ Auto 20% downpayment

        // --- Insert into DB ---
        $sql = "INSERT INTO event_reservations 
            (customer_name, customer_email, customer_phone, event_name, event_type, event_date, event_time, venue, num_guests, special_requests, reservation_status, payment_status, total_amount, amount_paid, event_package) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param(
            "ssssssssisssdds",
            $customer_name, $customer_email, $customer_phone,
            $event_name, $event_type, $event_date, $event_time,
            $venue, $num_guests, $special_requests,
            $reservation_status, $payment_status,
            $total_amount, $amount_paid, $event_package
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $reservation_id = $stmt->insert_id;
        $stmt->close();

        // --- Email Confirmation ---
        $subject = "Event Reservation Confirmation - Soliera Restaurant";
        $message = "
            <div style='font-family: Arial, sans-serif; color:#333; line-height:1.6;'>
                <h2 style='color:#8b0000;'>Event Reservation Confirmation</h2>
                <p>Dear <strong>$customer_name</strong>,</p>
                <p>Thank you for booking your event with <em>Soliera Restaurant</em>. 
                Your reservation has been received and is currently <strong style='color:orange;'>pending confirmation</strong>.</p>
                
                <table style='width:100%; border-collapse:collapse; margin:15px 0;'>
                    <tr><td style='padding:8px; border:1px solid #ddd;'><strong>Event Name:</strong></td><td style='padding:8px; border:1px solid #ddd;'>$event_name</td></tr>
                    <tr><td style='padding:8px; border:1px solid #ddd;'><strong>Event Type:</strong></td><td style='padding:8px; border:1px solid #ddd;'>$event_type</td></tr>
                    <tr><td style='padding:8px; border:1px solid #ddd;'><strong>Venue:</strong></td><td style='padding:8px; border:1px solid #ddd;'>$venue</td></tr>
                    <tr><td style='padding:8px; border:1px solid #ddd;'><strong>Package:</strong></td><td style='padding:8px; border:1px solid #ddd;'>$event_package</td></tr>
                    <tr><td style='padding:8px; border:1px solid #ddd;'><strong>Guests:</strong></td><td style='padding:8px; border:1px solid #ddd;'>$num_guests</td></tr>
                    <tr><td style='padding:8px; border:1px solid #ddd;'><strong>Date:</strong></td><td style='padding:8px; border:1px solid #ddd;'>$event_date</td></tr>
                    <tr><td style='padding:8px; border:1px solid #ddd;'><strong>Time:</strong></td><td style='padding:8px; border:1px solid #ddd;'>$event_time</td></tr>
                    <tr><td style='padding:8px; border:1px solid #ddd;'><strong>Total Amount:</strong></td><td style='padding:8px; border:1px solid #ddd;'>₱" . number_format($total_amount, 2) . "</td></tr>
                    <tr><td style='padding:8px; border:1px solid #ddd;'><strong>Downpayment (20%):</strong></td><td style='padding:8px; border:1px solid #ddd;'>₱" . number_format($amount_paid, 2) . "</td></tr>
                </table>

                <p><strong>Special Requests:</strong> " . (!empty($special_requests) ? $special_requests : "None") . "</p>
                <p>Our event coordinator will contact you within <strong>24 hours</strong> to finalize details.</p>

                <p style='font-size:12px; color:#777;'>
                    Soliera Restaurant Events Team<br>
                    📞 +63-900-123-4567<br>
                    📧 events@soliera.com<br>
                    <em>This is an automated message. Please do not reply directly to this email.</em>
                </p>
            </div>
        ";

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'VehicleReservationManagement@gmail.com'; // ✅ Gmail
        $mail->Password   = 'fzja ezgo ojdu fobc'; // ✅ App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('Soliera_Hotel&Restaurant@gmail.com', 'Soliera Restaurant Events');
        $mail->addAddress($customer_email);
        $mail->addReplyTo('events@soliera.com', 'Events Team');

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->send();

        // ✅ Redirect success
        header("Location: ../main_reservation.php?type=event&id=" . $reservation_id);
        exit();

    } catch (Exception $e) {
        error_log("❌ Event reservation error: " . $e->getMessage());
        header("Location: reservation_error.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
