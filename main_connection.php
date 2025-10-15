<?php
// main_connection.php

$dbHost = "localhost"; 
$dbPass = "1234";

// ✅ database => username
$targetDatabases = [
    
    "rest_analytics"            => "rest_sid",
    "rest_soliera_usm"         => "rest_kheel",
    "rest_core_2_usm"          => "rest_che",
    "rest_m1_trs"              => "rest_than",
    "rest_m2_inventory"        => "rest_nash",
    "rest_m3_menu"             => "rest_kit",
    "rest_m4_pos"              => "rest_makmak",
    "rest_m7_billing_payments" => "rest_jade",
    "rest_m6_kot"              => "rest_ulan",
    "rest_m8_table_turnover"   => "rest_james",
    "rest_m9_wait_staff"       => "rest_gerwin",
    "rest_m10_comments_review" => "rest_bianca",
    "rest_m11_event"           => "rest_joy", 
];

$connections = [];
$errors = [];

foreach ($targetDatabases as $dbName => $dbUser) {
    $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

    if ($conn) {
        $connections[$dbName] = $conn;
    } else {
        $errors[] = "❌ Failed to connect to <strong>$dbName</strong> (user: $dbUser): " . mysqli_connect_error();
    }
}

// Debugging only (disable on production!)
if (!empty($errors)) {
    echo "<h2 style='color:red;'>❌ Connection Errors:</h2><ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}
?>
