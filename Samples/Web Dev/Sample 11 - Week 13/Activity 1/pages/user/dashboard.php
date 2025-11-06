<?php
session_start();
// require login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}
// include helper that sets $userFirstName, $userLastName, $userFullName
require_once __DIR__ . '/get_user.php';
// Fallback if helper didn't set a fullname
if (empty($userFullName)) {
  $userFullName = isset($_SESSION['name']) ? $_SESSION['name'] : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tails & Trails - My Appointments</title>
<link rel="stylesheet" href="../../assets/css/user/style.css">
</head>
<body>

<div class="dashboard-box">
  <div class="dashboard-container">
    
    <header class="dashboard-header">
      <div class="logo">🐾 <span>Tails & Trails</span></div>
      <div class="header-right" style="display:flex;align-items:center;gap:0.75rem;">
        <span id="welcomeText" style="font-weight:600;">Welcome, <?php echo htmlspecialchars($userFullName); ?></span>
        <button id="logoutBtn" class="btn" aria-label="Logout" onclick="window.location.href='../../auth/logout.php'">Logout</button>
        <button id="addBtn" class="btn primary-btn" aria-label="Book New Appointment">+ Book New Appointment</button>
      </div>
    </header>

    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Search my appointments..." aria-label="Search Appointments">
    </div>

    <section class="appointments-section">
      <h2>Upcoming Appointments</h2>
      <div class="table-wrapper">
        <table id="upcomingTable">
          <thead>
            <tr>
              <th>Pet's Name</th>
              <th>Service</th>
              <th>Assigned Vet</th>
              <th>Date</th>
              <th>Time</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </section>

    <section class="appointments-section">
      <h2>Past Appointments</h2>
      <div class="table-wrapper">
        <table id="pastTable">
          <thead>
            <tr>
              <th>Pet's Name</th>
              <th>Service</th>
              <th>Assigned Vet</th>
              <th>Date</th>
              <th>Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </section>
    
  </div>
</div>

<div id="modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal-content">
    <h2 id="modalTitle">Book New Appointment</h2>
    <form id="appointmentForm">
      
      <label for="petName">Pet Name:</label>
      <input type="text" id="petName" name="petName" placeholder="Enter your pet's name" required>

      <label for="service">Service:</label>
      <select id="service" name="service" required>
        <option value="" disabled selected>Select service</option>
        <option value="Check-up">Check-up</option>
        <option value="Vaccination">Vaccination</option>
        <option value="Grooming">Grooming</option>
        <option value="Dental Cleaning">Dental Cleaning</option>
        <option value="Surgery">Surgery</option>
      </select>

      <label for="vet">Preferred Vet (optional):</label>
      <select id="vet" name="vet">
        <option value="" selected>No preference</option>
        <option value="Dr. Palacios">Dr. Palacios</option>
        <option value="Dr. Santos">Dr. Santos</option>
        <option value="Dr. Padasay">Dr. Padasay</option>
      </select>

      <label for="date">Date:</label>
      <input type="date" id="date" name="date" required>

      <label for="time">Time:</label>
<select id="time" name="time" required>
  <option value="" disabled selected>Select time</option>
  <option value="08:00">08:00 AM</option>
  <option value="08:30">08:30 AM</option>
  <option value="09:00">09:00 AM</option>
  <option value="09:30">09:30 AM</option>
  <option value="10:00">10:00 AM</option>
  <option value="10:30">10:30 AM</option>
  <option value="11:00">11:00 AM</option>
  <option value="11:30">11:30 AM</option>
  <option value="13:00">01:00 PM</option>
  <option value="13:30">01:30 PM</option>
  <option value="14:00">02:00 PM</option>
  <option value="14:30">02:30 PM</option>
  <option value="15:00">03:00 PM</option>
  <option value="15:30">03:30 PM</option>
  <option value="16:00">04:00 PM</option>
  <option value="16:30">04:30 PM</option>
  <option value="17:00">05:00 PM</option>
  <option value="17:30">05:30 PM</option>
</select>


      <div class="modal-buttons">
        <button type="submit" class="btn primary-btn">Save</button>
        <button type="button" id="cancelBtn" class="btn cancel-btn">Cancel</button>
      </div>
    </form>
  </div>
</div>

<div id="receiptPopup" class="receipt-popup hidden" role="alertdialog" aria-modal="true">
  <div class="receipt-card">
    <div class="receipt-header">
      <span>Appointment Confirmation</span>
      <span id="receiptDate"></span>
    </div>
    <div class="receipt-body">
      <div class="receipt-title">
        <h2>Tails & Trails</h2>
        <p>Your appointment is confirmed!</p>
      </div>
      <div class="receipt-details">
        <p><strong>Pet Name:</strong> <span id="receiptPetName"></span></p>
        <p><strong>Service:</strong> <span id="receiptService"></span></p>
        <p><strong>Assigned Vet:</strong> <span id="receiptVet"></span></p>
        <p><strong>Date:</strong> <span id="receiptApptDate"></span></p>
        <p><strong>Time:</strong> <span id="receiptApptTime"></span></p>
        <p><strong>Status:</strong> <span id="receiptStatus">Pending</span></p>
      </div>
    </div>
    <div class="receipt-footer">
      <div class="barcode"></div>
      <button id="closeReceiptBtn" class="btn primary-btn">OK</button>
    </div>
  </div>
</div>

    <!-- Template for a hidden cancel button to be used in upcoming appointments' Action cell. -->
    <template id="cancel-template">
      <button class="btn cancel-btn hidden cancel-action" type="button" aria-hidden="true">Cancel</button>
    </template>

<script>
  // Logout handler: clear local/session storage and redirect to auth logout.
  (function(){
    var logoutBtn = document.getElementById('logoutBtn');
    if(logoutBtn){
      logoutBtn.addEventListener('click', function(){
        try{ localStorage.clear(); sessionStorage.clear(); }catch(e){ }
        window.location.href = '../../auth/logout.php';
      });
    }
  })();
</script>
<script src="../../assets/js/user/script.js"></script>
</body>
</html>
