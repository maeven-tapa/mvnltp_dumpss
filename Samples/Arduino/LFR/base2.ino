// Line Following Robot - ESP32-S3
// TB6612FNG Motor Driver + 5-Channel TCRT5000 IR Sensors
// Web Server for PID Tuning and Sensor Monitoring

#include <WiFi.h>
#include <WebServer.h>
#include <ESPmDNS.h>

// ========== WiFi Configuration ==========
const char* ssid = "Virus 2.4G";        // Replace with your WiFi SSID
const char* password = "MT42@DATA"; // Replace with your WiFi password

// ========== Pin Definitions ==========
// IR Sensors (Analog)
#define SENSOR1 15  // Leftmost
#define SENSOR2 7
#define SENSOR3 6   // Center
#define SENSOR4 5
#define SENSOR5 4   // Rightmost

// TB6612FNG Motor Driver
#define PWMA 21
#define AIN2 35
#define AIN1 36
#define STBY 37
#define BIN1 38
#define BIN2 39
#define PWMB 40

// Buttons
#define BUTTON_START 20
#define BUTTON_STOP 1

// ========== Motor & Robot Constants ==========
#define PWM_FREQ 5000
#define PWM_RESOLUTION 8
#define PWM_CHANNEL_A 0
#define PWM_CHANNEL_B 1

// PWM range adjusted for 7.4V (instead of 6V rated)
// Limiting to ~80% to stay within motor tolerance
#define MAX_PWM 205  // 80% of 255 = 204
#define MIN_PWM 0

// ========== Speed Settings ==========
int baseSpeed = 160;        // Base speed for straight line (~800 RPM)
int minSpeed = 100;         // Minimum speed in curves (~400 RPM)
int maxSpeed = 205;         // Maximum speed

// ========== PID Variables ==========
float Kp = 2.5;             // Proportional gain
float Ki = 0.0;             // Integral gain
float Kd = 1.5;             // Derivative gain
float multiplier = 1.0;     // PID multiplier

float lastError = 0;
float integral = 0;
float derivative = 0;

// ========== Sensor Variables ==========
int sensorValues[5];
int sensorMin[5] = {0, 0, 0, 0, 0};
int sensorMax[5] = {4095, 4095, 4095, 4095, 4095};
int calibratedValues[5];
int threshold = 2000;       // Threshold for line detection

// ========== Robot State ==========
bool robotRunning = false;
bool lastStartState = HIGH;
bool lastStopState = HIGH;
unsigned long lastDebounceTime = 0;
unsigned long debounceDelay = 50;

// ========== Web Server ==========
WebServer server(80);

// ========== Setup Function ==========
void setup() {
  Serial.begin(115200);
  Serial.println("Line Following Robot Starting...");
  
  // Initialize sensor pins
  pinMode(SENSOR1, INPUT);
  pinMode(SENSOR2, INPUT);
  pinMode(SENSOR3, INPUT);
  pinMode(SENSOR4, INPUT);
  pinMode(SENSOR5, INPUT);
  
  // Initialize motor pins
  pinMode(AIN1, OUTPUT);
  pinMode(AIN2, OUTPUT);
  pinMode(BIN1, OUTPUT);
  pinMode(BIN2, OUTPUT);
  pinMode(STBY, OUTPUT);
  
  // Initialize PWM
  ledcSetup(PWM_CHANNEL_A, PWM_FREQ, PWM_RESOLUTION);
  ledcSetup(PWM_CHANNEL_B, PWM_FREQ, PWM_RESOLUTION);
  ledcAttachPin(PWMA, PWM_CHANNEL_A);
  ledcAttachPin(PWMB, PWM_CHANNEL_B);
  
  // Initialize buttons
  pinMode(BUTTON_START, INPUT_PULLUP);
  pinMode(BUTTON_STOP, INPUT_PULLUP);
  
  // Enable motor driver
  digitalWrite(STBY, HIGH);
  
  // Stop motors initially
  stopMotors();
  
  // Connect to WiFi
  Serial.println("Connecting to WiFi...");
  WiFi.begin(ssid, password);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi connected!");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
    
    // Start mDNS
    if (MDNS.begin("lfr")) {
      Serial.println("mDNS started: http://lfr.local");
    }
    
    // Setup web server routes
    server.on("/", handleRoot);
    server.on("/sensors", handleSensors);
    server.on("/setPID", handleSetPID);
    server.on("/calibrate", handleCalibrate);
    server.on("/start", handleStartRobot);
    server.on("/stop", handleStopRobot);
    
    server.begin();
    Serial.println("Web server started");
  } else {
    Serial.println("\nWiFi connection failed. Continuing without web server.");
  }
  
  // Initial sensor calibration
  Serial.println("Place robot on track. Calibrating in 3 seconds...");
  delay(3000);
  calibrateSensors();
  
  Serial.println("Setup complete!");
}

// ========== Main Loop ==========
void loop() {
  // Handle web server
  server.handleClient();
  
  // Handle button inputs
  handleButtons();
  
  // Read sensors
  readSensors();
  
  // Run line following if enabled
  if (robotRunning) {
    followLine();
  } else {
    stopMotors();
  }
  
  delay(10); // Small delay for stability
}

// ========== Sensor Functions ==========
void readSensors() {
  sensorValues[0] = analogRead(SENSOR1);
  sensorValues[1] = analogRead(SENSOR2);
  sensorValues[2] = analogRead(SENSOR3);
  sensorValues[3] = analogRead(SENSOR4);
  sensorValues[4] = analogRead(SENSOR5);
  
  // Calibrate sensor values
  for (int i = 0; i < 5; i++) {
    calibratedValues[i] = map(sensorValues[i], sensorMin[i], sensorMax[i], 0, 4095);
    calibratedValues[i] = constrain(calibratedValues[i], 0, 4095);
  }
}

void calibrateSensors() {
  Serial.println("Calibrating sensors...");
  
  // Reset min/max
  for (int i = 0; i < 5; i++) {
    sensorMin[i] = 4095;
    sensorMax[i] = 0;
  }
  
  // Sample sensors for 5 seconds
  unsigned long startTime = millis();
  while (millis() - startTime < 5000) {
    for (int i = 0; i < 5; i++) {
      int value = 0;
      if (i == 0) value = analogRead(SENSOR1);
      else if (i == 1) value = analogRead(SENSOR2);
      else if (i == 2) value = analogRead(SENSOR3);
      else if (i == 3) value = analogRead(SENSOR4);
      else if (i == 4) value = analogRead(SENSOR5);
      
      if (value < sensorMin[i]) sensorMin[i] = value;
      if (value > sensorMax[i]) sensorMax[i] = value;
    }
    delay(10);
  }
  
  Serial.println("Calibration complete!");
  for (int i = 0; i < 5; i++) {
    Serial.printf("Sensor %d: Min=%d, Max=%d\n", i+1, sensorMin[i], sensorMax[i]);
  }
}

int getPosition() {
  // Calculate weighted average position
  // Position range: -2000 (far left) to +2000 (far right)
  long weightedSum = 0;
  long sum = 0;
  
  int weights[5] = {-2000, -1000, 0, 1000, 2000};
  
  for (int i = 0; i < 5; i++) {
    int value = 4095 - calibratedValues[i]; // Invert if needed
    weightedSum += (long)value * weights[i];
    sum += value;
  }
  
  if (sum == 0) {
    return 0; // Line not detected, return center
  }
  
  return weightedSum / sum;
}

// ========== Line Following Function ==========
void followLine() {
  int position = getPosition();
  int error = position; // Error is the position offset from center
  
  // PID calculations
  integral += error;
  integral = constrain(integral, -10000, 10000); // Prevent integral windup
  derivative = error - lastError;
  
  float pidOutput = (Kp * error + Ki * integral + Kd * derivative) * multiplier;
  
  lastError = error;
  
  // Calculate motor speeds
  int leftSpeed = baseSpeed + pidOutput;
  int rightSpeed = baseSpeed - pidOutput;
  
  // Constrain speeds
  leftSpeed = constrain(leftSpeed, minSpeed, maxSpeed);
  rightSpeed = constrain(rightSpeed, minSpeed, maxSpeed);
  
  // Set motor speeds
  setMotorSpeed(leftSpeed, rightSpeed);
}

// ========== Motor Control Functions ==========
void setMotorSpeed(int leftSpeed, int rightSpeed) {
  // Left motor (Motor A)
  if (leftSpeed >= 0) {
    digitalWrite(AIN1, HIGH);
    digitalWrite(AIN2, LOW);
    ledcWrite(PWM_CHANNEL_A, abs(leftSpeed));
  } else {
    digitalWrite(AIN1, LOW);
    digitalWrite(AIN2, HIGH);
    ledcWrite(PWM_CHANNEL_A, abs(leftSpeed));
  }
  
  // Right motor (Motor B)
  if (rightSpeed >= 0) {
    digitalWrite(BIN1, HIGH);
    digitalWrite(BIN2, LOW);
    ledcWrite(PWM_CHANNEL_B, abs(rightSpeed));
  } else {
    digitalWrite(BIN1, LOW);
    digitalWrite(BIN2, HIGH);
    ledcWrite(PWM_CHANNEL_B, abs(rightSpeed));
  }
}

void stopMotors() {
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, LOW);
  ledcWrite(PWM_CHANNEL_A, 0);
  ledcWrite(PWM_CHANNEL_B, 0);
}

// ========== Button Handling ==========
void handleButtons() {
  bool startReading = digitalRead(BUTTON_START);
  bool stopReading = digitalRead(BUTTON_STOP);
  
  // Start button (active LOW due to pull-up)
  if (startReading == LOW && lastStartState == HIGH) {
    if ((millis() - lastDebounceTime) > debounceDelay) {
      robotRunning = true;
      integral = 0; // Reset integral on start
      Serial.println("Robot STARTED");
      lastDebounceTime = millis();
    }
  }
  lastStartState = startReading;
  
  // Stop button (active LOW due to pull-up)
  if (stopReading == LOW && lastStopState == HIGH) {
    if ((millis() - lastDebounceTime) > debounceDelay) {
      robotRunning = false;
      stopMotors();
      Serial.println("Robot STOPPED");
      lastDebounceTime = millis();
    }
  }
  lastStopState = stopReading;
}

// ========== Web Server Handlers ==========
void handleRoot() {
  String html = "<!DOCTYPE html><html><head>";
  html += "<meta name='viewport' content='width=device-width, initial-scale=1'>";
  html += "<title>LFR Control Panel</title>";
  html += "<style>";
  html += "body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }";
  html += ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
  html += "h1 { color: #333; text-align: center; }";
  html += ".sensor-display { display: flex; justify-content: space-around; margin: 20px 0; }";
  html += ".sensor { text-align: center; padding: 10px; background: #e0e0e0; border-radius: 5px; min-width: 60px; }";
  html += ".sensor.active { background: #4CAF50; color: white; }";
  html += ".control-group { margin: 20px 0; }";
  html += "label { display: block; margin: 10px 0 5px; font-weight: bold; }";
  html += "input[type='range'] { width: 100%; }";
  html += ".value-display { display: inline-block; margin-left: 10px; font-weight: bold; color: #4CAF50; }";
  html += "button { background: #4CAF50; color: white; border: none; padding: 12px 24px; margin: 5px; border-radius: 5px; cursor: pointer; font-size: 16px; }";
  html += "button:hover { background: #45a049; }";
  html += "button.stop { background: #f44336; }";
  html += "button.stop:hover { background: #da190b; }";
  html += ".status { text-align: center; padding: 10px; margin: 10px 0; border-radius: 5px; font-weight: bold; }";
  html += ".running { background: #4CAF50; color: white; }";
  html += ".stopped { background: #f44336; color: white; }";
  html += "</style>";
  html += "<script>";
  html += "function updateSensors() {";
  html += "  fetch('/sensors').then(r => r.json()).then(data => {";
  html += "    for(let i=1; i<=5; i++) {";
  html += "      document.getElementById('s'+i).innerText = data['s'+i];";
  html += "      if(data['s'+i] > 2000) document.getElementById('sensor'+i).classList.add('active');";
  html += "      else document.getElementById('sensor'+i).classList.remove('active');";
  html += "    }";
  html += "    document.getElementById('position').innerText = data.position;";
  html += "    document.getElementById('status').innerText = data.running ? 'RUNNING' : 'STOPPED';";
  html += "    document.getElementById('status').className = 'status ' + (data.running ? 'running' : 'stopped');";
  html += "  });";
  html += "}";
  html += "function setPID() {";
  html += "  let kp = document.getElementById('kp').value;";
  html += "  let ki = document.getElementById('ki').value;";
  html += "  let kd = document.getElementById('kd').value;";
  html += "  let mult = document.getElementById('mult').value;";
  html += "  fetch('/setPID?kp='+kp+'&ki='+ki+'&kd='+kd+'&mult='+mult);";
  html += "}";
  html += "function updateValue(id, val) { document.getElementById(id+'_val').innerText = val; }";
  html += "setInterval(updateSensors, 100);";
  html += "updateSensors();";
  html += "</script>";
  html += "</head><body>";
  html += "<div class='container'>";
  html += "<h1>🤖 Line Following Robot</h1>";
  html += "<div id='status' class='status'>STOPPED</div>";
  
  // Sensor display
  html += "<h2>IR Sensors</h2>";
  html += "<div class='sensor-display'>";
  for (int i = 1; i <= 5; i++) {
    html += "<div class='sensor' id='sensor" + String(i) + "'>";
    html += "S" + String(i) + "<br><span id='s" + String(i) + "'>0</span>";
    html += "</div>";
  }
  html += "</div>";
  html += "<p style='text-align:center;'>Position: <span id='position'>0</span></p>";
  
  // Control buttons
  html += "<div style='text-align:center; margin: 20px 0;'>";
  html += "<button onclick=\"fetch('/start')\">▶ START</button>";
  html += "<button class='stop' onclick=\"fetch('/stop')\">⏹ STOP</button>";
  html += "<button onclick=\"fetch('/calibrate')\">🔧 CALIBRATE</button>";
  html += "</div>";
  
  // PID controls
  html += "<h2>PID Tuning</h2>";
  html += "<div class='control-group'>";
  html += "<label>Kp: <span class='value-display' id='kp_val'>" + String(Kp, 2) + "</span></label>";
  html += "<input type='range' id='kp' min='0' max='10' step='0.1' value='" + String(Kp) + "' oninput=\"updateValue('kp',this.value);setPID()\">";
  html += "</div>";
  
  html += "<div class='control-group'>";
  html += "<label>Ki: <span class='value-display' id='ki_val'>" + String(Ki, 2) + "</span></label>";
  html += "<input type='range' id='ki' min='0' max='1' step='0.01' value='" + String(Ki) + "' oninput=\"updateValue('ki',this.value);setPID()\">";
  html += "</div>";
  
  html += "<div class='control-group'>";
  html += "<label>Kd: <span class='value-display' id='kd_val'>" + String(Kd, 2) + "</span></label>";
  html += "<input type='range' id='kd' min='0' max='10' step='0.1' value='" + String(Kd) + "' oninput=\"updateValue('kd',this.value);setPID()\">";
  html += "</div>";
  
  html += "<div class='control-group'>";
  html += "<label>Multiplier: <span class='value-display' id='mult_val'>" + String(multiplier, 2) + "</span></label>";
  html += "<input type='range' id='mult' min='0.1' max='3' step='0.1' value='" + String(multiplier) + "' oninput=\"updateValue('mult',this.value);setPID()\">";
  html += "</div>";
  
  html += "<p style='text-align:center; color:#666; margin-top:30px;'>ESP32-S3 LFR v1.0</p>";
  html += "</div></body></html>";
  
  server.send(200, "text/html", html);
}

void handleSensors() {
  String json = "{";
  json += "\"s1\":" + String(calibratedValues[0]) + ",";
  json += "\"s2\":" + String(calibratedValues[1]) + ",";
  json += "\"s3\":" + String(calibratedValues[2]) + ",";
  json += "\"s4\":" + String(calibratedValues[3]) + ",";
  json += "\"s5\":" + String(calibratedValues[4]) + ",";
  json += "\"position\":" + String(getPosition()) + ",";
  json += "\"running\":" + String(robotRunning ? "true" : "false");
  json += "}";
  
  server.send(200, "application/json", json);
}

void handleSetPID() {
  if (server.hasArg("kp")) Kp = server.arg("kp").toFloat();
  if (server.hasArg("ki")) Ki = server.arg("ki").toFloat();
  if (server.hasArg("kd")) Kd = server.arg("kd").toFloat();
  if (server.hasArg("mult")) multiplier = server.arg("mult").toFloat();
  
  Serial.printf("PID Updated: Kp=%.2f, Ki=%.2f, Kd=%.2f, Mult=%.2f\n", Kp, Ki, Kd, multiplier);
  server.send(200, "text/plain", "OK");
}

void handleCalibrate() {
  robotRunning = false;
  stopMotors();
  server.send(200, "text/plain", "Calibrating...");
  delay(1000);
  calibrateSensors();
}

void handleStartRobot() {
  robotRunning = true;
  integral = 0;
  Serial.println("Robot STARTED via Web");
  server.send(200, "text/plain", "Started");
}

void handleStopRobot() {
  robotRunning = false;
  stopMotors();
  Serial.println("Robot STOPPED via Web");
  server.send(200, "text/plain", "Stopped");
}
