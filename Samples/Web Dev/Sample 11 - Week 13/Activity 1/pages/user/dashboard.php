<?php

require_once __DIR__ . '/../../includes/session-check.php';


require_once __DIR__ . '/get_user.php';

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

<div class="dashboard-box">
  <div class="dashboard-container">

    <header class="dashboard-header">
      <div class="logo"><img src="../../assets/images/logo.png" alt="Tails & Trails Logo" class="logo-img"> <span>Tails & Trails</span></div>
      <div class="header-right" style="display:flex;align-items:center;gap:0.75rem;">
        <span id="welcomeText" style="font-weight:600;">Welcome, <?php echo htmlspecialchars($userFullName); ?></span>
        <button id="logoutBtn" class="btn" aria-label="Logout">Logout</button>
        <button id="addBtn" class="btn primary-btn" aria-label="Book New Appointment">+ Book New Appointment</button>
      </div>
    </header>

    <section class="appointments-section">
      <div class="section-header">
        <h2>Upcoming Appointments</h2>
        <div class="search-filter-bar">
          <input type="text" id="upcomingSearchInput" placeholder="Search upcoming appointments..." aria-label="Search Upcoming Appointments" class="search-input">
          <div class="filter-buttons">
            <button class="filter-btn active" data-status="all" aria-label="All Statuses">All</button>
            <button class="filter-btn" data-status="pending" aria-label="Filter Pending">Pending</button>
            <button class="filter-btn" data-status="confirmed" aria-label="Filter Confirmed">Confirmed</button>
          </div>
        </div>
      </div>
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
      <div class="section-header">
        <h2>Past Appointments</h2>
        <div class="search-filter-bar">
          <input type="text" id="pastSearchInput" placeholder="Search past appointments..." aria-label="Search Past Appointments" class="search-input">
          <div class="filter-buttons">
            <button class="filter-btn active" data-status="all" aria-label="All Statuses">All</button>
            <button class="filter-btn" data-status="completed" aria-label="Filter Completed">Completed</button>
            <button class="filter-btn" data-status="cancelled" aria-label="Filter Cancelled">Cancelled</button>
          </div>
        </div>
      </div>
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

      <label for="vet">Preferred Vet:</label>
      <select id="vet" name="vet">
        <option value="" selected>No preference</option>
      </select>

      <div class="date-time-section">
        <label for="date">Date:</label>
        <input type="text" id="date" name="date" placeholder="YYYY-MM-DD" required>

        <label for="time">Time:</label>
        <select id="time" name="time" required>
          <option value="" disabled selected>Select time</option>
        </select>
      </div>


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


    <template id="cancel-template">
      <button class="btn cancel-btn hidden cancel-action" type="button" aria-hidden="true">Cancel</button>
    </template>

<script>

  (function(){
    var logoutBtn = document.getElementById('logoutBtn');
    if(logoutBtn){
      logoutBtn.addEventListener('click', function(e){
        e.preventDefault();

        showLogoutConfirmation();
      });
    }
  })();


  (function(){

    window.history.pushState(null, null, window.location.href);


    window.addEventListener('popstate', function(){
      window.history.pushState(null, null, window.location.href);
    });
  })();
</script>


<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/components/booking-date-picker.js"></script>
<script src="../../assets/js/components/toast.js"></script>
<script src="../../assets/js/utils/appointment.js"></script>
<script src="../../assets/js/user/script.js"></script>
</body>
</html>
