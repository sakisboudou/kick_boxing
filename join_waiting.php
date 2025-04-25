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
 * Υπολογίζει το συνολικό κόστος κρατήσεων για N κρατήσεις
 */
function total_cost_for_n_bookings($n) {
    if ($n <= 0) return 0.0;
    switch ($n) {
        case 1: return 12.0;
        case 2: return 18.0;
        case 3: return 25.0;
        case 4: return 32.5;
        case 5: return 40.0;
        default: return 40.0 + ($n - 5) * 7.5;
    }
}

// Έλεγχος εάν ο χρήστης έχει συνδεθεί
if (!isset($_SESSION['user_id'])) {
    show_page_and_exit("❗ Δεν έχετε συνδεθεί. Παρακαλώ συνδεθείτε πρώτα.", "danger");
}

$user_id = intval($_SESSION['user_id']);

// Έλεγχος εάν δόθηκε το session_id
if (!isset($_GET['session_id']) || empty($_GET['session_id'])) {
    show_page_and_exit("❗ Δεν παρέχεται session_id.", "danger");
}
$session_id = intval($_GET['session_id']);

// Φόρτωση στοιχείων χρήστη
$stmt = $conn->prepare("SELECT email, full_name, balance, trial_approved, trial_used FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    show_page_and_exit("❗ Δεν βρέθηκε ο χρήστης.", "danger");
}

$email          = $user['email'];
$full_name      = $user['full_name'];
$balance        = floatval($user['balance']);
$trialApproved  = intval($user['trial_approved']);
$trialUsed      = intval($user['trial_used']);
$trial_available = ($trialApproved === 1 && $trialUsed === 0);

// Φόρτωση στοιχείων του μαθήματος (session)
$stmtSess = $conn->prepare("SELECT date, start_time FROM sessions WHERE id = ? AND status = 'active'");
$stmtSess->bind_param("i", $session_id);
$stmtSess->execute();
$resSess = $stmtSess->get_result();
if ($resSess->num_rows < 1) {
    show_page_and_exit("❗ Δεν βρέθηκε το μάθημα ή είναι ανενεργό.", "danger");
}
$session = $resSess->fetch_assoc();
$stmtSess->close();

$session_date = $session['date'];
$session_time = $session['start_time'];

// Υπολογισμός πόσες κρατήσεις (πληρωμένες) έχει κάνει αυτή την εβδομάδα ο χρήστης
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
$stmtCount->bind_param("iss", $user_id, $week_start, $week_end);
$stmtCount->execute();
$resCount   = $stmtCount->get_result();
$rowCount   = $resCount->fetch_assoc();
$stmtCount->close();

$bookingsThisWeek = intval($rowCount['cnt']);
$newBookingNumber = $bookingsThisWeek + 1;
$new_total        = total_cost_for_n_bookings($newBookingNumber);
$old_total        = total_cost_for_n_bookings($bookingsThisWeek);
$booking_cost     = $new_total - $old_total;

// Έλεγχος για χρήση trial
$use_trial = intval($_GET['trial'] ?? 0);
if ($use_trial === 1) {
    // Trial
    if (!$trial_available) {
        show_page_and_exit("❗ Δεν έχετε διαθέσιμο trial!", "danger");
    }
    // Στο waiting list, ίσως δεν χρεώνουμε τίποτα ακόμα, αλλά αν θέλετε να επιτρέπετε waiting μόνο αν έχει trial, μένει ως έχει
    $booking_cost = 0.0;
} else {
    // Χωρίς trial, έλεγχος υπολοίπου
    // (αν θέλετε να χρεώνετε μόνο στη φάση της επιβεβαίωσης, μπορείτε να αφαιρέσετε αυτόν τον έλεγχο. 
    //  Διαφορετικά, το αφήνετε αν θέλετε ο χρήστης να έχει τουλάχιστον αυτό το υπόλοιπο πριν μπει waiting.)
    if ($balance < $booking_cost) {
        show_page_and_exit("❗ Δεν έχετε αρκετό υπόλοιπο για να κλείσετε θέση! Χρειάζεστε {$booking_cost}€.", "danger");
    }
}

// Δημιουργία token (για μεταγενέστερη επιβεβαίωση, αν το χρειάζεστε)
try {
    $token = bin2hex(random_bytes(16));
} catch (Exception $e) {
    show_page_and_exit("❗ Σφάλμα κατά τη δημιουργία token.", "danger");
}

// ------------------------------
// Αποθηκεύουμε μόνο created_at και status='waiting'
// ------------------------------
$createdAt = date('Y-m-d H:i:s'); // Ώρα Ελλάδας
$status = 'waiting';

// Εισαγωγή στη λίστα αναμονής
$stmt = $conn->prepare("
    INSERT INTO waiting_list 
      (user_id, session_id, email, token, created_at, status)
    VALUES (?, ?, ?, ?, ?, ?)
");
if (!$stmt) {
    show_page_and_exit("❗ Σφάλμα στην προετοιμασία της εντολής: " . $conn->error, "danger");
}

// Bind παραμέτρων
$stmt->bind_param("iissss", $user_id, $session_id, $email, $token, $createdAt, $status);

if ($stmt->execute()) {
    // Μήνυμα επιτυχίας
    show_page_and_exit(
        "✅ Έχετε εγγραφεί στη λίστα αναμονής για το μάθημα. " 
        . "Θα ειδοποιηθείτε με email αν αδειάσει θέση, οπότε θα μπορέσετε να επιβεβαιώσετε.",
        "success"
    );
} else {
    show_page_and_exit("❗ Σφάλμα κατά την εγγραφή στη λίστα αναμονής: " . $stmt->error, "danger");
}

$stmt->close();
$conn->close();
