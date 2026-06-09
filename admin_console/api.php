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

    if ($usersTotal === 0) { $users = read_json('user.json'); $usersTotal = count($users); }
    if ($tasksPending === 0) { $tasks = read_json('tasks.json'); foreach ($tasks as $t) { if (empty($t['done'])) $tasksPending++; } }
    if ($totalKg === 0) {
        $harvest = read_json('harvest.json');
        foreach ($harvest as $h) { $totalKg += floatval($h['kg'] ?? 0); $totalRon += floatval($h['kg'] ?? 0) * floatval($h['pret'] ?? 0); }
        $totalKg = round($totalKg, 2); $totalRon = round($totalRon, 2);
    }
    if ($totalReadings === 0) {
        $histFiles = glob(APP_ROOT . '/history/*.json') ?: [];
        foreach ($histFiles as $f) { $d = json_decode(file_get_contents($f), true); if (is_array($d)) $totalReadings += count($d); }
    }

    $activity = [];
    for ($i = 6; $i >= 0; $i--) { $activity[date('d.m', strtotime("-$i days"))] = 0; }
    if ($pdo) {
        try {
            $cutoff7 = strtotime('-7 days');
            $rows = $pdo->query("SELECT date FROM mp_jurnal WHERE ts >= $cutoff7")->fetchAll();
            foreach ($rows as $j) { $d = substr($j['date'] ?? '', 0, 5); if (isset($activity[$d])) $activity[$d]++; }
        } catch (PDOException $e) {
            $jurnal = read_json('jurnal.json');
            foreach ($jurnal as $j) { $d = substr($j['date'] ?? '', 0, 5); if (isset($activity[$d])) $activity[$d]++; }
        }
    } else {
        $jurnal = read_json('jurnal.json');
        foreach ($jurnal as $j) { $d = substr($j['date'] ?? '', 0, 5); if (isset($activity[$d])) $activity[$d]++; }
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
        'activity'       => $activity,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// STUPI
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_hives') {
    $pdo  = getAdminDB();
    $now  = time();
    $result = [];

    $dataJson   = read_json('data.json');
    $metaJson   = read_json('metadata.json');
    $manualJson = read_json('manual_hives.json');
    $ctrlJson   = read_json('controllers.json');

    $dataByChip = [];
    foreach ($dataJson as $h) { $dataByChip[(string)$h['chipID']] = $h; }

    $chipToCtrl = [];
    foreach ($ctrlJson as $cKey => $c) {
        foreach ($c['chipIDs'] ?? [] as $cid) {
            $chipToCtrl[(string)$cid] = ['id'=>(string)$cKey,'name'=>$c['name']??$cKey];
        }
    }

    if ($pdo) {
        try {
            $rows = $pdo->query("
                SELECT r.*, m.nickname, m.q_color, m.q_year, m.q_breed, m.q_score,
                       m.maintenance, m.supers, m.lat, m.lng, m.weight_ref
                FROM mp_hive_readings r
                INNER JOIN (SELECT chip_id, MAX(ts) as max_ts FROM mp_hive_readings GROUP BY chip_id) l
                  ON r.chip_id = l.chip_id AND r.ts = l.max_ts
                LEFT JOIN mp_metadata m ON r.chip_id = m.chip_id
                ORDER BY r.chip_id
            ")->fetchAll();

            $ctrls = [];
            try {
                $ctrlRows = $pdo->query("SELECT ctrl_id, name, chip_ids FROM mp_controllers")->fetchAll();
                foreach ($ctrlRows as $c) {
                    $chips = json_decode($c['chip_ids'] ?? '[]', true) ?: [];
                    foreach ($chips as $cid) { $ctrls[(string)$cid] = ['id' => $c['ctrl_id'], 'name' => $c['name'] ?? $c['ctrl_id']]; }
                }
            } catch (PDOException $e) {}

            foreach ($rows as $h) {
                $cid = (string)$h['chip_id'];
                $diff = $now - (int)($h['ts'] ?? 0);
                $status = $diff < 7200 ? 'online' : ($diff < 86400 ? 'warning' : 'offline');
                $ctrlInfo = $ctrls[$cid] ?? $chipToCtrl[$cid] ?? null;
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

            $manualOnly = $pdo->query("SELECT h.chip_id, h.weight, h.temperature, h.battery, h.delta24, h.ts, h.creator, m.nickname, m.q_color, m.q_year, m.q_breed, m.q_score FROM mp_manual_hives h LEFT JOIN mp_metadata m ON h.chip_id = m.chip_id")->fetchAll();
            $withReadings = array_column($result, 'chipID');
            foreach ($manualOnly as $m) {
                if (!in_array($m['chip_id'], $withReadings)) {
                    $result[] = ['chipID'=>$m['chip_id'],'nickname'=>$m['nickname']??$m['chip_id'],'weight'=>0,'temperature'=>0,'battery'=>4.2,'delta24'=>0,'delta'=>0,'status'=>'manual','last_updated'=>0,'firmware'=>'manual','wifi'=>0,'controller'=>'','controllerID'=>'','qColor'=>$m['q_color']??'transparent','qYear'=>$m['q_year']??'','qBreed'=>$m['q_breed']??'','qScore'=>(int)($m['q_score']??5),'supers'=>0,'maintenance'=>false,'lat'=>null,'lng'=>null,'isManual'=>true,'creator'=>$m['creator']??''];
                }
            }

            $chipsInDB = array_column($result, 'chipID');
            foreach ($dataByChip as $cid => $h) {
                if (in_array($cid, $chipsInDB)) continue;
                $m = $metaJson[$cid] ?? [];
                $diff = $now - ($h['lastUpdated'] ?? 0);
                $status = $diff < 7200 ? 'online' : ($diff < 86400 ? 'warning' : 'offline');
                $ctrlInfo = $chipToCtrl[$cid] ?? null;
                $result[] = ['chipID'=>$cid,'nickname'=>$m['nickname']??'Stup '.$cid,'weight'=>round(floatval($h['weight']??0),3),'temperature'=>round(floatval($h['temperature']??0),2),'battery'=>round(floatval($h['battery']??0),3),'delta24'=>round(floatval($h['delta24']??0),3),'delta'=>round(floatval($h['delta']??0),3),'status'=>$status,'last_updated'=>(int)($h['lastUpdated']??0),'firmware'=>$h['firmwareVersion']??'','wifi'=>(int)($h['wifiSignal']??0),'controller'=>$ctrlInfo?$ctrlInfo['name']:(string)($h['controllerID']??''),'controllerID'=>$ctrlInfo?$ctrlInfo['id']:(string)($h['controllerID']??''),'qColor'=>$m['qColor']??'transparent','qYear'=>$m['qYear']??'','qBreed'=>$m['qBreed']??'','qScore'=>(int)($m['qScore']??5),'supers'=>(int)($m['supers']??0),'maintenance'=>(bool)($m['maintenance']??false),'lat'=>$m['lat']??null,'lng'=>$m['lng']??null,'isManual'=>false,'creator'=>''];
            }
            json_ok($result);
        } catch (PDOException $e) { error_log('[get_hives] DB error: ' . $e->getMessage()); }
    }

    foreach ($dataByChip as $cid => $h) {
        $m = $metaJson[$cid] ?? [];
        $diff = $now - ($h['lastUpdated'] ?? 0);
        $status = $diff < 7200 ? 'online' : ($diff < 86400 ? 'warning' : 'offline');
        $ctrlInfo = $chipToCtrl[$cid] ?? null;
        $result[] = ['chipID'=>$cid,'nickname'=>$m['nickname']??'Stup '.$cid,'weight'=>round(floatval($h['weight']??0),3),'temperature'=>round(floatval($h['temperature']??0),2),'battery'=>round(floatval($h['battery']??0),3),'delta24'=>round(floatval($h['delta24']??0),3),'delta'=>round(floatval($h['delta']??0),3),'status'=>$status,'last_updated'=>(int)($h['lastUpdated']??0),'firmware'=>$h['firmwareVersion']??'','wifi'=>(int)($h['wifiSignal']??0),'controller'=>$ctrlInfo?$ctrlInfo['name']:(string)($h['controllerID']??''),'controllerID'=>$ctrlInfo?$ctrlInfo['id']:(string)($h['controllerID']??''),'qColor'=>$m['qColor']??'transparent','qYear'=>$m['qYear']??'','qBreed'=>$m['qBreed']??'','qScore'=>(int)($m['qScore']??5),'supers'=>(int)($m['supers']??0),'maintenance'=>(bool)($m['maintenance']??false),'lat'=>$m['lat']??null,'lng'=>$m['lng']??null,'isManual'=>false,'creator'=>''];
    }
    foreach ($manualJson as $h) {
        $cid = (string)$h['chipID'];
        $m = $metaJson[$cid] ?? [];
        $result[] = ['chipID'=>$cid,'nickname'=>$m['nickname']??$cid,'weight'=>$h['weight']??0,'temperature'=>$h['temperature']??0,'battery'=>$h['battery']??4.2,'delta24'=>$h['delta24']??0,'delta'=>0,'status'=>'manual','last_updated'=>$h['ts']??0,'firmware'=>'manual','wifi'=>0,'controller'=>'','controllerID'=>'','qColor'=>$m['qColor']??'transparent','qYear'=>$m['qYear']??'','qBreed'=>$m['qBreed']??'','qScore'=>(int)($m['qScore']??5),'supers'=>(int)($m['supers']??0),'maintenance'=>(bool)($m['maintenance']??false),'lat'=>null,'lng'=>null,'isManual'=>true,'creator'=>$h['creator']??''];
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
    dbSync('mp_metadata', ['chip_id'=>$chipID,'nickname'=>$meta[$chipID]['nickname']??'','q_color'=>$meta[$chipID]['qColor']??'transparent','q_year'=>$meta[$chipID]['qYear']??'','supers'=>$meta[$chipID]['supers']??0,'maintenance'=>!empty($meta[$chipID]['maintenance'])?1:0], 'chip_id');
    audit('HIVE_META', "Actualizat metadata stup $chipID");
    json_ok($meta[$chipID]);
}

if ($action === 'delete_hive_manual') {
    $chipID = trim($_POST['chipID'] ?? '');
    if (!$chipID) json_error('chipID lipsa');
    if (strpos($chipID, 'M') !== 0) json_error('Doar stupii manuali pot fi stersi');
    $manual = read_json('manual_hives.json');
    $new = []; foreach ($manual as $h) { if ((string)$h['chipID'] !== $chipID) $new[] = $h; }
    write_json('manual_hives.json', $new);
    $meta = read_json('metadata.json'); unset($meta[$chipID]); write_json('metadata.json', $meta);
    dbDelete('mp_manual_hives', $chipID, 'chip_id');
    audit('HIVE_DELETE', "Sters stup manual $chipID");
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// RESETARE WEIGHTREF (recalibrare greutate)
// ═══════════════════════════════════════════════════════════════
if ($action === 'reset_weight_ref') {
    $chipID = trim($_POST['chipID'] ?? '');
    $ref    = floatval($_POST['ref'] ?? 0);
    if (!$chipID) json_error('chipID lipsa');
    $meta = read_json('metadata.json');
    if (!isset($meta[$chipID])) $meta[$chipID] = [];
    $meta[$chipID]['weightRef'] = $ref;
    write_json('metadata.json', $meta);
    dbSync('mp_metadata', ['chip_id'=>$chipID,'weight_ref'=>$ref], 'chip_id');
    audit('WEIGHT_REF', "Reset weightRef stup $chipID = $ref kg");
    json_ok(['chipID'=>$chipID,'weightRef'=>$ref]);
}

// ═══════════════════════════════════════════════════════════════
// TELEMETRIE LIVE
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_telemetry_live') {
    $pdo = getAdminDB();
    $now = time();
    $result = [];
    if ($pdo) {
        try {
            $rows = $pdo->query("
                SELECT r.chip_id, r.weight, r.temperature, r.battery, r.wifi_signal,
                       r.delta24, r.delta, r.ts, r.firmware_version,
                       COALESCE(m.nickname, CONCAT('Stup ',r.chip_id)) as nickname
                FROM mp_hive_readings r
                INNER JOIN (SELECT chip_id, MAX(ts) as max_ts FROM mp_hive_readings GROUP BY chip_id) l
                  ON r.chip_id = l.chip_id AND r.ts = l.max_ts
                LEFT JOIN mp_metadata m ON r.chip_id = m.chip_id
                ORDER BY r.ts DESC
            ")->fetchAll();
            foreach ($rows as $h) {
                $diff = $now - (int)$h['ts'];
                $result[] = [
                    'chipID'      => $h['chip_id'],
                    'nickname'    => $h['nickname'],
                    'weight'      => round(floatval($h['weight']),3),
                    'temperature' => round(floatval($h['temperature']),2),
                    'battery'     => round(floatval($h['battery']),3),
                    'wifi'        => (int)$h['wifi_signal'],
                    'delta24'     => round(floatval($h['delta24']),3),
                    'delta'       => round(floatval($h['delta']),3),
                    'ts'          => (int)$h['ts'],
                    'seconds_ago' => $diff,
                    'firmware'    => $h['firmware_version'] ?? '',
                    'status'      => $diff < 7200 ? 'online' : ($diff < 86400 ? 'warning' : 'offline'),
                ];
            }
        } catch (PDOException $e) {}
    }
    if (empty($result)) {
        $data = read_json('data.json');
        $meta = read_json('metadata.json');
        foreach ($data as $h) {
            $diff = $now - ($h['lastUpdated'] ?? 0);
            $cid  = (string)$h['chipID'];
            $result[] = ['chipID'=>$cid,'nickname'=>$meta[$cid]['nickname']??'Stup '.$cid,'weight'=>round(floatval($h['weight']??0),3),'temperature'=>round(floatval($h['temperature']??0),2),'battery'=>round(floatval($h['battery']??0),3),'wifi'=>(int)($h['wifiSignal']??0),'delta24'=>round(floatval($h['delta24']??0),3),'delta'=>round(floatval($h['delta']??0),3),'ts'=>(int)($h['lastUpdated']??0),'seconds_ago'=>$diff,'firmware'=>$h['firmwareVersion']??'','status'=>$diff<7200?'online':($diff<86400?'warning':'offline')];
        }
    }
    json_ok(['readings'=>$result,'generated_at'=>$now]);
}

// ═══════════════════════════════════════════════════════════════
// STERGERE SELECTIVA ISTORIC IoT
// ═══════════════════════════════════════════════════════════════
if ($action === 'delete_readings_range') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita superadmin', 403);
    $chipID   = trim($_POST['chipID']   ?? '');
    $dateFrom = trim($_POST['date_from'] ?? '');
    $dateTo   = trim($_POST['date_to']   ?? '');
    if (!$chipID || !$dateFrom || !$dateTo) json_error('Parametri lipsa');

    $tsFrom = strtotime($dateFrom . ' 00:00:00');
    $tsTo   = strtotime($dateTo   . ' 23:59:59');
    if (!$tsFrom || !$tsTo || $tsFrom > $tsTo) json_error('Interval invalid');

    $deleted = 0;
    $pdo = getAdminDB();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM mp_hive_readings WHERE chip_id=? AND ts BETWEEN ? AND ?");
            $stmt->execute([$chipID, $tsFrom, $tsTo]);
            $deleted = $stmt->rowCount();
        } catch (PDOException $e) { json_error('DB: '.$e->getMessage()); }
    }

    $file = APP_ROOT . '/history/' . preg_replace('/[^0-9a-zA-Z]/', '', $chipID) . '.json';
    $deletedJson = 0;
    if (file_exists($file)) {
        $hist = json_decode(file_get_contents($file), true) ?: [];
        $newHist = array_values(array_filter($hist, function($r) use ($tsFrom, $tsTo) {
            return !isset($r['ts']) || $r['ts'] < $tsFrom || $r['ts'] > $tsTo;
        }));
        $deletedJson = count($hist) - count($newHist);
        file_put_contents($file, json_encode($newHist), LOCK_EX);
    }

    audit('DELETE_READINGS', "Sters citiri stup $chipID intre $dateFrom - $dateTo: DB=$deleted, JSON=$deletedJson");
    json_ok(['deleted_db'=>$deleted,'deleted_json'=>$deletedJson,'chip_id'=>$chipID]);
}

// ═══════════════════════════════════════════════════════════════
// SINCRONIZARE JSON → DB
// ═══════════════════════════════════════════════════════════════
if ($action === 'sync_json_to_db') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita superadmin', 403);
    $pdo = getAdminDB();
    if (!$pdo) json_error('DB indisponibil');

    $stats = [];

    // Users
    $users = read_json('user.json');
    $cnt = 0;
    foreach ($users as $uname => $u) {
        dbSync('mp_users', ['username'=>$uname,'email'=>$u['email']??'','can_manage_manual'=>(int)(!empty($u['can_manage_manual'])),'is_admin'=>(int)(!empty($u['is_admin'])),'hives'=>implode(',',$u['hives']??[]),'controllers'=>implode(',',$u['controllers']??[]),'created_at'=>$u['created_at']??date('d.m.Y H:i')], 'username');
        $cnt++;
    }
    $stats['mp_users'] = $cnt;

    // Metadata
    $meta = read_json('metadata.json');
    $cnt = 0;
    foreach ($meta as $cid => $m) {
        dbSync('mp_metadata', ['chip_id'=>$cid,'nickname'=>$m['nickname']??'','q_color'=>$m['qColor']??'transparent','q_year'=>$m['qYear']??'','q_breed'=>$m['qBreed']??'','supers'=>(int)($m['supers']??0),'maintenance'=>(int)(!empty($m['maintenance'])),'lat'=>$m['lat']??0,'lng'=>$m['lng']??0,'x'=>$m['x']??50,'y'=>$m['y']??50,'weight_ref'=>$m['weightRef']??0], 'chip_id');
        $cnt++;
    }
    $stats['mp_metadata'] = $cnt;

    // Jurnal
    $jurnal = read_json('jurnal.json');
    $cnt = 0;
    foreach ($jurnal as $j) {
        dbSync('mp_jurnal', ['id'=>$j['id'],'user'=>$j['user']??'','stup'=>$j['stup']??'','text'=>$j['text']??'','image'=>$j['image']??'','date'=>$j['date']??'']);
        $cnt++;
    }
    $stats['mp_jurnal'] = $cnt;

    // Harvest
    $harvest = read_json('harvest.json');
    $cnt = 0;
    foreach ($harvest as $h) {
        dbSync('mp_harvest', ['id'=>$h['id'],'stup'=>$h['stup']??'','kg'=>floatval($h['kg']??0),'tip'=>$h['tip']??'','pret'=>floatval($h['pret']??0),'date'=>$h['date']??'']);
        $cnt++;
    }
    $stats['mp_harvest'] = $cnt;

    // Expenses
    $expenses = read_json('expenses.json');
    $cnt = 0;
    foreach ($expenses as $e) {
        dbSync('mp_expenses', ['id'=>$e['id'],'stup'=>$e['stup']??'','suma'=>floatval($e['suma']??0),'description'=>$e['desc']??$e['description']??'','date'=>$e['date']??'']);
        $cnt++;
    }
    $stats['mp_expenses'] = $cnt;

    // Tasks
    $tasks = read_json('tasks.json');
    $cnt = 0;
    foreach ($tasks as $t) {
        dbSync('mp_tasks', ['id'=>$t['id'],'user'=>$t['user']??'','stup'=>$t['stup']??'','type'=>$t['type']??'manual','text'=>$t['text']??'','date'=>$t['date']??'','done'=>(int)(!empty($t['done'])),'has_reminder'=>(int)(!empty($t['has_reminder']))]);
        $cnt++;
    }
    $stats['mp_tasks'] = $cnt;

    // Inventory
    $inv = read_json('inventory.json');
    $cnt = 0;
    foreach ($inv as $i) {
        dbSync('mp_inventory', ['id'=>$i['id'],'user'=>$i['user']??'','item'=>$i['item']??'','qty'=>floatval($i['qty']??0),'type'=>$i['type']??'','category'=>$i['category']??'']);
        $cnt++;
    }
    $stats['mp_inventory'] = $cnt;

    // Queen history
    $queens = read_json('queen_history.json');
    $cnt = 0;
    foreach ($queens as $q) {
        dbSync('mp_queen_history', ['id'=>$q['id'],'chip_id'=>$q['chipID']??'','event'=>$q['event']??'','breed'=>$q['breed']??'','year'=>$q['year']??'','notes'=>$q['notes']??'','date'=>$q['date']??'','user'=>$q['user']??'']);
        $cnt++;
    }
    $stats['mp_queen_history'] = $cnt;

    $total = array_sum($stats);
    audit('SYNC_JSON_DB', "Sincronizare manuala JSON→DB: $total inregistrari in ".count($stats)." tabele");
    json_ok(['stats'=>$stats,'total'=>$total]);
}

// ═══════════════════════════════════════════════════════════════
// GESTIONARE FISIERE HISTORY
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_history_files') {
    $histDir = APP_ROOT . '/history';
    $files = glob($histDir . '/*.json') ?: [];
    $meta  = read_json('metadata.json');
    $result = [];
    foreach ($files as $f) {
        $chipID = basename($f, '.json');
        $data   = json_decode(file_get_contents($f), true) ?: [];
        $first  = !empty($data) ? $data[0]['ts']  ?? 0 : 0;
        $last   = !empty($data) ? end($data)['ts'] ?? 0 : 0;
        $result[] = [
            'chipID'    => $chipID,
            'nickname'  => $meta[$chipID]['nickname'] ?? 'Stup '.$chipID,
            'count'     => count($data),
            'size_kb'   => round(filesize($f)/1024, 1),
            'first_ts'  => $first,
            'last_ts'   => $last,
            'first_date'=> $first ? date('d.m.Y', $first) : '-',
            'last_date' => $last  ? date('d.m.Y', $last)  : '-',
        ];
    }
    usort($result, function($a,$b){ return $b['size_kb'] - $a['size_kb']; });
    json_ok(['files'=>$result,'total_files'=>count($result),'total_kb'=>round(array_sum(array_column($result,'size_kb')),1)]);
}

if ($action === 'trim_history_file') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita superadmin', 403);
    $chipID  = preg_replace('/[^0-9a-zA-Z]/', '', $_POST['chipID'] ?? '');
    $keepDays = max(1, intval($_POST['keep_days'] ?? 30));
    if (!$chipID) json_error('chipID lipsa');

    $file = APP_ROOT . '/history/' . $chipID . '.json';
    if (!file_exists($file)) json_error('Fisier history negasit');

    $data    = json_decode(file_get_contents($file), true) ?: [];
    $cutoff  = time() - ($keepDays * 86400);
    $newData = array_values(array_filter($data, function($r) use ($cutoff) { return ($r['ts']??0) >= $cutoff; }));
    $deleted = count($data) - count($newData);
    file_put_contents($file, json_encode($newData), LOCK_EX);

    audit('TRIM_HISTORY', "Trim history stup $chipID: pastrat $keepDays zile, sters $deleted citiri");
    json_ok(['chipID'=>$chipID,'deleted'=>$deleted,'remaining'=>count($newData)]);
}

if ($action === 'delete_history_file') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita superadmin', 403);
    $chipID = preg_replace('/[^0-9a-zA-Z]/', '', $_POST['chipID'] ?? '');
    if (!$chipID) json_error('chipID lipsa');
    $file = APP_ROOT . '/history/' . $chipID . '.json';
    if (file_exists($file)) {
        unlink($file);
        $pdo = getAdminDB();
        if ($pdo) { try { $pdo->prepare("DELETE FROM mp_hive_readings WHERE chip_id=?")->execute([$chipID]); } catch(PDOException $e){} }
        audit('DELETE_HISTORY', "Sters complet history stup $chipID");
        json_ok(['deleted'=>true,'chipID'=>$chipID]);
    }
    json_error('Fisier negasit');
}

// ═══════════════════════════════════════════════════════════════
// EMAIL TEST
// ═══════════════════════════════════════════════════════════════
if ($action === 'send_test_email') {
    $to      = trim($_POST['to'] ?? '');
    $subject = 'Test Email — MATCA Admin Console';
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) json_error('Email invalid');

    $body = "<html><body style='font-family:Arial;padding:20px'>
        <h2 style='color:#d4860b'>🍯 MATCA — Email de Test</h2>
        <p>Dacă primești acest email, configurația de email a serverului funcționează corect.</p>
        <hr>
        <p style='font-size:12px;color:#888'>
            Trimis de: " . htmlspecialchars(admin_current_user()) . "<br>
            Data: " . date('d.m.Y H:i:s') . "<br>
            Server: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "
        </p>
    </body></html>";

    $headers  = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: MATCA Admin <noreply@soul2soul.ro>\r\n";
    $sent     = @mail($to, $subject, $body, $headers);
    audit('EMAIL_TEST', "Email test trimis catre $to: " . ($sent ? 'OK' : 'FAIL'));
    if ($sent) json_ok(['sent'=>true,'to'=>$to]);
    else json_error('mail() a returnat false — verificati configuratia SMTP a serverului');
}

// ═══════════════════════════════════════════════════════════════
// LOG ERORI PHP
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_error_log') {
    $lines   = max(50, min(500, intval($_GET['lines'] ?? 100)));
    $logFile = APP_ROOT . '/error_log';
    if (!file_exists($logFile)) {
        $logFile2 = dirname(APP_ROOT) . '/error_log';
        if (file_exists($logFile2)) $logFile = $logFile2;
        else json_ok(['entries'=>[],'file'=>'Nu am gasit error_log','size_kb'=>0]);
    }
    $size   = round(filesize($logFile)/1024, 1);
    $content = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $last   = array_slice($content, -$lines);
    $entries = array_reverse($last);
    json_ok(['entries'=>$entries,'file'=>$logFile,'size_kb'=>$size,'total_lines'=>count($content)]);
}

if ($action === 'clear_error_log') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita superadmin', 403);
    $logFile = APP_ROOT . '/error_log';
    if (file_exists($logFile)) { file_put_contents($logFile, ''); audit('CLEAR_ERROR_LOG', 'Error log sters'); }
    json_ok();
}

// ═══════════════════════════════════════════════════════════════
// IMPERSONARE USER (login as)
// ═══════════════════════════════════════════════════════════════
if ($action === 'impersonate_user') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita superadmin', 403);
    $targetUser = trim($_POST['username'] ?? '');
    if (!$targetUser) json_error('Username lipsa');

    $users = read_json('user.json');
    if (!isset($users[$targetUser])) json_error('Userul nu exista');

    // Salvam sesiunea admin curenta
    $_SESSION['admin_impersonating'] = admin_current_user();

    // Cream token unic pentru impersonare
    $token = bin2hex(random_bytes(16));
    $_SESSION['impersonate_token']   = $token;
    $_SESSION['impersonate_user']    = $targetUser;
    $_SESSION['impersonate_expires'] = time() + 3600;

    audit('IMPERSONATE', "Admin " . admin_current_user() . " a impersonat userul $targetUser");
    json_ok(['token'=>$token,'username'=>$targetUser,'redirect'=>'../index.php?_imp='.$token]);
}

if ($action === 'stop_impersonation') {
    $original = $_SESSION['admin_impersonating'] ?? '';
    unset($_SESSION['admin_impersonating'], $_SESSION['impersonate_token'], $_SESSION['impersonate_user'], $_SESSION['impersonate_expires']);
    audit('IMPERSONATE_STOP', "Stop impersonare, revenire la $original");
    json_ok(['redirect'=>'index.php']);
}

// ═══════════════════════════════════════════════════════════════
// EDITOR JSON LIVE
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_json_file') {
    $allowed = ['metadata.json','controllers.json','user.json','manual_hives.json','manifest.json'];
    $file    = trim($_GET['file'] ?? '');
    if (!in_array($file, $allowed)) json_error('Fisier nepermis');
    $path = APP_ROOT . '/' . $file;
    if (!file_exists($path)) json_error('Fisier inexistent');
    $content = file_get_contents($path);
    $valid   = json_decode($content) !== null;
    json_ok(['file'=>$file,'content'=>$content,'valid'=>$valid,'size_kb'=>round(filesize($path)/1024,1),'modified'=>date('d.m.Y H:i:s', filemtime($path))]);
}

if ($action === 'save_json_file') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita superadmin', 403);
    $allowed = ['metadata.json','controllers.json','user.json','manual_hives.json','manifest.json'];
    $file    = trim($_POST['file'] ?? '');
    $content = $_POST['content'] ?? '';
    if (!in_array($file, $allowed)) json_error('Fisier nepermis');

    $decoded = json_decode($content);
    if ($decoded === null && $content !== 'null') json_error('JSON invalid: ' . json_last_error_msg());

    $path    = APP_ROOT . '/' . $file;
    $backup  = $path . '.bak_' . date('YmdHis');
    if (file_exists($path)) copy($path, $backup);

    $pretty  = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($path, $pretty, LOCK_EX);
    audit('JSON_EDIT', "Editat $file (" . strlen($pretty) . " bytes)");
    json_ok(['saved'=>true,'file'=>$file,'backup'=>basename($backup)]);
}

// ═══════════════════════════════════════════════════════════════
// RAPOARTE EXTINSE
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_report_monthly') {
    $pdo   = getAdminDB();
    $year  = intval($_GET['year']  ?? date('Y'));
    $month = intval($_GET['month'] ?? date('n'));
    if (!$pdo) json_error('DB indisponibil');

    $monthStart = mktime(0,0,0,$month,1,$year);
    $monthEnd   = mktime(23,59,59,$month,cal_days_in_month(CAL_GREGORIAN,$month,$year),$year);

    $result = ['year'=>$year,'month'=>$month,'month_name'=>strftime('%B %Y', $monthStart)];

    try {
        // Citiri IoT in luna
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mp_hive_readings WHERE ts BETWEEN ? AND ?");
        $stmt->execute([$monthStart, $monthEnd]);
        $result['iot_readings'] = (int)$stmt->fetchColumn();

        // Intrari jurnal in luna
        $stmt = $pdo->prepare("SELECT COUNT(*), user FROM mp_jurnal WHERE ts BETWEEN ? AND ? GROUP BY user");
        $stmt->execute([$monthStart, $monthEnd]);
        $result['jurnal_by_user'] = $stmt->fetchAll();

        // Recolta in luna
        $stmt = $pdo->prepare("SELECT stup, SUM(kg) as kg, SUM(kg*pret) as ron FROM mp_harvest WHERE date LIKE ? GROUP BY stup");
        $likeDate = sprintf('%02d.%04d', $month, $year) . '%';
        // Alternate: search by year/month in date string dd.mm.yyyy
        $likeDate2 = '%.' . sprintf('%02d', $month) . '.' . $year;
        $stmt = $pdo->prepare("SELECT stup, SUM(kg) as kg, SUM(kg*pret) as ron FROM mp_harvest WHERE date LIKE ? OR date LIKE ? GROUP BY stup");
        $stmt->execute(['%/'.$month.'/'.$year, $likeDate2]);
        $result['harvest'] = $stmt->fetchAll();

        // Cheltuieli in luna
        $stmt = $pdo->prepare("SELECT SUM(suma) as total FROM mp_expenses WHERE date LIKE ?");
        $stmt->execute([$likeDate2]);
        $result['expenses_total'] = round(floatval($stmt->fetchColumn()), 2);

        // Sarcini rezolvate in luna
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mp_tasks WHERE done=1 AND date LIKE ?");
        $stmt->execute([$likeDate2]);
        $result['tasks_done'] = (int)$stmt->fetchColumn();

        // Top stupi activi (cele mai multe citiri)
        $stmt = $pdo->prepare("
            SELECT r.chip_id, COALESCE(m.nickname, CONCAT('Stup ',r.chip_id)) as nickname, COUNT(*) as readings,
                   ROUND(AVG(r.weight),2) as avg_weight, ROUND(MIN(r.weight),2) as min_weight, ROUND(MAX(r.weight),2) as max_weight
            FROM mp_hive_readings r LEFT JOIN mp_metadata m ON r.chip_id=m.chip_id
            WHERE r.ts BETWEEN ? AND ?
            GROUP BY r.chip_id, m.nickname ORDER BY readings DESC LIMIT 10
        ");
        $stmt->execute([$monthStart, $monthEnd]);
        $result['top_hives'] = $stmt->fetchAll();

        // Alerte in luna
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mp_alerte WHERE ts BETWEEN ? AND ?");
        $stmt->execute([$monthStart, $monthEnd]);
        $result['alerts_count'] = (int)$stmt->fetchColumn();

    } catch (PDOException $e) { json_error('DB: '.$e->getMessage()); }

    json_ok($result);
}

if ($action === 'get_report_financial') {
    $pdo  = getAdminDB();
    $year = intval($_GET['year'] ?? date('Y'));
    if (!$pdo) json_error('DB indisponibil');

    $result = ['year'=>$year,'months'=>[],'by_hive'=>[],'totals'=>[]];

    try {
        // Per luna
        for ($m = 1; $m <= 12; $m++) {
            $likeDate = '%.' . sprintf('%02d',$m) . '.' . $year;
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(kg),0) as kg, COALESCE(SUM(kg*pret),0) as ron FROM mp_harvest WHERE date LIKE ?");
            $stmt->execute([$likeDate]);
            $h = $stmt->fetch();
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(suma),0) as suma FROM mp_expenses WHERE date LIKE ?");
            $stmt->execute([$likeDate]);
            $e = $stmt->fetch();
            $result['months'][] = [
                'month'    => $m,
                'name'     => date('M', mktime(0,0,0,$m,1,$year)),
                'kg'       => round(floatval($h['kg']),2),
                'venit'    => round(floatval($h['ron']),2),
                'chelt'    => round(floatval($e['suma']),2),
                'profit'   => round(floatval($h['ron']) - floatval($e['suma']),2),
            ];
        }

        // Per stup
        $stmt = $pdo->query("SELECT stup, SUM(kg) as kg, SUM(kg*pret) as ron FROM mp_harvest GROUP BY stup ORDER BY ron DESC");
        $harvestByHive = $stmt->fetchAll();
        foreach ($harvestByHive as $h) {
            $stmt2 = $pdo->prepare("SELECT COALESCE(SUM(suma),0) FROM mp_expenses WHERE stup=?");
            $stmt2->execute([$h['stup']]);
            $chelt = floatval($stmt2->fetchColumn());
            $result['by_hive'][] = ['stup'=>$h['stup'],'kg'=>round(floatval($h['kg']),2),'venit'=>round(floatval($h['ron']),2),'chelt'=>round($chelt,2),'profit'=>round(floatval($h['ron'])-$chelt,2)];
        }

        // Totale
        $stmt = $pdo->query("SELECT COALESCE(SUM(kg),0) as kg, COALESCE(SUM(kg*pret),0) as ron FROM mp_harvest");
        $t = $stmt->fetch();
        $stmt = $pdo->query("SELECT COALESCE(SUM(suma),0) as suma FROM mp_expenses");
        $e = $stmt->fetch();
        $result['totals'] = ['kg'=>round(floatval($t['kg']),2),'venit'=>round(floatval($t['ron']),2),'chelt'=>round(floatval($e['suma']),2),'profit'=>round(floatval($t['ron'])-floatval($e['suma']),2)];

    } catch (PDOException $e) { json_error('DB: '.$e->getMessage()); }
    json_ok($result);
}

if ($action === 'export_report_csv') {
    $type = trim($_GET['type'] ?? '');
    $pdo  = getAdminDB();
    if (!$pdo) json_error('DB indisponibil');

    $filename = 'matca_raport_' . $type . '_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

    if ($type === 'stupi_complet') {
        fputcsv($out, ['Chip ID','Nickname','Greutate (kg)','Temperatura','Baterie (V)','Delta 24h','Controller','Firmware','Ultima citire','Status']);
        $rows = $pdo->query("SELECT r.chip_id, COALESCE(m.nickname,'Stup '||r.chip_id) as nickname, r.weight, r.temperature, r.battery, r.delta24, r.controller_id, r.firmware_version, r.ts FROM mp_hive_readings r INNER JOIN (SELECT chip_id,MAX(ts) as max_ts FROM mp_hive_readings GROUP BY chip_id) l ON r.chip_id=l.chip_id AND r.ts=l.max_ts LEFT JOIN mp_metadata m ON r.chip_id=m.chip_id ORDER BY nickname")->fetchAll();
        $now = time();
        foreach ($rows as $r) {
            $diff = $now - (int)$r['ts'];
            fputcsv($out, [$r['chip_id'],$r['nickname'],round($r['weight'],3),round($r['temperature'],2),round($r['battery'],3),round($r['delta24'],3),$r['controller_id']??'',$r['firmware_version']??'',date('d.m.Y H:i',$r['ts']),$diff<7200?'Online':($diff<86400?'Warning':'Offline')]);
        }
    } elseif ($type === 'jurnal_complet') {
        fputcsv($out, ['ID','Data','User','Stup','Nota','Foto']);
        $rows = $pdo->query("SELECT id,date,user,stup,text,image FROM mp_jurnal ORDER BY date DESC")->fetchAll();
        foreach ($rows as $r) { fputcsv($out, [$r['id'],$r['date'],$r['user'],$r['stup'],$r['text'],$r['image']??'']); }
    } elseif ($type === 'financiar_complet') {
        fputcsv($out, ['Tip','Data','Stup','Kg/Suma','Tip Miere/Descriere','Pret/kg','Valoare']);
        $rows = $pdo->query("SELECT 'Recolta' as tip,date,stup,kg,tip as detaliu,pret,kg*pret as val FROM mp_harvest UNION ALL SELECT 'Cheltuiala',date,stup,suma,'',0,suma FROM mp_expenses ORDER BY date DESC")->fetchAll();
        foreach ($rows as $r) { fputcsv($out, [$r['tip'],$r['date'],$r['stup'],$r['kg'],$r['detaliu']??'',$r['pret']??0,round($r['val']??0,2)]); }
    } elseif ($type === 'users_complet') {
        fputcsv($out, ['Username','Email','Admin','Stupi Alocati','Controllers','Creat la']);
        $rows = $pdo->query("SELECT username,email,is_admin,hives,controllers,created_at FROM mp_users ORDER BY username")->fetchAll();
        foreach ($rows as $r) { fputcsv($out, [$r['username'],$r['email'],$r['is_admin']?'Da':'Nu',$r['hives']??'',$r['controllers']??'',$r['created_at']??'']); }
    } elseif ($type === 'alerte_complet') {
        fputcsv($out, ['Alert ID','Data','Stup','Mesaj','Rezolvata de','Timestamp']);
        $rows = $pdo->query("SELECT alert_id,date,stup,msg,user,ts FROM mp_alerte ORDER BY ts DESC")->fetchAll();
        foreach ($rows as $r) { fputcsv($out, [$r['alert_id'],$r['date'],$r['stup'],$r['msg'],$r['user'],date('d.m.Y H:i',$r['ts']??0)]); }
    } elseif ($type === 'audit_complet') {
        fputcsv($out, ['Data','Admin','Actiune','Detaliu','IP']);
        $log = read_json_local('audit_log.json');
        foreach ($log as $l) { fputcsv($out, [$l['date']??'',$l['user']??'',$l['action']??'',$l['detail']??'',$l['ip']??'']); }
    } else {
        fputcsv($out, ['Eroare','Tip raport necunoscut: '.$type]);
    }

    fclose($out);
    audit('EXPORT_CSV', "Export CSV: $type");
    exit;
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
    foreach ($allHiveIDs as $id) { $allNames[$id] = $meta[$id]['nickname'] ?? 'Stup '.$id; }
    $result = [];
    foreach ($users as $uname => $u) {
        $hiveNames = [];
        foreach ($u['hives'] ?? [] as $id) { $hiveNames[] = $allNames[$id] ?? $id; }
        $result[] = ['username'=>$uname,'email'=>$u['email']??'','is_admin'=>!empty($u['is_admin']),'can_manage_manual'=>!empty($u['can_manage_manual']),'hives'=>$u['hives']??[],'controllers'=>$u['controllers']??[],'hive_names'=>$hiveNames];
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
    $users[$uname] = ['password'=>password_hash($pass, PASSWORD_BCRYPT),'email'=>$email,'hives'=>[],'controllers'=>[],'can_manage_manual'=>!empty($_POST['can_manage_manual']),'is_admin'=>!empty($_POST['is_admin'])];
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
    dbDelete('mp_users', $uname, 'username');
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
            $latestReadings = [];
            $readRows = $pdo->query("SELECT r.chip_id, r.weight, r.ts as lastUpdated FROM mp_hive_readings r INNER JOIN (SELECT chip_id, MAX(ts) as max_ts FROM mp_hive_readings GROUP BY chip_id) l ON r.chip_id = l.chip_id AND r.ts = l.max_ts")->fetchAll();
            foreach ($readRows as $r) $latestReadings[(string)$r['chip_id']] = $r;
            $metaRows = $pdo->query("SELECT chip_id, nickname FROM mp_metadata")->fetchAll();
            $metaMap  = [];
            foreach ($metaRows as $m) $metaMap[$m['chip_id']] = $m['nickname'];
            foreach ($ctrls as $c) {
                $chipIDs  = json_decode($c['chip_ids'] ?? '[]', true) ?: [];
                $hiveList = [];
                foreach ($chipIDs as $cid) {
                    $cid = (string)$cid;
                    $r   = $latestReadings[$cid] ?? null;
                    $online = $r ? (($now - ($r['lastUpdated'] ?? 0)) < 7200) : false;
                    $hiveList[] = ['chipID'=>$cid,'nickname'=>$metaMap[$cid]??'Stup '.$cid,'online'=>$online,'weight'=>$r?round(floatval($r['weight']),2):0];
                }
                $result[] = ['id'=>$c['ctrl_id'],'name'=>$c['name']??$c['ctrl_id'],'lastSeen'=>(int)($c['last_seen']??0),'ip'=>$c['ip']??'','vbat'=>$c['vbat']??null,'vsolar'=>$c['vsolar']??null,'hives'=>$hiveList,'hive_count'=>count($hiveList)];
            }
            json_ok($result);
        } catch (PDOException $e) { error_log('[get_controllers] DB error: ' . $e->getMessage()); }
    }
    $ctrl = read_json('controllers.json');
    $meta = read_json('metadata.json');
    $data = read_json('data.json');
    $dataByChip = [];
    foreach ($data as $h) $dataByChip[(string)$h['chipID']] = $h;
    foreach ($ctrl as $id => $c) {
        $chipIDs = $c['chipIDs'] ?? [];
        $hiveList = [];
        foreach ($chipIDs as $cid) {
            $cid = (string)$cid;
            $h   = $dataByChip[$cid] ?? null;
            $hiveList[] = ['chipID'=>$cid,'nickname'=>$meta[$cid]['nickname']??'Stup '.$cid,'online'=>$h?(($now-($h['lastUpdated']??0))<7200):false,'weight'=>$h['weight']??0];
        }
        $result[] = ['id'=>$id,'name'=>$c['name']??$id,'lastSeen'=>$c['lastSeen']??0,'ip'=>$c['ip']??'','hives'=>$hiveList,'hive_count'=>count($hiveList)];
    }
    json_ok($result);
}

if ($action === 'update_controller') {
    $id = trim($_POST['id'] ?? '');
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
    $chipID  = trim($_POST['chipID'] ?? '');
    $newCtrl = trim($_POST['new_controller'] ?? '');
    if (!$chipID) json_error('chipID lipsa');
    $data = read_json('data.json');
    $updated = false;
    foreach ($data as &$h) {
        if ((string)$h['chipID'] === $chipID) { $oldCtrl = $h['controllerID']??''; $h['controllerID']=$newCtrl; $updated=true; audit('HIVE_MOVE',"Mutat stup $chipID: $oldCtrl → $newCtrl"); break; }
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
    $page   = max(1, intval($_GET['page'] ?? 1));
    $limit  = intval($_GET['limit'] ?? 50);
    $filter = strtolower(trim($_GET['filter'] ?? ''));
    $stup   = trim($_GET['stup'] ?? '');
    $user   = trim($_GET['user'] ?? '');
    if ($filter && $filter !== 'toate') {
        $keyword = '';
        if ($filter === 'inspectii')      $keyword = 'inspect';
        elseif ($filter === 'tratamente') $keyword = 'tratam';
        elseif ($filter === 'sarcini')    $keyword = 'sarcin';
        elseif ($filter === 'ok')         $keyword = 'stup ok';
        else $keyword = $filter;
        $filtered = [];
        foreach ($jurnal as $j) { if (strpos(strtolower($j['text']??''), $keyword) !== false) $filtered[] = $j; }
        $jurnal = $filtered;
    }
    if ($stup) { $filtered=[]; foreach ($jurnal as $j) { if (($j['stup']??'') === $stup) $filtered[]=$j; } $jurnal=$filtered; }
    if ($user) { $filtered=[]; foreach ($jurnal as $j) { if (($j['user']??'') === $user) $filtered[]=$j; } $jurnal=$filtered; }
    $total = count($jurnal);
    $paged = array_slice($jurnal, ($page-1)*$limit, $limit);
    json_ok(['items'=>$paged,'total'=>$total,'pages'=>(int)ceil($total/$limit)]);
}

if ($action === 'delete_jurnal') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) json_error('ID lipsa');
    $jurnal = read_json('jurnal.json');
    $new = []; foreach ($jurnal as $j) { if ($j['id'] !== $id) $new[] = $j; }
    write_json('jurnal.json', $new);
    dbDelete('mp_jurnal', $id);
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
    if ($stup) { $f=[]; foreach ($harvest as $h) { if (($h['stup']??'') === $stup) $f[]=$h; } $harvest=$f; }
    if ($year) { $f=[]; foreach ($harvest as $h) { if (strpos($h['date']??'',$year)!==false) $f[]=$h; } $harvest=$f; }
    $totalKg=0; $totalRon=0;
    foreach ($harvest as $h) { $totalKg+=floatval($h['kg']??0); $totalRon+=floatval($h['kg']??0)*floatval($h['pret']??0); }
    $totalExp=0; foreach ($expenses as $e) { $totalExp+=floatval($e['suma']??0); }
    json_ok(['harvest'=>array_values($harvest),'expenses'=>array_values($expenses),'total_kg'=>round($totalKg,2),'total_ron'=>round($totalRon,2),'total_exp'=>round($totalExp,2),'profit'=>round($totalRon-$totalExp,2)]);
}

if ($action === 'delete_harvest') {
    $id=trim($_POST['id']??''); if(!$id) json_error('ID lipsa');
    $h=read_json('harvest.json'); $new=[]; foreach($h as $r){if($r['id']!==$id)$new[]=$r;} write_json('harvest.json',$new);
    dbDelete('mp_harvest',$id); audit('HARVEST_DELETE',"Sters recolta $id"); json_ok();
}

if ($action === 'delete_expense') {
    $id=trim($_POST['id']??''); if(!$id) json_error('ID lipsa');
    $e=read_json('expenses.json'); $new=[]; foreach($e as $r){if($r['id']!==$id)$new[]=$r;} write_json('expenses.json',$new);
    dbDelete('mp_expenses',$id); audit('EXPENSE_DELETE',"Stearsa cheltuiala $id"); json_ok();
}

// ═══════════════════════════════════════════════════════════════
// ALERTE
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_alerts') {
    $log = read_json('alerte_log.json'); $rez = read_json('alerte_rezolvate.json'); $meta = read_json('metadata.json');
    $active = [];
    foreach ($log as $id => $a) {
        if (!empty($a['active'])) { $cid=explode('_',$id)[0]??''; $name=$meta[$cid]['nickname']??$cid; $active[]=array_merge($a,['id'=>$id,'hive_name'=>$name]); }
    }
    json_ok(['active'=>$active,'resolved'=>array_slice($rez,0,200)]);
}

if ($action === 'resolve_alert') {
    $id=trim($_POST['id']??''); $user=admin_current_user(); if(!$id) json_error('ID lipsa');
    $log=read_json('alerte_log.json'); if(!isset($log[$id])) json_error('Alerta negasita');
    $rez=read_json('alerte_rezolvate.json');
    $rez[]=['alert_id'=>$id,'stup'=>$log[$id]['stup']??'','msg'=>$log[$id]['msg']??'','date'=>date('d.m.Y H:i'),'user'=>$user,'hive_ts'=>$log[$id]['ts']??0];
    write_json('alerte_rezolvate.json',$rez); unset($log[$id]); write_json('alerte_log.json',$log);
    audit('ALERT_RESOLVE',"Rezolvata alerta $id"); json_ok();
}

if ($action === 'resolve_all_alerts') {
    $log=read_json('alerte_log.json'); $rez=read_json('alerte_rezolvate.json'); $user=admin_current_user(); $cnt=0;
    foreach($log as $id=>$a){ if(!empty($a['active'])){ $rez[]=['alert_id'=>$id,'stup'=>$a['stup']??'','msg'=>$a['msg']??'','date'=>date('d.m.Y H:i'),'user'=>$user,'hive_ts'=>$a['ts']??0]; unset($log[$id]); $cnt++; } }
    write_json('alerte_log.json',$log); write_json('alerte_rezolvate.json',$rez);
    audit('ALERT_RESOLVE_ALL',"Rezolvate toate alertele ($cnt)"); json_ok(['count'=>$cnt]);
}

// ═══════════════════════════════════════════════════════════════
// TASKS
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_tasks') {
    $tasks=read_json('tasks.json'); $filter=$_GET['filter']??'all'; $user=trim($_GET['user']??'');
    if($filter==='pending'){$f=[];foreach($tasks as $t){if(empty($t['done']))$f[]=$t;}$tasks=$f;}
    if($filter==='done'){$f=[];foreach($tasks as $t){if(!empty($t['done']))$f[]=$t;}$tasks=$f;}
    if($user){$f=[];foreach($tasks as $t){if(($t['user']??')===$user)$f[]=$t;}$tasks=$f;}
    json_ok(array_values($tasks));
}

if ($action === 'delete_task') {
    $id=trim($_POST['id']??''); $t=read_json('tasks.json'); $new=[]; foreach($t as $r){if($r['id']!==$id)$new[]=$r;} write_json('tasks.json',$new);
    dbDelete('mp_tasks',$id); audit('TASK_DELETE',"Stearsa sarcina $id"); json_ok();
}

if ($action === 'toggle_task') {
    $id=trim($_POST['id']??''); $t=read_json('tasks.json');
    foreach($t as &$task){if($task['id']===$id){$task['done']=!($task['done']??false);break;}} unset($task);
    write_json('tasks.json',$t); json_ok();
}

// ═══════════════════════════════════════════════════════════════
// INVENTORY
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_inventory') { json_ok(read_json('inventory.json')); }

if ($action === 'save_inventory_item') {
    $inv=read_json('inventory.json'); $id=trim($_POST['id']??'')?:uniqid(); $item=trim($_POST['item']??'');
    if(!$item) json_error('Nume produs lipsa');
    $row=['id'=>$id,'item'=>$item,'qty'=>floatval($_POST['qty']??0),'type'=>trim($_POST['type']??'Bucati'),'category'=>trim($_POST['category']??'')];
    $found=false; foreach($inv as &$r){if($r['id']===$id){$r=$row;$found=true;break;}} unset($r);
    if(!$found) $inv[]=$row;
    write_json('inventory.json',$inv); dbSync('mp_inventory',$row); audit('INVENTORY_SAVE',"Salvat produs: $item"); json_ok($row);
}

if ($action === 'delete_inventory_item') {
    $id=trim($_POST['id']??''); $inv=read_json('inventory.json'); $new=[]; foreach($inv as $r){if($r['id']!==$id)$new[]=$r;}
    write_json('inventory.json',$new); dbDelete('mp_inventory',$id); audit('INVENTORY_DELETE',"Sters produs $id"); json_ok();
}

// ═══════════════════════════════════════════════════════════════
// DATABASE
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_db_stats') {
    $pdo = getAdminDB();
    if (!$pdo) json_error('Conexiune DB esuata');
    $tables = ['mp_hive_readings','mp_jurnal','mp_harvest','mp_expenses','mp_inventory','mp_tasks','mp_alerte','mp_queen_history','mp_metadata','mp_manual_hives','mp_controllers','mp_users','mp_markers','mp_apiary_location'];
    $stats = [];
    foreach ($tables as $t) {
        try { $n=$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); $stats[$t]=intval($n); }
        catch (PDOException $e) { $stats[$t]=null; }
    }
    $size = 0;
    try { $size=$pdo->query("SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) AS mb FROM information_schema.tables WHERE table_schema='".DB_NAME."'")->fetchColumn(); } catch (PDOException $e) {}
    json_ok(['tables'=>$stats,'size_mb'=>$size]);
}

if ($action === 'query_table') {
    $pdo = getAdminDB();
    if (!$pdo) json_error('DB indisponibil');
    $table  = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table'] ?? '');
    $limit  = min(500, intval($_GET['limit']  ?? 100));
    $offset = max(0,   intval($_GET['offset'] ?? 0));
    $search = trim($_GET['search'] ?? '');
    $allowed = ['mp_hive_readings','mp_jurnal','mp_harvest','mp_expenses','mp_inventory','mp_tasks','mp_alerte','mp_queen_history','mp_metadata','mp_manual_hives','mp_controllers','mp_users','mp_markers','mp_apiary_location'];
    if (!in_array($table, $allowed)) json_error('Tabel nepermis');
    try {
        $where=''; $params=[];
        if ($search) {
            $cols=$pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll();
            $textCols=[];
            foreach($cols as $c){ if(strpos($c['Type'],'char')!==false||strpos($c['Type'],'text')!==false) $textCols[]=$c; }
            if($textCols){ $conds=[]; foreach(array_slice($textCols,0,4) as $c){ $conds[]="`{$c['Field']}` LIKE :search"; } $where='WHERE ('.implode(' OR ',$conds).')'; $params[':search']='%'.$search.'%'; }
        }
        $stmtCnt=$pdo->prepare("SELECT COUNT(*) FROM `$table` $where"); $stmtCnt->execute($params); $totalRows=$stmtCnt->fetchColumn();
        $stmt=$pdo->prepare("SELECT * FROM `$table` $where ORDER BY 1 DESC LIMIT $limit OFFSET $offset"); $stmt->execute($params);
        $rows=$stmt->fetchAll(); $cols=$rows?array_keys($rows[0]):[];
        json_ok(['rows'=>$rows,'columns'=>$cols,'total'=>intval($totalRows)]);
    } catch (PDOException $e) { json_error('Query error: '.$e->getMessage()); }
}

if ($action === 'run_sql') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita rol superadmin', 403);
    $pdo = getAdminDB();
    if (!$pdo) json_error('DB indisponibil');
    $sql = trim($_POST['sql'] ?? '');
    if (!$sql) json_error('SQL gol');
    $sqlUpper = strtoupper($sql);
    $blocked  = ['DROP TABLE','DROP DATABASE','TRUNCATE','ALTER TABLE','GRANT','REVOKE','FLUSH'];
    foreach ($blocked as $b) { if (strpos($sqlUpper, $b) !== false) json_error("Operatia $b este blocata"); }
    try {
        audit('SQL_QUERY', substr($sql, 0, 200));
        $isSelect = (strpos($sqlUpper,'SELECT')===0||strpos($sqlUpper,'SHOW')===0||strpos($sqlUpper,'DESCRIBE')===0);
        if ($isSelect) { $stmt=$pdo->query($sql); $rows=$stmt->fetchAll(); $cols=$rows?array_keys($rows[0]):[]; json_ok(['rows'=>$rows,'columns'=>$cols,'count'=>count($rows)]); }
        else { $affected=$pdo->exec($sql); json_ok(['affected'=>$affected]); }
    } catch (PDOException $e) { json_error('SQL Error: '.$e->getMessage()); }
}

if ($action === 'export_table_csv') {
    $table   = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table'] ?? '');
    $allowed = ['mp_jurnal','mp_harvest','mp_expenses','mp_inventory','mp_tasks'];
    if (!in_array($table, $allowed)) json_error('Export nepermis');
    $pdo = getAdminDB();
    if (!$pdo) json_error('DB indisponibil');
    try { $rows=$pdo->query("SELECT * FROM `$table` ORDER BY 1 DESC")->fetchAll(); }
    catch (PDOException $e) { json_error('Export error: '.$e->getMessage()); }
    $filename = str_replace('mp_', '', $table);
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename={$filename}_".date('Ymd').".csv");
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    if ($rows) { fputcsv($out, array_keys($rows[0])); foreach ($rows as $row) fputcsv($out, $row); }
    fclose($out); exit;
}

// ═══════════════════════════════════════════════════════════════
// AUDIT LOG
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_audit_log') {
    $log   = read_json_local('audit_log.json');
    $page  = max(1, intval($_GET['page']  ?? 1));
    $limit = intval($_GET['limit']        ?? 50);
    $act   = strtoupper(trim($_GET['action_filter'] ?? ''));
    if ($act) { $filtered=[]; foreach($log as $l){if(strpos($l['action']??'',$act)!==false)$filtered[]=$l;} $log=$filtered; }
    $total=count($log); $paged=array_slice($log,($page-1)*$limit,$limit);
    json_ok(['items'=>$paged,'total'=>$total,'pages'=>(int)ceil($total/$limit)]);
}

if ($action === 'clear_audit_log') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita rol superadmin', 403);
    write_json_local('audit_log.json', []); json_ok();
}

// ═══════════════════════════════════════════════════════════════
// ADMIN USERS
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_admin_users') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita rol superadmin', 403);
    $admins = load_admins(); $result=[];
    foreach ($admins as $u => $a) { $result[]=['username'=>$u,'role'=>$a['role']??'admin','email'=>$a['email']??'','name'=>$a['name']??$u,'created'=>$a['created']??'']; }
    json_ok($result);
}

if ($action === 'create_admin_user') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita rol superadmin', 403);
    $uname=trim($_POST['username']??''); $pass=$_POST['password']??'';
    $role=in_array($_POST['role']??'',['superadmin','admin'])?$_POST['role']:'admin';
    if(!$uname||strlen($pass)<6) json_error('Date invalide');
    $admins=load_admins(); if(isset($admins[$uname])) json_error('Admin exista deja');
    $admins[$uname]=['password'=>password_hash($pass,PASSWORD_BCRYPT),'role'=>$role,'email'=>trim($_POST['email']??''),'name'=>trim($_POST['name']??$uname),'created'=>date('Y-m-d H:i:s')];
    file_put_contents(ADMINS_FILE, json_encode($admins, JSON_PRETTY_PRINT));
    audit('ADMIN_CREATE',"Creat admin: $uname ($role)"); json_ok();
}

if ($action === 'delete_admin_user') {
    if (admin_current_role() !== 'superadmin') json_error('Necesita rol superadmin', 403);
    $uname=trim($_POST['username']??'');
    if($uname===admin_current_user()) json_error('Nu te poti sterge pe tine insuti');
    $admins=load_admins(); unset($admins[$uname]);
    file_put_contents(ADMINS_FILE, json_encode($admins, JSON_PRETTY_PRINT));
    audit('ADMIN_DELETE',"Sters admin: $uname"); json_ok();
}

if ($action === 'change_own_password') {
    $old=$_POST['old_password']??''; $new=$_POST['new_password']??'';
    if(strlen($new)<6) json_error('Parola noua min 6 caractere');
    $admins=load_admins(); $me=admin_current_user();
    if(!password_verify($old,$admins[$me]['password']??'')) json_error('Parola veche incorecta');
    $admins[$me]['password']=password_hash($new,PASSWORD_BCRYPT);
    file_put_contents(ADMINS_FILE, json_encode($admins, JSON_PRETTY_PRINT));
    audit('AUTH','Schimbata parola proprie'); json_ok();
}

// ═══════════════════════════════════════════════════════════════
// HISTORY
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_hive_history') {
    $chipID = preg_replace('/[^0-9a-zA-Z]/', '', $_GET['chipID'] ?? '');
    $days   = min(90, max(1, intval($_GET['days'] ?? 7)));
    if (!$chipID) json_error('chipID lipsa');
    $cutoff = time() - ($days * 86400);
    $pdo = getAdminDB();
    if ($pdo) {
        try {
            $stmt=$pdo->prepare("SELECT ts,weight,temperature,battery FROM mp_hive_readings WHERE chip_id=? AND ts>=? ORDER BY ts ASC");
            $stmt->execute([$chipID,$cutoff]); json_ok($stmt->fetchAll());
        } catch (PDOException $e) {}
    }
    $file = APP_ROOT . '/history/' . $chipID . '.json';
    if (!file_exists($file)) json_ok([]);
    $data=json_decode(file_get_contents($file),true)?:[];
    $filtered=[]; foreach($data as $r){if(($r['ts']??0)>=$cutoff)$filtered[]=$r;}
    json_ok($filtered);
}

if ($action === 'logout') { admin_logout(); json_ok(); }

// ═══════════════════════════════════════════════════════════════
// QUEEN HISTORY
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_queens') {
    $pdo=$getAdminDB=getAdminDB(); $chipID=trim($_GET['chipID']??'');
    $page=max(1,intval($_GET['page']??1)); $limit=intval($_GET['limit']??50); $offset=($page-1)*$limit;
    if ($pdo) {
        try {
            $where=$chipID?"WHERE q.chip_id = :cid":""; $params=$chipID?[':cid'=>$chipID]:[];
            $stmtC=$pdo->prepare("SELECT COUNT(*) FROM mp_queen_history q $where"); $stmtC->execute($params); $total=(int)$stmtC->fetchColumn();
            $stmt=$pdo->prepare("SELECT q.*,m.nickname FROM mp_queen_history q LEFT JOIN mp_metadata m ON q.chip_id=m.chip_id $where ORDER BY q.date DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params); json_ok(['items'=>$stmt->fetchAll(),'total'=>$total,'pages'=>(int)ceil($total/$limit)]);
        } catch (PDOException $e) { json_error('DB: '.$e->getMessage()); }
    }
    json_ok(['items'=>read_json('queen_history.json'),'total'=>0,'pages'=>1]);
}

if ($action === 'save_queen') {
    $id=trim($_POST['id']??'')?:uniqid(); $chipID=trim($_POST['chipID']??'');
    if(!$chipID) json_error('chipID lipsa');
    $row=['id'=>$id,'chip_id'=>$chipID,'event'=>trim($_POST['event']??'Inregistrare'),'breed'=>trim($_POST['breed']??''),'year'=>trim($_POST['year']??''),'notes'=>trim($_POST['notes']??''),'date'=>trim($_POST['date']??date('d.m.Y H:i')),'user'=>admin_current_user()];
    $pdo=getAdminDB();
    if($pdo){try{$cols=array_keys($row);$cList=implode(',',array_map(function($c){return"`$c`";},$cols));$pList=implode(',',array_map(function($c){return":$c";},$cols));$uList=implode(',',array_map(function($c){return"`$c`=VALUES(`$c`)";},$cols));$pdo->prepare("INSERT INTO mp_queen_history ($cList) VALUES ($pList) ON DUPLICATE KEY UPDATE $uList")->execute($row);}catch(PDOException $e){}}
    $q=read_json('queen_history.json'); $found=false;
    foreach($q as &$item){if($item['id']===$id){$item=['id'=>$id,'chipID'=>$chipID,'event'=>$row['event'],'breed'=>$row['breed'],'year'=>$row['year'],'notes'=>$row['notes'],'date'=>$row['date'],'user'=>$row['user']];$found=true;break;}} unset($item);
    if(!$found) array_unshift($q,['id'=>$id,'chipID'=>$chipID,'event'=>$row['event'],'breed'=>$row['breed'],'year'=>$row['year'],'notes'=>$row['notes'],'date'=>$row['date'],'user'=>$row['user']]);
    write_json('queen_history.json',$q); audit('QUEEN_SAVE',"Eveniment matca: $chipID / {$row['event']}"); json_ok($row);
}

if ($action === 'delete_queen') {
    $id=trim($_POST['id']??''); $pdo=getAdminDB();
    if($pdo){try{$pdo->prepare("DELETE FROM mp_queen_history WHERE id=?")->execute([$id]);}catch(PDOException $e){}}
    $q=read_json('queen_history.json');
    write_json('queen_history.json',array_values(array_filter($q,function($x)use($id){return($x['id']??'')!==$id;})));
    audit('QUEEN_DELETE',"Sters eveniment matca $id"); json_ok();
}

// ═══════════════════════════════════════════════════════════════
// ALERTE — istoric complet + delete
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_alerts_full') {
    $pdo=getAdminDB(); $page=max(1,intval($_GET['page']??1)); $limit=intval($_GET['limit']??50); $offset=($page-1)*$limit; $stup=trim($_GET['stup']??'');
    if($pdo){
        try{
            $where=$stup?"WHERE stup = :stup":""; $params=$stup?[':stup'=>$stup]:[];
            $stmtC=$pdo->prepare("SELECT COUNT(*) FROM mp_alerte $where"); $stmtC->execute($params); $total=(int)$stmtC->fetchColumn();
            $stmt=$pdo->prepare("SELECT * FROM mp_alerte $where ORDER BY ts DESC LIMIT $limit OFFSET $offset"); $stmt->execute($params);
            json_ok(['items'=>$stmt->fetchAll(),'total'=>$total,'pages'=>(int)ceil($total/$limit)]);
        }catch(PDOException $e){json_error('DB: '.$e->getMessage());}
    }
    json_ok(['items'=>[],'total'=>0,'pages'=>1]);
}

if ($action === 'delete_alert') {
    $id=trim($_POST['alert_id']??''); $pdo=getAdminDB();
    if($pdo){try{$pdo->prepare("DELETE FROM mp_alerte WHERE alert_id=?")->execute([$id]);}catch(PDOException $e){json_error('DB: '.$e->getMessage());}}
    $a=read_json('alerte_rezolvate.json');
    write_json('alerte_rezolvate.json',array_values(array_filter($a,function($x)use($id){return($x['alert_id']??'')!==$id;})));
    audit('ALERT_DELETE',"Sters alerta $id"); json_ok();
}

if ($action === 'delete_all_alerts') {
    if(admin_current_role()!=='superadmin') json_error('Necesita superadmin',403);
    $pdo=getAdminDB();
    if($pdo){try{$pdo->exec("DELETE FROM mp_alerte");}catch(PDOException $e){json_error('DB: '.$e->getMessage());}}
    write_json('alerte_rezolvate.json',[]); audit('ALERT_DELETE_ALL','Sterse toate alertele'); json_ok();
}

// ═══════════════════════════════════════════════════════════════
// SYSTEM HEALTH
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_system_health') {
    $pdo=$pdo??getAdminDB(); $now=time();
    $staleHives=[]; $lowBattery=[]; $tableStats=[]; $expiredTasks=0; $readingsLast24h=0; $inactiveUsers=[];
    if($pdo){
        try{$cutoff=$now-86400;$rows=$pdo->query("SELECT r.chip_id,m.nickname,MAX(r.ts) as last_ts FROM mp_hive_readings r LEFT JOIN mp_metadata m ON r.chip_id=m.chip_id GROUP BY r.chip_id,m.nickname HAVING MAX(r.ts) < $cutoff ORDER BY last_ts ASC")->fetchAll();foreach($rows as $r){$staleHives[]=['chip_id'=>$r['chip_id'],'nickname'=>$r['nickname']??'Stup '.$r['chip_id'],'last_ts'=>(int)$r['last_ts'],'hours_ago'=>$r['last_ts']?round(($now-$r['last_ts'])/3600,1):null];}}catch(PDOException $e){}
        try{$rows=$pdo->query("SELECT r.chip_id,m.nickname,r.battery,r.ts FROM mp_hive_readings r INNER JOIN (SELECT chip_id,MAX(ts) as max_ts FROM mp_hive_readings GROUP BY chip_id) l ON r.chip_id=l.chip_id AND r.ts=l.max_ts LEFT JOIN mp_metadata m ON r.chip_id=m.chip_id WHERE r.battery>0 AND r.battery<3.4 ORDER BY r.battery ASC")->fetchAll();foreach($rows as $r){$lowBattery[]=['chip_id'=>$r['chip_id'],'nickname'=>$r['nickname']??'Stup '.$r['chip_id'],'battery'=>round(floatval($r['battery']),3),'ts'=>(int)$r['ts']];}}catch(PDOException $e){}
        foreach(['mp_hive_readings','mp_jurnal','mp_tasks','mp_harvest','mp_expenses','mp_alerte'] as $t){try{$cnt=(int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();$size=$pdo->query("SELECT ROUND((data_length+index_length)/1024/1024,3) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='$t'")->fetchColumn();$tableStats[$t]=['rows'=>$cnt,'mb'=>floatval($size??0)];}catch(PDOException $e){$tableStats[$t]=['rows'=>0,'mb'=>0];}}
        try{$allTasks=$pdo->query("SELECT date FROM mp_tasks WHERE done=0")->fetchAll();foreach($allTasks as $t){$parts=explode('.',substr($t['date']??'',0,10));if(count($parts)===3){$ts=mktime(0,0,0,(int)$parts[1],(int)$parts[0],(int)$parts[2]);if($ts<strtotime('today'))$expiredTasks++;}}}catch(PDOException $e){}
        try{$readingsLast24h=(int)$pdo->query("SELECT COUNT(*) FROM mp_hive_readings WHERE ts >= ".($now-86400))->fetchColumn();}catch(PDOException $e){}
        try{$cutoff30=date('d.m.Y',strtotime('-30 days'));$users=$pdo->query("SELECT username FROM mp_users")->fetchAll();foreach($users as $u){$stmt=$pdo->prepare("SELECT COUNT(*) FROM mp_jurnal WHERE `user`=? AND date >= ?");$stmt->execute([$u['username'],$cutoff30]);if((int)$stmt->fetchColumn()===0)$inactiveUsers[]=$u['username'];}}catch(PDOException $e){}
    }
    $serverInfo=['php_version'=>PHP_VERSION,'php_sapi'=>PHP_SAPI,'memory_limit'=>ini_get('memory_limit'),'max_exec_time'=>ini_get('max_execution_time'),'upload_max'=>ini_get('upload_max_filesize'),'disk_free_gb'=>round(disk_free_space('/')/1073741824,2),'disk_total_gb'=>round(disk_total_space('/')/1073741824,2),'server_time'=>date('d.m.Y H:i:s'),'app_root_size'=>null];
    try{$size=0;$iter=new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ROOT,FilesystemIterator::SKIP_DOTS));foreach($iter as $f){if($f->isFile())$size+=$f->getSize();}$serverInfo['app_root_size']=round($size/1048576,2);}catch(Exception $e){}
    $jsonFiles=[];
    foreach(['data.json','metadata.json','user.json','jurnal.json','tasks.json','harvest.json','expenses.json','inventory.json','queen_history.json','map_markers.json','alerte_rezolvate.json'] as $f){$path=APP_ROOT.'/'.$f;if(!file_exists($path))$path=ADMIN_CONSOLE_ROOT.'/'.$f;$jsonFiles[$f]=['exists'=>file_exists($path),'size_kb'=>file_exists($path)?round(filesize($path)/1024,1):0,'modified'=>file_exists($path)?date('d.m.Y H:i',filemtime($path)):null,'valid'=>file_exists($path)?(json_decode(file_get_contents($path))!==null):false];}
    json_ok(['stale_hives'=>$staleHives,'low_battery'=>$lowBattery,'inactive_users'=>$inactiveUsers,'table_stats'=>$tableStats,'server_info'=>$serverInfo,'json_files'=>$jsonFiles,'expired_tasks'=>$expiredTasks,'readings_last_24h'=>$readingsLast24h,'generated_at'=>date('d.m.Y H:i:s')]);
}

// ═══════════════════════════════════════════════════════════════
// RAPOARTE ADMIN (existente)
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_admin_report') {
    $pdo=getAdminDB(); $type=trim($_GET['type']??'overview');
    if($type==='activity'){
        $result=[];
        if($pdo){try{$users=$pdo->query("SELECT username FROM mp_users ORDER BY username")->fetchAll();foreach($users as $u){$un=$u['username'];$stmt=$pdo->prepare("SELECT COUNT(*) FROM mp_jurnal WHERE `user`=?");$stmt->execute([$un]);$jCnt=(int)$stmt->fetchColumn();$stmt=$pdo->prepare("SELECT COUNT(*) FROM mp_tasks WHERE `user`=? AND done=1");$stmt->execute([$un]);$tDone=(int)$stmt->fetchColumn();$stmt=$pdo->prepare("SELECT COUNT(*) FROM mp_tasks WHERE `user`=? AND done=0");$stmt->execute([$un]);$tPend=(int)$stmt->fetchColumn();$stmt=$pdo->prepare("SELECT MAX(date) FROM mp_jurnal WHERE `user`=?");$stmt->execute([$un]);$last=$stmt->fetchColumn();$result[]=['user'=>$un,'jurnal'=>$jCnt,'tasks_done'=>$tDone,'tasks_pending'=>$tPend,'last_activity'=>$last];}}catch(PDOException $e){}}
        json_ok($result);
    }
    if($type==='hive_performance'){
        $result=[];
        if($pdo){try{$meta=$pdo->query("SELECT chip_id,nickname FROM mp_metadata")->fetchAll();foreach($meta as $m){$cid=$m['chip_id'];$nick=$m['nickname']?:'Stup '.$cid;$stmt=$pdo->prepare("SELECT COALESCE(SUM(kg),0) FROM mp_harvest WHERE stup=?");$stmt->execute([$nick]);$kg=$stmt->fetchColumn();$stmt=$pdo->prepare("SELECT COALESCE(SUM(kg*pret),0) FROM mp_harvest WHERE stup=?");$stmt->execute([$nick]);$ron=$stmt->fetchColumn();$stmt=$pdo->prepare("SELECT COALESCE(SUM(suma),0) FROM mp_expenses WHERE stup=?");$stmt->execute([$nick]);$exp=$stmt->fetchColumn();$stmt=$pdo->prepare("SELECT COUNT(*) FROM mp_hive_readings WHERE chip_id=?");$stmt->execute([$cid]);$readings=(int)$stmt->fetchColumn();$stmt=$pdo->prepare("SELECT MAX(date) FROM mp_jurnal WHERE stup=?");$stmt->execute([$nick]);$lastJ=$stmt->fetchColumn();$result[]=['chip_id'=>$cid,'nickname'=>$nick,'kg_total'=>round(floatval($kg),2),'ron_total'=>round(floatval($ron),2),'expenses'=>round(floatval($exp),2),'profit'=>round(floatval($ron)-floatval($exp),2),'readings'=>$readings,'last_jurnal'=>$lastJ];}}catch(PDOException $e){}}
        json_ok($result);
    }
    if($type==='data_growth'){
        $result=[];
        if($pdo){try{for($i=29;$i>=0;$i--){$dayStart=strtotime("midnight -$i days");$dayEnd=$dayStart+86399;$cnt=(int)$pdo->query("SELECT COUNT(*) FROM mp_hive_readings WHERE ts BETWEEN $dayStart AND $dayEnd")->fetchColumn();$result[]=['date'=>date('d.m',$dayStart),'readings'=>$cnt];}}catch(PDOException $e){}}
        json_ok($result);
    }
    json_error('Tip raport necunoscut');
}

// ═══════════════════════════════════════════════════════════════
// BACKUP
// ═══════════════════════════════════════════════════════════════
if ($action === 'backup_json') {
    if(!class_exists('ZipArchive')) json_error('ZipArchive nu este disponibil');
    $zipName='matca_backup_'.date('Ymd_His').'.zip'; $zipPath=sys_get_temp_dir().'/'.$zipName;
    $zip=new ZipArchive(); if($zip->open($zipPath,ZipArchive::CREATE)!==true) json_error('Nu am putut crea arhiva ZIP');
    $files=['data.json','metadata.json','controllers.json','user.json','jurnal.json','tasks.json','harvest.json','expenses.json','inventory.json','queen_history.json','map_markers.json','alerte_rezolvate.json','manual_hives.json','admins.json'];
    $added=0;
    foreach($files as $f){$path=file_exists(APP_ROOT.'/'.$f)?APP_ROOT.'/'.$f:ADMIN_CONSOLE_ROOT.'/'.$f;if(file_exists($path)){$zip->addFile($path,$f);$added++;}}
    $histDir=APP_ROOT.'/history';
    if(is_dir($histDir)){foreach(glob($histDir.'/*.json')?:[] as $hf){$zip->addFile($hf,'history/'.basename($hf));$added++;}}
    $zip->addFromString('README.txt',"Backup MatcaDB\nGenerat: ".date('d.m.Y H:i:s')."\nAdmin: ".admin_current_user()."\nFisiere: $added\n");
    $zip->close();
    audit('BACKUP',"JSON backup: $added fisiere");
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$zipName.'"');
    header('Content-Length: '.filesize($zipPath));
    header('Cache-Control: no-cache');
    readfile($zipPath); unlink($zipPath); exit;
}

if ($action === 'backup_sql') {
    if(admin_current_role()!=='superadmin') json_error('Necesita superadmin',403);
    $pdo=getAdminDB(); if(!$pdo) json_error('DB indisponibil');
    $tables=['mp_hive_readings','mp_metadata','mp_manual_hives','mp_controllers','mp_users','mp_jurnal','mp_tasks','mp_harvest','mp_expenses','mp_inventory','mp_queen_history','mp_markers','mp_alerte','mp_apiary_location'];
    $filename='matca_db_backup_'.date('Ymd_His').'.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: no-cache');
    echo "-- MatcaDB SQL Backup\n-- Generat: ".date('d.m.Y H:i:s')."\n-- Admin: ".admin_current_user()."\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach($tables as $table){try{$create=$pdo->query("SHOW CREATE TABLE `$table`")->fetch();echo"-- Table: $table\nDROP TABLE IF EXISTS `$table`;\n".$create['Create Table'].";\n\n";$rows=$pdo->query("SELECT * FROM `$table`")->fetchAll();if($rows){$cols='`'.implode('`,`',array_keys($rows[0])).'`';echo"INSERT INTO `$table` ($cols) VALUES\n";$lines=[];foreach($rows as $row){$vals=array_map(function($v){return$v===null?'NULL':"'".addslashes($v)."'";},$values=array_values($row));$lines[]='('.implode(',',$vals).')';}echo implode(",\n",$lines).";\n\n";}}catch(PDOException $e){echo"-- EROARE la $table: ".$e->getMessage()."\n\n";}}
    echo "SET FOREIGN_KEY_CHECKS=1;\n-- END BACKUP\n";
    audit('BACKUP','SQL dump generat'); exit;
}

if ($action === 'get_backup_info') {
    $pdo=getAdminDB(); $info=['json_files'=>[],'db_tables'=>[],'total_json_kb'=>0,'total_db_rows'=>0,'history_files'=>0];
    foreach(['data.json','metadata.json','user.json','jurnal.json','tasks.json','harvest.json','expenses.json','inventory.json','queen_history.json','map_markers.json','alerte_rezolvate.json'] as $f){$path=file_exists(APP_ROOT.'/'.$f)?APP_ROOT.'/'.$f:ADMIN_CONSOLE_ROOT.'/'.$f;if(file_exists($path)){$kb=round(filesize($path)/1024,1);$info['json_files'][]=['name'=>$f,'kb'=>$kb,'modified'=>date('d.m.Y H:i',filemtime($path))];$info['total_json_kb']+=$kb;}}
    $info['history_files']=count(glob(APP_ROOT.'/history/*.json')?:[]);
    if($pdo){foreach(['mp_hive_readings','mp_metadata','mp_manual_hives','mp_controllers','mp_users','mp_jurnal','mp_tasks','mp_harvest','mp_expenses','mp_inventory','mp_queen_history','mp_markers','mp_alerte','mp_apiary_location'] as $t){try{$cnt=(int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();$info['db_tables'][]=['name'=>$t,'rows'=>$cnt];$info['total_db_rows']+=$cnt;}catch(PDOException $e){$info['db_tables'][]=['name'=>$t,'rows'=>null];}}}
    json_ok($info);
}

// ═══════════════════════════════════════════════════════════════
// GRAFIC GREUTATE STUPI
// ═══════════════════════════════════════════════════════════════
if ($action === 'get_weight_chart') {
    $days=min(90,max(1,intval($_GET['days']??30))); $cutoff=time()-($days*86400);
    $pdo=getAdminDB(); if(!$pdo) json_error('DB indisponibil');
    try{
        $chips=$pdo->query("SELECT DISTINCT r.chip_id, COALESCE(m.nickname, CONCAT('Stup ',r.chip_id)) as label FROM mp_hive_readings r LEFT JOIN mp_metadata m ON r.chip_id=m.chip_id WHERE r.ts >= $cutoff AND r.weight IS NOT NULL AND r.weight > 0 ORDER BY label")->fetchAll();
        $datasets=[]; $colors=['#f4a820','#10ac84','#ee5253','#3498db','#9b59b6','#e67e22','#1abc9c','#e74c3c','#f39c12','#2ecc71'];
        foreach($chips as $i=>$chip){
            $rows=$pdo->prepare("SELECT FROM_UNIXTIME(ts,'%Y-%m-%d') as day, ROUND(AVG(weight),3) as avg_w FROM mp_hive_readings WHERE chip_id=? AND ts>=? AND weight IS NOT NULL AND weight>0 GROUP BY day ORDER BY day ASC");
            $rows->execute([$chip['chip_id'],$cutoff]); $pts=$rows->fetchAll();
            $datasets[]=['chip_id'=>$chip['chip_id'],'label'=>$chip['label'],'color'=>$colors[$i%count($colors)],'data'=>array_map(function($p){return['x'=>$p['day'],'y'=>floatval($p['avg_w'])];}, $pts)];
        }
        json_ok(['datasets'=>$datasets,'days'=>$days]);
    }catch(PDOException $e){json_error('DB: '.$e->getMessage());}
}

json_error('Actiune necunoscuta: ' . $action, 404);
