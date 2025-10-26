#include <WiFi.h>
#include <WebServer.h>
#include <Wire.h>
#include <Adafruit_MPU6050.h>
#include <Adafruit_Sensor.h>

// WiFi credentials
const char* ssid = "Virus 2.4G";
const char* password = "MT42@DATA";

WebServer server(80);

// MPU6050
Adafruit_MPU6050 mpu;
#define SDA_PIN 14
#define SCL_PIN 42
float gyroZOffset = 0;
float accelXOffset = 0;
float accelYOffset = 0;
float heading = 0; // Integrated heading from gyro
unsigned long lastMPURead = 0;
float mpuX = 0, mpuY = 0, mpuZ = 0; // Display values (reversed X/Y)
const float MPU_OFFSET_CM = 14.0; // 14cm between MPU and IR sensor

// TB6612FNG Motor Driver Pins
#define PWMA 21
#define AIN2 35
#define AIN1 36
#define STBY 37
#define BIN1 38
#define BIN2 39
#define PWMB 40

// TCRT5000 Digital Sensor Pins
#define SENSOR1 15
#define SENSOR2 7
#define SENSOR3 6
#define SENSOR4 5
#define SENSOR5 4

// PWM properties
const int freq = 5000;
const int motorAChannel = 0;
const int motorBChannel = 1;
const int resolution = 8;

const uint8_t SensorCount = 5;
int sensorValues[SensorCount];
bool sensorDetected[SensorCount];
int sensorPins[SensorCount] = {SENSOR1, SENSOR2, SENSOR3, SENSOR4, SENSOR5};
const int analogMax = 4095;
int sensorThreshold = 2000;
const int sensorThresholdMax = 3000;
const int forwardButtonPin = 20;
const int stopButtonPin = 1;

const char* movementMessage = "Robot stopped";

float Kp = 0;
float Ki = 0;
float Kd = 0;
float KpGyro = 0; // Gyro correction gain

uint8_t multiP = 1;
uint8_t multiI = 1;
uint8_t multiD = 1;
float Pvalue;
float Ivalue;
float Dvalue;
float GyroCorrection = 0;

boolean onoff = false;
int position = 0;
int error = 0;
int P = 0;
int I = 0;
int D = 0;
int previousError = 0;
int lsp = 0;
int rsp = 0;
int lfspeed = 230;

enum MovementState : uint8_t { MovementUnknown, MovementStraight, MovementLeft, MovementRight };
MovementState lastMovement = MovementUnknown;
enum ControlMode : uint8_t { ModeLineFollower, ModeManual };
ControlMode controlMode = ModeLineFollower;
enum ManualDirection : uint8_t { ManualStop, ManualForward, ManualBackward, ManualLeft, ManualRight };
ManualDirection manualDirection = ManualStop;
ManualDirection lastManualDirection = ManualStop;
const int manualSpeed = 255;

void logMovement(MovementState state, const char* message) {
  if (state != lastMovement) {
    Serial.println(message);
    movementMessage = message;
    lastMovement = state;
  }
}

// HTML web page
const char index_html[] PROGMEM = R"rawliteral(
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Line Follower Control</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }
    .container {
      background: white;
      border-radius: 20px;
      padding: 30px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      max-width: 500px;
      width: 100%;
    }
    h1 {
      text-align: center;
      color: #333;
      margin-bottom: 30px;
      font-size: 24px;
    }
    .pid-group {
      background: #f8f9fa;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 15px;
    }
    .pid-title {
      font-size: 18px;
      font-weight: bold;
      color: #667eea;
      margin-bottom: 15px;
    }
    .slider-container {
      margin-bottom: 15px;
    }
    label {
      display: block;
      margin-bottom: 8px;
      color: #555;
      font-size: 14px;
      font-weight: 600;
    }
    .slider-wrapper {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    input[type="range"] {
      flex: 1;
      height: 8px;
      border-radius: 5px;
      background: #ddd;
      outline: none;
      -webkit-appearance: none;
    }
    input[type="range"]::-webkit-slider-thumb {
      -webkit-appearance: none;
      appearance: none;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: #667eea;
      cursor: pointer;
    }
    input[type="range"]::-moz-range-thumb {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: #667eea;
      cursor: pointer;
      border: none;
    }
    .value-display {
      min-width: 80px;
      text-align: right;
      font-weight: bold;
      color: #333;
      font-size: 16px;
    }
    .button-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
      margin-top: 30px;
    }
    button {
      padding: 20px;
      font-size: 18px;
      font-weight: bold;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s;
      color: white;
    }
    #forwardBtn {
      background: #10b981;
    }
    #forwardBtn:hover {
      background: #059669;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
    }
    #stopBtn {
      background: #ef4444;
    }
    #stopBtn:hover {
      background: #dc2626;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
    }
    .status {
      text-align: center;
      margin-top: 20px;
      padding: 15px;
      border-radius: 10px;
      font-weight: bold;
      font-size: 16px;
    }
    .status.running {
      background: #d1fae5;
      color: #065f46;
    }
    .status.stopped {
      background: #fee2e2;
      color: #991b1b;
    }
    .controller-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
      margin-top: 20px;
    }
    .controller-grid span {
      visibility: hidden;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Group 1 - Line Follower Robot</h1>
    
    <div class="pid-group">
      <div class="pid-title">Kp (Proportional)</div>
      <div class="slider-container">
        <label>Value (0-255)</label>
        <div class="slider-wrapper">
          <input type="range" id="kp" min="0" max="255" value="0" oninput="updateValue('kp')">
          <span class="value-display" id="kpValue">0</span>
        </div>
      </div>
      <div class="slider-container">
        <label>Multiplier (10^x)</label>
        <div class="slider-wrapper">
          <input type="range" id="multiP" min="0" max="5" value="1" oninput="updateValue('multiP')">
          <span class="value-display" id="multiPValue">1</span>
        </div>
      </div>
      <div style="text-align: right; margin-top: 10px; color: #667eea; font-weight: bold;">
        Final Kp: <span id="finalKp">0.0</span>
      </div>
    </div>

    <div class="pid-group">
      <div class="pid-title">Ki (Integral)</div>
      <div class="slider-container">
        <label>Value (0-255)</label>
        <div class="slider-wrapper">
          <input type="range" id="ki" min="0" max="255" value="0" oninput="updateValue('ki')">
          <span class="value-display" id="kiValue">0</span>
        </div>
      </div>
      <div class="slider-container">
        <label>Multiplier (10^x)</label>
        <div class="slider-wrapper">
          <input type="range" id="multiI" min="0" max="5" value="1" oninput="updateValue('multiI')">
          <span class="value-display" id="multiIValue">1</span>
        </div>
      </div>
      <div style="text-align: right; margin-top: 10px; color: #667eea; font-weight: bold;">
        Final Ki: <span id="finalKi">0.0</span>
      </div>
    </div>

    <div class="pid-group">
      <div class="pid-title">Kd (Derivative)</div>
      <div class="slider-container">
        <label>Value (0-255)</label>
        <div class="slider-wrapper">
          <input type="range" id="kd" min="0" max="255" value="0" oninput="updateValue('kd')">
          <span class="value-display" id="kdValue">0</span>
        </div>
      </div>
      <div class="slider-container">
        <label>Multiplier (10^x)</label>
        <div class="slider-wrapper">
          <input type="range" id="multiD" min="0" max="5" value="1" oninput="updateValue('multiD')">
          <span class="value-display" id="multiDValue">1</span>
        </div>
      </div>
      <div style="text-align: right; margin-top: 10px; color: #667eea; font-weight: bold;">
        Final Kd: <span id="finalKd">0.0</span>
      </div>
    </div>

    <div class="pid-group">
      <div class="pid-title">Kp Gyro (Heading Correction)</div>
      <div class="slider-container">
        <label>Value (0-255)</label>
        <div class="slider-wrapper">
          <input type="range" id="kpGyro" min="0" max="255" value="0" oninput="updateValue('kpGyro')">
          <span class="value-display" id="kpGyroValue">0</span>
        </div>
      </div>
    </div>

    <div class="pid-group">
      <div class="pid-title">Threshold</div>
      <div class="slider-container">
        <label>Sensor Trigger (0-3000)</label>
        <div class="slider-wrapper">
          <input type="range" id="threshold" min="0" max="3000" value="2000" oninput="updateValue('threshold')">
          <span class="value-display" id="thresholdValue">2000</span>
        </div>
      </div>
    </div>

    <div class="button-container">
      <button id="forwardBtn" onclick="sendCommand('forward')">Forward</button>
      <button id="stopBtn" onclick="sendCommand('stop')">Stop</button>
    </div>

    <div class="pid-group">
      <div class="pid-title">MPU6050 Sensor (X/Y Reversed)</div>
      <div id="mpuReadings" style="font-family:'Courier New',monospace;line-height:1.8;font-size:14px;">
        Accel X: 0.00 m/s²<br>
        Accel Y: 0.00 m/s²<br>
        Gyro Z: 0.00 °/s<br>
        Heading: 0.00 °
      </div>
      <button onclick="calibrateMPU()" style="margin-top:10px;width:100%;padding:12px;background:#667eea;color:white;border:none;border-radius:8px;cursor:pointer;font-weight:bold;">Calibrate MPU (Set Zero)</button>
    </div>

    <div class="pid-group">
      <div class="pid-title">Manual Control</div>
      <div class="controller-grid">
        <span></span>
        <button onclick="sendManual(1)">UP</button>
        <span></span>
        <button onclick="sendManual(3)">LEFT</button>
        <button onclick="sendManual(0)">STOP</button>
        <button onclick="sendManual(4)">RIGHT</button>
        <span></span>
        <button onclick="sendManual(2)">DOWN</button>
        <span></span>
      </div>
    </div>
    <div class="status stopped" id="status">Status: STOPPED</div>
  </div>

  <script>
    function updateValue(id) {
      const slider = document.getElementById(id);
      const valueDisplay = document.getElementById(id + 'Value');
      valueDisplay.textContent = slider.value;
      
      updateFinalValues();
      
      let command = '';
      if (id === 'kp') command = '1';
      else if (id === 'multiP') command = '2';
      else if (id === 'ki') command = '3';
      else if (id === 'multiI') command = '4';
      else if (id === 'kd') command = '5';
      else if (id === 'multiD') command = '6';
      else if (id === 'threshold') command = '8';
      else if (id === 'kpGyro') command = '10';
      fetch('/update?cmd=' + command + '&val=' + slider.value);
    }

    function updateFinalValues() {
      const kp = document.getElementById('kp').value;
      const multiP = document.getElementById('multiP').value;
      const ki = document.getElementById('ki').value;
      const multiI = document.getElementById('multiI').value;
      const kd = document.getElementById('kd').value;
      const multiD = document.getElementById('multiD').value;
      
      document.getElementById('finalKp').textContent = (kp / Math.pow(10, multiP)).toFixed(5);
      document.getElementById('finalKi').textContent = (ki / Math.pow(10, multiI)).toFixed(5);
      document.getElementById('finalKd').textContent = (kd / Math.pow(10, multiD)).toFixed(5);
    }

    const statusEl = document.getElementById('status');
    const sensorGroup = document.createElement('div');
    sensorGroup.className = 'pid-group';
    sensorGroup.innerHTML = `
      <div class="pid-title">Sensor Monitor</div>
      <div id="sensorReadings" style="font-family:'Courier New',monospace;line-height:1.6;">Awaiting data...</div>
      <div id="directionStatus" style="margin-top:10px;font-weight:bold;color:#065f46;">Direction: --</div>
    `;
    statusEl.insertAdjacentElement('afterend', sensorGroup);

    function fetchSensors() {
      fetch('/sensors')
        .then(response => response.json())
        .then(data => {
          const readings = data.values.map((value, index) => `S${index + 1}: ${value}`).join('<br>');
          document.getElementById('sensorReadings').innerHTML = readings + `<br>Threshold: ${data.threshold}`;
          const thresholdSlider = document.getElementById('threshold');
          if (thresholdSlider) {
            thresholdSlider.value = data.threshold;
            document.getElementById('thresholdValue').textContent = data.threshold;
          }
          document.getElementById('directionStatus').textContent = `Direction: ${data.direction}`;
          
          // Update MPU readings
          if (data.mpu) {
            document.getElementById('mpuReadings').innerHTML = 
              `Accel X: ${data.mpu.x.toFixed(2)} m/s²<br>` +
              `Accel Y: ${data.mpu.y.toFixed(2)} m/s²<br>` +
              `Gyro Z: ${data.mpu.z.toFixed(2)} °/s<br>` +
              `Heading: ${data.mpu.heading.toFixed(2)} °`;
          }
        })
        .catch(() => {
          document.getElementById('sensorReadings').textContent = 'Sensor read failed';
          document.getElementById('directionStatus').textContent = 'Direction: unknown';
        });
    }

    setInterval(fetchSensors, 500);
    fetchSensors();

    function calibrateMPU() {
      fetch('/update?cmd=11&val=1')
        .then(() => alert('MPU6050 calibrated! Current position set to zero.'))
        .catch(() => alert('Calibration failed'));
    }

    function sendCommand(cmd) {
      const status = document.getElementById('status');
      if (cmd === 'forward') {
        fetch('/update?cmd=7&val=1');
        status.textContent = 'Status: RUNNING';
        status.className = 'status running';
      } else {
        fetch('/update?cmd=7&val=0');
        status.textContent = 'Status: STOPPED';
        status.className = 'status stopped';
      }
    }

    function sendManual(direction) {
      const labels = {
        0: 'MANUAL STOP',
        1: 'MANUAL FORWARD',
        2: 'MANUAL BACKWARD',
        3: 'MANUAL LEFT',
        4: 'MANUAL RIGHT'
      };
      fetch('/update?cmd=9&val=' + direction);
      statusEl.textContent = 'Status: ' + labels[direction];
      statusEl.className = direction === 0 ? 'status stopped' : 'status running';
    }

    // Initialize display
    updateFinalValues();
  </script>
</body>
</html>
)rawliteral";

void setup() {
  Serial.begin(115200);
  
  // Initialize I2C for MPU6050
  Wire.begin(SDA_PIN, SCL_PIN);
  
  // Initialize MPU6050
  if (!mpu.begin(0x68, &Wire)) {
    Serial.println("Failed to find MPU6050 chip");
  } else {
    Serial.println("MPU6050 Found!");
    mpu.setAccelerometerRange(MPU6050_RANGE_4_G);
    mpu.setGyroRange(MPU6050_RANGE_500_DEG);
    mpu.setFilterBandwidth(MPU6050_BAND_21_HZ);
    delay(100);
    calibrateMPU6050();
  }
  
  // Configure motor driver pins
  pinMode(PWMA, OUTPUT);
  pinMode(AIN1, OUTPUT);
  pinMode(AIN2, OUTPUT);
  pinMode(STBY, OUTPUT);
  pinMode(BIN1, OUTPUT);
  pinMode(BIN2, OUTPUT);
  pinMode(PWMB, OUTPUT);

  // Setup PWM channels
  ledcSetup(motorAChannel, freq, resolution);
  ledcSetup(motorBChannel, freq, resolution);
  ledcAttachPin(PWMA, motorAChannel);
  ledcAttachPin(PWMB, motorBChannel);

  // Enable motor driver
  digitalWrite(STBY, HIGH);

  // Configure digital sensor pins as INPUT
  for (int i = 0; i < SensorCount; i++) {
    pinMode(sensorPins[i], INPUT);
  }
  analogReadResolution(12);
  pinMode(forwardButtonPin, INPUT_PULLDOWN);
  pinMode(stopButtonPin, INPUT_PULLDOWN);

  pinMode(LED_BUILTIN, OUTPUT);

  // Connect to WiFi
  Serial.println("Connecting to WiFi...");
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    digitalWrite(LED_BUILTIN, !digitalRead(LED_BUILTIN));
  }
  
  digitalWrite(LED_BUILTIN, LOW);
  Serial.println("\nWiFi connected!");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());

  // Setup web server routes
  server.on("/", handleRoot);
  server.on("/update", handleUpdate);
  server.on("/sensors", handleSensors);
  
  server.begin();
  Serial.println("Web server started!");
}

void loop() {
  server.handleClient();
  handleButtons();
  updateMPU6050();

  if (controlMode == ModeLineFollower) {
    if (onoff == true) {
      robot_control();
    } else {
      stopMotors();
    }
  } else {
    applyManualControl();
  }
}

void handleRoot() {
  server.send(200, "text/html", index_html);
}

void handleUpdate() {
  if (server.hasArg("cmd") && server.hasArg("val")) {
    int cmd = server.arg("cmd").toInt();
    int val = server.arg("val").toInt();
    
    switch(cmd) {
      case 1:
        Kp = val;
        Serial.print("Kp set to: ");
        Serial.println(Kp);
        break;
      case 2:
        multiP = val;
        Serial.print("multiP set to: ");
        Serial.println(multiP);
        break;
      case 3:
        Ki = val;
        Serial.print("Ki set to: ");
        Serial.println(Ki);
        break;
      case 4:
        multiI = val;
        Serial.print("multiI set to: ");
        Serial.println(multiI);
        break;
      case 5:
        Kd = val;
        Serial.print("Kd set to: ");
        Serial.println(Kd);
        break;
      case 6:
        multiD = val;
        Serial.print("multiD set to: ");
        Serial.println(multiD);
        break;
      case 7:
        onoff = (val == 1);
        lastMovement = MovementUnknown;
        controlMode = ModeLineFollower;
        manualDirection = ManualStop;
        lastManualDirection = ManualStop;
        movementMessage = onoff ? "Robot active" : "Robot stopped";
        heading = 0; // Reset heading on start
        Serial.print("Robot ");
        Serial.println(onoff ? "STARTED" : "STOPPED");
        if (!onoff) {
          stopMotors();
        }
        break;
      case 8:
        sensorThreshold = constrain(val, 0, sensorThresholdMax);
        Serial.print("Threshold set to: ");
        Serial.println(sensorThreshold);
        break;
      case 9:
        controlMode = ModeManual;
        onoff = false;
        switch (val) {
          case 1: manualDirection = ManualForward; break;
          case 2: manualDirection = ManualBackward; break;
          case 3: manualDirection = ManualLeft; break;
          case 4: manualDirection = ManualRight; break;
          default: manualDirection = ManualStop; break;
        }
        applyManualControl();
        break;
      case 10:
        KpGyro = val;
        Serial.print("KpGyro set to: ");
        Serial.println(KpGyro);
        break;
      case 11:
        calibrateMPU6050();
        Serial.println("MPU6050 recalibrated");
        break;
    }
  }
  server.send(200, "text/plain", "OK");
}

void handleSensors() {
  readSensors();
  String json = "{\"values\":[";
  for (int i = 0; i < SensorCount; i++) {
    if (i > 0) json += ",";
    json += String(sensorValues[i]);
  }
  json += "],\"threshold\":" + String(sensorThreshold);
  json += ",\"direction\":\"" + String(movementMessage) + "\"";
  json += ",\"mpu\":{\"x\":" + String(mpuX, 2) + ",\"y\":" + String(mpuY, 2) + ",\"z\":" + String(mpuZ, 2) + ",\"heading\":" + String(heading, 2) + "}}";
  server.send(200, "application/json", json);
}

void calibrateMPU6050() {
  Serial.println("Calibrating MPU6050... Keep robot still!");
  gyroZOffset = 0;
  accelXOffset = 0;
  accelYOffset = 0;
  heading = 0;
  
  const int samples = 100;
  for (int i = 0; i < samples; i++) {
    sensors_event_t a, g, temp;
    mpu.getEvent(&a, &g, &temp);
    // Reversed: X becomes Y, Y becomes X
    gyroZOffset += g.gyro.z;
    accelXOffset += a.acceleration.y; // Reversed
    accelYOffset += a.acceleration.x; // Reversed
    delay(10);
  }
  gyroZOffset /= samples;
  accelXOffset /= samples;
  accelYOffset /= samples;
  
  Serial.println("MPU6050 Calibration complete!");
  Serial.print("Gyro Z offset: "); Serial.println(gyroZOffset);
  Serial.print("Accel X offset: "); Serial.println(accelXOffset);
  Serial.print("Accel Y offset: "); Serial.println(accelYOffset);
}

void updateMPU6050() {
  unsigned long currentTime = millis();
  if (currentTime - lastMPURead < 20) return; // Read at 50Hz
  
  float dt = (currentTime - lastMPURead) / 1000.0;
  lastMPURead = currentTime;
  
  sensors_event_t a, g, temp;
  if (!mpu.getEvent(&a, &g, &temp)) return;
  
  // Reverse X and Y axes (robot mounted reversed)
  mpuX = a.acceleration.y - accelXOffset;
  mpuY = a.acceleration.x - accelYOffset;
  mpuZ = g.gyro.z - gyroZOffset;
  
  // Integrate gyro for heading (only when moving)
  if (onoff && controlMode == ModeLineFollower) {
    heading += mpuZ * dt;
    // Keep heading in range -180 to 180
    if (heading > 180) heading -= 360;
    if (heading < -180) heading += 360;
  }
}

void robot_control() {
  readSensors();
  position = calculatePosition();
  error = -position;

  bool lineLost = true;
  for (int i = 0; i < SensorCount; i++) {
    if (sensorDetected[i]) {
      lineLost = false;
      break;
    }
  }

  if (lineLost) {
    if (previousError > 0) {
      motor_drive(-230, 230);
      logMovement(MovementRight, "Robot going right");
    } else {
      motor_drive(230, -230);
      logMovement(MovementLeft, "Robot going left");
    }
    return;
  }
  PID_Linefollow(error);
}

void readSensors() {
  for (int i = 0; i < SensorCount; i++) {
    sensorValues[i] = analogRead(sensorPins[i]);
    sensorDetected[i] = sensorValues[i] > sensorThreshold;
  }
}


int calculatePosition() {
  int weights[5] = {-2000, -1000, 0, 1000, 2000};
  long weightedSum = 0;
  long sum = 0;

  for (int i = 0; i < SensorCount; i++) {
    int value = analogMax - sensorValues[i];
    if (value < 0) value = 0;
    weightedSum += (long)value * weights[i];
    sum += value;
  }

  if (sum == 0) {
    return previousError;
  }

  return (int)(weightedSum / sum);
}

void PID_Linefollow(int error) {
  P = error;
  I = I + error;
  D = error - previousError;

  Pvalue = (Kp / pow(10, multiP)) * P;
  Ivalue = (Ki / pow(10, multiI)) * I;
  Dvalue = (Kd / pow(10, multiD)) * D;
  
  // Add gyro heading correction (compensate for 14cm offset)
  // Heading error tries to keep robot pointing straight
  GyroCorrection = KpGyro * heading;

  float PIDvalue = Pvalue + Ivalue + Dvalue + GyroCorrection;
  previousError = error;

  lsp = lfspeed - PIDvalue;
  rsp = lfspeed + PIDvalue;

  if (lsp > 255) lsp = 255;
  if (lsp < -255) lsp = -255;
  if (rsp > 255) rsp = 255;
  if (rsp < -255) rsp = -255;
  
  motor_drive(lsp, rsp);
  if (abs(PIDvalue) < 20) {
    logMovement(MovementStraight, "Robot going straight");
  } else if (PIDvalue > 0) {
    logMovement(MovementLeft, "Robot going left");
  } else {
    logMovement(MovementRight, "Robot going right");
  }
}

void motor_drive(int left, int right) {
  if (left > 0) {
    digitalWrite(AIN1, HIGH);
    digitalWrite(AIN2, LOW);
    ledcWrite(motorAChannel, abs(left));
  } else if (left < 0) {
    digitalWrite(AIN1, LOW);
    digitalWrite(AIN2, HIGH);
    ledcWrite(motorAChannel, abs(left));
  } else {
    digitalWrite(AIN1, LOW);
    digitalWrite(AIN2, LOW);
    ledcWrite(motorAChannel, 0);
  }

  if (right > 0) {
    digitalWrite(BIN1, HIGH);
    digitalWrite(BIN2, LOW);
    ledcWrite(motorBChannel, abs(right));
  } else if (right < 0) {
    digitalWrite(BIN1, LOW);
    digitalWrite(BIN2, HIGH);
    ledcWrite(motorBChannel, abs(right));
  } else {
    digitalWrite(BIN1, LOW);
    digitalWrite(BIN2, LOW);
    ledcWrite(motorBChannel, 0);
  }
}

void stopMotors() {
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, LOW);
  ledcWrite(motorAChannel, 0);
  ledcWrite(motorBChannel, 0);
  movementMessage = "Robot stopped";
  lastMovement = MovementUnknown;
}

void applyManualControl() {
  int left = 0;
  int right = 0;
  const char* manualMsg = "Manual stop";

  switch (manualDirection) {
    case ManualForward:
      left = manualSpeed;
      right = manualSpeed;
      manualMsg = "Manual forward";
      break;
    case ManualBackward:
      left = -manualSpeed;
      right = -manualSpeed;
      manualMsg = "Manual backward";
      break;
    case ManualLeft:
      left = -manualSpeed;
      right = manualSpeed;
      manualMsg = "Manual left";
      break;
    case ManualRight:
      left = manualSpeed;
      right = -manualSpeed;
      manualMsg = "Manual right";
      break;
    default:
      break;
  }

  motor_drive(left, right);

  if (manualDirection != lastManualDirection) {
    Serial.println(manualMsg);
    movementMessage = manualMsg;
    lastMovement = MovementUnknown;
    lastManualDirection = manualDirection;
  } else if (manualDirection == ManualStop) {
    movementMessage = manualMsg;
    lastMovement = MovementUnknown;
  }
}

void handleButtons() {
  static bool lastForwardState = false;
  static bool lastStopState = false;
  bool forwardState = digitalRead(forwardButtonPin);
  bool stopState = digitalRead(stopButtonPin);

  if (forwardState && !lastForwardState) {
    onoff = true;
    controlMode = ModeLineFollower;
    manualDirection = ManualStop;
    lastManualDirection = ManualStop;
    movementMessage = "Robot active";
    lastMovement = MovementUnknown;
    Serial.println("Robot STARTED (button)");
  }
  if (stopState && !lastStopState) {
    onoff = false;
    controlMode = ModeLineFollower;
    manualDirection = ManualStop;
    lastManualDirection = ManualStop;
    stopMotors();
    Serial.println("Robot STOPPED (button)");
  }

  lastForwardState = forwardState;
  lastStopState = stopState;
}