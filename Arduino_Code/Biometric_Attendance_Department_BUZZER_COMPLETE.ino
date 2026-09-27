#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <SoftwareSerial.h>
#include <Adafruit_Fingerprint.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

// ============================================================
// WIFI SETTINGS
// ============================================================

const char* WIFI_SSID = " ";
const char* WIFI_PASSWORD = " ";

// Windows PC running XAMPP
const char* SERVER_IP = " "; //IP P4

// ============================================================
// DEVICE SETTINGS
// ============================================================

const char* DEVICE_ID = "GATE_01";

// ============================================================
// PHP API SETTINGS
// ============================================================

const char* GET_REQUEST_API =
  "http://10.54.241.24/AMSproject/biometric_get_request.php";

const char* COMPLETE_ENROLLMENT_API =
  "http://10.54.241.24/AMSproject/biometric_complete_enrollment.php";

// ============================================================
// AS608 CONNECTION
// ============================================================
//
// NodeMCU:
//
// AS608 TX -> D5
// AS608 RX -> D6
// AS608 VCC -> 5V
// AS608 GND -> GND
//
// SoftwareSerial:
// ESP8266 D5 = RX
// ESP8266 D6 = TX
//

#define FINGER_RX D5
#define FINGER_TX D6

SoftwareSerial fingerSerial(FINGER_RX, FINGER_TX);

Adafruit_Fingerprint finger =
  Adafruit_Fingerprint(&fingerSerial);

// ============================================================
// OLED SETTINGS
// ============================================================

#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

#define OLED_RESET -1

Adafruit_SSD1306 display(
  SCREEN_WIDTH,
  SCREEN_HEIGHT,
  &Wire,
  OLED_RESET);

// OLED:
// SDA -> D2
// SCL -> D1
// VCC -> 3.3V
// GND -> GND
//

// ============================================================
// GLOBAL VARIABLES
// ============================================================

bool enrollmentInProgress = false;

int currentRequestID = 0;

String currentStudentID = "";
String currentStudentName = "";
String currentBranch = "";
String currentSemester = "";

uint16_t currentFingerprintID = 0;

// ============================================================
// MODE SWITCH SETTINGS
// ============================================================
// 4-pin push button:
//   One side -> D7
//   Other side -> GND
//
// D7 is used because D5/D6 are already used by AS608.
//
// DEFAULT MODE: ATTENDANCE
// One press: ATTENDANCE <-> REGISTRATION
// ============================================================

#define MODE_BUTTON D7

// ============================================================
// 3-PIN BUZZER MODULE
// ============================================================
// Buzzer module pins:
//   GND -> ESP8266 GND
//   VCC -> 3V3 (ONLY if your module supports 3.3V)
//   I/O -> D0
//
// Most 3-pin active buzzer modules are HIGH-triggered.
// If your module works in reverse, change BUZZER_ACTIVE_HIGH to false.

#define BUZZER_PIN D0
const bool BUZZER_ACTIVE_HIGH = true;

void buzzerSet(bool on) {
  digitalWrite(
    BUZZER_PIN,
    on == BUZZER_ACTIVE_HIGH ? HIGH : LOW
  );
}

void buzzerOff() {
  buzzerSet(false);
}

void buzzerBeep(unsigned int durationMs) {
  buzzerSet(true);
  delay(durationMs);
  buzzerOff();
}

void buzzerSuccess() {
  // Two short beeps = attendance/registration success
  buzzerBeep(120);
  delay(100);
  buzzerBeep(120);
}

void buzzerAlreadyMarked() {
  // Two very quick beeps = already marked
  buzzerBeep(80);
  delay(80);
  buzzerBeep(80);
}

void buzzerNotRegistered() {
  // One long beep = finger not registered
  buzzerBeep(500);
}

void buzzerDepartmentDenied() {
  // Three short beeps = department/permission denied
  buzzerBeep(100);
  delay(100);
  buzzerBeep(100);
  delay(100);
  buzzerBeep(100);
}

void buzzerError() {
  // One long beep = error
  buzzerBeep(350);
}


bool registrationMode = false;  // false = Attendance, true = Registration

bool modeButtonLastReading = HIGH;
bool modeButtonStableState = HIGH;
unsigned long modeButtonLastDebounceTime = 0;
const unsigned long MODE_BUTTON_DEBOUNCE_MS = 50;

unsigned long lastEnrollmentRequestCheck = 0;
const unsigned long ENROLLMENT_REQUEST_INTERVAL = 2000;


// ============================================================
// BIOMETRIC ATTENDANCE CONTROL GLOBALS
// These must be declared before showAttendanceMode(), setup(),
// and loop() because those functions use them.
// ============================================================

const char* ATTENDANCE_API =
  "http://10.54.241.24/AMSproject/biometric_mark_attendance.php";

const char* ATTENDANCE_CONTROL_API =
  "http://10.54.241.24/AMSproject/biometric_attendance_control.php";

bool biometricAttendanceEnabled = false;

unsigned long lastAttendanceControlCheck = 0;
const unsigned long ATTENDANCE_CONTROL_INTERVAL = 1000;

unsigned long lastAttendanceScan = 0;
const unsigned long ATTENDANCE_SCAN_INTERVAL = 1500;

unsigned long lastAttendanceSuccess = 0;
const unsigned long ATTENDANCE_SUCCESS_DELAY = 5000;

bool attendanceBusy = false;
String attendanceLastResponse = "";

// Forward declarations for functions used before their definitions.
bool updateBiometricAttendancePermission();
void checkAttendance();
bool sendAttendanceToServer(int fingerprintID);
String getJsonStringAttendance(const String &json, const String &key);
String getNestedStudentNameAttendance(const String &json);




// ============================================================
// OLED FUNCTIONS
// ============================================================

void oledClear() {
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.setTextSize(1);
  display.setCursor(0, 0);
}

void oledMessage(String line1, String line2 = "", String line3 = "") {
  oledClear();

  display.setTextSize(1);

  display.setCursor(0, 5);
  display.println(line1);

  if (line2.length() > 0) {
    display.setCursor(0, 25);
    display.println(line2);
  }

  if (line3.length() > 0) {
    display.setCursor(0, 45);
    display.println(line3);
  }

  display.display();
}


void oledSuccess() {
  display.clearDisplay();

  display.setTextColor(SSD1306_WHITE);

  display.setTextSize(1);
  display.setCursor(18, 0);
  display.println("REGISTRATION");

  display.setTextSize(2);
  display.setCursor(28, 14);
  display.println("SUCCESS");

  display.setTextSize(1);
  display.setCursor(0, 38);
  display.print("Name: ");

  // Display maximum useful characters
  String name = currentStudentName;

  if (name.length() > 18) {
    name = name.substring(0, 18);
  }

  display.println(name);

  display.setCursor(0, 52);
  display.print("Finger ID: ");
  display.println(currentFingerprintID);

  display.display();
}


void oledWaitingFinger() {
  display.clearDisplay();

  display.setTextColor(SSD1306_WHITE);

  display.setTextSize(1);
  display.setCursor(20, 0);
  display.println("BIOMETRIC");

  display.setTextSize(2);
  display.setCursor(25, 15);
  display.println("ENROLL");

  display.setTextSize(1);

  display.setCursor(0, 40);
  display.println("Place finger");

  display.setCursor(0, 52);
  display.println("on sensor...");

  display.display();
}



// ============================================================
// MODE DISPLAY
// ============================================================

void showAttendanceMode() {
  if (biometricAttendanceEnabled) {
    oledMessage(
      "BIOMETRIC SYSTEM",
      "ATTENDANCE ON",
      "Place finger..."
    );
  } else {
    // Sensor still scans so it can identify the student and
    // display DEPARTMENT NOT ALLOWED when permission is off.
    oledMessage(
      "BIOMETRIC SYSTEM",
      "ATTENDANCE READY",
      "Permission OFF"
    );
  }
}

void showRegistrationMode() {
  oledMessage(
    "BIOMETRIC SYSTEM",
    "REGISTRATION MODE",
    "Waiting for request"
  );
}

// ============================================================
// MODE BUTTON
// ============================================================

void handleModeButton() {
  bool reading = digitalRead(MODE_BUTTON);

  if (reading != modeButtonLastReading) {
    modeButtonLastDebounceTime = millis();
    modeButtonLastReading = reading;
  }

  if ((millis() - modeButtonLastDebounceTime) >= MODE_BUTTON_DEBOUNCE_MS) {
    if (reading != modeButtonStableState) {
      modeButtonStableState = reading;

      // Button uses INPUT_PULLUP, so LOW means pressed.
      if (modeButtonStableState == LOW) {

        // Never switch mode in the middle of an enrollment.
        if (enrollmentInProgress) {
          Serial.println("Mode button ignored: enrollment in progress.");
          return;
        }

        registrationMode = !registrationMode;

        Serial.println();
        Serial.println("========================================");
        Serial.print("MODE CHANGED TO: ");
        Serial.println(registrationMode ? "REGISTRATION" : "ATTENDANCE");
        Serial.println("========================================");

        buzzerBeep(80);

        if (registrationMode) {
          lastEnrollmentRequestCheck = 0;
          showRegistrationMode();
        } else {
          updateBiometricAttendancePermission();
          showAttendanceMode();
        }
      }
    }
  }
}

// ============================================================
// WIFI
// ============================================================

void connectWiFi() {
  Serial.println();
  Serial.println("================================");
  Serial.println("Connecting to Wi-Fi...");
  Serial.println("================================");

  WiFi.mode(WIFI_STA);

  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int retry = 0;

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);

    Serial.print(".");

    retry++;

    if (retry > 40) {
      Serial.println();
      Serial.println("Wi-Fi connection failed.");
      Serial.println("Restarting...");

      ESP.restart();
    }
  }

  Serial.println();
  Serial.println("Wi-Fi connected!");

  Serial.print("ESP8266 IP: ");
  Serial.println(WiFi.localIP());

  Serial.print("Gateway: ");
  Serial.println(WiFi.gatewayIP());

  Serial.print("Server IP: ");
  Serial.println(SERVER_IP);

  Serial.println();
}


// ============================================================
// FIND NEXT AVAILABLE FINGERPRINT ID
// ============================================================

int findNextFingerprintID() {
  Serial.println();
  Serial.println("Searching for available fingerprint ID...");

  for (int id = 1; id <= 127; id++) {
    uint8_t p = finger.loadModel(id);

    if (p != FINGERPRINT_OK) {
      Serial.print("Available fingerprint ID: ");
      Serial.println(id);

      return id;
    }

    delay(20);
  }

  Serial.println("Fingerprint database is full.");

  return -1;
}


// ============================================================
// GET ENROLLMENT REQUEST
// ============================================================

bool getEnrollmentRequest() {
  if (WiFi.status() != WL_CONNECTED) {
    return false;
  }

  WiFiClient client;
  HTTPClient http;

  String url =
    String(GET_REQUEST_API) + "?device_id=" + String(DEVICE_ID) + "&t=" + String(millis());

  Serial.println();
  Serial.println("================================");
  Serial.println("Checking biometric request...");
  Serial.println("URL:");
  Serial.println(url);

  if (!http.begin(client, url)) {
    Serial.println("HTTP begin failed.");

    return false;
  }

  http.setTimeout(5000);

  int httpCode = http.GET();

  Serial.print("HTTP Code: ");
  Serial.println(httpCode);

  if (httpCode != HTTP_CODE_OK) {
    Serial.print("HTTP error: ");
    Serial.println(http.errorToString(httpCode));

    http.end();

    return false;
  }

  String response = http.getString();

  http.end();

  Serial.println("--------------------------------");
  Serial.println("SERVER RESPONSE:");
  Serial.println(response);
  Serial.println("--------------------------------");

  // ----------------------------------------------------------
  // Check whether request exists
  // ----------------------------------------------------------

  if (response.indexOf("\"request_available\":true") == -1) {
    Serial.println("No pending enrollment request.");

    return false;
  }

  // ----------------------------------------------------------
  // Extract request_id
  // ----------------------------------------------------------

  int pos = response.indexOf("\"request_id\":");

  if (pos == -1) {
    Serial.println("request_id not found.");

    return false;
  }

  pos += 13;

  int endPos = response.indexOf(",", pos);

  if (endPos == -1) {
    endPos = response.indexOf("}", pos);
  }

  String requestIDString =
    response.substring(pos, endPos);

  currentRequestID =
    requestIDString.toInt();

  // ----------------------------------------------------------
  // Extract student_id
  // ----------------------------------------------------------

  pos = response.indexOf("\"student_id\":\"");

  if (pos != -1) {
    pos += 14;

    endPos = response.indexOf("\"", pos);

    currentStudentID =
      response.substring(pos, endPos);
  }

  // ----------------------------------------------------------
  // Extract student_name
  // ----------------------------------------------------------

  pos = response.indexOf("\"student_name\":\"");

  if (pos != -1) {
    pos += 16;

    endPos = response.indexOf("\"", pos);

    currentStudentName =
      response.substring(pos, endPos);
  }

  // ----------------------------------------------------------
  // Extract branch_name
  // ----------------------------------------------------------

  pos = response.indexOf("\"branch_name\":\"");

  if (pos != -1) {
    pos += 15;

    endPos = response.indexOf("\"", pos);

    currentBranch =
      response.substring(pos, endPos);
  }

  // ----------------------------------------------------------
  // Extract semester
  // ----------------------------------------------------------

  pos = response.indexOf("\"semester\":\"");

  if (pos != -1) {
    pos += 12;

    endPos = response.indexOf("\"", pos);

    currentSemester =
      response.substring(pos, endPos);
  }

  // ----------------------------------------------------------
  // Print request information
  // ----------------------------------------------------------

  Serial.println();
  Serial.println("********************************");
  Serial.println("ENROLLMENT REQUEST FOUND!");
  Serial.println("********************************");

  Serial.print("Request ID: ");
  Serial.println(currentRequestID);

  Serial.print("Student ID: ");
  Serial.println(currentStudentID);

  Serial.print("Student Name: ");
  Serial.println(currentStudentName);

  Serial.print("Branch: ");
  Serial.println(currentBranch);

  Serial.print("Semester: ");
  Serial.println(currentSemester);

  Serial.println("********************************");

  return true;
}


// ============================================================
// SEND ENROLLMENT RESULT TO PHP
// ============================================================

// ============================================================
// SEND ENROLLMENT RESULT TO PHP
// ============================================================

bool sendEnrollmentResult(
  int requestID,
  int fingerprintID,
  bool success,
  String message)
{
  if (WiFi.status() != WL_CONNECTED)
  {
    Serial.println("ERROR: Wi-Fi not connected.");
    return false;
  }

  WiFiClient client;
  HTTPClient http;

  Serial.println();
  Serial.println("================================");
  Serial.println("SENDING ENROLLMENT RESULT");
  Serial.println("================================");

  Serial.print("Request ID: ");
  Serial.println(requestID);

  Serial.print("Fingerprint ID: ");
  Serial.println(fingerprintID);

  Serial.print("Student ID: ");
  Serial.println(currentStudentID);

  Serial.print("Student Name: ");
  Serial.println(currentStudentName);

  Serial.print("Success: ");
  Serial.println(success ? "true" : "false");

  Serial.print("Message: ");
  Serial.println(message);

  Serial.print("Completion API: ");
  Serial.println(COMPLETE_ENROLLMENT_API);

  if (!http.begin(client, COMPLETE_ENROLLMENT_API))
  {
    Serial.println("ERROR: Cannot connect to completion API.");
    return false;
  }

  http.setTimeout(8000);

  http.addHeader(
    "Content-Type",
    "application/x-www-form-urlencoded"
  );

  String data =
    "request_id=" + String(requestID) +
    "&fingerprint_id=" + String(fingerprintID) +
    "&device_id=" + String(DEVICE_ID) +
    "&success=" + String(success ? "true" : "false") +
    "&message=" + message;

  Serial.println("--------------------------------");
  Serial.println("POST DATA:");
  Serial.println(data);
  Serial.println("--------------------------------");

  int httpCode = http.POST(data);

  Serial.print("HTTP CODE: ");
  Serial.println(httpCode);

  if (httpCode <= 0)
  {
    Serial.print("HTTP ERROR: ");
    Serial.println(http.errorToString(httpCode));

    http.end();
    return false;
  }

  String response = http.getString();

  Serial.println("--------------------------------");
  Serial.println("SERVER RESPONSE:");
  Serial.println(response);
  Serial.println("--------------------------------");

  http.end();

  if (httpCode == HTTP_CODE_OK)
  {
    Serial.println("Enrollment result sent successfully.");
    return true;
  }

  Serial.println("ERROR: Server rejected enrollment result.");
  return false;
}
// ============================================================
// ENROLL FINGERPRINT
// ============================================================

bool enrollFingerprint() {
  currentFingerprintID = findNextFingerprintID();

  if (currentFingerprintID < 1) {
    Serial.println("No fingerprint ID available.");

    oledMessage(
      "ERROR",
      "Fingerprint memory",
      "is full");

    buzzerError();
    return false;
  }

  Serial.println();
  Serial.println("================================");
  Serial.println("STARTING FINGERPRINT ENROLLMENT");
  Serial.println("================================");

  Serial.print("Student: ");
  Serial.println(currentStudentName);

  Serial.print("Student ID: ");
  Serial.println(currentStudentID);

  Serial.print("Fingerprint ID: ");
  Serial.println(currentFingerprintID);

  oledWaitingFinger();

  // ----------------------------------------------------------
  // FIRST FINGER
  // ----------------------------------------------------------

  Serial.println();
  Serial.println("Place finger on sensor...");

  int p = -1;

  while (p != FINGERPRINT_OK) {
    p = finger.getImage();

    if (p == FINGERPRINT_OK) {
      Serial.println("First image captured.");
      break;
    }

    if (p == FINGERPRINT_NOFINGER) {
      delay(100);
      continue;
    }

    Serial.print("Sensor error: ");
    Serial.println(p);

    delay(500);
  }

  // ----------------------------------------------------------
  // CONVERT FIRST IMAGE
  // ----------------------------------------------------------

  p = finger.image2Tz(1);

  if (p != FINGERPRINT_OK) {
    Serial.println("Failed to convert first fingerprint.");

    return false;
  }

  Serial.println("First fingerprint converted.");

  oledMessage(
    "Finger captured",
    "Remove finger");

  delay(2000);

  // ----------------------------------------------------------
  // WAIT FOR FINGER REMOVAL
  // ----------------------------------------------------------

  Serial.println("Remove finger...");

  while (finger.getImage() != FINGERPRINT_NOFINGER) {
    delay(100);
  }

  delay(1000);

  // ----------------------------------------------------------
  // SECOND FINGER
  // ----------------------------------------------------------

  oledMessage(
    "Place same finger",
    "again...");

  Serial.println();
  Serial.println("Place same finger again...");

  p = -1;

  while (p != FINGERPRINT_OK) {
    p = finger.getImage();

    if (p == FINGERPRINT_OK) {
      Serial.println("Second image captured.");
      break;
    }

    if (p == FINGERPRINT_NOFINGER) {
      delay(100);
      continue;
    }

    Serial.print("Sensor error: ");
    Serial.println(p);

    delay(500);
  }

  // ----------------------------------------------------------
  // CONVERT SECOND IMAGE
  // ----------------------------------------------------------

  p = finger.image2Tz(2);

  if (p != FINGERPRINT_OK) {
    Serial.println("Failed to convert second fingerprint.");

    return false;
  }

  Serial.println("Second fingerprint converted.");

  // ----------------------------------------------------------
  // CREATE MODEL
  // ----------------------------------------------------------

  Serial.println("Creating fingerprint model...");

  p = finger.createModel();

  if (p != FINGERPRINT_OK) {
    Serial.println("Fingerprints did not match.");

    oledMessage(
      "ENROLLMENT FAILED",
      "Fingerprints",
      "do not match");

    return false;
  }

  Serial.println("Fingerprint model created.");

  // ----------------------------------------------------------
  // STORE MODEL
  // ----------------------------------------------------------

  Serial.print("Storing fingerprint at ID ");
  Serial.println(currentFingerprintID);

  p = finger.storeModel(currentFingerprintID);

  if (p != FINGERPRINT_OK) {
    Serial.println("Failed to store fingerprint.");

    oledMessage(
      "ENROLLMENT FAILED",
      "Could not store",
      "fingerprint");

    return false;
  }

  Serial.println("Fingerprint stored successfully.");

  return true;
}


// ============================================================
// PROCESS ENROLLMENT
// ============================================================

// ============================================================
// PROCESS ENROLLMENT
// ============================================================

void processEnrollment()
{
  enrollmentInProgress = true;

  Serial.println();
  Serial.println("################################");
  Serial.println(" PROCESSING BIOMETRIC REQUEST");
  Serial.println("################################");

  Serial.print("Request ID: ");
  Serial.println(currentRequestID);

  Serial.print("Student ID: ");
  Serial.println(currentStudentID);

  Serial.print("Student Name: ");
  Serial.println(currentStudentName);

  oledMessage(
    "Enrollment Request",
    "Found!",
    currentStudentName
  );

  delay(2000);

  bool success = enrollFingerprint();

  if (success)
  {
    Serial.println();
    Serial.println("################################");
    Serial.println(" REGISTRATION SUCCESSFUL");
    Serial.println("################################");

    Serial.print("Student Name: ");
    Serial.println(currentStudentName);

    Serial.print("Student ID: ");
    Serial.println(currentStudentID);

    Serial.print("Fingerprint ID: ");
    Serial.println(currentFingerprintID);

    bool sent = sendEnrollmentResult(
      currentRequestID,
      currentFingerprintID,
      true,
      "Fingerprint registration successful"
    );

    if (sent)
    {
      Serial.println("--------------------------------");
      Serial.println("Enrollment result sent to server.");
      Serial.println("Database should now be updated.");
      Serial.println("--------------------------------");
    }
    else
    {
      Serial.println("--------------------------------");
      Serial.println("WARNING: Could not send result to server.");
      Serial.println("--------------------------------");
    }

    buzzerSuccess();
    oledSuccess();

    delay(7000);
  }
  else
  {
    Serial.println();
    Serial.println("################################");
    Serial.println(" REGISTRATION FAILED");
    Serial.println("################################");

    bool sent = sendEnrollmentResult(
      currentRequestID,
      0,
      false,
      "Fingerprint registration failed"
    );

    if (sent)
    {
      Serial.println("Failure result sent to server.");
    }
    else
    {
      Serial.println("WARNING: Could not send failure result.");
    }

    buzzerError();

    oledMessage(
      "REGISTRATION FAILED",
      "Please try again"
    );

    delay(5000);
  }

  enrollmentInProgress = false;

  currentRequestID = 0;
  currentStudentID = "";
  currentStudentName = "";
  currentBranch = "";
  currentSemester = "";
  currentFingerprintID = 0;
}


// Forward declaration for attendance mode function.
void checkAttendance();

// ============================================================
// SETUP
// ============================================================

void setup() {
  Serial.begin(115200);

  // 3-pin buzzer module: I/O -> D0
  pinMode(BUZZER_PIN, OUTPUT);
  buzzerOff();

  // Mode button: D7 -> button -> GND
  pinMode(MODE_BUTTON, INPUT_PULLUP);
  modeButtonLastReading = digitalRead(MODE_BUTTON);
  modeButtonStableState = modeButtonLastReading;

  delay(1000);

  Serial.println();
  Serial.println("==========================================");
  Serial.println("     BIOMETRIC ATTENDANCE SYSTEM");
  Serial.println("==========================================");

  // ----------------------------------------------------------
  // OLED
  // ----------------------------------------------------------

  Wire.begin(D2, D1);

  if (!display.begin(
        SSD1306_SWITCHCAPVCC,
        0x3C)) {
    Serial.println("OLED initialization failed!");
  } else {
    Serial.println("OLED initialized.");

    oledMessage(
      "BIOMETRIC SYSTEM",
      "Starting...");
  }

  // ----------------------------------------------------------
  // Fingerprint sensor
  // ----------------------------------------------------------

  fingerSerial.begin(57600);

  finger.begin(57600);

  delay(1000);

  Serial.println();
  Serial.println("Checking AS608 fingerprint sensor...");

  if (finger.verifyPassword()) {
    Serial.println("Fingerprint sensor detected!");

    oledMessage(
      "Fingerprint Sensor",
      "Connected");

    delay(1500);
  } else {
    Serial.println("Fingerprint sensor NOT detected!");

    oledMessage(
      "ERROR",
      "Fingerprint sensor",
      "not detected");

    delay(3000);
  }

  // ----------------------------------------------------------
  // Wi-Fi
  // ----------------------------------------------------------

  connectWiFi();

  // Attendance is always the default physical mode after power-up/reset.
  registrationMode = false;

  // Read the teacher dashboard permission before showing attendance state.
  biometricAttendanceEnabled = false;
  updateBiometricAttendancePermission();

  showAttendanceMode();

  Serial.println();
  Serial.println("==========================================");
  Serial.println("SYSTEM READY");
  Serial.println("DEFAULT MODE: ATTENDANCE");
  Serial.println("Press D7 button to switch mode.");
  Serial.println("==========================================");
}


// ============================================================
// LOOP
// ============================================================

void loop() {

  // ----------------------------------------------------------
  // Handle physical mode button
  // ----------------------------------------------------------
  handleModeButton();

  // ----------------------------------------------------------
  // Check Wi-Fi
  // ----------------------------------------------------------
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Wi-Fi disconnected.");

    oledMessage(
      "Wi-Fi disconnected",
      "Reconnecting..."
    );

    connectWiFi();

    if (registrationMode) {
      showRegistrationMode();
    } else {
      showAttendanceMode();
    }

    return;
  }

  // ----------------------------------------------------------
  // Read teacher dashboard biometric ON/OFF state.
  // ----------------------------------------------------------
  unsigned long controlNow = millis();

  if (controlNow - lastAttendanceControlCheck >=
      ATTENDANCE_CONTROL_INTERVAL) {

    lastAttendanceControlCheck = controlNow;
    updateBiometricAttendancePermission();
  }

  // ----------------------------------------------------------
  // Do not run another task while enrollment is active.
  // ----------------------------------------------------------
  if (enrollmentInProgress) {
    delay(20);
    return;
  }

  // ----------------------------------------------------------
  // REGISTRATION MODE
  // ----------------------------------------------------------
  if (registrationMode) {

    unsigned long now = millis();

    if (now - lastEnrollmentRequestCheck >=
        ENROLLMENT_REQUEST_INTERVAL) {

      lastEnrollmentRequestCheck = now;

      bool requestFound = getEnrollmentRequest();

      if (requestFound) {
        processEnrollment();

        // Return to the selected mode after enrollment.
        if (registrationMode) {
          showRegistrationMode();
        } else {
          updateBiometricAttendancePermission();
          showAttendanceMode();
        }
      }
    }

    delay(10);
    return;
  }

  // ----------------------------------------------------------
  // ATTENDANCE MODE
  // ----------------------------------------------------------
  checkAttendance();

  delay(10);
}


// ================================================================
// BIOMETRIC ATTENDANCE MODULE
// ================================================================

// IMPORTANT:
// The D7 push button controls PHYSICAL MODE only:
//   false = Attendance Mode
//   true  = Registration Mode
//
// The Teacher Dashboard controls PERMISSION only.
// Therefore, even when teacher permission is OFF, the ESP8266
// still scans the finger in Attendance Mode. The server identifies
// the student and department and tells the ESP8266 whether attendance
// is allowed for the active teacher's department.

// ------------------------------------------------
// Simple JSON string reader
// ------------------------------------------------
String getJsonStringAttendance(const String &json, const String &key) {

  String searchKey = "\"" + key + "\"";
  int keyPos = json.indexOf(searchKey);

  if (keyPos < 0) return "";

  int colon = json.indexOf(':', keyPos + searchKey.length());

  if (colon < 0) return "";

  int firstQuote = json.indexOf('"', colon + 1);

  if (firstQuote < 0) return "";

  int secondQuote = json.indexOf('"', firstQuote + 1);

  if (secondQuote < 0) return "";

  return json.substring(firstQuote + 1, secondQuote);
}


// ------------------------------------------------
// Read teacher dashboard ON/OFF state
// ------------------------------------------------
bool updateBiometricAttendancePermission() {

  if (WiFi.status() != WL_CONNECTED) {
    return false;
  }

  WiFiClient client;
  HTTPClient http;

  String url =
    String(ATTENDANCE_CONTROL_API) +
    "?device_id=" + String(DEVICE_ID) +
    "&_=" + String(millis());

  if (!http.begin(client, url)) {

    Serial.println(
      "Attendance control API connection failed."
    );

    return false;
  }

  http.setTimeout(3000);

  int httpCode = http.GET();

  if (httpCode <= 0) {

    Serial.print(
      "Attendance control HTTP error: "
    );

    Serial.println(http.errorToString(httpCode));

    http.end();

    return false;
  }

  String response = http.getString();

  http.end();

  Serial.println();
  Serial.println("ATTENDANCE CONTROL RESPONSE:");
  Serial.println(response);

  bool enabled =
    response.indexOf("\"enabled\":true") >= 0 ||
    response.indexOf("\"enabled\": true") >= 0;

  if (enabled != biometricAttendanceEnabled) {

    biometricAttendanceEnabled = enabled;

    Serial.print(
      "Teacher biometric permission: "
    );

    Serial.println(
      enabled ? "ON" : "OFF"
    );

    if (!registrationMode && !enrollmentInProgress) {

      if (enabled) {

        oledMessage(
          "ATTENDANCE READY",
          "Teacher: ON",
          "Place finger"
        );

      } else {

        oledMessage(
          "ATTENDANCE READY",
          "Teacher: OFF",
          "Place finger"
        );
      }
    }
  }

  return true;
}


// ------------------------------------------------
// Send fingerprint ID to attendance PHP
// ------------------------------------------------
// PHP does ALL of the following:
//   1. Find student by fingerprint ID
//   2. Find student's department
//   3. Find active teacher's department
//   4. Compare departments
//   5. Insert attendance only when allowed
// ------------------------------------------------
bool sendAttendanceToServer(int fingerprintID) {

  if (WiFi.status() != WL_CONNECTED) {

    Serial.println(
      "Attendance: Wi-Fi disconnected."
    );

    attendanceLastResponse = "";

    return false;
  }

  WiFiClient client;
  HTTPClient http;

  String url =
    String(ATTENDANCE_API) +
    "?device_id=" + String(DEVICE_ID) +
    "&fingerprint_id=" + String(fingerprintID) +
    "&_=" + String(millis());

  Serial.println();
  Serial.println("========================================");
  Serial.println("SENDING FINGERPRINT TO ATTENDANCE API");
  Serial.print("Fingerprint ID: ");
  Serial.println(fingerprintID);
  Serial.println(url);
  Serial.println("========================================");

  if (!http.begin(client, url)) {

    Serial.println(
      "Attendance API connection failed."
    );

    attendanceLastResponse = "";

    return false;
  }

  http.setTimeout(8000);

  int httpCode = http.GET();

  Serial.print("Attendance HTTP Code: ");
  Serial.println(httpCode);

  if (httpCode <= 0) {

    Serial.print(
      "Attendance HTTP error: "
    );

    Serial.println(
      http.errorToString(httpCode)
    );

    http.end();

    attendanceLastResponse = "";

    return false;
  }

  attendanceLastResponse = http.getString();

  http.end();

  Serial.println();
  Serial.println("ATTENDANCE SERVER RESPONSE:");
  Serial.println("----------------------------------------");
  Serial.println(attendanceLastResponse);
  Serial.println("----------------------------------------");

  return true;
}


// ------------------------------------------------
// Extract nested student name
// ------------------------------------------------
String getNestedStudentNameAttendance(
  const String &json
) {

  int studentPos =
    json.indexOf("\"student\"");

  if (studentPos < 0) {
    return "";
  }

  int namePos =
    json.indexOf(
      "\"student_name\"",
      studentPos
    );

  if (namePos < 0) {
    return "";
  }

  int colon =
    json.indexOf(':', namePos);

  if (colon < 0) {
    return "";
  }

  int firstQuote =
    json.indexOf('"', colon + 1);

  if (firstQuote < 0) {
    return "";
  }

  int secondQuote =
    json.indexOf('"', firstQuote + 1);

  if (secondQuote < 0) {
    return "";
  }

  return json.substring(
    firstQuote + 1,
    secondQuote
  );
}


// ------------------------------------------------
// Extract nested student ID
// ------------------------------------------------
String getNestedStudentIDAttendance(
  const String &json
) {

  int studentPos =
    json.indexOf("\"student\"");

  if (studentPos < 0) {
    return "";
  }

  int idPos =
    json.indexOf(
      "\"student_id\"",
      studentPos
    );

  if (idPos < 0) {
    return "";
  }

  int colon =
    json.indexOf(':', idPos);

  if (colon < 0) {
    return "";
  }

  int firstQuote =
    json.indexOf('"', colon + 1);

  if (firstQuote < 0) {
    return "";
  }

  int secondQuote =
    json.indexOf('"', firstQuote + 1);

  if (secondQuote < 0) {
    return "";
  }

  return json.substring(
    firstQuote + 1,
    secondQuote
  );
}


// ------------------------------------------------
// Extract nested student department
// ------------------------------------------------
String getNestedStudentBranchAttendance(
  const String &json
) {

  int studentPos =
    json.indexOf("\"student\"");

  if (studentPos < 0) {
    return "";
  }

  int branchPos =
    json.indexOf(
      "\"branch_name\"",
      studentPos
    );

  if (branchPos < 0) {
    return "";
  }

  int colon =
    json.indexOf(':', branchPos);

  if (colon < 0) {
    return "";
  }

  int firstQuote =
    json.indexOf('"', colon + 1);

  if (firstQuote < 0) {
    return "";
  }

  int secondQuote =
    json.indexOf('"', firstQuote + 1);

  if (secondQuote < 0) {
    return "";
  }

  return json.substring(
    firstQuote + 1,
    secondQuote
  );
}


// ------------------------------------------------
// Check AS608 for fingerprint
// ------------------------------------------------
void checkAttendance() {

  if (attendanceBusy) {
    return;
  }

  if (WiFi.status() != WL_CONNECTED) {
    return;
  }

  if (enrollmentInProgress) {
    return;
  }

  unsigned long now = millis();

  if (
    now - lastAttendanceScan <
    ATTENDANCE_SCAN_INTERVAL
  ) {
    return;
  }

  if (
    now - lastAttendanceSuccess <
    ATTENDANCE_SUCCESS_DELAY
  ) {
    return;
  }

  lastAttendanceScan = now;

  uint8_t p = finger.getImage();

  if (p == FINGERPRINT_NOFINGER) {
    return;
  }

  attendanceBusy = true;

  Serial.println();
  Serial.println("========================================");
  Serial.println("ATTENDANCE: FINGER DETECTED");
  Serial.println("========================================");

  oledMessage(
    "FINGER DETECTED",
    "Checking..."
  );

  // ----------------------------------------------------------
  // Convert image
  // ----------------------------------------------------------
  if (p != FINGERPRINT_OK) {

    Serial.print(
      "getImage failed: "
    );

    Serial.println(p);

    attendanceBusy = false;

    return;
  }

  p = finger.image2Tz();

  if (p != FINGERPRINT_OK) {

    Serial.print(
      "image2Tz failed: "
    );

    Serial.println(p);

    buzzerError();

    oledMessage(
      "FINGER ERROR",
      "Place again"
    );

    delay(1200);

    attendanceBusy = false;

    return;
  }

  // ----------------------------------------------------------
  // Search fingerprint database
  // ----------------------------------------------------------
  p = finger.fingerFastSearch();

  if (p == FINGERPRINT_NOTFOUND) {

    Serial.println(
      "FINGERPRINT NOT REGISTERED"
    );

    buzzerNotRegistered();

    oledMessage(
      "FINGER NOT",
      "REGISTERED"
    );

    delay(1800);

    while (
      finger.getImage() !=
      FINGERPRINT_NOFINGER
    ) {
      delay(50);
    }

    attendanceBusy = false;

    if (!registrationMode) {
      showAttendanceMode();
    }

    return;
  }

  if (p != FINGERPRINT_OK) {

    Serial.print(
      "Fingerprint search failed: "
    );

    Serial.println(p);

    buzzerError();

    oledMessage(
      "SCAN FAILED",
      "Try again"
    );

    delay(1200);

    attendanceBusy = false;

    if (!registrationMode) {
      showAttendanceMode();
    }

    return;
  }

  int fingerprintID =
    finger.fingerID;

  Serial.print(
    "MATCHED FINGERPRINT ID: "
  );

  Serial.println(fingerprintID);

  // ----------------------------------------------------------
  // IMPORTANT:
  // Send the fingerprint to PHP even when teacher permission
  // is OFF. PHP must identify the student first and then decide
  // whether the department is allowed.
  // ----------------------------------------------------------
  bool requestSent =
    sendAttendanceToServer(
      fingerprintID
    );

  if (!requestSent) {

    buzzerError();

    oledMessage(
      "SERVER ERROR",
      "Try again"
    );

    delay(1800);

    attendanceBusy = false;

    if (!registrationMode) {
      showAttendanceMode();
    }

    return;
  }

  // ----------------------------------------------------------
  // Read server result
  // ----------------------------------------------------------
  String response =
    attendanceLastResponse;

  String message =
    getJsonStringAttendance(
      response,
      "message"
    );

  String studentName =
    getNestedStudentNameAttendance(
      response
    );

  String studentID =
    getNestedStudentIDAttendance(
      response
    );

  String studentBranch =
    getNestedStudentBranchAttendance(
      response
    );

  bool attendanceMarked =
    response.indexOf(
      "\"attendance_marked\":true"
    ) >= 0 ||
    response.indexOf(
      "\"attendance_marked\": true"
    ) >= 0;

  bool alreadyMarked =
    response.indexOf(
      "\"already_marked\":true"
    ) >= 0 ||
    response.indexOf(
      "\"already_marked\": true"
    ) >= 0;

  bool departmentAllowed =
    response.indexOf(
      "\"biometric_allowed\":true"
    ) >= 0 ||
    response.indexOf(
      "\"biometric_allowed\": true"
    ) >= 0;

  Serial.println();
  Serial.println("IDENTIFIED STUDENT");
  Serial.print("Student ID: ");
  Serial.println(studentID);
  Serial.print("Student Name: ");
  Serial.println(studentName);
  Serial.print("Department: ");
  Serial.println(studentBranch);
  Serial.print("Teacher Permission: ");
  Serial.println(
    biometricAttendanceEnabled
      ? "ON"
      : "OFF"
  );
  Serial.print("Department Allowed: ");
  Serial.println(
    departmentAllowed
      ? "YES"
      : "NO"
  );

  // ----------------------------------------------------------
  // CASE 1: Attendance successfully inserted
  // ----------------------------------------------------------
  if (
    attendanceMarked &&
    departmentAllowed
  ) {

    lastAttendanceSuccess =
      millis();

    oledMessage(
      "WELCOME",
      studentName
    );

    Serial.println(
      "ATTENDANCE MARKED SUCCESSFULLY"
    );

    buzzerSuccess();
    delay(2500);

  }

  // ----------------------------------------------------------
  // CASE 2: Already marked today
  // ----------------------------------------------------------
  else if (alreadyMarked) {

    buzzerAlreadyMarked();

    oledMessage(
      "ALREADY MARKED",
      studentName
    );

    delay(2000);

  }

  // ----------------------------------------------------------
  // CASE 3: Student belongs to another department OR
  // teacher permission is OFF.
  // ----------------------------------------------------------
  else if (!departmentAllowed) {

    buzzerDepartmentDenied();

    if (studentBranch.length() > 0) {

      Serial.println(
        "BIOMETRIC ATTENDANCE NOT ALLOWED"
      );

      Serial.print(
        "Student Department: "
      );

      Serial.println(studentBranch);

      oledMessage(
        "DEPARTMENT NOT",
        "ALLOWED"
      );

      delay(2200);

    } else {

      oledMessage(
        "ATTENDANCE",
        "NOT ALLOWED"
      );

      delay(1800);
    }
  }

  // ----------------------------------------------------------
  // CASE 4: Other server error
  // ----------------------------------------------------------
  else {

    Serial.print(
      "Attendance server message: "
    );

    Serial.println(message);

    buzzerError();

    oledMessage(
      "ATTENDANCE",
      "NOT RECORDED"
    );

    delay(1800);
  }

  // ----------------------------------------------------------
  // Wait until finger is removed.
  // This prevents repeated attendance requests.
  // ----------------------------------------------------------
  while (
    finger.getImage() !=
    FINGERPRINT_NOFINGER
  ) {
    delay(50);
  }

  attendanceBusy = false;

  if (!registrationMode) {
    showAttendanceMode();
  }
}
// ================================================================
// END BIOMETRIC ATTENDANCE MODULE
// ================================================================
