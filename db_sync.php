<?php
/* ================================================================
   db_sync.php  —  MatcaDB Parallel Writer v1.0
   ================================================================
   SCOP:
     Fișier inclus la SFÂRȘITUL submit.php, DUPĂ ce JSON-urile au
     fost deja salvate. Nu afectează cu NIMIC logica existentă.

   FUNCȚIONARE:
     submit.php scrie în JSON (neschimbat) → include 'db_sync.php'
     → db_sync.php scrie aceleași date și în MySQL, în paralel.

   SIGURANȚĂ:
     • Orice eroare DB este logată silențios — nu blochează nimic.
     • Dacă DB nu e disponibilă, aplicația funcționează normal.
     • Nu trimite niciun output (răspunsul a fost deja trimis).
   ================================================================ */

// Previne output accidental (răspunsul HTTP a fost deja trimis)
if (ob_get_level() > 0) @ob_end_clean();

/* ── Constante DB (din db_config.php) ───────────────────────── */
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/db_config.php';
}

/* ── Singleton PDO ──────────────────────────────────────────── */
function mpGetDB(): ?PDO {
    static $pdo    = null;
    static $failed = false;
    if ($failed) return null;
    if ($pdo !== null) return $pdo;
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 3,   // timeout 3s — nu bloca IoT
        ]);
    } catch (PDOException $e) {
        $failed = true;
        error_log('[MatcaDB/db_sync] Conexiune esuata: ' . $e->getMessage());
        $pdo = null;
    }
    return $pdo;
}

/* ── INSERT … ON DUPLICATE KEY UPDATE ──────────────────────── */
function mpUpsert(string $table, array $data): void {
    $pdo = mpGetDB();
    if (!$pdo || empty($data)) return;
    try {
        $cols         = array_keys($data);
        $colList      = implode(', ', array_map(fn($c) => "`$c`", $cols));
        $placeholders = implode(', ', array_map(fn($c) => ":$c", $cols));
        $updates      = implode(', ', array_map(fn($c) => "`$c` = VALUES(`$c`)", $cols));
        $sql  = "INSERT INTO `$table` ($colList) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
    } catch (PDOException $e) {
        error_log("[MatcaDB/db_sync] upsert $table: " . $e->getMessage());
    }
}

/* ================================================================
   MAIN — procesăm $newData (variabila din submit.php)
   $newData este array de obiecte citiri IoT, deja normalizat
   $existing este array-ul curent cu delta calculat
   ================================================================ */

// Verificăm că avem datele din submit.php în scope
if (empty($newData) || !is_array($newData)) return;

$pdo = mpGetDB();
if (!$pdo) return; // DB indisponibilă — exit silentios

foreach ($newData as $obj) {
    if (!isset($obj['chipID'])) continue;

    $chipId       = (string)$obj['chipID'];
    $controllerId = isset($obj['controllerID']) ? (string)$obj['controllerID'] : null;
    $ts           = isset($obj['lastUpdated'])  ? (int)$obj['lastUpdated']     : time();

    /* ── Delta calculat în submit.php ── */
    // Căutăm în $existing (deja updatat de submit.php) valoarea delta
    $delta   = 0;
    $delta24 = 0;
    if (!empty($existing) && is_array($existing)) {
        foreach ($existing as $ex) {
            if (isset($ex['chipID']) && (string)$ex['chipID'] === $chipId) {
                $delta   = (float)($ex['delta']   ?? 0);
                $delta24 = (float)($ex['delta24'] ?? 0);
                break;
            }
        }
    }

    /* ── 1. Scriere în mp_hive_readings (ultima citire per stup) ── */
    mpUpsert('mp_hive_readings', [
        'chip_id'          => $chipId,
        'controller_id'    => $controllerId,
        'slave_mac'        => $obj['slaveMac']        ?? null,
        'weight'           => isset($obj['weight'])      ? round((float)$obj['weight'],      4) : null,
        'temperature'      => isset($obj['temperature']) ? round((float)$obj['temperature'],  4) : null,
        'battery'          => isset($obj['battery'])     ? round((float)$obj['battery'],      4) : null,
        'wifi_signal'      => isset($obj['wifiSignal'])  ? (int)$obj['wifiSignal']              : null,
        'zi_noapte'        => $obj['ziNoapte']        ?? null,
        'firmware_version' => $obj['firmwareVersion'] ?? null,
        'posibil_roi'      => isset($obj['posibilROI']) ? (int)(bool)$obj['posibilROI'] : 0,
        'delta'            => round($delta,   4),
        'delta24'          => round($delta24, 4),
        'lat'              => isset($obj['lat'])  ? round((float)$obj['lat'],  8) : null,
        'lng'              => isset($obj['long']) ? round((float)$obj['long'], 8) : null,
        'ts'               => $ts,
    ]);

    /* ── 2. Scriere în mp_telemetry_history (istoric complet time-series) ── */
    // INSERT simplu fără ON DUPLICATE KEY — fiecare citire e un rând nou
    try {
        $stmtHist = $pdo->prepare("
            INSERT INTO `mp_telemetry_history`
                (chip_id, weight, temperature, battery, delta, delta24, ts)
            VALUES
                (:chip_id, :weight, :temperature, :battery, :delta, :delta24, :ts)
        ");
        $stmtHist->execute([
            ':chip_id'     => $chipId,
            ':weight'      => isset($obj['weight'])      ? round((float)$obj['weight'],      4) : null,
            ':temperature' => isset($obj['temperature']) ? round((float)$obj['temperature'],  4) : null,
            ':battery'     => isset($obj['battery'])     ? round((float)$obj['battery'],      4) : null,
            ':delta'       => round($delta,   4),
            ':delta24'     => round($delta24, 4),
            ':ts'          => $ts,
        ]);
    } catch (PDOException $e) {
        error_log("[MatcaDB/db_sync] telemetry_history $chipId: " . $e->getMessage());
    }

    /* ── 2. Update controller lastSeen (dacă există) ── */
    if ($controllerId) {
        $ctrlFile = __DIR__ . '/controllers.json';
        if (file_exists($ctrlFile)) {
            $ctrls = json_decode(file_get_contents($ctrlFile), true) ?: [];
            if (isset($ctrls[$controllerId])) {
                $ctrl = $ctrls[$controllerId];
                mpUpsert('mp_controllers', [
                    'controller_id' => $controllerId,
                    'name'          => $ctrl['name']    ?? 'Gateway_' . $controllerId,
                    'chip_ids'      => json_encode($ctrl['chipIDs'] ?? []),
                    'last_seen'     => (int)($ctrl['lastSeen'] ?? time()),
                ]);
            }
        }
    }
}

/* ── Log simplu la fiecare 100 de scrieri (optional, util debug) ── */
// error_log('[MatcaDB/db_sync] Scrise ' . count($newData) . ' citiri IoT la ' . date('H:i:s'));