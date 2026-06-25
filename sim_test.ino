#include <Wire.h>
#include <DFRobot_BMI160.h>

HardwareSerial sim800(1);
DFRobot_BMI160 bmi160;

int16_t accelGyro[6];

String phoneNumber = "09925283361";

unsigned long lastSend = 0;
const unsigned long SEND_INTERVAL = 10000;

bool sendAT(String cmd, String expected, unsigned long timeout) {
  sim800.println(cmd);
  unsigned long start = millis();
  String response = "";

  while (millis() - start < timeout) {
    while (sim800.available()) {
      char c = sim800.read();
      response += c;
    }

    if (response.indexOf(expected) != -1) {
      Serial.println(response);
      return true;
    }
  }

  Serial.println(response);
  return false;
}

bool checkSIM800() {
  Serial.println("Checking SIM800L...");

  if (!sendAT("AT", "OK", 3000)) {
    Serial.println("SIM800L not responding.");
    return false;
  }

  Serial.println("SIM800L detected.");

  if (!sendAT("AT+CPIN?", "READY", 3000)) {
    Serial.println("SIM card not ready.");
    return false;
  }

  Serial.println("SIM card ready.");

  sim800.println("AT+CSQ");
  delay(1000);

  String response = "";
  while (sim800.available()) {
    response += char(sim800.read());
  }

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

  if (signal < 10 || signal == 99) {
    Serial.println("Signal too weak.");
    return false;
  }

  Serial.println("Signal OK.");
  return true;
}

void sendSMS(String number, String msg) {
  Serial.println("Preparing SMS...");

  if (!checkSIM800()) {
    Serial.println("SMS cancelled. SIM800L not ready.");
    return;
  }

  sendAT("AT+CMGF=1", "OK", 3000);

  sim800.print("AT+CMGS=\"");
  sim800.print(number);
  sim800.println("\"");

  delay(1000);

  sim800.print(msg);
  delay(500);

  sim800.write(26);
  delay(5000);

  Serial.println("SMS command sent.");
}

void setup() {
  Serial.begin(115200);
  delay(1000);

  Wire.begin(8, 9);

  sim800.begin(9600, SERIAL_8N1, 16, 15);

  Serial.println("Initializing BMI160...");

  if (bmi160.softReset() != BMI160_OK) {
    Serial.println("BMI160 not detected.");
    while (1);
  }

  bmi160.I2cInit();

  Serial.println("BMI160 Ready.");
  Serial.print("Default number: ");
  Serial.println(phoneNumber);
  Serial.println("Type new number then press Enter if you want to change it.");

  checkSIM800();
}

void loop() {
  if (Serial.available()) {
    String newNumber = Serial.readStringUntil('\n');
    newNumber.trim();

    if (newNumber.length() > 0) {
      phoneNumber = newNumber;
      Serial.print("New phone number saved: ");
      Serial.println(phoneNumber);
    }
  }

  bmi160.getAccelGyroData(accelGyro);

  float ax = accelGyro[0] / 16384.0;
  float ay = accelGyro[1] / 16384.0;
  float az = accelGyro[2] / 16384.0;

  Serial.print("X: ");
  Serial.print(ax, 2);
  Serial.print(" | Y: ");
  Serial.print(ay, 2);
  Serial.print(" | Z: ");
  Serial.println(az, 2);

  if (millis() - lastSend >= SEND_INTERVAL) {
    String sms = "BMI160 XYZ Data\n";
    sms += "X: " + String(ax, 2);
    sms += "\nY: " + String(ay, 2);
    sms += "\nZ: " + String(az, 2);

    sendSMS(phoneNumber, sms);
    lastSend = millis();
  }

  delay(500);
}