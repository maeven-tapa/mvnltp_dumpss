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

#include <Adafruit_GFX.h>
#include <Adafruit_ST7789.h>
#include <SPI.h>
#include <ThreeWire.h>
#include <RtcDS1302.h>
#include <ESP32Servo.h>

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
#define BTN2     10
#define BTN3     11

// Buzzer pin
#define BUZZER   4

// Feed system pins
#define SERVO_PIN 42
#define RELAY_PIN 13

// Create display object
Adafruit_ST7789 tft = Adafruit_ST7789(TFT_CS, TFT_DC, TFT_MOSI, TFT_CLK, TFT_RST);

// Create RTC object
ThreeWire myWire(RTC_DAT, RTC_CLK, RTC_RST);
RtcDS1302<ThreeWire> Rtc(myWire);

// Create Servo object
Servo feedServo;

// Mode management
enum Mode {
  HOME_MODE,
  FEED_ROUNDS_MODE
};

Mode currentMode = HOME_MODE;

// Button state tracking
unsigned long lastDebounceTime[3] = {0, 0, 0};
bool lastButtonState[3] = {LOW, LOW, LOW};
const unsigned long debounceDelay = 50;

// Long press tracking for Button 1
unsigned long btn1PressStartTime = 0;
bool btn1LongPressTriggered = false;
const unsigned long longPressDuration = 2000;  // 2 seconds

// Double press tracking for Button 1 (to exit Feed Rounds mode)
unsigned long lastBtn1PressTime = 0;
int btn1PressCount = 0;
const unsigned long doublePressWindow = 500;  // 500ms window for double press

// Feed rounds state
int totalRounds = 1;  // Default 1 round
bool feedingComplete = false;
bool showBowlIcon = false;
unsigned long bowlIconStartTime = 0;
const unsigned long bowlIconDuration = 1000;  // Show for 5 seconds

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
  
  // Initialize feed system
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, LOW);
  feedServo.attach(SERVO_PIN);
  feedServo.write(0);  // Initial position
  
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
  
  // Draw initial UI
  drawClockUI();
}

void loop() {
  static unsigned long lastUpdate = 0;
  
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
    
    // Check buttons (including long press for Button 1)
    checkHomeButtons();
    
  } else if (currentMode == FEED_ROUNDS_MODE) {
    checkFeedButtons();
  }
}

// Check button states in HOME mode
void checkHomeButtons() {
  bool currentBtn1State = digitalRead(BTN1);
  
  // Long press detection for Button 1
  if (currentBtn1State == HIGH && lastButtonState[0] == LOW) {
    // Button just pressed
    btn1PressStartTime = millis();
    btn1LongPressTriggered = false;
  }
  
  if (currentBtn1State == HIGH && !btn1LongPressTriggered) {
    // Check if held for 2 seconds
    if (millis() - btn1PressStartTime >= longPressDuration) {
      btn1LongPressTriggered = true;
      
      // Buzz to confirm
      digitalWrite(BUZZER, HIGH);
      delay(200);
      digitalWrite(BUZZER, LOW);
      
      // Switch to Feed Rounds Mode
      currentMode = FEED_ROUNDS_MODE;
      totalRounds = 1;  // Reset to default
      drawFeedRoundsUI();
      
      Serial.println("Entering Feed Rounds Mode");
    }
  }
  
  lastButtonState[0] = currentBtn1State;
  
  // Check other buttons for normal press
  int buttons[2] = {BTN2, BTN3};
  for (int i = 0; i < 2; i++) {
    bool currentState = digitalRead(buttons[i]);
    
    if (currentState != lastButtonState[i + 1]) {
      lastDebounceTime[i + 1] = millis();
    }
    
    if ((millis() - lastDebounceTime[i + 1]) > debounceDelay) {
      if (currentState == HIGH) {
        digitalWrite(BUZZER, HIGH);
        delay(100);
        digitalWrite(BUZZER, LOW);
        
        Serial.print("Button ");
        Serial.print(i + 2);
        Serial.println(" pressed!");
      }
    }
    
    lastButtonState[i + 1] = currentState;
  }
}

// Check button states in FEED_ROUNDS mode
void checkFeedButtons() {
  bool btn1State = digitalRead(BTN1);
  bool btn2State = digitalRead(BTN2);
  bool btn3State = digitalRead(BTN3);
  
  // BTN2: Increase rounds
  if (btn2State == HIGH && lastButtonState[1] == LOW) {
    if (totalRounds < 10) {
      totalRounds++;
      updateFeedRoundsDisplay();
      
      digitalWrite(BUZZER, HIGH);
      delay(100);
      digitalWrite(BUZZER, LOW);
    }
    delay(200);  // Simple debounce
  }
  
  // BTN3: Decrease rounds
  if (btn3State == HIGH && lastButtonState[2] == LOW) {
    if (totalRounds > 1) {
      totalRounds--;
      updateFeedRoundsDisplay();
      
      digitalWrite(BUZZER, HIGH);
      delay(100);
      digitalWrite(BUZZER, LOW);
    }
    delay(200);  // Simple debounce
  }
  
  // BTN1: Double press to go back, Long press to save and execute
  if (btn1State == HIGH && lastButtonState[0] == LOW) {
    unsigned long currentTime = millis();
    
    // Check if this is within double press window
    if (currentTime - lastBtn1PressTime <= doublePressWindow) {
      btn1PressCount++;
      
      if (btn1PressCount >= 2) {
        // Double press detected - go back to home
        digitalWrite(BUZZER, HIGH);
        delay(100);
        digitalWrite(BUZZER, LOW);
        delay(50);
        digitalWrite(BUZZER, HIGH);
        delay(100);
        digitalWrite(BUZZER, LOW);
        
        Serial.println("Double press detected. Returning to home...");
        
        currentMode = HOME_MODE;
        btn1PressCount = 0;
        drawClockUI();
        
        lastButtonState[0] = btn1State;
        lastButtonState[1] = btn2State;
        lastButtonState[2] = btn3State;
        return;
      }
    } else {
      // Reset count if outside window
      btn1PressCount = 1;
    }
    
    lastBtn1PressTime = currentTime;
    btn1PressStartTime = currentTime;
    btn1LongPressTriggered = false;
  }
  
  // Reset press count if window expired
  if (millis() - lastBtn1PressTime > doublePressWindow) {
    btn1PressCount = 0;
  }
  
  // Long press detection for save and execute
  if (btn1State == HIGH && !btn1LongPressTriggered) {
    if (millis() - btn1PressStartTime >= longPressDuration) {
      btn1LongPressTriggered = true;
      btn1PressCount = 0;  // Reset double press counter
      
      // Buzz to confirm
      digitalWrite(BUZZER, HIGH);
      delay(200);
      digitalWrite(BUZZER, LOW);
      
      Serial.print("Saved ");
      Serial.print(totalRounds);
      Serial.println(" rounds. Returning to home...");
      
      // Return to home mode and show paw icon FIRST
      currentMode = HOME_MODE;
      showBowlIcon = true;
      bowlIconStartTime = millis();
      drawClockUI();
      
      // Small delay to show the icon before feeding starts
      delay(500);
      
      // Execute feeding rounds
      executeFeedRounds();
      
      // Update the icon start time after feeding completes
      bowlIconStartTime = millis();
    }
  }
  
  lastButtonState[0] = btn1State;
  lastButtonState[1] = btn2State;
  lastButtonState[2] = btn3State;
}

// Draw minimalist clock UI with circular border
void drawClockUI() {
  tft.fillScreen(ST77XX_WHITE);
  
  // Draw circular border (centered)
  int centerX = 160;
  int centerY = 120;
  int radius = 100;
  
  // Draw thick circle border
  for (int i = 0; i < 3; i++) {
    tft.drawCircle(centerX, centerY, radius - i, ST77XX_BLACK);
  }
  
  // Draw bowl icon if needed
  if (showBowlIcon) {
    drawBowlIcon();
  }
  
  // Draw the time immediately
  updateTime();
}

// Draw minimalist Feed Rounds UI
void drawFeedRoundsUI() {
  tft.fillScreen(ST77XX_WHITE);
  
  // Title at top
  tft.setTextSize(2);
  tft.setTextColor(ST77XX_BLACK);
  tft.setCursor(95, 40);
  tft.print("FEED ROUNDS");
  
  // Draw simple circle border
  int centerX = 160;
  int centerY = 140;
  int radius = 60;
  
  for (int i = 0; i < 2; i++) {
    tft.drawCircle(centerX, centerY, radius - i, ST77XX_BLACK);
  }
  
  // Display number of rounds
  updateFeedRoundsDisplay();
  
  // Instructions at bottom (centered)
  tft.setTextSize(1);
  tft.setTextColor(ST77XX_BLACK);
  tft.setCursor(20, 210);
  tft.print("BTN 1 - SAVE (2x BACK) | BTN 2 (+) | BTN 3 (-)");
}

// Update feed rounds number display
void updateFeedRoundsDisplay() {
  int centerX = 160;
  int centerY = 140;
  
  // Clear center area
  tft.fillCircle(centerX, centerY, 55, ST77XX_WHITE);
  
  // Display rounds number
  tft.setTextSize(6);
  tft.setTextColor(ST77XX_BLACK);
  
  // Center the number
  int numWidth = (totalRounds >= 10) ? 36 : 18;  // Approximate width
  tft.setCursor(centerX - numWidth, centerY - 24);
  tft.print(totalRounds);
}

// Execute feeding rounds
void executeFeedRounds() {
  Serial.print("Executing ");
  Serial.print(totalRounds);
  Serial.println(" feeding rounds...");
  
  for (int i = 0; i < totalRounds; i++) {
    Serial.print("Round ");
    Serial.print(i + 1);
    Serial.print("/");
    Serial.println(totalRounds);
    
    // Activate servo and relay
    feedServo.write(30);
    digitalWrite(RELAY_PIN, HIGH);
    
    delay(1500);  // 1.5 seconds
    
    // Deactivate
    feedServo.write(0);
    digitalWrite(RELAY_PIN, LOW);
    
    // Wait between rounds (except after last round)
    if (i < totalRounds - 1) {
      delay(1000);  // 1 second delay
    }
  }
  
  Serial.println("Feeding complete!");
}

// Draw mini pet bowl icon at top of clock circle
void drawBowlIcon() {
  int centerX = 160;
  // Circle top is at y=20 (120-100), time top is around y=77 (120-24-10-9)
  // Position icon in the middle: (20 + 77) / 2 = 48.5
  int topY = 48;
  
  // Draw paw print icon (🐾)
  // Main pad (large circle)
  tft.fillCircle(centerX, topY + 8, 4, ST77XX_BLACK);
  
  // Four toe pads (small circles)
  tft.fillCircle(centerX - 6, topY + 2, 2, ST77XX_BLACK);  // Top left
  tft.fillCircle(centerX - 2, topY, 2, ST77XX_BLACK);      // Top center-left
  tft.fillCircle(centerX + 2, topY, 2, ST77XX_BLACK);      // Top center-right
  tft.fillCircle(centerX + 6, topY + 2, 2, ST77XX_BLACK);  // Top right
}

// Update and display current time
void updateTime() {
  RtcDateTime now = Rtc.GetDateTime();
  
  if (!now.IsValid()) {
    return;
  }
  
  int hour = now.Hour();
  int minute = now.Minute();
  int second = now.Second();
  
  // Determine AM/PM
  bool isPM = hour >= 12;
  if (hour > 12) hour -= 12;
  if (hour == 0) hour = 12;
  
  // Format time string
  char timeStr[12];
  sprintf(timeStr, "%02d:%02d:%02d", hour, minute, second);
  
  // Calculate text positions for centering
  int centerX = 160;
  int centerY = 120;
  
  // Display time with background color to overwrite previous text
  tft.setTextSize(3);
  tft.setTextColor(ST77XX_BLACK, ST77XX_WHITE);
  
  // Calculate time text position (centered)
  // Each character is 6*textSize wide, 8*textSize tall
  int timeWidth = strlen(timeStr) * 6 * 3;  // 8 chars * 6px * size 3
  int timeHeight = 8 * 3;
  tft.setCursor(centerX - timeWidth / 2, centerY - timeHeight / 2 - 10);
  tft.print(timeStr);
  
  // Display AM/PM indicator with background color
  tft.setTextSize(2);
  tft.setTextColor(ST77XX_BLACK, ST77XX_WHITE);
  
  // Calculate AM/PM text position (centered below time)
  int ampmWidth = 2 * 6 * 2;  // 2 chars * 6px * size 2
  int ampmHeight = 8 * 2;
  tft.setCursor(centerX - ampmWidth / 2, centerY + timeHeight / 2 + 5);
  tft.println(isPM ? "PM" : "AM");
}
