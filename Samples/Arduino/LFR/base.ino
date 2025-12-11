/*
 Sample Line Following Code for ESP32-S3-N16R8 with TB6612FNG
 With WiFi Web Interface
*/

#include <SparkFun_TB6612.h>
#include <WiFi.h>
#include <WebServer.h>

// WiFi credentials - UPDATE THESE WITH YOUR ROUTER INFO
const char* ssid = "Virus 2.4G";
const char* password = "MT42@DATA";

WebServer server(80);

// Virtual button states
bool buttonBPressed = false;
bool buttonCPressed = false;

// TB6612FNG Motor  Pins
#define AIN1 36
#define AIN2 35
#define PWMA 21
#define BIN1 38
#define BIN2 39
#define PWMB 40
#define STBY 37

// TCRT5000 5-Channel Analog Sensor Pins
#define SENSOR_LEFT 15    // OUT1
#define SENSOR_L2 7       // OUT2
#define SENSOR_CENTER 6   // OUT3
#define SENSOR_R2 5       // OUT4
#define SENSOR_RIGHT 4    // OUT5

// these constants are used to allow you to make your motor configuration
// line up with function names like forward.  Value can be 1 or -1
const int offsetA = 1;
const int offsetB = 1;

// Initializing motors.  The library will allow you to initialize as many
// motors as you have memory for.  If you are using functions like forward
// that take 2 motors as arguements you can either write new functions or
// call the function more than once.

Motor motor1 = Motor(AIN1, AIN2, PWMA, offsetA, STBY);
Motor motor2 = Motor(BIN1, BIN2, PWMB, offsetB, STBY);


int P, D, I, previousError, PIDvalue, error;
int lsp, rsp;
int lfspeed = 200;

float Kp = 0;
float Kd = 0;
float Ki = 0 ;


int threshold = 2000;  // Default threshold value for all sensors

void setup()
{
  Serial.begin(115200);
  
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
  server.on("/", handleRoot);
  server.on("/buttonB", handleButtonB);
  server.on("/buttonC", handleButtonC);
  server.on("/sensorData", handleSensorData);
  server.on("/setThreshold", handleSetThreshold);
  
  server.begin();
  Serial.println("Web server started");
}


void loop()
{
  server.handleClient();  // Handle web server requests
  
  // Wait for Button B press via web to start line following
  if (buttonBPressed) {
    buttonBPressed = false;
    Serial.println("Button B pressed - Starting line following");
    
    while (!buttonCPressed)  // Run until Button C is pressed to stop
    {
      server.handleClient();  // Keep handling web requests
      
      if (analogRead(SENSOR_L2) > threshold && analogRead(SENSOR_RIGHT) < threshold )
      {
        lsp = 0; rsp = lfspeed;
        motor1.drive(0);
        motor2.drive(lfspeed);
      }

      else if (analogRead(SENSOR_RIGHT) > threshold && analogRead(SENSOR_L2) < threshold)
      { lsp = lfspeed; rsp = 0;
        motor1.drive(lfspeed);
        motor2.drive(0);
      }
      else if (analogRead(SENSOR_CENTER) > threshold)
      {
        Kp = 0.0006 * (1000 - analogRead(SENSOR_CENTER));
        Kd = 10 * Kp;
        //Ki = 0.0001;
        linefollow();
      }
    }
    buttonCPressed = false;  // Reset flag
    motor1.drive(0);
    motor2.drive(0);
    Serial.println("Line following stopped");
  }
  
  // Button C stops any robot process
  if (buttonCPressed) {
    buttonCPressed = false;
    motor1.drive(0);
    motor2.drive(0);
    Serial.println("Button C pressed - All motors stopped");
  }
}

void linefollow()
{
  int error = (analogRead(SENSOR_L2) - analogRead(SENSOR_R2));

  P = error;
  I = I + error;
  D = error - previousError;

  PIDvalue = (Kp * P) + (Ki * I) + (Kd * D);
  previousError = error;

  lsp = lfspeed - PIDvalue;
  rsp = lfspeed + PIDvalue;

  if (lsp > 255) {
    lsp = 255;
  }
  if (lsp < 0) {
    lsp = 0;
  }
  if (rsp > 255) {
    rsp = 255;
  }
  if (rsp < 0) {
    rsp = 0;
  }
  motor1.drive(lsp);
  motor2.drive(rsp);

}

// Web server handler functions
void handleRoot() {
  String html = "<!DOCTYPE html><html><head>";
  html += "<meta name='viewport' content='width=device-width, initial-scale=1'>";
  html += "<style>";
  html += "body { font-family: Arial; text-align: center; margin: 20px; background: #f0f0f0; }";
  html += "h1 { color: #333; }";
  html += ".button { background-color: #4CAF50; border: none; color: white; ";
  html += "padding: 20px 40px; text-align: center; font-size: 20px; margin: 10px; ";
  html += "cursor: pointer; border-radius: 8px; }";
  html += ".buttonB { background-color: #008CBA; }";
  html += ".buttonC { background-color: #f44336; }";
  html += ".sensor-data { background: white; padding: 20px; margin: 20px auto; ";
  html += "max-width: 600px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
  html += ".sensor { margin: 10px 0; padding: 10px; background: #e8e8e8; border-radius: 4px; }";
  html += ".threshold-control { background: white; padding: 20px; margin: 20px auto; ";
  html += "max-width: 600px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
  html += ".slider { width: 100%; height: 25px; }";
  html += ".threshold-value { font-size: 24px; font-weight: bold; color: #4CAF50; margin: 10px 0; }";
  html += "</style>";
  html += "<script>";
  html += "function buttonB() { fetch('/buttonB'); }";
  html += "function buttonC() { fetch('/buttonC'); }";
  html += "function updateSensors() {";
  html += "  fetch('/sensorData').then(r => r.json()).then(data => {";
  html += "    document.getElementById('left').innerText = data.left;";
  html += "    document.getElementById('l2').innerText = data.l2;";
  html += "    document.getElementById('center').innerText = data.center;";
  html += "    document.getElementById('r2').innerText = data.r2;";
  html += "    document.getElementById('right').innerText = data.right;";
  html += "  });";
  html += "}";
  html += "function updateThreshold(value) {";
  html += "  document.getElementById('thresholdValue').innerText = value;";
  html += "  fetch('/setThreshold?value=' + value);";
  html += "}";
  html += "setInterval(updateSensors, 200);";
  html += "</script></head><body>";
  html += "<h1>ESP32 Line Follower Control</h1>";
  html += "<button class='button buttonB' onclick='buttonB()'>START<br>Line Following</button><br>";
  html += "<button class='button buttonC' onclick='buttonC()'>STOP<br>Motors</button>";
  html += "<div class='threshold-control'><h2>Threshold Control</h2>";
  html += "<p>Adjust threshold for all sensors (0-4095)</p>";
  html += "<input type='range' min='0' max='4095' value='" + String(threshold) + "' class='slider' ";
  html += "oninput='updateThreshold(this.value)'>";
  html += "<div class='threshold-value' id='thresholdValue'>" + String(threshold) + "</div>";
  html += "</div>";
  html += "<div class='sensor-data'><h2>Sensor Values</h2>";
  html += "<div class='sensor'>Left: <span id='left'>-</span></div>";
  html += "<div class='sensor'>L2: <span id='l2'>-</span></div>";
  html += "<div class='sensor'>Center: <span id='center'>-</span></div>";
  html += "<div class='sensor'>R2: <span id='r2'>-</span></div>";
  html += "<div class='sensor'>Right: <span id='right'>-</span></div>";
  html += "</div></body></html>";
  
  server.send(200, "text/html", html);
}

void handleButtonB() {
  buttonBPressed = true;
  server.send(200, "text/plain", "Button B pressed");
  Serial.println("Web: Button B pressed");
}

void handleButtonC() {
  buttonCPressed = true;
  server.send(200, "text/plain", "Button C pressed");
  Serial.println("Web: Button C pressed");
}

void handleSensorData() {
  String json = "{";
  json += "\"left\":" + String(analogRead(SENSOR_LEFT)) + ",";
  json += "\"l2\":" + String(analogRead(SENSOR_L2)) + ",";
  json += "\"center\":" + String(analogRead(SENSOR_CENTER)) + ",";
  json += "\"r2\":" + String(analogRead(SENSOR_R2)) + ",";
  json += "\"right\":" + String(analogRead(SENSOR_RIGHT));
  json += "}";
  
  server.send(200, "application/json", json);
}

void handleSetThreshold() {
  if (server.hasArg("value")) {
    threshold = server.arg("value").toInt();
    Serial.print("Threshold set to: ");
    Serial.println(threshold);
  }
  server.send(200, "text/plain", "OK");
}