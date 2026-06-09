<section id="view-compare" class="view-section">
    <div class="page-container" style="max-width:900px;margin:0 auto;">
        <h2 data-i18n="compare_title">📊 Comparație Evoluție Stupi</h2>

        <div class="card-box" style="margin-bottom:16px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div>
                    <label style="font-size:0.78rem;font-weight:800;color:var(--premium-brown);display:block;margin-bottom:5px;" data-i18n="compare_hive1">🐝 Stup 1</label>
                    <select id="comp-sel-1" style="width:100%;padding:10px;border-radius:8px;border:1.5px solid var(--wood-light);font-family:inherit;background:var(--white);color:var(--text-dark);"></select>
                </div>
                <div>
                    <label style="font-size:0.78rem;font-weight:800;color:var(--premium-brown);display:block;margin-bottom:5px;" data-i18n="compare_hive2">🐝 Stup 2</label>
                    <select id="comp-sel-2" style="width:100%;padding:10px;border-radius:8px;border:1.5px solid var(--wood-light);font-family:inherit;background:var(--white);color:var(--text-dark);"></select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px;">
                <div>
                    <label style="font-size:0.78rem;font-weight:800;color:var(--premium-brown);display:block;margin-bottom:5px;" data-i18n="compare_data_type">📈 Tip Date</label>
                    <select id="comp-chart-type" style="width:100%;padding:9px;border-radius:8px;border:1.5px solid var(--wood-light);font-family:inherit;background:var(--white);color:var(--text-dark);">
                        <option value="weight" data-i18n="data_weight">⚖️ Greutate</option>
                        <option value="temperature" data-i18n="data_temp">🌡️ Temperatură</option>
                        <option value="battery" data-i18n="data_battery">🔋 Baterie</option>
                        <option value="delta" data-i18n="data_delta">📉 Variație zilnică</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.78rem;font-weight:800;color:var(--premium-brown);display:block;margin-bottom:5px;" data-i18n="compare_period">🗓️ Perioadă</label>
                    <select id="comp-range" style="width:100%;padding:9px;border-radius:8px;border:1.5px solid var(--wood-light);font-family:inherit;background:var(--white);color:var(--text-dark);">
                        <option value="1" data-i18n="period_24h">Ultimele 24h</option>
                        <option value="3" data-i18n="period_3d">Ultimele 3 zile</option>
                        <option value="7" selected data-i18n="period_7d">Ultimele 7 zile</option>
                        <option value="14" data-i18n="period_14d">Ultimele 14 zile</option>
                        <option value="30" data-i18n="period_30d">Ultimele 30 zile</option>
                        <option value="90" data-i18n="period_90d">Ultimele 90 zile</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.78rem;font-weight:800;color:var(--premium-brown);display:block;margin-bottom:5px;" data-i18n="compare_chart_type">🎨 Tip Grafic</label>
                    <select id="comp-viz-type" style="width:100%;padding:9px;border-radius:8px;border:1.5px solid var(--wood-light);font-family:inherit;background:var(--white);color:var(--text-dark);">
                        <option value="line" data-i18n="chart_line">📈 Linie</option>
                        <option value="area" data-i18n="chart_area">🏔️ Zonă</option>
                        <option value="bar" data-i18n="chart_bar">📊 Bare</option>
                    </select>
                </div>
            </div>

            <button onclick="compareHives()" class="btn-resolve" style="width:100%;font-size:1rem;padding:13px;" data-i18n="compare_btn">
                🔍 Compară Stupii
            </button>

            <div id="compare-info"></div>

            <div style="margin-top:16px;height:360px;position:relative;">
                <canvas id="compareChart"></canvas>
            </div>

            <div id="compare-stats"></div>
        </div>

        <div id="year-comparison"></div>
    </div>
</section>