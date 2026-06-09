<section id="view-harvest" class="view-section">
    <div class="page-container" style="max-width:850px">
        <h2 data-i18n="harvest_log_title">🍯 Log Recoltare</h2>
        <div class="card-box" style="border: 2px solid #f1c40f">
            <div class="input-grid">
                <select id="h-stup" style="padding:10px; border-radius:8px; border:1px solid #ddd"></select>
                <select id="h-tip" style="padding:10px; border-radius:8px; border:1px solid #ddd">
                    <option data-i18n="honey_acacia">Salcâm</option>
                    <option data-i18n="honey_linden">Tei</option>
                    <option data-i18n="honey_poly">Polifloră</option>
                    <option data-i18n="honey_rapeseed">Rapiță</option>
                    <option>Pădure</option>
                </select>
            </div>
            <div class="input-grid">
                <input type="number" id="h-kg" placeholder="Cantitate (kg)" data-i18n-placeholder="harvest_qty" style="padding:10px; border-radius:8px; border:1px solid #ddd">
                <input type="number" id="h-pret" placeholder="Preț per Kg (RON)" data-i18n-placeholder="harvest_price" style="padding:10px; border-radius:8px; border:1px solid #ddd">
            </div>
            <div style="display:flex;align-items:center;gap:10px;margin-top:6px;padding:8px 0;">
                <img src="uploads/emoji-harvest-spring-flow.png" style="width:36px;height:36px;object-fit:contain;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.2));transition:all 0.3s ease;" alt="harvest type" id="harvest-type-preview">
                <small style="font-size:0.75rem;color:var(--wood-dark);font-weight:700;opacity:0.7;" id="harvest-type-hint">Selectează tipul de miere</small>
            </div>
            <button onclick="saveHarvest()" class="btn-resolve" style="width:100%; background:#f1c40f; color:#333; margin-top:10px" data-i18n="harvest_record">Înregistrează Recoltă</button>
        </div>

        <h2 style="margin-top:40px" data-i18n="expenses_title">💸 Cheltuieli & ROI</h2>
        <div class="dashboard-grid">
            <div class="card-box" style="border: 2px solid var(--accent-red)">
                <h4 data-i18n="expense_add">Adaugă Cheltuială</h4>
                <div class="input-grid">
                    <select id="e-stup" style="padding:10px; border-radius:8px; border:1px solid #ddd;"></select>
                    <input type="number" id="e-suma" placeholder="Sumă (RON)" data-i18n-placeholder="expense_sum" style="padding:10px; border-radius:8px; border:1px solid #ddd;">
                </div>
                <input type="text" id="e-desc" placeholder="Descriere cheltuială (ex: Sirop, Tratamente)" data-i18n-placeholder="expense_desc" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; margin-top:10px; margin-bottom:10px;">
                <button onclick="saveExpense()" class="btn-resolve" style="width:100%; background:var(--accent-red); margin-top:10px" data-i18n="expense_save">Salvează Cheltuiala</button>
            </div>

            <div id="total-roi-box" class="card-box" style="background: var(--premium-brown); color: white;">
                <h3 style="margin:0; color:white; display:flex; align-items:center; gap:10px;">
                    <img src="uploads/emoji-inventory-profit.png" style="width:40px;height:40px;object-fit:contain;filter:drop-shadow(0 2px 6px rgba(0,0,0,0.4));" alt="profit">
                    <span data-i18n="balance_global">Bilanț Global</span>
                </h3>
                <hr style="opacity:0.3">
                <p>Venituri: <b id="stat-total-venit">0</b> lei</p>
                <p>Cheltuieli: <b id="stat-total-cost">0</b> lei</p>
                <h2 id="stat-total-profit" style="margin:10px 0 0; color:white; font-size:2rem;">0 lei</h2>
                <small id="stat-total-roi-perc" style="opacity:0.8">ROI: 0%</small>
            </div>
        </div>

        <div id="roi-list-container" class="card-box" style="background:#f9f9f9; border-color:#eee;">
            <h3 style="margin-top:0" data-i18n="roi_per_hive">📊 Rentabilitate per Stup</h3>
            <div id="roi-list" style="overflow-x:auto"></div>
        </div>

        <div class="card-box">
            <h3 data-i18n="expenses_list">📜 Lista Cheltuieli Detaliată</h3>
            <div id="expense-history-list" style="max-height: 300px; overflow-y: auto;"></div>
        </div>

        <div class="card-box" style="margin-top:40px">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px">
              <h3 style="margin:0; display:flex; align-items:center; gap:10px;">
                  <img src="uploads/emoji-harvest-spring-flow.png" style="width:32px;height:32px;object-fit:contain;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.2));" alt="harvest">
                  <span data-i18n="harvest_history">Istoric Recoltări</span>
              </h3>
              <select id="h-filter-stup" onchange="renderHarvest()" style="padding:8px; border-radius:8px; border:2px solid var(--wood-mid); font-weight:700;">
                  <option value="" data-i18n="all_hives">Toți Stupii</option>
              </select>
            </div>
            <div id="harvest-list"></div>
        </div>
    </div>
</section>
<script>
(function() {
    function updateHarvestPreview() {
        var sel = document.getElementById('h-tip');
        var img = document.getElementById('harvest-type-preview');
        var hint = document.getElementById('harvest-type-hint');
        if (!sel || !img) return;
        var val = (sel.value || '').toLowerCase();
        var isForest = val.includes('p\u0103dure') || val.includes('padure') || val.includes('tei') || val.includes('fag');
        img.src = isForest ? 'uploads/emoji-harvest-forest-flow.png' : 'uploads/emoji-harvest-spring-flow.png';
        if (hint) hint.textContent = isForest ? 'Cules de p\u0103dure / tei' : 'Cules de c\u00e2mpie / flori';
    }
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'h-tip') updateHarvestPreview();
    });
})();
</script>