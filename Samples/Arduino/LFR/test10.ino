// Line Follower Robot with Physical Button Control
// Arduino R4 Minima with TCRT5000 sensors and TB6612FNG motor driver

#include <U8g2lib.h>

// TCRT5000 Sensor Pins (analog)
#define SENSOR1 A0
#define SENSOR2 A1
#define SENSOR3 A2
#define SENSOR4 A3
#define SENSOR5 A4

// TB6612FNG Motor Driver Pins
#define PWMA 11   // PWM pin for Motor A
#define AIN2 12
#define AIN1 8
#define STBY 10
#define BIN1 4
#define BIN2 5
#define PWMB 6    // PWM pin for Motor B

// Button Pins
#define START_BUTTON 2
#define STOP_BUTTON 3

// Software I2C pins for OLED
#define OLED_SDA 9
#define OLED_SCL 13

// OLED Display settings
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

// Motor speed settings
#define BASE_SPEED 200
#define MAX_SPEED 255
#define TURN_SPEED 190

// Button debounce settings
#define DEBOUNCE_DELAY 50

// Display state management
#define STOPPING_DISPLAY_TIME 2000  // 2 seconds

// Global variables
bool robotRunning = false;
int sensorValues[5] = {0, 0, 0, 0, 0};
int threshold = 512; // Adjust based on your sensor calibration (0-1023 range for Arduino)
int lastDirection = 0; // -1 for left, 1 for right, 0 for center

// Button variables
bool lastStartButtonState = LOW;
bool lastStopButtonState = LOW;
unsigned long lastStartDebounceTime = 0;
unsigned long lastStopDebounceTime = 0;

// Display variables
U8G2_SSD1306_128X64_NONAME_F_SW_I2C display(U8G2_R0, OLED_SCL, OLED_SDA, U8X8_PIN_NONE);
bool isStoppingState = false;
unsigned long stoppingStartTime = 0;

// Serial print variables
unsigned long lastPrintTime = 0;
#define PRINT_INTERVAL 500  // Print every 500ms (half second)

void setup() {
  // Initialize Serial communication
  Serial.begin(9600);
  
  // Initialize OLED display
  display.begin();
  display.setFont(u8g2_font_ncenB14_tr);
  updateDisplay("READY");
  
  // Initialize sensor pins
  pinMode(SENSOR1, INPUT);
  pinMode(SENSOR2, INPUT);
  pinMode(SENSOR3, INPUT);
  pinMode(SENSOR4, INPUT);
  pinMode(SENSOR5, INPUT);
  
  // Initialize button pins for external pull-down resistors
  pinMode(START_BUTTON, INPUT);
  pinMode(STOP_BUTTON, INPUT);
  
  // Initialize motor driver pins
  pinMode(AIN1, OUTPUT);
  pinMode(AIN2, OUTPUT);
  pinMode(BIN1, OUTPUT);
  pinMode(BIN2, OUTPUT);
  pinMode(STBY, OUTPUT);
  pinMode(PWMA, OUTPUT);
  pinMode(PWMB, OUTPUT);
  
  // Enable motor driver
  digitalWrite(STBY, HIGH);
  
  // Stop motors initially
  stopMotors();
}

void loop() {
  // Handle stopping state timer
  if (isStoppingState) {
    if (millis() - stoppingStartTime >= STOPPING_DISPLAY_TIME) {
      isStoppingState = false;
      updateDisplay("READY");
    }
  }
  
  // Read and handle button presses
  handleButtons();
  
  // Read sensor values
  readSensors();
  
  // Print sensor values every half second
  printSensorValues();
  
  // Run line following algorithm if robot is started
  if (robotRunning) {
    followLine();
  }
  
  delay(5);
}

void updateDisplay(const char* message) {
  display.clearBuffer();
  display.drawStr(10, 35, message);
  display.sendBuffer();
}

void handleButtons() {
  // Read current button states (HIGH when pressed with pull-down)
  bool startButtonReading = digitalRead(START_BUTTON);
  bool stopButtonReading = digitalRead(STOP_BUTTON);
  
  // Handle Start Button with debouncing
  if (startButtonReading != lastStartButtonState) {
    lastStartDebounceTime = millis();
  }
  
  if ((millis() - lastStartDebounceTime) > DEBOUNCE_DELAY) {
    if (startButtonReading == HIGH && !robotRunning && !isStoppingState) {
      robotRunning = true;
      updateDisplay("RUNNING");
    }
  }
  
  lastStartButtonState = startButtonReading;
  
  // Handle Stop Button with debouncing
  if (stopButtonReading != lastStopButtonState) {
    lastStopDebounceTime = millis();
  }
  
  if ((millis() - lastStopDebounceTime) > DEBOUNCE_DELAY) {
    if (stopButtonReading == HIGH && robotRunning) {
      robotRunning = false;
      stopMotors();
      isStoppingState = true;
      stoppingStartTime = millis();
      updateDisplay("STOPPING");
    }
  }
  
  lastStopButtonState = stopButtonReading;
}

void readSensors() {
  sensorValues[0] = analogRead(SENSOR1);
  sensorValues[1] = analogRead(SENSOR2);
  sensorValues[2] = analogRead(SENSOR3);
  sensorValues[3] = analogRead(SENSOR4);
  sensorValues[4] = analogRead(SENSOR5);
}

void printSensorValues() {
  if (millis() - lastPrintTime >= PRINT_INTERVAL) {
    Serial.print("Sensor1 = ");
    Serial.print(sensorValues[0]);
    Serial.print(" | Sensor2 = ");
    Serial.print(sensorValues[1]);
    Serial.print(" | Sensor3 = ");
    Serial.print(sensorValues[2]);
    Serial.print(" | Sensor4 = ");
    Serial.print(sensorValues[3]);
    Serial.print(" | Sensor5 = ");
    Serial.println(sensorValues[4]);
    
    lastPrintTime = millis();
  }
}

void followLine() {
  // Convert analog values to digital (1 = black line detected, 0 = white)
  bool s1 = sensorValues[0] > threshold;
  bool s2 = sensorValues[1] > threshold;
  bool s3 = sensorValues[2] > threshold;
  bool s4 = sensorValues[3] > threshold;
  bool s5 = sensorValues[4] > threshold;
  
  // Check if any sensor detects the line
  bool lineDetected = s1 || s2 || s3 || s4 || s5;
  
  // Priority-based line following logic
  if (!lineDetected) {
    // No line detected - continue rotating in the last known direction
    if (lastDirection == -1) {
      rotateLeft(TURN_SPEED);
    }
    else if (lastDirection == 1) {
      rotateRight(TURN_SPEED);
    }
    else {
      rotateRight(TURN_SPEED);
      lastDirection = 1;
    }
  }
  // FORWARD - 1 1 1 1 1
  else if (s1 && s2 && s3 && s4 && s5) {
    moveForward(BASE_SPEED);
    lastDirection = 0;
  }
  // FORWARD - MIDDLE ONLY - 0 0 1 0 0
  else if (!s1 && !s2 && s3 && !s4 && !s5) {
    moveForward(MAX_SPEED);
    lastDirection = 0;
  }
  // FFORWARD - NO MIDDLE - 0 1 0 1 0
  else if (!s1 && s2 && !s3 && s4 && !s5) {
    moveForward(BASE_SPEED);
    lastDirection = 0;
  }
  // FFORWARD - NO MIDDLE - 1 0 0 0 1
  else if (s1 && !s2 && !s3 && !s4 && s5) {
    moveForward(BASE_SPEED);
    lastDirection = 0;
  }
  // FFORWARD - NO MIDDLE - 1 1 0 1 1
  else if (s1 && s2 && !s3 && s4 && s5) {
    moveForward(BASE_SPEED);
    lastDirection = 0;
  }
  // FORWARD - LEFT MIDDLE ONLY - 0 1 0 0 0
  else if (!s1 && s2 && !s3 && !s4 && !s5) {
    moveForward(MAX_SPEED);
    lastDirection = -1;
  }
  // FORWARD - RIGHT MIDDLE ONLY - 0 0 0 1 0
  else if (!s1 && !s2 && !s3 && s4 && !s5) {
    moveForward(MAX_SPEED);
    lastDirection = 1;
  }
  // FORWARD - MIDDLE ONLY - 0 1 1 1 0
  else if (!s1 && s2 && s3 && s4 && !s5) {
    moveForward(MAX_SPEED);
    lastDirection = 0;
  }
  // FFORWARD - NO MIDDLE - 1 0 1 0 1
  else if (s1 && !s2 && s3 && !s4 && s5) {
    moveForward(BASE_SPEED);
    lastDirection = 0;
  }
  // FFORWARD - LEFT - 1 0 1 0 0
  else if (s1 && !s2 && !s3 && s4 && !s5) {
    turnLeft(TURN_SPEED, BASE_SPEED);
    lastDirection = -1;
  }
  // FFORWARD - LEFT - 1 0 0 1 0
  else if (s1 && !s2 && !s3 && s4 && !s5) {
    moveForward(BASE_SPEED);
    lastDirection = -1;
  }
  // ANGLE RIGHT - 0 0 1 0 1
  else if (!s1 && !s2 && s3 && !s4 && s5) {
    turnRight(BASE_SPEED, TURN_SPEED);
    lastDirection = 1;
  }
  // FFORWARD - LEFT - 0 1 0 0 1
  else if (!s1 && s2 && !s3 && !s4 && s5) {
    moveForward(BASE_SPEED);
    lastDirection = 1;
  }
  // SHARP LEFT - 1 0 0 0 0
  else if (s1 && !s2 && !s3 && !s4 && !s5) {
    rotateLeft(TURN_SPEED);
    lastDirection = -1;
  }
  // SHARP RIGHT - 0 0 0 0 1
  else if (!s1 && !s2 && !s3 && !s4 && s5) {
    rotateRight(TURN_SPEED);
    lastDirection = 1;
  }
  // ANGLE LEFT - 1 1 0 0 0
  else if (s1 && s2 && !s3 && !s4 && !s5) {
    turnLeft(TURN_SPEED, BASE_SPEED);
    lastDirection = -1;
  }
  // ANGLE LEFT - 0 1 1 0 0
  else if (!s1 && s2 && s3 && !s4 && !s5) {
    turnLeft(TURN_SPEED, BASE_SPEED);
    lastDirection = -1;
  }
  // ANGLE RIGHT - 0 0 1 1 0
  else if (!s1 && !s2 && s3 && s4 && !s5) {
    turnRight(BASE_SPEED, TURN_SPEED);
    lastDirection = 1;
  }
  // ANGLE RIGHT - 0 0 0 1 1
  else if (!s1 && !s2 && !s3 && s4 && s5) {
    turnRight(BASE_SPEED, TURN_SPEED);
    lastDirection = 1;
  }
  // SLIGHT LEFT - 1 1 0 1 0
  else if (s1 && s2 && !s3 && s4 && !s5) {
    turnLeft(TURN_SPEED, BASE_SPEED);
    lastDirection = -1;
  }
  // SLIGHT LEFT - 1 1 0 0 1
  else if (s1 && s2 && !s3 && !s4 && s5) {
    rotateLeft(TURN_SPEED);
    lastDirection = -1;
  }
  // SLIGHT LEFT - 1 1 1 0 0
  else if (s1 && s2 && s3 && !s4 && !s5) {
    rotateLeft(TURN_SPEED);
    lastDirection = -1;
  }
  // SLIGHT LEFT - 1 1 1 0 1
  else if (s1 && s2 && s3 && !s4 && s5) {
    rotateLeft(TURN_SPEED);
    lastDirection = -1;
  }
  // SLIGHT LEFT - 1 1 1 1 0
  else if (s1 && s2 && s3 && s4 && !s5) {
    rotateLeft(TURN_SPEED);
    lastDirection = -1;
  }
  // SLIGHT RIGHT - 0 1 0 1 1
  else if (!s1 && s2 && !s3 && s4 && s5) {
    turnRight(BASE_SPEED, BASE_SPEED);
    lastDirection = 1;
  }
  // SLIGHT RIGHT - 1 0 0 1 1
  else if (s1 && !s2 && !s3 && s4 && s5) {
    rotateLeft(TURN_SPEED);
    lastDirection = 1;
  }
  // SLIGHT RIGHT - 0 1 1 1 1
  else if (!s1 && s2 && s3 && s4 && s5) {
    rotateLeft(TURN_SPEED);
    lastDirection = 1;
  }
  // SLIGHT RIGHT - 1 0 1 1 1
  else if (s1 && !s2 && s3 && s4 && s5) {
    rotateLeft(TURN_SPEED);
    lastDirection = 1;
  }
  // SLIGHT RIGHT - 0 0 1 1 1
  else if (!s1 && !s2 && s3 && s4 && s5) {
    rotateLeft(TURN_SPEED);
    lastDirection = 1;
  }
}

void moveForward(int speed) {
  digitalWrite(AIN1, HIGH);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, HIGH);
  digitalWrite(BIN2, LOW);
  analogWrite(PWMA, speed);
  analogWrite(PWMB, speed);
}

void moveBackward(int speed) {
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, HIGH);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, HIGH);
  analogWrite(PWMA, speed);
  analogWrite(PWMB, speed);
}

void turnLeft(int leftSpeed, int rightSpeed) {
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, HIGH);
  digitalWrite(BIN1, HIGH);
  digitalWrite(BIN2, LOW);
  analogWrite(PWMA, leftSpeed);
  analogWrite(PWMB, rightSpeed);
}

void turnRight(int leftSpeed, int rightSpeed) {
  digitalWrite(AIN1, HIGH);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, HIGH);
  analogWrite(PWMA, leftSpeed);
  analogWrite(PWMB, rightSpeed);
}

void rotateLeft(int speed) {
  // Rotate in place - left motor backward, right motor forward
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, HIGH);
  digitalWrite(BIN1, HIGH);
  digitalWrite(BIN2, LOW);
  analogWrite(PWMA, speed);
  analogWrite(PWMB, speed);
}

void rotateRight(int speed) {
  // Rotate in place - left motor forward, right motor backward
  digitalWrite(AIN1, HIGH);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, HIGH);
  analogWrite(PWMA, speed);
  analogWrite(PWMB, speed);
}

void stopMotors() {
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, LOW);
  analogWrite(PWMA, 0);
  analogWrite(PWMB, 0);
}