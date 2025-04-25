<?php
// services.php
session_start();
date_default_timezone_set('Europe/Athens');
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Υπηρεσίες - Go's Studio Pilates</title>
  <meta name="description" content="Υπηρεσίες του Go's Studio Pilates - Ανακαλύψτε τα προγράμματά μας για Pilates, Yoga, Personal Training και Prenatal Training.">
  <meta name="theme-color" content="#a47c48">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- CSS Αρχείο -->
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- Manifest για PWA -->
  <link rel="manifest" href="manifest.json">
  <!-- Εικονίδια -->
  <link rel="apple-touch-icon" href="assets/images/logo_192x192.png">
  <link rel="icon" type="image/png" sizes="192x192" href="assets/images/logo_192x192.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-B8ZH9DP5V6"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-B8ZH9DP5V6');
</script>

</head>
<body>

  <!-- Header -->
  <header class="main-header">
    <div class="logo">Go's Studio Pilates</div>
    <nav class="main-nav">
      <div id="hamburger" class="hamburger">
        <span></span>
        <span></span>
        <span></span>
      </div>
      <ul class="nav-links" id="menu">
        <li><a href="index.php">Αρχική</a></li>
        <li><a href="services.php" class="active">Υπηρεσίες</a></li>
        <li><a href="index.php#info">Πληροφορίες</a></li>
        <li><a href="index.php#contact">Επικοινωνία</a></li>
        <li><a href="login.php">Σύνδεση</a></li>
      </ul>
    </nav>
  </header>

  <!-- Κύριο Περιεχόμενο -->
  <main>
    <section class="services-page">
      <div class="container">
        <h1>Οι Υπηρεσίες μας</h1>
        
        <article id="pilates-mat">
          <h2>Pilates Mat</h2>
          <p>
            Το Pilates Mat πραγματοποιείται στο έδαφος πάνω σε ένα στρώμα (mat). Εστιάζει στη σωστή στάση του σώματος, στην ενδυνάμωση του κορμού και στη βελτίωση της ευλυγισίας. Οι ασκήσεις βασίζονται σε ήπιες, ελεγχόμενες κινήσεις, δίνοντας έμφαση στην αναπνοή και στον έλεγχο.
          </p>
        </article>
        
        <article id="pilates-reformer">
          <h2>Pilates Reformer</h2>
          <p>
            Το Pilates Reformer χρησιμοποιεί ειδικό εξοπλισμό με ελατήρια, ιμάντες και κινούμενη πλατφόρμα, προσφέροντας μεγαλύτερη ποικιλία ασκήσεων και δυνατότητα προσαρμογής της αντίστασης. Είναι εξαιρετικό για ευθυγράμμιση, ενδυνάμωση και αποκατάσταση τραυματισμών.
          </p>
        </article>
        
        <article id="group-classes">
          <h2>Ομαδικά Μαθήματα</h2>
          <p>
            Σε ομαδικά μαθήματα, μια ομάδα ατόμων γυμνάζεται μαζί υπό την καθοδήγηση εκπαιδευτή/τριας, προσφέροντας αίσθηση κοινότητας και ομαδικό πνεύμα. Η δομή και η ένταση των μαθημάτων προσαρμόζονται στο επίπεδο των συμμετεχόντων, κάνοντας τα διασκεδαστικά και ενθαρρυντικά.
          </p>
        </article>
        
        <article id="personal-training">
          <h2>Personal Training</h2>
          <p>
            Το Personal Training προσφέρει ατομικές συνεδρίες με προσωπικό γυμναστή/τρια, με πρόγραμμα προσαρμοσμένο στις ανάγκες και τους στόχους του ασκούμενου. Παρέχεται στενή παρακολούθηση της τεχνικής, για γρήγορη πρόοδο και άμεση ανατροφοδότηση.
          </p>
        </article>
        
        <article id="yoga-sessions">
          <h2>Yoga Sessions</h2>
          <p>
            Η Yoga συνδυάζει σωματικές στάσεις, τεχνικές αναπνοής και διαλογισμό για βελτίωση της ευλυγισίας, της ισορροπίας και της μυϊκής ενδυνάμωσης, ενώ μειώνει το άγχος. Υπάρχουν διάφορα στυλ Yoga, όπως Hatha, Vinyasa και Ashtanga.
          </p>
        </article>
        
        <article id="prenatal-training">
          <h2>Prenatal Training</h2>
          <p>
            Το Prenatal Training αφορά ειδικά σχεδιασμένη άσκηση για γυναίκες κατά τη διάρκεια της εγκυμοσύνης, εστιάζοντας στην ήπια ενδυνάμωση, τη διατήρηση της ευλυγισίας και τη βελτίωση της στάσης του σώματος, ενώ προσφέρει τεχνικές χαλάρωσης και αναπνοής για την προετοιμασία για τον τοκετό.
          </p>
        </article>
        
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer>
    <div class="footer-location">
      <h5>Που θα μας βρείτε</h5>
      <p>Διγενή 9, Πολίχνη</p>
      <p>Θεσσαλονίκη</p>
      <p>Πατήστε για να μας βρείτε στον χάρτη:</p>
      <p>
        <a href="https://maps.app.goo.gl/VdxZJUxKWK4RE11b8" target="_blank" rel="noopener">
          <img src="assets/images/new_google_map_icon.png" alt="Χάρτης Google" width="80" height="80" class="map-icon">
        </a>
      </p>
    </div>
    <div class="footer-logo">
      <p>Σχεδιασμένο με αγάπη από τον Sakis B</p>
      <img src="assets/images/footer-logo.png" alt="Σακης Μπουδουρίδης">
      <p>© 2025 Go's Studio. Όλα τα δικαιώματα κατοχυρωμένα.</p>
      <div class="social-icons">
        <a href="https://www.instagram.com/gos_studiopilates/" target="_blank" rel="noopener">
          <i class="fab fa-instagram"></i>
        </a>
        <a href="https://www.facebook.com/profile.php?id=61560202374505&locale=el_GR" target="_blank">
          <i class="fab fa-facebook"></i>
        </a>
      </div>
    </div>
    <div class="footer-contact">
      <p>Επικοινωνία</p>
      <p>Τηλέφωνο: <a href="tel:+302314072090">+30 2314 072090</a></p>
      <p>
        Email: <a href="mailto:info@gosstudio-pilates.com">info@gosstudio-pilates.com</a>
      </p>
      <div style="margin-top: 10px;"></div>
      <a href="privacy.html">Πολιτική Απορρήτου</a>
      <a href="terms.html">Όροι Χρήσης</a>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="assets/js/responsive-menu.js"></script>
</body>
</html>
