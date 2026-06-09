#define FW_VERSION "1.83"


#include <sys/time.h>
#include <stdlib.h>   // setenv, unsetenv
#include <driver/gpio.h>
#include <math.h>
#include "mbedtls/sha256.h"
// Pentru debug
#define DEBUG_ENABLED
//#define KEEP_AT_DUMP

// Debug GPS
#define DEBUG_GPS_STATUS    1
//#define DEBUG_GPS_RAW_NMEA  1   // ATENTIE: spam mare pe Serial


// În 4G: lăsăm OFF până implementăm HTTPS către script.google.com prin modem
#define ENABLE_GOOGLE_SHEETS 1

#define AP_SSID "ESP32-AP"
#define AP_PASSWORD "12345678"

// ===================== MQTT =====================
#define ENABLE_MQTT_HA     1
#define ENABLE_MQTT_CLOUD  0




// ===================== HOME ASSISTANT MQTT =====================
#if ENABLE_MQTT_HA
  #define MQTT_HA_HOST         "dancalinescuhomeassistant.duckdns.org"
  #define MQTT_HA_PORT         1883
  #define MQTT_HA_USERNAME     "danutzu008"
  #define MQTT_HA_PASSWORD     "0ctav!@n"

  #define MQTT_HA_BASE_TOPIC   "stupina/master"
  #define MQTT_HA_TELE_TOPIC   MQTT_HA_BASE_TOPIC "/telemetry"
  #define MQTT_HA_CMD_TOPIC    MQTT_HA_BASE_TOPIC "/cmd"
  #define MQTT_HA_STATE_TOPIC  MQTT_HA_BASE_TOPIC "/state"
  #define MQTT_HA_GPS_TOPIC    MQTT_HA_BASE_TOPIC "/gps"

  #define MQTT_ENABLE_DISCOVERY 0
#endif

// ===================== CLOUD MQTT =====================
// Completezi ulterior cu brokerul cloud real
#if ENABLE_MQTT_CLOUD
  #define MQTT_CLOUD_HOST         "broker.emqx.io"
  #define MQTT_CLOUD_PORT         1883
  #define MQTT_CLOUD_USERNAME     ""
  #define MQTT_CLOUD_PASSWORD     ""

  #define MQTT_CLOUD_ROOT         "stupina-cloud"

  #define MQTT_CLOUD_MASTER_BASE  MQTT_CLOUD_ROOT "/master"
  #define MQTT_CLOUD_MASTER_TELE  MQTT_CLOUD_MASTER_BASE "/telemetry"
  #define MQTT_CLOUD_MASTER_STATE MQTT_CLOUD_MASTER_BASE "/state"
  #define MQTT_CLOUD_MASTER_GPS   MQTT_CLOUD_MASTER_BASE "/gps"

  #define MQTT_CLOUD_HIVES_BASE   MQTT_CLOUD_ROOT "/hives"
#endif

// Google Sheets Script ID (deploy-ul actual)
#define GScriptId "AKfycbxN2FMOe-o7NLZM8iuW85jxpUQMCXpamzAt-5G_gGA69ZsDA-aaeq1wztptER0TcPjTmg"

#define PHP_TELEMETRY_PATH "/telemetry.php"

#ifdef DEBUG_ENABLED
  #define DEBUG_PRINT(...)   Serial.print(__VA_ARGS__)
  #define DEBUG_PRINTLN(...) Serial.println(__VA_ARGS__)
#else
  #define DEBUG_PRINTLN(x)
  #define DEBUG_PRINT(x)
#endif

// ===== ANTI-THEFT QUALITY FILTER =====
#define GPS_MIN_SATS          7
#define GPS_MAX_HDOP          2.0f
#define THEFT_DISTANCE_M      200.0
#define THEFT_CONFIRM_COUNT   3

// ===================== GPS periodic policy =====================
// NORMAL (theft=0)
#define GPS_CHECK_INTERVAL_MS   (60UL * 60UL * 1000UL)
#define GPS_FIX_TRY_MS          (3UL * 60UL * 1000UL)

// THEFT (theft=1)
#define GPS_THEFT_PUBLISH_MS    (1UL * 60UL * 1000UL)

// 4G override în theft
#define THEFT_FORCE_4G          1

// Definiții
#define NUMSLAVES 20
#define MAX_BUFFER_SIZE 50
#define BATCH_SEND_INTERVAL 60000UL
#define BATCH_FLUSH_TIMEOUT 10000UL



// ===================== OTA =====================
#define ENABLE_OTA_4G 1


#define OTA_HOST "soul2soul.ro"
#define OTA_PORT 443          // pentru version.txt HTTPS
#define OTA_HTTP_PORT 80      // pentru firmware.bin HTTP stream

#define OTA_VERSION_PATH "/ota/ESP32_4G/version.txt"
#define OTA_BIN_PATH     "/ota/ESP32_4G/firmware.bin"
#define OTA_SHA256_PATH  "/ota/ESP32_4G/firmware.sha256"

#define OTA_MIN_VBAT 3.75f


extern "C" {
  #include "esp_wifi.h"
}

#include <esp_now.h>
#include <WiFi.h>
#include <WebServer.h>
#include <ESPmDNS.h>
#include <esp_task_wdt.h>
#include <time.h>

// ===================== FreeRTOS =====================
#include "freertos/FreeRTOS.h"
#include "freertos/task.h"
#include "freertos/queue.h"
#include "freertos/semphr.h"

// ===================== 4G / A7670G (TinyGSM-fork) =====================
#define TINY_GSM_MODEM_A7670
#define TINY_GSM_MODEM_A76XXSSL
#define TINY_GSM_RX_BUFFER 1024
#include <TinyGsmClient.h>
#include <PubSubClient.h>
#include <Update.h> 

#define SIM_PIN ""

// APN-uri
const char* APN_LIST[] = { "internet", "prepaid", "net" };
const int APN_COUNT = sizeof(APN_LIST) / sizeof(APN_LIST[0]);

// Pini LilyGO T-A7670G
#define MODEM_BAUDRATE 115200
#define MODEM_TX_PIN 26
#define MODEM_RX_PIN 27
#define BOARD_PWRKEY_PIN 4
#define BOARD_POWERON_PIN 12
#define MODEM_RESET_PIN 5
#define MODEM_RESET_LEVEL HIGH
#define MODEM_DTR_PIN 25

#define GPS_TX_PIN 21
#define GPS_RX_PIN 22
#define GPS_PPS_PIN 23
#define GPS_WAKEUP_PIN 19
#define GPS_BAUD 9600

HardwareSerial SerialGPS(2);

#define DEDUP_WEIGHT_EPS   0.001f
#define DEDUP_TEMP_EPS     0.01f
#define DEDUP_BATT_EPS     0.005f
#define DEDUP_RSSI_EPS     2
#define DEDUP_WINDOW_MS    12UL * 1000UL

// ===================== BATTERY & SOLAR (local ADC) =====================
#define BAT_ADC_PIN 35
#define SOLAR_ADC_PIN 36
#define ADC_REF 3.3f

// ===================== BATTERY CALIB =====================
#define VBAT_MASURAT 4.105f
#define VBAT_DIV_BASE 2.0f
#define VBAT_ADC_CALIB 4.070f

// ===================== SOLAR PANNEL CALIB =====================
#define VSOLAR_MASURAT 1.755f
#define VSOLAR_DIV_BASE 2.0f
#define VSOLAR_ADC_CALIB 1.499f

static float VBAT_DIV   = VBAT_DIV_BASE   * (VBAT_MASURAT  / VBAT_ADC_CALIB);
static float VSOLAR_DIV = VSOLAR_DIV_BASE * (VSOLAR_MASURAT / VSOLAR_ADC_CALIB);




bool modemReady = false;
bool modemWanted = false;
unsigned long modemNextTry = 0;
unsigned long modemRetryDelay = 30000UL;
const unsigned long modemRetryDelayMax = 300000UL;

// Print periodic
unsigned long lastTelemetryPrint = 0;
const unsigned long TELEMETRY_PRINT_MS = 10000UL;

// ===== Praguri =====
static const float SOLAR_NIGHT_TH = 0.0f;
static const uint32_t NIGHT_WAKE_MINUTES = 30;
static const uint32_t SUNRISE_GPS_TIMEOUT_MS = 10000;

// GPS last read (RAM)
double lastGpsLat = 0, lastGpsLon = 0;
int lastGpsSats = 0, lastGpsFq = 0;
bool gpsHasFix = false;
bool gpsIsOn = false;

unsigned long nextGpsCheckAtMs   = 0;
unsigned long nextTheftPublishMs = 0;

// ===== RTC persist =====
RTC_DATA_ATTR uint8_t rtcLastWasNight = 0;
RTC_DATA_ATTR double rtcRefLat = 0;
RTC_DATA_ATTR double rtcRefLon = 0;
RTC_DATA_ATTR uint8_t rtcHasRef = 0;
RTC_DATA_ATTR uint8_t rtcTheft = 0;
RTC_DATA_ATTR uint32_t rtcLastRefTs = 0;
RTC_DATA_ATTR uint8_t rtcEspNowChannel = 1;
RTC_DATA_ATTR uint8_t rtcBootTelemetrySent = 0;
RTC_DATA_ATTR uint8_t rtcTheftCandidateCount = 0;

// ===================== ENERGY POLICY (DAY) =====================
static const float VBAT_ALLOW_4G = 3.60f;
static const float VSOLAR_STRONG_SUN = 5.00f;
static const unsigned long LOWPOWER_4G_COOLDOWN_MS = 60000UL;
unsigned long next4GAllowedAt = 0;

volatile bool netBusy = false;
volatile bool otaRequested = false;
volatile bool telemetryRequested = false;

unsigned long lastTaskStackPrint = 0;
const unsigned long TASK_STACK_PRINT_MS = 60000UL;


// ===================== PHP FAIL-GUARD =====================
bool phpEnabled = true;
unsigned long phpDisabledUntil = 0;
static const unsigned long PHP_DISABLE_MS = 30UL * 60UL * 1000UL;

enum PhpFailReason { PHP_FAIL_NONE, PHP_FAIL_NO_NET, PHP_FAIL_CONNECT, PHP_FAIL_TIMEOUT, PHP_FAIL_STATUS, PHP_FAIL_CLOSED };
volatile PhpFailReason lastPhpFail = PHP_FAIL_NONE;

#define SerialAT Serial1
TinyGsm modem(SerialAT);
TinyGsmClient gsmClient(modem);
TinyGsmClient otaHttpClient(modem, 2);

struct MqttRuntime {
  const char* name;
  const char* host;
  uint16_t port;
  const char* username;
  const char* password;
  const char* baseTopic;
  bool enabled;
  bool subscribeCmd;
  bool subDone;
};

#if ENABLE_MQTT_HA
  TinyGsmClient mqttHaNet(modem);
  PubSubClient mqttHa(mqttHaNet);
  bool mqttHaDiscoverySent = false;

  MqttRuntime mqttHaCfg = {
    "HA",
    MQTT_HA_HOST,
    MQTT_HA_PORT,
    MQTT_HA_USERNAME,
    MQTT_HA_PASSWORD,
    MQTT_HA_BASE_TOPIC,
    true,
    true,
    false
  };
#endif

#if ENABLE_MQTT_CLOUD
  TinyGsmClient mqttCloudNet(modem);
  PubSubClient mqttCloud(mqttCloudNet);

  MqttRuntime mqttCloudCfg = {
    "CLOUD",
    MQTT_CLOUD_HOST,
    MQTT_CLOUD_PORT,
    MQTT_CLOUD_USERNAME,
    MQTT_CLOUD_PASSWORD,
    MQTT_CLOUD_ROOT,
    true,
    false,
    false
  };
#endif

// ===================== Structuri / Buffer ESP-NOW =====================
typedef struct {
  uint32_t chipID;
  float temperature;
  float weight;
  float battery;
  char message[20];
  char slaveMac[18];
  int wifiSignal;
  char ziNoapte[8];
  char firmwareVersion[8];
  bool posibilROI;
} ESPnowMessage;

// Buffer pentru mesajele primite (FIFO)
ESPnowMessage batchBuffer[MAX_BUFFER_SIZE];
uint32_t batchReceivedTs[MAX_BUFFER_SIZE];
int bufferCount = 0;

ESPnowMessage phpPending[MAX_BUFFER_SIZE];
uint32_t phpPendingReceivedTs[MAX_BUFFER_SIZE];
int phpPendingCount = 0;

struct LastRxByChip {
  uint32_t chipID;
  float weight;
  float temperature;
  float battery;
  int wifiSignal;
  unsigned long ms;
  bool valid;
};

LastRxByChip lastRx[NUMSLAVES];

// ---------------------------------------------------
// AFISARE pentru HTML local
struct SlaveDisplay {
  ESPnowMessage msg;
  unsigned long lastUpdate;
};

SlaveDisplay slaveDisplay[NUMSLAVES];
int displayCount = 0;

unsigned long phpNextTry = 0;
unsigned long phpRetryDelay = 60000UL;
const unsigned long phpRetryDelayMax = 300000UL;

// Canal FIX
uint8_t CHANNEL = 1;

// Listă de slave-uri
esp_now_peer_info_t slaves[NUMSLAVES] = {};
int SlaveCnt = 0;
bool slaveConfirmed[NUMSLAVES] = { false };

unsigned long lastMessageTime = 0;
unsigned long lastBatchAttempt = 0;
unsigned long batchStartTime = 0;
unsigned long lastBatchTime = 0;
bool isSending = false;

// ===== TELEMETRY POLICY =====
static const unsigned long TELEMETRY_PERIOD_MS = 5UL * 60UL * 1000UL;
static const unsigned long TELEMETRY_BATCH_GAP_MS = 15000UL;

unsigned long lastEspNowRxMs = 0;
unsigned long lastTelemetrySentMs = 0;
unsigned long lastTelemetryAttemptMs = 0;
bool telemetryBootSent = false;
unsigned long lastTelemetryBatchMs = 0;

// ===================== 4G HEALTH / SELF RECOVERY =====================
unsigned long lastNetSuccessMs = 0;
unsigned long firstUnsentDataMs = 0;
unsigned long last4GHealthCheckMs = 0;
unsigned long last4GRecoverMs = 0;

static const unsigned long HEALTH_CHECK_MS       = 60UL * 1000UL;
static const unsigned long NET_DEAD_RESTART_MS  = 30UL * 60UL * 1000UL;
static const unsigned long UNSENT_RESTART_MS    = 30UL * 60UL * 1000UL;
static const unsigned long MODEM_RECOVER_GAP_MS = 5UL * 60UL * 1000UL;

// ===================== Google Pending Retry =====================
ESPnowMessage googlePending[MAX_BUFFER_SIZE];
int googlePendingCount = 0;
unsigned long googleNextTry = 0;
unsigned long googleRetryDelay = 60000UL;
const unsigned long googleRetryDelayMax = 300000UL;
unsigned long googleDisabledUntil = 0;
const unsigned long googleDisable403Ms = 1800000UL;

bool solarPresent = false;
bool sunriseFlag = false;

// ===================== FreeRTOS / Dual Core =====================
TaskHandle_t taskLocalHandle = nullptr;
TaskHandle_t task4GHandle    = nullptr;

QueueHandle_t espNowRxQueue = nullptr;

SemaphoreHandle_t batchMutex   = nullptr;
SemaphoreHandle_t displayMutex = nullptr;
SemaphoreHandle_t stateMutex   = nullptr;
SemaphoreHandle_t modemMutex   = nullptr;
SemaphoreHandle_t adcMutex     = nullptr;

typedef struct {
  ESPnowMessage msg;
} EspNowQueuedMessage;

// ===================== HTML server =====================
WebServer server(80);

const char PAGE_HTML[] PROGMEM = R"rawliteral(
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Stupina</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    #container {
      display: flex;
      flex-wrap: wrap;
      gap: 0.1rem;
      justify-content: center;
      margin: 20px;
    }
    .hive-wrapper {
      width: 240px;
      height: 220px;
      overflow: hidden;
      position: relative;
      background: #fff;
      margin: 0 auto;
    }
    .hive {
      position: absolute;
      top: 0;
      left: 0;
      width: 240px;
      height: 220px;
      font-size: 1.1rem;
      text-align: center;
      transform-origin: top left;
    }
    @media (max-width: 500px) {
      .hive-wrapper {
        width: 175px;
        height: 170px;
      }
      .hive {
        transform: scale(0.75);
      }
    }
    @media (max-width: 400px) {
      .hive-wrapper {
        width: 175px;
        height: 170px;
      }
      .hive {
        transform: scale(0.5);
      }
    }
    .roof {
      position: absolute;
      top: 0;
      left: 10px;
      width: 220px;
      height: 100px;
      background: lightgrey;
      clip-path: polygon(0 0, 100% 0, 100% 20%, 90% 20%, 90% 10%, 10% 10%, 10% 20%, 0 20%);
    }
    .body {
      position: absolute;
      top: 10px;
      left: 20px;
      width: 200px;
      background: #f7eaaa;
      border: 4px solid lightgrey;
      box-sizing: border-box;
      padding: 8px;
    }
    .base {
      position: absolute;
      bottom: 5px;
      left: 10px;
      width: 220px;
      height: 70px;
      background: lightgrey;
      clip-path: polygon(0 70%, 100% 70%, 100% 100%, 90% 100%, 90% 90%, 10% 90%, 10% 100%, 0 100%);
    }
    .row {
      border: 1px solid #000;
      margin: 3px 0;
      padding: 3px;
      background: #f7eaaa;
      white-space: nowrap;
      position:relative;
    }
    .row-l, .row-r {
      display: inline-block;
      vertical-align: middle;
      width: 50%;
      box-sizing: border-box;
    }
    .row-l { text-align: left; }
    .row-r { text-align: right; }
    .bigValue {
        font-size: 1.3rem;
        font-weight: bold;
    }
    .normalValue {
        font-size: 1rem;
        font-weight: normal;
    }
    .smallValue {
        font-size: 0.8rem;
        font-weight: normal;
    }
     .roi-inline
    { position:absolute;
    top:50%;
    right:6px;
    width:18px;
    height:18px;
    border-radius:50%;
    transform:translateY(-50%);
    }
    h1 { text-align: center; margin-top: 20px; }
    #restartBtn{margin:10px;padding:10px 20px;font-size:1rem;cursor:pointer;}
  </style>
</head>
<body>
  <h1>Stupina</h1>
  <button id="restartBtn" onclick="if(confirm('Restart ESP32?')) fetch('/restart');">Restart ESP32</button>
  <div id="container"></div>

  <script>
    const chipIDtoName = {
      "731678": "Stup 1",
      "731671": "Stup 3",
      "731662": "Stup 4",
      "731674": "Stup 6",
      "731677": "Stup 7",
      "7295024": "Stup Test"
    };
    var oldWeights = {};
    var redTimestamps = {};

    function formatTime(sec) {
      if (sec < 60) return sec + " second" + (sec !== 1 ? "s" : "") + " ago";
      var minutes = Math.floor(sec / 60);
      if (minutes < 60) return minutes + " minute" + (minutes !== 1 ? "s" : "") + " ago";
      var hours = Math.floor(minutes / 60);
      if (hours < 24) return hours + " hour" + (hours !== 1 ? "s" : "") + " ago";
      var days = Math.floor(hours / 24);
      return days + " day" + (days !== 1 ? "s" : "") + " ago";
    }

    function updateHives(data) {
      var container = document.getElementById("container");
      container.innerHTML = "";
      var currentMillis = Date.now();

      data
          .sort((a, b) => {
            const nameA = chipIDtoName[a.chipID] || 'zzz_' + a.chipID;
            const nameB = chipIDtoName[b.chipID] || 'zzz_' + b.chipID;
            return nameA.localeCompare(nameB, 'ro', { numeric: true });
            })
            .forEach(function(item) {

        var wrapper = document.createElement("div");
        wrapper.className = "hive-wrapper";

        var hive = document.createElement("div");
        hive.className = "hive";

        var roof = document.createElement("div");
        roof.className = "roof";
        hive.appendChild(roof);

        var body = document.createElement("div");
        body.className = "body";

        var row1 = document.createElement("div");
        row1.className = "row";
        row1.textContent = chipIDtoName[item.chipID] || item.chipID;
        if (item.posibilROI) {
          var pill = document.createElement("span");
          pill.className = "roi-inline";
          pill.title = "Posibil ROI";
          pill.style.backgroundColor = "red";
          row1.appendChild(pill);
        }
        body.appendChild(row1);

        var row2 = document.createElement("div");
        row2.className = "row";
        var row2l = document.createElement("div");
        row2l.className = "row-l bigValue";
        row2l.textContent = item.weight + " kg";
        var oldW = oldWeights[item.chipID];
        if (oldW !== undefined) {
          var diff = Math.abs(item.weight - oldW);
          if (diff > 0.5) {
            redTimestamps[item.chipID] = currentMillis;
            row2l.style.color = "red";
          } else if (redTimestamps[item.chipID] !== undefined) {
            if (currentMillis - redTimestamps[item.chipID] < 120000) {
              row2l.style.color = "red";
            } else {
              delete redTimestamps[item.chipID];
              row2l.style.color = "black";
            }
          } else {
            row2l.style.color = "black";
          }
        }
        oldWeights[item.chipID] = item.weight;
        var row2r = document.createElement("div");
        row2r.className = "row-r normalValue";
        row2r.textContent = item.temperature + " °C";
        if (item.temperature <= 0) row2r.style.color = "blue";
        else if (item.temperature < 15) row2r.style.color = "orange";
        else if (item.temperature < 30) row2r.style.color = "green";
        else row2r.style.color = "red";
        row2.appendChild(row2l);
        row2.appendChild(row2r);
        body.appendChild(row2);

        var row3 = document.createElement("div");
        row3.className = "row";
        var row3l = document.createElement("div");
        row3l.className = "row-l smallValue";
        row3l.textContent = item.ziNoapte;
        var row3r = document.createElement("div");
        row3r.className = "row-r smallValue";
        row3r.textContent = item.battery + " V";
        if (item.battery < 2.8) row3r.style.color = "red";
        else if (item.battery < 3.1) row3r.style.color = "orange";
        else row3r.style.color = "green";
        row3.appendChild(row3l);
        row3.appendChild(row3r);
        body.appendChild(row3);

        var row4 = document.createElement("div");
        row4.className = "row";
        var row4l = document.createElement("div");
        row4l.className = "row-l smallValue";
        row4l.textContent = item.slaveMac;
        var row4r = document.createElement("div");
        row4r.className = "row-r smallValue";
        row4r.textContent = item.wifiSignal + " dB";
        row4.appendChild(row4l);
        row4.appendChild(row4r);
        body.appendChild(row4);

        var row5 = document.createElement("div");
        row5.className = "row";
        var row5l = document.createElement("div");
        row5l.className = "row-l smallValue";
        row5l.style.color = (item.age < 10) ? "green" : "red";
        row5l.textContent = formatTime(item.age);
        var row5r = document.createElement("div");
        row5r.className = "row-r smallValue";
        row5r.textContent = "v"+ item.firmwareVersion;
        row5.appendChild(row5l);
        row5.appendChild(row5r);
        body.appendChild(row5);

        hive.appendChild(body);

        var base = document.createElement("div");
        base.className = "base";
        hive.appendChild(base);

        wrapper.appendChild(hive);
        document.getElementById("container").appendChild(wrapper);
      });
    }

function fetchData(){
  fetch("/data").then(r=>r.json()).then(updateHives);
}
window.onload=fetchData; setInterval(fetchData,2000);
  </script>
</body>
</html>
)rawliteral";

// ===================== Helpers / mutex =====================
static bool almostEqualFloat(float a, float b, float eps) {
  return fabsf(a - b) <= eps;
}

static int findLastRxSlot(uint32_t chipID) {
  for (int i = 0; i < NUMSLAVES; i++) {
    if (lastRx[i].valid && lastRx[i].chipID == chipID) return i;
  }
  for (int i = 0; i < NUMSLAVES; i++) {
    if (!lastRx[i].valid) return i;
  }
  return 0;
}

static bool lockMutex(SemaphoreHandle_t mtx, uint32_t timeoutMs = 1000)
{
  if (mtx == nullptr) return false;
  return xSemaphoreTake(mtx, pdMS_TO_TICKS(timeoutMs)) == pdTRUE;
}

static void unlockMutex(SemaphoreHandle_t mtx)
{
  if (mtx) xSemaphoreGive(mtx);
}

static void phpDisableNow(const __FlashStringHelper* reason) {
  phpEnabled = false;
  phpDisabledUntil = millis() + PHP_DISABLE_MS;
  DEBUG_PRINT(F("⛔ PHP disabled 30 min: "));
  DEBUG_PRINTLN(reason);
}

static void phpMaybeReEnable() {
  if (!phpEnabled && millis() > phpDisabledUntil) {
    phpEnabled = true;
    DEBUG_PRINTLN(F("✅ PHP re-enabled (timeout elapsed)"));
  }
}

void updateDisplayData(const ESPnowMessage &data)
{
  if (!lockMutex(displayMutex, 100)) return;

  for (int i = 0; i < displayCount; i++)
  {
    if (strcmp(slaveDisplay[i].msg.slaveMac, data.slaveMac) == 0)
    {
      slaveDisplay[i].msg = data;
      slaveDisplay[i].lastUpdate = millis();
      unlockMutex(displayMutex);
      return;
    }
  }

  if (displayCount < NUMSLAVES)
  {
    slaveDisplay[displayCount].msg = data;
    slaveDisplay[displayCount].lastUpdate = millis();
    displayCount++;
  }

  unlockMutex(displayMutex);
}

// ===================== HTML handlers =====================
void handleRoot()
{
  String page = FPSTR(PAGE_HTML);
  server.send(200, "text/html", page);
}

void handleRestart()
{
  server.send(200, "text/plain", "Rebooting...");
  delay(150);
  ESP.restart();
}

void handleData()
{
  String json = "[";

  if (lockMutex(displayMutex, 200))
  {
    for (int i = 0; i < displayCount; i++)
    {
      unsigned long age = (millis() - slaveDisplay[i].lastUpdate) / 1000;

      json += "{";
      json += "\"slaveMac\":\"" + String(slaveDisplay[i].msg.slaveMac) + "\",";
      json += "\"chipID\":" + String(slaveDisplay[i].msg.chipID) + ",";
      json += "\"temperature\":" + String(slaveDisplay[i].msg.temperature, 2) + ",";
      json += "\"weight\":" + String(slaveDisplay[i].msg.weight, 2) + ",";
      json += "\"battery\":" + String(slaveDisplay[i].msg.battery, 2) + ",";
      json += "\"wifiSignal\":" + String(slaveDisplay[i].msg.wifiSignal) + ",";
      json += "\"ziNoapte\":\"" + String(slaveDisplay[i].msg.ziNoapte) + "\",";
      json += "\"firmwareVersion\":\"" + String(slaveDisplay[i].msg.firmwareVersion) + "\",";
      json += "\"posibilROI\":" + String(slaveDisplay[i].msg.posibilROI ? "true" : "false") + ",";
      json += "\"age\": " + String(age);
      json += "}";

      if (i < displayCount - 1) json += ",";
    }
    unlockMutex(displayMutex);
  }

  json += "]";
  server.send(200, "application/json", json);
}

// ===================== TIME FORMATTING =====================
void getFormattedTime(char* dateStr, size_t dateLen, char* timeStr, size_t timeLen, char* isoStr, size_t isoLen)
{
  time_t now = time(nullptr);
  struct tm t;
  localtime_r(&now, &t);

  snprintf(dateStr, dateLen, "%04d-%02d-%02d", t.tm_year + 1900, t.tm_mon + 1, t.tm_mday);
  snprintf(timeStr, timeLen, "%02d:%02d:%02d", t.tm_hour, t.tm_min, t.tm_sec);
  snprintf(isoStr, isoLen, "%04d-%02d-%02dT%02d:%02d:%02d",
           t.tm_year + 1900, t.tm_mon + 1, t.tm_mday,
           t.tm_hour, t.tm_min, t.tm_sec);
}

// ===================== 4G helpers =====================
static void hardPowerCycleModem()
{
  pinMode(BOARD_POWERON_PIN, OUTPUT);
  digitalWrite(BOARD_POWERON_PIN, HIGH);
  delay(50);

  pinMode(MODEM_DTR_PIN, OUTPUT);
  digitalWrite(MODEM_DTR_PIN, LOW);
  delay(20);

  pinMode(MODEM_RESET_PIN, OUTPUT);
  digitalWrite(MODEM_RESET_PIN, !MODEM_RESET_LEVEL);
  delay(100);
  digitalWrite(MODEM_RESET_PIN, MODEM_RESET_LEVEL);
  delay(2600);
  digitalWrite(MODEM_RESET_PIN, !MODEM_RESET_LEVEL);
  delay(100);

  pinMode(BOARD_PWRKEY_PIN, OUTPUT);
  digitalWrite(BOARD_PWRKEY_PIN, LOW);
  delay(100);
  digitalWrite(BOARD_PWRKEY_PIN, HIGH);
  delay(1000);
  digitalWrite(BOARD_PWRKEY_PIN, LOW);
  delay(800);
}

static bool readModemLine(String &out, uint32_t timeoutMs)
{
  out = "";
  uint32_t t0 = millis();

  while (millis() - t0 < timeoutMs)
  {
    while (SerialAT.available())
    {
      char c = (char)SerialAT.read();
      if (c == '\r') continue;

      if (c == '\n')
      {
        out.trim();
        if (out.length() > 0) return true;
      }
      else
      {
        out += c;
      }
    }
    delay(2);
  }

  out.trim();
  return (out.length() > 0);
}

#ifdef KEEP_AT_DUMP
void dumpAT(const char* cmd, uint32_t ms = 2000)
{
  Serial.print("📟 AT DUMP >> ");
  Serial.println(cmd);

  SerialAT.println(cmd);

  uint32_t t0 = millis();
  while (millis() - t0 < ms)
  {
    while (SerialAT.available())
    {
      char c = (char)SerialAT.read();
      Serial.write(c);
    }
    delay(2);
  }

  Serial.println();
  Serial.println("📟 AT DUMP << END");
}
#endif

// ===================== BATTERY READ =====================
float readVBAT_ADC()
{
  if (!lockMutex(adcMutex, 500)) return 0.0f;

  analogSetPinAttenuation(BAT_ADC_PIN, ADC_11db);
  analogReadResolution(12);

  uint32_t sum = 0;
  const int N = 20;

  for (int i = 0; i < N; i++)
  {
    sum += analogReadMilliVolts(BAT_ADC_PIN);
    delay(3);
  }

  unlockMutex(adcMutex);

  float mv_adc = (float)sum / N;
  float v_adc  = mv_adc / 1000.0f;
  float vbat   = v_adc * VBAT_DIV;
  return vbat;
}

bool updateSolarPresent(float vsolar)
{
  const float ON_TH  = 1.20f;
  const float OFF_TH = 0.80f;

  if (!solarPresent && vsolar >= ON_TH) solarPresent = true;
  else if (solarPresent && vsolar <= OFF_TH) solarPresent = false;

  return solarPresent;
}

static bool waitForSIM(unsigned long timeoutMs)
{
  unsigned long t0 = millis();

  while (millis() - t0 < timeoutMs)
  {
    SimStatus s = modem.getSimStatus();
    if (s == SIM_READY)
    {
      DEBUG_PRINTLN(F("✅ SIM card online"));
      return true;
    }

    if (s == SIM_LOCKED)
    {
      DEBUG_PRINTLN(F("🔒 SIM locked, incerc unlock..."));
      modem.simUnlock(SIM_PIN);
    }

    delay(500);
  }

  DEBUG_PRINTLN(F("❌ Timeout SIM_READY"));
  return false;
}

static bool waitForNetworkRegistration(unsigned long timeoutMs)
{
  unsigned long t0 = millis();

  while (millis() - t0 < timeoutMs)
  {
    RegStatus r = modem.getRegistrationStatus();
    if (r == REG_OK_HOME || r == REG_OK_ROAMING)
    {
      DEBUG_PRINTLN(F("✅ Înregistrare rețea OK"));
      return true;
    }

    if (r == REG_DENIED)
    {
      DEBUG_PRINTLN(F("❌ Înregistrare respinsă"));
      return false;
    }

    int16_t sq = modem.getSignalQuality();
    DEBUG_PRINT("[");
    DEBUG_PRINT((millis() - t0) / 1000.0);
    DEBUG_PRINT(F(" s] Căutare rețea… semnal "));
    DEBUG_PRINTLN(sq);

    delay(1000);
  }

  DEBUG_PRINTLN(F("❌ Timeout înregistrare rețea"));
  return false;
}

static bool connectGPRS(const char* apn)
{
  DEBUG_PRINT(F("📲 Conectare GPRS (APN="));
  DEBUG_PRINT(apn);
  DEBUG_PRINTLN(F(")…"));

  modem.gprsDisconnect();
  delay(200);

  if (modem.gprsConnect(apn))
  {
    DEBUG_PRINT(F("✅ GPRS conectat, IP: "));
    DEBUG_PRINTLN(modem.localIP());
    return true;
  }

  DEBUG_PRINTLN(F("⚠️ gprsConnect() a eșuat, încerc CGDCONT + CGATT și retry..."));

  modem.sendAT("+CMEE=2");
  modem.waitResponse(2000);

  modem.sendAT(GF("+CGDCONT=1,\"IP\",\""), apn, "\"");
  int r = modem.waitResponse(4000);
  if (r != 1)
  {
    DEBUG_PRINT(F("❌ Eroare setare APN (CGDCONT). waitResponse="));
    DEBUG_PRINTLN(r);

    modem.sendAT("+CGDCONT?");
    modem.waitResponse(2000);
    modem.sendAT("+CGATT?");
    modem.waitResponse(2000);
    return false;
  }

  modem.sendAT("+CGATT=1");
  modem.waitResponse(8000);
  delay(200);

  if (modem.gprsConnect(apn))
  {
    DEBUG_PRINT(F("✅ GPRS conectat, IP: "));
    DEBUG_PRINTLN(modem.localIP());
    return true;
  }

  DEBUG_PRINTLN(F("❌ GPRS eșuat și după retry."));
  return false;
}

bool connectModem4G()
{
  gpio_deep_sleep_hold_dis();
  gpio_hold_dis((gpio_num_t)GPS_WAKEUP_PIN);
  gpio_hold_dis((gpio_num_t)MODEM_RESET_PIN);
  gpio_hold_dis((gpio_num_t)BOARD_POWERON_PIN);

  pinMode(BOARD_POWERON_PIN, OUTPUT);
  digitalWrite(BOARD_POWERON_PIN, HIGH);
  delay(80);

  pinMode(MODEM_RESET_PIN, OUTPUT);
  digitalWrite(MODEM_RESET_PIN, !MODEM_RESET_LEVEL);
  delay(50);

  pinMode(MODEM_DTR_PIN, OUTPUT);
  digitalWrite(MODEM_DTR_PIN, LOW);
  delay(20);

  pinMode(BOARD_PWRKEY_PIN, OUTPUT);
  digitalWrite(BOARD_PWRKEY_PIN, LOW);
  delay(10);

  pinMode(BOARD_POWERON_PIN, OUTPUT);
  digitalWrite(BOARD_POWERON_PIN, HIGH);
  delay(80);

  pinMode(MODEM_DTR_PIN, OUTPUT);
  digitalWrite(MODEM_DTR_PIN, LOW);
  delay(20);

  SerialAT.begin(MODEM_BAUDRATE, SERIAL_8N1, MODEM_RX_PIN, MODEM_TX_PIN);
  delay(200);

  DEBUG_PRINTLN(F("🚀 Start modem… (force power sequence)"));
  hardPowerCycleModem();

  const uint32_t AT_DEADLINE_MS = 45000;
  uint32_t tStart = millis();
  int pwrkeyPulses = 0;

  while (!modem.testAT(1000))
  {
    DEBUG_PRINT(".");

    if ((millis() - tStart) > (uint32_t)(12000 * (pwrkeyPulses + 1)))
    {
      DEBUG_PRINTLN(F("\n⚠️ no AT -> pulse PWRKEY"));
      pinMode(BOARD_PWRKEY_PIN, OUTPUT);
      digitalWrite(BOARD_PWRKEY_PIN, LOW);
      delay(120);
      digitalWrite(BOARD_PWRKEY_PIN, HIGH);
      delay(1200);
      digitalWrite(BOARD_PWRKEY_PIN, LOW);
      pwrkeyPulses++;
    }

    if (millis() - tStart > AT_DEADLINE_MS)
    {
      DEBUG_PRINTLN(F("\n❌ Modem no AT within deadline -> FAIL connectModem4G()"));
      return false;
    }

    delay(80);
  }

  DEBUG_PRINTLN(F("\n✅ Modem AT OK"));

  if (!modem.init())
  {
    DEBUG_PRINTLN(F("⚠️ modem.init() eșuat, continui…"));
  }

  if (!waitForSIM(20000)) return false;
  if (strlen(SIM_PIN) > 0) modem.simUnlock(SIM_PIN);

  if (!waitForNetworkRegistration(60000)) return false;

  modem.sendAT("+CGATT?");
  modem.waitResponse(2000);

  bool gprsOk = false;
  for (int i = 0; i < APN_COUNT; i++)
  {
    DEBUG_PRINT(F("🔌 Incerc APN: "));
    DEBUG_PRINTLN(APN_LIST[i]);

    if (connectGPRS(APN_LIST[i]))
    {
      gprsOk = true;
      break;
    }
  }

  if (!gprsOk) return false;
  return true;
}

uint32_t getEsp32ChipId()
{
  uint64_t mac = ESP.getEfuseMac();
  return (uint32_t)(mac & 0xFFFFFFFF);
}


bool syncTimeFromModemCCLK(uint32_t timeoutMs = 4000)
{
  SerialAT.println("AT+CLTS=1");
  delay(100);
  SerialAT.println("AT+CTZU=1");
  delay(100);
  SerialAT.println("AT+CCLK?");

  String line;
  uint32_t t0 = millis();

  while (millis() - t0 < timeoutMs)
  {
    if (!readModemLine(line, 800)) continue;

    int p = line.indexOf("+CCLK:");
    if (p >= 0)
    {
      int q1 = line.indexOf('"', p);
      int q2 = line.indexOf('"', q1 + 1);
      if (q1 < 0 || q2 < 0) return false;

      String s = line.substring(q1 + 1, q2);

      int yy = s.substring(0, 2).toInt();
      int MM = s.substring(3, 5).toInt();
      int dd = s.substring(6, 8).toInt();
      int hh = s.substring(9, 11).toInt();
      int mm = s.substring(12, 14).toInt();
      int ss = s.substring(15, 17).toInt();

      int tzSignPos = s.indexOf('+', 17);
      int tzSignNeg = s.indexOf('-', 17);
      int tzPos = (tzSignPos >= 0) ? tzSignPos : tzSignNeg;

      int tzq = 0;
      if (tzPos >= 0 && tzPos + 3 <= (int)s.length())
      {
        tzq = s.substring(tzPos).toInt();
      }

      struct tm t {};
      t.tm_year = (2000 + yy) - 1900;
      t.tm_mon  = MM - 1;
      t.tm_mday = dd;
      t.tm_hour = hh;
      t.tm_min  = mm;
      t.tm_sec  = ss;

      setenv("TZ", "UTC0", 1);
      tzset();
      time_t epoch = mktime(&t);
      unsetenv("TZ");
      tzset();

      epoch -= (tzq * 15 * 60);

      if (epoch < 1000000000) return false;

      struct timeval now;
      now.tv_sec = epoch;
      now.tv_usec = 0;
      settimeofday(&now, nullptr);

      Serial.print("✅ Timp setat din modem. Epoch=");
      Serial.println((unsigned long)epoch);

      return true;
    }
  }

  return false;
}

// ===================== INTERNET CHECK =====================
static void mark4GDead(const __FlashStringHelper* reason)
{
  DEBUG_PRINT(F("4G invalid -> "));
  DEBUG_PRINTLN(reason);

  modemReady = false;
  modemWanted = true;
  netBusy = false;

  gsmClient.stop();
  otaHttpClient.stop();

  modemNextTry = millis() + 5000UL;
}

bool isInternet()
{
  if (!modemReady)
  {
    return false;
  }

  bool netOk = modem.isNetworkConnected();
  bool gprsOk = modem.isGprsConnected();

  if (!netOk || !gprsOk)
  {
    mark4GDead(F("network/GPRS down"));
    DEBUG_PRINTLN(F("📴 Fără rețea/GPRS pe 4G."));
    return false;
  }

  IPAddress ip = modem.localIP();

  if (!ip)
  {
    mark4GDead(F("IP invalid"));
    return false;
  }

  return true;
}


#if ENABLE_OTA_4G

bool otaGetServerVersion(String &serverVersion)
{
  serverVersion = "";

  if (!isInternet())
  {
    DEBUG_PRINTLN(F("❌ OTA: internet indisponibil."));
    return false;
  }

  DEBUG_PRINTLN(F("🔎 OTA: citesc version.txt prin HTTPS..."));

  String url = String("https://") + OTA_HOST + OTA_VERSION_PATH;

  modem.https_begin();
  modem.https_set_url(url.c_str());

  int httpCode = modem.https_get();

  DEBUG_PRINT(F("🔎 OTA: version HTTP code = "));
  DEBUG_PRINTLN(httpCode);

  if (httpCode != 200)
  {
    modem.https_end();
    return false;
  }

  String body = modem.https_body();
  modem.https_end();

  body.trim();

  DEBUG_PRINTLN(F("🔎 OTA: version.txt primit:"));
  DEBUG_PRINTLN(body);

  int pos = body.indexOf("ESP32 version");

  if (pos < 0)
  {
    DEBUG_PRINTLN(F("❌ OTA: nu am găsit linia ESP32 version."));
    return false;
  }

  int lineEnd = body.indexOf('\n', pos);
  String line;

  if (lineEnd >= 0)
    line = body.substring(pos, lineEnd);
  else
    line = body.substring(pos);

  line.trim();

  serverVersion = line;
  serverVersion.replace("ESP32 version", "");
  serverVersion.trim();

  DEBUG_PRINT(F("🔎 OTA: versiune ESP32 server = ["));
  DEBUG_PRINT(serverVersion);
  DEBUG_PRINTLN(F("]"));

  return serverVersion.length() > 0;
}

String otaSha256ToString(const uint8_t hash[32])
{
  const char hexChars[] = "0123456789abcdef";
  String out;
  out.reserve(64);

  for (int i = 0; i < 32; i++)
  {
    out += hexChars[(hash[i] >> 4) & 0x0F];
    out += hexChars[hash[i] & 0x0F];
  }

  return out;
}

bool otaGetExpectedSha256(String &expectedHash)
{
  expectedHash = "";

  if (!isInternet())
  {
    DEBUG_PRINTLN(F("❌ OTA SHA: internet indisponibil."));
    return false;
  }

  DEBUG_PRINTLN(F("🔐 OTA SHA: citesc firmware.sha256 prin HTTPS..."));

  String url = String("https://") + OTA_HOST + OTA_SHA256_PATH;

  modem.https_begin();
  modem.https_set_url(url.c_str());

  int httpCode = modem.https_get();

  DEBUG_PRINT(F("🔐 OTA SHA HTTP code = "));
  DEBUG_PRINTLN(httpCode);

  if (httpCode != 200)
  {
    modem.https_end();
    return false;
  }

  expectedHash = modem.https_body();
  modem.https_end();

  expectedHash.trim();
  expectedHash.toLowerCase();

  int sp = expectedHash.indexOf(' ');
  if (sp > 0)
  {
    expectedHash = expectedHash.substring(0, sp);
    expectedHash.trim();
  }

  DEBUG_PRINT(F("🔐 OTA SHA expected = "));
  DEBUG_PRINTLN(expectedHash);

  if (expectedHash.length() != 64)
  {
    DEBUG_PRINTLN(F("❌ OTA SHA: hash invalid, trebuie 64 caractere."));
    return false;
  }

  return true;
}

bool otaDownloadAndFlash(const String &expectedHash)
{
  if (!isInternet())
  {
    DEBUG_PRINTLN(F("❌ OTA: internet indisponibil la download."));
    return false;
  }

  DEBUG_PRINTLN(F("⬇️ OTA: descarc firmware prin HTTP stream..."));

  otaHttpClient.stop();
  otaHttpClient.setTimeout(30000);



  DEBUG_PRINTLN(F("⬇️ OTA: TCP connect către server HTTP..."));

  otaHttpClient.stop();
  otaHttpClient.setTimeout(10000);

  bool connected = otaHttpClient.connect(OTA_HOST, OTA_HTTP_PORT);

  DEBUG_PRINT(F("⬇️ OTA: TCP connect result = "));
  DEBUG_PRINTLN(connected ? F("OK") : F("FAIL"));

  if (!connected)
  {
    DEBUG_PRINTLN(F("❌ OTA: conectare HTTP firmware eșuată."));
    otaHttpClient.stop();
    return false;
  }

  String req;


  req  = "GET ";
  req += OTA_BIN_PATH;
  req += " HTTP/1.0\r\n";
  req += "Host: ";
  req += OTA_HOST;
  req += "\r\n";
  req += "User-Agent: ESP32-A7670G-OTA\r\n";
  req += "Accept: */*\r\n";
  req += "Connection: close\r\n\r\n";

  DEBUG_PRINTLN(F("⬇️ OTA: trimit request HTTP..."));
  otaHttpClient.print(req);

  DEBUG_PRINT(F("⬇️ OTA: request bytes = "));
  DEBUG_PRINTLN(req.length());

  delay(500);

  unsigned long waitStart = millis();

  while (!otaHttpClient.available() && otaHttpClient.connected() && millis() - waitStart < 30000UL)
  {
    delay(50);
    yield();
    vTaskDelay(1);
  }

  if (!otaHttpClient.available())
  {
    DEBUG_PRINTLN(F("❌ OTA: serverul nu a trimis răspuns HTTP în 30 sec."));
    otaHttpClient.stop();
    return false;
  }

  String statusLine = otaHttpClient.readStringUntil('\n');
  statusLine.trim();





  DEBUG_PRINT(F("⬇️ OTA: status line = "));
  DEBUG_PRINTLN(statusLine);

  int statusCode = -1;

  if (statusLine.startsWith("HTTP/1.1 "))
    statusCode = statusLine.substring(9, 12).toInt();
  else if (statusLine.startsWith("HTTP/1.0 "))
    statusCode = statusLine.substring(9, 12).toInt();

  if (statusCode != 200)
  {
    DEBUG_PRINT(F("❌ OTA: HTTP status firmware = "));
    DEBUG_PRINTLN(statusCode);
    otaHttpClient.stop();
    return false;
  }

  int contentLength = -1;

  while (otaHttpClient.connected() || otaHttpClient.available())
  {
    if (!otaHttpClient.available())
    {
      delay(10);
      yield();
      continue;
    }

    String line = otaHttpClient.readStringUntil('\n');
    line.trim();

    if (line.length() == 0)
      break;

    String lower = line;
    lower.toLowerCase();

    if (lower.startsWith("content-length:"))
    {
      contentLength = line.substring(15).toInt();
    }
  }

  DEBUG_PRINT(F("⬇️ OTA: Content-Length = "));
  DEBUG_PRINTLN(contentLength);

  if (contentLength < 100000)
  {
    DEBUG_PRINTLN(F("❌ OTA: firmware prea mic / invalid."));
    otaHttpClient.stop();
    return false;
  }

  if (!Update.begin(contentLength))
  {
    DEBUG_PRINT(F("❌ OTA: Update.begin failed. Error="));
    DEBUG_PRINTLN(Update.getError());
    otaHttpClient.stop();
    return false;
  }

  

  mbedtls_sha256_context shaCtx;
  mbedtls_sha256_init(&shaCtx);
  mbedtls_sha256_starts(&shaCtx, 0);

  uint8_t buffer[1024];
  size_t written = 0;
  unsigned long lastDataMs = millis();
  unsigned long lastProgressMs = millis();

  while (written < (size_t)contentLength)
  {
    int availableBytes = otaHttpClient.available();

    if (availableBytes > 0)
    {
      int toRead = availableBytes;

      if (toRead > (int)sizeof(buffer))
        toRead = sizeof(buffer);

      if ((size_t)toRead > ((size_t)contentLength - written))
        toRead = contentLength - written;

      int readLen = otaHttpClient.read(buffer, toRead);

      if (readLen > 0)
      {
        mbedtls_sha256_update(&shaCtx, buffer, readLen);

        size_t w = Update.write(buffer, readLen);

        if (w != (size_t)readLen)
        {
          DEBUG_PRINTLN(F("❌ OTA: Update.write incomplet."));
          Update.abort();
          otaHttpClient.stop();
          mbedtls_sha256_free(&shaCtx);
          return false;
        }

        written += w;
        lastDataMs = millis();

        if (millis() - lastProgressMs > 5000UL)
        {
          DEBUG_PRINT(F("⬇️ OTA progress = "));
          DEBUG_PRINT(written);
          DEBUG_PRINT(F(" / "));
          DEBUG_PRINTLN(contentLength);
          lastProgressMs = millis();
        }

        yield();
        vTaskDelay(1);
      }
    }
    else
    {
      if (millis() - lastDataMs > 30000UL)
      {
        DEBUG_PRINTLN(F("❌ OTA: timeout download firmware."));
        Update.abort();
        otaHttpClient.stop();
        mbedtls_sha256_free(&shaCtx);
        return false;
      }

      delay(5);
      yield();
      vTaskDelay(1);
    }
  }

  uint8_t hash[32];
  mbedtls_sha256_finish(&shaCtx, hash);
  mbedtls_sha256_free(&shaCtx);

  String calculatedHash = otaSha256ToString(hash);

  DEBUG_PRINT(F("⬇️ OTA: written = "));
  DEBUG_PRINTLN(written);

  DEBUG_PRINT(F("🔐 OTA SHA calculated = "));
  DEBUG_PRINTLN(calculatedHash);

  DEBUG_PRINT(F("🔐 OTA SHA expected   = "));
  DEBUG_PRINTLN(expectedHash);

  if (written != (size_t)contentLength)
  {
    DEBUG_PRINTLN(F("❌ OTA: dimensiune scrisă diferită."));
    Update.abort();
    otaHttpClient.stop();
    return false;
  }

  if (calculatedHash != expectedHash)
  {
    DEBUG_PRINTLN(F("❌ OTA: SHA256 diferit! Abort update."));
    Update.abort();
    otaHttpClient.stop();
    return false;
  }

  DEBUG_PRINTLN(F("✅ OTA: SHA256 OK."));





  if (!Update.end())
  {
    DEBUG_PRINT(F("❌ OTA: Update.end failed. Error="));
    DEBUG_PRINTLN(Update.getError());
    otaHttpClient.stop();
    return false;
  }

  if (!Update.isFinished())
  {
    DEBUG_PRINTLN(F("❌ OTA: update neterminat."));
    otaHttpClient.stop();
    return false;
  }

  otaHttpClient.stop();

  DEBUG_PRINTLN(F("✅ OTA: update reușit. Restart..."));
  delay(1000);
  ESP.restart();

  return true;
}


bool otaCheckAndUpdate()
{
  DEBUG_PRINTLN(F("🔎 OTA: check start"));

  if (bufferCount > 0)
  {
    DEBUG_PRINTLN(F("⏳ OTA: buffer ESP-NOW nu este gol, sar peste OTA."));
    return false;
  }

  if (netBusy)
  {
    DEBUG_PRINTLN(F("⏳ OTA: netBusy activ, sar peste OTA."));
    return false;
  }

  float vbat = readVBAT_ADC();

  DEBUG_PRINT(F("🔋 OTA: VBAT = "));
  DEBUG_PRINTLN(vbat, 3);

  if (vbat > 0.1f && vbat < OTA_MIN_VBAT)
  {
    DEBUG_PRINTLN(F("❌ OTA: baterie prea mică."));
    return false;
  }

  if (!modemReady || !modem.isGprsConnected())
  {
    DEBUG_PRINTLN(F("❌ OTA: modem/GPRS indisponibil."));
    return false;
  }

  String serverVersion = "";

  netBusy = true;

  bool okVersion = otaGetServerVersion(serverVersion);

  if (!okVersion)
  {
    netBusy = false;
    return false;
  }

  DEBUG_PRINT(F("🔎 OTA: versiune curentă = "));
  DEBUG_PRINTLN(FW_VERSION);

  if (serverVersion == FW_VERSION)
  {
    DEBUG_PRINTLN(F("✅ OTA: firmware deja la zi."));
    netBusy = false;
    return true;
  }

  DEBUG_PRINTLN(F("🚀 OTA: versiune diferită, citesc SHA256..."));

  String expectedHash;

  if (!otaGetExpectedSha256(expectedHash))
  {
    DEBUG_PRINTLN(F("❌ OTA: nu pot citi SHA256, opresc update-ul."));
    netBusy = false;
    return false;
  }

  DEBUG_PRINTLN(F("🚀 OTA: SHA256 citit, încep update..."));

  bool okFlash = otaDownloadAndFlash(expectedHash);

  netBusy = false;

  return okFlash;
  
}

#endif


bool connectTcpHost(const char* host, int port) {
  for (int i = 0; i < 3; i++) {
    gsmClient.stop();
    delay(80);

    DEBUG_PRINT(F("🔗 TCP connect attempt "));
    DEBUG_PRINTLN(i + 1);

    if (gsmClient.connect(host, port)) {
      DEBUG_PRINTLN(F("✅ TCP connected"));
      return true;
    }

    DEBUG_PRINTLN(F("⚠️ TCP connect fail, retry..."));
    delay(500);
  }

  DEBUG_PRINTLN(F("❌ TCP connect failed (all retries)"));
  return false;
}


bool sendToPHP(const String &jsonPayload)
{
  DEBUG_PRINTLN(F("📨 Trimitere catre PHP prin 4G:"));
  DEBUG_PRINTLN(jsonPayload);

  const char* host = "soul2soul.ro";
  const int   port = 80;
  //const char* path = "/MPDashboard/submit.php";
  const char* path = "/submit.php";
  
  if (!isInternet())
  {
    DEBUG_PRINTLN(F("❌ 4G nu este disponibil."));
    lastPhpFail = PHP_FAIL_NO_NET;
    return false;
  }

  netBusy = true;
  while (gsmClient.available()) (void)gsmClient.read();

  if (!connectTcpHost(host, port))
  {
    DEBUG_PRINTLN(F("❌ Eroare conectare TCP către PHP server."));
    lastPhpFail = PHP_FAIL_CONNECT;
    netBusy = false;
    return false;
  }

  gsmClient.setTimeout(15000);

  String req;
  req.reserve(200 + jsonPayload.length());

  req  = "POST ";
  req += path;
  req += " HTTP/1.1\r\n";
  req += "Host: " + String(host) + "\r\n";
  req += "User-Agent: ESP32-A7670G\r\n";
  req += "Content-Type: application/json\r\n";
  req += "Connection: close\r\n";
  req += "Content-Length: " + String(jsonPayload.length()) + "\r\n\r\n";
  req += jsonPayload;

  size_t n = gsmClient.print(req);

  if (n == 0) {
    DEBUG_PRINTLN(F("PHP: write returned 0"));
    gsmClient.stop();
    netBusy = false;
    lastPhpFail = PHP_FAIL_CLOSED;
    return false;
  }

  unsigned long start = millis();
  while (!gsmClient.available() && gsmClient.connected() && millis() - start < 15000UL)
  {
    delay(20);
  }

  if (!gsmClient.available())
  {
    DEBUG_PRINTLN(F("PHP: no HTTP response"));
    gsmClient.stop();
    netBusy = false;
    lastPhpFail = PHP_FAIL_TIMEOUT;
    return false;
  }

  String statusLine = gsmClient.readStringUntil('\n');
  statusLine.trim();

  int code = 0;
  int sp1 = statusLine.indexOf(' ');
  if (sp1 >= 0)
  {
    int sp2 = statusLine.indexOf(' ', sp1 + 1);
    if (sp2 < 0) sp2 = statusLine.length();
    code = statusLine.substring(sp1 + 1, sp2).toInt();
  }

  while (gsmClient.available()) (void)gsmClient.read();
  gsmClient.stop();
  netBusy = false;

  DEBUG_PRINT(F("PHP: HTTP status="));
  DEBUG_PRINTLN(code);

  if (code >= 200 && code < 400)
  {
    lastPhpFail = PHP_FAIL_NONE;
    return true;
  }

  lastPhpFail = PHP_FAIL_STATUS;
  return false;
}

bool sendTelemetryToPHP(float vbat, float vsolar)
{
  const char* host = "soul2soul.ro";
  const int   port = 80;
  //const char* path = "/MPDashboard/telemetry.php";
  const char* path = "/telemetry.php";

  if (!isInternet()) return false;

  String payload;
  payload.reserve(260);
  payload  = "{";
  payload += "\"type\":\"telemetry\",";
  payload += "\"controllerID\":" + String(getEsp32ChipId()) + ",";
  payload += "\"ts\":" + String((uint32_t)time(nullptr)) + ",";
  payload += "\"vbat\":" + String(vbat, 3) + ",";
  payload += "\"vsolar\":" + String(vsolar, 3) + ",";
  payload += "\"ip\":\"" + modem.localIP().toString() + "\",";

  if (gpsHasFix)
  {
    payload += "\"lat\":" + String(lastGpsLat, 6) + ",";
    payload += "\"long\":" + String(lastGpsLon, 6);
  }
  else
  {
    payload += "\"lat\":null,";
    payload += "\"long\":null";
  }

  payload += "}";

  DEBUG_PRINTLN(F("📡 TELEMETRY -> PHP: /telemetry.php"));
  DEBUG_PRINTLN(payload);

  netBusy = true;

  if (!connectTcpHost(host, port))
  {
    DEBUG_PRINTLN(F("❌ TELEMETRY: TCP connect fail"));
    netBusy = false;
    return false;
  }

  String req;
  req.reserve(240 + payload.length());
  req  = "POST ";
  req += path;
  req += " HTTP/1.1\r\n";
  req += "Host: " + String(host) + "\r\n";
  req += "User-Agent: ESP32-A7670G\r\n";
  req += "Content-Type: application/json\r\n";
  req += "Connection: close\r\n";
  req += "Content-Length: " + String(payload.length()) + "\r\n\r\n";
  req += payload;

  size_t n = gsmClient.print(req);

  if (n == 0)
  {
    DEBUG_PRINTLN(F("TELEMETRY: write returned 0"));
    gsmClient.stop();
    netBusy = false;
    return false;
  }

  unsigned long start = millis();
  while (!gsmClient.available() && gsmClient.connected() && millis() - start < 10000UL)
  {
    delay(20);
  }

  if (!gsmClient.available())
  {
    DEBUG_PRINTLN(F("TELEMETRY: no HTTP response"));
    gsmClient.stop();
    netBusy = false;
    return false;
  }

  String statusLine = gsmClient.readStringUntil('\n');
  statusLine.trim();

  int code = 0;
  int sp1 = statusLine.indexOf(' ');
  if (sp1 >= 0)
  {
    int sp2 = statusLine.indexOf(' ', sp1 + 1);
    if (sp2 < 0) sp2 = statusLine.length();
    code = statusLine.substring(sp1 + 1, sp2).toInt();
  }

  while (gsmClient.available()) (void)gsmClient.read();
  gsmClient.stop();
  netBusy = false;

  DEBUG_PRINT(F("TELEMETRY: HTTP status="));
  DEBUG_PRINTLN(code);

  return (code >= 200 && code < 400);
}

// ===================== PHP Sender =====================
String buildPhpJsonFromBatch(const ESPnowMessage* buf, const uint32_t* receivedTs, int count)
{
  String js = "[";

  for (int i = 0; i < count; i++)
  {
    uint32_t rowTs = (receivedTs && receivedTs[i] > 1000000000UL)
                       ? receivedTs[i]
                       : (uint32_t)time(nullptr);

    js += "{";
    js += "\"chipID\":" + String(buf[i].chipID) + ",";
    js += "\"controllerID\":" + String(getEsp32ChipId()) + ",";
    js += "\"weight\":" + String(buf[i].weight, 2) + ",";
    js += "\"temperature\":" + String(buf[i].temperature, 2) + ",";
    js += "\"battery\":" + String(buf[i].battery, 2) + ",";
    js += "\"wifiSignal\":" + String(buf[i].wifiSignal) + ",";
    js += "\"ziNoapte\":\"" + String(buf[i].ziNoapte) + "\",";
    js += "\"firmwareVersion\":\"" + String(buf[i].firmwareVersion) + "\",";
    js += "\"slaveMac\":\"" + String(buf[i].slaveMac) + "\",";
    js += "\"posibilROI\":" + String(buf[i].posibilROI ? "true" : "false") + ",";
    js += "\"lastUpdated\":" + String(rowTs) + ",";

    if (gpsHasFix)
    {
      js += "\"lat\":" + String(lastGpsLat, 6) + ",";
      js += "\"long\":" + String(lastGpsLon, 6);
    }
    else
    {
      js += "\"lat\":null,";
      js += "\"long\":null";
    }

    js += "}";

    if (i < count - 1) js += ",";
  }

  js += "]";
  return js;
}

String buildGoogleJsonFromBatch(const ESPnowMessage* buf, int count)
{
  char dateStr[16], timeStr[16], isoStr[32];
  getFormattedTime(dateStr, sizeof(dateStr),
                   timeStr, sizeof(timeStr),
                   isoStr,  sizeof(isoStr));

  String localIP = modem.localIP().toString();

  String jsonPayload =
    "{\"command\":\"insert_batch\",\"sheet_name\":\"Date\",\"batch_mode\":\"append\",\"rows\":[";

  for (int i = 0; i < count; i++)
  {
    char row[260];
    snprintf(row, sizeof(row),
             "\"%s,%s,%.2f,%.2f,%u,%.2f,%d,%s,%s,%s,%s,%s\"",
             dateStr,
             timeStr,
             buf[i].weight,
             buf[i].temperature,
             buf[i].chipID,
             buf[i].battery,
             buf[i].wifiSignal,
             localIP.c_str(),
             buf[i].slaveMac,
             buf[i].ziNoapte,
             buf[i].firmwareVersion,
             isoStr);

    jsonPayload += row;
    if (i < count - 1) jsonPayload += ",";
  }

  jsonPayload += "]}";
  return jsonPayload;
}

int parseHttpStatusFromHeader(const String& hdr)
{
  int p = hdr.indexOf("HTTP/");
  if (p < 0) return 0;

  int sp1 = hdr.indexOf(' ', p);
  if (sp1 < 0) return 0;

  int sp2 = hdr.indexOf(' ', sp1 + 1);
  if (sp2 < 0) sp2 = hdr.indexOf('\r', sp1 + 1);
  if (sp2 < 0) return 0;

  return hdr.substring(sp1 + 1, sp2).toInt();
}

bool sendToGoogleSheets_DirectHTTPS(const String& googleJsonPayload)
{
  if (!isInternet()) return false;

  if (!modem.https_begin())
  {
    DEBUG_PRINTLN(F("❌ https_begin() fail"));
    return false;
  }

  modem.https_set_timeout(60, 60, 60);
  modem.https_set_content_type("application/json");
  modem.https_set_accept_type("application/json");

  String url = String("https://script.google.com/macros/s/") + GScriptId + "/exec?user_content=true";

  if (!modem.https_set_url(url))
  {
    DEBUG_PRINTLN(F("❌ https_set_url() fail"));
    modem.https_end();
    return false;
  }

  int rc = modem.https_post_json_format(googleJsonPayload);
  (void)rc;

  String hdr = modem.https_header();
  int status = parseHttpStatusFromHeader(hdr);

  DEBUG_PRINT(F("🌐 Google DIRECT status="));
  DEBUG_PRINTLN(status);

  bool ok = (status == 200 || status == 302);

  modem.https_end();
  return ok;
}

bool sendToGoogleSheets_ProxyPHP(const String& googleJsonPayload)
{
  const char* host = "soul2soul.ro";
  const int   port = 80;
  //const char* path = "/MPDashboard/google_proxy.php";
  const char* path = "/google_proxy.php";

  if (!isInternet()) return false;

  netBusy = true;
  while (gsmClient.available()) (void)gsmClient.read();

  if (!connectTcpHost(host, port)) {
    DEBUG_PRINTLN(F("❌ Proxy TCP connect fail"));
    netBusy = false;
    return false;
  }

  String req;
  req.reserve(220 + googleJsonPayload.length());
  req  = "POST ";
  req += path;
  req += " HTTP/1.1\r\n";
  req += "Host: " + String(host) + "\r\n";
  req += "User-Agent: ESP32-A7670G\r\n";
  req += "Content-Type: application/json\r\n";
  req += "Connection: close\r\n";
  req += "Content-Length: " + String(googleJsonPayload.length()) + "\r\n\r\n";
  req += googleJsonPayload;

  size_t n = gsmClient.print(req);
  delay(250);

  gsmClient.stop();
  netBusy = false;

  if (n == 0) {
    DEBUG_PRINTLN(F("⚠️ Proxy: write returned 0 (treat as OK - optimistic)"));
    return true;
  }

  DEBUG_PRINTLN(F("✅ Proxy: SENT (no-wait)"));
  return true;
}

bool sendToGoogleSheets_4G(const String& googleJsonPayload)
{
#if ENABLE_GOOGLE_SHEETS
  DEBUG_PRINTLN(F("➡️ Google: încerc PROXY PHP..."));
  if (sendToGoogleSheets_ProxyPHP(googleJsonPayload))
  {
    DEBUG_PRINTLN(F("✅ Google: PROXY OK"));
    return true;
  }

  DEBUG_PRINTLN(F("➡️ Google: încerc DIRECT HTTPS..."));
  if (sendToGoogleSheets_DirectHTTPS(googleJsonPayload))
  {
    DEBUG_PRINTLN(F("✅ Google: DIRECT OK (200/302)"));
    return true;
  }

  DEBUG_PRINTLN(F("❌ Google: ambele au eșuat"));
  return false;
#else
  (void)googleJsonPayload;
  return true;
#endif
}

void addToPhpPending(const ESPnowMessage* msgs, const uint32_t* receivedTs, int count)
{
  if (!lockMutex(batchMutex, 500)) return;

  for (int i = 0; i < count; i++)
  {
    uint32_t rowTs = (receivedTs && receivedTs[i] > 1000000000UL)
                       ? receivedTs[i]
                       : (uint32_t)time(nullptr);

    if (phpPendingCount < MAX_BUFFER_SIZE)
    {
      phpPending[phpPendingCount] = msgs[i];
      phpPendingReceivedTs[phpPendingCount] = rowTs;
      phpPendingCount++;
    }
    else
    {
      for (int k = 1; k < MAX_BUFFER_SIZE; k++)
      {
        phpPending[k - 1] = phpPending[k];
        phpPendingReceivedTs[k - 1] = phpPendingReceivedTs[k];
      }

      phpPending[MAX_BUFFER_SIZE - 1] = msgs[i];
      phpPendingReceivedTs[MAX_BUFFER_SIZE - 1] = rowTs;
    }
  }

  DEBUG_PRINT(F("📦 addToPhpPending -> phpPendingCount="));
  DEBUG_PRINTLN(phpPendingCount);

  unlockMutex(batchMutex);
}

void addToGooglePending(const ESPnowMessage* msgs, int count)
{
  if (!lockMutex(batchMutex, 500)) return;

  for (int i = 0; i < count; i++)
  {
    if (googlePendingCount < MAX_BUFFER_SIZE)
    {
      googlePending[googlePendingCount++] = msgs[i];
    }
    else
    {
      for (int k = 1; k < MAX_BUFFER_SIZE; k++)
        googlePending[k - 1] = googlePending[k];

      googlePending[MAX_BUFFER_SIZE - 1] = msgs[i];
    }
  }

  unlockMutex(batchMutex);
}

bool sendData(const ESPnowMessage* localBuf, const uint32_t* localReceivedTs, int localCount)
{
  String phpPayload = buildPhpJsonFromBatch(localBuf, localReceivedTs, localCount);

#if ENABLE_GOOGLE_SHEETS
  String googlePayload = buildGoogleJsonFromBatch(localBuf, localCount);
#endif

  bool phpOk = true;

  if (phpEnabled) {
    phpOk = sendToPHP(phpPayload);
  } else {
    phpOk = false;
    DEBUG_PRINTLN(F("⛔ PHP disabled -> skip submit.php"));
  }

#if ENABLE_GOOGLE_SHEETS
  bool gOk = sendToGoogleSheets_4G(googlePayload);
#else
  bool gOk = true;
#endif

  if (phpOk)
  {
#if ENABLE_GOOGLE_SHEETS
    if (!gOk)
    {
      DEBUG_PRINTLN(F("⚠️ Google picat -> bag in googlePending"));
      addToGooglePending(localBuf, localCount);
    }
#endif
    return true;
  }

#if ENABLE_GOOGLE_SHEETS
  if (gOk)
  {
    DEBUG_PRINTLN(F("⚠️ PHP picat, dar Google OK -> bag in phpPending"));
    addToPhpPending(localBuf, localReceivedTs, localCount);
    return true;
  }
#endif

  return false;
}

void trySendPhpPending() {
  int localCount = 0;
  static ESPnowMessage localCopy[MAX_BUFFER_SIZE];
  static uint32_t localReceivedTs[MAX_BUFFER_SIZE];

  if (!phpEnabled) return;

  if (!lockMutex(batchMutex, 200)) return;
  if (phpPendingCount == 0) {
    unlockMutex(batchMutex);
    return;
  }
  localCount = phpPendingCount;
  for (int i = 0; i < localCount; i++)
  {
    localCopy[i] = phpPending[i];
    localReceivedTs[i] = phpPendingReceivedTs[i];
  }
  unlockMutex(batchMutex);

  unsigned long now = millis();
  if (phpNextTry != 0 && now < phpNextTry) return;

  DEBUG_PRINT(F("📦 phpPendingCount="));
  DEBUG_PRINTLN(localCount);

  if (!isInternet()) {
    phpRetryDelay = min(phpRetryDelayMax, phpRetryDelay * 2);
    phpNextTry = now + phpRetryDelay;
    return;
  }

  String payload = buildPhpJsonFromBatch(localCopy, localReceivedTs, localCount);
  if (sendToPHP(payload)) {
    DEBUG_PRINTLN(F("✅ phpPending trimis OK"));
    if (lockMutex(batchMutex, 300))
    {
      phpPendingCount = 0;
      unlockMutex(batchMutex);
    }
    phpRetryDelay = 60000UL;
    phpNextTry = 0;
  } else {
    DEBUG_PRINTLN(F("❌ phpPending fail, retry cu backoff"));
    phpRetryDelay = min(phpRetryDelayMax, phpRetryDelay * 2);
    phpNextTry = now + phpRetryDelay;

    if (lastPhpFail == PHP_FAIL_CONNECT) {
      phpDisableNow(F("phpPending connect fail"));
    } else {
      DEBUG_PRINTLN(F("ℹ️ phpPending: nu dezactivez PHP (timeout/status/etc)"));
    }
  }
}

void trySendGooglePending()
{
#if ENABLE_GOOGLE_SHEETS
  int localCount = 0;
  static ESPnowMessage localCopy[MAX_BUFFER_SIZE];

  if (!lockMutex(batchMutex, 200)) return;
  if (googlePendingCount == 0) {
    unlockMutex(batchMutex);
    return;
  }
  localCount = googlePendingCount;
  for (int i = 0; i < localCount; i++) localCopy[i] = googlePending[i];
  unlockMutex(batchMutex);

  unsigned long now = millis();

  if (now < googleDisabledUntil) return;
  if (googleNextTry != 0 && now < googleNextTry) return;

  if (!isInternet())
  {
    googleRetryDelay = min(googleRetryDelayMax, googleRetryDelay * 2);
    googleNextTry = now + googleRetryDelay;
    return;
  }

  String payload = buildGoogleJsonFromBatch(localCopy, localCount);
  bool ok = sendToGoogleSheets_4G(payload);

  if (ok)
  {
    DEBUG_PRINTLN(F("✅ googlePending trimis OK"));
    if (lockMutex(batchMutex, 300))
    {
      googlePendingCount = 0;
      unlockMutex(batchMutex);
    }
    googleRetryDelay = 60000UL;
    googleNextTry = 0;
    return;
  }

  DEBUG_PRINTLN(F("❌ googlePending fail, backoff"));
  googleRetryDelay = min(googleRetryDelayMax, googleRetryDelay * 2);
  googleNextTry = now + googleRetryDelay;
#else
  return;
#endif
}

static void setTimezoneRomania()
{
  setenv("TZ", "EET-2EEST,M3.5.0/3,M10.5.0/4", 1);
  tzset();
  DEBUG_PRINTLN(F("🕒 TZ set: Romania (EET/EEST)"));
}

bool sendToPHPPath(const char* path, const String &jsonPayload)
{
  const char* host = "soul2soul.ro";
  const int port = 80;

  if (!isInternet()) return false;

  if (!connectTcpHost(host, port)) {
    DEBUG_PRINTLN(F("❌ Eroare conectare TCP către PHP server."));
    return false;
  }

  String req;
  req  = "POST ";
  req += path;
  req += " HTTP/1.1\r\n";
  req += "Host: " + String(host) + "\r\n";
  req += "User-Agent: ESP32-A7670G\r\n";
  req += "Content-Type: application/json\r\n";
  req += "Connection: close\r\n";
  req += "Content-Length: " + String(jsonPayload.length()) + "\r\n\r\n";
  req += jsonPayload;

  gsmClient.print(req);

  unsigned long t0 = millis();
  while (!gsmClient.available())
  {
    if (!gsmClient.connected())
    {
      gsmClient.stop();
      return false;
    }

    if (millis() - t0 > 30000)
    {
      gsmClient.stop();
      return false;
    }

    delay(10);
  }

  String statusLine = gsmClient.readStringUntil('\n');
  statusLine.trim();

  int code = 0;
  int sp1 = statusLine.indexOf(' ');
  if (sp1 > 0)
  {
    int sp2 = statusLine.indexOf(' ', sp1 + 1);
    if (sp2 < 0) sp2 = statusLine.length();
    code = statusLine.substring(sp1 + 1, sp2).toInt();
  }

  while (gsmClient.connected())
  {
    String h = gsmClient.readStringUntil('\n');
    if (h == "\r" || h.length() == 0) break;
  }

  gsmClient.stop();
  return (code >= 200 && code < 300);
}

// ===================== GPS =====================
static bool parseNmeaLatLon(const String& latStr, const String& ns,
                            const String& lonStr, const String& ew,
                            double &lat, double &lon)
{
  if (latStr.length() < 4 || lonStr.length() < 5) return false;

  double vlat = latStr.toFloat();
  int degLat = (int)(vlat / 100);
  double minLat = vlat - degLat * 100;
  lat = degLat + (minLat / 60.0);
  if (ns == "S") lat = -lat;

  double vlon = lonStr.toFloat();
  int degLon = (int)(vlon / 100);
  double minLon = vlon - degLon * 100;
  lon = degLon + (minLon / 60.0);
  if (ew == "W") lon = -lon;

  if (lat < -90 || lat > 90 || lon < -180 || lon > 180) return false;
  if (lat == 0 || lon == 0) return false;

  return true;
}

bool readGPS_FromNMEA(double &lat, double &lon, int &sats, int &fixQuality, float &hdop, uint32_t timeoutMs = 1200)
{
  lat = 0;
  lon = 0;
  sats = 0;
  fixQuality = 0;
  hdop = 99.0f;

  String line;
  uint32_t t0 = millis();

  while (millis() - t0 < timeoutMs)
  {
    if (!SerialGPS.available()) { delay(2); continue; }

    line = SerialGPS.readStringUntil('\n');
    line.trim();

    if (!line.startsWith("$GNGGA") && !line.startsWith("$GPGGA")) continue;

    const int MAX = 20;
    String tok[MAX];
    int n = 0, i = 0;

    while (n < MAX)
    {
      int c = line.indexOf(',', i);
      if (c < 0) { tok[n++] = line.substring(i); break; }
      tok[n++] = line.substring(i, c);
      i = c + 1;
    }

    for (int k = 0; k < n; k++) tok[k].trim();

    if (n < 9) continue;

    fixQuality = tok[6].toInt();
    sats       = tok[7].toInt();
    hdop       = tok[8].toFloat();

    if (fixQuality <= 0) return false;
    if (!parseNmeaLatLon(tok[2], tok[3], tok[4], tok[5], lat, lon)) return false;

    return true;
  }

  return false;
}

static bool isNightNow(float vsolar)
{
  return (vsolar <= SOLAR_NIGHT_TH);
}

static double haversineMeters(double lat1, double lon1, double lat2, double lon2)
{
  const double R = 6371000.0;
  double p1 = lat1 * M_PI / 180.0;
  double p2 = lat2 * M_PI / 180.0;
  double dp = (lat2 - lat1) * M_PI / 180.0;
  double dl = (lon2 - lon1) * M_PI / 180.0;
  double a = sin(dp/2) * sin(dp/2) + cos(p1) * cos(p2) * sin(dl/2) * sin(dl/2);
  double c = 2.0 * atan2(sqrt(a), sqrt(1.0 - a));
  return R * c;
}

static void gpsOn()
{
  if (gpsIsOn) return;
  gpsIsOn = true;

  pinMode(GPS_WAKEUP_PIN, OUTPUT);
  digitalWrite(GPS_WAKEUP_PIN, HIGH);
  delay(80);
  SerialGPS.begin(GPS_BAUD, SERIAL_8N1, GPS_RX_PIN, GPS_TX_PIN);
}

static void gpsOff()
{
  if (!gpsIsOn) return;
  gpsIsOn = false;

  SerialGPS.end();
  pinMode(GPS_WAKEUP_PIN, OUTPUT);
  digitalWrite(GPS_WAKEUP_PIN, LOW);
}

static void enterNightDeepSleep(uint32_t minutes)
{
  Serial.println("🌙 Night -> deep sleep (HARD CUT)");

  Serial.println("🛰️ GPS OFF (WAKE=LOW + HOLD)");
  pinMode(GPS_WAKEUP_PIN, OUTPUT);
  digitalWrite(GPS_WAKEUP_PIN, LOW);
  gpio_hold_en((gpio_num_t)GPS_WAKEUP_PIN);

  Serial.println("📴 MODEM: try AT+CPOF");
  SerialAT.println("AT+CPOF");
  delay(600);

  Serial.println("📴 MODEM: try modem.poweroff()");
  modem.poweroff();
  delay(600);

  Serial.println("🧷 MODEM RESET HOLD (active level)");
  pinMode(MODEM_RESET_PIN, OUTPUT);
  digitalWrite(MODEM_RESET_PIN, MODEM_RESET_LEVEL);
  gpio_hold_en((gpio_num_t)MODEM_RESET_PIN);

  Serial.println("🔋 BOOST OFF (BOARD_POWERON_PIN=LOW + HOLD)");
  pinMode(BOARD_POWERON_PIN, OUTPUT);
  digitalWrite(BOARD_POWERON_PIN, LOW);
  gpio_hold_en((gpio_num_t)BOARD_POWERON_PIN);

  WiFi.mode(WIFI_OFF);
  btStop();

  gpio_deep_sleep_hold_en();
  delay(200);

  Serial.print("😴 ESP32 -> deep sleep ");
  Serial.print(minutes);
  Serial.println(" min");

  esp_sleep_enable_timer_wakeup((uint64_t)minutes * 60ULL * 1000000ULL);
  esp_deep_sleep_start();
}

static void earlyNightGateOrContinue()
{
  float vbat = readVBAT_ADC();
  float vsolar = readVSOLAR_ADC();

  Serial.print("🌗 Early check: VBAT=");
  Serial.print(vbat, 2);
  Serial.print(" VSOLAR=");
  Serial.print(vsolar, 2);
  Serial.println(" V");

  bool night = isNightNow(vsolar);
  bool sunrise = (rtcLastWasNight == 1 && !night);

  if (night)
  {
    rtcLastWasNight = 1;
    enterNightDeepSleep(NIGHT_WAKE_MINUTES);
  }

  if (rtcLastWasNight == 1) rtcLastWasNight = 0;
  sunriseFlag = sunrise;
}

static void releaseNightHoldsAndPrepPins()
{
  gpio_deep_sleep_hold_dis();

  gpio_hold_dis((gpio_num_t)GPS_WAKEUP_PIN);
  gpio_hold_dis((gpio_num_t)MODEM_RESET_PIN);
  gpio_hold_dis((gpio_num_t)BOARD_POWERON_PIN);

  pinMode(BOARD_POWERON_PIN, OUTPUT);
  digitalWrite(BOARD_POWERON_PIN, HIGH);
  delay(80);

  pinMode(MODEM_RESET_PIN, OUTPUT);
  digitalWrite(MODEM_RESET_PIN, !MODEM_RESET_LEVEL);
  delay(50);

  pinMode(MODEM_DTR_PIN, OUTPUT);
  digitalWrite(MODEM_DTR_PIN, LOW);
  delay(20);

  pinMode(BOARD_PWRKEY_PIN, OUTPUT);
  digitalWrite(BOARD_PWRKEY_PIN, LOW);
  delay(10);
}

float readVSOLAR_ADC()
{
  if (!lockMutex(adcMutex, 500)) return 0.0f;

  analogSetPinAttenuation(SOLAR_ADC_PIN, ADC_11db);
  analogReadResolution(12);

  uint32_t sum = 0;
  const int N = 20;

  for (int i = 0; i < N; i++)
  {
    sum += analogRead(SOLAR_ADC_PIN);
    delay(3);
  }

  unlockMutex(adcMutex);

  float raw = (float)sum / N;
  float v_adc = (raw / 4095.0f) * ADC_REF;
  float vsolar = v_adc * VSOLAR_DIV;
  return vsolar;
}

static bool isStrongSun(float vsolar)
{
  return (vsolar >= VSOLAR_STRONG_SUN);
}

static bool canTurnOn4GNow_Policy(float vbat, float vsolar)
{
  if (isStrongSun(vsolar)) return true;
  if (next4GAllowedAt != 0 && millis() < next4GAllowedAt) return false;
  return (vbat >= VBAT_ALLOW_4G);
}

static bool shouldKeep4GOnAfterSends_Policy(float vbat, float vsolar)
{
  if (isStrongSun(vsolar)) return true;
  if (vbat >= VBAT_ALLOW_4G) return true;
  return false;
}

static void powerOffModemDay()
{
  DEBUG_PRINTLN(F("📴 Day policy -> modem OFF + boost OFF"));

  SerialAT.println("AT+CPOF");
  delay(600);
  modem.poweroff();
  delay(600);

  pinMode(MODEM_RESET_PIN, OUTPUT);
  digitalWrite(MODEM_RESET_PIN, MODEM_RESET_LEVEL);

  pinMode(BOARD_POWERON_PIN, OUTPUT);
  digitalWrite(BOARD_POWERON_PIN, LOW);

  modemReady = false;
}

void check4GHealthAndRecover()
{
  unsigned long now = millis();

  if (now - last4GHealthCheckMs < HEALTH_CHECK_MS)
  {
    return;
  }

  last4GHealthCheckMs = now;

  bool hasUnsent = false;

  if (lockMutex(batchMutex, 100))
  {
    hasUnsent = (bufferCount > 0 || phpPendingCount > 0 || googlePendingCount > 0);
    unlockMutex(batchMutex);
  }

  if (hasUnsent)
  {
    if (firstUnsentDataMs == 0)
    {
      firstUnsentDataMs = now;
    }
  }
  else
  {
    firstUnsentDataMs = 0;
  }

  if (modemReady)
  {
    bool netOk = modem.isNetworkConnected();
    bool gprsOk = modem.isGprsConnected();
    IPAddress ip = modem.localIP();

    if (!netOk || !gprsOk || !ip)
    {
      DEBUG_PRINTLN(F("HEALTH: modemReady=true but 4G invalid -> power cycle modem"));

      modemReady = false;
      modemWanted = true;
      netBusy = false;

      gsmClient.stop();
      otaHttpClient.stop();

      if (now - last4GRecoverMs > MODEM_RECOVER_GAP_MS)
      {
        last4GRecoverMs = now;

        if (lockMutex(modemMutex, 2000))
        {
          powerOffModemDay();
          unlockMutex(modemMutex);
        }
      }

      modemNextTry = now + 10000UL;
      return;
    }
  }

  if (hasUnsent && firstUnsentDataMs != 0 && now - firstUnsentDataMs > UNSENT_RESTART_MS)
  {
    DEBUG_PRINTLN(F("HEALTH: unsent data >30 min -> ESP.restart()"));
    delay(500);
    ESP.restart();
  }

  if (lastNetSuccessMs != 0 && now - lastNetSuccessMs > NET_DEAD_RESTART_MS)
  {
    DEBUG_PRINTLN(F("HEALTH: no network success >30 min -> ESP.restart()"));
    delay(500);
    ESP.restart();
  }
}

static bool is4GCooldownActive()
{
  float vsolar = readVSOLAR_ADC();
  if (isStrongSun(vsolar)) return false;
  return (next4GAllowedAt != 0 && millis() < next4GAllowedAt);
}

static void theftUpdateByDistance(double distM)
{
  if (distM > THEFT_DISTANCE_M)
  {
    if (rtcTheftCandidateCount < 255) rtcTheftCandidateCount++;
    DEBUG_PRINT(F("🚨 Theft candidate ++ -> "));
    DEBUG_PRINTLN((int)rtcTheftCandidateCount);

    if (rtcTheftCandidateCount >= THEFT_CONFIRM_COUNT)
    {
      rtcTheft = 1;
      DEBUG_PRINTLN(F("🚨 THEFT CONFIRMED (3/3) -> rtcTheft=1"));
    }
  }
  else
  {
    if (rtcTheftCandidateCount != 0)
    {
      DEBUG_PRINTLN(F("✅ Dist back under threshold -> reset candidateCount=0"));
    }
    rtcTheftCandidateCount = 0;
    rtcTheft = 0;
  }
}

bool getStableFix(double &lat, double &lon, int &sats, int &fq, float &hdop, uint32_t totalMs)
{
  double lat1 = 0, lon1 = 0, lat2 = 0, lon2 = 0;
  int s1 = 0, q1 = 0, s2 = 0, q2 = 0;
  float h1 = 99, h2 = 99;

  uint32_t t0 = millis();
  bool got1 = false;

#ifdef DEBUG_ENABLED
  uint32_t lastDbgMs = 0;
  uint32_t cntReadOk = 0;
  uint32_t cntNoNmea = 0;
  uint32_t cntBadQt  = 0;
  uint32_t cntBadSat = 0;
  uint32_t cntBadHd  = 0;
  uint32_t cntTooFar = 0;

  double lastLt = 0, lastLn = 0;
  int lastSt = 0, lastQt = 0;
  float lastHd = 99;
#endif

  while (millis() - t0 < totalMs)
  {
    double lt = 0, ln = 0;
    int st = 0, qt = 0;
    float hd = 99;

    bool ok = readGPS_FromNMEA(lt, ln, st, qt, hd, 1500);

#ifdef DEBUG_ENABLED
    if (!ok) cntNoNmea++;
#endif

    if (ok)
    {
#ifdef DEBUG_ENABLED
      cntReadOk++;
      lastLt = lt; lastLn = ln; lastSt = st; lastQt = qt; lastHd = hd;
#endif

      if (qt <= 0) {
#ifdef DEBUG_ENABLED
        cntBadQt++;
#endif
        goto DBG_TICK;
      }

      if (st < GPS_MIN_SATS) {
#ifdef DEBUG_ENABLED
        cntBadSat++;
#endif
        goto DBG_TICK;
      }

      if (hd < 0.1f || hd > GPS_MAX_HDOP) {
#ifdef DEBUG_ENABLED
        cntBadHd++;
#endif
        goto DBG_TICK;
      }

      if (!got1)
      {
        lat1 = lt; lon1 = ln; s1 = st; q1 = qt; h1 = hd;
        got1 = true;

#ifdef DEBUG_ENABLED
        DEBUG_PRINT(F("🛰️ getStableFix: got first candidate lat="));
        DEBUG_PRINT(lat1, 6);
        DEBUG_PRINT(F(" lon="));
        DEBUG_PRINT(lon1, 6);
        DEBUG_PRINT(F(" sats="));
        DEBUG_PRINT(s1);
        DEBUG_PRINT(F(" q="));
        DEBUG_PRINT(q1);
        DEBUG_PRINT(F(" hdop="));
        DEBUG_PRINTLN(h1, 2);
#endif
      }
      else
      {
        lat2 = lt; lon2 = ln; s2 = st; q2 = qt; h2 = hd;
        double d = haversineMeters(lat1, lon1, lat2, lon2);

#ifdef DEBUG_ENABLED
        DEBUG_PRINT(F("🛰️ candidate2 d="));
        DEBUG_PRINT(d, 1);
        DEBUG_PRINT(F("m | sats="));
        DEBUG_PRINT(s2);
        DEBUG_PRINT(F(" q="));
        DEBUG_PRINT(q2);
        DEBUG_PRINT(F(" hdop="));
        DEBUG_PRINTLN(h2, 2);
#endif

        if (d < 8.0)
        {
#ifdef DEBUG_ENABLED
          DEBUG_PRINTLN(F("✅ getStableFix: STABLE FIX accepted"));
#endif
          lat = lat2; lon = lon2;
          sats = s2; fq = q2; hdop = h2;
          return true;
        }

#ifdef DEBUG_ENABLED
        cntTooFar++;
#endif
        lat1 = lat2; lon1 = lon2; s1 = s2; q1 = q2; h1 = h2;
      }
    }

DBG_TICK:
#ifdef DEBUG_ENABLED
    if (millis() - lastDbgMs >= 1000) {
      lastDbgMs = millis();
      uint32_t elapsed = (millis() - t0) / 1000;

      DEBUG_PRINT(F("🛰️ getStableFix t="));
      DEBUG_PRINT(elapsed);
      DEBUG_PRINT(F("s / total="));
      DEBUG_PRINT(totalMs / 1000);
      DEBUG_PRINT(F("s | last: ok="));
      DEBUG_PRINT(ok ? 1 : 0);
      DEBUG_PRINT(F(" lat="));
      DEBUG_PRINT(lastLt, 6);
      DEBUG_PRINT(F(" lon="));
      DEBUG_PRINT(lastLn, 6);
      DEBUG_PRINT(F(" sats="));
      DEBUG_PRINT(lastSt);
      DEBUG_PRINT(F(" q="));
      DEBUG_PRINT(lastQt);
      DEBUG_PRINT(F(" hdop="));
      DEBUG_PRINT(lastHd, 2);
      DEBUG_PRINT(F(" | rejects qt="));
      DEBUG_PRINT(cntBadQt);
      DEBUG_PRINT(F(" sat="));
      DEBUG_PRINT(cntBadSat);
      DEBUG_PRINT(F(" hd="));
      DEBUG_PRINT(cntBadHd);
      DEBUG_PRINT(F(" far="));
      DEBUG_PRINT(cntTooFar);
      DEBUG_PRINT(F(" nonmea="));
      DEBUG_PRINTLN(cntNoNmea);
    }
#endif
    delay(50);
  }

#ifdef DEBUG_ENABLED
  DEBUG_PRINTLN(F("⚠️ getStableFix: TIMEOUT, no STABLE fix"));
#endif
  return false;
}


// ===================== MQTT =====================
#if ENABLE_MQTT_HA || ENABLE_MQTT_CLOUD

  static String mqttMakeClientId(const char* suffix)
  {
    uint64_t mac = ESP.getEfuseMac();
    char buf[48];
    snprintf(buf, sizeof(buf), "StupinaMaster-%08X%08X-%s",
            (uint32_t)(mac >> 32), (uint32_t)mac, suffix);
    return String(buf);
  }

  static void mqttSetupClientCommon(PubSubClient& client, const MqttRuntime& cfg)
  {
    client.setServer(cfg.host, cfg.port);
    client.setBufferSize(1024);
    client.setKeepAlive(60);
    client.setSocketTimeout(30);
  }

  static bool mqttConnectOnceCommon(PubSubClient& client, MqttRuntime& cfg, const char* clientSuffix, uint32_t timeoutMs = 15000)
  {
    if (!cfg.enabled) return false;
    if (!modemReady) return false;
    if (!isInternet()) return false;

    if (client.connected()) return true;

    const String cid = mqttMakeClientId(clientSuffix);
    uint32_t start = millis();

    while (!client.connected() && (millis() - start) < timeoutMs)
    {
      DEBUG_PRINT(F("📡 MQTT connect ["));
      DEBUG_PRINT(cfg.name);
      DEBUG_PRINT(F("] -> "));
      DEBUG_PRINTLN(cfg.host);

      client.disconnect();
      
      delay(150);

      bool ok;
      if (strlen(cfg.username) > 0) {
        ok = client.connect(cid.c_str(), cfg.username, cfg.password);
      } else {
        ok = client.connect(cid.c_str());
      }

      if (ok)
      {
        DEBUG_PRINT(F("✅ MQTT connected ["));
        DEBUG_PRINT(cfg.name);
        DEBUG_PRINTLN(F("]"));
        cfg.subDone = false;
        return true;
      }

      DEBUG_PRINT(F("⚠️ MQTT connect fail ["));
      DEBUG_PRINT(cfg.name);
      DEBUG_PRINTLN(F("], retry..."));
      delay(700);
    }

    return client.connected();
  }

  static void mqttDisconnectNowCommon(PubSubClient& client, TinyGsmClient& net, MqttRuntime& cfg)
  {
    if (client.connected()) client.disconnect();
    net.stop();
    cfg.subDone = false;
  }

  static bool mqttPublishWithRetryCommon(  PubSubClient& client,  TinyGsmClient& net,  MqttRuntime& cfg,  const char* clientSuffix,  const char* topic,  const String& payload,  bool retain = true)
  {
    if (!cfg.enabled) return false;
    if (!modemReady || !modem.isGprsConnected()) return false;

    mqttSetupClientCommon(client, cfg);

    if (!client.connected()) {
      if (!mqttConnectOnceCommon(client, cfg, clientSuffix, 8000)) {
        DEBUG_PRINT(F("❌ MQTT publish: not connected ["));
        DEBUG_PRINT(cfg.name);
        DEBUG_PRINTLN(F("]"));
        return false;
      }
    }

    bool ok = client.publish(topic, payload.c_str(), retain);
    client.loop();

    if (!ok) {
      DEBUG_PRINT(F("⚠️ MQTT publish failed ["));
      DEBUG_PRINT(cfg.name);
      DEBUG_PRINTLN(F("] -> reconnect + retry once"));

      mqttDisconnectNowCommon(client, net, cfg);
      delay(150);

      if (mqttConnectOnceCommon(client, cfg, clientSuffix, 8000)) {
        ok = client.publish(topic, payload.c_str(), retain);
        client.loop();
      }
    }

    return ok;
  }

#endif


#if ENABLE_MQTT_HA

  static bool mqttHaSubDone = false;
  static unsigned long mqttHaLastConnectTry = 0;
  static const unsigned long MQTT_HA_CONNECT_COOLDOWN_MS = 5000UL;

  static void mqttCallbackHA(char* topic, byte* payload, unsigned int length);

  void mqttSetupClientHA()
  {
    mqttHa.setServer(MQTT_HA_HOST, MQTT_HA_PORT);
    mqttHa.setCallback(mqttCallbackHA);
  }

  bool mqttConnectOnceHA(uint32_t timeoutMs = 4000)
  {
    (void)timeoutMs;

    if (!mqttHaCfg.enabled) return false;
    if (!modemReady) return false;
    if (!isInternet()) return false;
    if (mqttHa.connected()) return true;

    unsigned long now = millis();
    if ((now - mqttHaLastConnectTry) < MQTT_HA_CONNECT_COOLDOWN_MS) {
      return false;
    }
    mqttHaLastConnectTry = now;

    mqttHa.disconnect();
    delay(100);

    String clientId = mqttMakeClientId("HA");

    DEBUG_PRINT(F("📡 MQTT connect [HA] -> "));
    DEBUG_PRINTLN(MQTT_HA_HOST);

    bool ok = mqttHa.connect(clientId.c_str(), MQTT_HA_USERNAME, MQTT_HA_PASSWORD);

    if (ok)
    {
      DEBUG_PRINTLN(F("✅ MQTT connected [HA]"));
      mqttHaSubDone = false;
      return true;
    }

    DEBUG_PRINTLN(F("⚠️ MQTT connect fail [HA]"));
    return false;
  }

  void mqttDisconnectNowHA()
  {
    if (mqttHa.connected()) mqttHa.disconnect();
    mqttHaSubDone = false;
  }

  static void publishStateNowHA()
  {
    if (!mqttHaCfg.enabled) return;
    if (!modemReady || !modem.isGprsConnected()) return;

    if (!mqttHa.connected()) {
      if (!mqttConnectOnceHA(3000)) return;
    }

    String payload;
    payload.reserve(160);
    payload  = "{";
    payload += "\"state\":\"online\"";
    payload += ",\"espnow_channel\":" + String((int)CHANNEL);
    payload += ",\"theft\":" + String((int)rtcTheft);
    payload += ",\"ts\":" + String((uint32_t)time(nullptr));
    payload += "}";

    mqttHa.publish(MQTT_HA_STATE_TOPIC, payload.c_str(), true);
    mqttHa.loop();
  }

  static void mqttEnsureSubscribedHA()
  {
    if (!mqttHaCfg.enabled) return;
    if (!modemReady || !modem.isGprsConnected()) return;

    if (!mqttHa.connected()) {
      if (!mqttConnectOnceHA(3000)) return;
    }

    if (!mqttHaSubDone) {
      bool ok = mqttHa.subscribe(MQTT_HA_CMD_TOPIC, 0);

      DEBUG_PRINT(F("📡 MQTT subscribe cmd [HA]: "));
      DEBUG_PRINTLN(ok ? F("OK") : F("FAIL"));

      mqttHaSubDone = ok;

      if (ok) publishStateNowHA();
    }
  }

  static bool mqttPublishWithRetryHA(const char* topic, const String& payload, bool retain = true)
  {
    if (!mqttHaCfg.enabled) return false;
    if (!modemReady || !modem.isGprsConnected()) return false;

    if (!mqttHa.connected()) {
      if (!mqttConnectOnceHA(3000)) {
        DEBUG_PRINTLN(F("❌ MQTT HA publish: not connected"));
        return false;
      }
      mqttEnsureSubscribedHA();
    }

    bool ok = mqttHa.publish(topic, payload.c_str(), retain);
    mqttHa.loop();

    if (!ok)
    {
      DEBUG_PRINTLN(F("⚠️ MQTT HA publish failed -> reconnect + retry"));
      mqttDisconnectNowHA();
      delay(150);

      if (mqttConnectOnceHA(3000))
      {
        mqttEnsureSubscribedHA();
        ok = mqttHa.publish(topic, payload.c_str(), retain);
        mqttHa.loop();
      }
    }

    return ok;
  }

  static void applyEspNowChannel(uint8_t ch)
  {
    if (ch < 1 || ch > 13) {
      DEBUG_PRINTLN(F("❌ Invalid channel (1..13)"));
      return;
    }

    if (ch == CHANNEL) return;

    DEBUG_PRINT(F("📶 APPLY ESP-NOW CHANNEL -> "));
    DEBUG_PRINTLN(ch);

    CHANNEL = ch;
    rtcEspNowChannel = ch;

    esp_now_deinit();
    delay(100);

    WiFi.softAPdisconnect(true);
    WiFi.disconnect(true, true);
    WiFi.mode(WIFI_OFF);
    delay(250);

    WiFi.mode(WIFI_AP_STA);
    delay(100);

    bool apOK = WiFi.softAP(AP_SSID, AP_PASSWORD, CHANNEL);
    delay(200);

    DEBUG_PRINT(F("✅ softAP start="));
    DEBUG_PRINTLN(apOK ? F("OK") : F("FAIL"));

    DEBUG_PRINT(F("✅ softAP channel (should be new) = "));
    DEBUG_PRINTLN(WiFi.channel());

    wifi_country_t country = { "RO", 1, 13, WIFI_COUNTRY_POLICY_MANUAL };
    esp_wifi_set_country(&country);
    esp_wifi_set_channel(CHANNEL, WIFI_SECOND_CHAN_NONE);
    delay(50);

    if (esp_now_init() == ESP_OK)
    {
      DEBUG_PRINTLN(F("✅ ESPNow Init Success"));
    }
    else
    {
      DEBUG_PRINTLN(F("❌ ESPNow Init Failed"));
      ESP.restart();
    }

    esp_now_register_recv_cb(OnDataRecv);

    DEBUG_PRINTLN(F("✅ ESP-NOW re-init done."));
    publishStateNowHA();
  }

  static void mqttCallbackHA(char* topic, byte* payload, unsigned int length)
  {
    String t = String(topic);
    String s;
    s.reserve(length + 1);
    for (unsigned int i = 0; i < length; i++) s += (char)payload[i];
    s.trim();

    DEBUG_PRINT(F("📩 MQTT RX [HA] topic="));
    DEBUG_PRINT(t);
    DEBUG_PRINT(F(" payload="));
    DEBUG_PRINTLN(s);

    if (t != MQTT_HA_CMD_TOPIC) return;
    if (s.indexOf("\"cmd\"") < 0) return;

    #if ENABLE_OTA_4G
    if (s.indexOf("ota_check") >= 0 || s.indexOf("ota_update") >= 0)
    {
      DEBUG_PRINTLN(F("🔧 CMD ota_update/ota_check received"));
      otaRequested = true;
      modemWanted = true;
      return;
    }
    #endif

    if (s.indexOf("send_telemetry") >= 0 ||
        s.indexOf("telemetry") >= 0 ||
        s.indexOf("send_status") >= 0)
    {
      DEBUG_PRINTLN(F("📡 CMD send_telemetry received"));
      telemetryRequested = true;
      modemWanted = true;
      return;
    }


    if (s.indexOf("set_channel") < 0) return;

    int p = s.indexOf("\"ch\"");
    if (p < 0) return;

    int colon = s.indexOf(':', p);
    if (colon < 0) return;

    int ch = s.substring(colon + 1).toInt();

    DEBUG_PRINT(F("🔧 CMD set_channel received ch="));
    DEBUG_PRINT(ch);
    DEBUG_PRINT(F(" current CHANNEL="));
    DEBUG_PRINTLN(CHANNEL);

    if (ch >= 1 && ch <= 13)
    {
      applyEspNowChannel((uint8_t)ch);
    }
  }

  bool mqttPublishTelemetryHA(float vbat, float vsolar, const __FlashStringHelper* reason)
  {
    if (!mqttHaCfg.enabled) return false;

    String r = String(reason);

  String payload;
  payload.reserve(240);
  payload  = "{";
  payload += "\"type\":\"telemetry\",";
  payload += "\"ts\":" + String((uint32_t)time(nullptr)) + ",";
  payload += "\"vbat\":" + String(vbat, 3) + ",";
  payload += "\"vsolar\":" + String(vsolar, 3) + ",";
  payload += "\"ip\":\"" + modem.localIP().toString() + "\",";
  payload += "\"controllerID\":" + String(getEsp32ChipId()) + ",";
  payload += "\"fw\":\"" + String(FW_VERSION) + "\",";
  payload += "\"theft\":" + String((int)rtcTheft) + ",";
  payload += "\"reason\":\"" + r + "\"";
  payload += "}";

    DEBUG_PRINTLN(F("📡 TELEMETRY -> MQTT HA"));
    DEBUG_PRINTLN(payload);

    bool ok = mqttPublishWithRetryHA(MQTT_HA_TELE_TOPIC, payload, true);

    DEBUG_PRINTLN(ok ? F("✅ MQTT HA TELEMETRY: OK") : F("❌ MQTT HA TELEMETRY: FAIL"));
    return ok;
  }

#endif


#if ENABLE_MQTT_CLOUD

  void mqttSetupClientCloud()
  {
    mqttSetupClientCommon(mqttCloud, mqttCloudCfg);
  }

  bool mqttConnectOnceCloud(uint32_t timeoutMs = 15000)
  {
    mqttSetupClientCloud();
    return mqttConnectOnceCommon(mqttCloud, mqttCloudCfg, "CLOUD", timeoutMs);
  }

  void mqttDisconnectNowCloud()
  {
    mqttDisconnectNowCommon(mqttCloud, mqttCloudNet, mqttCloudCfg);
  }

  static bool mqttPublishWithRetryCloud(const char* topic, const String& payload, bool retain = true)
  {
    mqttSetupClientCloud();

    return mqttPublishWithRetryCommon(
      mqttCloud,
      mqttCloudNet,
      mqttCloudCfg,
      "CLOUD",
      topic,
      payload,
      retain
    );
  }




  bool mqttPublishTelemetryCloud(float vbat, float vsolar, const __FlashStringHelper* reason)
  {
    if (!mqttCloudCfg.enabled) return false;

    String r = String(reason);

    String payload;
    payload.reserve(260);
    payload  = "{";
    payload += "\"type\":\"telemetry\",";
    payload += "\"masterId\":\"" + String((uint32_t)(ESP.getEfuseMac() & 0xFFFFFFFF), HEX) + "\",";
    payload += "\"ts\":" + String((uint32_t)time(nullptr)) + ",";
    payload += "\"vbat\":" + String(vbat, 3) + ",";
    payload += "\"vsolar\":" + String(vsolar, 3) + ",";
    payload += "\"ip\":\"" + modem.localIP().toString() + "\",";
    payload += "\"theft\":" + String((int)rtcTheft) + ",";
    payload += "\"espnow_channel\":" + String((int)CHANNEL) + ",";
    payload += "\"reason\":\"" + r + "\"";
    payload += "}";

    DEBUG_PRINTLN(F("☁️ TELEMETRY -> MQTT CLOUD"));
    DEBUG_PRINTLN(payload);

    bool ok = mqttPublishWithRetryCloud(MQTT_CLOUD_MASTER_TELE, payload, true);

    DEBUG_PRINTLN(ok ? F("✅ MQTT CLOUD TELEMETRY: OK") : F("❌ MQTT CLOUD TELEMETRY: FAIL"));
    return ok;
  }

#endif


bool mqttPublishTelemetry(float vbat, float vsolar, const __FlashStringHelper* reason)
{
  bool okAny = false;

  #if ENABLE_MQTT_HA
    if (mqttHaCfg.enabled) {
      if (mqttPublishTelemetryHA(vbat, vsolar, reason)) okAny = true;
    }
  #endif

  #if ENABLE_MQTT_CLOUD
    if (mqttCloudCfg.enabled) {
      if (mqttPublishTelemetryCloud(vbat, vsolar, reason)) okAny = true;
    }
  #endif

  return okAny;
}


#if ENABLE_MQTT_CLOUD

  static String mqttCloudHiveBaseTopic(uint32_t chipId)
  {
    String topic = String(MQTT_CLOUD_HIVES_BASE);
    topic += "/";
    topic += String(chipId);
    return topic;
  }

  static String mqttCloudHiveLatestTopic(uint32_t chipId)
  {
    String topic = mqttCloudHiveBaseTopic(chipId);
    topic += "/latest";
    return topic;
  }

  static String mqttCloudHiveEventTopic(uint32_t chipId)
  {
    String topic = mqttCloudHiveBaseTopic(chipId);
    topic += "/event";
    return topic;
  }

  static String buildHiveCloudPayload(const ESPnowMessage& msg)
  {
    String payload;
    payload.reserve(420);

    payload  = "{";
    payload += "\"type\":\"hive_data\"";
    payload += ",\"chipId\":" + String(msg.chipID);
    payload += ",\"temperature\":" + String(msg.temperature, 2);
    payload += ",\"weight\":" + String(msg.weight, 2);
    payload += ",\"battery\":" + String(msg.battery, 3);
    payload += ",\"wifiSignal\":" + String(msg.wifiSignal);
    payload += ",\"message\":\"" + String(msg.message) + "\"";
    payload += ",\"slaveMac\":\"" + String(msg.slaveMac) + "\"";
    payload += ",\"ziNoapte\":\"" + String(msg.ziNoapte) + "\"";
    payload += ",\"firmwareVersion\":\"" + String(msg.firmwareVersion) + "\"";
    payload += ",\"posibilROI\":" + String(msg.posibilROI ? "true" : "false");
    payload += ",\"ts\":" + String((uint32_t)time(nullptr));
    payload += "}";

    return payload;
  }

  bool mqttPublishHiveLatestCloud(const ESPnowMessage& msg)
  {
    if (!mqttCloudCfg.enabled) return false;

    String topic   = mqttCloudHiveLatestTopic(msg.chipID);
    String payload = buildHiveCloudPayload(msg);

    DEBUG_PRINTLN(F("☁️ HIVE LATEST -> MQTT CLOUD"));
    DEBUG_PRINT(F("Topic: "));
    DEBUG_PRINTLN(topic);
    DEBUG_PRINTLN(payload);

    bool ok = mqttPublishWithRetryCloud(topic.c_str(), payload, true);

    DEBUG_PRINTLN(ok ? F("✅ MQTT CLOUD HIVE LATEST: OK") : F("❌ MQTT CLOUD HIVE LATEST: FAIL"));
    return ok;
  }

  bool mqttPublishHiveEventCloud(const ESPnowMessage& msg)
  {
    if (!mqttCloudCfg.enabled) return false;

    String topic   = mqttCloudHiveEventTopic(msg.chipID);
    String payload = buildHiveCloudPayload(msg);

    DEBUG_PRINTLN(F("☁️ HIVE EVENT -> MQTT CLOUD"));
    DEBUG_PRINT(F("Topic: "));
    DEBUG_PRINTLN(topic);
    DEBUG_PRINTLN(payload);

    bool ok = mqttPublishWithRetryCloud(topic.c_str(), payload, false);

    DEBUG_PRINTLN(ok ? F("✅ MQTT CLOUD HIVE EVENT: OK") : F("❌ MQTT CLOUD HIVE EVENT: FAIL"));
    return ok;
  }

  bool mqttPublishHiveToCloud(const ESPnowMessage& msg)
  {
    bool okLatest = mqttPublishHiveLatestCloud(msg);
    bool okEvent  = mqttPublishHiveEventCloud(msg);
    return (okLatest || okEvent);
  }

#endif





//bool telemetryTrySend(const __FlashStringHelper* reason, bool force = false)
bool telemetryTrySend(const __FlashStringHelper* reason, bool force = false, bool bypassCooldown = false)
{
  bool wantPhp  = phpEnabled;

  #if ENABLE_MQTT_HA || ENABLE_MQTT_CLOUD
    bool wantMqtt = true;
  #else
    bool wantMqtt = false;
  #endif

  if (!wantPhp && !wantMqtt) return false;
  if (!isInternet()) return false;

  unsigned long now = millis();

  if (!bypassCooldown)
    {
      if (force) {
        if (lastTelemetryBatchMs != 0 && (now - lastTelemetryBatchMs) < TELEMETRY_BATCH_GAP_MS) {
          return false;
        }
      } else {
        if (lastTelemetrySentMs != 0 && (now - lastTelemetrySentMs) < TELEMETRY_PERIOD_MS) {
          return false;
        }
      }
    }

  float vbat   = readVBAT_ADC();
  float vsolar = readVSOLAR_ADC();

  DEBUG_PRINT(F("📡 TELEMETRY reason: "));
  DEBUG_PRINTLN(reason);

  bool okPhp  = false;
  bool okMqtt = false;

  if (wantPhp) {
    okPhp = sendTelemetryToPHP(vbat, vsolar);
  }

  #if ENABLE_MQTT_HA || ENABLE_MQTT_CLOUD
    if (wantMqtt) {
      okMqtt = mqttPublishTelemetry(vbat, vsolar, reason);
    }
  #endif

  bool ok = okPhp || okMqtt;

  if (ok) {
    lastNetSuccessMs = millis();

    if (force) lastTelemetryBatchMs = now;
    else       lastTelemetrySentMs  = now;

    return true;
  } else {
    return false;
  }
}

// ===================== ESP-NOW init =====================
void InitESPNow()
{
  if (esp_now_init() == ESP_OK)
  {
    DEBUG_PRINTLN(F("✅ ESPNow Init Success"));
  }
  else
  {
    DEBUG_PRINTLN(F("❌ ESPNow Init Failed"));
    ESP.restart();
  }
}

static void setFixedChannel(uint8_t ch)
{
  wifi_country_t country = { "RO", 1, 13, WIFI_COUNTRY_POLICY_MANUAL };
  esp_wifi_set_country(&country);
  esp_wifi_set_channel(ch, WIFI_SECOND_CHAN_NONE);
}

// ===================== ESP-NOW RX reworked =====================
void processReceivedEspNowMessage(ESPnowMessage &receivedData)
{
  DEBUG_PRINT(F("📡 Date primite - ChipID: "));
  DEBUG_PRINT(receivedData.chipID);
  DEBUG_PRINT(F(", Temp: "));
  DEBUG_PRINT(receivedData.temperature);
  DEBUG_PRINT(F("°C, Greutate: "));
  DEBUG_PRINT(receivedData.weight);
  DEBUG_PRINT(F("kg, Baterie: "));
  DEBUG_PRINT(receivedData.battery);
  DEBUG_PRINT(F("V, Mesaj: "));
  DEBUG_PRINT(receivedData.message);
  DEBUG_PRINT(F(", Slave MAC: "));
  DEBUG_PRINT(receivedData.slaveMac);
  DEBUG_PRINT(F(", Zi_Noapte: "));
  DEBUG_PRINT(receivedData.ziNoapte);
  DEBUG_PRINT(F(", Firmware: "));
  DEBUG_PRINT(receivedData.firmwareVersion);
  DEBUG_PRINT(F(", RSSI: "));
  DEBUG_PRINTLN(receivedData.wifiSignal);

  {
    unsigned long nowMs = millis();
    int slot = findLastRxSlot(receivedData.chipID);

    if (lastRx[slot].valid && lastRx[slot].chipID == receivedData.chipID) {
      bool inWindow = (nowMs - lastRx[slot].ms) <= DEDUP_WINDOW_MS;

      bool same =
        almostEqualFloat(receivedData.weight,      lastRx[slot].weight,      DEDUP_WEIGHT_EPS) &&
        almostEqualFloat(receivedData.temperature, lastRx[slot].temperature, DEDUP_TEMP_EPS) &&
        almostEqualFloat(receivedData.battery,     lastRx[slot].battery,     DEDUP_BATT_EPS);

      if (inWindow && same) {
        DEBUG_PRINTLN(F("🧽 DEDUP RX: duplicate -> DROP (not added to buffer)"));
        updateDisplayData(receivedData);
        lastEspNowRxMs = nowMs;
        modemWanted = true;
        return;
      }
    }

    lastRx[slot].valid = true;
    lastRx[slot].chipID = receivedData.chipID;
    lastRx[slot].weight = receivedData.weight;
    lastRx[slot].temperature = receivedData.temperature;
    lastRx[slot].battery = receivedData.battery;
    lastRx[slot].wifiSignal = receivedData.wifiSignal;
    lastRx[slot].ms = nowMs;
  }

  bool acceptedInBuffer = false;

  if (lockMutex(batchMutex, 200))
  {
    if (bufferCount == 0) batchStartTime = millis();

    if (bufferCount < MAX_BUFFER_SIZE)
    {
      uint32_t rxTs = (uint32_t)time(nullptr);
      if (rxTs < 1000000000UL) rxTs = 0;

      batchBuffer[bufferCount] = receivedData;
      batchReceivedTs[bufferCount] = rxTs;
      bufferCount++;
      acceptedInBuffer = true;

      DEBUG_PRINT(F("Mesaj adăugat in buffer. Total in buffer: "));
      DEBUG_PRINTLN(bufferCount);

      lastMessageTime = millis();
      lastEspNowRxMs = millis();
      modemWanted = true;
    }
    else
    {
      DEBUG_PRINTLN(F("Buffer plin! Mesajul nu poate fi adăugat."));
    }

    unlockMutex(batchMutex);
  }

  #if ENABLE_MQTT_CLOUD
    if (acceptedInBuffer)
    {
      DEBUG_PRINTLN(F("☁️ HIVE CLOUD publish requested"));

      if (modemReady && modem.isGprsConnected())
      {
        if (lockMutex(modemMutex, 3000))
        {
          bool okCloud = mqttPublishHiveToCloud(receivedData);
          DEBUG_PRINTLN(okCloud ? F("☁️ HIVE CLOUD publish OK") : F("☁️ HIVE CLOUD publish FAIL"));
          unlockMutex(modemMutex);
        }
        else
        {
          DEBUG_PRINTLN(F("☁️ HIVE CLOUD publish skipped: modemMutex busy"));
        }
      }
      else
      {
        DEBUG_PRINTLN(F("☁️ HIVE CLOUD publish skipped: 4G not ready"));
      }
    }
  #endif

  updateDisplayData(receivedData);
}

void processEspNowQueue()
{
  EspNowQueuedMessage qmsg;
  while (xQueueReceive(espNowRxQueue, &qmsg, 0) == pdTRUE)
  {
    processReceivedEspNowMessage(qmsg.msg);
  }
}

void OnDataRecv(const esp_now_recv_info_t *info, const uint8_t *incomingData, int len)
{
  EspNowQueuedMessage qmsg;
  memset(&qmsg, 0, sizeof(qmsg));

  int copyLen = len;
  if (copyLen > (int)sizeof(ESPnowMessage)) copyLen = sizeof(ESPnowMessage);

  memcpy(&qmsg.msg, incomingData, copyLen);

  char macStr[18];
  snprintf(macStr, sizeof(macStr), "%02X:%02X:%02X:%02X:%02X:%02X",
           info->src_addr[0], info->src_addr[1], info->src_addr[2],
           info->src_addr[3], info->src_addr[4], info->src_addr[5]);

  strcpy(qmsg.msg.slaveMac, macStr);
  qmsg.msg.wifiSignal = info->rx_ctrl ? info->rx_ctrl->rssi : 0;

  if (len != sizeof(ESPnowMessage))
  {
    DEBUG_PRINT(F("⚠️ ESP-NOW RX len mismatch. got="));
    DEBUG_PRINT(len);
    DEBUG_PRINT(F(" expected="));
    DEBUG_PRINTLN(sizeof(ESPnowMessage));
  }

  BaseType_t ok = xQueueSend(espNowRxQueue, &qmsg, 0);
  if (ok != pdTRUE)
  {
    DEBUG_PRINTLN(F("⚠️ espNowRxQueue full -> packet dropped"));
  }
}

// ===================== Buffer sender =====================
void sendDataFromBuffer()
{
  static ESPnowMessage localBuffer[MAX_BUFFER_SIZE];
  static uint32_t localReceivedTs[MAX_BUFFER_SIZE];
  int localCount = 0;

  if (!lockMutex(batchMutex, 500)) return;

  if (bufferCount == 0)
  {
    unlockMutex(batchMutex);
    return;
  }

  localCount = bufferCount;
  if (localCount > MAX_BUFFER_SIZE) localCount = MAX_BUFFER_SIZE;

  for (int i = 0; i < localCount; i++)
  {
    localBuffer[i] = batchBuffer[i];
    localReceivedTs[i] = batchReceivedTs[i];
  }

  bufferCount = 0;

  unlockMutex(batchMutex);

  bool ok = sendData(localBuffer, localReceivedTs, localCount);

  if (!ok)
  {
    DEBUG_PRINTLN(F("❌ sendData() a eșuat. Reintroduc batch-ul în buffer."));

    if (lockMutex(batchMutex, 500))
    {
      int existingCount = bufferCount;
      int putBack = min(localCount, MAX_BUFFER_SIZE);
      int keepExisting = min(existingCount, MAX_BUFFER_SIZE - putBack);
      int droppedExisting = existingCount - keepExisting;

      if (keepExisting > 0)
      {
        int srcStart = existingCount - keepExisting;
        for (int i = keepExisting - 1; i >= 0; i--)
        {
          batchBuffer[putBack + i] = batchBuffer[srcStart + i];
          batchReceivedTs[putBack + i] = batchReceivedTs[srcStart + i];
        }
      }

      for (int i = 0; i < putBack; i++)
      {
        batchBuffer[i] = localBuffer[i];
        batchReceivedTs[i] = localReceivedTs[i];
      }

      bufferCount = putBack + keepExisting;
      batchStartTime = millis();
      lastMessageTime = millis();

      if (droppedExisting > 0)
      {
        DEBUG_PRINT(F("Batch retry buffer full, dropped oldest RX count="));
        DEBUG_PRINTLN(droppedExisting);
      }

      unlockMutex(batchMutex);
    }
  }
  else
  {
    lastNetSuccessMs = millis();
    firstUnsentDataMs = 0;
    DEBUG_PRINTLN(F("✅ sendData() OK (pipeline)."));
  }
}

static bool ensure4GOn_TheftOverride()
{
#if THEFT_FORCE_4G
  if (!rtcTheft) return true;

  if (modemReady && modem.isGprsConnected()) return true;

  DEBUG_PRINTLN(F("🚨 THEFT: forcing 4G ON for tracking..."));
  modemWanted = true;

  bool ok = connectModem4G();
  modemReady = ok;
  if (modemReady) {
    modemNextTry = 0;
    modemRetryDelay = 30000UL;
    lastNetSuccessMs = millis();

    if (syncTimeFromModemCCLK(6000)) setTimezoneRomania();
  }

  if (!ok) {
    DEBUG_PRINTLN(F("🚨 THEFT: 4G force FAILED (will retry)"));
  }
  return ok;
#else
  return true;
#endif
}

// ===================== Serial command handler =====================
void handleSerialCommandsLocal()
{
  if (!Serial.available()) return;

  String cmd = Serial.readStringUntil('\n');
  cmd.trim();
  cmd.toLowerCase();

  if (cmd == "restart")
  {
    DEBUG_PRINTLN(F("Serial command restart received. Rebooting ESP32."));
    delay(100);
    ESP.restart();
  }

  if (cmd == "4g")
  {
    Serial.println("📲 Manual: connectModem4G()");
    if (lockMutex(modemMutex, 2000))
    {
      if (connectModem4G())
      {
        modemReady = true;
        modemWanted = true;
        modemNextTry = 0;
        modemRetryDelay = 30000UL;
        lastNetSuccessMs = millis();
        next4GAllowedAt = 0;

        Serial.print("✅ 4G OK, IP=");
        Serial.println(modem.localIP());

        if (syncTimeFromModemCCLK(6000)) setTimezoneRomania();

        #if ENABLE_MQTT_HA
          mqttSetupClientHA();
          mqttEnsureSubscribedHA();
        #endif

        #if ENABLE_MQTT_CLOUD
          mqttSetupClientCloud();
          mqttConnectOnceCloud(3000);
        #endif
      }
      else
      {
        modemReady = false;
        Serial.println("❌ 4G FAIL");
      }
      unlockMutex(modemMutex);
    }
  }

  if (cmd == "sleep")
  {
    enterNightDeepSleep(NIGHT_WAKE_MINUTES);
  }

  if (cmd == "resetref")
  {
    Serial.println("📌 resetref: trying to set FIXED reference from current GPS fix...");

    gpsOn();

    double lat = 0, lon = 0;
    int sats = 0, fq = 0;
    bool got = false;

    uint32_t t0 = millis();
    while (millis() - t0 < GPS_FIX_TRY_MS)
    {
      float hdop = 99;
      if (readGPS_FromNMEA(lat, lon, sats, fq, hdop, 1500)) { got = true; break; }
      delay(200);
    }

    if (got)
    {
      rtcHasRef = 1;
      rtcRefLat = lat;
      rtcRefLon = lon;
      rtcLastRefTs = (uint32_t)time(nullptr);
      rtcTheft = 0;
      nextTheftPublishMs = 0;
      rtcTheftCandidateCount = 0;

      gpsHasFix   = true;
      lastGpsLat  = lat;
      lastGpsLon  = lon;
      lastGpsSats = sats;
      lastGpsFq   = fq;

      Serial.print("✅ FIXED REF UPDATED: lat=");
      Serial.print(lat, 6);
      Serial.print(" lon=");
      Serial.print(lon, 6);
      Serial.print(" sats=");
      Serial.println(sats);

      gpsOff();
    }
    else
    {
      Serial.println("❌ resetref: no GPS fix (timeout) -> REF not changed");
      gpsOff();
    }
  }
}

// ===================== Local core work =====================
void runLocalCoreWork()
{
  unsigned long currentMillis = millis();

  processEspNowQueue();
  server.handleClient();
  handleSerialCommandsLocal();

  if (currentMillis - lastTelemetryPrint >= TELEMETRY_PRINT_MS)
  {
    lastTelemetryPrint = currentMillis;

    float vbat = readVBAT_ADC();
    float vsolar = readVSOLAR_ADC();
    bool sun = updateSolarPresent(vsolar);

    Serial.print("🔋 VBAT(ADC)= ");
    Serial.print(vbat, 2);
    Serial.println(" V");

    Serial.print("☀️ VSOLAR(ADC)= ");
    Serial.print(vsolar, 2);
    Serial.print(" V => ");
    Serial.println(sun ? "SUN/CHARGING" : "NO SUN");

    bool night = isNightNow(vsolar);
    bool sunrise = (rtcLastWasNight == 1 && !night);

    if (night)
    {
      rtcLastWasNight = 1;
      enterNightDeepSleep(NIGHT_WAKE_MINUTES);
    }
    else
    {
      if (rtcLastWasNight == 1)
      {
        rtcLastWasNight = 0;
      }

      if (sunrise)
      {
        DEBUG_PRINTLN(F("🌅 Sunrise -> GPS short fix + anti-theft check"));
        gpsOn();

        double lat = 0, lon = 0;
        int sats = 0, fq = 0;
        float hdop = 99;

        bool got = getStableFix(lat, lon, sats, fq, hdop, SUNRISE_GPS_TIMEOUT_MS);

        if (got)
        {
          lastGpsLat  = lat;
          lastGpsLon  = lon;
          lastGpsSats = sats;
          lastGpsFq   = fq;
          gpsHasFix   = true;

          DEBUG_PRINT(F("📍 GPS STABLE FIX: lat="));
          DEBUG_PRINT(lat, 6);
          DEBUG_PRINT(F(" lon="));
          DEBUG_PRINT(lon, 6);
          DEBUG_PRINT(F(" sats="));
          DEBUG_PRINT(sats);
          DEBUG_PRINT(F(" hdop="));
          DEBUG_PRINTLN(hdop, 2);

          if (rtcHasRef)
          {
            double d = haversineMeters(rtcRefLat, rtcRefLon, lat, lon);
            DEBUG_PRINT(F("📏 Dist from ref = "));
            DEBUG_PRINT(d, 1);
            DEBUG_PRINTLN(F(" m"));

            theftUpdateByDistance(d);
          }
          else
          {
            rtcHasRef = 1;
            rtcRefLat = lat;
            rtcRefLon = lon;
            rtcLastRefTs = (uint32_t)time(nullptr);

            rtcTheft = 0;
            rtcTheftCandidateCount = 0;

            DEBUG_PRINTLN(F("📌 Set FIXED reference (first good sunrise fix)"));
          }

          rtcLastRefTs = (uint32_t)time(nullptr);

          #if ENABLE_MQTT_HA || ENABLE_MQTT_CLOUD
                    if (modemReady && modem.isGprsConnected())
                    {
                      String payload;
                      payload.reserve(260);
                      payload  = "{";
                      payload += "\"type\":\"gps\",\"reason\":\"sunrise_fix\"";
                      payload += ",\"ts\":" + String((uint32_t)time(nullptr));
                      payload += ",\"lat\":" + String(lat, 6);
                      payload += ",\"lon\":" + String(lon, 6);
                      payload += ",\"sats\":" + String(sats);
                      payload += ",\"fq\":" + String(fq);
                      payload += ",\"hdop\":" + String(hdop, 2);
                      payload += ",\"theft\":" + String((int)rtcTheft);
                      payload += "}";

                      if (lockMutex(modemMutex, 8000))
                      {
                        bool ok = false;

                        #if ENABLE_MQTT_HA
                          ok = mqttPublishWithRetryHA(MQTT_HA_GPS_TOPIC, payload, true) || ok;
                        #endif

                        #if ENABLE_MQTT_CLOUD
                          ok = mqttPublishWithRetryCloud(MQTT_CLOUD_MASTER_GPS, payload, true) || ok;
                        #endif

                        DEBUG_PRINTLN(ok ? F("✅ MQTT GPS sunrise: OK") : F("❌ MQTT GPS sunrise: FAIL"));
                        unlockMutex(modemMutex);
                      }
                    }
          #endif
        }
        else
        {
          gpsHasFix = false;
          DEBUG_PRINTLN(F("📍 Sunrise GPS: no STABLE fix (timeout/quality)"));
        }

        if (!rtcTheft)
        {
          gpsOff();
          DEBUG_PRINTLN(F("🛰️ GPS OFF for the day"));
        }
        else
        {
          DEBUG_PRINTLN(F("🛰️ Theft mode: keeping GPS ON (for next step)"));
        }
      }
    }

    if (digitalRead(GPS_WAKEUP_PIN) == HIGH)
    {
      double lat, lon;
      int sats, fq;
      float hdop = 99;
      if (readGPS_FromNMEA(lat, lon, sats, fq, hdop, 1500))
      {
        Serial.print("📍 GPS FIX: lat=");
        Serial.print(lat, 6);
        Serial.print(" lon=");
        Serial.print(lon, 6);
        Serial.print(" sats=");
        Serial.print(sats);
        Serial.print(" q=");
        Serial.println(fq);
      }
      else
      {
        Serial.println("📍 GPS: no fix yet (NMEA)");
      }
    }
  }

  if (!rtcTheft)
  {
    if ((int32_t)(millis() - nextGpsCheckAtMs) >= 0)
    {
      DEBUG_PRINTLN(F("🛰️ Periodic GPS check (anti-theft)"));
      gpsOn();

      double lat = 0, lon = 0;
      int sats = 0, fq = 0;
      float hdop = 99;

      bool got = getStableFix(lat, lon, sats, fq, hdop, GPS_FIX_TRY_MS);

      if (got)
      {
        gpsHasFix   = true;
        lastGpsLat  = lat;
        lastGpsLon  = lon;
        lastGpsSats = sats;
        lastGpsFq   = fq;

        DEBUG_PRINT(F("📍 Periodic STABLE FIX: lat="));
        DEBUG_PRINT(lat, 6);
        DEBUG_PRINT(F(" lon="));
        DEBUG_PRINT(lon, 6);
        DEBUG_PRINT(F(" sats="));
        DEBUG_PRINT(sats);
        DEBUG_PRINT(F(" hdop="));
        DEBUG_PRINTLN(hdop, 2);

        if (rtcHasRef)
        {
          double d = haversineMeters(rtcRefLat, rtcRefLon, lat, lon);

          DEBUG_PRINT(F("📏 Dist from ref = "));
          DEBUG_PRINT(d, 1);
          DEBUG_PRINTLN(F(" m"));

          theftUpdateByDistance(d);
        }
        else
        {
          rtcHasRef = 1;
          rtcRefLat = lat;
          rtcRefLon = lon;
          rtcLastRefTs = (uint32_t)time(nullptr);

          rtcTheft = 0;
          rtcTheftCandidateCount = 0;

          DEBUG_PRINTLN(F("📌 Set FIXED reference (first good periodic fix)"));
        }

        rtcLastRefTs = (uint32_t)time(nullptr);

        if (!rtcTheft)
        {
          gpsOff();
          DEBUG_PRINTLN(F("🛰️ OK -> GPS OFF (normal mode)"));
        }
        else
        {
          DEBUG_PRINTLN(F("🚨 THEFT detected/confirming -> GPS stays ON"));
          nextTheftPublishMs = 0;
        }
      }
      else
      {
        DEBUG_PRINTLN(F("🛰️ Periodic: no STABLE fix -> GPS OFF"));
        gpsOff();
      }

      nextGpsCheckAtMs = millis() + GPS_CHECK_INTERVAL_MS;
      DEBUG_PRINT(F("🛰️ Next periodic check in "));
      DEBUG_PRINT(GPS_CHECK_INTERVAL_MS / 1000);
      DEBUG_PRINTLN(F(" sec"));
    }
  }

  if (rtcTheft)
  {
    gpsOn();

    double lat = 0, lon = 0;
    int sats = 0, fq = 0;
    float hdop = 99;

    bool got = getStableFix(lat, lon, sats, fq, hdop, 3500);

    if (got)
    {
      gpsHasFix   = true;
      lastGpsLat  = lat;
      lastGpsLon  = lon;
      lastGpsSats = sats;
      lastGpsFq   = fq;

      if (rtcHasRef)
      {
        double d = haversineMeters(rtcRefLat, rtcRefLon, lat, lon);

        DEBUG_PRINT(F("🚨 THEFT MODE: dist="));
        DEBUG_PRINT(d, 1);
        DEBUG_PRINT(F("m sats="));
        DEBUG_PRINT(sats);
        DEBUG_PRINT(F(" hdop="));
        DEBUG_PRINTLN(hdop, 2);

        if (d > THEFT_DISTANCE_M)
        {
          if (rtcTheftCandidateCount < 250) rtcTheftCandidateCount++;
        }
        else
        {
          if (rtcTheftCandidateCount > 0) rtcTheftCandidateCount--;

          if (rtcTheftCandidateCount == 0)
          {
            rtcTheft = 0;
            DEBUG_PRINTLN(F("✅ THEFT CLEARED (back under threshold)"));
          }
        }
      }
    }
    else
    {
      DEBUG_PRINTLN(F("🚨 THEFT MODE: no STABLE fix (skip update/publish)"));
    }

    if (!rtcTheft)
    {
      gpsOff();
      DEBUG_PRINTLN(F("🛰️ Theft cleared -> GPS OFF"));
      nextTheftPublishMs = 0;
    }
  }

  if (millis() - lastTaskStackPrint >= TASK_STACK_PRINT_MS)
  {
    lastTaskStackPrint = millis();

    DEBUG_PRINT(F("📊 taskLocal free stack = "));
    DEBUG_PRINTLN(uxTaskGetStackHighWaterMark(taskLocalHandle));

    DEBUG_PRINT(F("📊 task4G free stack = "));
    DEBUG_PRINTLN(uxTaskGetStackHighWaterMark(task4GHandle));
  }

}

// ===================== 4G core work =====================
void run4GCoreWork()
{
  phpMaybeReEnable();
  check4GHealthAndRecover();

  #if ENABLE_OTA_4G
    if (otaRequested && modemReady && modem.isGprsConnected() && !netBusy)
    {
      otaRequested = false;

      DEBUG_PRINTLN(F("🚀 OTA: cerere primită, încerc update..."));

      if (lockMutex(modemMutex, 30000))
      {
        otaCheckAndUpdate();
        unlockMutex(modemMutex);
      }
      else
      {
        DEBUG_PRINTLN(F("❌ OTA: modemMutex busy, reîncerc mai târziu."));
        otaRequested = true;
      }

      return;
    }
  #endif

    if (telemetryRequested && modemReady && modem.isGprsConnected() && !netBusy)
  {
    telemetryRequested = false;

    DEBUG_PRINTLN(F("📡 TELEMETRY: cerere on-demand primită, trimit acum..."));

    if (lockMutex(modemMutex, 15000))
    {
      bool ok = telemetryTrySend(F("on_demand"), true, true);

      DEBUG_PRINTLN(ok ? F("✅ TELEMETRY on-demand: OK") : F("❌ TELEMETRY on-demand: FAIL"));

      unlockMutex(modemMutex);
    }
    else
    {
      DEBUG_PRINTLN(F("❌ TELEMETRY: modemMutex busy, reîncerc mai târziu."));
      telemetryRequested = true;
    }

    return;
  }  

  unsigned long currentMillis = millis();
  unsigned long now = millis();

  if (!modemReady && modemWanted)
  {
    if (modemNextTry != 0 && millis() < modemNextTry)
    {
      return;
    }

    float vbat = readVBAT_ADC();
    float vsolar = readVSOLAR_ADC();

    if (next4GAllowedAt != 0 && millis() < next4GAllowedAt && !isStrongSun(vsolar))
    {
      // cooldown activ
    }
    else
    {
      if (canTurnOn4GNow_Policy(vbat, vsolar))
      {
        DEBUG_PRINTLN(F("📶 Policy: pornesc 4G ..."));

        if (lockMutex(modemMutex, 2000))
        {
          if (connectModem4G())
          {
            modemReady = true;
            modemNextTry = 0;
            modemRetryDelay = 30000UL;
            lastNetSuccessMs = millis();

            if (syncTimeFromModemCCLK(6000)) setTimezoneRomania();

            DEBUG_PRINTLN(F("✅ 4G ready (policy)"));

            #if ENABLE_MQTT_HA
              mqttSetupClientHA();
              mqttEnsureSubscribedHA();
            #endif

            #if ENABLE_MQTT_CLOUD
              mqttSetupClientCloud();
              mqttConnectOnceCloud(3000);
            #endif

            if (isStrongSun(vsolar)) next4GAllowedAt = 0;
          }
          else
          {
            DEBUG_PRINTLN(F("❌ 4G start fail -> cooldown"));
            modemReady = false;
            modemWanted = true;
            modemNextTry = millis() + modemRetryDelay;
            modemRetryDelay = min(modemRetryDelayMax, modemRetryDelay * 2);
            next4GAllowedAt = millis() + LOWPOWER_4G_COOLDOWN_MS;
          }

          unlockMutex(modemMutex);
        }
      }
      else
      {
        if (!isStrongSun(vsolar) && vbat < VBAT_ALLOW_4G)
        {
          if (next4GAllowedAt == 0 || millis() >= next4GAllowedAt)
          {
            DEBUG_PRINTLN(F("🔋 Policy: VBAT<3.6 și VSOLAR<5 -> 4G OFF + retry later"));
            next4GAllowedAt = millis() + LOWPOWER_4G_COOLDOWN_MS;
          }
        }
      }
    }
  }

  bool shouldTryBatch = false;
  unsigned long idleMs = 0;
  unsigned long ageMs = 0;

  if (lockMutex(batchMutex, 100))
  {
    if (!isSending && bufferCount > 0 && (millis() - lastBatchAttempt > 3000))
    {
      if ((currentMillis - lastMessageTime >= BATCH_FLUSH_TIMEOUT) ||
          (currentMillis - batchStartTime >= BATCH_SEND_INTERVAL))
      {
        shouldTryBatch = true;
        isSending = true;
        lastBatchAttempt = millis();
        idleMs = currentMillis - lastMessageTime;
        ageMs  = currentMillis - batchStartTime;
      }
    }
    unlockMutex(batchMutex);
  }

  if (shouldTryBatch)
  {
    if (isInternet())
    {
      DEBUG_PRINTLN(F("Conexiunea este disponibilă. Se trimite batch-ul..."));

      if (idleMs >= BATCH_FLUSH_TIMEOUT) {
        DEBUG_PRINT(F("🧾 Flush reason: idle=")); DEBUG_PRINT(idleMs); DEBUG_PRINTLN(F("ms"));
      } else if (ageMs >= BATCH_SEND_INTERVAL) {
        DEBUG_PRINT(F("🧾 Flush reason: age=")); DEBUG_PRINT(ageMs); DEBUG_PRINTLN(F("ms"));
      }

      if (lockMutex(modemMutex, 5000))
      {
        sendDataFromBuffer();
        unlockMutex(modemMutex);
      }

      if (lockMutex(batchMutex, 100))
      {
        if (bufferCount == 0)
        {
          batchStartTime = 0;
          lastMessageTime = 0;
        }
        isSending = false;
        unlockMutex(batchMutex);
      }
    }
    else
    {
      if (is4GCooldownActive())
      {
        static unsigned long lastCdLog = 0;
        if (millis() - lastCdLog > 60000UL)
        {
          lastCdLog = millis();
          DEBUG_PRINTLN(F("⏳ 4G cooldown activ -> batch păstrat în buffer (no spam)"));
        }
      }
      else
      {
        DEBUG_PRINTLN(F("Internetul nu este disponibil. Mesajele rămân în buffer."));
      }

      if (lockMutex(batchMutex, 100))
      {
        isSending = false;
        unlockMutex(batchMutex);
      }
    }
  }

  if (modemReady)
  {
    if (lockMutex(modemMutex, 5000))
    {
      trySendPhpPending();
      trySendGooglePending();
      unlockMutex(modemMutex);
    }
  }

  if (modemReady)
  {
    bool hasWork = false;

    if (lockMutex(batchMutex, 100))
    {
      hasWork = (bufferCount > 0 || phpPendingCount > 0 || googlePendingCount > 0);
      unlockMutex(batchMutex);
    }

    if (!hasWork)
    {
      float vbat = readVBAT_ADC();
      float vsolar = readVSOLAR_ADC();

      if (!shouldKeep4GOnAfterSends_Policy(vbat, vsolar))
      {
        if (lockMutex(modemMutex, 5000))
        {
          powerOffModemDay();
          unlockMutex(modemMutex);
        }
        next4GAllowedAt = millis() + LOWPOWER_4G_COOLDOWN_MS;
      }
    }
  }

  if (lockMutex(batchMutex, 100))
  {
    if ((bufferCount > 0 || phpPendingCount > 0 || googlePendingCount > 0))
    {
      modemWanted = true;
    }
    unlockMutex(batchMutex);
  }

  if (modemReady) {
    unsigned long now2 = millis();
    bool telemetryDue = (lastTelemetryAttemptMs == 0) || ((now2 - lastTelemetryAttemptMs) >= TELEMETRY_PERIOD_MS);

    if (telemetryDue) {
      if (lockMutex(modemMutex, 15000))
      {
        lastTelemetryAttemptMs = now2;
        telemetryTrySend(F("periodic_heartbeat"));
        unlockMutex(modemMutex);
      }
    }
  }

  #if ENABLE_MQTT_HA
    static unsigned long lastEnsureHA = 0;

    if (!netBusy && modemReady && modem.isGprsConnected())
    {
      if (lockMutex(modemMutex, 200))
      {
        mqttHa.loop();

        if (millis() - lastEnsureHA > 5000) {
          lastEnsureHA = millis();
          mqttEnsureSubscribedHA();
        }

        unlockMutex(modemMutex);
      }
    }
  #endif

  #if ENABLE_MQTT_CLOUD
    static unsigned long lastEnsureCloud = 0;

    if (!netBusy && modemReady && modem.isGprsConnected())
    {
      if (lockMutex(modemMutex, 200))
      {
        mqttCloud.loop();

        if (millis() - lastEnsureCloud > 1000) {
          lastEnsureCloud = millis();
          mqttConnectOnceCloud(3000);
        }

        unlockMutex(modemMutex);
      }
    }
  #endif



  if (rtcTheft)
  {
    if (nextTheftPublishMs == 0) nextTheftPublishMs = now;

    if (now >= nextTheftPublishMs)
    {
      if (lockMutex(modemMutex, 5000))
      {
        bool netOk = ensure4GOn_TheftOverride();

        #if ENABLE_MQTT_HA || ENABLE_MQTT_CLOUD
                if (gpsHasFix && netOk && modemReady && modem.isGprsConnected())
                {
                  String payload;
                  payload.reserve(280);
                  payload  = "{";
                  payload += "\"type\":\"gps\",\"reason\":\"theft\"";
                  payload += ",\"ts\":" + String((uint32_t)time(nullptr));
                  payload += ",\"lat\":" + String(lastGpsLat, 6);
                  payload += ",\"lon\":" + String(lastGpsLon, 6);
                  payload += ",\"sats\":" + String(lastGpsSats);
                  payload += ",\"fq\":" + String(lastGpsFq);
                  payload += ",\"theft\":1";
                  payload += "}";

                  bool ok = false;

                  #if ENABLE_MQTT_HA
                    ok = mqttPublishWithRetryHA(MQTT_HA_GPS_TOPIC, payload, true) || ok;
                  #endif

                  #if ENABLE_MQTT_CLOUD
                    ok = mqttPublishWithRetryCloud(MQTT_CLOUD_MASTER_GPS, payload, true) || ok;
                  #endif

                  DEBUG_PRINTLN(ok ? F("✅ MQTT GPS theft: OK") : F("❌ MQTT GPS theft: FAIL"));
                }
        #endif
        unlockMutex(modemMutex);
      }

      nextTheftPublishMs = now + GPS_THEFT_PUBLISH_MS;
    }
  }
}

// ===================== FreeRTOS task wrappers =====================
void taskLocalCore(void *pv)
{
  for (;;)
  {
    runLocalCoreWork();
    vTaskDelay(pdMS_TO_TICKS(5));
  }
}

void task4GCore(void *pv)
{
  for (;;)
  {
    run4GCoreWork();
    vTaskDelay(pdMS_TO_TICKS(20));
  }
}

// ===================== SETUP =====================
void setup()
{
  gpio_deep_sleep_hold_dis();
  gpio_hold_dis((gpio_num_t)GPS_WAKEUP_PIN);
  gpio_hold_dis((gpio_num_t)MODEM_RESET_PIN);
  gpio_hold_dis((gpio_num_t)BOARD_POWERON_PIN);

#ifdef DEBUG_ENABLED
  Serial.begin(115200);
  delay(200);
#endif

  DEBUG_PRINT(F("sizeof(ESPnowMessage) receiver = "));
  DEBUG_PRINTLN(sizeof(ESPnowMessage));

  espNowRxQueue = xQueueCreate(50, sizeof(EspNowQueuedMessage));

  batchMutex   = xSemaphoreCreateMutex();
  displayMutex = xSemaphoreCreateMutex();
  stateMutex   = xSemaphoreCreateMutex();
  modemMutex   = xSemaphoreCreateMutex();
  adcMutex     = xSemaphoreCreateMutex();

  if (!espNowRxQueue || !batchMutex || !displayMutex || !stateMutex || !modemMutex || !adcMutex)
  {
    DEBUG_PRINTLN(F("❌ Eroare creare queue/mutex"));
    while (true) delay(1000);
  }

  sunriseFlag = false;
  earlyNightGateOrContinue();

  nextGpsCheckAtMs = millis() + GPS_CHECK_INTERVAL_MS;
  DEBUG_PRINT(F("🛰️ nextGpsCheckAtMs armed in setup, in "));
  DEBUG_PRINT(GPS_CHECK_INTERVAL_MS / 1000);
  DEBUG_PRINTLN(F("s"));

  bool wokeFromSleep = (esp_sleep_get_wakeup_cause() == ESP_SLEEP_WAKEUP_TIMER);

  DEBUG_PRINT(F("Wake cause: "));
  DEBUG_PRINTLN((int)esp_sleep_get_wakeup_cause());

  bool need4GNow = false;

  if (!wokeFromSleep) {
    need4GNow = true;
  }
  else if (sunriseFlag) {
    float vbat   = readVBAT_ADC();
    float vsolar = readVSOLAR_ADC();

    if (canTurnOn4GNow_Policy(vbat, vsolar)) {
      need4GNow = true;
    } else {
      DEBUG_PRINTLN(F("🌅 Sunrise dar policy nu permite 4G acum -> skip"));
    }
  }

  if (need4GNow)
  {
    modemReady = false;

    DEBUG_PRINTLN(F("🚀 ESP32 Receiver ESP-NOW + Web + 4G(PHP)"));
    DEBUG_PRINTLN(F("🔌 Inițializare modem 4G..."));

    if (!connectModem4G())
    {
      DEBUG_PRINTLN(F("❌ Modem 4G init eșuat. Continui cu ESP-NOW + WEB local."));
      modemReady = false;
    }
    else
    {
      modemReady = true;
      modemNextTry = 0;
      modemRetryDelay = 30000UL;
      lastNetSuccessMs = millis();

      DEBUG_PRINTLN(F("✅ Modem 4G inițializat."));
      DEBUG_PRINT(F("📡 IP: "));
      DEBUG_PRINTLN(modem.localIP().toString());

      if (syncTimeFromModemCCLK(6000)) setTimezoneRomania();


        #if ENABLE_MQTT_HA
          mqttSetupClientHA();
          mqttEnsureSubscribedHA();
           publishStateNowHA();
        #endif

        #if ENABLE_MQTT_CLOUD
          mqttSetupClientCloud();
          mqttConnectOnceCloud(3000);
          
        #endif


      if (!wokeFromSleep) {
        if (!rtcBootTelemetrySent) {
          telemetryTrySend(F("boot"), true);
          rtcBootTelemetrySent = 1;
        }
      } else if (sunriseFlag) {
        telemetryTrySend(F("sunrise"), true);
      }

      lastTelemetrySentMs = millis();
      lastEspNowRxMs = millis();
    }
  }
  else
  {
    modemReady = false;
    DEBUG_PRINTLN(F("😴 Wake din deep sleep: 4G OFF (no work / policy)"));
  }

  if (rtcEspNowChannel < 1 || rtcEspNowChannel > 13) rtcEspNowChannel = 1;
  CHANNEL = rtcEspNowChannel;

  WiFi.mode(WIFI_AP_STA);
  WiFi.softAP(AP_SSID, AP_PASSWORD, CHANNEL);

  DEBUG_PRINT(F("✅ SoftAP pornit pe canalul: "));
  DEBUG_PRINTLN(CHANNEL);

  DEBUG_PRINT(F("➡️ IP AP: "));
  DEBUG_PRINTLN(WiFi.softAPIP());

  setFixedChannel(CHANNEL);

  InitESPNow();
  esp_now_register_recv_cb(OnDataRecv);

  if (!MDNS.begin("Stupina"))
  {
    DEBUG_PRINTLN(F("⚠️ Eroare mDNS"));
  }
  else
  {
    DEBUG_PRINTLN(F("✅ mDNS pornit: http://Stupina.local"));
    MDNS.addService("http", "tcp", 80);
  }

  server.on("/", handleRoot);
  server.on("/data", handleData);
  server.on("/restart", handleRestart);
  server.begin();

  DEBUG_PRINTLN(F("✅ Webserver pornit."));

  Serial.print("🔧 VBAT_DIV computed = ");
  Serial.println(VBAT_DIV, 6);

  Serial.print("🔧 VSOLAR_DIV computed = ");
  Serial.println(VSOLAR_DIV, 6);

  if (!rtcHasRef) {
    DEBUG_PRINTLN(F("📌 Boot: no ref -> taking initial GPS fix (LAST in setup)"));

    gpsOn();

    double lat = 0, lon = 0;
    int sats = 0, fq = 0;
    float hdop = 99;

    bool got = getStableFix(lat, lon, sats, fq, hdop, GPS_FIX_TRY_MS);

    if (got) {
      rtcHasRef = 1;
      rtcRefLat = lat;
      rtcRefLon = lon;
      rtcLastRefTs = (uint32_t)time(nullptr);

      rtcTheft = 0;
      rtcTheftCandidateCount = 0;

      gpsHasFix   = true;
      lastGpsLat  = lat;
      lastGpsLon  = lon;
      lastGpsSats = sats;
      lastGpsFq   = fq;

      DEBUG_PRINT(F("✅ Boot REF set: lat="));
      DEBUG_PRINT(lat, 6);
      DEBUG_PRINT(F(" lon="));
      DEBUG_PRINT(lon, 6);
      DEBUG_PRINT(F(" sats="));
      DEBUG_PRINT(sats);
      DEBUG_PRINT(F(" hdop="));
      DEBUG_PRINTLN(hdop, 2);
    } else {
      DEBUG_PRINTLN(F("⚠️ Boot REF: no STABLE fix (will try later periodically)"));
    }

    gpsOff();
  }

  if (sunriseFlag)
  {
    DEBUG_PRINTLN(F("🌅 Sunrise -> GPS short fix + anti-theft check (LAST in setup)"));
    gpsOn();

    double lat = 0, lon = 0;
    int sats = 0, fq = 0;
    float hdop = 99;

    bool got = getStableFix(lat, lon, sats, fq, hdop, SUNRISE_GPS_TIMEOUT_MS);

    if (got)
    {
      DEBUG_PRINT(F("📍 GPS STABLE FIX: lat="));
      Serial.print(lat, 6);
      DEBUG_PRINT(F(" lon="));
      Serial.print(lon, 6);
      DEBUG_PRINT(F(" sats="));
      DEBUG_PRINT(sats);
      DEBUG_PRINT(F(" hdop="));
      DEBUG_PRINTLN(hdop, 2);

      if (rtcHasRef)
      {
        double d = haversineMeters(rtcRefLat, rtcRefLon, lat, lon);
        DEBUG_PRINT(F("📏 Dist from ref = "));
        Serial.print(d, 1);
        DEBUG_PRINTLN(F(" m"));

        theftUpdateByDistance(d);
      }
      else
      {
        rtcHasRef = 1;
        rtcRefLat = lat;
        rtcRefLon = lon;
        rtcLastRefTs = (uint32_t)time(nullptr);

        rtcTheft = 0;
        rtcTheftCandidateCount = 0;

        DEBUG_PRINTLN(F("📌 Set FIXED reference (first good sunrise fix)"));
      }

      rtcLastRefTs = (uint32_t)time(nullptr);

      gpsHasFix   = true;
      lastGpsLat  = lat;
      lastGpsLon  = lon;
      lastGpsSats = sats;
      lastGpsFq   = fq;

      #if ENABLE_MQTT_HA || ENABLE_MQTT_CLOUD
            if (modemReady && modem.isGprsConnected()) {
              String payload;
              payload.reserve(260);
              payload  = "{";
              payload += "\"type\":\"gps\",\"reason\":\"sunrise_fix\"";
              payload += ",\"ts\":" + String((uint32_t)time(nullptr));
              payload += ",\"lat\":" + String(lat, 6);
              payload += ",\"lon\":" + String(lon, 6);
              payload += ",\"sats\":" + String(sats);
              payload += ",\"fq\":" + String(fq);
              payload += ",\"hdop\":" + String(hdop, 2);
              payload += ",\"theft\":" + String((int)rtcTheft);
              payload += "}";

              if (lockMutex(modemMutex, 8000))
              {
                bool ok = false;

                #if ENABLE_MQTT_HA
                  ok = mqttPublishWithRetryHA(MQTT_HA_GPS_TOPIC, payload, true) || ok;
                #endif

                #if ENABLE_MQTT_CLOUD
                  ok = mqttPublishWithRetryCloud(MQTT_CLOUD_MASTER_GPS, payload, true) || ok;
                #endif


                DEBUG_PRINTLN(ok ? F("✅ MQTT GPS sunrise: OK") : F("❌ MQTT GPS sunrise: FAIL"));
                unlockMutex(modemMutex);
              }
            }
      #endif
    }
    else
    {
      DEBUG_PRINTLN(F("📍 Sunrise GPS: no STABLE fix (timeout/quality)"));
    }

    if (!rtcTheft)
    {
      gpsOff();
      DEBUG_PRINTLN(F("🛰️ GPS OFF for the day"));
    }
    else
    {
      DEBUG_PRINTLN(F("🚨 Theft mode ON (GPS rămâne ON - pasul următor)"));
    }
  }

  xTaskCreatePinnedToCore(
    taskLocalCore,
    "taskLocalCore",
    12000,
    nullptr,
    2,
    &taskLocalHandle,
    0
  );

  xTaskCreatePinnedToCore(
    task4GCore,
    "task4GCore",
    16000,
    nullptr,
    1,
    &task4GHandle,
    1
  );

  DEBUG_PRINTLN(F("✅ FreeRTOS tasks started"));
}

// ===================== LOOP =====================
void loop()
{
  vTaskDelay(pdMS_TO_TICKS(1000));
}

#ifndef TINY_GSM_FORK_LIBRARY
  #error "Trebuie instalata TinyGSM-fork de lewisxhe (nu TinyGSM original)."
#endif
