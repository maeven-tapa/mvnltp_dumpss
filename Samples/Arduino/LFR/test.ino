// LFR Sensor Test with Web Interface for ESP32-S3
// 5 Channel TCRT5000 Analog Sensors

#include <WiFi.h>
#include <WebServer.h>

// WiFi credentials
const char* ssid = "Virus 2.4G";        // Replace with your WiFi SSID
const char* password = "MT42@DATA"; // Replace with your WiFi password

// Sensor pins
const int sensor1 = 15;  // OUT1 - GPIO15
const int sensor2 = 7;   // OUT2 - GPIO7
const int sensor3 = 6;   // OUT3 - GPIO6
const int sensor4 = 5;   // OUT4 - GPIO5
const int sensor5 = 4;   // OUT5 - GPIO4

// Web server on port 80
WebServer server(80);

// Sensor values
int sensorValues[5];

void setup() {
  Serial.begin(115200);
  
  // Configure sensor pins as input
  pinMode(sensor1, INPUT);
  pinMode(sensor2, INPUT);
  pinMode(sensor3, INPUT);
  pinMode(sensor4, INPUT);
  pinMode(sensor5, INPUT);
  
  // Connect to WiFi
  Serial.println();
  Serial.print("Connecting to ");
  Serial.println(ssid);
  
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  
  Serial.println();
  Serial.println("WiFi connected!");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());
  
  // Setup web server routes
  server.on("/", handleRoot);
  server.on("/data", handleData);
  
  server.begin();
  Serial.println("Web server started");
  Serial.println("Open browser and go to: http://" + WiFi.localIP().toString());
}

void loop() {
  server.handleClient();
  
  // Read sensor values
  sensorValues[0] = analogRead(sensor1);
  sensorValues[1] = analogRead(sensor2);
  sensorValues[2] = analogRead(sensor3);
  sensorValues[3] = analogRead(sensor4);
  sensorValues[4] = analogRead(sensor5);
  
  // Print to Serial Monitor
  Serial.print("S1:");
  Serial.print(sensorValues[0]);
  Serial.print(" | S2:");
  Serial.print(sensorValues[1]);
  Serial.print(" | S3:");
  Serial.print(sensorValues[2]);
  Serial.print(" | S4:");
  Serial.print(sensorValues[3]);
  Serial.print(" | S5:");
  Serial.println(sensorValues[4]);
  
  delay(100);
}

void handleRoot() {
  String html = R"rawliteral(
<!DOCTYPE html>
<html>
<head>
  <title>LFR Sensor Monitor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #1e1e1e;
      color: #00ff00;
      margin: 0;
      padding: 20px;
    }
    .container {
      max-width: 800px;
      margin: 0 auto;
      background-color: #2d2d2d;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0,255,0,0.3);
    }
    h1 {
      text-align: center;
      color: #00ff00;
      text-shadow: 0 0 10px #00ff00;
    }
    .sensor {
      margin: 15px 0;
      padding: 15px;
      background-color: #1a1a1a;
      border-left: 4px solid #00ff00;
      border-radius: 5px;
    }
    .sensor-name {
      font-weight: bold;
      color: #00ccff;
      display: inline-block;
      width: 100px;
    }
    .sensor-value {
      font-size: 24px;
      font-weight: bold;
      color: #00ff00;
      display: inline-block;
      margin-left: 20px;
    }
    .bar-container {
      width: 100%;
      height: 25px;
      background-color: #0a0a0a;
      border-radius: 5px;
      margin-top: 8px;
      overflow: hidden;
    }
    .bar {
      height: 100%;
      background: linear-gradient(90deg, #00ff00, #00cc00);
      transition: width 0.1s;
      border-radius: 5px;
    }
    .output {
      background-color: #0a0a0a;
      color: #00ff00;
      font-family: 'Courier New', monospace;
      padding: 15px;
      border-radius: 5px;
      margin-top: 20px;
      height: 200px;
      overflow-y: auto;
      border: 1px solid #00ff00;
    }
    .timestamp {
      color: #888;
      font-size: 12px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>⚡ LFR Sensor Monitor ⚡</h1>
    
    <div class="sensor">
      <span class="sensor-name">Sensor 1:</span>
      <span class="sensor-value" id="s1">0</span>
      <div class="bar-container"><div class="bar" id="bar1"></div></div>
    </div>
    
    <div class="sensor">
      <span class="sensor-name">Sensor 2:</span>
      <span class="sensor-value" id="s2">0</span>
      <div class="bar-container"><div class="bar" id="bar2"></div></div>
    </div>
    
    <div class="sensor">
      <span class="sensor-name">Sensor 3:</span>
      <span class="sensor-value" id="s3">0</span>
      <div class="bar-container"><div class="bar" id="bar3"></div></div>
    </div>
    
    <div class="sensor">
      <span class="sensor-name">Sensor 4:</span>
      <span class="sensor-value" id="s4">0</span>
      <div class="bar-container"><div class="bar" id="bar4"></div></div>
    </div>
    
    <div class="sensor">
      <span class="sensor-name">Sensor 5:</span>
      <span class="sensor-value" id="s5">0</span>
      <div class="bar-container"><div class="bar" id="bar5"></div></div>
    </div>
    
    <div class="output" id="output">
      <div class="timestamp">Serial Monitor Output...</div>
    </div>
  </div>

  <script>
    const maxValue = 4095; // ESP32 ADC max value (12-bit)
    
    function updateSensors() {
      fetch('/data')
        .then(response => response.json())
        .then(data => {
          for (let i = 1; i <= 5; i++) {
            document.getElementById('s' + i).textContent = data['s' + i];
            const percentage = (data['s' + i] / maxValue) * 100;
            document.getElementById('bar' + i).style.width = percentage + '%';
          }
          
          // Add to serial monitor output
          const output = document.getElementById('output');
          const timestamp = new Date().toLocaleTimeString();
          const line = `<div><span class="timestamp">[${timestamp}]</span> S1:${data.s1} | S2:${data.s2} | S3:${data.s3} | S4:${data.s4} | S5:${data.s5}</div>`;
          output.innerHTML = line + output.innerHTML;
          
          // Keep only last 20 lines
          const lines = output.getElementsByTagName('div');
          if (lines.length > 20) {
            output.removeChild(lines[lines.length - 1]);
          }
        });
    }
    
    // Update every 100ms
    setInterval(updateSensors, 100);
    updateSensors();
  </script>
</body>
</html>
)rawliteral";
  
  server.send(200, "text/html", html);
}

void handleData() {
  String json = "{";
  json += "\"s1\":" + String(sensorValues[0]) + ",";
  json += "\"s2\":" + String(sensorValues[1]) + ",";
  json += "\"s3\":" + String(sensorValues[2]) + ",";
  json += "\"s4\":" + String(sensorValues[3]) + ",";
  json += "\"s5\":" + String(sensorValues[4]);
  json += "}";
  
  server.send(200, "application/json", json);
}
