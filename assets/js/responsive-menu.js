document.addEventListener("DOMContentLoaded", function () {
  const hamburger = document.getElementById('hamburger');
  const menu = document.getElementById('menu');

  // Όταν κλικάρουμε το hamburger
  hamburger.addEventListener('click', function (e) {
    // Σταματά την «διάχυση» του κλικ ώστε να μη θεωρηθεί κλικ εκτός μενού
    e.stopPropagation();
    menu.classList.toggle('active');
  });

  // Όταν κλικάρουμε οπουδήποτε στο document
  document.addEventListener('click', function (e) {
    // Ελέγχουμε αν το κλικ έγινε εκτός του hamburger ή του μενού
    if (!hamburger.contains(e.target) && !menu.contains(e.target)) {
      menu.classList.remove('active');
    }
  });
});
