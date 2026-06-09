<?php
/**
 * MATCA — One-time patch script for app.js
 * Upload to public_html/MPDashboard/ and run once via browser
 * THEN DELETE THIS FILE IMMEDIATELY!
 *
 * Applies 8 custom emoji image patches to app.js
 * Part of MiereaPofta v2 visual upgrade
 */

$appJsPath = __DIR__ . '/app.js';

if (!file_exists($appJsPath)) {
    die('ERROR: app.js not found at ' . $appJsPath);
}

$content = file_get_contents($appJsPath);
$originalSize = strlen($content);
$patchCount = 0;
$log = [];

// Helper
function applyPatch(&$content, $old, $new, $name, &$patchCount, &$log) {
    if (strpos($content, $old) !== false) {
        $content = str_replace($old, $new, $content);
        $patchCount++;
        $log[] = "<span style='color:green'>&#x2705; $name</span>";
    } else {
        $log[] = "<span style='color:orange'>&#x26A0;&#xFE0F; $name &mdash; already applied or not found</span>";
    }
}

// ── PATCH 1: Swarm Alert Banner ──
applyPatch($content,
    'roireHTML = `<div class="roire-alert-banner" style="${isCritical?\'\':',
    'const _swarmImg = `<img src="uploads/emoji-hive-swarm-alert.png" class="mp-emoji-inline" alt="alert">`;' . "\n" .
    '            roireHTML = `<div class="roire-alert-banner" style="${isCritical?\'\':',
    'Patch 1: Swarm alert banner image', $patchCount, $log
);

// ── PATCH 2: Weather wind/humidity icons ──
applyPatch($content,
    '<div style="font-size:0.73rem;color:var(--text-muted);margin-top:2px">' . "\xf0\x9f\x92\xa8" . ' ${Math.round(wind)} km/h &nbsp;' . "\xf0\x9f\x92\xa7" . ' ${humidity}%</div>',
    '<div style="font-size:0.73rem;color:var(--text-muted);margin-top:2px;display:flex;align-items:center;gap:4px;flex-wrap:wrap;"><img src="uploads/emoji-weather-wind-strong.png" class="mp-emoji-weather" alt="wind"> ${Math.round(wind)} km/h &nbsp;<img src="uploads/emoji-weather-humidity.png" class="mp-emoji-weather" alt="humidity"> ${humidity}%</div>',
    'Patch 2: Weather wind/humidity icons', $patchCount, $log
);

// ── PATCH 3: Wind alert reason ──
applyPatch($content,
    "if (wind > 25)              reasons.push(ro ? `\xf0\x9f\x92\xa8 V\xc3\xa2nt puternic",
    'if (wind > 25)              reasons.push(ro ? `<img src="uploads/emoji-weather-wind-strong.png" class="mp-emoji-reason" alt="wind"> V\xc3\xa2nt puternic',
    'Patch 3: Wind reason icon', $patchCount, $log
);

// ── PATCH 4: Humidity alert reason ──
applyPatch($content,
    "reasons.push(ro ? `\xf0\x9f\x92\xa7 Umiditate ridicat\xc3\xa3",
    'reasons.push(ro ? `<img src="uploads/emoji-weather-humidity.png" class="mp-emoji-reason" alt="humidity"> Umiditate ridicat\xc3\xa3',
    'Patch 4: Humidity reason icon', $patchCount, $log
);

// ── PATCH 5: Inventory category icons ──
$old5pos = strpos($content, "html += `<div class=\"inventory-section open\"><h4 onclick=\"this.parentElement.classList.toggle('open')\"><span>\xf0\x9f\x93\xa6 ");
if ($old5pos !== false) {
    $old5 = "html += `<div class=\"inventory-section open\"><h4 onclick=\"this.parentElement.classList.toggle('open')\"><span>\xf0\x9f\x93\xa6 \${title}</span><span>&#x25BC;</span></h4><div class=\"inventory-list-content\">\${items}</div></div>`;"; 
    // Use a simpler needle
    $needle5 = "\xf0\x9f\x93\xa6 \${title}</span><span>\\u25BC</span>";
    if (strpos($content, "\xf0\x9f\x93\xa6 \${title}") !== false) {
        $content = preg_replace(
            '/html \+= `<div class="inventory-section open"><h4 onclick="this\.parentElement\.classList\.toggle\(\'open\'\)"><span>' . preg_quote("\xf0\x9f\x93\xa6", '/') . ' \$\{title\}<\/span>/',
            'const _invCatImg={' . "\n" .
            '                    \'Tratamente & Hrana\': \'<img src="uploads/emoji-hive-treatment-done.png" class="mp-emoji-cat" alt="treatment">\',' . "\n" .
            '                    \'Unelte\': \'<img src="uploads/emoji-inventory-scale.png" class="mp-emoji-cat" alt="tools">\',' . "\n" .
            '                    \'Cutii & Rame\': \'<img src="uploads/emoji-inventory-frames-boxes.png" class="mp-emoji-cat" alt="frames">\'' . "\n" .
            '                };' . "\n" .
            '                const _catIcon = _invCatImg[title] || \'<img src="uploads/emoji-inventory-frames-boxes.png" class="mp-emoji-cat" alt="inv">\';' . "\n" .
            'html += `<div class="inventory-section open"><h4 onclick="this.parentElement.classList.toggle(\'open\')"><span>${_catIcon} ${title}</span>',
            $content
        );
        $patchCount++;
        $log[] = "<span style='color:green'>&#x2705; Patch 5: Inventory category icons</span>";
    } else {
        $log[] = "<span style='color:orange'>&#x26A0;&#xFE0F; Patch 5 &mdash; already applied or pattern changed</span>";
    }
} else {
    $log[] = "<span style='color:orange'>&#x26A0;&#xFE0F; Patch 5 &mdash; already applied</span>";
}

// ── PATCH 6: Harvest list item icons ──
$old6needle = 'return `<div class="card-box" style="padding:14px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;"><div><b>${h.stup}</b>';
if (strpos($content, $old6needle) !== false) {
    $content = str_replace(
        $old6needle,
        'const _harvestTypeIcon = (tip) => {' . "\n" .
        '                if (!tip) return \'\';' . "\n" .
        '                const tl = tip.toLowerCase();' . "\n" .
        '                if (tl.includes(\'padure\') || tl.includes(\'p\xc4\x83dure\') || tl.includes(\'forest\') || tl.includes(\'fag\') || tl.includes(\'tei\') || tl.includes(\'linden\')) return \'<img src="uploads/emoji-harvest-forest-flow.png" class="mp-emoji-harvest" alt="forest">\';' . "\n" .
        '                return \'<img src="uploads/emoji-harvest-spring-flow.png" class="mp-emoji-harvest" alt="spring">\';' . "\n" .
        '            };' . "\n" .
        '            return `<div class="card-box" style="padding:14px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;"><div style="display:flex;align-items:center;gap:10px;">${_harvestTypeIcon(h.tip)}<div><b>${h.stup}</b>',
        $content
    );
    $patchCount++;
    $log[] = "<span style='color:green'>&#x2705; Patch 6: Harvest list item icons</span>";
} else {
    $log[] = "<span style='color:orange'>&#x26A0;&#xFE0F; Patch 6 &mdash; already applied</span>";
}

// ── PATCH 7: Hive status strip ──
$old7needle = '${(() => { const d=getLastInspectionDays(item.meta?.nickname||(\'Stup \'+item.chipID)); return d!==null&&d>14?`<div style="font-size:0.62rem;font-weight:700;color:#f39c12;margin-top:2px;">\xf0\x9f\x93\x8b ${d}z f\xc4\x83r\xc4\x83 inspec\xc8\x9bie</div>`:\'\'; })()} ';
if (strpos($content, $old7needle) === false) {
    // try without trailing space
    $old7needle = '${(() => { const d=getLastInspectionDays(item.meta?.nickname||(\'Stup \'+item.chipID)); return d!==null&&d>14?`<div style="font-size:0.62rem;font-weight:700;color:#f39c12;margin-top:2px;">\xf0\x9f\x93\x8b ${d}z f\xc4\x83r\xc4\x83 inspec\xc8\x9bie</div>`:\'\'; })()';
}
if (strpos($content, $old7needle) !== false) {
    $statusStripCode = "\n" .
        '                ${(() => {' . "\n" .
        '                    let icons = [];' . "\n" .
        '                    if (h >= 80 && (item.delta24||0) >= 0 && !isMaint) icons.push(\'<img src="uploads/emoji-hive-strong.png" class="mp-emoji-status" title="Stup puternic" alt="strong">\');' . "\n" .
        '                    if (isMaint) icons.push(\'<img src="uploads/emoji-hive-treatment-done.png" class="mp-emoji-status" title="Mod \xc3\xaentre\xc8\x9binere" alt="maintenance">\');' . "\n" .
        '                    const _broodDate = localStorage.getItem(\'brood_\' + item.chipID);' . "\n" .
        '                    if (_broodDate) { const _bd = new Date(_broodDate); const _now = new Date(); const _diff = (_now-_bd)/(1000*60*60*24); if(_diff < 21) icons.push(\'<img src="uploads/emoji-hive-eggs-brood.png" class="mp-emoji-status" title="Puiet activ" alt="brood">\'); }' . "\n" .
        '                    if ((item.honeyEstimate||0) >= 5) icons.push(\'<img src="uploads/emoji-inventory-profit.png" class="mp-emoji-status" title="Recolt\xc4\x83 abundent\xc4\x83" alt="profit">\');' . "\n" .
        '                    return icons.length ? `<div class="mp-status-strip">${icons.join(\'\')}</div>` : \'\';' . "\n" .
        '                })()';
    $content = str_replace($old7needle, $old7needle . $statusStripCode, $content);
    $patchCount++;
    $log[] = "<span style='color:green'>&#x2705; Patch 7: Hive status strip</span>";
} else {
    $log[] = "<span style='color:orange'>&#x26A0;&#xFE0F; Patch 7 &mdash; already applied or not matched</span>";
}

// ── PATCH 8: Inventory item icons ──
$old8needle = 'return `<div class="card-box" style="padding:14px;margin-bottom:10px;border:1px solid var(--wood-light);box-shadow:none;"><div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;"><div><b>${i.item}</b>';
if (strpos($content, $old8needle) !== false) {
    $content = str_replace(
        $old8needle,
        'const _invItemIcon = (() => {' . "\n" .
        '                        const t = (i.type||\'\'). toLowerCase();' . "\n" .
        '                        const c = (i.category||\'\'\);' . "\n" .
        '                        if (t === \'litri\' || t.includes(\'litri\')) return \'<img src="uploads/emoji-inventory-liquid-stock.png" class="mp-emoji-inv-item" alt="liquid">\';' . "\n" .
        '                        if (c === \'Cutii&Rame\') return \'<img src="uploads/emoji-inventory-frames-boxes.png" class="mp-emoji-inv-item" alt="frames">\';' . "\n" .
        '                        if (c === \'Tratamente&Hrana\') return \'<img src="uploads/emoji-hive-treatment-done.png" class="mp-emoji-inv-item" alt="treatment">\';' . "\n" .
        '                        return \'<img src="uploads/emoji-inventory-scale.png" class="mp-emoji-inv-item" alt="item">\';' . "\n" .
        '                    })();' . "\n" .
        '                    return `<div class="card-box mp-inv-item-card" style="padding:14px;margin-bottom:10px;border:1px solid var(--wood-light);box-shadow:none;"><div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;"><div style="display:flex;align-items:center;gap:8px;">${_invItemIcon}<div><b>${i.item}</b>',
        $content
    );
    $patchCount++;
    $log[] = "<span style='color:green'>&#x2705; Patch 8: Inventory item icons</span>";
} else {
    $log[] = "<span style='color:orange'>&#x26A0;&#xFE0F; Patch 8 &mdash; already applied</span>";
}

// ── Save ──
$newSize = strlen($content);
if ($patchCount > 0) {
    copy($appJsPath, $appJsPath . '.bak_before_emoji_' . date('Ymd_His'));
    file_put_contents($appJsPath, $content);
}

// Output
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>MATCA Patch</title>';
echo '<style>body{font-family:Nunito,sans-serif;max-width:600px;margin:40px auto;padding:20px;background:#fbf7f0;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.1)}h2{color:#4a3528}hr{border-color:#e8dcc8}</style></head><body>';
echo '<h2>&#x1F41D; MATCA — Emoji Patch Script</h2><hr>';
foreach ($log as $l) echo "<p>$l</p>";
echo "<hr>";
if ($patchCount > 0) {
    echo "<p><strong style='color:green'>Done! Applied $patchCount/8 patches.</strong></p>";
    echo "<p>app.js: {$originalSize} &rarr; {$newSize} bytes</p>";
    echo "<p style='background:#fff3cd;padding:12px;border-radius:8px;'><strong>&#x26A0;&#xFE0F; DELETE this file now!</strong><br>Go to cPanel File Manager and delete <code>apply_patches.php</code></p>";
} else {
    echo '<p><strong>No patches applied &mdash; already up to date or patterns changed.</strong></p>';
}
echo '</body></html>';
