/*
 * Global Variables and Objects
 */

#ifndef GLOBALS_H
#define GLOBALS_H

#include <Adafruit_GFX.h>
#include <Adafruit_ST7789.h>
#include <SPI.h>
#include <ThreeWire.h>
#include <RtcDS1302.h>
#include <ESP32Servo.h>
#include <HX711.h>
#include <WiFi.h>
#include <WebServer.h>
#include <Preferences.h>
#include "config.h"

// Create display object
extern Adafruit_ST7789 tft;

// Create RTC object
extern ThreeWire myWire;
extern RtcDS1302<ThreeWire> Rtc;

// Create Servo object
extern Servo feedServo;

// Create HX711 object
extern HX711 scale;

// Mode management
enum Mode {
  HOME_MODE,
  FEED_ROUNDS_MODE,
  DISTURBED_MODE,
  SCALING_MODE,
  WIFI_MODE,
  SCHEDULING_MODE,
  SET_TIME_MODE,
  FOOD_ROUNDS_MODE
};

extern Mode currentMode;

// Button state tracking
extern unsigned long lastDebounceTime[3];
extern bool lastButtonState[3];

// Long press tracking for Button 1
extern unsigned long btn1PressStartTime;
extern bool btn1LongPressTriggered;

// Double press tracking for Button 1 (to exit Feed Rounds mode)
extern unsigned long lastBtn1PressTime;
extern int btn1PressCount;

// Feed rounds state
extern int totalRounds;
extern bool feedingComplete;
extern bool showBowlIcon;
extern unsigned long bowlIconStartTime;

// Tilt switch state
extern bool lastTiltState;
extern unsigned long tiltReturnTime;
extern bool waitingForNormalReturn;

// Disturbed recognition timing
extern unsigned long tiltHighStartTime;
extern bool tiltHighTimerActive;

// Disturbed mode display state
extern bool disturbedBlinkState;
extern unsigned long lastDisturbedBlink;

// Scaling mode state
extern float currentWeight;
extern unsigned long lastWeightUpdate;

// Long press tracking for Button 2 (Scaling Mode entry)
extern unsigned long btn2PressStartTime;
extern bool btn2LongPressTriggered;

// Double press tracking for Button 2 (exit Scaling Mode)
extern unsigned long lastBtn2PressTime;
extern int btn2PressCount;

// Long press tracking for Button 3 (Tare function / WiFi Mode)
extern unsigned long btn3PressStartTime;
extern bool btn3LongPressTriggered;

// WiFi state
extern WebServer server;
extern Preferences preferences;
extern bool wifiConnected;
extern String savedSSID;
extern String savedPassword;
extern int wifiRSSI;

// Double press tracking for Button 1 (exit WiFi Mode)
extern unsigned long lastBtn1WifiPressTime;
extern int btn1WifiPressCount;

// Long press tracking for Button 2 (WiFi reset)
extern unsigned long btn2WifiPressStartTime;
extern bool btn2WifiLongPressTriggered;

// Scheduling mode state
struct Schedule {
  bool isSet;
  int hours;
  int minutes;
  int seconds;
  int foodRounds;
};

extern Schedule feedSchedule;

// Interval feeding tracking
extern unsigned long lastFeedTime;
extern unsigned long feedIntervalMillis;

// Scheduling mode tracking - simultaneous button press
extern unsigned long btn1And2PressStartTime;
extern bool btn1And2LongPressTriggered;
extern bool btn1And2BothPressed;

// Set Time Mode state
extern int selectedTimeField;  // 0=hours, 1=minutes, 2=seconds
extern int tempHours;
extern int tempMinutes;
extern int tempSeconds;

// Food Rounds Mode state
extern int tempFoodRounds;

// Double press tracking for Button 1 (exit Scheduling Mode)
extern unsigned long lastBtn1SchedulingPressTime;
extern int btn1SchedulingPressCount;

// Long press tracking for Button 3 (reset schedule)
extern unsigned long btn3SchedulingPressStartTime;
extern bool btn3SchedulingLongPressTriggered;

// Open Panel Status state
extern bool openPanelStatus;
extern unsigned long btn1And3PressStartTime;
extern bool btn1And3LongPressTriggered;
extern bool btn1And3BothPressed;

#endif // GLOBALS_H
