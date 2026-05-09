<section id="view-inventory" class="view-section">
    <div class="page-container" style="max-width:700px">
        <h2>📦 Gestiune Stocuri (Inventar)</h2>
        <div class="card-box">
            <div class="input-grid">
                <input type="text" id="i-item" placeholder="Nume Produs / Articol" style="padding:10px; border-radius:8px; border:1px solid #ddd">
                <select id="i-cat" style="padding:10px; border-radius:8px; border:1px solid #ddd">
                    <option value="Tratamente&Hrana">Tratamente & Hrană</option>
                    <option value="Unelte">Unelte</option>
                    <option value="Cutii&Rame">Cutii & Rame</option>
                </select>
            </div>
            <div class="input-grid">
                <input type="number" id="i-qty" placeholder="Cantitate" style="padding:10px; border-radius:8px; border:1px solid #ddd">
                <select id="i-type" style="padding:10px; border-radius:8px; border:1px solid #ddd"><option>Bucăți</option><option>Litri</option><option>Kg</option></select>
            </div>
            <button id="btn-save-inv" onclick="saveInventory()" class="btn-resolve" style="width:100%; background:var(--wood-dark); margin-top:10px">Adaugă în Stoc</button>
        </div>
        <div id="inventory-list"></div>
    </div>
</section>