<?php
session_start();

// Ρυθμίσεις για εμφάνιση σφαλμάτων (προσωρινά για development)
// (Αν δεν θέλετε να πετάει exceptions η MySQL, σχολιάστε την επόμενη γραμμή)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Ρύθμιση ζώνης ώρας (Ελλάδα)
date_default_timezone_set('Europe/Athens');

// Σύνδεση με τη βάση δεδομένων
require_once 'includes/db_connect.php';

// Μεταβλητές για μηνύματα
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Διαβάζουμε τις τιμές από τη φόρμα
    $full_name    = trim($_POST['full_name'] ?? '');
    $email        = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password_raw = $_POST['password'] ?? '';

    // 2. Ορίζουμε την τρέχουσα ημερομηνία ως signup_date (YYYY-MM-DD)
    $signup_date = date('Y-m-d');

    // 3. Έλεγχοι εγκυρότητας
    if (empty($full_name) || empty($email) || empty($password_raw)) {
        $error = "❗ Συμπληρώστε όλα τα πεδία!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❗ Το email δεν είναι έγκυρο.";
    } else {
        try {
            // ➤ Έλεγχος αν υπάρχει ήδη χρήστης με αυτό το email
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                // Αν βρούμε ήδη το email, βάζουμε το δικό μας μήνυμα
                $error = "❗ Το email χρησιμοποιείται ήδη!";
            } else {
                // ➤ Δημιουργούμε hash του κωδικού
                $hashed_password = password_hash($password_raw, PASSWORD_DEFAULT);

                // ➤ Default τιμές για το νέο χρήστη
                $balance    = 0.00;       // ξεκινά με 0€
                $status     = 'active';   // ενεργός λογαριασμός
                $is_admin   = 0;          // 0 => απλός χρήστης
                $trial_used = 0;          // 0 => δεν έχει χρησιμοποιήσει trial

                // ➤ Προετοιμάζουμε το INSERT
                $stmt = $conn->prepare("
                    INSERT INTO users
                        (full_name, email, hashed_password, balance, status, is_admin, signup_date, trial_used)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                if (!$stmt) {
                    throw new Exception("❗ Σφάλμα προετοιμασίας: " . $conn->error);
                }

                // Προσοχή να ΜΗΝ ξανακάνουμε execute διπλή φορά
                $stmt->bind_param(
                    "sssdsisi",
                    $full_name,      // s
                    $email,          // s
                    $hashed_password,// s
                    $balance,        // d
                    $status,         // s
                    $is_admin,       // i
                    $signup_date,    // s
                    $trial_used      // i
                );

                // ➤ Εκτέλεση
                $stmt->execute();

                // Αν φτάσουμε εδώ, σημαίνει ότι δεν πέταξε exception
                $success = "✅ Η εγγραφή ολοκληρώθηκε! <a href='login.php'>Σύνδεση εδώ</a>";

                $stmt->close();
            }

            $check_stmt->close();

        } catch (Exception $e) {
            // Ελέγχουμε αν το exception περιέχει "Duplicate entry"
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                // βάζουμε το δικό μας μήνυμα
                $error = "❗ Το email χρησιμοποιείται ήδη!";
            } else {
                // αλλιώς εμφανίζουμε γενικό μήνυμα
                $error = "❗ Σφάλμα κατά την εγγραφή. Δοκιμάστε ξανά ή επικοινωνήστε με τη διαχείριση.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Εγγραφή Χρήστη</title>
    <!-- Meta viewport για καλύτερη προσαρμογή σε κινητές συσκευές -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Λογότυπο */
        .header-logo {
            text-align: center;
            padding: 30px 0 10px 0;
        }

        .header-logo img {
            width: 120px;
            height: auto;
        }

        /* Κουμπί "Αρχική" */
        .home-button-container {
            text-align: center;
            margin: 20px 0;
        }

        .home-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #a47c48;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .home-button:hover {
            background-color: #8b6436;
            transform: translateY(-2px);
        }

        /* Κεντρικό container εγγραφής */
        .register-container {
            background: linear-gradient(135deg, #e8d7c0, #f4eade, #fffaf4);
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 2rem;
            color: #4a3b30;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #4a3b30;
            font-size: 1rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #a47c48;
            outline: none;
        }

        .btn-submit {
            background-color: #a47c48;
            color: #fff;
            border: none;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #8b6436;
            transform: translateY(-2px);
        }

        .alert {
            background-color: #ffcccc;
            color: #990000;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }

        .success {
            background-color: #e0f7e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }

        .login-link {
            margin-top: 20px;
            text-align: center;
            font-size: 0.9rem;
        }

        .login-link a {
            color: #a47c48;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Footer */
        footer {
            margin-top: auto;
            background: linear-gradient(135deg, #e8d7c0, #f4eade, #fffaf4);
            color: #5c483a;
            padding: 20px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
        }

        footer .footer-left,
        footer .footer-right {
            flex: 1;
        }

        footer .footer-center {
            text-align: center;
            flex: 1;
        }

        footer p,
        footer a {
            color: #5c483a;
            text-decoration: none;
        }

        footer a:hover {
            color: #a47c48;
        }

        /* Media Queries για κινητά */
        @media (max-width: 480px) {
            /* Μικρότερο μέγεθος λογότυπου */
            .header-logo img {
                width: 80px;
            }

            /* Λιγότερα padding στο container */
            .register-container {
                padding: 20px 15px;
            }

            h2 {
                font-size: 1.8rem;
                margin-bottom: 20px;
            }

            input[type="text"],
            input[type="email"],
            input[type="password"] {
                padding: 10px 12px;
                font-size: 0.95rem;
            }

            .btn-submit {
                padding: 12px;
                font-size: 1rem;
            }

            footer {
                flex-direction: column;
                text-align: center;
            }

            footer .footer-left,
            footer .footer-right {
                margin-bottom: 10px;
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <!-- LOGO -->
    <div class="header-logo">
        <img src="assets/images/logo_192x192.png" alt="Go's Studio">
    </div>
  
    <div class="home-button-container">
        <a href="index.php" class="home-button">Αρχική</a>
    </div>

    <!-- REGISTER FORM -->
    <div class="register-container">
        <h2>Εγγραφή Χρήστη</h2>

        <?php if (!empty($error)): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <label for="full_name">Ονοματεπώνυμο</label>
            <input type="text" id="full_name" name="full_name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Κωδικός</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn-submit">Εγγραφή</button>
        </form>

        <div class="login-link">
            Έχετε ήδη λογαριασμό; <a href="login.php">Σύνδεση εδώ</a>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="footer-left">
            <p>&copy; 2024 Go's Studio Pilates</p>
        </div>
        <div class="footer-center">
            <a href="#">Όροι Χρήσης</a>
        </div>
        <div class="footer-right">
            <a href="#">Επικοινωνία</a>
        </div>
    </footer>
</body>
</html>
