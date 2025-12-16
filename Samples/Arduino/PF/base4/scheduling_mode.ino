/*
 * Scheduling Mode Functions
 */

#include "globals.h"

// Check button states in SCHEDULING mode (main view)
void checkSchedulingButtons() {
  bool btn1State = digitalRead(BTN1);
  bool btn2State = digitalRead(BTN2);
  bool btn3State = digitalRead(BTN3);
  
  // Double press BTN1 to exit
  if (btn1State == HIGH && lastButtonState[0] == LOW) {
    unsigned long currentTime = millis();
    
    if (currentTime - lastBtn1SchedulingPressTime <= doublePressWindow) {
      btn1SchedulingPressCount++;
      
      if (btn1SchedulingPressCount >= 2) {
        // Double press detected - exit to home
        digitalWrite(BUZZER, HIGH);
        delay(100);
        digitalWrite(BUZZER, LOW);
        delay(50);
        digitalWrite(BUZZER, HIGH);
        delay(100);
        digitalWrite(BUZZER, LOW);
        
        Serial.println("Exiting Scheduling Mode...");
        
        currentMode = HOME_MODE;
        btn1SchedulingPressCount = 0;
        drawClockUI();
        
        lastButtonState[0] = btn1State;
        lastButtonState[1] = btn2State;
        lastButtonState[2] = btn3State;
        return;
      }
    } else {
      btn1SchedulingPressCount = 1;
    }
    
    lastBtn1SchedulingPressTime = currentTime;
  }
  
  // Reset press count if window expired
  if (millis() - lastBtn1SchedulingPressTime > doublePressWindow) {
    btn1SchedulingPressCount = 0;
  }
  
  // Long press BTN2 to enter Set Time Mode
  if (btn2State == HIGH && lastButtonState[1] == LOW) {
    btn2PressStartTime = millis();
    btn2LongPressTriggered = false;
  }
  
  if (btn2State == HIGH && !btn2LongPressTriggered) {
    if (millis() - btn2PressStartTime >= longPressDuration) {
      btn2LongPressTriggered = true;
      
      // Buzz to confirm
      digitalWrite(BUZZER, HIGH);
      delay(200);
      digitalWrite(BUZZER, LOW);
      
      // Initialize temp variables
      if (feedSchedule.isSet) {
        tempHours = feedSchedule.hours;
        tempMinutes = feedSchedule.minutes;
        tempSeconds = feedSchedule.seconds;
      } else {
        tempHours = 0;
        tempMinutes = 0;
        tempSeconds = 0;
      }
      selectedTimeField = 0;  // Start with hours
      
      // Switch to Set Time Mode
      currentMode = SET_TIME_MODE;
      drawSetTimeUI();
      
      Serial.println("Entering Set Time Mode");
    }
  }
  
  // Long press BTN3 to reset schedule
  if (btn3State == HIGH && lastButtonState[2] == LOW) {
    btn3SchedulingPressStartTime = millis();
    btn3SchedulingLongPressTriggered = false;
  }
  
  if (btn3State == HIGH && !btn3SchedulingLongPressTriggered) {
    if (millis() - btn3SchedulingPressStartTime >= longPressDuration) {
      btn3SchedulingLongPressTriggered = true;
      
      // Buzz to confirm
      digitalWrite(BUZZER, HIGH);
      delay(200);
      digitalWrite(BUZZER, LOW);
      
      // Reset schedule
      feedSchedule.isSet = false;
      feedSchedule.hours = 0;
      feedSchedule.minutes = 0;
      feedSchedule.seconds = 0;
      feedSchedule.foodRounds = 0;
      feedIntervalMillis = 0;
      lastFeedTime = 0;
      
      // Redraw scheduling UI
      drawSchedulingUI();
      
      Serial.println("Schedule reset");
    }
  }
  
  lastButtonState[0] = btn1State;
  lastButtonState[1] = btn2State;
  lastButtonState[2] = btn3State;
}

// Check button states in SET_TIME mode
void checkSetTimeButtons() {
  bool btn1State = digitalRead(BTN1);
  bool btn2State = digitalRead(BTN2);
  bool btn3State = digitalRead(BTN3);
  
  // BTN1 pressed - handle double press for cancel, semi-long for cursor, long for save
  if (btn1State == HIGH && lastButtonState[0] == LOW) {
    unsigned long currentTime = millis();
    
    // Check for double press (cancel)
    if (currentTime - lastBtn1SchedulingPressTime <= doublePressWindow) {
      btn1SchedulingPressCount++;
      
      if (btn1SchedulingPressCount >= 2) {
        // Double press detected - cancel
        digitalWrite(BUZZER, HIGH);
        delay(100);
        digitalWrite(BUZZER, LOW);
        delay(50);
        digitalWrite(BUZZER, HIGH);
        delay(100);
        digitalWrite(BUZZER, LOW);
        
        Serial.println("Set Interval cancelled");
        
        currentMode = SCHEDULING_MODE;
        btn1SchedulingPressCount = 0;
        drawSchedulingUI();
        
        lastButtonState[0] = btn1State;
        lastButtonState[1] = btn2State;
        lastButtonState[2] = btn3State;
        return;
      }
    } else {
      btn1SchedulingPressCount = 1;
    }
    
    lastBtn1SchedulingPressTime = currentTime;
    btn1PressStartTime = currentTime;
    btn1LongPressTriggered = false;
  }
  
  // Reset press count if window expired
  if (millis() - lastBtn1SchedulingPressTime > doublePressWindow) {
    btn1SchedulingPressCount = 0;
  }
  
  // Semi-long press BTN1 (1 sec) to move cursor - single beep
  static bool semiLongPressTriggered = false;
  if (btn1State == HIGH && !semiLongPressTriggered && !btn1LongPressTriggered) {
    if (millis() - btn1PressStartTime >= semiLongPressDuration) {
      semiLongPressTriggered = true;
      
      // Move cursor and beep once
      selectedTimeField = (selectedTimeField + 1) % 3;
      updateSetTimeDisplay();
      
      digitalWrite(BUZZER, HIGH);
      delay(100);
      digitalWrite(BUZZER, LOW);
      
      Serial.println("Cursor moved");
    }
  }
  
  // Full long press BTN1 (3 sec) to save - double beep
  if (btn1State == HIGH && !btn1LongPressTriggered) {
    if (millis() - btn1PressStartTime >= longPressDuration) {
      btn1LongPressTriggered = true;
      btn1SchedulingPressCount = 0;
      
      // Double beep to confirm save
      digitalWrite(BUZZER, HIGH);
      delay(150);
      digitalWrite(BUZZER, LOW);
      delay(100);
      digitalWrite(BUZZER, HIGH);
      delay(150);
      digitalWrite(BUZZER, LOW);
      
      // Save time temporarily
      feedSchedule.hours = tempHours;
      feedSchedule.minutes = tempMinutes;
      feedSchedule.seconds = tempSeconds;
      
      // Initialize food rounds
      if (feedSchedule.isSet && feedSchedule.foodRounds > 0) {
        tempFoodRounds = feedSchedule.foodRounds;
      } else {
        tempFoodRounds = 1;
      }
      
      // Switch to Food Rounds Mode
      currentMode = FOOD_ROUNDS_MODE;
      drawFoodRoundsSchedulingUI();
      
      Serial.println("Interval saved, entering Food Rounds Mode");
    }
  }
  
  // Reset semi-long press flag when button released
  if (btn1State == LOW) {
    semiLongPressTriggered = false;
  }
  
  // BTN2: Increase selected value
  if (btn2State == HIGH && lastButtonState[1] == LOW) {
    if (selectedTimeField == 0) {  // Hours
      tempHours = (tempHours + 1) % 24;
    } else if (selectedTimeField == 1) {  // Minutes
      tempMinutes = (tempMinutes + 1) % 60;
    } else {  // Seconds
      tempSeconds = (tempSeconds + 1) % 60;
    }
    
    updateSetTimeDisplay();
    
    digitalWrite(BUZZER, HIGH);
    delay(50);
    digitalWrite(BUZZER, LOW);
    
    delay(200);  // Simple debounce
  }
  
  // BTN3: Decrease selected value
  if (btn3State == HIGH && lastButtonState[2] == LOW) {
    if (selectedTimeField == 0) {  // Hours
      tempHours = (tempHours - 1 + 24) % 24;
    } else if (selectedTimeField == 1) {  // Minutes
      tempMinutes = (tempMinutes - 1 + 60) % 60;
    } else {  // Seconds
      tempSeconds = (tempSeconds - 1 + 60) % 60;
    }
    
    updateSetTimeDisplay();
    
    digitalWrite(BUZZER, HIGH);
    delay(50);
    digitalWrite(BUZZER, LOW);
    
    delay(200);  // Simple debounce
  }
  
  lastButtonState[0] = btn1State;
  lastButtonState[1] = btn2State;
  lastButtonState[2] = btn3State;
}

// Check button states in FOOD_ROUNDS mode (scheduling)
void checkFoodRoundsSchedulingButtons() {
  bool btn1State = digitalRead(BTN1);
  bool btn2State = digitalRead(BTN2);
  bool btn3State = digitalRead(BTN3);
  
  // BTN2: Increase food rounds
  if (btn2State == HIGH && lastButtonState[1] == LOW) {
    if (tempFoodRounds < maxFoodRounds) {
      tempFoodRounds++;
      updateFoodRoundsSchedulingDisplay();
      
      digitalWrite(BUZZER, HIGH);
      delay(50);
      digitalWrite(BUZZER, LOW);
    }
    delay(200);  // Simple debounce
  }
  
  // BTN3: Decrease food rounds
  if (btn3State == HIGH && lastButtonState[2] == LOW) {
    if (tempFoodRounds > minFoodRounds) {
      tempFoodRounds--;
      updateFoodRoundsSchedulingDisplay();
      
      digitalWrite(BUZZER, HIGH);
      delay(50);
      digitalWrite(BUZZER, LOW);
    }
    delay(200);  // Simple debounce
  }
  
  // Double press BTN1 to go back to Set Time Mode
  if (btn1State == HIGH && lastButtonState[0] == LOW) {
    unsigned long currentTime = millis();
    
    if (currentTime - lastBtn1SchedulingPressTime <= doublePressWindow) {
      btn1SchedulingPressCount++;
      
      if (btn1SchedulingPressCount >= 2) {
        // Double press detected - go back
        digitalWrite(BUZZER, HIGH);
        delay(100);
        digitalWrite(BUZZER, LOW);
        delay(50);
        digitalWrite(BUZZER, HIGH);
        delay(100);
        digitalWrite(BUZZER, LOW);
        
        Serial.println("Going back to Set Time Mode");
        
        currentMode = SET_TIME_MODE;
        btn1SchedulingPressCount = 0;
        drawSetTimeUI();
        
        lastButtonState[0] = btn1State;
        lastButtonState[1] = btn2State;
        lastButtonState[2] = btn3State;
        return;
      }
    } else {
      btn1SchedulingPressCount = 1;
    }
    
    lastBtn1SchedulingPressTime = currentTime;
    btn1PressStartTime = currentTime;
    btn1LongPressTriggered = false;
  }
  
  // Reset press count if window expired
  if (millis() - lastBtn1SchedulingPressTime > doublePressWindow) {
    btn1SchedulingPressCount = 0;
  }
  
  // Long press BTN1 to save and return to Scheduling Mode
  if (btn1State == HIGH && !btn1LongPressTriggered) {
    if (millis() - btn1PressStartTime >= longPressDuration) {
      btn1LongPressTriggered = true;
      btn1SchedulingPressCount = 0;
      
      // Buzz to confirm
      digitalWrite(BUZZER, HIGH);
      delay(200);
      digitalWrite(BUZZER, LOW);
      
      // Save complete schedule
      feedSchedule.foodRounds = tempFoodRounds;
      feedSchedule.isSet = true;
      
      // Calculate interval in milliseconds
      feedIntervalMillis = (unsigned long)feedSchedule.hours * 3600000UL +
                          (unsigned long)feedSchedule.minutes * 60000UL +
                          (unsigned long)feedSchedule.seconds * 1000UL;
      
      // Set last feed time to now so first feeding happens after the interval
      lastFeedTime = millis();
      
      Serial.print("Schedule saved: Feed every ");
      Serial.print(feedSchedule.hours);
      Serial.print("h ");
      Serial.print(feedSchedule.minutes);
      Serial.print("m ");
      Serial.print(feedSchedule.seconds);
      Serial.print("s - ");
      Serial.print(feedSchedule.foodRounds);
      Serial.println(" rounds");
      
      // Return to Scheduling Mode
      currentMode = SCHEDULING_MODE;
      drawSchedulingUI();
    }
  }
  
  lastButtonState[0] = btn1State;
  lastButtonState[1] = btn2State;
  lastButtonState[2] = btn3State;
}
