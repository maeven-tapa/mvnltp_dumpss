/*
 * Scaling Mode Functions
 */

#include "globals.h"

// Handle scaling mode (update weight display)
void handleScalingMode() {
  // Weight is now updated in main loop every 200ms
  // Just update the display here
  static unsigned long lastDisplayUpdate = 0;
  
  if (millis() - lastDisplayUpdate >= weightUpdateInterval) {
    lastDisplayUpdate = millis();
    updateWeightDisplay();
  }
}

// Check button states in SCALING mode
void checkScalingButtons() {
  bool btn1State = digitalRead(BTN1);
  bool btn2State = digitalRead(BTN2);
  bool btn3State = digitalRead(BTN3);
  
  // BTN1: Double press to exit
  if (btn1State == HIGH && lastButtonState[0] == LOW) {
    // Debounce check
    if (millis() - lastDebounceTime[0] > debounceDelay) {
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
          saveCurrentMode();
          btn1PressCount = 0;
          btn1LongPressTriggered = false;
          btn2LongPressTriggered = false;
          btn3LongPressTriggered = false;
          lastButtonState[0] = LOW;
          lastButtonState[1] = LOW;
          lastButtonState[2] = LOW;
          drawClockUI();
          return;
        }
      } else {
        // Reset count if outside window
        btn1PressCount = 1;
      }
      
      lastBtn1PressTime = currentTime;
      btn1PressStartTime = currentTime;
      lastDebounceTime[0] = currentTime;
      
      // Single press buzzer feedback
      digitalWrite(BUZZER, HIGH);
      delay(100);
      digitalWrite(BUZZER, LOW);
    }
  }
  
  // Reset press count if window expired
  if (millis() - lastBtn1PressTime > doublePressWindow) {
    btn1PressCount = 0;
  }
  
  // BTN3: Long press to tare
  if (btn3State == HIGH && lastButtonState[2] == LOW) {
    btn3PressStartTime = millis();
    btn3LongPressTriggered = false;
  }
  
  if (btn3State == HIGH && !btn3LongPressTriggered) {
    if (millis() - btn3PressStartTime >= longPressDuration) {
      btn3LongPressTriggered = true;
      
      // Buzz to confirm long press
      digitalWrite(BUZZER, HIGH);
      delay(200);
      digitalWrite(BUZZER, LOW);
      
      // Perform tare
      scale.tare();
      currentWeight = 0;
      
      // Save tare offset to flash memory
      preferences.begin("device-settings", false);
      preferences.putLong("tare_offset", scale.get_offset());
      preferences.end();
      
      Serial.println("Scale tared (zeroed) and saved to flash");
      
      // Redraw to show zero weight
      drawScalingUI();
    }
  }
  
  // BTN3 single press buzzer feedback (only if not doing long press)
  if (btn3State == HIGH && lastButtonState[2] == LOW) {
    digitalWrite(BUZZER, HIGH);
    delay(100);
    digitalWrite(BUZZER, LOW);
  }
  
  lastButtonState[0] = btn1State;
  lastButtonState[1] = btn2State;
  lastButtonState[2] = btn3State;
}
