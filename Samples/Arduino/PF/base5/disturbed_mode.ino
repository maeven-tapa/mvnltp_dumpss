/*
 * Disturbed Mode Functions
 */

#include "globals.h"

// Check tilt switch state
void checkTiltSwitch() {
  bool currentTiltState = digitalRead(TILT_PIN);
  
  if (currentTiltState == HIGH && lastTiltState == LOW) {
    // Tilt just went HIGH - start timer
    tiltHighStartTime = millis();
    tiltHighTimerActive = true;
    Serial.println("Tilt detected - starting timer...");
  } else if (currentTiltState == HIGH && tiltHighTimerActive) {
    // Check if HIGH duration exceeds threshold
    if (millis() - tiltHighStartTime >= disturbedRecognitionDelay) {
      // Tilt has been HIGH long enough - enter disturbed mode
      if (currentMode != DISTURBED_MODE) {
        Serial.println("DEVICE DISTURBED!");
        currentMode = DISTURBED_MODE;
        waitingForNormalReturn = false;
        tiltHighTimerActive = false;
        drawDisturbedUI();
        digitalWrite(BUZZER, HIGH);  // Turn on buzzer
        
        // Send alert to server
        if (wifiConnected) {
          sendAlert("Disturbed", "Device has been moved or disturbed!");
        }
      }
    }
  } else if (currentTiltState == LOW && lastTiltState == HIGH) {
    // Tilt returned to normal
    tiltHighTimerActive = false;
    
    if (currentMode == DISTURBED_MODE) {
      // Start 2-second countdown to return to home
      Serial.println("Device returned to normal position. Waiting 2 seconds...");
      waitingForNormalReturn = true;
      tiltReturnTime = millis();
    } else {
      Serial.println("Tilt cleared before triggering disturbed mode");
    }
  } else if (currentTiltState == LOW && lastTiltState == LOW) {
    // Still LOW - clear timer if it was active
    if (tiltHighTimerActive) {
      tiltHighTimerActive = false;
    }
  }
  
  // Check if we're waiting for normal return delay
  if (waitingForNormalReturn && currentMode == DISTURBED_MODE) {
    if (millis() - tiltReturnTime >= normalReturnDelay) {
      // 2 seconds elapsed, return to home mode
      Serial.println("Returning to HOME mode");
      waitingForNormalReturn = false;
      currentMode = HOME_MODE;
      saveCurrentMode();
      digitalWrite(BUZZER, LOW);  // Turn off buzzer
      drawClockUI();
    }
  }
  
  lastTiltState = currentTiltState;
}

// Handle disturbed mode (blinking display)
void handleDisturbedMode() {
  // No blinking needed - just maintain the display
  // The display is already drawn in drawDisturbedUI()
}
