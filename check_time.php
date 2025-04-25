<?php
require_once 'includes/db_connect.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ορίζουμε τη ζώνη ώρας στην PHP σε Europe/Athens
date_default_timezone_set('Europe/Athens');
echo "PHP Time (Europe/Athens): " . date('Y-m-d H:i:s') . "\n";

// Ρυθμίζουμε τη ζώνη ώρας για τη σύνδεση MySQL
$conn->query("SET time_zone = 'Europe/Athens'");

// Εκτελούμε query για να δούμε την ώρα που επιστρέφει η MySQL
$result = $conn->query("SELECT NOW() AS now_time");
$row = $result->fetch_assoc();
echo "MySQL NOW(): " . $row['now_time'] . "\n";

// Εναλλακτικά, αν θέλετε να δείτε τη μετατροπή από UTC σε Europe/Athens:
$result2 = $conn->query("SELECT CONVERT_TZ(NOW(), 'UTC', 'Europe/Athens') AS local_time");
$row2 = $result2->fetch_assoc();
echo "MySQL CONVERT_TZ(NOW(),'UTC','Europe/Athens'): " . $row2['local_time'] . "\n";
?>
