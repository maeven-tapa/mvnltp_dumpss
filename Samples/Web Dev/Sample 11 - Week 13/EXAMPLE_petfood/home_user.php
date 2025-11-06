<?php
session_start();
require 'db.php';


$items = $pdo->query('SELECT * FROM items ORDER BY created_at DESC')->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Pet Food — Browse & Reserve</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
<h1>Pet Food — Browse & Reserve</h1>
<p><a href="home_admin.php">Admin? Log in here</a></p>


<?php foreach($items as $it): ?>
<div class="card">
<div class="item-row">
<div>
<h3><?= e($it['name']) ?> <small class="meta">₱<?= number_format($it['price'],2) ?></small></h3>
<p><?= nl2br(e($it['description'])) ?></p>
<p><small>Stock: <?= (int)$it['stock'] ?></small></p>
</div>
<div style="min-width:220px;">
<form action="reserve.php" method="post">
<input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
<div class="form-field"><input required name="customer_name" placeholder="Your name"></div>
<div class="form-field"><input required name="customer_contact" placeholder="Contact (phone/email)"></div>
<div class="form-field"><input type="number" name="quantity" value="1" min="1" max="<?= (int)$it['stock'] ?>"></div>
<div class="form-field">
<button type="submit">Reserve</button>
</div>
</form>
</div>
</div>
</div>
<?php endforeach; ?>


</div>
</body>
</html>