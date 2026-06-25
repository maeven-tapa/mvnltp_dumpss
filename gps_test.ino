#include <TinyGPSPlus.h>

#define GPS_RX 18   // ESP32 RX pin, connect to GPS TX
#define GPS_TX 17   // ESP32 TX pin, connect to GPS RX

HardwareSerial gpsSerial(2);
TinyGPSPlus gps;

unsigned long lastPrint = 0;
const unsigned long PRINT_INTERVAL = 2000; // print every 2 seconds

String rawGPS = "";

void setup() {
  Serial.begin(115200);
  delay(1000);

  gpsSerial.begin(9600, SERIAL_8N1, GPS_RX, GPS_TX);

  rawGPS.reserve(2000);

  Serial.println();
  Serial.println("ESP32-S3 + NEO-M8N GPS TEST");
  Serial.println("---------------------------");
  Serial.println("GPS TX -> ESP32 GPIO 18");
  Serial.println("GPS RX -> ESP32 GPIO 17");
  Serial.println("Printing GPS data every 2 seconds only.");
  Serial.println();
}

void loop() {
  while (gpsSerial.available()) {
    char c = gpsSerial.read();

    // Save raw GPS data
    rawGPS += c;

    // Prevent memory issue if GPS sends too much data
    if (rawGPS.length() > 1800) {
      rawGPS = rawGPS.substring(rawGPS.length() - 1000);
    }

    // Parse GPS data
    gps.encode(c);
  }

  if (millis() - lastPrint >= PRINT_INTERVAL) {
    lastPrint = millis();

    Serial.println();
    Serial.println("========== RAW GPS DATA ==========");
    if (rawGPS.length() > 0) {
      Serial.print(rawGPS);
    } else {
      Serial.println("No raw GPS data received.");
    }

    rawGPS = ""; // clear after printing

    Serial.println();
    Serial.println("========== PARSED GPS DATA ==========");

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

    if (gps.altitude.isValid()) {
      Serial.print("Altitude: ");
      Serial.print(gps.altitude.meters());
      Serial.println(" m");
    } else {
      Serial.println("Altitude: No data");
    }

    if (gps.speed.isValid()) {
      Serial.print("Speed: ");
      Serial.print(gps.speed.kmph());
      Serial.println(" km/h");
    } else {
      Serial.println("Speed: No data");
    }

    if (gps.satellites.isValid()) {
      Serial.print("Satellites: ");
      Serial.println(gps.satellites.value());
    } else {
      Serial.println("Satellites: No data");
    }

    if (gps.hdop.isValid()) {
      Serial.print("HDOP: ");
      Serial.println(gps.hdop.hdop());
    } else {
      Serial.println("HDOP: No data");
    }

    if (gps.date.isValid() && gps.time.isValid()) {
      Serial.print("Date UTC: ");
      Serial.print(gps.date.month());
      Serial.print("/");
      Serial.print(gps.date.day());
      Serial.print("/");
      Serial.println(gps.date.year());

      Serial.print("Time UTC: ");
      if (gps.time.hour() < 10) Serial.print("0");
      Serial.print(gps.time.hour());
      Serial.print(":");
      if (gps.time.minute() < 10) Serial.print("0");
      Serial.print(gps.time.minute());
      Serial.print(":");
      if (gps.time.second() < 10) Serial.print("0");
      Serial.println(gps.time.second());
    } else {
      Serial.println("Date/Time: No data");
    }

    Serial.print("Characters processed: ");
    Serial.println(gps.charsProcessed());

    if (gps.charsProcessed() < 10) {
      Serial.println("WARNING: No GPS data received.");
      Serial.println("Check TX/RX wiring or try baud rate 38400.");
    }

    Serial.println("=====================================");
  }
}