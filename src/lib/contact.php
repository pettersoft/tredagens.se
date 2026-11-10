<?php
// Contact form: validation, bot protection, rate limiting and storage.
//
// Layers, cheapest first: honeypot field, signed form token (CSRF + minimum
// fill time), per-client and global rate limits, ALTCHA proof-of-work. What
// gets through is stored; messages that still look odd are stored with status
// "misstänkt" so a human decides.

require_once __DIR__ . '/altcha.php';

const CONTACT_STATUSES = ['ny', 'misstänkt', 'äkta', 'hanterad', 'spam'];
const CONTACT_MIN_SECONDS = 3;
const CONTACT_SUSPICIOUS_SECONDS = 8;
const CONTACT_TOKEN_TTL = 2 * 3600;
const CONTACT_LIMITS = [
    // [window seconds, max stored messages per client]
    [10 * 60, 3],
    [24 * 3600, 10],
];
const CONTACT_GLOBAL_DAILY_LIMIT = 50;
const CONTACT_FIELDS = ['name', 'phone', 'email', 'company', 'message'];

function contact_db(): PDO {
    static $db = null;
    if ($db !== null) {
        return $db;
    }
    $path = getenv('CONTACT_DB_PATH') ?: '/var/www/data/contact.db';
    $db = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec('PRAGMA busy_timeout = 2000');
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA synchronous = NORMAL');
    $db->exec("
        CREATE TABLE IF NOT EXISTS contact_messages (
            id         INTEGER PRIMARY KEY,
            created_at INTEGER NOT NULL,
            name       TEXT    NOT NULL,
            phone      TEXT    NOT NULL,
            email      TEXT,
            company    TEXT,
            message    TEXT    NOT NULL,
            status     TEXT    NOT NULL DEFAULT 'ny',
            flags      TEXT,
            client     TEXT    NOT NULL,
            user_agent TEXT
        );
        CREATE INDEX IF NOT EXISTS contact_messages_status ON contact_messages(status, created_at);
        CREATE TABLE IF NOT EXISTS contact_events (
            ts     INTEGER NOT NULL,
            client TEXT    NOT NULL,
            kind   TEXT    NOT NULL
        );
        CREATE INDEX IF NOT EXISTS contact_events_ts ON contact_events(ts);
        CREATE TABLE IF NOT EXISTS altcha_used (
            signature TEXT PRIMARY KEY,
            expires   INTEGER NOT NULL
        );
    ");
    return $db;
}

function contact_configured(): bool {
    return strlen(getenv('CONTACT_SECRET') ?: '') >= 32;
}

function contact_form_key(): string {
    return hash_hmac('sha256', 'contact-form', getenv('CONTACT_SECRET') ?: '');
}

// Identifies a client for rate limiting for up to a day without storing the IP.
function contact_client(): string {
    return substr(hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', contact_form_key()), 0, 16);
}

function contact_form_token(): string {
    $ts = (string) time();
    return $ts . '.' . hash_hmac('sha256', $ts, contact_form_key());
}

// Seconds since the form was rendered, or null if the token is invalid.
function contact_token_age(string $token): ?int {
    if (!preg_match('/^(\d{1,12})\.([0-9a-f]{64})$/', $token, $m)) {
        return null;
    }
    if (!hash_equals(hash_hmac('sha256', $m[1], contact_form_key()), $m[2])) {
        return null;
    }
    $age = time() - (int) $m[1];
    return ($age < 0 || $age > CONTACT_TOKEN_TTL) ? null : $age;
}

function contact_clean(string $value, bool $multiline = false): string {
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    // Strip control characters (keep newlines in the message).
    $value = preg_replace($multiline ? '/[^\P{C}\n]/u' : '/\p{C}/u', '', $value) ?? '';
    if (!$multiline) {
        $value = preg_replace('/\s+/u', ' ', $value);
    }
    return trim($value);
}

// Returns [cleaned values, errors keyed by field].
function contact_validate(array $input): array {
    $v = [];
    foreach (CONTACT_FIELDS as $field) {
        $raw = is_string($input[$field] ?? null) ? $input[$field] : '';
        if (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = '';
        }
        $v[$field] = contact_clean($raw, $field === 'message');
    }

    $errors = [];
    $len = fn(string $s) => mb_strlen($s);

    if ($len($v['name']) < 2 || $len($v['name']) > 100) {
        $errors['name'] = 'Skriv ditt namn.';
    }

    $digits = preg_replace('/\D/', '', $v['phone']);
    if (!preg_match('/^\+?[\d\s\-()]+$/', $v['phone']) || strlen($digits) < 7 || strlen($digits) > 15) {
        $errors['phone'] = 'Skriv ett telefonnummer, t.ex. 070-123 45 67.';
    } else {
        $v['phone'] = (str_starts_with($v['phone'], '+') ? '+' : '') . $digits;
    }

    if ($v['email'] !== '' && ($len($v['email']) > 254 || !filter_var($v['email'], FILTER_VALIDATE_EMAIL))) {
        $errors['email'] = 'E-postadressen ser inte rätt ut.';
    }

    if ($len($v['company']) > 100) {
        $errors['company'] = 'Högst 100 tecken.';
    }

    if ($len($v['message']) < 10) {
        $errors['message'] = 'Skriv några ord om vad du vill ha hjälp med.';
    } elseif ($len($v['message']) > 3000) {
        $errors['message'] = 'Meddelandet får vara högst 3000 tecken.';
    }

    return [$v, $errors];
}

// Reasons a message that passed every check still looks like spam.
function contact_flags(PDO $db, array $v, int $age): array {
    $flags = [];
    $text = $v['name'] . ' ' . $v['company'] . ' ' . $v['message'];
    $links = preg_match_all('~https?://|www\.|\[url~i', $text);
    if ($links > 0) {
        $flags[] = $links > 1 ? "{$links} länkar" : 'länk';
    }
    if (preg_match('/[\p{Cyrillic}\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}\p{Arabic}\p{Thai}]/u', $text)) {
        $flags[] = 'främmande skrift';
    }
    if ($age < CONTACT_SUSPICIOUS_SECONDS) {
        $flags[] = "ifyllt på {$age} s";
    }
    $dup = $db->prepare('SELECT COUNT(*) FROM contact_messages WHERE message = ? AND created_at > ?');
    $dup->execute([$v['message'], time() - 30 * 86400]);
    if ((int) $dup->fetchColumn() > 0) {
        $flags[] = 'samma text som tidigare';
    }
    if ($v['name'] !== '' && $v['name'] === mb_strtoupper($v['name']) && $v['message'] === mb_strtoupper($v['message'])) {
        $flags[] = 'bara versaler';
    }
    return $flags;
}

function contact_log_event(PDO $db, string $client, string $kind): void {
    $db->prepare('INSERT INTO contact_events (ts, client, kind) VALUES (?, ?, ?)')->execute([time(), $client, $kind]);
}

function contact_rate_limited(PDO $db, string $client): bool {
    $now = time();
    $q = $db->prepare("SELECT COUNT(*) FROM contact_events WHERE kind = 'saved' AND client = ? AND ts > ?");
    foreach (CONTACT_LIMITS as [$window, $max]) {
        $q->execute([$client, $now - $window]);
        if ((int) $q->fetchColumn() >= $max) {
            return true;
        }
    }
    $g = $db->prepare("SELECT COUNT(*) FROM contact_events WHERE kind = 'saved' AND ts > ?");
    $g->execute([$now - 86400]);
    return (int) $g->fetchColumn() >= CONTACT_GLOBAL_DAILY_LIMIT;
}

// Handles a POST from the contact form. Returns:
//   ['ok' => true]                                  stored (or silently dropped bot)
//   ['ok' => false, 'values' => [...], 'errors' => [...]]  show the form again
function contact_handle(array $post): array {
    $values = array_fill_keys(CONTACT_FIELDS, '');
    foreach (CONTACT_FIELDS as $f) {
        if (is_string($post[$f] ?? null)) {
            $values[$f] = mb_substr($post[$f], 0, 3500);
        }
    }
    $fail = fn(array $errors) => ['ok' => false, 'values' => $values, 'errors' => $errors];

    if (!contact_configured()) {
        return $fail(['form' => 'Formuläret fungerar inte just nu. Mejla eller ring oss i stället.']);
    }

    try {
        $db = contact_db();
        $client = contact_client();

        // Bots that fill every field get a fake success and nothing is stored.
        if (trim((string) ($post['website'] ?? '')) !== '') {
            contact_log_event($db, $client, 'blocked:honeypot');
            return ['ok' => true];
        }

        $age = contact_token_age((string) ($post['token'] ?? ''));
        if ($age === null) {
            contact_log_event($db, $client, 'blocked:token');
            return $fail(['form' => 'Formuläret hann bli för gammalt. Försök igen.']);
        }
        if ($age < CONTACT_MIN_SECONDS) {
            contact_log_event($db, $client, 'blocked:fast');
            return $fail(['form' => 'Det gick lite för snabbt. Vänta ett par sekunder och skicka igen.']);
        }

        [$clean, $errors] = contact_validate($post);
        if ($errors) {
            return $fail($errors);
        }

        if (contact_rate_limited($db, $client)) {
            contact_log_event($db, $client, 'blocked:rate');
            return $fail(['form' => 'Vi har redan fått flera meddelanden härifrån. Försök igen senare, eller ring oss.']);
        }

        $signature = altcha_verify((string) ($post['altcha'] ?? ''));
        if ($signature === null) {
            contact_log_event($db, $client, 'blocked:captcha');
            return $fail(['captcha' => 'Bekräfta att du inte är en robot.']);
        }

        $db->beginTransaction();
        $db->prepare('DELETE FROM altcha_used WHERE expires < ?')->execute([time()]);
        $used = $db->prepare('INSERT OR IGNORE INTO altcha_used (signature, expires) VALUES (?, ?)');
        $used->execute([$signature, time() + ALTCHA_TTL]);
        if ($used->rowCount() === 0) {
            $db->rollBack();
            contact_log_event($db, $client, 'blocked:replay');
            return $fail(['captcha' => 'Bekräfta att du inte är en robot.']);
        }

        $flags = contact_flags($db, $clean, $age);
        $db->prepare('INSERT INTO contact_messages
                (created_at, name, phone, email, company, message, status, flags, client, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([
                time(),
                $clean['name'],
                $clean['phone'],
                $clean['email'] !== '' ? $clean['email'] : null,
                $clean['company'] !== '' ? $clean['company'] : null,
                $clean['message'],
                $flags ? 'misstänkt' : 'ny',
                $flags ? implode(', ', $flags) : null,
                $client,
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200),
            ]);
        contact_log_event($db, $client, 'saved');
        $db->commit();
        return ['ok' => true];
    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log('contact_handle: ' . $e->getMessage());
        return $fail(['form' => 'Något gick fel när meddelandet skulle sparas. Mejla eller ring oss i stället.']);
    }
}

// Retention: spam after 30 days, other messages after a year, events after 30 days.
function contact_cleanup(PDO $db): void {
    $now = time();
    $db->prepare("DELETE FROM contact_messages WHERE status = 'spam' AND created_at < ?")->execute([$now - 30 * 86400]);
    $db->prepare('DELETE FROM contact_messages WHERE created_at < ?')->execute([$now - 365 * 86400]);
    $db->prepare('DELETE FROM contact_events WHERE ts < ?')->execute([$now - 30 * 86400]);
}

function contact_unread_count(): int {
    try {
        return (int) contact_db()->query("SELECT COUNT(*) FROM contact_messages WHERE status IN ('ny', 'misstänkt')")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}
