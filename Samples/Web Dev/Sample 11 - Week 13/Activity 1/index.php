<?php
session_start();


if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'user';
    if ($role === 'admin') {
        header("Location: pages/admin/dashboard.php");
    } else {
        header("Location: pages/user/dashboard.php");
    }
    exit();
}


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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/booking-date-picker.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  <style>
    .invalid-date {
      border-color: #f44336 !important;
      background-color: #ffebee !important;
    }


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
      gap: 1rem;
    }
  </style>
</head>
<body style="margin: 0; padding: 0;">
  <header class="sticky-top" style="top: 0; z-index: 1020;">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="padding: 0.5rem 0;">
      <div class="container-lg">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#top" style="margin-bottom: 0;">
          <div class="logo-circle">
            <img src="assets/images/logo.png" alt="Logo" style="width: 50px; height: 50px; object-fit: contain;">
          </div>
          <span class="fw-bold" style="color: var(--secondary); font-size: 1.2rem;">Tails and Trails</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav" style="margin: 0 auto; gap: 3rem;">
            <li class="nav-item"><a class="nav-link" href="#about" style="text-transform: uppercase; font-weight: 600;">About Us</a></li>
            <li class="nav-item"><a class="nav-link" href="#services" style="text-transform: uppercase; font-weight: 600;">Services</a></li>
            <li class="nav-item"><a class="nav-link" href="#visit" style="text-transform: uppercase; font-weight: 600;">Visit Us</a></li>
            <li class="nav-item"><a class="nav-link" href="#newsletter" style="text-transform: uppercase; font-weight: 600;">Newsletter</a></li>
          </ul>
          <ul class="navbar-nav align-items-center gap-2">
            <li class="nav-item"><button class="btn btn-sm" style="background-color: var(--primary); color: white; border-radius: 20px; padding: 6px 18px;" onclick="window.location.href='auth/login.php'">Log In</button></li>
            <li class="nav-item"><button class="btn btn-sm" style="background-color: var(--secondary); color: white; border-radius: 20px; padding: 6px 18px;" id="bookNowNavBtn">Book Now</button></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <section class="hero" style="margin-top: 0; padding-top: 0; min-height: 100vh; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); display: flex; align-items: center; position: relative; overflow: hidden; margin-top: -70px; padding-top: 70px;">

    <div style="position: absolute; top: -50%; right: -10%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(255,255,255,0.15), transparent); border-radius: 50%; animation: float 8s ease-in-out infinite;"></div>
    <div style="position: absolute; bottom: -30%; left: -5%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(255,255,255,0.08), transparent); border-radius: 50%; animation: float 10s ease-in-out infinite 1s;"></div>

    <div class="container-lg" style="position: relative; z-index: 2;">
      <div class="row align-items-center g-5 py-5">
        <div class="col-lg-6">
          <div style="animation: slideInLeft 0.8s ease-out;">
            <span class="badge bg-white text-secondary fw-bold mb-3" style="padding: 8px 16px; font-size: 0.9rem;">Tails of Health & Happiness</span>
            <h1 class="display-2 fw-bold text-white mb-4" style="line-height: 1.15; letter-spacing: -1px;">Where Every Wag Tells a Story!</h1>
            <p class="fs-5 text-white mb-5" style="line-height: 1.8; opacity: 0.95; max-width: 500px;">
              Your pets deserve the best care. At Tails and Trails, we combine compassion with expertise to give your furry friends a second home.
            </p>
            <div class="d-flex gap-3 flex-wrap">
              <a href="#services" class="btn btn-light btn-lg fw-bold" style="color: var(--secondary); border-radius: 50px; padding: 14px 35px; box-shadow: 0 8px 20px rgba(0,0,0,0.15); transition: all 0.3s ease;">Explore Services</a>
              <button class="btn btn-outline-light btn-lg fw-bold" id="bookNowBtn" style="border-radius: 50px; padding: 14px 35px; border-width: 2px; transition: all 0.3s ease;">Book Now</button>
            </div>
          </div>
        </div>
        <div class="col-lg-6" style="animation: slideInRight 0.8s ease-out;">
          <div style="position: relative; display: flex; align-items: center; justify-content: center;">

            <div style="position: absolute; width: 420px; height: 420px; background: radial-gradient(circle, rgba(255,255,255,0.1), rgba(255,255,255,0.05)); border-radius: 50%; top: 50%; left: 50%; transform: translate(-50%, -50%);"></div>
            <img src="assets/images/catdog.png" alt="Happy pets at Tails and Trails" class="img-fluid" style="filter: drop-shadow(0 30px 60px rgba(0,0,0,0.25)); position: relative; z-index: 1; max-width: 100%; animation: float 4s ease-in-out infinite;">
          </div>
        </div>
      </div>
    </div>


    <svg style="position: absolute; bottom: 0; left: 0; right: 0; width: 100%; height: 80px;" preserveAspectRatio="none" viewBox="0 0 1200 80" xmlns="http://www.w3.org/2000/svg">
      <path d="M0,40 Q300,0 600,40 T1200,40 L1200,80 L0,80 Z" fill="white"></path>
    </svg>
  </section>

  <style>
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-30px); }
    }
  </style>

  <section class="py-5 bg-white" id="about" style="position: relative; padding-bottom: 100px !important;">
    <div class="container-lg">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <h2 class="h1 fw-bold mb-4" style="color: var(--secondary);">
            About Us
            <div style="height: 4px; width: 80px; background: var(--primary); border-radius: 2px; margin-top: 12px;"></div>
          </h2>
          <p class="fs-5 mb-4" style="color: var(--dark); line-height: 1.8;">
            At <strong>Tails and Trails</strong>, we believe pets are more than companions—they're family. Our journey began with a passion for creating a safe, welcoming place where pets receive the love and medical attention they deserve.
          </p>
          <p class="fs-5 mb-4" style="color: #666;">
            From routine check-ups and preventive care to specialized treatments, our team of experienced veterinarians and staff are dedicated to supporting every stage of your pet's life.
          </p>
          <div class="d-flex gap-3 flex-wrap">
            <div class="text-center" style="flex: 1; min-width: 120px;">
              <h3 class="fw-bold mb-2" style="color: var(--primary); font-size: 2rem;">50+</h3>
              <p style="color: #666; font-size: 0.9rem;">Expert Vets</p>
            </div>
            <div class="text-center" style="flex: 1; min-width: 120px;">
              <h3 class="fw-bold mb-2" style="color: var(--primary); font-size: 2rem;">10K+</h3>
              <p style="color: #666; font-size: 0.9rem;">Happy Pets</p>
            </div>
            <div class="text-center" style="flex: 1; min-width: 120px;">
              <h3 class="fw-bold mb-2" style="color: var(--primary); font-size: 2rem;">24/7</h3>
              <p style="color: #666; font-size: 0.9rem;">Emergency Care</p>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 40px; text-align: center; color: white; min-height: 300px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
              <div style="font-size: 4rem; margin-bottom: 20px;">🏥</div>
              <h4 style="font-size: 1.5rem; margin-bottom: 15px;">World-Class Veterinary Care</h4>
              <p style="font-size: 1rem; opacity: 0.95; max-width: 300px;">State-of-the-art facilities equipped with modern medical technology for your pet's wellness.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <svg style="position: absolute; bottom: -1px; left: 0; right: 0; width: 100%; height: 80px;" preserveAspectRatio="none" viewBox="0 0 1200 80" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="waveGradient1" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:rgba(103,197,187,0.08);stop-opacity:1" />
          <stop offset="100%" style="stop-color:rgba(44,95,111,0.08);stop-opacity:1" />
        </linearGradient>
      </defs>
      <path d="M0,40 Q300,0 600,40 T1200,40 L1200,80 L0,80 Z" fill="url(#waveGradient1)"></path>
    </svg>
  </section>

  <section class="services py-5" id="services" style="background: linear-gradient(135deg, rgba(103,197,187,0.08), rgba(44,95,111,0.08));">
    <div class="container-lg">
      <div class="text-center mb-5">
        <h2 class="h1 fw-bold mb-3" style="color: var(--secondary);">Our Services</h2>
        <div style="height: 4px; width: 80px; background: var(--primary); border-radius: 2px; margin: 0 auto 20px;"></div>
        <p class="fs-5" style="color: #666; max-width: 600px; margin: 0 auto;">Comprehensive veterinary care tailored to meet the unique needs of your beloved pets.</p>
      </div>
      <div class="row g-4">
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm h-100 text-center" style="transition: all 0.3s ease;">
            <div class="card-body p-4">
              <div style="font-size: 3rem; margin-bottom: 15px;">💉</div>
              <h5 class="card-title fw-bold mb-3" style="color: var(--secondary);">Checkups & Vaccinations</h5>
              <p style="color: #666; font-size: 0.95rem;">Preventive care and regular health screenings to keep your pet healthy.</p>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm h-100 text-center" style="transition: all 0.3s ease;">
            <div class="card-body p-4">
              <div style="font-size: 3rem; margin-bottom: 15px;">🦷</div>
              <h5 class="card-title fw-bold mb-3" style="color: var(--secondary);">Dental Care</h5>
              <p style="color: #666; font-size: 0.95rem;">Professional cleaning and oral health treatments for optimal pet wellness.</p>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm h-100 text-center" style="transition: all 0.3s ease;">
            <div class="card-body p-4">
              <div style="font-size: 3rem; margin-bottom: 15px;">⚕️</div>
              <h5 class="card-title fw-bold mb-3" style="color: var(--secondary);">Surgery & Emergency</h5>
              <p style="color: #666; font-size: 0.95rem;">Advanced surgical procedures and 24/7 emergency medical services.</p>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm h-100 text-center" style="transition: all 0.3s ease;">
            <div class="card-body p-4">
              <div style="font-size: 3rem; margin-bottom: 15px;">✨</div>
              <h5 class="card-title fw-bold mb-3" style="color: var(--secondary);">Grooming & Nutrition</h5>
              <p style="color: #666; font-size: 0.95rem;">Professional grooming and personalized nutrition guidance for your pet.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="contact-info py-5 bg-white" id="visit" style="position: relative; margin-top: 0; padding-top: 100px !important;">

    <svg style="position: absolute; top: -1px; left: 0; right: 0; width: 100%; height: 80px;" preserveAspectRatio="none" viewBox="0 0 1200 80" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="waveGradient" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:rgba(103,197,187,0.08);stop-opacity:1" />
          <stop offset="100%" style="stop-color:rgba(44,95,111,0.08);stop-opacity:1" />
        </linearGradient>
      </defs>
      <path d="M0,0 L1200,0 L1200,40 Q900,80 600,40 Q300,0 0,40 Z" fill="url(#waveGradient)"></path>
    </svg>
    <div class="container-lg">
      <div class="text-center mb-5">
        <h2 class="h1 fw-bold mb-3" style="color: var(--secondary);">Visit Us</h2>
        <div style="height: 4px; width: 80px; background: var(--primary); border-radius: 2px; margin: 0 auto 20px;"></div>
        <p class="fs-5" style="color: #666;">Find your nearest Tails and Trails location</p>
      </div>
      <div id="map" class="rounded shadow-sm mb-5" style="width: 100%; height: 450px; border-radius: 15px;"></div>
      <div class="row g-4">
        <div class="col-md-6">
          <div class="card border-0 shadow-md h-100" style="border-radius: 15px; overflow: hidden; background: white; border: 1px solid #e0e0e0; border-left: 3px solid var(--primary);">
            <div style="color: var(--dark); padding: 30px;">
              <h5 class="card-title fw-bold mb-4" style="font-size: 1.3rem; color: var(--secondary);">📞 Contact Information</h5>
              <div class="mb-3 d-flex gap-3">
                <div style="font-size: 1.5rem;">📱</div>
                <div>
                  <p style="margin: 0; font-weight: 500; font-size: 0.95rem; color: #888;">Phone</p>
                  <p style="margin: 0; font-size: 1rem; color: var(--dark); font-weight: 600;">09063979666</p>
                </div>
              </div>
              <div class="d-flex gap-3">
                <div style="font-size: 1.5rem;">✉️</div>
                <div>
                  <p style="margin: 0; font-weight: 500; font-size: 0.95rem; color: #888;">Email</p>
                  <p style="margin: 0; font-size: 1rem; color: var(--dark); font-weight: 600;">tailsandtrailsve@gmail.com</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card border-0 shadow-md h-100" style="border-radius: 15px; overflow: hidden; background: white; border: 1px solid #e0e0e0; border-left: 3px solid var(--secondary);">
            <div style="color: var(--dark); padding: 30px;">
              <h5 class="card-title fw-bold mb-4" style="font-size: 1.3rem; color: var(--secondary);">📍 Our Location</h5>
              <div class="mb-3 d-flex gap-3">
                <div style="font-size: 1.5rem;">🏥</div>
                <div>
                  <p style="margin: 0; font-weight: 500; font-size: 0.95rem; color: #888;">Address</p>
                  <p style="margin: 0; font-size: 1rem; color: var(--dark); font-weight: 600;">Carlos Q. Trinidad Ave, Salawag, Dasmarinas City</p>
                </div>
              </div>
              <div class="d-flex gap-3">
                <div style="font-size: 1.5rem;">👥</div>
                <div>
                  <p style="margin: 0; font-weight: 500; font-size: 0.95rem; color: #888;">Follow Us</p>
                  <p style="margin: 0; font-size: 1rem; color: var(--dark); font-weight: 600;">@TailsAndTrails</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="newsletter py-5" id="newsletter" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
    <div class="container-lg">
      <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
          <h2 class="h1 fw-bold mb-3">Stay Updated</h2>
          <p class="fs-5 mb-4" style="opacity: 0.95;">Get pet health tips, clinic updates, and special care guides delivered to your inbox.</p>
          <form class="d-flex gap-2 mb-3 flex-column flex-sm-row" id="newsletterForm">
            <input type="email" class="form-control form-control-lg" placeholder="Enter your email" required id="newsletterEmail" style="border: none; border-radius: 50px; padding: 12px 20px;">
            <button type="submit" class="btn btn-light btn-lg fw-bold" style="border-radius: 50px; white-space: nowrap;">Subscribe</button>
          </form>
          <p class="small" style="opacity: 0.8;">We respect your privacy. Unsubscribe anytime.</p>
        </div>
      </div>
    </div>
  </section>

  <footer class="py-4 text-white" style="background-color: var(--secondary);">
    <div class="container-lg text-center">
      <p class="mb-0">&copy; 2025 Tails and Trails. All rights reserved.</p>
    </div>
  </footer>

  <div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header border-bottom-0" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
          <h2 class="modal-title fw-bold text-white">Book Appointment</h2>
          <button type="button" class="btn-close btn-close-white" id="closeModal" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <form id="bookingForm">
            <div class="mb-3">
              <label for="fullname" class="form-label fw-500">Full Name</label>
              <input type="text" id="fullname" name="fullname" class="form-control form-control-lg" required>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label fw-500">Email</label>
              <input type="email" id="email" name="email" class="form-control form-control-lg" required>
            </div>
            <div class="mb-3">
              <label for="contact" class="form-label fw-500">Contact No.</label>
              <input type="text" id="contact" name="contact" class="form-control form-control-lg" required>
            </div>
            <div class="mb-3">
              <label for="pet_name" class="form-label fw-500">Pet Name</label>
              <input type="text" id="pet_name" name="pet_name" class="form-control form-control-lg" required>
            </div>
            <div class="mb-3">
              <label for="doctor" class="form-label fw-500">Preferred Vet</label>
              <select id="doctor" name="doctor" class="form-select form-select-lg">
                <option value="">No preference</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="service" class="form-label fw-500">Service</label>
              <select id="service" name="service" class="form-select form-select-lg" required>
                <option value="">Select service</option>
                <option value="Checkup">Checkup</option>
                <option value="Surgery">Surgery</option>
                <option value="Grooming">Grooming</option>
              </select>
            </div>
            <div class="date-time-section">
              <div class="mb-3">
                <label for="date" class="form-label fw-500">Date</label>
                <input type="date" id="date" name="date" class="form-control form-control-lg" required>
              </div>
              <div class="mb-3">
                <label for="time" class="form-label fw-500">Time</label>
                <select id="time" name="time" class="form-select form-select-lg" required>
                  <option value="">Select time</option>
                </select>
              </div>
            </div>
            <button type="submit" class="btn btn-lg w-100" style="background-color: var(--secondary); color: white; font-weight: 600;">Book Now</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/components/booking-date-picker.js"></script>
  <script src="assets/js/utils/appointment.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <script>
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
    const bookNowNavBtn = document.getElementById('bookNowNavBtn');
    const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
    const bookingForm = document.getElementById('bookingForm');

    bookNowBtn.addEventListener('click', () => {
      bookingModal.show();
    });

    bookNowNavBtn.addEventListener('click', () => {
      bookingModal.show();
    });
    bookingForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const formData = new FormData(bookingForm);

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


      if (!guestDatePicker) {
        guestDatePicker = new BookingDatePicker(guestDateInput, []);
      }


      guestAllDoctors = await fetchAvailableDoctors();
      populateGuestDoctorSelect();


      guestDoctorSelect.addEventListener('change', onGuestDoctorChange);
      guestDateInput.addEventListener('change', onGuestDateChange);
    }

    function populateGuestDoctorSelect() {
      guestDoctorSelect.innerHTML = '<option value="">No preference</option>';
      guestAllDoctors.forEach(doctor => {
        const option = document.createElement('option');
        option.value = doctor.name_without_prefix;
        option.textContent = doctor.name;
        guestDoctorSelect.appendChild(option);
      });
    }

    async function onGuestDoctorChange() {
      guestTimeSelect.innerHTML = '<option value="">Select time</option>';

      const selectedDoctor = guestDoctorSelect.value;
      const dateTimeSection = document.querySelector('.date-time-section');

      if (!selectedDoctor) {

        dateTimeSection.classList.remove('visible');
        guestDateInput.removeAttribute('required');
        guestTimeSelect.removeAttribute('required');

        const today = new Date().toISOString().split("T")[0];
        guestDateInput.setAttribute("min", today);
        guestDateInput.removeAttribute("max");
        return;
      }


      dateTimeSection.classList.add('visible');
      guestDateInput.setAttribute('required', 'required');
      guestTimeSelect.setAttribute('required', 'required');


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

        const response = await fetch(`pages/admin/api_doctors_public.php?action=getBookedTimes&date=${selectedDate}&doctor=${selectedDoctorName}`);
        const bookedData = await response.json();
        const bookedTimes = bookedData.data || [];


        const doctor = guestAllDoctors.find(d => d.name_without_prefix === selectedDoctorName);
        if (doctor && doctor.available_times) {
          const timeSlots = generateTimeSlots(doctor.available_times);
          availableTimeSlots = timeSlots.filter(slot => !bookedTimes.includes(slot));
        }
      } else {

        availableTimeSlots = generateTimeSlots(['8-17']);
      }


      availableTimeSlots.forEach(time => {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = formatTime(time);
        guestTimeSelect.appendChild(option);
      });
    }


    function initializeMap() {
      const clinicLat = 14.3451;
      const clinicLng = 120.9661;


      const map = L.map('map').setView([clinicLat, clinicLng], 17);


      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
      }).addTo(map);


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


      marker.bindPopup('<strong>Tails and Trails Clinic</strong><br>Carlos Q. Trinidad Ave, Salawag, Dasmarinas City').openPopup();
    }

    initializeGuestBooking();
    initializeMap();
  </script>
</body>
</html>
