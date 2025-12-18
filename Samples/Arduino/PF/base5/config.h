/*
 * Configuration File
 * Pin definitions and constants
 */

#ifndef CONFIG_H
#define CONFIG_H

// Display pins
#define TFT_CS   38
#define TFT_DC   39
#define TFT_RST  40
#define TFT_MOSI 35
#define TFT_MISO 37
#define TFT_CLK  36
#define TFT_LED  41

// RTC pins
#define RTC_CLK  18
#define RTC_DAT  19
#define RTC_RST  20

// Button pins (pull-down)
#define BTN1     12
#define BTN2     11
#define BTN3     10

// Buzzer pin
#define BUZZER   4

// Tilt switch pin
#define TILT_PIN 6

// Feed system pins
#define SERVO_PIN 42
#define RELAY_PIN 13

// HX711 Load Cell pins
#define HX711_DT  16
#define HX711_SCK 17

// Timing constants
const unsigned long debounceDelay = 50;
const unsigned long semiLongPressDuration = 1000;  // 1 second for cursor movement
const unsigned long longPressDuration = 3000;  // 3 seconds for save
const unsigned long doublePressWindow = 500;   // 500ms window for double press
const unsigned long bowlIconDuration = 1000;   // Show for 1 second
const unsigned long normalReturnDelay = 2000;  // 2 seconds
const unsigned long disturbedRecognitionDelay = 1000;  // 1 second
const unsigned long disturbedBlinkInterval = 500;  // Blink every 500ms
const unsigned long serverUpdateInterval = 30000;  // Send updates every 30 seconds
const unsigned long commandCheckInterval = 3000;  // Check for commands every 3 seconds
const unsigned long weightUpdateInterval = 200;  // Update weight every 200ms

// Scaling mode constants
const float maxCapacity = 20000.0;  // Maximum capacity in grams (20kg load cell)
const float calibrationFactor = 80.0;

// WiFi configuration constants
const char* AP_SSID = "Bites_and_Bowls";  // Default AP SSID
const char* AP_PASSWORD = "";  // Open network (no password)
const unsigned long wifiConnectTimeout = 10000;  // 10 seconds

// Server configuration - CHANGE THIS TO YOUR COMPUTER'S LOCAL IP ADDRESS
// Find your IP: Open Command Prompt and type: ipconfig
// Look for "IPv4 Address" under your WiFi adapter
const char* SERVER_IP = "192.168.1.100";  // CHANGE THIS!
const int SERVER_PORT = 80;  // Default HTTP port for XAMPP/WAMP
const char* API_KEY = "your_secret_hardware_key_12345";  // Must match hardware_update.php

// API endpoints
const char* API_HARDWARE_UPDATE = "/webapp/api/hardware_update.php";
const char* API_GET_SCHEDULES = "/webapp/api/get_active_schedules.php";
const char* API_DEVICE_STATUS = "/webapp/api/get_device_status.php";

// Data sending intervals
const unsigned long heartbeatInterval = 15000;  // Send heartbeat every 15 seconds
const unsigned long weightUpdateInterval_Server = 5000;  // Send weight updates every 5 seconds

// Scheduling mode constants
const int minFoodRounds = 1;
const int maxFoodRounds = 5;

#endif // CONFIG_H
