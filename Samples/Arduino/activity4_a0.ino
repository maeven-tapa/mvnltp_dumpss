#define trigPin 9
#define echoPin 10
#define ledPin 13

// Threshold values
#define TEMP THRESHOLD 30.0 
#define LIGHT THRESHOLD 400 
#define DIST THRESHOLD 20

bool autoMode = true; 

void setup() {
    Serial.begin(9600);
    pinMode(trigPin, OUTPUT);
    pinMode(echoPin, INPUT);
    pinMode(ledPin, OUTPUT);
    Serial.println("System Ready.");
    Serial.println("Type'A' for Auto Mode, '1' to force LED ON, '0' to force LED OFF.");
}

void loop() {
    bool ledState = false;

    // --- Check Serial Commands ---
    if (Serial.available() > 0) {
        char cmd= Serial.read();
        if (cmd='A' || cmd == 'a') {
            autoMode = true;
            Serial.println("Switched to AUTO mode.");
            } 
        else if (cmd='1') {
            autoMode = false;
            digital Write(ledPin, HIGH);
            Serial.println("Manual Mode: LED ON");
            } 
        else if (cmd='0') {
            autoMode = false;
            digital Write(ledPin, LOW);
            Serial.println("Manual Mode: LED OFF");
            }
    }

    if (autoMode) {
        // Temperature
        int tempADC = analogRead(A0);
        float temperature = (tempADC * 5.0 * 100.0) / 1024.0;
        if (temperature > TEMP_THRESHOLD) {
            ledState = true;
        }
            
        // Light
        int lightValue = analogRead(Al);
        if (lightValue < LIGHT_THRESHOLD) {
            ledState = true;
        }

        // Distance
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

        // Print values
        Serial.print("Temp:");
        Serial.print(temperature);
        Serial.print(" C | Light:");
        Serial.print(lightValue);
        Serial.print(" | Distance: ");
        Serial.print(distance);
        Serial.print(" cm | LED: ");
        Serial.println(ledState ? "ON (Auto)": "OFF (Auto)");
        }
    delay(1000);
}
