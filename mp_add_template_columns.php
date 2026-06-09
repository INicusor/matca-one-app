<?php
$SECRET = 'matca_migrate_2026';
if (($_GET['secret'] ?? '') !== $SECRET) { http_response_code(403); die('Acces interzis.'); }

define('DB_HOST',    'localhost');
define('DB_NAME',    'danc_MatcaDB');
define('DB_USER',    'danc_matcaapp');
define('DB_PASS',    '^gcq?&,~B)K6-tZC');
define('DB_CHARSET', 'utf8mb4');

header('Content-Type: text/html; charset=utf-8');

$results = [];

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $columns = [
        'hive_template'  => "VARCHAR(32)  DEFAULT 'dadant10' COMMENT 'Template corp stup'       AFTER `super_frames`",
        'body_kg'        => "DECIMAL(6,2) DEFAULT 16.00      COMMENT 'Greutate corp gol kg'     AFTER `hive_template`",
        'super_kg'       => "DECIMAL(6,2) DEFAULT 4.00       COMMENT 'Greutate magazie goala'   AFTER `body_kg`",
        'super_frame_kg' => "DECIMAL(6,3) DEFAULT 0.280      COMMENT 'Greutate rama foita kg'   AFTER `super_kg`",
    ];

    foreach ($columns as $col => $def) {
        $exists = $pdo->query("SHOW COLUMNS FROM `mp_hive_metadata` LIKE '$col'")->fetchAll();
        if (count($exists) > 0) {
            $results[] = ['col'=>$col, 'ok'=>true, 'msg'=>'Există deja'];
        } else {
            $pdo->exec("ALTER TABLE `mp_hive_metadata` ADD COLUMN `$col` $def");
            $results[] = ['col'=>$col, 'ok'=>true, 'msg'=>'Adăugată cu succes'];
        }
    }
} catch (PDOException $e) {
    $results[] = ['col'=>'ERROR', 'ok'=>false, 'msg'=>$e->getMessage()];
}
?><!DOCTYPE html>
<html lang="ro">
<head><meta charset="UTF-8"><title>Add Template Columns</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#fdf6ec;padding:28px;color:#333}
h1{color:#5d4037;margin-bottom:20px}
.row{background:#fff;border-radius:8px;border:1px solid #e8ddd0;padding:11px 16px;
     margin:7px 0;display:flex;justify-content:space-between;align-items:center;font-size:.88rem}
.row.ok {border-left:4px solid #2e7d32}
.row.err{border-left:4px solid #c62828;background:#ffebee}
.ok {color:#2e7d32;font-weight:700}
.err{color:#c62828;font-weight:700}
code{background:#f5ede0;padding:2px 6px;border-radius:4px;font-size:.82rem}
.warn{background:#fff3e0;border:1px solid #ffe0b2;border-left:4px solid #f57c00;
      border-radius:8px;padding:12px 16px;margin-top:16px;font-size:.83rem;color:#5d4037}
</style>
</head>
<body>
<h1>🐝 MatcaDB — Adăugare Coloane Template</h1>
<?php foreach ($results as $r): ?>
<div class="row <?= $r['ok'] ? 'ok' : 'err' ?>">
  <span><code><?= htmlspecialchars($r['col']) ?></code></span>
  <span class="<?= $r['ok'] ? 'ok' : 'err' ?>"><?= $r['ok'] ? '✅' : '❌' ?> <?= htmlspecialchars($r['msg']) ?></span>
</div>
<?php endforeach; ?>
<div class="warn">⚠️ Șterge acest fișier după execuție!</div>
</body>
</html>
