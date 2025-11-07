<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Pet Food Place — Home</title>
<link rel="stylesheet" href="styles.css">
<style>
/* You can move this part into styles.css later */

/* Page Base */
body {
  margin: 0;
  font-family: 'Segoe UI', sans-serif;
  background: linear-gradient(135deg, #f6f0e0, #f1d1a2);
  color: #442a10;
}

.container {
  max-width: 1100px;
  margin: auto;
  padding: 40px 20px;
}

/* Header */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 25px 0;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo {
  background: #8B4513;
  color: white;
  font-weight: bold;
  font-size: 20px;
  width: 48px;
  height: 48px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 12px;
}

.links a {
  color: #8B4513;
  text-decoration: none;
  font-weight: 600;
  margin-left: 20px;
  border: 2px solid #8B4513;
  padding: 8px 16px;
  border-radius: 25px;
  transition: 0.3s;
}

.links a:hover {
  background: #8B4513;
  color: white;
}

/* Hero Section */
.hero {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 40px;
  margin-top: 40px;
}

.hero-text {
  flex: 1;
  min-width: 280px;
}

.hero-text h1 {
  font-size: 48px;
  line-height: 1.2;
  color: #5A3412;
  margin-bottom: 20px;
}

.hero-text p {
  font-size: 18px;
  color: #6d4c1d;
  margin-bottom: 30px;
}

.hero-buttons a {
  text-decoration: none;
  padding: 12px 24px;
  border-radius: 30px;
  margin-right: 10px;
  font-weight: 600;
  transition: 0.3s;
}

.btn-brown {
  background-color: #8B4513;
  color: white;
}

.btn-brown:hover {
  background-color: #6e3510;
}

.btn-outline {
  border: 2px solid #8B4513;
  color: #8B4513;
}

.btn-outline:hover {
  background: #8B4513;
  color: white;
}

/* Image slot */
.hero-image {
  flex: 1;
  min-width: 300px;
  text-align: center;
}

.hero-image img {
  max-width: 100%;
  border-radius: 20px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Footer */
footer {
  text-align: center;
  padding: 40px 0 10px;
  font-size: 14px;
  color: #6d4c1d;
}
</style>
</head>
<body>
<div class="container">

  <!-- Header -->
  <div class="header">
    <div class="brand">
      <div class="logo">PF</div>
      <h2>Pet Food Place</h2>
    </div>
    <div class="links">
      <a href="auth/login.php">Log In</a>
      <a href="auth/signup.php">Sign Up</a>
    </div>
  </div>

  <!-- Hero Section -->
  <div class="hero">
    <div class="hero-text">
      <h1>Healthy Food for Your Pets</h1>
      <p>Welcome to Pet Food Place — where nutrition meets love. Reserve your pet’s favorite meals, or explore our variety of healthy pet foods for every breed and size.</p>
      <div class="hero-buttons">
        <a href="auth/signup.php" class="btn-brown">Get Started</a>
        <a href="auth/login.php" class="btn-outline">Login</a>
      </div>
    </div>
    <div class="hero-image">
      <!-- 🐾 You can replace this with your own banner image -->
      <img src="images/hero-petfood.jpg" alt="Happy pets eating healthy food">
    </div>
  </div>

  <!-- Footer -->
  <footer>
    &copy; <?= date('Y') ?> Pet Food Place. All rights reserved.
  </footer>

</div>
</body>
</html>
