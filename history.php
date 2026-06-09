<?php
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_GET['chipID'])) {
    echo json_encode([]); exit;
}
$cid  = preg_replace('/[^0-9]/','', $_GET['chipID']);
$file = $_SERVER['DOCUMENT_ROOT'] . "/history/$cid.json";

if (!file_exists($file)) {
    echo json_encode([]); exit;
}

$rows = json_decode(file_get_contents($file), true);
if (!is_array($rows)) {
    echo json_encode([]); exit;
}

// Temperatura curentă din data.json pentru acest stup
$currentTemp = null;
$dataFile = $_SERVER['DOCUMENT_ROOT'] . '/data.json';
if (file_exists($dataFile)) {
    $allData = json_decode(file_get_contents($dataFile), true) ?: [];
    foreach ($allData as $entry) {
        if (isset($entry['chipID']) && (string)$entry['chipID'] === $cid) {
            $currentTemp = isset($entry['temperature']) ? (float)$entry['temperature'] : null;
            break;
        }
    }
}

// Rânduri vechi fără temperatură → fallback la temperatura curentă
// Rânduri noi (după fix submit.php) → au deja temperatura, o păstrăm
foreach ($rows as &$row) {
    if (!isset($row['temperature']) || $row['temperature'] === null) {
        $row['temperature'] = $currentTemp;
    }
}
unset($row);

echo json_encode($rows);
?>