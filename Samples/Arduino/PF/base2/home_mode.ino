/*
 * Home Mode Functions
 */

#include "globals.h"

// Check button states in HOME mode
void checkHomeButtons() {
  bool currentBtn1State = digitalRead(BTN1);
  bool currentBtn2State = digitalRead(BTN2);
  bool currentBtn3State = digitalRead(BTN3);
  
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
  
  // Long press detection for Button 2 (Scaling Mode)
  if (currentBtn2State == HIGH && lastButtonState[1] == LOW) {
    btn2PressStartTime = millis();
    btn2LongPressTriggered = false;
  }
  
  if (currentBtn2State == HIGH && !btn2LongPressTriggered) {
    if (millis() - btn2PressStartTime >= longPressDuration) {
      btn2LongPressTriggered = true;
      
      // Buzz to confirm
      digitalWrite(BUZZER, HIGH);
      delay(200);
      digitalWrite(BUZZER, LOW);
      
      // Switch to Scaling Mode
      currentMode = SCALING_MODE;
      drawScalingUI();
      
      Serial.println("Entering Scaling Mode");
    }
  }
  
  lastButtonState[1] = currentBtn2State;
  
  // Long press detection for Button 3 (WiFi Mode)
  if (currentBtn3State == HIGH && lastButtonState[2] == LOW) {
    btn3PressStartTime = millis();
    btn3LongPressTriggered = false;
  }
  
  if (currentBtn3State == HIGH && !btn3LongPressTriggered) {
    if (millis() - btn3PressStartTime >= longPressDuration) {
      btn3LongPressTriggered = true;
      
      // Buzz to confirm
      digitalWrite(BUZZER, HIGH);
      delay(200);
      digitalWrite(BUZZER, LOW);
      
      // Switch to WiFi Mode
      currentMode = WIFI_MODE;
      
      // Start AP mode if not connected to WiFi
      if (!wifiConnected) {
        startAPMode();
      }
      
      drawWiFiUI();
      
      Serial.println("Entering WiFi Mode");
    }
  }
  
  lastButtonState[2] = currentBtn3State;}