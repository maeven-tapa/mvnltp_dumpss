/*
 Sample Line Following Code for the Robojunkies LF-2 robot
*/

#define AIN1 36
#define BIN1 38
#define AIN2 35
#define BIN2 39
#define PWMA 21
#define PWMB 40
#define STBY 37

// these constants are used to allow you to make your motor configuration
// line up with function names like forward.  Value can be 1 or -1
const int offsetA = 1;
const int offsetB = 1;

int P, D, I, previousError, PIDvalue, error;
int lsp, rsp;
int lfspeed = 200;

float Kp = 0;
float Kd = 0;
float Ki = 0 ;


int minValues[6], maxValues[6], threshold[6];
int sensorPins[6] = {0, 15, 7, 6, 5, 4}; // Index 1-5 for sensors

void setup()
{
  Serial.begin(9600);
  pinMode(20, INPUT_PULLDOWN); // BUTTONA
  pinMode(1, INPUT_PULLDOWN);  // BUTTONB
  
  // Motor pins
  pinMode(AIN1, OUTPUT);
  pinMode(AIN2, OUTPUT);
  pinMode(PWMA, OUTPUT);
  pinMode(BIN1, OUTPUT);
  pinMode(BIN2, OUTPUT);
  pinMode(PWMB, OUTPUT);
  pinMode(STBY, OUTPUT);
  digitalWrite(STBY, HIGH); // Enable motor driver
}


void loop()
{
  while (!digitalRead(20)) {} // Wait for BUTTONA press (HIGH)
  delay(1000);
  calibrate();
  while (!digitalRead(1)) {} // Wait for BUTTONB press (HIGH)
  delay(1000);

  while (1)
  {
    if (analogRead(sensorPins[1]) > threshold[1] && analogRead(sensorPins[5]) < threshold[5] )
    {
      lsp = 0; rsp = lfspeed;
      driveMotor1(0);
      driveMotor2(lfspeed);
    }

    else if (analogRead(sensorPins[5]) > threshold[5] && analogRead(sensorPins[1]) < threshold[1])
    { lsp = lfspeed; rsp = 0;
      driveMotor1(lfspeed);
      driveMotor2(0);
    }
    else if (analogRead(sensorPins[3]) > threshold[3])
    {
      Kp = 0.0006 * (1000 - analogRead(sensorPins[3]));
      Kd = 10 * Kp;
      //Ki = 0.0001;
      linefollow();
    }
  }
}

void linefollow()
{
  int error = (analogRead(sensorPins[2]) - analogRead(sensorPins[4]));

  P = error;
  I = I + error;
  D = error - previousError;

  PIDvalue = (Kp * P) + (Ki * I) + (Kd * D);
  previousError = error;

  lsp = lfspeed - PIDvalue;
  rsp = lfspeed + PIDvalue;

  if (lsp > 255) {
    lsp = 255;
  }
  if (lsp < 0) {
    lsp = 0;
  }
  if (rsp > 255) {
    rsp = 255;
  }
  if (rsp < 0) {
    rsp = 0;
  }
  driveMotor1(lsp);
  driveMotor2(rsp);
}

void calibrate()
{
  for ( int i = 1; i < 6; i++)
  {
    minValues[i] = analogRead(sensorPins[i]);
    maxValues[i] = analogRead(sensorPins[i]);
  }
  
  for (int i = 0; i < 3000; i++)
  {
    driveMotor1(50);
    driveMotor2(-50);

    for ( int i = 1; i < 6; i++)
    {
      if (analogRead(sensorPins[i]) < minValues[i])
      {
        minValues[i] = analogRead(sensorPins[i]);
      }
      if (analogRead(sensorPins[i]) > maxValues[i])
      {
        maxValues[i] = analogRead(sensorPins[i]);
      }
    }
  }

  for ( int i = 1; i < 6; i++)
  {
    threshold[i] = (minValues[i] + maxValues[i]) / 2;
    Serial.print(threshold[i]);
    Serial.print("   ");
  }
  Serial.println();
  
  driveMotor1(0);
  driveMotor2(0);
}

// New motor drive functions
void driveMotor1(int speed) {
  if (speed > 0) {
    digitalWrite(AIN1, HIGH);
    digitalWrite(AIN2, LOW);
    analogWrite(PWMA, speed);
  } else if (speed < 0) {
    digitalWrite(AIN1, LOW);
    digitalWrite(AIN2, HIGH);
    analogWrite(PWMA, -speed);
  } else {
    digitalWrite(AIN1, LOW);
    digitalWrite(AIN2, LOW);
    analogWrite(PWMA, 0);
  }
}

void driveMotor2(int speed) {
  if (speed > 0) {
    digitalWrite(BIN1, HIGH);
    digitalWrite(BIN2, LOW);
    analogWrite(PWMB, speed);
  } else if (speed < 0) {
    digitalWrite(BIN1, LOW);
    digitalWrite(BIN2, HIGH);
    analogWrite(PWMB, -speed);
  } else {
    digitalWrite(BIN1, LOW);
    digitalWrite(BIN2, LOW);
    analogWrite(PWMB, 0);
  }
}