<section id="view-help" class="view-section">
  <div class="page-container" style="max-width:860px;margin:0 auto;">

    <!-- ALERTE ACTIVE -->
    <h2>🚨 Alerte Active</h2>
    <div id="active-alerts-list"></div>
    <button onclick="openResolvedAlerts()" style="width:100%;margin-top:16px;margin-bottom:32px;padding:14px;border-radius:14px;border:2px dashed #ccc;background:transparent;font-weight:800;cursor:pointer;color:var(--wood-shadow)">📜 Istoric Rezolvări</button>

    <h2 style="margin-bottom:16px;">📚 Ghid MATCA — Management Apicol</h2>

    <!-- Download PDF -->
    <div class="card-box" style="margin-bottom:20px;border-top-color:var(--honey);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div>
        <div style="font-weight:900;font-size:1rem;color:var(--premium-brown);">📄 Ghid Complet PDF</div>
        <div style="font-size:0.82rem;color:var(--text-muted);margin-top:3px;">8 sectiuni · Instructiuni pas cu pas · Alerte & Mentenanta · FAQ</div>
      </div>
      <a href="assets/MATCA_Ghid_Utilizare.pdf" download="MATCA_Ghid_Utilizare.pdf"
         style="padding:10px 20px;background:var(--honey,#d4860b);border-radius:10px;color:#fff;font-weight:800;text-decoration:none;font-size:0.88rem;white-space:nowrap;">
        ⬇️ Descarcă PDF
      </a>
    </div>

    <!-- PRIMII PAȘI -->
    <div class="card-box" style="margin-bottom:16px;border-top-color:var(--accent-green);">
      <h3 style="margin-top:0;color:var(--accent-green);">🚀 Primii Pași</h3>
      <div style="display:grid;gap:8px;font-size:0.86rem;line-height:1.7;">
        <div style="background:#f0fdf4;border-radius:8px;padding:10px 14px;">
          <b>1. Adaugă stupi</b> — Din Dashboard, butonul <b>➕ Stup Manual</b> (jos-dreapta) creează un stup pe care îl poți actualiza manual. Stupii cu senzori IoT apar automat când controllerul trimite date.
        </div>
        <div style="background:#f0fdf4;border-radius:8px;padding:10px 14px;">
          <b>2. Personalizează</b> — Click pe orice stup → tab <b>👑 Management & Regină</b> pentru a seta: nume, culoare matcă, nr. magazii, mod mentenanță.
        </div>
        <div style="background:#f0fdf4;border-radius:8px;padding:10px 14px;">
          <b>3. Înregistrează inspecții</b> — Tab <b>📋 Inspecție</b> din modalul stupului pentru inspecție rapidă cu câmpuri predefinite (matcă, puiet, botci, rame).
        </div>
        <div style="background:#f0fdf4;border-radius:8px;padding:10px 14px;">
          <b>4. Urmărește activitatea</b> — Secțiunea <b>📔 Jurnal</b> centralizează toate notițele, pozele și sarcinile. Calendarul vizual marchează zilele active cu puncte colorate.
        </div>
      </div>
    </div>

    <!-- DASHBOARD -->
    <div class="card-box" style="margin-bottom:16px;">
      <h3 style="margin-top:0;color:var(--premium-brown);">🏠 Dashboard — Cardul de Stup</h3>
      <div style="display:grid;gap:8px;font-size:0.85rem;">
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;">
          <b style="color:var(--premium-brown);">🏥 Badge sănătate (colț dreapta-sus)</b><br>
          Scor calculat din 100%: <span style="color:#ee5253;font-weight:700;">-20%</span> baterie sub 3.4V · <span style="color:#ee5253;font-weight:700;">-30%</span> scădere &gt;0.15kg<br>
          <span style="color:#10ac84;font-weight:700;">Verde 76-100%</span> · <span style="color:#f39c12;font-weight:700;">Galben 41-75%</span> · <span style="color:#ee5253;font-weight:700;">Roșu 0-40%</span>
        </div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;">
          <b style="color:var(--premium-brown);">💎 Diamantul colorat (colț stânga-sus)</b> — Culoarea mătcii setată manual. Standard apicol: ⚪ Alb (an terminat în 1/6), 🟡 Galben (2/7), 🔴 Roșu (3/8), 🟢 Verde (4/9), 🔵 Albastru (5/0).
        </div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;">
          <b style="color:var(--premium-brown);">⚖️ Greutatea (centru)</b> — Greutatea curentă în kg de la senzor. <b>Delta (+/-)</b> = variația față de citirea anterioară. <b>24h</b> = față de acum 24 ore.
        </div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;">
          <b style="color:var(--premium-brown);">💪 Puterea coloniei (bara)</b> — Bazată pe nr. rame ocupate din ultima inspecție rapidă: <span style="color:#10ac84;font-weight:700;">8+ rame = Puternică</span> · <span style="color:#f39c12;font-weight:700;">5-7 = Medie</span> · <span style="color:#ee5253;font-weight:700;">&lt;5 = Slabă</span>. Dacă nu există inspecție, se estimează din senzori.
        </div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;">
          <b style="color:var(--premium-brown);">● Bulina + timp (jos)</b> — Status senzor: <span style="color:#10ac84;font-weight:700;">● Verde</span> &lt;2h · <span style="color:#f39c12;font-weight:700;">● Portocaliu</span> 2-24h · <span style="color:#ee5253;font-weight:700;">● Roșu</span> &gt;24h offline
        </div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;">
          <b style="color:var(--premium-brown);">🔔 Sortare</b> — Bara de sortare permite ordonarea stupilor după: Nume · Greutate · Sănătate · Actualizat. Click din nou pe același buton inversează ordinea.
        </div>
      </div>
    </div>

    <!-- ALERTE -->
    <div class="card-box" style="margin-bottom:16px;border-top-color:#ee5253;">
      <h3 style="margin-top:0;color:#ee5253;">🚨 Alerte & Notificări</h3>
      <div style="display:grid;gap:8px;font-size:0.85rem;">
        <div style="background:#fff5f5;border-radius:8px;padding:10px 14px;">
          <b>Alertă Roire</b> — Apare când greutatea scade ≥0.15 kg față de citirea anterioară. Click pe banner roșu → confirmi → dispare. Reapare la o nouă citire cu scădere. Nu apare dacă stupul e în Mod Mentenanță.
        </div>
        <div style="background:#fff5f5;border-radius:8px;padding:10px 14px;">
          <b>⚖️ Reset Bază Greutate</b> — Dacă ai scos/adăugat magazii sau echipamente și nu vrei alertă falsă, deschide modalul stupului → <b>Management & Regină</b> → butonul <b>Resetez Baza</b>. Greutatea curentă devine noua referință.
        </div>
        <div style="background:#fff5f5;border-radius:8px;padding:10px 14px;">
          <b>📧 Email automat</b> — La alertă de roire se trimite email automat (maxim 1 la 6 ore). Reminder inspecție: dacă un stup nu a fost inspectat în 14+ zile, primești email (maxim 1 la 7 zile).
        </div>
        <div style="background:#fff5f5;border-radius:8px;padding:10px 14px;">
          <b>🌡️ Alertă temperatură</b> — La temperaturi anormale față de media lunii (senzor la urdiniș). Pragurile variază sezonier: iarnă 5-20°C, vară 18-35°C.
        </div>
      </div>
    </div>

    <!-- MODAL STUP -->
    <div class="card-box" style="margin-bottom:16px;">
      <h3 style="margin-top:0;color:var(--premium-brown);">🔍 Modalul Stupului — Tab-uri</h3>
      <div style="display:grid;gap:8px;font-size:0.85rem;">
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>📈 Date</b> — Grafic greutate + grafic baterie cu zoom și selector perioadă.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>📋 Inspecție Rapidă</b> — Formulare pentru: matcă văzută, puiet/ouă, botci, rame ocupate (cu detalii per tip). Calculează automat puterea coloniei.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>📜 Jurnal Stup</b> — Ultimele notițe din jurnal filtrate pentru stupul curent.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>👑 Management & Regină</b> — Setează: nickname, culoare matcă, rasă, scor matcă, magazii montate, mod mentenanță, reset bază greutate. Salvează cu butonul verde.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>📖 Istoric Regină</b> — Înregistrează evenimentele mătcii: Înregistrare, Schimbare, Roire, Pierdere, Botci, Împereche artificială. Include rasă, an și observații.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>🥚 Calendar Pontă</b> — Introdu data ouatului → aplicația calculează și afișează stadiile: Ouăt (0-3 zile) → Larvă (3-9 zile) → Căpăcit (9-21 zile) → Eclozare (ziua 21). Bară de progres și countdown.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>📸 Foto</b> — Galerie fotografii per stup. Adaugă poze direct din modal (funcționează cu iPhone HEIC, PNG, JPG). Sortate cronologic.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>🍯 Recoltă</b> — Grafic și listă recolte individuale pentru stupul curent.</div>
      </div>
    </div>

    <!-- JURNAL & CALENDAR -->
    <div class="card-box" style="margin-bottom:16px;">
      <h3 style="margin-top:0;color:var(--premium-brown);">📔 Jurnal & Calendar</h3>
      <div style="display:grid;gap:8px;font-size:0.85rem;">
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;">
          <b>Calendar vizual</b> — Puncte colorate per zi: <span style="color:#10ac84;font-weight:700;">● Verde</span> Observații · <span style="color:#3498db;font-weight:700;">● Albastru</span> Sarcini · <span style="color:#f39c12;font-weight:700;">● Portocaliu</span> Recoltă · <span style="color:#8e44ad;font-weight:700;">● Mov</span> Tratamente. Click pe zi → popup cu activitățile grupate pe categorii (accordion).
        </div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;">
          <b>Upload poze</b> — Câmpul de fișier din formular acceptă JPG, PNG, HEIC (iPhone). Poza apare în entry-ul de jurnal și în galeria tab-ului Foto al stupului.
        </div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;">
          <b>Auto-refresh</b> — Dacă alt utilizator adaugă o notițǎ sau poză, jurnalul se actualizează automat în 5 secunde fără refresh manual.
        </div>
      </div>
    </div>

    <!-- TRATAMENTE -->
    <div class="card-box" style="margin-bottom:16px;">
      <h3 style="margin-top:0;color:var(--premium-brown);">💊 Tratamente & Hrănire</h3>
      <p style="font-size:0.85rem;line-height:1.7;margin:0 0 10px;">
        Din <b>Jurnal → Sarcini & Tratamente</b>: selectezi stupul, produsul și data de start. Sistemul generează automat sarcinile la intervalele corecte.<br>
        Produse disponibile: Varachet, Taktic, Acid Oxalic, ApiBioxal, Apiguard, Bayvarol, Varromed și hrăniri (sirop stimulent, turtă energetică/proteică, Protofil).
      </p>
      <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;font-size:0.85rem;">
        <b>🔔 Email Reminder</b> — Bifează opțiunea înainte de programare pentru a primi email cu 2 zile înainte de fiecare doză.
      </div>
    </div>

    <!-- VARROA -->
    <div class="card-box" style="margin-bottom:16px;">
      <h3 style="margin-top:0;color:var(--premium-brown);">🦠 Calculator Varroa</h3>
      <p style="font-size:0.85rem;line-height:1.7;margin:0;">
        Tab-ul <b>Inspecție</b> din modalul stupului. Introduci acarieni căzuți + zile monitorizare.<br>
        <span style="color:#10ac84;font-weight:700;">Sub 1 acarian/zi</span> = infestare sub control · <span style="color:#ee5253;font-weight:700;">Peste 1/zi</span> = tratament necesar.
      </p>
    </div>

    <!-- RECOLTA & ROI -->
    <div class="card-box" style="margin-bottom:16px;">
      <h3 style="margin-top:0;color:var(--premium-brown);">🍯 Recoltă & ROI</h3>
      <div style="display:grid;gap:8px;font-size:0.85rem;">
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>Înregistrare recoltă</b> — Data, stup, tip miere, kg, preț/kg. Calculează automat venitul.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>Tabel ROI</b> — Venit, cheltuieli și profit per stup. Filtru pe an.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>🏆 Indice Productivitate</b> — Clasament stupi după kg recoltați raportat la greutatea stupului.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>💰 Cost per kg</b> — Cheltuieli împărțite la kg recoltați per stup. Verde &lt;5 RON/kg · Portocaliu 5-15 · Roșu &gt;15.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>🔮 Predicție Recoltă</b> — Estimare 7 și 14 zile bazată pe stupi cu deltaDay pozitiv susținut (cules activ).</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>📅 Comparație An vs An</b> — Grafic recoltă lunară suprapusă pe toți anii din istoric.</div>
      </div>
    </div>

    <!-- HARTĂ -->
    <div class="card-box" style="margin-bottom:16px;">
      <h3 style="margin-top:0;color:var(--premium-brown);">🗺️ Harta Stupinei</h3>
      <p style="font-size:0.85rem;line-height:1.7;margin:0;">
        Stupii apar ca iconițe SVG pe hartă reală (OpenStreetMap). <b>Drag & drop</b> pentru repoziționare — pozițiile se salvează automat.<br>
        <b>Culori iconițe:</b> <span style="color:#d4860b;font-weight:700;">Auriu</span> normal · <span style="color:#ee5253;font-weight:700;">Roșu</span> alertă roire · <span style="color:#f39c12;font-weight:700;">Portocaliu</span> baterie slabă/mentenanță.<br>
        <b>QR Code</b> — Din modalul stupului → butonul QR → scanezi codul din câmp și se deschide direct fișa stupului.
      </p>
    </div>

    <!-- PREDICȚII SEZONIERE -->
    <div class="card-box" style="margin-bottom:16px;">
      <h3 style="margin-top:0;color:var(--premium-brown);">🌸 Predicții & Remindere Sezoniere</h3>
      <div style="display:grid;gap:8px;font-size:0.85rem;">
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>Calendar fenologic</b> — Afișat în dashboard când e cules activ (deltaDay &gt;0.3 kg). Arată culesurile următoare 14 zile: Salcâm, Rapiță, Tei, Floarea-soarelui etc.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>Remindere sezoniere</b> — Sfaturi lunare automate (ex: pregătire iernare în octombrie, stimulare în martie). Dismiss cu ✕.</div>
      </div>
    </div>

    <!-- ADMIN -->
    <div class="card-box" style="margin-bottom:16px;">
      <h3 style="margin-top:0;color:var(--premium-brown);">⚙️ Administrare (doar Admin)</h3>
      <div style="display:grid;gap:8px;font-size:0.85rem;">
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>Utilizatori</b> — Creare conturi, alocare stupi sau controllere, setare permisiuni (Standard/Admin), acces manual, reset parolă.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>Controllere ESP</b> — Grupare senzori pe controllere, alocare la utilizatori. Un controller = acces la toți stupii lui.</div>
        <div style="background:#fdf8ef;border-radius:8px;padding:10px 14px;"><b>Export Date</b> — CSV pentru: Jurnal, Recoltă, Cheltuieli, Inventar. Raport Word personalizat cu filtru dată și tip activitate.</div>
      </div>
    </div>

    <!-- HEALTH SCORE -->
    <div class="card-box" style="margin-bottom:16px;border:2px solid var(--accent-green);">
      <h3 style="margin-top:0;color:var(--accent-green);">🏥 Health Score — Detalii</h3>
      <table style="width:100%;font-size:0.85rem;border-collapse:collapse;margin-top:8px;">
        <tr style="background:rgba(0,0,0,0.04);"><th style="text-align:left;padding:8px;border-radius:6px 0 0 6px;">Eveniment</th><th style="text-align:center;padding:8px;border-radius:0 6px 6px 0;">Penalizare</th></tr>
        <tr style="border-bottom:1px solid #eee"><td style="padding:8px;">🔋 Baterie sub 3.4V</td><td style="text-align:center;color:#ee5253;font-weight:800;">-20%</td></tr>
        <tr><td style="padding:8px;">⚖️ Scădere greutate &gt;0.15kg</td><td style="text-align:center;color:#ee5253;font-weight:800;">-30%</td></tr>
      </table>
      <div style="margin-top:14px;display:flex;gap:14px;align-items:center;font-size:0.8rem;flex-wrap:wrap;">
        <span><span style="color:#10ac84;font-size:1.2rem;">●</span> 76-100% Optim</span>
        <span><span style="color:#f1c40f;font-size:1.2rem;">●</span> 41-75% Atenție</span>
        <span><span style="color:#ee5253;font-size:1.2rem;">●</span> 0-40% Critic</span>
      </div>
    </div>

    <!-- NOTIFICĂRI PUSH -->
    <div class="card-box">
      <h3 style="margin-top:0;">🔔 Notificări Push</h3>
      <p style="font-size:0.85rem;margin:0 0 12px;">Activează notificările web push pentru a primi alerte direct pe telefon când apar evenimente critice.</p>
      <button onclick="enableNotifications()" class="btn-resolve" style="width:100%;background:#8e44ad;">Activează Notificările Web Push</button>
    </div>

  </div>
</section>