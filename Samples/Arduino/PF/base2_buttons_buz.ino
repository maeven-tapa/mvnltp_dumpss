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
#define BTN1     10
#define BTN2     11
#define BTN3     12

// Buzzer pin
#define BUZZER   4

// Create display object
Adafruit_ST7789 tft = Adafruit_ST7789(TFT_CS, TFT_DC, TFT_MOSI, TFT_CLK, TFT_RST);

// Create RTC object
ThreeWire myWire(RTC_DAT, RTC_CLK, RTC_RST);
RtcDS1302<ThreeWire> Rtc(myWire);

// Button state tracking
unsigned long lastDebounceTime[3] = {0, 0, 0};
bool lastButtonState[3] = {LOW, LOW, LOW};
const unsigned long debounceDelay = 50;

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
  
  // Update every second
  if (millis() - lastUpdate >= 1000) {
    lastUpdate = millis();
    updateTime();
  }
  
  // Check buttons
  checkButtons();
}

// Check button states and trigger buzzer
void checkButtons() {
  int buttons[3] = {BTN1, BTN2, BTN3};
  
  for (int i = 0; i < 3; i++) {
    bool currentState = digitalRead(buttons[i]);
    
    // Debounce logic
    if (currentState != lastButtonState[i]) {
      lastDebounceTime[i] = millis();
    }
    
    if ((millis() - lastDebounceTime[i]) > debounceDelay) {
      // Button pressed (HIGH because pull-down)
      if (currentState == HIGH) {
        // Trigger buzzer
        digitalWrite(BUZZER, HIGH);
        delay(100);  // Buzz for 100ms
        digitalWrite(BUZZER, LOW);
        
        Serial.print("Button ");
        Serial.print(i + 1);
        Serial.println(" pressed!");
      }
    }
    
    lastButtonState[i] = currentState;
  }
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
}

// Update and display current time
void updateTime() {
  RtcDateTime now = Rtc.GetDateTime();
  
  if (!now.IsValid()) {
    Serial.println("RTC DateTime is not valid!");
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
  
  // Print to serial for debugging
  Serial.print(timeStr);
  Serial.print(" ");
  Serial.println(isPM ? "PM" : "AM");
}
