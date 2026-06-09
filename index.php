<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'backend.php';

if (!isset($_SESSION['authenticated'])) {
    require_once 'login.php';
    exit;
}

// Citim corect tokenul generat de noul backend
$csrfToken = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Cpolygon points='20,2 35,10.5 35,27.5 20,36 5,27.5 5,10.5' fill='%23c8860a'/%3E%3Cpolygon points='20,6 31,12.5 31,25.5 20,32 9,25.5 9,12.5' fill='%23e8a020'/%3E%3Cellipse cx='20' cy='22' rx='6' ry='8' fill='%23f5c518'/%3E%3C/svg%3E">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
  <title>Matca — Management Apicol</title>

  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#c8860a">
  <meta name="description" content="Matca — Aplicație de management apicol">
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="icon-192.png">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Nunito:wght@400;600;700;800;900&family=Roboto+Mono:wght@500;700&display=swap" rel="stylesheet">

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <link  rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <link rel="stylesheet" href="assets/style.css">
</head>

<body>

  <div id="offline-banner" data-i18n="offline_msg">✈️ Ești offline. Datele se salvează în telefon.</div>

  <div class="top-left-clock" id="live-clock">--.--.---- 00:00:00</div>

  <div class="user-menu-container">
    <div class="user-avatar-btn" onclick="document.getElementById('user-dd').classList.toggle('show')" title="Meniu cont">
      👤
    </div>
    <div class="user-dropdown" id="user-dd">
      <span style="font-weight:800;color:var(--premium-brown);">Salut, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
      <hr style="width:100%;border:0;border-top:1px solid rgba(209,180,144,0.3);margin:0;">
      <div class="weather-box" id="weather-ui">🌤️ --°C</div>
      <div class="mode-toggle" onclick="toggleNight()" id="suit-night-btn">🌙 Mod Noapte</div>
      <div class="mode-toggle" id="suit-toggle-btn" onclick="toggleSuitMode()" style="color:var(--accent-orange);font-weight:800;">🐝 Mod Costum</div>
      <div class="mode-toggle" onclick="openSettings()" style="color:var(--premium-brown);font-weight:800;">⚙️ Setări</div>
      <a href="#" onclick="doLogout()" class="logout-btn-top">🚪 Ieșire Cont</a>
    </div>
  </div>

  <div id="pollen-container"></div>
  <div id="health-tip"></div>

  <div id="weather-alert">⚠️ Condiții meteo nefavorabile pentru inspecție.</div>

  <?php
$currentU    = $_SESSION['username'] ?? '';
$usersForNav = json_decode(file_get_contents(__DIR__ . '/user.json'), true) ?: [];
$isAdminUser = ($currentU === 'admin' || !empty($usersForNav[$currentU]['is_admin']));
?>
<nav id="main-nav" role="navigation" aria-label="Navigare principală">
    <button class="nav-btn active" onclick="showPage('view-dashboard', this)">🏠 Dashboard</button>
    <?php if ($isAdminUser): ?>
    <button class="nav-btn" onclick="showPage('view-map', this)">🗺️ Hartă</button>
    <?php endif; ?>
    <button class="nav-btn" onclick="showPage('view-compare', this)">📊 Comparație</button>
    <button class="nav-btn" onclick="showPage('view-table', this)" id="nav-btn-table">📋 Inspecție</button>
    <button class="nav-btn" onclick="showPage('view-jurnal', this)">📔 Jurnal</button>
    <button class="nav-btn" onclick="showPage('view-harvest', this)">🍯 Recoltă & ROI</button>
    <button class="nav-btn" onclick="showPage('view-inventory', this)">📦 Gestiune</button>
    <?php if ($isAdminUser): ?>
    <button class="nav-btn" onclick="showPage('view-admin', this)">⚙️ Admin</button>
    <?php endif; ?>
    <button class="nav-btn" onclick="showPage('view-help', this)">❓ Ajutor</button>
  </nav>
  <nav id="bottom-nav" role="navigation" aria-label="Navigare mobilă">
    <div class="bnav-inner">
      <button class="bnav-btn active" onclick="showPage('view-dashboard', this, true)" data-page="view-dashboard">
        <span class="bnav-icon">🏠</span>
        <span>Acasă</span>
        <span class="bnav-dot"></span>
      </button>
      <button class="bnav-btn" onclick="showPage('view-jurnal', this, true)" data-page="view-jurnal">
        <span class="bnav-icon">📔</span>
        <span>Jurnal</span>
        <span class="bnav-dot"></span>
      </button>
      
      <button class="bnav-btn bnav-center-btn" onclick="showPage('view-table', this, true)" data-page="view-table">
        <span class="bnav-icon">📋</span>
      </button>
      
      <?php if ($isAdminUser): ?>
      <button class="bnav-btn" onclick="showPage('view-map', this, true)" data-page="view-map">
          <span class="bnav-icon">🗺️</span>
          <span>Hartă</span>
          <span class="bnav-dot"></span>
      </button>
      <?php endif; ?>

      <button class="bnav-btn" onclick="document.getElementById('mobile-more-menu').style.display='flex'">
        <span class="bnav-icon">☰</span>
        <span>Meniu</span>
        <span class="bnav-dot"></span>
      </button>
    </div>
  </nav>

  <div id="mobile-more-menu" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; flex-direction:column; justify-content:flex-end; backdrop-filter:blur(4px);">
      <div style="background:var(--cream); border-radius:24px 24px 0 0; padding:20px; animation:sheetUp 0.3s ease; box-shadow:0 -10px 30px rgba(0,0,0,0.1);">
          <div style="width:40px; height:5px; background:var(--wood-light); border-radius:5px; margin:0 auto 20px;"></div>
          
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
              <button class="btn-secondary" onclick="showPage('view-harvest', null, true); document.getElementById('mobile-more-menu').style.display='none'" style="text-align:left; padding:16px;">🍯 Recoltă & ROI</button>
              <button class="btn-secondary" onclick="showPage('view-inventory', null, true); document.getElementById('mobile-more-menu').style.display='none'" style="text-align:left; padding:16px;">📦 Gestiune</button>
              <button class="btn-secondary" onclick="showPage('view-compare', null, true); document.getElementById('mobile-more-menu').style.display='none'" style="text-align:left; padding:16px;">📊 Comparație</button>
              <button class="btn-secondary" onclick="showPage('view-help', null, true); document.getElementById('mobile-more-menu').style.display='none'" style="text-align:left; padding:16px;">❓ Ajutor</button>
              
              <?php if ($isAdminUser): ?>
              <button class="btn-secondary" onclick="showPage('view-admin', null, true); document.getElementById('mobile-more-menu').style.display='none'" style="text-align:left; padding:16px; grid-column: span 2; border-color:var(--accent-red); color:var(--accent-red);">⚙️ Panou Admin</button>
              <?php endif; ?>
          </div>
          <button class="btn-primary" onclick="document.getElementById('mobile-more-menu').style.display='none'" style="width:100%; margin-top:20px; background:var(--wood-mid);">Închide</button>
      </div>
  </div>

  <button class="save-all-btn" id="global-save-suit" onclick="saveAllInspections()" title="Salvează toate inspecțiile">💾</button>

  <h1><svg width="42" height="42" viewBox="0 0 40 40" style="vertical-align:middle;margin-right:8px;" xmlns="http://www.w3.org/2000/svg"><polygon points="20,2 35,10.5 35,27.5 20,36 5,27.5 5,10.5" fill="#c8860a"/><polygon points="20,6 31,12.5 31,25.5 20,32 9,25.5 9,12.5" fill="#e8a020"/><ellipse cx="20" cy="22" rx="6" ry="8" fill="#f5c518"/><rect x="14" y="19" width="12" height="2.5" rx="1" fill="#1a1a1a" opacity="0.85"/><rect x="14" y="23" width="12" height="2.5" rx="1" fill="#1a1a1a" opacity="0.85"/><circle cx="20" cy="13" r="4" fill="#f5c518"/><ellipse cx="13" cy="19" rx="5" ry="3" fill="rgba(200,230,255,0.75)" transform="rotate(-25 13 19)"/><ellipse cx="27" cy="19" rx="5" ry="3" fill="rgba(200,230,255,0.75)" transform="rotate(25 27 19)"/></svg> Matca</h1>

  <?php
    require 'views/dashboard.php';
    require 'views/map.php';
    require 'views/compare.php';
    require 'views/table.php';
    require 'views/jurnal.php';
    require 'views/harvest.php';
    require 'views/inventory.php';
    require 'views/admin.php';
    require 'views/help.php';
    require 'views/modals.php';
  ?>

  <script>
    window.isAdmin    = <?= ($isAdminUser ?? false) ? 'true' : 'false' ?>;
    window.csrfToken  = <?= json_encode($csrfToken) ?>;
    window.currentUser = <?= json_encode($_SESSION['username']) ?>;
    <?php
      $userProfile = json_decode(file_get_contents(__DIR__ . '/user.json'), true) ?: [];
      $uname = $_SESSION['username'] ?? '';
      $apiaryLat = isset($userProfile[$uname]['apiary_lat']) ? floatval($userProfile[$uname]['apiary_lat']) : null;
      $apiaryLon = isset($userProfile[$uname]['apiary_lon']) ? floatval($userProfile[$uname]['apiary_lon']) : null;
    ?>
    window.apiaryLat = <?= json_encode($apiaryLat) ?>;
    window.apiaryLon = <?= json_encode($apiaryLon) ?>;
  </script>

  <script src="assets/app.js"></script>
</body>
</html>