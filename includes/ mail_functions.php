<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Βεβαιωθείτε ότι έχετε εγκαταστήσει το PHPMailer μέσω Composer και ότι συμπεριλαμβάνεται το autoload:
require 'vendor/autoload.php';

function sendWaitingNotification($email, $session_id, $token) {
    $mail = new PHPMailer(true);
    try {
        // Ρυθμίσεις διακομιστή (SMTP)
        // $mail->SMTPDebug = 2; // Ενεργοποιήστε για debugging αν χρειαστεί
        $mail->isSMTP();
        $mail->Host       = 'mail.gosstudio.gr';           // Ο SMTP server σας
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@gosstudio.gr';           // Το email χρήστη του λογαριασμού αποστολέα
        $mail->Password   = 'Arf199vbn@arf';          // Εισάγετε εδώ το password σας
        // Αν χρησιμοποιείτε SSL (SMTPS) σε θύρα 465:
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;     // Για SMTPS (SSL). Αν προτιμάτε TLS, αλλάξτε σε ENCRYPTION_STARTTLS και τη θύρα σε 587.
        $mail->Port       = 465;                             // Θύρα SMTPS (ή 587 για TLS)

        // Παραλήπτες
        $mail->setFrom('info@gosstudio.gr', "Go's Studio Pilates");
        $mail->addAddress($email);

        // Περιεχόμενο email
        $mail->isHTML(true);
        $mail->Subject = 'Ειδοποίηση Εγγραφής στη Λίστα Αναμονής';
        $confirmationLink = "https://gosstudio.gr/confirm_waiting.php?token=" . urlencode($token);
        $mail->Body    = "Γεια σας,<br><br>Έχετε εγγραφεί στη λίστα αναμονής για το μάθημα (session ID: {$session_id}).<br>
                          Πατήστε <a href='{$confirmationLink}'>εδώ</a> για να επιβεβαιώσετε την κράτησή σας.<br><br>
                          Σε περίπτωση που δεν επιβεβαιώσετε εντός 30 λεπτών, η εγγραφή σας θα λήξει.<br><br>
                          Ευχαριστούμε,<br>Go's Studio Pilates";
        $mail->AltBody = "Γεια σας,\n\nΈχετε εγγραφεί στη λίστα αναμονής για το μάθημα (session ID: {$session_id}).\n
                          Επισκεφθείτε: " . $confirmationLink . "\n\nΣε περίπτωση που δεν επιβεβαιώσετε εντός 30 λεπτών, η εγγραφή σας θα λήξει.\n\n
                          Ευχαριστούμε,\nGo's Studio Pilates";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
