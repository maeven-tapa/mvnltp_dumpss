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

#include "globals.h"

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
int wifiRSSI = 0;

// Double press tracking for Button 1 (exit WiFi Mode)
unsigned long lastBtn1WifiPressTime = 0;
int btn1WifiPressCount = 0;

// Long press tracking for Button 2 (WiFi reset)
unsigned long btn2WifiPressStartTime = 0;
bool btn2WifiLongPressTriggered = false;

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
  feedServo.attach(SERVO_PIN);
  feedServo.write(0);  // Initial position
  
  // Initialize HX711 Load Cell
  scale.begin(HX711_DT, HX711_SCK);
  scale.set_scale(calibrationFactor);  // Set calibration factor
  scale.tare();  // Reset scale to zero
  Serial.println("HX711 Load Cell initialized");
  
  // Initialize ST7789 display
  tft.init(240, 320, SPI_MODE0);
  tft.invertDisplay(false);
  tft.setRotation(1);  // Landscape mode (320x240)
  delay(100);
  
  // Clear screen
  tft.fillScreen(ST77XX_WHITE);
  
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
  
  // Draw initial UI
  drawClockUI();
}

void loop() {
  static unsigned long lastUpdate = 0;
  
  // Check tilt switch first (highest priority)
  checkTiltSwitch();
  
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
  }
  
  // Handle web server requests when in WiFi mode
  if (currentMode == WIFI_MODE) {
    server.handleClient();
  }
}