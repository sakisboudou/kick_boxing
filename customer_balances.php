<?php
session_start();
require_once 'includes/db_connect.php';
date_default_timezone_set('Europe/Athens');

ini_set('display_errors', 1);
error_reporting(E_ALL);

// ➤ Έλεγχος admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("Μόνο οι διαχειριστές έχουν πρόσβαση εδώ.");
}

$today = new DateTime();
$current_week_start = date('Y-m-d', strtotime('monday this week'));

// ➤ Παίρνουμε χρήστες (εκτός admin)
$query = "
    SELECT 
        id, 
        full_name, 
        balance, 
        weekly_bookings, 
        week_start_date,
        weekly_sessions_allowed,
        trial,
        trial_approved
    FROM users
    WHERE is_admin = 0
    ORDER BY full_name
";

$result = $conn->query($query);

if (!$result) {
    die("Σφάλμα στο query: " . $conn->error);
}

// ➤ Κάνουμε reset όπου χρειάζεται και υπολογίζουμε τα allowed sessions
while ($user = $result->fetch_assoc()) {
    $user_id            = $user['id'];
    $week_start_date    = $user['week_start_date'];
    $balance            = $user['balance'];
    $trial              = $user['trial'];
    $weekly_sessions_allowed = $user['weekly_sessions_allowed'];

    // ➤ Εβδομαδιαίο reset αν μπήκαμε σε νέα εβδομάδα
    if ($week_start_date !== $current_week_start) {
        $stmt_reset_week = $conn->prepare("
            UPDATE users
            SET weekly_bookings = 0, week_start_date = ?
            WHERE id = ?
        ");
        $stmt_reset_week->bind_param("si", $current_week_start, $user_id);
        $stmt_reset_week->execute();
    }

    // ➤ Υπολογισμός εβδομαδιαίων συνεδριών με βάση το balance
    $weekly_sessions_allowed = calculate_weekly_sessions_allowed($balance);

    $stmt_update_sessions = $conn->prepare("
        UPDATE users
        SET weekly_sessions_allowed = ?
        WHERE id = ?
    ");
    $stmt_update_sessions->bind_param("ii", $weekly_sessions_allowed, $user_id);
    $stmt_update_sessions->execute();
}

// ➤ Ξαναπαίρνουμε τα δεδομένα για εμφάνιση
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Υπόλοιπα Πελατών</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .search-input { max-width: 400px; margin-bottom: 15px; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4 text-center">Υπόλοιπα Πελατών</h2>

    <div class="mb-3 text-start">
        <a href="admin_panel.php" class="btn btn-secondary">🔙 Επιστροφή στο Admin Panel</a>
    </div>

    <div class="mb-3 text-end">
        <input type="text" class="form-control search-input" id="searchInput" placeholder="Αναζήτηση Πελάτη...">
    </div>

    <table class="table table-bordered table-hover" id="customerTable">
        <thead class="table-dark">
            <tr>
                <th>Όνομα Πελάτη</th>
                <th>Υπόλοιπο (€)</th>
                <th>Κρατήσεις Εβδομάδας</th>
                <th>Επιτρεπόμενες Συνεδρίες</th>
                <th>Διαθέσιμες Συνεδρίες</th>
                <th>Υπόλοιπες Ημέρες Εβδομάδας</th>
                <th>Trial</th>
                <th>Χρήση Προγράμματος</th>
                <th>Ενέργεια</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php
                $user_id                = $row['id'];
                $full_name              = $row['full_name'];
                $balance                = floatval($row['balance']);
                $weekly_bookings        = intval($row['weekly_bookings']);
                $weekly_sessions_allowed= intval($row['weekly_sessions_allowed']);
                $available_sessions     = max(0, $weekly_sessions_allowed - $weekly_bookings);
                $trial                  = intval($row['trial']);
                $trial_approved         = intval($row['trial_approved']);

                $remaining_days = (new DateTime('Sunday this week'))->diff($today)->days;

                $usage_text = "{$weekly_bookings} / {$weekly_sessions_allowed} συνεδρίες";
                $progress_percentage = ($weekly_sessions_allowed > 0)
                    ? ($weekly_bookings / $weekly_sessions_allowed) * 100
                    : 0;

                $bar_class = 'bg-success';
                if ($progress_percentage >= 75) $bar_class = 'bg-warning';
                if ($progress_percentage >= 100) $bar_class = 'bg-danger';

                $progress_bar = '
                    <div class="progress mt-1">
                        <div class="progress-bar ' . $bar_class . '" role="progressbar" style="width: ' . $progress_percentage . '%;">
                            ' . round($progress_percentage) . '% 
                        </div>
                    </div>';

                // ➤ Trial εμφανιση
                $trial_status = $trial == 1 ? '✅ Διαθέσιμο' : '❌ Μη διαθέσιμο';
                if ($trial_approved == 0) {
                    $trial_status .= ' <span class="badge bg-warning">Προς έγκριση</span>';
                }
            ?>

            <tr>
                <td><?= htmlspecialchars($full_name); ?></td>
                <td><?= number_format($balance, 2); ?>€</td>
                <td><?= $weekly_bookings; ?></td>
                <td><?= $weekly_sessions_allowed; ?></td>
                <td><?= $available_sessions; ?></td>
                <td><?= $remaining_days; ?> ημέρες</td>
                <td><?= $trial_status; ?></td>
                <td>
                    <?= $usage_text; ?>
                    <?= $progress_bar; ?>
                </td>
                <td class="text-center">
                    <a href="payment.php?user_id=<?= intval($user_id); ?>" class="btn btn-primary btn-sm mb-1">💳 Πληρωμή</a><br>
                    <?php if ($trial_approved == 0): ?>
                        <a href="approve_trial.php?user_id=<?= intval($user_id); ?>" class="btn btn-warning btn-sm">✔ Έγκριση Trial</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div class="mb-3 text-end">
        <a href="export_balances.php" class="btn btn-outline-success">📥 Εξαγωγή σε Excel</a>
    </div>
</div>

<script>
// 🔎 Αναζήτηση Πελάτη
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#customerTable tbody tr');

    rows.forEach(row => {
        let name = row.querySelector('td').textContent.toLowerCase();
        row.style.display = name.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>

<?php
// ➤ Υπολογισμός εβδομαδιαίων συνεδριών με βάση το balance
function calculate_weekly_sessions_allowed($balance) {
    $sessions = 0;
    $remaining = $balance;
    $next_booking_number = 1;

    while (true) {
        $cost = booking_cost($next_booking_number);
        if ($remaining >= $cost) {
            $sessions++;
            $remaining -= $cost;
            $next_booking_number++;
        } else {
            break;
        }
    }
    return $sessions;
}

// ➤ Κόστος ανά κράτηση
function booking_cost($booking_number) {
    switch ($booking_number) {
        case 1: return 12;
        case 2: return 6;
        case 3: return 7;
        default: return 7.5;
    }
}
?>
