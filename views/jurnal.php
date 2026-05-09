<section id="view-jurnal" class="view-section">
    <div class="page-container">
        <div class="dashboard-grid">
            <div>
                <h2>📔 Jurnal Inspecții</h2>
                <div style="margin-bottom:15px">
                    <input type="text" id="j-search" style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd" placeholder="🔎 Caută în note..." oninput="renderJurnal()">
                </div>
                <div class="card-box" style="border: 2px solid var(--accent-green)">
                    <div class="input-grid">
                        <select id="j-stup-sel" style="padding:10px; border-radius:8px; border:1px solid #ddd; font-family: inherit; font-weight: 700;"></select>
                        <input type="file" id="j-img" accept="image/*">
                    </div>
                    <textarea id="j-text" rows="3" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; box-sizing:border-box; font-family: inherit;" placeholder="Notează observațiile..."></textarea>
                    <button onclick="saveNoteFromJurnal()" class="btn-resolve" style="width:100%; margin-top:10px">Salvează</button>
                </div>
                <div id="activity-calendar"></div>
                <div id="jurnal-list"></div>
            </div>
            <div>
                <h2>📝 Sarcini & Tratamente</h2>
                <div class="card-box">
                    <input type="text" id="t-text" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd" placeholder="Adaugă sarcină simplă...">
                    <button onclick="saveTask()" class="btn-resolve" style="width:100%; margin-top:10px; background:var(--wood-dark)">Adaugă Sarcină</button>
                    <hr style="border:0; border-top:1px solid #eee; margin:15px 0">
                    
                    <h4 style="margin:0 0 10px; font-size:0.9rem">💊 Tratament Inteligent Varroa & Hrană</h4>
                    
                    <select id="t-stup-trat" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; margin-bottom:10px"></select>
                    
                    <select id="t-tip-trat" onchange="updateTratDesc()" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; margin-bottom:10px; font-weight: bold; color: var(--premium-brown);">
                        <option value="Varachet Forte|3|7" data-desc="Fumigație pe urdiniș cu benzi fumigene. Se aplică seara târziu, când majoritatea culegătoarelor s-au întors în stup.">Varachet Forte (3 doze la 7 zile)</option>
                        <option value="Taktic / Scabatox (Fumigație)|3|7" data-desc="Fumigație cu substanță activă Amitraz. Se aplică seara, de preferat în lipsa puietului căpăcit.">Taktic / Scabatox (3 doze la 7 zile)</option>
                        <option value="Oxistrip (Benzi Oxalic)|1|0" data-desc="Se introduc 2-4 benzi (în funcție de puterea familiei) printre ramele cu puiet căpăcit. Se mențin în stup 30-45 de zile.">Oxistrip / Benzi Oxalic (Aplicate o dată)</option>
                        <option value="Acid Oxalic (Sublimare)|4|6" data-desc="Aproximativ 2g per familie folosind vaporizatorul. Atenție: Masca de protecție respiratorie este OBLIGATORIE! Nu lasă reziduuri.">Acid Oxalic Sublimare (4 doze la 6 zile)</option>
                        <option value="ApiBioxal (Picurare)|1|0" data-desc="Soluție de acid oxalic amestecată cu sirop de zahăr. Se picură câte 5ml pe fiecare interval ocupat de albine. Ideal iarna.">ApiBioxal / Oxalic Picurare (Tratament Unic)</option>
                        <option value="Acid Formic (Evaporare)|1|0" data-desc="Se folosesc evaporatoare speciale așezate deasupra ramelor. PERICOL: A nu se administra la temperaturi de peste 25°C!">Acid Formic Evaporare (Așezare unică)</option>
                        <option value="Apiguard (Thymol)|2|14" data-desc="Se așează o tăviță deasupra cuibului, cu ușa deschisă complet. După 14 zile se înlocuiește cu a doua tăviță.">Apiguard - Thymol (2 doze la 14 zile)</option>
                        <option value="Bayvarol / Mavrirol (Benzi)|1|0" data-desc="Benzi impregnate cu Fluvalinat/Flumetrin introduse în cuib pentru 30-40 de zile. Se rotesc substanțele anual.">Bayvarol / Mavrirol (Benzi - Aplicate o dată)</option>
                        <option value="Varromed (Picurare)|3|6" data-desc="Produs gata preparat pe bază de acid formic și oxalic. Aplicare cu picurătorul direct pe albinele dintre rame.">Varromed - Picurare (3 doze la 6 zile)</option>
                        
                        <option value="Hrănire Stimulentă (Sirop 1:1)|4|3" data-desc="Hrănire în porții mici (250-500ml) seara, pentru a simula culesul de întreținere și a stimula ponta mătcii.">Hrănire Stimulentă Sirop 1:1 (4 doze la 3 zile)</option>
                        <option value="Hrănire Completare (Sirop Invertit)|2|5" data-desc="Administrare în cantități mari (2-3L) pentru completarea rapidă a rezervelor de iernare, toamna târziu.">Hrănire Completare Sirop (2 doze la 5 zile)</option>
                        <option value="Hrănire Turtă Energetică|1|0" data-desc="Se decupează punga și se așează direct pe spetezele ramelor, deasupra ghemului de iernare (Apifonda, Dulcofruct etc).">Hrănire Turtă Energetică (O dată)</option>
                        <option value="Hrănire Turtă Proteică|1|0" data-desc="Turtă cu polen sau înlocuitori pentru dezvoltarea corpului gras și stimularea creșterii puietului primăvara timpuriu.">Hrănire Turtă Proteică (O dată)</option>
                        <option value="Supliment Protofil / Nosevit|4|4" data-desc="Supliment natural adăugat în sirop pentru stimularea familiilor, sănătatea tractului digestiv și combaterea Nosemozei.">Protofil / Nosevit în sirop (4 doze la 4 zile)</option>
                    </select>

                    <div id="trat-desc-box" style="font-size:0.8rem; background:#fdfbf7; padding:12px; border-radius:8px; border:1px dashed #10ac84; margin-bottom:10px; color: #5d4037;">
                        ℹ️ <b>Mod de aplicare:</b> <span id="trat-desc-text">Fumigație pe urdiniș cu benzi fumigene. Se aplică seara târziu, când majoritatea culegătoarelor s-au întors în stup.</span>
                    </div>

                    <div style="margin-bottom:10px;">
                        <label style="font-size:0.8rem; font-weight:bold; display:block; margin-bottom:5px; color: var(--text-dark);">📅 Începe din data de:</label>
                        <input type="date" id="t-start-date" value="<?php echo date('Y-m-d'); ?>" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; box-sizing:border-box;">
                    </div>

                    <div style="margin-bottom:15px; display:flex; align-items:center; gap:10px; background:rgba(0,0,0,0.03); padding:8px; border-radius:8px;">
                        <input type="checkbox" id="t-pre-alert" style="width:20px; height:20px; cursor:pointer;" checked>
                        <label for="t-pre-alert" style="font-size:0.85rem; font-weight:bold; cursor:pointer; color: var(--text-dark);">🔔 Email Reminder (cu 2 zile înainte)</label>
                    </div>

                    <button onclick="scheduleTreatment()" class="btn-resolve" style="width:100%; background:var(--accent-red)">Programează Schema Automat</button>
                    <div id="task-list" style="margin-top:20px"></div>
                </div>
                <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
                    <button onclick="exportCSV('jurnal')"    class="btn-resolve" style="flex:1;background:#34495e;min-width:100px;">📥 Jurnal CSV</button>
                    <button onclick="exportCSV('harvest')"   class="btn-resolve" style="flex:1;background:#27ae60;min-width:100px;">🍯 Recoltă CSV</button>
                    <button onclick="exportCSV('expenses')"  class="btn-resolve" style="flex:1;background:#e74c3c;min-width:100px;">💸 Cheltuieli CSV</button>
                    <button onclick="exportCSV('inventory')" class="btn-resolve" style="flex:1;background:#8e44ad;min-width:100px;">📦 Inventar CSV</button>
                </div>

                <div class="card-box" style="background:#f9f9f9; border-color:#d1b490;">
                    <h3 style="margin-top:0; color:var(--premium-brown);">📑 Raport Jurnal Detaliat</h3>
                    <div class="input-grid" style="margin-bottom:10px;">
                        <div>
                            <label style="font-size:0.8rem; font-weight:bold;">De la:</label>
                            <input type="date" id="exp-start" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;">
                        </div>
                        <div>
                            <label style="font-size:0.8rem; font-weight:bold;">Până la:</label>
                            <input type="date" id="exp-end" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;">
                        </div>
                    </div>
                    <label style="font-size:0.8rem; font-weight:bold;">Tip Raport:</label>
                    <select id="exp-type" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; margin-bottom:15px;">
                        <option value="toate">Toate Activitățile</option>
                        <option value="note">Doar Notițe / Inspecții</option>
                        <option value="sarcini">Doar Sarcini & Tratamente</option>
                        <option value="recolte">Doar Recoltări</option>
                    </select>
                    <button onclick="generateCustomReport()" class="btn-resolve" style="width:100%; background:#8e44ad;">📄 Generează Raport (Word)</button>
                </div>
            </div>
        </div>
    </div>
</section>