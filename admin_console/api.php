<?php
require_once __DIR__ . '/auth.php';
admin_require_auth();

if (!csrf_validate()) json_error('CSRF invalid', 403);

$action = $_REQUEST['action'] ?? '';

// ═══════════════════════════════════════════════════════════════
// DASHBOARD STATS
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_dashboard_stats') {
    $pdo = getAdminDB();
    $now = time();

    // ── Status stupi ─────────────────────────────────────────
    // Incearca DB, fallback JSON
    $online = 0; $offline = 0; $warning = 0;
    $totalW = 0; $wCnt   = 0;
    $dbHiveCount = 0;

    if ($pdo) {
        try {
            $hiveRows = $pdo->query("
                SELECT r.chip_id, r.weight, r.ts as lastUpdated
                FROM mp_hive_readings r
                INNER JOIN (SELECT chip_id, MAX(ts) as max_ts FROM mp_hive_readings GROUP BY chip_id) l
                  ON r.chip_id = l.chip_id AND r.ts = l.max_ts
            ")->fetchAll();
            $dbHiveCount = count($hiveRows);
            foreach ($hiveRows as $h) {
                $diff = $now - (int)($h['lastUpdated'] ?? 0);
                if ($diff < 7200)      $online++;
                elseif ($diff < 86400) $warning++;
                else                   $offline++;
                if (($h['weight'] ?? 0) > 1) { $totalW += $h['weight']; $wCnt++; }
            }
        } catch (PDOException $e) {}
    }

    // Fallback JSON daca DB nu are date
    if ($dbHiveCount === 0) {
        $data = read_json('data.json');
        foreach ($data as $h) {
            $diff = $now - ($h['lastUpdated'] ?? 0);
            if ($diff < 7200)      $online++;
            elseif ($diff < 86400) $warning++;
            else                   $offline++;
            if (($h['weight'] ?? 0) > 1) { $totalW += $h['weight']; $wCnt++; }
        }
        $dbHiveCount = count($data);
    }

    // ── Counts din DB ─────────────────────────────────────────
    $usersTotal   = 0;
    $jurnalTotal  = 0;
    $tasksPending = 0;
    $totalReadings= 0;
    $totalKg      = 0;
    $totalRon     = 0;
    $alertsActive = 0;

    if ($pdo) {
        try { $usersTotal   = (int)$pdo->query("SELECT COUNT(*) FROM mp_users")->fetchColumn(); } catch (PDOException $e) {}
        try { $jurnalTotal  = (int)$pdo->query("SELECT COUNT(*) FROM mp_jurnal")->fetchColumn(); } catch (PDOException $e) {}
        try { $tasksPending = (int)$pdo->query("SELECT COUNT(*) FROM mp_tasks WHERE done=0")->fetchColumn(); } catch (PDOException $e) {}
        try { $totalReadings= (int)$pdo->query("SELECT COUNT(*) FROM mp_hive_readings")->fetchColumn(); } catch (PDOException $e) {}
        try {
            $row = $pdo->query("SELECT SUM(kg) as kg, SUM(kg*pret) as ron FROM mp_harvest")->fetch();
            $totalKg  = round(floatval($row['kg']  ?? 0), 2);
            $totalRon = round(floatval($row['ron'] ?? 0), 2);
        } catch (PDOException $e) {}
    }

    // Fallback JSON pentru counts
    if ($usersTotal === 0) {
        $users = read_json('user.json');
        $usersTotal = count($users);
    }
    if ($tasksPending === 0) {
        $tasks = read_json('tasks.json');
        foreach ($tasks as $t) { if (empty($t['done'])) $tasksPending++; }
    }
    if ($totalKg === 0) {
        $harvest = read_json('harvest.json');
        foreach ($harvest as $h) {
            $totalKg  += floatval($h['kg']   ?? 0);
            $totalRon += floatval($h['kg']   ?? 0) * floatval($h['pret'] ?? 0);
        }
        $totalKg  = round($totalKg,  2);
        $totalRon = round($totalRon, 2);
    }
    if ($totalReadings === 0) {
        $histDir   = APP_ROOT . '/history';
        $histFiles = glob($histDir . '/*.json') ?: [];
        foreach ($histFiles as $f) {
            $d = json_decode(file_get_contents($f), true);
            if (is_array($d)) $totalReadings += count($d);
        }
    }

    // ── Activitate 7 zile ────────────────────────────────────
    $activity = [];
    for ($i = 6; $i >= 0; $i--) {
        $activity[date('d.m', strtotime("-$i days"))] = 0;
    }
    if ($pdo) {
        try {
            $rows = $pdo->query("SELECT date FROM mp_jurnal WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchAll();
            foreach ($rows as $j) {
                $d = substr($j['date'] ?? '', 0, 5);
                if (isset($activity[$d])) $activity[$d]++;
            }
        } catch (PDOException $e) {
            // Fallback JSON
            $jurnal = read_json('jurnal.json');
            foreach ($jurnal as $j) {
                $d = substr($j['date'] ?? '', 0, 5);
                if (isset($activity[$d])) $activity[$d]++;
            }
        }
    } else {
        $jurnal = read_json('jurnal.json');
        foreach ($jurnal as $j) {
            $d = substr($j['date'] ?? '', 0, 5);
            if (isset($activity[$d])) $activity[$d]++;
        }
    }

    json_ok([
        'stupi_total'    => $dbHiveCount,
        'stupi_online'   => $online,
        'stupi_warning'  => $warning,
        'stupi_offline'  => $offline,
        'users_total'    => $usersTotal,
        'alerte_active'  => $alertsActive,
        'jurnal_total'   => $jurnalTotal,
        'tasks_pending'  => $tasksPending,
        'recolta_kg'     => $totalKg,
        'recolta_ron'    => $totalRon,
        'total_readings' => $totalReadings,
        'activity'       => $activity,   // ← 'activity' nu 'activity_chart'
    ]);
}
// ═══════════════════════════════════════════════════════════════
// STUPI
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_hives') {
    $pdo  = getAdminDB();
    $now  = time();
    $result = [];

    // Citim intotdeauna si JSON-ul ca sursa de adevar pentru datele live
    $dataJson   = read_json('data.json');
    $metaJson   = read_json('metadata.json');
    $manualJson = read_json('manual_hives.json');
    $ctrlJson   = read_json('controllers.json');

    // Indexam JSON dupa chipID
    $dataByChip = [];
    foreach ($dataJson as $h) { $dataByChip[(string)$h['chipID']] = $h; }

    // Map controller din JSON
    $chipToCtrl = [];
    foreach ($ctrlJson as $cKey => $c) {
        foreach ($c['chipIDs'] ?? [] as $cid) {
            $chipToCtrl[(string)$cid] = ['id'=>(string)$cKey,'name'=>$c['name']??$cKey];
        }
    }

    if ($pdo) {
        try {
            // Ultima citire per chip din DB - folosim pentru date tehnice
            $rows = $pdo->query("
                SELECT r.*, m.nickname, m.q_color, m.q_year, m.q_breed, m.q_score,
                       m.maintenance, m.supers, m.lat, m.lng,
                       m.weight_ref
                FROM mp_hive_readings r
                INNER JOIN (SELECT chip_id, MAX(ts) as max_ts FROM mp_hive_readings GROUP BY chip_id) l
                  ON r.chip_id = l.chip_id AND r.ts = l.max_ts
                LEFT JOIN mp_metadata m ON r.chip_id = m.chip_id
                ORDER BY r.chip_id
            ")->fetchAll();

            // Controllere pentru nume
            $ctrls = [];
            try {
                $ctrlRows = $pdo->query("SELECT controller_id, name, chip_ids FROM mp_controllers")->fetchAll();
                foreach ($ctrlRows as $c) {
                    $chips = json_decode($c['chip_ids'] ?? '[]', true) ?: [];
                    foreach ($chips as $cid) {
                        $ctrls[(string)$cid] = ['id' => $c['controller_id'], 'name' => $c['name'] ?? $c['controller_id']];
                    }
                }
            } catch (PDOException $e) {}

            foreach ($rows as $h) {
                $cid      = (string)$h['chip_id'];
                $diff     = $now - (int)($h['ts'] ?? 0);
                $status   = $diff < 7200 ? 'online' : ($diff < 86400 ? 'warning' : 'offline');
                $ctrlInfo = $ctrls[$cid] ?? null;
                $result[] = [
                    'chipID'       => $cid,
                    'nickname'     => $h['nickname']    ?? 'Stup '.$cid,
                    'weight'       => round(floatval($h['weight']      ?? 0), 3),
                    'temperature'  => round(floatval($h['temperature'] ?? 0), 2),
                    'battery'      => round(floatval($h['battery']     ?? 0), 3),
                    'delta24'      => round(floatval($h['delta24']     ?? 0), 3),
                    'delta'        => round(floatval($h['delta']       ?? 0), 3),
                    'status'       => $status,
                    'last_updated' => (int)($h['ts'] ?? 0),
                    'firmware'     => $h['firmware_version'] ?? '',
                    'wifi'         => (int)($h['wifi_signal'] ?? 0),
                    'controller'   => $ctrlInfo ? $ctrlInfo['name'] : (string)($h['controller_id'] ?? ''),
                    'controllerID' => $ctrlInfo ? $ctrlInfo['id']   : (string)($h['controller_id'] ?? ''),
                    'qColor'       => $h['q_color']    ?? 'transparent',
                    'qYear'        => $h['q_year']     ?? '',
                    'qBreed'       => $h['q_breed']    ?? '',
                    'qScore'       => (int)($h['q_score'] ?? 5),
                    'supers'       => (int)($h['supers']  ?? 0),
                    'maintenance'  => (bool)($h['maintenance'] ?? false),
                    'lat'          => $h['lat'] ?? null,
                    'lng'          => $h['lng'] ?? null,
                    'isManual'     => false,
                    'creator'      => '',
                ];
            }

            // Stupi manuali fara readings
            $manualOnly = $pdo->query("SELECT h.chip_id, h.weight, h.temperature, h.battery, h.delta24, h.ts, h.creator, m.nickname, m.q_color, m.q_year, m.q_breed, m.q_score FROM mp_manual_hives h LEFT JOIN mp_metadata m ON h.chip_id = m.chip_id")->fetchAll();
            $withReadings = array_column($result, 'chipID');
            foreach ($manualOnly as $m) {
                if (!in_array($m['chip_id'], $withReadings)) {
                    $result[] = [
                        'chipID'       => $m['chip_id'],
                        'nickname'     => $m['nickname']  ?? $m['chip_id'],
                        'weight'       => 0, 'temperature' => 0,
                        'battery'      => 4.2, 'delta24' => 0, 'delta' => 0,
                        'status'       => 'manual',
                        'last_updated' => 0, 'firmware' => 'manual', 'wifi' => 0,
                        'controller'   => '', 'controllerID' => '',
                        'qColor'       => $m['q_color']  ?? 'transparent',
                        'qYear'        => $m['q_year']   ?? '',
                        'qBreed'       => $m['q_breed']  ?? '',
                        'qScore'       => (int)($m['q_score'] ?? 5),
                        'supers'       => (int)($m['supers']  ?? 0),
                        'maintenance'  => (bool)($m['maintenance'] ?? false),
                        'lat'          => null, 'lng' => null,
                        'isManual'     => true,
                        'creator'      => $m['creator']  ?? '',
                    ];
                }
            }
            // Adaugam din JSON stupii care nu sunt in DB (submit.php nou poate nu ruleaza inca)
            $chipsInDB = array_column($result, 'chipID');
            foreach ($dataByChip as $cid => $h) {
                if (in_array($cid, $chipsInDB)) continue; // deja in result din DB
                $m      = $metaJson[$cid] ?? [];
                $diff   = $now - ($h['lastUpdated'] ?? 0);
                $status = $diff < 7200 ? 'online' : ($diff < 86400 ? 'warning' : 'offline');
                $ctrlInfo = $chipToCtrl[$cid] ?? null;
                $result[] = [
                    'chipID'       => $cid,
                    'nickname'     => $m['nickname']         ?? 'Stup '.$cid,
                    'weight'       => round(floatval($h['weight']      ?? 0), 3),
                    'temperature'  => round(floatval($h['temperature'] ?? 0), 2),
                    'battery'      => round(floatval($h['battery']     ?? 0), 3),
                    'delta24'      => round(floatval($h['delta24']     ?? 0), 3),
                    'delta'        => round(floatval($h['delta']       ?? 0), 3),
                    'status'       => $status,
                    'last_updated' => (int)($h['lastUpdated'] ?? 0),
                    'firmware'     => $h['firmwareVersion']  ?? '',
                    'wifi'         => (int)($h['wifiSignal'] ?? 0),
                    'controller'   => $ctrlInfo ? $ctrlInfo['name'] : (string)($h['controllerID'] ?? ''),
                    'controllerID' => $ctrlInfo ? $ctrlInfo['id']   : (string)($h['controllerID'] ?? ''),
                    'qColor'       => $m['qColor']           ?? 'transparent',
                    'qYear'        => $m['qYear']            ?? '',
                    'qBreed'       => $m['qBreed']           ?? '',
                    'qScore'       => (int)($m['qScore']     ?? 5),
                    'supers'       => (int)($m['supers']     ?? 0),
                    'maintenance'  => (bool)($m['maintenance'] ?? false),
                    'lat'          => $m['lat'] ?? null,
                    'lng'          => $m['lng'] ?? null,
                    'isManual'     => false,
                    'creator'      => '',
                ];
            }
            json_ok($result);
        } catch (PDOException $e) {
            error_log('[get_hives] DB error: ' . $e->getMessage());
            // Fallback complet la JSON
        }
    }

    // Fallback JSON complet daca DB nu e disponibil
    foreach ($dataByChip as $cid => $h) {
        $m        = $metaJson[$cid] ?? [];
        $diff     = $now - ($h['lastUpdated'] ?? 0);
        $status   = $diff < 7200 ? 'online' : ($diff < 86400 ? 'warning' : 'offline');
        $ctrlInfo = $chipToCtrl[$cid] ?? null;
        $result[] = [
            'chipID'       => $cid,
            'nickname'     => $m['nickname']         ?? 'Stup '.$cid,
            'weight'       => round(floatval($h['weight']      ?? 0), 3),
            'temperature'  => round(floatval($h['temperature'] ?? 0), 2),
            'battery'      => round(floatval($h['battery']     ?? 0), 3),
            'delta24'      => round(floatval($h['delta24']     ?? 0), 3),
            'delta'        => round(floatval($h['delta']       ?? 0), 3),
            'status'       => $status,
            'last_updated' => (int)($h['lastUpdated'] ?? 0),
            'firmware'     => $h['firmwareVersion']  ?? '',
            'wifi'         => (int)($h['wifiSignal'] ?? 0),
            'controller'   => $ctrlInfo ? $ctrlInfo['name'] : (string)($h['controllerID'] ?? ''),
            'controllerID' => $ctrlInfo ? $ctrlInfo['id']   : (string)($h['controllerID'] ?? ''),
            'qColor'       => $m['qColor']           ?? 'transparent',
            'qYear'        => $m['qYear']            ?? '',
            'qBreed'       => $m['qBreed']           ?? '',
            'qScore'       => (int)($m['qScore']     ?? 5),
            'supers'       => (int)($m['supers']     ?? 0),
            'maintenance'  => (bool)($m['maintenance'] ?? false),
            'lat'          => $m['lat'] ?? null,
            'lng'          => $m['lng'] ?? null,
            'isManual'     => false,
            'creator'      => '',
        ];
    }
    foreach ($manualJson as $h) {
        $cid = (string)$h['chipID'];
        $m   = $metaJson[$cid] ?? [];
        $result[] = [
            'chipID'       => $cid,
            'nickname'     => $m['nickname'] ?? $cid,
            'weight'       => $h['weight']       ?? 0,
            'temperature'  => $h['temperature']  ?? 0,
            'battery'      => $h['battery']      ?? 4.2,
            'delta24'      => $h['delta24']      ?? 0,
            'delta'        => 0,
            'status'       => 'manual',
            'last_updated' => $h['ts']           ?? 0,
            'firmware'     => 'manual',
            'wifi'         => 0,
            'controller'   => '', 'controllerID' => '',
            'qColor'       => $m['qColor']       ?? 'transparent',
            'qYear'        => $m['qYear']        ?? '',
            'qBreed'       => $m['qBreed']       ?? '',
            'qScore'       => (int)($m['qScore'] ?? 5),
            'supers'       => (int)($m['supers'] ?? 0),
            'maintenance'  => (bool)($m['maintenance'] ?? false),
            'lat'          => null, 'lng' => null,
            'isManual'     => true,
            'creator'      => $h['creator'] ?? '',
        ];
    }
    json_ok($result);
}

if ($action === 'update_hive_meta') {
    $chipID = trim($_POST['chipID'] ?? '');
    if (!$chipID) json_error('chipID lipsa');

    $meta = read_json('metadata.json');
    if (!isset($meta[$chipID])) $meta[$chipID] = [];

    $fields = ['nickname','qColor','qYear','qBreed','qScore','supers','maintenance','lat','lng','parent'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $val = $_POST[$f];
            if ($f === 'maintenance') $val = ($val === 'true' || $val === '1');
            if (in_array($f, ['supers','qScore'])) $val = intval($val);
            if (in_array($f, ['lat','lng'])) $val = ($val === '') ? null : floatval($val);
            $meta[$chipID][$f] = $val;
        }
    }
    write_json('metadata.json', $meta);
    dbSync('hive_metadata', [
        'chip_id'     => $chipID,
        'nickname'    => $meta[$chipID]['nickname']    ?? '',
        'q_color'     => $meta[$chipID]['qColor']      ?? 'transparent',
        'q_year'      => $meta[$chipID]['qYear']       ?? '',
        'supers'      => $meta[$chipID]['supers']      ?? 0,
        'maintenance' => !empty($meta[$chipID]['maintenance']) ? 1 : 0,
    ], 'chip_id');

    audit('HIVE_META', "Actualizat metadata stup $chipID");
    json_ok($meta[$chipID]);
}

if ($action === 'delete_hive_manual') {
    $chipID = trim($_POST['chipID'] ?? '');
    if (!$chipID) json_error('chipID lipsa');
    if (strpos($chipID, 'M') !== 0) json_error('Doar stupii manuali pot fi stersi');

    $manual = read_json('manual_hives.json');
    $new = [];
    foreach ($manual as $h) {
        if ((string)$h['chipID'] !== $chipID) $new[] = $h;
    }
    write_json('manual_hives.json', $new);

    $meta = read_json('metadata.json');
    unset($meta[$chipID]);
    write_json('metadata.json', $meta);

    dbDelete('manual_hives', $chipID, 'chip_id');
    audit('HIVE_DELETE', "Sters stup manual $chipID");
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// USERS
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_users') {
    $users = read_json('user.json');
    $meta  = read_json('metadata.json');
    $data  = read_json('data.json');

    $allHiveIDs = [];
    foreach ($data as $h) $allHiveIDs[] = (string)$h['chipID'];

    $allNames = [];
    foreach ($allHiveIDs as $id) {
        $allNames[$id] = $meta[$id]['nickname'] ?? 'Stup '.$id;
    }

    $result = [];
    foreach ($users as $uname => $u) {
        $hiveNames = [];
        foreach ($u['hives'] ?? [] as $id) {
            $hiveNames[] = $allNames[$id] ?? $id;
        }
        $result[] = [
            'username'          => $uname,
            'email'             => $u['email']             ?? '',
            'is_admin'          => !empty($u['is_admin']),
            'can_manage_manual' => !empty($u['can_manage_manual']),
            'hives'             => $u['hives']             ?? [],
            'controllers'       => $u['controllers']       ?? [],
            'hive_names'        => $hiveNames,
        ];
    }
    json_ok(['users' => $result, 'all_hives' => $allNames]);
}

if ($action === 'create_user') {
    $uname = trim($_POST['username'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    if (!$uname || strlen($uname) < 2) json_error('Username prea scurt');
    if (strlen($pass) < 6) json_error('Parola min 6 caractere');

    $users = read_json('user.json');
    if (isset($users[$uname])) json_error('Utilizatorul exista deja');

    $users[$uname] = [
        'password'          => password_hash($pass, PASSWORD_BCRYPT),
        'email'             => $email,
        'hives'             => [],
        'controllers'       => [],
        'can_manage_manual' => !empty($_POST['can_manage_manual']),
        'is_admin'          => !empty($_POST['is_admin']),
    ];
    write_json('user.json', $users);
    audit('USER_CREATE', "Creat user: $uname");
    json_ok();
}

if ($action === 'update_user') {
    $uname = trim($_POST['username'] ?? '');
    if (!$uname) json_error('Username lipsa');

    $users = read_json('user.json');
    if (!isset($users[$uname])) json_error('Utilizatorul nu exista');

    if (!empty($_POST['password'])) {
        if (strlen($_POST['password']) < 6) json_error('Parola min 6 caractere');
        $users[$uname]['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }
    if (isset($_POST['email']))             $users[$uname]['email']             = trim($_POST['email']);
    if (isset($_POST['is_admin']))          $users[$uname]['is_admin']          = ($_POST['is_admin'] === 'true');
    if (isset($_POST['can_manage_manual'])) $users[$uname]['can_manage_manual'] = ($_POST['can_manage_manual'] === 'true');
    if (isset($_POST['hives']))             $users[$uname]['hives']             = json_decode($_POST['hives'], true) ?: [];
    if (isset($_POST['controllers']))       $users[$uname]['controllers']       = json_decode($_POST['controllers'], true) ?: [];

    write_json('user.json', $users);
    audit('USER_UPDATE', "Actualizat user: $uname");
    json_ok();
}

if ($action === 'delete_user') {
    $uname = trim($_POST['username'] ?? '');
    if (!$uname || $uname === 'admin') json_error('Nu poti sterge userul admin');
    $users = read_json('user.json');
    if (!isset($users[$uname])) json_error('Utilizatorul nu exista');
    unset($users[$uname]);
    write_json('user.json', $users);
    dbDelete('users', $uname, 'username');
    audit('USER_DELETE', "Sters user: $uname");
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// CONTROLLERS
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_controllers') {
    $pdo = getAdminDB();
    $now = time();
    $result = [];

    if ($pdo) {
        try {
            $ctrls = $pdo->query("SELECT * FROM mp_controllers ORDER BY name")->fetchAll();

            // Ultima citire per chip
            $latestReadings = [];
            $readRows = $pdo->query("
                SELECT r.chip_id, r.weight, r.ts as lastUpdated
                FROM mp_hive_readings r
                INNER JOIN (SELECT chip_id, MAX(ts) as max_ts FROM mp_hive_readings GROUP BY chip_id) l
                  ON r.chip_id = l.chip_id AND r.ts = l.max_ts
            ")->fetchAll();
            foreach ($readRows as $r) $latestReadings[(string)$r['chip_id']] = $r;

            // Metadata
            $metaRows = $pdo->query("SELECT chip_id, nickname FROM mp_metadata")->fetchAll();
            $metaMap  = [];
            foreach ($metaRows as $m) $metaMap[$m['chip_id']] = $m['nickname'];

            foreach ($ctrls as $c) {
                $chipIDs  = json_decode($c['chip_ids'] ?? '[]', true) ?: [];
                $hiveList = [];
                foreach ($chipIDs as $cid) {
                    $cid    = (string)$cid;
                    $r      = $latestReadings[$cid] ?? null;
                    $online = $r ? (($now - ($r['lastUpdated'] ?? 0)) < 7200) : false;
                    $hiveList[] = [
                        'chipID'   => $cid,
                        'nickname' => $metaMap[$cid] ?? 'Stup '.$cid,
                        'online'   => $online,
                        'weight'   => $r ? round(floatval($r['weight']), 2) : 0,
                    ];
                }
                $result[] = [
                    'id'         => $c['controller_id'],
                    'name'       => $c['name']     ?? $c['controller_id'],
                    'lastSeen'   => (int)($c['last_seen'] ?? 0),
                    'ip'         => $c['ip']        ?? '',
                    'vbat'       => $c['vbat']      ?? null,
                    'vsolar'     => $c['vsolar']    ?? null,
                    'hives'      => $hiveList,
                    'hive_count' => count($hiveList),
                ];
            }
            json_ok($result);
        } catch (PDOException $e) {
            error_log('[get_controllers] DB error: ' . $e->getMessage());
            // Fallback la JSON
        }
    }

    // Fallback JSON
    $ctrl = read_json('controllers.json');
    $meta = read_json('metadata.json');
    $data = read_json('data.json');
    $dataByChip = [];
    foreach ($data as $h) $dataByChip[(string)$h['chipID']] = $h;
    foreach ($ctrl as $id => $c) {
        $chipIDs  = $c['chipIDs'] ?? [];
        $hiveList = [];
        foreach ($chipIDs as $cid) {
            $cid  = (string)$cid;
            $h    = $dataByChip[$cid] ?? null;
            $hiveList[] = ['chipID'=>$cid,'nickname'=>$meta[$cid]['nickname']??'Stup '.$cid,'online'=>$h?(($now-($h['lastUpdated']??0))<7200):false,'weight'=>$h['weight']??0];
        }
        $result[] = ['id'=>$id,'name'=>$c['name']??$id,'lastSeen'=>$c['lastSeen']??0,'ip'=>$c['ip']??'','hives'=>$hiveList,'hive_count'=>count($hiveList)];
    }
    json_ok($result);
}

if ($action === 'update_controller') {
    $id   = trim($_POST['id']   ?? '');
    $name = trim($_POST['name'] ?? '');
    if (!$id) json_error('Controller ID lipsa');
    $ctrl = read_json('controllers.json');
    if (!isset($ctrl[$id])) json_error('Controller negasit');
    if ($name) $ctrl[$id]['name'] = $name;
    write_json('controllers.json', $ctrl);
    audit('CTRL_UPDATE', "Actualizat controller $id → $name");
    json_ok();
}

if ($action === 'move_hive_to_controller') {
    $chipID  = trim($_POST['chipID']         ?? '');
    $newCtrl = trim($_POST['new_controller'] ?? '');
    if (!$chipID) json_error('chipID lipsa');

    $data    = read_json('data.json');
    $updated = false;
    foreach ($data as &$h) {
        if ((string)$h['chipID'] === $chipID) {
            $oldCtrl = $h['controllerID'] ?? '';
            $h['controllerID'] = $newCtrl;
            $updated = true;
            audit('HIVE_MOVE', "Mutat stup $chipID: $oldCtrl → $newCtrl");
            break;
        }
    }
    unset($h);
    if (!$updated) json_error('Stup negasit');
    write_json('data.json', $data);
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// JURNAL
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_jurnal') {
    $jurnal = read_json('jurnal.json');
    $page   = max(1, intval($_GET['page']   ?? 1));
    $limit  = intval($_GET['limit']         ?? 50);
    $filter = strtolower(trim($_GET['filter'] ?? ''));
    $stup   = trim($_GET['stup']            ?? '');
    $user   = trim($_GET['user']            ?? '');

    if ($filter && $filter !== 'toate') {
        $keyword = '';
        if ($filter === 'inspectii')  $keyword = 'inspect';
        elseif ($filter === 'tratamente') $keyword = 'tratam';
        elseif ($filter === 'sarcini')    $keyword = 'sarcin';
        elseif ($filter === 'ok')         $keyword = 'stup ok';
        else $keyword = $filter;

        $filtered = [];
        foreach ($jurnal as $j) {
            if (strpos(strtolower($j['text'] ?? ''), $keyword) !== false) $filtered[] = $j;
        }
        $jurnal = $filtered;
    }

    if ($stup) {
        $filtered = [];
        foreach ($jurnal as $j) { if (($j['stup'] ?? '') === $stup) $filtered[] = $j; }
        $jurnal = $filtered;
    }
    if ($user) {
        $filtered = [];
        foreach ($jurnal as $j) { if (($j['user'] ?? '') === $user) $filtered[] = $j; }
        $jurnal = $filtered;
    }

    $total = count($jurnal);
    $paged = array_slice($jurnal, ($page-1)*$limit, $limit);
    json_ok(['items' => $paged, 'total' => $total, 'pages' => (int)ceil($total/$limit)]);
}

if ($action === 'delete_jurnal') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) json_error('ID lipsa');
    $jurnal = read_json('jurnal.json');
    $new = [];
    foreach ($jurnal as $j) { if ($j['id'] !== $id) $new[] = $j; }
    write_json('jurnal.json', $new);
    dbDelete('jurnal', $id);
    audit('JURNAL_DELETE', "Sters nota $id");
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// HARVEST & EXPENSES
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_harvest') {
    $harvest  = read_json('harvest.json');
    $expenses = read_json('expenses.json');
    $stup = trim($_GET['stup'] ?? '');
    $year = trim($_GET['year'] ?? '');

    if ($stup) {
        $filtered = [];
        foreach ($harvest as $h) { if (($h['stup'] ?? '') === $stup) $filtered[] = $h; }
        $harvest = $filtered;
    }
    if ($year) {
        $filtered = [];
        foreach ($harvest as $h) { if (strpos($h['date'] ?? '', $year) !== false) $filtered[] = $h; }
        $harvest = $filtered;
    }

    $totalKg = 0; $totalRon = 0;
    foreach ($harvest as $h) {
        $totalKg  += floatval($h['kg'] ?? 0);
        $totalRon += floatval($h['kg'] ?? 0) * floatval($h['pret'] ?? 0);
    }
    $totalExp = 0;
    foreach ($expenses as $e) { $totalExp += floatval($e['suma'] ?? 0); }

    json_ok([
        'harvest'   => array_values($harvest),
        'expenses'  => array_values($expenses),
        'total_kg'  => round($totalKg, 2),
        'total_ron' => round($totalRon, 2),
        'total_exp' => round($totalExp, 2),
        'profit'    => round($totalRon - $totalExp, 2),
    ]);
}

if ($action === 'delete_harvest') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) json_error('ID lipsa');
    $h  = read_json('harvest.json');
    $new = [];
    foreach ($h as $r) { if ($r['id'] !== $id) $new[] = $r; }
    write_json('harvest.json', $new);
    dbDelete('harvest', $id);
    audit('HARVEST_DELETE', "Sters recolta $id");
    json_ok();
}

if ($action === 'delete_expense') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) json_error('ID lipsa');
    $e  = read_json('expenses.json');
    $new = [];
    foreach ($e as $r) { if ($r['id'] !== $id) $new[] = $r; }
    write_json('expenses.json', $new);
    dbDelete('expenses', $id);
    audit('EXPENSE_DELETE', "Stearsa cheltuiala $id");
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// ALERTE
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_alerts') {
    $log  = read_json('alerte_log.json');
    $rez  = read_json('alerte_rezolvate.json');
    $meta = read_json('metadata.json');

    $active = [];
    foreach ($log as $id => $a) {
        if (!empty($a['active'])) {
            $cid  = explode('_', $id)[0] ?? '';
            $name = $meta[$cid]['nickname'] ?? $cid;
            $active[] = array_merge($a, ['id' => $id, 'hive_name' => $name]);
        }
    }
    json_ok(['active' => $active, 'resolved' => array_slice($rez, 0, 200)]);
}

if ($action === 'resolve_alert') {
    $id   = trim($_POST['id'] ?? '');
    $user = admin_current_user();
    if (!$id) json_error('ID lipsa');
    $log = read_json('alerte_log.json');
    if (!isset($log[$id])) json_error('Alerta negasita');
    $rez   = read_json('alerte_rezolvate.json');
    $rez[] = [
        'alert_id' => $id,
        'stup'     => $log[$id]['stup']  ?? '',
        'msg'      => $log[$id]['msg']   ?? '',
        'date'     => date('d.m.Y H:i'),
        'user'     => $user,
        'hive_ts'  => $log[$id]['ts']    ?? 0,
    ];
    write_json('alerte_rezolvate.json', $rez);
    unset($log[$id]);
    write_json('alerte_log.json', $log);
    audit('ALERT_RESOLVE', "Rezolvata alerta $id");
    json_ok();
}

if ($action === 'resolve_all_alerts') {
    $log  = read_json('alerte_log.json');
    $rez  = read_json('alerte_rezolvate.json');
    $user = admin_current_user();
    $cnt  = 0;
    foreach ($log as $id => $a) {
        if (!empty($a['active'])) {
            $rez[] = [
                'alert_id' => $id,
                'stup'     => $a['stup']  ?? '',
                'msg'      => $a['msg']   ?? '',
                'date'     => date('d.m.Y H:i'),
                'user'     => $user,
                'hive_ts'  => $a['ts']    ?? 0,
            ];
            unset($log[$id]);
            $cnt++;
        }
    }
    write_json('alerte_log.json', $log);
    write_json('alerte_rezolvate.json', $rez);
    audit('ALERT_RESOLVE_ALL', "Rezolvate toate alertele ($cnt)");
    json_ok(['count' => $cnt]);
}

// ═══════════════════════════════════════════════════════════════
// TASKS
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_tasks') {
    $tasks  = read_json('tasks.json');
    $filter = $_GET['filter'] ?? 'all';
    $user   = trim($_GET['user'] ?? '');

    if ($filter === 'pending') {
        $f = [];
        foreach ($tasks as $t) { if (empty($t['done'])) $f[] = $t; }
        $tasks = $f;
    }
    if ($filter === 'done') {
        $f = [];
        foreach ($tasks as $t) { if (!empty($t['done'])) $f[] = $t; }
        $tasks = $f;
    }
    if ($user) {
        $f = [];
        foreach ($tasks as $t) { if (($t['user'] ?? '') === $user) $f[] = $t; }
        $tasks = $f;
    }
    json_ok(array_values($tasks));
}

if ($action === 'delete_task') {
    $id = trim($_POST['id'] ?? '');
    $t  = read_json('tasks.json');
    $new = [];
    foreach ($t as $r) { if ($r['id'] !== $id) $new[] = $r; }
    write_json('tasks.json', $new);
    dbDelete('tasks', $id);
    audit('TASK_DELETE', "Stearsa sarcina $id");
    json_ok();
}

if ($action === 'toggle_task') {
    $id = trim($_POST['id'] ?? '');
    $t  = read_json('tasks.json');
    foreach ($t as &$task) {
        if ($task['id'] === $id) {
            $task['done'] = !($task['done'] ?? false);
            break;
        }
    }
    unset($task);
    write_json('tasks.json', $t);
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// INVENTORY
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_inventory') {
    json_ok(read_json('inventory.json'));
}

if ($action === 'save_inventory_item') {
    $inv  = read_json('inventory.json');
    $id   = trim($_POST['id']   ?? '') ?: uniqid();
    $item = trim($_POST['item'] ?? '');
    if (!$item) json_error('Nume produs lipsa');

    $row = [
        'id'       => $id,
        'item'     => $item,
        'qty'      => floatval($_POST['qty']      ?? 0),
        'type'     => trim($_POST['type']         ?? 'Bucati'),
        'category' => trim($_POST['category']     ?? ''),
    ];

    $found = false;
    foreach ($inv as &$r) {
        if ($r['id'] === $id) { $r = $row; $found = true; break; }
    }
    unset($r);
    if (!$found) $inv[] = $row;
    write_json('inventory.json', $inv);
    dbSync('inventory', $row);
    audit('INVENTORY_SAVE', "Salvat produs: $item");
    json_ok($row);
}

if ($action === 'delete_inventory_item') {
    $id  = trim($_POST['id'] ?? '');
    $inv = read_json('inventory.json');
    $new = [];
    foreach ($inv as $r) { if ($r['id'] !== $id) $new[] = $r; }
    write_json('inventory.json', $new);
    dbDelete('inventory', $id);
    audit('INVENTORY_DELETE', "Sters produs $id");
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// DATABASE
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_db_stats') {
    $pdo = getAdminDB();
    if (!$pdo) json_error('Conexiune DB esuata');

    $tables = ['jurnal','harvest','expenses','inventory','tasks',
               'alerte_rezolvate','queen_history','hive_metadata',
               'hive_history','hive_current','users','manual_hives','markers'];
    $stats = [];
    foreach ($tables as $t) {
        try {
            $n = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            $stats[$t] = intval($n);
        } catch (PDOException $e) {
            $stats[$t] = null;
        }
    }

    $size = 0;
    try {
        $size = $pdo->query("SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) AS mb
            FROM information_schema.tables WHERE table_schema='" . DB_NAME . "'")->fetchColumn();
    } catch (PDOException $e) {}

    json_ok(['tables' => $stats, 'size_mb' => $size]);
}

if ($action === 'query_table') {
    $pdo = getAdminDB();
    if (!$pdo) json_error('DB indisponibil');

    $table  = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table'] ?? '');
    $limit  = min(500, intval($_GET['limit']  ?? 100));
    $offset = max(0,   intval($_GET['offset'] ?? 0));
    $search = trim($_GET['search'] ?? '');

    $allowed = ['jurnal','harvest','expenses','inventory','tasks','alerte_rezolvate',
                'queen_history','hive_metadata','hive_history','hive_current',
                'users','manual_hives','markers','alerte_log'];
    if (!in_array($table, $allowed)) json_error('Tabel nepermis');

    try {
        $where  = '';
        $params = [];
        if ($search) {
            $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll();
            $textCols = [];
            foreach ($cols as $c) {
                if (strpos($c['Type'], 'char') !== false || strpos($c['Type'], 'text') !== false) {
                    $textCols[] = $c;
                }
            }
            if ($textCols) {
                $conds = [];
                foreach (array_slice($textCols, 0, 4) as $c) {
                    $conds[] = "`{$c['Field']}` LIKE :search";
                }
                $where = 'WHERE (' . implode(' OR ', $conds) . ')';
                $params[':search'] = '%' . $search . '%';
            }
        }
        $stmtCnt = $pdo->prepare("SELECT COUNT(*) FROM `$table` $where");
        $stmtCnt->execute($params);
        $totalRows = $stmtCnt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM `$table` $where ORDER BY 1 DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $cols = $rows ? array_keys($rows[0]) : [];

        json_ok(['rows' => $rows, 'columns' => $cols, 'total' => intval($totalRows)]);
    } catch (PDOException $e) {
        json_error('Query error: ' . $e->getMessage());
    }
}

if ($action === 'run_sql') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita rol superadmin', 403);
    $pdo = getAdminDB();
    if (!$pdo) json_error('DB indisponibil');

    $sql = trim($_POST['sql'] ?? '');
    if (!$sql) json_error('SQL gol');

    $sqlUpper = strtoupper($sql);
    $blocked  = ['DROP TABLE','DROP DATABASE','TRUNCATE','ALTER TABLE','GRANT','REVOKE','FLUSH'];
    foreach ($blocked as $b) {
        if (strpos($sqlUpper, $b) !== false) json_error("Operatia $b este blocata din motive de siguranta");
    }

    try {
        audit('SQL_QUERY', substr($sql, 0, 200));
        $isSelect = (strpos($sqlUpper, 'SELECT') === 0 || strpos($sqlUpper, 'SHOW') === 0 || strpos($sqlUpper, 'DESCRIBE') === 0);
        if ($isSelect) {
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll();
            $cols = $rows ? array_keys($rows[0]) : [];
            json_ok(['rows' => $rows, 'columns' => $cols, 'count' => count($rows)]);
        } else {
            $affected = $pdo->exec($sql);
            json_ok(['affected' => $affected]);
        }
    } catch (PDOException $e) {
        json_error('SQL Error: ' . $e->getMessage());
    }
}

if ($action === 'export_table_csv') {
    $table   = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table'] ?? '');
    $allowed = ['jurnal','harvest','expenses','inventory','tasks'];
    if (!in_array($table, $allowed)) json_error('Export nepermis');
    $pdo = getAdminDB();
    if (!$pdo) json_error('DB indisponibil');
    try {
        $rows = $pdo->query("SELECT * FROM `$table` ORDER BY 1 DESC")->fetchAll();
    } catch (PDOException $e) {
        json_error('Export error: ' . $e->getMessage());
    }
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename={$table}_" . date('Ymd') . ".csv");
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    if ($rows) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// AUDIT LOG
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_audit_log') {
    $log   = read_json_local('audit_log.json');
    $page  = max(1, intval($_GET['page']  ?? 1));
    $limit = intval($_GET['limit']        ?? 50);
    $act   = strtoupper(trim($_GET['action_filter'] ?? ''));

    if ($act) {
        $filtered = [];
        foreach ($log as $l) { if (strpos($l['action'] ?? '', $act) !== false) $filtered[] = $l; }
        $log = $filtered;
    }

    $total = count($log);
    $paged = array_slice($log, ($page-1)*$limit, $limit);
    json_ok(['items' => $paged, 'total' => $total, 'pages' => (int)ceil($total/$limit)]);
}

if ($action === 'clear_audit_log') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita rol superadmin', 403);
    write_json_local('audit_log.json', []);
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// ADMIN USERS
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_admin_users') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita rol superadmin', 403);
    $admins = load_admins();
    $result = [];
    foreach ($admins as $u => $a) {
        $result[] = [
            'username' => $u,
            'role'     => $a['role']    ?? 'admin',
            'email'    => $a['email']   ?? '',
            'name'     => $a['name']    ?? $u,
            'created'  => $a['created'] ?? '',
        ];
    }
    json_ok($result);
}

if ($action === 'create_admin_user') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita rol superadmin', 403);
    $uname = trim($_POST['username'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = in_array($_POST['role'] ?? '', ['superadmin','admin']) ? $_POST['role'] : 'admin';
    if (!$uname || strlen($pass) < 6) json_error('Date invalide');
    $admins = load_admins();
    if (isset($admins[$uname])) json_error('Admin exista deja');
    $admins[$uname] = [
        'password' => password_hash($pass, PASSWORD_BCRYPT),
        'role'     => $role,
        'email'    => trim($_POST['email'] ?? ''),
        'name'     => trim($_POST['name']  ?? $uname),
        'created'  => date('Y-m-d H:i:s'),
    ];
    file_put_contents(ADMINS_FILE, json_encode($admins, JSON_PRETTY_PRINT));
    audit('ADMIN_CREATE', "Creat admin: $uname ($role)");
    json_ok();
}

if ($action === 'delete_admin_user') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita rol superadmin', 403);
    $uname = trim($_POST['username'] ?? '');
    if ($uname === admin_current_user()) json_error('Nu te poti sterge pe tine insuti');
    $admins = load_admins();
    unset($admins[$uname]);
    file_put_contents(ADMINS_FILE, json_encode($admins, JSON_PRETTY_PRINT));
    audit('ADMIN_DELETE', "Sters admin: $uname");
    json_ok();
}

if ($action === 'change_own_password') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    if (strlen($new) < 6) json_error('Parola noua min 6 caractere');
    $admins = load_admins();
    $me = admin_current_user();
    if (!password_verify($old, $admins[$me]['password'] ?? '')) json_error('Parola veche incorecta');
    $admins[$me]['password'] = password_hash($new, PASSWORD_BCRYPT);
    file_put_contents(ADMINS_FILE, json_encode($admins, JSON_PRETTY_PRINT));
    audit('AUTH', 'Schimbata parola proprie');
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// HISTORY
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_hive_history') {
    $chipID = preg_replace('/[^0-9]/', '', $_GET['chipID'] ?? '');
    $days   = min(90, max(1, intval($_GET['days'] ?? 7)));
    if (!$chipID) json_error('chipID lipsa');
    $cutoff = time() - ($days * 86400);

    $pdo = getAdminDB();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT ts, weight, temperature, battery FROM hive_history WHERE chip_id = ? AND ts >= ? ORDER BY ts ASC");
            $stmt->execute([$chipID, $cutoff]);
            json_ok($stmt->fetchAll());
        } catch (PDOException $e) { /* fallback */ }
    }

    $file = APP_ROOT . '/history/' . $chipID . '.json';
    if (!file_exists($file)) json_ok([]);
    $data = json_decode(file_get_contents($file), true) ?: [];
    $filtered = [];
    foreach ($data as $r) { if (($r['ts'] ?? 0) >= $cutoff) $filtered[] = $r; }
    json_ok($filtered);
}

// ═══════════════════════════════════════════════════════════════
// LOGOUT
// ═══════════════════════════════════════════════════════════════
if ($action === 'logout') {
    admin_logout();
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// QUEEN HISTORY — CRUD complet
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_queens') {
    $pdo    = getAdminDB();
    $chipID = trim($_GET['chipID'] ?? '');
    $page   = max(1, intval($_GET['page'] ?? 1));
    $limit  = intval($_GET['limit'] ?? 50);
    $offset = ($page - 1) * $limit;
    if ($pdo) {
        try {
            $where  = $chipID ? "WHERE q.chip_id = :cid" : "";
            $params = $chipID ? [':cid' => $chipID] : [];
            $stmtC  = $pdo->prepare("SELECT COUNT(*) FROM mp_queen_history q $where");
            $stmtC->execute($params);
            $total  = (int)$stmtC->fetchColumn();
            $stmt   = $pdo->prepare("SELECT q.*, m.nickname FROM mp_queen_history q LEFT JOIN mp_metadata m ON q.chip_id = m.chip_id $where ORDER BY q.date DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            json_ok(['items' => $stmt->fetchAll(), 'total' => $total, 'pages' => (int)ceil($total/$limit)]);
        } catch (PDOException $e) { json_error('DB: '.$e->getMessage()); }
    }
    json_ok(['items' => read_json('queen_history.json'), 'total' => 0, 'pages' => 1]);
}

if ($action === 'save_queen') {
    $id     = trim($_POST['id'] ?? '') ?: uniqid();
    $chipID = trim($_POST['chipID'] ?? '');
    if (!$chipID) json_error('chipID lipsa');
    $row = [
        'id'      => $id,
        'chip_id' => $chipID,
        'event'   => trim($_POST['event'] ?? 'Inregistrare'),
        'breed'   => trim($_POST['breed'] ?? ''),
        'year'    => trim($_POST['year']  ?? ''),
        'notes'   => trim($_POST['notes'] ?? ''),
        'date'    => trim($_POST['date']  ?? date('d.m.Y H:i')),
        'user'    => admin_current_user(),
    ];
    $pdo = getAdminDB();
    if ($pdo) {
        try {
            $cols  = array_keys($row);
            $cList = implode(',', array_map(function($c){ return "`$c`"; }, $cols));
            $pList = implode(',', array_map(function($c){ return ":$c"; }, $cols));
            $uList = implode(',', array_map(function($c){ return "`$c`=VALUES(`$c`)"; }, $cols));
            $pdo->prepare("INSERT INTO mp_queen_history ($cList) VALUES ($pList) ON DUPLICATE KEY UPDATE $uList")->execute($row);
        } catch (PDOException $e) {}
    }
    $q = read_json('queen_history.json');
    $found = false;
    foreach ($q as &$item) {
        if ($item['id'] === $id) {
            $item = ['id'=>$id,'chipID'=>$chipID,'event'=>$row['event'],'breed'=>$row['breed'],'year'=>$row['year'],'notes'=>$row['notes'],'date'=>$row['date'],'user'=>$row['user']];
            $found = true; break;
        }
    }
    unset($item);
    if (!$found) array_unshift($q, ['id'=>$id,'chipID'=>$chipID,'event'=>$row['event'],'breed'=>$row['breed'],'year'=>$row['year'],'notes'=>$row['notes'],'date'=>$row['date'],'user'=>$row['user']]);
    write_json('queen_history.json', $q);
    audit('QUEEN_SAVE', "Eveniment matca: $chipID / {$row['event']}");
    json_ok($row);
}

if ($action === 'delete_queen') {
    $id  = trim($_POST['id'] ?? '');
    $pdo = getAdminDB();
    if ($pdo) { try { $pdo->prepare("DELETE FROM mp_queen_history WHERE id=?")->execute([$id]); } catch(PDOException $e){} }
    $q = read_json('queen_history.json');
    write_json('queen_history.json', array_values(array_filter($q, function($x) use ($id){ return ($x['id']??'') !== $id; })));
    audit('QUEEN_DELETE', "Sters eveniment matca $id");
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// ALERTE — istoric complet + delete
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_alerts_full') {
    $pdo    = getAdminDB();
    $page   = max(1, intval($_GET['page']  ?? 1));
    $limit  = intval($_GET['limit']        ?? 50);
    $offset = ($page - 1) * $limit;
    $stup   = trim($_GET['stup'] ?? '');
    if ($pdo) {
        try {
            $where  = $stup ? "WHERE stup = :stup" : "";
            $params = $stup ? [':stup' => $stup]   : [];
            $stmtC  = $pdo->prepare("SELECT COUNT(*) FROM mp_alerte $where");
            $stmtC->execute($params);
            $total  = (int)$stmtC->fetchColumn();
            $stmt   = $pdo->prepare("SELECT * FROM mp_alerte $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            json_ok(['items' => $stmt->fetchAll(), 'total' => $total, 'pages' => (int)ceil($total/$limit)]);
        } catch (PDOException $e) { json_error('DB: '.$e->getMessage()); }
    }
    json_ok(['items' => [], 'total' => 0, 'pages' => 1]);
}

if ($action === 'delete_alert') {
    $id  = trim($_POST['alert_id'] ?? '');
    $pdo = getAdminDB();
    if ($pdo) { try { $pdo->prepare("DELETE FROM mp_alerte WHERE alert_id=?")->execute([$id]); } catch(PDOException $e){ json_error('DB: '.$e->getMessage()); } }
    $a = read_json('alerte_rezolvate.json');
    write_json('alerte_rezolvate.json', array_values(array_filter($a, function($x) use ($id){ return ($x['alert_id']??'') !== $id; })));
    audit('ALERT_DELETE', "Sters alerta $id");
    json_ok();
}

if ($action === 'delete_all_alerts') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita superadmin', 403);
    $pdo = getAdminDB();
    if ($pdo) { try { $pdo->exec("DELETE FROM mp_alerte"); } catch(PDOException $e){ json_error('DB: '.$e->getMessage()); } }
    write_json('alerte_rezolvate.json', []);
    audit('ALERT_DELETE_ALL', 'Sterse toate alertele');
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// SYSTEM HEALTH
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_system_health') {
    $pdo = getAdminDB();
    $now = time();

    $staleHives = [];
    if ($pdo) {
        try {
            $cutoff = $now - 86400;
            $rows = $pdo->query("SELECT r.chip_id, m.nickname, MAX(r.ts) as last_ts FROM mp_hive_readings r LEFT JOIN mp_metadata m ON r.chip_id=m.chip_id GROUP BY r.chip_id, m.nickname HAVING MAX(r.ts) < $cutoff ORDER BY last_ts ASC")->fetchAll();
            foreach ($rows as $r) {
                $staleHives[] = ['chip_id'=>$r['chip_id'],'nickname'=>$r['nickname']??'Stup '.$r['chip_id'],'last_ts'=>(int)$r['last_ts'],'hours_ago'=>$r['last_ts']?round(($now-$r['last_ts'])/3600,1):null];
            }
        } catch (PDOException $e) {}
    }

    $lowBattery = [];
    if ($pdo) {
        try {
            $rows = $pdo->query("SELECT r.chip_id, m.nickname, r.battery, r.ts FROM mp_hive_readings r INNER JOIN (SELECT chip_id, MAX(ts) as max_ts FROM mp_hive_readings GROUP BY chip_id) l ON r.chip_id=l.chip_id AND r.ts=l.max_ts LEFT JOIN mp_metadata m ON r.chip_id=m.chip_id WHERE r.battery>0 AND r.battery<3.4 ORDER BY r.battery ASC")->fetchAll();
            foreach ($rows as $r) { $lowBattery[] = ['chip_id'=>$r['chip_id'],'nickname'=>$r['nickname']??'Stup '.$r['chip_id'],'battery'=>round(floatval($r['battery']),3),'ts'=>(int)$r['ts']]; }
        } catch (PDOException $e) {}
    }

    $tableStats = [];
    if ($pdo) {
        foreach (['mp_hive_readings','mp_jurnal','mp_tasks','mp_harvest','mp_expenses','mp_alerte'] as $t) {
            try {
                $cnt  = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
                $size = $pdo->query("SELECT ROUND((data_length+index_length)/1024/1024,3) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='$t'")->fetchColumn();
                $tableStats[$t] = ['rows'=>$cnt,'mb'=>floatval($size??0)];
            } catch (PDOException $e) { $tableStats[$t] = ['rows'=>0,'mb'=>0]; }
        }
    }

    $expiredTasks = 0;
    if ($pdo) {
        try {
            $allTasks = $pdo->query("SELECT date FROM mp_tasks WHERE done=0")->fetchAll();
            foreach ($allTasks as $t) {
                $parts = explode('.', substr($t['date']??'',0,10));
                if (count($parts)===3) { $ts = mktime(0,0,0,(int)$parts[1],(int)$parts[0],(int)$parts[2]); if ($ts < strtotime('today')) $expiredTasks++; }
            }
        } catch (PDOException $e) {}
    }

    $readingsLast24h = 0;
    if ($pdo) { try { $readingsLast24h = (int)$pdo->query("SELECT COUNT(*) FROM mp_hive_readings WHERE ts >= ".($now-86400))->fetchColumn(); } catch (PDOException $e) {} }

    $inactiveUsers = [];
    if ($pdo) {
        try {
            $cutoff30 = date('d.m.Y', strtotime('-30 days'));
            $users = $pdo->query("SELECT username FROM mp_users")->fetchAll();
            foreach ($users as $u) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM mp_jurnal WHERE `user`=? AND date >= ?");
                $stmt->execute([$u['username'], $cutoff30]);
                if ((int)$stmt->fetchColumn() === 0) $inactiveUsers[] = $u['username'];
            }
        } catch (PDOException $e) {}
    }

    $serverInfo = [
        'php_version'   => PHP_VERSION,
        'php_sapi'      => PHP_SAPI,
        'memory_limit'  => ini_get('memory_limit'),
        'max_exec_time' => ini_get('max_execution_time'),
        'upload_max'    => ini_get('upload_max_filesize'),
        'disk_free_gb'  => round(disk_free_space('/')/1073741824,2),
        'disk_total_gb' => round(disk_total_space('/')/1073741824,2),
        'server_time'   => date('d.m.Y H:i:s'),
        'app_root_size' => null,
    ];
    try {
        $size = 0;
        $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ROOT, FilesystemIterator::SKIP_DOTS));
        foreach ($iter as $f) { if ($f->isFile()) $size += $f->getSize(); }
        $serverInfo['app_root_size'] = round($size/1048576,2);
    } catch (Exception $e) {}

    $jsonFiles = [];
    foreach (['data.json','metadata.json','user.json','jurnal.json','tasks.json','harvest.json','expenses.json','inventory.json','queen_history.json','map_markers.json','alerte_rezolvate.json'] as $f) {
        $path = APP_ROOT.'/'.$f;
        if (!file_exists($path)) $path = ADMIN_CONSOLE_ROOT.'/'.$f;
        $jsonFiles[$f] = ['exists'=>file_exists($path),'size_kb'=>file_exists($path)?round(filesize($path)/1024,1):0,'modified'=>file_exists($path)?date('d.m.Y H:i',filemtime($path)):null,'valid'=>file_exists($path)?(json_decode(file_get_contents($path))!==null):false];
    }

    json_ok(['stale_hives'=>$staleHives,'low_battery'=>$lowBattery,'inactive_users'=>$inactiveUsers,'table_stats'=>$tableStats,'server_info'=>$serverInfo,'json_files'=>$jsonFiles,'expired_tasks'=>$expiredTasks,'readings_last_24h'=>$readingsLast24h,'generated_at'=>date('d.m.Y H:i:s')]);
}

// ═══════════════════════════════════════════════════════════════
// RAPOARTE ADMIN
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_admin_report') {
    $pdo  = getAdminDB();
    $type = trim($_GET['type'] ?? 'overview');

    if ($type === 'activity') {
        $result = [];
        if ($pdo) {
            try {
                $users = $pdo->query("SELECT username FROM mp_users ORDER BY username")->fetchAll();
                foreach ($users as $u) {
                    $un   = $u['username'];
                    $jCnt = (int)$pdo->query("SELECT COUNT(*) FROM mp_jurnal WHERE `user`='".addslashes($un)."'")->fetchColumn();
                    $tDone= (int)$pdo->query("SELECT COUNT(*) FROM mp_tasks WHERE `user`='".addslashes($un)."' AND done=1")->fetchColumn();
                    $tPend= (int)$pdo->query("SELECT COUNT(*) FROM mp_tasks WHERE `user`='".addslashes($un)."' AND done=0")->fetchColumn();
                    $last = $pdo->query("SELECT MAX(date) FROM mp_jurnal WHERE `user`='".addslashes($un)."'")->fetchColumn();
                    $result[] = ['user'=>$un,'jurnal'=>$jCnt,'tasks_done'=>$tDone,'tasks_pending'=>$tPend,'last_activity'=>$last];
                }
            } catch (PDOException $e) {}
        }
        json_ok($result);
    }

    if ($type === 'hive_performance') {
        $result = [];
        if ($pdo) {
            try {
                $meta = $pdo->query("SELECT chip_id, nickname FROM mp_metadata")->fetchAll();
                foreach ($meta as $m) {
                    $cid  = $m['chip_id'];
                    $nick = $m['nickname'] ?: 'Stup '.$cid;
                    $kg   = $pdo->query("SELECT COALESCE(SUM(kg),0) FROM mp_harvest WHERE stup='".addslashes($nick)."'")->fetchColumn();
                    $ron  = $pdo->query("SELECT COALESCE(SUM(kg*pret),0) FROM mp_harvest WHERE stup='".addslashes($nick)."'")->fetchColumn();
                    $exp  = $pdo->query("SELECT COALESCE(SUM(suma),0) FROM mp_expenses WHERE stup='".addslashes($nick)."'")->fetchColumn();
                    $readings = (int)$pdo->query("SELECT COUNT(*) FROM mp_hive_readings WHERE chip_id='".addslashes($cid)."'")->fetchColumn();
                    $lastJ = $pdo->query("SELECT MAX(date) FROM mp_jurnal WHERE stup='".addslashes($nick)."'")->fetchColumn();
                    $result[] = ['chip_id'=>$cid,'nickname'=>$nick,'kg_total'=>round(floatval($kg),2),'ron_total'=>round(floatval($ron),2),'expenses'=>round(floatval($exp),2),'profit'=>round(floatval($ron)-floatval($exp),2),'readings'=>$readings,'last_jurnal'=>$lastJ];
                }
            } catch (PDOException $e) {}
        }
        json_ok($result);
    }

    if ($type === 'data_growth') {
        $result = [];
        if ($pdo) {
            try {
                for ($i = 29; $i >= 0; $i--) {
                    $dayStart = strtotime("midnight -$i days");
                    $dayEnd   = $dayStart + 86399;
                    $cnt      = (int)$pdo->query("SELECT COUNT(*) FROM mp_hive_readings WHERE ts BETWEEN $dayStart AND $dayEnd")->fetchColumn();
                    $result[] = ['date'=>date('d.m',$dayStart),'readings'=>$cnt];
                }
            } catch (PDOException $e) {}
        }
        json_ok($result);
    }
    json_error('Tip raport necunoscut');
}

// ═══════════════════════════════════════════════════════════════
// BACKUP
// ═══════════════════════════════════════════════════════════════
if ($action === 'backup_json') {
    if (!class_exists('ZipArchive')) json_error('ZipArchive nu este disponibil');
    $zipName = 'matca_backup_'.date('Ymd_His').'.zip';
    $zipPath = sys_get_temp_dir().'/'.$zipName;
    $zip     = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) json_error('Nu am putut crea arhiva ZIP');
    $files = ['data.json','metadata.json','controllers.json','user.json','jurnal.json','tasks.json','harvest.json','expenses.json','inventory.json','queen_history.json','map_markers.json','alerte_rezolvate.json','manual_hives.json','admins.json'];
    $added = 0;
    foreach ($files as $f) {
        $path = file_exists(APP_ROOT.'/'.$f) ? APP_ROOT.'/'.$f : ADMIN_CONSOLE_ROOT.'/'.$f;
        if (file_exists($path)) { $zip->addFile($path, $f); $added++; }
    }
    $histDir = APP_ROOT.'/history';
    if (is_dir($histDir)) { foreach (glob($histDir.'/*.json') ?: [] as $hf) { $zip->addFile($hf,'history/'.basename($hf)); $added++; } }
    $zip->addFromString('README.txt', "Backup MatcaDB
Generat: ".date('d.m.Y H:i:s')."
Admin: ".admin_current_user()."
Fisiere: $added
");
    $zip->close();
    audit('BACKUP', "JSON backup: $added fisiere");
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$zipName.'"');
    header('Content-Length: '.filesize($zipPath));
    header('Cache-Control: no-cache');
    readfile($zipPath);
    unlink($zipPath);
    exit;
}

if ($action === 'backup_sql') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita superadmin', 403);
    $pdo = getAdminDB();
    if (!$pdo) json_error('DB indisponibil');
    $tables = ['mp_hive_readings','mp_telemetry_history','mp_metadata','mp_manual_hives','mp_controllers','mp_users','mp_jurnal','mp_tasks','mp_harvest','mp_expenses','mp_inventory','mp_queen_history','mp_markers','mp_alerte','mp_apiary_location'];
    $filename = 'matca_db_backup_'.date('Ymd_His').'.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: no-cache');
    echo "-- MatcaDB SQL Backup
-- Generat: ".date('d.m.Y H:i:s')."
-- Admin: ".admin_current_user()."

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

";
    foreach ($tables as $table) {
        try {
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            echo "-- Table: $table
DROP TABLE IF EXISTS `$table`;
".$create['Create Table'].";

";
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
            if ($rows) {
                $cols = '`'.implode('`,`', array_keys($rows[0])).'`';
                echo "INSERT INTO `$table` ($cols) VALUES
";
                $lines = [];
                foreach ($rows as $row) {
                    $vals = array_map(function($v){ return $v===null?'NULL':"'".addslashes($v)."'"; }, array_values($row));
                    $lines[] = '('.implode(',',$vals).')';
                }
                echo implode(",
",$lines).";

";
            }
        } catch (PDOException $e) { echo "-- EROARE la $table: ".$e->getMessage()."

"; }
    }
    echo "SET FOREIGN_KEY_CHECKS=1;
-- END BACKUP
";
    audit('BACKUP', 'SQL dump generat');
    exit;
}

if ($action === 'get_backup_info') {
    $pdo = getAdminDB();
    $info = ['json_files'=>[],'db_tables'=>[],'total_json_kb'=>0,'total_db_rows'=>0,'history_files'=>0];
    foreach (['data.json','metadata.json','user.json','jurnal.json','tasks.json','harvest.json','expenses.json','inventory.json','queen_history.json','map_markers.json','alerte_rezolvate.json'] as $f) {
        $path = file_exists(APP_ROOT.'/'.$f) ? APP_ROOT.'/'.$f : ADMIN_CONSOLE_ROOT.'/'.$f;
        if (file_exists($path)) { $kb=round(filesize($path)/1024,1); $info['json_files'][]=['name'=>$f,'kb'=>$kb,'modified'=>date('d.m.Y H:i',filemtime($path))]; $info['total_json_kb']+=$kb; }
    }
    $info['history_files'] = count(glob(APP_ROOT.'/history/*.json') ?: []);
    if ($pdo) {
        foreach (['mp_hive_readings','mp_telemetry_history','mp_metadata','mp_manual_hives','mp_controllers','mp_users','mp_jurnal','mp_tasks','mp_harvest','mp_expenses','mp_inventory','mp_queen_history','mp_markers','mp_alerte','mp_apiary_location'] as $t) {
            try { $cnt=(int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); $info['db_tables'][]=['name'=>$t,'rows'=>$cnt]; $info['total_db_rows']+=$cnt; }
            catch (PDOException $e) { $info['db_tables'][]=['name'=>$t,'rows'=>null]; }
        }
    }
    json_ok($info);
}

// ═══════════════════════════════════════════════════════════════
// GRAFIC GREUTATE STUPI
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_weight_chart') {
    $days   = min(90, max(1, intval($_GET['days'] ?? 30)));
    $cutoff = time() - ($days * 86400);
    $pdo    = getAdminDB();
    if (!$pdo) json_error('DB indisponibil');
    try {
        $chips = $pdo->query("SELECT DISTINCT r.chip_id, COALESCE(m.nickname, CONCAT('Stup ',r.chip_id)) as label FROM mp_hive_readings r LEFT JOIN mp_metadata m ON r.chip_id=m.chip_id WHERE r.ts >= $cutoff AND r.weight IS NOT NULL AND r.weight > 0 ORDER BY label")->fetchAll();
        $datasets = [];
        $colors   = ['#f4a820','#10ac84','#ee5253','#3498db','#9b59b6','#e67e22','#1abc9c','#e74c3c','#f39c12','#2ecc71'];
        foreach ($chips as $i => $chip) {
            $rows = $pdo->prepare("SELECT FROM_UNIXTIME(ts,'%Y-%m-%d') as day, ROUND(AVG(weight),3) as avg_w FROM mp_hive_readings WHERE chip_id=? AND ts>=? AND weight IS NOT NULL AND weight>0 GROUP BY day ORDER BY day ASC");
            $rows->execute([$chip['chip_id'], $cutoff]);
            $pts = $rows->fetchAll();
            $datasets[] = ['chip_id'=>$chip['chip_id'],'label'=>$chip['label'],'color'=>$colors[$i%count($colors)],'data'=>array_map(function($p){ return ['x'=>$p['day'],'y'=>floatval($p['avg_w'])]; }, $pts)];
        }
        json_ok(['datasets'=>$datasets,'days'=>$days]);
    } catch (PDOException $e) { json_error('DB: '.$e->getMessage()); }
}

json_error('Actiune necunoscuta: ' . $action, 404);