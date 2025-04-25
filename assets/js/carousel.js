let slideIndex = 1;
showSlides(slideIndex);

// Έλεγχος για "Προηγούμενο" / "Επόμενο"
function plusSlides(n) {
  showSlides(slideIndex += n);
}

// Άμεση μετάβαση σε συγκεκριμένο slide
function currentSlide(n) {
  showSlides(slideIndex = n);
}

function showSlides(n) {
  let i;
  let slides = document.getElementsByClassName("slide");
  let dots = document.getElementsByClassName("dot");
  
  if (n > slides.length) {
    slideIndex = 1;
  }
  if (n < 1) {
    slideIndex = slides.length;
  }

  // Απόκρυψη όλων των slides
  for (i = 0; i < slides.length; i++) {
    slides[i].style.display = "none";
  }
  // Απενεργοποίηση όλων των dots
  for (i = 0; i < dots.length; i++) {
    dots[i].className = dots[i].className.replace(" active", "");
  }

  // Εμφάνιση του τρέχοντος slide & ενεργοποίηση του αντίστοιχου dot
  slides[slideIndex - 1].style.display = "block";
  dots[slideIndex - 1].className += " active";
}

// Αυτόματη αλλαγή slide κάθε 3 δευτερόλεπτα
setInterval(() => {
  plusSlides(1);
}, 3000);

