<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
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
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', sans-serif;
  color: var(--text-dark);
  background: linear-gradient(135deg, var(--cream-light), var(--cream));
  overflow-x: hidden;
}

/* ===== NAVBAR ===== */
.header {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.75);
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 60px;
  z-index: 1000;
  transition: 0.3s ease;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

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
.hero {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  padding: 120px 80px;
  min-height: 100vh;
  gap: 40px;
}

.hero-text {
  flex: 1;
  min-width: 320px;
  max-width: 600px;
  animation: fadeInUp 0.8s ease;
}

.hero-text h1 {
  font-size: 3.5rem;
  line-height: 1.2;
  color: var(--text-dark);
  margin-bottom: 20px;
}

.hero-text p {
  font-size: 1.1rem;
  color: var(--text-soft);
  margin-bottom: 35px;
  line-height: 1.7;
}

.hero-buttons a {
  text-decoration: none;
  padding: 14px 32px;
  border-radius: 35px;
  margin-right: 15px;
  font-weight: 600;
  transition: all 0.3s ease;
  display: inline-block;
}

.btn-brown {
  background-color: var(--brown);
  color: white;
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

.btn-brown:hover {
  background-color: var(--brown-dark);
  transform: scale(1.05);
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
}

.hero-image img {
  width: 100%;
  max-width: 550px;
  border-radius: 25px;
  box-shadow: 0 12px 30px rgba(0,0,0,0.25);
  transition: transform 0.5s ease;
}

.hero-image img:hover {
  transform: scale(1.03);
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

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
  .hero {
    padding: 100px 40px;
    flex-direction: column-reverse;
    text-align: center;
  }

  .hero-text h1 {
    font-size: 2.5rem;
  }

  .links {
    margin-top: 10px;
  }

  .links a {
    margin: 5px;
  }
}

@media (max-width: 600px) {
  .header {
    padding: 15px 30px;
  }

  .hero {
    padding: 80px 25px;
  }
}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <div class="brand">
    <div class="logo">PF</div>
    <h2>Pet Food Place</h2>
  </div>
  <nav class="links">
    <a href="auth/login.php">Log In</a>
    <a href="auth/signup.php">Sign Up</a>
  </nav>
</header>

<!-- Hero Section -->
<section class="hero">
  <div class="hero-text">
    <h1>Healthy Food for Your Pets</h1>
    <p>Welcome to Pet Food Place — where nutrition meets love. Discover fresh, balanced meals for dogs and cats of every breed, size, and personality.</p>
    <div class="hero-buttons">
      <a href="auth/signup.php" class="btn-brown">Get Started</a>
      <a href="auth/login.php" class="btn-outline">Login</a>
    </div>
  </div>
  <div class="hero-image">
    <img src="images/hero-petfood.jpg" alt="Happy pets eating healthy food">
  </div>
</section>

<!-- Footer -->
<footer>
  &copy; <?= date('Y') ?> Pet Food Place. All rights reserved.
</footer>

</body>
</html>
