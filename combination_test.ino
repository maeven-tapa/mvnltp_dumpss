#include <Wire.h>
#include <TinyGPSPlus.h>
#include <DFRobot_BMI160.h>

// ===================== PINS =====================
#define BMI_SDA 8
#define BMI_SCL 9

#define GPS_RX 18   // ESP32 RX <- NEO-M8N TX
#define GPS_TX 17   // ESP32 TX -> NEO-M8N RX

#define SIM800_RX 16   // ESP32 RX <- SIM800 TX
#define SIM800_TX 15   // ESP32 TX -> SIM800 RX

#define BTN_GPS 35     // Send GPS only
#define BTN_GYRO 36    // Send Gyro only
#define BTN_ALL 37     // Send all data

// ===================== SETTINGS =====================
String phoneNumber = "09925283361";

int minSignal = 5;  // CSQ minimum allowed. 5 allows weak signal for testing.

#define LR_FLIP 1
#define FB_FLIP -1   // forward/backward switched

const float ACC_THRESHOLD = 0.08;
const float ANGLE_THRESHOLD = 5.0;

const unsigned long BUTTON_DEBOUNCE = 350;
const unsigned long LIVE_PRINT_INTERVAL = 2000;

// ===================== OBJECTS =====================
HardwareSerial sim800(1);
HardwareSerial gpsSerial(2);

TinyGPSPlus gps;
DFRobot_BMI160 bmi160;

int16_t accelGyro[6];

// ===================== STATE =====================
bool bmiReady = false;
bool simReady = false;
bool livePrint = false;

String rawGPS = "";

unsigned long lastLivePrint = 0;

bool lastGpsBtn = LOW;
bool lastGyroBtn = LOW;
bool lastAllBtn = LOW;

unsigned long lastGpsBtnTime = 0;
unsigned long lastGyroBtnTime = 0;
unsigned long lastAllBtnTime = 0;

// ===================== ZERO MODE VALUES =====================
float zeroAX = 0, zeroAY = 0, zeroAZ = 0;
float zeroGX = 0, zeroGY = 0, zeroGZ = 0;
float zeroPitch = 0, zeroRoll = 0;

// ===================== GPS UPDATE =====================
void updateGPS() {
  while (gpsSerial.available()) {
    char c = gpsSerial.read();

    rawGPS += c;
    if (rawGPS.length() > 1800) {
      rawGPS = rawGPS.substring(rawGPS.length() - 1000);
    }

    gps.encode(c);
  }
}

void collectGPS(unsigned long durationMs) {
  unsigned long start = millis();

  while (millis() - start < durationMs) {
    updateGPS();
    delay(2);
  }
}

// ===================== SIM800 FUNCTIONS =====================
void clearSIM800Buffer() {
  while (sim800.available()) {
    sim800.read();
  }
}

bool sendAT(String cmd, String expected, unsigned long timeout) {
  clearSIM800Buffer();
  delay(100);

  Serial.print("SEND: ");
  Serial.println(cmd);

  sim800.println(cmd);

  unsigned long start = millis();
  String response = "";

  while (millis() - start < timeout) {
    updateGPS();

    while (sim800.available()) {
      char c = sim800.read();
      response += c;
    }

    if (response.indexOf(expected) != -1) {
      Serial.println("RESPONSE:");
      Serial.println(response);
      return true;
    }
  }

  Serial.println("RESPONSE:");
  Serial.println(response);
  return false;
}

bool checkSIM800() {
  Serial.println();
  Serial.println("Checking SIM800L...");

  clearSIM800Buffer();
  delay(100);

  if (!sendAT("AT", "OK", 3000)) {
    Serial.println("SIM800L not responding.");
    return false;
  }

  Serial.println("SIM800L module detected.");

  sendAT("ATE0", "OK", 3000);

  if (!sendAT("AT+CPIN?", "READY", 5000)) {
    Serial.println("SIM card not ready.");
    return false;
  }

  Serial.println("SIM card ready.");

  Serial.println("Checking network registration...");
  sendAT("AT+CREG?", "OK", 3000);

  clearSIM800Buffer();
  delay(100);

  Serial.println("Checking signal...");
  sim800.println("AT+CSQ");

  unsigned long start = millis();
  String response = "";

  while (millis() - start < 3000) {
    updateGPS();

    while (sim800.available()) {
      char c = sim800.read();
      response += c;
    }
  }

  Serial.println("CSQ RESPONSE:");
  Serial.println(response);

  int csqIndex = response.indexOf("+CSQ:");
  if (csqIndex == -1) {
    Serial.println("Cannot read signal.");
    return false;
  }

  int commaIndex = response.indexOf(",", csqIndex);
  int signal = response.substring(csqIndex + 6, commaIndex).toInt();

  Serial.print("Signal value: ");
  Serial.println(signal);

  if (signal == 99) {
    Serial.println("No signal detected.");
    return false;
  }

  if (signal < minSignal) {
    Serial.println("Signal very weak.");
    return false;
  }

  if (signal < 10) {
    Serial.println("Signal weak but allowed for testing.");
  } else {
    Serial.println("Signal OK.");
  }

  return true;
}

bool waitForPrompt(unsigned long timeout) {
  unsigned long start = millis();
  String response = "";

  while (millis() - start < timeout) {
    updateGPS();

    while (sim800.available()) {
      char c = sim800.read();
      response += c;

      if (c == '>') {
        Serial.println("SMS prompt received.");
        return true;
      }
    }
  }

  Serial.println("SMS prompt response:");
  Serial.println(response);
  return false;
}

bool sendSMS(String number, String msg) {
  Serial.println();
  Serial.println("Preparing SMS...");
  Serial.println("Testing SIM800L before sending...");

  if (!checkSIM800()) {
    Serial.println("SMS cancelled. SIM800L not ready.");
    return false;
  }

  if (!sendAT("AT+CMGF=1", "OK", 3000)) {
    Serial.println("SMS text mode failed.");
    return false;
  }

  clearSIM800Buffer();
  delay(100);

  Serial.print("SEND: AT+CMGS=\"");
  Serial.print(number);
  Serial.println("\"");

  sim800.print("AT+CMGS=\"");
  sim800.print(number);
  sim800.println("\"");

  if (!waitForPrompt(5000)) {
    Serial.println("SMS prompt not received.");
    return false;
  }

  sim800.print(msg);
  delay(500);

  sim800.write(26);  // CTRL + Z

  Serial.println("Sending SMS...");
  Serial.println("Please wait...");

  unsigned long start = millis();
  String response = "";

  while (millis() - start < 20000) {
    updateGPS();

    while (sim800.available()) {
      char c = sim800.read();
      response += c;
    }

    if (response.indexOf("+CMGS:") != -1 && response.indexOf("OK") != -1) {
      Serial.println("SMS RESPONSE:");
      Serial.println(response);
      Serial.println("SMS SENT SUCCESSFULLY.");
      return true;
    }

    if (response.indexOf("ERROR") != -1) {
      Serial.println("SMS RESPONSE:");
      Serial.println(response);
      Serial.println("SMS FAILED.");
      return false;
    }
  }

  Serial.println("SMS RESPONSE:");
  Serial.println(response);
  Serial.println("SMS send timeout.");
  return false;
}

// ===================== BMI160 FUNCTIONS =====================
bool startBMI160() {
  Serial.println();
  Serial.println("Initializing BMI160...");
  Serial.println("SDA = GPIO 8");
  Serial.println("SCL = GPIO 9");

  Wire.begin(BMI_SDA, BMI_SCL);
  delay(100);

  if (bmi160.I2cInit(0x68) == BMI160_OK) {
    Serial.println("BMI160 detected at address 0x68.");
    return true;
  }

  delay(100);

  if (bmi160.I2cInit(0x69) == BMI160_OK) {
    Serial.println("BMI160 detected at address 0x69.");
    return true;
  }

  Serial.println("BMI160 not detected.");
  return false;
}

bool readBMI160(float &ax, float &ay, float &az, float &gx, float &gy, float &gz) {
  int result = bmi160.getAccelGyroData(accelGyro);

  if (result != 0) {
    return false;
  }

  // DFRobot BMI160 order:
  // accelGyro[0] = Gyro X
  // accelGyro[1] = Gyro Y
  // accelGyro[2] = Gyro Z
  // accelGyro[3] = Accel X
  // accelGyro[4] = Accel Y
  // accelGyro[5] = Accel Z

  gx = accelGyro[0] / 16.4;
  gy = accelGyro[1] / 16.4;
  gz = accelGyro[2] / 16.4;

  ax = accelGyro[3] / 16384.0;
  ay = accelGyro[4] / 16384.0;
  az = accelGyro[5] / 16384.0;

  return true;
}

float getPitch(float ax, float ay, float az) {
  return atan2(-ax, sqrt((ay * ay) + (az * az))) * RAD_TO_DEG;
}

float getRoll(float ax, float ay, float az) {
  return atan2(ay, az) * RAD_TO_DEG;
}

void setZeroMode() {
  if (!bmiReady) {
    Serial.println("Cannot zero. BMI160 not ready.");
    return;
  }

  Serial.println();
  Serial.println("ZERO MODE...");
  Serial.println("Keep board steady.");

  float sumAX = 0, sumAY = 0, sumAZ = 0;
  float sumGX = 0, sumGY = 0, sumGZ = 0;
  float sumPitch = 0, sumRoll = 0;

  const int samples = 100;
  int validSamples = 0;

  for (int i = 0; i < samples; i++) {
    updateGPS();

    float ax, ay, az, gx, gy, gz;

    if (readBMI160(ax, ay, az, gx, gy, gz)) {
      sumAX += ax;
      sumAY += ay;
      sumAZ += az;

      sumGX += gx;
      sumGY += gy;
      sumGZ += gz;

      sumPitch += getPitch(ax, ay, az);
      sumRoll += getRoll(ax, ay, az);

      validSamples++;
    }

    delay(5);
  }

  if (validSamples > 0) {
    zeroAX = sumAX / validSamples;
    zeroAY = sumAY / validSamples;
    zeroAZ = sumAZ / validSamples;

    zeroGX = sumGX / validSamples;
    zeroGY = sumGY / validSamples;
    zeroGZ = sumGZ / validSamples;

    zeroPitch = sumPitch / validSamples;
    zeroRoll = sumRoll / validSamples;

    Serial.println("ZERO SAVED.");
    Serial.println("Current board position is now the reference position.");
  } else {
    Serial.println("ZERO FAILED. BMI160 not reading.");
  }

  Serial.println();
}

String getDirection(float leftRightAccel, float forwardBackAccel, float relativeRoll, float relativePitch) {
  String lr = "CENTER";
  String fb = "CENTER";

  if (leftRightAccel > ACC_THRESHOLD || relativeRoll > ANGLE_THRESHOLD) {
    lr = "RIGHT";
  } else if (leftRightAccel < -ACC_THRESHOLD || relativeRoll < -ANGLE_THRESHOLD) {
    lr = "LEFT";
  }

  if (forwardBackAccel > ACC_THRESHOLD || relativePitch > ANGLE_THRESHOLD) {
    fb = "FORWARD";
  } else if (forwardBackAccel < -ACC_THRESHOLD || relativePitch < -ANGLE_THRESHOLD) {
    fb = "BACKWARD";
  }

  return lr + " / " + fb;
}

// ===================== MESSAGE BUILDERS =====================
String getUTCDateTime() {
  if (gps.date.isValid() && gps.time.isValid()) {
    String dt = "";

    dt += String(gps.date.year());
    dt += "-";
    if (gps.date.month() < 10) dt += "0";
    dt += String(gps.date.month());
    dt += "-";
    if (gps.date.day() < 10) dt += "0";
    dt += String(gps.date.day());

    dt += " ";

    if (gps.time.hour() < 10) dt += "0";
    dt += String(gps.time.hour());
    dt += ":";
    if (gps.time.minute() < 10) dt += "0";
    dt += String(gps.time.minute());
    dt += ":";
    if (gps.time.second() < 10) dt += "0";
    dt += String(gps.time.second());

    dt += " UTC";
    return dt;
  }

  return "No GPS time";
}

String createGPSMessage() {
  collectGPS(1500);

  String msg = "";
  msg += "AQUAWATCH GPS REPORT\n";

  if (gps.location.isValid()) {
    msg += "Status: GPS FIX\n";
    msg += "Lat: " + String(gps.location.lat(), 6) + "\n";
    msg += "Lng: " + String(gps.location.lng(), 6) + "\n";
    msg += "Map: https://maps.google.com/?q=";
    msg += String(gps.location.lat(), 6);
    msg += ",";
    msg += String(gps.location.lng(), 6);
    msg += "\n";
  } else {
    msg += "Status: NO GPS FIX\n";
  }

  if (gps.satellites.isValid()) {
    msg += "Sat: " + String(gps.satellites.value()) + "\n";
  } else {
    msg += "Sat: No data\n";
  }

  if (gps.speed.isValid()) {
    msg += "Speed: " + String(gps.speed.kmph(), 2) + " km/h\n";
  } else {
    msg += "Speed: No data\n";
  }

  if (gps.altitude.isValid()) {
    msg += "Alt: " + String(gps.altitude.meters(), 1) + " m\n";
  }

  msg += "Time: " + getUTCDateTime();

  return msg;
}

String createGyroMessage() {
  float ax, ay, az, gx, gy, gz;

  String msg = "";
  msg += "AQUAWATCH GYRO REPORT\n";

  if (!readBMI160(ax, ay, az, gx, gy, gz)) {
    msg += "BMI160 ERROR\n";
    msg += "Sensor reading failed.";
    return msg;
  }

  float relativeAX = ax - zeroAX;
  float relativeAY = ay - zeroAY;
  float relativeAZ = az - zeroAZ;

  float relativeGX = gx - zeroGX;
  float relativeGY = gy - zeroGY;
  float relativeGZ = gz - zeroGZ;

  float relativePitch = getPitch(ax, ay, az) - zeroPitch;
  float relativeRoll = getRoll(ax, ay, az) - zeroRoll;

  float leftRightAccel = relativeAX * LR_FLIP;
  float forwardBackAccel = relativeAY * FB_FLIP;

  float directionRoll = relativeRoll * LR_FLIP;
  float directionPitch = relativePitch * FB_FLIP;

  String direction = getDirection(leftRightAccel, forwardBackAccel, directionRoll, directionPitch);

  msg += "Direction: " + direction + "\n";
  msg += "Pitch: " + String(relativePitch, 2) + " deg\n";
  msg += "Roll: " + String(relativeRoll, 2) + " deg\n";

  msg += "ACC X: " + String(relativeAX, 3) + "g\n";
  msg += "ACC Y: " + String(relativeAY, 3) + "g\n";
  msg += "ACC Z: " + String(relativeAZ, 3) + "g\n";

  msg += "GYRO X: " + String(relativeGX, 2) + "dps\n";
  msg += "GYRO Y: " + String(relativeGY, 2) + "dps\n";
  msg += "GYRO Z: " + String(relativeGZ, 2) + "dps";

  return msg;
}

String createAllMessage() {
  String msg = "";
  msg += "AQUAWATCH FULL REPORT\n\n";
  msg += createGPSMessage();
  msg += "\n\n";
  msg += createGyroMessage();

  return msg;
}

// ===================== SERIAL PRINTS =====================
void printGPSData() {
  collectGPS(1000);

  Serial.println();
  Serial.println("========== GPS DATA ==========");

  if (gps.location.isValid()) {
    Serial.print("Latitude: ");
    Serial.println(gps.location.lat(), 6);

    Serial.print("Longitude: ");
    Serial.println(gps.location.lng(), 6);

    Serial.print("Google Maps: ");
    Serial.print("https://maps.google.com/?q=");
    Serial.print(gps.location.lat(), 6);
    Serial.print(",");
    Serial.println(gps.location.lng(), 6);
  } else {
    Serial.println("Location: NO FIX YET");
  }

  if (gps.satellites.isValid()) {
    Serial.print("Satellites: ");
    Serial.println(gps.satellites.value());
  } else {
    Serial.println("Satellites: No data");
  }

  if (gps.speed.isValid()) {
    Serial.print("Speed: ");
    Serial.print(gps.speed.kmph(), 2);
    Serial.println(" km/h");
  } else {
    Serial.println("Speed: No data");
  }

  if (gps.altitude.isValid()) {
    Serial.print("Altitude: ");
    Serial.print(gps.altitude.meters(), 1);
    Serial.println(" m");
  } else {
    Serial.println("Altitude: No data");
  }

  Serial.print("Time: ");
  Serial.println(getUTCDateTime());

  Serial.print("Characters processed: ");
  Serial.println(gps.charsProcessed());

  Serial.println("==============================");
}

void printGyroData() {
  float ax, ay, az, gx, gy, gz;

  Serial.println();
  Serial.println("========== BMI160 / GYRO DATA ==========");

  if (!readBMI160(ax, ay, az, gx, gy, gz)) {
    Serial.println("BMI160 read error.");
    Serial.println("========================================");
    return;
  }

  float relativeAX = ax - zeroAX;
  float relativeAY = ay - zeroAY;
  float relativeAZ = az - zeroAZ;

  float relativeGX = gx - zeroGX;
  float relativeGY = gy - zeroGY;
  float relativeGZ = gz - zeroGZ;

  float relativePitch = getPitch(ax, ay, az) - zeroPitch;
  float relativeRoll = getRoll(ax, ay, az) - zeroRoll;

  float leftRightAccel = relativeAX * LR_FLIP;
  float forwardBackAccel = relativeAY * FB_FLIP;

  float directionRoll = relativeRoll * LR_FLIP;
  float directionPitch = relativePitch * FB_FLIP;

  String direction = getDirection(leftRightAccel, forwardBackAccel, directionRoll, directionPitch);

  Serial.print("Direction relative to zero: ");
  Serial.println(direction);

  Serial.print("ACC X: ");
  Serial.print(relativeAX, 3);
  Serial.print(" g | Y: ");
  Serial.print(relativeAY, 3);
  Serial.print(" g | Z: ");
  Serial.print(relativeAZ, 3);
  Serial.println(" g");

  Serial.print("Pitch: ");
  Serial.print(relativePitch, 2);
  Serial.print(" deg | Roll: ");
  Serial.print(relativeRoll, 2);
  Serial.println(" deg");

  Serial.print("Gyro X: ");
  Serial.print(relativeGX, 2);
  Serial.print(" dps | Y: ");
  Serial.print(relativeGY, 2);
  Serial.print(" dps | Z: ");
  Serial.print(relativeGZ, 2);
  Serial.println(" dps");

  Serial.println("========================================");
}

void printAllData() {
  printGPSData();
  printGyroData();
}

void printRawGPS() {
  Serial.println();
  Serial.println("========== RAW GPS DATA ==========");

  if (rawGPS.length() > 0) {
    Serial.print(rawGPS);
  } else {
    Serial.println("No raw GPS data received yet.");
  }

  Serial.println();
  Serial.println("==================================");
}

// ===================== BUTTON HANDLER =====================
void handleButtons() {
  bool gpsBtn = digitalRead(BTN_GPS);
  bool gyroBtn = digitalRead(BTN_GYRO);
  bool allBtn = digitalRead(BTN_ALL);

  if (gpsBtn == HIGH && lastGpsBtn == LOW && millis() - lastGpsBtnTime > BUTTON_DEBOUNCE) {
    lastGpsBtnTime = millis();

    Serial.println();
    Serial.println("GPIO 35 pressed: Send GPS SMS");

    String msg = createGPSMessage();

    Serial.println("SMS PREVIEW:");
    Serial.println(msg);

    sendSMS(phoneNumber, msg);
    showMenu();
  }

  if (gyroBtn == HIGH && lastGyroBtn == LOW && millis() - lastGyroBtnTime > BUTTON_DEBOUNCE) {
    lastGyroBtnTime = millis();

    Serial.println();
    Serial.println("GPIO 36 pressed: Send GYRO SMS");

    String msg = createGyroMessage();

    Serial.println("SMS PREVIEW:");
    Serial.println(msg);

    sendSMS(phoneNumber, msg);
    showMenu();
  }

  if (allBtn == HIGH && lastAllBtn == LOW && millis() - lastAllBtnTime > BUTTON_DEBOUNCE) {
    lastAllBtnTime = millis();

    Serial.println();
    Serial.println("GPIO 37 pressed: Send ALL DATA SMS");

    String msg = createAllMessage();

    Serial.println("SMS PREVIEW:");
    Serial.println(msg);

    sendSMS(phoneNumber, msg);
    showMenu();
  }

  lastGpsBtn = gpsBtn;
  lastGyroBtn = gyroBtn;
  lastAllBtn = allBtn;
}

// ===================== MENU / SERIAL COMMANDS =====================
void showMenu() {
  Serial.println();
  Serial.println("========== AQUAWATCH COMMANDS ==========");
  Serial.println("Button GPIO 35 = Send GPS only");
  Serial.println("Button GPIO 36 = Send Gyro only");
  Serial.println("Button GPIO 37 = Send ALL data");
  Serial.println();
  Serial.println("Type 1 then Enter = Send GPS SMS");
  Serial.println("Type 2 then Enter = Send Gyro SMS");
  Serial.println("Type 3 then Enter = Send ALL DATA SMS");
  Serial.println("Type t then Enter = Test SIM800L");
  Serial.println("Type g then Enter = Print GPS data");
  Serial.println("Type b then Enter = Print BMI160/Gyro data");
  Serial.println("Type a then Enter = Print all data");
  Serial.println("Type z then Enter = Set gyro zero mode");
  Serial.println("Type l then Enter = Toggle live serial print");
  Serial.println("Type r then Enter = Print latest RAW GPS data");
  Serial.println("Type n:09XXXXXXXXX = Change phone number");
  Serial.println("Type min:5 = Change minimum CSQ signal");
  Serial.println("Type m then Enter = Show this menu");
  Serial.println("========================================");
  Serial.println();
}

void handleSerialCommand() {
  if (!Serial.available()) {
    return;
  }

  String command = Serial.readStringUntil('\n');
  command.trim();

  if (command.length() == 0) {
    return;
  }

  if (command == "1") {
    String msg = createGPSMessage();

    Serial.println();
    Serial.println("SMS PREVIEW:");
    Serial.println(msg);

    sendSMS(phoneNumber, msg);
    showMenu();
  }

  else if (command == "2") {
    String msg = createGyroMessage();

    Serial.println();
    Serial.println("SMS PREVIEW:");
    Serial.println(msg);

    sendSMS(phoneNumber, msg);
    showMenu();
  }

  else if (command == "3") {
    String msg = createAllMessage();

    Serial.println();
    Serial.println("SMS PREVIEW:");
    Serial.println(msg);

    sendSMS(phoneNumber, msg);
    showMenu();
  }

  else if (command == "t" || command == "T") {
    checkSIM800();
    showMenu();
  }

  else if (command == "g" || command == "G") {
    printGPSData();
    showMenu();
  }

  else if (command == "b" || command == "B") {
    printGyroData();
    showMenu();
  }

  else if (command == "a" || command == "A") {
    printAllData();
    showMenu();
  }

  else if (command == "z" || command == "Z") {
    setZeroMode();
    showMenu();
  }

  else if (command == "l" || command == "L") {
    livePrint = !livePrint;

    Serial.print("Live serial print: ");
    Serial.println(livePrint ? "ON" : "OFF");

    showMenu();
  }

  else if (command == "r" || command == "R") {
    printRawGPS();
    showMenu();
  }

  else if (command == "m" || command == "M") {
    showMenu();
  }

  else if (command.startsWith("n:")) {
    String newNumber = command.substring(2);
    newNumber.trim();

    if (newNumber.length() >= 10) {
      phoneNumber = newNumber;
      Serial.print("New phone number saved: ");
      Serial.println(phoneNumber);
    } else {
      Serial.println("Invalid phone number.");
    }

    showMenu();
  }

  else if (command.startsWith("min:")) {
    String value = command.substring(4);
    value.trim();

    int newMin = value.toInt();

    if (newMin >= 1 && newMin <= 31) {
      minSignal = newMin;
      Serial.print("New minimum CSQ signal: ");
      Serial.println(minSignal);
    } else {
      Serial.println("Invalid CSQ value. Use 1 to 31.");
    }

    showMenu();
  }

  else {
    Serial.println("Unknown command.");
    showMenu();
  }
}

// ===================== SETUP / LOOP =====================
void setup() {
  Serial.begin(115200);
  delay(1000);

  pinMode(BTN_GPS, INPUT);
  pinMode(BTN_GYRO, INPUT);
  pinMode(BTN_ALL, INPUT);

  gpsSerial.begin(9600, SERIAL_8N1, GPS_RX, GPS_TX);
  sim800.begin(9600, SERIAL_8N1, SIM800_RX, SIM800_TX);

  rawGPS.reserve(2000);

  Serial.println();
  Serial.println("AQUAWATCH ESP32-S3 STARTING...");
  Serial.println("--------------------------------");
  Serial.println("BMI160 SDA = GPIO 8");
  Serial.println("BMI160 SCL = GPIO 9");
  Serial.println("GPS RX = GPIO 18");
  Serial.println("GPS TX = GPIO 17");
  Serial.println("SIM800 RX = GPIO 16");
  Serial.println("SIM800 TX = GPIO 15");
  Serial.println("BTN GPS = GPIO 35");
  Serial.println("BTN GYRO = GPIO 36");
  Serial.println("BTN ALL = GPIO 37");
  Serial.println("--------------------------------");

  bmiReady = startBMI160();

  if (!bmiReady) {
    Serial.println("Fix BMI160 wiring first.");
    while (1) {
      updateGPS();
      delay(1000);
    }
  }

  delay(500);
  setZeroMode();

  Serial.println("Testing SIM800L initial signal...");
  simReady = checkSIM800();

  if (simReady) {
    Serial.println("SIM800L initial test OK.");
  } else {
    Serial.println("SIM800L initial test failed.");
    Serial.println("You can still use command t to test again.");
  }

  Serial.println();
  Serial.print("Current phone number: ");
  Serial.println(phoneNumber);

  Serial.println("Collecting GPS data...");
  collectGPS(2000);

  showMenu();
}

void loop() {
  updateGPS();
  handleButtons();
  handleSerialCommand();

  if (livePrint && millis() - lastLivePrint >= LIVE_PRINT_INTERVAL) {
    lastLivePrint = millis();
    printAllData();
  }
}