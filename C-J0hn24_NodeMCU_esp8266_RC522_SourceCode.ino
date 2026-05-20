//Node MCU with Esp8266 + Rfid RC 522 source code(C-J0hn24 aka Srijan Thapa)
//Do not touch anything if udk how it works ty! :)
#include <SPI.h>
#include <MFRC522.h>
//defin pin for RFID reader
#define RST_PIN   D3
#define SS_PIN    D4
//if green LED glows that means card has been detected and scanned (just a nice indecator)
//without haveing to look at console log
//the green led when scanned
#define LED_PIN   D8

MFRC522 mfrc522(SS_PIN, RST_PIN);

unsigned long lastScanTime = 0;
unsigned long lastReinitTime = 0;
//i put a scanner bc it was scanning 2 fast
const unsigned long scanCooldown = 500;
const unsigned long reinitInterval = 5000;

void setup() {
  Serial.begin(115200);
  delay(2000); //this thing makes the mCu 2 reset so no more manual resets yay

  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);

  SPI.begin();
  mfrc522.PCD_Init();

  delay(1000);

  Serial.println("RFID_READY");
  Serial.println("Scan an RFID card or key fob...");

  lastReinitTime = millis();
}

void loop() {
  //this restarts the RFID every few sec that way i dont have to deal with the card not scanning
  if (millis() - lastReinitTime > reinitInterval) {
    mfrc522.PCD_Init();
    lastReinitTime = millis();
    Serial.println("RFID_READY");
  }

  if (!mfrc522.PICC_IsNewCardPresent()) {
    return;
  }

  if (!mfrc522.PICC_ReadCardSerial()) {
    return;
  }

  unsigned long currentTime = millis();

  if (currentTime - lastScanTime < scanCooldown) {
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
    return;
  }

  lastScanTime = currentTime;
//this is not necessary but just shows the scanned card's UID, 
//no need for extra tables in db for what im planning
//i put it here anyways
  Serial.print("Card UID:");

  String content = "";

  for (byte i = 0; i < mfrc522.uid.size; i++) {
    Serial.print(mfrc522.uid.uidByte[i] < 0x10 ? " 0" : " ");
    Serial.print(mfrc522.uid.uidByte[i], HEX);

    content.concat(mfrc522.uid.uidByte[i] < 0x10 ? "0" : "");
    content.concat(String(mfrc522.uid.uidByte[i], HEX));
  }

  Serial.println();

  content.toUpperCase();
// so i didnt wanna make it very complicated n uses these as variable n sent it as Yes no parms to db
  Serial.println("Card Scanned!");
  Serial.println("RFID_SCANNED");

  digitalWrite(LED_PIN, HIGH);
  delay(300);
  digitalWrite(LED_PIN, LOW);

  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();

  lastReinitTime = millis();

  delay(500);
}