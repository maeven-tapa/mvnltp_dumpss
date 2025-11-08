<?php
session_start();

// If user is already logged in, redirect them to their appropriate dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'user';
    if ($role === 'admin') {
        header("Location: pages/admin/dashboard.php");
    } else {
        header("Location: pages/user/dashboard.php");
    }
    exit();
}

// index.php — serves the homepage on GET and handles newsletter POST subscriptions on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $host = 'localhost';
    $username = 'root';
    $password = 'root';
    $database = 'db_trailsandtails_midterm';

    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection error']);
        exit();
    }

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    if ($email === '') {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        $conn->close();
        exit();
    }

    // Check if email already exists
    $checkQuery = $conn->prepare("SELECT id FROM tbl_newsletter WHERE email = ?");
    $checkQuery->bind_param('s', $email);
    $checkQuery->execute();
    $result = $checkQuery->get_result();
    if ($result && $result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This email is already subscribed to our newsletter!']);
        $checkQuery->close();
        $conn->close();
        exit();
    }

    $insertQuery = $conn->prepare("INSERT INTO tbl_newsletter (email) VALUES (?)");
    $insertQuery->bind_param('s', $email);
    if ($insertQuery->execute()) {
        echo json_encode(['success' => true, 'message' => 'Successfully subscribed to our newsletter!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error subscribing. Please try again.']);
    }
    $insertQuery->close();
    $checkQuery->close();
    $conn->close();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tails and Trails - Veterinary Clinic</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/booking-date-picker.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  <style>
    .invalid-date {
      border-color: #f44336 !important;
      background-color: #ffebee !important;
    }

    /* Enhanced Date Picker Styling */
    input[type="date"] {
      cursor: pointer !important;
      transition: all 0.3s ease !important;
      border: 2px solid #ddd !important;
      padding: 8px 10px !important;
      border-radius: 8px !important;
      font-size: 1em !important;
    }

    input[type="date"]:hover {
      border-color: #67C5BB !important;
      box-shadow: 0 0 4px rgba(103, 197, 187, 0.3) !important;
    }

    input[type="date"]:focus {
      outline: none !important;
      border-color: #67C5BB !important;
      box-shadow: 0 0 0 3px rgba(103, 197, 187, 0.1) !important;
    }

    input[type="date"].valid-date {
      background-color: #e8f5e9 !important;
      border-color: #4caf50 !important;
      box-shadow: 0 0 8px rgba(76, 175, 80, 0.4) !important;
    }

    input[type="date"].invalid-date {
      background-color: #ffebee !important;
      border-color: #f44336 !important;
      box-shadow: 0 0 8px rgba(244, 67, 54, 0.4) !important;
    }

    input[type="date"].date-picker-active {
      box-shadow: 0 0 10px rgba(103, 197, 187, 0.6) !important;
      border-color: #67C5BB !important;
    }

    /* Hide date and time sections by default */
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
      gap: 1rem;
    }
  </style>
</head>
<body>
  <header>
    <nav>
      <div class="logo-section">
        <div class="logo-circle">
          <img src="assets/images/logo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <div class="logo-text">Tails and Trails</div>
      </div>
      <div class="menu-toggle" id="menuToggle">
        <span></span>
        <span></sp
        <span></span>
      </div>
      <ul class="nav-links" id="navLinks">
        <li><a href="#about">About Us</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#visit">Visit Us</a></li>
        <li><a href="#newsletter">Newsletter</a></li>
  <li><button class="btn-login" onclick="window.location.href='auth/login.php'">Log In</button></li>
      </ul>
    </nav>
  </header>

  <section class="hero">
    <div class="hero-container">
      <div class="hero-content">
        <h1>Where every wait leads to wagging tails!</h1>
        <p>At Tails and Trails, we understand that pets are family. Our dedicated team is here to provide the best care for your furry friends.</p>
        <div class="hero-buttons">
          <a href="#services" class="btn btn-primary">Explore Services</a>
          <button class="btn btn-secondary" id="bookNowBtn">Book Now</button>
        </div>
      </div>
      <div class="hero-image">
        <img src="assets/images/catdog.png" alt="Happy pets at Tails and Trails">
      </div>
    </div>
  </section>

  <section class="about" id="about">
    <div class="about-container">
      <h2 class="section-title">About Us</h2>
      <p class="about-text">
        At <strong>Tails and Trails</strong>, we believe pets are more than companions—they're family. Our journey began with a passion for creating a safe, welcoming place where pets receive the love and medical attention they deserve. From routine check-ups and preventive care to specialized treatments, our team of experienced veterinarians and staff are dedicated to supporting every stage of your pet's life.
      </p>
    </div>
  </section>

  <section class="services" id="services">
    <div class="services-container">
      <h2 class="section-title" style="text-align: center;">Services</h2>
      <div class="services-grid">
        <div class="service-card">
          <h3>Services</h3>
          <ul>
            <li>Checkups & Vaccinations</li>
            <li>Dental Care</li>
            <li>Surgery & Emergency</li>
            <li>Grooming & Nutrition Advice</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <section class="contact-info" id="visit">
    <div class="contact-container">
      <h2 class="section-title" style="text-align: center;">Visit Us</h2>
      <p class="tagline" style="text-align: center; font-size: 1.1em; color: #67C5BB; margin-bottom: 2rem; font-weight: 500;">
        <strong>Conveniently Located</strong><br>Find your nearest Tails and Trails location.
      </p>
      <div id="map" style="width: 100%; height: 400px; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);"></div>
      <div class="info-grid">
        <div class="info-card">
          <h3>Contact</h3>
          <ol>
            <li>Call us at 09063979666</li>
            <li>Email: tailsandtrailsve@gmail</li>
          </ol>
        </div>
        <div class="info-card">
          <h3>Visit Us</h3>
          <ol>
            <li>Carlos Q. Trinidad Avenu, Salawag, Dasmarinas City</li>
            <li>Follow us: Tails and Tails</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <section class="newsletter" id="newsletter">
    <div class="newsletter-container">
      <h2 class="section-title">Newsletter</h2>
      <p>Get pet health tips, clinic updates, and special care guides.</p>
      <form class="newsletter-form" id="newsletterForm">
        <input type="email" class="newsletter-input" placeholder="you@example.com" required id="newsletterEmail">
        <button type="submit" class="btn-book-now">Confirm</button>
      </form>
      <p class="newsletter-note">We respect your privacy. Unsubscribe anytime.</p>
    </div>
  </section>

  <footer>
    <p>&copy; 2025 Tails and Trails. All rights reserved.</p>
  </footer>

  <div class="modal" id="bookingModal">
    <div class="modal-content">
      <button class="close-btn" id="closeModal">×</button>
      <h2 class="modal-title">Book Appointment</h2>
      <form id="bookingForm">
        <div class="form-group">
          <label for="fullname">Full Name</label>
          <input type="text" id="fullname" name="fullname" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="contact">Contact No.</label>
          <input type="text" id="contact" name="contact" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="pet_name">Pet Name</label>
          <input type="text" id="pet_name" name="pet_name" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="doctor">Preferred Vet</label>
          <select id="doctor" name="doctor" class="form-control">
            <option value="">No preference</option>
          </select>
        </div>
        <div class="form-group">
          <label for="service">Service</label>
          <select id="service" name="service" class="form-control" required>
            <option value="">Select service</option>
            <option value="Checkup">Checkup</option>
            <option value="Surgery">Surgery</option>
            <option value="Grooming">Grooming</option>
          </select>
        </div>
        <div class="date-time-section">
          <div class="form-group">
            <label for="date">Date</label>
            <input type="date" id="date" name="date" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="time">Time</label>
            <select id="time" name="time" class="form-control" required>
              <option value="">Select time</option>
            </select>
          </div>
        </div>
        <!-- symptoms and pet gender removed per design; simplified guest booking -->
        <button type="submit" class="btn-book-now" style="width: 100%; margin-top: 1rem;">Book Now</button>
      </form>
    </div>
  </div>

  <script src="assets/js/components/booking-date-picker.js"></script>
  <script src="assets/js/utils/appointment.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <script>
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');
    menuToggle.addEventListener('click', () => {
      navLinks.classList.toggle('active');
    });
    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        navLinks.classList.remove('active');
      });
    });

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });

    const bookNowBtn = document.getElementById('bookNowBtn');
    const bookingModal = document.getElementById('bookingModal');
    const closeModal = document.getElementById('closeModal');
    const bookingForm = document.getElementById('bookingForm');

    bookNowBtn.addEventListener('click', () => {
      bookingModal.style.display = 'flex';
    });
    closeModal.addEventListener('click', () => {
      bookingModal.style.display = 'none';
    });
    bookingModal.addEventListener('click', (e) => {
      if (e.target === bookingModal) {
        bookingModal.style.display = 'none';
      }
    });
    bookingForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const formData = new FormData(bookingForm);
      // send booking to guest_book.php to persist into tbl_appointments
      fetch('includes/guest_book.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          alert('Appointment booked successfully! Reference ID: ' + (data.appointment_ref ?? ''));
          bookingForm.reset();
          bookingModal.style.display = 'none';
        } else {
          alert('Booking failed: ' + (data.message || 'Unknown error'));
        }
      })
      .catch(err => {
        console.error(err);
        alert('Error connecting to server. Please try again.');
      });
    });

    const newsletterForm = document.getElementById('newsletterForm');
    const newsletterEmail = document.getElementById('newsletterEmail');
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const email = newsletterEmail.value.trim();
      if (email) {
        // Send to PHP backend (this same file)
        const formData = new FormData();
        formData.append('email', email);
        fetch('index.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          alert(data.message);
          if (data.success) {
            newsletterEmail.value = '';
          }
        })
        .catch(error => {
          alert('Error subscribing. Please try again.');
        });
      }
    });

    window.addEventListener('scroll', () => {
      const header = document.querySelector('header');
      const currentScroll = window.pageYOffset;
      if (currentScroll > 100) {
        header.style.padding = '0.2rem 0';
      } else {
        header.style.padding = '0.8rem 0';
      }
    });

    // Initialize guest booking form with doctors and advanced filtering
    let guestAllDoctors = [];
    let guestAllAppointments = [];
    let guestDatePicker = null;
    const guestDoctorSelect = document.getElementById('doctor');
    const guestDateInput = document.getElementById('date');
    const guestTimeSelect = document.getElementById('time');
    const guestBookingForm = document.getElementById('bookingForm');

    async function initializeGuestBooking() {
      const today = new Date().toISOString().split("T")[0];
      guestDateInput.setAttribute("min", today);

      // Initialize custom date picker
      if (!guestDatePicker) {
        guestDatePicker = new BookingDatePicker(guestDateInput, []);
      }

      // Load all doctors
      guestAllDoctors = await fetchAvailableDoctors();
      populateGuestDoctorSelect();

      // Listen for changes
      guestDoctorSelect.addEventListener('change', onGuestDoctorChange);
      guestDateInput.addEventListener('change', onGuestDateChange);
    }

    function populateGuestDoctorSelect() {
      guestDoctorSelect.innerHTML = '<option value="">No preference</option>';
      guestAllDoctors.forEach(doctor => {
        const option = document.createElement('option');
        option.value = doctor.name_without_prefix;  // Store name without prefix for database
        option.textContent = doctor.name;  // Display with Dr. prefix
        guestDoctorSelect.appendChild(option);
      });
    }

    async function onGuestDoctorChange() {
      guestTimeSelect.innerHTML = '<option value="">Select time</option>';

      const selectedDoctor = guestDoctorSelect.value;
      const dateTimeSection = document.querySelector('.date-time-section');

      if (!selectedDoctor) {
        // No doctor selected, hide date and time section
        dateTimeSection.classList.remove('visible');
        guestDateInput.removeAttribute('required');
        guestTimeSelect.removeAttribute('required');
        // Clear date restrictions
        const today = new Date().toISOString().split("T")[0];
        guestDateInput.setAttribute("min", today);
        guestDateInput.removeAttribute("max");
        return;
      }

      // Doctor selected, show date and time section
      dateTimeSection.classList.add('visible');
      guestDateInput.setAttribute('required', 'required');
      guestTimeSelect.setAttribute('required', 'required');

      // Find doctor data and update date picker with available dates
      const doctor = guestAllDoctors.find(d => d.name_without_prefix === selectedDoctor);
      if (doctor && doctor.available_dates && guestDatePicker) {
        const availableDates = getAvailableDatesForDoctor(doctor.available_dates);
        guestDatePicker.updateAvailableDates(availableDates);
      }

      onGuestDateChange();
    }

    async function onGuestDateChange() {
      guestTimeSelect.innerHTML = '<option value="">Select time</option>';

      const selectedDate = guestDateInput.value;
      if (!selectedDate) return;

      const selectedDoctorName = guestDoctorSelect.value;

      let availableTimeSlots = [];

      if (selectedDoctorName) {
        // Fetch booked times for this doctor and date
        const response = await fetch(`pages/admin/api_doctors_public.php?action=getBookedTimes&date=${selectedDate}&doctor=${selectedDoctorName}`);
        const bookedData = await response.json();
        const bookedTimes = bookedData.data || [];

        // Get doctor's available times
        const doctor = guestAllDoctors.find(d => d.name_without_prefix === selectedDoctorName);
        if (doctor && doctor.available_times) {
          const timeSlots = generateTimeSlots(doctor.available_times);
          availableTimeSlots = timeSlots.filter(slot => !bookedTimes.includes(slot));
        }
      } else {
        // No doctor selected - show default available hours (8 AM to 5 PM)
        availableTimeSlots = generateTimeSlots(['8-17']);
      }

      // Populate time dropdown
      availableTimeSlots.forEach(time => {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = formatTime(time);
        guestTimeSelect.appendChild(option);
      });
    }

    // Initialize map
    function initializeMap() {
      const clinicLat = 14.3451;
      const clinicLng = 120.9661;
      
      // Create map centered at clinic location
      const map = L.map('map').setView([clinicLat, clinicLng], 17);
      
      // Add OpenStreetMap tiles
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
      }).addTo(map);
      
      // Add custom marker with clinic icon
      const marker = L.marker([clinicLat, clinicLng], {
        icon: L.icon({
          iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
          shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
          iconSize: [25, 41],
          iconAnchor: [12, 41],
          popupAnchor: [1, -34],
          shadowSize: [41, 41]
        })
      }).addTo(map);
      
      // Add popup to marker
      marker.bindPopup('<strong>Tails and Trails Clinic</strong><br>Carlos Q. Trinidad Ave, Salawag, Dasmarinas City').openPopup();
    }

    initializeGuestBooking();
    initializeMap();
  </script>
</body>
</html>
