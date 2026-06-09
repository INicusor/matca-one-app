<section id="view-help" class="view-section">
  <div class="page-container" style="max-width:860px;margin:0 auto;">

    <!-- ALERTE ACTIVE -->
    <h2>🚨 Alerte Active</h2>
    <div id="active-alerts-list"></div>
    <button onclick="openResolvedAlerts()" style="width:100%;margin-top:16px;margin-bottom:32px;padding:14px;border-radius:14px;border:2px dashed #ccc;background:transparent;font-weight:800;cursor:pointer;color:var(--wood-shadow)">📜 Istoric Rezolvări</button>

    <!-- NOTIFICĂRI PUSH -->
    <div class="card-box" style="margin-bottom:24px;">
      <h3 style="margin-top:0;">🔔 Notificări Push</h3>
      <p style="font-size:0.85rem;margin:0 0 12px;color:var(--text-muted);">Activează notificările web push pentru a primi alerte direct pe telefon când apar evenimente critice.</p>
      <button onclick="enableNotifications()" class="btn-resolve" style="width:100%;background:#8e44ad;">Activează Notificările Web Push</button>
    </div>

    <!-- GHID PDF -->
    <div class="card-box" style="margin-bottom:24px;border-top-color:var(--honey);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div>
        <div style="font-weight:900;font-size:1rem;color:var(--premium-brown);">📄 Ghid Complet PDF</div>
        <div style="font-size:0.82rem;color:var(--text-muted);margin-top:3px;">Instrucțiuni complete · Alerte & Mentenanță · FAQ</div>
      </div>
      <a href="assets/MATCA_Ghid_Utilizare.pdf" download="MATCA_Ghid_Utilizare.pdf"
         style="padding:10px 20px;background:var(--honey,#d4860b);border-radius:10px;color:#fff;font-weight:800;text-decoration:none;font-size:0.88rem;white-space:nowrap;">
        ⬇️ Descarcă PDF
      </a>
    </div>

    <!-- FORMULAR CONTACT -->
    <h2 style="margin-bottom:16px;">✉️ Contact & Feedback</h2>
    <div class="card-box" style="margin-bottom:32px;border-top-color:var(--accent-blue);">
      <p style="font-size:0.88rem;color:var(--text-muted);margin:0 0 18px;">Ai găsit un bug, ai o sugestie sau vrei să ne contactezi? Completează formularul de mai jos.</p>

      <div style="display:grid;gap:14px;">

        <div>
          <label style="font-size:0.8rem;font-weight:800;color:var(--premium-brown);display:block;margin-bottom:5px;">Categorie <span style="color:#ee5253;">*</span></label>
          <select id="contact-category" style="width:100%;padding:11px 14px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.9rem;background:var(--white);color:var(--text-dark);">
            <option value="">— Selectează categoria —</option>
            <option value="bug">🐛 Bug / Eroare</option>
            <option value="sugestie">💡 Sugestie / Funcționalitate nouă</option>
            <option value="feedback">⭐ Feedback general</option>
            <option value="cont">👤 Problemă cont / Acces</option>
            <option value="senzor">📡 Problemă senzor / Date</option>
            <option value="altele">📌 Altele</option>
          </select>
        </div>

        <div>
          <label style="font-size:0.8rem;font-weight:800;color:var(--premium-brown);display:block;margin-bottom:5px;">Subiect <span style="color:#ee5253;">*</span></label>
          <input type="text" id="contact-subject" placeholder="ex: Bara de putere nu se afișează corect..."
            style="width:100%;padding:11px 14px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.9rem;background:var(--white);color:var(--text-dark);box-sizing:border-box;">
        </div>

        <div>
          <label style="font-size:0.8rem;font-weight:800;color:var(--premium-brown);display:block;margin-bottom:5px;">Mesaj <span style="color:#ee5253;">*</span></label>
          <textarea id="contact-message" rows="5" placeholder="Descrie problema sau sugestia cât mai detaliat..."
            style="width:100%;padding:11px 14px;border:1.5px solid var(--wood-light);border-radius:10px;font-family:inherit;font-size:0.9rem;resize:vertical;background:var(--white);color:var(--text-dark);box-sizing:border-box;"></textarea>
        </div>

        <div style="background:rgba(209,180,144,0.15);border-radius:10px;padding:10px 14px;font-size:0.8rem;color:var(--text-muted);display:flex;align-items:center;gap:8px;">
          📬 Mesajul va fi trimis direct echipei MATCA.
        </div>

        <button onclick="submitContactForm()" class="btn-resolve" style="width:100%;font-size:1rem;padding:14px;">
          📤 Trimite Mesajul
        </button>

        <div id="contact-status" style="display:none;text-align:center;padding:12px;border-radius:10px;font-weight:800;font-size:0.9rem;"></div>
      </div>
    </div>

  </div>
</section>

<script>
// Inițializare formular contact
(function initContactForm() {
  function trySetUsername() {
    const el = document.getElementById('contact-username-display');
    if (!el) return;
    // Încearcă din salut-ul din dropdown
    const salut = document.querySelector('#user-dd > span');
    if (salut) {
      const m = salut.textContent.match(/Salut,\s*(.+)!/);
      if (m) { el.textContent = m[1].trim(); return; }
    }
    // Fallback: din titlul paginii sau din meta
    setTimeout(trySetUsername, 500);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', trySetUsername);
  } else {
    trySetUsername();
  }
})();

function submitContactForm() {
  const category = document.getElementById('contact-category')?.value;
  const subject  = document.getElementById('contact-subject')?.value?.trim();
  const message  = document.getElementById('contact-message')?.value?.trim();
  const statusEl = document.getElementById('contact-status');

  if (!category) { toast('Selectează o categorie!', 'warning'); return; }
  if (!subject)  { toast('Completează subiectul!', 'warning'); return; }
  if (!message || message.length < 10) { toast('Mesajul trebuie să aibă cel puțin 10 caractere!', 'warning'); return; }

  const btn = document.querySelector('button[onclick="submitContactForm()"]');
  if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Se trimite...'; }
  statusEl.style.display = 'none';

  const fd = new FormData();
  fd.append('action',   'send_contact');
  fd.append('category', category);
  fd.append('subject',  subject);
  fd.append('message',  message);

  fetch('backend.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(resp => {
      if (btn) { btn.disabled = false; btn.innerHTML = '📤 Trimite Mesajul'; }
      statusEl.style.display = 'block';
      if (resp === 'ok') {
        statusEl.style.background = 'rgba(16,172,132,0.12)';
        statusEl.style.color = '#10ac84';
        statusEl.style.border = '1px solid rgba(16,172,132,0.3)';
        statusEl.innerHTML = '✅ Mesaj trimis cu succes! Îți mulțumim pentru feedback.';
        document.getElementById('contact-category').value = '';
        document.getElementById('contact-subject').value = '';
        document.getElementById('contact-message').value = '';
        setTimeout(() => { statusEl.style.display = 'none'; }, 6000);
      } else {
        statusEl.style.background = 'rgba(238,82,83,0.1)';
        statusEl.style.color = '#ee5253';
        statusEl.style.border = '1px solid rgba(238,82,83,0.3)';
        statusEl.innerHTML = '❌ Eroare la trimitere. Încearcă din nou sau contactează administratorul.';
      }
    })
    .catch(() => {
      if (btn) { btn.disabled = false; btn.innerHTML = '📤 Trimite Mesajul'; }
      toast('Eroare de rețea!', 'error');
    });
}
</script>