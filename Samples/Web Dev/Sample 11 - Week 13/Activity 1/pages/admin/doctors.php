<?php
// Include comprehensive session validation
require_once __DIR__ . '/../../includes/session-check.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

// Database connection
$host = 'localhost';
$username = 'root';
$password = 'root';
$database = 'db_trailsandtails_midterm';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get admin name from session
$adminFullName = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';

// Fetch doctor statistics
$stats = [
    'total' => 0,
    'on_duty' => 0,
    'off_duty' => 0,
    'on_leave' => 0
];

$statsQuery = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'On Duty' THEN 1 ELSE 0 END) as on_duty,
        SUM(CASE WHEN status = 'Off Duty' THEN 1 ELSE 0 END) as off_duty,
        SUM(CASE WHEN status = 'On Leave' THEN 1 ELSE 0 END) as on_leave
    FROM tbl_vets
");

if ($statsQuery && $statsResult = $statsQuery->fetch_assoc()) {
    $stats['total'] = $statsResult['total'] ?? 0;
    $stats['on_duty'] = $statsResult['on_duty'] ?? 0;
    $stats['off_duty'] = $statsResult['off_duty'] ?? 0;
    $stats['on_leave'] = $statsResult['on_leave'] ?? 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tails & Trails - Doctors Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../assets/css/admin/style.css">
</head>
<body>

<!-- App wrapper containing sidebar and main content -->
<div class="app-wrapper">

  <!-- Left floating panel (collapsed by default) -->
  <aside id="leftPanel" class="left-panel closed" aria-hidden="true">
  <div class="side-top">
    <div class="side-logo">🐾 <span>Tails & Trails</span></div>
    <button id="panelToggle" class="side-toggle" aria-expanded="false" aria-label="Open navigation">☰</button>
  </div>
  <nav class="nav" role="navigation" aria-label="Main navigation">
    <button class="side-btn" data-target="dashboard"><i class="bi bi-house-door-fill icon"></i><span class="label">Dashboard</span></button>
    <button class="side-btn" data-target="users"><i class="bi bi-people-fill icon"></i><span class="label">Users</span></button>
    <button class="side-btn active" data-target="doctors"><i class="bi bi-person-hearts icon"></i><span class="label">Doctors</span></button>
    <button class="side-btn logout-btn"><i class="bi bi-box-arrow-right icon"></i><span class="label">Logout</span></button>
  </nav>
  </aside>

  <div class="user-page">
  <header class="admin-header">
    <div class="logo">👨‍⚕️ <span>Doctors</span></div>
    <div class="top-controls">
      <button id="addDoctorBtn" class="btn primary-btn">+ Add Doctor</button>
      <input type="text" id="doctorSearch" placeholder="Search by name, email or contact...">
    </div>
  </header>

  <section class="summary-section">
    <div class="summary-card">
      <h3>Total</h3>
      <p id="totalDoctors"><?php echo $stats['total']; ?></p>
    </div>
    <div class="summary-card">
      <h3>On Duty</h3>
      <p id="onDutyDoctors"><?php echo $stats['on_duty']; ?></p>
    </div>
    <div class="summary-card">
      <h3>Off Duty</h3>
      <p id="offDutyDoctors"><?php echo $stats['off_duty']; ?></p>
    </div>
    <div class="summary-card">
      <h3>On Leave</h3>
      <p id="onLeaveDoctors"><?php echo $stats['on_leave']; ?></p>
    </div>
  </section>

  <div class="filter-bar">
    <button class="filter-btn active" data-status="all">All</button>
    <button class="filter-btn" data-status="On Duty">On Duty</button>
    <button class="filter-btn" data-status="Off Duty">Off Duty</button>
    <button class="filter-btn" data-status="On Leave">On Leave</button>
  </div>

  <section class="users-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
      <h2 style="margin: 0;">Doctor List</h2>
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
      <table id="doctorTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Status</th>
            <th>Available Days</th>
            <th>Available Times</th>
            <th>Created At</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <div class="pagination-wrapper">
      <div class="pagination-info">
        Showing <span id="showingStart">0</span> to <span id="showingEnd">0</span> of <span id="totalRecords">0</span> doctors
      </div>
      <div class="pagination-controls">
        <button id="prevPage" class="pagination-btn" disabled>← Previous</button>
        <div id="pageNumbers" class="page-numbers"></div>
        <button id="nextPage" class="pagination-btn">Next →</button>
      </div>
    </div>
  </section>
</div>


<div id="doctorModal" class="modal">
  <div class="modal-content">
    <h2 id="doctorModalTitle">Add Doctor</h2>
    <form id="doctorForm">
      <input type="hidden" id="vetId">

      <label for="doctorName">Name:</label>
      <input type="text" id="doctorName" required>

      <label for="doctorEmail">Email:</label>
      <input type="email" id="doctorEmail" required>

      <label for="doctorContact">Contact:</label>
      <input type="text" id="doctorContact" required>

      <label for="doctorAvailableTimes">Available Times:</label>
      <div class="available-times-group">
        <label class="checkbox-label">
          <input type="checkbox" name="availableTime" value="7-12" class="time-checkbox"> 7:00 AM - 12:00 PM
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="availableTime" value="12-5" class="time-checkbox"> 12:00 PM - 5:00 PM
        </label>
      </div>

      <label for="doctorAvailableDays">Available Days:</label>
      <div class="available-days-group">
        <label class="checkbox-label">
          <input type="checkbox" name="availableDay" value="Monday" class="day-checkbox"> Monday
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="availableDay" value="Tuesday" class="day-checkbox"> Tuesday
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="availableDay" value="Wednesday" class="day-checkbox"> Wednesday
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="availableDay" value="Thursday" class="day-checkbox"> Thursday
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="availableDay" value="Friday" class="day-checkbox"> Friday
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="availableDay" value="Saturday" class="day-checkbox"> Saturday
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="availableDay" value="Sunday" class="day-checkbox"> Sunday
        </label>
      </div>

      <div class="modal-buttons">
        <button type="submit" class="btn primary-btn">Save</button>
        <button type="button" id="doctorCancelBtn" class="btn cancel-btn">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Script loading order (IMPORTANT: app.js MUST be first) -->
<script src="../../assets/js/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/components/toast.js"></script>
<script src="../../assets/js/admin/doctors_script.js"></script>
</body>
</html>

<?php $conn->close(); ?>
