<?php
session_start();
require_once 'includes/db_connect.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ρυθμίζουμε ζώνη ώρας στην Ελλάδα
date_default_timezone_set('Europe/Athens');

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

/**
 * Συνάρτηση μορφοποίησης ημερομηνίας στα ελληνικά.
 */
function format_date_gr($date) {
    $days = ['Κυριακή', 'Δευτέρα', 'Τρίτη', 'Τετάρτη', 'Πέμπτη', 'Παρασκευή', 'Σάββατο'];
    $timestamp = strtotime($date);
    $day_name = $days[date('w', $timestamp)];
    return $day_name . ' ' . date('d/m/Y', $timestamp);
}

/**
 * Συνάρτηση ελέγχου ακύρωσης συνεδρίας.
 * Αν το μάθημα ξεκινά πριν τις 12:00, το deadline ακύρωσης είναι η προηγούμενη ημέρα στις 18:00.
 * Διαφορετικά, το deadline είναι η ίδια ημέρα στις 12:00.
 */
function canCancelSession($session_date, $start_time) {
    $sessionDateTime = new DateTimeImmutable($session_date . ' ' . $start_time, new DateTimeZone('Europe/Athens'));
    $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Athens'));

    if ($start_time < "12:00:00") {
        $deadline = $sessionDateTime->modify('-1 day')->setTime(18, 0, 0);
    } else {
        $deadline = $sessionDateTime->setTime(12, 0, 0);
    }
    return ($now <= $deadline);
}

// --- Φέρνουμε τα στοιχεία του χρήστη ---
$stmt = $conn->prepare("
    SELECT balance, full_name, trial_approved, trial_used, week_start_date
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("❗ Χρήστης δεν βρέθηκε");
}

$full_name      = htmlspecialchars($user['full_name']);
$balance        = floatval($user['balance']);
$trial_approved = intval($user['trial_approved']);
$trial_used     = intval($user['trial_used']);

// Διάστημα 3 μηνών: τρέχων + 2 επόμενοι
$startOfCalendar = date('Y-m-01');
$endOfCalendar   = date('Y-m-t', strtotime('+2 month'));

// Trial διαθέσιμο;
$trial_available      = ($trial_approved === 1 && $trial_used === 0);
$insufficient_balance = ($balance < 7.5 && !$trial_available);

// --- Φέρνουμε τις κρατήσεις του χρήστη (μόνο για τα session IDs) ---
$stmt_bookings = $conn->prepare("
    SELECT id AS booking_id, session_id
    FROM bookings
    WHERE user_id = ? AND status = 'active'
");
$stmt_bookings->bind_param("i", $user_id);
$stmt_bookings->execute();
$res_bookings = $stmt_bookings->get_result();

$booked_session_ids = [];
while ($booking = $res_bookings->fetch_assoc()) {
    $booked_session_ids[$booking['session_id']] = $booking['booking_id'];
}
$stmt_bookings->close();

// --- Φέρνουμε τις κρατήσεις του χρήστη με πληροφορίες για τις συνεδρίες ---
// Αυτό το query θα χρησιμοποιηθεί για να εμφανιστεί η λίστα με τα μαθήματα που έχει κλειστεί ήδη.
$sql_my_bookings = "
    SELECT b.id AS booking_id, s.date, s.start_time
    FROM bookings b
    JOIN sessions s ON b.session_id = s.id
    WHERE b.user_id = ? 
      AND b.status = 'active'
      AND s.date >= CURDATE()
    ORDER BY s.date ASC, s.start_time ASC
";
$stmt_my_bookings = $conn->prepare($sql_my_bookings);
$stmt_my_bookings->bind_param("i", $user_id);
$stmt_my_bookings->execute();
$res_my_bookings = $stmt_my_bookings->get_result();

$myBookings = [];
while ($row = $res_my_bookings->fetch_assoc()) {
    $myBookings[] = $row;
}
$stmt_my_bookings->close();

// --- Φέρνουμε τις εγγραφές στη λίστα αναμονής του χρήστη ---
$stmt_waiting = $conn->prepare("
    SELECT session_id
    FROM waiting_list
    WHERE user_id = ? 
      AND status IN ('waiting', 'notified')
");
$stmt_waiting->bind_param("i", $user_id);
$stmt_waiting->execute();
$res_waiting = $stmt_waiting->get_result();

$waiting_session_ids = [];
while ($row = $res_waiting->fetch_assoc()) {
    $waiting_session_ids[$row['session_id']] = true;
}
$stmt_waiting->close();

// --- Φέρνουμε τις συνεδρίες του 3μήνου ---
$stmt_sessions = $conn->prepare("
    SELECT *
    FROM sessions
    WHERE status = 'active'
      AND date >= ?
      AND date <= ?
    ORDER BY date, start_time
");
$stmt_sessions->bind_param("ss", $startOfCalendar, $endOfCalendar);
$stmt_sessions->execute();
$res_sessions = $stmt_sessions->get_result();

$sessions_by_date = [];
$now = new DateTimeImmutable();

while ($session = $res_sessions->fetch_assoc()) {
    $session_date = $session['date'];
    $session_time = $session['start_time'];
    $sessionDateTime = new DateTimeImmutable($session_date . ' ' . $session_time);
    // Αποκλείουμε παρελθοντικές συνεδρίες:
    if ($sessionDateTime <= $now) {
        continue;
    }
    $sessions_by_date[$session_date][] = $session;
}
$stmt_sessions->close();
$stmt->close();

// --- Ομαδοποίηση συνεδριών ανά μήνα (YYYY-mm) ---
$sessions_by_month = [];
foreach ($sessions_by_date as $date => $sessions) {
    $monthKey = date("Y-m", strtotime($date));
    if (!isset($sessions_by_month[$monthKey])) {
        $sessions_by_month[$monthKey] = [];
    }
    $sessions_by_month[$monthKey][$date] = $sessions;
}

// --- Δημιουργία καρτελών για 3 μήνες ---
$baseDate = date('Y-m-01'); // Βάση: πρώτη ημέρα του τρέχοντος μήνα
$monthTabs = [];
for ($i = 0; $i < 3; $i++) {
    $monthStart = date('Y-m-01', strtotime("$baseDate +$i month"));
    $monthEnd   = date('Y-m-t', strtotime("$baseDate +$i month"));
    $label      = date('F Y', strtotime($monthStart)); // π.χ. "April 2025"
    $monthKey   = date("Y-m", strtotime($monthStart));
    $monthTabs[] = [
        'key'   => $monthKey,
        'label' => $label,
        'start' => $monthStart,
        'end'   => $monthEnd
    ];
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Go's Studio Pilates - Μαθήματα</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f5f5f5;
            font-family: 'Poppins', sans-serif;
        }
        .container-main {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            max-width: 1000px;
            margin: 20px auto;
        }
        .day-slide {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
        }
        .day-slide::-webkit-scrollbar {
            display: none;
        }
        .day-container {
            flex: 0 0 100%;
            scroll-snap-align: start;
            transition: transform 0.5s ease;
        }
        .day-card {
            background: #fffaf4;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 15px;
        }
        .hour-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .hour-label {
            flex: 0 0 70px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .slot-status {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            color: #fff;
            font-weight: bold;
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease;
        }
        .slot-status form {
            margin-top: 6px;
        }
        .available {
            background-color: #28a745;
            cursor: pointer;
        }
        .selected {
            background-color: #dc3545 !important;
            border: 2px solid #a71d2a;
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.6);
        }
        .full {
            background-color: #6c757d;
        }
        .logout-btn {
            background-color: #a47c48;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
        }
        .nav-buttons {
            text-align: center;
            margin-bottom: 10px;
        }
        .nav-buttons button {
            padding: 6px 12px;
            margin: 0 10px;
        }
        .btn-cancel {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 6px 10px;
            font-size: 0.8rem;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-cancel:hover {
            background-color: #0056b3;
        }
        .scroll-arrow-btn {
            border: 1px solid #ccc;
            background-color: #fff;
            color: #000;
            padding: 2px 8px;
            margin: 2px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="container-main">
    <div class="text-center mb-4">
        <h4 class="mb-2">👋 Καλωσήρθες, <?php echo $full_name; ?>!</h4>
        <div class="alert alert-info p-2">
            Υπόλοιπο: <strong><?php echo number_format($balance, 2); ?>€</strong> |
            Trial: <strong>
                <?php
                if ($trial_approved === 0) {
                   echo 'Δεν έχει εγκριθεί. <a href="tel:+302314072090">Επικοινωνήστε μαζί μας</a>';
                } elseif ($trial_approved === 1 && $trial_used === 0) {
                    echo "Διαθέσιμο";
                } elseif ($trial_approved === 1 && $trial_used === 1) {
                    echo "Χρησιμοποιημένο";
                }
                ?>
            </strong>
        </div>
        <?php if ($insufficient_balance): ?>
            <div class="alert alert-danger p-2">
                ❗ Το υπόλοιπό σου δεν επαρκεί για κράτηση και δεν έχεις διαθέσιμο ή δεν έχει εγκριθεί το trial.
            </div>
        <?php endif; ?>

        <!-- Εμφάνιση λίστας με τα ήδη κλεισμένα μαθήματα με κουμπί ακύρωσης -->
        <?php if (count($myBookings) > 0): ?>
            <div class="alert alert-success" style="text-align: left;">
                <strong>Τα μαθήματα που έχεις ήδη κλείσει:</strong>
                <ul style="margin-bottom: 0;">
                    <?php foreach ($myBookings as $bk): ?>
                        <li>
                            <?php 
                                echo format_date_gr($bk['date']) . ' &bull; ' . substr($bk['start_time'], 0, 5);
                                // Έλεγχος αν ο χρήστης μπορεί να ακυρώσει τη συγκεκριμένη κράτηση
                                if (canCancelSession($bk['date'], $bk['start_time'])): ?>
                                    <form action="cancel_booking.php" method="POST" style="display:inline; margin-left: 10px;">
                                        <input type="hidden" name="booking_id" value="<?php echo $bk['booking_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Επιβεβαιώνετε την ακύρωση;');">
                                            Ακύρωση
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:0.8rem; color:#999; margin-left: 10px;">(Δεν επιτρέπεται ακύρωση)</span>
                                <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary" style="text-align: left;">
                Δεν έχεις κλείσει κανένα μάθημα ακόμα.
            </div>
        <?php endif; ?>

    </div>

    <h3 class="text-center mt-4 mb-3">
        Μαθήματα (<?php echo date("d/m/Y", strtotime($startOfCalendar)); ?> - <?php echo date("d/m/Y", strtotime($endOfCalendar)); ?>)
    </h3>

    <!-- Bootstrap Tabs -->
    <ul class="nav nav-tabs justify-content-center" id="monthTab" role="tablist">
        <?php foreach ($monthTabs as $index => $tab): ?>
            <li class="nav-item" role="presentation">
                <button 
                    class="nav-link <?php echo ($index === 0) ? 'active' : ''; ?>" 
                    id="tab-<?php echo $tab['key']; ?>" 
                    data-bs-toggle="tab" 
                    data-bs-target="#content-<?php echo $tab['key']; ?>" 
                    type="button" 
                    role="tab" 
                    aria-controls="content-<?php echo $tab['key']; ?>" 
                    aria-selected="<?php echo ($index === 0) ? 'true' : 'false'; ?>">
                    <?php echo $tab['label']; ?>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content" id="monthTabContent">
        <?php foreach ($monthTabs as $index => $tab):
            $monthKey = $tab['key'];
            $month_sessions = isset($sessions_by_month[$monthKey]) ? $sessions_by_month[$monthKey] : [];
        ?>
            <div class="tab-pane fade <?php echo ($index === 0) ? 'show active' : ''; ?>" 
                 id="content-<?php echo $monthKey; ?>" 
                 role="tabpanel" 
                 aria-labelledby="tab-<?php echo $monthKey; ?>">

                <div class="text-center my-2">
                    <button class="scroll-arrow-btn" onclick="scrollMonth('slide-<?php echo $monthKey; ?>', -1)">◀</button>
                    <button class="scroll-arrow-btn" onclick="scrollMonth('slide-<?php echo $monthKey; ?>', 1)">▶</button>
                </div>

                <div class="day-slide mt-3" id="slide-<?php echo $monthKey; ?>">
                    <?php
                    $currentDate = strtotime($tab['start']);
                    $endDate    = strtotime($tab['end']);
                    $hasAnySessionThisMonth = false;

                    while ($currentDate <= $endDate) {
                        $dateKey = date('Y-m-d', $currentDate);
                        if (isset($month_sessions[$dateKey])) {
                            $hasAnySessionThisMonth = true;
                            ?>
                            <div class="day-container">
                                <div class="day-card">
                                    <h5 class="text-center"><?php echo format_date_gr($dateKey); ?></h5>
                                    <hr>
                                    <?php foreach ($month_sessions[$dateKey] as $session):
                                        $session_id = $session['id'];
                                        $available = intval($session['available_slots']);
                                        $has_my_booking = isset($booked_session_ids[$session_id]);
                                        $booking_id = $has_my_booking ? $booked_session_ids[$session_id] : null;
                                        $can_book_balance = (!$has_my_booking && $available > 0 && $balance >= 7.5);
                                        $can_book_trial   = (!$has_my_booking && $available > 0 && $trial_available);
                                        ?>
                                        <div class="hour-row">
                                            <div class="hour-label">
                                                <?php echo substr($session['start_time'], 0, 5); ?>
                                            </div>
                                            <div class="slot-status <?php echo $has_my_booking ? 'selected' : ($available < 1 ? 'full' : 'available'); ?>">
                                                <?php
                                                if ($has_my_booking) {
                                                    echo '✅ Έχεις κράτηση';
                                                    ?>
                                                    <form action="cancel_booking.php" method="POST" style="display:inline;">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                                        <button type="submit" class="btn-cancel" onclick="return confirm('Επιβεβαιώνετε την ακύρωση;');">
                                                            Ακύρωση
                                                        </button>
                                                    </form>
                                                    <?php
                                                } elseif ($available < 1) {
                                                    // Έλεγχος αν ο χρήστης είναι ήδη στη λίστα αναμονής για το συγκεκριμένο session
                                                    if (isset($waiting_session_ids[$session_id])) {
                                                        echo "🔔 Είσαι ήδη στη λίστα αναμονής";
                                                    } else {
                                                        echo "🚫 Πλήρες";
                                                        ?>
                                                        <br>
                                                        <button class="btn btn-info btn-sm" onclick="joinWaitingList(<?php echo $session_id; ?>)">
                                                            Εγγραφή στη λίστα αναμονής
                                                        </button>
                                                        <?php
                                                    }
                                                } elseif ($insufficient_balance && !$trial_available) {
                                                    echo "🚫 Δεν έχεις υπόλοιπο";
                                                } else {
                                                    echo $available . " διαθέσιμες";
                                                }
                                                ?>
                                                <div class="mt-2">
                                                    <?php if ($can_book_trial): ?>
                                                        <button class="btn btn-warning btn-sm" onclick="bookTrial(<?php echo $session_id; ?>)">
                                                            Χρήση Trial
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($can_book_balance): ?>
                                                        <button class="btn btn-success btn-sm" onclick="selectSlot(this, <?php echo $session_id; ?>)">
                                                            Κανονική Κράτηση
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php
                        }
                        $currentDate = strtotime('+1 day', $currentDate);
                    }
                    if (!$hasAnySessionThisMonth) {
                        echo '<div class="day-container"><div class="day-card text-center"><h5>Δεν υπάρχουν συνεδρίες</h5></div></div>';
                    }
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center mt-4">
        <a href="logout.php" class="logout-btn">Αποσύνδεση</a>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function scrollMonth(slideId, direction) {
    const slideEl = document.getElementById(slideId);
    if (!slideEl) return;
    slideEl.scrollBy({
        left: direction * 300,
        behavior: 'smooth'
    });
}

function selectSlot(element, sessionId) {
    const confirmed = confirm('Θέλεις να κάνεις κράτηση για αυτό το μάθημα;');
    if (confirmed) {
        window.location.href = `book_now.php?session_id=${sessionId}`;
    }
}

function bookTrial(sessionId) {
    const confirmed = confirm('Θέλεις να χρησιμοποιήσεις το trial για αυτή τη κράτηση;');
    if (confirmed) {
        window.location.href = `book_now.php?session_id=${sessionId}&trial=1`;
    }
}

function joinWaitingList(sessionId) {
    const confirmed = confirm('Θέλεις να εγγραφείς στη λίστα αναμονής για αυτή τη συνεδρία;');
    if (confirmed) {
        window.location.href = `join_waiting.php?session_id=${sessionId}`;
    }
}
</script>
</body>
</html>
