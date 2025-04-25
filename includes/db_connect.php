<?php
$servername = "localhost";
$username   = "ihhsrcmy_personal_user";
$password   = "arf199vbn@";
$database   = "ihhsrcmy_personal_trainer_app";

// Δημιουργούμε τη σύνδεση με mysqli
$conn = new mysqli($servername, $username, $password, $database);

// Έλεγχος σφάλματος σύνδεσης
if ($conn->connect_error) {
    die("Σφάλμα σύνδεσης: " . $conn->connect_error);
}

// Ορισμός σετ χαρακτήρων (utf8)
$conn->set_charset("utf8");

// ✅ Ορισμός Timezone σε Europe/Athens για MySQL
$conn->query("SET time_zone = 'Europe/Athens'");

// Αν θες, έλεγχος αν μπήκε σωστά
/*
$result = $conn->query("SELECT NOW(), @@session.time_zone");
$row = $result->fetch_row();
echo "Τρέχουσα ώρα: " . $row[0] . " | Timezone: " . $row[1];
*/
?>
