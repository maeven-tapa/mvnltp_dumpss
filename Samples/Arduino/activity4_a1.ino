#include <OneWire.h>
#include <DallasTemperature.h>

#define trigPin 10
#define echoPin 11
#define ledPin 13
#define tempPin 12    // DS18B20 data pin
#define ldrPin A0

#define TEMP_THRESHOLD 30.0
#define LIGHT_THRESHOLD 400
#define DIST_THRESHOLD 20

bool autoMode = true;

// Setup DS18B20
OneWire oneWire(tempPin);
DallasTemperature sensors(&oneWire);

void setup() {
  Serial.begin(9600);
  pinMode(trigPin, OUTPUT);
  pinMode(echoPin, INPUT);
  pinMode(ledPin, OUTPUT);

  sensors.begin();

  Serial.println("System Ready.");
  Serial.println("Type 'A' for Auto Mode, '1' to force LED ON, '0' to force LED OFF.");
}

void loop() {
  bool ledState = false;

  if (Serial.available() > 0) {
    char cmd = Serial.read();
    if (cmd == 'A' || cmd == 'a') {
      autoMode = true;
      Serial.println("Switched to AUTO mode.");
    } else if (cmd == '1') {
      autoMode = false;
      digitalWrite(ledPin, HIGH);
      Serial.println("Manual Mode: LED ON");
    } else if (cmd == '0') {
      autoMode = false;
      digitalWrite(ledPin, LOW);
      Serial.println("Manual Mode: LED OFF");
    }
  }

  if (autoMode) {
    sensors.requestTemperatures();
    float temperature = sensors.getTempCByIndex(0);
    if (temperature > TEMP_THRESHOLD) {
      ledState = true;
    }

    int lightValue = analogRead(ldrPin);
    if (lightValue < LIGHT_THRESHOLD) {
      ledState = true;
    }

    digitalWrite(trigPin, LOW);
    delayMicroseconds(2);
    digitalWrite(trigPin, HIGH);
    delayMicroseconds(10);
    digitalWrite(trigPin, LOW);
    long duration = pulseIn(echoPin, HIGH);
    float distance = (duration * 0.034) / 2;
    if (distance < DIST_THRESHOLD) {
      ledState = true;
    }

    digitalWrite(ledPin, ledState ? HIGH : LOW);
    Serial.print("Temp: ");
    Serial.print(temperature);
    Serial.print(" °C | Light: ");
    Serial.print(lightValue);
    Serial.print(" | Distance: ");
    Serial.print(distance);
    Serial.print(" cm | LED: ");
    Serial.println(ledState ? "ON (Auto)" : "OFF (Auto)");
  }

  delay(1000);
}
