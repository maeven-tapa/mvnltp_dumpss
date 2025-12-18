/*
 * Home Mode Functions
 */

#include "globals.h"

// Check button states in HOME mode
void checkHomeButtons() {
  bool currentBtn1State = digitalRead(BTN1);
  bool currentBtn2State = digitalRead(BTN2);
  bool currentBtn3State = digitalRead(BTN3);
  
  // Simultaneous long press detection for Button 1 AND Button 3 (Open Panel Status)
  if (currentBtn1State == HIGH && currentBtn3State == HIGH && currentBtn2State == LOW) {
    if (!btn1And3BothPressed) {
      // Both buttons just pressed together
      btn1And3PressStartTime = millis();
      btn1And3BothPressed = true;
      btn1And3LongPressTriggered = false;
    }
    
    if (!btn1And3LongPressTriggered) {
      if (millis() - btn1And3PressStartTime >= longPressDuration) {
        btn1And3LongPressTriggered = true;
        
        // Toggle Open Panel Status
        openPanelStatus = !openPanelStatus;
        
        // Save to flash memory
        saveOpenPanelStatus();
        
        // Buzz to confirm
        digitalWrite(BUZZER, HIGH);
        delay(200);
        digitalWrite(BUZZER, LOW);
        
        // Redraw UI to show/hide alert icon
        drawClockUI();
        
        if (openPanelStatus) {
          Serial.println("Open Panel Status ACTIVATED");
        } else {
          Serial.println("Open Panel Status DEACTIVATED");
        }
        
        lastButtonState[0] = currentBtn1State;
        lastButtonState[1] = currentBtn2State;
        lastButtonState[2] = currentBtn3State;
        return;
      }
    }
  } else {
    btn1And3BothPressed = false;
  }
  
  // Simultaneous long press detection for Button 1 AND Button 2 (Scheduling Mode)
  if (currentBtn1State == HIGH && currentBtn2State == HIGH && currentBtn3State == LOW) {
    if (!btn1And2BothPressed) {
      // Both buttons just pressed together
      btn1And2PressStartTime = millis();
      btn1And2BothPressed = true;
      btn1And2LongPressTriggered = false;
    }
    
    if (!btn1And2LongPressTriggered) {
      if (millis() - btn1And2PressStartTime >= longPressDuration) {
        btn1And2LongPressTriggered = true;
        
        // Buzz to confirm
        digitalWrite(BUZZER, HIGH);
        delay(200);
        digitalWrite(BUZZER, LOW);
        
        // Switch to Scheduling Mode (only if Open Panel Status is OFF)
        if (!openPanelStatus) {
          currentMode = SCHEDULING_MODE;
          saveCurrentMode();
          drawSchedulingUI();
          Serial.println("Entering Scheduling Mode");
        } else {
          Serial.println("Scheduling Mode disabled - Panel is open");
        }
        
        lastButtonState[0] = currentBtn1State;
        lastButtonState[1] = currentBtn2State;
        lastButtonState[2] = currentBtn3State;
        return;
      }
    }
  } else {
    btn1And2BothPressed = false;
  }
  
  // Long press detection for Button 1 (only if not pressed with Button 2 or Button 3)
  if (currentBtn1State == HIGH && currentBtn2State == LOW && currentBtn3State == LOW && lastButtonState[0] == LOW) {
    // Button just pressed
    btn1PressStartTime = millis();
    btn1LongPressTriggered = false;
  }
  
  if (currentBtn1State == HIGH && currentBtn2State == LOW && currentBtn3State == LOW && !btn1LongPressTriggered) {
    // Check if held for 3 seconds (only if Button 2 and Button 3 not pressed)
    if (millis() - btn1PressStartTime >= longPressDuration) {
      btn1LongPressTriggered = true;
      
      // Buzz to confirm
      digitalWrite(BUZZER, HIGH);
      delay(200);
      digitalWrite(BUZZER, LOW);
      
      // Switch to Feed Rounds Mode (only if Open Panel Status is OFF)
      if (!openPanelStatus) {
        currentMode = FEED_ROUNDS_MODE;
        saveCurrentMode();
        totalRounds = 1;  // Reset to default
        drawFeedRoundsUI();
        Serial.println("Entering Feed Rounds Mode");
      } else {
        Serial.println("Feed Rounds Mode disabled - Panel is open");
      }
    }
  }
  
  lastButtonState[0] = currentBtn1State;
  
  // Long press detection for Button 2 (Scaling Mode) (only if not pressed with Button 1)
  if (currentBtn2State == HIGH && currentBtn1State == LOW && lastButtonState[1] == LOW) {
    btn2PressStartTime = millis();
    btn2LongPressTriggered = false;
  }
  
  if (currentBtn2State == HIGH && currentBtn1State == LOW && !btn2LongPressTriggered) {
    if (millis() - btn2PressStartTime >= longPressDuration) {
      btn2LongPressTriggered = true;
      
      // Buzz to confirm
      digitalWrite(BUZZER, HIGH);
      delay(200);
      digitalWrite(BUZZER, LOW);
      
      // Switch to Scaling Mode (only if Open Panel Status is OFF)
      if (!openPanelStatus) {
        currentMode = SCALING_MODE;
        saveCurrentMode();
        drawScalingUI();
        Serial.println("Entering Scaling Mode");
      } else {
        Serial.println("Scaling Mode disabled - Panel is open");
      }
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
      saveCurrentMode();
      
      // Start AP mode if not connected to WiFi
      if (!wifiConnected) {
        startAPMode();
      } else {
        // Check server status immediately when entering WiFi mode
        testServerConnection();
      }
      
      drawWiFiUI();
      
      Serial.println("Entering WiFi Mode");
    }
  }
  
  lastButtonState[2] = currentBtn3State;}