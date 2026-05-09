<section id="view-compare" class="view-section">
    <div class="page-container">
        <h2>📊 Comparație Evoluție Stupi</h2>
        <div class="card-box">
            <div class="input-grid">
                <select id="comp-sel-1" style="padding:10px; border-radius:8px; border:1px solid #ddd;"></select>
                <select id="comp-sel-2" style="padding:10px; border-radius:8px; border:1px solid #ddd;"></select>
            </div>
            <button onclick="compareHives()" class="btn-resolve" style="width:100%">Compară Greutatea (Ultimele 7 Zile)</button>
            <div class="chart-container" style="margin-top:20px; height:350px;"><canvas id="compareChart"></canvas></div>
        </div>
    </div>
</section>