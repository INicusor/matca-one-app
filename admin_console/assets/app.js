/* ═══════════════════════════════════════════════════════════
   MATCA Admin Console — Frontend Logic
   ═══════════════════════════════════════════════════════════ */

'use strict';

// ── State ─────────────────────────────────────────────────────
let currentView    = 'dashboard';
let hivesData      = [];
let usersData      = {};
let allHiveNames   = {};
let controllersData= [];
let activityChart  = null;
let statusChart    = null;
let currentTable   = '';
let dbOffset       = 0;
let dbTotal        = 0;
let jurnalPage     = 1;
let auditPage      = 1;
let sortState      = {};
let confirmCb      = null;
let queensPage     = 1;
let alertsPage     = 1;
let weightChart    = null;
let growthChart    = null;

// ── API Helper ─────────────────────────────────────────────────
async function api(action, method = 'GET', params = {}, body = null, responseType = 'json') {
    const url = new URL('api.php', window.location.href);
    url.searchParams.set('action', action);

    const opts = {
        method,
        headers: { 'X-CSRF-Token': window.CSRF },
    };

    if (method === 'GET') {
        Object.entries(params).forEach(([k,v]) => url.searchParams.set(k, v));
    } else {
        const fd = new FormData();
        fd.append('csrf', window.CSRF);
        fd.append('action', action);
        if (body) Object.entries(body).forEach(([k,v]) => fd.append(k, v));
        opts.body = fd;
    }

    try {
        const res = await fetch(url, opts);
        if (responseType === 'csv') {
            const blob = await res.blob();
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = action + '_' + Date.now() + '.csv';
            a.click();
            return;
        }
        const data = await res.json();
        if (data.error) throw new Error(data.error);
        return data;
    } catch (e) {
        toast(e.message || 'Eroare retea', 'error');
        throw e;
    }
}

// ── Toast ──────────────────────────────────────────────────────
function toast(msg, type = 'info', duration = 3500) {
    const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = `<span>${icons[type]||'ℹ'}</span><span>${msg}</span>`;
    document.getElementById('toast-container').appendChild(t);
    setTimeout(() => t.remove(), duration);
}

// ── Confirm Modal ──────────────────────────────────────────────
function confirm(title, text, cb, icon = '⚠') {
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-text').textContent  = text;
    document.getElementById('confirm-icon').textContent  = icon;
    confirmCb = cb;
    openModal('confirm-modal');
    document.getElementById('confirm-ok-btn').onclick = () => {
        closeModal('confirm-modal');
        if (confirmCb) confirmCb();
    };
}

// ── Modal helpers ──────────────────────────────────────────────
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

// ── Navigation ─────────────────────────────────────────────────
const VIEW_TITLES = {
    dashboard:   ['Dashboard',              'Vedere de ansamblu — status stupi, activitate și metrici stupinei'],
    health:      ['System Health',          'Starea tehnică a serverului web soul2soul.ro și a bazei de date MySQL'],
    hives:       ['Stupi',                  'Toți stupii din aplicație — cu senzori IoT (date live) și manuali'],
    controllers: ['Controllers',            'Gateway-urile ESP32/ESP8266 care colectează date de la senzori și le trimit la server'],
    queens:      ['Matci',                  'Istoricul complet al matcilor — schimbări, rase, evenimente per stup'],
    alerts:      ['Alerte',                 'Alertele rezolvate — scăderi de greutate (roi), baterii descărcate, stupi offline'],
    jurnal:      ['Jurnal',                 'Toate notițele și inspecțiile salvate de utilizatori în aplicație'],
    harvest:     ['Recoltă & Cheltuieli',   'Evidența recoltelor de miere și cheltuielilor pe stupi — bilanț financiar'],
    tasks:       ['Sarcini & Tratamente',   'Sarcini planificate și scheme de tratament anti-varroa pe stupi'],
    inventory:   ['Inventar',               'Stocul de materiale apicole — tratamente, unelte, cutii și rame'],
    users:       ['Utilizatori App',        'Conturile apicultorilor din aplicația MATCA — acces, stupi alocați, activitate'],
    reports:     ['Rapoarte Admin',         'Statistici tehnice și de performanță — activitate useri, performanță stupi'],
    backup:      ['Backup & Export',        'Backup complet JSON, dump SQL și export CSV — protejează datele stupinei'],
    database:    ['Baza de Date',           'Browser tabel MySQL, SQL Console direct și statistici per tabel'],
    audit:       ['Audit Log',              'Istoricul tuturor acțiunilor din consola de admin — cine ce a făcut și când'],
    admins:      ['Admin Users',            'Conturile administratorilor consolei MATCA — separat de utilizatorii aplicației'],
    settings:    ['Setări',                 'Preferințe cont admin — schimbare parolă și informații sesiune curentă'],
}

function showView(view, el) {
    document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    const viewEl = document.getElementById('view-' + view);
    if (viewEl) viewEl.classList.add('active');
    if (el) el.classList.add('active');
    currentView = view;
    const [title, sub] = VIEW_TITLES[view] || [view, ''];
    document.getElementById('page-title').innerHTML = `${title} <span>${sub}</span>`;
    loadView(view);
}

function loadView(view) {
    const loaders = {
        dashboard:   loadDashboard,
        hives:       loadHives,
        controllers: loadControllers,
        health:      loadHealth,
        queens:      loadQueens,
        alerts:      loadAlertsFull,
        jurnal:      loadJurnal,
        harvest:     loadHarvest,
        tasks:       loadTasks,
        inventory:   loadInventory,
        users:       loadUsers,
        reports:     loadReportActivity,
        backup:      loadBackupInfo,
        database:    loadDatabase,
        audit:       loadAudit,
        admins:      loadAdminUsers,
    };
    if (loaders[view]) loaders[view]();
}

function refreshCurrentView() { loadView(currentView); }

// ── Clock ──────────────────────────────────────────────────────
function updateClock() {
    document.getElementById('clock').textContent = new Date().toLocaleTimeString('ro-RO');
}
setInterval(updateClock, 1000);
updateClock();

// ── Tab helper ─────────────────────────────────────────────────
function switchTab(group, tab, btn) {
    document.querySelectorAll(`#view-${group} .tab-btn`).forEach(b => b.classList.remove('active'));
    document.querySelectorAll(`#view-${group} .tab-panel`).forEach(p => p.classList.remove('active'));
    if (btn) btn.classList.add('active');
    const panel = document.getElementById(`${group}-tab-${tab}`);
    if (panel) panel.classList.add('active');
}

// ── Time formatter ─────────────────────────────────────────────
function fmtTime(ts) {
    if (!ts) return '-';
    const d = new Date(ts * 1000);
    const diff = Math.floor((Date.now()/1000) - ts);
    if (diff < 3600)  return Math.floor(diff/60)  + ' min ago';
    if (diff < 86400) return Math.floor(diff/3600) + ' ore ago';
    return d.toLocaleDateString('ro-RO') + ' ' + d.toLocaleTimeString('ro-RO', {hour:'2-digit',minute:'2-digit'});
}

// ═══════════════════════════════════════════════════════════════
// DASHBOARD
// ═══════════════════════════════════════════════════════════════
async function loadDashboard() {
    try {
        const r = await api('get_dashboard_stats');
        const d = r.data;

        document.getElementById('s-online').textContent  = d.stupi_online;
        document.getElementById('s-offline').textContent = d.stupi_offline + (d.stupi_warning ? ` (+${d.stupi_warning})` : '');
        document.getElementById('s-alerts').textContent  = d.alerte_active;
        document.getElementById('s-users').textContent   = d.users_total;
        document.getElementById('s-kg').textContent      = d.recolta_kg + ' kg';
        document.getElementById('s-ron').textContent     = d.recolta_ron + ' RON';
        document.getElementById('s-readings').textContent= fmtBig(d.total_readings);
        document.getElementById('s-tasks').textContent   = d.tasks_pending;

        const badge = document.getElementById('alerts-badge');
        if (d.alerte_active > 0) {
            badge.textContent = d.alerte_active;
            badge.style.display = '';
        } else {
            badge.style.display = 'none';
        }

        // Activity chart
        const labels = Object.keys(d.activity || d.activity_chart || {});
        const vals   = Object.values(d.activity || d.activity_chart || {});
        if (activityChart) activityChart.destroy();
        activityChart = new Chart(
            document.getElementById('activity-chart').getContext('2d'),
            { type: 'bar',
              data: { labels, datasets: [{ label: 'Note', data: vals,
                  backgroundColor: 'rgba(212,134,11,0.5)', borderColor: '#d4860b', borderWidth: 1 }] },
              options: { responsive:true, maintainAspectRatio:false,
                  plugins:{ legend:{display:false} },
                  scales:{ x:{ticks:{color:'#8892a4'}}, y:{ticks:{color:'#8892a4'},grid:{color:'#1e2130'}} } }
            }
        );

        // Status pie chart
        if (statusChart) statusChart.destroy();
        statusChart = new Chart(
            document.getElementById('status-chart').getContext('2d'),
            { type: 'doughnut',
              data: { labels:['Online','Warning','Offline'],
                  datasets:[{ data:[d.stupi_online, d.stupi_warning, d.stupi_offline],
                      backgroundColor:['#10ac84','#f39c12','#ee5253'], borderWidth:0 }] },
              options: { responsive:true, maintainAspectRatio:false,
                  plugins:{ legend:{position:'right', labels:{color:'#8892a4'}} } }
            }
        );
    } catch(e) {}

    // Grafic greutate stupi
    loadWeightChart();
}

function fmtBig(n) {
    if (n >= 1000000) return (n/1000000).toFixed(1) + 'M';
    if (n >= 1000)    return (n/1000).toFixed(1) + 'K';
    return n;
}

// ═══════════════════════════════════════════════════════════════
// STUPI
// ═══════════════════════════════════════════════════════════════
async function loadHives() {
    document.getElementById('hives-tbody').innerHTML =
        '<tr class="loading-row"><td colspan="11"><div class="spinner"></div></td></tr>';
    try {
        const r = await api('get_hives');
        hivesData = r.data;
        renderHivesTable(hivesData);
    } catch(e) {}
}

function renderHivesTable(data) {
    const tbody = document.getElementById('hives-tbody');
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;color:var(--text-muted);padding:30px">Niciun stup gasit</td></tr>';
        return;
    }
    tbody.innerHTML = data.map(h => {
        const statusBadge = {
            online:  '<span class="badge badge-online"><span class="status-dot online"></span>Online</span>',
            warning: '<span class="badge badge-warning"><span class="status-dot warning"></span>Warning</span>',
            offline: '<span class="badge badge-offline"><span class="status-dot offline"></span>Offline</span>',
            manual:  '<span class="badge badge-manual"><span class="status-dot manual"></span>Manual</span>',
        }[h.status] || h.status;

        const batColor = h.battery < 3.4 ? 'var(--red)' : h.battery < 3.6 ? 'var(--orange)' : 'var(--green)';
        const d24Color = h.delta24 > 0 ? 'var(--green)' : h.delta24 < -0.5 ? 'var(--red)' : 'var(--text-muted)';

        return `<tr>
            <td>${statusBadge}</td>
            <td><strong>${esc(h.nickname)}</strong></td>
            <td class="td-mono">${h.chipID}</td>
            <td>${h.weight ? h.weight.toFixed(3) + ' kg' : '-'}</td>
            <td>${h.temperature ? h.temperature.toFixed(1) + '°C' : '-'}</td>
            <td style="color:${batColor}">${h.battery ? h.battery.toFixed(2) + 'V' : '-'}</td>
            <td style="color:${d24Color}">${h.delta24 !== undefined ? (h.delta24 > 0 ? '+' : '') + h.delta24.toFixed(3) : '-'}</td>
            <td class="td-muted">${esc(h.controller || '-')}</td>
            <td class="td-mono" style="font-size:11px">${esc(h.firmware || '-')}</td>
            <td class="td-muted" style="font-size:11px">${fmtTime(h.last_updated)}</td>
            <td>
                <button class="btn btn-ghost btn-sm btn-icon" onclick="editHive(${JSON.stringify(h).replace(/"/g,'&quot;')})" title="Editeaza">&#9998;</button>
                ${h.isManual ? `<button class="btn btn-danger btn-sm btn-icon" onclick="deleteManualHive('${h.chipID}','${esc(h.nickname)}')" title="Sterge">&#128465;</button>` : ''}
            </td>
        </tr>`;
    }).join('');
}

function filterHives() {
    const q      = document.getElementById('hive-search').value.toLowerCase();
    const status = document.getElementById('hive-filter-status').value;
    const filtered = hivesData.filter(h => {
        const matchQ = !q || h.nickname.toLowerCase().includes(q) || String(h.chipID).includes(q);
        const matchS = !status || h.status === status;
        return matchQ && matchS;
    });
    renderHivesTable(filtered);
}

function editHive(h) {
    document.getElementById('hm-chipid').value      = h.chipID;
    document.getElementById('hm-nickname').value    = h.nickname;
    document.getElementById('hm-qcolor').value      = h.qColor !== 'transparent' ? h.qColor : '#d4860b';
    document.getElementById('hm-qyear').value       = h.qYear  || '';
    document.getElementById('hm-qbreed').value      = h.qBreed || '';
    document.getElementById('hm-supers').value      = h.supers || 0;
    document.getElementById('hm-lat').value         = h.lat    || '';
    document.getElementById('hm-lng').value         = h.lng    || '';
    document.getElementById('hm-maintenance').checked = !!h.maintenance;
    document.getElementById('hive-modal-title').textContent = 'Editeaza: ' + h.nickname;

    // Populate controller dropdown
    const sel = document.getElementById('hm-controller');
    sel.innerHTML = '<option value="">-- Nu muta --</option>';
    controllersData.forEach(c => {
        sel.innerHTML += `<option value="${c.id}" ${h.controllerID === c.id ? 'selected' : ''}>${esc(c.name)} (${c.id})</option>`;
    });

    openModal('hive-modal');
}

async function saveHiveMeta() {
    const chipID     = document.getElementById('hm-chipid').value;
    const newCtrl    = document.getElementById('hm-controller').value;
    const body = {
        chipID,
        nickname:    document.getElementById('hm-nickname').value,
        qColor:      document.getElementById('hm-qcolor').value,
        qYear:       document.getElementById('hm-qyear').value,
        qBreed:      document.getElementById('hm-qbreed').value,
        supers:      document.getElementById('hm-supers').value,
        lat:         document.getElementById('hm-lat').value,
        lng:         document.getElementById('hm-lng').value,
        maintenance: document.getElementById('hm-maintenance').checked ? 'true' : 'false',
    };
    try {
        await api('update_hive_meta', 'POST', {}, body);
        if (newCtrl) await api('move_hive_to_controller', 'POST', {}, { chipID, new_controller: newCtrl });
        closeModal('hive-modal');
        toast('Stup actualizat!', 'success');
        loadHives();
    } catch(e) {}
}

function deleteManualHive(chipID, name) {
    confirm('Sterge stup manual', `Stergi permanent "${name}" (${chipID})?`, async () => {
        await api('delete_hive_manual', 'POST', {}, { chipID });
        toast('Stup sters!', 'success');
        loadHives();
    }, '🗑');
}

function exportHivesCSV() {
    if (!hivesData.length) return;
    const cols = ['chipID','nickname','status','weight','temperature','battery','delta24','controller','firmware','last_updated'];
    const lines = [cols.join(',')];
    hivesData.forEach(h => {
        lines.push(cols.map(c => JSON.stringify(h[c] ?? '')).join(','));
    });
    dlCSV(lines.join('\n'), 'stupi_export');
}

function sortTable(ctx, col) {
    const key = ctx + '_' + col;
    const asc = !(sortState[key]);
    sortState[key] = asc;
    if (ctx === 'hives') {
        hivesData.sort((a,b) => {
            const av = a[col], bv = b[col];
            return asc ? (av > bv ? 1 : -1) : (av < bv ? 1 : -1);
        });
        renderHivesTable(hivesData);
    }
}

// ═══════════════════════════════════════════════════════════════
// CONTROLLERS
// ═══════════════════════════════════════════════════════════════
async function loadControllers() {
    try {
        const r = await api('get_controllers');
        controllersData = r.data;
        const el = document.getElementById('controllers-list');
        if (!r.data.length) {
            el.innerHTML = '<div class="empty-state"><div class="empty-icon">&#9881;</div><p>Niciun controller gasit</p></div>';
            return;
        }
        el.innerHTML = r.data.map(c => `
        <div class="card" style="margin-bottom:12px">
          <div class="card-header">
            <div class="card-title">
              <span class="status-dot ${c.hive_count > 0 ? 'online' : 'offline'}"></span>
              ${esc(c.name)} <span style="font-size:11px;color:var(--text-muted);font-weight:400">(${c.id})</span>
            </div>
            <button class="btn btn-ghost btn-sm" onclick="editController('${c.id}','${esc(c.name)}')">&#9998; Redenumeste</button>
          </div>
          <div style="display:flex;gap:24px;font-size:13px;color:var(--text-secondary);margin-bottom:12px">
            <span>Stupi: <strong>${c.hive_count}</strong></span>
            <span>Firmware: <strong>${esc(c.firmware||'-')}</strong></span>
            <span>IP: <strong>${esc(c.ip||'-')}</strong></span>
            <span>Ultima activitate: <strong>${fmtTime(c.lastSeen)}</strong></span>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:8px">
            ${c.hives.map(h => `
              <div style="background:var(--bg-base);border:1px solid var(--border);border-radius:6px;padding:6px 10px;font-size:12px;display:flex;align-items:center;gap:6px">
                <span class="status-dot ${h.online ? 'online' : 'offline'}"></span>
                ${esc(h.nickname)} <span class="td-mono">(${h.chipID})</span>
              </div>`).join('')}
          </div>
        </div>`).join('');
    } catch(e) {}
}

async function editController(id, name) {
    const newName = window.prompt('Redenumeste controller-ul:', name);
    if (!newName || newName === name) return;
    await api('update_controller', 'POST', {}, { id, name: newName });
    toast('Controller redenumit!', 'success');
    loadControllers();
}

// ═══════════════════════════════════════════════════════════════
// ALERTE
// ═══════════════════════════════════════════════════════════════
async function loadAlerts() {
    try {
        const r = await api('get_alerts');
        const { active, resolved } = r.data;

        document.getElementById('active-alerts-count').textContent = active.length;

        const activeEl = document.getElementById('active-alerts-list');
        activeEl.innerHTML = active.length ? active.map(a => `
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;border-bottom:1px solid var(--border-light)">
              <div>
                <div style="font-weight:600;font-size:13px">${esc(a.hive_name||a.stup)}</div>
                <div style="font-size:12px;color:var(--text-muted)">${esc(a.msg||'')} &middot; ${a.date||''}</div>
              </div>
              <button class="btn btn-success btn-sm" onclick="resolveAlert('${a.id}')">&#10003;</button>
            </div>`).join('')
          : '<div class="empty-state"><p>Nicio alerta activa</p></div>';

        const resEl = document.getElementById('resolved-alerts-list');
        resEl.innerHTML = resolved.length ? resolved.map(a => `
            <div style="padding:8px 12px;border-bottom:1px solid var(--border-light);font-size:12px">
              <span style="color:var(--text-muted)">${a.date}</span>
              <strong style="margin:0 6px">${esc(a.stup)}</strong>
              <span style="color:var(--text-secondary)">${esc(a.msg||'')}</span>
              <span style="color:var(--green);margin-left:6px">&#10003; ${esc(a.user)}</span>
            </div>`).join('')
          : '<div class="empty-state"><p>Nicio alerta rezolvata</p></div>';
    } catch(e) {}
}

async function resolveAlert(id) {
    await api('resolve_alert', 'POST', {}, { id });
    toast('Alerta rezolvata!', 'success');
    loadAlerts();
    loadDashboard();
}

async function resolveAllAlerts() {
    confirm('Rezolva toate alertele', 'Marchezi toate alertele active ca rezolvate?', async () => {
        const r = await api('resolve_all_alerts', 'POST', {}, {});
        toast(`${r.data.count} alerte rezolvate!`, 'success');
        loadAlerts();
        loadDashboard();
    });
}

// ═══════════════════════════════════════════════════════════════
// JURNAL
// ═══════════════════════════════════════════════════════════════
async function loadJurnal() {
    const stup   = document.getElementById('jurnal-filter-stup').value;
    const user   = document.getElementById('jurnal-filter-user').value;
    const filter = document.getElementById('jurnal-filter-type').value;
    const tbody  = document.getElementById('jurnal-tbody');
    tbody.innerHTML = '<tr class="loading-row"><td colspan="6"><div class="spinner"></div></td></tr>';
    try {
        const r = await api('get_jurnal', 'GET', { page: jurnalPage, limit: 50, stup, user, filter });
        const { items, total, pages } = r.data;

        // Populeaza filtre
        const stupi = [...new Set(items.map(j => j.stup).filter(Boolean))].sort();
        const useri = [...new Set(items.map(j => j.user).filter(Boolean))].sort();
        populateSelect('jurnal-filter-stup', stupi, 'Toti stupii');
        populateSelect('jurnal-filter-user', useri, 'Toti userii');

        tbody.innerHTML = items.map(j => `
            <tr>
              <td class="td-muted">${j.date}</td>
              <td><strong>${esc(j.stup)}</strong></td>
              <td class="td-muted">${esc(j.user)}</td>
              <td style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(j.text||'')}</td>
              <td>${j.image ? `<a href="../${j.image}" target="_blank" style="color:var(--honey)">&#128247;</a>` : '-'}</td>
              <td><button class="btn btn-danger btn-sm btn-icon" onclick="deleteJurnal('${j.id}')">&#128465;</button></td>
            </tr>`).join('') || '<tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted)">Nicio nota gasita</td></tr>';

        renderPagination('jurnal-pagination', pages, jurnalPage, p => { jurnalPage = p; loadJurnal(); });
    } catch(e) {}
}

function deleteJurnal(id) {
    confirm('Sterge nota', 'Aceasta actiune este ireversibila!', async () => {
        await api('delete_jurnal', 'POST', {}, { id });
        toast('Nota stearsa!', 'success');
        loadJurnal();
    });
}

// ═══════════════════════════════════════════════════════════════
// HARVEST & EXPENSES
// ═══════════════════════════════════════════════════════════════
async function loadHarvest() {
    const year = document.getElementById('harvest-filter-year').value;
    try {
        const r = await api('get_harvest', 'GET', { year });
        const { harvest, expenses, total_kg, total_ron, total_exp, profit } = r.data;

        // Populam filtrele de an
        const years = [...new Set(harvest.map(h => (h.date||'').split('.')[2]?.trim()).filter(Boolean))].sort().reverse();
        populateSelect('harvest-filter-year', years, 'Toti anii');

        document.getElementById('h-total-kg').textContent  = total_kg + ' kg';
        document.getElementById('h-total-ron').textContent = total_ron + ' RON';
        const p = document.getElementById('h-profit');
        p.textContent = profit + ' RON';
        p.style.color = profit >= 0 ? 'var(--green)' : 'var(--red)';

        document.getElementById('harvest-tbody').innerHTML = harvest.map(h => {
            const val = (parseFloat(h.kg||0) * parseFloat(h.pret||0)).toFixed(0);
            return `<tr>
              <td class="td-muted">${h.date}</td>
              <td><strong>${esc(h.stup)}</strong></td>
              <td>${esc(h.tip)}</td>
              <td>${h.kg} kg</td>
              <td>${h.pret} RON</td>
              <td style="color:var(--green);font-weight:700">${val} RON</td>
              <td><button class="btn btn-danger btn-sm btn-icon" onclick="deleteHarvestRow('${h.id}')">&#128465;</button></td>
            </tr>`;
        }).join('') || '<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted)">Nicio recolta</td></tr>';

        document.getElementById('expenses-tbody').innerHTML = expenses.map(e => `
            <tr>
              <td class="td-muted">${e.date}</td>
              <td><strong>${esc(e.stup)}</strong></td>
              <td style="color:var(--red)">${e.suma} RON</td>
              <td>${esc(e.desc||'')}</td>
              <td><button class="btn btn-danger btn-sm btn-icon" onclick="deleteExpenseRow('${e.id}')">&#128465;</button></td>
            </tr>`).join('') || '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted)">Nicio cheltuiala</td></tr>';

        // ROI per stup
        const byStup = {};
        harvest.forEach(h => {
            if (!byStup[h.stup]) byStup[h.stup] = { kg:0, venit:0, chelt:0 };
            byStup[h.stup].kg    += parseFloat(h.kg||0);
            byStup[h.stup].venit += parseFloat(h.kg||0)*parseFloat(h.pret||0);
        });
        expenses.forEach(e => {
            if (!byStup[e.stup]) byStup[e.stup] = { kg:0, venit:0, chelt:0 };
            byStup[e.stup].chelt += parseFloat(e.suma||0);
        });
        const roiHtml = Object.entries(byStup).map(([stup, d]) => {
            const profit = d.venit - d.chelt;
            const cpk    = d.kg > 0 && d.chelt > 0 ? (d.chelt/d.kg).toFixed(2) : '-';
            return `<div style="background:var(--bg-elevated);border-radius:8px;padding:12px;border:1px solid var(--border)">
              <div style="font-weight:700;margin-bottom:6px">${esc(stup)}</div>
              <div style="font-size:12px;color:var(--text-secondary);display:grid;grid-template-columns:1fr 1fr;gap:4px">
                <span>Recolta: <strong>${d.kg.toFixed(1)} kg</strong></span>
                <span>Venit: <strong style="color:var(--green)">${d.venit.toFixed(0)} RON</strong></span>
                <span>Cheltuieli: <strong style="color:var(--red)">${d.chelt.toFixed(0)} RON</strong></span>
                <span>Profit: <strong style="color:${profit>=0?'var(--green)':'var(--red)'}">${profit.toFixed(0)} RON</strong></span>
                <span>Cost/kg: <strong>${cpk} RON</strong></span>
              </div>
            </div>`;
        }).join('');
        document.getElementById('roi-stats').innerHTML = `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px">${roiHtml}</div>`;
    } catch(e) {}
}

function deleteHarvestRow(id) {
    confirm('Sterge recolta', 'Aceasta actiune este ireversibila!', async () => {
        await api('delete_harvest', 'POST', {}, { id });
        toast('Recolta stearsa!', 'success');
        loadHarvest();
    });
}

function deleteExpenseRow(id) {
    confirm('Sterge cheltuiala', 'Aceasta actiune este ireversibila!', async () => {
        await api('delete_expense', 'POST', {}, { id });
        toast('Cheltuiala stearsa!', 'success');
        loadHarvest();
    });
}

// ═══════════════════════════════════════════════════════════════
// TASKS
// ═══════════════════════════════════════════════════════════════
async function loadTasks() {
    const filter = document.getElementById('tasks-filter').value;
    const user   = document.getElementById('tasks-filter-user').value;
    const tbody  = document.getElementById('tasks-tbody');
    tbody.innerHTML = '<tr class="loading-row"><td colspan="7"><div class="spinner"></div></td></tr>';
    try {
        const r = await api('get_tasks', 'GET', { filter, user });
        const tasks = r.data;
        const useri = [...new Set(tasks.map(t => t.user).filter(Boolean))].sort();
        populateSelect('tasks-filter-user', useri, 'Toti userii');
        tbody.innerHTML = tasks.map(t => `
            <tr>
              <td>${t.done
                  ? '<span class="badge badge-done">&#10003; Done</span>'
                  : '<span class="badge badge-pending">Pending</span>'}
              </td>
              <td class="td-muted">${t.date}</td>
              <td><strong>${esc(t.stup)}</strong></td>
              <td class="td-muted">${esc(t.user)}</td>
              <td style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(t.text||'')}</td>
              <td class="td-muted">${esc(t.type||'')}</td>
              <td style="display:flex;gap:6px">
                <button class="btn btn-ghost btn-sm btn-icon" onclick="toggleTask('${t.id}')" title="${t.done?'Reactiveza':'Marcheaza done'}">
                  ${t.done ? '&#8635;' : '&#10003;'}
                </button>
                <button class="btn btn-danger btn-sm btn-icon" onclick="deleteTask('${t.id}')">&#128465;</button>
              </td>
            </tr>`).join('') || '<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted)">Nicio sarcina</td></tr>';
    } catch(e) {}
}

async function toggleTask(id) {
    await api('toggle_task', 'POST', {}, { id });
    loadTasks();
}

function deleteTask(id) {
    confirm('Sterge sarcina', 'Aceasta actiune este ireversibila!', async () => {
        await api('delete_task', 'POST', {}, { id });
        toast('Sarcina stearsa!', 'success');
        loadTasks();
    });
}

// ═══════════════════════════════════════════════════════════════
// INVENTORY
// ═══════════════════════════════════════════════════════════════
async function loadInventory() {
    try {
        const r = await api('get_inventory');
        const inv = r.data;
        document.getElementById('inventory-tbody').innerHTML = inv.map(i => `
            <tr>
              <td><strong>${esc(i.item)}</strong></td>
              <td>${i.qty}</td>
              <td class="td-muted">${esc(i.type)}</td>
              <td class="td-muted">${esc(i.category||'-')}</td>
              <td style="display:flex;gap:6px">
                <button class="btn btn-ghost btn-sm btn-icon" onclick="openInventoryModal(${JSON.stringify(i).replace(/"/g,'&quot;')})">&#9998;</button>
                <button class="btn btn-danger btn-sm btn-icon" onclick="deleteInventory('${i.id}')">&#128465;</button>
              </td>
            </tr>`).join('') || '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted)">Stoc gol</td></tr>';
    } catch(e) {}
}

function openInventoryModal(item = null) {
    document.getElementById('inv-id').value       = item?.id    || '';
    document.getElementById('inv-item').value     = item?.item  || '';
    document.getElementById('inv-qty').value      = item?.qty   || 1;
    document.getElementById('inv-type').value     = item?.type  || 'Bucati';
    document.getElementById('inv-category').value = item?.category || 'Tratamente&Hrana';
    openModal('inventory-modal');
}

async function saveInventoryItem() {
    const body = {
        id:       document.getElementById('inv-id').value,
        item:     document.getElementById('inv-item').value,
        qty:      document.getElementById('inv-qty').value,
        type:     document.getElementById('inv-type').value,
        category: document.getElementById('inv-category').value,
    };
    await api('save_inventory_item', 'POST', {}, body);
    closeModal('inventory-modal');
    toast('Produs salvat!', 'success');
    loadInventory();
}

function deleteInventory(id) {
    confirm('Sterge produs', '', async () => {
        await api('delete_inventory_item', 'POST', {}, { id });
        toast('Produs sters!', 'success');
        loadInventory();
    });
}

// ═══════════════════════════════════════════════════════════════
// USERS
// ═══════════════════════════════════════════════════════════════
async function loadUsers() {
    const tbody = document.getElementById('users-tbody');
    tbody.innerHTML = '<tr class="loading-row"><td colspan="6"><div class="spinner"></div></td></tr>';
    try {
        const r = await api('get_users');
        usersData = {};
        allHiveNames = r.data.all_hives;
        r.data.users.forEach(u => usersData[u.username] = u);

        tbody.innerHTML = r.data.users.map(u => `
            <tr>
              <td><strong>${esc(u.username)}</strong></td>
              <td class="td-muted">${esc(u.email||'-')}</td>
              <td>${u.is_admin ? '<span class="badge badge-admin">Admin</span>' : '<span class="badge" style="background:var(--bg-elevated);color:var(--text-muted)">User</span>'}</td>
              <td style="font-size:12px;color:var(--text-secondary)">
                ${(u.hive_names||[]).length ? u.hive_names.slice(0,3).map(n => esc(n)).join(', ') + (u.hive_names.length > 3 ? ` +${u.hive_names.length-3}` : '') : '-'}
              </td>
              <td>
                ${u.can_manage_manual ? '<span class="badge badge-manual" style="font-size:10px">Stupi manuali</span>' : ''}
              </td>
              <td style="display:flex;gap:6px">
                <button class="btn btn-ghost btn-sm btn-icon" onclick="editUser('${esc(u.username)}')">&#9998;</button>
                <button class="btn btn-danger btn-sm btn-icon" onclick="deleteUser('${esc(u.username)}')">&#128465;</button>
              </td>
            </tr>`).join('');
    } catch(e) {}
}

function openUserModal(username = null) {
    document.getElementById('user-modal-title').textContent = username ? 'Editeaza: ' + username : 'Utilizator Nou';
    document.getElementById('um-original-username').value = username || '';
    document.getElementById('um-username').value     = username || '';
    document.getElementById('um-username').readOnly  = !!username;
    document.getElementById('um-email').value        = '';
    document.getElementById('um-password').value     = '';
    document.getElementById('um-is-admin').checked   = false;
    document.getElementById('um-can-manual').checked = false;

    // Hive checkboxes
    const hivesDiv = document.getElementById('um-hives-checkboxes');
    hivesDiv.innerHTML = Object.entries(allHiveNames).map(([id, name]) =>
        `<label class="form-check" style="font-size:12px">
          <input type="checkbox" value="${id}" name="um-hive-cb"> ${esc(name)}
        </label>`).join('');

    // Controllers checkboxes
    const ctrlDiv = document.getElementById('um-controllers-checkboxes');
    ctrlDiv.innerHTML = controllersData.map(c =>
        `<label class="form-check" style="font-size:12px">
          <input type="checkbox" value="${c.id}" name="um-ctrl-cb"> ${esc(c.name)}
        </label>`).join('') || '<span style="color:var(--text-muted);font-size:12px">Niciun controller</span>';

    openModal('user-modal');
}

async function editUser(username) {
    await loadUsers();
    const u = usersData[username];
    if (!u) return;
    openUserModal(username);
    document.getElementById('um-email').value        = u.email || '';
    document.getElementById('um-is-admin').checked   = u.is_admin;
    document.getElementById('um-can-manual').checked = u.can_manage_manual;
    // Bifeaza stupii
    document.querySelectorAll('[name="um-hive-cb"]').forEach(cb => {
        cb.checked = (u.hives||[]).includes(cb.value);
    });
    document.querySelectorAll('[name="um-ctrl-cb"]').forEach(cb => {
        cb.checked = (u.controllers||[]).includes(cb.value);
    });
}

async function saveUser() {
    const original = document.getElementById('um-original-username').value;
    const username = document.getElementById('um-username').value.trim();
    const hives    = [...document.querySelectorAll('[name="um-hive-cb"]:checked')].map(cb => cb.value);
    const ctrls    = [...document.querySelectorAll('[name="um-ctrl-cb"]:checked')].map(cb => cb.value);
    const body = {
        username,
        email:             document.getElementById('um-email').value,
        password:          document.getElementById('um-password').value,
        is_admin:          document.getElementById('um-is-admin').checked ? 'true' : 'false',
        can_manage_manual: document.getElementById('um-can-manual').checked ? 'true' : 'false',
        hives:             JSON.stringify(hives),
        controllers:       JSON.stringify(ctrls),
    };
    const action = original ? 'update_user' : 'create_user';
    await api(action, 'POST', {}, body);
    closeModal('user-modal');
    toast(original ? 'User actualizat!' : 'User creat!', 'success');
    loadUsers();
}

function deleteUser(username) {
    confirm('Sterge utilizator', `Stergi permanent contul "${username}"?`, async () => {
        await api('delete_user', 'POST', {}, { username });
        toast('User sters!', 'success');
        loadUsers();
    }, '&#128465;');
}

// ═══════════════════════════════════════════════════════════════
// DATABASE
// ═══════════════════════════════════════════════════════════════
async function loadDatabase() {
    loadDbStats();
}

async function loadDbStats() {
    try {
        const r = await api('get_db_stats');
        const { tables, size_mb } = r.data;

        // Table selector
        const listEl = document.getElementById('db-table-list');
        listEl.innerHTML = Object.entries(tables).map(([t, n]) => `
            <div class="db-table-item" onclick="selectTable('${t}')" id="dbt-${t}">
              <div class="tbl-name">${t}</div>
              <div class="tbl-count">${n !== null ? n + ' rows' : 'N/A'}</div>
            </div>`).join('');

        // Stats grid
        const statsEl = document.getElementById('db-stats-grid');
        statsEl.innerHTML = Object.entries(tables).map(([t, n]) => `
            <div class="db-table-item">
              <div class="tbl-name">${t}</div>
              <div class="tbl-count" style="font-size:16px;font-weight:700;color:var(--honey);margin-top:4px">${n !== null ? n.toLocaleString() : '-'}</div>
            </div>`).join('');

        document.getElementById('db-size-info').innerHTML = `Dimensiune totala DB: <strong style="color:var(--honey)">${size_mb} MB</strong>`;
    } catch(e) {}
}

function selectTable(table) {
    currentTable = table;
    dbOffset = 0;
    document.querySelectorAll('.db-table-item').forEach(el => el.classList.remove('active'));
    document.getElementById('dbt-' + table)?.classList.add('active');
    document.getElementById('db-browser-toolbar').style.display = '';
    document.getElementById('db-table-card').style.display = '';
    browseTable();
}

async function browseTable() {
    if (!currentTable) return;
    const search = document.getElementById('db-search').value;
    document.getElementById('db-table-result').innerHTML = '<div style="padding:20px;text-align:center"><div class="spinner"></div></div>';
    try {
        const r = await api('query_table', 'GET', { table: currentTable, limit: 50, offset: dbOffset, search });
        const { rows, columns, total } = r.data;
        dbTotal = total;

        if (!rows.length) {
            document.getElementById('db-table-result').innerHTML = '<div class="empty-state"><p>Niciun rezultat</p></div>';
            return;
        }

        const html = `<table>
            <thead><tr>${columns.map(c => `<th>${c}</th>`).join('')}</tr></thead>
            <tbody>${rows.map(row => `<tr>${columns.map(c => `<td class="td-mono" style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${esc(String(row[c]||''))}">${esc(String(row[c]||''))}</td>`).join('')}</tr>`).join('')}</tbody>
        </table>`;
        document.getElementById('db-table-result').innerHTML = html;

        const pages = Math.ceil(total / 50);
        const currentPage = Math.floor(dbOffset / 50) + 1;
        renderPagination('db-pagination', pages, currentPage, p => {
            dbOffset = (p-1) * 50;
            browseTable();
        });
    } catch(e) {}
}

function exportCurrentTable() {
    if (!currentTable) return;
    window.open(`api.php?action=export_table_csv&table=${currentTable}&csrf=${window.CSRF}`);
}

async function runSQL() {
    const sql = document.getElementById('sql-input').value.trim();
    if (!sql) return;
    const statusEl = document.getElementById('sql-status');
    const resultEl = document.getElementById('sql-result');
    statusEl.textContent = 'Se ruleaza...';
    resultEl.innerHTML   = '';
    const t0 = Date.now();
    try {
        const r = await api('run_sql', 'POST', {}, { sql });
        const elapsed = Date.now() - t0;
        const d = r.data;
        if (d.rows !== undefined) {
            statusEl.textContent = `${d.count} randuri in ${elapsed}ms`;
            if (!d.rows.length) { resultEl.innerHTML = '<div class="alert-banner alert-info">Niciun rezultat</div>'; return; }
            resultEl.innerHTML = `<div class="table-wrap"><table>
                <thead><tr>${d.columns.map(c=>`<th>${c}</th>`).join('')}</tr></thead>
                <tbody>${d.rows.map(row=>`<tr>${d.columns.map(c=>`<td class="td-mono">${esc(String(row[c]||''))}</td>`).join('')}</tr>`).join('')}</tbody>
            </table></div>`;
        } else {
            statusEl.textContent = `${d.affected} randuri afectate in ${elapsed}ms`;
            resultEl.innerHTML = '<div class="alert-banner alert-success">Executat cu succes</div>';
        }
    } catch(e) {
        statusEl.textContent = 'Eroare';
    }
}

// ═══════════════════════════════════════════════════════════════
// AUDIT LOG
// ═══════════════════════════════════════════════════════════════
async function loadAudit() {
    const action = document.getElementById('audit-filter-action').value;
    const tbody  = document.getElementById('audit-tbody');
    tbody.innerHTML = '<tr class="loading-row"><td colspan="5"><div class="spinner"></div></td></tr>';
    try {
        const r = await api('get_audit_log', 'GET', { page: auditPage, limit: 50, action_filter: action });
        const { items, total, pages } = r.data;
        const actionColors = {
            AUTH:'var(--blue)', USER_CREATE:'var(--green)', USER_DELETE:'var(--red)',
            HIVE_DELETE:'var(--red)', SQL_QUERY:'var(--orange)', ALERT_RESOLVE:'var(--green)',
        };
        tbody.innerHTML = items.map(l => {
            const c = actionColors[l.action] || 'var(--text-muted)';
            return `<tr>
              <td class="td-mono" style="font-size:11px">${l.date}</td>
              <td><strong>${esc(l.user)}</strong></td>
              <td><span style="color:${c};font-weight:600;font-size:12px">${esc(l.action)}</span></td>
              <td style="font-size:12px;color:var(--text-secondary)">${esc(l.detail||'')}</td>
              <td class="td-mono" style="font-size:11px">${esc(l.ip||'')}</td>
            </tr>`;
        }).join('') || '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted)">Log gol</td></tr>';
        renderPagination('audit-pagination', pages, auditPage, p => { auditPage = p; loadAudit(); });
    } catch(e) {}
}

async function clearAuditLog() {
    confirm('Sterge Audit Log', 'Stergi permanent toate inregistrarile din audit log?', async () => {
        await api('clear_audit_log', 'POST', {}, {});
        toast('Log sters!', 'success');
        loadAudit();
    }, '&#128465;');
}

// ═══════════════════════════════════════════════════════════════
// ADMIN USERS
// ═══════════════════════════════════════════════════════════════
async function loadAdminUsers() {
    try {
        const r = await api('get_admin_users');
        document.getElementById('admins-tbody').innerHTML = r.data.map(a => `
            <tr>
              <td><strong>${esc(a.username)}</strong></td>
              <td>${esc(a.name||'-')}</td>
              <td class="td-muted">${esc(a.email||'-')}</td>
              <td>${a.role==='superadmin'
                  ? '<span class="badge badge-super">Superadmin</span>'
                  : '<span class="badge badge-admin">Admin</span>'}
              </td>
              <td class="td-muted">${a.created||'-'}</td>
              <td><button class="btn btn-danger btn-sm btn-icon" onclick="deleteAdminUser('${esc(a.username)}')">&#128465;</button></td>
            </tr>`).join('');
    } catch(e) {}
}

function openAdminUserModal() {
    ['au-username','au-name','au-email','au-password'].forEach(id => document.getElementById(id).value = '');
    openModal('admin-user-modal');
}

async function saveAdminUser() {
    const body = {
        username: document.getElementById('au-username').value,
        name:     document.getElementById('au-name').value,
        email:    document.getElementById('au-email').value,
        password: document.getElementById('au-password').value,
        role:     document.getElementById('au-role').value,
    };
    await api('create_admin_user', 'POST', {}, body);
    closeModal('admin-user-modal');
    toast('Admin creat!', 'success');
    loadAdminUsers();
}

function deleteAdminUser(username) {
    confirm('Sterge admin', `Stergi contul de admin "${username}"?`, async () => {
        await api('delete_admin_user', 'POST', {}, { username });
        toast('Admin sters!', 'success');
        loadAdminUsers();
    });
}

// ═══════════════════════════════════════════════════════════════
// SETTINGS
// ═══════════════════════════════════════════════════════════════
async function changePassword() {
    const old  = document.getElementById('old-pass').value;
    const nw   = document.getElementById('new-pass').value;
    const nw2  = document.getElementById('new-pass2').value;
    if (nw !== nw2) { toast('Parolele nu coincid!', 'error'); return; }
    await api('change_own_password', 'POST', {}, { old_password: old, new_password: nw });
    toast('Parola schimbata!', 'success');
    ['old-pass','new-pass','new-pass2'].forEach(id => document.getElementById(id).value = '');
}

async function doLogout() {
    await api('logout', 'POST', {}, {});
    window.location.href = 'index.php';
}

// ═══════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════
function esc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function populateSelect(id, options, defaultLabel) {
    const sel = document.getElementById(id);
    if (!sel) return;
    const cur = sel.value;
    sel.innerHTML = `<option value="">${defaultLabel}</option>` +
        options.map(o => `<option value="${esc(o)}" ${o===cur?'selected':''}>${esc(o)}</option>`).join('');
}

function renderPagination(containerId, pages, current, onPage) {
    const el = document.getElementById(containerId);
    if (!el || pages <= 1) { if (el) el.innerHTML = ''; return; }
    const max = 5;
    let html = `<button class="page-btn" ${current<=1?'disabled':''} onclick="(${onPage.toString()})(${current-1})">&#8249;</button>`;
    for (let i = 1; i <= pages; i++) {
        if (pages > max && i > 2 && i < pages-1 && Math.abs(i-current) > 1) {
            if (i === 3 || i === pages-2) html += '<span style="padding:5px 4px;color:var(--text-muted)">…</span>';
            continue;
        }
        html += `<button class="page-btn ${i===current?'active':''}" onclick="(${onPage.toString()})(${i})">${i}</button>`;
    }
    html += `<button class="page-btn" ${current>=pages?'disabled':''} onclick="(${onPage.toString()})(${current+1})">&#8250;</button>`;
    el.innerHTML = html;
}

function dlCSV(content, filename) {
    const blob = new Blob(['\uFEFF' + content], { type: 'text/csv;charset=utf-8' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename + '_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}

// ── Close modal on overlay click ──────────────────────────────
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.classList.remove('open');
    });
});

async function loadWeightChart() {
    const days = document.getElementById('weight-chart-days')?.value || 30;
    try {
        const r = await api('get_weight_chart', 'GET', { days });
        const { datasets } = r.data;
        const ctx = document.getElementById('weight-chart')?.getContext('2d');
        if (!ctx) return;

        if (weightChart) weightChart.destroy();

        weightChart = new Chart(ctx, {
            type: 'line',
            data: {
                datasets: datasets.map(ds => ({
                    label:       ds.label,
                    data:        ds.data,
                    borderColor: ds.color,
                    backgroundColor: ds.color + '18',
                    borderWidth: 2,
                    pointRadius: 2,
                    tension:     0.3,
                    fill:        false,
                }))
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                parsing: { xAxisKey: 'x', yAxisKey: 'y' },
                scales: {
                    x: { type: 'category', ticks: { color: '#888', maxTicksLimit: 10, font: { size: 10 } }, grid: { color: '#2a2f3a' } },
                    y: { ticks: { color: '#888', callback: v => v + ' kg', font: { size: 10 } }, grid: { color: '#2a2f3a' } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y} kg` } }
                }
            }
        });

        // Legend custom
        const leg = document.getElementById('weight-chart-legend');
        if (leg) {
            leg.innerHTML = datasets.map(ds =>
                `<span style="display:flex;align-items:center;gap:4px">
                   <span style="width:14px;height:3px;background:${ds.color};display:inline-block;border-radius:2px"></span>
                   ${esc(ds.label)}
                 </span>`
            ).join('');
        }
    } catch(e) {}
}

// Extinde loadDashboard pentru a incarca si graficul de greutate


// ═══════════════════════════════════════════════════════════════
// SYSTEM HEALTH
// ═══════════════════════════════════════════════════════════════
async function loadHealth() {
    document.getElementById('health-content').innerHTML = '<div style="text-align:center;padding:40px"><div class="spinner"></div></div>';
    try {
        const r = await api('get_system_health');
        const d = r.data;

        document.getElementById('health-generated').textContent = 'Generat: ' + d.generated_at;

        // Badge sidebar
        const issues = (d.stale_hives?.length || 0) + (d.low_battery?.length || 0) + d.expired_tasks;
        const badge  = document.getElementById('health-badge');
        if (badge) { badge.style.display = issues > 0 ? '' : 'none'; badge.textContent = issues; }

        let html = '';

        // Server info
        const si = d.server_info || {};
        const diskPct = si.disk_total_gb ? Math.round((1 - si.disk_free_gb / si.disk_total_gb) * 100) : 0;
        html += `<div class="two-col" style="margin-bottom:16px">
          <div class="card">
            <div class="card-header">
              <div class="card-title">&#128187; Server Info</div>
              <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Informații despre serverul web <strong>soul2soul.ro</strong> pe care rulează aplicația MATCA</div>
            </div>
            <table style="width:100%;font-size:13px;border-collapse:collapse">
              ${[
                ['PHP Version',   si.php_version,    'Versiunea limbajului PHP folosit de server'],
                ['SAPI',          si.php_sapi,        'Modul de rulare PHP (fpm-fcgi = FastCGI, optim pentru web)'],
                ['Memory Limit',  si.memory_limit,    'Memoria RAM maxima permisa per request PHP'],
                ['Max Exec Time', si.max_exec_time + 's', 'Timpul maxim de executie pentru un script PHP'],
                ['Upload Max',    si.upload_max,      'Dimensiunea maxima a fisierelor uploadate (poze inspecții etc.)'],
                ['Disk Liber',    si.disk_free_gb + ' GB / ' + si.disk_total_gb + ' GB (' + (100-diskPct) + '% liber)', 'Spatiu disponibil pe discul serverului soul2soul.ro'],
                ['App Size',      (si.app_root_size || '?') + ' MB', 'Dimensiunea totala a fisierelor aplicatiei MATCA pe server'],
                ['Server Time',   si.server_time,     'Ora curenta pe serverul de hosting'],
              ].map(([k,v,tip]) => `<tr style="border-bottom:1px solid var(--border)" title="${tip}"><td style="padding:6px 8px;color:var(--text-muted)">${k} <span style="font-size:9px;opacity:0.5;cursor:help" title="${tip}">ⓘ</span></td><td style="padding:6px 8px;font-weight:600">${esc(String(v||'-'))}</td></tr>`).join('')}
            </table>
          </div>
          <div class="card">
            <div class="card-header">
              <div class="card-title">&#128202; Metrici DB</div>
              <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Tabelele bazei de date MySQL <strong>danc_MatcaDB</strong> — număr înregistrări și spațiu ocupat</div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
              ${Object.entries(d.table_stats || {}).map(([t, s]) =>
                `<div style="background:var(--bg-elevated);border-radius:6px;padding:8px 10px">
                   <div style="font-size:10px;color:var(--text-muted);font-family:monospace">${esc(t)}</div>
                   <div style="font-size:16px;font-weight:700;color:var(--honey)">${(s.rows||0).toLocaleString()}</div>
                   <div style="font-size:10px;color:var(--text-muted)">${s.mb} MB</div>
                 </div>`
              ).join('')}
            </div>
            <div style="margin-top:10px;font-size:12px;color:var(--text-muted)">
              Citiri IoT ultimele 24h: <strong style="color:var(--green)">${d.readings_last_24h}</strong>
            </div>
          </div>
        </div>`;

        // Issues panel
        html += `<div class="stat-grid" style="margin-bottom:16px">
          <div class="stat-card" style="--accent:${d.stale_hives?.length ? 'var(--red)' : 'var(--green)'}">
            <div class="stat-value">${d.stale_hives?.length || 0}</div>
            <div class="stat-label">Stupi fara citiri 24h</div>
          </div>
          <div class="stat-card" style="--accent:${d.low_battery?.length ? 'var(--orange)' : 'var(--green)'}">
            <div class="stat-value">${d.low_battery?.length || 0}</div>
            <div class="stat-label">Baterii critice (&lt;3.4V)</div>
          </div>
          <div class="stat-card" style="--accent:${d.expired_tasks ? 'var(--orange)' : 'var(--green)'}">
            <div class="stat-value">${d.expired_tasks || 0}</div>
            <div class="stat-label">Sarcini expirate</div>
          </div>
          <div class="stat-card" style="--accent:var(--blue)">
            <div class="stat-value">${d.inactive_users?.length || 0}</div>
            <div class="stat-label">Useri inactivi 30 zile</div>
          </div>
        </div>`;

        // Stale hives
        if (d.stale_hives?.length) {
            html += `<div class="card" style="margin-bottom:12px">
              <div class="card-header"><div class="card-title" style="color:var(--red)">&#9888; Stupi Fara Citiri Recente</div></div>
              <div class="table-wrap"><table><thead><tr><th>Chip ID</th><th>Nickname</th><th>Ultima citire</th><th>Ore inactive</th></tr></thead><tbody>
              ${d.stale_hives.map(h => `<tr>
                <td class="td-mono">${esc(h.chip_id)}</td>
                <td>${esc(h.nickname)}</td>
                <td>${h.last_ts ? new Date(h.last_ts*1000).toLocaleString('ro-RO') : 'Niciodata'}</td>
                <td style="color:var(--red);font-weight:700">${h.hours_ago ? h.hours_ago + 'h' : '—'}</td>
              </tr>`).join('')}
              </tbody></table></div></div>`;
        }

        // Low battery
        if (d.low_battery?.length) {
            html += `<div class="card" style="margin-bottom:12px">
              <div class="card-header"><div class="card-title" style="color:var(--orange)">&#128267; Baterii Critice</div></div>
              <div class="table-wrap"><table><thead><tr><th>Chip ID</th><th>Nickname</th><th>Baterie</th><th>Timestamp</th></tr></thead><tbody>
              ${d.low_battery.map(h => `<tr>
                <td class="td-mono">${esc(h.chip_id)}</td>
                <td>${esc(h.nickname)}</td>
                <td style="color:var(--orange);font-weight:700">${h.battery}V</td>
                <td>${new Date(h.ts*1000).toLocaleString('ro-RO')}</td>
              </tr>`).join('')}
              </tbody></table></div></div>`;
        }

        // JSON files status
        html += `<div class="card">
          <div class="card-header"><div class="card-title">&#128196; Status Fisiere JSON</div></div>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px">
          ${Object.entries(d.json_files || {}).map(([f, s]) =>
            `<div style="background:var(--bg-elevated);border-radius:6px;padding:8px 10px;border-left:3px solid ${s.valid ? 'var(--green)' : 'var(--red)'}">
               <div style="font-size:11px;font-family:monospace;color:var(--text-secondary)">${esc(f)}</div>
               <div style="font-size:12px;margin-top:3px">
                 ${s.exists ? `<span style="color:var(--green)">✓</span> ${s.size_kb}KB` : '<span style="color:var(--red)">✗ Lipseste</span>'}
                 ${s.valid === false ? ' <span style="color:var(--red)">JSON invalid</span>' : ''}
               </div>
               ${s.modified ? `<div style="font-size:10px;color:var(--text-muted)">${esc(s.modified)}</div>` : ''}
             </div>`
          ).join('')}
          </div></div>`;

        document.getElementById('health-content').innerHTML = html;
    } catch(e) {
        document.getElementById('health-content').innerHTML = `<div class="alert-banner alert-warning">Eroare la health check: ${esc(e.message)}</div>`;
    }
}

// ═══════════════════════════════════════════════════════════════
// QUEEN HISTORY — CRUD
// ═══════════════════════════════════════════════════════════════
async function loadQueens() {
    const chipID = document.getElementById('queen-filter-hive')?.value || '';
    const tbody  = document.getElementById('queens-tbody');
    if (tbody) tbody.innerHTML = '<tr class="loading-row"><td colspan="8"><div class="spinner"></div></td></tr>';
    try {
        const r = await api('get_queens', 'GET', { chipID, page: queensPage, limit: 50 });
        const { items, total, pages } = r.data;

        if (tbody) {
            tbody.innerHTML = items.length ? items.map(q => `
                <tr>
                  <td class="td-mono" style="font-size:11px">${esc(q.date||'-')}</td>
                  <td>${esc(q.nickname || q.chip_id)}<br><span class="td-mono" style="font-size:10px;color:var(--text-muted)">${esc(q.chip_id)}</span></td>
                  <td><span class="badge" style="background:var(--bg-elevated)">${esc(q.event||'-')}</span></td>
                  <td>${esc(q.breed||'-')}</td>
                  <td>${esc(q.year||'-')}</td>
                  <td style="max-width:200px;font-size:12px;color:var(--text-secondary)">${esc(q.notes||'-')}</td>
                  <td>${esc(q.user||'-')}</td>
                  <td style="display:flex;gap:6px">
                    <button class="btn btn-ghost btn-sm btn-icon" onclick="editQueen(${JSON.stringify(q).replace(/"/g,'&quot;')})">&#9998;</button>
                    <button class="btn btn-danger btn-sm btn-icon" onclick="deleteQueen('${esc(q.id)}')">&#128465;</button>
                  </td>
                </tr>`).join('')
            : '<tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted)">Niciun eveniment</td></tr>';
        }
        renderPagination('queens-pagination', pages, queensPage, p => { queensPage = p; loadQueens(); });

        // Populeaza dropdown hive filter
        const sel = document.getElementById('queen-filter-hive');
        if (sel && sel.options.length <= 1 && hivesData.length) {
            hivesData.forEach(h => {
                const opt = document.createElement('option');
                opt.value = h.chipID; opt.textContent = h.nickname || h.chipID;
                sel.appendChild(opt);
            });
        }
    } catch(e) { if (tbody) tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--red)">Eroare la incarcare</td></tr>'; }
}

function openQueenModal() {
    document.getElementById('qm-id').value     = '';
    document.getElementById('qm-breed').value  = '';
    document.getElementById('qm-year').value   = '';
    document.getElementById('qm-notes').value  = '';
    document.getElementById('qm-date').value   = new Date().toLocaleString('ro-RO', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}).replace(',','');
    document.getElementById('queen-modal-title').textContent = 'Eveniment Nou Matca';

    // Populeaza chipid select
    const sel = document.getElementById('qm-chipid');
    sel.innerHTML = '<option value="">-- Selecteaza stup --</option>';
    hivesData.forEach(h => {
        const opt = document.createElement('option');
        opt.value = h.chipID; opt.textContent = (h.nickname || h.chipID) + ' (' + h.chipID + ')';
        sel.appendChild(opt);
    });
    openModal('queen-modal');
}

function editQueen(q) {
    if (typeof q === 'string') q = JSON.parse(q);
    document.getElementById('queen-modal-title').textContent = 'Editeaza Eveniment Matca';
    document.getElementById('qm-id').value    = q.id    || '';
    document.getElementById('qm-breed').value = q.breed || '';
    document.getElementById('qm-year').value  = q.year  || '';
    document.getElementById('qm-notes').value = q.notes || '';
    document.getElementById('qm-date').value  = q.date  || '';
    document.getElementById('qm-event').value = q.event || 'Inregistrare';

    const sel = document.getElementById('qm-chipid');
    sel.innerHTML = '<option value="">-- Selecteaza stup --</option>';
    hivesData.forEach(h => {
        const opt = document.createElement('option');
        opt.value = h.chipID; opt.textContent = (h.nickname || h.chipID) + ' (' + h.chipID + ')';
        if (h.chipID === q.chip_id) opt.selected = true;
        sel.appendChild(opt);
    });
    openModal('queen-modal');
}

async function saveQueen() {
    const chipID = document.getElementById('qm-chipid').value;
    if (!chipID) { toast('Selecteaza un stup', 'error'); return; }
    try {
        await api('save_queen', 'POST', {}, {
            id:      document.getElementById('qm-id').value,
            chipID,
            event:   document.getElementById('qm-event').value,
            breed:   document.getElementById('qm-breed').value,
            year:    document.getElementById('qm-year').value,
            notes:   document.getElementById('qm-notes').value,
            date:    document.getElementById('qm-date').value,
        });
        closeModal('queen-modal');
        toast('Eveniment salvat!', 'success');
        loadQueens();
    } catch(e) {}
}

function deleteQueen(id) {
    confirm('Sterge Eveniment', 'Stergi permanent acest eveniment din istoricul matcilor?', async () => {
        await api('delete_queen', 'POST', {}, { id });
        toast('Eveniment sters!', 'success');
        loadQueens();
    }, '&#128465;');
}

// ═══════════════════════════════════════════════════════════════
// ALERTE — istoric complet + delete
// ═══════════════════════════════════════════════════════════════
async function loadAlertsFull() {
    const stup  = document.getElementById('alert-filter-stup')?.value || '';
    const tbody = document.getElementById('alerts-tbody');
    if (tbody) tbody.innerHTML = '<tr class="loading-row"><td colspan="5"><div class="spinner"></div></td></tr>';
    try {
        const r = await api('get_alerts_full', 'GET', { stup, page: alertsPage, limit: 50 });
        const { items, total, pages } = r.data;

        if (tbody) {
            tbody.innerHTML = items.length ? items.map(a => `
                <tr>
                  <td class="td-mono" style="font-size:11px">${esc(a.date||'-')}</td>
                  <td>${esc(a.stup||'-')}</td>
                  <td style="font-size:12px;max-width:300px">${esc(a.msg||'-')}</td>
                  <td>${esc(a.user||'-')}</td>
                  <td>
                    <button class="btn btn-danger btn-sm btn-icon" onclick="deleteAlert('${esc(a.alert_id)}')">&#128465;</button>
                  </td>
                </tr>`).join('')
            : '<tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted)">Nicio alerta in istoric</td></tr>';
        }

        // Badge
        const badge = document.getElementById('alerts-badge');
        if (badge) { badge.style.display = total > 0 ? '' : 'none'; badge.textContent = total; }

        renderPagination('alerts-pagination', pages, alertsPage, p => { alertsPage = p; loadAlertsFull(); });

        // Populeaza filter stup
        const sel = document.getElementById('alert-filter-stup');
        if (sel && sel.options.length <= 1 && items.length) {
            const stups = [...new Set(items.map(a => a.stup).filter(Boolean))];
            stups.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s; opt.textContent = s;
                sel.appendChild(opt);
            });
        }
    } catch(e) { if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--red)">Eroare</td></tr>'; }
}

function deleteAlert(id) {
    confirm('Sterge Alerta', 'Stergi permanent aceasta alerta din baza de date?', async () => {
        await api('delete_alert', 'POST', {}, { alert_id: id });
        toast('Alerta stearsa!', 'success');
        loadAlertsFull();
    }, '&#128465;');
}

function deleteAllAlerts() {
    confirm('Sterge TOATE Alertele', 'Stergi permanent TOATE alertele din baza de date? Aceasta actiune este ireversibila!', async () => {
        await api('delete_all_alerts', 'POST', {}, {});
        toast('Toate alertele au fost sterse!', 'success');
        loadAlertsFull();
    }, '&#9888;');
}

// ═══════════════════════════════════════════════════════════════
// RAPOARTE ADMIN
// ═══════════════════════════════════════════════════════════════
async function loadReportActivity() {
    const tbody = document.getElementById('report-activity-tbody');
    if (tbody) tbody.innerHTML = '<tr class="loading-row"><td colspan="5"><div class="spinner"></div></td></tr>';
    try {
        const r = await api('get_admin_report', 'GET', { type: 'activity' });
        if (tbody) {
            tbody.innerHTML = r.data.map(u => `<tr>
                <td><strong>${esc(u.user)}</strong></td>
                <td style="text-align:center">${u.jurnal}</td>
                <td style="text-align:center;color:var(--green)">${u.tasks_done}</td>
                <td style="text-align:center;color:var(--orange)">${u.tasks_pending}</td>
                <td style="font-size:12px;color:var(--text-muted)">${esc(u.last_activity||'Nicio activitate')}</td>
            </tr>`).join('') || '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted)">Niciun utilizator</td></tr>';
        }
    } catch(e) {}
}

async function loadReportHivePerf() {
    const tbody = document.getElementById('report-perf-tbody');
    if (tbody) tbody.innerHTML = '<tr class="loading-row"><td colspan="8"><div class="spinner"></div></td></tr>';
    try {
        const r = await api('get_admin_report', 'GET', { type: 'hive_performance' });
        if (tbody) {
            tbody.innerHTML = r.data.map(h => `<tr>
                <td><strong>${esc(h.nickname)}</strong></td>
                <td class="td-mono" style="font-size:11px">${esc(h.chip_id)}</td>
                <td style="text-align:right">${h.kg_total} kg</td>
                <td style="text-align:right;color:var(--green)">${h.ron_total} RON</td>
                <td style="text-align:right;color:var(--red)">${h.expenses} RON</td>
                <td style="text-align:right;font-weight:700;color:${h.profit >= 0 ? 'var(--green)' : 'var(--red)'}">${h.profit} RON</td>
                <td style="text-align:center">${h.readings.toLocaleString()}</td>
                <td style="font-size:11px;color:var(--text-muted)">${esc(h.last_jurnal||'-')}</td>
            </tr>`).join('') || '<tr><td colspan="8" style="text-align:center;padding:20px;color:var(--text-muted)">Nicio data</td></tr>';
        }
    } catch(e) {}
}

async function loadReportGrowth() {
    try {
        const r = await api('get_admin_report', 'GET', { type: 'data_growth' });
        const pts = r.data;
        const ctx = document.getElementById('growth-chart')?.getContext('2d');
        if (!ctx) return;
        if (growthChart) growthChart.destroy();
        growthChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels:   pts.map(p => p.date),
                datasets: [{ label: 'Citiri IoT', data: pts.map(p => p.readings),
                    backgroundColor: '#f4a82088', borderColor: '#f4a820', borderWidth: 1 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#888', font: { size: 10 } }, grid: { color: '#2a2f3a' } },
                    y: { ticks: { color: '#888', font: { size: 10 } }, grid: { color: '#2a2f3a' } }
                }
            }
        });
    } catch(e) {}
}

// ═══════════════════════════════════════════════════════════════
// BACKUP & EXPORT
// ═══════════════════════════════════════════════════════════════
function downloadBackupJSON() {
    const url = `api.php?action=backup_json&csrf=${encodeURIComponent(window.CSRF)}`;
    const a   = document.createElement('a');
    a.href    = url; a.click();
    toast('Se descarca backup JSON...', 'info');
}

function downloadBackupSQL() {
    const url = `api.php?action=backup_sql&csrf=${encodeURIComponent(window.CSRF)}`;
    const a   = document.createElement('a');
    a.href    = url; a.click();
    toast('Se genereaza SQL dump...', 'info');
}

function exportTableCSV(table) {
    const url = `api.php?action=export_table_csv&table=${encodeURIComponent(table)}&csrf=${encodeURIComponent(window.CSRF)}`;
    const a   = document.createElement('a');
    a.href    = url; a.click();
    toast(`Export CSV: ${table}`, 'info');
}

async function loadBackupInfo() {
    const el = document.getElementById('backup-info-content');
    if (el) el.innerHTML = '<div style="text-align:center;padding:20px"><div class="spinner"></div></div>';
    try {
        const r = await api('get_backup_info');
        const d = r.data;
        if (!el) return;
        el.innerHTML = `
        <div class="two-col">
          <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px">
              Fisiere JSON (${d.total_json_kb} KB total + ${d.history_files} fisiere history)
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
              ${(d.json_files||[]).map(f => `
                <div style="background:var(--bg-elevated);border-radius:5px;padding:7px 10px;font-size:12px">
                  <div style="font-family:monospace;font-size:10px;color:var(--text-muted)">${esc(f.name)}</div>
                  <div style="font-weight:700">${f.kb} KB</div>
                  <div style="font-size:10px;color:var(--text-muted)">${esc(f.modified)}</div>
                </div>`).join('')}
            </div>
          </div>
          <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px">
              Tabele DB (${(d.total_db_rows||0).toLocaleString()} randuri total)
            </div>
            <div style="display:grid;gap:5px">
              ${(d.db_tables||[]).map(t => `
                <div style="display:flex;justify-content:space-between;align-items:center;background:var(--bg-elevated);border-radius:5px;padding:6px 10px">
                  <span style="font-family:monospace;font-size:11px">${esc(t.name)}</span>
                  <span style="font-weight:700;color:var(--honey)">${t.rows !== null ? t.rows.toLocaleString() : '—'}</span>
                </div>`).join('')}
            </div>
          </div>
        </div>`;
    } catch(e) { if (el) el.innerHTML = `<div class="alert-banner alert-warning">Eroare: ${esc(e.message)}</div>`; }
}



// ── Init ──────────────────────────────────────────────────────
loadDashboard();
loadControllers(); // preincarca pentru dropdown-uri