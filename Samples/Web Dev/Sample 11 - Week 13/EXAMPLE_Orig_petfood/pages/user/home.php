<?php
session_start();
require '../../backend/db.php';

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$items = $pdo->query("SELECT * FROM items ORDER BY created_at DESC")->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Products — Pet Food Place</title>
<link rel="stylesheet" href="../../assets/css/shared.css">
</head>
<body>

<!-- Floating Paw Prints -->
<div id="paw-container"></div>

<!-- Header -->
<header class="header">
  <div class="brand">
    <div class="logo">PF</div>
    <h1>Pet Food Place</h1>
  </div>
  <nav class="links">
    <a href="../../index.php">Home</a>
    <a href="../../backend/auth/logout.php" class="btn btn-danger">Logout</a>
  </nav>
</header>

<!-- Main Content -->
<div class="container">
  <div style="text-align: center; margin-bottom: 50px;">
    <h2 style="font-size: 2.5rem; color: var(--text-dark); margin-bottom: 12px;">Premium Pet Food Selection</h2>
    <p style="font-size: 1.1rem; color: var(--text-soft); max-width: 700px; margin: 0 auto;">
      Discover our carefully curated collection of nutritious meals for your beloved pets. Quality ingredients, balanced nutrition, happy pets.
    </p>
  </div>

  <div class="grid">
    <?php foreach($items as $it): ?>
      <div class="card">
        <div class="thumb"><?= e($it['item_code'] ?? 'N/A') ?></div>
        <h3><?= e($it['name']) ?></h3>
        <div class="meta"><?= nl2br(e($it['description'])) ?></div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid #f0ebe6;">
          <div>
            <div class="price">₱<?= number_format($it['price'],2) ?></div>
            <div class="meta" style="margin-top: 4px;">
              Stock: <strong style="color: <?= $it['stock'] > 10 ? '#137d3b' : ($it['stock'] > 0 ? '#b45a00' : '#b33a2b') ?>">
                <?= (int)$it['stock'] ?> available
              </strong>
            </div>
          </div>
          <div>
            <?php if($it['stock'] > 0): ?>
              <a href="order_page.php?id=<?= (int)$it['id'] ?>" class="btn btn-brown" style="padding: 10px 20px;">Order Now</a>
            <?php else: ?>
              <button class="secondary" disabled style="padding: 10px 20px; opacity: 0.5; cursor: not-allowed;">Out of Stock</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Footer -->
<footer>
  &copy; <?= date('Y') ?> Pet Food Place. All rights reserved.
</footer>

<script src="../../assets/js/paw-animation.js"></script>
</body>
</html>