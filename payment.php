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

$user_id = intval($_GET['user_id'] ?? 0);
if ($user_id === 0) {
    die("❗ Μη έγκυρος χρήστης.");
}

// ➤ Παίρνουμε τα στοιχεία του χρήστη
$stmt_user = $conn->prepare("
    SELECT id, full_name, balance, signup_date, weekly_bookings, week_start_date, trial, trial_approved
    FROM users
    WHERE id = ?
");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

if (!$user) {
    die("❗ Ο χρήστης δεν βρέθηκε.");
}

$error = '';
$success = '';

// ➤ Έλεγχος εβδομάδας ➜ Reset αν χρειάζεται
$current_week_start = date('Y-m-d', strtotime('monday this week'));

if ($user['week_start_date'] !== $current_week_start) {
    $stmt_reset_week = $conn->prepare("
        UPDATE users 
        SET weekly_bookings = 0, week_start_date = ?
        WHERE id = ?
    ");
    $stmt_reset_week->bind_param("si", $current_week_start, $user_id);
    $stmt_reset_week->execute();

    $user['weekly_bookings'] = 0;
    $user['week_start_date'] = $current_week_start;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ➤ Αν είναι Trial mode
    if (isset($_POST['give_trial'])) {

        if ($user['trial_approved'] == 1 && $user['trial'] == 1) {
            $error = "⚠ Ο χρήστης έχει ήδη ενεργοποιημένο trial.";
        } else {
            $stmt_trial = $conn->prepare("
                UPDATE users
                SET trial = 1, trial_approved = 1
                WHERE id = ?
            ");
            $stmt_trial->bind_param("i", $user_id);
            if ($stmt_trial->execute()) {
                $success = "✅ Δόθηκε επιτυχώς trial στον χρήστη!";
                $user['trial'] = 1;
                $user['trial_approved'] = 1;
            } else {
                $error = "❗ Σφάλμα κατά την ενεργοποίηση του trial.";
            }
        }

    } elseif (isset($_POST['payment'])) {

        $amount = floatval($_POST['amount'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $notes = trim($_POST['notes'] ?? '');

        if ($amount <= 0) {
            $error = "❗ Το ποσό πρέπει να είναι μεγαλύτερο από 0!";
        } else {
            $conn->begin_transaction();

            try {
                // ➤ Υπολογισμός νέου υπολοίπου
                $new_balance = round($user['balance'] + $amount, 2);

                // ➤ Υπολογισμός νέου weekly_sessions_allowed
                $weekly_sessions_allowed = calculate_weekly_sessions_allowed($new_balance, $user['trial']);

                // ➤ Ενημέρωση του χρήστη
                $stmt_update_user = $conn->prepare("
                    UPDATE users
                    SET balance = ?, weekly_sessions_allowed = ?
                    WHERE id = ?
                ");
                $stmt_update_user->bind_param("dii", $new_balance, $weekly_sessions_allowed, $user_id);
                $stmt_update_user->execute();

                $conn->commit();

                // ➤ Ενημέρωση του τοπικού array για εμφάνιση
                $user['balance'] = $new_balance;

                $success = "✅ Η πληρωμή καταχωρήθηκε επιτυχώς!<br> 
                            Νέο Υπόλοιπο: <strong>€" . number_format($new_balance, 2) . "</strong><br>
                            Επιτρεπόμενα Εβδομαδιαία Μαθήματα: <strong>{$weekly_sessions_allowed}</strong>";

            } catch (Exception $e) {
                $conn->rollback();
                $error = "❗ Σφάλμα κατά την αποθήκευση της πληρωμής: " . $e->getMessage();
            }
        }
    }
}

// ➤ Συνάρτηση Υπολογισμού Επιτρεπόμενων Συνεδριών Βάσει Υπολοίπου + trial
function calculate_weekly_sessions_allowed($balance, $trial) {
    $sessions = 0;
    $remaining = $balance;

    if ($trial == 1) {
        $sessions++; // Trial προσθέτει 1 έξτρα συνεδρία
    }

    if ($remaining < 12) return $sessions;

    $sessions++;
    $remaining -= 12;

    if ($remaining >= 6) {
        $sessions++;
        $remaining -= 6;
    }

    if ($remaining >= 7) {
        $sessions++;
        $remaining -= 7;
    }

    while ($remaining >= 7.5) {
        $sessions++;
        $remaining -= 7.5;
    }

    return $sessions;
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Καταχώρηση Πληρωμής Πελάτη</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4 text-center">Καταχώρηση Πληρωμής Πελάτη</h2>

    <div class="card shadow p-4">
        <h4>Πελάτης: <strong><?= htmlspecialchars($user['full_name']); ?></strong></h4>
        <p>Υπόλοιπο: <strong>€<?= number_format($user['balance'], 2); ?></strong></p>
        <p>Ημερομηνία Εγγραφής: <strong><?= htmlspecialchars($user['signup_date']); ?></strong></p>
        <p>Εβδομαδιαία Κρατήσεις: <strong><?= intval($user['weekly_bookings']); ?></strong></p>
        <p>Έναρξη Τρέχουσας Εβδομάδας: <strong><?= htmlspecialchars($user['week_start_date']); ?></strong></p>
        <p>Trial Status: <?= ($user['trial'] == 1) ? '<span class="badge bg-success">Ενεργό</span>' : '<span class="badge bg-secondary">Ανενεργό</span>'; ?></p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?= $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <h5 class="mt-3">➤ Καταχώρηση Πληρωμής</h5>
            <div class="mb-3">
                <label class="form-label">Ποσό Πληρωμής (€):</label>
                <input type="number" name="amount" step="0.01" min="0" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Μέθοδος Πληρωμής:</label>
                <select name="payment_method" class="form-select">
                    <option value="cash">Μετρητά</option>
                    <option value="card">Κάρτα</option>
                    <option value="bank">Κατάθεση Τράπεζας</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Σημειώσεις (προαιρετικά):</label>
                <textarea name="notes" class="form-control"></textarea>
            </div>

            <button type="submit" name="payment" class="btn btn-primary">Καταχώρηση Πληρωμής</button>
        </form>

        <hr>

        <form method="POST" action="">
            <h5 class="mt-3">➤ Ενεργοποίηση Trial</h5>
            <button type="submit" name="give_trial" class="btn btn-warning" <?= ($user['trial'] == 1) ? 'disabled' : ''; ?>>
                Δώσε Trial
            </button>
        </form>

        <div class="mt-4">
            <a href="customer_balances.php" class="btn btn-secondary">🔙 Επιστροφή</a>
        </div>
    </div>
</div>

</body>
</html>
