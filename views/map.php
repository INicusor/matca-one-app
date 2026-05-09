<section id="view-map" class="view-section">
    <div class="page-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <h2 style="margin: 0;">🗺️ Așezare Stupi și Topografie</h2>
            <button onclick="resetMapPositions()" class="btn-resolve" style="background:#e67e22; padding: 8px 15px; font-size: 0.85rem;">🔄 Resetează Pozițiile (Aduce la centru)</button>
        </div>

        <div class="card-box" style="padding: 15px; margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; background: rgba(255,255,255,0.8);">
            <span style="font-weight: 800; color: var(--wood-dark); margin-right: 10px; align-self: center;">Adaugă obiecte pe hartă:</span>
            <button onclick="addMapMarker('Salcâm', '🌳')" class="nav-btn" style="border:1px solid #ddd; padding:8px 15px; font-size:0.9rem; background: #fff;">🌳 Salcâm</button>
            <button onclick="addMapMarker('Rapiță', '🌼')" class="nav-btn" style="border:1px solid #ddd; padding:8px 15px; font-size:0.9rem; background: #fff;">🌼 Rapiță</button>
            <button onclick="addMapMarker('Tei', '🌿')" class="nav-btn" style="border:1px solid #ddd; padding:8px 15px; font-size:0.9rem; background: #fff;">🌿 Tei</button>
            <button onclick="addMapMarker('Apă', '💧')" class="nav-btn" style="border:1px solid #ddd; padding:8px 15px; font-size:0.9rem; background: #fff;">💧 Sursă Apă</button>
            <button onclick="addMapMarker('Bază', '🏕️')" class="nav-btn" style="border:1px solid #ddd; padding:8px 15px; font-size:0.9rem; background: #fff;">🏕️ Bază</button>
        </div>

        <div id="hive-map"></div>
        <p style="text-align:center; opacity:0.6; margin-top:15px">Trage stupii și marcajele în poziția dorită pe grilă. <br><b>Dublu-click</b> pe un element topografic (copac, apă) pentru a-l șterge.</p>
    </div>
</section>