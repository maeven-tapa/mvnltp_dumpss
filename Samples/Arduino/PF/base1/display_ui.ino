/*
 * Display UI Functions
 */

#include "globals.h"

// Draw disturbed mode UI with blinking background
void drawDisturbedUI() {
  // Solid red background (no blinking)
  tft.fillScreen(ST77XX_RED);
  
  // Draw circular border in white
  int centerX = 160;
  int centerY = 120;
  int radius = 100;
  
  for (int i = 0; i < 3; i++) {
    tft.drawCircle(centerX, centerY, radius - i, ST77XX_WHITE);
  }
  
  // Display "DISTURBED" text in white
  tft.setTextSize(3);
  tft.setTextColor(ST77XX_WHITE);
  
  // Calculate text position (centered)
  const char* text1 = "DEVICE";
  const char* text2 = "DISTURBED";
  int text1Width = strlen(text1) * 6 * 3;
  int text2Width = strlen(text2) * 6 * 3;
  int textHeight = 8 * 3;
  
  tft.setCursor(centerX - text1Width / 2, centerY - textHeight - 5);
  tft.print(text1);
  
  tft.setCursor(centerX - text2Width / 2, centerY + 5);
  tft.print(text2);
}

// Draw minimalist clock UI with circular border
void drawClockUI() {
  tft.fillScreen(ST77XX_WHITE);
  
  // Draw circular border (centered)
  int centerX = 160;
  int centerY = 120;
  int radius = 100;
  
  // Draw thick circle border
  for (int i = 0; i < 3; i++) {
    tft.drawCircle(centerX, centerY, radius - i, ST77XX_BLACK);
  }
  
  // Draw bowl icon if needed
  if (showBowlIcon) {
    drawBowlIcon();
  }
  
  // Draw the time immediately
  updateTime();
}

// Draw minimalist Feed Rounds UI
void drawFeedRoundsUI() {
  tft.fillScreen(ST77XX_WHITE);
  
  // Title at top
  tft.setTextSize(2);
  tft.setTextColor(ST77XX_BLACK);
  tft.setCursor(95, 40);
  tft.print("FEED ROUNDS");
  
  // Draw simple circle border
  int centerX = 160;
  int centerY = 140;
  int radius = 60;
  
  for (int i = 0; i < 2; i++) {
    tft.drawCircle(centerX, centerY, radius - i, ST77XX_BLACK);
  }
  
  // Display number of rounds
  updateFeedRoundsDisplay();
  
  // Instructions at bottom (centered)
  tft.setTextSize(1);
  tft.setTextColor(ST77XX_BLACK);
  tft.setCursor(20, 210);
  tft.print("BTN 1 - SAVE (2x BACK) | BTN 2 (+) | BTN 3 (-)");
}

// Update feed rounds number display
void updateFeedRoundsDisplay() {
  int centerX = 160;
  int centerY = 140;
  
  // Clear center area
  tft.fillCircle(centerX, centerY, 55, ST77XX_WHITE);
  
  // Display rounds number
  tft.setTextSize(6);
  tft.setTextColor(ST77XX_BLACK);
  
  // Center the number
  int numWidth = (totalRounds >= 10) ? 36 : 18;  // Approximate width
  tft.setCursor(centerX - numWidth, centerY - 24);
  tft.print(totalRounds);
}

// Draw mini pet bowl icon at top of clock circle
void drawBowlIcon() {
  int centerX = 160;
  // Circle top is at y=20 (120-100), time top is around y=77 (120-24-10-9)
  // Position icon in the middle: (20 + 77) / 2 = 48.5
  int topY = 48;
  
  // Draw paw print icon (🐾)
  // Main pad (large circle)
  tft.fillCircle(centerX, topY + 8, 4, ST77XX_BLACK);
  
  // Four toe pads (small circles)
  tft.fillCircle(centerX - 6, topY + 2, 2, ST77XX_BLACK);  // Top left
  tft.fillCircle(centerX - 2, topY, 2, ST77XX_BLACK);      // Top center-left
  tft.fillCircle(centerX + 2, topY, 2, ST77XX_BLACK);      // Top center-right
  tft.fillCircle(centerX + 6, topY + 2, 2, ST77XX_BLACK);  // Top right
}

// Update and display current time
void updateTime() {
  RtcDateTime now = Rtc.GetDateTime();
  
  if (!now.IsValid()) {
    return;
  }
  
  int hour = now.Hour();
  int minute = now.Minute();
  int second = now.Second();
  
  // Determine AM/PM
  bool isPM = hour >= 12;
  if (hour > 12) hour -= 12;
  if (hour == 0) hour = 12;
  
  // Format time string
  char timeStr[12];
  sprintf(timeStr, "%02d:%02d:%02d", hour, minute, second);
  
  // Calculate text positions for centering
  int centerX = 160;
  int centerY = 120;
  
  // Display time with background color to overwrite previous text
  tft.setTextSize(3);
  tft.setTextColor(ST77XX_BLACK, ST77XX_WHITE);
  
  // Calculate time text position (centered)
  // Each character is 6*textSize wide, 8*textSize tall
  int timeWidth = strlen(timeStr) * 6 * 3;  // 8 chars * 6px * size 3
  int timeHeight = 8 * 3;
  tft.setCursor(centerX - timeWidth / 2, centerY - timeHeight / 2 - 10);
  tft.print(timeStr);
  
  // Display AM/PM indicator with background color
  tft.setTextSize(2);
  tft.setTextColor(ST77XX_BLACK, ST77XX_WHITE);
  
  // Calculate AM/PM text position (centered below time)
  int ampmWidth = 2 * 6 * 2;  // 2 chars * 6px * size 2
  int ampmHeight = 8 * 2;
  tft.setCursor(centerX - ampmWidth / 2, centerY + timeHeight / 2 + 5);
  tft.println(isPM ? "PM" : "AM");
}

// Draw Scaling Mode UI
void drawScalingUI() {
  tft.fillScreen(ST77XX_WHITE);
  
  // Title at top (centered)
  tft.setTextSize(2);
  tft.setTextColor(ST77XX_BLACK);
  const char* titleText = "SCALING MODE";
  int titleWidth = strlen(titleText) * 6 * 2;
  tft.setCursor(160 - titleWidth / 2, 20);
  tft.print(titleText);
  
  // Draw circle border
  int centerX = 160;
  int centerY = 110;
  int radius = 60;
  
  for (int i = 0; i < 2; i++) {
    tft.drawCircle(centerX, centerY, radius - i, ST77XX_BLACK);
  }
  
  // Draw weight display immediately
  updateWeightDisplay();
  
  // Instructions at bottom (centered)
  tft.setTextSize(1);
  tft.setTextColor(ST77XX_BLACK);
  const char* instructionText = "BTN1 - EXIT (2x) | BTN3 - TARE (HOLD)";
  int instructionWidth = strlen(instructionText) * 6 * 1;
  tft.setCursor(160 - instructionWidth / 2, 210);
  tft.print(instructionText);
}

// Update weight display
void updateWeightDisplay() {
  int centerX = 160;
  int centerY = 110;
  int radius = 60;
  
  // Only redraw if weight changed significantly
  static float lastDisplayedWeight = -1;
  static float lastDisplayedPercentage = -1;
  
  float fillPercentage = (currentWeight / maxCapacity) * 100.0;
  if (fillPercentage > 100) fillPercentage = 100;
  
  // Check if we need to update (weight changed by more than 0.1g or percentage changed)
  if (abs(currentWeight - lastDisplayedWeight) < 0.1 && abs(fillPercentage - lastDisplayedPercentage) < 1.0) {
    return; // No significant change, skip update
  }
  
  // Clear weight display area (fixed size to prevent text artifacts)
  tft.fillRect(centerX - 45, centerY - 20, 90, 20, ST77XX_WHITE);
  
  // Display weight value
  tft.setTextSize(2);
  tft.setTextColor(ST77XX_BLACK);
  
  char weightStr[16];
  sprintf(weightStr, "%.1f", currentWeight);
  
  // Calculate actual width based on string length
  int textWidth = strlen(weightStr) * 6 * 2;  // chars * 6px per char * text size 2
  tft.setCursor(centerX - textWidth / 2, centerY - 16);
  tft.print(weightStr);
  
  // Clear "grams" area
  tft.fillRect(centerX - 30, centerY + 4, 60, 10, ST77XX_WHITE);
  
  // Display "grams" below the number
  tft.setTextSize(1);
  tft.setTextColor(ST77XX_BLACK);
  
  const char* gramsText = "grams";
  int gramsWidth = strlen(gramsText) * 6 * 1;
  tft.setCursor(centerX - gramsWidth / 2, centerY + 4);
  tft.print(gramsText);
  
  // Clear percentage area
  tft.fillRect(centerX - 40, centerY + radius + 10, 80, 20, ST77XX_WHITE);
  
  // Display percentage below circle
  tft.setTextSize(2);
  tft.setTextColor(ST77XX_BLACK);
  char percentStr[16];
  sprintf(percentStr, "%.0f%%", fillPercentage);
  
  // Calculate actual width based on string length
  int percentWidth = strlen(percentStr) * 6 * 2;  // chars * 6px per char * text size 2
  tft.setCursor(centerX - percentWidth / 2, centerY + radius + 10);
  tft.print(percentStr);
  
  lastDisplayedWeight = currentWeight;
  lastDisplayedPercentage = fillPercentage;
}
