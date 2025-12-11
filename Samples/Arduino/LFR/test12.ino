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
#define BASE_SPEED 50
#define MAX_SPEED 130
#define TURN_SPEED 100

// PID Constants - Tune these values for your robot
#define KP 4 // Proportional gain
#define KI 0.0   // Integral gain
#define KD 8

// Button debounce settings
#define DEBOUNCE_DELAY 50

// Display state management
#define STOPPING_DISPLAY_TIME 2000  // 2 seconds

// Global variables

bool robotRunning = false;
int sensorValues[5] = {0, 0, 0, 0, 0};
int threshold = 300; // Adjust based on your sensor calibration (0-1023 range for Arduino)
int lastDirection = 0; // -1 for left, 1 for right, 0 for center

// PID variables
float lastError = 0;
float integral = 0;
float error = 0;

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

float calculateError() {
  // Convert analog values to digital (1 = black line detected, 0 = white)
  bool s[5];
  for (int i = 0; i < 5; i++) {
    s[i] = sensorValues[i] > threshold;
  }
  
  // Calculate weighted position (-2 to +2, with 0 being center)
  // Sensor positions: -2, -1, 0, 1, 2
  int weights[5] = {-2, -1, 0, 1, 2};
  int weightedSum = 0;
  int sum = 0;
  
  for (int i = 0; i < 5; i++) {
    if (s[i]) {
      weightedSum += weights[i];
      sum++;
    }
  }
  
  // If no line detected, return large error in last direction
  if (sum == 0) {
    return lastDirection * 4.0;
  }
  
  // Calculate position error
  float position = (float)weightedSum / (float)sum;
  
  // Update last direction based on error
  if (position < -0.5) {
    lastDirection = -1;
  } else if (position > 0.5) {
    lastDirection = 1;
  } else {
    lastDirection = 0;
  }
  
  return position;
}

float calculatePID(float error) {
  // Proportional term
  float P = error;
  
  // Integral term (with anti-windup)
  integral += error;
  // Limit integral to prevent windup
  if (integral > 100) integral = 100;
  if (integral < -100) integral = -100;
  
  // Derivative term
  float derivative = error - lastError;
  
  // Calculate PID output
  float output = (KP * P) + (KI * integral) + (KD * derivative);
  
  // Save error for next iteration
  lastError = error;
  
  return output;
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
    Serial.print(sensorValues[4]);
    Serial.print(" | Error = ");
    Serial.println(error);
    
    lastPrintTime = millis();
  }
}

void followLine() {
  // Calculate error from sensor readings
  error = calculateError();
  
  // Calculate PID correction
  float correction = calculatePID(error);
  
  // Dynamic speed adjustment based on error magnitude
  // Lower error (straighter line) = higher speed
  // Higher error (sharper turn) = lower speed
  float absError = abs(error);
  int dynamicBaseSpeed;
  
  if (absError < 0.5) {
    // Nearly straight - use maximum speed
    dynamicBaseSpeed = MAX_SPEED;
  } else if (absError < 1.0) {
    // Slight curve - use high speed
    dynamicBaseSpeed = map(absError * 100, 50, 100, MAX_SPEED * 0.8, MAX_SPEED);
  } else if (absError < 2.0) {
    // Moderate curve - use medium speed
    dynamicBaseSpeed = map(absError * 100, 100, 200, BASE_SPEED, MAX_SPEED * 0.8);
  } else {
    // Sharp turn or lost line - use base speed
    dynamicBaseSpeed = BASE_SPEED;
  }
  
  // Calculate motor speeds with dynamic base speed
  int leftSpeed = dynamicBaseSpeed + correction;
  int rightSpeed = dynamicBaseSpeed - correction;
  
  // Constrain speeds to valid range
  leftSpeed = constrain(leftSpeed, -MAX_SPEED, MAX_SPEED);
  rightSpeed = constrain(rightSpeed, -MAX_SPEED, MAX_SPEED);
  
  // Apply speeds to motors
  if (leftSpeed >= 0) {
    digitalWrite(AIN1, HIGH);
    digitalWrite(AIN2, LOW);
    analogWrite(PWMA, leftSpeed);
  } else {
    digitalWrite(AIN1, LOW);
    digitalWrite(AIN2, HIGH);
    analogWrite(PWMA, -leftSpeed);
  }
  
  if (rightSpeed >= 0) {
    digitalWrite(BIN1, HIGH);
    digitalWrite(BIN2, LOW);
    analogWrite(PWMB, rightSpeed);
  } else {
    digitalWrite(BIN1, LOW);
    digitalWrite(BIN2, HIGH);
    analogWrite(PWMB, -rightSpeed);
  }
}

void stopMotors() {
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, LOW);
  analogWrite(PWMA, 0);
  analogWrite(PWMB, 0);
}