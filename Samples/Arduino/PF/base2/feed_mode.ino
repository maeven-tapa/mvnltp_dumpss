/*
 * Feed Rounds Mode Functions
 */

#include "globals.h"

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
