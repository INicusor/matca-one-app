<?php
/*
 * login.php — inclus din index.php (după require_once 'backend.php')
 * NU face require_once 'backend.php' din nou.
 * $csrfToken este deja disponibil din backend.php via generateCsrfToken().
 */
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Login — MATCA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#d1b490">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --honey:        #d4860b;
            --honey-light:  #f5c842;
            --honey-pale:   #fdf3dc;
            --wood:         #8b5e3c;
            --wood-dark:    #5d4037;
            --wood-light:   #d1b490;
            --cream:        #fdfbf7;
            --green:        #10ac84;
            --red:          #ee5253;
            --text:         #2c3e50;
            --text-muted:   #7f8c8d;
        }

        body {
            font-family: 'Nunito', sans-serif;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fdf8ef;
            background-image:
                radial-gradient(ellipse at 20% 10%, rgba(245,200,66,0.18) 0%, transparent 55%),
                radial-gradient(ellipse at 80% 90%, rgba(166,132,92,0.15) 0%, transparent 55%);
            background-attachment: fixed;
            background-size: cover;
            padding: 20px;
            box-sizing: border-box;
        }
        html { height: 100%; }

        /* Faguri SVG animați în fundal */
        .honeycomb-bg {
            position: fixed; inset: 0;
            pointer-events: none; z-index: 0; overflow: hidden;
        }
        .hc-cell {
            fill: none;
            stroke: rgba(212,134,11,0.12);
            stroke-width: 1.2;
            transition: fill 0.4s;
        }
        .hc-cell.lit {
            fill: rgba(245,200,66,0.13);
            stroke: rgba(212,134,11,0.28);
        }

        /* Card */
        .login-card {
            position: relative; z-index: 10;
            width: 100%; max-width: 420px;
            background: rgba(255,255,255,0.93);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-radius: 28px;
            border: 1.5px solid rgba(209,180,144,0.5);
            box-shadow: 0 4px 6px rgba(93,64,55,0.04), 0 20px 60px rgba(93,64,55,0.12), 0 0 0 1px rgba(255,255,255,0.6) inset;
            overflow: hidden;
        }

        /* Header */
        .card-header {
            background: linear-gradient(135deg, #a6845c 0%, #d4860b 60%, #f5c842 100%);
            padding: 32px 32px 24px;
            text-align: center; position: relative; overflow: hidden;
        }
        .card-header::before { content:''; position:absolute; top:-30px; right:-30px; width:120px; height:120px; border-radius:50%; background:rgba(255,255,255,0.08); }
        .card-header::after  { content:''; position:absolute; bottom:-20px; left:-20px; width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,0.06); }
        .logo-ring {
            width:68px; height:68px; border-radius:50%;
            background:rgba(255,255,255,0.22); border:2px solid rgba(255,255,255,0.4);
            display:flex; align-items:center; justify-content:center;
            margin:0 auto 12px; font-size:2rem;
            box-shadow:0 4px 16px rgba(0,0,0,0.12);
        }
        .card-header h1 { font-size:1.55rem; font-weight:900; color:#fff; text-shadow:0 2px 8px rgba(0,0,0,0.15); letter-spacing:-0.3px; margin:0; }
        .card-header p  { font-size:0.83rem; color:rgba(255,255,255,0.82); margin-top:4px; font-weight:600; }

        /* Body */
        .card-body { padding: 28px 28px 20px; }

        /* Mesaje */
        .msg { font-size:0.84rem; font-weight:700; margin-bottom:18px; padding:11px 15px; border-radius:12px; display:flex; align-items:center; gap:8px; animation:slideDown 0.3s ease; }
        @keyframes slideDown { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
        .msg.error   { background:#fff0f0; color:#c0392b; border:1px solid #fbc4c4; }
        .msg.success { background:#f0fff8; color:#1e8449; border:1px solid #b2dfdb; }
        .msg.error::before   { content:'⚠️'; }
        .msg.success::before { content:'✅'; }

        /* Tab bar */
        .tab-bar {
            display:flex; background:#f5f0e8; border-radius:14px;
            padding:4px; gap:2px; margin-bottom:22px;
        }
        .tab-btn {
            flex:1; padding:8px 6px; border:none; border-radius:10px;
            background:transparent; font-family:inherit; font-size:0.78rem;
            font-weight:800; color:var(--text-muted); cursor:pointer;
            transition:all 0.22s; white-space:nowrap;
        }
        .tab-btn.active { background:#fff; color:var(--wood-dark); box-shadow:0 2px 10px rgba(93,64,55,0.1); }

        /* Formulare */
        .form-section { display:none; }
        .form-section.active { display:block; animation:fadeSlide 0.25s ease; }
        @keyframes fadeSlide { from{opacity:0;transform:translateX(8px)} to{opacity:1;transform:translateX(0)} }

        .field { margin-bottom:14px; }
        .field label { display:block; font-size:0.78rem; font-weight:800; color:var(--wood-dark); margin-bottom:5px; letter-spacing:0.3px; }
        .input-wrap { position:relative; }
        .input-wrap .icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); font-size:0.95rem; pointer-events:none; }
        .input-wrap input {
            width:100%; padding:12px 13px 12px 40px;
            border:1.5px solid #e8ddd0; border-radius:12px;
            font-family:inherit; font-size:0.93rem; font-weight:600;
            color:var(--text); background:#fdfbf7;
            transition:all 0.2s; outline:none;
        }
        .input-wrap input:focus { border-color:var(--honey); background:#fff; box-shadow:0 0 0 3px rgba(212,134,11,0.1); }
        .input-wrap input::placeholder { color:#bbb; font-weight:400; }
        .pass-toggle { position:absolute; right:13px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; font-size:0.95rem; color:var(--text-muted); padding:0; line-height:1; }
        .input-wrap input.has-toggle { padding-right:40px; }

        /* Buton principal */
        .btn-primary {
            width:100%; padding:13px; border:none; border-radius:14px;
            background:linear-gradient(135deg, var(--honey) 0%, #e8970d 100%);
            color:#fff; font-family:inherit; font-size:0.97rem; font-weight:900;
            cursor:pointer; transition:all 0.22s;
            box-shadow:0 4px 16px rgba(212,134,11,0.32);
            letter-spacing:0.3px; margin-top:4px; position:relative; overflow:hidden;
        }
        .btn-primary::after { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(255,255,255,0.15),transparent); }
        .btn-primary:hover  { transform:translateY(-2px); box-shadow:0 8px 24px rgba(212,134,11,0.42); }
        .btn-primary:active { transform:translateY(0); }

        /* Link helper */
        .link-text { text-align:center; font-size:0.8rem; color:var(--text-muted); margin-top:14px; }
        .link-text span { color:var(--honey); font-weight:800; cursor:pointer; border-bottom:1px dashed rgba(212,134,11,0.3); }

        /* Hint box */
        .hint-box { background:var(--honey-pale); border:1px solid rgba(212,134,11,0.25); border-radius:12px; padding:11px 13px; font-size:0.79rem; color:var(--wood-dark); margin-bottom:14px; line-height:1.5; }

        /* Indicator parolă */
        #pass-strength { margin:-6px 0 14px; height:4px; border-radius:4px; background:#eee; transition:all 0.3s; width:0%; }

        /* Footer */
        .card-footer { padding:12px 28px 18px; text-align:center; border-top:1px solid rgba(209,180,144,0.18); }
        .card-footer p { font-size:0.73rem; color:var(--text-muted); }
        .bee-row { font-size:1.1rem; letter-spacing:4px; margin-top:4px; opacity:0.45; }

        @media (max-width: 480px) {
            .card-body { padding:20px 18px 16px; }
            .card-header { padding:24px 18px 18px; }
        }
    </style>
</head>
<body>

<div class="honeycomb-bg">
    <svg id="hc-svg" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"></svg>
</div>

<div class="login-card" role="main">

    <div class="card-header">
        <div class="logo-ring">🍯</div>
        <h1>MATCA</h1>
        <p>Sistem de management apicol</p>
    </div>

    <div class="card-body">

        <?php
            if (isset($_SESSION['error_msg']))   { echo "<div class='msg error'>"   . htmlspecialchars($_SESSION['error_msg'])   . "</div>"; unset($_SESSION['error_msg']); }
            if (isset($_SESSION['success_msg'])) { echo "<div class='msg success'>" . htmlspecialchars($_SESSION['success_msg']) . "</div>"; unset($_SESSION['success_msg']); }
            if (isset($error))                   { echo "<div class='msg error'>"   . htmlspecialchars($error)                   . "</div>"; }
        ?>

        <div class="tab-bar" role="tablist">
            <button class="tab-btn active" onclick="showForm('form-login',this)"    role="tab">🔑 Intră</button>
            <button class="tab-btn"        onclick="showForm('form-register',this)" role="tab">✨ Cont nou</button>
            <button class="tab-btn"        onclick="showForm('form-forgot',this)"   role="tab">🔒 Parolă</button>
        </div>

        <div id="form-login" class="form-section active">
            <form method="POST" action="index.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="field">
                    <label for="l-user">Utilizator</label>
                    <div class="input-wrap">
                        <span class="icon">👤</span>
                        <input type="text" id="l-user" name="user" placeholder="Numele tău de utilizator"
                               autocomplete="username" required maxlength="64">
                    </div>
                </div>
                <div class="field">
                    <label for="l-pass">Parolă</label>
                    <div class="input-wrap">
                        <span class="icon">🔐</span>
                        <input type="password" id="l-pass" name="pass" placeholder="••••••••"
                               autocomplete="current-password" required maxlength="128" class="has-toggle">
                        <button type="button" class="pass-toggle" onclick="togglePass('l-pass',this)">👁️</button>
                    </div>
                </div>
                <button type="submit" name="login" class="btn-primary">Intră în cont</button>
            </form>
        </div>

        <div id="form-register" class="form-section">
            <form method="POST" action="index.php" onsubmit="return validateReg(this)">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="field">
                    <label for="r-user">Utilizator dorit</label>
                    <div class="input-wrap">
                        <span class="icon">👤</span>
                        <input type="text" id="r-user" name="reg_user" placeholder="ex: apicultor_ion"
                               pattern="[a-zA-Z0-9_]{3,32}" title="3–32 caractere: litere, cifre, _"
                               autocomplete="username" required maxlength="32">
                    </div>
                </div>
                <div class="field">
                    <label for="r-email">Email</label>
                    <div class="input-wrap">
                        <span class="icon">📧</span>
                        <input type="email" id="r-email" name="reg_email" placeholder="adresa@email.ro"
                               autocomplete="email" required maxlength="128">
                    </div>
                </div>
                <div class="field">
                    <label for="r-pass">Parolă (min. 6 caractere)</label>
                    <div class="input-wrap">
                        <span class="icon">🔐</span>
                        <input type="password" id="r-pass" name="reg_pass" placeholder="••••••••"
                               autocomplete="new-password" required minlength="6" maxlength="128" class="has-toggle">
                        <button type="button" class="pass-toggle" onclick="togglePass('r-pass',this)">👁️</button>
                    </div>
                </div>
                <div id="pass-strength"></div>
                <button type="submit" name="register_account" class="btn-primary">Trimite cererea</button>
            </form>
            <p class="link-text">Adminul va aloca stupii după aprobare.</p>
        </div>

        <div id="form-forgot" class="form-section">
            <div class="hint-box">💌 Introdu emailul contului. Vei primi o parolă temporară.</div>
            <form method="POST" action="index.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="field">
                    <label for="f-email">Adresa de email</label>
                    <div class="input-wrap">
                        <span class="icon">📧</span>
                        <input type="email" id="f-email" name="reset_email" placeholder="adresa@email.ro"
                               autocomplete="email" required maxlength="128">
                    </div>
                </div>
                <button type="submit" name="forgot_password" class="btn-primary">Trimite parola temporară</button>
            </form>
        </div>

    </div>

    <div class="card-footer">
        <p>© <?= date('Y') ?> Mierea Pofta · Toate drepturile rezervate</p>
        <div class="bee-row">🐝 🌸 🍯</div>
    </div>

</div>

<script>
/* Faguri interactivi - Rezolvare spațiu gol */
(function(){
    const svg = document.getElementById('hc-svg'), S = 32, W = S * 2, H = S * Math.sqrt(3);
    const cells = [];
    let drawTimeout;

    function hexPath(cx, cy, r) {
        let d = '';
        for (let i = 0; i < 6; i++) {
            const a = Math.PI / 180 * (60 * i - 30);
            const x = cx + r * Math.cos(a);
            const y = cy + r * Math.sin(a);
            d += (i === 0 ? 'M' : 'L') + x.toFixed(1) + ' ' + y.toFixed(1);
        }
        return d + 'Z';
    }

    function drawGrid() {
        svg.innerHTML = '';
        cells.length = 0;
        
        // Luăm dimensiunile maxime curente ale ecranului
        const w = window.innerWidth;
        const h = window.innerHeight;
        
        // MATEMATICA REPARATĂ: Lățimea ocupată orizontal de un rând legat este de W * 0.75
        // Am adăugat +3 coloane/rânduri ca buffer de siguranță pentru margini
        const cols = Math.ceil(w / (W * 0.75)) + 3;
        const rows = Math.ceil(h / H) + 3;

        for (let row = -1; row < rows; row++) {
            for (let col = -1; col < cols; col++) {
                const cx = col * W * 0.75 + S;
                const cy = row * H + (col % 2 === 0 ? H / 2 : 0) + H / 2;
                
                const p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                p.setAttribute('d', hexPath(cx, cy, S - 1));
                p.setAttribute('class', 'hc-cell');
                svg.appendChild(p);
                cells.push(p);
            }
        }
    }

    // Desenare inițială
    drawGrid();

    // Redesenare inteligentă dacă redimensionezi fereastra (fără să blocheze browserul)
    window.addEventListener('resize', () => {
        clearTimeout(drawTimeout);
        drawTimeout = setTimeout(drawGrid, 200);
    });

    // Animația (twinkle)
    function twinkle() {
        if (!cells.length) return;
        const c = cells[Math.floor(Math.random() * cells.length)];
        c.classList.add('lit');
        setTimeout(() => c.classList.remove('lit'), 900 + Math.random() * 600);
    }
    setInterval(twinkle, 180);
})();

/* Tab switch */
function showForm(id, btn){
    document.querySelectorAll('.form-section').forEach(el=>el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el=>el.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    if(btn) btn.classList.add('active');
}

/* Toggle parolă */
function togglePass(id,btn){
    const inp=document.getElementById(id);
    inp.type=inp.type==='password'?'text':'password';
    btn.textContent=inp.type==='password'?'👁️':'🙈';
}

/* Validare register */
function validateReg(form){
    const u=form.querySelector('#r-user').value.trim(), p=form.querySelector('#r-pass').value;
    if(!/^[a-zA-Z0-9_]{3,32}$/.test(u)){alert('Utilizatorul poate conține doar litere, cifre și _ (3–32 caractere).');return false;}
    if(p.length<6){alert('Parola trebuie să aibă cel puțin 6 caractere.');return false;}
    return true;
}

/* Indicator putere parolă */
document.getElementById('r-pass').addEventListener('input',function(){
    const bar=document.getElementById('pass-strength'), v=this.value;
    let s=0;
    if(v.length>=6)s++;if(v.length>=10)s++;
    if(/[A-Z]/.test(v)&&/[a-z]/.test(v))s++;
    if(/[0-9]/.test(v))s++;if(/[^a-zA-Z0-9]/.test(v))s++;
    const cols=['#eee','#ee5253','#f39c12','#f5c842','#10ac84','#0e8c6e'];
    const ws=['0%','25%','45%','65%','85%','100%'];
    bar.style.background=cols[s];bar.style.width=ws[s];
});

/* Activare tab din PHP dacă e nevoie */
<?php
$activeTab = 'form-login';
if (isset($_POST['register_account'])) $activeTab = 'form-register';
if (isset($_POST['forgot_password']))  $activeTab = 'form-forgot';
?>
(function(){
    const t='<?= $activeTab ?>';
    if(t!=='form-login'){
        const idx=t==='form-register'?1:2;
        showForm(t,document.querySelectorAll('.tab-btn')[idx]);
    }
})();

/* SW */
if('serviceWorker' in navigator) window.addEventListener('load',()=>navigator.serviceWorker.register('sw.js').catch(()=>{}));
</script>
</body>
</html>