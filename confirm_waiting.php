<?php
session_start();
require_once 'includes/db_connect.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ζώνη ώρας Ελλάδας
date_default_timezone_set('Europe/Athens');

/**
 * Εμφανίζει σελίδα ενημέρωσης/σφάλματος και σταματάει την εκτέλεση
 */
function show_page_and_exit($message, $alert_class) {
    ?>
    <!DOCTYPE html>
    <html lang="el">
    <head>
        <meta charset="UTF-8">
        <title>Ενημέρωση Κράτησης</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container text-center mt-5">
            <div class="alert alert-<?php echo $alert_class; ?>">
                <?php echo $message; ?>
            </div>
            <a href="booking.php" class="btn btn-secondary">Επιστροφή στα Μαθήματα</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Υπολογίζει το συνολικό κόστος κρατήσεων για N κρατήσεις
 */
function total_cost_for_n_bookings($n) {
    if ($n <= 0) return 0.0;
    switch ($n) {
        case 1:  return 12.0;
        case 2:  return 18.0;
        case 3:  return 25.0;
        case 4:  return 32.5;
        case 5:  return 40.0;
        default: return 40.0 + ($n - 5) * 7.5;
    }
}

// 1) Έλεγχος token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    show_page_and_exit("❗ Μη έγκυρο token.", "danger");
}
$token = $_GET['token'];

// Παίρνουμε και πιθανό ορισμό trial από το query
$use_trial = intval($_GET['trial'] ?? 0);

// 2) Φόρτωση από waiting_list
$stmt = $conn->prepare("
    SELECT id, user_id, session_id, expires_at, status
    FROM waiting_list
    WHERE token = ?
");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows < 1) {
    show_page_and_exit("❗ Μη έγκυρο ή ληγμένο token.", "danger");
}
$waiting = $res->fetch_assoc();
$stmt->close();

// 3) Έλεγχος λήξης token
try {
    $expires = new DateTime($waiting['expires_at'], new DateTimeZone('Europe/Athens'));
    $now     = new DateTime('now', new DateTimeZone('Europe/Athens'));
} catch (Exception $e) {
    show_page_and_exit("❗ Σφάλμα χρόνου: " . $e->getMessage(), "danger");
}

// Αν έχει λήξει
if ($expires < $now) {
    // Κάνουμε status='expired'
    $stmtExpire = $conn->prepare("
        UPDATE waiting_list
           SET status = 'expired'
         WHERE id = ?
    ");
    $stmtExpire->bind_param("i", $waiting['id']);
    $stmtExpire->execute();
    $stmtExpire->close();

    show_page_and_exit("❗ Το token έχει λήξει. Παρακαλώ εγγραφείτε ξανά στη λίστα αναμονής.", "danger");
}

// Πρέπει να είναι σε notified
if ($waiting['status'] !== 'notified') {
    show_page_and_exit("❗ Η κράτησή σας δεν μπορεί να επιβεβαιωθεί. Επικοινωνήστε με τη διαχείριση.", "danger");
}

// 4) Ξεκινάμε συναλλαγή για να ολοκληρώσουμε την κράτηση
$conn->begin_transaction();
try {
    $waiting_user_id = intval($waiting['user_id']);
    $session_id      = intval($waiting['session_id']);

    // Φόρτωση χρήστη
    $stmtUser = $conn->prepare("
        SELECT balance, trial_approved, trial_used
        FROM users
        WHERE id = ?
    ");
    $stmtUser->bind_param("i", $waiting_user_id);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    if ($resUser->num_rows < 1) {
        throw new Exception("❗ Ο χρήστης δεν βρέθηκε.");
    }
    $user = $resUser->fetch_assoc();
    $stmtUser->close();

    $balance        = floatval($user['balance']);
    $trialApproved  = intval($user['trial_approved']);
    $trialUsed      = intval($user['trial_used']);

    // Φόρτωση session
    $stmtSess = $conn->prepare("
        SELECT date, start_time, available_slots
        FROM sessions
        WHERE id = ?
          AND status = 'active'
    ");
    $stmtSess->bind_param("i", $session_id);
    $stmtSess->execute();
    $resSess = $stmtSess->get_result();
    if ($resSess->num_rows < 1) {
        throw new Exception("❗ Δεν βρέθηκε το μάθημα ή είναι ανενεργό.");
    }
    $session = $resSess->fetch_assoc();
    $stmtSess->close();

    $session_date    = $session['date'];
    $session_time    = $session['start_time'];
    $available_slots = intval($session['available_slots']);

    // Εάν τα slots είναι 0, βλέπουμε αν είναι "notified". Αν είναι notified, του επιτρέπουμε να συνεχίσει.
    if ($available_slots <= 0) {
        // Αν *δεν* είναι notified (επιπλέον check, αν θέλετε) -> throw exception.
        // Όμως εδώ έχουμε φτάσει ήδη, ξέρουμε status='notified'.
        // Άρα τον αφήνουμε να συνεχίσει.
        // => Δεν κάνουμε throw. Προχωράμε.
    }

    // Υπολογισμός πόσα πληρωμένα bookings έχει ήδη για αυτή την εβδομάδα
    $week_start = date('Y-m-d 00:00:00', strtotime('monday this week', strtotime($session_date)));
    $week_end   = date('Y-m-d 23:59:59', strtotime($week_start . ' +6 days'));

    $stmtCount = $conn->prepare("
        SELECT COUNT(*) as cnt
        FROM bookings b
        JOIN sessions s ON b.session_id = s.id
        WHERE b.user_id = ?
          AND b.status = 'active'
          AND s.date >= ?
          AND s.date <= ?
          AND b.booking_cost > 0
    ");
    $stmtCount->bind_param("iss", $waiting_user_id, $week_start, $week_end);
    $stmtCount->execute();
    $resCount   = $stmtCount->get_result();
    $rowCount   = $resCount->fetch_assoc();
    $stmtCount->close();

    $bookingsThisWeek = intval($rowCount['cnt']);
    $newBookingNumber = $bookingsThisWeek + 1;
    $new_total        = total_cost_for_n_bookings($newBookingNumber);
    $old_total        = total_cost_for_n_bookings($bookingsThisWeek);
    $booking_cost     = $new_total - $old_total;

    // Αν χρησιμοποιεί trial
    if ($use_trial === 1) {
        if (!($trialApproved === 1 && $trialUsed === 0)) {
            throw new Exception("❗ Δεν έχετε διαθέσιμο trial!");
        }
        $booking_cost = 0.0;
    }

    // Αν *δεν* είναι trial, χρεώνεται
    if ($use_trial === 0 && $balance < $booking_cost) {
        throw new Exception("❗ Δεν έχετε αρκετό υπόλοιπο για αυτή την κράτηση! Χρειάζεστε {$booking_cost}€.");
    }

    // 5) Εισάγουμε την κράτηση
    // Εδώ θα βάλουμε created_at = NOW() ή δική μας ώρα
    $createdAt = date('Y-m-d H:i:s');
    $stmtInsert = $conn->prepare("
        INSERT INTO bookings (user_id, session_id, status, created_at, booking_cost)
        VALUES (?, ?, 'active', ?, ?)
    ");
    $stmtInsert->bind_param("issd", $waiting_user_id, $session_id, $createdAt, $booking_cost);
    $stmtInsert->execute();
    $stmtInsert->close();

    // 6) Μειώνουμε τα slots *μόνο* αν είχε slots>0
    //    ή αν θέλουμε να μένουν μηδέν; 
    //    Επειδή το slot έχει ήδη «δεσμευτεί» γι' αυτόν, μπορείτε να μην το αλλάξετε
    //    *ή* αν το 'available_slots' > 0, τότε -> available_slots--
    if ($available_slots > 0) {
        $stmtUpdSess = $conn->prepare("
            UPDATE sessions
               SET available_slots = available_slots - 1
             WHERE id = ?
        ");
        $stmtUpdSess->bind_param("i", $session_id);
        $stmtUpdSess->execute();
        $stmtUpdSess->close();
    }

    // 7) Ενημέρωση υπολοίπου (αν πληρώθηκε)
    if ($use_trial === 1) {
        // trial_used=1, balance μένει ίδιο
        $new_balance   = $balance;
        $new_trialUsed = 1;
    } else {
        $new_balance   = $balance - $booking_cost;
        $new_trialUsed = $trialUsed;
    }
    $stmtUpdUser = $conn->prepare("
        UPDATE users
           SET balance = ?,
               trial_used = ?
         WHERE id = ?
    ");
    $stmtUpdUser->bind_param("dii", $new_balance, $new_trialUsed, $waiting_user_id);
    $stmtUpdUser->execute();
    $stmtUpdUser->close();

    // 8) αλλάζουμε waiting_list.status = 'confirmed'
    $stmtUpdateWait = $conn->prepare("
        UPDATE waiting_list
           SET status = 'confirmed'
         WHERE id = ?
    ");
    $stmtUpdateWait->bind_param("i", $waiting['id']);
    $stmtUpdateWait->execute();
    $stmtUpdateWait->close();

    // ΟΚ, commit
    $conn->commit();

    // Διαμόρφωση μηνύματος επιτυχίας
    $sessionDateTime   = new DateTimeImmutable($session_date . ' ' . $session_time, new DateTimeZone('Europe/Athens'));
    $formattedDateTime = $sessionDateTime->format('d/m/Y H:i');

    $msg  = "✅ Η κράτησή σας ολοκληρώθηκε για τις <strong>{$formattedDateTime}</strong>!<br>";
    $msg .= "Είναι η <strong>{$newBookingNumber}η</strong> κράτησή σας για αυτήν την εβδομάδα.";
    if ($use_trial === 0) {
        $msg .= "<br>Σύνολο έως τώρα: <strong>" . total_cost_for_n_bookings($newBookingNumber) . "€</strong>";
    } else {
        $msg .= "<br>🎁 <strong>Χρήση Trial (δωρεάν κράτηση)!</strong>";
    }

    show_page_and_exit($msg, "success");

} catch (Exception $e) {
    // Rollback σε περίπτωση σφάλματος
    $conn->rollback();
    show_page_and_exit("❗ Σφάλμα κατά την επιβεβαίωση της κράτησης: " . $e->getMessage(), "danger");
}
?>
