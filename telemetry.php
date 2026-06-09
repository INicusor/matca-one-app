<?php
header("Content-Type: application/json");

$file       = "telemetry.json";
$histFile   = "telemetry_history.json";
$now        = time();

/* ===========================
   0) VIEW în browser (GET)
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (file_exists($file)) {
    // întoarce ultimul snapshot salvat
    echo file_get_contents($file);
  } else {
    echo json_encode(["status"=>"empty","message"=>"No telemetry saved yet"]);
  }
  exit;
}

/* ===========================
   1) POST (ESP32)
   =========================== */
$input = file_get_contents("php://input");
if (!$input) { echo json_encode(["status"=>"error","message"=>"No data received"]); exit; }

$data = json_decode($input, true);
if ($data === null) { echo json_encode(["status"=>"error","message"=>"Invalid JSON"]); exit; }

if (isset($data["type"])) $data = [$data]; // uniformizare obiect -> array
if (!is_array($data)) $data = [];

$last = null;
foreach ($data as $obj) {
  if (!is_array($obj)) continue;

  // acceptăm doar telemetry
  if (!isset($obj["type"]) || $obj["type"] !== "telemetry") continue;

  if (!isset($obj["ts"])) $obj["ts"] = $now;

  // păstrăm ultimul pachet ca snapshot
  $last = $obj;

  // history (opțional)
  $hist = file_exists($histFile) ? json_decode(file_get_contents($histFile), true) : [];
  if (!is_array($hist)) $hist = [];

  $hist[] = $obj;
  if (count($hist) > 2000) $hist = array_slice($hist, -2000);
  file_put_contents($histFile, json_encode($hist, JSON_PRETTY_PRINT));
}

if ($last) {
  file_put_contents($file, json_encode($last, JSON_PRETTY_PRINT));
  echo json_encode(["status"=>"success","message"=>"Telemetry saved"]);
} else {
  echo json_encode(["status"=>"ignored","message"=>"No telemetry object in payload"]);
}
?>