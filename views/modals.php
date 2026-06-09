<div id="histModal">
    <div id="histBox">
      <span id="closeHist">✕</span>
      <h3 id="histTitle">Stup</h3>
      <div style="display:flex; justify-content:center; gap:10px; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px; flex-wrap:wrap;">
          <button class="nav-btn active" id="m-tab-graph" onclick="setModalTab('graph')">📈 Date</button>
          <button class="nav-btn" id="m-tab-inspec" onclick="setModalTab('inspec')">📋 Inspecție</button>
          <button class="nav-btn" id="m-tab-logs" onclick="setModalTab('logs')">📜 Jurnal Stup</button>
          <button class="nav-btn" id="m-tab-meta" onclick="setModalTab('meta')">👑 Management & Regină</button>
          <button class="nav-btn" id="m-tab-queenhist" onclick="setModalTab('queenhist')">📖 Istoric Regină</button>
          <button class="nav-btn" id="m-tab-photo" onclick="setModalTab('photo')">📸 Foto</button>
          <button class="nav-btn" id="m-tab-brood" onclick="setModalTab('brood')">🥚 Pontă</button>
          <button class="nav-btn" id="m-tab-harvest" onclick="setModalTab('harvest')">🍯 Recoltă</button>
      </div>

      <div id="m-view-graph">
          <div id="iot-graph-controls">
              <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                  <div class="segmented-control"><button class="seg-btn active" onclick="changeTab('W', this)">⚖️ Greutate</button><button class="seg-btn" onclick="changeTab('T', this)">🌡️ Temp.</button><button class="seg-btn" onclick="changeTab('B', this)">🔋 Baterie</button></div>
                  <div class="segmented-control"><button class="seg-btn rangeBtn active" onclick="changeRange(1, this)">24h</button><button class="seg-btn rangeBtn" onclick="changeRange(7, this)">7 Zile</button><button class="seg-btn rangeBtn" onclick="changeRange(365, this)">1 An</button></div>
              </div>
              <div style="display:flex; justify-content:center; margin-top:8px;">
                  <div class="segmented-control">
                      <button class="seg-btn active" onclick="changeChartViz('line', this)" title="Linie">📈 Linie</button>
                      <button class="seg-btn" onclick="changeChartViz('area', this)" title="Zonă">🏔️ Zonă</button>
                      <button class="seg-btn" onclick="changeChartViz('bar', this)" title="Bare">📊 Bare</button>
                      <button class="seg-btn" onclick="changeChartViz('scatter', this)" title="Puncte">⚬ Puncte</button>
                      <button class="seg-btn" onclick="changeChartViz('stepped', this)" title="Trepte">⌇ Trepte</button>
                  </div>
              </div>
              <div class="chart-container"><canvas id="histChart"></canvas></div>
          </div>

          <div id="manual-data-entry" style="display:none; padding: 20px; background: rgba(0,0,0,0.02); border-radius: 12px; border: 1px dashed #d1b490; margin-top: 10px;">
              <h4 style="margin-top:0; color:var(--wood-dark);">✍️ Actualizare Date Stup Manual</h4>
              <p style="font-size:0.8rem; opacity:0.7; margin-bottom: 15px;">Introdu valorile estimate sau măsurate la ultima inspecție:</p>
              
              <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                  <div>
                      <label style="display:block; font-size:0.8rem; font-weight:bold; margin-bottom:5px;">Greutate (kg):</label>
                      <input type="number" id="m-weight-input" step="0.1" style="width:100%; padding:12px; border-radius:8px; border:1px solid #ddd; box-sizing:border-box;">
                  </div>
                  <div>
                      <label style="display:block; font-size:0.8rem; font-weight:bold; margin-bottom:5px;">Temperatură (°C):</label>
                      <input type="number" id="m-temp-input" step="0.1" style="width:100%; padding:12px; border-radius:8px; border:1px solid #ddd; box-sizing:border-box;">
                  </div>
              </div>
              
              <button onclick="updateManualHiveData()" class="btn-resolve" style="width:100%; background:var(--accent-green); margin-bottom:15px; padding:12px; font-size:1rem;">💾 Salvează Datele</button>
              <hr style="border:0; border-top:1px solid #eee; margin:15px 0">
              <button onclick="deleteManualHive()" class="btn-resolve" style="width:100%; background:#e74c3c; font-size:0.85rem; padding:10px;">🗑️ Șterge Definitiv Acest Stup</button>
          </div>
      </div>

      <div id="m-view-inspec" style="display:none;">
          <h4 style="margin: 0 0 6px 0; color: var(--wood-dark);">🏠 Configurație Corp Stup</h4>
          <p style="font-size:0.72rem; opacity:0.6; margin:0 0 8px 0;">Selectează tipul corpului pentru a ajusta numărul de rame.</p>
          <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px;" id="hive-template-btns">
              <button class="hive-tpl-btn" data-tpl="dadant10"  onclick="applyHiveTemplate('dadant10')">Dadant 10<br><span style="font-size:0.62rem;opacity:0.7">Standard RO</span></button>
              <button class="hive-tpl-btn" data-tpl="dadant12"  onclick="applyHiveTemplate('dadant12')">Dadant 12<br><span style="font-size:0.62rem;opacity:0.7">Corp extins</span></button>
              <button class="hive-tpl-btn" data-tpl="lang10"    onclick="applyHiveTemplate('lang10')">Langstroth 10<br><span style="font-size:0.62rem;opacity:0.7">Internat.</span></button>
              <button class="hive-tpl-btn" data-tpl="lang8"     onclick="applyHiveTemplate('lang8')">Langstroth 8<br><span style="font-size:0.62rem;opacity:0.7">Compact</span></button>
              <button class="hive-tpl-btn" data-tpl="roman12"   onclick="applyHiveTemplate('roman12')">Românesc 12<br><span style="font-size:0.62rem;opacity:0.7">Vertical RO</span></button>
              <button class="hive-tpl-btn" data-tpl="multip20"  onclick="applyHiveTemplate('multip20')">Multi-plus 20<br><span style="font-size:0.62rem;opacity:0.7">Producție</span></button>
              <button class="hive-tpl-btn" data-tpl="warre8"    onclick="applyHiveTemplate('warre8')">Warré 8<br><span style="font-size:0.62rem;opacity:0.7">Natural</span></button>
              <button class="hive-tpl-btn" data-tpl="polonez15" onclick="applyHiveTemplate('polonez15')">Polonez 15<br><span style="font-size:0.62rem;opacity:0.7">Orizontal</span></button>
              <button class="hive-tpl-btn" data-tpl="onp10"     onclick="applyHiveTemplate('onp10')">ONP 10<br><span style="font-size:0.62rem;opacity:0.7">Norma PL</span></button>
              <button class="hive-tpl-btn" data-tpl="custom"    onclick="applyHiveTemplate('custom')">✏️ Custom<br><span style="font-size:0.62rem;opacity:0.7">Manual</span></button>
          </div>

          <h4 style="margin: 0 0 6px 0; color: var(--wood-dark);">🖼️ Harta Ramelor <span id="frame-count-label" style="font-size:0.75rem;font-weight:600;opacity:0.6;margin-left:6px;">10 rame</span></h4>
          <p style="font-size:0.72rem; opacity:0.6; margin:0 0 8px 0;">Click = înainte · Click dreapta = înapoi</p>
          <div class="frame-mapper" id="frame-mapper-container"></div>
          
          <div style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; font-size: 0.75rem; margin-bottom: 25px; color: var(--text-main); font-weight: 700;">
              <span style="display:flex; align-items:center; gap:5px;"><span style="width:16px; height:22px; background:#b0bec5; border-radius:3px; border:2px solid #78909c; display:inline-block;"></span> Goală</span>
              <span style="display:flex; align-items:center; gap:5px;"><span style="width:16px; height:22px; background:#f9a825; border-radius:3px; border:2px solid #e65100; display:inline-block;"></span> 🍯 Miere</span>
              <span style="display:flex; align-items:center; gap:5px;"><span style="width:16px; height:22px; background:#ef6c00; border-radius:3px; border:2px solid #bf360c; display:inline-block;"></span> 🌼 Polen</span>
              <span style="display:flex; align-items:center; gap:5px;"><span style="width:16px; height:22px; background:#5d4037; border-radius:3px; border:2px solid #3e2723; display:inline-block;"></span> 🐝 Puiet</span>
              <span style="display:flex; align-items:center; gap:5px;"><span style="width:16px; height:22px; background:#fffde7; border:2px dashed #f9a825; box-sizing:border-box; border-radius:3px; display:inline-block;"></span> ✨ Ceară / Foiță</span>
          </div>

          <h4 style="margin: 20px 0 10px 0; color: var(--wood-dark);">⚡ Inspecție Rapidă</h4>
          <div class="quick-group">
              <label>Matcă:</label>
              <button class="quick-btn" onclick="toggleQuick(this, 'matca')">Văzută</button>
              <button class="quick-btn active" onclick="toggleQuick(this, 'matca')">Nevăzută</button>
          </div>
          <div class="quick-group">
              <label>Puiet/Ouă:</label>
              <button class="quick-btn" onclick="toggleQuick(this, 'oua')">Prezente</button>
              <button class="quick-btn" onclick="toggleQuick(this, 'oua')">Lipsă</button>
          </div>
          <div class="quick-group">
              <label>Botci:</label>
              <button class="quick-btn active" onclick="toggleQuick(this, 'botci')">Fără</button>
              <button class="quick-btn" onclick="toggleQuick(this, 'botci')">Roire</button>
              <button class="quick-btn" onclick="toggleQuick(this, 'botci')">Salvare</button>
              <button class="quick-btn" onclick="toggleQuick(this, 'botci')">Schimb Liniștit</button>
          </div>
          
          <div style="display:flex;gap:8px;margin-top:10px;">
    <button id="btn-stup-ok" onclick="saveQuickInspectionOK()" class="btn-resolve" style="flex:1;background:linear-gradient(135deg,#10ac84,#0e8c6e);">✅ Stup OK</button>
    <button onclick="saveQuickInspection()" class="btn-resolve" style="flex:1;">💾 Salvează Raport</button>
</div>

          <div class="varroa-box" style="border-color: #8e44ad; margin-top:20px;">
              <h4 style="margin: 0 0 10px 0; color: #8e44ad;">💊 Administrare Tratament / Hrană</h4>
              <select id="m-tip-trat" onchange="updateModalTratDesc()" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; margin-bottom:10px; font-weight: bold;">
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
              <div id="m-trat-desc-box" style="font-size:0.8rem; background:#fdfbf7; padding:8px; border-radius:8px; border:1px dashed #8e44ad; margin-bottom:10px; color: #5d4037;">
                  ℹ️ <span id="m-trat-desc-text">Fumigație pe urdiniș cu benzi fumigene. Se aplică seara târziu, când majoritatea culegătoarelor s-au întors în stup.</span>
              </div>

              <div style="margin-bottom:10px;">
                  <label style="font-size:0.75rem; font-weight:bold; display:block; margin-bottom:5px; color: var(--text-dark);">📅 Data primei administrări:</label>
                  <input type="date" id="m-start-date" value="<?php echo date('Y-m-d'); ?>" lang="ro" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; box-sizing: border-box;">
              </div>
              
              <div style="margin-bottom:15px; display:flex; align-items:center; gap:10px; background:rgba(0,0,0,0.03); padding:8px; border-radius:8px;">
                  <input type="checkbox" id="m-pre-alert" style="width:20px; height:20px; cursor:pointer;" checked>
                  <label for="m-pre-alert" style="font-size:0.85rem; font-weight:bold; cursor:pointer; color: var(--text-dark);">🔔 Email Reminder (cu 2 zile înainte)</label>
              </div>

              <button onclick="scheduleModalTreatment()" class="btn-resolve" style="width:100%; background:#8e44ad;">Programează pentru acest stup</button>
          </div>

          <div class="varroa-box">
              <h4 style="margin: 0 0 10px 0; color: #e74c3c;">🦠 Calculator Infestare Varroa</h4>
              <div style="display:flex; gap:10px;">
                  <input type="number" id="v-count" placeholder="Nr. Acarieni" style="width:50%; padding:10px; border-radius:8px; border:1px solid #ddd;">
                  <input type="number" id="v-days" placeholder="Zile curățare" style="width:50%; padding:10px; border-radius:8px; border:1px solid #ddd;">
              </div>
              <button onclick="calcVarroa()" class="btn-resolve" style="width:100%; margin-top:10px; background:#e74c3c;">Calculează Rata</button>
          </div>

          <div class="varroa-box" style="border-color: #3498db; margin-top:20px;">
              <h4 style="margin: 0 0 10px 0; color: #3498db;">🔄 Transferă Resurse</h4>
              <div style="display:flex; flex-direction:column; gap:10px;">
                  <select id="transfer-target" style="padding:10px; border-radius:8px; border:1px solid #ddd;"></select>
                  <select id="transfer-item" style="padding:10px; border-radius:8px; border:1px solid #ddd;">
                      <option value="1 Ramă Puiet Căpăcit">1 Ramă Puiet Căpăcit</option>
                      <option value="1 Ramă Miere/Hrană">1 Ramă Miere/Hrană</option>
                      <option value="Albine Acoperitoare">Albine Acoperitoare</option>
                  </select>
              </div>
              <button onclick="executeTransfer()" class="btn-resolve" style="width:100%; margin-top:10px; background:#3498db;">Execută Transferul</button>
          </div>
      </div>

      <div id="m-view-logs" style="display:none;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
              <h4 style="margin:0;">📜 Istoric Jurnal Stup</h4>
              <button onclick="generatePasaportStup()" class="btn-resolve" style="background:#e67e22; font-size:0.8rem; padding:7px 12px;">📄 Export Word</button>
          </div>
          <div id="modal-jurnal-list" class="modal-mini-logs"></div>
      </div>

      <div id="m-view-meta" style="display:none; text-align:center">
          <div class="input-grid">
              <input type="text" id="m-nick" style="padding:10px; border-radius:8px; border:1px solid #ddd;" placeholder="Poreclă stup">
              <input type="text" id="m-parent" style="padding:10px; border-radius:8px; border:1px solid #ddd;" placeholder="Stup Părinte">
          </div>
          <div class="input-grid" style="margin-top:10px">
              <input type="text" id="m-qBreed" placeholder="Rasă Regină (ex: Buckfast)" style="padding:10px; border-radius:8px; border:1px solid #ddd;">
              <select id="m-qScore" style="padding:10px; border-radius:8px; border:1px solid #ddd;">
                  <option value="5">⭐⭐⭐⭐⭐ (Excelent)</option>
                  <option value="4">⭐⭐⭐⭐ (Bun)</option>
                  <option value="3">⭐⭐⭐ (Mediu)</option>
                  <option value="2">⭐⭐ (Slab)</option>
                  <option value="1">⭐ (De schimbat)</option>
              </select>
          </div>
          <p>Culoare Matcă:</p>
          <div style="display:flex; justify-content:center; gap:15px; margin:20px 0; flex-wrap:wrap;">
              <div onclick="currQCol='white';saveMeta()" style="width:35px;height:35px;background:#fff;border:1px solid #ccc;cursor:pointer;border-radius:5px;"></div>
              <div onclick="currQCol='#f1c40f';saveMeta()" style="width:35px;height:35px;background:#f1c40f;cursor:pointer;border-radius:5px;"></div>
              <div onclick="currQCol='#e74c3c';saveMeta()" style="width:35px;height:35px;background:#e74c3c;cursor:pointer;border-radius:5px;"></div>
              <div onclick="currQCol='#2ecc71';saveMeta()" style="width:35px;height:35px;background:#2ecc71;cursor:pointer;border-radius:5px;"></div>
              <div onclick="currQCol='#3498db';saveMeta()" style="width:35px;height:35px;background:#3498db;cursor:pointer;border-radius:5px;"></div>
              <div onclick="currQCol='transparent';saveMeta()" style="width:35px;height:35px;background:transparent;border:2px dashed #95a5a6;color:#95a5a6;cursor:pointer;border-radius:5px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:1.2rem;" title="Resetează culoarea (Fără culoare)">✕</div>
          </div>
          <button onclick="saveMeta()" class="btn-resolve" style="width:100%; max-width:300px;">Salvează Setări</button>
          
          <hr style="border:0; border-top:1px solid #eee; margin:20px 0">
          <button onclick="generateQR()" class="btn-resolve" style="background:#3498db; padding:8px 15px">📱 Generează Cod QR Stup</button>
          
          <div id="qr-container" style="margin-top:15px"></div>
      </div>

      <div id="m-view-photo" style="display:none;">
          <div style="background:var(--cream,#fdfbf7);border:2px dashed var(--wood-light);border-radius:12px;padding:16px;margin-bottom:14px;text-align:center;">
              <input type="file" id="modal-photo-input" accept="image/*" style="display:none;" onchange="previewModalPhoto(this)">
              <div id="modal-photo-preview" style="margin-bottom:10px;display:none;"><img id="modal-photo-img" style="max-width:100%;max-height:160px;border-radius:8px;"></div>
              <input type="text" id="modal-photo-desc" placeholder="Descriere optionala..." style="display:none;width:100%;margin-top:8px;padding:8px 10px;border:1.5px solid var(--wood-light);border-radius:8px;font-family:inherit;font-size:0.85rem;box-sizing:border-box;">
              <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:8px;">
                  <button onclick="document.getElementById('modal-photo-input').click()" style="padding:8px 16px;background:var(--wood-light);border:none;border-radius:8px;font-family:inherit;font-weight:800;cursor:pointer;color:var(--premium-brown);font-size:0.85rem;">Alege Poza</button>
                  <button onclick="saveModalPhoto()" id="modal-photo-save-btn" style="display:none;padding:8px 16px;background:var(--honey);border:none;border-radius:8px;font-family:inherit;font-weight:800;cursor:pointer;color:#fff;font-size:0.85rem;">Salveaza</button>
              </div>
          </div>
          <div id="hive-photo-gallery"></div>
      </div>
      
      <div id="m-view-queenhist" style="display:none;">
          <!-- Form adaugare eveniment regina -->
          <div style="background:var(--cream,#fdfbf7);border:1.5px solid var(--wood-light);border-radius:12px;padding:14px;margin-bottom:14px;">
              <div style="font-size:0.8rem;font-weight:800;color:var(--premium-brown);margin-bottom:10px;">👑 Adaugă Eveniment Regină</div>
              <select id="qh-event" style="width:100%;padding:8px 10px;border:1.5px solid var(--wood-light);border-radius:8px;font-family:inherit;margin-bottom:8px;">
                  <option value="Inregistrare">📋 Înregistrare initială</option>
                  <option value="Schimbare">🔄 Schimbare de matcă</option>
                  <option value="Roire">🐝 Roire naturală</option>
                  <option value="Pierdere">⚠️ Pierdere matcă</option>
                  <option value="Botci">🏠 Botci detectate</option>
                  <option value="Artificiu">🧪 Împereche artificială</option>
              </select>
              <div style="display:flex;gap:8px;margin-bottom:8px;">
                  <input type="text" id="qh-breed" placeholder="Rasă (ex: Buckfast)" style="flex:1;padding:8px 10px;border:1.5px solid var(--wood-light);border-radius:8px;font-family:inherit;font-size:0.85rem;">
                  <input type="text" id="qh-year" placeholder="An" style="width:70px;padding:8px 10px;border:1.5px solid var(--wood-light);border-radius:8px;font-family:inherit;font-size:0.85rem;">
              </div>
              <textarea id="qh-notes" rows="2" placeholder="Observații..." style="width:100%;padding:8px 10px;border:1.5px solid var(--wood-light);border-radius:8px;font-family:inherit;font-size:0.85rem;box-sizing:border-box;resize:none;"></textarea>
              <button onclick="saveQueenEvent()" style="width:100%;margin-top:8px;padding:9px;background:var(--honey);border:none;border-radius:8px;font-family:inherit;font-weight:800;cursor:pointer;color:#fff;font-size:0.85rem;">💾 Salvează Eveniment</button>
          </div>
          <!-- Lista evenimente -->
          <div id="queen-history-list"></div>
      </div>

      <div id="m-view-brood" style="display:none;">
          <div id="brood-calendar-container"></div>
      </div>

      <div id="m-view-harvest" style="display:none; text-align:center">
          <h4 style="margin: 0 0 10px 0; color: var(--wood-dark);">🍯 Recoltă Individuală Stup</h4>
          <div style="height: 220px; position: relative;"><canvas id="harvestPieChartModal"></canvas></div>
          <div id="modal-harvest-list" style="margin-top: 15px; text-align: left;"></div>
      </div>
    </div>
</div>

<div id="resolvedModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10000; align-items:center; justify-content:center; backdrop-filter: blur(3px);">
    <div style="background:#fff; width:90%; max-width:500px; border-radius:12px; padding:25px; position:relative; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-height: 85vh; overflow-y: auto;">
        <span onclick="document.getElementById('resolvedModal').style.display='none'" style="position:absolute; right:20px; top:20px; font-size:1.5rem; cursor:pointer; color:#e74c3c; font-weight:bold; line-height:1;" title="Închide">✕</span>
        <h3 style="margin-top:0; color:#2c3e50; border-bottom:2px solid #eee; padding-bottom:10px;">🗂️ Istoric Alerte Rezolvate</h3>
        <div id="resolved-list" style="margin-top:15px;">
            </div>
    </div>
</div>