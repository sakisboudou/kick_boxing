<?php
session_start();
require_once 'includes/db_connect.php';

$conn->set_charset("utf8");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Europe/Athens');

// ➤ Ημερομηνίες εβδομάδας
$start_date_week = date("d/m/Y", strtotime("monday this week"));
$end_date_week   = date("d/m/Y", strtotime("sunday this week"));

echo "<h3>🗓️ Εβδομαδιαίο Reconciliation: $start_date_week ➜ $end_date_week</h3><hr>";
echo "<button onclick=\"window.location.href='admin_panel.php'\" style='padding: 8px 12px; background-color: #337ab7; color: #fff; border: none; border-radius: 4px; cursor: pointer;'>
         Επιστροφή στο Admin Panel
      </button>";


// ➤ Παίρνουμε όλους τους χρήστες εκτός admin
$users_q = $conn->query("
    SELECT id, full_name, balance, weekly_bookings
    FROM users
    WHERE is_admin = 0
");

if (!$users_q || $users_q->num_rows === 0) {
    echo "<h3>⚠️ Δεν βρέθηκαν χρήστες για reconciliation.</h3>";
    exit;
}

$conn->begin_transaction();

try {
    while ($user = $users_q->fetch_assoc()) {

        $user_id          = intval($user['id']);
        $full_name        = htmlspecialchars($user['full_name']);
        $balance          = floatval($user['balance']);
        $weekly_bookings  = intval($user['weekly_bookings']);
        
      


        echo "<h4>👤 {$full_name}</h4>";
        echo "📅 Κρατήσεις Εβδομάδας: <strong>{$weekly_bookings}</strong><br>";
        echo "💰 Υπόλοιπο Πριν Χρέωση: <strong>€" . number_format($balance, 2) . "</strong><br>";

        // ➤ Υπολογισμός χρέωσης
        $price_to_charge = calculate_total_booking_cost($weekly_bookings);
        echo "✅ Χρέωση: <strong>€" . number_format($price_to_charge, 2) . "</strong><br>";

        // ➤ Υπολογισμός νέου υπολοίπου
        $new_balance = max(0, $balance - $price_to_charge);
        echo "💰 Νέο Υπόλοιπο: <strong>€" . number_format($new_balance, 2) . "</strong><br>";

        // ➤ Υπολογισμός νέων διαθέσιμων συνεδριών
        $weekly_sessions_allowed = calculate_max_sessions($new_balance);
        echo "📌 Επιτρεπόμενες Συνεδρίες Εβδομάδας: <strong>{$weekly_sessions_allowed}</strong><br>";

        // ➤ Ενημέρωση βάσεων δεδομένων
        $monday_next_week = date("Y-m-d", strtotime("monday next week"));

        $update = $conn->prepare("
            UPDATE users
            SET balance = ?, weekly_bookings = 0, week_start_date = ?, weekly_sessions_allowed = ?
            WHERE id = ?
        ");

        if (!$update) {
            throw new Exception("Σφάλμα στο prepare για update users: " . $conn->error);
        }

        $update->bind_param("dsii", $new_balance, $monday_next_week, $weekly_sessions_allowed, $user_id);
        $update->execute();

        if ($update->affected_rows === -1) {
            throw new Exception("Σφάλμα στο update user ID {$user_id}: " . $update->error);
        }

        // ➤ Καταγραφή πληρωμής (χρέωσης)
        if ($price_to_charge > 0) {
            $notes = "Εβδομαδιαίο reconciliation από {$start_date_week} έως {$end_date_week} - {$weekly_bookings} κρατήσεις";

            $insert_payment = $conn->prepare("
                INSERT INTO payments (user_id, amount, payment_date, payment_method, notes)
                VALUES (?, ?, NOW(), 'reconciliation', ?)
            ");

            if (!$insert_payment) {
                throw new Exception("Σφάλμα στο prepare για insert payments: " . $conn->error);
            }

            $insert_payment->bind_param("ids", $user_id, $price_to_charge, $notes);
            $insert_payment->execute();

            if ($insert_payment->affected_rows === -1) {
                throw new Exception("Σφάλμα στο insert payment για user ID {$user_id}: " . $insert_payment->error);
            }

            echo "💾 Καταχωρήθηκε πληρωμή ➜ <strong>€" . number_format($price_to_charge, 2) . "</strong><br><hr>";
        } else {
            echo "ℹ️ Δεν έγινε χρέωση γιατί δεν υπήρχαν κρατήσεις.<hr>";
        }

    }

    $conn->commit();
    echo "<h3>✔️ Το reconciliation ολοκληρώθηκε επιτυχώς!</h3>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<h3>❌ Σφάλμα ➜ " . $e->getMessage() . "</h3>";
}

/**
 * ➤ Υπολογισμός συνολικού κόστους κρατήσεων για εβδομάδα
 * - 1η: 12€
 * - 2η: +6€
 * - 3η: +7€
 * - Από 4η και πάνω: +7.5€/κρατηση
 */
function calculate_total_booking_cost($weekly_bookings) {
    $total_cost = 0;

    if ($weekly_bookings >= 1) {
        $total_cost += 12;
    }

    if ($weekly_bookings >= 2) {
        $total_cost += 6;
    }

    if ($weekly_bookings >= 3) {
        $total_cost += 7;
    }

    if ($weekly_bookings >= 4) {
        $extra_sessions = $weekly_bookings - 3;
        $total_cost += $extra_sessions * 7.5;
    }

    return $total_cost;
}

/**
 * ➤ Υπολογισμός διαθέσιμων κρατήσεων βάσει νέου υπολοίπου
 */
function calculate_max_sessions($balance) {
    $sessions = 0;

    if ($balance < 12) {
        return $sessions;
    }

    $sessions = 1;
    $remaining = $balance - 12;

    if ($remaining >= 6) {
        $sessions++;
        $remaining -= 6;
    }

    if ($remaining >= 7) {
        $sessions++;
        $remaining -= 7;
    }

    while ($remaining >= 7.5) {
        $sessions++;
        $remaining -= 7.5;
    }

    return $sessions;
}
?>
