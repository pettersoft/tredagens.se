<?php
// Single-user admin login. The password is checked against a bcrypt/argon hash
// in the ADMIN_PASSWORD_HASH environment variable (see README).

require_once __DIR__ . '/stats.php';

const AUTH_IDLE_SECONDS = 8 * 3600;
const AUTH_MAX_FAILURES = 5;
const AUTH_FAILURE_WINDOW = 15 * 60;

function auth_start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('tdadmin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/admin/',
        'secure' => ($_SERVER['HTTPS'] ?? '') === 'on',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();

    if (isset($_SESSION['admin_seen']) && time() - $_SESSION['admin_seen'] > AUTH_IDLE_SECONDS) {
        auth_logout();
        session_start();
    }
    if (!empty($_SESSION['admin'])) {
        $_SESSION['admin_seen'] = time();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
}

function auth_configured(): bool {
    return (getenv('ADMIN_PASSWORD_HASH') ?: '') !== '';
}

function auth_is_logged_in(): bool {
    return !empty($_SESSION['admin']);
}

function auth_csrf_token(): string {
    return $_SESSION['csrf'];
}

function auth_check_csrf(): bool {
    return hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''));
}

function auth_client_key(): string {
    return substr(hash('sha256', 'login' . ($_SERVER['REMOTE_ADDR'] ?? '')), 0, 16);
}

function auth_is_throttled(PDO $db): bool {
    $db->prepare('DELETE FROM login_failures WHERE ts < ?')->execute([time() - AUTH_FAILURE_WINDOW]);
    $count = $db->prepare('SELECT COUNT(*) FROM login_failures WHERE client = ?');
    $count->execute([auth_client_key()]);
    return (int) $count->fetchColumn() >= AUTH_MAX_FAILURES;
}

// Returns null on success, or an error message to show.
function auth_login(string $password): ?string {
    if (!auth_configured()) {
        return 'Inloggning är inte konfigurerad på servern.';
    }
    $db = stats_db();
    if (auth_is_throttled($db)) {
        return 'För många misslyckade försök. Vänta en stund och försök igen.';
    }
    if (!password_verify($password, getenv('ADMIN_PASSWORD_HASH'))) {
        $db->prepare('INSERT INTO login_failures (ts, client) VALUES (?, ?)')->execute([time(), auth_client_key()]);
        return 'Fel lösenord.';
    }
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    $_SESSION['admin_seen'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return null;
}

function auth_logout(): void {
    $_SESSION = [];
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'secure' => $params['secure'],
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_destroy();
}
