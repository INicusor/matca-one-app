<section id="view-inventory" class="view-section">
    <div class="page-container" style="max-width:700px">
        <h2 data-i18n="inv_title">📦 Gestiune Stocuri (Inventar)</h2>
        <div class="card-box">
            <div class="input-grid">
                <input type="text" id="i-item" placeholder="Nume Produs / Articol" data-i18n-placeholder="inv_item_name" style="padding:10px; border-radius:8px; border:1px solid #ddd">
                <select id="i-cat" style="padding:10px; border-radius:8px; border:1px solid #ddd">
                    <option value="Tratamente&Hrana">Tratamente & Hrană</option>
                    <option value="Unelte">Unelte</option>
                    <option value="Cutii&Rame">Cutii & Rame</option>
                </select>
            </div>
            <div class="input-grid">
                <input type="number" id="i-qty" placeholder="Cantitate" data-i18n-placeholder="inv_qty" style="padding:10px; border-radius:8px; border:1px solid #ddd">
                <select id="i-type" style="padding:10px; border-radius:8px; border:1px solid #ddd"><option>Bucăți</option><option>Litri</option><option>Kg</option></select>
            </div>
            <div id="i-share-wrap" style="margin-top:10px;">
                <label style="font-size:0.82rem;font-weight:700;color:var(--wood-dark);display:block;margin-bottom:5px;">👥 Share cu useri (opțional):</label>
                <div id="i-share-users" style="display:flex;flex-wrap:wrap;gap:6px;min-height:32px;padding:8px;border:1px solid #ddd;border-radius:8px;background:#fafafa;">
                    <span style="font-size:0.78rem;color:#aaa;align-self:center;">Se încarcă userii...</span>
                </div>
            </div>
            <button id="btn-save-inv" onclick="saveInventory()" class="btn-resolve" style="width:100%; background:var(--wood-dark); margin-top:10px" data-i18n="inv_add">Adaugă în Stoc</button>
        </div>
        <div id="inventory-list"></div>
    </div>
</section>