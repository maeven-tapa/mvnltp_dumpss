#include <TinyGPSPlus.h>

#define GPS_RX 18   // ESP32 RX <- NEO-M8N TX
#define GPS_TX 17   // ESP32 TX -> NEO-M8N RX

// 1 = GPS + Galileo + BeiDou
// 2 = GPS + Galileo + GLONASS
#define GNSS_MODE 1

HardwareSerial gpsSerial(2);
TinyGPSPlus gps;

unsigned long lastPrint = 0;
const unsigned long PRINT_INTERVAL = 2000;

String rawGPS = "";

void sendUBX(uint8_t cls, uint8_t id, const uint8_t *payload, uint16_t len) {
  uint8_t ckA = 0;
  uint8_t ckB = 0;

  auto checksum = [&](uint8_t b) {
    ckA = ckA + b;
    ckB = ckB + ckA;
  };

  gpsSerial.write(0xB5);
  gpsSerial.write(0x62);

  gpsSerial.write(cls);
  checksum(cls);

  gpsSerial.write(id);
  checksum(id);

  gpsSerial.write(len & 0xFF);
  checksum(len & 0xFF);

  gpsSerial.write((len >> 8) & 0xFF);
  checksum((len >> 8) & 0xFF);

  for (uint16_t i = 0; i < len; i++) {
    gpsSerial.write(payload[i]);
    checksum(payload[i]);
  }

  gpsSerial.write(ckA);
  gpsSerial.write(ckB);
}

void addGNSSBlock(uint8_t *payload, int &index, uint8_t gnssId, uint8_t resCh, uint8_t maxCh, bool enable) {
  uint32_t flags;

  if (enable) {
    flags = 0x01010001UL;   // enabled
  } else {
    flags = 0x01010000UL;   // disabled
  }

  payload[index++] = gnssId;
  payload[index++] = resCh;
  payload[index++] = maxCh;
  payload[index++] = 0x00;

  payload[index++] = flags & 0xFF;
  payload[index++] = (flags >> 8) & 0xFF;
  payload[index++] = (flags >> 16) & 0xFF;
  payload[index++] = (flags >> 24) & 0xFF;
}

void configureGNSS() {
  Serial.println();
  Serial.println("Configuring NEO-M8N GNSS...");

  bool useGPS = true;
  bool useGalileo = true;
  bool useBeiDou = false;
  bool useGLONASS = false;

  if (GNSS_MODE == 1) {
    useBeiDou = true;
    useGLONASS = false;
    Serial.println("Mode: GPS + Galileo + BeiDou");
  } else if (GNSS_MODE == 2) {
    useBeiDou = false;
    useGLONASS = true;
    Serial.println("Mode: GPS + Galileo + GLONASS");
  }

  uint8_t payload[60];
  int i = 0;

  payload[i++] = 0x00;  // message version
  payload[i++] = 0x20;  // hardware tracking channels
  payload[i++] = 0x20;  // tracking channels to use
  payload[i++] = 0x07;  // number of GNSS config blocks

  // GNSS IDs:
  // 0 = GPS
  // 1 = SBAS
  // 2 = Galileo
  // 3 = BeiDou
  // 4 = IMES
  // 5 = QZSS
  // 6 = GLONASS

  addGNSSBlock(payload, i, 0, 8, 16, useGPS);       // GPS
  addGNSSBlock(payload, i, 1, 0, 3, false);        // SBAS off
  addGNSSBlock(payload, i, 2, 4, 8, useGalileo);   // Galileo
  addGNSSBlock(payload, i, 3, 8, 16, useBeiDou);   // BeiDou
  addGNSSBlock(payload, i, 4, 0, 8, false);        // IMES off
  addGNSSBlock(payload, i, 5, 0, 3, false);        // QZSS off
  addGNSSBlock(payload, i, 6, 8, 14, useGLONASS);  // GLONASS

  sendUBX(0x06, 0x3E, payload, sizeof(payload));

  Serial.println("GNSS config command sent.");
  Serial.println("Wait 10-30 seconds outside for satellite data.");
  Serial.println();
}

void setup() {
  Serial.begin(115200);
  delay(1000);

  gpsSerial.begin(9600, SERIAL_8N1, GPS_RX, GPS_TX);

  rawGPS.reserve(2000);

  Serial.println();
  Serial.println("ESP32-S3 + NEO-M8N GPS MULTI-GNSS TEST");
  Serial.println("--------------------------------------");
  Serial.println("NEO-M8N TX -> ESP32 GPIO 18");
  Serial.println("NEO-M8N RX -> ESP32 GPIO 17");

  delay(1000);

  configureGNSS();

  Serial.println("Printing GPS data every 2 seconds.");
  Serial.println();
}

void loop() {
  while (gpsSerial.available()) {
    char c = gpsSerial.read();

    rawGPS += c;

    if (rawGPS.length() > 1800) {
      rawGPS = rawGPS.substring(rawGPS.length() - 1000);
    }

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

    rawGPS = "";

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

    Serial.println("=====================================");
  }
}