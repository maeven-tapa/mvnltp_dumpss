/*
 * ST7789 Display Configuration Template
 * 
 * Display Pins:
 * MOSI -> GPIO35, MISO -> GPIO37, SCK -> GPIO36
 * CS -> GPIO38, DC -> GPIO39, RST -> GPIO40, LED -> GPIO41
 * 
 * RTC Pins (DS1302):
 * CLK -> GPIO18, DAT -> GPIO19, RST -> GPIO20
 * 
 * Button Pins (Pull-down):
 * BTN1 -> GPIO10, BTN2 -> GPIO11, BTN3 -> GPIO12
 * 
 * Buzzer Pin:
 * BUZZER -> GPIO4 (Active Buzzer)
 * 
 * Libraries: Adafruit_ST7789, Adafruit_GFX, ThreeWire, RtcDS1302
 */

// ============================================================================
// FIRMWARE VERSION
// ============================================================================
const char* FIRMWARE_VERSION = "1.0";

#include "globals.h"
#include "splash_image.h"

// Initialize display object
Adafruit_ST7789 tft = Adafruit_ST7789(TFT_CS, TFT_DC, TFT_MOSI, TFT_CLK, TFT_RST);

// Initialize RTC objects
ThreeWire myWire(RTC_DAT, RTC_CLK, RTC_RST);
RtcDS1302<ThreeWire> Rtc(myWire);

// Initialize Servo object
Servo feedServo;

// Initialize HX711 object
HX711 scale;

// Global variable definitions
Mode currentMode = HOME_MODE;

// Button state tracking
unsigned long lastDebounceTime[3] = {0, 0, 0};
bool lastButtonState[3] = {LOW, LOW, LOW};

// Long press tracking for Button 1
unsigned long btn1PressStartTime = 0;
bool btn1LongPressTriggered = false;

// Double press tracking for Button 1
unsigned long lastBtn1PressTime = 0;
int btn1PressCount = 0;

// Feed rounds state
int totalRounds = 1;
bool feedingComplete = false;
bool showBowlIcon = false;
unsigned long bowlIconStartTime = 0;

// Tilt switch state
bool lastTiltState = LOW;
unsigned long tiltReturnTime = 0;
bool waitingForNormalReturn = false;

// Disturbed recognition timing
unsigned long tiltHighStartTime = 0;
bool tiltHighTimerActive = false;

// Disturbed mode display state
bool disturbedBlinkState = false;
unsigned long lastDisturbedBlink = 0;

// Scaling mode state
float currentWeight = 0.0;
unsigned long lastWeightUpdate = 0;

// Long press tracking for Button 2
unsigned long btn2PressStartTime = 0;
bool btn2LongPressTriggered = false;

// Double press tracking for Button 2
unsigned long lastBtn2PressTime = 0;
int btn2PressCount = 0;

// Long press tracking for Button 3
unsigned long btn3PressStartTime = 0;
bool btn3LongPressTriggered = false;

// WiFi state variables
WebServer server(80);
Preferences preferences;
bool wifiConnected = false;
String savedSSID = "";
String savedPassword = "";
String savedServerIP = "";
int wifiRSSI = 0;
unsigned long wifiConnectionStartTime = 0;
bool wifiConnectionInProgress = false;

// Double press tracking for Button 1 (exit WiFi Mode)
unsigned long lastBtn1WifiPressTime = 0;
int btn1WifiPressCount = 0;

// Long press tracking for Button 2 (WiFi reset)
unsigned long btn2WifiPressStartTime = 0;
bool btn2WifiLongPressTriggered = false;

// Server communication state
unsigned long lastHeartbeat = 0;
bool serverConnected = false;
float lastSentWeight = 0.0;
unsigned long lastServerUpdate = 0;
unsigned long lastCommandCheck = 0;

// Scheduling mode state
Schedule feedSchedule = {false, 0, 0, 0, 0};

// Scheduling mode tracking - simultaneous button press
unsigned long btn1And2PressStartTime = 0;
bool btn1And2LongPressTriggered = false;
bool btn1And2BothPressed = false;

// Set Time Mode state
int selectedTimeField = 0;
int tempHours = 0;
int tempMinutes = 0;
int tempSeconds = 0;

// Food Rounds Mode state
int tempFoodRounds = 1;

// Double press tracking for Button 1 (exit Scheduling Mode)
unsigned long lastBtn1SchedulingPressTime = 0;
int btn1SchedulingPressCount = 0;

// Long press tracking for Button 3 (reset schedule)
unsigned long btn3SchedulingPressStartTime = 0;
bool btn3SchedulingLongPressTriggered = false;

// Open Panel Status state
bool openPanelStatus = false;
unsigned long btn1And3PressStartTime = 0;
bool btn1And3LongPressTriggered = false;
bool btn1And3BothPressed = false;

// Interval feeding tracking
unsigned long lastFeedTime = 0;
unsigned long feedIntervalMillis = 0;

// ============================================================================
// SAVE/LOAD SETTINGS FUNCTIONS
// ============================================================================

// Save schedule to Preferences
void saveSchedule() {
  preferences.begin("device-settings", false);
  preferences.putBool("sched_isset", feedSchedule.isSet);
  preferences.putInt("sched_hours", feedSchedule.hours);
  preferences.putInt("sched_mins", feedSchedule.minutes);
  preferences.putInt("sched_secs", feedSchedule.seconds);
  preferences.putInt("sched_rounds", feedSchedule.foodRounds);
  preferences.end();
  Serial.println("Schedule saved to flash memory");
}

// Load schedule from Preferences
void loadSchedule() {
  preferences.begin("device-settings", true);
  feedSchedule.isSet = preferences.getBool("sched_isset", false);
  feedSchedule.hours = preferences.getInt("sched_hours", 0);
  feedSchedule.minutes = preferences.getInt("sched_mins", 0);
  feedSchedule.seconds = preferences.getInt("sched_secs", 0);
  feedSchedule.foodRounds = preferences.getInt("sched_rounds", 0);
  preferences.end();
  
  if (feedSchedule.isSet) {
    // Recalculate interval
    feedIntervalMillis = (unsigned long)feedSchedule.hours * 3600000UL +
                        (unsigned long)feedSchedule.minutes * 60000UL +
                        (unsigned long)feedSchedule.seconds * 1000UL;
    Serial.println("Schedule loaded from flash memory");
    Serial.print("Feed every: ");
    Serial.print(feedSchedule.hours);
    Serial.print("h ");
    Serial.print(feedSchedule.minutes);
    Serial.print("m ");
    Serial.print(feedSchedule.seconds);
    Serial.print("s - ");
    Serial.print(feedSchedule.foodRounds);
    Serial.println(" rounds");
  }
}

// Save current mode to Preferences
void saveCurrentMode() {
  preferences.begin("device-settings", false);
  preferences.putInt("current_mode", (int)currentMode);
  preferences.end();
}

// Load current mode from Preferences
void loadCurrentMode() {
  preferences.begin("device-settings", true);
  int savedMode = preferences.getInt("current_mode", HOME_MODE);
  currentMode = (Mode)savedMode;
  preferences.end();
  Serial.print("Loaded mode: ");
  Serial.println((int)currentMode);
}

// Save open panel status to Preferences
void saveOpenPanelStatus() {
  preferences.begin("device-settings", false);
  preferences.putBool("open_panel", openPanelStatus);
  preferences.end();
}

// Load open panel status from Preferences
void loadOpenPanelStatus() {
  preferences.begin("device-settings", true);
  openPanelStatus = preferences.getBool("open_panel", false);
  preferences.end();
  Serial.print("Open panel status: ");
  Serial.println(openPanelStatus ? "OPEN" : "CLOSED");
}

// Load all settings from Preferences
void loadSettings() {
  Serial.println("Loading saved settings from flash memory...");
  loadSchedule();
  loadCurrentMode();
  loadOpenPanelStatus();
  Serial.println("Settings loaded successfully");
}

// Boot splash screen with fun buzzer melody
void showBootSplash() {
  // Fill screen with white background
  tft.fillScreen(ST77XX_WHITE);
  
  // Define colors
  uint16_t brownColor = tft.color565(111, 78, 55);      // #6F4E37
  
  // Calculate position to center the image horizontally
  int imageX = (320 - SPLASH_WIDTH) / 2;
  int imageY = 20;  // Top margin
  
  // Draw the splash image from PROGMEM using fast bulk transfer
  tft.drawRGBBitmap(imageX, imageY, splashImage, SPLASH_WIDTH, SPLASH_HEIGHT);
  
  // Play fun boot melody with rhythm
  // Note frequencies (approximate)
  int melody[] = {
    523,  // C5
    587,  // D5
    659,  // E5
    698,  // F5
    784,  // G5
    784,  // G5
    698   // F5
  };
  
  int durations[] = {
    150,  // Short
    150,  // Short
    150,  // Short
    100,  // Very short
    200,  // Medium
    200,  // Medium
    300   // Long
  };
  
  int pauses[] = {
    50,   // Short pause
    50,
    50,
    30,
    100,
    100,
    0     // No pause after last note
  };
  
  // Play the melody
  for (int i = 0; i < 7; i++) {
    tone(BUZZER, melody[i], durations[i]);
    delay(durations[i]);
    noTone(BUZZER);
    delay(pauses[i]);
  }
  
  // Hold splash screen for remaining time (total 3 seconds)
  delay(1200);
  
  // Fade effect - quickly flash to indicate loading complete
  tft.fillRect(75, 180, 170, 20, ST77XX_WHITE);
  tft.setTextColor(brownColor);
  tft.setTextSize(2);
  tft.setCursor(105, 180);
  tft.print("Ready!");
  
  // Short success beep
  tone(BUZZER, 1000, 100);
  delay(200);
  noTone(BUZZER);
}

void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("Initializing Display and RTC...");
  
  // Initialize display backlight
  pinMode(TFT_LED, OUTPUT);
  digitalWrite(TFT_LED, HIGH);  // Turn on backlight
  
  // Initialize button pins
  pinMode(BTN1, INPUT);
  pinMode(BTN2, INPUT);
  pinMode(BTN3, INPUT);
  
  // Initialize buzzer pin
  pinMode(BUZZER, OUTPUT);
  digitalWrite(BUZZER, LOW);
  
  // Initialize tilt switch pin
  pinMode(TILT_PIN, INPUT);
  
  // Initialize feed system
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, LOW);
  
  // Initialize HX711 Load Cell
  scale.begin(HX711_DT, HX711_SCK);
  scale.set_scale(calibrationFactor);  // Set calibration factor
  
  // Check if we need to tare
  preferences.begin("device-settings", false);
  bool hasBeenTared = preferences.getBool("scale_tared", false);
  
  if (!hasBeenTared) {
    // First time setup - tare the scale
    scale.tare();
    preferences.putBool("scale_tared", true);
    Serial.println("First time tare completed");
  } else {
    // Load saved tare offset if available
    long savedOffset = preferences.getLong("tare_offset", 0);
    if (savedOffset != 0) {
      scale.set_offset(savedOffset);
      Serial.print("Loaded tare offset: ");
      Serial.println(savedOffset);
    }
    
    // Check current weight - if below 20g, auto-tare
    float currentWeight = scale.get_units(5);
    if (abs(currentWeight) < 20.0) {
      Serial.println("Container empty - auto taring");
      scale.tare();
      preferences.putLong("tare_offset", scale.get_offset());
    }
  }
  preferences.end();
  
  Serial.println("HX711 Load Cell initialized");
  
  // Initialize ST7789 display
  tft.init(240, 320, SPI_MODE0);
  tft.invertDisplay(false);
  tft.setRotation(1);  // Landscape mode (320x240)
  delay(100);
  
  // Show boot splash screen with fun melody
  showBootSplash();
  
  // Initialize servo after splash screen
  feedServo.attach(SERVO_PIN);
  feedServo.write(0);  // Initial position
  
  // Initialize RTC
  Rtc.Begin();
  
  RtcDateTime compiled = RtcDateTime(__DATE__, __TIME__);
  
  if (!Rtc.IsDateTimeValid()) {
    Serial.println("RTC lost confidence in the DateTime!");
    Serial.println("Resetting to 12:00:00 AM");
    // Reset to 12:00:00 AM (midnight) with current date
    RtcDateTime midnight = RtcDateTime(compiled.Year(), compiled.Month(), compiled.Day(), 0, 0, 0);
    Rtc.SetDateTime(midnight);
  }
  
  if (!Rtc.GetIsRunning()) {
    Serial.println("RTC was not actively running, starting now");
    Rtc.SetIsRunning(true);
  }
  
  // Auto-sync with compile time when uploaded
  RtcDateTime now = Rtc.GetDateTime();
  if (now < compiled) {
    Serial.println("RTC time is older than compile time. Auto-syncing...");
    Rtc.SetDateTime(compiled);
    Serial.print("Time synced to: ");
    Serial.print(__DATE__);
    Serial.print(" ");
    Serial.println(__TIME__);
  }
  
  Rtc.SetIsWriteProtected(false);
  
  Serial.println("Display and RTC Ready!");
  
  // Initialize WiFi
  initWiFi();
  
  // Load saved settings from Preferences
  loadSettings();
  
  // Draw initial UI
  drawClockUI();
}

void loop() {
  static unsigned long lastUpdate = 0;
  
  // Check WiFi connection status (non-blocking) in all modes
  checkWiFiConnection();
  
  // Update weight reading every 200ms (for all modes)
  if (millis() - lastWeightUpdate >= weightUpdateInterval) {
    lastWeightUpdate = millis();
    if (scale.is_ready()) {
      currentWeight = scale.get_units(5);  // Average of 5 readings
      if (currentWeight < 0) currentWeight = 0;  // Prevent negative weights
    }
  }
  
  // Check tilt switch first (highest priority) - UNLESS Open Panel Status is active
  if (!openPanelStatus) {
    checkTiltSwitch();
  }
  
  // Check if it's time for scheduled feeding (only in HOME_MODE and not disturbed and panel not open)
  if (currentMode == HOME_MODE && feedSchedule.isSet && !openPanelStatus) {
    unsigned long currentTime = millis();
    
    // Check if interval has passed
    if (currentTime - lastFeedTime >= feedIntervalMillis) {
      // Time to feed!
      lastFeedTime = currentTime;
      
      // Show bowl icon and update screen FIRST
      showBowlIcon = true;
      drawClockUI();
      
      // Small delay to ensure display updates
      delay(100);
      
      // Buzz to indicate feeding
      digitalWrite(BUZZER, HIGH);
      delay(300);
      digitalWrite(BUZZER, LOW);
      
      Serial.print("Auto-feeding triggered! Rounds: ");
      Serial.println(feedSchedule.foodRounds);
      
      // Execute feeding sequence
      executeFeedingSequence(feedSchedule.foodRounds);
      
      // Remove icon after feeding completes
      showBowlIcon = false;
      drawClockUI();
    }
  }
  
  if (currentMode == HOME_MODE) {
    // Update every second
    if (millis() - lastUpdate >= 1000) {
      lastUpdate = millis();
      updateTime();
    }
    
    // Check for bowl icon timeout
    if (showBowlIcon && (millis() - bowlIconStartTime >= bowlIconDuration)) {
      showBowlIcon = false;
      drawClockUI();  // Redraw to remove icon
    }
    
    // Check buttons (including long press for Button 1 and Button 2)
    checkHomeButtons();
    
  } else if (currentMode == FEED_ROUNDS_MODE) {
    checkFeedButtons();
  } else if (currentMode == DISTURBED_MODE) {
    handleDisturbedMode();
  } else if (currentMode == SCALING_MODE) {
    handleScalingMode();
    checkScalingButtons();
  } else if (currentMode == WIFI_MODE) {
    handleWiFiMode();
    checkWiFiButtons();
    server.handleClient();  // Handle web server requests
  } else if (currentMode == SCHEDULING_MODE) {
    checkSchedulingButtons();
  } else if (currentMode == SET_TIME_MODE) {
    checkSetTimeButtons();
  } else if (currentMode == FOOD_ROUNDS_MODE) {
    checkFoodRoundsSchedulingButtons();
  }
  
  // Handle server communication (send updates to webapp)
  if (wifiConnected && currentMode != WIFI_MODE) {
    handleServerCommunication();
  }
  
  // Handle web server requests when in AP mode (WiFi configuration)
  if (currentMode == WIFI_MODE && !wifiConnected) {
    server.handleClient();
  }
}