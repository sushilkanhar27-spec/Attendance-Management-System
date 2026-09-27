#include <Adafruit_Fingerprint.h>

// Change these pins according to your ESP8266 wiring
// ESP8266 RX  <- R307S TX
// ESP8266 TX  -> R307S RX

#define FP_RX D5
#define FP_TX D6

SoftwareSerial fingerSerial(FP_RX, FP_TX);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&fingerSerial);

void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println();
  Serial.println("=================================");
  Serial.println(" R307S Fingerprint Test");
  Serial.println("=================================");

  finger.begin(57600);

  if (finger.verifyPassword()) {
    Serial.println("Fingerprint sensor detected!");
  } else {
    Serial.println("Fingerprint sensor NOT detected!");
    Serial.println("Check RX/TX, power and baud rate.");
    while (1) {
      delay(100);
    }
  }

  // Read sensor parameters
  if (finger.getParameters() == FINGERPRINT_OK) {
    Serial.println();
    Serial.println("Sensor Information:");
    Serial.print("Status Register: 0x");
    Serial.println(finger.status_reg, HEX);

    Serial.print("System ID: 0x");
    Serial.println(finger.system_id, HEX);

    Serial.print("Capacity: ");
    Serial.println(finger.capacity);

    Serial.print("Security Level: ");
    Serial.println(finger.security_level);

    Serial.print("Device Address: 0x");
    Serial.println(finger.device_addr, HEX);

    Serial.print("Packet Length: ");
    Serial.println(finger.packet_len);

    Serial.print("Baud Rate: ");
    Serial.println(finger.baud_rate);
  }

  showFingerprintCount();

  Serial.println();
  Serial.println("Commands:");
  Serial.println("C = Check enrolled fingerprint count");
  Serial.println("L = List enrolled fingerprint IDs");
  Serial.println("D = DELETE ALL fingerprint data");
  Serial.println();
}

void loop() {

  if (Serial.available()) {

    char command = Serial.read();

    if (command == 'C' || command == 'c') {
      showFingerprintCount();
    }

    else if (command == 'L' || command == 'l') {
      listFingerprints();
    }

    else if (command == 'D' || command == 'd') {
      deleteAllFingerprints();
    }
  }
}


// =====================================================
// SHOW NUMBER OF ENROLLED FINGERPRINTS
// =====================================================

void showFingerprintCount() {

  Serial.println();
  Serial.println("---------------------------------");
  Serial.println("Fingerprint Memory Information");
  Serial.println("---------------------------------");

  uint16_t count = getTemplateCount();

  if (count == 0xFFFF) {
    Serial.println("Unable to read fingerprint count.");
    return;
  }

  Serial.print("Total enrolled fingerprints: ");
  Serial.println(count);

  Serial.print("Sensor capacity: ");
  Serial.println(finger.capacity);

  if (finger.capacity >= count) {
    Serial.print("Free fingerprint slots: ");
    Serial.println(finger.capacity - count);
  }

  Serial.println("---------------------------------");
}


// =====================================================
// GET TEMPLATE COUNT
// =====================================================

uint16_t getTemplateCount() {

  uint8_t p = finger.getTemplateCount();

  if (p == FINGERPRINT_OK) {
    return finger.templateCount;
  }

  Serial.print("getTemplateCount error: ");
  Serial.println(p);

  return 0xFFFF;
}


// =====================================================
// LIST FINGERPRINT IDs
// =====================================================

void listFingerprints() {

  Serial.println();
  Serial.println("---------------------------------");
  Serial.println("Checking fingerprint IDs...");
  Serial.println("---------------------------------");

  uint16_t count = getTemplateCount();

  if (count == 0xFFFF) {
    return;
  }

  if (count == 0) {
    Serial.println("No fingerprints enrolled.");
    return;
  }

  Serial.print("Total enrolled: ");
  Serial.println(count);

  Serial.println();
  Serial.println("Searching IDs...");

  uint16_t found = 0;

  // R307S commonly supports IDs from 1 to capacity.
  for (uint16_t id = 1; id <= finger.capacity; id++) {

    uint8_t p = finger.loadModel(id);

    if (p == FINGERPRINT_OK) {
      Serial.print("Fingerprint ID ");
      Serial.print(id);
      Serial.println(" : OCCUPIED");

      found++;
    }

    delay(10);

    if (found >= count) {
      break;
    }
  }

  Serial.println();
  Serial.print("IDs found: ");
  Serial.println(found);

  Serial.println("---------------------------------");
}


// =====================================================
// DELETE ALL FINGERPRINTS
// =====================================================

void deleteAllFingerprints() {

  Serial.println();
  Serial.println("=================================");
  Serial.println(" WARNING!");
  Serial.println("=================================");
  Serial.println("This will DELETE ALL fingerprint");
  Serial.println("templates from the R307S memory.");
  Serial.println();
  Serial.println("Type YES and press Enter to continue.");
  Serial.println("Anything else will cancel.");
  Serial.println();

  String confirmation = "";

  unsigned long startTime = millis();

  while (millis() - startTime < 10000) {

    if (Serial.available()) {

      confirmation = Serial.readStringUntil('\n');
      confirmation.trim();

      if (confirmation == "YES") {

        Serial.println();
        Serial.println("Deleting all fingerprints...");

        uint8_t p = finger.emptyDatabase();

        if (p == FINGERPRINT_OK) {

          Serial.println();
          Serial.println("SUCCESS!");
          Serial.println("All fingerprint templates deleted.");
          Serial.println("R307S memory is now empty.");

          delay(1000);

          showFingerprintCount();

        } else {

          Serial.print("DELETE FAILED. Error code: ");
          Serial.println(p);
        }

      } else {

        Serial.println();
        Serial.println("Delete operation CANCELLED.");
      }

      Serial.println();
      Serial.println("Commands:");
      Serial.println("C = Check count");
      Serial.println("L = List IDs");
      Serial.println("D = Delete all");

      return;
    }
  }

  Serial.println("Delete operation timed out.");
}