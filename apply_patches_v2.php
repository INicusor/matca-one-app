<?php
/**
 * MATCA — One-time emoji patch for app.js v2
 * Run ONCE: soul2soul.ro/MPDashboard/apply_patches_v2.php
 * DELETE IMMEDIATELY after!
 */
$appJsPath = __DIR__ . '/app.js';
if (!file_exists($appJsPath)) die('ERROR: app.js not found at ' . $appJsPath);
$content = file_get_contents($appJsPath);
$originalSize = strlen($content);
$log = []; $patches = 0;

// P2: Weather icons
$o = "\xf0\x9f\x92\xa8 \${Math.round(wind)} km/h &nbsp;\xf0\x9f\x92\xa7 \${humidity}%</div>";
$n = '<img src="uploads/emoji-weather-wind-strong.png" class="mp-emoji-weather" alt="wind"> ${Math.round(wind)} km/h &nbsp;<img src="uploads/emoji-weather-humidity.png" class="mp-emoji-weather" alt="humidity"> ${humidity}%</div>';
if (str_contains($content,$o)){$content=str_replace($o,$n,$content);$patches++;$log[]='\u2705 Weather icons';}else{$log[]='\u26a0\ufe0f Weather icons — already done';}

// P3: Wind reason
$o = "reasons.push(ro ? `\xf0\x9f\x92\xa8 V\xc3\xa2nt puternic";
$n = 'reasons.push(ro ? `<img src="uploads/emoji-weather-wind-strong.png" class="mp-emoji-reason" alt="wind"> V\u00e2nt puternic';
if (str_contains($content,$o)){$content=str_replace($o,$n,$content);$patches++;$log[]='\u2705 Wind reason';}else{$log[]='\u26a0\ufe0f Wind reason — already done';}

// P4: Humidity reason
$o = "reasons.push(ro ? `\xf0\x9f\x92\xa7 Umiditate ridicat\xc4\x83";
$n = 'reasons.push(ro ? `<img src="uploads/emoji-weather-humidity.png" class="mp-emoji-reason" alt="humidity"> Umiditate ridicat\u0103';
if (str_contains($content,$o)){$content=str_replace($o,$n,$content);$patches++;$log[]='\u2705 Humidity reason';}else{$log[]='\u26a0\ufe0f Humidity reason — already done';}

// P5: Inventory category icons
$o = "<span>\xf0\x9f\x93\xa6 \${title}</span><span>\xe2\x96\xbc</span></h4>";
$n = '<span>${({"Tratamente & Hrana":"<img src=\'uploads/emoji-hive-treatment-done.png\' class=\'mp-emoji-cat\'>","Unelte":"<img src=\'uploads/emoji-inventory-scale.png\' class=\'mp-emoji-cat\'>","Cutii & Rame":"<img src=\'uploads/emoji-inventory-frames-boxes.png\' class=\'mp-emoji-cat\'>" }[title]||"\xf0\x9f\x93\xa6")} ${title}</span><span>\xe2\x96\xbc</span></h4>';
if (str_contains($content,$o)){$content=str_replace($o,$n,$content);$patches++;$log[]='\u2705 Inventory category icons';}else{$log[]='\u26a0\ufe0f Inv cats — already done';}

// P6: Harvest type icons
$o = '<div><b>${h.stup}</b>: ${h.kg}kg ${h.tip} <span style="color:var(--accent-green);font-weight:800">(${ron.toFixed(0)} RON)</span><br><small style="opacity:0.6">${h.date}</small></div><button onclick="deleteHarvest';
$n = '<div style="display:flex;align-items:center;gap:10px;">${(()=>{const tl=(h.tip||"").toLowerCase();return tl.includes("padure")||tl.includes("tei")||tl.includes("forest")?"<img src=\'uploads/emoji-harvest-forest-flow.png\' class=\'mp-emoji-harvest\'>": "<img src=\'uploads/emoji-harvest-spring-flow.png\' class=\'mp-emoji-harvest\'>";})()}<div><b>${h.stup}</b>: ${h.kg}kg ${h.tip} <span style="color:var(--accent-green);font-weight:800">(${ron.toFixed(0)} RON)</span><br><small style="opacity:0.6">${h.date}</small></div></div><button onclick="deleteHarvest';
if (str_contains($content,$o)){$content=str_replace($o,$n,$content);$patches++;$log[]='\u2705 Harvest type icons';}else{$log[]='\u26a0\ufe0f Harvest icons — already done';}

// P7: Hive status strip
$o = "return d!==null&&d>14?`<div style=\"font-size:0.62rem;font-weight:700;color:#f39c12;margin-top:2px;\">\xf0\x9f\x93\x8b \${d}z f\xc4\x83r\xc4\x83 inspec\xc8\x9bie</div>`:''; })()}";
$strip = '\n                ${(()=>{let ic=[];'
    . 'if(h>=80&&(item.delta24||0)>=0&&!isMaint)ic.push("<img src=\'uploads/emoji-hive-strong.png\' class=\'mp-emoji-status\' title=\'Stup puternic\'>");'
    . 'if(isMaint)ic.push("<img src=\'uploads/emoji-hive-treatment-done.png\' class=\'mp-emoji-status\' title=\'Mod \u00centre\u0163inere\'>");'
    . 'const bd=localStorage.getItem("brood_"+item.chipID);'
    . 'if(bd){const d=new Date(bd),nw=new Date();if((nw-d)/86400000<21)ic.push("<img src=\'uploads/emoji-hive-eggs-brood.png\' class=\'mp-emoji-status\' title=\'Puiet activ\'>");}'
    . 'if((item.honeyEstimate||0)>=5)ic.push("<img src=\'uploads/emoji-inventory-profit.png\' class=\'mp-emoji-status\' title=\'Recolt\u0103 abundent\u0103\'>");'
    . 'return ic.length?`<div class="mp-status-strip">${ic.join("")}</div>`:"";})()}';
$n = "return d!==null&&d>14?`<div style=\"font-size:0.62rem;font-weight:700;color:#f39c12;margin-top:2px;\">\xf0\x9f\x93\x8b \${d}z f\xc4\x83r\xc4\x83 inspec\xc8\x9bie</div>`:''; })()}" . $strip;
if (str_contains($content,$o)){$content=str_replace($o,$n,$content);$patches++;$log[]='\u2705 Hive status strip';}else{$log[]='\u26a0\ufe0f Status strip — already done';}

// P8: Inventory item icons
$o = '<div><b>${i.item}</b>: ${i.qty} ${i.type}${sharedBadge}</div><div style="display:flex;gap:4px;flex-shrink:0;">';
$n = '<div style="display:flex;align-items:center;gap:8px;">${(()=>{const t=(i.type||"").toLowerCase(),c=i.category||"";if(t.includes("litri"))return"<img src=\'uploads/emoji-inventory-liquid-stock.png\' class=\'mp-emoji-inv-item\'>";if(c==="Cutii&Rame")return"<img src=\'uploads/emoji-inventory-frames-boxes.png\' class=\'mp-emoji-inv-item\'>";if(c==="Tratamente&Hrana")return"<img src=\'uploads/emoji-hive-treatment-done.png\' class=\'mp-emoji-inv-item\'>";return"<img src=\'uploads/emoji-inventory-scale.png\' class=\'mp-emoji-inv-item\'>";})()}<div><b>${i.item}</b>: ${i.qty} ${i.type}${sharedBadge}</div></div><div style="display:flex;gap:4px;flex-shrink:0;">';
if (str_contains($content,$o)){$content=str_replace($o,$n,$content);$patches++;$log[]='\u2705 Inventory item icons';}else{$log[]='\u26a0\ufe0f Inv items — already done';}

// Save
if($patches>0){copy($appJsPath,$appJsPath.'.bak_'.date('Ymd_His'));file_put_contents($appJsPath,$content);}
$newSize=strlen($content);

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><style>body{font-family:system-ui,sans-serif;max-width:560px;margin:40px auto;padding:24px;background:#fbf7f0;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.08)}h2{color:#4a3528;margin-top:0}</style></head><body>';
echo '<h2>&#x1F41D; MATCA Emoji Patch v2</h2>';
foreach($log as $l) echo "<p>$l</p>";
echo "<hr><p><b>Patches applied: $patches/7</b></p><p>app.js: {$originalSize} &rarr; {$newSize} bytes</p>";
if($patches>0) echo '<p style="background:#fef3cd;padding:12px;border-radius:8px;"><b>&#x26A0;&#xFE0F; DELETE apply_patches_v2.php from cPanel File Manager now!</b></p>';
echo '</body></html>';
