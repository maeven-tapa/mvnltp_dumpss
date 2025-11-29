<?php

require_once __DIR__ . '/../../includes/session-check.php';


if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}


$host = 'localhost';
$username = 'root';
$password = 'root';
$database = 'db_trailsandtails_midterm';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$adminFullName = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';


$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0
];

$statsQuery = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
    FROM tbl_users
    WHERE role = 'user'
");

if ($statsQuery && $statsResult = $statsQuery->fetch_assoc()) {
    $stats['total'] = $statsResult['total'] ?? 0;
    $stats['active'] = $statsResult['active'] ?? 0;
    $stats['inactive'] = $statsResult['inactive'] ?? 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tails & Trails - Users Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../assets/css/admin/style.css">
</head>
<body>


<div class="app-wrapper">


  <aside id="leftPanel" class="left-panel closed" aria-hidden="true">
  <div class="side-top">
    <div class="side-logo">🐾 <span>Tails & Trails</span></div>
    <button id="panelToggle" class="side-toggle" aria-expanded="false" aria-label="Open navigation">☰</button>
  </div>
  <nav class="nav" role="navigation" aria-label="Main navigation">
    <button class="side-btn" data-target="dashboard"><i class="bi bi-house-door-fill icon"></i><span class="label">Dashboard</span></button>
    <button class="side-btn active" data-target="users"><i class="bi bi-people-fill icon"></i><span class="label">Users</span></button>
    <button class="side-btn" data-target="doctors"><i class="bi bi-person-hearts icon"></i><span class="label">Doctors</span></button>
    <button class="side-btn logout-btn"><i class="bi bi-box-arrow-right icon"></i><span class="label">Logout</span></button>
  </nav>
  </aside>

  <div class="user-page">
  <header class="admin-header">
    <div class="logo">👥 <span>Users</span></div>
    <div class="top-controls">
      <input type="text" id="userSearch" placeholder="Search by name, email or contact...">
    </div>
  </header>

  <section class="summary-section">
    <div class="summary-card">
      <h3>Total</h3>
      <p id="totalUsers"><?php echo $stats['total']; ?></p>
    </div>
    <div class="summary-card">
      <h3>Active</h3>
      <p id="activeUsers"><?php echo $stats['active']; ?></p>
    </div>
    <div class="summary-card">
      <h3>Inactive</h3>
      <p id="inactiveUsers"><?php echo $stats['inactive']; ?></p>
    </div>
  </section>

  <div class="filter-bar">
    <button class="filter-btn active" data-status="all">All</button>
    <button class="filter-btn" data-status="active">Active</button>
    <button class="filter-btn" data-status="inactive">Inactive</button>
  </div>

  <section class="users-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
      <h2 style="margin: 0;">User List</h2>
      <div style="display: flex; gap: 10px; align-items: center;">
        <span style="color: #666; font-size: 0.9em;">Items per page:</span>
        <select id="itemsPerPage" style="padding: 5px 10px; border-radius: 6px; border: 1px solid #dcdcdc;">
          <option value="10">10</option>
          <option value="15" selected>15</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="all">All</option>
        </select>
      </div>
    </div>
    <div class="table-wrapper">
      <table id="userTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>User ID</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Created At</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <div class="pagination-wrapper">
      <div class="pagination-info">
        Showing <span id="showingStart">0</span> to <span id="showingEnd">0</span> of <span id="totalRecords">0</span> users
      </div>
      <div class="pagination-controls">
        <button id="prevPage" class="pagination-btn" disabled>← Previous</button>
        <div id="pageNumbers" class="page-numbers"></div>
        <button id="nextPage" class="pagination-btn">Next →</button>
      </div>
    </div>
  </section>
</div>


<div id="userModal" class="modal">
  <div class="modal-content">
    <h2 id="userModalTitle">Edit User</h2>
    <form id="userForm">
      <input type="hidden" id="userId">

      <label for="userName">Name:</label>
      <input type="text" id="userName" required>

      <label for="userEmail">Email:</label>
      <input type="email" id="userEmail" required>

      <label for="userContact">Contact:</label>
      <input type="text" id="userContact" required>

      <label for="userStatus">Status:</label>
      <select id="userStatus" required>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>

      <label for="userCreatedAt">Created At:</label>
      <input type="text" id="userCreatedAt" disabled>

      <div class="modal-buttons">
        <button type="submit" class="btn primary-btn">Save</button>
        <button type="button" id="userCancelBtn" class="btn cancel-btn">Cancel</button>
      </div>
    </form>
  </div>
</div>


<script src="../../assets/js/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/components/toast.js"></script>
<script src="../../assets/js/admin/users_script.js"></script>
</body>
</html>

<?php $conn->close(); ?>
