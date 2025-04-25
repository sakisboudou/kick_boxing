<?php
session_start();
require_once 'includes/db_connect.php';

// Ενεργοποίηση σφαλμάτων (προαιρετικά)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ➤ Έλεγχος αν ο χρήστης είναι admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    die("⛔ Δεν έχετε πρόσβαση ή δεν είστε admin.");
}

/**
 * Μορφοποιεί μια ημερομηνία (yyyy-mm-dd ή datetime) σε dd/mm/yyyy.
 */
function format_date_dmy($dateTimeStr) {
    if (empty($dateTimeStr)) return '';
    $ts = strtotime($dateTimeStr);
    return date('d/m/Y', $ts);
}

// -------------------------------
// Επεξεργασία POST αιτημάτων
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Αν το action είναι για αλλαγή status
        if ($_POST['action'] === 'toggle_status' && isset($_POST['user_id'])) {
            $uid = intval($_POST['user_id']);
            $new_status = $_POST['new_status'] ?? 'inactive';
            $stmtSt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmtSt->bind_param("si", $new_status, $uid);
            $stmtSt->execute();
            $stmtSt->close();
            header("Location: customer_history.php");
            exit;
        }
        // Μπορείς να προσθέσεις κι άλλα POST actions εδώ αν χρειάζεται
    }
}

// -------------------------------
// Παίρνουμε παραμέτρους
// -------------------------------
$search_name = trim($_GET['search_name'] ?? '');
$user_id     = intval($_GET['user_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Ιστορικό Πελατών</title>
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
        h2, h3, h4 {
            text-align: center;
            margin-bottom: 20px;
        }
        table.table thead th {
            background-color: #f0eade;
        }
        /* Media Query για μικρές οθόνες */
        @media (max-width: 576px) {
            .container-main {
                padding: 10px;
                margin: 10px auto;
            }
            h2, h3, h4 {
                font-size: 1.2rem;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>

<div class="container-main">
    <!-- Επικεφαλίδα με κουμπιά -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="m-0">Ιστορικό Πελατών</h2>
        <div>
            <a href="admin_panel.php" class="btn btn-secondary me-2">Επιστροφή Admin</a>
            <a href="logout.php" class="btn-logout">Αποσύνδεση</a>
        </div>
    </div>

<?php
// ---------------------------------------------------
// (1) Αν δεν έχουμε user_id, εμφανίζουμε λίστα χρηστών βάσει search_name
// ---------------------------------------------------
if ($user_id === 0) {
    if (!empty($search_name)) {
        $sql = "SELECT id, full_name, email, signup_date, status
                FROM users
                WHERE full_name LIKE ?
                ORDER BY full_name ASC";
        $stmt = $conn->prepare($sql);
        $like_name = '%' . $search_name . '%';
        $stmt->bind_param("s", $like_name);
    } else {
        $sql = "SELECT id, full_name, email, signup_date, status
                FROM users
                ORDER BY full_name ASC";
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    ?>

    <!-- Φόρμα αναζήτησης -->
    <form method="GET" action="customer_history.php" class="mb-4">
        <div class="input-group">
            <input type="text" name="search_name" class="form-control" placeholder="Πληκτρολογήστε όνομα..."
                   value="<?php echo htmlspecialchars($search_name); ?>">
            <button type="submit" class="btn btn-primary">Αναζήτηση</button>
        </div>
    </form>

    <hr>
    <h4>Αποτελέσματα Αναζήτησης</h4>
    <?php if ($res->num_rows === 0): ?>
        <div class="alert alert-warning">Δεν βρέθηκαν χρήστες.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ονοματεπώνυμο</th>
                        <th>Email</th>
                        <th>Ημερομηνία Εγγραφής</th>
                        <th>Προβολή/Εναλλαγή Κατάστασης</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo format_date_dmy($row['signup_date']); ?></td>
                        <td>
                            <a href="customer_history.php?user_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                Προβολή Ιστορικού
                            </a>
                            <?php if ($row['status'] === 'active'): ?>
                                <form method="POST" action="customer_history.php" style="display:inline-block; margin-left:5px;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="new_status" value="inactive">
                                    <button type="submit" class="btn btn-sm btn-warning">Make Inactive</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="customer_history.php" style="display:inline-block; margin-left:5px;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="new_status" value="active">
                                    <button type="submit" class="btn btn-sm btn-success">Make Active</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div> <!-- .table-responsive -->
    <?php endif; ?>
    </div> <!-- container-main -->
</body>
</html>
<?php
    exit;
}

// ---------------------------------------------------
// (2) Έχουμε user_id => Προβολή Αναλυτικών Στοιχείων
// ---------------------------------------------------

// (α) Παίρνουμε βασικά στοιχεία χρήστη
$sqlUser = "
    SELECT id, full_name, email, phone, balance, status, signup_date,
           trial_used, trial_approved
    FROM users
    WHERE id = ?
";
$stmtU = $conn->prepare($sqlUser);
$stmtU->bind_param("i", $user_id);
$stmtU->execute();
$resU = $stmtU->get_result();
$userRow = $resU->fetch_assoc();
$stmtU->close();

if (!$userRow) {
    echo "<div class='alert alert-danger'>Ο χρήστης δεν βρέθηκε.</div>";
    exit;
}

// (β) Παίρνουμε τις συναλλαγές (transactions)
$sqlTrans = "
    SELECT id, amount, type, description, created_at
    FROM transactions
    WHERE user_id = ?
    ORDER BY created_at DESC
";
$stmtT = $conn->prepare($sqlTrans);
$stmtT->bind_param("i", $user_id);
$stmtT->execute();
$resT = $stmtT->get_result();
$stmtT->close();

// (γ) Παίρνουμε τις κρατήσεις (bookings)
$sqlBk = "
    SELECT b.id AS booking_id,
           b.created_at,
           b.booking_cost,
           b.status,
           s.date AS session_date,
           s.start_time
    FROM bookings b
    JOIN sessions s ON b.session_id = s.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
";
$stmtB = $conn->prepare($sqlBk);
$stmtB->bind_param("i", $user_id);
$stmtB->execute();
$resB = $stmtB->get_result();

$totalBookings = 0;     // active κρατήσεις
$totalCost     = 0.0;   // συνολικό ποσό πληρωμής
$rowsBookings  = [];
$lastTrainingDate = null;

while ($bkRow = $resB->fetch_assoc()) {
    $rowsBookings[] = $bkRow;
    if ($bkRow['status'] === 'active') {
        $totalBookings++;
        $totalCost += floatval($bkRow['booking_cost']);
        if ($lastTrainingDate === null || $bkRow['session_date'] > $lastTrainingDate) {
            $lastTrainingDate = $bkRow['session_date'];
        }
    }
}
$stmtB->close();

// (δ) Μέσος Όρος Προπονήσεων/Εβδομάδα
$signUpTs = strtotime($userRow['signup_date']);
$nowTs    = time();
$diffDays = ($nowTs - $signUpTs) / 86400.0;
if ($diffDays < 7) {
    $avgPerWeek = $totalBookings; 
} else {
    $weeks = $diffDays / 7.0;
    $avgPerWeek = $totalBookings / $weeks;
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Ιστορικό Πελατών - <?php echo htmlspecialchars($userRow['full_name']); ?></title>
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
        h2, h3, h4 {
            text-align: center;
            margin-bottom: 20px;
        }
        table.table thead th {
            background-color: #f0eade;
        }
        @media (max-width: 576px) {
            .container-main {
                padding: 10px;
                margin: 10px auto;
            }
            h2, h3, h4 {
                font-size: 1.2rem;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>

<div class="container-main">
    <!-- Επικεφαλίδα με κουμπιά -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="m-0">Ιστορικό Πελατών</h2>
        <div>
            <a href="admin_panel.php" class="btn btn-secondary me-2">Επιστροφή Admin</a>
            <a href="logout.php" class="btn-logout">Αποσύνδεση</a>
        </div>
    </div>

    <!-- Εμφάνιση βασικών στοιχείων χρήστη -->
    <div class="mb-4">
        <h4>Στοιχεία Χρήστη</h4>
        <ul>
            <li><strong>ID:</strong> <?php echo $userRow['id']; ?></li>
            <li><strong>Ονοματεπώνυμο:</strong> <?php echo htmlspecialchars($userRow['full_name']); ?></li>
            <li><strong>Email:</strong> <?php echo htmlspecialchars($userRow['email']); ?></li>
            <li><strong>Τηλέφωνο:</strong> <?php echo htmlspecialchars($userRow['phone'] ?? ''); ?></li>
            <li><strong>Υπόλοιπο:</strong> <?php echo number_format($userRow['balance'], 2); ?>€</li>
            <li><strong>Κατάσταση:</strong> <?php echo $userRow['status']; ?></li>
            <li><strong>Ημ/νία Εγγραφής:</strong> <?php echo format_date_dmy($userRow['signup_date']); ?></li>
            <li><strong>Trial Approved:</strong> <?php echo ($userRow['trial_approved'] == 1) ? 'ΝΑΙ' : 'ΟΧΙ'; ?></li>
            <li><strong>Trial Used:</strong> <?php echo ($userRow['trial_used'] == 1) ? 'ΝΑΙ' : 'ΟΧΙ'; ?></li>
        </ul>
    </div>

    <div class="mb-4">
        <h4>Στατιστικά Κρατήσεων</h4>
        <p>Συνολικές (active) Κρατήσεις: <strong><?php echo $totalBookings; ?></strong></p>
        <p>Συνολικό Πληρωμένο Κόστος: <strong><?php echo number_format($totalCost, 2); ?>€</strong></p>
        <p>Μέσος Όρος Προπονήσεων/Εβδομάδα: <strong><?php echo round($avgPerWeek, 2); ?></strong></p>
        <?php if ($lastTrainingDate): ?>
            <p>Τελευταία Προπόνηση: <strong><?php echo format_date_dmy($lastTrainingDate); ?></strong></p>
        <?php else: ?>
            <p>Τελευταία Προπόνηση: <em>Δεν υπάρχει (καμία active κράτηση)</em></p>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <h4>Πληρωμές / Συναλλαγές</h4>
        <?php if ($resT->num_rows === 0): ?>
            <div class="alert alert-info">Δεν υπάρχουν καταγεγραμμένες συναλλαγές.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Ημ/νία</th>
                            <th>Ποσό</th>
                            <th>Τύπος</th>
                            <th>Περιγραφή</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($tr = $resT->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo format_date_dmy($tr['created_at']); ?></td>
                            <td><?php echo number_format($tr['amount'], 2); ?>€</td>
                            <td><?php echo $tr['type']; ?></td>
                            <td><?php echo htmlspecialchars($tr['description']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div> <!-- .table-responsive -->
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <h4>Αναλυτική Λίστα Κρατήσεων</h4>
        <?php if (count($rowsBookings) === 0): ?>
            <div class="alert alert-info">Δεν υπάρχουν κρατήσεις.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Ημ/νία Κράτησης</th>
                            <th>Κατάσταση</th>
                            <th>Ημ/νία Μαθήματος</th>
                            <th>Ώρα</th>
                            <th>Κόστος</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rowsBookings as $bk): ?>
                        <tr>
                            <td><?php echo format_date_dmy($bk['created_at']); ?></td>
                            <td><?php echo $bk['status']; ?></td>
                            <td><?php echo format_date_dmy($bk['session_date']); ?></td>
                            <td><?php echo substr($bk['start_time'], 0, 5); ?></td>
                            <td><?php echo number_format($bk['booking_cost'], 2); ?>€</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div> <!-- .table-responsive -->
        <?php endif; ?>
    </div>

    <div class="text-center mb-4">
        <a href="customer_history.php" class="btn btn-secondary">Επιστροφή στην Αναζήτηση</a>
    </div>

</div><!-- .container-main -->
</body>
</html>
