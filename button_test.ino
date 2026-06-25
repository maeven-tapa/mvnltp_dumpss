#include <Adafruit_NeoPixel.h>

#define LED_PIN 48      // built-in RGB LED. Try 21 if 48 doesn't work
#define NUM_LEDS 1

#define BTN1 35
#define BTN2 36
#define BTN3 37

Adafruit_NeoPixel led(NUM_LEDS, LED_PIN, NEO_GRB + NEO_KHZ800);

int colorIndex = 0;

uint32_t colors[] = {
  led.Color(255, 0, 0),     // Red
  led.Color(0, 255, 0),     // Green
  led.Color(0, 0, 255),     // Blue
  led.Color(255, 255, 0),   // Yellow
  led.Color(128, 0, 255),   // Violet
  led.Color(0, 255, 255),   // Cyan
  led.Color(255, 255, 255)  // White
};

const int totalColors = sizeof(colors) / sizeof(colors[0]);

bool lastButtonState = LOW;
unsigned long lastDebounceTime = 0;
const unsigned long debounceDelay = 200;

void setup() {
  led.begin();
  led.setBrightness(50);
  led.setPixelColor(0, colors[colorIndex]);
  led.show();

  pinMode(BTN1, INPUT);
  pinMode(BTN2, INPUT);
  pinMode(BTN3, INPUT);
}

void loop() {
  bool buttonPressed =
    digitalRead(BTN1) == HIGH ||
    digitalRead(BTN2) == HIGH ||
    digitalRead(BTN3) == HIGH;

  if (buttonPressed && !lastButtonState) {
    if (millis() - lastDebounceTime > debounceDelay) {
      colorIndex++;

      if (colorIndex >= totalColors) {
        colorIndex = 0;
      }

      led.setPixelColor(0, colors[colorIndex]);
      led.show();

      lastDebounceTime = millis();
    }
  }

  lastButtonState = buttonPressed;
}