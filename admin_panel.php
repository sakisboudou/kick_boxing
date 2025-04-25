<?php
session_start();
require_once 'includes/db_connect.php';

// Ενεργοποίηση σφαλμάτων (για debugging προαιρετικά)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Βεβαιωθείτε ότι έχετε εγκαταστήσει το PHPMailer μέσω Composer και ότι συμπεριλαμβάνεται το autoload:
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ➤ Έλεγχος admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

// ----------------------------------------------
// (Α) Διαχείριση POST (approve, revoke, deposit)
// ----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $action  = $_POST['action'];
        $user_id = intval($_POST['user_id']);

        // 1) Approve Trial
        if ($action === 'approve') {
            // Ενημέρωση του πίνακα για να γίνει approve το trial
            $stmt = $conn->prepare("UPDATE users SET trial_approved = 1 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Ανάκτηση email και ονόματος χρήστη για αποστολή email
            $stmt_email = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $stmt_email->bind_param("i", $user_id);
            $stmt_email->execute();
            $result_email = $stmt_email->get_result();
            $user_info = $result_email->fetch_assoc();
            $stmt_email->close();

           // Αποστολή email μέσω PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Βασικές ρυθμίσεις UTF-8
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';
                // Ρυθμίσεις διακομιστή (SMTP)
                // $mail->SMTPDebug = 2; // Ενεργοποιήστε για debugging αν χρειαστεί
                $mail->isSMTP();
                $mail->Host       = 'mail.gosstudio.gr';           // Ο SMTP server σας
                $mail->SMTPAuth   = true;
                $mail->Username   = 'info@gosstudio.gr';           // Το email χρήστη του λογαριασμού αποστολέα
                $mail->Password   = 'Arf199vbn@arf';                // Το password του λογαριασμού σας
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;     // Χρήση SMTPS (SSL)
                $mail->Port       = 465;                             // Θύρα SMTPS
            
                // Ρυθμίσεις αποστολέα & παραλήπτη
                $mail->setFrom('info@gosstudio.gr', "Go's Studio Pilates");
                $mail->addAddress($user_info['email']);
            
                // Σύνθεση email (HTML & Plain Text)
                $mail->isHTML(true);
                $mail->Subject = 'Καλωσόρισμα στο Go\'s Studio Pilates!';
                $full_name = $user_info['full_name'];
                $mail->Body = '
                <!DOCTYPE html>
                <html>
                <head>
                  <meta charset="utf-8">
                  <title>Καλωσόρισμα στο Go\'s Studio Pilates!</title>
                  <style>
                    body {
                      background-color: #f5f5f5;
                      font-family: "Poppins", sans-serif;
                      margin: 0;
                      padding: 0;
                    }
                    .container {
                      max-width: 600px;
                      margin: 0 auto;
                      background: #ffffff;
                      border-radius: 8px;
                      overflow: hidden;
                      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    }
                    .header {
                      background-color: #a47c48;
                      color: #ffffff;
                      text-align: center;
                      padding: 20px;
                    }
                    .content {
                      padding: 20px;
                      color: #333333;
                      font-size: 16px;
                      line-height: 1.6;
                    }
                    .content a.btn {
                      display: inline-block;
                      background-color: #a47c48;
                      color: #ffffff;
                      padding: 10px 20px;
                      text-decoration: none;
                      border-radius: 5px;
                      margin-top: 20px;
                    }
                    .content a.btn:hover {
                      background-color: #8b6436;
                    }
                    .footer {
                      background-color: #eee;
                      padding: 10px;
                      text-align: center;
                      font-size: 12px;
                      color: #666666;
                    }
                    .footer img {
                      max-width: 20px;
                    }
                  </style>
                </head>
                <body>
                  <div class="container">
                    <div class="header">
                      <h1>Καλωσήλθες!</h1>
                    </div>
                    <div class="content">
                      <p>Γεια σου ' . $full_name . ',</p>
                      <p>
                        Σ’ ευχαριστούμε που εγγράφηκες στο Go\'s Studio Pilates. Ο λογαριασμός σου έχει εγκριθεί για το trial και μπορείς πλέον να κάνεις login και να κλείσεις το πρώτο σου ραντεβού.
                      </p>
                      <p>
                        Παρακαλούμε, διάβασε τους κανόνες ακύρωσης στην ιστοσελίδα μας πριν ολοκληρώσεις την κράτησή σου.
                      </p>
                      <p style="text-align: center;">
                        <a href="https://gosstudio.gr/login.php" class="btn">Κάνε Login Τώρα</a>
                      </p>
                      <p>
                        Με εκτίμηση,<br>
                        Η ομάδα του Go\'s Studio Pilates
                      </p>
                    </div>
                    <div class="footer">
                      <p>Design by Sakis Boudouridis </p>
                    </div>
                  </div>
                </body>
                </html>
                ';
            
                $mail->AltBody = "Γεια σου $full_name,\n\n"
                               . "Σ’ ευχαριστούμε που εγγράφηκες στο Go's Studio Pilates. Ο λογαριασμός σου έχει εγκριθεί για το trial και μπορείς πλέον να κάνεις login και να κλείσεις το πρώτο σου ραντεβού.\n\n"
                               . "Παρακαλούμε, διάβασε τους κανόνες ακύρωσης στην ιστοσελίδα μας.\n\n"
                               . "Με εκτίμηση,\n"
                               . "Η ομάδα του Go's Studio Pilates";
            
                $mail->send();
            } catch (Exception $e) {
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }

           
        }
        // 2) Revoke Trial
        elseif ($action === 'revoke') {
            $stmt = $conn->prepare("UPDATE users SET trial_approved = 0 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
        // 3) Καταχώρηση πληρωμής (deposit)
        elseif ($action === 'deposit') {
            $amount = floatval($_POST['deposit_amount'] ?? 0);
            if ($amount > 0) {
                // Ενημέρωση balance στον πίνακα users
                $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->bind_param("di", $amount, $user_id);
                $stmt->execute();
                $stmt->close();

                // Καταγραφή συναλλαγής στο transactions
                $desc = 'Admin deposit';
                $stmtT = $conn->prepare("
                    INSERT INTO transactions (user_id, amount, type, description)
                    VALUES (?, ?, 'deposit', ?)
                ");
                $stmtT->bind_param("ids", $user_id, $amount, $desc);
                $stmtT->execute();
                $stmtT->close();
            }
        }
    }
    // Μετά από κάθε ενέργεια, κάνουμε redirect (POST/Redirect/GET)
    header("Location: admin_panel.php");
    exit;
}

// ----------------------------------------------
// (Β) Ορισμός χρονικού διαστήματος 3 μηνών
//     (τρέχων μήνας + 2 επόμενοι)
// ----------------------------------------------
$baseDate = date('Y-m-01'); // 1η μέρα τρέχοντος μήνα
$startOfCalendar = date('Y-m-01', strtotime($baseDate));
$endOfCalendar   = date('Y-m-t', strtotime("$baseDate +2 month"));

// Δημιουργούμε έναν πίνακα με τις "καρτέλες" των 3 μηνών
$monthTabs = [];
for ($i = 0; $i < 3; $i++) {
    $monthStart = date('Y-m-01', strtotime("$baseDate +$i month"));
    $monthEnd   = date('Y-m-t', strtotime("$baseDate +$i month"));
    $label      = date('F Y', strtotime($monthStart)); // π.χ. "April 2025"
    $monthKey   = date('Y-m', strtotime($monthStart));

    $monthTabs[] = [
        'key'   => $monthKey,
        'label' => $label,
        'start' => $monthStart,
        'end'   => $monthEnd
    ];
}

// ----------------------------------------------
// (Γ) Φέρνουμε ΟΛΕΣ τις κρατήσεις εντός 3μήνου
// ----------------------------------------------
$sqlBookings = "
  SELECT b.id AS booking_id,
         b.user_id,
         b.session_id,
         b.status,
         b.booking_cost,
         s.date AS session_date,
         s.start_time,
         s.max_capacity,
         u.full_name
  FROM bookings b
  JOIN sessions s ON b.session_id = s.id
  JOIN users    u ON b.user_id = u.id
  WHERE b.status = 'active'
    AND s.date >= ?
    AND s.date <= ?
  ORDER BY s.date, s.start_time
";
$stmtBookings = $conn->prepare($sqlBookings);
$stmtBookings->bind_param("ss", $startOfCalendar, $endOfCalendar);
$stmtBookings->execute();
$resBookings = $stmtBookings->get_result();
$stmtBookings->close();

// ----------------------------------------------
// Συναρτήσεις για μορφοποίηση ημερομηνίας & έλεγχο παρελθοντικών
// ----------------------------------------------
function format_date_gr($date) {
    // Μορφή: Τετάρτη 26/03/2025
    $days = ['Κυριακή','Δευτέρα','Τρίτη','Τετάρτη','Πέμπτη','Παρασκευή','Σάββατο'];
    $ts = strtotime($date);
    $dayName = $days[date('w', $ts)];
    $dayNum  = date('d', $ts);
    $monthNum= date('m', $ts);
    $yearNum = date('Y', $ts);
    return "{$dayName} {$dayNum}/{$monthNum}/{$yearNum}";
}

function isPastSession($date, $time) {
    // Ελέγχει αν (date time) <= τώρα
    $sessionDateTime = new DateTimeImmutable("$date $time");
    $now = new DateTimeImmutable();
    return ($sessionDateTime <= $now);
}

// Ομαδοποίηση κρατήσεων ανά (date -> time)
$bookings_by_date_time = [];
while ($row = $resBookings->fetch_assoc()) {
    $date = $row['session_date'];
    $time = substr($row['start_time'], 0, 5); // π.χ. "08:00"

    // Παραλείπουμε τις παρελθοντικές κρατήσεις
    if (isPastSession($date, $row['start_time'])) {
        continue;
    }
    $bookings_by_date_time[$date][$time][] = $row;
}

// ----------------------------------------------
// (Δ) Λίστα Χρηστών με δυνατότητα αναζήτησης
// ----------------------------------------------
$search_name = trim($_GET['search_name'] ?? '');

$sqlUsers = "
  SELECT id, full_name, email, balance, trial_approved, trial_used, is_admin
  FROM users
  ORDER BY id ASC
";
if (!empty($search_name)) {
    $sqlUsers = "
      SELECT id, full_name, email, balance, trial_approved, trial_used, is_admin
      FROM users
      WHERE full_name LIKE ?
      ORDER BY id ASC
    ";
}

if (!empty($search_name)) {
    $stmtUsers = $conn->prepare($sqlUsers);
    $like_name = "%".$search_name."%";
    $stmtUsers->bind_param("s", $like_name);
} else {
    $stmtUsers = $conn->prepare($sqlUsers);
}

$stmtUsers->execute();
$resUsers = $stmtUsers->get_result();
$stmtUsers->close();

// ----------------------------------------------
// (Ε) Εξυπηρέτηση νέων χρηστών (Banner)
// Εδώ θεωρούμε "νέους" χρήστες αυτούς που δεν έχουν εγκριθεί για trial (και δεν είναι admin).
// Αν υπάρχει πεδίο registration_date, μπορείς να το προσθέσεις στο WHERE (π.χ. νέοι των τελευταίων 7 ημερών).
// ----------------------------------------------
$sqlNewUsers = "
    SELECT id, full_name, email
    FROM users
    WHERE trial_approved = 0
      AND trial_used = 0
      AND is_admin = 0
";
$stmtNewUsers = $conn->prepare($sqlNewUsers);
$stmtNewUsers->execute();
$resNewUsers = $stmtNewUsers->get_result();

$newUsers = [];
while ($row = $resNewUsers->fetch_assoc()) {
    $newUsers[] = $row;
}
$stmtNewUsers->close();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Κρατήσεις 3μήνου (Admin Panel)</title>
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
            max-width: 1100px;
            margin: 20px auto;
        }
        .btn-logout {
            background-color: #a47c48;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            padding: 8px 12px;
        }
        .btn-logout:hover {
            background-color: #8b6436;
        }
        h2, h3 {
            text-align: center;
            margin-bottom: 20px;
        }
        /* Tabs */
        .nav-tabs .nav-link.active {
            background-color: #ddd;
        }
        /* Slider styling */
        .day-slide {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            scroll-snap-type: x mandatory;
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
            margin-bottom: 8px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .hour-time {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .booking-user {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        /* Πίνακας χρηστών */
        table.table thead th {
            background-color: #f0eade;
        }
        .form-deposit {
            display: inline-flex;
            gap: 4px;
            align-items: center;
        }
        .form-deposit input[type="number"] {
            width: 80px;
        }
        /* Responsive */
        @media (max-width: 576px) {
            .container-main {
                padding: 10px;
                margin: 10px auto;
            }
            .day-card {
                padding: 10px;
            }
            .booking-user {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            .booking-user div:last-child {
                margin-left: auto;
            }
        }
    </style>
</head>
<body>

<div class="container-main">
    <!-- Πάνω κουμπιά -->
    <div class="d-flex flex-column align-items-center gap-2 mb-3">
        <a href="logout.php" class="btn btn-sm btn-danger" style="min-width:150px;">Αποσύνδεση</a>
        <a href="customer_history.php" class="btn btn-sm btn-info" style="min-width:150px;">Ιστορικό Πελατών</a>
        <a href="weekly_reconciliation.php" class="btn btn-sm btn-warning" style="min-width:150px;">Weekly Reconciliation</a>
    </div>

    <!-- Banner νέων χρηστών -->
    <?php if (count($newUsers) > 0): ?>
        <div class="alert alert-warning">
            <strong>Νέοι χρήστες εγγραφής:</strong>
            <ul>
                <?php foreach ($newUsers as $nu): ?>
                    <li>
                        <?php echo htmlspecialchars($nu['full_name']) . " (" . htmlspecialchars($nu['email']) . ")"; ?>
                        <!-- Κουμπί για έγκριση trial -->
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="user_id" value="<?php echo $nu['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-sm btn-success">Approve Trial</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Τίτλος -->
    <h3 class="text-center">Κρατήσεις 3μήνου</h3>

    <!-- Tabs για τους 3 μήνες -->
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
                    aria-selected="<?php echo ($index === 0) ? 'true' : 'false'; ?>"
                >
                    <?php echo $tab['label']; ?>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Περιεχόμενο των 3 tabs -->
    <div class="tab-content" id="monthTabContent">
        <?php foreach ($monthTabs as $index => $tab):
            $monthKey   = $tab['key'];
            $monthStart = $tab['start'];
            $monthEnd   = $tab['end'];
        ?>
            <div class="tab-pane fade <?php echo ($index === 0) ? 'show active' : ''; ?>"
                 id="content-<?php echo $monthKey; ?>"
                 role="tabpanel"
                 aria-labelledby="tab-<?php echo $monthKey; ?>">

                <!-- Κουμπιά για scroll αριστερά/δεξιά -->
                <div class="text-center my-3">
                    <button class="btn btn-secondary" onclick="scrollDays('<?php echo $monthKey; ?>', -1)">◀ Προηγούμενη</button>
                    <button class="btn btn-secondary" onclick="scrollDays('<?php echo $monthKey; ?>', 1)">Επόμενη ▶</button>
                </div>

                <!-- Slider ανά ημέρα για τον μήνα -->
                <div class="day-slide" id="slider-<?php echo $monthKey; ?>">
                    <?php
                    // Περνάμε από κάθε ημέρα του συγκεκριμένου μήνα
                    $currentDay = strtotime($monthStart);
                    $endDay     = strtotime($monthEnd);
                    $localDayIndex = 0;

                    while ($currentDay <= $endDay):
                        $dateKey = date('Y-m-d', $currentDay);
                        
                        // Μη εμφάνιση προηγούμενων ημερών από τη σημερινή
                        if ($dateKey < date('Y-m-d')) {
                            $currentDay = strtotime('+1 day', $currentDay);
                            continue;
                        }
                        
                        // Μη εμφάνιση Σαββάτου (6) και Κυριακής (7)
                        if (in_array(date('N', $currentDay), [6,7])) {
                            $currentDay = strtotime('+1 day', $currentDay);
                            continue;
                        }
                        
                        $displayDate = format_date_gr($dateKey);
                        $timesArray = isset($bookings_by_date_time[$dateKey]) ? $bookings_by_date_time[$dateKey] : [];
                    ?>
                        <div class="day-container" id="day-<?php echo $monthKey . '-' . $localDayIndex; ?>">
                            <div class="day-card">
                                <h5 class="text-center mb-3"><?php echo $displayDate; ?></h5>
                                <hr>
                                <?php if (empty($timesArray)): ?>
                                    <div class="text-center text-muted">Δεν υπάρχουν κρατήσεις</div>
                                <?php else: ?>
                                    <?php foreach ($timesArray as $time => $bookingsAtTime):
                                        $maxCapacity   = intval($bookingsAtTime[0]['max_capacity']);
                                        $bookingsCount = count($bookingsAtTime);
                                        $freeSlots     = $maxCapacity - $bookingsCount;
                                    ?>
                                        <div class="hour-row">
                                            <div class="hour-time">
                                                <?php echo $time; ?>
                                                <span style="color: green; font-size: 0.9rem;">
                                                    (<?php echo $freeSlots; ?> διαθέσιμες θέσεις)
                                                </span>
                                            </div>
                                            <?php foreach ($bookingsAtTime as $bk):
                                                $booking_id = $bk['booking_id'];
                                                $username   = htmlspecialchars($bk['full_name']);
                                                $cost       = number_format($bk['booking_cost'], 2) . '€';
                                            ?>
                                                <div class="booking-user">
                                                    <div>
                                                        <strong>Χρήστης:</strong> <?php echo $username; ?><br>
                                                        <strong>Κόστος:</strong> <?php echo $cost; ?>
                                                    </div>
                                                    <div>
                                                        <form method="POST" action="admin_cancel_booking.php" style="display:inline-block;">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">Ακύρωση</button>
                                                        </form>
                                                        <a href="admin_move_booking.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-sm btn-warning">
                                                            Μετακίνηση
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php
                        $localDayIndex++;
                        $currentDay = strtotime('+1 day', $currentDay);
                    endwhile;
                    ?>
                </div> <!-- .day-slide -->
            </div> <!-- .tab-pane -->
        <?php endforeach; ?>
    </div> <!-- .tab-content -->

    <hr class="my-4">

    <!-- (Ζ) Λίστα Χρηστών (με αναζήτηση) -->
    <h3 class="text-center">Λίστα Χρηστών</h3>
    <form method="GET" action="admin_panel.php" class="mb-4">
        <div class="input-group">
            <input type="text" name="search_name" class="form-control"
                   placeholder="Αναζήτηση χρήστη..."
                   value="<?php echo htmlspecialchars($search_name); ?>">
            <button type="submit" class="btn btn-primary">Αναζήτηση</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Όνομα</th>
                    <th>Email</th>
                    <th>Υπόλοιπο</th>
                    <th>Admin?</th>
                    <th>Trial Approved</th>
                    <th>Trial Used</th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $resUsers->fetch_assoc()): 
                    $user_id   = $u['id'];
                    $full_name = htmlspecialchars($u['full_name']);
                    $email     = htmlspecialchars($u['email']);
                    $balance   = floatval($u['balance']);
                    $is_admin  = ($u['is_admin'] == 1) ? 'ΝΑΙ' : 'ΟΧΙ';
                    $trial_appr= ($u['trial_approved'] == 1) ? 'ΝΑΙ' : 'ΟΧΙ';
                    $trial_used= ($u['trial_used'] == 1) ? 'ΝΑΙ' : 'ΟΧΙ';
                ?>
                    <tr>
                        <td><?php echo $user_id; ?></td>
                        <td><?php echo $full_name; ?></td>
                        <td><?php echo $email; ?></td>
                        <td><?php echo number_format($balance, 2); ?>€</td>
                        <td><?php echo $is_admin; ?></td>
                        <td><?php echo $trial_appr; ?></td>
                        <td><?php echo $trial_used; ?></td>
                        <td>
                            <?php if ($u['trial_approved'] == 0): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-sm btn-success">Approve Trial</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                    <input type="hidden" name="action" value="revoke">
                                    <button type="submit" class="btn btn-sm btn-danger">Revoke Trial</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" class="form-deposit ms-2">
                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                <input type="hidden" name="action" value="deposit">
                                <input type="number" name="deposit_amount" step="0.01" min="0.01" placeholder="€">
                                <button type="submit" class="btn btn-sm btn-primary">Καταχώρηση</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div> <!-- .table-responsive -->
</div> <!-- .container-main -->

<!-- Απαιτείται Bootstrap JS για τα tabs -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/**
 * scrollDays: Μετακινεί οριζόντια το slider των ημερών για τον συγκεκριμένο μήνα
 * @param {string} monthKey - π.χ. "2025-03"
 * @param {number} direction - -1 (αριστερά), +1 (δεξιά)
 */
function scrollDays(monthKey, direction) {
    const sliderId = 'slider-' + monthKey;
    const sliderEl = document.getElementById(sliderId);
    if (!sliderEl) return;
    sliderEl.scrollBy({
        left: direction * 300,
        behavior: 'smooth'
    });
}
</script>
</body>
</html>
