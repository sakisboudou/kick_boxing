<?php
session_start();
require_once 'includes/db_connect.php';

// ✅ Ενεργοποίηση σφαλμάτων για debugging (προαιρετικά σε dev περιβάλλον)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Έλεγχος αν είναι admin
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php?error=unauthorized');
    exit;
}

$session_id = intval($_GET['session_id'] ?? 0);

if ($session_id <= 0) {
    die("❗ Μη έγκυρο session ID.");
}

$conn->begin_transaction();

try {
    // ✅ Ακυρώνουμε το session
    $stmt_session_cancel = $conn->prepare("UPDATE sessions SET status = 'cancelled' WHERE id = ?");
    $stmt_session_cancel->bind_param("i", $session_id);
    $stmt_session_cancel->execute();

    if ($stmt_session_cancel->affected_rows === 0) {
        throw new Exception("❗ Δεν βρέθηκε η συνεδρία για ακύρωση.");
    }

    // ✅ Παίρνουμε όλους τους χρήστες που είχαν κρατήσει θέση
    $stmt_bookings = $conn->prepare("
        SELECT user_id, is_trial 
        FROM bookings 
        WHERE session_id = ? AND status = 'active'
    ");
    $stmt_bookings->bind_param("i", $session_id);
    $stmt_bookings->execute();
    $result = $stmt_bookings->get_result();

    // ✅ Ακυρώνουμε τις κρατήσεις και αν ήταν trial, ξεκλειδώνουμε ξανά το trial
    $stmt_cancel_bookings = $conn->prepare("
        UPDATE bookings 
        SET status = 'cancelled' 
        WHERE session_id = ? AND user_id = ?
    ");

    $stmt_unlock_trial = $conn->prepare("
        UPDATE users 
        SET trial_unlocked = 1 
        WHERE id = ?
    ");

    while ($booking = $result->fetch_assoc()) {
        $user_id = $booking['user_id'];
        $is_trial = $booking['is_trial'];

        // ✅ Ακυρώνουμε την κράτηση
        $stmt_cancel_bookings->bind_param("ii", $session_id, $user_id);
        $stmt_cancel_bookings->execute();

        // ✅ Αν ήταν trial ➜ ξαναδίνουμε το trial unlocked στον χρήστη
        if ($is_trial) {
            $stmt_unlock_trial->bind_param("i", $user_id);
            $stmt_unlock_trial->execute();
        }
    }

    $conn->commit();

    echo "✅ Το session ακυρώθηκε και οι κρατήσεις ακυρώθηκαν.<br>";
    echo "<a href='admin_panel.php'>Επιστροφή στο Admin Panel</a>";

} catch (Exception $e) {
    $conn->rollback();

    echo "<div style='text-align:center; margin-top:50px;'>
            <h3>❗ Σφάλμα: {$e->getMessage()}</h3>
            <a href='admin_panel.php' style='padding: 10px 20px; background-color: #007bff; color: white; border-radius: 5px; text-decoration: none;'>Επιστροφή στο Admin Panel</a>
          </div>";
    exit;
}
?>

