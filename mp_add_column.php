<?php
$SECRET = 'matca_migrate_2026';
if (($_GET['secret'] ?? '') !== $SECRET) { http_response_code(403); die('Acces interzis.'); }

define('DB_HOST',    'localhost');
define('DB_NAME',    'danc_MatcaDB');
define('DB_USER',    'danc_matcaapp');
define('DB_PASS',    '^gcq?&,~B)K6-tZC');
define('DB_CHARSET', 'utf8mb4');

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Verifică dacă coloana există deja
    $cols = $pdo->query("SHOW COLUMNS FROM `mp_hive_metadata` LIKE 'super_frames'")->fetchAll();
    
    if (count($cols) > 0) {
        $msg = '✅ Coloana <code>super_frames</code> există deja — totul e în regulă!';
        $ok  = true;
    } else {
        $pdo->exec("ALTER TABLE `mp_hive_metadata` ADD COLUMN `super_frames` TINYINT DEFAULT 0 COMMENT 'Rame per magazie' AFTER `supers`");
        $msg = '✅ Coloana <code>super_frames</code> a fost adăugată cu succes!';
        $ok  = true;
    }
} catch (PDOException $e) {
    $msg = '❌ Eroare: ' . htmlspecialchars($e->getMessage());
    $ok  = false;
}
?><!DOCTYPE html>
<html lang="ro">
<head><meta charset="UTF-8"><title>Add Column</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#fdf6ec;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.box{background:#fff;border-radius:12px;padding:32px 40px;text-align:center;border:1px solid #e8ddd0;max-width:480px;box-shadow:0 2px 12px rgba(0,0,0,.06)}
h2{color:#5d4037;margin-bottom:16px}
.msg{font-size:1.1rem;line-height:1.7;color:#333}
.warn{margin-top:20px;font-size:.8rem;color:#999;border-top:1px solid #f0e8d8;padding-top:12px}
</style>
</head>
<body>
<div class="box">
  <h2>🐝 MatcaDB — Add Column</h2>
  <div class="msg"><?= $msg ?></div>
  <div class="warn">⚠️ Șterge acest fișier după execuție!</div>
</div>
</body>
</html>
