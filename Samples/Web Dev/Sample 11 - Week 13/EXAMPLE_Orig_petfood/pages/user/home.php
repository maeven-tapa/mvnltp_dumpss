<?php
session_start();
require 'db.php';

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// fetch items
$items = $pdo->query("SELECT * FROM items ORDER BY created_at DESC")->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Pet Food — Browse</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand">
      <div class="logo">PF</div>
      <h1>Pet Food Place</h1>
    </div>
    <div class="links">
      <a href="home_admin.php">Admin</a>
      <a class="cta" href="home_user.php">Shop</a>
    </div>
  </div>

  <section>
    <h2 style="margin-top:0">Best Pet Foods</h2>
    <div class="grid">
      <?php foreach($items as $it): ?>
        <div class="card">
          <div class="thumb"><?= e($it['item_code'] ?? '') ?></div>
          <h3><?= e($it['name']) ?></h3>
          <div class="meta"><?= nl2br(e($it['description'])) ?></div>
          <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
            <div>
              <div class="price">₱<?= number_format($it['price'],2) ?></div>
              <div class="meta">Stock: <?= (int)$it['stock'] ?></div>
            </div>
            <div class="actions">
              <?php if($it['stock'] > 0): ?>
				<a class="btn btn-brown" href="order_page.php?id=<?= (int)$it['id'] ?>">Order Now</a>
              <?php else: ?>
                <button class="secondary" disabled>Out of stock</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
</body>
</html>
