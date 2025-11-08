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

// Fetch appointment statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'cancelled' => 0
];

$statsQuery = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM tbl_appointments
");

if ($statsQuery && $statsResult = $statsQuery->fetch_assoc()) {
    $stats['total'] = $statsResult['total'] ?? 0;
    $stats['pending'] = $statsResult['pending'] ?? 0;
    $stats['confirmed'] = $statsResult['confirmed'] ?? 0;
    $stats['cancelled'] = $statsResult['cancelled'] ?? 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tails & Trails - Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../assets/css/admin/style.css">
<link rel="stylesheet" href="../../assets/css/booking-date-picker.css">
<link rel="stylesheet" href="../../assets/css/toast.css">
<style>
  .date-time-section {
    display: none;
    transition: opacity 0.3s ease;
  }
  .date-time-section.visible {
    display: block;
    animation: fadeIn 0.3s ease;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }

  /* Ensure modal scrolls when content expands */
  .modal-content {
    max-height: 90vh;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
  }

  .modal-content form {
    display: flex;
    flex-direction: column;
    gap: 0;
  }

  /* Compact form field spacing */
  .modal-content label {
    margin-top: 12px !important;
    margin-bottom: 4px !important;
  }

  .modal-content input,
  .modal-content select,
  .modal-content textarea {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
  }

  /* Booking date picker inside modal - position relative to modal */
  .modal-content .booking-date-picker-wrapper {
    position: relative;
    overflow: visible !important;
    margin-top: 12px;
  }

  .modal-content .booking-date-picker-container {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 9999;
  }
</style>
</head>
<body>

<!-- App wrapper containing sidebar and main content -->
<div class="app-wrapper">

  <!-- Left floating panel (collapsed by default) -->
  <aside id="leftPanel" class="left-panel closed" aria-hidden="true">
  <div class="side-top">
    <div class="side-logo"><img src="../../assets/images/logo.png" alt="Tails & Trails Logo" class="logo-img"> <span>Tails & Trails</span></div>
    <button id="panelToggle" class="side-toggle" aria-expanded="false" aria-label="Open navigation">☰</button>
  </div>
  <nav class="nav" role="navigation" aria-label="Main navigation">
    <button class="side-btn active" data-target="dashboard"><i class="bi bi-house-door-fill icon"></i><span class="label">Dashboard</span></button>
    <button class="side-btn" data-target="users"><i class="bi bi-people-fill icon"></i><span class="label">Users</span></button>
    <button class="side-btn" data-target="doctors"><i class="bi bi-person-hearts icon"></i><span class="label">Doctors</span></button>
    <button class="side-btn logout-btn"><i class="bi bi-box-arrow-right icon"></i><span class="label">Logout</span></button>
  </nav>
  </aside>

  <div class="admin-dashboard">
  <header class="admin-header">
    <div class="logo"><img src="../../assets/images/logo.png" alt="Tails & Trails Logo" class="logo-img"> <span>Dashboard</span></div>
    <div class="top-controls">
      <span style="font-weight: 600; margin-right: 20px;">Welcome, <?php echo htmlspecialchars($adminFullName); ?></span>
      <button id="addAdminBtn" class="btn primary-btn">+ Add Appointment</button>
      <input type="text" id="adminSearch" placeholder="Search by pet, owner, or doctor...">
    </div>
  </header>

  <section class="summary-section">
    <div class="summary-card">
      <h3>Total</h3>
      <p id="totalAppointments"><?php echo $stats['total']; ?></p>
    </div>
    <div class="summary-card">
      <h3>Pending</h3>
      <p id="pendingAppointments"><?php echo $stats['pending']; ?></p>
    </div>
    <div class="summary-card">
      <h3>Confirmed</h3>
      <p id="confirmedAppointments"><?php echo $stats['confirmed']; ?></p>
    </div>
    <div class="summary-card">
      <h3>Cancelled</h3>
      <p id="cancelledAppointments"><?php echo $stats['cancelled']; ?></p>
    </div>
  </section>

  <div class="filter-bar">
    <button class="filter-btn active" data-status="all">All</button>
    <button class="filter-btn" data-status="Pending">Pending</button>
    <button class="filter-btn" data-status="Confirmed">Confirmed</button>
    <button class="filter-btn" data-status="Completed">Completed</button>
    <button class="filter-btn" data-status="Cancelled">Cancelled</button>
  </div>

  <section class="appointments-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
      <h2 style="margin: 0;">Appointments</h2>
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
      <table id="adminTable">
        <thead>
          <tr>
            <th>Pet Name</th>
            <th>Owner</th>
            <th>Doctor</th>
            <th>Service</th>
            <th class="sortable" data-sort="date">Date <span class="sort-icon">↕</span></th>
            <th class="sortable" data-sort="time">Time <span class="sort-icon">↕</span></th>
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
        Showing <span id="showingStart">0</span> to <span id="showingEnd">0</span> of <span id="totalRecords">0</span> appointments
      </div>
      <div class="pagination-controls">
        <button id="prevPage" class="pagination-btn" disabled>← Previous</button>
        <div id="pageNumbers" class="page-numbers"></div>
        <button id="nextPage" class="pagination-btn">Next →</button>
      </div>
    </div>
  </section>
</div>


<div id="adminModal" class="modal">
  <div class="modal-content">
    <h2 id="adminModalTitle">Add Appointment</h2>
    <form id="adminForm">
      <label for="adminBookingType">Booking Type:</label>
      <select id="adminBookingType" required>
        <option value="guest" selected>Guest</option>
        <option value="registered">Registered User</option>
      </select>

      <!-- Registered User Fields -->
      <div id="registeredFields" style="display: none;">
        <label for="adminUserId">User ID:</label>
        <input type="text" id="adminUserId" placeholder="e.g., USR0001">
      </div>

      <!-- Guest Fields -->
      <div id="guestFields">
        <label for="adminGuestName">Guest Name:</label>
        <input type="text" id="adminGuestName">

        <label for="adminGuestEmail">Guest Email:</label>
        <input type="email" id="adminGuestEmail">

        <label for="adminGuestContact">Guest Contact:</label>
        <input type="tel" id="adminGuestContact">
      </div>

      <label for="adminPetName">Pet Name:</label>
      <input type="text" id="adminPetName" required>

      <label for="adminDoctor">Doctor:</label>
      <select id="adminDoctor" required>
        <option value="" disabled selected>Select doctor</option>
      </select>

      <label for="adminService">Service:</label>
      <select id="adminService" required>
        <option value="" disabled selected>Select service</option>
        <option value="Check-up">Check-up</option>
        <option value="Vaccination">Vaccination</option>
        <option value="Grooming">Grooming</option>
        <option value="Dental Cleaning">Dental Cleaning</option>
        <option value="Surgery">Surgery</option>
      </select>

      <div class="date-time-section">
        <label for="adminDate">Date:</label>
        <input type="text" id="adminDate" placeholder="YYYY-MM-DD" required>

        <label for="adminTime">Time:</label>
        <select id="adminTime" required>
          <option value="" disabled selected>Select time</option>
        </select>
      </div>

      <div class="modal-buttons">
        <button type="submit" class="btn primary-btn">Save</button>
        <button type="button" id="adminCancelBtn" class="btn cancel-btn">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="../../assets/js/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/components/booking-date-picker.js"></script>
<script src="../../assets/js/components/toast.js"></script>
<script src="../../assets/js/utils/appointment.js"></script>
<script src="../../assets/js/admin/script.js"></script>
</body>
</html>

<?php $conn->close(); ?>
