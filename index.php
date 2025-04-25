<?php
// index.php
session_start();
date_default_timezone_set('Europe/Athens');
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Τίτλος σελίδας -->
  <title>Go's Studio Pilates</title>

  <!-- Περιγραφή για SEO -->
  <meta name="description" content="Go's Studio Pilates - Ανακαλύψτε την ευεξία με τη μέθοδο Pilates Reformer στη Θεσσαλονίκη.">
  
  <!-- Open Graph Tags για Social Media Preview -->
  <meta property="og:title" content="Go's Studio Pilates">
  <meta property="og:description" content="Ανακάλυψε την ευεξία με τη μέθοδο Pilates Reformer στη Θεσσαλονίκη.">
  <meta property="og:image" content="https://gosstudio.gr/assets/images/logo_192x192.png">
  <meta property="og:url" content="https://gosstudio.gr">
  <meta property="og:type" content="website">
  
  <!-- Χρώμα για το theme (Chrome & Android toolbar) -->
  <meta name="theme-color" content="#a47c48">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- CSS Αρχείο -->
  <link rel="stylesheet" href="assets/css/style.css">
  
  <!-- Προσθήκη εσωτερικού CSS για το header (αν δεν το έχεις ήδη στο style.css) -->
  <style>
    /* Container για το λογότυπο και το κουμπί διαμοιρασμού */
    .logo-container {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    /* Στυλ για το share κουμπί */
    .share-icon-button {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 20px;
      color: #333;
    }
    /* Αν προτιμάς hover effect */
    .share-icon-button:hover {
      color: #a47c48;
    }
  </style>

  <!-- Manifest για PWA -->
  <link rel="manifest" href="manifest.json">

  <!-- Εικονίδιο για Apple Touch -->
  <link rel="apple-touch-icon" href="assets/images/logo_192x192.png">

  <!-- Favicon (μικρό εικονίδιο για desktop browsers) -->
  <link rel="icon" type="image/png" sizes="192x192" href="assets/images/logo_192x192.png">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

  <!-- 
    Σημείωση: Αφαιρέσαμε το Google Analytics script από εδώ για να το φορτώσουμε
    δυναμικά στο κάτω μέρος, μόνο αν ο χρήστης συναινέσει.
  -->
</head>

<body>

  <!-- Header -->
  <header class="main-header">
    <!-- Container που συγκεντρώνει το λογότυπο και το share icon -->
    <div class="logo-container">
      <div class="logo">Go's Studio Pilates</div>
      <!-- Κουμπί με εικονίδιο διαμοιρασμού -->
      <button id="shareBtn" class="share-icon-button" title="Μοιράσου τη σελίδα">
        <i class="fas fa-share-alt"></i>
      </button>
    </div>
  
    <nav class="main-nav">
      <div id="hamburger" class="hamburger">
        <span></span>
        <span></span>
        <span></span>
      </div>
      <ul class="nav-links" id="menu">
        <li><a href="#about">Σχετικά</a></li>
        <li><a href="services.php">Υπηρεσίες</a></li>
        <li><a href="#info">Πληροφορίες</a></li>
        <li><a href="#contact">Επικοινωνία</a></li>
        <li><a href="login.php">Σύνδεση</a></li>
      </ul>
    </nav>
  </header>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <h1>Go's Studio Pilates</h1>
      <p>Muscle Empowerment • Mind Alignment</p>
      <a href="login.php" class="cta-button">Κλείστε Ραντεβού</a>
    </div>
  </section>

  <!-- About Section -->
  <section id="about" class="about-section">
    <div class="about-container">
      <div class="about-text">
        <h2>Go's Studio Pilates</h2>
        <p>Το 2018, το όραμα του Γεώργιου Χριστάκογλου και της Όλγας Ζερβού μετατράπηκε σε πραγματικότητα με τη δημιουργία του GO’s Studio, ενός χώρου αφιερωμένου στο Pilates Reformer και τη Yoga.</p>
        <p>Στο στούντιο, προσφέρουμε εξατομικευμένα προγράμματα εκγύμνασης με έμφαση στην ενδυνάμωση του σώματος και την ευεξία του πνεύματος.</p>
        
        <!-- Οι ιδιοκτήτες σε ίση παρουσίαση -->
        <div class="owners-wrapper">
          <div class="owners-container">
            <div class="owner george">
              <h3>Όλγα Ζερβού</h3>
              <p>Pilates &amp; Yoga Trainer<br>Graduate of TEFAA &amp; MSc in Medicine</p>
            </div>
            <div class="owner olga">
              <h3>Γεώργιος Χριστάκογλου</h3>
              <p>Physiotherapist &amp; Referee</p>
            </div>
          </div>
        </div>
        
        <!-- Εικόνα -->
        <div class="about-image">
          <img src="assets/images/gos-studio-14.jpg" alt="Gosstudio Owners">
        </div>
      </div>
    </div>
  </section>

  <section id="info" class="info">
    <div class="container">
      <h2>Πληροφορίες &amp; Οφέλη</h2>
      <p>
        <strong>Το Pilates</strong> αποτελεί μια ολοκληρωμένη μέθοδο ενδυνάμωσης του σώματος και βελτίωσης της διάθεσης. Στο GO’s Studio, η εξειδικευμένη ομάδα μας διασφαλίζει ότι κάθε άσκηση προσαρμόζεται στις δικές σας ανάγκες, προσφέροντας μια πραγματικά εξατομικευμένη εμπειρία.
      </p>
      <p>
        Επιπλέον, εμπλουτίζουμε το πρόγραμμά μας με <strong>μαθήματα Yoga</strong>, χαρίζοντάς σας την απόλυτη εμπειρία χαλάρωσης και εσωτερικής ισορροπίας. Συνδυάζοντας Pilates και Yoga, έχετε τη δυνατότητα να ανακαλύψετε τη βέλτιστη σύνθεση σωματικής και πνευματικής ευεξίας.
      </p>
      <p>
        Για τις μέλλουσες μητέρες, οι πιστοποιημένοι <strong>Prenatal Trainers</strong> του GO’s Studio έχουν σχεδιάσει ασφαλή και αποτελεσματικά προγράμματα άσκησης που βοηθούν στην:
      </p>
      <ul>
        <li>Ενδυνάμωση του πυελικού εδάφους και εκμάθηση θέσεων τοκετού</li>
        <li>Ανακούφιση από πόνους στην πλάτη και τις αρθρώσεις</li>
        <li>Βελτίωση της ποιότητας ύπνου και μείωση του άγχους</li>
        <li>Ενίσχυση της συνολικής σωματικής και ψυχικής ευεξίας</li>
      </ul>

      <h3>Pilates Reformer για Όλους: Εξατομικευμένη Προπόνηση σε Mini Groups</h3>
      <p>
        Το GO’s Studio είναι αφιερωμένο στο <strong>Pilates Reformer</strong>, προσφέροντας δύο σύγχρονες αίθουσες, καθεμία εξοπλισμένη με τέσσερα κρεβάτια Reformer. Οι προπονήσεις σε mini groups δημιουργούν ένα ιδανικό περιβάλλον, όπου κάθε πρόγραμμα προσαρμόζεται απόλυτα στο επίπεδο και τους στόχους σας, εξασφαλίζοντας ασφάλεια και αποτελεσματικότητα.
      </p>
      <p>
        Η φιλοσοφία μας βασίζεται στην εξατομίκευση: είτε είστε αρχάριοι, είτε έμπειροι αθλούμενοι, στο GO’s Studio θα βρείτε την κατάλληλη καθοδήγηση. Δεν υπάρχει “τυπικός” ασκούμενος, αφού κάθε σώμα έχει τις δικές του ανάγκες και δυνατότητες.
      </p>

      <h3>Γιατί να επιλέξετε Pilates Reformer;</h3>
      <p>
        Το Pilates Reformer βελτιώνει τη στάση του σώματος, την ισορροπία και την ευλυγισία, μειώνοντας παράλληλα τον κίνδυνο τραυματισμών. Μέσα από στοχευμένες ασκήσεις, οι μύες επανέρχονται στους σωστούς ρόλους τους, εξασφαλίζοντας σταθερότητα και ανθεκτικότητα.
      </p>
      <p>
        Παράλληλα, η συστηματική εξάσκηση απελευθερώνει ενδορφίνες, καταπολεμώντας το άγχος και βελτιώνοντας τη διάθεση. Στο GO’s Studio, θα βρείτε ένα φιλόξενο περιβάλλον και έμπειρους εκπαιδευτές που θα σας στηρίξουν σε κάθε βήμα. Κλείστε την πρώτη σας συνεδρία και ανακαλύψτε μια πιο υγιή, χαρούμενη εκδοχή του εαυτού σας.
      </p>
    </div>
  </section>

  <!-- Carousel Section -->
  <section class="slideshow-container">
    <div class="slide fade">
      <img src="assets/images/gos-studio-12.jpg" alt="Studio Photo 12">
    </div>
    <div class="slide fade">
      <img src="assets/images/gos-studio-13.jpg" alt="Studio Photo 13">
    </div>
    <div class="slide fade">
      <img src="assets/images/gos-studio-14.jpg" alt="Studio Photo 14">
    </div>
    <div class="slide fade">
      <img src="assets/images/gos-studio-16.jpg" alt="Studio Photo 16">
    </div>
    <div class="slide fade">
      <img src="assets/images/gos-studio-6.jpg" alt="Studio Photo 6">
    </div>
    <!-- Κουμπιά πλοήγησης (προηγούμενη/επόμενη) -->
    <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
    <a class="next" onclick="plusSlides(1)">&#10095;</a>
  </section>

  <!-- Δείκτες (dots) για άμεση μετάβαση σε slide -->
  <div class="dots-container" style="text-align:center">
    <span class="dot" onclick="currentSlide(1)"></span>
    <span class="dot" onclick="currentSlide(2)"></span>
    <span class="dot" onclick="currentSlide(3)"></span>
    <span class="dot" onclick="currentSlide(4)"></span>
    <span class="dot" onclick="currentSlide(5)"></span>
  </div>

  <!-- Contact Section -->
  <section id="contact" class="contact">
    <div class="container">
      <h2>Επικοινωνία</h2>
      <!-- Contact Form -->
      <form action="contact_process.php" method="post" class="contact-form">
        <div class="form-group">
          <label for="name">Ονοματεπώνυμο:</label>
          <input type="text" id="name" name="name" placeholder="Το όνομά σας" required>
        </div>
        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" id="email" name="email" placeholder="Το email σας" required>
        </div>
        <div class="form-group">
          <label for="subject">Θέμα:</label>
          <input type="text" id="subject" name="subject" placeholder="Το θέμα του μηνύματος" required>
        </div>
        <div class="form-group">
          <label for="message">Μήνυμα:</label>
          <textarea id="message" name="message" placeholder="Το μήνυμά σας" required></textarea>
        </div>
        <button type="submit" class="btn">Αποστολή</button>
      </form>
    </div>
  </section>

  <footer>
    <div class="footer-location">
      <h5 data-el="footer-location">Που θα μας βρείτε</h5>
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
      <img src="assets/images/footer-logo.png" alt="Σακης Μπουδουρίδης" />
      <p>© 2025 Go's Studio. Όλα τα δικαιώματα κατοχυρωμένα.</p>
      <div class="social-icons">
        <a href="https://www.instagram.com/gos_studiopilates/" target="_blank" rel="noopener">
          <i class="fab fa-instagram"></i>
        </a>
        <a href="https://www.facebook.com/profile.php?id=61560202374505&locale=el_GR" target="_blank" rel="noopener">
          <i class="fab fa-facebook"></i>
        </a>
      </div>
    </div>

    <div class="footer-contact">
      <p>Επικοινωνία</p>
      <p>Τηλέφωνο: <a href="tel:+302314072090">+30 2314 072090</a></p>
      <p>
        email: 
        <a href="mailto:info@gosstudio.gr">info@gosstudio.gr</a>
      </p>
      <div style="margin-top: 10px;"></div>
      <a href="privacy.html">Πολιτική Απορρήτου</a>
      <a href="terms.html">Όροι Χρήσης</a>
    </div>
  </footer>
  
  <!-- Εγγραφή Service Worker (αν θες PWA) -->
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('service-worker.js')
        .then(function(reg) {
          console.log('Service worker registered ✅', reg);
        }).catch(function(error) {
          console.log('Service worker error ❌', error);
        });
    }
  </script>

  <!-- Script για μενού responsive & Carousel -->
  <script src="assets/js/responsive-menu.js"></script>
  <script src="assets/js/carousel.js"></script>

  <!-- Banner για αποδοχή cookies (Google Analytics) -->
  <div id="cookieBanner" style="display:none; position: fixed; bottom: 0; width: 100%; padding: 20px; background: #f5f5f5; text-align: center;">
    <p>
      Ο ιστότοπός μας χρησιμοποιεί Google Analytics για στατιστικούς σκοπούς. 
      <a href="privacy.html" target="_blank">Πολιτική Απορρήτου</a>.
    </p>
    <button id="acceptCookiesBtn">Αποδοχή</button>
  </div>

  <script>
    // Έλεγχος αν υπάρχει ήδη cookie consent
    function checkCookieConsent() {
      const cookies = document.cookie.split(';');
      const hasCookieConsent = cookies.some((item) => item.trim().startsWith('cookieAccepted='));
      if (!hasCookieConsent) {
        document.getElementById('cookieBanner').style.display = 'block';
      } else {
        loadGoogleAnalytics();
      }
    }
    // Όταν ο χρήστης πατά "Αποδοχή"
    function acceptCookies() {
      document.cookie = "cookieAccepted=true; path=/; max-age=31536000";
      document.getElementById('cookieBanner').style.display = 'none';
      loadGoogleAnalytics();
    }
    // Φόρτωση του GA script δυναμικά
    function loadGoogleAnalytics() {
      const gaScript = document.createElement('script');
      gaScript.async = true;
      gaScript.src = "https://www.googletagmanager.com/gtag/js?id=G-B8ZH9DP5V6";
      document.head.appendChild(gaScript);
      gaScript.onload = function() {
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'G-B8ZH9DP5V6', { 'anonymize_ip': true });
      };
    }
    window.addEventListener('load', checkCookieConsent);
    document.getElementById('acceptCookiesBtn').addEventListener('click', acceptCookies);
  </script>
  
  <!-- Ενσωματωμένος κώδικας για το Web Share API -->
  <script>
    document.getElementById('shareBtn').addEventListener('click', async () => {
      if (navigator.share) {
        try {
          await navigator.share({
            title: "Go's Studio Pilates",
            text: "Ανακάλυψε την ευεξία με τη μέθοδο Pilates Reformer στη Θεσσαλονίκη.",
            url: "https://gosstudio.gr"
          });
          console.log('Η κοινή χρήση ολοκληρώθηκε με επιτυχία.');
        } catch (error) {
          console.error('Σφάλμα στην κοινή χρήση:', error);
        }
      } else {
        alert("Η λειτουργία κοινής χρήσης δεν υποστηρίζεται σε αυτήν τη συσκευή.");
      }
    });
  </script>

</body>
</html>
