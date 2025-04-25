<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db_connect.php';
date_default_timezone_set('Europe/Athens');

/**
 * Εμφανίζει σελίδα (Bootstrap Alert) και κάνει exit.
 */
function show_page_and_exit($message, $alert_class) {
    ?>
    <!DOCTYPE html>
    <html lang="el">
    <head>
        <meta charset="UTF-8">
        <title>Ενημέρωση Κράτησης</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Custom CSS για πιο όμορφη εμφάνιση -->
        <style>
            body {
                background-color: #f5f5f5;
                font-family: 'Poppins', sans-serif;
            }
            .custom-alert-container {
                max-width: 1000px;
                margin: 100px auto;
                padding: 0 60px;
            }
            .custom-alert-container .alert {
                font-size: 1.4rem;
                line-height: 1.8;
                padding: 40px 50px;
                text-align: center;
            }
            .custom-alert-container .alert h4 {
                font-size: 2rem;
                margin-bottom: 30px;
            }
            .alert-success {
                background-color: #e2f5e6 !important;
                border: 1px solid #b0e0c0 !important;
                color: #2f6f42 !important;
            }
            .alert-danger {
                background-color: #fce2e2 !important;
                border: 1px solid #f5b3b3 !important;
                color: #a44242 !important;
            }
            .btn-secondary {
                font-size: 1.4rem;
                padding: 14px 30px;
            }
            @media (max-width: 480px) {
                .custom-alert-container {
                    margin: 40px auto;
                    padding: 0 20px;
                    max-width: 100%;
                }
                .custom-alert-container .alert {
                    font-size: 1.2rem;
                    line-height: 1.5;
                    padding: 30px 20px;
                }
                .custom-alert-container .alert h4 {
                    font-size: 1.6rem;
                    margin-bottom: 20px;
                }
                .btn-secondary {
                    font-size: 1.2rem;
                    padding: 10px 25px;
                }
            }
        </style>
    </head>
    <body>
    <div class="container custom-alert-container">
        <div class="alert alert-<?php echo $alert_class; ?> text-center p-4 shadow-sm rounded">
            <h4 class="mb-3">
                <?php echo ($alert_class === "success") ? "Επιτυχία!" : "Προσοχή!"; ?>
            </h4>
            <div><?php echo $message; ?></div>
        </div>
        <div class="text-center mt-4">
            <a href="booking.php" class="btn btn-secondary">Επιστροφή στα Μαθήματα</a>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Επιστρέφει το ΣΥΝΟΛΙΚΟ κόστος για n κρατήσεις (σωρευτικό).
 *  1η => 12€
 *  2η => 18€  (συνολικά)
 *  3η => 25€
 *  4η => 32.5€
 *  5η => 40€
 *  από την 6η και πάνω => +7.5€ ανά κράτηση
 */
function total_cost_for_n_bookings($n) {
    if ($n <= 0) return 0.0;
    switch ($n) {
        case 1:  return 12.0;
        case 2:  return 18.0;
        case 3:  return 25.0;
        case 4:  return 32.5;
        case 5:  return 40.0;
        default:
            return 40.0 + ($n - 5) * 7.5;
    }
}

// --- Έλεγχος αν ο χρήστης είναι συνδεδεμένος ---
if (!isset($_SESSION['user_id'])) {
    show_page_and_exit("❗ Δεν έχετε συνδεθεί. Παρακαλώ συνδεθείτε πρώτα.", "danger");
}

$user_id    = intval($_SESSION['user_id']);
$session_id = intval($_GET['session_id'] ?? 0);
$use_trial  = intval($_GET['trial'] ?? 0);  // trial=1 => χρήση trial

if ($session_id === 0) {
    show_page_and_exit("❗ Μη έγκυρο μάθημα (session_id).", "danger");
}

// --- Φέρνουμε τον χρήστη: balance, trial_approved, trial_used ---
// Δεν χρειαζόμαστε πλέον το week_start_date από τον χρήστη για τον υπολογισμό της τιμολόγησης,
// γιατί θα υπολογίσουμε τα όρια εβδομάδας βάσει της ημερομηνίας του session.
$stmtUser = $conn->prepare("
    SELECT balance, trial_approved, trial_used
    FROM users
    WHERE id = ?
");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$resUser = $stmtUser->get_result();
if ($resUser->num_rows < 1) {
    show_page_and_exit("❗ Ο χρήστης δεν βρέθηκε.", "danger");
}
$user = $resUser->fetch_assoc();
$stmtUser->close();

$balance       = floatval($user['balance']);
$trialApproved = intval($user['trial_approved']);
$trialUsed     = intval($user['trial_used']);

// --- Φέρνουμε στοιχεία του session (date, start_time, available_slots) ---
$stmtSess = $conn->prepare("
    SELECT date, start_time, available_slots
    FROM sessions
    WHERE id = ? AND status = 'active'
");
$stmtSess->bind_param("i", $session_id);
$stmtSess->execute();
$resSess = $stmtSess->get_result();
if ($resSess->num_rows < 1) {
    show_page_and_exit("❗ Δεν βρέθηκε το μάθημα ή είναι ανενεργό.", "danger");
}
$session = $resSess->fetch_assoc();
$stmtSess->close();

$session_date    = $session['date'];      // π.χ. "2025-04-15"
$session_time    = $session['start_time'];  // π.χ. "09:30:00"
$available_slots = intval($session['available_slots']);

if ($available_slots <= 0) {
    show_page_and_exit("❗ Δεν υπάρχουν διαθέσιμες θέσεις για αυτό το μάθημα.", "danger");
}

// --- Υπολογισμός ορίων εβδομάδας βάσει της ημερομηνίας του session ---
// Βρίσκουμε τη Δευτέρα της εβδομάδας που περιέχει την ημερομηνία του session.
$week_start = date('Y-m-d 00:00:00', strtotime('monday this week', strtotime($session_date)));
$week_end   = date('Y-m-d 23:59:59', strtotime($week_start . ' +6 days'));

// --- Πόσες κρατήσεις έχει ήδη ο χρήστης για αυτή την εβδομάδα ---
// Κάνουμε join με το table sessions για να πάρουμε το date του κάθε booked session.
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
$stmtCount->bind_param("iss", $user_id, $week_start, $week_end);
$stmtCount->execute();
$resCount = $stmtCount->get_result();
$rowCount = $resCount->fetch_assoc();
$stmtCount->close();

$bookingsThisWeek = intval($rowCount['cnt']);   // μόνο οι πληρωμένες κρατήσεις για αυτή την εβδομάδα
$newBookingNumber = $bookingsThisWeek + 1;         // η νέα κράτηση είναι η (μέτρηση + 1)

// --- Υπολογισμός κόστους κράτησης ---
$new_total = total_cost_for_n_bookings($newBookingNumber);
$old_total = total_cost_for_n_bookings($bookingsThisWeek);
$booking_cost = $new_total - $old_total;

if ($use_trial === 1) {
    if (!($trialApproved === 1 && $trialUsed === 0)) {
        show_page_and_exit("❗ Δεν έχετε διαθέσιμο trial!", "danger");
    }
    $booking_cost = 0.0;
}

if ($use_trial === 0 && $balance < $booking_cost) {
    show_page_and_exit("❗ Δεν έχεις αρκετό υπόλοιπο για αυτή την κράτηση! Χρειάζεσαι {$booking_cost}€.", "danger");
}

// --- Έλεγχος διπλής κράτησης (αν έχει ήδη κάνει κράτηση για αυτό το μάθημα) ---
$stmtDup = $conn->prepare("
    SELECT COUNT(*) as cnt
    FROM bookings
    WHERE user_id = ?
      AND session_id = ?
      AND status = 'active'
");
$stmtDup->bind_param("ii", $user_id, $session_id);
$stmtDup->execute();
$resDup = $stmtDup->get_result();
$rowDup = $resDup->fetch_assoc();
$stmtDup->close();

if ($rowDup['cnt'] > 0) {
    show_page_and_exit("❗ Έχεις ήδη κάνει κράτηση για αυτό το μάθημα!", "danger");
}

// --- Ξεκινάμε συναλλαγή ---
$conn->begin_transaction();

try {
    // (1) Καταχώρηση νέας κράτησης
    $stmtInsert = $conn->prepare("
        INSERT INTO bookings (user_id, session_id, status, created_at, booking_cost)
        VALUES (?, ?, 'active', NOW(), ?)
    ");
    $stmtInsert->bind_param("iid", $user_id, $session_id, $booking_cost);
    $stmtInsert->execute();
    $stmtInsert->close();

    // (2) Μείωση διαθέσιμων θέσεων στο session
    $stmtUpdSess = $conn->prepare("
        UPDATE sessions
        SET available_slots = available_slots - 1
        WHERE id = ?
    ");
    $stmtUpdSess->bind_param("i", $session_id);
    $stmtUpdSess->execute();
    $stmtUpdSess->close();

    // (3) Ενημέρωση χρήστη (balance, trial_used)
    if ($use_trial === 1) {
        $new_balance   = $balance;
        $new_trialUsed = 1;
    } else {
        $new_balance   = $balance - $booking_cost;
        $new_trialUsed = $trialUsed;
    }
    $stmtUpdUser = $conn->prepare("
        UPDATE users
        SET balance = ?, trial_used = ?
        WHERE id = ?
    ");
    $stmtUpdUser->bind_param("dii", $new_balance, $new_trialUsed, $user_id);
    $stmtUpdUser->execute();
    $stmtUpdUser->close();

    // (4) Commit της συναλλαγής
    $conn->commit();
    
    // Μετατροπή ημερομηνίας/ώρας για εμφάνιση
    $sessionDateTime = new DateTimeImmutable($session_date . ' ' . $session_time);
    $formattedDateTime = $sessionDateTime->format('d/m/Y H:i');

    // (5) Μήνυμα επιτυχίας
    $msg  = "✅ Η κράτηση ολοκληρώθηκε για τις <strong>{$formattedDateTime}</strong>!";
    $msg .= "<br>Είναι η <strong>{$newBookingNumber}η</strong> κράτησή σου για αυτή την εβδομάδα.";
    if ($use_trial === 0) {
        $totalSoFar = total_cost_for_n_bookings($newBookingNumber);
        $msg .= "<br>Σύνολο έως τώρα: <strong>{$totalSoFar}€</strong>";
    } else {
        $msg .= "<br>🎁 <strong>Χρήση Trial (δωρεάν κράτηση)!</strong>";
    }

    show_page_and_exit($msg, "success");

} catch (Exception $e) {
    $conn->rollback();
    show_page_and_exit("❗ Σφάλμα κατά την ολοκλήρωση της κράτησης: " . $e->getMessage(), "danger");
}
?>
