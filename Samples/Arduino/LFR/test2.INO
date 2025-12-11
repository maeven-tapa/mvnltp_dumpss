#include <WiFi.h>
#include <WebServer.h>

// WiFi credentials - Change these to your network
const char* ssid = "Virus 2.4G";
const char* password = "MT42@DATA";

WebServer server(80);

// TB6612FNG Motor Driver Pins
#define PWMA 21
#define AIN2 35
#define AIN1 36
#define STBY 37
#define BIN1 38
#define BIN2 39
#define PWMB 40

// TCRT5000 Analog Sensor Pins
#define SENSOR1 15
#define SENSOR2 7
#define SENSOR3 6
#define SENSOR4 5
#define SENSOR5 4

// PWM Configuration
const int pwmFreq = 5000;
const int pwmResolution = 8;
const int pwmChannelA = 0;
const int pwmChannelB = 1;

const uint8_t SensorCount = 5;
const uint8_t sensorPins[SensorCount] = {SENSOR1, SENSOR2, SENSOR3, SENSOR4, SENSOR5};
uint16_t sensorValues[SensorCount];
int threshold[SensorCount];
uint16_t calibrationMin[SensorCount];
uint16_t calibrationMax[SensorCount];

float Kp = 0;
float Ki = 0;
float Kd = 0;

uint8_t multiP = 1;
uint8_t multiI  = 1;
uint8_t multiD = 1;
uint8_t Kpfinal;
uint8_t Kifinal;
uint8_t Kdfinal;
float Pvalue;
float Ivalue;
float Dvalue;

boolean onoff = false;

uint16_t position = 2000;
int P, D, I, previousError, PIDvalue, error;
int lsp, rsp;
int lfspeed = 230;

// Web page HTML
const char index_html[] PROGMEM = R"rawliteral(
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ESP32 Line Follower Control</title>
  <style>
    body { font-family: Arial; margin: 20px; background: #f0f0f0; }
    .container { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; text-align: center; }
    .control-group { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
    .control-group h3 { margin-top: 0; color: #555; }
    label { display: block; margin: 10px 0 5px; font-weight: bold; }
    input[type="range"] { width: 100%; }
    .value-display { display: inline-block; min-width: 80px; text-align: right; color: #007bff; font-weight: bold; }
    .button { background: #28a745; color: white; border: none; padding: 15px 30px; font-size: 18px; border-radius: 5px; cursor: pointer; width: 100%; margin-top: 10px; }
    .button:active { background: #218838; }
    .button.stop { background: #dc3545; }
    .button.stop:active { background: #c82333; }
    .status { text-align: center; font-size: 20px; font-weight: bold; padding: 10px; margin: 10px 0; border-radius: 5px; }
    .status.running { background: #d4edda; color: #155724; }
    .status.stopped { background: #f8d7da; color: #721c24; }
  </style>
</head>
<body>
  <div class="container">
    <h1>🤖 Line Follower Control</h1>
    
    <div class="status" id="status">Status: STOPPED</div>
    
    <div class="control-group">
      <h3>PID Parameters</h3>
      
      <label>Kp: <span class="value-display" id="kpVal">0.00</span></label>
      <input type="range" id="kp" min="0" max="255" value="0" oninput="updateKp()">
      
      <label>Kp Multiplier (÷10^n): <span class="value-display" id="multiPVal">1</span></label>
      <input type="range" id="multiP" min="0" max="5" value="1" oninput="updateMultiP()">
      
      <label>Ki: <span class="value-display" id="kiVal">0.00</span></label>
      <input type="range" id="ki" min="0" max="255" value="0" oninput="updateKi()">
      
      <label>Ki Multiplier (÷10^n): <span class="value-display" id="multiIVal">1</span></label>
      <input type="range" id="multiI" min="0" max="5" value="1" oninput="updateMultiI()">
      
      <label>Kd: <span class="value-display" id="kdVal">0.00</span></label>
      <input type="range" id="kd" min="0" max="255" value="0" oninput="updateKd()">
      
      <label>Kd Multiplier (÷10^n): <span class="value-display" id="multiDVal">1</span></label>
      <input type="range" id="multiD" min="0" max="5" value="1" oninput="updateMultiD()">
    </div>
    
    <button class="button" onclick="startRobot()">START</button>
    <button class="button stop" onclick="stopRobot()">STOP</button>
  </div>

  <script>
    function sendData(param, value) {
      fetch('/update?param=' + param + '&value=' + value);
    }
    
    function updateDisplay() {
      var kp = document.getElementById('kp').value;
      var multiP = document.getElementById('multiP').value;
      var ki = document.getElementById('ki').value;
      var multiI = document.getElementById('multiI').value;
      var kd = document.getElementById('kd').value;
      var multiD = document.getElementById('multiD').value;
      
      document.getElementById('kpVal').innerText = (kp / Math.pow(10, multiP)).toFixed(5);
      document.getElementById('kiVal').innerText = (ki / Math.pow(10, multiI)).toFixed(5);
      document.getElementById('kdVal').innerText = (kd / Math.pow(10, multiD)).toFixed(5);
      document.getElementById('multiPVal').innerText = Math.pow(10, multiP);
      document.getElementById('multiIVal').innerText = Math.pow(10, multiI);
      document.getElementById('multiDVal').innerText = Math.pow(10, multiD);
    }
    
    function updateKp() { sendData(1, document.getElementById('kp').value); updateDisplay(); }
    function updateMultiP() { sendData(2, document.getElementById('multiP').value); updateDisplay(); }
    function updateKi() { sendData(3, document.getElementById('ki').value); updateDisplay(); }
    function updateMultiI() { sendData(4, document.getElementById('multiI').value); updateDisplay(); }
    function updateKd() { sendData(5, document.getElementById('kd').value); updateDisplay(); }
    function updateMultiD() { sendData(6, document.getElementById('multiD').value); updateDisplay(); }
    
    function startRobot() {
      sendData(7, 1);
      document.getElementById('status').innerText = 'Status: RUNNING';
      document.getElementById('status').className = 'status running';
    }
    
    function stopRobot() {
      sendData(7, 0);
      document.getElementById('status').innerText = 'Status: STOPPED';
      document.getElementById('status').className = 'status stopped';
    }
    
    updateDisplay();
  </script>
</body>
</html>
)rawliteral";

void handleRoot() {
  server.send(200, "text/html", index_html);
}

void handleUpdate() {
  if (server.hasArg("param") && server.hasArg("value")) {
    int param = server.arg("param").toInt();
    int value = server.arg("value").toInt();
    
    switch(param) {
      case 1: Kp = value; break;
      case 2: multiP = value; break;
      case 3: Ki = value; break;
      case 4: multiI = value; break;
      case 5: Kd = value; break;
      case 6: multiD = value; break;
      case 7: onoff = (value == 1); break;
    }
    
    server.send(200, "text/plain", "OK");
  } else {
    server.send(400, "text/plain", "Bad Request");
  }
}

void handleStatus() {
  String json = "{";
  json += "\"kp\":" + String(Kp) + ",";
  json += "\"multiP\":" + String(multiP) + ",";
  json += "\"ki\":" + String(Ki) + ",";
  json += "\"multiI\":" + String(multiI) + ",";
  json += "\"kd\":" + String(Kd) + ",";
  json += "\"multiD\":" + String(multiD) + ",";
  json += "\"running\":" + String(onoff ? "true" : "false");
  json += "}";
  server.send(200, "application/json", json);
}

void setup()
{
  // Configure motor driver pins
  pinMode(AIN1, OUTPUT);
  pinMode(AIN2, OUTPUT);
  pinMode(BIN1, OUTPUT);
  pinMode(BIN2, OUTPUT);
  pinMode(STBY, OUTPUT);
  
  // Enable motor driver
  digitalWrite(STBY, HIGH);
  
  // Configure PWM channels
  ledcSetup(pwmChannelA, pwmFreq, pwmResolution);
  ledcSetup(pwmChannelB, pwmFreq, pwmResolution);
  ledcAttachPin(PWMA, pwmChannelA);
  ledcAttachPin(PWMB, pwmChannelB);
  
  // Configure sensor pins
  for (uint8_t i = 0; i < SensorCount; i++)
  {
    pinMode(sensorPins[i], INPUT);
    calibrationMin[i] = 4095;
    calibrationMax[i] = 0;
  }

  delay(500);
  pinMode(LED_BUILTIN, OUTPUT);
  digitalWrite(LED_BUILTIN, HIGH); // turn on LED to indicate calibration mode
  
  Serial.begin(115200);
  Serial.println("ESP32 Line Follower Starting...");
  
  // Connect to WiFi
  Serial.print("Connecting to WiFi");
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.println("WiFi Connected!");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
  
  // Setup web server routes
  server.on("/", handleRoot);
  server.on("/update", handleUpdate);
  server.on("/status", handleStatus);
  server.begin();
  Serial.println("Web server started");
  
  // Calibration routine - 400 iterations (~10 seconds)
  Serial.println("Starting calibration...");
  for (uint16_t i = 0; i < 400; i++)
  {
    calibrateSensors();
    delay(25);
  }
  digitalWrite(LED_BUILTIN, LOW); // turn off LED to indicate calibration complete

  // Calculate thresholds and print calibration values
  for (uint8_t i = 0; i < SensorCount; i++)
  {
    threshold[i] = (calibrationMin[i] + calibrationMax[i]) / 2;
    Serial.print("Sensor ");
    Serial.print(i);
    Serial.print(" - Min: ");
    Serial.print(calibrationMin[i]);
    Serial.print(", Max: ");
    Serial.print(calibrationMax[i]);
    Serial.print(", Threshold: ");
    Serial.println(threshold[i]);
  }
  
  Serial.println("Ready! Open web browser and navigate to:");
  Serial.println(WiFi.localIP());

  delay(1000);
}

void calibrateSensors()
{
  for (uint8_t i = 0; i < SensorCount; i++)
  {
    uint16_t value = analogRead(sensorPins[i]);
    if (value < calibrationMin[i])
      calibrationMin[i] = value;
    if (value > calibrationMax[i])
      calibrationMax[i] = value;
  }
}

void loop()
{
  // Handle web server requests
  server.handleClient();
  
  if (onoff == true){
    robot_control();
  }
  else if(onoff == false){
    stopMotors();
  }
}

uint16_t readLineSensor()
{
  // Read all 5 sensors
  for (uint8_t i = 0; i < SensorCount; i++)
  {
    sensorValues[i] = analogRead(sensorPins[i]);
  }
  
  // Calculate weighted average position
  // Position range: 0 (far left) to 4000 (far right)
  // Sensor weights: [0, 1000, 2000, 3000, 4000]
  uint32_t avg = 0;
  uint32_t sum = 0;
  
  for (uint8_t i = 0; i < SensorCount; i++)
  {
    uint16_t value = sensorValues[i];
    
    // TCRT5000: Lower values = line detected (darker surface)
    // Invert the reading so higher value = stronger detection
    uint16_t invertedValue = calibrationMax[i] - value + calibrationMin[i];
    
    if (value < threshold[i])  // Line detected when value is below threshold
    {
      // Weighted average: each sensor contributes based on its position
      avg += (uint32_t)invertedValue * (i * 1000);
      sum += invertedValue;
    }
  }
  
  if (sum == 0)
  {
    // No line detected, return last known position
    return position;
  }
  
  // Return weighted average position
  return avg / sum;
}

void robot_control(){
  // Step 1: Read where the line is (0=far left, 2000=center, 4000=far right)
  position = readLineSensor();
  
  // Step 2: Calculate error (how far off-center are we?)
  // Positive error = line is to the RIGHT, need to turn RIGHT
  // Negative error = line is to the LEFT, need to turn LEFT
  error = position - 2000;
  
  // Step 3: Check if we lost the line completely
  boolean noLineDetected = true;
  for (uint8_t i = 0; i < SensorCount; i++)
  {
    if (sensorValues[i] < threshold[i])  // TCRT5000: low value means line detected
    {
      noLineDetected = false;
      break;
    }
  }
  
  // Step 4: If line is lost, spin in place to find it
  while(noLineDetected){ 
    if(previousError > 0){
      motor_drive(230, -230); // Line was on RIGHT, turn RIGHT to find it
    }
    else{
      motor_drive(-230, 230); // Line was on LEFT, turn LEFT to find it
    }
    position = readLineSensor();
    
    // Re-check sensors
    noLineDetected = true;
    for (uint8_t i = 0; i < SensorCount; i++)
    {
      if (sensorValues[i] < threshold[i])
      {
        noLineDetected = false;
        break;
      }
    }
  }
  
  // Step 5: Use PID to smoothly follow the line
  PID_Linefollow(error);
}
void PID_Linefollow(int error){
    // === PROPORTIONAL (P) ===
    // P responds to CURRENT error
    // Higher Kp = stronger/faster correction, but can oscillate
    P = error;
    Pvalue = (Kp/pow(10,multiP)) * P;
    
    // === INTEGRAL (I) ===
    // I accumulates ALL past errors
    // Helps eliminate steady-state offset (drift)
    // Keep Ki small or it will overshoot!
    I = I + error;
    Ivalue = (Ki/pow(10,multiI)) * I;
    
    // === DERIVATIVE (D) ===
    // D responds to RATE OF CHANGE of error
    // Helps smooth out oscillations and predict future error
    D = error - previousError;
    Dvalue = (Kd/pow(10,multiD)) * D;
    
    // === TOTAL PID CORRECTION ===
    float PIDvalue = Pvalue + Ivalue + Dvalue;
    previousError = error;  // Save for next cycle
    
    // === APPLY CORRECTION TO MOTORS ===
    // If error is POSITIVE (line to the right):
    //   - Slow down LEFT motor
    //   - Speed up RIGHT motor
    //   - Robot turns RIGHT
    // If error is NEGATIVE (line to the left):
    //   - Speed up LEFT motor
    //   - Slow down RIGHT motor  
    //   - Robot turns LEFT
    lsp = lfspeed - PIDvalue;  // Left motor speed
    rsp = lfspeed + PIDvalue;  // Right motor speed
    
    // Limit motor speeds to valid range
    if (lsp > 255) lsp = 255;
    if (lsp < -255) lsp = -255;
    if (rsp > 255) rsp = 255;
    if (rsp < -255) rsp = -255;
    
    motor_drive(lsp, rsp);
}

void motor_drive(int left, int right){
  // Motor A (Left Motor)
  if(left > 0)
  {
    digitalWrite(AIN1, HIGH);
    digitalWrite(AIN2, LOW);
    ledcWrite(pwmChannelA, abs(left));
  }
  else if(left < 0)
  {
    digitalWrite(AIN1, LOW);
    digitalWrite(AIN2, HIGH);
    ledcWrite(pwmChannelA, abs(left));
  }
  else
  {
    digitalWrite(AIN1, LOW);
    digitalWrite(AIN2, LOW);
    ledcWrite(pwmChannelA, 0);
  }
  
  // Motor B (Right Motor)
  if(right > 0)
  {
    digitalWrite(BIN1, HIGH);
    digitalWrite(BIN2, LOW);
    ledcWrite(pwmChannelB, abs(right));
  }
  else if(right < 0)
  {
    digitalWrite(BIN1, LOW);
    digitalWrite(BIN2, HIGH);
    ledcWrite(pwmChannelB, abs(right));
  }
  else
  {
    digitalWrite(BIN1, LOW);
    digitalWrite(BIN2, LOW);
    ledcWrite(pwmChannelB, 0);
  }
}

void stopMotors(){
  digitalWrite(AIN1, LOW);
  digitalWrite(AIN2, LOW);
  digitalWrite(BIN1, LOW);
  digitalWrite(BIN2, LOW);
  ledcWrite(pwmChannelA, 0);
  ledcWrite(pwmChannelB, 0);
}