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
const unsigned long longPressDuration = 2000;  // 2 seconds
const unsigned long doublePressWindow = 500;   // 500ms window for double press
const unsigned long bowlIconDuration = 1000;   // Show for 1 second
const unsigned long normalReturnDelay = 2000;  // 2 seconds
const unsigned long disturbedRecognitionDelay = 1000;  // 1 second
const unsigned long disturbedBlinkInterval = 500;  // Blink every 500ms
const unsigned long weightUpdateInterval = 200;  // Update weight every 200ms

// Scaling mode constants
const float maxCapacity = 20000.0;  // Maximum capacity in grams (20kg load cell)
const float calibrationFactor = 80.0;

#endif // CONFIG_H
