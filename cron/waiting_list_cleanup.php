<?php
require_once __DIR__ . '/../includes/db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ορίζουμε τη ζώνη ώρας στην PHP σε Europe/Athens
date_default_timezone_set('Europe/Athens');
echo "🕓 Server Time (PHP - Greece): " . date('Y-m-d H:i:s') . "\n";

// Ρυθμίζουμε τη ζώνη ώρας για τη σύνδεση MySQL ώστε το NOW() να λειτουργεί σε τοπική ώρα
$conn->query("SET time_zone = 'Europe/Athens'");

// Επιλέγουμε όλες τις εγγραφές στη waiting_list με status = 'notified'
$stmt = $conn->prepare("
    SELECT id, session_id, expires_at
    FROM waiting_list
    WHERE status = 'notified'
");
if (!$stmt) {
    die("Σφάλμα προετοιμασίας ερωτήματος: " . $conn->error);
}
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "⚠️ Δεν βρέθηκαν εγγραφές με status = 'notified'\n";
} else {
    echo "✅ Βρέθηκαν {$res->num_rows} εγγραφές με status = 'notified'\n";
}

$current = new DateTime('now', new DateTimeZone('Europe/Athens'));
echo "Current Time: " . $current->format('Y-m-d H:i:s') . "\n";

while ($row = $res->fetch_assoc()) {
    $waiting_id = intval($row['id']);
    $session_id = intval($row['session_id']);
    
    try {
        $expires_at = new DateTime($row['expires_at'], new DateTimeZone('Europe/Athens'));
    } catch (Exception $e) {
        echo "Record $waiting_id: Σφάλμα στη δημιουργία του DateTime για expires_at: " . $e->getMessage() . "\n";
        continue;
    }
    
    echo "Record $waiting_id: expires_at = " . $expires_at->format('Y-m-d H:i:s') . "\n";
    
    // Αν η τρέχουσα ώρα έχει περάσει το expires_at, τότε το token έχει λήξει
    if ($current > $expires_at) {
        echo "Record $waiting_id: Το token έχει λήξει. (current > expires_at)\n";
        
        // (1) Ενημέρωση του waiting_list record σε 'expired'
        $stmtUpdate = $conn->prepare("UPDATE waiting_list SET status = 'expired' WHERE id = ?");
        if ($stmtUpdate) {
            $stmtUpdate->bind_param("i", $waiting_id);
            if ($stmtUpdate->execute()) {
                echo "Record $waiting_id: Status updated to 'expired'.\n";
            } else {
                echo "Record $waiting_id: Error executing UPDATE waiting_list: " . $stmtUpdate->error . "\n";
            }
            $stmtUpdate->close();
        } else {
            error_log("Σφάλμα προετοιμασίας UPDATE waiting_list: " . $conn->error);
        }
        
        // (2) Αύξηση των available_slots για το αντίστοιχο session
        $stmtSlots = $conn->prepare("UPDATE sessions SET available_slots = available_slots + 1 WHERE id = ?");
        if ($stmtSlots) {
            $stmtSlots->bind_param("i", $session_id);
            if ($stmtSlots->execute()) {
                echo "Record $waiting_id: available_slots increased for session $session_id.\n";
            } else {
                echo "Record $waiting_id: Error executing UPDATE sessions: " . $stmtSlots->error . "\n";
            }
            $stmtSlots->close();
        } else {
            error_log("Σφάλμα προετοιμασίας UPDATE sessions: " . $conn->error);
        }
    } else {
        echo "Record $waiting_id: Το token δεν έχει λήξει ακόμα. (current <= expires_at)\n";
    }
}

$stmt->close();
$conn->close();

echo "✅ Τέλος καθαρισμού waiting_list\n";
?>
