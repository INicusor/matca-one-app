/* ============================================
   MIEREA POFTA — app.js v2.0
   CSRF integrat, bottom nav sync,
   smartFetch cu token automat
   ============================================ */

/* ── STATE GLOBAL ── */
const chipIDtoName    = {};
let currentChipID     = null;
let currentChipName   = null;
let currentTab        = 'W';
let currentRange      = 1;
let chartObj          = null;
let compareChartObj   = null;
let activeBees        = [];
let currQCol          = 'transparent';
let resolvedAlertIDs  = [];
const _roiResolvedInSession = {
    _k: 'mp_roi_ts',
    _d() { try { return JSON.parse(sessionStorage.getItem(this._k)||'{}'); } catch(e) { return {}; } },
    has(id)      { return this._d()[id] !== undefined; },
    add(id, ts)  { const d=this._d(); d[id]=ts||0; sessionStorage.setItem(this._k,JSON.stringify(d)); },
    delete(id)   { const d=this._d(); delete d[id]; sessionStorage.setItem(this._k,JSON.stringify(d)); },
    getTs(id)    { return this._d()[id] || 0; }
};
let hivesDataLocal    = [];
let jurnalDataLocal   = [];
let harvestPieModalObj = null;
let harvestDataLocal   = [];
let expensesDataLocal  = [];
let _selectedYear      = new Date().getFullYear();
let isDragging        = false;
let dragOccurred      = false;
let markersLocal      = [];
let frameStates       = ['f-gol', 'f-miere', 'f-polen', 'f-puiet', 'f-ceara'];
let currentFrames     = Array(10).fill('f-gol');
let userPermissions   = { isAdmin: false, canManageManual: false };
let suitModeActive    = false;
let suitData          = {};
let _calendarYear = new Date().getFullYear();
let _calendarMonth = new Date().getMonth();

/* ════════════════════════════════════════
   SISTEM MULTILINGV (RO / EN)
   ════════════════════════════════════════ */
const TRANSLATIONS = {
    ro: {
        // Nav
        nav_dashboard: '🏠 Dashboard', nav_map: '🗺️ Hartă', nav_compare: '📊 Comparație',
        nav_table: '📋 Inspecție', nav_jurnal: '📔 Jurnal', nav_harvest: '🍯 Recoltă & ROI',
        nav_inventory: '📦 Gestiune', nav_admin: '⚙️ Admin', nav_help: '❓ Ajutor',
        // Bottom nav
        bnav_home: 'Acasă', bnav_jurnal: 'Jurnal', bnav_map: 'Hartă', bnav_harvest: 'Recoltă',
        // Dashboard KPI
        kpi_hives: 'Stupi', kpi_total_kg: 'kg Total', kpi_avg_kg: 'kg Medie', kpi_alerts: 'Alerte',
        kpi_no_alerts: '✅', kpi_has_alerts: '🚨',
        // Sort
        sort_name: '📝 Nume', sort_weight: '⚖️ Greutate', sort_health: '🏥 Sănătate', sort_updated: '🕒 Actualizat',
        // Hive card
        hive_maintenance: 'Mentenanță', hive_manual: 'M', hive_alarm: '🚨 ALARMĂ ROIRE',
        hive_now: 'Acum câteva sec.', hive_min: 'Acum {n} min', hive_ore: 'Acum {n} ore',
        // Buttons
        btn_save: 'Salvează', btn_cancel: 'Anulează', btn_delete: 'Șterge', btn_close: 'Închide',
        btn_add: 'Adaugă', btn_edit: 'Editează', btn_resolve: 'Rezolvă', btn_export: 'Exportă',
        // Toast & mesaje
        toast_saved: 'Salvat cu succes!', toast_deleted: 'Șters!', toast_error: 'A apărut o eroare!',
        toast_offline: 'Offline — se salvează local.', toast_synced: 'Date sincronizate!',
        toast_treatment_saved: 'Schema de tratament generată! Verifică sarcinile.',
        toast_note_saved: 'Notă salvată!', toast_task_saved: 'Sarcină adăugată!',
        toast_harvest_saved: 'Recoltă înregistrată!', toast_expense_saved: 'Cheltuială salvată!',
        toast_pass_changed: 'Parola schimbată cu succes!',
        toast_pass_wrong: 'Parola actuală este incorectă!',
        toast_pass_short: 'Parola nouă trebuie să aibă cel puțin 6 caractere!',
        toast_pass_mismatch: 'Parolele noi nu coincid!',
        toast_select_hive: 'Selectează mai întâi un stup!',
        toast_select_treatment: 'Selectează un tratament din listă!',
        toast_select_period: 'Selectează perioada pentru raport!',
        toast_compare_two: 'Selectează doi stupi pentru comparație!',
        toast_no_data: 'Unul din stupi nu are date de comparație!',
        toast_manual_only: 'Comparația nu e disponibilă pentru stupi manuali!',
        toast_fill_amount: 'Introdu cantitatea!',
        toast_fill_sum: 'Completează stupul și suma!',
        toast_fill_user: 'Completează numele de utilizator!',
        toast_pass_required: 'Parola este obligatorie pentru un cont nou!',
        // Confirm dialogs
        confirm_delete_note: 'Ștergi nota?',
        confirm_delete_hive: 'Ești sigur că vrei să ștergi definitiv acest stup?',
        confirm_reset_map: 'Ești sigur? Această acțiune va aduce toți stupii în centrul hărții.',
        confirm_delete_user: "Ștergi definitiv contul '{u}'? Această acțiune nu poate fi anulată.",
        confirm_delete_marker: 'Dorești să ștergi acest element de pe hartă?',
        confirm_delete_harvest: 'Ștergi?',
        confirm_delete_expense: 'Ștergi această cheltuială?',
        confirm_delete_task: 'Ștergi?',
        confirm_resolve_alert: 'Marchezi ca rezolvat?',
        confirm_resolve_task: 'Rezolvi task-ul?',
        confirm_reset_pass: "Trimite o parolă temporară pe emailul lui {u}?",
        // Modal stup
        modal_tab_graph: '📈 Grafic', modal_tab_inspec: '🔍 Inspecție',
        modal_tab_meta: '👑 Regină', modal_tab_photo: '📸 Poze',
        modal_tab_harvest: '🍯 Recoltă', modal_tab_logs: '📔 Jurnal', modal_tab_queen: '🕐 Istoric',
        modal_maintenance: '🛠️ Mod Mentenanță (Oprește alarmele de greutate)',
        // Inspecție rapidă
        quick_queen_seen: 'Văz', quick_queen_not: 'Nev', quick_yes: 'DA', quick_no: 'NU',
        quick_no_cells: 'Fără', quick_swarm: 'Roire', quick_save: 'Salv',
        // Alerte
        alert_battery: '🔋 Baterie descărcată ({v}V)',
        alert_swarm: '🚨 POSIBILĂ ROIRE / PIERDERE (Scădere: {v} kg)',
        alert_none: '✅ Totul e sub control. Nicio alertă activă.',
        alert_urgent: 'URGENT: AZI!', alert_delayed: 'ÎNTÂRZIAT!', alert_in: 'Peste {n} zile',
        // Admin
        adm_new_user: 'Utilizator Nou', adm_edit_user: 'Editare: {u}',
        adm_create: '💾 Creează Contul', adm_update: '💾 Salvează Modificările',
        // Jurnal
        jurnal_search: '🔎 Caută în note...', jurnal_no_notes: 'Nicio notă găsită.',
        jurnal_load_more: '⬇️ Mai mult ({n} rămase)',
        // Hartă
        map_reset: '🔄 Resetează Pozițiile (Aduce la centru)',
        // Meteo
        weather_good: 'Bun pentru inspecție', weather_bad: 'Condiții nefavorabile',
        // Offline
        offline_msg: '✈️ Mod Avion. Funcționează offline.',
        offline_queue: '✈️ Mod Avion. {n} acțiuni nesincronizate.',
        offline_sync: '🔄 Se sincronizează {n} acțiuni...',
        // Change password
        cp_title: '🔑 Schimbă Parola', cp_old: 'Parola actuală',
        cp_new: 'Parola nouă (min. 6 caractere)', cp_confirm: 'Confirmă parola nouă',
        cp_save: '💾 Salvează', cp_fill: 'Completează toate câmpurile!',
        // Session
        session_warning: '⏰ Sesiunea expiră în 5 minute!',
        session_expired: 'Sesiunea a expirat. Vei fi deconectat.',
        // Stup manual
        add_manual_name: 'Scrie Numele sau Porecla stupului:',
        add_manual_weight: 'Greutatea estimată (kg):',
        add_manual_temp: 'Temperatura estimată (°C):',
    },
    en: {
        nav_dashboard: '🏠 Dashboard', nav_map: '🗺️ Map', nav_compare: '📊 Compare',
        nav_table: '📋 Inspection', nav_jurnal: '📔 Journal', nav_harvest: '🍯 Harvest & ROI',
        nav_inventory: '📦 Inventory', nav_admin: '⚙️ Admin', nav_help: '❓ Help',
        bnav_home: 'Home', bnav_jurnal: 'Journal', bnav_map: 'Map', bnav_harvest: 'Harvest',
        kpi_hives: 'Hives', kpi_total_kg: 'kg Total', kpi_avg_kg: 'kg Average', kpi_alerts: 'Alerts',
        kpi_no_alerts: '✅', kpi_has_alerts: '🚨',
        sort_name: '📝 Name', sort_weight: '⚖️ Weight', sort_health: '🏥 Health', sort_updated: '🕒 Updated',
        hive_maintenance: 'Maintenance', hive_manual: 'M', hive_alarm: '🚨 SWARM ALERT',
        hive_now: 'Just now', hive_min: '{n} min ago', hive_ore: '{n} hours ago',
        btn_save: 'Save', btn_cancel: 'Cancel', btn_delete: 'Delete', btn_close: 'Close',
        btn_add: 'Add', btn_edit: 'Edit', btn_resolve: 'Resolve', btn_export: 'Export',
        toast_saved: 'Saved successfully!', toast_deleted: 'Deleted!', toast_error: 'An error occurred!',
        toast_offline: 'Offline — saving locally.', toast_synced: 'Data synchronized!',
        toast_treatment_saved: 'Treatment schedule created! Check your tasks.',
        toast_note_saved: 'Note saved!', toast_task_saved: 'Task added!',
        toast_harvest_saved: 'Harvest recorded!', toast_expense_saved: 'Expense saved!',
        toast_pass_changed: 'Password changed successfully!',
        toast_pass_wrong: 'Current password is incorrect!',
        toast_pass_short: 'New password must be at least 6 characters!',
        toast_pass_mismatch: 'New passwords do not match!',
        toast_select_hive: 'Please select a hive first!',
        toast_select_treatment: 'Please select a treatment from the list!',
        toast_select_period: 'Please select a period for the report!',
        toast_compare_two: 'Please select two hives to compare!',
        toast_no_data: 'One of the hives has no comparison data!',
        toast_manual_only: 'Comparison is not available for manual hives!',
        toast_fill_amount: 'Please enter the quantity!',
        toast_fill_sum: 'Please fill in the hive and amount!',
        toast_fill_user: 'Please fill in the username!',
        toast_pass_required: 'Password is required for a new account!',
        confirm_delete_note: 'Delete this note?',
        confirm_delete_hive: 'Are you sure you want to permanently delete this hive?',
        confirm_reset_map: 'Are you sure? This will bring all hives to the center of the map.',
        confirm_delete_user: "Permanently delete account '{u}'? This cannot be undone.",
        confirm_delete_marker: 'Delete this map element?',
        confirm_delete_harvest: 'Delete?',
        confirm_delete_expense: 'Delete this expense?',
        confirm_delete_task: 'Delete?',
        confirm_resolve_alert: 'Mark as resolved?',
        confirm_resolve_task: 'Mark task as done?',
        confirm_reset_pass: "Send a temporary password to {u}'s email?",
        modal_tab_graph: '📈 Graph', modal_tab_inspec: '🔍 Inspection',
        modal_tab_meta: '👑 Queen', modal_tab_photo: '📸 Photos',
        modal_tab_harvest: '🍯 Harvest', modal_tab_logs: '📔 Journal', modal_tab_queen: '🕐 History',
        modal_maintenance: '🛠️ Maintenance Mode (Disables weight alerts)',
        quick_queen_seen: 'Seen', quick_queen_not: 'N/S', quick_yes: 'YES', quick_no: 'NO',
        quick_no_cells: 'None', quick_swarm: 'Swarm', quick_save: 'Save',
        alert_battery: '🔋 Low battery ({v}V)',
        alert_swarm: '🚨 POSSIBLE SWARM / LOSS (Drop: {v} kg)',
        alert_none: '✅ All clear. No active alerts.',
        alert_urgent: 'URGENT: TODAY!', alert_delayed: 'OVERDUE!', alert_in: 'In {n} days',
        adm_new_user: 'New User', adm_edit_user: 'Edit: {u}',
        adm_create: '💾 Create Account', adm_update: '💾 Save Changes',
        jurnal_search: '🔎 Search notes...', jurnal_no_notes: 'No notes found.',
        jurnal_load_more: '⬇️ Load more ({n} remaining)',
        map_reset: '🔄 Reset Positions (Center all)',
        weather_good: 'Good for inspection', weather_bad: 'Unfavorable conditions',
        offline_msg: '✈️ Offline. Working locally.',
        offline_queue: '✈️ Offline. {n} actions pending.',
        offline_sync: '🔄 Syncing {n} actions...',
        cp_title: '🔑 Change Password', cp_old: 'Current password',
        cp_new: 'New password (min. 6 chars)', cp_confirm: 'Confirm new password',
        cp_save: '💾 Save', cp_fill: 'Please fill in all fields!',
        session_warning: '⏰ Session expires in 5 minutes!',
        session_expired: 'Session expired. You will be logged out.',
        add_manual_name: 'Enter hive name or nickname:',
        add_manual_weight: 'Estimated weight (kg):',
        add_manual_temp: 'Estimated temperature (°C):',
    }
};

let _lang = localStorage.getItem('appLang') || 'ro';

function t(key, vars = {}) {
    const dict = TRANSLATIONS[_lang] || TRANSLATIONS.ro;
    let str = dict[key] || TRANSLATIONS.ro[key] || key;
    Object.entries(vars).forEach(([k, v]) => { str = str.replace(`{${k}}`, v); });
    return str;
}

function setLang(lang) {
    _lang = lang;
    localStorage.setItem('appLang', lang);

    // Ștergem elementele create o singură dată ca să se recreeze traduse
    const sortBar = document.getElementById('dashboard-sort-bar');
    if (sortBar) sortBar.remove();
    const kpiWrap = document.getElementById('dashboard-kpi');
    if (kpiWrap) kpiWrap.remove();

    applyLang();
    // Re-randează elementele dinamice
    renderDashboard();
    renderJurnal();
    renderTasks();
    fetchWeather();
    updateOfflineBanner();
    toast(lang === 'en' ? '🇬🇧 English activated' : '🇷🇴 Română activată', 'info');
}

function applyLang() {
    const ro = _lang === 'ro';

    // ── NAV DESKTOP ──
    const navMap = {
        'view-dashboard': 'nav_dashboard', 'view-map': 'nav_map',
        'view-compare': 'nav_compare', 'view-table': 'nav_table',
        'view-jurnal': 'nav_jurnal', 'view-harvest': 'nav_harvest',
        'view-inventory': 'nav_inventory', 'view-admin': 'nav_admin', 'view-help': 'nav_help'
    };
    document.querySelectorAll('#main-nav .nav-btn').forEach(btn => {
        const m = (btn.getAttribute('onclick')||'').match(/showPage\('([^']+)'/);
        if (m && navMap[m[1]]) btn.textContent = t(navMap[m[1]]);
    });

    // ── BOTTOM NAV ──
    const bnavMap = { 'view-dashboard':'bnav_home','view-jurnal':'bnav_jurnal','view-map':'bnav_map','view-harvest':'bnav_harvest' };
    document.querySelectorAll('#bottom-nav .bnav-btn[data-page]').forEach(btn => {
        const span = btn.querySelector('span:not(.bnav-icon):not(.bnav-dot)');
        if (span && bnavMap[btn.dataset.page]) span.textContent = t(bnavMap[btn.dataset.page]);
    });

    // ── SORT BUTTONS ──
    const sortMap = { 'sort-name':'sort_name','sort-weight':'sort_weight','sort-health':'sort_health','sort-updated':'sort_updated' };
    Object.entries(sortMap).forEach(([id,key]) => { const el=document.getElementById(id); if(el) el.textContent=t(key); });

    // ── PLACEHOLDERS ──
    const ph = {
        'j-search':         t('jurnal_search'),
        'dashboard-search': ro ? '🔎 Caută stup (nume sau ID)...' : '🔎 Search hive (name or ID)...',
        'i-item':           ro ? 'Nume Produs / Articol' : 'Product / Item name',
        'j-text':           ro ? 'Notează observațiile...' : 'Write your observations...',
        'adm-user':         ro ? 'ex: apicultor_ion' : 'e.g. beekeeper_john',
        'adm-pass':         ro ? 'Minim 6 caractere' : 'Min. 6 characters',
        'adm-email':        ro ? 'apicultor@email.ro' : 'beekeeper@email.com',
    };
    Object.entries(ph).forEach(([id,val]) => { const el=document.getElementById(id); if(el&&el.placeholder!==undefined) el.placeholder=val; });

    // ── BUTOANE CU TEXT STATIC ──
    const btns = [
        ['button[onclick="resetMapPositions()"]',         t('map_reset')],
        ['button[onclick="enableNotifications()"]',        ro?'Activați Notificările Web Push':'Enable Web Push Notifications'],
        ['button[onclick="openResolvedAlerts()"]',         ro?'📜 Istoric Rezolvări':'📜 Resolved History'],
        ['[data-csv-btn="jurnal"]',                        ro?'📥 Jurnal CSV':'📥 Journal CSV'],
        ['[data-csv-btn="harvest"]',                       ro?'🍯 Recoltă CSV':'🍯 Harvest CSV'],
        ['[data-csv-btn="expenses"]',                      ro?'💸 Cheltuieli CSV':'💸 Expenses CSV'],
        ['[data-csv-btn="inventory"]',                     ro?'📦 Inventar CSV':'📦 Inventory CSV'],
        ['button[onclick="saveNoteFromJurnal()"]',         ro?'Salvează':'Save'],
        ['button[onclick="saveTask()"]',                   ro?'Adaugă Sarcină':'Add Task'],
        ['button[onclick="scheduleTreatment()"]',          ro?'Programează Schema Automat':'Schedule Automatically'],
        ['button[onclick="compareHives()"]',               ro?'Compară Greutatea (Ultimele 7 Zile)':'Compare Weight (Last 7 Days)'],
        ['button[onclick="saveAllInspections()"]',         ro?'💾 Salvează Toate Inspecțiile':'💾 Save All Inspections'],
        ['button[onclick="saveHarvest()"]',                ro?'Înregistrează Recoltă':'Record Harvest'],
        ['button[onclick="saveExpense()"]',                ro?'Salvează Cheltuiala':'Save Expense'],
        ['button[onclick="saveInventory()"]',              ro?'Adaugă în Stoc':'Add to Stock'],
        ['button[onclick="admOpenAddUser()"]',             ro?'+ Utilizator Nou':'+ New User'],
        ['button[onclick="sendAllReportsOnEmail()"]',      ro?'📧 Trimite Rapoarte':'📧 Send Reports'],
    ];
    btns.forEach(([sel, txt]) => { const el = document.querySelector(sel); if(el) el.textContent = txt; });

    // ── LABELS ──
    const labels = {
        'adm-pass-hint': ro ? 'Lasă gol pentru a păstra parola existentă' : 'Leave empty to keep existing password',
    };
    Object.entries(labels).forEach(([id,val]) => { const el=document.getElementById(id); if(el) el.textContent=val; });

    // ── RAPOARTE ADMIN h4 + p ──
    const repMap = [
        ['rep_financial', 'rep_financial_desc'],
        ['rep_health',    'rep_health_desc'],
        ['rep_harvest',   'rep_harvest_desc'],
        ['rep_inventory', 'rep_inventory_desc'],
        ['rep_ansvsa',    'rep_ansvsa_desc'],
        ['rep_journal',   'rep_journal_desc'],
    ];
    document.querySelectorAll('.adm-report-card').forEach((card, i) => {
        if (repMap[i]) {
            const h4 = card.querySelector('h4');
            const p  = card.querySelector('p');
            if (h4) h4.textContent = t(repMap[i][0]);
            if (p)  p.textContent  = t(repMap[i][1]);
        }
    });

    // ── TITLURI SECȚIUNI h2 ──
    const h2map = {
        'view-jurnal':    ro ? ['📔 Jurnal Inspecții','📝 Sarcini & Tratamente'] : ['📔 Inspection Journal','📝 Tasks & Treatments'],
        'view-harvest':   ro ? ['🍯 Log Recoltare','💸 Cheltuieli & ROI']         : ['🍯 Harvest Log','💸 Expenses & ROI'],
        'view-inventory': ro ? ['📦 Gestiune Stocuri (Inventar)']                 : ['📦 Stock Management (Inventory)'],
        'view-compare':   ro ? ['📊 Comparație Evoluție Stupi']                   : ['📊 Hive Evolution Comparison'],
        'view-map':       ro ? ['🗺️ Așezare Stupi și Topografie']                 : ['🗺️ Hive Layout and Topography'],
        'view-help':      ro ? ['🚨 Alerte Active']                               : ['🚨 Active Alerts'],
        'view-admin':     ro ? ['⚙️ Panou Administrare']                          : ['⚙️ Administration Panel'],
    };
    Object.entries(h2map).forEach(([sectionId, titles]) => {
        const section = document.getElementById(sectionId);
        if (!section) return;
        const h2s = section.querySelectorAll('h2');
        titles.forEach((title, i) => { if (h2s[i]) h2s[i].textContent = title; });
    });

    // ── MODAL STUP — tab labels ──
    const tabLabels = {
        'm-tab-graph':   ro ? '📈 Date'              : '📈 Data',
        'm-tab-inspec':  ro ? '📋 Inspecție'         : '📋 Inspection',
        'm-tab-logs':    ro ? '📜 Jurnal Stup'       : '📜 Hive Journal',
        'm-tab-meta':    ro ? '👑 Management & Regină' : '👑 Management & Queen',
        'm-tab-photo':   ro ? '📸 Foto'              : '📸 Photos',
        'm-tab-harvest': ro ? '🍯 Recoltă'           : '🍯 Harvest',
        'm-tab-queen':   ro ? '🕐 Istoric'           : '🕐 History',
    };
    Object.entries(tabLabels).forEach(([id,label]) => { const el=document.getElementById(id); if(el) el.textContent=label; });

    // ── TITLUL APLICAȚIEI — păstrăm SVG-ul hexagon, nu suprascrie innerHTML ──

    // ── BUTON SALVARE COSTUM (flotant) ──
    const globalSave = document.getElementById('global-save-suit');
    if (globalSave) globalSave.title = ro ? 'Salvează toate inspecțiile' : 'Save all inspections';

    // ── METEO BOX — dacă e gol ──
    const weatherUi = document.getElementById('weather-ui');
    if (weatherUi && weatherUi.innerHTML.trim() === (ro ? '🌤️ --°C' : '🌤️ --°C')) {
        weatherUi.innerHTML = '🌤️ --°C';
    }

    // ── MODUL NOAPTE buton — reflectă starea curentă ──
    updateNightBtn();
    const suitBtn = document.getElementById('suit-toggle-btn');
    if (suitBtn && !suitModeActive) suitBtn.textContent = ro ? '🐝 Mod Costum' : '🐝 Field Mode';

    // ── CHANGE PASSWORD button ──
    const cpBtn = document.querySelector('.mode-toggle[onclick="openChangePassword()"]');
    if (cpBtn) cpBtn.textContent = ro ? '🔑 Schimbă Parola' : '🔑 Change Password';

    // ── CHANGE PASSWORD modal ──
    if (document.getElementById('cp-title')) {
        document.getElementById('cp-title').textContent = t('cp_title');
    }

    // ── OFFLINE BANNER ──
    updateOfflineBanner();

    // ── ELEMENTE CU data-i18n ──
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        const val = t(key);
        if (val && val !== key) el.textContent = val;
    });

    // ── LANG BUTTONS highlight ──
    document.querySelectorAll('.lang-btn').forEach(b => {
        b.style.fontWeight = b.dataset.lang === _lang ? '900' : '400';
        b.style.opacity    = b.dataset.lang === _lang ? '1'   : '0.55';
    });

    // ── DOCUMENT TITLE ──
    document.title = ro ? 'MATCA — Management Apicol' : 'MATCA — Beekeeping Dashboard';
}


/* ════════════════════════════════════════
   TOAST NOTIFICATIONS
   ════════════════════════════════════════ */
function toast(msg, type='success', duration=3500) {
    let box = document.getElementById('toast-container');
    if (!box) {
        box = document.createElement('div');
        box.id = 'toast-container';
        box.style.cssText = 'position:fixed;top:80px;right:16px;z-index:99999;display:flex;flex-direction:column;gap:8px;max-width:320px;pointer-events:none;';
        document.body.appendChild(box);
    }
    const icons  = { success:'✅', error:'❌', warning:'⚠️', info:'ℹ️' };
    const colors = {
        success: 'background:linear-gradient(135deg,#0e8c6e,#10ac84);',
        error:   'background:linear-gradient(135deg,#c0392b,#ee5253);',
        warning: 'background:linear-gradient(135deg,#d68910,#f39c12);',
        info:    'background:linear-gradient(135deg,#2471a3,#3498db);'
    };
    const toastEl = document.createElement('div');
    toastEl.style.cssText = `${colors[type]||colors.info}color:#fff;padding:11px 16px;border-radius:12px;font-family:'Nunito',sans-serif;font-size:0.88rem;font-weight:700;display:flex;align-items:center;gap:8px;pointer-events:auto;box-shadow:0 4px 18px rgba(0,0,0,0.2);transform:translateX(120%);transition:transform 0.3s cubic-bezier(0.16,1,0.3,1),opacity 0.3s;opacity:0;max-width:100%;`;
    toastEl.innerHTML = `<span style="font-size:1rem;flex-shrink:0">${icons[type]||'ℹ️'}</span><span style="flex:1;line-height:1.4;word-break:break-word;">${msg}</span>`;
    box.appendChild(toastEl);
    requestAnimationFrame(() => requestAnimationFrame(() => { toastEl.style.transform='translateX(0)'; toastEl.style.opacity='1'; }));
    setTimeout(() => { toastEl.style.transform='translateX(120%)'; toastEl.style.opacity='0'; setTimeout(()=>toastEl.remove(), 320); }, duration);
}

/* ════════════════════════════════════════
   DEBOUNCE
   ════════════════════════════════════════ */
function debounce(fn, delay) {
    let timer;
    return function(...args) { clearTimeout(timer); timer = setTimeout(()=>fn.apply(this,args), delay); };
}

/* ════════════════════════════════════════
   SESSION TIMEOUT — 2h inactivitate
   ════════════════════════════════════════ */
(function initSessionTimeout() {
    const TIMEOUT_MS = 2 * 60 * 60 * 1000;
    const WARNING_MS = 5 * 60 * 1000;
    let timeoutT, warningT, warnShown = false;
    function reset() {
        clearTimeout(timeoutT); clearTimeout(warningT); warnShown = false;
        const w = document.getElementById('session-warning-banner');
        if (w) w.style.display = 'none';
        warningT = setTimeout(() => {
            if (warnShown) return; warnShown = true;
            let b = document.getElementById('session-warning-banner');
            if (!b) {
                b = document.createElement('div'); b.id = 'session-warning-banner';
                b.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#d68910,#e67e22);color:#fff;padding:12px 20px;border-radius:12px;font-family:"Nunito",sans-serif;font-weight:800;font-size:0.9rem;z-index:99998;box-shadow:0 4px 18px rgba(0,0,0,0.3);align-items:center;gap:12px;white-space:nowrap;display:flex;';
                b.innerHTML = `⏰ ${t('session_warning')} <button onclick="document.getElementById('session-warning-banner').style.display='none'" style="background:rgba(255,255,255,0.25);border:none;color:#fff;padding:4px 10px;border-radius:6px;cursor:pointer;font-weight:800;">OK</button>`;
                document.body.appendChild(b);
            } else { b.style.display = 'flex'; }
        }, TIMEOUT_MS - WARNING_MS);
        timeoutT = setTimeout(() => { toast(t('session_expired'),'warning',4000); setTimeout(doLogout,4200); }, TIMEOUT_MS);
    }
    ['mousemove','keydown','click','touchstart','scroll'].forEach(ev => document.addEventListener(ev, reset, { passive:true }));
    // Pornim după ce pagina s-a încărcat complet
    window.addEventListener('load', reset);
})();

/* ════════════════════════════════════════
   KEYBOARD SHORTCUTS
   ════════════════════════════════════════ */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const hist = document.getElementById('histModal');
        if (hist && hist.style.display !== 'none') { hist.style.display='none'; document.querySelectorAll('.hive-wrapper').forEach(h=>h.classList.remove('opening')); document.querySelectorAll('.flying-bee').forEach(b=>b.remove()); return; }
        const admM = document.getElementById('adm-user-modal');
        if (admM && admM.style.display !== 'none') { admCloseModal(); return; }
        const resM = document.getElementById('resolvedModal');
        if (resM && resM.style.display !== 'none') { resM.style.display='none'; return; }
        const cpM = document.getElementById('change-pass-modal');
        if (cpM && cpM.style.display !== 'none') { cpM.style.display='none'; return; }
    }
    if ((e.ctrlKey||e.metaKey) && e.key==='f') {
        const sec = document.querySelector('.view-section.active');
        if (sec && sec.id==='view-dashboard') { e.preventDefault(); const s=document.getElementById('dashboard-search'); if(s){s.focus();s.select();} }
    }
    if ((e.ctrlKey||e.metaKey) && e.key==='s') {
        if (suitModeActive) { e.preventDefault(); saveAllInspections(); }
    }
});


/* ── CSRF TOKEN (din meta tag injectat de PHP) ── */
function getCsrfToken() {
    return window.csrfToken
        || document.querySelector('meta[name="csrf-token"]')?.content
        || '';
}

/* ── OFFLINE QUEUE ── */
let offlineQueue = JSON.parse(localStorage.getItem('offlineQueue') || '[]');
window.addEventListener('online',  syncOfflineQueue);
window.addEventListener('offline', updateOfflineBanner);

/* ── SW: Ascultă mesaje de sync ── */
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', e => {
        if (e.data?.type === 'SW_SYNC_REQUEST') syncOfflineQueue();
    });
}

/* ════════════════════════════════════════
   FETCH SECURIZAT (cu CSRF + offline fallback)
   ════════════════════════════════════════ */
function smartFetch(fd, successMsg = null) {
    // Injectăm CSRF token în orice FormData POST
    const token = getCsrfToken();
    if (token && !fd.has('csrf_token')) {
        fd.append('csrf_token', token);
    }

    if (!navigator.onLine) {
        const obj = {};
        for (let [key, val] of fd.entries()) {
            if (val instanceof File && val.name) {
                toast("Atenție: Pozele nu pot fi salvate offline.", "warning");
                continue;
            }
            obj[key] = val;
        }
        offlineQueue.push(obj);
        localStorage.setItem('offlineQueue', JSON.stringify(offlineQueue));
        updateOfflineBanner();
        return Promise.resolve({ ok: true, offline: true });
    }

    return fetch('backend.php', { method: 'POST', body: fd })
        .then(r => {
            if (r.status === 403) {
                console.warn('[CSRF] Token invalid — reîncarcă pagina.');
                toast('Sesiunea a expirat. Pagina se va reîncărca.', 'warning');
                setTimeout(() => location.reload(), 2000);
            }
            if (successMsg) console.log(successMsg);
            return r;
        });
}

async function syncOfflineQueue() {
    if (offlineQueue.length === 0) { updateOfflineBanner(); return; }
    const banner = document.getElementById('offline-banner');
    if (banner) banner.innerText = t('offline_sync', { n: offlineQueue.length });
    const token = getCsrfToken();
    const failed = [];
    for (let payload of offlineQueue) {
        try {
            const fd = new FormData();
            for (let key in payload) fd.append(key, payload[key]);
            if (!fd.has('csrf_token') && token) fd.append('csrf_token', token);
            await fetch('backend.php', { method: 'POST', body: fd });
        } catch (e) {
            failed.push(payload); // păstrăm ce n-a putut fi trimis
        }
    }
    offlineQueue = failed;
    if (failed.length === 0) {
        localStorage.removeItem('offlineQueue');
    } else {
        localStorage.setItem('offlineQueue', JSON.stringify(failed));
        toast(`⚠️ ${failed.length} acțiuni nu au putut fi sincronizate.`, 'warning');
    }
    updateOfflineBanner();
    fetchData();
    renderJurnal();
}

function updateOfflineBanner() {
    const banner = document.getElementById('offline-banner');
    if (!banner) return;
    if (!navigator.onLine) {
        banner.style.display = 'block';
        banner.innerText = offlineQueue.length > 0
            ? t('offline_queue', { n: offlineQueue.length })
            : t('offline_msg');
    } else {
        banner.style.display = 'none';
    }
}

/* ════════════════════════════════════════
   CEAS LIVE
   ════════════════════════════════════════ */
function updateLiveClock() {
    const now = new Date();
    const d   = String(now.getDate()).padStart(2, '0');
    const m   = String(now.getMonth() + 1).padStart(2, '0');
    const y   = now.getFullYear();
    const hh  = String(now.getHours()).padStart(2, '0');
    const mm  = String(now.getMinutes()).padStart(2, '0');
    const ss  = String(now.getSeconds()).padStart(2, '0');
    const el  = document.getElementById('live-clock');
    if (el) el.textContent = `${d}.${m}.${y} ${hh}:${mm}:${ss}`;
}

/* ════════════════════════════════════════
   NAVIGARE (Desktop + Mobile sync)
   ════════════════════════════════════════ */
function showPage(id, btn, fromBottomNav = false) {
    // Dezactivează toate secțiunile
    document.querySelectorAll('.view-section').forEach(s => s.classList.remove('active'));

    // Dezactivează butoanele desktop
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));

    // Dezactivează butoanele bottom nav
    document.querySelectorAll('.bnav-btn').forEach(b => b.classList.remove('active'));

    setTimeout(() => {
        const section = document.getElementById(id);
        if (section) section.classList.add('active');

        // Activează butonul care a fost apăsat
        if (btn) btn.classList.add('active');

        // Sincronizare cross-nav: dacă s-a apăsat din desktop, actualizează bottom nav
        if (!fromBottomNav) {
            const bnavBtn = document.querySelector(`#bottom-nav .bnav-btn[data-page="${id}"]`);
            if (bnavBtn) bnavBtn.classList.add('active');
        } else {
            // Dacă s-a apăsat din bottom nav, actualizează nav desktop
            const desktopBtn = document.querySelector(`#main-nav .nav-btn[data-page="${id}"]`);
            if (desktopBtn) desktopBtn.classList.add('active');
        }
    }, 10);

    // Render per secțiune
    if (id === 'view-table')     renderSuitTable();
    if (id === 'view-jurnal')    { renderJurnal(); renderTasks(); }
    if (id === 'view-harvest')   renderHarvest();
    if (id === 'view-compare')   setTimeout(renderYearComparison, 200);
    if (id === 'view-inventory') renderInventory();
    if (id === 'view-admin' && window.isAdmin) { loadAdminUsers(); renderAdminHiveCheckboxes(); }

    // Lazy init hartă Leaflet — prima dată când se navighează la ea
    if (id === 'view-map') {
        setTimeout(() => {
            if (!_leafletMap) {
                const ok = initLeafletMap();
                if (ok) renderLeafletMarkers();
            } else {
                // Leaflet e inițializat dar trebuie să-și recalculeze dimensiunile
                _leafletMap.invalidateSize();
                renderLeafletMarkers();
            }
        }, 150); // mică întârziere ca div-ul să fie vizibil
    }

    // Scroll to top la schimbarea secțiunii
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ════════════════════════════════════════
   MOD NOAPTE & MOD COSTUM
   ════════════════════════════════════════ */
function updateNightBtn() {
    const isNight = document.body.classList.contains('night-mode');
    const ro = _lang !== 'en';
    // Buton din dropdown user
    const nightBtn = document.querySelector('.mode-toggle[onclick="toggleNight()"]');
    if (nightBtn) nightBtn.textContent = isNight
        ? (ro ? '☀️ Mod Zi' : '☀️ Day Mode')
        : (ro ? '🌙 Mod Noapte' : '🌙 Night Mode');
    // Toggle din settings modal
    renderSettingsNightState();
}

function toggleNight() {
    document.body.classList.toggle('night-mode');
    localStorage.setItem('nightMode', document.body.classList.contains('night-mode'));
    updateNightBtn();
}

function toggleSuitMode() {
    suitModeActive = !suitModeActive;
    document.body.classList.toggle('suit-mode-active', suitModeActive);
    const btn = document.getElementById('suit-toggle-btn');
    if (btn) btn.innerText = suitModeActive ? "✅ Mod Tabel Activ" : "🐝 Mod Costum";

    if (suitModeActive) {
        showPage('view-table', document.getElementById('nav-btn-table'));
        const saveBtn = document.getElementById('global-save-suit');
        if (saveBtn) saveBtn.style.display = 'block';
    } else {
        showPage('view-dashboard', document.querySelector('.nav-btn'));
        const saveBtn = document.getElementById('global-save-suit');
        if (saveBtn) saveBtn.style.display = 'none';
    }
}


/* ════════════════════════════════════════
   VALIDARE INPUT-URI NUMERICE
   ════════════════════════════════════════ */
function validateNumeric(val, min, max, fieldName) {
    const n = parseFloat(val);
    if (isNaN(n))       { toast(`${fieldName}: valoare invalidă!`, 'warning'); return false; }
    if (n < min)        { toast(`${fieldName}: minim ${min}!`, 'warning'); return false; }
    if (max && n > max) { toast(`${fieldName}: maxim ${max}!`, 'warning'); return false; }
    return true;
}

/* ════════════════════════════════════════
   VREME — folosește locația stupinei salvată per user pe server
   ════════════════════════════════════════ */
async function fetchWeather() {
    const lat = window.apiaryLat || 44.1885318;
    const lon = window.apiaryLon || 25.0979963;

    try {
        const r = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true&hourly=relativehumidity_2m&forecast_days=1&timezone=Europe%2FBucharest`);
        const d = await r.json();
        const temp     = d.current_weather.temperature;
        const wind     = d.current_weather.windspeed;
        const humidity = d.hourly?.relativehumidity_2m?.[new Date().getHours()] ?? '—';

        const wCode = d.current_weather.weathercode || 0;
        let icon = '☀️';
        if (wCode >= 51 && wCode <= 67)  icon = '🌧️';
        else if (wCode >= 71 && wCode <= 77) icon = '❄️';
        else if (wCode >= 80 && wCode <= 99) icon = '⛈️';
        else if (wCode >= 2 && wCode <= 3)   icon = '⛅';
        else if (wCode === 1)                icon = '🌤️';

        const okForInspect = temp >= 14 && wind <= 25;
        const inspectIcon  = okForInspect ? '✅' : '⛔';
        const inspectTxt   = okForInspect
            ? (_lang==='en' ? 'Good for inspection' : 'Bun pentru inspecție')
            : (_lang==='en' ? 'Unfavorable conditions' : 'Condiții nefavorabile');

        const el = document.getElementById('weather-ui');
        if (el) el.innerHTML = `
            <div style="text-align:left;width:100%">
                <div style="font-size:1.1rem;font-weight:900;color:var(--accent-blue)">${icon} ${Math.round(temp)}°C</div>
                <div style="font-size:0.76rem;color:var(--text-muted);margin-top:2px">💨 ${Math.round(wind)} km/h &nbsp;💧 ${humidity}%</div>
                <div style="font-size:0.76rem;margin-top:4px;font-weight:800;color:${okForInspect?'var(--accent-green)':'var(--accent-red)'}">${inspectIcon} ${inspectTxt}</div>
            </div>`;

        const alertEl = document.getElementById('weather-alert');
        if (alertEl && !okForInspect) {
            const reasons = [];
            if (temp < 14)   reasons.push(_lang==='en' ? `🌡️ Low temperature (${Math.round(temp)}°C, min. 14°C)` : `🌡️ Temperatură scăzută (${Math.round(temp)}°C, min. 14°C)`);
            if (wind > 25)   reasons.push(_lang==='en' ? `💨 Strong wind (${Math.round(wind)} km/h, max. 25 km/h)` : `💨 Vânt puternic (${Math.round(wind)} km/h, max. 25 km/h)`);
            if (wCode >= 51 && wCode <= 67)  reasons.push(_lang==='en' ? '🌧️ Rain / precipitation' : '🌧️ Precipitații / ploaie');
            if (wCode >= 71 && wCode <= 77)  reasons.push(_lang==='en' ? '❄️ Snow / ice' : '❄️ Ninsoare / gheață');
            if (wCode >= 80 && wCode <= 99)  reasons.push(_lang==='en' ? '⛈️ Thunderstorm' : '⛈️ Furtună / grindină');
            alertEl.innerHTML = `⚠️ ${_lang==='en'?'Unfavorable conditions for inspection':'Condiții nefavorabile pentru inspecție'}${reasons.length ? ':<br><small style="font-weight:600;opacity:0.9">' + reasons.join(' &nbsp;|&nbsp; ') + '</small>' : '.'}`;
            alertEl.style.display = 'block';
        } else if (alertEl) {
            alertEl.style.display = 'none';
        }
    } catch (e) {}
}

/* ════════════════════════════════════════
   DROPDOWN-URI STUPI
   ════════════════════════════════════════ */
function populateDropdowns(data) {
    const selects = ['j-stup-sel', 'h-stup', 't-stup-trat', 'comp-sel-1', 'comp-sel-2', 'transfer-target', 'e-stup', 'h-filter-stup'];
    selects.forEach(id => {
        const sel = document.getElementById(id);
        if (!sel) return;
        const currentVal = sel.value;
        if (sel.options.length <= 1 || sel.options.length !== data.length + 1) {
            let html = id === 'h-filter-stup'
                ? '<option value="">Toți Stupii</option>'
                : '<option value="">Alege Stupul...</option>';
            data.forEach(item => {
                const name = item.meta.nickname || chipIDtoName[item.chipID] || item.chipID;
                html += `<option value="${name}" data-id="${item.chipID}">${name}</option>`;
            });
            sel.innerHTML = html;
        }
        if (currentVal) sel.value = currentVal;
    });
}

let _lastHivesHash = '';
let _firstLoad = true;

function updateHives(data) {
    const newHash = JSON.stringify(data.map(d => ({
        id: d.chipID, w: d.weight, t: d.temperature, b: d.battery, d24: d.delta24, ts: d.ts
    })));
    const hasChanged = (newHash !== _lastHivesHash) || _firstLoad;
    _lastHivesHash = newHash;
    _firstLoad = false;

    hivesDataLocal = data;

    // Re-aplică sortarea curentă dacă există
    if (_dashSortKey) {
        hivesDataLocal.sort((a, b) => {
            let va, vb;
            const nameA = String(a.meta?.nickname || chipIDtoName[a.chipID] || a.chipID || '');
            const nameB = String(b.meta?.nickname || chipIDtoName[b.chipID] || b.chipID || '');
            if (_dashSortKey === 'weight')  { va = parseFloat(a.weight)||0; vb = parseFloat(b.weight)||0; }
            else if (_dashSortKey === 'updated') { va = a.ts||0; vb = b.ts||0; }
            else if (_dashSortKey === 'health') {
                va = 100-((!a.isManual&&a.battery<3.4)?20:0)-((!a.isManual&&(a.delta24||0)<=-0.5&&!(a.meta?.maintenance==='true'||a.meta?.maintenance===true))?25:0);
                vb = 100-((!b.isManual&&b.battery<3.4)?20:0)-((!b.isManual&&(b.delta24||0)<=-0.5&&!(b.meta?.maintenance==='true'||b.meta?.maintenance===true))?25:0);
            } else { va = nameA.toLowerCase(); vb = nameB.toLowerCase(); }
            if (va < vb) return -1 * _dashSortDir;
            if (va > vb) return  1 * _dashSortDir;
            return 0;
        });
    }

    populateDropdowns(data);
    if (!isDragging) fetchMarkers();
    renderAdminHiveCheckboxes();
    // Re-randăm dashboard-ul doar dacă datele s-au schimbat
    if (hasChanged) fetchResolvedList(data);
    else checkAlerts(data); // alertele se verifică mereu
    renderSeasonalReminders();
    checkTemperatureAlerts(data);
}

function fetchResolvedList(data) {
    fetch('backend.php?fetch=alerte').then(r => r.json()).then(list => {
        resolvedAlertIDs = [];
        list.forEach(a => {
            if (a.alert_id.endsWith('_roi')) return;
            resolvedAlertIDs.push(a.alert_id);
        });
        renderDashboard();
        renderSuitTable();
        checkAlerts(data);
    });
}

/* ════════════════════════════════════════
   DASHBOARD — RENDER STUPI
   ════════════════════════════════════════ */
/* ════════════════════════════════════════
   REMINDERE SEZONIERE APICOLE
   ════════════════════════════════════════ */
const SEASONAL_REMINDERS = [
    { months:[1],       emoji:'🌡️', msg_ro:'Verifică rezervele pe zile calde (+10°C) — primul zbor de curățire al albinelor.',    msg_en:'Check reserves on warm days (+10°C) — first cleansing flight.' },
    { months:[2,3],     emoji:'🌱', msg_ro:'Verifică rezervele de hrană — primăvara coloniile pot consuma rapid 1-2 kg/zi.',       msg_en:'Check food reserves — colonies consume 1-2 kg/day in spring.' },
    { months:[3],       emoji:'🧪', msg_ro:'Primul tratament Varroa de primăvară cu acid oxalic (în perioadele fără puiet căpăcit).', msg_en:'First spring Varroa treatment with oxalic acid (brood-free periods).' },
    { months:[3,4],     emoji:'🐝', msg_ro:'Pregătește corpurile pentru extindere — adaugă rame dacă e nevoie.',                    msg_en:'Prepare hive bodies for expansion — add frames if needed.' },
    { months:[4],       emoji:'👑', msg_ro:'Verifică vârsta mătcii — matcile de 2+ ani au productivitate cu 30% mai mică. An bun pentru schimb.', msg_en:'Check queen age — queens 2+ years are 30% less productive. Good time to replace.' },
    { months:[4,5],     emoji:'🌸', msg_ro:'Sezon salcâm / rapiță — pregătește magaziile și verifică spațiul.',                    msg_en:'Acacia/rapeseed season — prepare supers and check space.' },
    { months:[5,6],     emoji:'⚠️', msg_ro:'Risc maxim de roire! Verifică botcile și spațiul disponibil săptămânal.',             msg_en:'Maximum swarm risk! Check queen cells and space weekly.' },
    { months:[6,7],     emoji:'🌿', msg_ro:'Sezon tei — pregătește magaziile dacă nu sunt montate.',                               msg_en:'Linden season — prepare supers if not yet mounted.' },
    { months:[7,8],     emoji:'🍯', msg_ro:'Recoltă miere — verifică gradul de căpăcire înainte de extracție (min. 80%).',        msg_en:'Honey harvest — check capping before extraction (min 80%).' },
    { months:[7,8],     emoji:'🪲', msg_ro:'Tratament Varroa obligatoriu după recoltă — cea mai importantă intervenție din an!',   msg_en:'Varroa treatment mandatory after harvest — most important intervention of the year!' },
    { months:[8],       emoji:'🔍', msg_ro:'Verifică prezența botcilor după recoltă — risc roire târzie. Verifică și matca dacă e prezentă.', msg_en:'Check for queen cells after harvest — late swarm risk. Also verify queen presence.' },
    { months:[8,9],     emoji:'🍂', msg_ro:'Pregătește hrana de completare pentru iarnă (sirop 2:1 sau pastă) — min. 15 kg/stup.', msg_en:'Prepare winter feed — 2:1 syrup or candy — min. 15 kg/hive.' },
    { months:[9,10],    emoji:'❄️', msg_ro:'Restrânge urdinișul și montează protecția contra șoarecilor.',                        msg_en:'Reduce entrance and install mouse guard.' },
    { months:[10],      emoji:'🧪', msg_ro:'Tratament final Varroa înainte de iernare — obligatoriu conform ANSVSA (acid oxalic sau Apivar).', msg_en:'Final Varroa treatment before winter — mandatory (oxalic acid or Apivar).' },
    { months:[10,11],   emoji:'🛡️', msg_ro:'Verifică ultimele rezerve înainte de iarnă — min. 15-20 kg per stup.',               msg_en:'Check winter reserves — min. 15-20 kg per hive.' },
    { months:[11,12,1], emoji:'🌨️', msg_ro:'Iarnă: nu deranja coloniile. Verifică vizual o dată pe lună. Ascultă — freamătul ușor = semn bun.', msg_en:'Winter: do not disturb. Visual check monthly. Gentle hum = good sign.' },
];

function getSeasonalReminders() {
    const month = new Date().getMonth() + 1; // 1-12
    return SEASONAL_REMINDERS.filter(r => r.months.includes(month));
}

function renderSeasonalReminders() {
    let el = document.getElementById('seasonal-reminders-banner');
    if (!el) {
        el = document.createElement('div');
        el.id = 'seasonal-reminders-banner';
        el.style.cssText = 'margin:0 auto 14px;max-width:900px;padding:0 20px;';
        // Inserăm după prediction banner sau după KPI
        const pred = document.getElementById('harvest-prediction-banner');
        const kpi  = document.getElementById('dashboard-kpi');
        const ref  = pred || kpi;
        if (ref) ref.parentNode.insertBefore(el, ref.nextSibling);
        else return;
    }

    const reminders = getSeasonalReminders();
    if (!reminders.length) { el.innerHTML = ''; return; }

    // Dismissal pe sesiune
    const dismissed = (() => { try { return JSON.parse(sessionStorage.getItem('dismissed_reminders')||'[]'); } catch(e) { return []; } })();
    const active = reminders.filter((_,i) => !dismissed.includes(i));
    if (!active.length) { el.innerHTML = ''; return; }

    el.innerHTML = `<div style="background:var(--white,#fff);border:1.5px solid var(--wood-light);border-radius:12px;padding:10px 14px;display:flex;flex-direction:column;gap:6px;">
        <div style="font-size:0.72rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px;">📅 Remindere sezoniere</div>
        ${active.map((r,i) => `
        <div style="display:flex;align-items:flex-start;gap:8px;padding:6px 0;border-bottom:1px solid var(--wood-light);last:border:none;">
            <span style="font-size:1.1rem;flex-shrink:0;">${r.emoji}</span>
            <span style="font-size:0.8rem;font-weight:600;color:var(--premium-brown);flex:1;line-height:1.4;">${_lang==='en'?r.msg_en:r.msg_ro}</span>
            <button onclick="dismissReminder(${i})" title="Ascunde" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:0.9rem;flex-shrink:0;padding:0 4px;opacity:0.6;">✕</button>
        </div>`).join('')}
    </div>`;
}

function dismissReminder(idx) {
    try {
        const d = JSON.parse(sessionStorage.getItem('dismissed_reminders')||'[]');
        d.push(idx);
        sessionStorage.setItem('dismissed_reminders', JSON.stringify(d));
    } catch(e) {}
    renderSeasonalReminders();
}

/* ════════════════════════════════════════
   ALERTĂ TEMPERATURĂ INTERNĂ STUP
   Norme bazate pe biologia albinelor:
   - Iarnă (nov-feb): ghemul menține 20-35°C constant; sub 15°C = pericol dezintegrare ghem
   - Primăvară (mar-apr): colonia crește, puietul necesită 34-35°C în zona centrală
   - Sezon activ (mai-aug): zona de cules/magazie poate fi mai rece; zona puiet 32-36°C
   - Toamnă (sep-oct): tranziție spre ghem de iernare
   ════════════════════════════════════════ */
const _TEMP_NORMS = {
    //        [min, max] — temperaturi în zona senzorului (corp stup, nu magazia de sus)
    1:  [15, 36],  // Ian: ghem activ — sub 15°C = ghem dezintegrat/colonie în pericol
    2:  [15, 36],  // Feb: idem
    3:  [18, 36],  // Mar: primăvară timpurie, puiet apare
    4:  [20, 37],  // Apr: extindere rapidă
    5:  [22, 38],  // Mai: sezon activ, cules
    6:  [22, 38],  // Iun: sezon activ, risc supraîncălzire
    7:  [22, 39],  // Iul: vârf căldură — supraîncălzire frecventă
    8:  [20, 38],  // Aug: după recoltă, colonia se restrânge
    9:  [18, 36],  // Sep: tranziție spre iernare
    10: [16, 34],  // Oct: ghem se formează
    11: [15, 35],  // Nov: ghem activ
    12: [15, 36],  // Dec: ghem activ
};
const _tempAlerted = new Set();

function checkTemperatureAlerts(data) {
    const month = new Date().getMonth() + 1;
    const [minN, maxN] = _TEMP_NORMS[month] || [20,36];
    const monthName = new Date().toLocaleString('ro-RO',{month:'long'});

    data.forEach(h => {
        if (h.isManual) return;
        const temp = parseFloat(h.temperature) || 0;
        if (temp <= 0) return;
        const name    = h.meta?.nickname || ('Stup ' + h.chipID);
        const isMaint = h.meta?.maintenance === true || h.meta?.maintenance === 'true';
        if (isMaint) return;

        const keyLow  = h.chipID + '_tL';
        const keyHigh = h.chipID + '_tH';

        if (temp < minN && !_tempAlerted.has(keyLow)) {
            _tempAlerted.add(keyLow);
            toast(`🌡️ ${name}: temp. scăzută (${temp}°C, normal ${minN}-${maxN}°C în ${monthName})`, 'warning');
        }
        if (temp > maxN && !_tempAlerted.has(keyHigh)) {
            _tempAlerted.add(keyHigh);
            toast(`🌡️ ${name}: supraîncălzire (${temp}°C, normal max ${maxN}°C)! Verifică ventilația.`, 'warning');
        }
    });
}

function renderDashboard() {
    const container = document.getElementById('container');
    if (!container) return;
    const q = (document.getElementById('dashboard-search')?.value || '').toLowerCase();
    container.innerHTML = '';

    // ── KPI CARDS ──
    let kpiWrap = document.getElementById('dashboard-kpi');
    if (!kpiWrap) {
        kpiWrap = document.createElement('div');
        kpiWrap.id = 'dashboard-kpi';
        kpiWrap.style.cssText = 'display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;padding:0 20px;margin-bottom:20px;max-width:900px;margin-left:auto;margin-right:auto;';
        container.parentNode.insertBefore(kpiWrap, container);
    }
    // Calculează KPI-uri
    const totalStupi = hivesDataLocal.length;
    const totalHoneyEst = hivesDataLocal.reduce((s, h) => {
        const est = parseFloat(h.honeyEstimate) || 0;
        return s + (est > 0 ? est : 0); // ignorăm valorile negative (stup sub greutatea de referință)
    }, 0);
    let cuAlerta = 0;
    hivesDataLocal.forEach(h => {
        if (!h.isManual) {
            const isMaintH = h.meta.maintenance === true || h.meta.maintenance === 'true';
            const aID_bat = h.chipID + '_bat';
            const aID_roi = h.chipID + '_roi';
            if (h.battery < 3.4 && !resolvedAlertIDs.includes(aID_bat)) cuAlerta++;
            else if ((h.delta24||0) <= -0.15 && !isMaintH && (() => {
                if (!_roiResolvedInSession.has(aID_roi)) return true;
                const s=_roiResolvedInSession.getTs(aID_roi), c=parseInt(h.ts)||0;
                return s>0 && c>s;
            })()) cuAlerta++;
        }
    });
    kpiWrap.innerHTML = `
        <div style="background:var(--white,#fff);border:1.5px solid var(--wood-light);border-radius:var(--r-lg,14px);padding:14px 16px;text-align:center;box-shadow:0 2px 8px rgba(93,64,55,0.06);">
            <div style="font-size:1.6rem">🐝</div>
            <div style="font-size:1.6rem;font-weight:900;color:var(--premium-brown);font-family:'Roboto Mono',monospace;line-height:1.1">${totalStupi}</div>
            <div style="font-size:0.7rem;font-weight:700;color:var(--text-muted,#7f8c8d);text-transform:uppercase;letter-spacing:0.5px;margin-top:3px">${_lang==='en'?(totalStupi===1?'Hive':'Hives'):(totalStupi===1?'Stup':'Stupi')}</div>
        </div>
        <div style="background:var(--white,#fff);border:1.5px solid var(--wood-light);border-radius:var(--r-lg,14px);padding:14px 16px;text-align:center;box-shadow:0 2px 8px rgba(93,64,55,0.06);">
            <div style="font-size:1.6rem">🍯</div>
            <div style="font-size:1.6rem;font-weight:900;color:var(--premium-brown);font-family:'Roboto Mono',monospace;line-height:1.1">${totalHoneyEst.toFixed(1)}</div>
            <div style="font-size:0.7rem;font-weight:700;color:var(--text-muted,#7f8c8d);text-transform:uppercase;letter-spacing:0.5px;margin-top:3px">${_lang==='en'?'kg Est. Honey':'kg Est. Miere'}</div>
        </div>
        <div style="background:var(--white,#fff);border:1.5px solid ${cuAlerta>0?'#ee5253':'var(--wood-light)'};border-radius:var(--r-lg,14px);padding:14px 16px;text-align:center;box-shadow:0 2px 8px rgba(93,64,55,0.06);">
            <div style="font-size:1.6rem">${cuAlerta>0?'🚨':'✅'}</div>
            <div style="font-size:1.6rem;font-weight:900;color:${cuAlerta>0?'var(--accent-red)':'var(--accent-green)'};font-family:'Roboto Mono',monospace;line-height:1.1">${cuAlerta}</div>
            <div style="font-size:0.7rem;font-weight:700;color:var(--text-muted,#7f8c8d);text-transform:uppercase;letter-spacing:0.5px;margin-top:3px">${_lang==='en'?'Alerts':'Alerte'}</div>
        </div>`;

    renderSeasonalReminders();

    // Productivitate per stup din harvestDataLocal
    const _prodByHive = {};
    (harvestDataLocal || []).forEach(h => {
        if (!_prodByHive[h.stup]) _prodByHive[h.stup] = 0;
        _prodByHive[h.stup] += parseFloat(h.kg) || 0;
    });

    hivesDataLocal.forEach(item => {
        const name = item.meta.nickname || chipIDtoName[item.chipID] || item.chipID;
        if (q && !String(name).toLowerCase().includes(q) && !String(item.chipID).toLowerCase().includes(q)) return;

        const isManual  = item.isManual === true;
        const batteryTxt = isManual ? 'N/A' : (item.battery != null ? item.battery.toFixed(1) : '?') + 'V';
        const manualBadge = isManual ? '<span style="font-size:0.62rem;background:#34495e;color:white;padding:2px 6px;border-radius:4px;margin-left:6px;vertical-align:middle;font-weight:bold;">M</span>' : '';
        const isMaint   = item.meta.maintenance === true || item.meta.maintenance === "true";
        const maintIcon = isMaint ? '<div style="position:absolute;top:-15px;left:-15px;background:#e67e22;border-radius:50%;width:30px;height:30px;display:flex;justify-content:center;align-items:center;font-size:1rem;box-shadow:0 2px 5px rgba(0,0,0,0.3);z-index:20;" title="Mentenanță Activă">🛠️</div>' : '';

        // Timp de la ultima actualizare
        const ts = item.lastUpdated || item.ts;
        let timeString = "N/A";
        if (ts) {
            const diff = Math.floor((Date.now() / 1000) - ts);
            if (diff < 60)    timeString = (_lang==='en' ? 'Just now' : 'Acum câteva sec.');
            else if (diff < 3600) {
                const m = Math.floor(diff/60);
                timeString = _lang==='en'
                    ? (m===1 ? '1 minute ago' : `${m} min ago`)
                    : (m===1 ? 'Acum 1 minut' : `Acum ${m} min`);
            } else if (diff < 86400) {
                const h = Math.floor(diff/3600);
                timeString = _lang==='en'
                    ? (h===1 ? '1 hour ago' : `${h} hours ago`)
                    : (h===1 ? 'Acum 1 oră' : `Acum ${h} ore`);
            }
            else {
                const dt = new Date(ts * 1000);
                timeString = dt.toLocaleDateString('ro-RO') + ' ' + dt.getHours().toString().padStart(2,'0') + ':' + dt.getMinutes().toString().padStart(2,'0');
            }
        }

        // Scor sănătate
        let h = 100, tip = [];
        if (!isManual) {
            if (item.battery < 3.4) { h -= 20; tip.push("Baterie descărcată"); }
            // Prag roire: -0.5 kg/24h = alertă normală; -1.5 kg/24h = alertă critică (roire confirmată)
            if (item.delta24 <= -1.5 && !isMaint) { h -= 40; tip.push("Scădere critică greutate — posibilă roire!"); }
            else if (item.delta24 <= -0.5 && !isMaint) { h -= 25; tip.push("Scădere greutate semnificativă"); }
            if (item.battery < 3.2 || (item.delta24 <= -1.5 && !isMaint)) {
                sendEmailNotification(name, item.chipID, item.battery < 3.2 ? "Baterie critică" : "Posibilă roire — scădere critică greutate");
            }
        }
        const hCol = h > 75 ? 'var(--accent-green)' : (h > 40 ? '#f1c40f' : '#ee5253');

        // Banner roire — 2 niveluri:
        // -0.5 kg/24h = avertisment (poate fi evaporare, fluctuație)
        // -1.5 kg/24h = alertă critică (roire probabilă)
        const aID_roi = item.chipID + '_roi';
        let roireHTML = '';
        const _roiIsRes = _roiResolvedInSession.has(aID_roi) && (() => { const s=_roiResolvedInSession.getTs(aID_roi),c=parseInt(item.ts)||0; return !(s>0&&c>s); })();
        if (!isManual && item.delta24 <= -0.5 && !isMaint && !_roiIsRes) {
            const isCritical = item.delta24 <= -1.5;
            const alertLabel = isCritical
                ? (_lang==='en' ? '🚨 SWARM ALERT — CRITICAL DROP' : '🚨 ALARMĂ ROIRE')
                : (_lang==='en' ? '⚠️ WEIGHT DROP — CHECK HIVE' : '⚠️ SCĂDERE GREUTATE — VERIFICĂ STUPUL');
            roireHTML = `<div class="roire-alert-banner" style="${isCritical?'':'background:linear-gradient(135deg,#f39c12,#d68910);'}" onclick="event.stopPropagation();showConfirmModal({title:'${isCritical?'🚨':'⚠️'} ${_lang==='en'?(isCritical?'Critical Swarm Alert':'Weight Drop Alert'):(isCritical?'Alarmă Roire Critică':'Alertă Scădere Greutate')}',message:'${name} — ${_lang==='en'?'Mark as resolved?':'Marchezi ca rezolvat?'}',confirmText:'${_lang==='en'?'Mark Resolved':'Marchează Rezolvat'}',type:'${isCritical?'danger':'warning'}',onConfirm:()=>{resolveAlert('${aID_roi}','${item.chipID}','Alertă Greutate');renderDashboard();}})">${alertLabel}</div>`;
        }

        const card = document.createElement('div');
        card.className = 'hive-wrapper';
        card.id = `hive-card-${item.chipID}`;
        card.onclick = () => { card.classList.add('opening'); releaseBees(card); setTimeout(() => openHiveModal(item.chipID, name, item.meta), 400); };
        // Evităm hover flickering la marginea cardului
        let _hoverTimer = null;
        card.onmouseenter = () => {
            clearTimeout(_hoverTimer);
            card.style.transition = 'transform 0.2s ease';
            card.style.transform  = 'translateY(-10px) scale(1.02)';
        };
        card.onmouseleave = () => {
            _hoverTimer = setTimeout(() => {
                card.style.transform = '';
            }, 50); // mic delay anti-flicker
        };

        // Indicator conexiune senzor
        let sensorDot = '', sensorLabel = '';
        if (!isManual && ts) {
            const age = Math.floor((Date.now()/1000) - ts);
            if      (age < 7200)  { sensorDot = 'background:#10ac84'; sensorLabel = '●'; }  // < 2h verde
            else if (age < 86400) { sensorDot = 'background:#f39c12'; sensorLabel = '●'; }  // < 24h portocaliu
            else                  { sensorDot = 'background:#ee5253'; sensorLabel = '●'; }  // > 24h roșu
        }

        card.innerHTML = `
            ${maintIcon}
            <div class="health-badge" style="background:${hCol}" onmouseover="showHTip('${tip.join(', ')}', event)" onmouseout="hideHTip()">${h}%</div>
            <div class="queen-diamond" style="background:${item.meta.qColor}"></div>
            ${roireHTML}
            <div class="roof"></div>
            <div class="wood-box layer-top">
                <span>${name}${manualBadge}</span>
                <span class="chip-id">ID: ${item.chipID}</span>
            </div>
            <div class="wood-box layer-mid">
                <div style="display:flex;align-items:baseline;gap:3px;">
                    <span>${item.weight.toFixed(1)}</span>
                    <small style="font-size:0.9rem;opacity:0.6;font-family:'Nunito',sans-serif;">kg</small>
                </div>
                <div style="display:flex;gap:5px;align-items:center;margin-top:6px;flex-wrap:wrap;justify-content:center;">
                    <span style="font-size:0.78rem;font-weight:800;padding:2px 8px;border-radius:12px;background:${(item.delta24||0)>=0?'rgba(16,172,132,0.15)':'rgba(238,82,83,0.15)'};color:${item.delta24 >= 0 ? 'var(--accent-green)' : 'var(--accent-red)'}">
                        ${item.delta24 >= 0 ? '+' : ''}${(item.delta24 || 0).toFixed(2)}
                    </span>
                    ${!isManual && item.deltaDay !== undefined ? `<span style="font-size:0.68rem;font-weight:700;opacity:0.75;color:${(item.deltaDay||0)>=0?'var(--accent-green)':'var(--accent-red)'}">24h ${(item.deltaDay||0)>=0?'+':''}${(item.deltaDay||0).toFixed(2)}</span>` : ''}
                </div>
            </div>
            <div class="wood-box layer-bot">
                <div class="mini-stats">
                    ${!isManual && item.temperature ? `<span>🌡️ <b>${item.temperature}°C</b></span><span style="opacity:0.25;">|</span>` : ''}
                    <span>🔋 <b>${batteryTxt}</b></span>
                </div>
                ${!isManual && (item.honeyEstimate||0) > 0 ? `<div style="font-size:0.72rem;color:var(--accent-orange);font-weight:800;margin-top:4px;">🍯 ~${(item.honeyEstimate||0).toFixed(1)} kg${item.supersCount > 0 ? ' · '+item.supersCount+' mag.' : ''}</div>` : ''}
                ${(() => { const cs=calcColonyStrength(item); return `<div style="display:flex;align-items:center;gap:5px;margin-top:4px;width:88%;">`
                    + `<div style="flex:1;height:3px;border-radius:2px;background:rgba(0,0,0,0.1);overflow:hidden;"><div style="height:100%;width:${cs.score}%;background:${cs.color};border-radius:2px;"></div></div>`
                    + `<span style="font-size:0.62rem;font-weight:800;color:${cs.color};white-space:nowrap;">${cs.emoji} ${cs.label}${cs.details&&cs.details!=='estimat'?' · '+cs.details:''}</span>`
                    + `</div>`; })()}
                ${(() => { const d=getLastInspectionDays(item.meta?.nickname||('Stup '+item.chipID)); return d!==null&&d>14?`<div style="font-size:0.62rem;font-weight:700;color:#f39c12;margin-top:2px;">📋 ${d}z fără inspecție</div>`:''; })()}
            </div>
            <div class="base"></div>
            <div style="font-size:0.72rem;color:var(--wood-dark);font-weight:800;margin-top:7px;opacity:0.8;text-align:center;display:flex;align-items:center;justify-content:center;gap:5px;">
                ${sensorDot ? `<span style="width:8px;height:8px;border-radius:50%;${sensorDot};display:inline-block;flex-shrink:0;"></span>` : '🕒'}
                <span>${timeString}</span>
            </div>
        `;
        // Swipe actions pe mobile
        addSwipeToCard(card, item.chipID, name);
        container.appendChild(card);
    });

    // Sortare — recreăm mereu ca să reflecte starea activă corect
    let sortBar = document.getElementById('dashboard-sort-bar');
    if (sortBar) sortBar.remove();
    sortBar = document.createElement('div');
    sortBar.id = 'dashboard-sort-bar';
    sortBar.style.cssText = 'width:100%;display:flex;justify-content:center;gap:6px;margin-bottom:12px;flex-wrap:wrap;padding:0 20px;';
    const sortLabels = {
        name:    _lang==='en' ? '📝 Name'    : '📝 Nume',
        weight:  _lang==='en' ? '⚖️ Weight'  : '⚖️ Greutate',
        health:  _lang==='en' ? '🏥 Health'  : '🏥 Sănătate',
        updated: _lang==='en' ? '🕒 Updated' : '🕒 Actualizat',
    };
    sortBar.innerHTML = `
        <span style="font-size:0.78rem;font-weight:800;color:var(--text-muted);align-self:center;">${_lang==='en'?'Sort:':'Sortare:'}</span>
        ${Object.entries(sortLabels).map(([k,lbl]) => {
            const isActive = (_dashSortKey === k);
            const dir = isActive ? (_dashSortDir === 1 ? ' ↑' : ' ↓') : '';
            return `<button onclick="sortDashboard('${k}')" id="sort-${k}" class="sort-btn${isActive?' sort-btn-active':''}">${lbl}${dir}</button>`;
        }).join('')}`;
    container.parentNode.insertBefore(sortBar, container);

    // Card adăugare stup manual
    if (userPermissions.canManageManual || userPermissions.isAdmin) {
        const addCard = document.createElement('div');
        addCard.className = 'hive-wrapper';
        addCard.style.cssText = 'display:flex;flex-direction:column;justify-content:center;align-items:center;cursor:pointer;border:2px dashed #95a5a6;background:transparent;box-shadow:none;min-height:200px;';
        addCard.onclick = addManualHivePrompt;
        addCard.innerHTML = `<div style="font-size:2.5rem;color:var(--text-muted);line-height:1;">➕</div><div style="color:#95a5a6;font-weight:bold;margin-top:10px;text-transform:uppercase;font-size:0.78rem;text-align:center;">${_lang==='en'?'Add<br>Manual Hive':'Creare<br>Stup Manual'}</div>`;
        container.appendChild(addCard);
    }
}


/* ════════════════════════════════════════
   SORTARE DASHBOARD
   ════════════════════════════════════════ */
let _dashSortKey = 'name';
let _dashSortDir = 1; // 1=asc, -1=desc

function sortDashboard(key) {
    if (_dashSortKey === key) {
        _dashSortDir *= -1;
    } else {
        _dashSortKey = key;
        _dashSortDir = key === 'weight' ? -1 : 1;
    }

    hivesDataLocal.sort((a, b) => {
        let va, vb;
        const nameA = String(a.meta?.nickname || chipIDtoName[a.chipID] || a.chipID || '');
        const nameB = String(b.meta?.nickname || chipIDtoName[b.chipID] || b.chipID || '');
        if (key === 'name')    { va = nameA.toLowerCase(); vb = nameB.toLowerCase(); }
        else if (key === 'weight')  { va = parseFloat(a.weight)||0; vb = parseFloat(b.weight)||0; }
        else if (key === 'updated') { va = a.ts||0; vb = b.ts||0; }
        else if (key === 'health') {
            // health = 100 - penalizări
            const hA = 100 - ((!a.isManual && a.battery<3.4)?20:0) - ((!a.isManual && (a.delta24||0)<=-0.15 && !(a.meta.maintenance==='true'||a.meta.maintenance===true))?30:0);
            const hB = 100 - ((!b.isManual && b.battery<3.4)?20:0) - ((!b.isManual && (b.delta24||0)<=-0.15 && !(b.meta.maintenance==='true'||b.meta.maintenance===true))?30:0);
            va = hA; vb = hB;
        }
        if (va < vb) return -1 * _dashSortDir;
        if (va > vb) return  1 * _dashSortDir;
        return 0;
    });

    renderDashboard();
}

/* ════════════════════════════════════════
   ADMIN: CHECKBOX STUPI
   ════════════════════════════════════════ */
function renderAdminHiveCheckboxes() {
    const container = document.getElementById('adm-hives-container');
    if (!container) return;
    container.innerHTML = '';
    const allIds = new Set(Object.keys(chipIDtoName));
    hivesDataLocal.forEach(h => allIds.add(h.chipID.toString()));
    if (allIds.size === 0) {
        container.innerHTML = '<p style="opacity:0.6;font-size:0.85rem;margin:0;text-align:center;">Nu există stupi în sistem.</p>';
        return;
    }
    allIds.forEach(id => {
        const name = chipIDtoName[id] || id;
        const lbl  = document.createElement('label');
        lbl.style.cssText = 'display:flex;align-items:center;margin-bottom:8px;cursor:pointer;font-size:0.9rem;';
        lbl.innerHTML = `<input type="checkbox" class="adm-hive-cb" value="${id}" style="width:18px;height:18px;margin:0 10px 0 0;padding:0;flex-shrink:0;"> <span>${name} <b style="opacity:0.65">(${id})</b></span>`;
        container.appendChild(lbl);
    });
}

/* ════════════════════════════════════════
   TABEL MOD COSTUM
   ════════════════════════════════════════ */
let _suitTableHash = '';
function renderSuitTable() {
    const newHash = hivesDataLocal.map(h => h.chipID).join(',');
    if (newHash === _suitTableHash && document.getElementById('suit-mode-container')?.children.length > 0) return;
    _suitTableHash = newHash;
    const container = document.getElementById('suit-mode-container');
    if (!container) return;
    let html = `<div style="overflow-x:auto;"><table class="inspection-table" style="min-width:650px;">
        <thead><tr>
            <th style="text-align:left;">Stup</th>
            <th>Matcă</th><th>Puiet/Ouă</th><th>Botci</th>
            <th>Tratament</th><th>Hrănire</th><th>Mentenanță</th>
        </tr></thead><tbody>`;
    hivesDataLocal.forEach(item => {
        const name  = item.meta.nickname || chipIDtoName[item.chipID] || item.chipID;
        const id    = item.chipID;
        const isMaint = item.meta.maintenance === true || item.meta.maintenance === "true";
        if (!suitData[id]) suitData[id] = { q:'?', e:'?', c:'?', t:'?', h:'?', m: isMaint ? 'ON' : 'OFF' };
        const d = suitData[id];
        html += `<tr>
            <td style="text-align:left;font-weight:800;font-size:1rem;min-width:120px;">${name}<br><small style="opacity:0.45;font-size:0.68rem;">ID: ${id}</small></td>
            <td><button class="big-check-btn ${d.q==='Văz'?'active-green':(d.q==='Nev'?'active-red':'')}" onclick="cycleSuitState('${id}','q',this)">${d.q}</button></td>
            <td><button class="big-check-btn ${d.e==='DA'?'active-green':(d.e==='NU'?'active-red':'')}" onclick="cycleSuitState('${id}','e',this)">${d.e}</button></td>
            <td><button class="big-check-btn ${d.c==='Fără'?'active-green':(d.c==='Roire'?'active-red':(d.c==='Salv'?'active-orange':''))}" onclick="cycleSuitState('${id}','c',this)">${d.c}</button></td>
            <td><button class="big-check-btn ${d.t==='Dat'?'active-green':(d.t==='NU'?'active-red':'')}" onclick="cycleSuitState('${id}','t',this)">${d.t}</button></td>
            <td><button class="big-check-btn ${d.h==='Dat'?'active-green':(d.h==='NU'?'active-red':'')}" onclick="cycleSuitState('${id}','h',this)">${d.h}</button></td>
            <td><button class="big-check-btn ${d.m==='ON'?'active-orange':(d.m==='OFF'?'active-green':'')}" onclick="cycleSuitState('${id}','m',this)">${d.m}</button></td>
        </tr>`;
    });
    html += `</tbody></table></div>`;
    container.innerHTML = html;
}

function cycleSuitState(id, field, btn) {
    const states = { q:['?','Văz','Nev'], e:['?','DA','NU'], c:['?','Fără','Roire','Salv'], t:['?','Dat','NU'], h:['?','Dat','NU'], m:['?','ON','OFF'] };
    const current = suitData[id][field];
    const idx     = states[field].indexOf(current);
    const next    = states[field][(idx + 1) % states[field].length];
    suitData[id][field] = next;
    btn.innerText   = next;
    btn.className   = 'big-check-btn';
    if (['Văz','DA','Fără','Dat','OFF'].includes(next)) btn.classList.add('active-green');
    if (['Nev','NU','Roire'].includes(next))            btn.classList.add('active-red');
    if (['Salv','ON'].includes(next))                   btn.classList.add('active-orange');
}

async function saveAllInspections() {
    let count = 0;
    const btn = document.getElementById('global-save-suit');
    if (btn) btn.innerText = "⏳...";
    for (let id in suitData) {
        const d = suitData[id];
        if (d.q !== '?' || d.e !== '?' || d.c !== '?' || d.t !== '?' || d.h !== '?' || d.m !== '?') {
            const name = hivesDataLocal.find(h => h.chipID == id)?.meta.nickname || chipIDtoName[id] || id;
            const extras = [];
            if (d.t === 'Dat') extras.push("Tratament: DA"); else if (d.t === 'NU') extras.push("Tratament: NU");
            if (d.h === 'Dat') extras.push("Hrănire: DA");   else if (d.h === 'NU') extras.push("Hrănire: NU");
            if (d.m === 'ON')  extras.push("Mentenanță: PORNITĂ"); else if (d.m === 'OFF') extras.push("Mentenanță: OPRITĂ");
            let text = `📋 Inspecție Rapidă: Matcă ${d.q} | Puiet/Ouă: ${d.e} | Botci: ${d.c}`;
            if (extras.length) text += ' | ' + extras.join(' | ');
            const fd = new FormData(); fd.append('action','save_note'); fd.append('stup', name); fd.append('text', text);
            await smartFetch(fd);
            if (d.m === 'ON' || d.m === 'OFF') {
                const fdM = new FormData(); fdM.append('action','save_metadata'); fdM.append('chipID', id); fdM.append('maintenance', d.m === 'ON' ? 'true' : 'false');
                await smartFetch(fdM);
            }
            suitData[id] = { q:'?', e:'?', c:'?', t:'?', h:'?', m:'?' };
            count++;
        }
    }
    if (count > 0) { toast(`${count} inspecții salvate în jurnal!`, 'success'); fetchData(); renderJurnal(); renderSuitTable(); }
    else toast('Nu ai bifat nicio schimbare!', 'info');
    if (btn) btn.innerText = "💾";
}

/* ════════════════════════════════════════
   STUPI MANUALI
   ════════════════════════════════════════ */
function addManualHivePrompt() {
    // Modal custom — un singur popup cu toate câmpurile
    let overlay = document.getElementById('add-hive-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'add-hive-overlay';
        overlay.style.cssText = 'display:none;position:fixed;inset:0;z-index:99999;background:rgba(26,31,38,0.7);backdrop-filter:blur(6px);align-items:center;justify-content:center;';
        overlay.innerHTML = `
        <div style="background:var(--cream,#fdfbf7);width:92%;max-width:400px;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
            <div style="background:linear-gradient(135deg,var(--wood-dark,#a6845c),var(--honey,#d4860b));padding:18px 22px;display:flex;align-items:center;justify-content:space-between;">
                <span style="font-weight:900;font-size:1rem;color:#fff;">🐝 Stup Nou</span>
                <button onclick="document.getElementById('add-hive-overlay').style.display='none'" style="background:rgba(255,255,255,0.2);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:1rem;">✕</button>
            </div>
            <div style="padding:20px 22px;display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label style="font-size:0.8rem;font-weight:800;color:var(--premium-brown);display:block;margin-bottom:5px;">📝 Nume / Poreclă</label>
                    <input id="ahn-name" type="text" placeholder="ex: Stup 4, Albinuța..." style="width:100%;padding:10px 12px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.95rem;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:0.8rem;font-weight:800;color:var(--premium-brown);display:block;margin-bottom:5px;">⚖️ Greutate estimată (kg)</label>
                    <input id="ahn-weight" type="number" value="0" min="0" max="999" step="0.1" style="width:100%;padding:10px 12px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.95rem;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:0.8rem;font-weight:800;color:var(--premium-brown);display:block;margin-bottom:5px;">🌡️ Temperatură estimată (°C)</label>
                    <input id="ahn-temp" type="number" value="32" min="-30" max="60" style="width:100%;padding:10px 12px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.95rem;box-sizing:border-box;">
                </div>
            </div>
            <div style="padding:14px 22px 20px;display:flex;gap:10px;justify-content:flex-end;">
                <button onclick="document.getElementById('add-hive-overlay').style.display='none'" style="padding:10px 20px;background:#f5f0e8;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;color:var(--premium-brown);">Anulează</button>
                <button onclick="confirmAddHive()" style="padding:10px 22px;background:var(--honey,#d4860b);border:none;border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;color:#fff;">➕ Adaugă Stupul</button>
            </div>
        </div>`;
        overlay.onclick = e => { if (e.target === overlay) overlay.style.display='none'; };
        document.body.appendChild(overlay);
    }
    document.getElementById('ahn-name').value   = '';
    document.getElementById('ahn-weight').value = '0';
    document.getElementById('ahn-temp').value   = '32';
    overlay.style.display = 'flex';
    setTimeout(() => document.getElementById('ahn-name').focus(), 100);
}

function confirmAddHive() {
    const name   = document.getElementById('ahn-name')?.value?.trim();
    const weight = document.getElementById('ahn-weight')?.value;
    const temp   = document.getElementById('ahn-temp')?.value;
    if (!name) { toast('Introdu un nume pentru stup!', 'warning'); return; }
    document.getElementById('add-hive-overlay').style.display = 'none';
    const fd = new FormData();
    fd.append('action','add_manual_hive'); fd.append('name', name);
    fd.append('weight', weight || 0); fd.append('temp', temp || 0);
    smartFetch(fd).then(() => fetchData());
}

function updateManualHiveData() {
    const w = document.getElementById('m-weight-input')?.value;
    const t = document.getElementById('m-temp-input')?.value;
    if (w && !validateNumeric(w, 0, 999, 'Greutate')) return;
    if (t && !validateNumeric(t, -30, 60, 'Temperatură')) return;
    const fd = new FormData();
    fd.append('action','update_manual_data'); fd.append('chipID', currentChipID);
    fd.append('weight', w); fd.append('temp', t);
    smartFetch(fd).then(() => { toast('Date actualizate cu succes!', 'success'); fetchData(); });
}

function deleteManualHive() {
    showConfirmModal({ title: _lang==='en'?'🗑️ Delete hive':'🗑️ Șterge Stupul', message: t('confirm_delete_hive'), confirmText: t('btn_delete'), type:'danger', onConfirm: () => {
        const fd = new FormData(); fd.append('action','delete_manual_hive'); fd.append('chipID', currentChipID);
        smartFetch(fd).then(() => { document.getElementById('histModal').style.display='none'; document.querySelectorAll('.hive-wrapper').forEach(h=>h.classList.remove('opening')); document.querySelectorAll('.flying-bee').forEach(b=>b.remove()); fetchData(); }); } }); return;
    const fd = new FormData(); fd.append('action','delete_manual_hive'); fd.append('chipID', currentChipID);
    smartFetch(fd).then(() => {
        document.getElementById('histModal').style.display = 'none';
        document.querySelectorAll('.hive-wrapper').forEach(h => h.classList.remove('opening'));
        document.querySelectorAll('.flying-bee').forEach(b => b.remove());
        fetchData();
    });
}


/* ════════════════════════════════════════
   SWIPE ACTIONS PE CARDURI STUPI (mobile)
   Swipe stânga → acțiuni rapide
   ════════════════════════════════════════ */
function addSwipeToCard(card, chipID, name) {
    let startX = 0, startY = 0, swipeDist = 0;
    let swipePanel = null;

    card.addEventListener('touchstart', e => {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        swipeDist = 0;
    }, { passive: true });

    card.addEventListener('touchmove', e => {
        if (!e.touches[0]) return;
        swipeDist = e.touches[0].clientX - startX;
        const vertDist = Math.abs(e.touches[0].clientY - startY);
        // Ignoră scroll vertical
        if (vertDist > Math.abs(swipeDist)) return;
        if (swipeDist < -20 && swipeDist > -120) {
            card.style.transform = `translateX(${swipeDist}px)`;
            card.style.transition = 'none';
        }
    }, { passive: true });

    card.addEventListener('touchend', e => {
        card.style.transition = 'transform 0.3s ease';
        if (swipeDist < -60) {
            // Swipe complet — arată panoul
            card.style.transform = 'translateX(-80px)';
            showSwipePanel(card, chipID, name);
        } else {
            card.style.transform = '';
            hideSwipePanel(card);
        }
        swipeDist = 0;
    }, { passive: true });
}

function showSwipePanel(card, chipID, name) {
    hideSwipePanel(card); // curăță orice panou existent
    const panel = document.createElement('div');
    panel.className = 'swipe-actions-panel';
    panel.style.cssText = 'position:absolute;right:-80px;top:0;bottom:0;width:80px;display:flex;flex-direction:column;gap:0;overflow:hidden;border-radius:0 12px 12px 0;';
    panel.innerHTML = `
        <button onclick="event.stopPropagation();closeSwipe('${chipID}');openHiveModal('${chipID}','${name.replace(/'/g,"\'")}', hivesDataLocal.find(h=>h.chipID=='${chipID}')?.meta||{})"
            style="flex:1;background:#3498db;color:#fff;border:none;font-size:1.2rem;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;">
            <span>📋</span><span style="font-size:0.6rem;font-weight:800;">Inspecție</span>
        </button>
        <button onclick="event.stopPropagation();closeSwipe('${chipID}');quickNotePrompt('${name.replace(/'/g,"\'")}')"
            style="flex:1;background:#10ac84;color:#fff;border:none;font-size:1.2rem;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;">
            <span>📝</span><span style="font-size:0.6rem;font-weight:800;">Notă</span>
        </button>`;
    card.style.position = 'relative';
    card.style.overflow = 'visible';
    card.appendChild(panel);
    // Click oriunde altundeva — închide
    setTimeout(() => {
        document.addEventListener('click', function closeHandler() {
            closeSwipe(chipID);
            document.removeEventListener('click', closeHandler);
        });
    }, 50);
}

function hideSwipePanel(card) {
    const existing = card.querySelector('.swipe-actions-panel');
    if (existing) existing.remove();
    card.style.transform = '';
}

function closeSwipe(chipID) {
    const card = document.getElementById(`hive-card-${chipID}`);
    if (card) hideSwipePanel(card);
}

function quickNotePrompt(name) {
    // Mini-modal în loc de prompt() nativ (blocat pe unele browsere mobile)
    let modal = document.getElementById('quick-note-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'quick-note-modal';
        modal.style.cssText = 'display:none;position:fixed;inset:0;z-index:9600;background:rgba(26,31,38,0.65);backdrop-filter:blur(4px);align-items:flex-end;justify-content:center;';
        modal.innerHTML = `<div style="background:var(--cream,#fdfbf7);width:100%;max-width:520px;border-radius:20px 20px 0 0;padding:22px 20px 32px;box-shadow:0 -8px 32px rgba(0,0,0,0.18);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <span id="qnm-title" style="font-weight:900;font-size:1rem;color:var(--premium-brown)">📝 Notă rapidă</span>
                <button onclick="document.getElementById('quick-note-modal').style.display='none'" style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:var(--text-muted)">✕</button>
            </div>
            <textarea id="qnm-text" rows="3" placeholder="Observații rapide..." style="width:100%;padding:11px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.95rem;resize:none;box-sizing:border-box;outline:none;"></textarea>
            <div style="display:flex;gap:10px;margin-top:12px;">
                <button onclick="document.getElementById('quick-note-modal').style.display='none'" style="flex:1;padding:11px;background:#f5f0e8;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;">Anulează</button>
                <button id="qnm-save" style="flex:2;padding:11px;background:linear-gradient(135deg,var(--wood-dark,#a6845c),var(--honey,#d4860b));color:#fff;border:none;border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;">💾 Salvează</button>
            </div>
        </div>`;
        document.body.appendChild(modal);
    }
    document.getElementById('qnm-title').textContent = `📝 Notă: ${name}`;
    document.getElementById('qnm-text').value = '';
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('qnm-text').focus(), 200);
    // Binding buton salvare
    document.getElementById('qnm-save').onclick = () => {
        const text = document.getElementById('qnm-text').value.trim();
        if (!text) { toast(t('toast_select_hive'), 'warning'); return; }
        const fd = new FormData();
        fd.append('action', 'save_note'); fd.append('stup', name); fd.append('text', text);
        smartFetch(fd).then(() => {
            modal.style.display = 'none';
            toast(`${t('toast_note_saved')} (${name})`, 'success');
            renderJurnal();
        });
    };
}


/* ════════════════════════════════════════
   HARTĂ LEAFLET — integrare reală
   Fallback la harta custom dacă Leaflet
   nu e disponibil
   ════════════════════════════════════════ */
let _leafletMap = null;
let _leafletMarkers = {};

function initLeafletMap() {
    if (typeof L === 'undefined') {
        console.warn('Leaflet nu e disponibil, folosim harta custom.');
        return false;
    }
    const mapEl = document.getElementById('hive-map');
    if (!mapEl) return false;

    // Înlocuim div-ul cu unul pentru Leaflet
    const leafletDiv = document.createElement('div');
    leafletDiv.id = 'leaflet-map';
    leafletDiv.style.cssText = 'width:100%;height:500px;border-radius:var(--r-lg,14px);border:2.5px solid var(--wood-mid,#d1b490);box-shadow:0 4px 16px rgba(93,64,55,0.08);overflow:hidden;z-index:1;';
    mapEl.parentNode.insertBefore(leafletDiv, mapEl);
    mapEl.style.display = 'none';

    // Inițializare hartă cu tile OSM
    _leafletMap = L.map('leaflet-map', {
        center: [44.19, 25.10],
        zoom:   14,
        zoomControl: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(_leafletMap);

    return true;
}

function renderLeafletMarkers() {
    if (!_leafletMap) return;

    // Curăță markere vechi
    Object.values(_leafletMarkers).forEach(m => m.remove());
    _leafletMarkers = {};

    // Stupi
    hivesDataLocal.forEach(item => {
        const name   = item.meta.nickname || chipIDtoName[item.chipID] || item.chipID;
        const isMaint= item.meta.maintenance === true || item.meta.maintenance === 'true';
        const delta  = item.delta24 || 0;
        const isOk   = isMaint || delta > -0.15;

        // Icon custom HTML
        const color   = isOk ? 'var(--accent-green)' : '#ee5253';
        const icon    = L.divIcon({
            className: '',
            html: `<div style="background:${color};color:#fff;padding:5px 10px;border-radius:8px;font-weight:800;font-size:0.82rem;white-space:nowrap;border:2px solid #fff;box-shadow:0 3px 10px rgba(0,0,0,0.25);">🐝 ${name}<br><span style="font-size:0.7rem;opacity:0.9">⚖️ ${parseFloat(item.weight).toFixed(1)} kg</span></div>`,
            iconAnchor: [30, 20],
        });

        // Coordonate: dacă avem lat/lng salvate le folosim, altfel centru
        const lat = parseFloat(item.meta.lat) || 44.19 + (Math.random()-0.5)*0.005;
        const lng = parseFloat(item.meta.lng) || 25.10 + (Math.random()-0.5)*0.005;

        const marker = L.marker([lat, lng], { icon, draggable: true })
            .addTo(_leafletMap)
            .bindPopup(`<b>${name}</b><br>Greutate: ${parseFloat(item.weight).toFixed(2)} kg<br>Temp: ${item.temperature}°C<br>Delta 24h: ${delta>=0?'+':''}${delta.toFixed(2)} kg`)
            .on('click', () => openHiveModal(item.chipID, name, item.meta))
            .on('dragend', e => {
                const pos = e.target.getLatLng();
                const fd  = new FormData();
                fd.append('action','save_metadata'); fd.append('chipID', item.chipID);
                fd.append('lat', pos.lat.toFixed(6)); fd.append('lng', pos.lng.toFixed(6));
                fd.append('x', item.meta.x||50); fd.append('y', item.meta.y||50);
                smartFetch(fd).then(() => toast(`Poziție salvată pentru ${name}`, 'success'));
            });

        _leafletMarkers[item.chipID] = marker;
    });

    // Markere topografice
    markersLocal.forEach(m => {
        const lat = parseFloat(m.lat) || 44.19 + (Math.random()-0.5)*0.01;
        const lng = parseFloat(m.lng) || 25.10 + (Math.random()-0.5)*0.01;
        const icon = L.divIcon({
            className: '',
            html: `<div style="font-size:1.6rem;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.3));">${m.icon||'📍'}</div>`,
            iconAnchor: [12, 24],
        });
        const marker = L.marker([lat, lng], { icon, draggable: true })
            .addTo(_leafletMap)
            .bindPopup(`<b>${m.type}</b>`)
            .on('dragend', e => {
                const pos = e.target.getLatLng();
                const fd  = new FormData();
                fd.append('action','save_marker'); fd.append('id',m.id);
                fd.append('type',m.type); fd.append('icon',m.icon);
                fd.append('lat',pos.lat.toFixed(6)); fd.append('lng',pos.lng.toFixed(6));
                fd.append('x',m.x||50); fd.append('y',m.y||50);
                smartFetch(fd);
            })
            .on('dblclick', () => { deleteMapMarker(m.id); });
        _leafletMarkers['mark_'+m.id] = marker;
    });
}

/* ════════════════════════════════════════
   HARTĂ
   ════════════════════════════════════════ */
function fetchMarkers() {
    fetch('backend.php?fetch=markers').then(r => r.text()).then(text => {
        try { markersLocal = JSON.parse(text) || []; } catch (e) { markersLocal = []; }
        markersLocal = markersLocal.filter(m => m?.id);
        if (!isDragging) {
            renderMap();
            // Dacă Leaflet e activ, actualizăm și markere pe hartă reală
            if (_leafletMap) renderLeafletMarkers();
        }
    });
}

function renderMap() {
    const map = document.getElementById('hive-map');
    if (!map) return;

    // Markere custom
    markersLocal.forEach(m => {
        let el = document.getElementById(m.id);
        if (!el) {
            el = document.createElement('div'); el.className = 'map-marker'; el.id = m.id;
            el.innerHTML = `${m.icon || '📍'}<span>${m.type || 'Necunoscut'}</span>`;
            el.ondblclick = e => { e.stopPropagation(); deleteMapMarker(m.id); };
            el.onmousedown = e => dragMarker(e, el, m);
            el.addEventListener('touchstart', e => dragMarker(e, el, m), { passive: false });
            map.appendChild(el);
        }
        el.style.left = m.x + '%'; el.style.top = m.y + '%';
    });
    Array.from(map.getElementsByClassName('map-marker')).forEach(el => {
        if (!markersLocal.find(m => m.id === el.id)) el.remove();
    });

    // Stupi pe hartă — design compact
    hivesDataLocal.forEach(item => {
        const name   = item.meta.nickname || chipIDtoName[item.chipID] || item.chipID;
        const mId    = 'map-hive-' + item.chipID;
        const isMaint= item.meta.maintenance === true || item.meta.maintenance === 'true';
        const delta  = item.delta24 || 0;
        const isWarn = !item.isManual && delta <= -0.15 && !isMaint;
        const isBat  = !item.isManual && item.battery < 3.4;
        // Culoare stare: verde ok, portocaliu baterie slabă, roșu posibilă roire
        const dotCol = isWarn ? '#ee5253' : (isBat ? '#f39c12' : 'var(--accent-green)');

        let mHive = document.getElementById(mId);
        if (!mHive) {
            mHive = document.createElement('div');
            mHive.id        = mId;
            mHive.className = 'map-hive-pin'; // clasă nouă — fără conflict cu .map-hive vechi
            mHive.style.cssText = 'position:absolute;cursor:pointer;user-select:none;z-index:50;transform:translate(-50%,-50%);transition:transform 0.18s cubic-bezier(0.34,1.56,0.64,1);';
            mHive.onmouseenter = () => { mHive.style.transform = 'translate(-50%,-50%) scale(1.2)'; };
            mHive.onmouseleave = () => { mHive.style.transform = 'translate(-50%,-50%) scale(1)'; };
            mHive.onclick = e => { e.stopPropagation(); if (dragOccurred) { dragOccurred = false; return; } openHiveModal(item.chipID, name, item.meta); };
            mHive.onmousedown = e => dragHive(e, mHive, item.chipID, item.meta);
            mHive.addEventListener('touchstart', e => dragHive(e, mHive, item.chipID, item.meta), { passive: false });
            map.appendChild(mHive);
        }

        // Icon în formă de stup (casă cu acoperiș triunghiular)
        const label     = name.length > 9 ? name.substring(0,8) + '…' : name;
        const bodyColor = isWarn ? '#ee5253' : (isMaint ? '#e67e22' : (isBat ? '#f39c12' : '#d4860b'));
        const roofColor = isWarn ? '#c0392b' : (isMaint ? '#c0550a' : (isBat ? '#d68910' : '#a6845c'));

        mHive.innerHTML = `
            <div style="display:flex;flex-direction:column;align-items:center;gap:0;position:relative;">
                <svg width="38" height="42" viewBox="0 0 38 42" style="overflow:visible;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.25))">
                    <!-- Acoperiș -->
                    <polygon points="19,1 36,14 2,14" fill="${roofColor}" stroke="rgba(255,255,255,0.55)" stroke-width="0.8"/>
                    <!-- Corp stup -->
                    <rect x="5" y="14" width="28" height="20" rx="2" fill="${bodyColor}" stroke="rgba(255,255,255,0.35)" stroke-width="0.8"/>
                    <!-- Dungi fagure -->
                    <line x1="5" y1="20" x2="33" y2="20" stroke="rgba(255,255,255,0.2)" stroke-width="0.8"/>
                    <line x1="5" y1="26" x2="33" y2="26" stroke="rgba(255,255,255,0.2)" stroke-width="0.8"/>
                    <!-- Urdiniș -->
                    <rect x="13" y="28" width="12" height="4" rx="1" fill="${roofColor}" opacity="0.85"/>
                    <!-- Dot status -->
                    <circle cx="33" cy="10" r="4" fill="${dotCol}" stroke="white" stroke-width="1.2"/>
                </svg>
                <div style="margin-top:2px;background:rgba(255,255,255,0.93);color:#5d4037;padding:2px 6px;border-radius:5px;font-size:0.62rem;font-weight:800;white-space:nowrap;font-family:'Nunito',sans-serif;border:1px solid rgba(166,132,92,0.25);box-shadow:0 1px 3px rgba(0,0,0,0.1);max-width:72px;overflow:hidden;text-overflow:ellipsis;">
                    ${label}
                </div>
            </div>`;

        mHive.style.left = (item.meta.x !== undefined ? item.meta.x : 50) + '%';
        mHive.style.top  = (item.meta.y !== undefined ? item.meta.y : 50) + '%';
    });
    Array.from(map.getElementsByClassName('map-hive-pin')).forEach(el => {
        const hId = el.id.replace('map-hive-', '');
        if (!hivesDataLocal.find(h => h.chipID == hId)) el.remove();
    });
}

function addMapMarker(type, icon) {
    const id = 'mark_' + Date.now();
    const newX = 40 + Math.random() * 20, newY = 40 + Math.random() * 20;
    markersLocal.push({ id, type, icon, x: newX, y: newY });
    const fd = new FormData(); fd.append('action','save_marker'); fd.append('id', id); fd.append('type', type); fd.append('icon', icon); fd.append('x', newX); fd.append('y', newY);
    smartFetch(fd).then(() => renderMap());
}

function deleteMapMarker(id) {
    showConfirmModal({
        title:       _lang==='en' ? '🗑️ Delete marker' : '🗑️ Șterge Marcaj',
        message:     t('confirm_delete_marker'),
        confirmText: t('btn_delete'),
        type:        'danger',
        onConfirm:   () => {
            markersLocal = markersLocal.filter(m => m.id !== id);
            renderMap(); // imediat - fără să aștepte serverul
            const fd = new FormData(); fd.append('action','delete_marker'); fd.append('id', id);
            smartFetch(fd); // sync în background
        }
    });
}

function dragHive(e, el, id, meta) {
    if (e.type === 'touchstart') e.preventDefault();
    // Folosim Leaflet map dacă e activ, altfel hiva-map custom
    const mapEl = (_leafletMap ? document.getElementById('leaflet-map') : null)
                  || document.getElementById('hive-map');
    const rect   = mapEl ? mapEl.getBoundingClientRect() : { left:0, top:0, width:1, height:1 };
    const startX = e.touches ? e.touches[0].clientX : e.clientX;
    const startY = e.touches ? e.touches[0].clientY : e.clientY;
    let hasMoved = false; isDragging = true;
    function move(ev) {
        const cx = ev.touches ? ev.touches[0].clientX : ev.clientX;
        const cy = ev.touches ? ev.touches[0].clientY : ev.clientY;
        if (Math.abs(cx - startX) > 3 || Math.abs(cy - startY) > 3) hasMoved = true;
        let x = Math.min(88, Math.max(0, ((cx - rect.left) / rect.width) * 100));
        let y = Math.min(90, Math.max(0, ((cy - rect.top) / rect.height) * 100));
        el.style.left = x + '%'; el.style.top = y + '%'; meta.x = x; meta.y = y;
    }
    function stop() {
        isDragging = false; if (hasMoved) dragOccurred = true;
        window.removeEventListener('mousemove', move); window.removeEventListener('mouseup', stop);
        window.removeEventListener('touchmove', move); window.removeEventListener('touchend', stop);
        if (hasMoved) { const fd = new FormData(); fd.append('action','save_metadata'); fd.append('chipID', id); fd.append('x', meta.x); fd.append('y', meta.y); smartFetch(fd); }
    }
    window.addEventListener('mousemove', move); window.addEventListener('mouseup', stop);
    window.addEventListener('touchmove', move, { passive: false }); window.addEventListener('touchend', stop);
}

function dragMarker(e, el, mObj) {
    if (e.type === 'touchstart') e.preventDefault();
    const mapEl2 = (_leafletMap ? document.getElementById('leaflet-map') : null)
                   || document.getElementById('hive-map');
    const rect = mapEl2 ? mapEl2.getBoundingClientRect() : { left:0, top:0, width:1, height:1 };
    function move(ev) {
        const cx = ev.touches ? ev.touches[0].clientX : ev.clientX;
        const cy = ev.touches ? ev.touches[0].clientY : ev.clientY;
        let x = Math.min(95, Math.max(0, ((cx - rect.left) / rect.width) * 100));
        let y = Math.min(90, Math.max(0, ((cy - rect.top) / rect.height) * 100));
        el.style.left = x + '%'; el.style.top = y + '%'; mObj.x = x; mObj.y = y;
    }
    function stop() {
        window.removeEventListener('mousemove', move); window.removeEventListener('mouseup', stop);
        window.removeEventListener('touchmove', move); window.removeEventListener('touchend', stop);
        const fd = new FormData(); fd.append('action','save_marker'); fd.append('id', mObj.id); fd.append('type', mObj.type); fd.append('icon', mObj.icon); fd.append('x', mObj.x); fd.append('y', mObj.y); smartFetch(fd);
    }
    window.addEventListener('mousemove', move); window.addEventListener('mouseup', stop);
    window.addEventListener('touchmove', move, { passive: false }); window.addEventListener('touchend', stop);
}

function resetMapPositions() {
    showConfirmModal({ title: _lang==='en'?'🔄 Reset positions':'🔄 Resetează Pozițiile', message: t('confirm_reset_map'), confirmText: _lang==='en'?'Reset':'Resetează', type:'warning', onConfirm: () => {
    let offset=0; hivesDataLocal.forEach(h=>{h.meta.x=45+offset;h.meta.y=45+offset;offset+=1.5;const fd=new FormData();fd.append('action','save_metadata');fd.append('chipID',h.chipID);fd.append('x',h.meta.x);fd.append('y',h.meta.y);smartFetch(fd);}); renderMap(); } });
}

/* ════════════════════════════════════════
   EMAIL NOTIFICĂRI
   Set de protecție anti-duplicat per sesiune
   ════════════════════════════════════════ */
const _emailAlertsSentThisSession = new Set();

function sendEmailNotification(stup, id, msg, details = "") {
    const key = `${id}_${msg}`;
    if (_emailAlertsSentThisSession.has(key)) return; // deja trimis în sesiunea curentă
    _emailAlertsSentThisSession.add(key);
    const fd = new FormData();
    fd.append('action','send_alert_email'); fd.append('alert_id', id);
    fd.append('stup', stup); fd.append('msg', msg); fd.append('details', details);
    smartFetch(fd);
}

/* ════════════════════════════════════════
   RAME / CUIB
   ════════════════════════════════════════ */
function getFrameHTML(index, state) {
    const labels = { 'f-gol':'GOALĂ', 'f-miere':'MIERE', 'f-polen':'POLEN', 'f-puiet':'PUIET', 'f-ceara':'CEARĂ' };
    return `<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:100%;height:100%;padding:2px;text-align:center;"><span style="font-size:0.9rem;margin-bottom:2px;line-height:1;">${index + 1}</span><span style="font-size:0.52rem;font-weight:800;opacity:0.9;line-height:1.1;word-wrap:break-word;max-width:100%;">${labels[state]}</span></div>`;
}

function initFrameMapper(savedFramesStr) {
    let framesArr = [];
    try { framesArr = JSON.parse(savedFramesStr || '[]'); } catch (e) {}
    if (framesArr.length !== 10) framesArr = Array(10).fill('f-gol');
    currentFrames = framesArr;
    const container = document.getElementById('frame-mapper-container');
    if (!container) return;
    container.innerHTML = '';
    currentFrames.forEach((state, i) => {
        const f = document.createElement('div'); f.className = `frame-slot ${state}`; f.innerHTML = getFrameHTML(i, state);
        f.onclick = () => cycleFrame(i, f, 1); f.oncontextmenu = e => { e.preventDefault(); cycleFrame(i, f, -1); };
        container.appendChild(f);
    });
}

function cycleFrame(index, element, direction) {
    const currIdx  = frameStates.indexOf(currentFrames[index]);
    const nextState = frameStates[(currIdx + direction + frameStates.length) % frameStates.length];
    currentFrames[index] = nextState; element.className = `frame-slot ${nextState}`; element.innerHTML = getFrameHTML(index, nextState);
    const fd = new FormData(); fd.append('action','save_metadata'); fd.append('chipID', currentChipID); fd.append('frames', JSON.stringify(currentFrames)); smartFetch(fd);
}

/* ════════════════════════════════════════
   INSPECȚIE RAPIDĂ
   ════════════════════════════════════════ */
function toggleQuick(btn, groupName) {
    btn.parentElement.querySelectorAll('.quick-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function saveQuickInspection() {
    const matca = document.querySelector('button[onclick*="matca"].active')?.innerText || '?';
    const oua   = document.querySelector('button[onclick*="oua"].active')?.innerText || '?';
    const botci = document.querySelector('button[onclick*="botci"].active')?.innerText || '?';
    // Salvăm selecțiile pentru acest stup
    try {
        localStorage.setItem('qi_' + currentChipID, JSON.stringify({
            matca: document.querySelector('button[onclick*="matca"].active')?.getAttribute('onclick') || '',
            oua:   document.querySelector('button[onclick*="oua"].active')?.getAttribute('onclick')   || '',
            botci: document.querySelector('button[onclick*="botci"].active')?.getAttribute('onclick') || ''
        }));
    } catch(e) {}
    const counts = {}; let totalOcupat = 0;
    currentFrames.forEach(f => {
        if (f !== 'f-gol') {
            totalOcupat++;
            const label = { 'f-miere':'Miere', 'f-polen':'Polen', 'f-puiet':'Puiet', 'f-ceara':'Ceară Nouă' }[f] || 'Necunoscut';
            counts[label] = (counts[label] || 0) + 1;
        }
    });
    const rameInfo = totalOcupat > 0
        ? ` | Rame Ocupate: ${totalOcupat} (${Object.entries(counts).map(([n,c]) => c+' '+n).join(', ')})`
        : ' | Rame: Toate goale';
    const text = `📋 Inspecție Rapidă: Matcă ${matca} | Puiet/Ouă: ${oua} | Botci: ${botci}${rameInfo}.`;
    const fd = new FormData(); fd.append('action','save_note'); fd.append('stup', currentChipName); fd.append('text', text);
    smartFetch(fd).then(() => { toast('Raport inspecție salvat!', 'success'); renderJurnal(); renderMiniJurnalModal(); });
}

function calcVarroa() {
    const c = parseInt(document.getElementById('v-count')?.value);
    const d = parseInt(document.getElementById('v-days')?.value);
    const resultEl = document.getElementById('varroa-result');
    if (!c || !d || c <= 0 || d <= 0) {
        if (resultEl) resultEl.innerHTML = '';
        return toast('Introdu date valide!', 'warning');
    }
    const rate = parseFloat((c / d).toFixed(1));
    const month = new Date().getMonth() + 1;

    // Praguri conform normelor COLOSS / ANSVSA:
    // - Fără puiet (iarnă, oct-feb): tratament necesar la > 1 cădere/zi
    // - Sezon activ (mar-sep): tratament necesar la > 3-5 căderi/zi
    const isWinter = [10,11,12,1,2].includes(month);
    const threshold = isWinter ? 1 : 3;
    const needsTreatment = rate > threshold;

    // Estimare populație acarieni în colonie:
    // Fără puiet: căderi/zi × ~100-150; Cu puiet: căderi/zi × ~50 (mulți sunt în puiet căpăcit)
    const multiplier = isWinter ? 125 : 50;
    const estimatedMites = Math.round(rate * multiplier);
    const infestPct = isWinter
        ? ((estimatedMites / 30000) * 100).toFixed(1)  // ~30.000 albine iarna
        : ((estimatedMites / 50000) * 100).toFixed(1); // ~50.000 albine vara

    let msg = `🦠 Verificare Varroa: ${c} căzuți în ${d} zile. Rata: ${rate}/zi. `;
    msg += `Estimat ~${estimatedMites} acarieni în colonie (≈${infestPct}% infestare). `;

    if (needsTreatment) {
        const urgency = rate > threshold * 3 ? '🚨 TRATAMENT URGENT!' : '⚠️ NECESITĂ TRATAMENT!';
        msg += urgency;
        msg += isWinter ? ' Tratament cu acid oxalic recomandat (fără puiet).' : ' Tratament după recoltă recomandat.';
        const fdT = new FormData();
        fdT.append('action','save_task');
        fdT.append('stup', currentChipName);
        fdT.append('text', `TRATAMENT VARROA - ${currentChipName} (Rată: ${rate}/zi, ~${estimatedMites} acarieni)`);
        fdT.append('date', new Date().toLocaleDateString('ro-RO'));
        smartFetch(fdT);
    } else {
        msg += `✅ Infestare sub control (prag ${isWinter?'iarnă':'sezon activ'}: ${threshold}/zi).`;
    }

    const fd = new FormData();
    fd.append('action','save_note');
    fd.append('stup', currentChipName);
    fd.append('text', msg);
    smartFetch(fd).then(() => {
        toast(msg.substring(0, 120) + '...', needsTreatment ? 'warning' : 'success');
        if (document.getElementById('v-count')) {
            document.getElementById('v-count').value = '';
            document.getElementById('v-days').value  = '';
        }
        renderJurnal();
        renderMiniJurnalModal();
    });
}

function executeTransfer() {
    const target  = document.getElementById('transfer-target')?.value;
    const resursa = document.getElementById('transfer-item')?.value;
    if (!target || target === currentChipName) return toast('Selectează un stup destinație valid!', 'warning');
    const fd = new FormData(); fd.append('action','transfer_resources'); fd.append('source', currentChipName); fd.append('target', target); fd.append('resource', resursa);
    smartFetch(fd).then(() => { toast('Transfer de resurse realizat!', 'success'); if (document.getElementById('transfer-target')) document.getElementById('transfer-target').value = ''; renderJurnal(); renderMiniJurnalModal(); });
}

/* ════════════════════════════════════════
   RECOLTĂ & ROI
   ════════════════════════════════════════ */
function saveHarvest() {
    const s = document.getElementById('h-stup')?.value, k = document.getElementById('h-kg')?.value;
    const t = document.getElementById('h-tip')?.value, p = document.getElementById('h-pret')?.value;
    if (!s) { toast('Selectează stupul!', 'warning'); return; }
    if (!k) { toast('Introdu cantitatea!', 'warning'); return; }
    if (!validateNumeric(k, 0.1, 9999, 'Cantitate kg')) return;
    if (p && !validateNumeric(p, 0, 99999, 'Preț/kg')) return;
    const fd = new FormData(); fd.append('action','save_harvest'); fd.append('stup',s); fd.append('kg',k); fd.append('tip',t); fd.append('pret',p);
    smartFetch(fd).then(() => { if (document.getElementById('h-kg')) { document.getElementById('h-kg').value = ''; document.getElementById('h-pret').value = ''; } renderHarvest(); });
}

function saveExpense() {
    const s   = document.getElementById('e-stup')?.value;
    const sum = document.getElementById('e-suma')?.value;
    const desc = document.getElementById('e-desc')?.value;
    if (!s || !sum) return toast('Completează stupul și suma!', 'warning');
    const fd = new FormData(); fd.append('action','save_expense'); fd.append('stup',s); fd.append('suma',sum); fd.append('desc',desc);
    smartFetch(fd).then(() => { if (document.getElementById('e-suma')) { document.getElementById('e-suma').value = ''; document.getElementById('e-desc').value = ''; } renderROI(); });
}

async function renderROI() {
    const [hRes, eRes] = await Promise.all([
        fetch('backend.php?fetch=harvest&t='+Date.now()).then(r=>r.json()),
        fetch('backend.php?fetch=expenses&t='+Date.now()).then(r=>r.json())
    ]);
    // Filtrare pe an
    const hFiltered = filterByYear(hRes, 'date');
    const eFiltered = filterByYear(eRes, 'date');
    // Selector an cheltuieli — injectăm div dacă nu există
    if (!document.getElementById('expense-year-selector')) {
        const roiList = document.getElementById('roi-list');
        if (roiList) {
            const div = document.createElement('div');
            div.id = 'expense-year-selector';
            div.style.cssText = 'margin-bottom:12px;';
            roiList.parentNode.insertBefore(div, roiList);
        }
    }
    const eYears = getYearOptions(eRes, 'date');
    renderYearSelector('expense-year-selector', eYears, renderROI);
    let stats = {}, globalIncome = 0, globalExpense = 0;
    hFiltered.forEach(h => { if (!stats[h.stup]) stats[h.stup]={income:0,expense:0}; const inc = parseFloat(h.kg)*parseFloat(h.pret||0); stats[h.stup].income+=inc; globalIncome+=inc; });
    eFiltered.forEach(e => { if (!stats[e.stup]) stats[e.stup]={income:0,expense:0}; const exp = parseFloat(e.suma); stats[e.stup].expense+=exp; globalExpense+=exp; });
    if (document.getElementById('stat-total-venit')) document.getElementById('stat-total-venit').innerText = globalIncome.toFixed(0);
    if (document.getElementById('stat-total-cost'))  document.getElementById('stat-total-cost').innerText  = globalExpense.toFixed(0);
    const gp = globalIncome - globalExpense;
    if (document.getElementById('stat-total-profit')) document.getElementById('stat-total-profit').innerText = gp.toFixed(0) + " lei";
    const roiPerc = globalExpense > 0 ? ((gp/globalExpense)*100).toFixed(1) : 0;
    if (document.getElementById('stat-total-roi-perc')) document.getElementById('stat-total-roi-perc').innerText = `ROI Global: ${roiPerc}%`;
    let html = '<table style="width:100%;text-align:left;border-collapse:collapse;font-size:0.9rem;"><tr><th style="padding:8px;border-bottom:2px solid #eee;">Stup</th><th style="padding:8px;border-bottom:2px solid #eee;">Venit</th><th style="padding:8px;border-bottom:2px solid #eee;">Cost</th><th style="padding:8px;border-bottom:2px solid #eee;">Profit</th></tr>';
    for (let s in stats) {
        const profit = stats[s].income - stats[s].expense;
        html += `<tr><td style="padding:8px;border-bottom:1px solid #eee;">${s}</td><td style="padding:8px;border-bottom:1px solid #eee;">${stats[s].income.toFixed(0)} lei</td><td style="padding:8px;border-bottom:1px solid #eee;">${stats[s].expense.toFixed(0)} lei</td><td style="padding:8px;border-bottom:1px solid #eee;font-weight:800;color:${profit>=0?'var(--accent-green)':'var(--accent-red)'}">${profit.toFixed(0)} lei</td></tr>`;
    }
    if (document.getElementById('roi-list')) document.getElementById('roi-list').innerHTML = html + '</table>';
    renderExpenseList(eFiltered);
    harvestDataLocal = hRes; expensesDataLocal = eRes;
    renderHarvestPredictionAdvanced();
    renderProductivityIndex(hFiltered);
    renderCostPerKg();
}

function renderExpenseList(list) {
    let html = '<table style="width:100%;border-collapse:collapse;text-align:left;font-size:0.85rem;"><tr><th style="padding:8px;border-bottom:2px solid #eee;">Data</th><th style="padding:8px;border-bottom:2px solid #eee;">Stup</th><th style="padding:8px;border-bottom:2px solid #eee;">Descriere</th><th style="padding:8px;border-bottom:2px solid #eee;">Suma</th><th></th></tr>';
    list.slice().reverse().forEach(e => { html += `<tr><td style="padding:8px;border-bottom:1px solid #eee;">${e.date}</td><td style="padding:8px;border-bottom:1px solid #eee;"><b>${e.stup}</b></td><td style="padding:8px;border-bottom:1px solid #eee;">${e.desc}</td><td style="padding:8px;border-bottom:1px solid #eee;"><b>${e.suma} lei</b></td><td style="padding:8px;border-bottom:1px solid #eee;text-align:right;"><button onclick="deleteExpense('${e.id}')" style="background:none;border:none;color:var(--accent-red);cursor:pointer;font-weight:bold;">✕</button></td></tr>`; });
    if (document.getElementById('expense-history-list')) document.getElementById('expense-history-list').innerHTML = html + '</table>';
}

function deleteExpense(id) { if(confirm("Ștergi această cheltuială?")) { const fd = new FormData(); fd.append('action','delete_expense'); fd.append('id',id); smartFetch(fd).then(() => renderROI()); } }

function getYearOptions(list, field) {
    // Extrage anii unici dintr-o listă de obiecte cu câmp dată "dd.mm.yyyy"
    const years = new Set();
    list.forEach(item => {
        const d = (item[field] || item.date || '');
        const parts = d.split('.');
        if (parts.length >= 3) years.add(parts[2].trim().substring(0,4));
    });
    return Array.from(years).sort((a,b) => b-a); // descrescător
}

function renderYearSelector(containerId, years, onChange) {
    const wrap = document.getElementById(containerId);
    if (!wrap) return;
    const currentYearStr = String(_selectedYear);
    wrap.innerHTML = `<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="font-size:0.8rem;font-weight:800;color:var(--text-muted);">📅 An:</span>
        ${years.map(y => `<button onclick="setYear(${y},'${containerId}')" style="padding:5px 14px;border-radius:20px;border:1.5px solid ${y===currentYearStr?'var(--honey)':'var(--wood-light)'};background:${y===currentYearStr?'var(--honey)':'transparent'};color:${y===currentYearStr?'#fff':'var(--premium-brown)'};font-weight:800;cursor:pointer;font-size:0.85rem;font-family:inherit;">${y}</button>`).join('')}
        <button onclick="setYear('all','${containerId}')" style="padding:5px 14px;border-radius:20px;border:1.5px solid ${'all'===String(_selectedYear)?'var(--honey)':'var(--wood-light)'};background:${'all'===String(_selectedYear)?'var(--honey)':'transparent'};color:${'all'===String(_selectedYear)?'#fff':'var(--premium-brown)'};font-weight:800;cursor:pointer;font-size:0.85rem;font-family:inherit;">Toți</button>
    </div>`;
}

function setYear(year, containerId) {
    _selectedYear = year;
    renderHarvest();
    renderROI();
}

function filterByYear(list, field) {
    if (_selectedYear === 'all') return list;
    return list.filter(item => {
        const d = (item[field] || item.date || '');
        const parts = d.split('.');
        return parts.length >= 3 && parts[2].trim().startsWith(String(_selectedYear));
    });
}

function renderHarvest() {
    const filterStup = document.getElementById('h-filter-stup')?.value || '';
    fetch('backend.php?fetch=harvest&t='+Date.now()).then(r=>r.json()).then(list => {
        harvestDataLocal = list;
        // Selector an
        const years = getYearOptions(list, 'date');
        // Injectăm div selector dacă nu există
        if (!document.getElementById('harvest-year-selector')) {
            const harvestList = document.getElementById('harvest-list');
            if (harvestList) {
                const div = document.createElement('div');
                div.id = 'harvest-year-selector';
                div.style.cssText = 'margin-bottom:12px;';
                harvestList.parentNode.insertBefore(div, harvestList);
            }
        }
        renderYearSelector('harvest-year-selector', years, renderHarvest);
        // Filtrare
        let filtered = filterByYear(list, 'date');
        if (filterStup) filtered = filtered.filter(h=>h.stup===filterStup);
        let totalRON=0, totalKG=0; const tipuri={};
        const html = filtered.map(h => {
            const ron = parseFloat(h.kg)*parseFloat(h.pret||0); totalRON+=ron; totalKG+=parseFloat(h.kg); tipuri[h.tip]=(tipuri[h.tip]||0)+parseFloat(h.kg);
            return `<div class="card-box" style="padding:14px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;"><div><b>${h.stup}</b>: ${h.kg}kg ${h.tip} <span style="color:var(--accent-green);font-weight:800">(${ron.toFixed(0)} RON)</span><br><small style="opacity:0.6">${h.date}</small></div>${window.isAdmin?`<button onclick="deleteHarvest('${h.id}')" style="background:none;border:none;color:var(--accent-red);cursor:pointer;font-size:1.2rem;font-weight:800;">✕</button>`:''}</div>`;
        }).join('');
        const yearLabel = _selectedYear === 'all' ? 'Toți anii' : `An ${_selectedYear}`;
        if (document.getElementById('harvest-list')) document.getElementById('harvest-list').innerHTML = `<h4 style="color:var(--wood-dark)">📊 ${yearLabel} | ${filterStup||'Toți stupii'}: ${totalKG.toFixed(1)} kg | Venit: ${totalRON.toFixed(0)} RON</h4>` + (html || `<p style="opacity:0.6;text-align:center;">Nicio recoltă în ${yearLabel}.</p>`);
        renderROI();
    });
}

function renderModalHarvest() {
    fetch('backend.php?fetch=harvest&t='+Date.now()).then(r=>r.json()).then(list => {
        const stupHarvest = list.filter(h=>h.stup===currentChipName); let totalKG=0; const tipuri={};
        const html = stupHarvest.map(h => { totalKG+=parseFloat(h.kg); tipuri[h.tip]=(tipuri[h.tip]||0)+parseFloat(h.kg); return `<div style="padding:10px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;"><span>${h.date} - <b>${h.tip}</b></span><span style="font-weight:800;color:var(--accent-green);">${h.kg} kg</span></div>`; }).join('');
        if (document.getElementById('modal-harvest-list')) document.getElementById('modal-harvest-list').innerHTML = html || "<p style='opacity:0.6'>Nicio recoltă înregistrată pentru acest stup.</p>";
        deseneazaPieChartModal(tipuri);
    });
}

function deseneazaPieChartModal(dateGrupate) {
    const ctx = document.getElementById('harvestPieChartModal')?.getContext('2d');
    if (!ctx) return;
    const etichete = Object.keys(dateGrupate), valori = Object.values(dateGrupate);
    if (harvestPieModalObj) harvestPieModalObj.destroy();
    if (valori.length === 0) { harvestPieModalObj = new Chart(ctx,{type:'doughnut',data:{labels:['Fără Date'],datasets:[{data:[1],backgroundColor:['#eee']}]},options:{maintainAspectRatio:false}}); return; }
    if (harvestPieModalObj) { try { harvestPieModalObj.destroy(); } catch(e) {} harvestPieModalObj = null; }
    harvestPieModalObj = new Chart(ctx,{type:'doughnut',data:{labels:etichete,datasets:[{data:valori,backgroundColor:['#f1c40f','#e67e22','#1abc9c','#3498db','#9b59b6'],borderWidth:2,borderColor:'#fff'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'right'}}}});
}

function deleteHarvest(id) { showConfirmModal({ title: _lang==='en'?'🗑️ Delete harvest':'🗑️ Șterge Recolta', message: t('confirm_delete_harvest'), confirmText: t('btn_delete'), type:'danger', onConfirm:()=>{ const fd=new FormData(); fd.append('action','delete_harvest'); fd.append('id',id); smartFetch(fd).then(()=>renderHarvest()); } }); }

/* ════════════════════════════════════════
   INVENTAR
   ════════════════════════════════════════ */
let editingInventoryId = null;

function saveInventory() {
    const i = document.getElementById('i-item')?.value, q = document.getElementById('i-qty')?.value;
    const t = document.getElementById('i-type')?.value, cat = document.getElementById('i-cat')?.value;
    if (!i || !q) return;
    const fd = new FormData();
    if (editingInventoryId) { fd.append('action','edit_inventory'); fd.append('id', editingInventoryId); }
    else fd.append('action','save_inventory');
    fd.append('item',i); fd.append('qty',q); fd.append('type',t); fd.append('category',cat);
    smartFetch(fd).then(() => { document.getElementById('i-item').value=''; document.getElementById('i-qty').value=''; editingInventoryId=null; document.getElementById('btn-save-inv').innerText='Adaugă în Stoc'; renderInventory(); });
}

function editInventory(id, item, qty, type, cat) {
    document.getElementById('i-item').value=item; document.getElementById('i-qty').value=qty;
    document.getElementById('i-type').value=type; document.getElementById('i-cat').value=cat;
    editingInventoryId=id; document.getElementById('btn-save-inv').innerText='Salvează Modificarea';
    document.getElementById('i-item').scrollIntoView({behavior:'smooth',block:'center'});
}

function renderInventory() {
    fetch('backend.php?fetch=inventory').then(r=>r.json()).then(list => {
        const categorii = {"Tratamente&Hrana":[], "Unelte":[], "Cutii&Rame":[]};
        list.forEach(i => { const cat=i.category||"Unelte"; if(categorii[cat]) categorii[cat].push(i); else categorii["Unelte"].push(i); });
        let html='';
        for (let numeCat in categorii) {
            if (categorii[numeCat].length > 0) {
                const title = numeCat.replace('&', ' & ');
                html += `<div class="inventory-section open"><h4 onclick="this.parentElement.classList.toggle('open')"><span>📦 ${title}</span><span>▼</span></h4><div class="inventory-list-content">${categorii[numeCat].map(i=>`<div class="card-box" style="padding:14px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;border:1px solid var(--wood-light);box-shadow:none;"><div><b>${i.item}</b>: ${i.qty} ${i.type}</div><div><button class="edit-btn" onclick="editInventory('${i.id}','${i.item.replace(/'/g,"\\'")}','${i.qty}','${i.type}','${i.category}')">✏️</button><button onclick="deleteInventory('${i.id}')" style="background:none;border:none;color:var(--accent-red);cursor:pointer;font-weight:800;">✕</button></div></div>`).join('')}</div></div>`;
            }
        }
        if (document.getElementById('inventory-list')) document.getElementById('inventory-list').innerHTML = html || "<p style='text-align:center;opacity:0.6;'>Inventarul este gol.</p>";
    });
}

function deleteInventory(id) { showConfirmModal({ title: _lang==='en'?'🗑️ Delete item':'🗑️ Șterge Articol', message: t('confirm_delete_harvest'), confirmText: t('btn_delete'), type:'danger', onConfirm:()=>{ const fd=new FormData(); fd.append('action','delete_inventory'); fd.append('id',id); smartFetch(fd).then(renderInventory); } }); }

/* ════════════════════════════════════════
   GRAFICE ISTORIC
   ════════════════════════════════════════ */
function fetchHistory() {
    if (!currentChipID || currentChipID.toString().startsWith('M')) { drawEmptyChart(); return; }
    fetch(`history.php?chipID=${currentChipID}&t=${Date.now()}`).then(r=>r.json()).then(data => {
        let rows = Array.isArray(data) ? data : (data.data || []);
        if (!rows?.length) { drawEmptyChart(); return; }
        const cleanRows = rows.filter(r=>r.weight>1).sort((a,b)=>a.ts-b.ts);
        if (!cleanRows.length) { drawEmptyChart(); return; }
        const latestTs = cleanRows[cleanRows.length-1].ts, cutoff = latestTs - (currentRange*86400);
        const filtered = cleanRows.filter(r=>r.ts>=cutoff);
        const labels = filtered.map(r => { const d=new Date(r.ts*1000); if(currentRange===1) return d.getHours().toString().padStart(2,'0')+':'+d.getMinutes().toString().padStart(2,'0'); if(currentRange===7) return d.toLocaleDateString('ro-RO',{day:'numeric',month:'short'}); return d.toLocaleDateString('ro-RO',{day:'numeric',month:'short',year:'2-digit'}); });
        const vals = filtered.map(r=>currentTab==='W'?r.weight:(r.battery||r.bat||0));
        const ctx = document.getElementById('histChart')?.getContext('2d');
        if (!ctx) return;
        const col = currentTab==='W'?'166, 132, 92':'16, 172, 132';
        if (chartObj) chartObj.destroy();
        const isWeight  = currentTab === 'W';
        const yStepSize = isWeight ? 0.1 : 0.25;
        chartObj = new Chart(ctx,{type:'line',data:{labels,datasets:[{data:vals,borderColor:`rgb(${col})`,backgroundColor:`rgba(${col},0.1)`,fill:true,tension:0.35,borderWidth:4,pointRadius:filtered.length>50?0:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:false,ticks:{stepSize:yStepSize}},x:{ticks:{autoSkip:true,maxTicksLimit:12}}}}});
    }).catch(() => drawEmptyChart());
}

function drawEmptyChart() {
    const ctx = document.getElementById('histChart')?.getContext('2d');
    if (!ctx) return;
    if (chartObj) chartObj.destroy();
    chartObj = new Chart(ctx,{type:'line',data:{labels:['Fără Date'],datasets:[{data:[0],borderColor:'#ccc'}]},options:{maintainAspectRatio:false}});
}

function changeTab(t, b) { currentTab=t; b.parentElement.querySelectorAll('.seg-btn').forEach(el=>el.classList.remove('active')); b.classList.add('active'); fetchHistory(); }
function changeRange(r, b) { currentRange=r; b.parentElement.querySelectorAll('.seg-btn').forEach(el=>el.classList.remove('active')); b.classList.add('active'); fetchHistory(); }

/* ════════════════════════════════════════
   COMPARAȚIE STUPI
   ════════════════════════════════════════ */
async function compareHives() {
    const s1=document.getElementById('comp-sel-1'), s2=document.getElementById('comp-sel-2');
    const id1=s1?.options[s1.selectedIndex]?.getAttribute('data-id'), id2=s2?.options[s2.selectedIndex]?.getAttribute('data-id');
    if (!id1||!id2) return toast('Selectează doi stupi pentru comparație!', 'warning');
    if (id1.startsWith('M')||id2.startsWith('M')) return toast('Comparația nu e disponibilă pentru stupi manuali!', 'warning');
    try {
        const [r1,r2]=await Promise.all([fetch(`history.php?chipID=${id1}&t=${Date.now()}`).then(r=>r.json()).catch(()=>[]),fetch(`history.php?chipID=${id2}&t=${Date.now()}`).then(r=>r.json()).catch(()=>[])]);
        const c1=(Array.isArray(r1)?r1:(r1.data||[])).filter(r=>r.weight>1).sort((a,b)=>a.ts-b.ts);
        const c2=(Array.isArray(r2)?r2:(r2.data||[])).filter(r=>r.weight>1).sort((a,b)=>a.ts-b.ts);
        if (!c1.length||!c2.length) { toast('Unul din stupi nu are date de comparație!', 'warning'); return; }
        const latestTs=Math.max(c1[c1.length-1].ts,c2[c2.length-1].ts), cutoff=latestTs-(7*86400);
        const f1=c1.filter(r=>r.ts>=cutoff), f2=c2.filter(r=>r.ts>=cutoff);
        const labels=f1.map(r=>new Date(r.ts*1000).toLocaleDateString('ro-RO',{day:'numeric',month:'short'}));
        const ctx=document.getElementById('compareChart')?.getContext('2d');
        if (!ctx) return;
        if (compareChartObj) compareChartObj.destroy();
        compareChartObj=new Chart(ctx,{type:'line',data:{labels,datasets:[{label:s1.value,data:f1.map(r=>r.weight),borderColor:'var(--accent-green)',tension:0.3,borderWidth:3},{label:s2.value,data:f2.map(r=>r.weight),borderColor:'#e74c3c',tension:0.3,borderWidth:3}]},options:{responsive:true,maintainAspectRatio:false}});
    } catch (e) { toast(_lang==='en'?'Chart generation error!':'Eroare la generare comparație!', 'error'); }
}

/* ════════════════════════════════════════
   BELL TASK MENU
   ════════════════════════════════════════ */
function showBellTaskMenu(e, hiveTasks) {
    e.stopPropagation();
    // Popup modal centrat în loc de dropdown + confirm()
    let overlay = document.getElementById('bell-task-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'bell-task-overlay';
        overlay.style.cssText = 'display:none;position:fixed;inset:0;z-index:9800;background:rgba(26,31,38,0.65);backdrop-filter:blur(4px);align-items:center;justify-content:center;';
        overlay.innerHTML = `<div style="background:var(--cream,#fdfbf7);width:92%;max-width:440px;border-radius:22px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
            <div style="background:linear-gradient(135deg,var(--wood-dark,#a6845c),var(--honey,#d4860b));padding:18px 22px;display:flex;align-items:center;justify-content:space-between;">
                <span style="font-weight:900;font-size:1rem;color:#fff;" id="btm-title">🔔 Sarcini în așteptare</span>
                <button onclick="document.getElementById('bell-task-overlay').style.display='none'" style="background:rgba(255,255,255,0.2);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;">✕</button>
            </div>
            <div id="btm-list" style="padding:16px;max-height:340px;overflow-y:auto;"></div>
            <div style="padding:12px 16px;border-top:1px solid var(--wood-light);text-align:right;">
                <button onclick="document.getElementById('bell-task-overlay').style.display='none'" style="padding:9px 20px;background:#f5f0e8;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;color:var(--premium-brown);">${_lang==='en'?'Close':'Închide'}</button>
            </div>
        </div>`;
        overlay.onclick = ev => { if (ev.target === overlay) overlay.style.display='none'; };
        document.body.appendChild(overlay);
    }

    // Titlu cu numărul de sarcini și primul stup
    const hiveName = (hiveTasks[0]?.text?.match(/-\s*(.*)$/) || [])[1]?.trim() || '';
    document.getElementById('btm-title').textContent = `🔔 ${hiveName ? hiveName + ' — ' : ''}${hiveTasks.length} ${_lang==='en'?'pending task(s)':'sarcini în așteptare'}`;

    // Lista sarcinilor
    let html = '';
    hiveTasks.forEach(task => {
        const safeText = task.text.replace(/'/g, "\'");
        html += `<div style="padding:12px 14px;margin-bottom:8px;background:#fff;border:1.5px solid var(--wood-light);border-radius:12px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:800;font-size:0.88rem;color:var(--text-dark);margin-bottom:3px;">${task.text}</div>
                <div style="font-size:0.75rem;color:var(--text-muted);">${task.date||''}</div>
            </div>
            <button onclick="
                document.getElementById('bell-task-overlay').style.display='none';
                showConfirmModal({
                    title: '${_lang==='en'?'Complete task':'Rezolvă Sarcina'}',
                    message: '${safeText}',
                    confirmText: '${_lang==='en'?'Mark done':'Marchează Efectuat'}',
                    type: 'success',
                    onConfirm: () => resolveTask('${task.id}')
                });"
                style="flex-shrink:0;background:linear-gradient(135deg,#0e8c6e,#10ac84);color:#fff;border:none;padding:8px 14px;border-radius:9px;font-family:inherit;font-weight:800;font-size:0.82rem;cursor:pointer;white-space:nowrap;">
                ✅ ${_lang==='en'?'Done':'Rezolvă'}
            </button>
        </div>`;
    });
    document.getElementById('btm-list').innerHTML = html;
    overlay.style.display = 'flex';
}

/* ════════════════════════════════════════
   REGINA — TIMELINE
   ════════════════════════════════════════ */
/* ============================================================
   ISTORIC REGINA
   ============================================================ */
async function renderQueenHistory() {
    const list = document.getElementById('queen-history-list');
    if (!list) return;
    list.innerHTML = '<p style="opacity:0.5;text-align:center;padding:10px;">Se încarcă...</p>';
    const data = await fetch('backend.php?fetch=queen&t='+Date.now()).then(r=>r.json()).catch(()=>[]);
    const hiveEvents = data.filter(e => String(e.chipID) === String(currentChipID));
    if (!hiveEvents.length) {
        list.innerHTML = '<p style="opacity:0.6;text-align:center;padding:20px;">Niciun eveniment înregistrat.</p>';
        return;
    }
    const colors = { 'Inregistrare':'var(--accent-green)','Schimbare':'#3498db','Roire':'#e67e22','Pierdere':'#e74c3c','Botci':'#f39c12','Artificiu':'#8e44ad' };
    const emojis = { 'Inregistrare':'📋','Schimbare':'🔄','Roire':'🐝','Pierdere':'⚠️','Botci':'🏠','Artificiu':'🧪' };
    list.innerHTML = '<div style="display:flex;flex-direction:column;gap:8px;">' +
        hiveEvents.map(e => {
            const c = colors[e.event] || '#95a5a6';
            const em = emojis[e.event] || '👑';
            return `<div style="background:var(--white,#fff);border:1.5px solid ${c};border-left:4px solid ${c};border-radius:10px;padding:10px 12px;">`
                + `<div style="display:flex;justify-content:space-between;align-items:center;">`
                + `<div><span style="font-weight:900;font-size:0.88rem;color:${c};">${em} ${e.event}</span>`
                + (e.breed ? `<span style="margin-left:8px;font-size:0.75rem;background:rgba(0,0,0,0.06);padding:2px 7px;border-radius:10px;font-weight:700;">Rasă: ${e.breed}</span>` : '')
                + (e.year  ? `<span style="margin-left:4px;font-size:0.75rem;background:rgba(0,0,0,0.06);padding:2px 7px;border-radius:10px;font-weight:700;">An: ${e.year}</span>` : '')
                + `</div>`
                + `<button onclick="deleteQueenEvent('${e.id}')" style="background:none;border:none;color:var(--accent-red);cursor:pointer;font-size:0.9rem;">✕</button>`
                + `</div>`
                + (e.notes ? `<div style="margin-top:5px;font-size:0.8rem;color:var(--text-muted);">${e.notes}</div>` : '')
                + `<div style="margin-top:4px;font-size:0.7rem;opacity:0.5;">${e.date} — ${e.user}</div>`
                + `</div>`;
        }).join('') + '</div>';
}

function saveQueenEvent() {
    const event = document.getElementById('qh-event')?.value || 'Inregistrare';
    const breed = document.getElementById('qh-breed')?.value?.trim() || '';
    const year  = document.getElementById('qh-year')?.value?.trim()  || '';
    const notes = document.getElementById('qh-notes')?.value?.trim() || '';
    const fd = new FormData();
    fd.append('action', 'save_queen');
    fd.append('chipID', currentChipID);
    fd.append('event',  event);
    fd.append('breed',  breed);
    fd.append('year',   year);
    fd.append('notes',  notes);
    smartFetch(fd).then(() => {
        document.getElementById('qh-breed').value = '';
        document.getElementById('qh-year').value  = '';
        document.getElementById('qh-notes').value = '';
        toast('👑 Eveniment salvat!', 'success');
        renderQueenHistory();
    });
}

function resetWeightBase() {
    const hive = hivesDataLocal.find(h => String(h.chipID) === String(currentChipID));
    if (!hive) return;
    const w = parseFloat(hive.weight) || 0;
    if (w <= 0) { toast('Nu există greutate curentă!', 'error'); return; }
    showConfirmModal({
        title: '⚖️ Reset Bază Greutate',
        message: `Greutatea curentă (${w.toFixed(1)} kg) devine noua referință. Delta de roire se calculează de la aceasta valoare la următoarea citire.`,
        confirmText: 'Confirmă',
        type: 'success',
        onConfirm: () => {
            const fd = new FormData();
            fd.append('action', 'save_metadata');
            fd.append('chipID', currentChipID);
            fd.append('weightRef', w.toFixed(3));
            smartFetch(fd).then(() => {
                // Resetam delta24 local imediat pe hive
                const hiveLocal = hivesDataLocal.find(h => String(h.chipID) === String(currentChipID));
                if (hiveLocal) { hiveLocal.delta24 = 0; hiveLocal.deltaDay = 0; }
                // Curatam alerta roi din sessionStorage
                const aID = String(currentChipID) + '_roi';
                _roiResolvedInSession.delete(aID);
                toast('✅ Bază resetată la ' + w.toFixed(1) + ' kg', 'success');
                renderDashboard();
            });
        }
    });
}

function deleteQueenEvent(id) {
    showConfirmModal({
        title: '🗑️ Șterge eveniment',
        message: 'Sigur vrei să ștergi?',
        confirmText: 'Șterge', type: 'danger',
        onConfirm: () => {
            const fd = new FormData();
            fd.append('action', 'delete_queen');
            fd.append('id', id);
            smartFetch(fd).then(() => renderQueenHistory());
        }
    });
}


/* ════════════════════════════════════════════════════════
   1. CALENDAR PONTĂ MATCĂ
   ════════════════════════════════════════════════════════ */
function renderBroodCalendar() {
    const el = document.getElementById('brood-calendar-container');
    if (!el) return;

    const hiveNotes = jurnalDataLocal.filter(n =>
        n.stup === currentChipName &&
        (n.text || '').toLowerCase().includes('puiet')
    ).sort((a, b) => {
        const pd = s => { const p=(s||'').split(' ')[0].split('.'); return p.length>=3?new Date(p[2],p[1]-1,p[0]).getTime():0; };
        return pd(b.date) - pd(a.date);
    });

    const lastNote = hiveNotes[0];
    const today = new Date();
    const savedDate = localStorage.getItem('brood_' + currentChipID) || '';
    const savedType = localStorage.getItem('brood_type_' + currentChipID) || 'worker';

    el.innerHTML = `
    <div style="padding:4px;">
        <div class="card-box" style="margin-bottom:12px;">
            <div style="font-weight:800;font-size:0.9rem;color:var(--premium-brown);margin-bottom:10px;">🥚 Calendar Dezvoltare Puiet</div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px;">
                <select id="brood-type-select" onchange="localStorage.setItem('brood_type_${currentChipID}',this.value);calcBroodCalendar()"
                    style="padding:8px 12px;border:1.5px solid var(--wood-light);border-radius:8px;font-family:inherit;font-size:0.88rem;background:var(--cream);color:var(--text-dark);">
                    <option value="worker"  ${savedType==='worker' ?'selected':''}>🐝 Lucrătoare (21 zile)</option>
                    <option value="drone"   ${savedType==='drone'  ?'selected':''}>🐝 Trântor (24 zile)</option>
                    <option value="queen"   ${savedType==='queen'  ?'selected':''}>👑 Matcă (16 zile)</option>
                </select>
                <input type="date" id="brood-date-input" value="${savedDate || today.toISOString().split('T')[0]}"
                    style="padding:8px 12px;border:1.5px solid var(--wood-light);border-radius:8px;font-family:inherit;font-size:0.88rem;">
                <button onclick="calcBroodCalendar()" style="padding:8px 16px;background:var(--honey,#d4860b);border:none;border-radius:8px;font-family:inherit;font-weight:800;cursor:pointer;color:#fff;">
                    📅 Calculează
                </button>
            </div>
            ${lastNote ? `<div style="font-size:0.75rem;color:var(--text-muted);">Ultima inspecție cu puiet: <b>${lastNote.date}</b></div>` : ''}
        </div>
        <div id="brood-timeline"></div>
    </div>`;

    if (savedDate) calcBroodCalendar();
}

function calcBroodCalendar() {
    const inp      = document.getElementById('brood-date-input');
    const typeEl   = document.getElementById('brood-type-select');
    if (!inp) return;
    const startDate = new Date(inp.value);
    if (isNaN(startDate)) return;

    const broodType = typeEl?.value || 'worker';
    localStorage.setItem('brood_' + currentChipID, inp.value);
    localStorage.setItem('brood_type_' + currentChipID, broodType);

    // Cicluri corecte biologic:
    // Lucrătoare: 3 ou + 6 larvă deschisă + 12 larvă căpăcită (pupă) = 21 zile
    // Trântor:    3 ou + 7 larvă deschisă + 14 larvă căpăcită (pupă) = 24 zile
    // Matcă:      3 ou + 5 larvă deschisă + 8  larvă căpăcită (pupă) = 16 zile
    const CYCLES = {
        worker: {
            total: 21,
            stages: [
                { day: 0,  label: '🥚 Ouăt',            desc: 'Matca depune oul fecundat. Oul stă vertical 3 zile.', color: '#f39c12', duration: 3 },
                { day: 3,  label: '🐛 Larvă deschisă',  desc: 'Larva se hrănește cu lăptișor (primele 3 zile), apoi cu amestec de miere și polen.', color: '#27ae60', duration: 6 },
                { day: 9,  label: '🏠 Larvă căpăcită',  desc: 'Celula e căpăcită cu ceară perforabilă. Larva se transformă în prepupă (zi 9-12), apoi pupă (zi 12-21).', color: '#8e44ad', duration: 12 },
                { day: 21, label: '🐝 Eclozare',        desc: 'Albina adultă roade capacul și iese. Primele 3 săptămâni de viață le petrece în stup (doică, clăditoare).', color: '#c8860a', duration: 0 },
            ]
        },
        drone: {
            total: 24,
            stages: [
                { day: 0,  label: '🥚 Ouăt',            desc: 'Matca depune oul nefecundat (trântorul e haploid). Oul stă vertical 3 zile.', color: '#f39c12', duration: 3 },
                { day: 3,  label: '🐛 Larvă deschisă',  desc: 'Larva se hrănește mai mult decât lucrătoarea — trântorii sunt mai mari. 7 zile de larvă deschisă.', color: '#27ae60', duration: 7 },
                { day: 10, label: '🏠 Larvă căpăcită',  desc: 'Capacul trântorului este bombat (convex), distinctiv față de capacul plat al lucrătoarelor. 14 zile de pupă.', color: '#8e44ad', duration: 14 },
                { day: 24, label: '🐝 Trântor adult',   desc: 'Trântorul eclozează. Trăiește 50-90 zile. Util pentru diagnosticul Varroa: acarianul preferă puietul de trântor (8-10×).', color: '#c8860a', duration: 0 },
            ]
        },
        queen: {
            total: 16,
            stages: [
                { day: 0,  label: '🥚 Ouăt',            desc: 'Oul depus în botcă sau larvă transferată la vârsta de max. 24h. Hrănire exclusivă cu lăptișor pe toată durata.', color: '#f39c12', duration: 3 },
                { day: 3,  label: '🐛 Larvă deschisă',  desc: 'Larva de matcă se îneacă în lăptișor — primește de 10× mai mult lăptișor decât o lucrătoare. 5 zile de larvă.', color: '#27ae60', duration: 5 },
                { day: 8,  label: '🏠 Botcă căpăcită',  desc: 'Botca e căpăcită. Larva se transformă în pupă. Botcă are formă de ghindă, cu vârful în jos.', color: '#8e44ad', duration: 8 },
                { day: 16, label: '👑 Matcă eclozată',  desc: 'Matca tânără iese. Stridulează (tubbing) pentru a localiza rivalele. Zborul de împerechere la 5-7 zile după eclozare.', color: '#c8860a', duration: 0 },
            ]
        }
    };

    const cycle = CYCLES[broodType] || CYCLES.worker;
    const { total, stages } = cycle;
    const today = new Date();
    const daysSinceStart = Math.floor((today - startDate) / 86400000);

    const rows = stages.map((s) => {
        const stageDate = new Date(startDate);
        stageDate.setDate(stageDate.getDate() + s.day);
        const endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + s.day + s.duration);

        const isPast    = today > endDate && s.duration > 0;
        const isCurrent = today >= stageDate && (s.duration === 0 ? Math.abs(daysSinceStart - s.day) <= 1 : today <= endDate);
        const isFuture  = today < stageDate;
        const statusIcon = isPast ? '✅' : isCurrent ? '🔄' : '⏳';
        const bgRgb = s.color === '#c8860a' ? '200,134,10' : s.color === '#27ae60' ? '39,174,96' : s.color === '#8e44ad' ? '142,68,173' : '243,156,18';
        const bg = isCurrent ? `rgba(${bgRgb},0.1)` : 'transparent';

        return `<div style="display:flex;align-items:flex-start;gap:12px;padding:10px 12px;background:${bg};border-radius:10px;margin-bottom:6px;border:1.5px solid ${isCurrent ? s.color : 'var(--wood-light)'};${isFuture?'opacity:0.6':''}">
            <div style="width:36px;height:36px;border-radius:50%;background:${s.color};display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">${s.label.split(' ')[0]}</div>
            <div style="flex:1;">
                <div style="font-weight:800;font-size:0.88rem;color:var(--premium-brown);">${statusIcon} ${s.label}</div>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">${s.desc}</div>
                <div style="font-size:0.72rem;font-weight:700;color:${s.color};margin-top:4px;">
                    ${stageDate.toLocaleDateString('ro-RO')}${s.duration > 0 ? ' → ' + endDate.toLocaleDateString('ro-RO') : ' (Ziua ' + s.day + ')'}
                </div>
            </div>
        </div>`;
    }).join('');

    const daysTillEnd = total - daysSinceStart;
    const progress = Math.min(100, Math.max(0, (daysSinceStart / total) * 100));
    const eclozLabel = daysTillEnd > 0
        ? `${broodType==='queen'?'👑':'🐝'} Eclozare în <b style="color:var(--honey)">${daysTillEnd} zile</b>`
        : daysTillEnd === 0 ? '🎉 Eclozare completă!' : `✅ Ciclu depășit (acum ${Math.abs(daysTillEnd)} zile)`;

    // Fereastră tratament acid oxalic — optimă când nu există puiet căpăcit
    // Puietul căpăcit există din ziua 9 până în ziua total
    // Fereastra fără puiet căpăcit: după eclozare (ziua total+) sau înainte de căpăcire (ziua 0-9)
    const oxalicWindowStart = new Date(startDate);
    oxalicWindowStart.setDate(oxalicWindowStart.getDate() + total + 1);
    const oxalicNote = broodType === 'worker'
        ? `<div style="margin-top:10px;padding:8px 10px;background:rgba(142,68,173,0.08);border-radius:8px;border-left:3px solid #8e44ad;font-size:0.75rem;color:var(--premium-brown);">
            🧪 <b>Fereastră tratament acid oxalic:</b> după ${oxalicWindowStart.toLocaleDateString('ro-RO')} (când nu mai există puiet căpăcit). Eficiență maximă: 95%+.
           </div>`
        : '';

    document.getElementById('brood-timeline').innerHTML = `
        <div class="card-box">
            <div style="font-weight:800;font-size:0.85rem;color:var(--premium-brown);margin-bottom:8px;">${eclozLabel}</div>
            <div style="background:var(--wood-light);border-radius:6px;height:10px;margin-bottom:14px;overflow:hidden;">
                <div style="height:100%;width:${progress}%;background:linear-gradient(90deg,#f39c12,#c8860a);border-radius:6px;transition:width 0.5s;"></div>
            </div>
            ${rows}
            ${oxalicNote}
        </div>`;
}

/* ════════════════════════════════════════════════════════
   2. INDICATOR PUTERE COLONIE
   Se calculeaza per stup si se afiseaza pe card
   ════════════════════════════════════════════════════════ */
function calcColonyStrength(item) {
    const meta = item.meta || {};
    const hiveName = meta.nickname || ('Stup ' + item.chipID);
    const month = new Date().getMonth() + 1; // 1-12

    // Praguri sezoniere: iarna o colonie cu 4 rame e excelentă, vara cu 4 e slabă
    // Primăvară timpurie (feb-mar): prag redus
    // Sezon activ (apr-aug): prag normal
    // Toamnă/iarnă (sep-ian): prag redus
    const isWinter  = [11,12,1,2].includes(month);
    const isEarlySpring = [3].includes(month);
    const strongThresh = isWinter ? 4 : isEarlySpring ? 5 : 8;
    const mediumThresh = isWinter ? 2 : isEarlySpring ? 3 : 5;

    // Cautam ultima inspectie rapida cu Rame Ocupate
    const inspNotes = jurnalDataLocal
        .filter(n => n.stup === hiveName && (n.text||'').includes('Rame Ocupate:'))
        .sort((a, b) => {
            const pd = s => { const p=(s||'').split(' ')[0].split('.'); return p.length>=3?new Date(p[2],p[1]-1,p[0]).getTime():0; };
            return pd(b.date) - pd(a.date);
        });

    if (inspNotes.length > 0) {
        const match = (inspNotes[0].text || '').match(/Rame Ocupate:\s*(\d+)/);
        if (match) {
            const rame = parseInt(match[1]);
            const score = Math.round(Math.min(100, (rame / 10) * 100));
            const puietM = (inspNotes[0].text||'').match(/(\d+)\s*Puiet/);
            const puiet = puietM ? parseInt(puietM[1]) : 0;
            const details = rame + ' rame' + (puiet > 0 ? ', ' + puiet + ' puiet' : '');
            if (rame >= strongThresh) return { score, label: 'Puternică', color: 'var(--accent-green)', emoji: '💪', details };
            if (rame >= mediumThresh) return { score, label: 'Medie', color: '#f39c12', emoji: '📊', details };
            return { score, label: 'Slabă', color: '#ee5253', emoji: '⚠️', details };
        }
    }

    // Fallback fara inspectie: estimare din senzori
    let score = 100;
    if (!item.isManual) {
        if ((item.battery || 0) < 3.4) score -= 15;
        if ((item.delta24 || 0) <= -1.5) score -= 30; // scădere critică
        else if ((item.delta24 || 0) <= -0.5) score -= 15; // scădere semnificativă
        // Bonus pentru cules activ (câștig de greutate)
        if ((item.deltaDay || 0) >= 1.0) score = Math.min(100, score + 10);
        else if ((item.deltaDay || 0) >= 0.3) score = Math.min(100, score + 5);
        if (item.temperature > 0 && (item.temperature < 15 || item.temperature > 39)) score -= 10;
    }
    if (meta.maintenance === true || meta.maintenance === 'true') score -= 5;
    score = Math.max(0, Math.min(100, score));
    if (score >= 80) return { score, label: 'Puternică', color: 'var(--accent-green)', emoji: '💪', details: 'estimat' };
    if (score >= 55) return { score, label: 'Medie', color: '#f39c12', emoji: '📊', details: 'estimat' };
    return { score, label: 'Slabă', color: '#ee5253', emoji: '⚠️', details: 'estimat' };
}

/* ════════════════════════════════════════════════════════
   3. PREDICȚIE RECOLTĂ
   Estimare kg bazată pe deltaDay pozitiv susținut
   ════════════════════════════════════════════════════════ */
function renderHarvestPredictionAdvanced() {
    let el = document.getElementById('harvest-prediction-adv');
    if (!el) {
        el = document.createElement('div');
        el.id = 'harvest-prediction-adv';
        const roiList = document.getElementById('roi-list');
        if (roiList) roiList.parentNode.insertBefore(el, roiList);
        else return;
    }

    const activeCules = hivesDataLocal.filter(h => !h.isManual && (h.deltaDay || 0) > 0.3);
    if (!activeCules.length) { el.innerHTML = ''; return; }

    const totalDayGain = activeCules.reduce((s, h) => s + (h.deltaDay || 0), 0);
    // Factor 0.22: din greutatea brută adusă zilnic, ~20-25% devine miere recoltabilă
    // (restul este apă din nectar care se evaporă + consum colonie + albine moarte)
    const est7  = (totalDayGain * 7  * 0.22).toFixed(1);
    const est14 = (totalDayGain * 14 * 0.22).toFixed(1);

    el.innerHTML = `<div class="card-box" style="border-top-color:var(--accent-orange);margin-bottom:12px;">
        <h4 style="margin:0 0 10px;color:var(--premium-brown);font-size:0.95rem;">🍯 Predicție Recoltă</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
            <div style="text-align:center;padding:10px;background:rgba(243,156,18,0.08);border-radius:10px;">
                <div style="font-size:1.6rem;font-weight:900;color:var(--accent-orange);">${est7} kg</div>
                <div style="font-size:0.72rem;color:var(--text-muted);font-weight:700;">Estimat 7 zile</div>
            </div>
            <div style="text-align:center;padding:10px;background:rgba(243,156,18,0.08);border-radius:10px;">
                <div style="font-size:1.6rem;font-weight:900;color:var(--accent-orange);">${est14} kg</div>
                <div style="font-size:0.72rem;color:var(--text-muted);font-weight:700;">Estimat 14 zile</div>
            </div>
        </div>
        <div style="font-size:0.75rem;color:var(--text-muted);">
            Bazat pe ${activeCules.length} stup${activeCules.length>1?'i':''} activi cu câștig mediu de 
            <b style="color:var(--accent-orange);">+${(totalDayGain/activeCules.length).toFixed(2)} kg/zi</b>. 
            Estimarea presupune culesul activ continuu.
        </div>
    </div>`;
}

/* ════════════════════════════════════════════════════════
   4. COST PER KG MIERE
   ════════════════════════════════════════════════════════ */
/* ════════════════════════════════════════════════════════
   INDEX PRODUCTIVITATE PER STUP
   ════════════════════════════════════════════════════════ */
function renderProductivityIndex(hFiltered) {
    let el = document.getElementById('productivity-index');
    if (!el) {
        el = document.createElement('div');
        el.id = 'productivity-index';
        const roiList = document.getElementById('roi-list');
        if (roiList) roiList.parentNode.insertBefore(el, roiList);
        else return;
    }
    if (!hFiltered || !hFiltered.length) { el.innerHTML = ''; return; }

    const ro = _lang !== 'en';
    const rows = hFiltered.map(h => {
        const kgTotal = parseFloat(h.kg) || 0;
        const name    = h.stup || '—';
        return { name, kgTotal };
    }).reduce((acc, cur) => {
        const ex = acc.find(x => x.name === cur.name);
        if (ex) ex.kgTotal += cur.kgTotal;
        else acc.push({ ...cur });
        return acc;
    }, []).sort((a,b) => b.kgTotal - a.kgTotal);

    if (!rows.length) { el.innerHTML = ''; return; }

    const maxKg = rows[0].kgTotal || 1;
    el.innerHTML = `<div class="card-box" style="margin-bottom:12px;">
        <h4 style="margin:0 0 12px;color:var(--premium-brown);font-size:0.95rem;">📊 ${ro ? 'Productivitate per Stup' : 'Productivity per Hive'}</h4>
        ${rows.map(r => `
        <div style="margin-bottom:8px;">
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;font-weight:700;color:var(--premium-brown);margin-bottom:3px;">
                <span>${r.name}</span><span>${r.kgTotal.toFixed(1)} kg</span>
            </div>
            <div style="background:var(--wood-light);border-radius:4px;height:8px;overflow:hidden;">
                <div style="height:100%;width:${Math.round((r.kgTotal/maxKg)*100)}%;background:linear-gradient(90deg,var(--honey),#e8a020);border-radius:4px;transition:width 0.4s;"></div>
            </div>
        </div>`).join('')}
    </div>`;
}

function renderCostPerKg() {
    let el = document.getElementById('cost-per-kg');
    if (!el) {
        el = document.createElement('div');
        el.id = 'cost-per-kg';
        const prodIdx = document.getElementById('productivity-index');
        if (prodIdx) prodIdx.parentNode.insertBefore(el, prodIdx.nextSibling);
        else return;
    }

    if (!harvestDataLocal.length) { el.innerHTML = ''; return; }

    const byHive = {};
    harvestDataLocal.forEach(h => {
        if (!byHive[h.stup]) byHive[h.stup] = { kg: 0 };
        byHive[h.stup].kg += parseFloat(h.kg) || 0;
    });
    (expensesDataLocal || []).forEach(e => {
        if (!byHive[e.stup]) byHive[e.stup] = { kg: 0 };
        if (!byHive[e.stup].cost) byHive[e.stup].cost = 0;
        byHive[e.stup].cost += parseFloat(e.suma) || 0;
    });

    const rows = Object.entries(byHive)
        .filter(([, d]) => d.kg > 0)
        .map(([name, d]) => {
            const cost = d.cost || 0;
            const cpk = cost > 0 ? (cost / d.kg).toFixed(2) : '—';
            const color = !cost ? 'var(--text-muted)' : cost/d.kg < 5 ? '#27ae60' : cost/d.kg < 15 ? '#f39c12' : '#ee5253';
            return `<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border-bottom:1px solid var(--wood-light);font-size:0.85rem;">
                <span style="font-weight:700;color:var(--premium-brown);">${name}</span>
                <span style="color:var(--text-muted);">${d.kg.toFixed(1)} kg</span>
                <span style="color:var(--text-muted);">${cost.toFixed(0)} RON</span>
                <span style="font-weight:800;color:${color};">${cpk} RON/kg</span>
            </div>`;
        }).join('');

    if (!rows) { el.innerHTML = ''; return; }

    el.innerHTML = `<div class="card-box" style="padding:16px;margin-top:4px;">
        <h4 style="margin:0 0 12px;color:var(--premium-brown);font-size:0.95rem;">💰 Cost per kg Miere</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;padding:6px 10px;font-size:0.72rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">
            <span>Stup</span><span>Recoltă</span><span>Cheltuieli</span><span>Cost/kg</span>
        </div>
        ${rows}
        <p style="font-size:0.7rem;color:var(--text-muted);margin:8px 0 0;opacity:0.7;">Verde &lt;5 RON/kg · Portocaliu 5-15 RON/kg · Roșu &gt;15 RON/kg</p>
    </div>`;
}

/* ════════════════════════════════════════════════════════
   5. COMPARAȚIE AN VS AN
   ════════════════════════════════════════════════════════ */
function renderYearComparison() {
    let el = document.getElementById('year-comparison');
    if (!el) {
        // Inseram in view-compare
        const vc = document.getElementById('view-compare');
        if (!vc) return;
        el = document.createElement('div');
        el.id = 'year-comparison';
        el.style.cssText = 'max-width:900px;margin:20px auto;padding:0 20px;';
        vc.appendChild(el);
    }

    if (!harvestDataLocal.length) { el.innerHTML = ''; return; }

    const byYearMonth = {};
    harvestDataLocal.forEach(h => {
        const p = (h.date || '').split('.');
        if (p.length < 3) return;
        const yr = p[2].trim().substring(0,4);
        const mo = parseInt(p[1]) - 1;
        if (!byYearMonth[yr]) byYearMonth[yr] = Array(12).fill(0);
        byYearMonth[yr][mo] += parseFloat(h.kg) || 0;
    });

    const years = Object.keys(byYearMonth).sort();
    if (years.length < 2) { el.innerHTML = ''; return; }

    const months = ['Ian','Feb','Mar','Apr','Mai','Iun','Iul','Aug','Sep','Oct','Nov','Dec'];
    const colors = ['#c8860a','#27ae60','#3498db','#8e44ad','#ee5253'];

    const datasets = years.map((yr, i) => ({
        label: yr,
        data: byYearMonth[yr],
        borderColor: colors[i % colors.length],
        backgroundColor: colors[i % colors.length] + '22',
        tension: 0.4,
        borderWidth: 2.5,
        fill: true,
        pointRadius: 4,
    }));

    el.innerHTML = `<div class="card-box">
        <h4 style="margin:0 0 14px;color:var(--premium-brown);font-size:0.95rem;">📅 Comparație Recoltă An vs An</h4>
        <div style="height:260px;position:relative;"><canvas id="yearCompareChart"></canvas></div>
    </div>`;

    setTimeout(() => {
        const ctx = document.getElementById('yearCompareChart')?.getContext('2d');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'line',
            data: { labels: months, datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { title: { display: true, text: 'kg miere' }, beginAtZero: true }
                }
            }
        });
    }, 100);
}

/* ════════════════════════════════════════════════════════
   6. REMINDER INSPECȚIE
   Banner pe card dacă stupul nu a fost inspectat în X zile
   ════════════════════════════════════════════════════════ */
function getLastInspectionDays(chipName) {
    const notes = jurnalDataLocal.filter(n =>
        n.stup === chipName &&
        !(n.text || '').includes('✅ Sarcină') &&
        !(n.text || '').includes('📋 Inspecție Rapidă') === false
    );
    if (!notes.length) return null;
    const last = notes.sort((a, b) => {
        const pd = s => { const p=(s||'').split(' ')[0].split('.'); return p.length>=3?new Date(p[2],p[1]-1,p[0]).getTime():0; };
        return pd(b.date) - pd(a.date);
    })[0];
    const d = last.date?.split(' ')[0]?.split('.');
    if (!d || d.length < 3) return null;
    const lastDate = new Date(d[2], d[1]-1, d[0]);
    return Math.floor((new Date() - lastDate) / 86400000);
}

/* ════════════════════════════════════════════════════════
   7. EMAIL ALERTĂ ROIRE AUTOMATĂ
   ════════════════════════════════════════════════════════ */
function sendRoireEmailIfNeeded(item) {
    if (item.isManual || (item.delta24 || 0) > -0.15) return;
    const name = item.meta?.nickname || ('Stup ' + item.chipID);
    const aID  = 'email_roi_' + item.chipID;

    // Verificam daca am trimis deja email in ultimele 6h
    try {
        const sent = JSON.parse(localStorage.getItem('mp_email_sent') || '{}');
        if (sent[aID] && (Date.now() - sent[aID]) < 6 * 3600000) return;
    } catch(e) {}

    const fd = new FormData();
    fd.append('action',  'send_alert_email');
    fd.append('alert_id', aID);
    fd.append('stup',    name);
    fd.append('msg',     `⚠️ Alertă roire: scădere de ${Math.abs(item.delta24 || 0).toFixed(2)} kg detectată`);
    fd.append('details', `Delta24: ${(item.delta24||0).toFixed(2)} kg | Greutate: ${(item.weight||0).toFixed(1)} kg | Temperatură: ${item.temperature||'—'}°C`);

    fetch('backend.php', { method: 'POST', body: fd }).then(r => r.text()).then(resp => {
        if (resp === 'ok' || resp === 'sent') {
            try {
                const sent = JSON.parse(localStorage.getItem('mp_email_sent') || '{}');
                sent[aID] = Date.now();
                localStorage.setItem('mp_email_sent', JSON.stringify(sent));
            } catch(e) {}
        }
    });
}

/* ════════════════════════════════════════════════════════
   8. EMAIL REMINDER INSPECȚIE
   ════════════════════════════════════════════════════════ */
function checkInspectionReminders() {
    const DAYS_THRESHOLD = 14; // zile fara inspectie = reminder
    hivesDataLocal.forEach(h => {
        const name = h.meta?.nickname || ('Stup ' + h.chipID);
        const days = getLastInspectionDays(name);
        if (days === null || days < DAYS_THRESHOLD) return;

        const aID = 'insp_remind_' + h.chipID;
        try {
            const sent = JSON.parse(localStorage.getItem('mp_email_sent') || '{}');
            // Trimitem reminder maxim o data la 7 zile
            if (sent[aID] && (Date.now() - sent[aID]) < 7 * 86400000) return;
        } catch(e) {}

        const fd = new FormData();
        fd.append('action',   'send_alert_email');
        fd.append('alert_id', aID);
        fd.append('stup',     name);
        fd.append('msg',      `📋 Reminder: ${name} nu a fost inspectat de ${days} zile!`);
        fd.append('details',  `Ultima inspecție: acum ${days} zile. Te rugăm să verifici starea coloniei.`);

        fetch('backend.php', { method: 'POST', body: fd }).then(r => r.text()).then(resp => {
            if (resp === 'ok' || resp === 'sent') {
                try {
                    const sent = JSON.parse(localStorage.getItem('mp_email_sent') || '{}');
                    sent[aID] = Date.now();
                    localStorage.setItem('mp_email_sent', JSON.stringify(sent));
                } catch(e) {}
            }
        });
    });
}


function renderQueenTimeline() {
    const container = document.getElementById('queen-history-container');
    if (!container) return;
    const keywords = ['matcă','regină','matca','regina','botci','puiet','oua','ouă'];
    const timelineData = jurnalDataLocal.filter(n => n.stup===currentChipName && keywords.some(k=>n.text.toLowerCase().includes(k)));
    let html = '<div class="timeline">';
    timelineData.forEach(item => { html+=`<div class="timeline-item"><span class="timeline-date">${item.date}</span><div class="timeline-content">${item.text}</div></div>`; });
    html += '</div>';
    container.innerHTML = timelineData.length > 0 ? html : '<p style="opacity:0.6;padding:20px;">Nicio însemnare despre matcă în jurnal.</p>';
}

/* ════════════════════════════════════════
   ADMIN — USER MANAGEMENT (REFĂCUT)
   ════════════════════════════════════════ */
let _admEditingUser = null; // null = add mode, string = edit mode

function admShowTab(tabId, btn) {
    document.querySelectorAll('.adm-tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.adm-tab').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId)?.classList.add('active');
    if (btn) btn.classList.add('active');
    if (tabId === 'tab-controllers') loadControllers();
}

function loadAdminUsers() {
    loadControllers(); 
    fetch('backend.php?fetch_users=1&t='+Date.now()).then(r=>r.json()).then(users => {
        const totalUsers = Object.keys(users).length;
        let totalHivesAdm = 0, manualCount = 0;
        for (let u in users) {
            totalHivesAdm += (users[u].hives||[]).length;
            if (users[u].can_manage_manual) manualCount++;
        }
        const sv = document.getElementById('stat-total-users');    if(sv) sv.textContent = totalUsers;
        const sh = document.getElementById('stat-total-hives-adm'); if(sh) sh.textContent = totalHivesAdm;
        const sm = document.getElementById('stat-manual-access');   if(sm) sm.textContent = manualCount;

        let rows = '';
        for (let u in users) {
            const hives   = Array.isArray(users[u].hives) ? users[u].hives : [];
            const ctrls   = Array.isArray(users[u].controllers) ? users[u].controllers : [];
            const email   = users[u].email || '<span style="opacity:0.4">—</span>';
            const isAdm   = (u === 'admin' || !!users[u].is_admin);

            // --- LOGICA MODIFICATĂ PENTRU AFIȘAREA STUPILOR ---
            const hivePills = isAdm 
                ? `<span class="adm-hive-count" style="background:#e8f5e9;color:#2e7d32;border-color:#c8e6c9;">Toți (Admin)</span>` 
                : hives.length === 0 
                    ? `<span style="opacity:0.4;font-size:0.8rem;font-style:italic;">Niciunul</span>` 
                    : hives.length > 3 
                        ? hives.slice(0,2).map(h=>`<span class="adm-hive-pill">${h}</span>`).join('')+`<span class="adm-hive-count"> +${hives.length-2}</span>` 
                        : hives.map(h=>`<span class="adm-hive-pill">${h}</span>`).join('');
            // --------------------------------------------------

            const ctrlPills = ctrls.length === 0 ? `<span style="opacity:0.3;font-size:0.75rem">—</span>` : 
                ctrls.map(c => `<span class="adm-hive-pill" style="background:#e3f2fd;color:#1976d2;border-color:#bbdefb;font-size:0.68rem">🔌${c}</span>`).join('');

            const badge = isAdm ? '<span class="adm-badge adm-badge-admin">👑 Admin</span>' : 
                (users[u].can_manage_manual ? '<span class="adm-badge adm-badge-manual">🛠️ Manual</span>' : '<span class="adm-badge adm-badge-std">Standard</span>');

            const isApproved = isAdm || users[u].approved !== false;
            const approvedBadge = isApproved
                ? '<span style="color:#27ae60;font-size:0.75rem;font-weight:800;">✅ Aprobat</span>'
                : '<span style="color:#ee5253;font-size:0.75rem;font-weight:800;">⏳ Neaprobat</span>';

            rows += `<tr data-user="${u.toLowerCase()}">
                <td><b>${u}</b><br><small style="opacity:0.5">${approvedBadge}</small></td>
                <td style="font-size:0.83rem">${email}</td>
                <td><div style="display:flex;flex-wrap:wrap;gap:4px">${hivePills}</div></td>
                <td><div style="display:flex;flex-wrap:wrap;gap:4px">${ctrlPills}</div></td>
                <td>${badge}</td>
                <td style="text-align:center;">${users[u].can_manage_manual ? '<span style="color:#27ae60;font-weight:800;font-size:1rem;" title="Are acces manual">✅</span>' : '<span style="color:var(--accent-red);font-size:1rem;opacity:0.5;" title="Fără acces manual">✕</span>'}</td>
                <td style="text-align:center;white-space:nowrap;">
                    ${!isApproved ? `<button class="adm-act-btn" style="color:#27ae60;font-weight:800;" onclick="admApproveUser('${u}')" title="Aprobă contul">✅</button>` : ''}
                    <button class="adm-act-btn adm-act-edit" onclick="admOpenEditUser('${u}')">✏️</button>
                    <button class="adm-act-btn adm-act-reset" onclick="admOpenResetPass('${u}')">🔑</button>
                    <button class="adm-act-btn" style="color:#10ac84" onclick="admChangeEmail('${u}','${users[u].email||''}')">📧</button>
                    ${!isAdm ? `<button class="adm-act-btn adm-act-delete" onclick="admDeleteUser('${u}')">🗑️</button>` : ''}
                </td>
            </tr>`;
        }
        const tbody = document.getElementById('admin-users-list');
        if (tbody) tbody.innerHTML = rows || `<tr><td colspan="6" style="text-align:center;padding:20px;opacity:0.5">Niciun utilizator.</td></tr>`;
    });
}

function filterAdminUsers() {
    const q = (document.getElementById('admin-search-user')?.value || '').toLowerCase();
    document.querySelectorAll('#admin-users-list tr[data-user]').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

/* ── Modal helpers ── */
function admPopulateHiveGrid(allowedHives = []) {
    const grid = document.getElementById('adm-hives-container-modal');
    if (!grid) return;
    const allIds = new Set(Object.keys(chipIDtoName));
    hivesDataLocal.forEach(h => allIds.add(h.chipID.toString()));
    if (allIds.size === 0) { grid.innerHTML = `<p style="opacity:0.5;font-size:0.82rem;margin:0;text-align:center">${_lang==='en'?'No hives in system.':'Nu există stupi în sistem.'}</p>`; return; }
    grid.innerHTML = '';
    allIds.forEach(id => {
        const name    = chipIDtoName[id] || hivesDataLocal.find(h=>h.chipID==id)?.meta?.nickname || id;
        const checked = allowedHives.includes(id) ? 'checked' : '';
        const lbl = document.createElement('label');
        lbl.className = 'adm-hive-cb-label';
        lbl.innerHTML = `<input type="checkbox" class="adm-hive-cb" value="${id}" ${checked}><span>${name} <b style="opacity:0.5;font-size:0.7rem">(${id})</b></span>`;
        grid.appendChild(lbl);
    });
}

function admSelectAllHives(val) {
    document.querySelectorAll('.adm-hive-cb').forEach(cb => cb.checked = val);
}

function admTogglePass() {
    const inp = document.getElementById('adm-pass');
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
}

function admOpenAddUser() {
    _admEditingUser = null;
    document.getElementById('adm-modal-title').textContent = 'Utilizator Nou';
    document.getElementById('adm-pass-label').innerHTML   = 'Parolă <span class="req">*</span>';
    document.getElementById('adm-pass-hint').style.display = 'none';
    document.getElementById('adm-reset-section').style.display = 'none';

    const uInp = document.getElementById('adm-user');
    uInp.value = ''; uInp.readOnly = false; uInp.style.opacity = '1';
    document.getElementById('adm-pass').value  = '';
    document.getElementById('adm-email').value = '';
    const cbM = document.getElementById('adm-can-manual'); if (cbM) cbM.checked = false;
    const cbA = document.getElementById('adm-is-admin');   if (cbA) cbA.checked = false;
    admPopulateHiveGrid([]);
    admPopulateControllerGrid(document.getElementById('adm-controllers-modal-grid'), []);

    document.getElementById('btn-adm-save').textContent = '💾 Creează Contul';
    document.getElementById('adm-user-modal').style.display = 'flex';
}

function admOpenEditUser(u) {
    fetch('backend.php?fetch_users=1&t='+Date.now()).then(r=>r.json()).then(users => {
        if (!users[u]) return;
        _admEditingUser = u;
        const data = users[u];

        document.getElementById('adm-modal-title').textContent = `Editare: ${u}`;
        document.getElementById('adm-pass-label').innerHTML    = 'Parolă Nouă';
        document.getElementById('adm-pass-hint').style.display = '';
        document.getElementById('adm-reset-section').style.display = '';

        const uInp = document.getElementById('adm-user');
        uInp.value = u; uInp.readOnly = true; uInp.style.opacity = '0.55';

        document.getElementById('adm-pass').value  = '';
        document.getElementById('adm-email').value = data.email || '';
        const cbM = document.getElementById('adm-can-manual'); if (cbM) cbM.checked = !!data.can_manage_manual;
        const cbA = document.getElementById('adm-is-admin');   if (cbA) cbA.checked = !!data.is_admin;
        admPopulateHiveGrid(Array.isArray(data.hives) ? data.hives : []);
        admPopulateControllerGrid(
            document.getElementById('adm-controllers-modal-grid'),
            Array.isArray(data.controllers) ? data.controllers : []
        );

        document.getElementById('btn-adm-save').textContent = '💾 Salvează Modificările';
        document.getElementById('adm-user-modal').style.display = 'flex';
    });
}

function admOpenResetPass(u) {
    showConfirmModal({
        title:       _lang==='en' ? `Reset password: ${u}` : `Resetare parolă: ${u}`,
        message:     t('confirm_reset_pass', { u }),
        confirmText: _lang==='en' ? '📧 Send Email' : '📧 Trimite Email',
        type:        'warning',
        onConfirm:   () => {
            const fd = new FormData();
            fd.append('action',      'admin_reset_password');
            fd.append('target_user', u);
            smartFetch(fd).then(r => {
                if (r && !r.offline) r.text().then(resp => {
                    if (resp === 'ok') toast(`${_lang==='en'?'Temporary password sent to':'Parolă temporară trimisă la'} ${u}!`, 'success');
                    else toast((_lang==='en'?'Error: ':'Eroare: ') + resp, 'error');
                });
            });
        }
    });
}

function admCloseModal() {
    document.getElementById('adm-user-modal').style.display = 'none';
    _admEditingUser = null;
}

function admResetPassword() {
    if (_admEditingUser) admOpenResetPass(_admEditingUser);
}

/* ── Add / Update user (același buton) ── */
function addUser() {
    const u    = document.getElementById('adm-user')?.value?.trim();
    const p    = document.getElementById('adm-pass')?.value || '';
    const e    = document.getElementById('adm-email')?.value?.trim() || '';
    const h    = Array.from(document.querySelectorAll('.adm-hive-cb:checked')).map(cb=>cb.value).join(',');
    const canM = document.getElementById('adm-can-manual')?.checked || false;

    if (!u) return toast('Completează numele de utilizator!', 'warning');
    if (!_admEditingUser && !p) { toast(_lang==='en'?'Password required for new account!':'Parola este obligatorie pentru un cont nou!', 'warning'); return; };
    if (!_admEditingUser && p.length < 6) return toast('Parola trebuie să aibă cel puțin 6 caractere!', 'warning');

    const isAdminFlag = document.getElementById('adm-is-admin')?.checked || false;
    const ctrlSel     = Array.from(document.querySelectorAll('.adm-ctrl-cb:checked')).map(cb => cb.value).join(',');
    const fd = new FormData();
    fd.append('action',           'manage_user');
    fd.append('new_user',          u);
    fd.append('new_pass',          p);
    fd.append('new_hives',         h);
    fd.append('can_manage_manual', canM);
    fd.append('new_email',         e);
    fd.append('is_admin',          isAdminFlag);
    fd.append('user_controllers',  ctrlSel);

    smartFetch(fd).then(r => {
        if (r && !r.offline) r.text().then(t => {
            if (t === 'ok' || t.trim() === 'ok') {
                admCloseModal();
                loadAdminUsers();
                renderAdminHiveCheckboxes();
            } else if (t === 'error_pass') {
                toast('Parola trebuie să aibă cel puțin 6 caractere!', 'warning');
            } else {
                toast(_lang==='en'?'Server error. Please try again.':'Eroare server. Încearcă din nou.', 'error');
            }
        });
    });
}


/* ════════════════════════════════════════
   SCHIMBARE EMAIL USER (din admin)
   ════════════════════════════════════════ */
function admChangeEmail(username, currentEmail) {
    let modal = document.getElementById('change-email-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'change-email-modal';
        modal.style.cssText = 'display:none;position:fixed;inset:0;z-index:9600;background:rgba(26,31,38,0.7);backdrop-filter:blur(6px);align-items:center;justify-content:center;';
        modal.innerHTML = `<div style="background:var(--cream,#fdfbf7);width:92%;max-width:420px;border-radius:20px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
            <h3 id="cem-title" style="margin:0 0 18px;color:var(--premium-brown)">📧 Schimbă Email</h3>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.8rem;font-weight:800;color:var(--premium-brown);margin-bottom:6px;">Email nou</label>
                <input type="email" id="cem-email" placeholder="adresa@email.ro" maxlength="128"
                    style="width:100%;padding:11px 12px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.9rem;box-sizing:border-box;outline:none;">
            </div>
            <div style="display:flex;gap:10px;margin-top:6px;">
                <button onclick="document.getElementById('change-email-modal').style.display='none'"
                    style="flex:1;padding:11px;background:#f5f0e8;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;color:var(--premium-brown);">
                    Anulează
                </button>
                <button id="cem-save"
                    style="flex:2;padding:11px;background:linear-gradient(135deg,var(--wood-dark,#a6845c),var(--honey,#d4860b));color:#fff;border:none;border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;">
                    💾 Salvează Email
                </button>
            </div>
        </div>`;
        modal.onclick = ev => { if (ev.target === modal) modal.style.display='none'; };
        document.body.appendChild(modal);
    }

    document.getElementById('cem-title').textContent = `📧 Email pentru: ${username}`;
    document.getElementById('cem-email').value = currentEmail || '';
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('cem-email').focus(), 150);

    document.getElementById('cem-save').onclick = () => {
        const newEmail = document.getElementById('cem-email').value.trim();
        if (!newEmail || !/^[^@]+@[^@]+\.[^@]+$/.test(newEmail)) {
            toast(_lang==='en' ? 'Invalid email address!' : 'Adresă de email invalidă!', 'warning');
            return;
        }
        const fd = new FormData();
        fd.append('action',     'manage_user');
        fd.append('new_user',   username);
        fd.append('new_pass',   '');         // gol = păstrează parola existentă
        fd.append('new_email',  newEmail);
        fd.append('can_manage_manual', false);
        fd.append('new_hives',  '');         // nu modificăm stupii

        // Trimitem cu flag special pentru a nu reseta stupii
        fd.append('email_only', '1');

        smartFetch(fd).then(r => {
            if (r && !r.offline) r.text().then(resp => {
                if (resp.trim() === 'ok') {
                    modal.style.display = 'none';
                    toast(`Email actualizat pentru ${username}!`, 'success');
                    loadAdminUsers();
                } else {
                    toast(_lang==='en'?'Error updating email.':'Eroare la actualizarea emailului.', 'error');
                }
            });
        });
    };
}


/* ════════════════════════════════════════
   CONTROLLERE ESP — MANAGEMENT
   ════════════════════════════════════════ */
let _controllersData = {};

function loadControllers() {
    fetch('backend.php?get_controllers=1&t=' + Date.now())
        .then(r => r.json())
        .then(data => {
            _controllersData = data || {};
            renderControllersTab();
            admPopulateControllerGrid(document.getElementById('adm-controllers-modal-grid'), []);
        })
        .catch(() => {});
}

function renderControllersTab() {
    const container = document.getElementById('controllers-list-container');
    if (!container) return;

    const keys = Object.keys(_controllersData);
    if (keys.length === 0) {
        container.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:20px;opacity:0.5">${_lang==='en'?'No controllers defined yet.':'Niciun controller definit încă.'}</td></tr>`;
        return;
    }

    const now = Math.floor(Date.now() / 1000);
    let rows = '';
    keys.forEach(ctrlId => {
        const ctrl = _controllersData[ctrlId];
        const age  = ctrl.lastSeen ? (now - ctrl.lastSeen) : null;
        const onlineColor = !age ? '#95a5a6' : (age < 3600 ? 'var(--accent-green)' : (age < 86400 ? '#f39c12' : '#ee5253'));
        const onlineTxt   = !age ? '—' : (age < 3600 ? (_lang==='en'?'Online':'Online') : (age < 86400 ? (_lang==='en'?'Recent':'Recent') : (_lang==='en'?'Offline':'Offline')));
        const lastSeenTxt = ctrl.lastSeen ? new Date(ctrl.lastSeen * 1000).toLocaleString('ro-RO') : '—';

        rows += `<tr>
            <td><b>${ctrlId}</b></td>
            <td>${ctrl.name || '—'}</td>
            <td><span style="font-size:0.8rem">${(ctrl.chipIDs||[]).length} ${_lang==='en'?'hives':'stupi'}</span></td>
            <td style="font-size:0.8rem">${lastSeenTxt}</td>
            <td style="text-align:center">
                <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.78rem;font-weight:800;color:${onlineColor}">
                    <span style="width:8px;height:8px;border-radius:50%;background:${onlineColor};display:inline-block"></span>
                    ${onlineTxt}
                </span>
            </td>
            <td style="text-align:center;white-space:nowrap">
                <button class="adm-act-btn adm-act-edit" onclick="admOpenEditController('${ctrlId}')" title="${_lang==='en'?'Edit':'Editează'}">✏️</button>
                <button class="adm-act-btn adm-act-delete" onclick="admDeleteController('${ctrlId}')" title="${_lang==='en'?'Delete':'Șterge'}">🗑️</button>
            </td>
        </tr>`;
    });
    container.innerHTML = rows;
}

function admOpenAddController() {
    document.getElementById('ctrl-modal-title').textContent = _lang==='en' ? 'New Controller' : 'Controller Nou';
    document.getElementById('ctrl-id-input').value   = '';
    document.getElementById('ctrl-id-input').readOnly = false;
    document.getElementById('ctrl-id-input').style.opacity = '1';
    document.getElementById('ctrl-name-input').value = '';
    admPopulateControllerChipGrid([]);
    document.getElementById('controller-modal').style.display = 'flex';
}

function admOpenEditController(ctrlId) {
    const ctrl = _controllersData[ctrlId];
    if (!ctrl) return;
    document.getElementById('ctrl-modal-title').textContent = (_lang==='en' ? 'Edit: ' : 'Editare: ') + ctrlId;
    document.getElementById('ctrl-id-input').value    = ctrlId;
    document.getElementById('ctrl-id-input').readOnly = true;
    document.getElementById('ctrl-id-input').style.opacity = '0.55';
    document.getElementById('ctrl-name-input').value  = ctrl.name || '';
    admPopulateControllerChipGrid(ctrl.chipIDs || []);
    document.getElementById('controller-modal').style.display = 'flex';
}

function admPopulateControllerChipGrid(selectedIDs = []) {
    const grid = document.getElementById('ctrl-chip-grid');
    if (!grid) return;
    const allIDs = new Set(hivesDataLocal.map(h => String(h.chipID)));
    // Agaugă și chipID-urile din alte controllere (pot fi cunoscute chiar dacă offline)
    Object.values(_controllersData).forEach(ctrl => (ctrl.chipIDs||[]).forEach(id => allIDs.add(String(id))));
    if (allIDs.size === 0) {
        grid.innerHTML = `<p style="opacity:0.5;font-size:0.82rem;margin:0;text-align:center">${_lang==='en'?'No hives in system.':'Niciun stup în sistem.'}</p>`;
        return;
    }
    grid.innerHTML = '';
    allIDs.forEach(id => {
        const name    = chipIDtoName[id] || hivesDataLocal.find(h=>String(h.chipID)===id)?.meta?.nickname || id;
        const checked = selectedIDs.map(String).includes(String(id)) ? 'checked' : '';
        const lbl = document.createElement('label');
        lbl.className = 'adm-hive-cb-label';
        lbl.innerHTML = `<input type="checkbox" class="ctrl-chip-cb" value="${id}" ${checked}><span>${name} <b style="opacity:0.5;font-size:0.7rem">(${id})</b></span>`;
        grid.appendChild(lbl);
    });
}

function admSaveController() {
    const ctrlId   = document.getElementById('ctrl-id-input')?.value?.trim();
    const ctrlName = document.getElementById('ctrl-name-input')?.value?.trim();
    if (!ctrlId) { toast(_lang==='en'?'Enter a controller ID!':'Introdu un ID pentru controller!', 'warning'); return; }
    if (!/^[a-zA-Z0-9_-]+$/.test(ctrlId)) { toast(_lang==='en'?'ID: only letters, numbers, _ and -':'ID: doar litere, cifre, _ și -', 'warning'); return; }

    const chipIDs = Array.from(document.querySelectorAll('.ctrl-chip-cb:checked')).map(cb => cb.value);
    const fd = new FormData();
    fd.append('action',    'save_controller');
    fd.append('ctrl_id',   ctrlId);
    fd.append('ctrl_name', ctrlName || ctrlId);
    fd.append('chip_ids',  chipIDs.join(','));
    smartFetch(fd).then(() => {
        document.getElementById('controller-modal').style.display = 'none';
        toast(_lang==='en'?'Controller saved!':'Controller salvat!', 'success');
        loadControllers();
    });
}

function admDeleteController(ctrlId) {
    showConfirmModal({
        title:       _lang==='en' ? `Delete controller: ${ctrlId}` : `Șterge controller: ${ctrlId}`,
        message:     _lang==='en' ? `This will remove "${ctrlId}" from all users too. Hive data is preserved.` : `Se va elimina "${ctrlId}" și din toți utilizatorii. Datele stupilor se păstrează.`,
        confirmText: _lang==='en' ? 'Delete' : 'Șterge',
        type:        'danger',
        onConfirm:   () => {
            const fd = new FormData();
            fd.append('action',  'delete_controller');
            fd.append('ctrl_id', ctrlId);
            smartFetch(fd).then(() => { toast(_lang==='en'?'Controller deleted!':'Controller șters!', 'success'); loadControllers(); });
        }
    });
}

/* ── Grid controllere în modalul de editare user ── */
function admPopulateControllerGrid(container, selectedCtrlIDs = []) {
    if (!container) container = document.getElementById('adm-controllers-modal-grid');
    if (!container) return;

    const keys = Object.keys(_controllersData);
    if (keys.length === 0) {
        container.innerHTML = `<p style="opacity:0.5;font-size:0.82rem;margin:0;text-align:center">Nu există controllere.</p>`;
        return;
    }

    container.innerHTML = '';
    keys.forEach(ctrlId => {
        const ctrl    = _controllersData[ctrlId];
        const checked = selectedCtrlIDs.map(String).includes(String(ctrlId)) ? 'checked' : '';
        
        const lbl = document.createElement('label');
        lbl.className = 'adm-hive-cb-label'; // Folosim aceeași clasă ca la stupi pentru design unitar [citește: 10]
        lbl.innerHTML = `
            <input type="checkbox" class="adm-ctrl-cb" value="${ctrlId}" ${checked}>
            <span style="display:flex;align-items:center;gap:4px;">
                🔌 ${ctrl.name || ctrlId}
            </span>`;
        container.appendChild(lbl);
    });
}

function admApproveUser(u) {
    showConfirmModal({
        title: `✅ Aprobă contul: ${u}`,
        message: `Aprobi accesul utilizatorului "${u}" la aplicație? Acesta va putea loga imediat după aprobare.`,
        confirmText: '✅ Aprobă',
        type: 'success',
        onConfirm: () => {
            const fd = new FormData();
            fd.append('action', 'approve_user');
            fd.append('user', u);
            smartFetch(fd).then(() => {
                toast(`Contul "${u}" a fost aprobat!`, 'success');
                loadAdminUsers();
            });
        }
    });
}

function admDeleteUser(u) {
    showConfirmModal({
        title:       _lang==='en' ? `Delete account: ${u}` : `Șterge contul: ${u}`,
        message:     t('confirm_delete_user', { u }),
        confirmText: t('btn_delete'),
        type:        'danger',
        onConfirm:   () => { const fd=new FormData(); fd.append('action','delete_user'); fd.append('user',u); smartFetch(fd).then(()=>loadAdminUsers()); }
    });
}

/* ── Păstrate pentru compatibilitate ── */
function editUserForm(u, h, canM=false) { admOpenEditUser(u); }
function clearUserForm() { admCloseModal(); }
function deleteAdminUser(u) { admDeleteUser(u); }

/* ════════════════════════════════════════
   AMBIENT (ALBINE & POLENURI)
   ════════════════════════════════════════ */
function initAmbient() {
    const c = document.getElementById('pollen-container');
    if (!c) return;
    for (let i=0; i<30; i++) {
        const p=document.createElement('div'); p.className='pollen-grain'; p.style.width=p.style.height=Math.random()*4+2+'px'; p.style.left=Math.random()*100+'vw'; p.style.top=Math.random()*100+'vh'; p.style.animation=`floatPollen ${Math.random()*15+10}s infinite linear`; c.appendChild(p);
    }
    for (let i=0; i<6; i++) {
        const b=document.createElement('div'); b.innerHTML='🐝'; b.className='bg-bee'; b.style.left=Math.random()*100+'vw'; b.style.top=Math.random()*100+'vh'; c.appendChild(b); moveBee(b); b.onmouseover=()=>{ b.style.transform=`translate(${(Math.random()-0.5)*200}px,${(Math.random()-0.5)*200}px) scale(1.5)`; };
    }
}

function moveBee(b) { b.style.left=Math.random()*100+'vw'; b.style.top=Math.random()*100+'vh'; setTimeout(()=>moveBee(b), 5000+Math.random()*4000); }

function releaseBees(el) {
    const r=el.getBoundingClientRect();
    for (let i=0; i<8; i++) {
        const b=document.createElement('div'); b.innerHTML='🐝'; b.className='flying-bee'; document.body.appendChild(b); activeBees.push(b);
        const cx=window.innerWidth/2, cy=window.innerHeight/2, mW=Math.min(window.innerWidth*0.9,850)/2, mH=(window.innerHeight*0.9)/2, buf=30;
        let fx, fy; const side=Math.floor(Math.random()*4);
        if(side===0){fx=cx+(Math.random()-0.5)*mW*2;fy=cy-mH-buf-Math.random()*40;}
        else if(side===1){fx=cx+mW+buf+Math.random()*40;fy=cy+(Math.random()-0.5)*mH*2;}
        else if(side===2){fx=cx+(Math.random()-0.5)*mW*2;fy=cy+mH+buf+Math.random()*40;}
        else{fx=cx-mW-buf-Math.random()*40;fy=cy+(Math.random()-0.5)*mH*2;}
        b.animate([{left:(r.left+50)+'px',top:(r.top+20)+'px',opacity:0},{opacity:1,offset:0.1},{left:fx+'px',top:fy+'px',opacity:1}],{duration:800,fill:'forwards'}).onfinish=()=>b.classList.add('hovering');
    }
}

/* ════════════════════════════════════════
   MODAL STUP — TAB-URI
   ════════════════════════════════════════ */
function setModalTab(v) {
    // Refactorizat: loop în loc de 14 getElementById-uri
    ['graph','inspec','meta','photo','harvest','logs','queen','queenhist','brood'].forEach(tab => {
        const view = document.getElementById('m-view-' + tab);
        const btn  = document.getElementById('m-tab-' + tab);
        if (view) view.style.display = (tab === v) ? 'block' : 'none';
        if (btn)  btn.classList.toggle('active', tab === v);
    });
    // Callbacks per tab
    if (v === 'photo')     renderHiveGallery();
    if (v === 'harvest')   renderModalHarvest();
    if (v === 'logs')      renderMiniJurnalModal();
    if (v === 'queen')     renderQueenTimeline();
    if (v === 'queenhist') renderQueenHistory();
    if (v === 'brood')     renderBroodCalendar();
    if (v === 'inspec')    restoreQuickInspection(currentChipID);
}

function renderMiniJurnalModal() {
    const list = jurnalDataLocal.filter(n => n.stup === currentChipName);
    document.getElementById('modal-jurnal-list').innerHTML = list.map(n => `<div class="modal-log-item"><b>${n.date}</b>: ${n.text}</div>`).join('') || '<p style="color:var(--text-muted)">Niciun log.</p>';
}

function restoreQuickInspection(chipID) {
    try {
        const saved = JSON.parse(localStorage.getItem('qi_' + chipID));
        if (!saved) return;
        ['matca','oua','botci'].forEach(group => {
            if (!saved[group]) return;
            const btn = document.querySelector(`button[onclick="${saved[group]}"]`);
            if (btn) {
                // Dezactivăm toate din grup
                btn.closest('.quick-group') && btn.closest('.quick-group').querySelectorAll('button').forEach(b => b.classList.remove('active'));
                // Activăm cel salvat
                btn.classList.add('active');
            }
        });
    } catch(e) {}
}

function openHiveModal(id, name, meta) {
    currentChipID = id; currentChipName = name; currQCol = meta.qColor;
    document.getElementById('histTitle').innerText = name;
    document.getElementById('m-nick').value   = meta.nickname || '';
    document.getElementById('m-parent').value = meta.parent   || '';
    document.getElementById('m-qBreed').value = meta.qBreed   || '';
    document.getElementById('m-qScore').value = meta.qScore   || '5';
    document.getElementById('qr-container').innerHTML = '';

    let maintWrap = document.getElementById('maint-wrap-dynamic');
    if(!maintWrap) {
        maintWrap = document.createElement('div');
        maintWrap.id = 'maint-wrap-dynamic';
        maintWrap.style.marginTop='15px'; maintWrap.style.background='#fff3e0'; maintWrap.style.padding='10px'; maintWrap.style.borderRadius='8px'; maintWrap.style.border='1px dashed #e67e22';
        const _hw = hivesDataLocal.find(h=>String(h.chipID)===String(currentChipID));
        const _wkg = _hw?.weight?.toFixed(1) || '--';
        maintWrap.innerHTML = `<label style="display:flex;align-items:center;cursor:pointer;font-weight:bold;color:#d35400;font-size:0.9rem;"><input type="checkbox" id="m-maintenance" style="margin-right:8px;width:18px;height:18px;"> 🛠️ Mod Mentenanță (Oprește alarmele de greutate)</label>
        <div style="margin-top:10px;display:flex;align-items:center;gap:10px;">
            <label style="font-weight:bold;color:#8B6914;font-size:0.9rem;">🍯 Magazii montate:</label>
            <input type="number" id="m-supers" min="0" max="5" value="0" style="width:60px;padding:5px 8px;border:1.5px solid var(--wood-light);border-radius:8px;font-size:1rem;font-weight:800;text-align:center;">
        </div>
        <div style="margin-top:12px;padding:10px 12px;background:rgba(200,134,10,0.07);border-radius:10px;border:1px dashed rgba(200,134,10,0.3);">
            <div style="font-size:0.78rem;font-weight:800;color:var(--premium-brown);margin-bottom:4px;">⚖️ Reset Bază Greutate</div>
            <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:8px;line-height:1.4;">Folosește după ce scoți/adaugi magazii. Greutatea curentă devine noua referință pentru alerta de roire.</div>
            <button onclick="resetWeightBase()" style="padding:7px 14px;background:var(--honey,#d4860b);border:none;border-radius:8px;font-family:inherit;font-weight:800;cursor:pointer;color:#fff;font-size:0.82rem;">⚖️ Resetez Baza (${_wkg} kg)</button>
        </div>`;
        const metaTab = document.getElementById('m-view-meta');
        if(metaTab) metaTab.insertBefore(maintWrap, metaTab.firstChild);
    }
    const maintCb = document.getElementById('m-maintenance');
    if(maintCb) maintCb.checked = (meta.maintenance === true || meta.maintenance === 'true');
    const supersInput = document.getElementById('m-supers');
    if(supersInput) supersInput.value = meta.supers !== undefined ? meta.supers : 0;

    const isMan = id.toString().startsWith('M');
    const manWrap   = document.getElementById('manual-data-entry');
    const graphWrap = document.getElementById('iot-graph-controls');
    if(isMan) {
        if(manWrap)   manWrap.style.display   = userPermissions.canManageManual ? 'block':'none';
        if(graphWrap) graphWrap.style.display = 'none';
        const hiveObj = hivesDataLocal.find(h => h.chipID == id);
        if(hiveObj && document.getElementById('m-weight-input')) {
            document.getElementById('m-weight-input').value = hiveObj.weight;
            document.getElementById('m-temp-input').value   = hiveObj.temperature;
        }
    } else {
        if(manWrap)   manWrap.style.display   = 'none';
        if(graphWrap) graphWrap.style.display = 'block';
    }
    initFrameMapper(meta.frames);
    document.getElementById('histModal').style.display = 'flex';
    setModalTab('graph');
    fetchHistory();
}

document.addEventListener('DOMContentLoaded', () => {
    const closeBtn = document.getElementById('closeHist');
    if(closeBtn) closeBtn.onclick = () => {
        document.getElementById('histModal').style.display = 'none';
        document.querySelectorAll('.hive-wrapper').forEach(h => h.classList.remove('opening'));
        document.querySelectorAll('.flying-bee').forEach(b => b.remove());
    };
});

function generateQR() {
    const baseUrl = window.location.origin + window.location.pathname;
    const dataStr = `${baseUrl}?stupID=${currentChipID}`;
    document.getElementById('qr-container').innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(dataStr)}" style="border-radius:10px;border:2px solid var(--wood-light)"><p style="font-size:0.8rem;margin-top:5px;color:var(--text-muted)">Scanează cu telefonul în stupină.</p>`;
}

function saveMeta() {
    const newNick = document.getElementById('m-nick')?.value || '';
    const fd = new FormData();
    fd.append('action','save_metadata'); fd.append('chipID', currentChipID); fd.append('qColor', currQCol);
    fd.append('nickname', newNick);
    fd.append('parent',   document.getElementById('m-parent')?.value || '');
    fd.append('qBreed',   document.getElementById('m-qBreed')?.value || '');
    fd.append('qScore',   document.getElementById('m-qScore')?.value || '5');
    const maintCb = document.getElementById('m-maintenance');
    if(maintCb) fd.append('maintenance', maintCb.checked);
    const supersCb = document.getElementById('m-supers');
    if(supersCb) fd.append('supers', parseInt(supersCb.value) || 0);

    smartFetch(fd).then(() => {
        // Agualizare imediată în hivesDataLocal fără să așteptăm fetchData
        const hive = hivesDataLocal.find(h => String(h.chipID) === String(currentChipID));
        if (hive) {
            hive.meta.nickname    = newNick;
            hive.meta.qColor      = currQCol;
            hive.meta.qBreed      = document.getElementById('m-qBreed')?.value || '';
            hive.meta.qScore      = document.getElementById('m-qScore')?.value || '5';
            hive.meta.maintenance = maintCb ? maintCb.checked : false;
            hive.meta.supers      = supersCb ? (parseInt(supersCb.value) || 0) : 0;
            const nSupers = hive.meta.supers;
            hive.honeyEstimate    = Math.round((parseFloat(hive.weight) - 16.0 - (7.0 * nSupers) - 1.5) * 100) / 100;
        }
        // Forțăm re-render imediat
        _lastHivesHash = ''; // invalidează cache-ul de hash
        renderDashboard();
        renderMap();
        fetchData(); // sync complet în background
        // Ramanem pe tab-ul curent in loc sa sarim la graph
        toast(_lang==='en'?'Hive data saved!':'Date stup salvate!', 'success');
    });
}


/* ════════════════════════════════════════
   ȘTERGERE NOTE JURNAL PER STUP (admin only)
   ════════════════════════════════════════ */
function deleteAllHiveNotes(hiveName) {
    showConfirmModal({
        title:       _lang==='en' ? `Delete all notes: ${hiveName}` : `Șterge toate notele: ${hiveName}`,
        message:     _lang==='en'
            ? `This will permanently delete ALL journal entries for "${hiveName}". This cannot be undone.`
            : `Se vor șterge TOATE notele din jurnal pentru "${hiveName}". Acțiunea nu poate fi anulată.`,
        confirmText: _lang==='en' ? '🗑️ Delete All' : '🗑️ Șterge Toate',
        type:        'danger',
        onConfirm:   () => {
            const fd = new FormData();
            fd.append('action', 'delete_all_hive_notes');
            fd.append('stup',   hiveName);
            smartFetch(fd).then(() => {
                toast(_lang==='en' ? `All notes deleted for ${hiveName}!` : `Toate notele pentru ${hiveName} au fost șterse!`, 'success');
                jurnalDataLocal = jurnalDataLocal.filter(n => n.stup !== hiveName);
                renderJurnal();
            });
        }
    });
}

/* ════════════════════════════════════════
   JURNAL
   ════════════════════════════════════════ */
let _jurnalPage = 1;
const JURNAL_PER_PAGE = 30;

function renderJurnal(page = null) {
    if (page === null) page = _jurnalPage || 1;
    const q = (document.getElementById('j-search')?.value || '').toLowerCase();

    // Dacă e schimbare de pagină și avem deja date locale, nu mai facem fetch
    const doRender = (list) => {
        const el = document.getElementById('jurnal-list');
        if(!el) return;

        const openGroups = new Set();
        el.querySelectorAll('.jurnal-group.open').forEach(group => {
            const stupName = group.getAttribute('data-stup');
            if (stupName) openGroups.add(stupName);
        });

        jurnalDataLocal = list;
        _jurnalPage = page;
        renderActivityCalendar();

        const filtered = list.filter(n => n.text.toLowerCase().includes(q) || String(n.stup).toLowerCase().includes(q));
        const total = filtered.length;
        const shown = filtered.slice(0, page * JURNAL_PER_PAGE);

        const groups = {};
        shown.forEach(n => { if(!groups[n.stup]) groups[n.stup]=[]; groups[n.stup].push(n); });

        let html = '';
        for(let stup in groups) {
            const isAdmView = window.isAdmin === true;
            const isOpenClass = openGroups.has(stup) ? ' open' : '';
            const notesHtml = groups[stup].map(n => {
                return `
                <div class="card-box" style="margin-bottom:10px;border-width:1px;">
                    <div class="actions">
                        <button class="btn-icon" style="color:var(--accent-red)" onclick="deleteNote('${n.id}')">🗑️</button>
                    </div>
                    <p style="margin:10px 0">${n.text}</p>
                    ${n.image ? `<img src="${n.image}" class="note-img" onclick="window.open(this.src)">` : ''}
                    <br><small style="opacity:0.6">${n.date} - ${n.user}</small>
                </div>`;
            }).join('');

            html += `
            <div class="jurnal-group${isOpenClass}" data-stup="${stup}">
                <div class="jurnal-group-header" onclick="this.parentElement.classList.toggle('open')">
                    <span>🍯 ${stup} (${groups[stup].length} intrări)</span>
                    <div style="display:flex;align-items:center;gap:6px">
                        <span>▼</span>
                        ${isAdmView ? `<button onclick="event.stopPropagation();deleteAllHiveNotes('${stup.replace(/'/g,String.fromCharCode(92,39))}')" title="${_lang==='en'?'Delete all notes':'Șterge toate notele'}" style="background:rgba(238,82,83,0.1);color:var(--accent-red);border:none;border-radius:6px;padding:2px 8px;font-size:0.72rem;font-weight:800;cursor:pointer;">🗑️ ALL</button>` : ''}
                    </div>
                </div>
                <div class="jurnal-group-content">
                    ${notesHtml}
                </div>
            </div>`;
        }

        const remaining = total - shown.length;
        if (remaining > 0) {
            html += `<div style="text-align:center;margin:16px 0;">
                <button onclick="renderJurnal(${page + 1})"
                    style="padding:10px 28px;background:var(--wood-light);border:1.5px solid var(--wood-mid);border-radius:var(--r-full,9999px);font-family:inherit;font-weight:800;font-size:0.88rem;cursor:pointer;color:var(--premium-brown);transition:all 0.2s;">
                    ⬇️ Mai mult (${remaining} rămase)
                </button>
            </div>`;
        }

        el.innerHTML = html || '<p style="text-align:center;color:var(--text-muted)">Nicio notă găsită.</p>';
    };

    // Paginare: dacă avem deja date și nu e prima încărcare sau căutare, folosim local
    if (page > 1 && jurnalDataLocal.length > 0 && !q) {
        doRender(jurnalDataLocal);
    } else {
        fetch('backend.php?fetch=jurnal&t='+Date.now()).then(r=>r.json()).then(doRender);
    }
}

function previewModalPhoto(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('modal-photo-img').src = e.target.result;
        document.getElementById('modal-photo-preview').style.display = 'block';
        document.getElementById('modal-photo-desc').style.display = 'block';
        document.getElementById('modal-photo-save-btn').style.display = 'inline-block';
    };
    reader.readAsDataURL(file);
}

function saveModalPhoto() {
    const input = document.getElementById('modal-photo-input');
    const desc  = document.getElementById('modal-photo-desc')?.value?.trim() || 'Fotografie stup';
    const file  = input?.files[0];
    if (!file) { toast('Selectează o poză mai întâi!', 'warning'); return; }

    const btn = document.getElementById('modal-photo-save-btn');
    if (btn) { btn.disabled = true; btn.innerHTML = '⏳...'; }

    const fd = new FormData();
    fd.append('action', 'save_note');
    fd.append('stup', currentChipName);
    fd.append('text', desc);
    fd.append('image', file);
    fd.append('csrf_token', window.csrfToken || '');

    fetch('backend.php', { method:'POST', body:fd })
    .then(r => r.text())
    .then(resp => {
        if (btn) { btn.disabled = false; btn.innerHTML = '💾 Salvează'; }
        if (resp === 'ok' || resp.includes('ok')) {
            toast('Poza salvată!', 'success');
            document.getElementById('modal-photo-input').value = '';
            document.getElementById('modal-photo-preview').style.display = 'none';
            document.getElementById('modal-photo-desc').style.display = 'none';
            document.getElementById('modal-photo-desc').value = '';
            btn.style.display = 'none';
            // Reîncărcăm jurnalul și galeria
            // Reincarcam jurnalul complet din server
            fetch('backend.php?fetch=jurnal&t='+Date.now())
                .then(r=>r.json())
                .then(data => { jurnalDataLocal = data; renderHiveGallery(); renderMiniJurnalModal(); })
                .catch(() => renderHiveGallery());
        } else {
            toast('Eroare la salvare: ' + resp, 'error');
        }
    })
    .catch(() => {
        if (btn) { btn.disabled = false; btn.innerHTML = '💾 Salvează'; }
        toast('Eroare la upload!', 'error');
    });
}

function renderHiveGallery() {
    const el = document.getElementById('hive-photo-gallery');
    if (!el) return;

    // Sortare cronologică descrescătoare (cele mai noi primele)
    const stupNotes = jurnalDataLocal
        .filter(n => n.stup === currentChipName && n.image)
        .sort((a, b) => {
            // Format: dd.mm.yyyy hh:mm
            const parseDate = d => {
                const p = (d||'').split(/[\s.:]/);
                if (p.length >= 5) return new Date(p[2], p[1]-1, p[0], p[3], p[4]).getTime();
                if (p.length >= 3) return new Date(p[2], p[1]-1, p[0]).getTime();
                return 0;
            };
            return parseDate(b.date) - parseDate(a.date);
        });

    if (!stupNotes.length) {
        el.innerHTML = "<p style='color:var(--text-muted);text-align:center;padding:20px;'>Nicio poză înregistrată pentru acest stup.</p>";
        return;
    }

    el.innerHTML = `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;padding:4px 0;">
        ${stupNotes.map(n => `
        <div style="border-radius:10px;overflow:hidden;border:1.5px solid var(--wood-light);cursor:pointer;position:relative;background:#000;" onclick="window.open('${n.image}','_blank')">
            <img src="${n.image}" style="width:100%;height:120px;object-fit:cover;display:block;transition:opacity 0.2s;" onerror="this.parentNode.style.display='none'">
            <div style="padding:5px 7px;background:var(--cream,#fdfbf7);">
                <div style="font-size:0.68rem;color:var(--text-muted);font-weight:700;">${n.date}</div>
                ${n.text ? `<div style="font-size:0.72rem;color:var(--premium-brown);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${n.text}</div>` : ''}
            </div>
        </div>`).join('')}
    </div>`;
}

function saveNoteFromJurnal() {
    const stup = document.getElementById('j-stup-sel')?.value;
    const text = document.getElementById('j-text')?.value?.trim();
    if (!stup) { toast(t('toast_select_hive'), 'warning'); return; }
    if (!text) { toast(_lang==='en'?'Please write a note!':'Scrie o notă!', 'warning'); return; }

    // Loading state pe buton
    const btn = document.querySelector('button[onclick="saveNoteFromJurnal()"]');
    if (btn) { btn.disabled = true; btn.innerHTML = '⏳...'; }

    const fd = new FormData();
    fd.append('action','save_note');
    fd.append('stup', stup);
    fd.append('text', text);
    fd.append('csrf_token', window.csrfToken || '');
    const img = document.getElementById('j-img')?.files[0];
    if(img) fd.append('image', img);

    fetch('backend.php', { method:'POST', body:fd })
    .then(r => r.text())
    .then(resp => {
        if (btn) { btn.disabled = false; btn.innerHTML = t('btn_save'); }
        if (resp === 'ok' || resp.includes('ok')) {
            document.getElementById('j-text').value = '';
            if (document.getElementById('j-img')) document.getElementById('j-img').value = '';
            toast(t('toast_note_saved'), 'success');
            // Reincarcam jurnalul din server ca sa avem poza in jurnalDataLocal
            fetch('backend.php?fetch=jurnal&t='+Date.now()).then(r=>r.json()).then(data => {
                jurnalDataLocal = data;
                renderJurnal(1);
            }).catch(() => renderJurnal(1));
        } else if (resp === 'error_permissions') {
            toast('Eroare server: folderul uploads nu are permisiuni!', 'error');
        } else if (resp === 'error_ext') {
            toast('Tip fișier invalid! Folosește JPG, PNG sau WEBP.', 'error');
        } else if (resp === 'error_upload') {
            toast('Upload eșuat. Verifică configurația serverului.', 'error');
        } else {
            toast(t('toast_error'), 'error');
        }
    })
    .catch(() => {
        if (btn) { btn.disabled = false; btn.innerHTML = t('btn_save'); }
        toast(t('toast_error'), 'error');
    });
}

function deleteNote(id) {
    showConfirmModal({
        title:       _lang==='en' ? '🗑️ Delete note' : '🗑️ Șterge nota',
        message:     t('confirm_delete_note'),
        confirmText: t('btn_delete'),
        type:        'danger',
        onConfirm:   () => { const fd=new FormData(); fd.append('action','delete_note'); fd.append('id',id); smartFetch(fd).then(()=>renderJurnal()); }
    });
}


/* ════════════════════════════════════════
   CALENDAR ACTIVITATE JURNAL
   ════════════════════════════════════════ */
async function renderActivityCalendar(yearArg, monthArg) {
    const container = document.getElementById('activity-calendar');
    if (!container) return;

    // Actualizăm starea lunii vizualizate
    if (yearArg !== undefined) _calendarYear = yearArg;
    if (monthArg !== undefined) _calendarMonth = monthArg;

    const year = _calendarYear;
    const month = _calendarMonth;

    // Preluăm sarcinile programate pentru a afișa bulinele în viitor
    const tasks = await fetch('backend.php?fetch=tasks&t=' + Date.now()).then(r => r.json());

    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDay    = new Date(year, month, 1).getDay(); 
    const startOffset = (firstDay === 0 ? 6 : firstDay - 1); 

    const activeDays = {};
    const parseRoDate = str => {
        if (!str) return null;
        const p = str.split(' ')[0].split('.');
        if (p.length === 3) return new Date(p[2], p[1]-1, p[0]);
        return null;
    };

    // 1. Marcăm zilele cu activități din jurnal (trecut)
    const jData = typeof jurnalDataLocal !== 'undefined' ? jurnalDataLocal : [];
    jData.forEach(n => {
        const d = parseRoDate(n.date);
        if (d && d.getFullYear() === year && d.getMonth() === month) {
            const day = d.getDate();
            if (!activeDays[day]) activeDays[day] = { jurnal: 0, task: 0 };
            if (n.text.includes('✅ Sarcină rezolvată')) activeDays[day].task++;
            else activeDays[day].jurnal++;
        }
    });

    // 2. Marcăm zilele cu sarcini programate (viitor/nerezolvate)
    tasks.forEach(t => {
        if (t.done) return; 
        const d = parseRoDate(t.date);
        if (d && d.getFullYear() === year && d.getMonth() === month) {
            const day = d.getDate();
            if (!activeDays[day]) activeDays[day] = { jurnal: 0, task: 0 };
            activeDays[day].task++; 
        }
    });

    const months = ['Ianuarie','Februarie','Martie','Aprilie','Mai','Iunie','Iulie','August','Septembrie','Octombrie','Noiembrie','Decembrie'];
    const days   = ['Lu','Ma','Mi','Jo','Vi','Sâ','Du'];

    const prevM = month === 0 ? 11 : month - 1;
    const prevY = month === 0 ? year - 1 : year;
    const nextM = month === 11 ? 0 : month + 1;
    const nextY = month === 11 ? year + 1 : year;

    let html = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:6px;">
                <button onclick="renderActivityCalendar(${prevY}, ${prevM})" style="background:var(--wood-light,#e8d5b7);border:none;border-radius:6px;width:28px;height:28px;cursor:pointer;font-weight:900;color:var(--premium-brown);display:flex;align-items:center;justify-content:center;">‹</button>
                <span style="font-weight:800;color:var(--premium-brown);font-size:1rem;min-width:115px;text-align:center;">${months[month]} ${year}</span>
                <button onclick="renderActivityCalendar(${nextY}, ${nextM})" style="background:var(--wood-light,#e8d5b7);border:none;border-radius:6px;width:28px;height:28px;cursor:pointer;font-weight:900;color:var(--premium-brown);display:flex;align-items:center;justify-content:center;">›</button>
            </div>
            <div style="display:flex;gap:8px;font-size:0.7rem;font-weight:700;">
                <span style="display:flex;align-items:center;gap:3px;"><span style="width:7px;height:7px;background:#10ac84;border-radius:50%;display:inline-block;"></span>Obs</span>
                <span style="display:flex;align-items:center;gap:3px;"><span style="width:7px;height:7px;background:#3498db;border-radius:50%;display:inline-block;"></span>Sarc</span>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;text-align:center;">`;

    days.forEach(d => { html += `<div style="font-size:0.7rem;font-weight:800;color:var(--text-muted);padding:2px 0;">${d}</div>`; });

    for (let i = 0; i < startOffset; i++) html += '<div></div>';

    const now = new Date();
    for (let day = 1; day <= daysInMonth; day++) {
        const isToday = (day === now.getDate() && month === now.getMonth() && year === now.getFullYear());
        const act = activeDays[day];
        const bg = isToday ? 'var(--honey,#d4860b)' : 'var(--white,#fff)';
        const col = isToday ? '#fff' : 'var(--text-dark,#2c3e50)';
        const dateStr = `${String(day).padStart(2,'0')}.${String(month+1).padStart(2,'0')}.${year}`;

        html += `
        <div style="position:relative;aspect-ratio:1;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:8px;background:${bg};color:${col};border:1px solid var(--wood-light);font-size:0.85rem;font-weight:${isToday?900:700};cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,0.02);" 
             onclick="filterJurnalByDate('${dateStr}',${day},${month},${year})">
            ${day}
            ${(act?.jurnal || act?.task) ? `<div style="display:flex;gap:2px;position:absolute;bottom:3px;">
                ${act.jurnal ? '<span style="width:5px;height:5px;background:#10ac84;border-radius:50%;display:inline-block;"></span>' : ''}
                ${act.task   ? '<span style="width:5px;height:5px;background:#3498db;border-radius:50%;display:inline-block;"></span>' : ''}
            </div>` : ''}
        </div>`;
    }
    html += '</div>';
    container.innerHTML = html;
}

function filterJurnalByDate(dateStr, day, month, year) {
    // Deschidem doar fereastra pop-up cu detaliile zilei
    showDayPopup(dateStr, day, month, year);
    
    // Am eliminat codul care seta search.value și apela renderJurnal() 
    // pentru a păstra lista de note din pagina de Jurnal intactă.
}

function showDayPopup(dateStr, day, month, year) {
    const months = ['Ianuarie','Februarie','Martie','Aprilie','Mai','Iunie',
                    'Iulie','August','Septembrie','Octombrie','Noiembrie','Decembrie'];
    const ddmm  = dateStr.substring(0, 5);
    const title = `${day} ${months[month]} ${year}`;

    // Notițe din jurnal pentru ziua asta
    const notes = jurnalDataLocal.filter(n => {
        const p = n.date?.split(' ')[0]?.split('.');
        if (!p || p.length < 3) return false;
        return p[0] === String(day).padStart(2,'0') && p[1] === String(month+1).padStart(2,'0');
    });

    // Sarcini rezolvate în ziua asta
    const resolved = jurnalDataLocal.filter(n =>
        n.text?.includes('✅ Sarcină rezolvată') && n.date?.startsWith(ddmm)
    );

    let modal = document.getElementById('day-popup-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'day-popup-modal';
        modal.style.cssText = 'display:none;position:fixed;inset:0;z-index:9600;background:rgba(26,31,38,0.6);backdrop-filter:blur(4px);align-items:center;justify-content:center;';
        modal.innerHTML = `<div style="background:var(--cream,#fdfbf7);width:92%;max-width:480px;max-height:80vh;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.25);display:flex;flex-direction:column;">
            <div style="background:linear-gradient(135deg,var(--wood-dark,#a6845c),var(--honey,#d4860b));padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                <span id="dpm-title" style="font-weight:900;font-size:1rem;color:#fff;"></span>
                <button onclick="document.getElementById('day-popup-modal').style.display='none'" style="background:rgba(255,255,255,0.2);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:0.9rem;">✕</button>
            </div>
            <div id="dpm-body" style="overflow-y:auto;padding:16px 20px;flex:1;"></div>
        </div>`;
        modal.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };
        document.body.appendChild(modal);
    }

    document.getElementById('dpm-title').textContent = title;

    let body = '';

    // Sarcini/Tratamente programate pentru ziua asta
    // (fetch tasks și afișăm cele cu data = ddmm/year)
    fetch('backend.php?fetch=tasks&t=' + Date.now()).then(r => r.json()).then(tasks => {
        const dayTasks = tasks.filter(t => {
            if (!t.date) return false;
            const p = t.date.split('.');
            return p[0] === String(day).padStart(2,'0') && p[1] === String(month+1).padStart(2,'0');
        });

        if (dayTasks.length > 0) {
            body += `<div style="margin-bottom:14px;">
                <div style="font-weight:800;font-size:0.82rem;color:var(--honey,#d4860b);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">📋 ${_lang==='en'?'Tasks':'Sarcini'} (${dayTasks.length})</div>
                ${dayTasks.map(t => `<div style="padding:8px 10px;background:${t.done?'rgba(16,172,132,0.08)':'rgba(212,134,11,0.07)'};border-radius:8px;margin-bottom:6px;font-size:0.85rem;display:flex;align-items:center;gap:8px;">
                    <span style="font-size:1rem;">${t.done?'✅':'⏰'}</span>
                    <span style="${t.done?'text-decoration:line-through;opacity:0.6':''}">${t.text}</span>
                </div>`).join('')}
            </div>`;
        }

        if (notes.length > 0) {
            body += `<div style="margin-bottom:14px;">
                <div style="font-weight:800;font-size:0.82rem;color:var(--accent-green);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">📝 ${_lang==='en'?'Journal notes':'Notițe jurnal'} (${notes.length})</div>
                ${notes.map(n => `<div style="padding:8px 10px;background:rgba(16,172,132,0.06);border-radius:8px;margin-bottom:6px;font-size:0.85rem;">
                    <div style="font-weight:700;color:var(--premium-brown);margin-bottom:3px;">🐝 ${n.stup}</div>
                    <div style="opacity:0.8;line-height:1.4;">${n.text}</div>
                    <div style="font-size:0.72rem;opacity:0.5;margin-top:4px;">${n.date} · ${n.user}</div>
                </div>`).join('')}
            </div>`;
        }

        if (resolved.length > 0) {
            body += `<div>
                <div style="font-weight:800;font-size:0.82rem;color:var(--accent-blue);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">✅ ${_lang==='en'?'Completed tasks':'Sarcini rezolvate'} (${resolved.length})</div>
                ${resolved.map(n => `<div style="padding:8px 10px;background:rgba(52,152,219,0.06);border-radius:8px;margin-bottom:6px;font-size:0.85rem;opacity:0.7;">${n.text.replace('✅ Sarcină rezolvată: ','')}</div>`).join('')}
            </div>`;
        }

        if (!body) {
            body = `<p style="text-align:center;opacity:0.5;padding:20px 0;">${_lang==='en'?'No activity recorded for this day.':'Nicio activitate înregistrată pentru această zi.'}</p>`;
        }

        document.getElementById('dpm-body').innerHTML = body;
        modal.style.display = 'flex';
    });
}

/* ════════════════════════════════════════
   SARCINI (TASKS)
   ════════════════════════════════════════ */
function saveTask() {
    const text = document.getElementById('t-text')?.value;
    if(!text) return;
    const fd=new FormData(); fd.append('action','save_task'); fd.append('text',text); fd.append('date', new Date().toLocaleDateString('ro-RO'));
    smartFetch(fd).then(()=>{ document.getElementById('t-text').value=''; renderTasks(); fetchData(); });
}

function renderTasks() {
    fetch('backend.php?fetch=tasks&t='+Date.now()).then(r=>r.json()).then(list => {
        const el = document.getElementById('task-list');
        if(!el) return;

        // 1. Salvăm starea grupurilor deschise înainte să redesenăm lista
        const openGroups = new Set();
        el.querySelectorAll('.jurnal-group.open').forEach(group => {
            const stupId = group.getAttribute('data-stup');
            if (stupId) openGroups.add(stupId);
        });

        if (list.length === 0) {
            el.innerHTML = "<p style='text-align:center;color:var(--text-muted)'>Nicio sarcină.</p>";
            return;
        }

        // 2. Grupăm sarcinile în funcție de stup
        const groups = {};
        list.forEach(t => {
            const match = t.text.match(/-\s*(.*)$/);
            const stupName = match ? match[1].trim() : "General / Stupină";
            
            if (!groups[stupName]) groups[stupName] = [];
            groups[stupName].push(t);
        });

        // 3. Generăm HTML-ul
        let html = '';
        for (let stup in groups) {
            const tasks = groups[stup];
            const activeTasks = tasks.filter(t => !t.done).length;
            const statusIcon = activeTasks > 0 ? '⏳' : '✅';
            const isOpenClass = openGroups.has(stup) ? ' open' : '';

            // Construim HTML-ul pentru fiecare task din interiorul grupului
            const tasksHtml = tasks.map(t => {
                let cleanText = t.text.replace(/-\s*(.*)$/, '').trim() || t.text;
                let styleDone = t.done ? 'opacity:0.5; background:var(--white)' : '';
                let reminderHtml = t.has_reminder ? '<span title="Reminder Activ">🔔</span> ' : '';
                let resolveBtn = !t.done 
                    ? `<button onclick="resolveTask('${t.id}')" style="border:none;background:none;color:var(--accent-green);font-size:1.4rem;cursor:pointer;" title="Marchează ca rezolvat">✅</button>` 
                    : `<span style="font-size:0.8rem;color:var(--text-muted);font-weight:bold;">Rezolvat</span>`;

                return `
                <div style="padding:12px;border-bottom:1px solid var(--wood-light);display:flex;justify-content:space-between;align-items:center;${styleDone}">
                    <span>
                        ${reminderHtml}
                        <b style="color:var(--text-dark); font-size:0.95rem;">${cleanText}</b><br>
                        <small style="color:var(--text-muted)">${t.date||''}</small>
                    </span>
                    <div style="display:flex;gap:10px;">
                        ${resolveBtn}
                        <button onclick="deleteTask('${t.id}')" style="border:none;background:none;color:var(--accent-red);font-size:1.4rem;cursor:pointer;" title="Șterge sarcina">✕</button>
                    </div>
                </div>`;
            }).join('');

            // Adăugăm grupul și task-urile în HTML-ul final
            html += `
            <div class="jurnal-group${isOpenClass}" data-stup="${stup}">
                <div class="jurnal-group-header" onclick="this.parentElement.classList.toggle('open')">
                    <span>${statusIcon} ${stup} (${tasks.length} sarcini)</span>
                    <div style="display:flex;align-items:center;gap:6px"><span>▼</span></div>
                </div>
                <div class="jurnal-group-content">
                    ${tasksHtml}
                </div>
            </div>`;
        }

        el.innerHTML = html;
    });
}
function resolveTask(id) {
    const fd=new FormData(); fd.append('action','resolve_task'); fd.append('id',id);
    smartFetch(fd).then(()=>{ renderTasks(); renderJurnal(); fetchData(); });
}

function deleteTask(id) {
    showConfirmModal({
        title:       _lang==='en' ? '🗑️ Delete task' : '🗑️ Șterge Sarcina',
        message:     t('confirm_delete_task'),
        confirmText: t('btn_delete'),
        type:        'danger',
        onConfirm:   () => { const fd=new FormData(); fd.append('action','delete_task'); fd.append('id',id); smartFetch(fd).then(()=>{ renderTasks(); fetchData(); }); }
    });
}

/* ════════════════════════════════════════
   TRATAMENTE
   ════════════════════════════════════════ */
function updateTratDesc() {
    const sel = document.getElementById('t-tip-trat');
    if(sel && sel.options.length>0) document.getElementById('trat-desc-text').innerText = sel.options[sel.selectedIndex].getAttribute('data-desc');
}

function scheduleTreatment() {
    const stup = document.getElementById('t-stup-trat')?.value;
    if(!stup) return toast('Selectează mai întâi un stup!', 'warning');
    const rawVal = document.getElementById('t-tip-trat')?.value || '';
    const parts  = rawVal.split('|');
    if(parts.length < 3 || !parts[0].trim()) return toast('Selectează un tratament din listă!', 'warning');
    const preAlert  = document.getElementById('t-pre-alert')?.checked || false;
    const startDate = document.getElementById('t-start-date')?.value || new Date().toISOString().split('T')[0];
    const fd = new FormData();
    fd.append('action','save_treatment'); fd.append('stup', stup);
    fd.append('nume', parts[0].trim()); fd.append('doze', parts[1].trim()); fd.append('interval', parts[2].trim());
    fd.append('preAlert', preAlert); fd.append('startDate', startDate);
    smartFetch(fd).then(r => {
        if(r && !r.offline) toast('Schema de tratament generată! Verifică lista de sarcini.', 'success');
        renderTasks(); fetchData();
    });
}

function updateModalTratDesc() {
    const sel = document.getElementById('m-tip-trat');
    if(sel && sel.options.length>0) document.getElementById('m-trat-desc-text').innerText = sel.options[sel.selectedIndex].getAttribute('data-desc');
}

function scheduleModalTreatment() {
    if(!currentChipName) { toast(_lang==='en'?'Cannot identify current hive!':'Eroare identificare stup curent!', 'error'); return; };
    const rawVal = document.getElementById('m-tip-trat')?.value || '';
    const parts  = rawVal.split('|');
    if(parts.length < 3 || !parts[0].trim()) return toast('Selectează un tratament din listă!', 'warning');
    const preAlert  = document.getElementById('m-pre-alert')?.checked || false;
    const startDate = document.getElementById('m-start-date')?.value || new Date().toISOString().split('T')[0];
    const fd = new FormData();
    fd.append('action','save_treatment'); fd.append('stup', currentChipName);
    fd.append('nume', parts[0].trim()); fd.append('doze', parts[1].trim()); fd.append('interval', parts[2].trim());
    fd.append('preAlert', preAlert); fd.append('startDate', startDate);
    smartFetch(fd).then(r => {
        if(r && !r.offline) toast(`Programare salvată pentru ${currentChipName}!`, 'success');
        renderTasks(); fetchData();
    });
}

/* ════════════════════════════════════════
   EXPORT CSV
   ════════════════════════════════════════ */
function exportCSV(type = 'jurnal') {
    const configs = {
        jurnal:    { url:'backend.php?fetch=jurnal',    name:'Jurnal',     cols:['Data','Stup','Nota','User'],        fn: r => [`"${r.date}"`,`"${(r.stup||'').replace(/"/g,'""')}"`,`"${(r.text||'').replace(/"/g,'""')}"`,`"${r.user||''}"`].join(',') },
        harvest:   { url:'backend.php?fetch=harvest',   name:'Recolta',    cols:['Data','Stup','Tip','Kg','Pret_RON'], fn: r => [`"${r.date}"`,`"${r.stup}"`,`"${r.tip}"`,r.kg,r.pret].join(',') },
        expenses:  { url:'backend.php?fetch=expenses',  name:'Cheltuieli', cols:['Data','Stup','Descriere','Suma'],    fn: r => [`"${r.date}"`,`"${r.stup}"`,`"${(r.desc||'').replace(/"/g,'""')}"`,r.suma].join(',') },
        inventory: { url:'backend.php?fetch=inventory', name:'Inventar',   cols:['Articol','Cantitate','Unitate','Categorie'], fn: r => [`"${(r.item||'').replace(/"/g,'""')}"`,r.qty,`"${r.type}"`,`"${r.category||''}"`].join(',') },
    };
    const cfg = configs[type] || configs.jurnal;
    fetch(cfg.url).then(r=>r.json()).then(data=>{
        let csv = cfg.cols.join(',') + '\n';
        data.forEach(r => { csv += cfg.fn(r) + '\n'; });
        const blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href = url; a.download = `Export_${cfg.name}_${new Date().toLocaleDateString('ro-RO').replace(/\./g,'-')}.csv`;
        document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
        toast(`Export ${cfg.name} descărcat!`, 'success');
    });
}

/* ════════════════════════════════════════
   ALERTE & REZOLVARE
   ════════════════════════════════════════ */
function checkAlerts(data) {
    const list = document.getElementById('active-alerts-list');
    if(!list) return;
    let h = '';
    document.querySelectorAll('.hive-task-bell, .map-task-bell').forEach(el=>el.remove());

    let hiveGroups = {};
    data.forEach(item => {
        const name = item.meta.nickname || chipIDtoName[item.chipID] || item.chipID;
        const aID_roi = item.chipID+'_roi', aID_bat = item.chipID+'_bat';
        const isMaint = item.meta.maintenance===true || item.meta.maintenance==="true";
        if(!hiveGroups[name]) hiveGroups[name]={alerts:[],tasks:[]};
        if(item.isManual!==true) {
            // Alertă roire: -0.5 kg/24h avertisment, -1.5 kg/24h critic
            const isOffline = item.ts && (Math.floor(Date.now()/1000) - item.ts) > 86400;
            // Resetam rezolvarea din sesiune cand apare o citire noua de la senzor
            let roiResolvedNow = false;
            if (_roiResolvedInSession.has(aID_roi)) {
                const savedTs = _roiResolvedInSession.getTs(aID_roi);
                const currTs  = parseInt(item.ts) || 0;
                if (savedTs > 0 && currTs > savedTs) {
                    // Citire noua dupa rezolvare - stergem, alerta poate reaparea
                    _roiResolvedInSession.delete(aID_roi);
                } else {
                    roiResolvedNow = true;
                }
            }
            if(item.delta24<=-0.5 && !roiResolvedNow && !isMaint && !isOffline) {
                sendRoireEmailIfNeeded(item);
                const isCritical = item.delta24 <= -1.5;
                const alertMsg = isCritical
                    ? `🚨 ${_lang==='en'?'CRITICAL SWARM / LOSS':'ROIRE CRITICĂ / PIERDERE'} (${_lang==='en'?'Drop':'Scădere'}: ${item.delta24.toFixed(2)} kg)`
                    : `⚠️ ${_lang==='en'?'WEIGHT DROP — CHECK HIVE':'SCĂDERE GREUTATE — VERIFICĂ'} (${_lang==='en'?'Drop':'Scădere'}: ${item.delta24.toFixed(2)} kg)`;
                hiveGroups[name].alerts.push({id:aID_roi,cid:item.chipID,hiveName:name,msg:alertMsg,type:'Alertă Greutate'});
            }
            // Alertă baterie: < 3.4V și stup a mai transmis date recent
            if(item.battery<3.4 && !resolvedAlertIDs.includes(aID_bat) && !isOffline)
                hiveGroups[name].alerts.push({id:aID_bat,cid:item.chipID,hiveName:name,msg:`🔋 ${_lang==='en'?'Low battery':'Baterie descărcată'} (${item.battery.toFixed(2)}V)`,type:'Baterie slabă'});
        }
    });

    fetch('backend.php?fetch=tasks&t='+Date.now()).then(r=>r.json()).then(tasks=>{
        const today = new Date(); today.setHours(0,0,0,0);
        let tasksByHive = {};
        // Numele stupilor la care userul are acces
        const accessibleNames = new Set(hivesDataLocal.map(hv =>
            hv.meta.nickname || chipIDtoName[hv.chipID] || String(hv.chipID)
        ));

        tasks.forEach(t=>{
            if(!t.done){
                // Folosim câmpul stup dacă există (taskuri noi), altfel extragem din text (taskuri vechi)
                const stupTaskName = t.stup || (t.text.match(/-\s*(.*)$/)?.[1]?.trim()) || "Stupină";
                // Afișăm task-ul dacă e pentru un stup accesibil SAU dacă userul e admin
                if (!userPermissions.isAdmin && !accessibleNames.has(stupTaskName)) return;
                if(!tasksByHive[stupTaskName]) tasksByHive[stupTaskName]=[];
                tasksByHive[stupTaskName].push(t);
                if(!hiveGroups[stupTaskName]) hiveGroups[stupTaskName]={alerts:[],tasks:[]};
                hiveGroups[stupTaskName].tasks.push(t);
            }
        });

        hivesDataLocal.forEach(hv=>{
            const nm = hv.meta.nickname || chipIDtoName[hv.chipID] || hv.chipID;
            const hiveTasks = tasksByHive[nm]||[];
            if(hiveTasks.length>0){
                const dCard = document.getElementById('hive-card-'+hv.chipID);
                if(dCard && !dCard.querySelector('.hive-task-bell')){
                    let bell=document.createElement('div'); bell.className='hive-task-bell'; bell.title=`${hiveTasks.length} sarcini în așteptare`; bell.innerHTML='🔔';
                    bell.onclick=(e)=>showBellTaskMenu(e,hiveTasks); dCard.appendChild(bell);
                }
                const mIcon=document.getElementById('map-hive-'+hv.chipID);
                if(mIcon && !mIcon.querySelector('.map-task-bell')){
                    let bellM=document.createElement('div'); bellM.className='map-task-bell'; bellM.innerHTML='🔔';
                    bellM.onclick=(e)=>showBellTaskMenu(e,hiveTasks); mIcon.appendChild(bellM);
                }
            }
        });

        let activeCount=0;
        for(let stup in hiveGroups){
            const group=hiveGroups[stup];
            if(group.alerts.length===0 && group.tasks.length===0) continue;
            activeCount++;
            let groupHtml=`<div class="alert-accordion"><div class="alert-accordion-header" onclick="this.parentElement.classList.toggle('open')"><span>🐝 ${stup} (${group.alerts.length+group.tasks.length})</span><span>▼</span></div><div class="alert-accordion-body">`;
            group.alerts.forEach(a=>{
                groupHtml+=`<div style="margin-bottom:10px;font-size:0.85rem;border-bottom:1px solid #eee;padding-bottom:5px;display:flex;justify-content:space-between;align-items:center;">
                    <span>${a.msg}</span>
                    <button class="btn-resolve" style="padding:4px 8px;font-size:0.7rem;" onclick="showConfirmModal({title:'✅ ${_lang==='en'?'Resolve Alert':'Rezolvă Alertă'}',message:'${a.hiveName||a.cid}: ${a.msg}',confirmText:'${_lang==='en'?'Mark Resolved':'Marchează Rezolvat'}',type:'success',onConfirm:()=>resolveAlert('${a.id}','${a.cid}','${a.type}')})">✅</button>
                </div>`;
            });
            group.tasks.forEach(t=>{
                if (!t.date) return;
                const p=t.date.split('.');
                if (p.length < 3) return;
                const taskDate=new Date(p[2],p[1]-1,p[0]);
                if (isNaN(taskDate.getTime())) return;
                const diffDays=Math.ceil((taskDate-today)/(1000*60*60*24));
                let color="#3498db", statusText=`Peste ${diffDays} zile`;
                if(diffDays===0){color="#e67e22";statusText="URGENT: AZI!";}
                if(diffDays<0){color="#e74c3c";statusText="ÎNTÂRZIAT!";}
                if(t.has_reminder && diffDays<=2 && diffDays>=0){
                    const match=t.text.match(/-\s*(.*)$/); const stupTaskName=match?match[1].trim():"Stupină";
                    const fd=new FormData(); fd.append('action','send_alert_email'); fd.append('alert_id','task_'+t.id); fd.append('stup',stupTaskName); fd.append('msg',`📋 REAMINTIRE: Ai programat "${t.text}" pentru data de ${t.date}.`); smartFetch(fd);
                }
                groupHtml+=`<div style="margin-bottom:10px;font-size:0.85rem;border-left:3px solid ${color};padding-left:8px;display:flex;justify-content:space-between;align-items:center;">
                    <span><b>[${statusText}]</b> ${t.text}<br><small>${t.date}</small></span>
                    <button class="btn-resolve" style="padding:4px 8px;font-size:0.7rem;background:var(--accent-green);" onclick="showConfirmModal({title:'✅ Rezolvă Sarcina',message:'${t.text}',confirmText:'Marchează Efectuat',type:'success',onConfirm:()=>resolveTask('${t.id}')})">✅</button>
                </div>`;
            });
            groupHtml+=`</div></div>`;
            h+=groupHtml;
        }
        if(list) list.innerHTML = activeCount>0 ? h : '<p style="text-align:center;color:var(--text-muted)">✅ Totul e sub control. Nicio alertă activă.</p>';
    });
}

function resolveAlert(id, stup, msg) {
    const fd=new FormData(); fd.append('action','resolve_alert'); fd.append('alert_id',id); fd.append('stup',stup); fd.append('msg',msg);
    smartFetch(fd).then(()=>{
        resolvedAlertIDs.push(id);
        if (id.endsWith('_roi')) {
            // Salvam ts-ul citirii curente - alerta reapare doar la citire noua (ts mai mare)
            const hive = hivesDataLocal.find(h => String(h.chipID) === String(stup));
            _roiResolvedInSession.add(id, parseInt(hive?.ts) || 0);
        }
        renderDashboard();
        fetchData();
        toast(t('toast_saved'), 'success');
    });
}

function openResolvedAlerts() {
    fetch('backend.php?fetch=alerte&t='+Date.now()).then(r=>r.text()).then(text=>{
        let list=[]; try{list=JSON.parse(text);}catch(e){} if(!Array.isArray(list)) list=[];
        let html=list.map(a=>`<div style="padding:10px;border-bottom:1px dashed var(--wood-light);"><b style="color:var(--premium-brown)">${a.stup}</b>: <span>${a.msg}</span><br><small style="color:var(--text-muted)">${a.date} - ${a.user}</small></div>`).join('');
        const el=document.getElementById('resolved-list');
        if(el) el.innerHTML=html||'<p style="text-align:center;color:var(--text-muted)">Nicio alertă în istoric.</p>';
        const modal=document.getElementById('resolvedModal');
        if(modal) modal.style.display='flex';
    });
}


/* ════════════════════════════════════════
   MODAL CONFIRMARE GENERIC
   înlocuiește confirm() pentru acțiuni UX
   ════════════════════════════════════════ */
function showConfirmModal(opts) {
    // opts: { title, message, confirmText, cancelText, type, onConfirm }
    let modal = document.getElementById('confirm-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'confirm-modal';
        modal.style.cssText = 'display:none;position:fixed;inset:0;z-index:99999;background:rgba(26,31,38,0.7);backdrop-filter:blur(6px);align-items:center;justify-content:center;';
        modal.innerHTML = `<div style="background:var(--cream,#fdfbf7);width:92%;max-width:400px;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
            <div id="cm-header" style="padding:20px 22px 14px;border-bottom:1px solid var(--wood-light);">
                <span id="cm-title" style="font-weight:900;font-size:1.05rem;color:var(--premium-brown)"></span>
            </div>
            <div style="padding:18px 22px;">
                <p id="cm-message" style="margin:0;font-size:0.92rem;line-height:1.6;color:var(--text-dark)"></p>
            </div>
            <div style="padding:14px 22px 20px;display:flex;gap:10px;justify-content:flex-end;">
                <button id="cm-cancel" style="padding:10px 20px;background:#f5f0e8;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;color:var(--premium-brown)"></button>
                <button id="cm-confirm" style="padding:10px 22px;border:none;border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;color:#fff;"></button>
            </div>
        </div>`;
        document.body.appendChild(modal);
    }
    const colors = { danger:'#ee5253', success:'var(--accent-green)', warning:'#f39c12', info:'#3498db' };
    const bgCol  = colors[opts.type || 'danger'];
    document.getElementById('cm-title').textContent   = opts.title || '';
    document.getElementById('cm-message').textContent = opts.message || '';
    document.getElementById('cm-cancel').textContent  = opts.cancelText  || (t ? t('btn_cancel') : 'Anulează');
    document.getElementById('cm-confirm').textContent = opts.confirmText || (t ? t('btn_resolve') : 'Confirmat');
    document.getElementById('cm-confirm').style.background = bgCol;
    document.getElementById('cm-header').style.borderLeftColor = bgCol;

    modal.style.display = 'flex';

    document.getElementById('cm-cancel').onclick  = () => { modal.style.display = 'none'; };
    document.getElementById('cm-confirm').onclick = () => { modal.style.display = 'none'; if (opts.onConfirm) opts.onConfirm(); };

    // Esc închide
    modal.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };
}

/* ════════════════════════════════════════
   TOOLTIP SĂNĂTATE
   ════════════════════════════════════════ */
function showHTip(txt, e) {
    const tip=document.getElementById('health-tip'); if(!tip||!txt) return;
    tip.innerText=txt; tip.style.display='block'; tip.style.left=(e.pageX+12)+'px'; tip.style.top=(e.pageY-10)+'px';
}
function hideHTip() { const tip=document.getElementById('health-tip'); if(tip) tip.style.display='none'; }

/* ════════════════════════════════════════
   WORD EXPORT
   ════════════════════════════════════════ */
function getWordHTML(htmlContent) {
    const preHtml = `<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><head><meta charset='utf-8'><title>Raport</title><style>@page WordSection1 { size: 8.5in 11.0in; margin: 1.0in; } div.WordSection1 { page: WordSection1; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2c3e50; line-height: 1.6; } h1 { color: #2c3e50; font-size: 24pt; border-bottom: 2px solid #d1b490; padding-bottom: 10px; text-align: center; text-transform: uppercase; margin-top: 0; } h2, h3 { color: #5d4037; margin-top: 25px; margin-bottom: 10px; border-left: 5px solid #d1b490; padding-left: 10px; font-size: 16pt; } table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 30px; } th { background-color: #5d4037; color: #ffffff; padding: 12px; text-align: left; border: 1px solid #5d4037; } td { padding: 10px 12px; border: 1px solid #bdc3c7; } .summary-box { background-color: #fdfbf7; border: 2px solid #e6d3ba; padding: 15px; margin-bottom: 30px; border-radius: 5px; } .text-right { text-align: right; } .text-center { text-align: center; } .badge-green { color: #27ae60; font-weight: bold; } .badge-red { color: #c0392b; font-weight: bold; }</style></head><body><div class='WordSection1'>`;
    return preHtml + htmlContent + "</div></body></html>";
}

function exportHTMLToWord(htmlContent, filename) {
    const html = getWordHTML(htmlContent);
    const blob = new Blob(['\ufeff', html], { type: 'application/msword' });
    const downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);
    downloadLink.href     = URL.createObjectURL(blob);
    downloadLink.download = filename + '.doc';
    downloadLink.click();
    document.body.removeChild(downloadLink);
}


/* ════════════════════════════════════════
   PDF GENERATOR (client-side, fără lib externe)
   Folosește print-to-PDF prin window.print()
   cu CSS @media print dedicat
   ════════════════════════════════════════ */
function exportAsPDF(htmlContent, title) {
    const win = window.open('', '_blank', 'width=900,height=700');
    if (!win) { toast('Permite pop-up-urile pentru a genera PDF-ul!', 'warning'); return; }

    const pdfHTML = `<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>${title}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #2c3e50; background: #fff; padding: 0; }

  /* Antet PDF */
  .pdf-header { background: linear-gradient(135deg, #5d4037, #d4860b); padding: 28px 36px; color: #fff; display: flex; justify-content: space-between; align-items: center; }
  .pdf-header h1 { font-size: 20pt; font-weight: 900; letter-spacing: -0.5px; color: #ffffff; text-shadow: 0 1px 3px rgba(0,0,0,0.4); }
  .pdf-header .pdf-meta { text-align: right; font-size: 9pt; opacity: 0.85; line-height: 1.6; }
  .pdf-logo { font-size: 2.2rem; margin-right: 14px; }
  .pdf-header-left { display: flex; align-items: center; }

  /* Corp */
  .pdf-body { padding: 28px 36px; }

  h1 { color: #5d4037; font-size: 16pt; border-bottom: 2.5px solid #d1b490; padding-bottom: 8px; margin: 24px 0 14px; }
  h2, h3 { color: #5d4037; font-size: 12pt; margin: 18px 0 8px; padding-left: 8px; border-left: 4px solid #d1b490; }

  /* Summary box */
  .summary-box { background: #fdf8ef; border: 1.5px solid #d1b490; border-radius: 8px; padding: 14px 18px; margin: 14px 0 20px; font-size: 10pt; line-height: 1.8; }
  .summary-box b { color: #5d4037; }

  /* KPI row */
  .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 16px 0; }
  .kpi-box { background: #fdf8ef; border: 1px solid #e6d3ba; border-radius: 8px; padding: 12px 14px; text-align: center; }
  .kpi-box .kpi-num { font-size: 20pt; font-weight: 900; color: #5d4037; font-family: 'Courier New', monospace; }
  .kpi-box .kpi-lbl { font-size: 8pt; color: #7f8c8d; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px; }

  /* Tabele */
  table { width: 100%; border-collapse: collapse; margin: 12px 0 20px; font-size: 9.5pt; }
  thead tr { background: #5d4037; }
  th { color: #fff; padding: 9px 12px; text-align: left; font-weight: 700; font-size: 8.5pt; letter-spacing: 0.3px; }
  td { padding: 8px 12px; border-bottom: 1px solid #f0e8dc; }
  tr:nth-child(even) td { background: #fdf8ef; }
  tr:last-child td { border-bottom: none; }
  .text-right { text-align: right; }
  .text-center { text-align: center; }

  /* Badge-uri */
  .badge-green { color: #27ae60; font-weight: 700; }
  .badge-red   { color: #c0392b; font-weight: 700; }
  .badge-honey { color: #d4860b; font-weight: 700; }

  /* Footer */
  .pdf-footer { margin-top: 30px; padding: 14px 36px; border-top: 1px solid #e6d3ba; display: flex; justify-content: space-between; font-size: 8pt; color: #999; }

  @media print {
    @page { size: A4; margin: 0; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .pdf-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    thead tr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
  }
</style>
</head>
<body>
  <div class="pdf-header">
    <div class="pdf-header-left">
      <span class="pdf-logo">🍯</span>
      <div>
        <h1>Matca</h1>
        <div style="font-size:9pt;opacity:0.8;margin-top:2px">${title}</div>
      </div>
    </div>
    <div class="pdf-meta">
      Generat: ${new Date().toLocaleDateString('ro-RO', {day:'2-digit',month:'long',year:'numeric'})}<br>
      Ora: ${new Date().toLocaleTimeString('ro-RO', {hour:'2-digit',minute:'2-digit'})}
    </div>
  </div>
  <div class="pdf-body">
    ${htmlContent}
  </div>
  <div class="pdf-footer">
    <span>🍯 MiereaPofta — Sistem Management Apicol</span>
    <span>Document generat automat</span>
  </div>
  <div class="no-print" style="text-align:center;padding:20px;background:#f5f0e8;border-top:2px solid #d1b490;">
    <p style="font-size:12pt;font-weight:bold;color:#5d4037;margin-bottom:10px;">Document pregătit pentru tipărire / salvare PDF</p>
    <button onclick="window.print()" style="padding:12px 32px;background:#d4860b;color:#fff;border:none;border-radius:8px;font-size:12pt;font-weight:bold;cursor:pointer;margin-right:10px;">🖨️ Salvează ca PDF</button>
    <button onclick="window.close()" style="padding:12px 20px;background:#eee;border:none;border-radius:8px;font-size:11pt;cursor:pointer;">Închide</button>
  </div>
</body>
</html>`;

    win.document.write(pdfHTML);
    win.document.close();
    setTimeout(() => { try { win.focus(); } catch(e) {} }, 400);
}

/* Wrapper care decide PDF sau Word */
function exportReport(htmlContent, filename, format) {
    if (format === 'pdf') {
        exportAsPDF(htmlContent, filename);
    } else {
        exportHTMLToWord(htmlContent, filename);
    }
}

/* ════════════════════════════════════════
   RAPOARTE EMAIL & WORD
   ════════════════════════════════════════ */
async function sendAllReportsOnEmail() {
    const btn = document.getElementById('btn-send-reports'); if(btn) btn.innerText = "⏳ Se generează documentele Word și se trimit...";
    try {
        const [harvestAll, expensesAll, inv] = await Promise.all([ fetch('backend.php?fetch=harvest').then(r=>r.json()), fetch('backend.php?fetch=expenses').then(r=>r.json()), fetch('backend.php?fetch=inventory').then(r=>r.json()) ]);
        const harvest = filterByYear(harvestAll,'date'); const expenses = filterByYear(expensesAll,'date');
        let venit=0; harvest.forEach(h=>venit+=(parseFloat(h.kg)*parseFloat(h.pret||0))); let cost=0; expenses.forEach(e=>cost+=parseFloat(e.suma)); let profit=venit-cost;
        let profitClass=profit>=0?'badge-green':'badge-red', roiText=cost>0?((profit/cost)*100).toFixed(2)+'%':'N/A';
        let finHtml=`<h1>BILANȚ FINANCIAR STUPINĂ</h1><p>Data: <b>${new Date().toLocaleDateString('ro-RO')}</b></p><h3>Rezumat Global</h3><table><tr><td><b>Venituri:</b></td><td class="text-right badge-green">+ ${venit.toFixed(2)} RON</td></tr><tr><td><b>Cheltuieli:</b></td><td class="text-right badge-red">- ${cost.toFixed(2)} RON</td></tr><tr><td><b>PROFIT NET:</b></td><td class="text-right ${profitClass}">${profit.toFixed(2)} RON</td></tr><tr><td><b>R.O.I.:</b></td><td class="text-right"><b>${roiText}</b></td></tr></table><h3>Jurnal Cheltuieli</h3><table><tr><th>Data</th><th>Stup</th><th>Descriere</th><th class="text-right">Suma</th></tr>${expenses.map(e=>`<tr><td>${e.date}</td><td><b>${e.stup}</b></td><td>${e.desc}</td><td class="text-right badge-red">${e.suma} RON</td></tr>`).join('')||'<tr><td colspan="4">Nu există cheltuieli.</td></tr>'}</table>`;
        let totalStupi=hivesDataLocal.length, problemeCritice=0;
        let rowsHTML=hivesDataLocal.map(item=>{
            const name=item.meta.nickname||chipIDtoName[item.chipID]||item.chipID; let alerts=[];
            if(!item.isManual){if(item.battery<3.4)alerts.push("Baterie descărcată");if(item.delta24<-0.15)alerts.push("Pierdere greutate");}
            if(parseInt(item.meta.qScore||5)<3)alerts.push("Regină slabă");
            if(alerts.length>0){problemeCritice++;return `<tr><td><b>${name}</b></td><td class="text-center">${item.isManual?'N/A':item.battery.toFixed(1)+'V'}</td><td class="text-center">${item.delta24.toFixed(2)} kg</td><td class="badge-red">${alerts.join(', ')}</td></tr>`;}return '';
        }).join('');
        let sanatate=totalStupi>0?(((totalStupi-problemeCritice)/totalStupi)*100).toFixed(1):100;
        let sanHtml=`<h1>RAPORT SĂNĂTATE</h1><p>Data: <b>${new Date().toLocaleDateString('ro-RO')}</b></p><div class="summary-box"><b>Index Sănătate: ${sanatate}%</b><br>Stupi cu probleme: ${problemeCritice} din ${totalStupi}</div><h3>Alerte Curente</h3><table><tr><th>Stup</th><th class="text-center">Baterie</th><th class="text-center">Trend 24h</th><th>Probleme</th></tr>${rowsHTML||'<tr><td colspan="4">Toți stupii sunt în parametri normali.</td></tr>'}</table>`;
        let tipuri={},totalCules=0; harvest.forEach(h=>{let kg=parseFloat(h.kg);if(!tipuri[h.tip])tipuri[h.tip]=0;tipuri[h.tip]+=kg;totalCules+=kg;});
        let culHtml=`<h1>DINAMICA CULESULUI</h1><p>Data: <b>${new Date().toLocaleDateString('ro-RO')}</b></p><div class="summary-box"><b>Total Recoltă: ${totalCules} kg</b></div><h3>Centralizator Sortimente</h3><table><tr><th>Sortiment</th><th class="text-right">Cantitate Extrasă</th></tr>${Object.keys(tipuri).map(t=>`<tr><td><b>${t}</b></td><td class="text-right">${tipuri[t]} kg</td></tr>`).join('')||'<tr><td colspan="2">Nu există date.</td></tr>'}</table><h3>Jurnal Extracție</h3><table><tr><th>Data</th><th>Stup</th><th>Tip Miere</th><th class="text-right">Cantitate</th></tr>${harvest.map(h=>`<tr><td>${h.date}</td><td><b>${h.stup}</b></td><td>${h.tip}</td><td class="text-right"><b>${h.kg} kg</b></td></tr>`).join('')||'<tr><td colspan="4">Nu există extracții.</td></tr>'}</table>`;
        let countLitri=0,countBucati=0,countKg=0,groups={};
        inv.forEach(i=>{if(i.type.toLowerCase()==='litri')countLitri+=parseFloat(i.qty);if(i.type.toLowerCase()==='bucăți'||i.type.toLowerCase()==='bucati')countBucati+=parseFloat(i.qty);if(i.type.toLowerCase()==='kg')countKg+=parseFloat(i.qty);let cat=i.category||'Altele';if(!groups[cat])groups[cat]=[];groups[cat].push(i);});
        let invHtml=`<h1>SITUAȚIE INVENTAR</h1><p>Data: <b>${new Date().toLocaleDateString('ro-RO')}</b></p><div class="summary-box"><b>Lichide: ${countLitri} L | Solide: ${countKg} Kg | Echipamente: ${countBucati} Bucăți</b></div>`;
        for(let cat in groups){invHtml+=`<h3>${cat.replace('&',' & ')}</h3><table><tr><th>Articol</th><th class="text-center">Cantitate</th></tr>${groups[cat].map(i=>`<tr><td>${i.item}</td><td class="text-center"><b>${i.qty} ${i.type}</b></td></tr>`).join('')}</table>`;}
        const fd=new FormData(); fd.append('action','send_all_reports'); fd.append('doc_financiar',getWordHTML(finHtml)); fd.append('doc_sanatate',getWordHTML(sanHtml)); fd.append('doc_cules',getWordHTML(culHtml)); fd.append('doc_inventar',getWordHTML(invHtml));
        await smartFetch(fd); toast('Rapoartele au fost trimise pe email!', 'success'); if(btn) btn.innerText="📧 Trimite Toate Rapoartele pe Email";
    } catch(e){console.error(e);toast('Eroare la trimitere email!', 'error');if(btn) btn.innerText="📧 Trimite Toate Rapoartele pe Email";}
}

async function generateCustomReport(format='word') {
    // Suportă ambele seturi de ID-uri (jurnal.php: exp-start, admin.php: rep-start)
    const startStr = document.getElementById('rep-start')?.value || document.getElementById('exp-start')?.value;
    const endStr   = document.getElementById('rep-end')?.value   || document.getElementById('exp-end')?.value;
    const type     = document.getElementById('rep-type')?.value  || document.getElementById('exp-type')?.value || 'toate';
    const btn=document.querySelector('button[onclick*="generateCustomReport"]');
    if(!startStr||!endStr) return toast('Selectează perioada pentru raport!', 'warning');
    if(btn) btn.innerText="⏳ Se procesează...";
    const startDate=new Date(startStr); startDate.setHours(0,0,0,0); const endDate=new Date(endStr); endDate.setHours(23,59,59,999);
    try {
        const [tasks,harvest,jurnal]=await Promise.all([fetch('backend.php?fetch=tasks').then(r=>r.json()),fetch('backend.php?fetch=harvest').then(r=>r.json()),fetch('backend.php?fetch=jurnal').then(r=>r.json())]);
        const parseRoDate=(dStr)=>{if(!dStr)return new Date(0);let parts=dStr.split(' ')[0].split('.');if(parts.length===3)return new Date(parts[2],parts[1]-1,parts[0]);return new Date(0);};
        let items=[];
        if(type==='toate'||type==='note'){jurnal.forEach(j=>{if(!j.text.includes('✅ Sarcină rezolvată:')){let d=parseRoDate(j.date);if(d>=startDate&&d<=endDate)items.push({dateObj:d,date:j.date,stup:j.stup,tip:'Notiță Inspecție',desc:j.text});}});}
        if(type==='toate'||type==='sarcini'){jurnal.forEach(j=>{if(j.text.includes('✅ Sarcină rezolvată:')){let d=parseRoDate(j.date);if(d>=startDate&&d<=endDate)items.push({dateObj:d,date:j.date,stup:j.stup,tip:'Sarcină Efectuată',desc:j.text.replace('✅ Sarcină rezolvată: ','')});}}); tasks.forEach(t=>{let d=parseRoDate(t.date);if(d>=startDate&&d<=endDate)items.push({dateObj:d,date:t.date||'-',stup:'General / Planificat',tip:'Sarcină Programată',desc:t.text});});}
        if(type==='toate'||type==='recolte'){harvest.forEach(h=>{let d=parseRoDate(h.date);if(d>=startDate&&d<=endDate)items.push({dateObj:d,date:h.date,stup:h.stup,tip:'Recoltă',desc:`${h.kg} kg - ${h.tip} (${h.pret} RON/kg)`});});}
        items.sort((a,b)=>b.dateObj-a.dateObj);
        let html=`<h1>Jurnal Activități Apicole</h1><p>Perioada: <b>${startDate.toLocaleDateString('ro-RO')} - ${endDate.toLocaleDateString('ro-RO')}</b></p><table><tr><th>Data și Ora</th><th>Sursă / Stup</th><th>Tip Eveniment</th><th>Descriere</th></tr>${items.map(i=>`<tr><td>${i.date}</td><td><b>${i.stup}</b></td><td><i>${i.tip}</i></td><td>${i.desc}</td></tr>`).join('')}</table>`;
        exportReport(html, `Jurnal_${startStr}_${endStr}`, format); if(btn) btn.innerText="📄 Generează Raport (Word)";
    } catch(e){toast('A apărut o eroare!', 'error');if(btn) btn.innerText="📄 Generează Raport (Word)";}
}

async function generateWordReport(format='word') {
    const btn=document.querySelector('button[onclick="generateWordReport()"]'); if(btn) btn.innerText="⏳ Se generează...";
    try {
        const [tasks,harvest,jurnal]=await Promise.all([fetch('backend.php?fetch=tasks').then(r=>r.json()),fetch('backend.php?fetch=harvest').then(r=>r.json()),fetch('backend.php?fetch=jurnal').then(r=>r.json())]);
        const activeTasks=tasks.map(t=>({date:t.date||'-',text:t.text,status:'Programat'})), doneTasks=jurnal.filter(n=>n.text.includes('✅ Sarcină rezolvată:')).map(n=>({date:n.date.split(' ')[0],text:n.text.replace('✅ Sarcină rezolvată: ',''),status:'Efectuat'})), combinedTasks=[...doneTasks,...activeTasks];
        let totalMiere=0; harvest.forEach(h=>totalMiere+=parseFloat(h.kg));
        let html=`<h1>REGISTRUL STUPINEI (ANSVSA)</h1><p>Data: <b>${new Date().toLocaleDateString('ro-RO')}</b></p><div class="summary-box"><b>Sumar:</b> Tratamente: <b>${combinedTasks.length}</b> | Miere Recoltată: <b>${totalMiere} kg</b></div><h3>1. Registrul Tratamentelor</h3><table><tr><th>Data</th><th>Tratament</th><th>Status</th></tr>${combinedTasks.map(t=>`<tr><td>${t.date}</td><td>${t.text}</td><td><b>${t.status}</b></td></tr>`).join('')}</table><h3>2. Registrul Recoltărilor</h3><table><tr><th>Data</th><th>Stup</th><th>Tip Miere</th><th>Cantitate</th></tr>${harvest.map(h=>`<tr><td>${h.date}</td><td><b>${h.stup}</b></td><td>${h.tip}</td><td>${h.kg}</td></tr>`).join('')}</table>`;
        exportReport(html, `Registru_ANSVSA_${new Date().toLocaleDateString('ro-RO').replace(/\./g,'-')}`, format); if(btn) btn.innerText="📥 Generează & Descarcă Raport Word";
    } catch(e){toast('Eroare la generare raport!', 'error');if(btn) btn.innerText="📥 Generează & Descarcă Raport Word";}
}

async function generatePasaportStup() {
    try {
        const [jurnal,harvest]=await Promise.all([fetch('backend.php?fetch=jurnal').then(r=>r.json()),fetch('backend.php?fetch=harvest').then(r=>r.json())]);
        const hiveJurnal=jurnal.filter(n=>n.stup===currentChipName), hiveHarvest=harvest.filter(h=>h.stup===currentChipName);
        let totalMiereStup=0; hiveHarvest.forEach(h=>totalMiereStup+=parseFloat(h.kg));
        let html=`<h1>PAȘAPORT STUP: ${currentChipName.toUpperCase()}</h1><p>ID Senzor: <b>${currentChipID}</b></p><h3>Notițe Apicultor</h3><table><tr><th>Data</th><th>Observații</th></tr>${hiveJurnal.map(n=>`<tr><td>${n.date}</td><td>${n.text}</td></tr>`).join('')}</table><h3>Recoltă Individuală (Total: ${totalMiereStup} kg)</h3><table><tr><th>Data</th><th>Tip Miere</th><th>Cantitate Extrasă</th></tr>${hiveHarvest.map(h=>`<tr><td>${h.date}</td><td>${h.tip}</td><td>${h.kg} kg</td></tr>`).join('')}</table>`;
        exportHTMLToWord(html,`Pasaport_${currentChipName}`);
    } catch(e){toast('Eroare la generare!', 'error');}
}

async function generateRaportFinanciar(format='word') {
    try {
        const [harvestAll2,expensesAll2]=await Promise.all([fetch('backend.php?fetch=harvest').then(r=>r.json()),fetch('backend.php?fetch=expenses').then(r=>r.json())]);
        const harvest=filterByYear(harvestAll2,'date'); const expenses=filterByYear(expensesAll2,'date');
        let venit=0; harvest.forEach(h=>venit+=(parseFloat(h.kg)*parseFloat(h.pret||0))); let cost=0; expenses.forEach(e=>cost+=parseFloat(e.suma)); let profit=venit-cost;
        let html=`<h1>BILANȚ FINANCIAR STUPINĂ</h1><p>Data: <b>${new Date().toLocaleDateString('ro-RO')}</b></p><table><tr><td><b>Venituri:</b></td><td class="text-right">+ ${venit.toFixed(2)} RON</td></tr><tr><td><b>Cheltuieli:</b></td><td class="text-right">- ${cost.toFixed(2)} RON</td></tr><tr><td><b>PROFIT NET:</b></td><td class="text-right">${profit.toFixed(2)} RON</td></tr></table><h3>Jurnal Cheltuieli</h3><table><tr><th>Data</th><th>Stup</th><th>Descriere</th><th class="text-right">Suma</th></tr>${expenses.map(e=>`<tr><td>${e.date}</td><td><b>${e.stup}</b></td><td>${e.desc}</td><td class="text-right">${e.suma} RON</td></tr>`).join('')}</table>`;
        exportReport(html, `Raport_Financiar_${new Date().toLocaleDateString('ro-RO').replace(/\./g,'-')}`, format);
    } catch(e){toast('Eroare la generare!', 'error');}
}

/* ════════════════════════════════════════
   FETCH DATE (cu timestamp anti-cache)
   ════════════════════════════════════════ */
function fetchPermissions() {
    fetch('backend.php?get_permissions=1').then(r=>r.json()).then(perms=>{
        userPermissions = perms;
        window.isAdmin  = perms.isAdmin;
        if (perms.approved === false) {
            // Cont neaprobat — afișăm mesaj și nu încărcăm datele
            document.body.innerHTML = `
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;gap:20px;font-family:'Nunito',sans-serif;background:#fdf8ef;padding:20px;text-align:center;">
                    <div style="font-size:3rem;">🐝</div>
                    <h2 style="color:#5d4037;margin:0;">Cont în așteptare</h2>
                    <p style="color:#7f8c8d;max-width:400px;line-height:1.6;">Contul tău a fost creat cu succes, dar necesită aprobare din partea administratorului înainte de a putea accesa aplicația.</p>
                    <a href="index.php?logout=1" style="padding:12px 24px;background:#c8860a;color:#fff;border-radius:10px;text-decoration:none;font-weight:800;">Ieșire</a>
                </div>`;
            return;
        }
        fetchData();
    }).catch(()=>fetchData());
}

let _fetchController = null;
let _fetchInProgress = false;

function fetchData() {
    _fetchInProgress = true;

    fetch('backend.php?get_data=1&t='+Date.now())
        .then(r => r.json())
        .then(data => {
            _fetchInProgress = false;
            if (Array.isArray(data)) updateHives(data);
        })
        .catch(err => {
            _fetchInProgress = false;
            console.warn('[fetchData] ERROR:', err);
        });
}

function enableNotifications() {
    if (!("Notification" in window)) return;
    Notification.requestPermission().then(p => { if(p==="granted") new Notification("Sistem Activat 🐝"); });
}

/* ════════════════════════════════════════
   CLOSE DROPDOWN ON CLICK OUTSIDE
   ════════════════════════════════════════ */
document.addEventListener('click', function(e) {
    const dd = document.getElementById('user-dd');
    const btn = document.querySelector('.user-avatar-btn');
    if (dd?.classList.contains('show') && !dd.contains(e.target) && !btn?.contains(e.target)) {
        dd.classList.remove('show');
    }
});

/* ════════════════════════════════════════
   INIT
   ════════════════════════════════════════ */

/* ════════════════════════════════════════
   RAPOARTE ADMIN AVANSATE
   ════════════════════════════════════════ */
async function generateRaportSanatate(format='word') {
    const btn = document.querySelector('button[onclick="generateRaportSanatate()"]');
    if(btn) btn.innerText = '⏳ Se generează...';
    try {
        let problemeCritice = 0, totalStupi = hivesDataLocal.length;
        let rowsHTML = hivesDataLocal.map(item => {
            const name = item.meta.nickname || chipIDtoName[item.chipID] || item.chipID;
            let alerts = [];
            if(!item.isManual) {
                if(item.battery < 3.4) alerts.push('Baterie descărcată');
                if(item.delta24 < -0.15) alerts.push('Pierdere greutate');
            }
            if(parseInt(item.meta.qScore||5) < 3) alerts.push('Regină slabă');
            if(alerts.length > 0) { problemeCritice++; return `<tr><td><b>${name}</b></td><td class="text-center">${item.isManual?'N/A':item.battery.toFixed(1)+'V'}</td><td class="text-center">${(item.delta24||0).toFixed(2)} kg</td><td class="badge-red">${alerts.join(', ')}</td></tr>`; }
            return `<tr><td><b>${name}</b></td><td class="text-center badge-green">${item.isManual?'N/A':item.battery.toFixed(1)+'V'}</td><td class="text-center badge-green">${(item.delta24||0).toFixed(2)} kg</td><td class="badge-green">✅ OK</td></tr>`;
        }).join('');
        const sanatate = totalStupi > 0 ? (((totalStupi-problemeCritice)/totalStupi)*100).toFixed(1) : 100;
        const html = `<h1>RAPORT SĂNĂTATE STUPINĂ</h1><p>Data: <b>${new Date().toLocaleDateString('ro-RO')}</b></p><div class="summary-box"><b>Index Sănătate Global: ${sanatate}%</b> | Stupi cu probleme: <b>${problemeCritice}</b> din ${totalStupi}</div><h3>Status Individual</h3><table><tr><th>Stup</th><th class="text-center">Baterie</th><th class="text-center">Trend 24h</th><th>Status</th></tr>${rowsHTML}</table>`;
        exportReport(html, `Raport_Sanatate_${new Date().toLocaleDateString('ro-RO').replace(/\./g,'-')}`, format);
        if(btn) btn.innerText = '🏥 Raport Sănătate / Blacklist (Word)';
    } catch(e) { toast('Eroare la generare raport!', 'error'); if(btn) btn.innerText = '🏥 Raport Sănătate / Blacklist (Word)'; }
}

async function generateDinamicaCulesului(format='word') {
    const btn = document.querySelector('button[onclick="generateDinamicaCulesului()"]');
    if(btn) btn.innerText = '⏳ Se generează...';
    try {
        const harvest = await fetch('backend.php?fetch=harvest').then(r=>r.json());
        const tipuri = {}, stupuri = {};
        let totalCules = 0;
        harvest.forEach(h => {
            let kg = parseFloat(h.kg);
            tipuri[h.tip] = (tipuri[h.tip]||0) + kg;
            stupuri[h.stup] = (stupuri[h.stup]||0) + kg;
            totalCules += kg;
        });
        const html = `<h1>DINAMICA CULESULUI</h1><p>Data: <b>${new Date().toLocaleDateString('ro-RO')}</b></p><div class="summary-box"><b>Total Recoltă: ${totalCules} kg</b></div><h3>Centralizator pe Sortiment</h3><table><tr><th>Sortiment</th><th class="text-right">Cantitate (kg)</th><th class="text-right">Procent</th></tr>${Object.entries(tipuri).map(([t,kg])=>`<tr><td><b>${t}</b></td><td class="text-right">${kg.toFixed(2)} kg</td><td class="text-right">${totalCules>0?((kg/totalCules)*100).toFixed(1):0}%</td></tr>`).join('')}</table><h3>Centralizator pe Stup</h3><table><tr><th>Stup</th><th class="text-right">Total Recoltat (kg)</th></tr>${Object.entries(stupuri).sort((a,b)=>b[1]-a[1]).map(([s,kg])=>`<tr><td><b>${s}</b></td><td class="text-right">${kg.toFixed(2)} kg</td></tr>`).join('')}</table><h3>Jurnal Extracție Detaliat</h3><table><tr><th>Data</th><th>Stup</th><th>Tip Miere</th><th class="text-right">Cantitate</th><th class="text-right">Preț/kg</th></tr>${harvest.map(h=>`<tr><td>${h.date}</td><td><b>${h.stup}</b></td><td>${h.tip}</td><td class="text-right"><b>${h.kg} kg</b></td><td class="text-right">${h.pret||0} RON</td></tr>`).join('')}</table>`;
        exportReport(html, `Dinamica_Culesului_${new Date().toLocaleDateString('ro-RO').replace(/\./g,'-')}`, format);
        if(btn) btn.innerText = '📈 Dinamica Culesului (Word)';
    } catch(e) { toast('Eroare la generare raport!', 'error'); if(btn) btn.innerText = '📈 Dinamica Culesului (Word)'; }
}

async function generateRaportInventar(format='word') {
    const btn = document.querySelector('button[onclick="generateRaportInventar()"]');
    if(btn) btn.innerText = '⏳ Se generează...';
    try {
        const inv = await fetch('backend.php?fetch=inventory').then(r=>r.json());
        let countLitri=0, countBucati=0, countKg=0, groups={};
        inv.forEach(i => {
            const tip = i.type.toLowerCase();
            if(tip==='litri') countLitri+=parseFloat(i.qty);
            else if(tip==='bucăți'||tip==='bucati') countBucati+=parseFloat(i.qty);
            else if(tip==='kg') countKg+=parseFloat(i.qty);
            const cat = i.category||'Altele';
            if(!groups[cat]) groups[cat]=[];
            groups[cat].push(i);
        });
        let tablesHtml = '';
        for(let cat in groups) {
            tablesHtml += `<h3>${cat.replace('&',' & ')}</h3><table><tr><th>Articol</th><th class="text-center">Cantitate</th><th class="text-center">Unitate</th></tr>${groups[cat].map(i=>`<tr><td>${i.item}</td><td class="text-center"><b>${i.qty}</b></td><td class="text-center">${i.type}</td></tr>`).join('')}</table>`;
        }
        const html = `<h1>SITUAȚIE INVENTAR STUPINĂ</h1><p>Data: <b>${new Date().toLocaleDateString('ro-RO')}</b></p><div class="summary-box"><b>Rezumat Stocuri:</b> Lichide: <b>${countLitri} L</b> | Solide: <b>${countKg} Kg</b> | Echipamente: <b>${countBucati} Bucăți</b></div>${tablesHtml}`;
        exportReport(html, `Raport_Inventar_${new Date().toLocaleDateString('ro-RO').replace(/\./g,'-')}`, format);
        if(btn) btn.innerText = '📦 Raport Inventar (Word)';
    } catch(e) { toast('Eroare la generare raport!', 'error'); if(btn) btn.innerText = '📦 Raport Inventar (Word)'; }
}



/* ════════════════════════════════════════
   SCHIMBARE PAROLĂ (cont propriu)
   ════════════════════════════════════════ */
function openChangePassword() {
    document.getElementById('user-dd').classList.remove('show');
    let modal = document.getElementById('change-pass-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'change-pass-modal';
        modal.style.cssText = 'display:none;position:fixed;inset:0;z-index:9500;background:rgba(26,31,38,0.7);backdrop-filter:blur(6px);align-items:center;justify-content:center;';
        modal.innerHTML = `
            <div style="background:var(--cream,#fdfbf7);width:92%;max-width:420px;border-radius:20px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
                <h3 style="margin:0 0 20px;color:var(--premium-brown)">🔑 Schimbă Parola</h3>
                <div style="margin-bottom:12px">
                    <label style="display:block;font-size:0.8rem;font-weight:800;color:var(--premium-brown);margin-bottom:5px">Parola actuală</label>
                    <input type="password" id="cp-old" placeholder="••••••••"
                        style="width:100%;padding:11px 12px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.9rem;box-sizing:border-box;outline:none;">
                </div>
                <div style="margin-bottom:12px">
                    <label style="display:block;font-size:0.8rem;font-weight:800;color:var(--premium-brown);margin-bottom:5px">Parola nouă (min. 6 caractere)</label>
                    <input type="password" id="cp-new" placeholder="••••••••"
                        style="width:100%;padding:11px 12px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.9rem;box-sizing:border-box;outline:none;">
                </div>
                <div style="margin-bottom:20px">
                    <label style="display:block;font-size:0.8rem;font-weight:800;color:var(--premium-brown);margin-bottom:5px">Confirmă parola nouă</label>
                    <input type="password" id="cp-confirm" placeholder="••••••••"
                        style="width:100%;padding:11px 12px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.9rem;box-sizing:border-box;outline:none;">
                </div>
                <div style="display:flex;gap:10px;">
                    <button onclick="document.getElementById('change-pass-modal').style.display='none'"
                        style="flex:1;padding:11px;background:#f5f0e8;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;color:var(--premium-brown);">
                        Anulează
                    </button>
                    <button onclick="submitChangePassword()"
                        style="flex:1;padding:11px;background:linear-gradient(135deg,var(--wood-dark,#a6845c),var(--honey,#d4860b));color:#fff;border:none;border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;">
                        💾 Salvează
                    </button>
                </div>
            </div>`;
        document.body.appendChild(modal);
    }
    modal.style.display = 'flex';
}

function submitChangePassword() {
    const oldP    = document.getElementById('cp-old')?.value || '';
    const newP    = document.getElementById('cp-new')?.value || '';
    const confirm = document.getElementById('cp-confirm')?.value || '';

    if (!oldP || !newP) { toast('Completează toate câmpurile!', 'warning'); return; }
    if (newP.length < 6) { toast('Parola nouă trebuie să aibă cel puțin 6 caractere!', 'warning'); return; }
    if (newP !== confirm) { toast('Parolele noi nu coincid!', 'warning'); return; }

    const fd = new FormData();
    fd.append('action',   'change_password');
    fd.append('old_pass', oldP);
    fd.append('new_pass', newP);

    smartFetch(fd).then(r => {
        if (!r || r.offline) { toast('Offline — încearcă din nou când ești conectat.', 'warning'); return; }
        r.text().then(t => {
            if (t.trim() === 'ok') {
                toast('Parola a fost schimbată cu succes!', 'success');
                document.getElementById('change-pass-modal').style.display = 'none';
                document.getElementById('cp-old').value = '';
                document.getElementById('cp-new').value = '';
                document.getElementById('cp-confirm').value = '';
            } else if (t.trim() === 'error_old') {
                toast('Parola actuală este incorectă!', 'error');
            } else if (t.trim() === 'error_length') {
                toast('Parola nouă este prea scurtă!', 'warning');
            } else {
                toast('Eroare la schimbarea parolei.', 'error');
            }
        });
    });
}


/* ════════════════════════════════════════
   SCHIMBARE EMAIL CONT PROPRIU
   ════════════════════════════════════════ */
function openOwnEmailChange() {
    document.getElementById('user-dd').classList.remove('show');
    let modal = document.getElementById('own-email-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'own-email-modal';
        modal.style.cssText = 'display:none;position:fixed;inset:0;z-index:9500;background:rgba(26,31,38,0.7);backdrop-filter:blur(6px);align-items:center;justify-content:center;';
        modal.innerHTML = `<div style="background:var(--cream,#fdfbf7);width:92%;max-width:420px;border-radius:20px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
            <h3 style="margin:0 0 18px;color:var(--premium-brown)">📧 ${_lang==='en'?'Change Email':'Schimbă Email'}</h3>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.8rem;font-weight:800;color:var(--premium-brown);margin-bottom:6px;">${_lang==='en'?'New email address':'Adresa de email nouă'}</label>
                <input type="email" id="oem-email" placeholder="${_lang==='en'?'new@email.com':'email@adresa.ro'}" maxlength="128"
                    style="width:100%;padding:11px 12px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.9rem;box-sizing:border-box;outline:none;">
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.8rem;font-weight:800;color:var(--premium-brown);margin-bottom:6px;">${_lang==='en'?'Current password (for verification)':'Parola curentă (pentru verificare)'}</label>
                <input type="password" id="oem-pass" placeholder="••••••••"
                    style="width:100%;padding:11px 12px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.9rem;box-sizing:border-box;outline:none;">
            </div>
            <div style="display:flex;gap:10px;">
                <button onclick="document.getElementById('own-email-modal').style.display='none'"
                    style="flex:1;padding:11px;background:#f5f0e8;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;color:var(--premium-brown);">
                    ${_lang==='en'?'Cancel':'Anulează'}
                </button>
                <button id="oem-save"
                    style="flex:2;padding:11px;background:linear-gradient(135deg,#0e8c6e,#10ac84);color:#fff;border:none;border-radius:10px;font-family:inherit;font-weight:800;cursor:pointer;">
                    💾 ${_lang==='en'?'Save Email':'Salvează Email'}
                </button>
            </div>
        </div>`;
        modal.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };
        document.body.appendChild(modal);
    }
    // Re-render textele traduse
    modal.querySelector('h3').innerHTML = `📧 ${_lang==='en'?'Change Email':'Schimbă Email'}`;
    document.getElementById('oem-email').placeholder = _lang==='en'?'new@email.com':'email@adresa.ro';
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('oem-email').focus(), 150);

    document.getElementById('oem-save').onclick = () => {
        const newEmail = document.getElementById('oem-email').value.trim();
        const pass     = document.getElementById('oem-pass').value;
        if (!newEmail || !/^[^@]+@[^@]+\.[^@]+$/.test(newEmail)) {
            toast(_lang==='en'?'Invalid email address!':'Adresă de email invalidă!', 'warning'); return;
        }
        if (!pass) {
            toast(_lang==='en'?'Enter your current password!':'Introdu parola curentă!', 'warning'); return;
        }
        const fd = new FormData();
        fd.append('action',    'change_own_email');
        fd.append('new_email', newEmail);
        fd.append('password',  pass);
        smartFetch(fd).then(r => {
            if (!r || r.offline) return;
            r.text().then(resp => {
                if (resp.trim() === 'ok') {
                    modal.style.display = 'none';
                    document.getElementById('oem-email').value = '';
                    document.getElementById('oem-pass').value  = '';
                    toast(_lang==='en'?'Email updated successfully!':'Email actualizat cu succes!', 'success');
                } else if (resp.trim() === 'error_pass') {
                    toast(_lang==='en'?'Incorrect password!':'Parola incorectă!', 'error');
                } else if (resp.trim() === 'error_email') {
                    toast(_lang==='en'?'Invalid email format!':'Format email invalid!', 'warning');
                } else {
                    toast(_lang==='en'?'Error updating email.':'Eroare la actualizare email.', 'error');
                }
            });
        });
    };
}


/* ════════════════════════════════════════
   SETTINGS MODAL — grupează toate setările
   contului și preferințele aplicației
   ════════════════════════════════════════ */
function openSettings() {
    document.getElementById('user-dd')?.classList.remove('show');
    let modal = document.getElementById('settings-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'settings-modal';
        modal.style.cssText = 'display:none;position:fixed;inset:0;z-index:9500;background:rgba(26,31,38,0.7);backdrop-filter:blur(6px);align-items:flex-end;justify-content:center;';
        document.body.appendChild(modal);
    }

    const ro = _lang !== 'en';

    modal.innerHTML = `
        <div id="settings-sheet" style="background:var(--cream,#fdfbf7);width:100%;max-width:520px;max-height:90vh;border-radius:22px 22px 0 0;overflow-y:auto;box-shadow:0 -8px 40px rgba(0,0,0,0.2);animation:sheetUp 0.3s cubic-bezier(0.16,1,0.3,1);">

            <!-- Header -->
            <div style="padding:20px 22px 14px;border-bottom:1px solid var(--wood-light);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--cream,#fdfbf7);z-index:2;border-radius:22px 22px 0 0;">
                <h3 style="margin:0;color:var(--premium-brown);font-size:1.1rem;">⚙️ ${ro?'Setări Cont':'Account Settings'}</h3>
                <button onclick="document.getElementById('settings-modal').style.display='none'" style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:var(--text-muted);padding:4px 8px;border-radius:6px;">✕</button>
            </div>

            <!-- Cont -->
            <div style="padding:16px 22px 0;">
                <div style="font-size:0.72rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:10px;">${ro?'Cont':'Account'}</div>

                <div style="background:var(--white,#fff);border:1.5px solid var(--wood-light);border-radius:14px;overflow:hidden;margin-bottom:16px;">
                    <div class="settings-row" onclick="document.getElementById('settings-modal').style.display='none';openChangePassword()">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:rgba(52,152,219,0.1);display:flex;align-items:center;justify-content:center;font-size:1.1rem;">🔑</div>
                            <div>
                                <div style="font-weight:800;font-size:0.88rem;color:var(--text-dark)">${ro?'Schimbă Parola':'Change Password'}</div>
                                <div style="font-size:0.75rem;color:var(--text-muted)">${ro?'Actualizează parola contului':'Update your account password'}</div>
                            </div>
                        </div>
                        <span style="color:var(--text-muted);font-size:1rem;">›</span>
                    </div>
                    <div style="height:1px;background:var(--wood-light);margin:0 16px;"></div>
                    <div class="settings-row" onclick="document.getElementById('settings-modal').style.display='none';openOwnEmailChange()">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:rgba(16,172,132,0.1);display:flex;align-items:center;justify-content:center;font-size:1.1rem;">📧</div>
                            <div>
                                <div style="font-weight:800;font-size:0.88rem;color:var(--text-dark)">${ro?'Schimbă Email':'Change Email'}</div>
                                <div style="font-size:0.75rem;color:var(--text-muted)">${ro?'Actualizează adresa de email':'Update your email address'}</div>
                            </div>
                        </div>
                        <span style="color:var(--text-muted);font-size:1rem;">›</span>
                    </div>
                </div>
            </div>

            <!-- Preferințe -->
            <div style="padding:0 22px;">
                <div style="font-size:0.72rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:10px;">${ro?'Preferințe':'Preferences'}</div>

                <div style="background:var(--white,#fff);border:1.5px solid var(--wood-light);border-radius:14px;overflow:hidden;margin-bottom:16px;">
                    <div class="settings-row" onclick="toggleNight();renderSettingsNightState()">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:rgba(52,73,94,0.1);display:flex;align-items:center;justify-content:center;font-size:1.1rem;">🌙</div>
                            <div>
                                <div style="font-weight:800;font-size:0.88rem;color:var(--text-dark)">${ro?'Mod Noapte':'Night Mode'}</div>
                                <div style="font-size:0.75rem;color:var(--text-muted)">${ro?'Temă întunecată':'Dark theme'}</div>
                            </div>
                        </div>
                        <label style="display:flex;align-items:center;cursor:pointer;" onclick="event.stopPropagation()">
                            <input type="checkbox" id="settings-night-cb" ${document.body.classList.contains('night-mode')?'checked':''} onchange="toggleNight();this.checked=document.body.classList.contains('night-mode')" style="display:none">
                            <div id="settings-night-track" style="width:40px;height:22px;border-radius:11px;background:${document.body.classList.contains('night-mode')?'var(--honey,#d4860b)':'#ddd'};position:relative;transition:background 0.2s;">
                                <div style="position:absolute;width:16px;height:16px;background:#fff;border-radius:50%;top:3px;left:${document.body.classList.contains('night-mode')?'21':'3'}px;transition:left 0.2s;box-shadow:0 1px 4px rgba(0,0,0,0.2);"></div>
                            </div>
                        </label>
                    </div>
                    <div style="height:1px;background:var(--wood-light);margin:0 16px;"></div>
                    <div class="settings-row">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:rgba(212,134,11,0.1);display:flex;align-items:center;justify-content:center;font-size:1.1rem;">🌐</div>
                            <div>
                                <div style="font-weight:800;font-size:0.88rem;color:var(--text-dark)">${ro?'Limbă':'Language'}</div>
                                <div style="font-size:0.75rem;color:var(--text-muted)">${ro?'Română / Engleză':'Romanian / English'}</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:6px;">
                            <button onclick="setLang('ro')" style="padding:5px 12px;border-radius:8px;border:1.5px solid ${_lang==='ro'?'var(--honey)':'var(--wood-light)'};background:${_lang==='ro'?'var(--honey)':'transparent'};color:${_lang==='ro'?'#fff':'var(--text-dark)'};font-family:inherit;font-weight:800;font-size:0.8rem;cursor:pointer;">🇷🇴 RO</button>
                            <button onclick="setLang('en')" style="padding:5px 12px;border-radius:8px;border:1.5px solid ${_lang==='en'?'var(--honey)':'var(--wood-light)'};background:${_lang==='en'?'var(--honey)':'transparent'};color:${_lang==='en'?'#fff':'var(--text-dark)'};font-family:inherit;font-weight:800;font-size:0.8rem;cursor:pointer;">🇬🇧 EN</button>
                        </div>
                    </div>
                    <div style="height:1px;background:var(--wood-light);margin:0 16px;"></div>
                    <div class="settings-row" onclick="document.getElementById('settings-modal').style.display='none';toggleSuitMode()">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:rgba(230,126,34,0.1);display:flex;align-items:center;justify-content:center;font-size:1.1rem;">🐝</div>
                            <div>
                                <div style="font-weight:800;font-size:0.88rem;color:var(--text-dark)">${ro?'Mod Costum':'Field Mode'}</div>
                                <div style="font-size:0.75rem;color:var(--text-muted)">${ro?'Inspecție rapidă pe teren':'Quick field inspection'}</div>
                            </div>
                        </div>
                        <span style="color:var(--text-muted);font-size:1rem;">›</span>
                    </div>
                </div>
            </div>

            <!-- Locație Stupină -->
            <div style="padding:0 22px;">
                <div style="font-size:0.72rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:10px;">${ro?'Locație Stupină (Meteo)':'Apiary Location (Weather)'}</div>
                <div style="background:var(--white,#fff);border:1.5px solid var(--wood-light);border-radius:14px;overflow:hidden;margin-bottom:16px;">
                    <div style="padding:14px 16px;">
                        <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:10px;">${ro?'Introdu coordonatele GPS ale stupinei tale. Vremea din aplicație va fi mereu de acolo, indiferent de unde te conectezi.':'Enter your apiary GPS coordinates. The app weather will always use this location, regardless of where you log in from.'}</div>
                        <div style="display:flex;gap:8px;margin-bottom:10px;">
                            <div style="flex:1;">
                                <label style="font-size:0.72rem;font-weight:800;color:var(--text-muted);display:block;margin-bottom:4px;">Latitudine</label>
                                <input id="apiary-lat-input" type="number" step="0.0001" placeholder="ex: 44.1885"
                                    value="${window.apiaryLat !== null ? window.apiaryLat : ''}"
                                    style="width:100%;padding:8px 10px;border:1.5px solid var(--wood-light);border-radius:8px;font-family:inherit;font-size:0.88rem;background:var(--cream);color:var(--text-dark);">
                            </div>
                            <div style="flex:1;">
                                <label style="font-size:0.72rem;font-weight:800;color:var(--text-muted);display:block;margin-bottom:4px;">Longitudine</label>
                                <input id="apiary-lon-input" type="number" step="0.0001" placeholder="ex: 25.0979"
                                    value="${window.apiaryLon !== null ? window.apiaryLon : ''}"
                                    style="width:100%;padding:8px 10px;border:1.5px solid var(--wood-light);border-radius:8px;font-family:inherit;font-size:0.88rem;background:var(--cream);color:var(--text-dark);">
                            </div>
                        </div>
                        <button onclick="saveApiaryLocation()" style="width:100%;padding:10px;background:var(--honey);color:#fff;border:none;border-radius:9px;font-family:inherit;font-weight:800;font-size:0.88rem;cursor:pointer;">
                            💾 ${ro?'Salvează Locația Stupinei':'Save Apiary Location'}
                        </button>
                        <div id="apiary-location-status" style="font-size:0.76rem;margin-top:8px;font-weight:700;min-height:18px;color:var(--accent-green);">
                            ${window.apiaryLat !== null ? (ro?'✅ Locație curentă: ':'✅ Current location: ') + parseFloat(window.apiaryLat).toFixed(4) + ', ' + parseFloat(window.apiaryLon).toFixed(4) : (ro?'⚠️ Nicio locație configurată — se folosesc coordonate implicite.':'⚠️ No location set — using default coordinates.')}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notificări -->
            <div style="padding:0 22px;">
                <div style="font-size:0.72rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:10px;">${ro?'Notificări':'Notifications'}</div>
                <div style="background:var(--white,#fff);border:1.5px solid var(--wood-light);border-radius:14px;overflow:hidden;margin-bottom:32px;">
                    <div class="settings-row" onclick="enableNotifications()">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:rgba(142,68,173,0.1);display:flex;align-items:center;justify-content:center;font-size:1.1rem;">🔔</div>
                            <div>
                                <div style="font-weight:800;font-size:0.88rem;color:var(--text-dark)">${ro?'Notificări Push':'Push Notifications'}</div>
                                <div style="font-size:0.75rem;color:var(--text-muted)">${ro?'Activează alerte în browser':'Enable browser alerts'}</div>
                            </div>
                        </div>
                        <span style="color:var(--text-muted);font-size:1rem;">›</span>
                    </div>
                </div>
            </div>

        </div>`;

    modal.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };
    modal.style.display = 'flex';
}

function renderSettingsNightState() {
    const track = document.getElementById('settings-night-track');
    if (!track) return;
    const isNight = document.body.classList.contains('night-mode');
    track.style.background = isNight ? 'var(--honey,#d4860b)' : '#ddd';
    track.children[0].style.left = isNight ? '21px' : '3px';
}

/* ════════════════════════════════════════
   LOCAȚIE STUPINĂ — Salvare pe server per user
   ════════════════════════════════════════ */
async function saveApiaryLocation() {
    const latEl  = document.getElementById('apiary-lat-input');
    const lonEl  = document.getElementById('apiary-lon-input');
    const status = document.getElementById('apiary-location-status');
    const ro = _lang !== 'en';

    if (!latEl || !lonEl) return;
    const lat = parseFloat(latEl.value.replace(',', '.'));
    const lon = parseFloat(lonEl.value.replace(',', '.'));

    if (isNaN(lat) || isNaN(lon) || lat < -90 || lat > 90 || lon < -180 || lon > 180) {
        if (status) { status.style.color = 'var(--accent-red)'; status.textContent = ro ? '❌ Coordonate invalide!' : '❌ Invalid coordinates!'; }
        return;
    }

    if (status) { status.style.color = 'var(--text-muted)'; status.textContent = ro ? '⏳ Se salvează...' : '⏳ Saving...'; }

    const fd = new FormData();
    fd.append('action', 'save_apiary_location');
    fd.append('lat', lat);
    fd.append('lon', lon);

    try {
        const r = await smartFetch(fd);
        const txt = await r.text();
        if (txt.trim() === 'ok') {
            window.apiaryLat = lat;
            window.apiaryLon = lon;
            if (status) {
                status.style.color = 'var(--accent-green)';
                status.textContent = (ro ? '✅ Salvat: ' : '✅ Saved: ') + lat.toFixed(4) + ', ' + lon.toFixed(4);
            }
            toast(ro ? '📍 Locația stupinei salvată! Se actualizează vremea...' : '📍 Apiary location saved! Updating weather...', 'success');
            fetchWeather();
        } else {
            if (status) { status.style.color = 'var(--accent-red)'; status.textContent = ro ? '❌ Eroare la salvare.' : '❌ Save error.'; }
        }
    } catch(e) {
        if (status) { status.style.color = 'var(--accent-red)'; status.textContent = ro ? '❌ Eroare de rețea.' : '❌ Network error.'; }
    }
}

/* ════════════════════════════════════════
   LOGOUT — curăță cache SW înainte de redirect
   ════════════════════════════════════════ */
async function doLogout() {
    // Dezactivează SW-ul temporar pentru request-ul de logout
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({ type: 'SKIP_WAITING' });
    }
    // Curăță toate cache-urile SW
    if ('caches' in window) {
        try {
            const keys = await caches.keys();
            await Promise.all(keys.map(k => caches.delete(k)));
        } catch(e) {}
    }
    // Redirect direct la logout, bypass SW
    window.location.href = 'index.php?logout=1&nocache=' + Date.now();
}

window.onload = () => {
    // Restore night mode
    if (localStorage.getItem('nightMode') === 'true') document.body.classList.add('night-mode');

    // Debounce pe search dashboard
    const searchEl = document.getElementById('dashboard-search');
    if (searchEl) {
        const debouncedRender = debounce(renderDashboard, 250);
        searchEl.addEventListener('input', debouncedRender);
        // Elimină oninput inline dacă există (setăm prin JS)
    }

    // Adaugă data-page pe butoanele nav pentru cross-nav sync
    const pageMap = {
        'view-dashboard': 0, 'view-map': 1, 'view-compare': 2,
        'view-table': 3,     'view-jurnal': 4, 'view-harvest': 5,
        'view-inventory': 6, 'view-admin': 7,  'view-help': 8
    };
    document.querySelectorAll('#main-nav .nav-btn').forEach((btn, i) => {
        const onclick = btn.getAttribute('onclick') || '';
        const match   = onclick.match(/showPage\('([^']+)'/);
        if (match) btn.setAttribute('data-page', match[1]);
    });

    // Inițializează textul butonului noapte după restaurarea temei
    updateNightBtn();

    updateOfflineBanner();
    if (navigator.onLine) syncOfflineQueue();

    fetchWeather();
    initAmbient();
    // Leaflet se inițializează lazy la primul showPage('view-map')
    // fetchPermissions() apelează fetchData() intern după ce obține permisiunile
    fetchPermissions();
    renderHarvest();
    renderJurnal();
    updateLiveClock();

    setInterval(updateLiveClock, 1000);
    setInterval(fetchData, 30000);

    // Aplică limba salvată
    applyLang(); // Redus de la 5s la 30s pentru a nu supraîncărca serverul

    // Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').catch(() => {});
    }
};