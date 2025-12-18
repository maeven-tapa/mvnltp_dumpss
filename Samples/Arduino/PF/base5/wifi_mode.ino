/*
 * WiFi Mode Functions
 */

#include "globals.h"

// Forward declarations
void sendCommandCompletion(String commandId, bool success, String message = "");

// HTML for the WiFi configuration page
const char WIFI_CONFIG_HTML[] PROGMEM = R"rawliteral(
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bites and Bowls - WiFi Setup</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ffe4cf 0%, #ffd4b8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: #FFFFFF;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(111, 78, 55, 0.2);
            max-width: 500px;
            width: 100%;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        h1 {
            color: #6F4E37;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .subtitle {
            color: #524339;
            font-size: 0.95rem;
            margin-top: 5px;
        }
        label {
            display: block;
            margin-top: 20px;
            margin-bottom: 8px;
            font-weight: 600;
            color: #524339;
            font-size: 0.95rem;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #D6D2CC;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #FFFFFF;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #6F4E37;
            box-shadow: 0 0 0 3px rgba(111, 78, 55, 0.1);
        }
        .radio-group {
            margin: 15px 0;
            display: flex;
            gap: 20px;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            font-weight: 500;
            margin: 0;
            cursor: pointer;
            color: #524339;
        }
        input[type="radio"] {
            margin-right: 8px;
            cursor: pointer;
            accent-color: #6F4E37;
        }
        .password-field {
            display: none;
        }
        small {
            color: #524339;
            font-size: 0.85rem;
            display: block;
            margin-top: 6px;
            opacity: 0.8;
        }
        button {
            width: 100%;
            padding: 14px;
            margin-top: 25px;
            background: #6F4E37;
            color: #F8F4F0;
            border: none;
            border-radius: 8px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        button:hover {
            background: #524339;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(111, 78, 55, 0.3);
        }
        button:active {
            transform: translateY(0);
        }
        .status {
            margin-top: 20px;
            padding: 12px 15px;
            border-radius: 8px;
            display: none;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        <div class="header">
            <h1>🐾 Bites and Bowls</h1>
            <p class="subtitle">WiFi Configuration</p>
        </div>
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
            <small>Enter your computer's local IP address where the web app is hosted</small>
            
            <button type="submit">💾 Save & Connect</button>
        </form>
        
        <div id="status" class="status"></div>
    </div>
</body>
</html>
)rawliteral";

// Helper function to get saved server IP
String getSavedServerIP() {
  // Return the global savedServerIP variable (loaded in initWiFi)
  if (savedServerIP.length() > 0) {
    return savedServerIP;
  }
  return String(SERVER_IP);
}

// Initialize WiFi and load saved credentials
void initWiFi() {
  preferences.begin("wifi-config", false);
  
  // Load saved WiFi credentials and server IP
  savedSSID = preferences.getString("ssid", "");
  savedPassword = preferences.getString("password", "");
  savedServerIP = preferences.getString("serverip", SERVER_IP);
  
  preferences.end();  // Close preferences after reading
  
  // Update global SERVER_IP if saved
  if (savedServerIP.length() > 0) {
    Serial.print("Loaded Server IP: ");
    Serial.println(savedServerIP);
  }
  
  Serial.println("WiFi Initialization");
  
  // Set WiFi to auto-reconnect
  WiFi.setAutoReconnect(true);
  WiFi.persistent(true);  // Save WiFi credentials to flash
  
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

// Connect to WiFi network (non-blocking version)
void connectToWiFi(String ssid, String password) {
  Serial.print("Connecting to WiFi: ");
  Serial.println(ssid);
  
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid.c_str(), password.c_str());
  
  // Start tracking connection attempt
  wifiConnectionInProgress = true;
  wifiConnectionStartTime = millis();
  wifiConnected = false;
  
  Serial.println("WiFi connection initiated (non-blocking)");
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
    preferences.begin("wifi-config", false);
    preferences.putString("ssid", ssid);
    preferences.putString("password", password);
    preferences.putString("serverip", serverip);
    preferences.end();
    
    savedSSID = ssid;
    savedPassword = password;
    savedServerIP = serverip;
    
    Serial.print("Saved Server IP: ");
    Serial.println(savedServerIP);
    
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
  
  preferences.begin("wifi-config", false);
  preferences.putString("ssid", "");
  preferences.putString("password", "");
  preferences.putString("serverip", "");
  preferences.end();
  
  savedSSID = "";
  savedPassword = "";
  savedServerIP = "";
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
  static bool lastWifiStatus = false;
  static bool lastServerStatus = false;
  static int lastRSSI = 0;
  
  // Update WiFi status every 2 seconds
  if (millis() - lastUpdate >= 2000) {
    lastUpdate = millis();
    
    bool currentWifiStatus = (wifiConnected && WiFi.status() == WL_CONNECTED);
    
    if (currentWifiStatus) {
      wifiRSSI = WiFi.RSSI();
      
      // Test server connection
      testServerConnection();
      
      // Check if WiFi status, server status, or RSSI changed significantly
      if (lastWifiStatus != currentWifiStatus || 
          lastServerStatus != serverConnected ||
          abs(lastRSSI - wifiRSSI) > 5) {  // Redraw if RSSI changed by more than 5 dBm
        lastWifiStatus = currentWifiStatus;
        lastServerStatus = serverConnected;
        lastRSSI = wifiRSSI;
        drawWiFiUI();  // Redraw UI to show updated status
      }
    } else if (wifiConnected && WiFi.status() != WL_CONNECTED) {
      // Connection lost
      wifiConnected = false;
      serverConnected = false;
      lastWifiStatus = false;
      lastServerStatus = false;
      drawWiFiUI();
      Serial.println("WiFi connection lost in WiFi Mode");
    } else if (!wifiConnected && lastWifiStatus) {
      // Status changed from connected to disconnected
      lastWifiStatus = false;
      lastServerStatus = false;
      drawWiFiUI();
    }
  }
}

// Check WiFi connection status (non-blocking) - call this in main loop
void checkWiFiConnection() {
  // Only check if a connection attempt is in progress
  if (!wifiConnectionInProgress) {
    // Check if we have saved credentials but are disconnected
    if (savedSSID.length() > 0 && !wifiConnected && WiFi.status() != WL_CONNECTED) {
      // Try to reconnect
      static unsigned long lastReconnectAttempt = 0;
      if (millis() - lastReconnectAttempt >= 30000) {  // Try every 30 seconds
        lastReconnectAttempt = millis();
        Serial.println("WiFi disconnected, attempting reconnection...");
        connectToWiFi(savedSSID, savedPassword);
      }
    }
    return;
  }
  
  // Check if connection succeeded
  if (WiFi.status() == WL_CONNECTED) {
    wifiConnected = true;
    wifiRSSI = WiFi.RSSI();
    wifiConnectionInProgress = false;
    Serial.println("\nWiFi Connected!");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
    Serial.print("RSSI: ");
    Serial.println(wifiRSSI);
    
    // Update display based on current mode
    if (currentMode == WIFI_MODE) {
      testServerConnection();  // Check server immediately
      drawWiFiUI();
    } else if (currentMode == HOME_MODE) {
      drawClockUI();  // Refresh home display to show WiFi icon
    }
    return;
  }
  
  // Check if connection timeout
  if (millis() - wifiConnectionStartTime >= wifiConnectTimeout) {
    wifiConnectionInProgress = false;
    wifiConnected = false;
    Serial.println("\nWiFi Connection Failed (timeout)!");
    
    // Update WiFi UI if in WiFi mode
    if (currentMode == WIFI_MODE) {
      drawWiFiUI();
    }
    return;
  }
  
  // Periodic connection check - print dots while connecting
  static unsigned long lastPrint = 0;
  if (millis() - lastPrint >= 500) {
    lastPrint = millis();
    Serial.print(".");
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
        saveCurrentMode();
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

// Test server connection
void testServerConnection() {
  if (!wifiConnected || WiFi.status() != WL_CONNECTED) {
    serverConnected = false;
    return;
  }
  
  // Get saved server IP or use default
  String serverIP = getSavedServerIP();
  
  HTTPClient http;
  
  // Build full URL to test endpoint
  String url = "http://" + serverIP;
  if (SERVER_PORT != 80) {
    url += ":" + String(SERVER_PORT);
  }
  url += API_DEVICE_STATUS;
  
  Serial.print("Testing server connection: ");
  Serial.println(url);
  
  http.begin(url);
  http.setTimeout(3000);  // 3 second timeout
  
  int httpResponseCode = http.GET();
  
  if (httpResponseCode > 0) {
    Serial.println("Server ONLINE");
    serverConnected = true;
  } else {
    Serial.println("Server OFFLINE");
    serverConnected = false;
  }
  
  http.end();
}

// Send data to web server
bool sendDataToServer(String endpoint, String jsonData) {
  if (!wifiConnected || WiFi.status() != WL_CONNECTED) {
    Serial.println("Not connected to WiFi");
    return false;
  }
  
  // Get saved server IP or use default
  String serverIP = getSavedServerIP();
  
  HTTPClient http;
  
  // Build full URL
  String url = "http://" + serverIP;
  if (SERVER_PORT != 80) {
    url += ":" + String(SERVER_PORT);
  }
  url += endpoint;
  
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
  
  // Use the current weight from scaling mode (updated every 200ms)
  // This ensures web app shows same weight as Arduino display
  float weight = currentWeight;
  
  // If currentWeight is 0 or not initialized, read from scale
  if (weight == 0.0) {
    weight = scale.get_units(5);  // Average of 5 readings
    if (weight < 0) weight = 0;
  }
  
  // Build JSON data
  String jsonData = "{";
  jsonData += "\"weight\":" + String(weight, 2);
  jsonData += ",\"wifi_rssi\":" + String(WiFi.RSSI());
  jsonData += ",\"device_id\":\"ESP32_PetFeeder_001\"";
  jsonData += ",\"firmware_version\":\"" + String(FIRMWARE_VERSION) + "\"";
  jsonData += "}";
  
  sendDataToServer(API_HARDWARE_UPDATE, jsonData);
}

// Send feed event to server
void sendFeedEvent(int rounds, String feedType, String status) {
  if (!wifiConnected) return;
  
  // Get current date and time
  struct tm timeinfo;
  if (!getLocalTime(&timeinfo)) {
    Serial.println("Failed to obtain time for feed event");
    return;
  }
  
  char dateStr[11];
  char timeStr[9];
  strftime(dateStr, sizeof(dateStr), "%Y-%m-%d", &timeinfo);
  strftime(timeStr, sizeof(timeStr), "%H:%M:%S", &timeinfo);
  
  String jsonData = "{";
  jsonData += "\"dispensed\":" + String(rounds);
  jsonData += ",\"type\":\"" + feedType + "\"";
  jsonData += ",\"status\":\"" + status + "\"";
  jsonData += ",\"weight\":" + String(scale.get_units(5), 2);
  jsonData += ",\"feed_date\":\"" + String(dateStr) + "\"";
  jsonData += ",\"feed_time\":\"" + String(timeStr) + "\"";
  jsonData += "}";
  
  sendDataToServer(API_HARDWARE_UPDATE, jsonData);
}

// Send alert to server
void sendAlert(String alertType, String message) {
  if (!wifiConnected) return;
  
  String jsonData = "{";
  jsonData += "\"alert\":true";
  jsonData += ",\"alert_type\":\"" + alertType + "\"";
  jsonData += ",\"message\":\"" + message + "\"";
  jsonData += "}";
  
  sendDataToServer(API_HARDWARE_UPDATE, jsonData);
}

// Check for commands from server
void checkServerCommands() {
  if (!wifiConnected || WiFi.status() != WL_CONNECTED) {
    return;
  }
  
  Serial.print("[CHECK] Checking for commands from server (Mode: ");
  Serial.print(currentMode);
  Serial.println(")...");
  
  // Get saved server IP or use default
  String serverIP = getSavedServerIP();
  
  HTTPClient http;
  
  String url = "http://" + serverIP;
  if (SERVER_PORT != 80) {
    url += ":" + String(SERVER_PORT);
  }
  url += "/webapp/api/get_commands.php";
  
  Serial.print("[CHECK] URL: ");
  Serial.println(url);
  
  http.begin(url);
  http.addHeader("X-API-KEY", API_KEY);
  
  int httpResponseCode = http.GET();
  Serial.print("[CHECK] Response code: ");
  Serial.println(httpResponseCode);
  
  if (httpResponseCode == 200) {
    String response = http.getString();
    Serial.println("Server command received: " + response);
    
    // Parse JSON response
    if (response.indexOf("\"has_command\":true") > 0) {
      Serial.println("======================================");
      Serial.println("[COMMAND] Command received from server!");
      Serial.println("======================================");
      
      // Check for factory reset command
      if (response.indexOf("\"type\":\"factory_reset\"") > 0) {
        Serial.println("[COMMAND] Type: Factory Reset");
        
        // Extract command ID
        int idStart = response.indexOf("\"id\":") + 5;
        int idEnd = response.indexOf(",", idStart);
        String commandId = response.substring(idStart, idEnd);
        
        // Send completion feedback before reset
        sendCommandCompletion(commandId, true, "Factory reset initiated");
        
        executeFactoryReset();
      }
      // Check for dispense command
      else if (response.indexOf("\"type\":\"dispense\"") > 0) {
        Serial.println("[COMMAND] Type: Dispense Food");
        
        // Extract command data (format: "rounds:feedType")
        int dataStart = response.indexOf("\"data\":\"") + 8;
        int dataEnd = response.indexOf("\"", dataStart);
        String dataStr = response.substring(dataStart, dataEnd);
        
        Serial.print("[COMMAND] Raw data: ");
        Serial.println(dataStr);
        
        // Parse rounds and feed type from "rounds:feedType" format
        int colonIndex = dataStr.indexOf(':');
        int rounds = 0;
        String feedType = "Quick";
        
        if (colonIndex > 0) {
          // Format is "rounds:feedType"
          String roundsStr = dataStr.substring(0, colonIndex);
          rounds = roundsStr.toInt();
          feedType = dataStr.substring(colonIndex + 1);
        } else {
          // Fallback: just rounds (old format)
          rounds = dataStr.toInt();
        }
        
        Serial.print("[COMMAND] Rounds to dispense: ");
        Serial.println(rounds);
        Serial.print("[COMMAND] Feed type: ");
        Serial.println(feedType);
        
        if (rounds > 0 && rounds <= 10) {
          // Extract command ID
          int idStart = response.indexOf("\"id\":") + 5;
          int idEnd = response.indexOf(",", idStart);
          String commandId = response.substring(idStart, idEnd);
          
          // Show on display
          currentMode = HOME_MODE;
          saveCurrentMode();
          showBowlIcon = true;
          bowlIconStartTime = millis();
          drawClockUI();
          
          // Execute the feeding
          Serial.println("[COMMAND] Executing feed command...");
          totalRounds = rounds;
          executeFeedingSequenceWithType(rounds, feedType);
          
          Serial.println("[COMMAND] Feed command completed!");
          
          // Send command completion feedback to server
          sendCommandCompletion(commandId, true, "Dispensed " + String(rounds) + " rounds successfully");
          
          // Send status update to server
          Serial.println("[COMMAND] Sending status update to server...");
          sendStatusUpdate();
          
          Serial.println("[COMMAND] Command processing complete!");
          Serial.println("======================================");
          
          // Update icon time after feeding
          bowlIconStartTime = millis();
        } else {
          Serial.print("[COMMAND] ERROR: Invalid rounds value: ");
          Serial.println(rounds);
          Serial.println("======================================");
        }
      }
      // Check for recalibrate command
      else if (response.indexOf("\"type\":\"recalibrate\"") > 0) {
        Serial.println("[COMMAND] Type: Recalibrate Scale");
        
        // Extract command ID
        int idStart = response.indexOf("\"id\":") + 5;
        int idEnd = response.indexOf(",", idStart);
        String commandId = response.substring(idStart, idEnd);
        
        // Perform recalibration (tare the scale)
        Serial.println("[COMMAND] Performing recalibration...");
        scale.tare();
        
        // Save tare offset to flash memory
        preferences.begin("petfeeder", false);
        preferences.putLong("tare_offset", scale.get_offset());
        preferences.end();
        
        Serial.println("[COMMAND] Recalibration completed!");
        
        // Send command completion feedback to server
        sendCommandCompletion(commandId, true, "Recalibration completed successfully");
        
        // Send status update to server
        Serial.println("[COMMAND] Sending status update to server...");
        sendStatusUpdate();
        
        Serial.println("[COMMAND] Command processing complete!");
        Serial.println("======================================");
      }
      // Add more command types here as needed
    }
  }
  
  http.end();
}

// Send command completion feedback to server
void sendCommandCompletion(String commandId, bool success, String message) {
  if (!wifiConnected || WiFi.status() != WL_CONNECTED) {
    return;
  }
  
  Serial.println("[FEEDBACK] Sending command completion to server...");
  Serial.print("[FEEDBACK] Command ID: ");
  Serial.println(commandId);
  Serial.print("[FEEDBACK] Success: ");
  Serial.println(success ? "true" : "false");
  
  String serverIP = getSavedServerIP();
  HTTPClient http;
  
  String url = "http://" + serverIP;
  if (SERVER_PORT != 80) {
    url += ":" + String(SERVER_PORT);
  }
  url += "/webapp/api/command_complete.php";
  
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-KEY", API_KEY);
  
  String jsonData = "{";
  jsonData += "\"command_id\":" + commandId + ",";
  jsonData += "\"success\":" + String(success ? "true" : "false");
  if (message.length() > 0) {
    jsonData += ",\"message\":\"" + message + "\"";
  }
  jsonData += "}";
  
  Serial.print("[FEEDBACK] Payload: ");
  Serial.println(jsonData);
  
  int httpResponseCode = http.POST(jsonData);
  
  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.print("[FEEDBACK] Response code: ");
    Serial.println(httpResponseCode);
    Serial.print("[FEEDBACK] Response: ");
    Serial.println(response);
  } else {
    Serial.print("[FEEDBACK] Error sending completion: ");
    Serial.println(httpResponseCode);
  }
  
  http.end();
  Serial.println("[FEEDBACK] Command completion sent!");
}

// Execute factory reset
void executeFactoryReset() {
  Serial.println("=======================================");
  Serial.println("EXECUTING FACTORY RESET");
  Serial.println("=======================================");
  
  // Show confirmation on display
  tft.fillScreen(ST77XX_RED);
  tft.setTextSize(2);
  tft.setTextColor(ST77XX_WHITE);
  tft.setCursor(40, 100);
  tft.print("FACTORY RESET");
  tft.setCursor(60, 130);
  tft.print("IN PROGRESS...");
  
  // Buzz to indicate reset
  for (int i = 0; i < 3; i++) {
    digitalWrite(BUZZER, HIGH);
    delay(200);
    digitalWrite(BUZZER, LOW);
    delay(200);
  }
  
  // Clear all Preferences
  preferences.begin("device-settings", false);
  preferences.clear();
  preferences.end();
  
  preferences.begin("wifi-config", false);
  preferences.clear();
  preferences.end();
  
  Serial.println("All preferences cleared");
  
  // Show completion message
  tft.fillScreen(ST77XX_WHITE);
  tft.setTextSize(2);
  tft.setTextColor(ST77XX_BLACK);
  tft.setCursor(50, 100);
  tft.print("RESET COMPLETE");
  tft.setTextSize(1);
  tft.setCursor(40, 140);
  tft.print("Restarting device...");
  
  delay(3000);
  
  Serial.println("Restarting ESP32...");
  ESP.restart();
}

// Periodic server update function - call this in main loop
void handleServerCommunication() {
  if (!wifiConnected) return;
  
  unsigned long currentTime = millis();
  
  // Check for commands every 3 seconds (more responsive)
  if (currentTime - lastCommandCheck >= commandCheckInterval) {
    lastCommandCheck = currentTime;
    checkServerCommands();
  }
  
  // Send status update every 30 seconds
  if (currentTime - lastServerUpdate >= serverUpdateInterval) {
    lastServerUpdate = currentTime;
    sendStatusUpdate();
  }
}