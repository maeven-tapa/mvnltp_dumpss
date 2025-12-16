/*
 * Home Mode Functions
 */

#include "globals.h"

// Check button states in HOME mode
void checkHomeButtons() {
  bool currentBtn1State = digitalRead(BTN1);
  bool currentBtn2State = digitalRead(BTN2);
  
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
