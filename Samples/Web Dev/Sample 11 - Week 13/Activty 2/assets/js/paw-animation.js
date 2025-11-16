// ===== FLOATING PAW PRINTS ANIMATION =====
// This script creates and animates floating paw prints in the background

(function() {
  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPawAnimation);
  } else {
    initPawAnimation();
  }

  function initPawAnimation() {
    // Create paw container if it doesn't exist
    let pawContainer = document.getElementById('paw-container');
    if (!pawContainer) {
      pawContainer = document.createElement('div');
      pawContainer.id = 'paw-container';
      document.body.insertBefore(pawContainer, document.body.firstChild);
    }

    // Configuration
    const pawImage = window.PAW_IMAGE_PATH || '/assets/image/paw-print.png';
    const pawCount = 50; // Reduced for performance on all pages
    const paws = [];

    // Create paw prints
    for (let i = 0; i < pawCount; i++) {
      const paw = document.createElement('img');
      paw.src = pawImage;
      paw.classList.add('paw');
      paw.style.top = Math.random() * 100 + 'vh';
      paw.style.left = Math.random() * 100 + 'vw';
      
      // Random size (mix of small and large paws)
      const size = 20 + Math.random() * 70;
      paw.style.width = size + 'px';

      // Random opacity and animation duration
      paw.style.opacity = 0.04 + Math.random() * 0.08;
      paw.style.animationDuration = (10 + Math.random() * 12) + 's';
      paw.style.animationDelay = Math.random() * 5 + 's';
      
      // Error handling for image load
      paw.onerror = function() {
        this.style.display = 'none';
      };
      
      pawContainer.appendChild(paw);
      
      paws.push({
        el: paw,
        speedX: (Math.random() - 0.5) * 0.08,
        speedY: (Math.random() - 0.5) * 0.08
      });
    }

    // Gentle, slow random drifting movement
    let animationFrame;
    function animatePaws() {
      paws.forEach(p => {
        let x = parseFloat(p.el.style.left);
        let y = parseFloat(p.el.style.top);
        
        // Move slightly and wrap around
        x += p.speedX;
        y += p.speedY;
        
        if (x < -5) x = 105;
        if (x > 105) x = -5;
        if (y < -5) y = 105;
        if (y > 105) y = -5;
        
        p.el.style.left = x + 'vw';
        p.el.style.top = y + 'vh';
      });
      
      animationFrame = requestAnimationFrame(animatePaws);
    }

    animatePaws();

    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
      if (animationFrame) {
        cancelAnimationFrame(animationFrame);
      }
    });
  }
})();