/*
 * ST7789 Display Configuration Template
 * 
 * Display Pins:
 * MOSI -> GPIO35, MISO -> GPIO37, SCK -> GPIO36
 * CS -> GPIO38, DC -> GPIO39, RST -> GPIO40, LED -> GPIO41
 * 
 * Libraries: Adafruit_ST7789, Adafruit_GFX
 */

#include <Adafruit_GFX.h>
#include <Adafruit_ST7789.h>
#include <SPI.h>

// Display pins
#define TFT_CS   38
#define TFT_DC   39
#define TFT_RST  40
#define TFT_MOSI 35
#define TFT_MISO 37
#define TFT_CLK  36
#define TFT_LED  41

// Create display object
Adafruit_ST7789 tft = Adafruit_ST7789(TFT_CS, TFT_DC, TFT_MOSI, TFT_CLK, TFT_RST);

void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("Initializing Display...");
  
  // Initialize display backlight
  pinMode(TFT_LED, OUTPUT);
  digitalWrite(TFT_LED, HIGH);  // Turn on backlight
  
  // Initialize ST7789 display
  tft.init(240, 320, SPI_MODE0);
  tft.invertDisplay(false);
  tft.setRotation(1);  // Landscape mode (320x240)
  delay(100);
  
  // Clear screen
  tft.fillScreen(ST77XX_BLACK);
  
  Serial.println("Display Ready!");
  
  // Example: Draw something
  displayExample();
}

void loop() {
  // Your main code here
}

// Example display function
void displayExample() {
  // Fill screen with color
  tft.fillScreen(ST77XX_BLACK);
  
  // Draw text
  tft.setTextSize(3);
  tft.setTextColor(ST77XX_CYAN);
  tft.setCursor(50, 50);
  tft.println("HELLO!");
  
  // Draw shapes
  tft.drawRect(20, 100, 280, 100, ST77XX_WHITE);  // Rectangle
  tft.fillCircle(160, 150, 30, ST77XX_GREEN);     // Circle
  
  // Draw line
  tft.drawLine(20, 210, 300, 210, ST77XX_RED);
}
