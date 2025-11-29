

document.addEventListener('DOMContentLoaded', function() {

  setupPasswordToggleModern('togglePassword', 'password');


  const carousel = document.getElementById('loginCarousel');
  if (carousel) {
    initializeModernCarousel(carousel);
  }


  setupFormValidation();
});


function setupPasswordToggleModern(toggleButtonId, passwordInputId) {
  const toggle = document.getElementById(toggleButtonId);
  const pwd = document.getElementById(passwordInputId);

  if (!toggle || !pwd) return;

  toggle.addEventListener('click', function(e) {
    e.preventDefault();
    const isPassword = pwd.getAttribute('type') === 'password';
    pwd.setAttribute('type', isPassword ? 'text' : 'password');


    const icon = toggle.querySelector('i');
    if (icon) {
      icon.classList.toggle('bi-eye-fill');
      icon.classList.toggle('bi-eye-slash-fill');

      icon.style.color = 'var(--primary)';
      icon.style.fontSize = '1.1rem';
    }
  });
}


function initializeModernCarousel(carousel) {


  const indicators = carousel.querySelectorAll('.indicator');

  indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', function() {
      const bsCarousel = bootstrap.Carousel.getInstance(carousel);
      if (bsCarousel) {
        bsCarousel.to(index);
      }
    });
  });


  carousel.addEventListener('slid.bs.carousel', function(e) {
    const indicators = carousel.querySelectorAll('.indicator');
    indicators.forEach((ind, i) => {
      ind.classList.toggle('active', i === e.to);
    });
  });
}


function setupFormValidation() {
  const loginForm = document.getElementById('loginForm');
  if (!loginForm) return;

  loginForm.addEventListener('submit', function(e) {
    if (!loginForm.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();
    }
    loginForm.classList.add('was-validated');
  }, false);
}
