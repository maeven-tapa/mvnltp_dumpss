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
            var serverip = document.getElementById('serverip').value;
            
            if (!ssid) {
                showStatus('Please enter a WiFi SSID', 'error');
                return;
            }
            
            if (security === 'wpa' && !password) {
                showStatus('Please enter a password for WPA/WPA2', 'error');
                return;
            }
            
            if (!serverip) {
                showStatus('Please enter the server IP address', 'error');
                return;
            }
            
            var formData = new URLSearchParams();
            formData.append('ssid', ssid);
            formData.append('security', security);
            formData.append('password', security === 'wpa' ? password : '');
            formData.append('serverip', serverip);
            
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
            
            <label for="serverip">Server IP Address:</label>
            <input type="text" id="serverip" name="serverip" placeholder="e.g., 192.168.1.100" required>
            <small style="color: #666; display: block; margin-top: 5px;">Enter your computer's local IP address</small>
            
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
  
  // Load saved WiFi credentials and server IP
  savedSSID = preferences.getString("ssid", "");
  savedPassword = preferences.getString("password", "");
  String savedServerIP = preferences.getString("serverip", "");
  
  // Update global SERVER_IP if saved
  if (savedServerIP.length() > 0) {
    // Can't directly modify const, but we'll use savedServerIP in functions
    Serial.print("Loaded Server IP: ");
    Serial.println(savedServerIP);
  }
  
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
    String serverip = server.arg("serverip");
    
    Serial.println("Received WiFi configuration:");
    Serial.print("SSID: ");
    Serial.println(ssid);
    Serial.print("Security: ");
    Serial.println(security);
    Serial.print("Server IP: ");
    Serial.println(serverip);
    
    // Save to preferences
    preferences.putString("ssid", ssid);
    preferences.putString("password", password);
    preferences.putString("serverip", serverip);
    
    savedSSID = ssid;
    savedPassword = password;
    
    server.send(200, "text/plain", "WiFi and server settings saved! Attempting to connect...");
    
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
  Serial.println("Resetting WiFi credentials and server settings...");
  
  preferences.putString("ssid", "");
  preferences.putString("password", "");
  preferences.putString("serverip", "");
  
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
// ============================================================================
// SERVER COMMUNICATION FUNCTIONS
// ============================================================================

// Send data to web server
bool sendDataToServer(String endpoint, String jsonData) {
  if (!wifiConnected || WiFi.status() != WL_CONNECTED) {
    Serial.println("Not connected to WiFi");
    return false;
  }
  
  // Get saved server IP or use default
  String serverIP = preferences.getString("serverip", SERVER_IP);
  
  HTTPClient http;
  
  // Build full URL
  String url = "http://" + serverIP;
  if (SERVER_PORT != 80) {
    url += ":" + String(SERVER_PORT);
  }
  url += "/webapp/" + endpoint;
  
  Serial.print("Sending to: ");
  Serial.println(url);
  Serial.print("Data: ");
  Serial.println(jsonData);
  
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-KEY", API_KEY);
  
  int httpResponseCode = http.POST(jsonData);
  
  bool success = false;
  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.print("Response code: ");
    Serial.println(httpResponseCode);
    Serial.print("Response: ");
    Serial.println(response);
    success = (httpResponseCode == 200);
    serverConnected = true;
  } else {
    Serial.print("Error code: ");
    Serial.println(httpResponseCode);
    serverConnected = false;
  }
  
  http.end();
  return success;
}

// Send device status update to server
void sendStatusUpdate() {
  if (!wifiConnected) return;
  
  // Get current weight
  float weight = scale.get_units(5);  // Average of 5 readings
  
  // Build JSON data
  String jsonData = "{";
  jsonData += "\"weight\":" + String(weight, 2);
  jsonData += ",\"wifi_rssi\":" + String(WiFi.RSSI());
  jsonData += ",\"device_id\":\"ESP32_PetFeeder_001\"";
  jsonData += "}";
  
  sendDataToServer("api/hardware_update.php", jsonData);
}

// Send feed event to server
void sendFeedEvent(int rounds, String feedType) {
  if (!wifiConnected) return;
  
  String jsonData = "{";
  jsonData += "\"dispensed\":" + String(rounds);
  jsonData += ",\"type\":\"" + feedType + "\"";
  jsonData += ",\"weight\":" + String(scale.get_units(5), 2);
  jsonData += "}";
  
  sendDataToServer("api/hardware_update.php", jsonData);
}

// Send alert to server
void sendAlert(String alertType, String message) {
  if (!wifiConnected) return;
  
  String jsonData = "{";
  jsonData += "\"alert\":true";
  jsonData += ",\"alert_type\":\"" + alertType + "\"";
  jsonData += ",\"message\":\"" + message + "\"";
  jsonData += "}";
  
  sendDataToServer("api/hardware_update.php", jsonData);
}

// Check for commands from server
void checkServerCommands() {
  if (!wifiConnected || WiFi.status() != WL_CONNECTED) {
    return;
  }
  
  // Get saved server IP or use default
  String serverIP = preferences.getString("serverip", SERVER_IP);
  
  HTTPClient http;
  
  String url = "http://" + serverIP;
  if (SERVER_PORT != 80) {
    url += ":" + String(SERVER_PORT);
  }
  url += "/webapp/api/get_device_status.php";
  
  http.begin(url);
  http.addHeader("X-API-KEY", API_KEY);
  
  int httpResponseCode = http.GET();
  
  if (httpResponseCode == 200) {
    String response = http.getString();
    Serial.println("Server command check: " + response);
    
    // Parse response for commands (you can expand this)
    // Example: check for dispense command, schedule updates, etc.
  }
  
  http.end();
}

// Periodic server update function - call this in main loop
void handleServerCommunication() {
  if (!wifiConnected) return;
  
  unsigned long currentTime = millis();
  
  // Send status update every 30 seconds
  if (currentTime - lastServerUpdate >= serverUpdateInterval) {
    lastServerUpdate = currentTime;
    sendStatusUpdate();
    checkServerCommands();
  }
}