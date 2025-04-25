<?php
session_start();

// ✅ Admin Authentication
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("Μόνο οι διαχειριστές έχουν πρόσβαση εδώ.");
}

require_once 'includes/db_connect.php';

$user_id = intval($_GET['user_id'] ?? 0);
$old_session_id = intval($_GET['session_id'] ?? 0);
$new_session_id = intval($_POST['new_session_id'] ?? 0);

// ✅ Έλεγχος εγκυρότητας εισόδων
if ($user_id <= 0 || $old_session_id <= 0 || $new_session_id <= 0) {
    die("❗ Μη έγκυρα δεδομένα.");
}

$conn->begin_transaction();

try {
    // ✅ Έλεγχος αν η νέα συνεδρία έχει διαθεσιμότητα
    $stmt_check = $conn->prepare("SELECT available_slots FROM sessions WHERE id = ? AND available_slots > 0 FOR UPDATE");
    $stmt_check->bind_param("i", $new_session_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("❗ Η νέα συνεδρία δεν έχει διαθέσιμες θέσεις.");
    }

    // ✅ Έλεγχος αν υπάρχει ενεργή κράτηση στο παλιό session
    $stmt_old_booking = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? AND session_id = ? AND status = 'active' FOR UPDATE");
    $stmt_old_booking->bind_param("ii", $user_id, $old_session_id);
    $stmt_old_booking->execute();
    $result_old_booking = $stmt_old_booking->get_result();

    if ($result_old_booking->num_rows === 0) {
        throw new Exception("❗ Δεν υπάρχει ενεργή κράτηση σε αυτή τη συνεδρία.");
    }

    // ✅ Ακύρωση της παλιάς κράτησης
    $stmt_cancel = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE user_id = ? AND session_id = ?");
    $stmt_cancel->bind_param("ii", $user_id, $old_session_id);
    $stmt_cancel->execute();

    // ✅ Εισαγωγή νέας κράτησης
    $stmt_new_booking = $conn->prepare("INSERT INTO bookings (user_id, session_id, status) VALUES (?, ?, 'active')");
    $stmt_new_booking->bind_param("ii", $user_id, $new_session_id);
    $stmt_new_booking->execute();

    // ✅ Ενημέρωση διαθέσιμων slots
    $stmt_update_slots_new = $conn->prepare("UPDATE sessions SET available_slots = available_slots - 1 WHERE id = ?");
    $stmt_update_slots_new->bind_param("i", $new_session_id);
    $stmt_update_slots_new->execute();

    $stmt_update_slots_old = $conn->prepare("UPDATE sessions SET available_slots = available_slots + 1 WHERE id = ?");
    $stmt_update_slots_old->bind_param("i", $old_session_id);
    $stmt_update_slots_old->execute();

    $conn->commit();

    header("Location: admin_panel.php?message=move_success");
    exit;

} catch (Exception $e) {
    $conn->rollback();

    echo "<div style='text-align:center; margin-top:50px;'>
            <h3>{$e->getMessage()}</h3>
            <a href='admin_panel.php' style='padding: 10px 20px; background-color: #007bff; color: white; border-radius: 5px; text-decoration: none;'>Επιστροφή στο Admin Panel</a>
          </div>";
    exit;
}
?>

