/*
 * WiFi Mode Functions
 */

#include "globals.h"

// HTML for the WiFi configuration page
const char WIFI_CONFIG_HTML[] PROGMEM = R"rawliteral(
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pet Feeder WiFi Config</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f0;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .radio-group {
            margin: 10px 0;
        }
        .radio-group label {
            display: inline-block;
            margin-right: 20px;
            font-weight: normal;
        }
        input[type="radio"] {
            margin-right: 5px;
        }
        .password-field {
            display: none;
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .status {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            display: block;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            display: block;
        }
    </style>
    <script>
        function togglePassword() {
            var securityType = document.querySelector('input[name="security"]:checked').value;
            var passwordField = document.getElementById('password-field');
            if (securityType === 'wpa') {
                passwordField.style.display = 'block';
            } else {
                passwordField.style.display = 'none';
            }
        }
        
        function submitForm(event) {
            event.preventDefault();
            var ssid = document.getElementById('ssid').value;
            var security = document.querySelector('input[name="security"]:checked').value;
            var password = document.getElementById('password').value;
            
            if (!ssid) {
                showStatus('Please enter a WiFi SSID', 'error');
                return;
            }
            
            if (security === 'wpa' && !password) {
                showStatus('Please enter a password for WPA/WPA2', 'error');
                return;
            }
            
            var formData = new URLSearchParams();
            formData.append('ssid', ssid);
            formData.append('security', security);
            formData.append('password', security === 'wpa' ? password : '');
            
            showStatus('Connecting to WiFi...', 'success');
            
            fetch('/save', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                showStatus(data, 'success');
            })
            .catch(error => {
                showStatus('Error: ' + error, 'error');
            });
        }
        
        function showStatus(message, type) {
            var statusDiv = document.getElementById('status');
            statusDiv.textContent = message;
            statusDiv.className = 'status ' + type;
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>🐾 Pet Feeder WiFi Setup</h1>
        <form onsubmit="submitForm(event)">
            <label for="ssid">WiFi Network Name (SSID):</label>
            <input type="text" id="ssid" name="ssid" placeholder="Enter WiFi SSID" required>
            
            <label>Security Type:</label>
            <div class="radio-group">
                <label>
                    <input type="radio" name="security" value="open" onchange="togglePassword()">
                    Open (No Password)
                </label>
                <label>
                    <input type="radio" name="security" value="wpa" onchange="togglePassword()" checked>
                    WPA/WPA2
                </label>
            </div>
            
            <div id="password-field" class="password-field" style="display: block;">
                <label for="password">WiFi Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter WiFi password">
            </div>
            
            <button type="submit">💾 Save & Connect</button>
        </form>
        
        <div id="status" class="status"></div>
    </div>
</body>
</html>
)rawliteral";

// Initialize WiFi and load saved credentials
void initWiFi() {
  preferences.begin("wifi-config", false);
  
  // Load saved WiFi credentials
  savedSSID = preferences.getString("ssid", "");
  savedPassword = preferences.getString("password", "");
  
  Serial.println("WiFi Initialization");
  
  if (savedSSID.length() > 0) {
    Serial.println("Found saved WiFi credentials");
    Serial.print("SSID: ");
    Serial.println(savedSSID);
    
    // Try to connect to saved WiFi
    connectToWiFi(savedSSID, savedPassword);
  } else {
    Serial.println("No saved WiFi credentials");
    wifiConnected = false;
  }
}

// Connect to WiFi network
void connectToWiFi(String ssid, String password) {
  Serial.print("Connecting to WiFi: ");
  Serial.println(ssid);
  
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid.c_str(), password.c_str());
  
  unsigned long startTime = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - startTime < wifiConnectTimeout) {
    delay(500);
    Serial.print(".");
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    wifiConnected = true;
    wifiRSSI = WiFi.RSSI();
    Serial.println();
    Serial.println("WiFi Connected!");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
    Serial.print("RSSI: ");
    Serial.println(wifiRSSI);
  } else {
    wifiConnected = false;
    Serial.println();
    Serial.println("WiFi Connection Failed!");
  }
}

// Start WiFi Access Point mode
void startAPMode() {
  Serial.println("Starting AP Mode...");
  
  WiFi.mode(WIFI_AP);
  WiFi.softAP(AP_SSID, AP_PASSWORD);
  
  IPAddress IP = WiFi.softAPIP();
  Serial.print("AP IP address: ");
  Serial.println(IP);
  
  // Setup web server routes
  server.on("/", HTTP_GET, []() {
    server.send_P(200, "text/html", WIFI_CONFIG_HTML);
  });
  
  server.on("/save", HTTP_POST, []() {
    String ssid = server.arg("ssid");
    String security = server.arg("security");
    String password = server.arg("password");
    
    Serial.println("Received WiFi configuration:");
    Serial.print("SSID: ");
    Serial.println(ssid);
    Serial.print("Security: ");
    Serial.println(security);
    
    // Save to preferences
    preferences.putString("ssid", ssid);
    preferences.putString("password", password);
    
    savedSSID = ssid;
    savedPassword = password;
    
    server.send(200, "text/plain", "WiFi credentials saved! Attempting to connect...");
    
    delay(1000);
    
    // Stop AP mode and try to connect
    server.stop();
    WiFi.softAPdisconnect(true);
    
    // Try to connect to the new WiFi
    connectToWiFi(savedSSID, savedPassword);
    
    // Redraw WiFi mode display
    if (currentMode == WIFI_MODE) {
      drawWiFiUI();
    }
  });
  
  server.begin();
  Serial.println("Web server started");
}

// Reset WiFi settings
void resetWiFi() {
  Serial.println("Resetting WiFi credentials...");
  
  preferences.putString("ssid", "");
  preferences.putString("password", "");
  
  savedSSID = "";
  savedPassword = "";
  wifiConnected = false;
  
  // Disconnect from WiFi
  WiFi.disconnect(true);
  
  // Buzz to confirm
  digitalWrite(BUZZER, HIGH);
  delay(100);
  digitalWrite(BUZZER, LOW);
  delay(100);
  digitalWrite(BUZZER, HIGH);
  delay(100);
  digitalWrite(BUZZER, LOW);
  
  Serial.println("WiFi reset complete");
  
  // Start AP mode for reconfiguration
  startAPMode();
  
  // Redraw WiFi UI
  drawWiFiUI();
}

// Handle WiFi Mode updates
void handleWiFiMode() {
  static unsigned long lastUpdate = 0;
  
  // Update WiFi status every 2 seconds
  if (millis() - lastUpdate >= 2000) {
    lastUpdate = millis();
    
    if (wifiConnected && WiFi.status() == WL_CONNECTED) {
      wifiRSSI = WiFi.RSSI();
    } else if (wifiConnected && WiFi.status() != WL_CONNECTED) {
      // Connection lost
      wifiConnected = false;
      drawWiFiUI();
    }
  }
}

// Check button states in WiFi mode
void checkWiFiButtons() {
  bool currentBtn1State = digitalRead(BTN1);
  bool currentBtn2State = digitalRead(BTN2);
  
  // Double press detection for Button 1 (exit WiFi mode)
  if (currentBtn1State == HIGH && lastButtonState[0] == LOW) {
    unsigned long currentTime = millis();
    
    if (currentTime - lastBtn1WifiPressTime < doublePressWindow) {
      btn1WifiPressCount++;
      if (btn1WifiPressCount >= 2) {
        // Double press detected - exit WiFi mode
        digitalWrite(BUZZER, HIGH);
        delay(100);
        digitalWrite(BUZZER, LOW);
        
        // Stop AP mode if active
        if (!wifiConnected) {
          server.stop();
          WiFi.softAPdisconnect(true);
        }
        
        currentMode = HOME_MODE;
        btn1WifiPressCount = 0;
        drawClockUI();
        
        Serial.println("Exiting WiFi Mode");
      }
    } else {
      btn1WifiPressCount = 1;
    }
    
    lastBtn1WifiPressTime = currentTime;
  }
  
  lastButtonState[0] = currentBtn1State;
  
  // Long press detection for Button 2 (WiFi reset)
  if (currentBtn2State == HIGH && lastButtonState[1] == LOW) {
    btn2WifiPressStartTime = millis();
    btn2WifiLongPressTriggered = false;
  }
  
  if (currentBtn2State == HIGH && !btn2WifiLongPressTriggered) {
    if (millis() - btn2WifiPressStartTime >= longPressDuration) {
      btn2WifiLongPressTriggered = true;
      resetWiFi();
    }
  }
  
  lastButtonState[1] = currentBtn2State;
}
