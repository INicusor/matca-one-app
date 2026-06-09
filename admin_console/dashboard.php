<?php
require_once __DIR__ . '/auth.php';
admin_require_auth();
$adminUser = admin_current_user();
$adminRole = admin_current_role();
$adminName = $_SESSION['admin_name'] ?? $adminUser;
$csrf      = csrf_token();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MATCA Admin Console</title>
<link rel="stylesheet" href="assets/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<div id="app">

  <!-- SIDEBAR -->
  <nav id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-hex">M</div>
      <div class="brand-text">
        <div class="brand-title">MATCA</div>
        <div class="brand-sub">Admin Console</div>
      </div>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-section-label">Overview</div>
      <a class="nav-item active" onclick="showView('dashboard',this)">
        <span class="nav-icon">&#9670;</span> Dashboard
      </a>
      <a class="nav-item" onclick="showView('health',this)">
        <span class="nav-icon">&#9829;</span> System Health
        <span class="nav-badge" id="health-badge" style="display:none;background:var(--red)">!</span>
      </a>
      <a class="nav-item" onclick="showView('telemetry',this)">
        <span class="nav-icon">&#128225;</span> Telemetrie Live
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-section-label">Stupina</div>
      <a class="nav-item" onclick="showView('hives',this)">
        <span class="nav-icon">&#11041;</span> Stupi
      </a>
      <a class="nav-item" onclick="showView('controllers',this)">
        <span class="nav-icon">&#9881;</span> Controllers
      </a>
      <a class="nav-item" onclick="showView('queens',this)">
        <span class="nav-icon">&#128081;</span> Matci
      </a>
      <a class="nav-item" onclick="showView('alerts',this)">
        <span class="nav-icon">&#9888;</span> Alerte
        <span class="nav-badge" id="alerts-badge" style="display:none">0</span>
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-section-label">Operatiuni</div>
      <a class="nav-item" onclick="showView('jurnal',this)">
        <span class="nav-icon">&#128203;</span> Jurnal
      </a>
      <a class="nav-item" onclick="showView('harvest',this)">
        <span class="nav-icon">&#127855;</span> Recolta &amp; Cheltuieli
      </a>
      <a class="nav-item" onclick="showView('tasks',this)">
        <span class="nav-icon">&#10003;</span> Sarcini &amp; Tratamente
      </a>
      <a class="nav-item" onclick="showView('inventory',this)">
        <span class="nav-icon">&#128230;</span> Inventar
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-section-label">Administrare</div>
      <a class="nav-item" onclick="showView('users',this)">
        <span class="nav-icon">&#128101;</span> Utilizatori App
      </a>
      <a class="nav-item" onclick="showView('reports',this)">
        <span class="nav-icon">&#128202;</span> Rapoarte Admin
      </a>
      <a class="nav-item" onclick="showView('backup',this)">
        <span class="nav-icon">&#128190;</span> Backup &amp; Export
      </a>
      <a class="nav-item" onclick="showView('database',this)">
        <span class="nav-icon">&#128440;</span> Baza de Date
      </a>
      <a class="nav-item" onclick="showView('audit',this)">
        <span class="nav-icon">&#128220;</span> Audit Log
      </a>
      <?php if ($adminRole === 'superadmin'): ?>
      <a class="nav-item" onclick="showView('history_mgmt',this)">
        <span class="nav-icon">&#128204;</span> Gestionare History
      </a>
      <a class="nav-item" onclick="showView('json_editor',this)">
        <span class="nav-icon">&#123;</span> Editor JSON
      </a>
      <a class="nav-item" onclick="showView('admins',this)">
        <span class="nav-icon">&#128274;</span> Admin Users
      </a>
      <a class="nav-item" onclick="showView('errorlog',this)">
        <span class="nav-icon">&#128030;</span> Error Log
      </a>
      <?php endif; ?>
      <a class="nav-item" onclick="showView('settings',this)">
        <span class="nav-icon">&#9881;</span> Setari
      </a>
    </div>

    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?php echo strtoupper(substr($adminName,0,1)); ?></div>
        <div>
          <div class="user-name"><?php echo htmlspecialchars($adminName); ?></div>
          <div class="user-role"><?php echo htmlspecialchars($adminRole); ?></div>
        </div>
      </div>
      <button class="btn-logout" onclick="doLogout()">Deconectare</button>
    </div>
  </nav>

  <!-- MAIN -->
  <div id="main">
    <div id="header">
      <div id="page-title">Dashboard <span>Overview</span></div>
      <div class="header-actions">
        <div class="header-time" id="clock">--:--:--</div>
        <button class="btn btn-ghost btn-sm" onclick="refreshCurrentView()">&#8635; Refresh</button>
        <a href="../index.php" target="_blank" class="btn btn-ghost btn-sm">&#8599; App</a>
      </div>
    </div>

    <div id="content">

      <!-- VIEW: DASHBOARD -->
      <div class="view active" id="view-dashboard">
        <div class="stat-grid" id="stat-grid">
          <div class="stat-card" style="--accent:var(--green)"><div class="stat-value" id="s-online">-</div><div class="stat-label">Stupi Online</div></div>
          <div class="stat-card" style="--accent:var(--red)"><div class="stat-value" id="s-offline">-</div><div class="stat-label">Stupi Offline</div></div>
          <div class="stat-card" style="--accent:var(--orange)"><div class="stat-value" id="s-alerts">-</div><div class="stat-label">Alerte Active</div></div>
          <div class="stat-card" style="--accent:var(--honey)"><div class="stat-value" id="s-users">-</div><div class="stat-label">Utilizatori</div></div>
          <div class="stat-card" style="--accent:var(--honey)"><div class="stat-value" id="s-kg">-</div><div class="stat-label">Kg Recoltati</div></div>
          <div class="stat-card" style="--accent:var(--green)"><div class="stat-value" id="s-ron">-</div><div class="stat-label">Venit Total RON</div></div>
          <div class="stat-card" style="--accent:var(--blue)"><div class="stat-value" id="s-readings">-</div><div class="stat-label">Citiri Stocate</div></div>
          <div class="stat-card" style="--accent:var(--orange)"><div class="stat-value" id="s-tasks">-</div><div class="stat-label">Sarcini Pending</div></div>
        </div>
        <div class="two-col">
          <div class="card">
            <div class="card-header"><div class="card-title">Activitate Jurnal (7 zile)</div></div>
            <div class="chart-box"><canvas id="activity-chart"></canvas></div>
          </div>
          <div class="card">
            <div class="card-header"><div class="card-title">Status Stupi</div></div>
            <div class="chart-box"><canvas id="status-chart"></canvas></div>
          </div>
        </div>
        <div class="card" style="margin-top:16px">
          <div class="card-header">
            <div class="card-title">&#9878; Evolutie Greutate Stupi</div>
            <div style="display:flex;gap:8px;align-items:center">
              <select id="weight-chart-days" onchange="loadWeightChart()" style="font-size:12px;padding:4px 8px;background:var(--bg-elevated);border:1px solid var(--border);color:var(--text-primary);border-radius:4px">
                <option value="7">7 zile</option>
                <option value="14">14 zile</option>
                <option value="30" selected>30 zile</option>
                <option value="60">60 zile</option>
              </select>
              <button class="btn btn-ghost btn-sm" onclick="loadWeightChart()">&#8635;</button>
            </div>
          </div>
          <div style="height:280px;position:relative"><canvas id="weight-chart"></canvas></div>
          <div id="weight-chart-legend" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;font-size:11px"></div>
        </div>
      </div>

      <!-- VIEW: SYSTEM HEALTH -->
      <div class="view" id="view-health">
        <div class="toolbar">
          <button class="btn btn-primary btn-sm" onclick="loadHealth()">&#8635; Refresh Health Check</button>
          <span id="health-generated" style="font-size:11px;color:var(--text-muted);margin-left:8px"></span>
        </div>
        <div id="health-content">
          <div style="text-align:center;padding:40px;color:var(--text-muted)">Apasa Refresh pentru a rula health check...</div>
        </div>
      </div>

      <!-- VIEW: TELEMETRIE LIVE -->
      <div class="view" id="view-telemetry">
        <div class="toolbar">
          <button class="btn btn-primary btn-sm" onclick="loadTelemetry()">&#8635; Refresh</button>
          <label class="form-check" style="font-size:12px;margin-left:8px">
            <input type="checkbox" id="telemetry-autorefresh" onchange="toggleTelemetryAutoRefresh()"> Auto-refresh 30s
          </label>
          <span id="telemetry-last-update" style="font-size:11px;color:var(--text-muted);margin-left:8px"></span>
        </div>
        <div class="card" style="padding:0">
          <div class="table-wrap scroll-table">
            <table>
              <thead>
                <tr>
                  <th>Status</th><th>Stup</th><th>Chip ID</th>
                  <th>Greutate</th><th>Temp</th><th>Baterie</th>
                  <th>Delta 24h</th><th>WiFi</th><th>Firmware</th><th>Acum</th><th>Actiuni</th>
                </tr>
              </thead>
              <tbody id="telemetry-tbody">
                <tr class="loading-row"><td colspan="11"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- VIEW: STUPI -->
      <div class="view" id="view-hives">
        <div class="toolbar">
          <div class="search-bar">
            <span class="search-icon">&#9906;</span>
            <input type="text" id="hive-search" placeholder="Cauta stup..." oninput="filterHives()">
          </div>
          <select id="hive-filter-status" onchange="filterHives()" style="width:auto">
            <option value="">Toate statusurile</option>
            <option value="online">Online</option>
            <option value="warning">Warning</option>
            <option value="offline">Offline</option>
            <option value="manual">Manual</option>
          </select>
          <button class="btn btn-ghost btn-sm" onclick="loadHives()">&#8635; Refresh</button>
          <button class="btn btn-primary btn-sm" onclick="exportHivesCSV()">&#8595; CSV</button>
        </div>
        <div class="card" style="padding:0">
          <div class="table-wrap scroll-table">
            <table id="hives-table">
              <thead>
                <tr>
                  <th onclick="sortTable('hives','status')">Status</th>
                  <th onclick="sortTable('hives','nickname')">Nume</th>
                  <th onclick="sortTable('hives','chipID')">Chip ID</th>
                  <th onclick="sortTable('hives','weight')">Greutate</th>
                  <th onclick="sortTable('hives','temperature')">Temp</th>
                  <th onclick="sortTable('hives','battery')">Baterie</th>
                  <th onclick="sortTable('hives','delta24')">Delta 24h</th>
                  <th>Controller</th><th>Firmware</th><th>Ultima citire</th><th>Actiuni</th>
                </tr>
              </thead>
              <tbody id="hives-tbody">
                <tr class="loading-row"><td colspan="11"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- VIEW: CONTROLLERS -->
      <div class="view" id="view-controllers">
        <div class="toolbar">
          <button class="btn btn-ghost btn-sm" onclick="loadControllers()">&#8635; Refresh</button>
        </div>
        <div id="controllers-list"></div>
      </div>

      <!-- VIEW: MATCI -->
      <div class="view" id="view-queens">
        <div class="toolbar">
          <select id="queen-filter-hive" onchange="loadQueens()" style="width:auto"><option value="">Toti stupii</option></select>
          <button class="btn btn-primary btn-sm" onclick="openQueenModal()">+ Eveniment Nou</button>
          <button class="btn btn-ghost btn-sm" onclick="loadQueens()">&#8635;</button>
        </div>
        <div class="card" style="padding:0">
          <div class="table-wrap scroll-table">
            <table>
              <thead><tr><th>Data</th><th>Stup</th><th>Eveniment</th><th>Rasa</th><th>An</th><th>Note</th><th>User</th><th>Actiuni</th></tr></thead>
              <tbody id="queens-tbody"><tr class="loading-row"><td colspan="8"><div class="spinner"></div></td></tr></tbody>
            </table>
          </div>
        </div>
        <div class="pagination" id="queens-pagination"></div>
      </div>

      <!-- VIEW: ALERTE -->
      <div class="view" id="view-alerts">
        <div class="toolbar">
          <select id="alert-filter-stup" onchange="loadAlertsFull()" style="width:auto"><option value="">Toti stupii</option></select>
          <button class="btn btn-ghost btn-sm" onclick="loadAlertsFull()">&#8635; Refresh</button>
          <?php if ($adminRole === 'superadmin'): ?>
          <button class="btn btn-danger btn-sm" onclick="deleteAllAlerts()">&#128465; Sterge Tot</button>
          <?php endif; ?>
        </div>
        <div class="card" style="padding:0">
          <div class="table-wrap scroll-table">
            <table>
              <thead><tr><th>Data</th><th>Stup</th><th>Mesaj</th><th>Rezolvata de</th><th>Actiuni</th></tr></thead>
              <tbody id="alerts-tbody"><tr class="loading-row"><td colspan="5"><div class="spinner"></div></td></tr></tbody>
            </table>
          </div>
        </div>
        <div class="pagination" id="alerts-pagination"></div>
      </div>

      <!-- VIEW: JURNAL -->
      <div class="view" id="view-jurnal">
        <div class="toolbar">
          <div class="search-bar"><span class="search-icon">&#9906;</span><input type="text" id="jurnal-search" placeholder="Cauta in jurnal..."></div>
          <select id="jurnal-filter-stup" style="width:auto" onchange="loadJurnal()"><option value="">Toti stupii</option></select>
          <select id="jurnal-filter-user" style="width:auto" onchange="loadJurnal()"><option value="">Toti userii</option></select>
          <select id="jurnal-filter-type" style="width:auto" onchange="loadJurnal()">
            <option value="">Toate tipurile</option>
            <option value="inspectii">Inspectii</option>
            <option value="tratamente">Tratamente</option>
            <option value="sarcini">Sarcini</option>
            <option value="ok">Stup OK</option>
          </select>
          <button class="btn btn-ghost btn-sm" onclick="loadJurnal()">&#8635;</button>
          <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('jurnal_complet')">&#8595; CSV</button>
        </div>
        <div class="card" style="padding:0">
          <div class="table-wrap scroll-table">
            <table><thead><tr><th>Data</th><th>Stup</th><th>User</th><th>Nota</th><th>Foto</th><th>Actiuni</th></tr></thead>
            <tbody id="jurnal-tbody"></tbody></table>
          </div>
        </div>
        <div class="pagination" id="jurnal-pagination"></div>
      </div>

      <!-- VIEW: RECOLTA & CHELTUIELI -->
      <div class="view" id="view-harvest">
        <div class="tabs">
          <button class="tab-btn active" onclick="switchTab('harvest','recolta',this)">Recolta</button>
          <button class="tab-btn" onclick="switchTab('harvest','cheltuieli',this)">Cheltuieli</button>
          <button class="tab-btn" onclick="switchTab('harvest','roi',this)">ROI / Statistici</button>
        </div>
        <div class="tab-panel active" id="harvest-tab-recolta">
          <div class="toolbar">
            <select id="harvest-filter-year" style="width:auto" onchange="loadHarvest()"><option value="">Toti anii</option></select>
            <button class="btn btn-ghost btn-sm" onclick="loadHarvest()">&#8635;</button>
            <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('financiar_complet')">&#8595; CSV Financiar</button>
          </div>
          <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:12px">
            <div class="stat-card"><div class="stat-value" id="h-total-kg">-</div><div class="stat-label">Kg Total</div></div>
            <div class="stat-card"><div class="stat-value" id="h-total-ron">-</div><div class="stat-label">Venit RON</div></div>
            <div class="stat-card"><div class="stat-value" id="h-profit">-</div><div class="stat-label">Profit Net</div></div>
          </div>
          <div class="card" style="padding:0">
            <div class="table-wrap scroll-table">
              <table><thead><tr><th>Data</th><th>Stup</th><th>Tip</th><th>Kg</th><th>Pret/kg</th><th>Valoare</th><th>Actiuni</th></tr></thead>
              <tbody id="harvest-tbody"></tbody></table>
            </div>
          </div>
        </div>
        <div class="tab-panel" id="harvest-tab-cheltuieli">
          <div class="card" style="padding:0">
            <div class="table-wrap scroll-table">
              <table><thead><tr><th>Data</th><th>Stup</th><th>Suma RON</th><th>Descriere</th><th>Actiuni</th></tr></thead>
              <tbody id="expenses-tbody"></tbody></table>
            </div>
          </div>
        </div>
        <div class="tab-panel" id="harvest-tab-roi"><div id="roi-stats"></div></div>
      </div>

      <!-- VIEW: TASKS -->
      <div class="view" id="view-tasks">
        <div class="toolbar">
          <select id="tasks-filter" style="width:auto" onchange="loadTasks()">
            <option value="all">Toate</option><option value="pending">In asteptare</option><option value="done">Finalizate</option>
          </select>
          <select id="tasks-filter-user" style="width:auto" onchange="loadTasks()"><option value="">Toti userii</option></select>
          <button class="btn btn-ghost btn-sm" onclick="loadTasks()">&#8635;</button>
        </div>
        <div class="card" style="padding:0">
          <div class="table-wrap scroll-table">
            <table><thead><tr><th>Status</th><th>Data</th><th>Stup</th><th>User</th><th>Descriere</th><th>Tip</th><th>Actiuni</th></tr></thead>
            <tbody id="tasks-tbody"></tbody></table>
          </div>
        </div>
      </div>

      <!-- VIEW: INVENTAR -->
      <div class="view" id="view-inventory">
        <div class="toolbar">
          <button class="btn btn-primary btn-sm" onclick="openInventoryModal()">+ Adauga Produs</button>
          <button class="btn btn-ghost btn-sm" onclick="loadInventory()">&#8635;</button>
        </div>
        <div class="card" style="padding:0">
          <div class="table-wrap scroll-table">
            <table><thead><tr><th>Produs</th><th>Cantitate</th><th>Unitate</th><th>Categorie</th><th>Actiuni</th></tr></thead>
            <tbody id="inventory-tbody"></tbody></table>
          </div>
        </div>
      </div>

      <!-- VIEW: USERS -->
      <div class="view" id="view-users">
        <div class="toolbar">
          <button class="btn btn-primary btn-sm" onclick="openUserModal()">+ Utilizator Nou</button>
          <button class="btn btn-ghost btn-sm" onclick="loadUsers()">&#8635;</button>
          <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('users_complet')">&#8595; CSV</button>
        </div>
        <div class="card" style="padding:0">
          <div class="table-wrap scroll-table">
            <table><thead><tr><th>Utilizator</th><th>Email</th><th>Rol</th><th>Stupi Alocati</th><th>Permisiuni</th><th>Actiuni</th></tr></thead>
            <tbody id="users-tbody"></tbody></table>
          </div>
        </div>
      </div>

      <!-- VIEW: RAPOARTE ADMIN -->
      <div class="view" id="view-reports">
        <div class="tabs">
          <button class="tab-btn active" onclick="switchTab('reports','activity',this);loadReportActivity()">Activitate Useri</button>
          <button class="tab-btn" onclick="switchTab('reports','performance',this);loadReportHivePerf()">Performanta Stupi</button>
          <button class="tab-btn" onclick="switchTab('reports','growth',this);loadReportGrowth()">Crestere Date</button>
          <button class="tab-btn" onclick="switchTab('reports','monthly',this);loadReportMonthly()">Raport Lunar</button>
          <button class="tab-btn" onclick="switchTab('reports','financial',this);loadReportFinancial()">Raport Financiar</button>
        </div>

        <div class="tab-panel active" id="reports-tab-activity">
          <div class="toolbar" style="margin-top:12px">
            <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('audit_complet')">&#8595; Export Audit CSV</button>
          </div>
          <div class="card" style="padding:0;margin-top:8px">
            <div class="table-wrap scroll-table">
              <table>
                <thead><tr><th>Utilizator</th><th>Intrari Jurnal</th><th>Sarcini Done</th><th>Sarcini Pending</th><th>Ultima Activitate</th></tr></thead>
                <tbody id="report-activity-tbody"><tr class="loading-row"><td colspan="5"><div class="spinner"></div></td></tr></tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="tab-panel" id="reports-tab-performance">
          <div class="toolbar" style="margin-top:12px">
            <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('stupi_complet')">&#8595; Export Stupi CSV</button>
          </div>
          <div class="card" style="padding:0;margin-top:8px">
            <div class="table-wrap scroll-table">
              <table>
                <thead><tr><th>Stup</th><th>Chip ID</th><th>Kg Recoltati</th><th>Venit RON</th><th>Cheltuieli RON</th><th>Profit</th><th>Citiri IoT</th><th>Ultima Inspectie</th></tr></thead>
                <tbody id="report-perf-tbody"><tr class="loading-row"><td colspan="8"><div class="spinner"></div></td></tr></tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="tab-panel" id="reports-tab-growth">
          <div class="card" style="margin-top:12px">
            <div class="card-header"><div class="card-title">Citiri IoT per zi (ultimele 30 zile)</div></div>
            <div style="height:300px;position:relative"><canvas id="growth-chart"></canvas></div>
          </div>
        </div>

        <!-- Tab: Raport Lunar -->
        <div class="tab-panel" id="reports-tab-monthly">
          <div class="toolbar" style="margin-top:12px">
            <select id="report-month-year" style="width:auto" onchange="loadReportMonthly()"></select>
            <select id="report-month-month" style="width:auto" onchange="loadReportMonthly()">
              <option value="1">Ianuarie</option><option value="2">Februarie</option><option value="3">Martie</option>
              <option value="4">Aprilie</option><option value="5">Mai</option><option value="6">Iunie</option>
              <option value="7">Iulie</option><option value="8">August</option><option value="9">Septembrie</option>
              <option value="10">Octombrie</option><option value="11">Noiembrie</option><option value="12">Decembrie</option>
            </select>
            <button class="btn btn-ghost btn-sm" onclick="loadReportMonthly()">&#8635;</button>
          </div>
          <div id="report-monthly-content"></div>
        </div>

        <!-- Tab: Raport Financiar -->
        <div class="tab-panel" id="reports-tab-financial">
          <div class="toolbar" style="margin-top:12px">
            <select id="report-fin-year" style="width:auto" onchange="loadReportFinancial()"></select>
            <button class="btn btn-ghost btn-sm" onclick="loadReportFinancial()">&#8635;</button>
            <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('financiar_complet')">&#8595; Export CSV</button>
          </div>
          <div id="report-financial-content"></div>
        </div>
      </div>

      <!-- VIEW: BACKUP & EXPORT -->
      <div class="view" id="view-backup">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
          <div class="card">
            <div class="card-header"><div class="card-title">&#128190; Backup JSON</div></div>
            <p style="color:var(--text-secondary);font-size:13px;line-height:1.7;margin-bottom:16px">
              Descarca toate fisierele JSON ale aplicatiei intr-o arhiva ZIP.
            </p>
            <button class="btn btn-primary" onclick="downloadBackupJSON()" style="width:100%">&#8595; Descarca ZIP JSON</button>
          </div>
          <div class="card">
            <div class="card-header"><div class="card-title">&#128440; Dump SQL Database</div></div>
            <p style="color:var(--text-secondary);font-size:13px;line-height:1.7;margin-bottom:16px">
              Genereaza un dump SQL complet al tuturor tabelelor <code style="color:var(--honey)">mp_*</code>.
            </p>
            <?php if ($adminRole === 'superadmin'): ?>
            <button class="btn btn-primary" onclick="downloadBackupSQL()" style="width:100%">&#8595; Descarca SQL Dump</button>
            <?php else: ?>
            <div class="alert-banner alert-warning" style="margin:0">Necesita rol superadmin.</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Export Rapoarte CSV -->
        <div class="card" style="margin-bottom:16px">
          <div class="card-header"><div class="card-title">&#128196; Export Rapoarte CSV</div></div>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;padding:4px">
            <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('stupi_complet')" style="justify-content:flex-start">&#8595; Stupi Complet</button>
            <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('jurnal_complet')" style="justify-content:flex-start">&#8595; Jurnal Complet</button>
            <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('financiar_complet')" style="justify-content:flex-start">&#8595; Financiar Complet</button>
            <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('users_complet')" style="justify-content:flex-start">&#8595; Utilizatori</button>
            <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('alerte_complet')" style="justify-content:flex-start">&#8595; Alerte</button>
            <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('audit_complet')" style="justify-content:flex-start">&#8595; Audit Log</button>
          </div>
        </div>

        <!-- Info snapshot -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">&#128202; Snapshot Date Curente</div>
            <button class="btn btn-ghost btn-sm" onclick="loadBackupInfo()">&#8635; Refresh</button>
          </div>
          <div id="backup-info-content">
            <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px">Apasa Refresh pentru a vedea starea curenta...</div>
          </div>
        </div>

        <!-- Export CSV per tabel -->
        <div class="card" style="margin-top:0">
          <div class="card-header"><div class="card-title">&#128196; Export CSV per Tabel DB</div></div>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;padding:4px">
            <?php
            $exportTables = ['mp_jurnal','mp_harvest','mp_expenses','mp_inventory','mp_tasks','mp_hive_readings'];
            foreach ($exportTables as $t):
            ?>
            <button class="btn btn-ghost btn-sm" onclick="exportTableCSV('<?= $t ?>')" style="justify-content:flex-start;gap:8px">
              &#8595; <?= $t ?>
            </button>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($adminRole === 'superadmin'): ?>
        <!-- Sync JSON → DB -->
        <div class="card" style="margin-top:0">
          <div class="card-header"><div class="card-title">&#128260; Sincronizare JSON &#8594; DB</div></div>
          <p style="color:var(--text-secondary);font-size:13px;margin-bottom:12px">Forteaza rescrierea tuturor datelor din fisierele JSON in baza de date MySQL. Foloseste cand suspectezi ca DB si JSON sunt out of sync.</p>
          <button class="btn btn-warning" onclick="syncJsonToDb()">&#128260; Sincronizeaza Acum</button>
          <div id="sync-result" style="margin-top:12px"></div>
        </div>
        <?php endif; ?>

        <!-- Test Email -->
        <div class="card" style="margin-top:0">
          <div class="card-header"><div class="card-title">&#128231; Test Email</div></div>
          <div style="display:flex;gap:8px;align-items:center">
            <input type="email" id="test-email-to" placeholder="adresa@email.com" style="flex:1">
            <button class="btn btn-primary" onclick="sendTestEmail()">Trimite Test</button>
          </div>
        </div>
      </div>

      <!-- VIEW: DATABASE -->
      <div class="view" id="view-database">
        <div class="tabs">
          <button class="tab-btn active" onclick="switchTab('db','browser',this)">Browser Tabele</button>
          <button class="tab-btn" onclick="switchTab('db','sql',this)">SQL Console</button>
          <button class="tab-btn" onclick="switchTab('db','stats',this); loadDbStats()">Statistici DB</button>
        </div>
        <div class="tab-panel active" id="db-tab-browser">
          <div class="db-table-list" id="db-table-list"></div>
          <div class="toolbar" id="db-browser-toolbar" style="display:none">
            <div class="search-bar"><span class="search-icon">&#9906;</span><input type="text" id="db-search" placeholder="Cauta..."></div>
            <button class="btn btn-ghost btn-sm" onclick="browseTable()">Cauta</button>
            <button class="btn btn-ghost btn-sm" onclick="exportCurrentTable()">&#8595; CSV</button>
          </div>
          <div class="card" style="padding:0;display:none" id="db-table-card">
            <div class="table-wrap scroll-table" id="db-table-result"></div>
          </div>
          <div class="pagination" id="db-pagination"></div>
        </div>
        <div class="tab-panel" id="db-tab-sql">
          <?php if ($adminRole === 'superadmin'): ?>
          <div class="card">
            <div class="card-header"><div class="card-title">SQL Console</div></div>
            <div class="alert-banner alert-warning">Atentie: DROP TABLE, TRUNCATE, ALTER TABLE sunt blocate automat.</div>
            <div class="form-group">
              <textarea class="code-editor" id="sql-input" rows="6" placeholder="SELECT * FROM mp_jurnal LIMIT 10;"></textarea>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
              <button class="btn btn-primary" onclick="runSQL()">&#9654; Ruleaza</button>
              <span id="sql-status" style="font-size:12px;color:var(--text-muted)"></span>
            </div>
            <div id="sql-result" style="margin-top:16px"></div>
          </div>
          <?php else: ?>
          <div class="alert-banner alert-warning">SQL Console necesita rol superadmin.</div>
          <?php endif; ?>
        </div>
        <div class="tab-panel" id="db-tab-stats">
          <div class="db-table-list" id="db-stats-grid"></div>
          <div id="db-size-info" style="margin-top:12px;color:var(--text-muted);font-size:13px"></div>
        </div>
      </div>

      <!-- VIEW: AUDIT LOG -->
      <div class="view" id="view-audit">
        <div class="toolbar">
          <div class="search-bar"><span class="search-icon">&#9906;</span><input type="text" id="audit-filter-action" placeholder="Filtreaza actiune..."></div>
          <button class="btn btn-ghost btn-sm" onclick="loadAudit()">&#8635;</button>
          <button class="btn btn-ghost btn-sm" onclick="exportReportCSV('audit_complet')">&#8595; CSV</button>
          <?php if ($adminRole === 'superadmin'): ?>
          <button class="btn btn-danger btn-sm" onclick="clearAuditLog()">Sterge Log</button>
          <?php endif; ?>
        </div>
        <div class="card" style="padding:0">
          <div class="table-wrap scroll-table">
            <table><thead><tr><th>Data / Ora</th><th>Admin</th><th>Actiune</th><th>Detaliu</th><th>IP</th></tr></thead>
            <tbody id="audit-tbody"></tbody></table>
          </div>
        </div>
        <div class="pagination" id="audit-pagination"></div>
      </div>

      <!-- VIEW: GESTIONARE HISTORY -->
      <?php if ($adminRole === 'superadmin'): ?>
      <div class="view" id="view-history_mgmt">
        <div class="toolbar">
          <button class="btn btn-ghost btn-sm" onclick="loadHistoryFiles()">&#8635; Refresh</button>
          <span style="font-size:12px;color:var(--text-muted)" id="history-summary"></span>
        </div>
        <div class="card" style="padding:0">
          <div class="table-wrap scroll-table">
            <table>
              <thead><tr><th>Chip ID</th><th>Stup</th><th>Citiri</th><th>Marime</th><th>Prima</th><th>Ultima</th><th>Actiuni</th></tr></thead>
              <tbody id="history-tbody"><tr class="loading-row"><td colspan="7"><div class="spinner"></div></td></tr></tbody>
            </table>
          </div>
        </div>
        <!-- Delete readings range -->
        <div class="card" style="margin-top:16px">
          <div class="card-header"><div class="card-title">&#128465; Sterge Citiri per Interval</div></div>
          <div class="form-grid form-grid-2">
            <div class="form-group"><label>Stup (Chip ID)</label><input type="text" id="del-range-chipid" placeholder="ex: 123456"></div>
            <div class="form-group"><label>&nbsp;</label></div>
            <div class="form-group"><label>Data De La</label><input type="date" id="del-range-from"></div>
            <div class="form-group"><label>Data Pana La</label><input type="date" id="del-range-to"></div>
          </div>
          <button class="btn btn-danger" onclick="deleteReadingsRange()">&#128465; Sterge Citiri din Interval</button>
        </div>
      </div>

      <!-- VIEW: EDITOR JSON -->
      <div class="view" id="view-json_editor">
        <div class="toolbar">
          <select id="json-file-select" style="width:auto" onchange="loadJsonFile()">
            <option value="">-- Selecteaza fisier --</option>
            <option value="metadata.json">metadata.json</option>
            <option value="controllers.json">controllers.json</option>
            <option value="user.json">user.json</option>
            <option value="manual_hives.json">manual_hives.json</option>
            <option value="manifest.json">manifest.json</option>
          </select>
          <span id="json-file-info" style="font-size:11px;color:var(--text-muted);margin-left:8px"></span>
        </div>
        <div class="card" id="json-editor-card" style="display:none">
          <div class="card-header">
            <div class="card-title" id="json-editor-title">Editor JSON</div>
            <div style="display:flex;gap:8px">
              <button class="btn btn-ghost btn-sm" onclick="formatJsonEditor()">&#9632; Formateaza</button>
              <button class="btn btn-primary btn-sm" onclick="saveJsonFile()">&#128190; Salveaza</button>
            </div>
          </div>
          <div id="json-validation-msg" style="margin-bottom:8px"></div>
          <textarea id="json-editor-content" class="code-editor" rows="25" oninput="validateJsonEditor()"></textarea>
          <div style="margin-top:8px;font-size:11px;color:var(--text-muted)">Un backup automat se creeaza la fiecare salvare.</div>
        </div>
      </div>

      <!-- VIEW: ERROR LOG -->
      <div class="view" id="view-errorlog">
        <div class="toolbar">
          <button class="btn btn-ghost btn-sm" onclick="loadErrorLog()">&#8635; Refresh</button>
          <select id="errorlog-lines" style="width:auto" onchange="loadErrorLog()">
            <option value="50">Ultimele 50</option>
            <option value="100" selected>Ultimele 100</option>
            <option value="200">Ultimele 200</option>
            <option value="500">Ultimele 500</option>
          </select>
          <button class="btn btn-danger btn-sm" onclick="clearErrorLog()">&#128465; Sterge Log</button>
        </div>
        <div class="card" style="padding:0">
          <div id="errorlog-info" style="padding:8px 16px;font-size:11px;color:var(--text-muted);border-bottom:1px solid var(--border)"></div>
          <div id="errorlog-content" style="padding:12px;font-family:monospace;font-size:11px;max-height:600px;overflow-y:auto;background:var(--bg-base)">
            <div style="text-align:center;padding:20px;color:var(--text-muted)">Apasa Refresh pentru a incarca log-ul...</div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- VIEW: ADMIN USERS -->
      <div class="view" id="view-admins">
        <div class="toolbar">
          <button class="btn btn-primary btn-sm" onclick="openAdminUserModal()">+ Admin Nou</button>
          <button class="btn btn-ghost btn-sm" onclick="loadAdminUsers()">&#8635;</button>
          <?php if ($adminRole === 'superadmin'): ?>
          <button class="btn btn-warning btn-sm" onclick="showImpersonatePanel()">&#128100; Impersoneaza User</button>
          <?php endif; ?>
        </div>
        <div class="card" style="padding:0">
          <div class="table-wrap">
            <table><thead><tr><th>Username</th><th>Nume</th><th>Email</th><th>Rol</th><th>Creat</th><th>Actiuni</th></tr></thead>
            <tbody id="admins-tbody"></tbody></table>
          </div>
        </div>
        <?php if ($adminRole === 'superadmin'): ?>
        <div class="card" id="impersonate-panel" style="margin-top:16px;display:none">
          <div class="card-header"><div class="card-title">&#128100; Impersoneaza User App</div></div>
          <p style="color:var(--text-secondary);font-size:13px;margin-bottom:12px">Selecteaza un user din aplicatie pentru a te loga ca el (fara sa-i cunosti parola). Se va deschide aplicatia intr-un tab nou.</p>
          <div style="display:flex;gap:8px;align-items:center">
            <select id="impersonate-user-select" style="flex:1">
              <option value="">-- Selecteaza user --</option>
            </select>
            <button class="btn btn-warning" onclick="doImpersonate()">&#128100; Acceseaza ca User</button>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- VIEW: SETARI -->
      <div class="view" id="view-settings">
        <div class="two-col">
          <div class="card">
            <div class="card-header"><div class="card-title">Schimba Parola</div></div>
            <div class="form-group"><label>Parola Curenta</label><input type="password" id="old-pass"></div>
            <div class="form-group"><label>Parola Noua</label><input type="password" id="new-pass"></div>
            <div class="form-group"><label>Confirma Parola Noua</label><input type="password" id="new-pass2"></div>
            <button class="btn btn-primary" onclick="changePassword()">Salveaza</button>
          </div>
          <div class="card">
            <div class="card-header"><div class="card-title">Info Sesiune</div></div>
            <p style="color:var(--text-secondary);font-size:13px;line-height:2">
              Logat ca: <strong><?php echo htmlspecialchars($adminName); ?></strong><br>
              Rol: <strong><?php echo htmlspecialchars($adminRole); ?></strong><br>
              Sesiunea expira dupa 8 ore de inactivitate.
            </p>
            <div class="divider"></div>
            <a href="../index.php" target="_blank" class="btn btn-ghost" style="width:100%;justify-content:center">Deschide Aplicatia</a>
          </div>
        </div>
      </div>

    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /app -->

<!-- MODALS -->

<!-- Hive Modal -->
<div class="modal-overlay" id="hive-modal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title" id="hive-modal-title">Editeaza Stup</div>
      <button class="btn-close" onclick="closeModal('hive-modal')">&#10005;</button>
    </div>
    <div class="form-grid form-grid-2">
      <div class="form-group"><label>Nickname</label><input type="text" id="hm-nickname"></div>
      <div class="form-group"><label>Culoare Matca</label><input type="color" id="hm-qcolor" style="height:40px;padding:4px"></div>
      <div class="form-group"><label>An Matca</label><input type="text" id="hm-qyear" placeholder="ex: 24"></div>
      <div class="form-group"><label>Rasa Matca</label><input type="text" id="hm-qbreed" placeholder="ex: Carpatica"></div>
      <div class="form-group"><label>Nr. Magazii</label><input type="number" id="hm-supers" min="0" max="5" value="0"></div>
      <div class="form-group"><label>Weight Ref (kg)</label><input type="number" id="hm-weightref" step="0.001" placeholder="0.000"></div>
      <div class="form-group"><label>Latitudine</label><input type="number" id="hm-lat" step="0.000001"></div>
      <div class="form-group"><label>Longitudine</label><input type="number" id="hm-lng" step="0.000001"></div>
      <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:6px">
        <label class="form-check"><input type="checkbox" id="hm-maintenance"> Mod Mentenanta Activ</label>
      </div>
    </div>
    <div class="form-group"><label>Muta la Controller</label>
      <select id="hm-controller"><option value="">-- Nu muta --</option></select>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('hive-modal')">Anuleaza</button>
      <button class="btn btn-warning btn-sm" onclick="saveWeightRef()">&#9878; Reset WeightRef</button>
      <button class="btn btn-primary" onclick="saveHiveMeta()">Salveaza</button>
    </div>
    <input type="hidden" id="hm-chipid">
  </div>
</div>

<!-- Queen Modal -->
<div class="modal-overlay" id="queen-modal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title" id="queen-modal-title">Eveniment Matca</div>
      <button class="btn-close" onclick="closeModal('queen-modal')">&#10005;</button>
    </div>
    <div class="form-grid form-grid-2">
      <div class="form-group"><label>Stup (Chip ID) *</label>
        <select id="qm-chipid"><option value="">-- Selecteaza stup --</option></select>
      </div>
      <div class="form-group"><label>Eveniment *</label>
        <select id="qm-event">
          <option>Inregistrare</option><option>Inlocuire</option><option>Pierduta</option>
          <option>Roire</option><option>Tratament</option><option>Verificare</option><option>Altele</option>
        </select>
      </div>
      <div class="form-group"><label>Rasa</label><input type="text" id="qm-breed" placeholder="ex: Carpatica, Buckfast"></div>
      <div class="form-group"><label>An nastere</label><input type="text" id="qm-year" placeholder="ex: 2025"></div>
      <div class="form-group"><label>Data</label><input type="text" id="qm-date" placeholder="dd.mm.yyyy hh:mm"></div>
    </div>
    <div class="form-group"><label>Note</label><textarea id="qm-notes" rows="3" placeholder="Observatii..."></textarea></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('queen-modal')">Anuleaza</button>
      <button class="btn btn-primary" onclick="saveQueen()">Salveaza</button>
    </div>
    <input type="hidden" id="qm-id">
  </div>
</div>

<!-- User Modal -->
<div class="modal-overlay" id="user-modal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title" id="user-modal-title">Utilizator Nou</div>
      <button class="btn-close" onclick="closeModal('user-modal')">&#10005;</button>
    </div>
    <div class="form-grid form-grid-2">
      <div class="form-group"><label>Username *</label><input type="text" id="um-username"></div>
      <div class="form-group"><label>Email</label><input type="email" id="um-email"></div>
      <div class="form-group"><label>Parola</label><input type="password" id="um-password" placeholder="Lasati gol pentru a nu schimba"></div>
      <div class="form-group" style="display:flex;flex-direction:column;gap:10px;justify-content:center">
        <label class="form-check"><input type="checkbox" id="um-is-admin"> Este Admin App</label>
        <label class="form-check"><input type="checkbox" id="um-can-manual"> Gestioneaza stupi manuali</label>
      </div>
    </div>
    <div class="form-group"><label>Stupi Alocati</label>
      <div id="um-hives-checkboxes" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px;max-height:180px;overflow-y:auto;background:var(--bg-base);padding:10px;border-radius:6px;border:1px solid var(--border)"></div>
    </div>
    <div class="form-group"><label>Controllers Alocati</label>
      <div id="um-controllers-checkboxes" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px;background:var(--bg-base);padding:10px;border-radius:6px;border:1px solid var(--border)"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('user-modal')">Anuleaza</button>
      <button class="btn btn-primary" onclick="saveUser()">Salveaza</button>
    </div>
    <input type="hidden" id="um-original-username">
  </div>
</div>

<!-- Inventory Modal -->
<div class="modal-overlay" id="inventory-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Produs Inventar</div>
      <button class="btn-close" onclick="closeModal('inventory-modal')">&#10005;</button>
    </div>
    <div class="form-group"><label>Produs *</label><input type="text" id="inv-item" placeholder="ex: Varachet Forte"></div>
    <div class="form-grid form-grid-2">
      <div class="form-group"><label>Cantitate</label><input type="number" id="inv-qty" min="0" step="0.5" value="1"></div>
      <div class="form-group"><label>Unitate</label>
        <select id="inv-type"><option>Bucati</option><option>Litri</option><option>kg</option><option>ml</option><option>g</option><option>pachete</option></select>
      </div>
    </div>
    <div class="form-group"><label>Categorie</label>
      <select id="inv-category"><option>Tratamente&amp;Hrana</option><option>Echipamente</option><option>Rame&amp;Faguri</option><option>Ambalaje</option><option>Altele</option></select>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('inventory-modal')">Anuleaza</button>
      <button class="btn btn-primary" onclick="saveInventoryItem()">Salveaza</button>
    </div>
    <input type="hidden" id="inv-id">
  </div>
</div>

<!-- Admin User Modal -->
<div class="modal-overlay" id="admin-user-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Admin Nou</div>
      <button class="btn-close" onclick="closeModal('admin-user-modal')">&#10005;</button>
    </div>
    <div class="form-group"><label>Username *</label><input type="text" id="au-username"></div>
    <div class="form-group"><label>Nume Afisat</label><input type="text" id="au-name"></div>
    <div class="form-group"><label>Email</label><input type="email" id="au-email"></div>
    <div class="form-group"><label>Parola *</label><input type="password" id="au-password"></div>
    <div class="form-group"><label>Rol</label>
      <select id="au-role"><option value="admin">Admin</option><option value="superadmin">Superadmin</option></select>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('admin-user-modal')">Anuleaza</button>
      <button class="btn btn-primary" onclick="saveAdminUser()">Creeaza</button>
    </div>
  </div>
</div>

<!-- Confirm Modal -->
<div class="modal-overlay" id="confirm-modal">
  <div class="modal" style="max-width:400px;text-align:center">
    <div style="font-size:36px;margin-bottom:12px" id="confirm-icon">&#9888;</div>
    <div class="modal-title" style="margin-bottom:8px" id="confirm-title">Confirmare</div>
    <div style="color:var(--text-secondary);font-size:13px;margin-bottom:20px" id="confirm-text">Esti sigur?</div>
    <div style="display:flex;justify-content:center;gap:10px">
      <button class="btn btn-ghost" onclick="closeModal('confirm-modal')">Anuleaza</button>
      <button class="btn btn-danger" id="confirm-ok-btn">Confirma</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>

<script>
window.CSRF      = '<?php echo $csrf; ?>';
window.ADMIN_ROLE = '<?php echo $adminRole; ?>';
</script>
<script src="assets/app.js"></script>
</body>
</html>
