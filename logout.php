<?php
session_start();

// Καθαρίζουμε όλα τα δεδομένα του session
$_SESSION = [];
session_unset();
session_destroy();

// Αν χρησιμοποιούνται cookies για sessions, τα διαγράφουμε επίσης
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000, 
        $params['path'], 
        $params['domain'], 
        $params['secure'], 
        $params['httponly']
    );
}

// Ανακατεύθυνση στη σελίδα login
header("Location: login.php");
exit;
?>

