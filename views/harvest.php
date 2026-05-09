<section id="view-harvest" class="view-section">
    <div class="page-container" style="max-width:850px">
        <h2>🍯 Log Recoltare</h2>
        <div class="card-box" style="border: 2px solid #f1c40f">
            <div class="input-grid">
                <select id="h-stup" style="padding:10px; border-radius:8px; border:1px solid #ddd"></select>
                <select id="h-tip" style="padding:10px; border-radius:8px; border:1px solid #ddd"><option>Salcâm</option><option>Tei</option><option>Polifloră</option><option>Rapiță</option></select>
            </div>
            <div class="input-grid">
                <input type="number" id="h-kg" placeholder="Cantitate (kg)" style="padding:10px; border-radius:8px; border:1px solid #ddd">
                <input type="number" id="h-pret" placeholder="Preț per Kg (RON)" style="padding:10px; border-radius:8px; border:1px solid #ddd">
            </div>
            <button onclick="saveHarvest()" class="btn-resolve" style="width:100%; background:#f1c40f; color:#333; margin-top:10px">Înregistrează Recoltă</button>
        </div>

        <h2 style="margin-top:40px">💸 Cheltuieli & ROI</h2>
        <div class="dashboard-grid">
            <div class="card-box" style="border: 2px solid var(--accent-red)">
                <h4>Adaugă Cheltuială</h4>
                <div class="input-grid">
                    <select id="e-stup" style="padding:10px; border-radius:8px; border:1px solid #ddd;"></select>
                    <input type="number" id="e-suma" placeholder="Sumă (RON)" style="padding:10px; border-radius:8px; border:1px solid #ddd;">
                </div>
                <input type="text" id="e-desc" placeholder="Descriere cheltuială (ex: Sirop, Tratamente)" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; margin-top:10px; margin-bottom:10px;">
                <button onclick="saveExpense()" class="btn-resolve" style="width:100%; background:var(--accent-red); margin-top:10px">Salvează Cheltuiala</button>
            </div>

            <div id="total-roi-box" class="card-box" style="background: var(--premium-brown); color: white;">
                <h3 style="margin:0; color:white;">💰 Bilanț Global</h3>
                <hr style="opacity:0.3">
                <p>Venituri: <b id="stat-total-venit">0</b> lei</p>
                <p>Cheltuieli: <b id="stat-total-cost">0</b> lei</p>
                <h2 id="stat-total-profit" style="margin:10px 0 0; color:white; font-size:2rem;">0 lei</h2>
                <small id="stat-total-roi-perc" style="opacity:0.8">ROI: 0%</small>
            </div>
        </div>

        <div id="roi-list-container" class="card-box" style="background:#f9f9f9; border-color:#eee;">
            <h3 style="margin-top:0">📊 Rentabilitate per Stup</h3>
            <div id="roi-list" style="overflow-x:auto"></div>
        </div>

        <div class="card-box">
            <h3>📜 Lista Cheltuieli Detaliată</h3>
            <div id="expense-history-list" style="max-height: 300px; overflow-y: auto;"></div>
        </div>

        <div class="card-box" style="margin-top:40px">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px">
              <h3 style="margin:0">🍯 Istoric Recoltări</h3>
              <select id="h-filter-stup" onchange="renderHarvest()" style="padding:8px; border-radius:8px; border:2px solid var(--wood-mid); font-weight:700;">
                  <option value="">Toți Stupii</option>
              </select>
            </div>
            <div id="harvest-list"></div>
        </div>
    </div>
</section>