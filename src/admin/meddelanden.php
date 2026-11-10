<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/contact.php';
require_once __DIR__ . '/_layout.php';

auth_start_session();
admin_require_login();

$filters = [
    'inkorg' => ['label' => 'Att granska', 'statuses' => ['ny', 'misstänkt']],
    'äkta' => ['label' => 'Äkta', 'statuses' => ['äkta']],
    'hanterad' => ['label' => 'Hanterade', 'statuses' => ['hanterad']],
    'spam' => ['label' => 'Spam', 'statuses' => ['spam']],
    'alla' => ['label' => 'Alla', 'statuses' => CONTACT_STATUSES],
];
$filter = (string) ($_GET['visa'] ?? 'inkorg');
if (!isset($filters[$filter])) {
    $filter = 'inkorg';
}
$self = '/admin/meddelanden.php?visa=' . rawurlencode($filter);

$error = null;
try {
    $db = contact_db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int) ($_POST['id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        if (!auth_check_csrf()) {
            $error = 'Sessionen har gått ut. Ladda om sidan och försök igen.';
        } elseif ($action === 'status' && in_array($_POST['status'] ?? '', CONTACT_STATUSES, true)) {
            $db->prepare('UPDATE contact_messages SET status = ? WHERE id = ?')->execute([$_POST['status'], $id]);
        } elseif ($action === 'delete') {
            $db->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
        }
        if ($error === null) {
            header('Location: ' . $self, true, 303);
            exit;
        }
    }

    contact_cleanup($db);

    $counts = array_fill_keys(CONTACT_STATUSES, 0);
    foreach ($db->query('SELECT status, COUNT(*) AS n FROM contact_messages GROUP BY status') as $row) {
        $counts[$row['status']] = (int) $row['n'];
    }

    $statuses = $filters[$filter]['statuses'];
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $q = $db->prepare("SELECT * FROM contact_messages WHERE status IN ($placeholders) ORDER BY created_at DESC LIMIT 200");
    $q->execute($statuses);
    $messages = $q->fetchAll();

    $blocked = $db->prepare("SELECT kind, COUNT(*) AS n FROM contact_events
                             WHERE kind LIKE 'blocked:%' AND ts > ? GROUP BY kind");
    $blocked->execute([time() - 7 * 86400]);
    $blocked = array_column($blocked->fetchAll(), 'n', 'kind');
} catch (Throwable $e) {
    error_log('admin messages: ' . $e->getMessage());
    $error = 'Kunde inte läsa meddelandena från databasen.';
    $counts = array_fill_keys(CONTACT_STATUSES, 0);
    $messages = [];
    $blocked = [];
}

$blocked_labels = [
    'blocked:honeypot' => 'honeypot',
    'blocked:captcha' => 'captcha',
    'blocked:replay' => 'återanvänd captcha',
    'blocked:token' => 'ogiltigt formulär',
    'blocked:fast' => 'för snabbt',
    'blocked:rate' => 'för många inskick',
];
$tz = new DateTimeZone(STATS_TZ);
$months = ['jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
$actions = ['äkta' => 'Äkta', 'hanterad' => 'Hanterad', 'spam' => 'Spam', 'ny' => 'Ej granskad'];

function status_button(string $self, array $m, string $status, string $label): string {
    if ($m['status'] === $status) {
        return '';
    }
    return '<form method="post" action="' . h($self) . '">'
        . '<input type="hidden" name="csrf" value="' . h(auth_csrf_token()) . '">'
        . '<input type="hidden" name="action" value="status">'
        . '<input type="hidden" name="id" value="' . (int) $m['id'] . '">'
        . '<input type="hidden" name="status" value="' . h($status) . '">'
        . '<button type="submit">' . h($label) . '</button></form>';
}

admin_page_start('Meddelanden', 'messages');
?>
    <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>

    <nav class="ranges" aria-label="Filter">
        <?php foreach ($filters as $key => $f):
            $n = array_sum(array_intersect_key($counts, array_flip($f['statuses'])));
        ?>
            <a href="?visa=<?= h(rawurlencode($key)) ?>"<?= $key === $filter ? ' class="active" aria-current="page"' : '' ?>><?= h($f['label']) ?><span class="count"><?= $n ?></span></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($blocked): ?>
        <p class="blocked">Blockerade inskick senaste 7 dagarna:
            <?= h(implode(', ', array_map(fn($k, $n) => ($blocked_labels[$k] ?? $k) . ' ' . $n, array_keys($blocked), $blocked))) ?>
        </p>
    <?php endif; ?>

    <?php if (!$messages): ?>
        <div class="card"><p class="empty">Inga meddelanden här.</p></div>
    <?php endif; ?>

    <div class="messages">
    <?php foreach ($messages as $m):
        $dt = (new DateTimeImmutable('@' . $m['created_at']))->setTimezone($tz);
        $when = $dt->format('j') . ' ' . $months[(int) $dt->format('n') - 1] . ' ' . $dt->format('Y H:i');
    ?>
        <article class="card">
            <div class="msg-head">
                <div class="who">
                    <?= h($m['name']) ?><?php if ($m['company']): ?> <span class="company">· <?= h($m['company']) ?></span><?php endif; ?>
                    <span class="status status-<?= h($m['status']) ?>"><?= h($m['status']) ?></span>
                </div>
                <time datetime="<?= h($dt->format('c')) ?>"><?= h($when) ?></time>
            </div>
            <div class="msg-contact">
                <a href="tel:<?= h($m['phone']) ?>"><?= h($m['phone']) ?></a>
                <?php if ($m['email']): ?><a href="mailto:<?= h($m['email']) ?>"><?= h($m['email']) ?></a><?php endif; ?>
            </div>
            <?php if ($m['flags']): ?><div class="msg-flags">Misstänkt: <?= h($m['flags']) ?></div><?php endif; ?>
            <p class="msg-body"><?= h($m['message']) ?></p>
            <div class="msg-actions">
                <?php foreach ($actions as $status => $label) {
                    if (!($status === 'ny' && !in_array($m['status'], ['spam', 'hanterad', 'äkta'], true))) {
                        echo status_button($self, $m, $status, $label);
                    }
                } ?>
                <span class="spacer"></span>
                <form method="post" action="<?= h($self) ?>" onsubmit="return confirm('Radera meddelandet permanent?')">
                    <input type="hidden" name="csrf" value="<?= h(auth_csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <button class="danger" type="submit">Radera</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
    </div>

    <p class="note">Meddelanden markerade som spam raderas automatiskt efter 30 dagar, övriga efter ett år.</p>
<?php admin_page_end();
