<?php
session_start();
require_once 'includes/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Παρακαλώ συμπλήρωσε όλα τα πεδία.";
    } else {
        // Προσοχή στη στήλη hashed_password αντί για password
        $stmt = $conn->prepare("
            SELECT id, full_name, hashed_password, balance, status, is_admin 
            FROM users 
            WHERE email = ?
        ");
        
        if (!$stmt) {
            die("Σφάλμα στο prepare: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Έλεγχος αν ο λογαριασμός είναι ενεργός
            if ($user['status'] !== 'active') {
                $error = "Ο λογαριασμός σας δεν είναι ενεργός. Επικοινωνήστε με τη διαχείριση.";
            }
            // Έλεγχος κωδικού
            elseif (password_verify($password, $user['hashed_password'])) {
                // Ενδεχομένως: session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['balance']   = $user['balance'];
                $_SESSION['status']    = $user['status'];
                $_SESSION['is_admin']  = $user['is_admin'];

                if ($user['is_admin'] == 1) {
                    header("Location: admin_panel.php");
                    exit;
                } else {
                    header("Location: booking.php");
                    exit;
                }
            } else {
                $error = "Λάθος κωδικός. Προσπάθησε ξανά.";
            }
        } else {
            $error = "Ο χρήστης δεν βρέθηκε με αυτό το email.";
        }

        $stmt->close();
    }
}
?>



<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Go's Studio Pilates - Σύνδεση</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-B8ZH9DP5V6"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-B8ZH9DP5V6');
</script>


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

    .header-logo {
      text-align: center;
      padding: 30px 0 10px 0;
    }

    .header-logo img {
      width: 120px;
      height: auto;
    }

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

    .login-container {
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

    input[type="email"],
    input[type="password"] {
      padding: 12px 15px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.3s ease;
    }

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

    .register-link {
      margin-top: 20px;
      text-align: center;
      font-size: 0.9rem;
    }

    .register-link a {
      color: #a47c48;
      text-decoration: none;
      font-weight: 500;
    }

    .register-link a:hover {
      text-decoration: underline;
    }

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

    @media (max-width: 480px) {
      .login-container {
        padding: 30px 20px;
      }

      h2 {
        font-size: 1.8rem;
      }

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
        text-align: center;
        margin-bottom: 10px;
      }
    }
  </style>
</head>

<body>

  <!-- LOGO -->
  <div class="header-logo">
    <img src="assets/images/logo_192x192.png" alt="Go's Studio Logo">
  </div>
  
  <div class="home-button-container">
     <a href="index.php" class="home-button">Αρχική Σελίδα</a>
  </div>

  <!-- LOGIN BOX -->
  <div class="login-container">
    <h2>Σύνδεση Χρήστη</h2>

    <?php if (!empty($error)): ?>
      <div class="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <label for="email">Email:</label>
      <input type="email" name="email" id="email" required autofocus>

      <label for="password">Κωδικός:</label>
      <input type="password" name="password" id="password" required>

      <button type="submit" class="btn-submit">Σύνδεση</button>
    </form>

    <div class="register-link">
      Δεν έχετε λογαριασμό; <a href="register.php">Εγγραφείτε εδώ</a>
    </div>
  </div>

  <!-- FOOTER -->
  <footer>
    <div class="footer-left">
      <p>&copy; 2024 Go's Studio Pilates</p>
    </div>
    <div class="footer-center">
      <p>Muscle Empowerment · Mind Alignment</p>
    </div>
    <div class="footer-right">
      <p>Θεσσαλονίκη, Ελλάδα</p>
    </div>
  </footer>

</body>
</html>