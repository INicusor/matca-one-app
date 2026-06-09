<?php
/**
 * MATCA Admin Console — Auth Helper
 * Compatibil PHP 7.4+
 */

define('ADMIN_CONSOLE_ROOT', __DIR__);
define('APP_ROOT', dirname(__DIR__));
define('ADMINS_FILE', ADMIN_CONSOLE_ROOT . '/admins.json');
define('AUDIT_FILE',  ADMIN_CONSOLE_ROOT . '/audit_log.json');
define('SESSION_NAME', 'matca_admin_sess');
define('SESSION_LIFETIME', 3600 * 8);

// ── DB Config (din db_config.php al aplicației principale) ───
if (!defined('DB_HOST')) {
    require_once APP_ROOT . '/db_config.php';
}

// ── Session init ──────────────────────────────────────────────
function admin_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/MPDashboard/admin_console',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

// ── Verifica autentificare ────────────────────────────────────
function admin_require_auth() {
    admin_session_start();
    if (empty($_SESSION['admin_user']) || empty($_SESSION['admin_ts'])) {
        header('Location: index.php');
        exit;
    }
    if (time() - $_SESSION['admin_ts'] > SESSION_LIFETIME) {
        session_destroy();
        header('Location: index.php?timeout=1');
        exit;
    }
    $_SESSION['admin_ts'] = time();
}

function admin_current_user() {
    return $_SESSION['admin_user'] ?? '';
}

function admin_current_role() {
    return $_SESSION['admin_role'] ?? 'admin';
}

// ── Admins JSON ───────────────────────────────────────────────
function load_admins() {
    if (!file_exists(ADMINS_FILE)) {
        // IMPORTANT: La prima instalare se genereaza automat o parola aleatorie
        // Verifica fisierul admins.json creat pe server si schimba parola imediat!
        $randomPass = bin2hex(random_bytes(8));
        $default = [
            'superadmin' => [
                'password' => password_hash($randomPass, PASSWORD_BCRYPT),
                'role'     => 'superadmin',
                'email'    => 'admin@yourdomain.com',
                'name'     => 'Super Administrator',
                'created'  => date('Y-m-d H:i:s'),
            ]
        ];
        file_put_contents(ADMINS_FILE, json_encode($default, JSON_PRETTY_PRINT));
        // Salveaza parola generata intr-un fisier temporar pe server
        file_put_contents(ADMIN_CONSOLE_ROOT . '/admin_init_pass.txt',
            "Parola initiala superadmin: $randomPass\nSterge acest fisier dupa ce ai notat parola!\nGenerat: " . date('Y-m-d H:i:s')
        );
        return $default;
    }
    $d = json_decode(file_get_contents(ADMINS_FILE), true);
    return is_array($d) ? $d : [];
}

// ── Login ─────────────────────────────────────────────────────
function admin_login($username, $password) {
    $admins = load_admins();
    if (!isset($admins[$username])) return false;
    if (!password_verify($password, $admins[$username]['password'])) return false;
    admin_session_start();
    session_regenerate_id(true);
    $_SESSION['admin_user'] = $username;
    $_SESSION['admin_role'] = $admins[$username]['role'] ?? 'admin';
    $_SESSION['admin_name'] = $admins[$username]['name'] ?? $username;
    $_SESSION['admin_ts']   = time();
    audit('AUTH', "Login: $username");
    return true;
}

function admin_logout() {
    audit('AUTH', 'Logout: ' . admin_current_user());
    session_destroy();
}

// ── CSRF ──────────────────────────────────────────────────────
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate() {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ── Audit Log ─────────────────────────────────────────────────
function audit($action, $detail = '', $user = null) {
    $user  = $user ?? ($_SESSION['admin_user'] ?? 'system');
    $entry = [
        'ts'     => time(),
        'date'   => date('d.m.Y H:i:s'),
        'user'   => $user,
        'action' => $action,
        'detail' => $detail,
        'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
    ];
    $log = [];
    if (file_exists(AUDIT_FILE)) {
        $d = json_decode(file_get_contents(AUDIT_FILE), true);
        if (is_array($d)) $log = $d;
    }
    array_unshift($log, $entry);
    if (count($log) > 5000) $log = array_slice($log, 0, 5000);
    file_put_contents(AUDIT_FILE, json_encode($log, JSON_PRETTY_PRINT));
}

// ── DB Connection ─────────────────────────────────────────────
function getAdminDB() {
    static $pdo = null, $failed = false;
    if ($failed) return null;
    if ($pdo) return $pdo;
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        $failed = true;
        error_log('[AdminConsole DB] ' . $e->getMessage());
    }
    return $pdo;
}

// ── JSON Helpers ──────────────────────────────────────────────
function read_json($file) {
    $path = APP_ROOT . '/' . $file;
    if (!file_exists($path)) return [];
    $d = json_decode(file_get_contents($path), true);
    return is_array($d) ? $d : [];
}

function write_json($file, $data) {
    $path = APP_ROOT . '/' . $file;
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function read_json_local($file) {
    $path = ADMIN_CONSOLE_ROOT . '/' . $file;
    if (!file_exists($path)) return [];
    $d = json_decode(file_get_contents($path), true);
    return is_array($d) ? $d : [];
}

function write_json_local($file, $data) {
    $path = ADMIN_CONSOLE_ROOT . '/' . $file;
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

// ── DB Sync ───────────────────────────────────────────────────
function dbSync($table, $data, $idCol = 'id') {
    $pdo = getAdminDB();
    if (!$pdo || empty($data)) return;
    try {
        $cols         = array_keys($data);
        $colList      = implode(', ', array_map(function($c) { return "`$c`"; }, $cols));
        $placeholders = implode(', ', array_map(function($c) { return ":$c"; }, $cols));
        $updates      = implode(', ', array_map(function($c) { return "`$c`=VALUES(`$c`)"; }, $cols));
        $pdo->prepare("INSERT INTO `$table` ($colList) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates")
            ->execute($data);
    } catch (PDOException $e) {
        error_log("[AdminConsole dbSync $table] " . $e->getMessage());
    }
}

function dbDelete($table, $id, $idCol = 'id') {
    $pdo = getAdminDB();
    if (!$pdo) return;
    try {
        $pdo->prepare("DELETE FROM `$table` WHERE `$idCol` = :id")->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log("[AdminConsole dbDelete $table] " . $e->getMessage());
    }
}

// ── Response helpers ──────────────────────────────────────────
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error($msg, $code = 400) {
    json_response(['error' => $msg], $code);
}

function json_ok($data = null) {
    json_response(['ok' => true, 'data' => $data]);
}
