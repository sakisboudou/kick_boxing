<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// -------------------------
// Συνάρτηση αποστολής email ειδοποίησης για waiting list
// -------------------------
function sendWaitingNotification($email, $session_id, $token, $session_date, $start_time) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.gosstudio.gr';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@gosstudio.gr';
        $mail->Password   = 'Arf199vbn@arf';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';

        $mail->setFrom('info@gosstudio.gr', "Go's Studio Pilates");
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Ειδοποίηση Διαθέσιμης Θέσης';

        $confirmationLink = "https://gosstudio.gr/confirm_waiting.php?token=" . urlencode($token);

        // Μορφοποίηση ημερομηνίας/ώρας
        $dt = new DateTimeImmutable("$session_date $start_time", new DateTimeZone('Europe/Athens'));
        $weekdayMap = [
            'Monday'    => 'Δευτέρα',
            'Tuesday'   => 'Τρίτη',
            'Wednesday' => 'Τετάρτη',
            'Thursday'  => 'Πέμπτη',
            'Friday'    => 'Παρασκευή',
            'Saturday'  => 'Σάββατο',
            'Sunday'    => 'Κυριακή'
        ];
        $weekday = $weekdayMap[$dt->format('l')] ?? $dt->format('l');
        $formattedDate = $weekday . ', ' . $dt->format('d/m/Y');
        $formattedTime = $dt->format('H:i');

        // HTML email body
        $mail->Body = '
        <html>
        <body style="font-family: Helvetica, Arial, sans-serif; background-color: #f5f5f5; padding: 30px 0;">
            <div style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="https://gosstudio.gr/assets/images/logo_128x128.png" alt="Go\'s Studio" style="height: 60px;">
                </div>
                <h2 style="color: #a47c48; text-align: center; margin-bottom: 20px;">Έχετε μια νέα ευκαιρία ✨</h2>
                <p style="font-size: 16px; color: #333333;">Γεια σας,</p>
                <p style="font-size: 16px; color: #333333;">
                    Μόλις άδειασε θέση για το μάθημα της <strong>' . $formattedDate . '</strong> στις <strong>' . $formattedTime . '</strong>.
                </p>
                <p style="font-size: 16px; color: #333333;">
                    Πατήστε στο κουμπί παρακάτω για να επιβεβαιώσετε την κράτησή σας εντός <strong>30 λεπτών</strong>:
                </p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $confirmationLink . '" 
                        style="display: inline-block; background-color: #a47c48; color: white; padding: 14px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; transition: background-color 0.3s;">
                        Επιβεβαίωση Θέσης
                    </a>
                </div>
                <p style="font-size: 14px; color: #666666;">Αν δεν επιβεβαιώσετε εγκαίρως, η θέση θα προσφερθεί σε άλλο χρήστη.</p>
                <hr style="margin: 40px 0; border: none; border-top: 1px solid #eee;">
                <p style="text-align: center; font-size: 12px; color: #999999;">
                    Go\'s Studio Pilates • <a href="https://gosstudio.gr" style="color: #a47c48; text-decoration: none;">gosstudio.gr</a>
                </p>
            </div>
        </body>
        </html>';

        // Plain-text alternative
        $mail->AltBody = "Γεια σας,\n\nΜόλις άδειασε θέση για το μάθημα την $formattedDate, ώρα $formattedTime.\n\nΕπιβεβαιώστε εντός 30 λεπτών:\n$confirmationLink\n\nGo's Studio Pilates";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// -------------------------
// Συνάρτηση ελέγχου αν επιτρέπεται η ακύρωση της συνεδρίας
// -------------------------
function canCancelSession($session_date, $start_time) {
    $sessionDateTime = new DateTimeImmutable("$session_date $start_time", new DateTimeZone('Europe/Athens'));
    $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Athens'));

    if ($start_time < "12:00:00") {
        $deadlineDateTime = $sessionDateTime->modify('-1 day')->setTime(18, 0, 0);
    } else {
        $deadlineDateTime = $sessionDateTime->setTime(12, 0, 0);
    }
    return ($now <= $deadlineDateTime);
}

// -------------------------
// Κύρια λογική ακύρωσης κράτησης
// -------------------------

// 1. Έλεγχος σύνδεσης χρήστη
if (!isset($_SESSION['user_id'])) {
    die("⛔ Δεν είστε συνδεδεμένοι.");
}
$user_id = intval($_SESSION['user_id']);

// 2. Λήψη και έλεγχος του booking_id από τη φόρμα
$booking_id = intval($_POST['booking_id'] ?? 0);
if ($booking_id <= 0) {
    die("❗ Μη έγκυρο booking_id.");
}

$conn->begin_transaction();

try {
    // 3. Φόρτωση στοιχείων της κράτησης μαζί με πληροφορίες της συνεδρίας
    $stmt = $conn->prepare("
        SELECT 
            b.user_id, 
            b.session_id, 
            b.booking_cost, 
            b.status,
            s.date AS session_date, 
            s.start_time
        FROM bookings b
        JOIN sessions s ON b.session_id = s.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows < 1) {
        throw new Exception("❗ Η κράτηση δεν βρέθηκε.");
    }
    $booking = $res->fetch_assoc();
    $stmt->close();

    // 4. Επιβεβαίωση ότι ο τρέχων χρήστης είναι ο δημιουργός της κράτησης
    if (intval($booking['user_id']) !== $user_id) {
        throw new Exception("❗ Δεν μπορείτε να ακυρώσετε κράτηση άλλου χρήστη.");
    }

    // 5. Έλεγχος ότι η κράτηση είναι ενεργή
    if ($booking['status'] !== 'active') {
        throw new Exception("❗ Η κράτηση δεν είναι ενεργή (ή έχει ήδη ακυρωθεί).");
    }

    // 6. Έλεγχος αν επιτρέπεται η ακύρωση βάσει χρονικού ορίου
    $session_date = $booking['session_date'];
    $start_time   = $booking['start_time'];
    if (!canCancelSession($session_date, $start_time)) {
        throw new Exception("❗ Δεν μπορείτε να ακυρώσετε πέραν του χρονικού ορίου.");
    }

    // 7. Ακύρωση της κράτησης (ορισμός status ως 'cancelled')
    $stmtCancel = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $stmtCancel->bind_param("i", $booking_id);
    $stmtCancel->execute();
    $stmtCancel->close();

    // 8. Αν πρόκειται για κράτηση με trial (booking_cost == 0) επαναφέρουμε το trial του χρήστη
    $booking_cost = floatval($booking['booking_cost']);
    if ($booking_cost == 0) {
        $stmtTrialReset = $conn->prepare("UPDATE users SET trial_used = 0 WHERE id = ?");
        $stmtTrialReset->bind_param("i", $user_id);
        $stmtTrialReset->execute();
        $stmtTrialReset->close();
    }

    // 9. Επιστροφή χρημάτων στον χρήστη, αν είχε πληρώσει (booking_cost > 0)
    if ($booking_cost > 0) {
        // Ενημέρωση του balance του χρήστη
        $stmtRefund = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmtRefund->bind_param("di", $booking_cost, $user_id);
        $stmtRefund->execute();
        $stmtRefund->close();

        // Καταχώρηση στη λογιστική (transactions)
        $stmtTrans = $conn->prepare("
            INSERT INTO transactions (user_id, amount, type, created_at, description)
            VALUES (?, ?, 'refund', NOW(), ?)
        ");
        $desc = "User cancellation refund (booking_id={$booking_id})";
        $stmtTrans->bind_param("ids", $user_id, $booking_cost, $desc);
        $stmtTrans->execute();
        $stmtTrans->close();
    }

    // 10. Έλεγχος αν υπάρχει κάποιος στη waiting list για τη συγκεκριμένη συνεδρία
    $session_id = intval($booking['session_id']);
    $stmtWait = $conn->prepare("
        SELECT id, user_id, email, token, created_at
        FROM waiting_list
        WHERE session_id = ?
          AND status = 'waiting'
        ORDER BY created_at ASC
        LIMIT 1
    ");
    $stmtWait->bind_param("i", $session_id);
    $stmtWait->execute();
    $resWait = $stmtWait->get_result();

    if ($resWait->num_rows > 0) {
        // 10A. Βρέθηκε χρήστης στη waiting list → ενημέρωση και αποστολή ειδοποίησης
        $waiting = $resWait->fetch_assoc();
        $waiting_id    = intval($waiting['id']);
        $waiting_email = $waiting['email'];
        $waiting_token = $waiting['token'];

        // Ορισμός λήξης ειδοποίησης 30 λεπτών από τώρα
        $dt = new DateTime('now', new DateTimeZone('Europe/Athens'));
        $dt->modify('+30 minutes');
        $expiresAt = $dt->format('Y-m-d H:i:s');

        $stmtUpdateWait = $conn->prepare("
            UPDATE waiting_list
            SET status = 'notified', expires_at = ?
            WHERE id = ?
        ");
        $stmtUpdateWait->bind_param("si", $expiresAt, $waiting_id);
        $stmtUpdateWait->execute();
        $stmtUpdateWait->close();

        // Αποστολή email ειδοποίησης στον notified χρήστη
        sendWaitingNotification(
            $waiting_email,
            $session_id,
            $waiting_token,
            $session_date,
            $start_time
        );
        // Σημείωση: Δεν αυξάνουμε τα available_slots καθώς η θέση κατανέμεται στον ειδοποιημένο χρήστη.
    } else {
        // 10B. Αν δεν υπάρχει κάποιος στη waiting list, αυξάνουμε τα available_slots κατά 1
        $stmtSlots = $conn->prepare("
            UPDATE sessions
            SET available_slots = available_slots + 1
            WHERE id = ?
        ");
        $stmtSlots->bind_param("i", $session_id);
        $stmtSlots->execute();
        $stmtSlots->close();
    }
    $stmtWait->close();

    // 11. Commit όλων των αλλαγών
    $conn->commit();

    // Ανακατεύθυνση πίσω στη σελίδα κράτησης με μήνυμα επιτυχίας
    header("Location: booking.php?cancel_msg=success");
    exit;

} catch (Exception $e) {
    // Σε περίπτωση σφάλματος, rollback όλων των αλλαγών
    $conn->rollback();
    die("❗ Σφάλμα ακύρωσης: " . $e->getMessage());
}

$conn->close();
?>
