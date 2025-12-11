// Line Follower Robot with Physical Button Control
// ESP32S with TCRT5000 sensors and TB6612FNG motor driver

// TCRT5000 Sensor Pins (analog)
#define SENSOR1 15
#define SENSOR2 7
#define SENSOR3 6
#define SENSOR4 5
#define SENSOR5 4

// TB6612FNG Motor Driver Pins
#define PWMA 21
#define AIN2 35
#define AIN1 36
#define STBY 37
#define BIN1 38
#define BIN2 39
#define PWMB 40

// Button Pins (with internal pull-down)
#define START_BUTTON 20
#define STOP_BUTTON 1

// Motor speed settings
#define BASE_SPEED 250
#define MAX_SPEED 255
#define TURN_SPEED 240

// PWM settings
#define PWM_FREQ 1000
#define PWM_RESOLUTION 8
#define PWM_CHANNEL_A 0
#define PWM_CHANNEL_B 1

// Button debounce settings
#define DEBOUNCE_DELAY 50

// Global variables
bool robotRunning = false;
int sensorValues[5] = {0, 0, 0, 0, 0};
int threshold = 1700; // Adjust based on your sensor calibration
int lastDirection = 0; // -1 for left, 1 for right, 0 for center

// Button variables
bool lastStartButtonState = LOW;
bool lastStopButtonState = LOW;
unsigned long lastStartDebounceTime = 0;
unsigned long lastStopDebounceTime = 0;

void setup() {
  Serial.begin(115200);
  
  // Initialize sensor pins
  pinMode(SENSOR1, INPUT);
  pinMode(SENSOR2, INPUT);
  pinMode(SENSOR3, INPUT);
  pinMode(SENSOR4, INPUT);
  pinMode(SENSOR5, INPUT);
  
  // Initialize button pins with internal pull-down
  pinMode(START_BUTTON, INPUT_PULLDOWN);
  pinMode(STOP_BUTTON, INPUT_PULLDOWN);
  
  // Initialize motor driver pins
  pinMode(AIN1, OUTPUT);
  pinMode(AIN2, OUTPUT);
  pinMode(BIN1, OUTPUT);
  pinMode(BIN2, OUTPUT);
  pinMode(STBY, OUTPUT);
  
  // Setup PWM channels
  ledcSetup(PWM_CHANNEL_A, PWM_FREQ, PWM_RESOLUTION);
  ledcSetup(PWM_CHANNEL_B, PWM_FREQ, PWM_RESOLUTION);
  ledcAttachPin(PWMA, PWM_CHANNEL_A);
  ledcAttachPin(PWMB, PWM_CHANNEL_B);
  
  // Enable motor driver
  digitalWrite(STBY, HIGH);
  
  // Stop motors initially
  stopMotors();
  
  Serial.println("Line Follower Robot Initialized");
  Serial.println("Press START button to begin");
}

void loop() {
  // Read and handle button presses
  handleButtons();
  
  // Read sensor values
  readSensors();
  
  // Run line following algorithm if robot is started
  if (robotRunning) {
    followLine();
  }
  
  delay(5); // Reduced delay for faster response
}

void handleButtons() {
  // Read current button states
  bool startButtonReading = digitalRead(START_BUTTON);
  bool stopButtonReading = digitalRead(STOP_BUTTON);
  
  // Handle Start Button with debouncing
  if (startButtonReading != lastStartButtonState) {
    lastStartDebounceTime = millis();
  }
  
  if ((millis() - lastStartDebounceTime) > DEBOUNCE_DELAY) {
    if (startButtonReading == HIGH && !robotRunning) {
      robotRunning = true;
      Serial.println("Robot started (Button)");
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
      Serial.println("Robot stopped (Button)");
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
  ledcWrite(PWM_CHANNEL_A, speed);
  ledcWrite(PWM_CHANNEL_B, speed);
}

void moveBackward(int speed) {
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, HIGH);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, HIGH);
  ledcWrite(PWM_CHANNEL_A, speed);
  ledcWrite(PWM_CHANNEL_B, speed);
}

void turnLeft(int leftSpeed, int rightSpeed) {
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, HIGH);
  digitalWrite(BIN1, HIGH);
  digitalWrite(BIN2, LOW);
  ledcWrite(PWM_CHANNEL_A, leftSpeed);
  ledcWrite(PWM_CHANNEL_B, rightSpeed);
}

void turnRight(int leftSpeed, int rightSpeed) {
  digitalWrite(AIN1, HIGH);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, HIGH);
  ledcWrite(PWM_CHANNEL_A, leftSpeed);
  ledcWrite(PWM_CHANNEL_B, rightSpeed);
}

void rotateLeft(int speed) {
  // Rotate in place - left motor backward, right motor forward
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, HIGH);
  digitalWrite(BIN1, HIGH);
  digitalWrite(BIN2, LOW);
  ledcWrite(PWM_CHANNEL_A, speed);
  ledcWrite(PWM_CHANNEL_B, speed);
}

void rotateRight(int speed) {
  // Rotate in place - left motor forward, right motor backward
  digitalWrite(AIN1, HIGH);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, HIGH);
  ledcWrite(PWM_CHANNEL_A, speed);
  ledcWrite(PWM_CHANNEL_B, speed);
}

void stopMotors() {
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, LOW);
  ledcWrite(PWM_CHANNEL_A, 0);
  ledcWrite(PWM_CHANNEL_B, 0);
}