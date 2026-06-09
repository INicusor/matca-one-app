<?php
/**
 * MATCA — Deploy Script
 * Acceseaza acest fisier o singura data din browser pentru a face git pull pe server
 * URL: https://soul2soul.ro/MPDashboard/deploy.php?token=SCHIMBA_TOKENUL
 * 
 * IMPORTANT: Schimba DEPLOY_TOKEN cu o parola unica!
 * IMPORTANT: Sterge sau redenumeste acest fisier dupa deploy!
 */

define('DEPLOY_TOKEN', 'SCHIMBA_CU_TOKEN_SECRET');
define('REPO_PATH',    __DIR__);
define('GIT_BRANCH',  'main');

// Verificare token
$token = $_GET['token'] ?? '';
if (!hash_equals(DEPLOY_TOKEN, $token)) {
    http_response_code(403);
    die('Access denied.');
}

// Verifica ca git e disponibil
exec('which git 2>&1', $gitPath, $gitCode);
if ($gitCode !== 0) {
    // Incearca calea standard cPanel
    $git = '/usr/bin/git';
} else {
    $git = trim($gitPath[0]);
}

// Executa git pull
$commands = [
    "cd " . escapeshellarg(REPO_PATH) . " && {$git} fetch origin " . GIT_BRANCH . " 2>&1",
    "cd " . escapeshellarg(REPO_PATH) . " && {$git} reset --hard origin/" . GIT_BRANCH . " 2>&1",
];

$output  = [];
$success = true;

foreach ($commands as $cmd) {
    exec($cmd, $cmdOutput, $exitCode);
    $output[] = [
        'cmd'    => $cmd,
        'output' => implode("\n", $cmdOutput),
        'code'   => $exitCode,
    ];
    if ($exitCode !== 0) {
        $success = false;
    }
    $cmdOutput = [];
}

// Afiseaza rezultatul
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>MATCA Deploy</title>
  <style>
    body { font-family: monospace; background: #1a1a2e; color: #eee; padding: 30px; }
    h1   { color: <?= $success ? '#4ade80' : '#f87171' ?>; }
    pre  { background: #0f0f23; padding: 15px; border-radius: 8px; overflow-x: auto; border-left: 4px solid <?= $success ? '#4ade80' : '#f87171' ?>; }
    .ok  { color: #4ade80; }
    .err { color: #f87171; }
  </style>
</head>
<body>
  <h1><?= $success ? '✅ Deploy reusit!' : '❌ Deploy esuat!' ?></h1>
  <p>Branch: <strong><?= GIT_BRANCH ?></strong> | Data: <strong><?= date('d.m.Y H:i:s') ?></strong></p>

  <?php foreach ($output as $item): ?>
    <p><strong>$ <?= htmlspecialchars($item['cmd']) ?></strong></p>
    <pre class="<?= $item['code'] === 0 ? 'ok' : 'err' ?>"><?= htmlspecialchars($item['output']) ?></pre>
  <?php endforeach; ?>

  <p style="opacity:0.5;margin-top:30px;">⚠️ Sterge sau protejeaza deploy.php dupa folosire!</p>
</body>
</html>
