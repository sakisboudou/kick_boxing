<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Στέλνει email σε χρήστη της waiting_list, ειδοποιώντας ότι άνοιξε θέση.
 */
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

        // HTML Email με ενσωματωμένο styling
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>Ειδοποίηση Διαθέσιμης Θέσης</title>
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
              padding: 30px;
              border-radius: 10px;
              box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .header {
              text-align: center;
              margin-bottom: 20px;
            }
            .header img {
              height: 60px;
            }
            h2 {
              color: #a47c48;
              text-align: center;
              margin-bottom: 20px;
            }
            p {
              font-size: 16px;
              color: #333333;
              line-height: 1.6;
            }
            .btn {
              display: inline-block;
              background-color: #a47c48;
              color: white;
              padding: 14px 24px;
              border-radius: 6px;
              text-decoration: none;
              font-weight: bold;
              transition: background-color 0.3s;
            }
            .btn:hover {
              background-color: #8b6436;
            }
            .footer {
              text-align: center;
              font-size: 12px;
              color: #999999;
              margin-top: 30px;
              border-top: 1px solid #eee;
              padding-top: 20px;
            }
            .footer img {
              display: inline-block;
              border: 0;
              width: 20px !important;
              height: 20px !important;
              vertical-align: middle;
            }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <img src="https://gosstudio.gr/assets/images/logo_128x128.png" alt="Go\'s Studio">
            </div>
            <h2>Έχετε μια νέα ευκαιρία ✨</h2>
            <p>Γεια σας,</p>
            <p>
              Μόλις άδειασε θέση για το μάθημα της <strong>' . $formattedDate . '</strong> στις <strong>' . $formattedTime . '</strong>.
            </p>
            <p>
              Πατήστε στο κουμπί παρακάτω για να επιβεβαιώσετε την κράτησή σας εντός <strong>30 λεπτών</strong>:
            </p>
            <p style="text-align: center; margin: 30px 0;">
              <a href="' . $confirmationLink . '" class="btn">Επιβεβαίωση Θέσης</a>
            </p>
            <p style="font-size: 14px; color: #666666;">
              Αν δεν επιβεβαιώσετε εγκαίρως, η θέση θα προσφερθεί σε άλλο χρήστη.
            </p>
            <div class="footer">
              <p>Go\'s Studio Pilates • <a href="https://gosstudio.gr" style="color: #a47c48; text-decoration: none;">gosstudio.gr</a></p>
            </div>
          </div>
        </body>
        </html>';

        // AltBody (plain text)
        $mail->AltBody = "Γεια σας,\n\nΜόλις άδειασε θέση για το μάθημα την $formattedDate, ώρα $formattedTime.\n\nΕπιβεβαιώστε εντός 30 λεπτών:\n$confirmationLink\n\nGo's Studio Pilates";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}


// Ρυθμίζουμε ζώνη ώρας Ελλάδας
date_default_timezone_set('Europe/Athens');

// Έλεγχος αν ο χρήστης είναι admin
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    die("⛔ Δεν έχετε δικαίωμα πρόσβασης (admin μόνο).");
}

// Δεχόμαστε μόνο POST αιτήσεις
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    if ($booking_id <= 0) {
        die("❗ Μη έγκυρο booking_id.");
    }

    $conn->begin_transaction();
    try {
        // 1) Φορτώνουμε στοιχεία της κράτησης
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

        // Έλεγχος αν είναι ενεργή
        if ($booking['status'] !== 'active') {
            throw new Exception("❗ Η κράτηση δεν είναι ενεργή ή έχει ήδη ακυρωθεί.");
        }

        $user_id      = intval($booking['user_id']);
        $session_id   = intval($booking['session_id']);
        $booking_cost = floatval($booking['booking_cost']);
        $session_date = $booking['session_date'];
        $start_time   = $booking['start_time'];

        // 2) Ακυρώνουμε την κράτηση
        $stmtCancel = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmtCancel->bind_param("i", $booking_id);
        $stmtCancel->execute();
        $stmtCancel->close();

        // 3) Επιστροφή χρημάτων (αν υπάρχει κόστος κράτησης)
        if ($booking_cost > 0) {
            $stmtRefund = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmtRefund->bind_param("di", $booking_cost, $user_id);
            $stmtRefund->execute();
            $stmtRefund->close();

            $createdAt = date('Y-m-d H:i:s');
            $desc      = "Admin cancellation refund (booking_id={$booking_id})";
            $stmtTrans = $conn->prepare("
                INSERT INTO transactions (user_id, amount, type, created_at, description)
                VALUES (?, ?, 'refund', ?, ?)
            ");
            $stmtTrans->bind_param("idss", $user_id, $booking_cost, $createdAt, $desc);
            $stmtTrans->execute();
            $stmtTrans->close();
        }

        // 3.1) Ενημέρωση trial_used στο χρήστη ώστε να είναι 0 (trial ακύρωση)
        $stmtTrial = $conn->prepare("UPDATE users SET trial_used = 0 WHERE id = ?");
        $stmtTrial->bind_param("i", $user_id);
        $stmtTrial->execute();
        $stmtTrial->close();

        // 4) Έλεγχος αν υπάρχει κάποιος στη waiting_list
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
            $waiting = $resWait->fetch_assoc();
            $waiting_id    = intval($waiting['id']);
            $waiting_email = $waiting['email'];
            $waiting_token = $waiting['token'];

            $dt = new DateTime('now', new DateTimeZone('Europe/Athens'));
            $dt->modify('+30 minutes');
            $expiresAt = $dt->format('Y-m-d H:i:s');

            $stmtUpdateWait = $conn->prepare("
                UPDATE waiting_list
                   SET status = 'notified',
                       expires_at = ?
                 WHERE id = ?
            ");
            $stmtUpdateWait->bind_param("si", $expiresAt, $waiting_id);
            $stmtUpdateWait->execute();
            $stmtUpdateWait->close();

            sendWaitingNotification(
                $waiting_email,
                $session_id,
                $waiting_token,
                $session_date,
                $start_time
            );
        } else {
            $stmtUpdSess = $conn->prepare("
                UPDATE sessions
                   SET available_slots = available_slots + 1
                 WHERE id = ?
            ");
            $stmtUpdSess->bind_param("i", $session_id);
            $stmtUpdSess->execute();
            $stmtUpdSess->close();
        }
        $stmtWait->close();

        $conn->commit();

        header("Location: admin_panel.php?cancel_msg=success");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("❗ Σφάλμα ακύρωσης: " . $e->getMessage());
    }
}

die("❗ Μη έγκυρη πρόσβαση.");
$conn->close();
