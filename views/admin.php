<section id="view-admin" class="view-section">
<div class="page-container" style="max-width:1100px">

    <h2>⚙️ Panou Administrare</h2>

    <!-- ═══════════════════════════════════════
         TAB BAR ADMIN
         ═══════════════════════════════════════ -->
    <div class="adm-tab-bar">
        <button class="adm-tab active" onclick="admShowTab('tab-users', this)"><span data-i18n="nav_users">👥 Utilizatori</span></button>
        <button class="adm-tab" onclick="admShowTab('tab-controllers', this)"><span data-i18n="nav_controllers">🔌 Controllere</span></button>
        <button class="adm-tab" onclick="admShowTab('tab-reports', this)"><span data-i18n="nav_reports">📊 Rapoarte</span></button>
        <button class="adm-tab" onclick="admShowTab('tab-email', this)"><span data-i18n="nav_email_alert">📧 Email & Alertare</span></button>
    </div>

    <!-- ═══════════════════════════════════════
         TAB 1: UTILIZATORI
         ═══════════════════════════════════════ -->
    <div id="tab-users" class="adm-tab-content active">

        <!-- Stats rapide -->
        <div class="adm-stats-row" id="adm-stats-row">
            <div class="adm-stat-card">
                <span class="adm-stat-icon">👥</span>
                <span class="adm-stat-val" id="stat-total-users">—</span>
                <span class="adm-stat-label"><span data-i18n="stat_users">Utilizatori</span></span>
            </div>
            <div class="adm-stat-card">
                <span class="adm-stat-icon">🐝</span>
                <span class="adm-stat-val" id="stat-total-hives-adm">—</span>
                <span class="adm-stat-label"><span data-i18n="stat_hives_alloc">Stupi Alocați</span></span>
            </div>
            <div class="adm-stat-card">
                <span class="adm-stat-icon">🛠️</span>
                <span class="adm-stat-val" id="stat-manual-access">—</span>
                <span class="adm-stat-label"><span data-i18n="stat_manual_access">Acces Manual</span></span>
            </div>
        </div>

        <!-- Toolbar: search + buton adaugă -->
        <div class="adm-toolbar">
            <div class="adm-search-wrap">
                <span class="adm-search-icon">🔍</span>
                <input type="text" id="admin-search-user" placeholder="Caută după nume sau email..."
                       oninput="filterAdminUsers()" class="adm-search-input">
            </div>
            <button class="adm-btn-add" onclick="admOpenAddUser()">+ Utilizator Nou</button>
        </div>

        <!-- Tabel useri -->
        <div class="adm-table-wrap">
            <table class="adm-table" id="adm-users-table">
                <thead>
                    <tr>
                        <th>Utilizator</th>
                        <th>Email</th>
                        <th><span data-i18n="stat_hives_alloc">Stupi Alocați</span></th>
                        <th>Controllere</th> <th>Rol</th> <th style="text-align:center">Acces Manual</th> <th style="text-align:center">Acțiuni</th>
                    </tr>
                </thead>
                <tbody id="admin-users-list">
                    <tr><td colspan="5" style="text-align:center;padding:30px;opacity:0.5;">Se încarcă...</td></tr>
                </tbody>
            </table>
        </div>
        <!-- Container ascuns pentru checkbox stupi (folosit de JS) -->
        <div id="adm-hives-container" style="display:none"></div>
    </div>


    <!-- ═══════════════════════════════════════
         TAB 2: CONTROLLERE ESP
         ═══════════════════════════════════════ -->
    <div id="tab-controllers" class="adm-tab-content">

        <div class="adm-toolbar">
            <div style="flex:1;font-size:0.88rem;color:var(--text-muted)">
                Grupează chipID-urile senzorilor pe controllere ESP/GSM. Un chipID nu poate aparține la mai multe controllere.
            </div>
            <button class="adm-btn-add" onclick="admOpenAddController()">+ Controller Nou</button>
        </div>

        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>ID Controller</th>
                        <th>Nume</th>
                        <th>Stupi</th>
                        <th>Ultima citire</th>
                        <th style="text-align:center">Status</th>
                        <th style="text-align:center">Acțiuni</th>
                    </tr>
                </thead>
                <tbody id="controllers-list-container">
                    <tr><td colspan="6" style="text-align:center;padding:20px;opacity:0.5">Se încarcă...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="card-box" style="margin-top:20px;border:1.5px dashed var(--wood-mid);background:rgba(212,134,11,0.04)">
            <h4 style="margin:0 0 8px;color:var(--premium-brown)">📡 Format payload firmware ESP</h4>
            <p style="margin:0 0 8px;font-size:0.85rem;opacity:0.75;">Adaugă câmpul <code>controllerID</code> în JSON-ul trimis de firmware. Stupii se asociază automat la primul POST.</p>
            <pre style="background:var(--wood-light);padding:10px;border-radius:8px;font-size:0.78rem;overflow-x:auto;margin:0">{
  "chipID": 123456,
  "weight": 32.5,
  "temperature": 34.2,
  "battery": 4.1,
  "lastUpdated": 1735000000,
  "controllerID": "ctrl_001"
}</pre>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         TAB 3: RAPOARTE
         ═══════════════════════════════════════ -->
    <div id="tab-reports" class="adm-tab-content">
        <div class="adm-reports-grid">

            <div class="adm-report-card" style="--rc:var(--accent-green)">
                <div class="adm-report-icon">💰</div>
                <div class="adm-report-info">
                    <h4>Bilanț Financiar</h4>
                    <p>Venituri, cheltuieli, profit net și ROI per stup</p>
                </div>
                <div class="adm-report-btns">
                    <button onclick="generateRaportFinanciar('pdf')" class="adm-rb pdf">PDF</button>
                    <button onclick="generateRaportFinanciar('word')" class="adm-rb word">Word</button>
                </div>
            </div>

            <div class="adm-report-card" style="--rc:#e74c3c">
                <div class="adm-report-icon">🏥</div>
                <div class="adm-report-info">
                    <h4>Sănătate Stupină</h4>
                    <p>Index sănătate, alerte active, baterii critice</p>
                </div>
                <div class="adm-report-btns">
                    <button onclick="generateRaportSanatate('pdf')" class="adm-rb pdf">PDF</button>
                    <button onclick="generateRaportSanatate('word')" class="adm-rb word">Word</button>
                </div>
            </div>

            <div class="adm-report-card" style="--rc:#f39c12">
                <div class="adm-report-icon">📈</div>
                <div class="adm-report-info">
                    <h4>Dinamica Culesului</h4>
                    <p>Recoltă per sortiment, stup și perioadă</p>
                </div>
                <div class="adm-report-btns">
                    <button onclick="generateDinamicaCulesului('pdf')" class="adm-rb pdf">PDF</button>
                    <button onclick="generateDinamicaCulesului('word')" class="adm-rb word">Word</button>
                </div>
            </div>

            <div class="adm-report-card" style="--rc:#8e44ad">
                <div class="adm-report-icon">📦</div>
                <div class="adm-report-info">
                    <h4>Inventar Stocuri</h4>
                    <p>Situație completă tratamente, unelte, echipamente</p>
                </div>
                <div class="adm-report-btns">
                    <button onclick="generateRaportInventar('pdf')" class="adm-rb pdf">PDF</button>
                    <button onclick="generateRaportInventar('word')" class="adm-rb word">Word</button>
                </div>
            </div>

            <div class="adm-report-card" style="--rc:#2980b9">
                <div class="adm-report-icon">📋</div>
                <div class="adm-report-info">
                    <h4>Registru ANSVSA</h4>
                    <p>Tratamente și recoltări pentru depunere dosar oficial</p>
                </div>
                <div class="adm-report-btns">
                    <button onclick="generateWordReport('pdf')" class="adm-rb pdf">PDF</button>
                    <button onclick="generateWordReport('word')" class="adm-rb word">Word</button>
                </div>
            </div>

            <div class="adm-report-card" style="--rc:#16a085">
                <div class="adm-report-icon">📔</div>
                <div class="adm-report-info">
                    <h4>Jurnal Activități</h4>
                    <p>Notițe, inspecții și sarcini pe perioadă personalizată</p>
                </div>
                <div class="adm-report-btns">
                    <div style="display:flex;gap:6px;margin-bottom:8px;">
                        <input type="date" id="rep-start" style="flex:1;padding:5px 8px;border-radius:6px;border:1px solid #ddd;font-size:0.78rem;">
                        <input type="date" id="rep-end"   style="flex:1;padding:5px 8px;border-radius:6px;border:1px solid #ddd;font-size:0.78rem;">
                    </div>
                    <select id="rep-type" style="width:100%;padding:5px 8px;border-radius:6px;border:1px solid #ddd;margin-bottom:8px;font-size:0.78rem;">
                        <option value="toate">Toate activitățile</option>
                        <option value="note">Doar notițe / inspecții</option>
                        <option value="sarcini">Doar sarcini & tratamente</option>
                        <option value="recolte">Doar recoltări</option>
                    </select>
                    <button onclick="generateCustomReport('pdf')" class="adm-rb pdf" style="width:48%">PDF</button>
                    <button onclick="generateCustomReport('word')" class="adm-rb word" style="width:48%;margin-left:4%">Word</button>
                </div>
            </div>

        </div>
    </div>

    <!-- ═══════════════════════════════════════
         TAB 3: EMAIL & ALERTARE
         ═══════════════════════════════════════ -->
    <div id="tab-email" class="adm-tab-content">
        <div class="card-box" style="border:2px solid var(--accent-blue);background:rgba(52,152,219,0.04);margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:15px;flex-wrap:wrap;">
                <span style="font-size:2.5rem;">📧</span>
                <div style="flex:1">
                    <h3 style="margin:0 0 4px;color:var(--accent-blue)">Raport Complet pe Email</h3>
                    <p style="margin:0;font-size:0.88rem;opacity:0.75;">Generează și trimite toate rapoartele (Financiar, Sănătate, Cules, Inventar) ca atașamente Word la adresa administratorului.</p>
                </div>
                <button id="btn-send-reports" onclick="sendAllReportsOnEmail()"
                        class="btn-resolve" style="background:var(--accent-blue);white-space:nowrap;">
                    📧 Trimite Rapoarte
                </button>
            </div>
        </div>
        <div class="card-box">
            <h3 style="margin-top:0;color:var(--premium-brown)">ℹ️ Despre sistemul de alertare</h3>
            <p style="font-size:0.88rem;line-height:1.7;opacity:0.8;">Sistemul trimite automat emailuri când:</p>
            <ul style="font-size:0.85rem;line-height:2;opacity:0.75;padding-left:20px;">
                <li>Bateria unui senzor scade sub <b>3.2V</b></li>
                <li>Greutatea unui stup scade cu mai mult de <b>0.25kg</b> în 24h (posibilă roire)</li>
                <li>Un tratament programat se apropie (cu <b>2 zile</b> înainte, dacă opțiunea e activă)</li>
            </ul>
        </div>
    </div>

</div><!-- /page-container -->
</section>

<!-- ═══════════════════════════════════════════════════════
     MODAL: ADAUGĂ / EDITEAZĂ UTILIZATOR
     ═══════════════════════════════════════════════════════ -->
<div id="adm-user-modal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(26,31,38,0.7);backdrop-filter:blur(6px);align-items:center;justify-content:center;">
    <div class="adm-modal-box">

        <div class="adm-modal-header">
            <h3 id="adm-modal-title" style="margin:0;color:var(--premium-brown)">Utilizator Nou</h3>
            <button class="adm-modal-close" onclick="admCloseModal()">✕</button>
        </div>

        <div class="adm-modal-body">

            <!-- Câmpuri de bază -->
            <div class="adm-form-row">
                <div class="adm-form-field">
                    <label>Nume utilizator <span class="req">*</span></label>
                    <input type="text" id="adm-user" placeholder="ex: apicultor_ion" maxlength="32"
                           pattern="[a-zA-Z0-9_]+" title="Litere, cifre și _ (fără spații)">
                </div>
                <div class="adm-form-field">
                    <label id="adm-pass-label">Parolă <span class="req">*</span></label>
                    <div style="position:relative">
                        <input type="password" id="adm-pass" placeholder="Minim 6 caractere"
                               maxlength="128" style="padding-right:40px;">
                        <button type="button" onclick="admTogglePass()" 
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted)">👁️</button>
                    </div>
                    <small id="adm-pass-hint" style="color:var(--text-muted);font-size:0.75rem;">Lasă gol pentru a păstra parola existentă</small>
                </div>
            </div>

            <div class="adm-form-row">
                <div class="adm-form-field" style="flex:2">
                    <label>Email</label>
                    <input type="email" id="adm-email" placeholder="apicultor@email.ro" maxlength="128">
                </div>
                <div class="adm-form-field" style="flex:0 0 auto;min-width:200px">
                    <label>Permisiuni</label>
                    <label class="adm-toggle-row" style="margin-bottom:8px">
                        <input type="checkbox" id="adm-can-manual">
                        <span class="adm-toggle-track"><span class="adm-toggle-thumb"></span></span>
                        <span style="font-size:0.85rem;">Stupi Manuali</span>
                    </label>
                    <label class="adm-toggle-row" style="border-color:rgba(212,134,11,0.4);background:rgba(212,134,11,0.05)">
                        <input type="checkbox" id="adm-is-admin">
                        <span class="adm-toggle-track" style=""><span class="adm-toggle-thumb"></span></span>
                        <span style="font-size:0.85rem;color:var(--honey,#d4860b);font-weight:800;">👑 Drepturi Admin</span>
                    </label>
                </div>
            </div>

            <!-- Acces stupi -->
            <div class="adm-form-field">
                <label>Stupi Permiși</label>
                <div class="adm-hive-grid" id="adm-hives-container-modal">
                    <p style="opacity:0.5;font-size:0.85rem;margin:0;text-align:center">Se încarcă stupii...</p>
                </div>
                <div style="display:flex;gap:10px;margin-top:8px;">
                    <button type="button" onclick="admSelectAllHives(true)"  class="adm-hive-sel-btn">✅ Toți</button>
                    <button type="button" onclick="admSelectAllHives(false)" class="adm-hive-sel-btn">✕ Niciunul</button>
                </div>
            </div>

            <!-- Secțiune controllere alocate -->
            <div class="adm-form-field" style="margin-top:4px">
                <label>Controllere Alocate <span style="font-size:0.75rem;font-weight:400;opacity:0.6">(accesul la toți stupii controllerului)</span></label>
                <div class="adm-hive-grid" id="adm-controllers-modal-grid" style="max-height:120px">
                    <p style="opacity:0.5;font-size:0.82rem;margin:0;text-align:center">Se încarcă...</p>
                </div>
            </div>

            <!-- Secțiune reset parolă (doar la editare) -->
            <div id="adm-reset-section" style="display:none;margin-top:5px;">
                <hr style="border:0;border-top:1px solid var(--wood-light);margin:15px 0;">
                <div style="background:var(--honey-pale,#fdf3dc);border:1px solid rgba(212,134,11,0.3);border-radius:10px;padding:14px;">
                    <p style="margin:0 0 10px;font-size:0.85rem;font-weight:700;color:var(--wood-dark);">🔑 Reset Parolă Rapidă</p>
                    <p style="margin:0 0 12px;font-size:0.8rem;opacity:0.7;">Trimite o parolă temporară (generată aleatoriu) pe emailul utilizatorului.</p>
                    <button type="button" onclick="admResetPassword()" 
                            class="btn-resolve" style="background:var(--honey,#d4860b);width:100%;padding:10px;">
                        📧 Trimite Parolă Temporară pe Email
                    </button>
                </div>
            </div>

        </div><!-- /modal-body -->

        <div class="adm-modal-footer">
            <button type="button" onclick="admCloseModal()" class="adm-btn-cancel">Anulează</button>
            <button type="button" onclick="addUser()" id="btn-adm-save" class="adm-btn-save">💾 Salvează Contul</button>
        </div>
        <div id="adm-cancel-container"></div>

    </div><!-- /modal-box -->
</div>

<style>
/* ══════════════════════════════════════
   ADMIN — STILURI DEDICATE
   ══════════════════════════════════════ */

/* Tab bar */
.adm-tab-bar { display:flex; gap:4px; background:rgba(255,255,255,0.6); padding:6px; border-radius:16px; margin-bottom:24px; border:1px solid var(--wood-light); backdrop-filter:blur(10px); flex-wrap:wrap; }
.adm-tab { flex:1; min-width:120px; padding:10px 16px; border:none; border-radius:12px; background:transparent; font-family:inherit; font-size:0.88rem; font-weight:800; color:var(--text-muted,#7f8c8d); cursor:pointer; transition:all 0.22s; }
.adm-tab.active { background:#fff; color:var(--premium-brown,#5d4037); box-shadow:0 3px 12px rgba(93,64,55,0.12); }
.adm-tab-content { display:none; animation:admFade 0.25s ease; }
.adm-tab-content.active { display:block; }
@keyframes admFade { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

/* Stats row */
.adm-stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:20px; }
.adm-stat-card { background:#fff; border:1.5px solid var(--wood-light); border-radius:14px; padding:16px; display:flex; flex-direction:column; align-items:center; gap:4px; box-shadow:0 2px 8px rgba(93,64,55,0.06); }
.adm-stat-icon { font-size:1.6rem; }
.adm-stat-val  { font-size:1.8rem; font-weight:900; color:var(--premium-brown); font-family:'Roboto Mono',monospace; line-height:1; }
.adm-stat-label{ font-size:0.72rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; }

/* Toolbar */
.adm-toolbar { display:flex; gap:12px; margin-bottom:14px; flex-wrap:wrap; align-items:center; }
.adm-search-wrap { flex:1; min-width:200px; position:relative; }
.adm-search-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); pointer-events:none; }
.adm-search-input { width:100%; padding:10px 12px 10px 36px; border:1.5px solid var(--wood-light); border-radius:10px; font-family:inherit; font-size:0.9rem; background:#fff; outline:none; box-sizing:border-box; transition:border-color 0.2s; }
.adm-search-input:focus { border-color:var(--honey,#d4860b); box-shadow:0 0 0 3px rgba(212,134,11,0.1); }
.adm-btn-add { padding:10px 20px; background:linear-gradient(135deg,var(--wood-dark,#a6845c),var(--honey,#d4860b)); color:#fff; border:none; border-radius:10px; font-family:inherit; font-weight:800; font-size:0.9rem; cursor:pointer; white-space:nowrap; transition:all 0.2s; box-shadow:0 3px 10px rgba(212,134,11,0.3); }
.adm-btn-add:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(212,134,11,0.4); }

/* Tabel */
.adm-table-wrap { border-radius:14px; overflow:hidden; border:1.5px solid var(--wood-light); box-shadow:0 4px 16px rgba(93,64,55,0.07); }
.adm-table { width:100%; border-collapse:collapse; font-size:0.88rem; }
.adm-table thead tr { background:var(--wood-light); }
.adm-table th { padding:13px 16px; font-weight:800; color:var(--premium-brown); text-align:left; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.4px; white-space:nowrap; }
.adm-table td { padding:12px 16px; border-bottom:1px solid rgba(209,180,144,0.18); vertical-align:middle; }
.adm-table tbody tr { background:#fff; transition:background 0.15s; }
.adm-table tbody tr:last-child td { border-bottom:none; }
.adm-table tbody tr:hover { background:#fdf8f0; }

/* Badge-uri tabel */
.adm-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:800; }
.adm-badge-admin { background:rgba(212,134,11,0.12); color:var(--honey,#d4860b); }
.adm-badge-manual { background:rgba(52,152,219,0.1); color:#2980b9; }
.adm-badge-std    { background:rgba(127,140,141,0.1); color:#7f8c8d; }

/* Hive pills în tabel */
.adm-hive-pill { display:inline-block; background:rgba(16,172,132,0.1); color:var(--accent-green,#10ac84); border:1px solid rgba(16,172,132,0.25); border-radius:6px; padding:2px 7px; font-size:0.72rem; font-weight:700; margin:2px; }
.adm-hive-count { font-size:0.8rem; color:var(--text-muted); font-style:italic; }

/* Action buttons în tabel */
.adm-act-btn { background:none; border:none; cursor:pointer; font-size:1rem; padding:5px 8px; border-radius:6px; transition:all 0.15s; }
.adm-act-btn:hover { background:rgba(0,0,0,0.06); transform:scale(1.1); }
.adm-act-edit   { color:#3498db; }
.adm-act-reset  { color:var(--honey,#d4860b); }
.adm-act-delete { color:var(--accent-red,#ee5253); }

/* Modal */
.adm-modal-box { background:#fff; width:92%; max-width:680px; border-radius:22px; box-shadow:0 20px 60px rgba(0,0,0,0.25); max-height:92vh; overflow-y:auto; display:flex; flex-direction:column; }
.adm-modal-header { padding:22px 24px 16px; border-bottom:1px solid var(--wood-light); display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; background:#fff; z-index:2; border-radius:22px 22px 0 0; }
.adm-modal-close { background:none; border:none; font-size:1.3rem; cursor:pointer; color:var(--text-muted); padding:4px 8px; border-radius:6px; transition:all 0.15s; }
.adm-modal-close:hover { background:var(--accent-red); color:#fff; }
.adm-modal-body { padding:22px 24px; flex:1; }
.adm-modal-footer { padding:16px 24px; border-top:1px solid var(--wood-light); display:flex; gap:12px; justify-content:flex-end; background:#fdfbf7; border-radius:0 0 22px 22px; }

/* Form fields în modal */
.adm-form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
.adm-form-field { display:flex; flex-direction:column; gap:6px; }
.adm-form-field label { font-size:0.8rem; font-weight:800; color:var(--premium-brown); }
.adm-form-field input, .adm-form-field select {
    padding:10px 12px; border:1.5px solid var(--wood-light); border-radius:10px;
    font-family:inherit; font-size:0.9rem; outline:none; transition:border-color 0.2s; background:#fff;
}
.adm-form-field input:focus, .adm-form-field select:focus {
    border-color:var(--honey,#d4860b); box-shadow:0 0 0 3px rgba(212,134,11,0.1);
}
.req { color:var(--accent-red); }

/* Toggle switch */
.adm-toggle-row { display:flex; align-items:center; gap:10px; cursor:pointer; padding:10px 12px; border:1.5px solid var(--wood-light); border-radius:10px; background:#fff; user-select:none; }
.adm-toggle-row input { display:none; }
.adm-toggle-track { width:38px; height:22px; background:#ddd; border-radius:11px; position:relative; flex-shrink:0; transition:background 0.2s; }
.adm-toggle-row input:checked ~ .adm-toggle-track { background:var(--accent-green,#10ac84); }
.adm-toggle-thumb { position:absolute; width:16px; height:16px; background:#fff; border-radius:50%; top:3px; left:3px; transition:transform 0.2s; box-shadow:0 1px 4px rgba(0,0,0,0.2); }
.adm-toggle-row input:checked ~ .adm-toggle-track .adm-toggle-thumb { transform:translateX(16px); }

/* Hive grid în modal */
.adm-hive-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:8px; max-height:200px; overflow-y:auto; padding:10px; border:1.5px solid var(--wood-light); border-radius:10px; background:#fdfbf7; }
.adm-hive-cb-label { display:flex; align-items:center; gap:7px; font-size:0.82rem; font-weight:700; cursor:pointer; padding:6px 8px; border-radius:7px; border:1px solid rgba(209,180,144,0.3); background:#fff; transition:all 0.15s; }
.adm-hive-cb-label:hover { background:rgba(16,172,132,0.07); border-color:var(--accent-green); }
.adm-hive-cb-label input:checked + span { color:var(--accent-green); }
.adm-hive-cb-label input { accent-color:var(--accent-green); width:15px; height:15px; cursor:pointer; }
.adm-hive-sel-btn { padding:5px 12px; background:#f5f0e8; border:1px solid var(--wood-light); border-radius:7px; font-family:inherit; font-size:0.78rem; font-weight:700; cursor:pointer; transition:all 0.15s; }
.adm-hive-sel-btn:hover { background:var(--wood-light); }

/* Footer buttons */
.adm-btn-cancel { padding:10px 20px; background:#f5f0e8; border:1.5px solid var(--wood-light); border-radius:10px; font-family:inherit; font-weight:800; font-size:0.9rem; cursor:pointer; transition:all 0.15s; color:var(--premium-brown); }
.adm-btn-cancel:hover { background:var(--wood-light); }
.adm-btn-save { padding:10px 24px; background:linear-gradient(135deg,var(--wood-dark,#a6845c),var(--honey,#d4860b)); color:#fff; border:none; border-radius:10px; font-family:inherit; font-weight:800; font-size:0.9rem; cursor:pointer; transition:all 0.2s; box-shadow:0 3px 10px rgba(212,134,11,0.3); }
.adm-btn-save:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(212,134,11,0.4); }

/* Reports grid */
.adm-reports-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }
.adm-report-card { background:#fff; border:1.5px solid var(--wood-light); border-radius:16px; padding:18px 20px; display:flex; flex-direction:column; gap:12px; box-shadow:0 3px 12px rgba(93,64,55,0.06); border-left:4px solid var(--rc,var(--wood-mid)); transition:box-shadow 0.2s; }
.adm-report-card:hover { box-shadow:0 6px 20px rgba(93,64,55,0.12); }
.adm-report-icon { font-size:2rem; line-height:1; }
.adm-report-info h4 { margin:0 0 4px; color:var(--premium-brown); font-size:1rem; }
.adm-report-info p  { margin:0; font-size:0.8rem; opacity:0.65; line-height:1.4; }
.adm-report-btns { display:flex; gap:8px; flex-wrap:wrap; margin-top:4px; }
.adm-rb { padding:7px 16px; border:none; border-radius:8px; font-family:inherit; font-size:0.82rem; font-weight:800; cursor:pointer; transition:all 0.2s; }
.adm-rb.pdf  { background:rgba(231,76,60,0.1); color:#c0392b; border:1px solid rgba(231,76,60,0.3); }
.adm-rb.word { background:rgba(52,152,219,0.1); color:#2980b9; border:1px solid rgba(52,152,219,0.3); }
.adm-rb:hover { filter:brightness(0.92); transform:translateY(-1px); }

/* Night mode */
body.night-mode .adm-tab-bar { background:rgba(36,43,53,0.8); border-color:#3d4656; }
body.night-mode .adm-tab.active { background:#2c3444; color:#f0ebe3; }
body.night-mode .adm-stat-card, body.night-mode .adm-modal-box, body.night-mode .adm-report-card { background:#242b35; border-color:#3d4656; }
body.night-mode .adm-table-wrap { border-color:#3d4656; }
body.night-mode .adm-table thead tr { background:#2c3444; }
body.night-mode .adm-table th { color:#f0ebe3; }
body.night-mode .adm-table tbody tr { background:#242b35; }
body.night-mode .adm-table tbody tr:hover { background:#2c3444; }
body.night-mode .adm-table td { border-color:#3d4656; color:#f0ebe3; }
body.night-mode .adm-search-input, body.night-mode .adm-form-field input, body.night-mode .adm-form-field select { background:#2c3444; border-color:#3d4656; color:#f0ebe3; }
body.night-mode .adm-toggle-row, body.night-mode .adm-hive-grid, body.night-mode .adm-hive-cb-label { background:#2c3444; border-color:#3d4656; color:#f0ebe3; }
body.night-mode .adm-modal-header, body.night-mode .adm-modal-footer { background:#242b35; border-color:#3d4656; }
body.night-mode .adm-btn-cancel, body.night-mode .adm-hive-sel-btn { background:#2c3444; border-color:#3d4656; color:#f0ebe3; }

/* Responsive */
@media (max-width:600px) {
    .adm-form-row { grid-template-columns:1fr; }
    .adm-stats-row { grid-template-columns:1fr 1fr; }
    .adm-reports-grid { grid-template-columns:1fr; }
    .adm-table th:nth-child(3), .adm-table td:nth-child(3) { display:none; }
}
</style>

<!-- ═══════════════════════════════════════════════════════
     MODAL: ADAUGĂ / EDITEAZĂ CONTROLLER ESP
     ═══════════════════════════════════════════════════════ -->
<div id="controller-modal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(26,31,38,0.7);backdrop-filter:blur(6px);align-items:center;justify-content:center;">
    <div class="adm-modal-box" style="max-width:560px">

        <div class="adm-modal-header">
            <h3 id="ctrl-modal-title" style="margin:0;color:var(--premium-brown)">Controller Nou</h3>
            <button class="adm-modal-close" onclick="document.getElementById('controller-modal').style.display='none'">✕</button>
        </div>

        <div class="adm-modal-body">

            <div class="adm-form-row">
                <div class="adm-form-field">
                    <label>ID Controller <span style="color:var(--accent-red)">*</span></label>
                    <input type="text" id="ctrl-id-input" placeholder="ex: ctrl_stupina_nord"
                        pattern="[a-zA-Z0-9_\-]+" maxlength="32"
                        title="Litere, cifre, _ și - (fără spații)">
                    <small style="color:var(--text-muted);font-size:0.72rem">Același ID trebuie configurat în firmware-ul ESP</small>
                </div>
                <div class="adm-form-field">
                    <label>Nume Afișat</label>
                    <input type="text" id="ctrl-name-input" placeholder="ex: Stupina Nord" maxlength="64">
                </div>
            </div>

            <div class="adm-form-field">
                <label>ChipID-uri alocate <span style="font-size:0.75rem;font-weight:400;opacity:0.6">(stupii aferenti acestui controller)</span></label>
                <div class="adm-hive-grid" id="ctrl-chip-grid" style="max-height:200px">
                    <p style="opacity:0.5;font-size:0.82rem;margin:0;text-align:center">Se încarcă stupii...</p>
                </div>
                <div style="display:flex;gap:10px;margin-top:8px">
                    <button type="button" onclick="document.querySelectorAll('.ctrl-chip-cb').forEach(cb=>cb.checked=true)" class="adm-hive-sel-btn">✅ Toți</button>
                    <button type="button" onclick="document.querySelectorAll('.ctrl-chip-cb').forEach(cb=>cb.checked=false)" class="adm-hive-sel-btn">✕ Niciunul</button>
                </div>
            </div>

        </div>

        <div class="adm-modal-footer">
            <button type="button" onclick="document.getElementById('controller-modal').style.display='none'" class="adm-btn-cancel">Anulează</button>
            <button type="button" onclick="admSaveController()" class="adm-btn-save">💾 Salvează Controller</button>
        </div>

    </div>
</div>