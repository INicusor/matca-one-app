<section id="view-inventory" class="view-section">
    <div class="page-container" style="max-width:700px">
        <h2 data-i18n="inv_title" style="display:flex;align-items:center;gap:10px;">
            <img src="uploads/emoji-inventory-frames-boxes.png" style="width:36px;height:36px;object-fit:contain;filter:drop-shadow(0 2px 5px rgba(0,0,0,0.25));" alt="inventory">
            <span>Gestiune Stocuri (Inventar)</span>
        </h2>
        <div class="card-box">
            <div class="input-grid">
                <input type="text" id="i-item" placeholder="Nume Produs / Articol" data-i18n-placeholder="inv_item_name" style="padding:10px; border-radius:8px; border:1px solid #ddd">
                <select id="i-cat" style="padding:10px; border-radius:8px; border:1px solid #ddd" onchange="updateInvCatPreview()">
                    <option value="Tratamente&amp;Hrana">Tratamente &amp; Hrană</option>
                    <option value="Unelte">Unelte</option>
                    <option value="Cutii&amp;Rame">Cutii &amp; Rame</option>
                </select>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0 4px;">
                <img src="uploads/emoji-hive-treatment-done.png" id="inv-cat-preview" style="width:40px;height:40px;object-fit:contain;filter:drop-shadow(0 2px 5px rgba(0,0,0,0.22));transition:all 0.3s ease;" alt="category">
                <small style="font-size:0.75rem;font-weight:700;color:var(--wood-dark);opacity:0.75;" id="inv-cat-hint">Tratamente &amp; Hrană</small>
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
<script>
function updateInvCatPreview() {
    var sel = document.getElementById('i-cat');
    var img = document.getElementById('inv-cat-preview');
    var hint = document.getElementById('inv-cat-hint');
    if (!sel || !img) return;
    var val = sel.value;
    var map = {
        'Tratamente&Hrana': { src: 'uploads/emoji-hive-treatment-done.png', label: 'Tratamente & Hr\u0103n\u0103' },
        'Unelte':           { src: 'uploads/emoji-inventory-scale.png',     label: 'Unelte & Echipamente' },
        'Cutii&Rame':       { src: 'uploads/emoji-inventory-frames-boxes.png', label: 'Cutii & Rame' }
    };
    var m = map[val] || map['Unelte'];
    img.style.opacity = '0';
    img.style.transform = 'scale(0.7)';
    setTimeout(function() {
        img.src = m.src;
        img.style.opacity = '1';
        img.style.transform = 'scale(1)';
        if (hint) hint.textContent = m.label;
    }, 150);
}
</script>