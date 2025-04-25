<?php
session_start();

// ✅ Admin Authentication
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("Μόνο οι διαχειριστές έχουν πρόσβαση εδώ.");
}

require_once 'includes/db_connect.php';

$today = new DateTime();

// Παίρνουμε τους χρήστες και τα πακέτα τους
$query = "
    SELECT 
        u.id, 
        u.full_name, 
        u.balance,
        u.package_id,
        u.package_expiry_date,
        p.sessions_total,
        p.duration
    FROM users u
    LEFT JOIN packages p ON u.package_id = p.id
    WHERE u.is_admin = 0
    ORDER BY u.full_name
";

$result = $conn->query($query);

if (!$result) {
    die("Σφάλμα στο query: " . $conn->error);
}

// Headers για CSV Export
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=customer_balances.csv');

// Output stream
$output = fopen('php://output', 'w');

// Επικεφαλίδες
fputcsv($output, ['Όνομα Πελάτη', 'Υπόλοιπο Ποσό (€)', 'Υπόλοιπες Συνεδρίες', 'Ημ. Λήξης Πακέτου']);

// Δεδομένα πελατών
while ($row = $result->fetch_assoc()) {
    $user_id          = $row['id'];
    $full_name        = $row['full_name'];
    $balance          = number_format($row['balance'], 2);
    $package_expiry   = $row['package_expiry_date'] ? date("d/m/Y", strtotime($row['package_expiry_date'])) : '—';
    $sessions_total   = intval($row['sessions_total']);
    $package_duration = $row['duration'];

    // Αν δεν έχει πακέτο ➜ κενές συνεδρίες
    if (empty($row['package_id']) || $sessions_total === 0) {
        $remaining_sessions = '—';
    } else {
        // ➜ Υπολογισμός περιόδου
        if ($package_duration === 'weekly') {
            $start_date = date("Y-m-d", strtotime("monday this week"));
            $end_date   = date("Y-m-d", strtotime("sunday this week"));
        } elseif ($package_duration === 'monthly') {
            $start_date = date('Y-m-01');
            $end_date   = date('Y-m-t');
        } else {
            $start_date = null;
            $end_date   = null;
        }

        // ➜ Υπολογισμός πόσες κρατήσεις έκανε στην περίοδο
        $completed_sessions = 0;

        if (!empty($start_date) && !empty($end_date)) {
            $stmt_bookings = $conn->prepare("
                SELECT COUNT(*) 
                FROM bookings 
                WHERE user_id = ? AND status = 'active' AND is_trial = 0
                AND booking_date BETWEEN ? AND ?
            ");
            $stmt_bookings->bind_param("iss", $user_id, $start_date, $end_date);
            $stmt_bookings->execute();
            $stmt_bookings->bind_result($completed_sessions);
            $stmt_bookings->fetch();
            $stmt_bookings->close();

            $remaining_sessions = max(0, $sessions_total - intval($completed_sessions));
        } else {
            $remaining_sessions = '—';
        }
    }

    // ➜ Γράφουμε τη γραμμή στο CSV
    fputcsv($output, [
        $full_name,
        $balance,
        $remaining_sessions,
        $package_expiry
    ]);
}

fclose($output);
exit;
?>

