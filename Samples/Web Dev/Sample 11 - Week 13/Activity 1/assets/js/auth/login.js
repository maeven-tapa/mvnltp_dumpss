/**
 * Login Page Functionality
 */

document.addEventListener('DOMContentLoaded', function() {
  // Setup password toggle with icon switching
  setupPasswordToggleModern('togglePassword', 'password');

  // Initialize Bootstrap carousel if it exists
  const carousel = document.getElementById('loginCarousel');
  if (carousel) {
    initializeModernCarousel(carousel);
  }

  // Setup form validation
  setupFormValidation();
});

/**
 * Setup password toggle with modern icon switching
 */
function setupPasswordToggleModern(toggleButtonId, passwordInputId) {
  const toggle = document.getElementById(toggleButtonId);
  const pwd = document.getElementById(passwordInputId);

  if (!toggle || !pwd) return;

  toggle.addEventListener('click', function(e) {
    e.preventDefault();
    const isPassword = pwd.getAttribute('type') === 'password';
    pwd.setAttribute('type', isPassword ? 'text' : 'password');
    
    // Update icon
    const icon = toggle.querySelector('i');
    if (icon) {
      icon.classList.toggle('bi-eye-fill');
      icon.classList.toggle('bi-eye-slash-fill');
      // Ensure color stays with primary color
      icon.style.color = 'var(--primary)';
      icon.style.fontSize = '1.1rem';
    }
  });
}

/**
 * Initialize modern carousel with indicators
 */
function initializeModernCarousel(carousel) {
  // Bootstrap carousel is automatically initialized with data-bs-ride="carousel"
  // But we can add custom indicator click handlers
  const indicators = carousel.querySelectorAll('.indicator');
  
  indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', function() {
      const bsCarousel = bootstrap.Carousel.getInstance(carousel);
      if (bsCarousel) {
        bsCarousel.to(index);
      }
    });
  });

  // Update indicators when carousel changes
  carousel.addEventListener('slid.bs.carousel', function(e) {
    const indicators = carousel.querySelectorAll('.indicator');
    indicators.forEach((ind, i) => {
      ind.classList.toggle('active', i === e.to);
    });
  });
}

/**
 * Setup form validation
 */
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
