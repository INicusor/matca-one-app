<?php
require_once __DIR__ . '/auth.php';
admin_session_start();

// Daca e deja logat, redirect la dashboard
if (!empty($_SESSION['admin_user'])) {
    header('Location: dashboard.php');
    exit;
}

$error   = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if (admin_login($u, $p)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Utilizator sau parolă incorectă.';
    // Delay anti-brute-force
    sleep(1);
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MATCA Admin Console — Login</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: #0a0c10;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

/* Grid background */
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(212,134,11,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(212,134,11,0.04) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
}

/* Glow */
body::after {
    content: '';
    position: fixed;
    top: 30%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 600px;
    height: 400px;
    background: radial-gradient(ellipse, rgba(212,134,11,0.06) 0%, transparent 70%);
    pointer-events: none;
}

.login-box {
    background: #13161e;
    border: 1px solid #2a2d3a;
    border-radius: 16px;
    padding: 48px 40px;
    width: 420px;
    position: relative;
    z-index: 1;
    box-shadow: 0 24px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(212,134,11,0.1);
}

.logo-wrap {
    text-align: center;
    margin-bottom: 32px;
}

.logo-hex {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #d4860b, #8b5e3c);
    clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
    margin-bottom: 16px;
    font-size: 28px;
}

h1 {
    font-size: 22px;
    font-weight: 700;
    color: #f0f0f0;
    letter-spacing: 0.5px;
}

.subtitle {
    font-size: 12px;
    color: #d4860b;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-top: 4px;
    font-weight: 600;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #8892a4;
    margin-bottom: 8px;
}

input {
    width: 100%;
    padding: 12px 16px;
    background: #0d0f14;
    border: 1px solid #2a2d3a;
    border-radius: 8px;
    color: #e8eaf0;
    font-size: 15px;
    font-family: inherit;
    transition: all 0.2s;
    outline: none;
}

input:focus {
    border-color: #d4860b;
    box-shadow: 0 0 0 3px rgba(212,134,11,0.12);
}

input::placeholder { color: #3a3f4e; }

.btn-login {
    width: 100%;
    padding: 13px;
    background: linear-gradient(135deg, #d4860b, #b8720a);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    letter-spacing: 0.5px;
    transition: all 0.2s;
    margin-top: 8px;
}

.btn-login:hover {
    background: linear-gradient(135deg, #e8960d, #d4860b);
    box-shadow: 0 4px 20px rgba(212,134,11,0.35);
    transform: translateY(-1px);
}

.btn-login:active { transform: translateY(0); }

.error-msg {
    background: rgba(238,82,83,0.1);
    border: 1px solid rgba(238,82,83,0.3);
    border-radius: 8px;
    color: #ee5253;
    font-size: 13px;
    padding: 10px 14px;
    margin-bottom: 18px;
    text-align: center;
}

.timeout-msg {
    background: rgba(243,156,18,0.1);
    border: 1px solid rgba(243,156,18,0.3);
    border-radius: 8px;
    color: #f39c12;
    font-size: 13px;
    padding: 10px 14px;
    margin-bottom: 18px;
    text-align: center;
}

.back-link {
    text-align: center;
    margin-top: 20px;
    font-size: 12px;
    color: #4a5060;
}

.back-link a {
    color: #d4860b;
    text-decoration: none;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.back-link a:hover { opacity: 1; }

.badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #d4860b;
    border: 1px solid rgba(212,134,11,0.3);
    border-radius: 4px;
    padding: 2px 8px;
    margin-top: 8px;
}

.credentials-hint {
    background: rgba(212,134,11,0.06);
    border: 1px solid rgba(212,134,11,0.15);
    border-radius: 8px;
    padding: 10px 14px;
    margin-top: 16px;
    font-size: 11px;
    color: #6b7280;
    text-align: center;
    line-height: 1.6;
}
</style>
</head>
<body>
<div class="login-box">
    <div class="logo-wrap">
        <div class="logo-hex">🍯</div>
        <h1>MATCA</h1>
        <div class="subtitle">Admin Console</div>
        <div class="badge">Restricted Access</div>
    </div>

    <?php if ($timeout): ?>
    <div class="timeout-msg">⏱ Sesiunea a expirat. Te rog autentifică-te din nou.</div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="error-msg">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label>Utilizator Admin</label>
            <input type="text" name="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="username"
                   autofocus autocomplete="off">
        </div>

        <div class="form-group">
            <label>Parolă</label>
            <input type="password" name="password"
                   placeholder="••••••••"
                   autocomplete="current-password">
        </div>

        <button type="submit" class="btn-login">Autentificare →</button>
    </form>

    <div class="credentials-hint">
        Prima autentificare: <strong></strong> / <strong>!</strong><br>
        Schimbă parola imediat după prima logare.
    </div>

    <div class="back-link">
        <a href="../index.php">← Înapoi la aplicație</a>
    </div>
</div>
</body>
</html>