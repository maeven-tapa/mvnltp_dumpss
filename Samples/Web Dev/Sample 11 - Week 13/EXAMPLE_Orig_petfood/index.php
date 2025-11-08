<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
<base href="/Example_Orig_petfood/">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pet Food Place — Home</title>
<style>
:root {
  --brown: #8B4513;
  --brown-dark: #6e3510;
  --cream-light: #f9f4e6;
  --cream: #f1d1a2;
  --text-dark: #3b240b;
  --text-soft: #6d4c1d;
}

/* Reset & Base */
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: 'Segoe UI', sans-serif;
  color: var(--text-dark);
  background: linear-gradient(135deg, var(--cream-light), var(--cream));
  overflow-x: hidden;
  position: relative;
}

/* ===== NAVBAR ===== */
.header {
  position: fixed;
  top: 0; left: 0; width: 100%;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.75);
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 60px;
  z-index: 1000;
}

.brand { display: flex; align-items: center; gap: 12px; }
.logo {
  background: var(--brown);
  color: white;
  font-weight: 700;
  font-size: 22px;
  width: 52px;
  height: 52px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 14px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
.brand h2 {
  font-size: 1.7rem;
  color: var(--text-dark);
  letter-spacing: 0.5px;
}
.links a {
  text-decoration: none;
  color: var(--brown);
  font-weight: 600;
  margin-left: 20px;
  border: 2px solid var(--brown);
  padding: 8px 20px;
  border-radius: 30px;
  transition: all 0.3s ease;
}
.links a:hover {
  background: var(--brown);
  color: white;
  transform: translateY(-2px);
}

/* ===== HERO SECTION ===== */
/* ===== HERO SECTION ===== */
.hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 120px 80px;
  min-height: 100vh;
  gap: 40px;
  position: relative;
  overflow: hidden;
}

.hero-text {
  flex: 1.3; /* wider text area */
  min-width: 320px;
  max-width: 750px; /* more readable line width */
  animation: fadeInUp 0.8s ease;
}

.hero-text h1 {
  font-size: 4rem;
  line-height: 1.15;
  color: var(--text-dark); /* restore main text color */
  margin-bottom: 25px;
}

.hero-text p {
  font-size: 1.2rem;
  color: var(--text-soft); /* restore soft brown text color */
  margin-bottom: 40px;
  line-height: 1.8;
  max-width: 95%;
}

/* ===== HERO BUTTONS ===== */
.hero-buttons a {
  text-decoration: none;
  padding: 14px 32px;
  border-radius: 35px;
  margin-right: 15px;
  font-weight: 600;
  transition: all 0.3s ease;
  display: inline-block;
  font-size: 1.05rem;
}

.btn-brown {
  background-color: var(--brown);
  color: white;
  box-shadow: 0 4px 15px rgba(0,0,0,0.25);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.btn-brown:hover {
  background-color: var(--brown-dark);
  transform: scale(1.05);
  box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

.btn-outline {
  border: 2px solid var(--brown);
  color: var(--brown);
}

.btn-outline:hover {
  background: var(--brown);
  color: white;
  transform: scale(1.05);
}

/* ===== HERO IMAGE ===== */
.hero-image {
  flex: 1;
  min-width: 320px;
  text-align: center;
  animation: fadeIn 1s ease;
  position: relative;
  z-index: 2;
}

.hero-image {
  flex: 1;
  min-width: 320px;
  text-align: center;
  animation: fadeIn 1s ease;
  position: relative;
  z-index: 2;
}

.hero-image img {
  width: 130%; /* make image larger */
  max-width: 750px;
  border-radius: 25px;
  transform: translateX(5%) scale(1.1);
  transition: transform 0.6s ease;
}

.hero-image img:hover {
  transform: translateX(5%) scale(1.15) rotate(-1deg);
}

/* ===== FLOATING PAW PRINTS ===== */
.paw {
  position: absolute;
  pointer-events: none;
  opacity: 0.08;
  z-index: 1;
  animation: floatPaw ease-in-out infinite;
}

@keyframes floatPaw {
  0% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-15px) rotate(10deg); }
  100% { transform: translateY(0) rotate(0deg); }
}

/* ===== FOOTER ===== */
footer {
  background: var(--brown);
  color: white;
  text-align: center;
  padding: 40px 20px;
  font-size: 15px;
  margin-top: 60px;
}

/* ===== ANIMATIONS ===== */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@media (max-width: 992px) {
  .hero {
    flex-direction: column-reverse;
    text-align: center;
    padding: 100px 40px;
  }
  .hero-text {
    max-width: 100%;
  }
  .hero-text h1 {
    font-size: 2.8rem;
  }
  .hero-text p {
    font-size: 1.1rem;
    max-width: 100%;
  }
  .hero-image img {
    width: 90%;
    max-width: 500px;
    transform: scale(1);
  }
}
@media (max-width: 600px) {
  .header { padding: 15px 30px; }
  .hero { padding: 80px 25px; }
  .hero-text h1 { font-size: 2.2rem; }
  .hero-text p { font-size: 1rem; }
  .hero-buttons a { padding: 12px 28px; font-size: 1rem; }
}
</style>
</head>
<body>

<!-- Floating Paw Prints Container -->
<div id="paw-container"></div>

<!-- Header -->
<header class="header">
  <div class="brand">
    <div class="logo">PF</div>
    <h2>Pet Food Place</h2>
  </div>
  <nav class="links">
	<a href="backend/auth/login.php">Log In</a>
	<a href="backend/auth/signup.php">Sign Up</a>
  </nav>
</header>

<!-- Hero Section -->
<section class="hero">
  <div class="hero-text">
    <h1>Healthy Food for Your Pets</h1>
    <p>Welcome to Pet Food Place — where nutrition meets love. Discover fresh, balanced meals for dogs and cats of every breed, size, and personality.</p>
    <div class="hero-buttons">
      <a href="backend/auth/signup.php" class="btn-brown">Get Started</a>
      <a href="backend/auth/login.php" class="btn-outline">Login</a>
    </div>
  </div>
  <div class="hero-image">
    <img src="assets/image/petfood.png" alt="Happy pets eating healthy food">
  </div>
</section>

<!-- Footer -->
<footer>
  &copy; <?= date('Y') ?> Pet Food Place. All rights reserved.
</footer>

<!-- ===== JS FOR RANDOM FLOATING PAWS ===== -->
<script>
const pawContainer = document.getElementById('paw-container');
const pawImage = 'assets/image/paw-print.png';
const pawCount = 70;
const paws = [];

// Create paw prints
for (let i = 0; i < pawCount; i++) {
  const paw = document.createElement('img');
  paw.src = pawImage;
  paw.classList.add('paw');
  paw.style.top = Math.random() * 100 + 'vh';
  paw.style.left = Math.random() * 100 + 'vw';
  
  // Random size (mix of small and large paws)
  const size = 15 + Math.random() * 90;
  paw.style.width = size + 'px';

  // Random opacity and animation duration
  paw.style.opacity = 0.05 + Math.random() * 0.15;
  paw.style.animationDuration = (8 + Math.random() * 15) + 's';
  paw.style.animationDelay = Math.random() * 5 + 's';
  
  pawContainer.appendChild(paw);
  
  paws.push({
    el: paw,
    speedX: (Math.random() - 0.5) * 0.1,
    speedY: (Math.random() - 0.5) * 0.1
  });
}

// Gentle, slow random drifting movement
function animatePaws() {
  paws.forEach(p => {
    const rect = p.el.getBoundingClientRect();
    let x = parseFloat(p.el.style.left);
    let y = parseFloat(p.el.style.top);
    
    // Move slightly and wrap around
    x += p.speedX;
    y += p.speedY;
    if (x < 0) x = 100;
    if (x > 100) x = 0;
    if (y < 0) y = 100;
    if (y > 100) y = 0;
    
    p.el.style.left = x + 'vw';
    p.el.style.top = y + 'vh';
  });
  requestAnimationFrame(animatePaws);
}

animatePaws();
</script>

</body>
</html>
