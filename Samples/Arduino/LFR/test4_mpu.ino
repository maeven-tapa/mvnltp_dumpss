// Line Follower Robot with Web Server Control
// ESP32S with TCRT5000 sensors and TB6612FNG motor driver

#include <WiFi.h>
#include <WebServer.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <Adafruit_MPU6050.h>
#include <Adafruit_Sensor.h>

// WiFi credentials
const char* ssid = "Virus 2.4G";
const char* password = "MT42@DATA";

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

// MPU6050 I2C Pins
#define SDA_PIN 14
#define SCL_PIN 42

// Motor speed settings
#define BASE_SPEED 255
#define MAX_SPEED 255
#define TURN_SPEED 200

// PWM settings
#define PWM_FREQ 5000
#define PWM_RESOLUTION 8
#define PWM_CHANNEL_A 0
#define PWM_CHANNEL_B 1

// Global variables
WebServer server(80);
Adafruit_MPU6050 mpu;
bool robotRunning = false;
int sensorValues[5] = {0, 0, 0, 0, 0};
int threshold = 1700; // Adjust based on your sensor calibration
int lastDirection = 0; // -1 for left, 1 for right, 0 for center
float currentAngle = 0.0; // Current heading angle from gyro
float targetAngle = 0.0; // Target angle for turns
unsigned long lastGyroUpdate = 0;

void setup() {
  Serial.begin(115200);
  
  // Initialize sensor pins
  pinMode(SENSOR1, INPUT);
  pinMode(SENSOR2, INPUT);
  pinMode(SENSOR3, INPUT);
  pinMode(SENSOR4, INPUT);
  pinMode(SENSOR5, INPUT);
  
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
  
  // Initialize I2C for MPU6050
  Wire.begin(SDA_PIN, SCL_PIN);
  
  // Initialize MPU6050
  if (!mpu.begin()) {
    Serial.println("Failed to find MPU6050 chip");
  } else {
    Serial.println("MPU6050 Found!");
    mpu.setAccelerometerRange(MPU6050_RANGE_8_G);
    mpu.setGyroRange(MPU6050_RANGE_500_DEG);
    mpu.setFilterBandwidth(MPU6050_BAND_21_HZ);
    lastGyroUpdate = millis();
  }
  
  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.print("Connected! IP address: ");
  Serial.println(WiFi.localIP());
  
  // Setup web server routes
  server.on("/", HTTP_GET, handleRoot);
  server.on("/start", HTTP_GET, handleStart);
  server.on("/stop", HTTP_GET, handleStop);
  server.on("/sensors", HTTP_GET, handleSensors);
  server.on("/imu", HTTP_GET, handleIMU);
  
  server.begin();
  Serial.println("Web server started");
}

void loop() {
  server.handleClient();
  
  // Update gyro angle
  updateGyroAngle();
  
  // Read sensor values
  readSensors();
  
  // Run line following algorithm if robot is started
  if (robotRunning) {
    followLine();
  }
  
  delay(10);
}

void readSensors() {
  sensorValues[0] = analogRead(SENSOR1);
  sensorValues[1] = analogRead(SENSOR2);
  sensorValues[2] = analogRead(SENSOR3);
  sensorValues[3] = analogRead(SENSOR4);
  sensorValues[4] = analogRead(SENSOR5);
}

void updateGyroAngle() {
  sensors_event_t a, g, temp;
  if (mpu.getEvent(&a, &g, &temp)) {
    unsigned long currentTime = millis();
    float dt = (currentTime - lastGyroUpdate) / 1000.0; // Convert to seconds
    lastGyroUpdate = currentTime;
    
    // Swap X and Y axis and invert for 180-degree rotation
    // Original Z becomes our rotation axis
    float gyroZ = -g.gyro.z; // Negate Z for 180-degree flip
    
    // Integrate gyro to get angle (in degrees)
    currentAngle += gyroZ * dt * (180.0 / PI);
    
    // Keep angle in range -180 to 180
    while (currentAngle > 180.0) currentAngle -= 360.0;
    while (currentAngle < -180.0) currentAngle += 360.0;
  }
}

void followLine() {
  // Convert analog values to digital (1 = black line detected, 0 = white)
  bool s1 = sensorValues[0] > threshold;
  bool s2 = sensorValues[1] > threshold;
  bool s3 = sensorValues[2] > threshold;
  bool s4 = sensorValues[3] > threshold;
  bool s5 = sensorValues[4] > threshold;
  
  // Priority-based line following logic (simplified)
  // Check from outside to inside for better responsiveness
  
  if (!s1 && !s2 && !s3 && !s4 && !s5) {
    // No line detected - rotate in the last known direction to find the line
    if (lastDirection == -1) {
      turnLeft(100, TURN_SPEED);  // Both motors active
    }
    else if (lastDirection == 1) {
      turnRight(TURN_SPEED, 100);  // Both motors active
    }
    else {
      turnRight(TURN_SPEED, 100);  // Both motors active
      lastDirection = 1;
    }
  }
  else if (s1) {
    // Far left sensor detected - sharp left turn
    turnLeftControlled(120, BASE_SPEED);  // Increased from 50
    lastDirection = -1;
  }
  else if (s5) {
    // Far right sensor detected - sharp right turn
    turnRightControlled(BASE_SPEED, 120);  // Increased from 50
    lastDirection = 1;
  }
  else if (s2) {
    // Left-center sensor detected - slight left turn
    turnLeftControlled(TURN_SPEED - 50, BASE_SPEED);
    lastDirection = -1;
  }
  else if (s4) {
    // Right-center sensor detected - slight right turn
    turnRightControlled(BASE_SPEED, TURN_SPEED - 50);
    lastDirection = 1;
  }
  else if (s3) {
    // Only center sensor - go straight
    moveForward(BASE_SPEED);
    lastDirection = 0;
    targetAngle = currentAngle;
  }
  else {
    // Fallback - move forward slowly
    moveForward(BASE_SPEED - 50);
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

void stopMotors() {
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, LOW);
  ledcWrite(PWM_CHANNEL_A, 0);
  ledcWrite(PWM_CHANNEL_B, 0);
}

// Gyro-controlled turn functions to prevent overshooting
void turnLeftControlled(int leftSpeed, int rightSpeed) {
  // Basic left turn with gyro monitoring
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, HIGH);
  digitalWrite(BIN1, HIGH);
  digitalWrite(BIN2, LOW);
  
  // Reduce speed based on how much we've turned (prevents overshoot)
  float turnRate = abs(currentAngle - targetAngle);
  int adjustedLeftSpeed = leftSpeed;
  int adjustedRightSpeed = rightSpeed;
  
  // If turning more than 15 degrees, slow down
  if (turnRate > 15.0) {
    adjustedLeftSpeed = leftSpeed * 0.7;
    adjustedRightSpeed = rightSpeed * 0.7;
  }
  
  ledcWrite(PWM_CHANNEL_A, adjustedLeftSpeed);
  ledcWrite(PWM_CHANNEL_B, adjustedRightSpeed);
}

void turnRightControlled(int leftSpeed, int rightSpeed) {
  // Basic right turn with gyro monitoring
  digitalWrite(AIN1, HIGH);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, HIGH);
  
  // Reduce speed based on how much we've turned (prevents overshoot)
  float turnRate = abs(currentAngle - targetAngle);
  int adjustedLeftSpeed = leftSpeed;
  int adjustedRightSpeed = rightSpeed;
  
  // If turning more than 15 degrees, slow down
  if (turnRate > 15.0) {
    adjustedLeftSpeed = leftSpeed * 0.7;
    adjustedRightSpeed = rightSpeed * 0.7;
  }
  
  ledcWrite(PWM_CHANNEL_A, adjustedLeftSpeed);
  ledcWrite(PWM_CHANNEL_B, adjustedRightSpeed);
}

void handleRoot() {
  String html = R"rawliteral(
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Line Follower Robot Control</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding: 20px;
    }
    .container {
      max-width: 800px;
      margin: 0 auto;
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      padding: 30px;
    }
    h1 {
      text-align: center;
      color: #333;
      margin-bottom: 30px;
      font-size: 2em;
    }
    .status {
      text-align: center;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 30px;
      font-weight: bold;
      font-size: 1.2em;
    }
    .status.running {
      background: #4caf50;
      color: white;
    }
    .status.stopped {
      background: #f44336;
      color: white;
    }
    .controls {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-bottom: 40px;
    }
    button {
      padding: 15px 40px;
      font-size: 1.1em;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s;
      font-weight: bold;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.2);
    }
    .start-btn {
      background: #4caf50;
      color: white;
    }
    .start-btn:hover {
      background: #45a049;
    }
    .stop-btn {
      background: #f44336;
      color: white;
    }
    .stop-btn:hover {
      background: #da190b;
    }
    .sensor-panel {
      background: #f5f5f5;
      border-radius: 10px;
      padding: 20px;
    }
    .sensor-panel h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #555;
    }
    .imu-panel {
      background: #f5f5f5;
      border-radius: 10px;
      padding: 20px;
      margin-top: 20px;
    }
    .imu-panel h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #555;
    }
    .imu-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
      margin-bottom: 15px;
    }
    .imu-item {
      background: white;
      padding: 15px;
      border-radius: 8px;
      text-align: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .imu-label {
      font-size: 0.85em;
      color: #666;
      margin-bottom: 8px;
      font-weight: 600;
    }
    .imu-value {
      font-size: 1.3em;
      font-weight: bold;
      color: #333;
    }
    .angle-gauge {
      width: 100%;
      max-width: 200px;
      height: 200px;
      margin: 20px auto;
      position: relative;
    }
    .sensors {
      display: flex;
      justify-content: space-around;
      margin-bottom: 20px;
    }
    .sensor {
      text-align: center;
      flex: 1;
    }
    .sensor-circle {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      margin: 0 auto 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      border: 3px solid #ddd;
      transition: all 0.3s;
    }
    .sensor-circle.active {
      background: #333;
      color: white;
      border-color: #333;
    }
    .sensor-circle.inactive {
      background: white;
      color: #333;
    }
    .sensor-label {
      font-size: 0.9em;
      color: #666;
      margin-bottom: 5px;
    }
    .sensor-value {
      font-size: 0.8em;
      color: #999;
    }
    .info {
      text-align: center;
      margin-top: 20px;
      padding: 15px;
      background: #e3f2fd;
      border-radius: 8px;
      color: #1976d2;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>🤖 Line Follower Robot</h1>
    
    <div id="status" class="status stopped">
      STATUS: STOPPED
    </div>
    
    <div class="controls">
      <button class="start-btn" onclick="startRobot()">▶ START</button>
      <button class="stop-btn" onclick="stopRobot()">⏹ STOP</button>
    </div>
    
    <div class="sensor-panel">
      <h2>Sensor Monitor</h2>
      <div class="sensors">
        <div class="sensor">
          <div class="sensor-label">LEFT 2</div>
          <div id="s1" class="sensor-circle inactive">S1</div>
          <div id="v1" class="sensor-value">0</div>
        </div>
        <div class="sensor">
          <div class="sensor-label">LEFT 1</div>
          <div id="s2" class="sensor-circle inactive">S2</div>
          <div id="v2" class="sensor-value">0</div>
        </div>
        <div class="sensor">
          <div class="sensor-label">CENTER</div>
          <div id="s3" class="sensor-circle inactive">S3</div>
          <div id="v3" class="sensor-value">0</div>
        </div>
        <div class="sensor">
          <div class="sensor-label">RIGHT 1</div>
          <div id="s4" class="sensor-circle inactive">S4</div>
          <div id="v4" class="sensor-value">0</div>
        </div>
        <div class="sensor">
          <div class="sensor-label">RIGHT 2</div>
          <div id="s5" class="sensor-circle inactive">S5</div>
          <div id="v5" class="sensor-value">0</div>
        </div>
      </div>
      <div class="info">
        Sensors update in real-time. Dark circles indicate black line detection.
      </div>
    </div>
    
    <div class="imu-panel">
      <h2>IMU Data (MPU6050)</h2>
      <div class="imu-grid">
        <div class="imu-item">
          <div class="imu-label">Accel X</div>
          <div id="accel-x" class="imu-value">0.00</div>
        </div>
        <div class="imu-item">
          <div class="imu-label">Accel Y</div>
          <div id="accel-y" class="imu-value">0.00</div>
        </div>
        <div class="imu-item">
          <div class="imu-label">Accel Z</div>
          <div id="accel-z" class="imu-value">0.00</div>
        </div>
        <div class="imu-item">
          <div class="imu-label">Gyro X</div>
          <div id="gyro-x" class="imu-value">0.00</div>
        </div>
        <div class="imu-item">
          <div class="imu-label">Gyro Y</div>
          <div id="gyro-y" class="imu-value">0.00</div>
        </div>
        <div class="imu-item">
          <div class="imu-label">Gyro Z</div>
          <div id="gyro-z" class="imu-value">0.00</div>
        </div>
      </div>
      <div class="imu-item" style="max-width: 300px; margin: 0 auto;">
        <div class="imu-label">Heading Angle</div>
        <div id="heading-angle" class="imu-value" style="font-size: 2em; color: #667eea;">0.0°</div>
      </div>
    </div>
  </div>
  
  <script>
    const threshold = 1700;
    
    function startRobot() {
      fetch('/start')
        .then(response => response.text())
        .then(data => {
          document.getElementById('status').className = 'status running';
          document.getElementById('status').textContent = 'STATUS: RUNNING';
        })
        .catch(error => console.error('Error:', error));
    }
    
    function stopRobot() {
      fetch('/stop')
        .then(response => response.text())
        .then(data => {
          document.getElementById('status').className = 'status stopped';
          document.getElementById('status').textContent = 'STATUS: STOPPED';
        })
        .catch(error => console.error('Error:', error));
    }
    
    function updateSensors() {
      fetch('/sensors')
        .then(response => response.json())
        .then(data => {
          for (let i = 1; i <= 5; i++) {
            const value = data.sensors[i-1];
            const sensorCircle = document.getElementById('s' + i);
            const valueDisplay = document.getElementById('v' + i);
            
            valueDisplay.textContent = value;
            
            if (value > threshold) {
              sensorCircle.className = 'sensor-circle active';
            } else {
              sensorCircle.className = 'sensor-circle inactive';
            }
          }
        })
        .catch(error => console.error('Error:', error));
    }
    
    function updateIMU() {
      fetch('/imu')
        .then(response => response.json())
        .then(data => {
          document.getElementById('accel-x').textContent = data.accel.x.toFixed(2);
          document.getElementById('accel-y').textContent = data.accel.y.toFixed(2);
          document.getElementById('accel-z').textContent = data.accel.z.toFixed(2);
          document.getElementById('gyro-x').textContent = data.gyro.x.toFixed(2);
          document.getElementById('gyro-y').textContent = data.gyro.y.toFixed(2);
          document.getElementById('gyro-z').textContent = data.gyro.z.toFixed(2);
          document.getElementById('heading-angle').textContent = data.angle.toFixed(1) + '°';
        })
        .catch(error => console.error('Error:', error));
    }
    
    // Update sensors every 100ms
    setInterval(updateSensors, 100);
    setInterval(updateIMU, 100);
    updateSensors();
    updateIMU();
  </script>
</body>
</html>
)rawliteral";
  
  server.send(200, "text/html", html);
}

void handleStart() {
  robotRunning = true;
  Serial.println("Robot started");
  server.send(200, "text/plain", "Robot started");
}

void handleStop() {
  robotRunning = false;
  stopMotors();
  Serial.println("Robot stopped");
  server.send(200, "text/plain", "Robot stopped");
}

void handleSensors() {
  String json = "{\"sensors\":[";
  json += String(sensorValues[0]) + ",";
  json += String(sensorValues[1]) + ",";
  json += String(sensorValues[2]) + ",";
  json += String(sensorValues[3]) + ",";
  json += String(sensorValues[4]);
  json += "]}";
  
  server.send(200, "application/json", json);
}

void handleIMU() {
  sensors_event_t a, g, temp;
  String json = "{";
  
  if (mpu.getEvent(&a, &g, &temp)) {
    json += "\"accel\":{";
    json += "\"x\":" + String(a.acceleration.x, 2) + ",";
    json += "\"y\":" + String(a.acceleration.y, 2) + ",";
    json += "\"z\":" + String(a.acceleration.z, 2);
    json += "},";
    json += "\"gyro\":{";
    json += "\"x\":" + String(g.gyro.x, 2) + ",";
    json += "\"y\":" + String(g.gyro.y, 2) + ",";
    json += "\"z\":" + String(g.gyro.z, 2);
    json += "},";
    json += "\"angle\":" + String(currentAngle, 2);
  } else {
    json += "\"accel\":{\"x\":0,\"y\":0,\"z\":0},";
    json += "\"gyro\":{\"x\":0,\"y\":0,\"z\":0},";
    json += "\"angle\":0";
  }
  
  json += "}";
  server.send(200, "application/json", json);
}
