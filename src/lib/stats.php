<?php
// Cookie-free visitor statistics in SQLite.
//
// A visitor is identified by hash(daily salt + IP + user agent). The salt is
// random per day and old salts are deleted, so visitors can be counted per day
// but can't be followed across days, and no IP addresses are stored.

const STATS_TZ = 'Europe/Stockholm';

function stats_db(): PDO {
    static $db = null;
    if ($db !== null) {
        return $db;
    }
    $path = getenv('STATS_DB_PATH') ?: '/var/www/data/stats.db';
    $db = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec('PRAGMA busy_timeout = 2000');
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA synchronous = NORMAL');
    $db->exec('
        CREATE TABLE IF NOT EXISTS pageviews (
            id       INTEGER PRIMARY KEY,
            ts       INTEGER NOT NULL,
            day      TEXT    NOT NULL,
            path     TEXT    NOT NULL,
            visitor  TEXT    NOT NULL,
            referrer TEXT
        );
        CREATE INDEX IF NOT EXISTS pageviews_day ON pageviews(day);
        CREATE TABLE IF NOT EXISTS salts (
            day  TEXT PRIMARY KEY,
            salt TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS login_failures (
            ts      INTEGER NOT NULL,
            client  TEXT    NOT NULL
        );
    ');
    return $db;
}

function stats_today(): string {
    return (new DateTimeImmutable('now', new DateTimeZone(STATS_TZ)))->format('Y-m-d');
}

function stats_daily_salt(PDO $db, string $day): string {
    $salt = $db->prepare('SELECT salt FROM salts WHERE day = ?');
    $salt->execute([$day]);
    $value = $salt->fetchColumn();
    if ($value !== false) {
        return $value;
    }
    $db->prepare('INSERT OR IGNORE INTO salts (day, salt) VALUES (?, ?)')
        ->execute([$day, bin2hex(random_bytes(16))]);
    // Yesterday's salt is no longer needed; dropping it makes old hashes unlinkable.
    $db->prepare('DELETE FROM salts WHERE day < ?')->execute([$day]);
    $salt->execute([$day]);
    return $salt->fetchColumn();
}

function stats_is_bot(string $ua): bool {
    return $ua === ''
        || preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|curl|wget|python|httpclient|go-http|java\/|headless|lighthouse|monitor|uptime|scan/i', $ua) === 1;
}

// Records a page view for the current request. Never throws: statistics must
// not be able to break the site.
function stats_track(string $path): void {
    try {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (stats_is_bot($ua)) {
            return;
        }
        $db = stats_db();
        $day = stats_today();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $visitor = substr(hash('sha256', stats_daily_salt($db, $day) . "\n" . $ip . "\n" . $ua), 0, 16);

        $referrer = null;
        $ref_host = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST);
        if (is_string($ref_host) && $ref_host !== '') {
            $ref_host = strtolower(preg_replace('/^www\./i', '', $ref_host));
            $own = strtolower(preg_replace('/^www\./i', '', (string) parse_url(getenv('SITE_URL') ?: '', PHP_URL_HOST)));
            $own_req = strtolower(preg_replace('/^www\./i', '', explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]));
            if ($ref_host !== $own && $ref_host !== $own_req) {
                $referrer = substr($ref_host, 0, 255);
            }
        }

        $db->prepare('INSERT INTO pageviews (ts, day, path, visitor, referrer) VALUES (?, ?, ?, ?, ?)')
            ->execute([time(), $day, substr($path, 0, 255), $visitor, $referrer]);
    } catch (Throwable $e) {
        error_log('stats_track: ' . $e->getMessage());
    }
}
