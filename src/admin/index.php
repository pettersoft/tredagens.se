<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/contact.php';
require_once __DIR__ . '/_layout.php';

auth_start_session();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!auth_check_csrf()) {
        $error = 'Sessionen har gått ut. Försök igen.';
    } elseif ($action === 'login') {
        try {
            $error = auth_login((string) ($_POST['password'] ?? ''));
        } catch (Throwable $e) {
            error_log('admin login: ' . $e->getMessage());
            $error = 'Databasen är inte tillgänglig.';
        }
    } elseif ($action === 'logout') {
        auth_logout();
    }
    if ($error === null) {
        header('Location: /admin/', true, 303);
        exit;
    }
}

$ranges = [7 => '7 dagar', 30 => '30 dagar', 90 => '90 dagar', 365 => '1 år'];
$range = (int) ($_GET['dagar'] ?? 30);
if (!isset($ranges[$range])) {
    $range = 30;
}

$stats = null;
if (auth_is_logged_in()) {
    try {
        $db = stats_db();
        $tz = new DateTimeZone(STATS_TZ);
        $today = new DateTimeImmutable('today', $tz);
        $from = $today->modify('-' . ($range - 1) . ' days')->format('Y-m-d');

        $q = $db->prepare('SELECT day, COUNT(*) AS views, COUNT(DISTINCT visitor) AS visitors
                           FROM pageviews WHERE day >= ? GROUP BY day');
        $q->execute([$from]);
        $by_day = [];
        foreach ($q as $row) {
            $by_day[$row['day']] = $row;
        }

        $days = [];
        for ($d = new DateTimeImmutable($from, $tz); $d <= $today; $d = $d->modify('+1 day')) {
            $key = $d->format('Y-m-d');
            $days[] = [
                'day' => $key,
                'views' => (int) ($by_day[$key]['views'] ?? 0),
                'visitors' => (int) ($by_day[$key]['visitors'] ?? 0),
            ];
        }

        $top_pages = $db->prepare("SELECT path, COUNT(*) AS views, COUNT(DISTINCT day || visitor) AS visitors
                                   FROM pageviews WHERE day >= ? GROUP BY path ORDER BY visitors DESC, views DESC LIMIT 15");
        $top_pages->execute([$from]);

        $top_refs = $db->prepare("SELECT referrer, COUNT(DISTINCT day || visitor) AS visitors
                                  FROM pageviews WHERE day >= ? AND referrer IS NOT NULL
                                  GROUP BY referrer ORDER BY visitors DESC LIMIT 15");
        $top_refs->execute([$from]);

        $last = end($days);
        $stats = [
            'days' => $days,
            'today_visitors' => $last['visitors'],
            'today_views' => $last['views'],
            'visitors' => array_sum(array_column($days, 'visitors')),
            'views' => array_sum(array_column($days, 'views')),
            'pages' => $top_pages->fetchAll(),
            'refs' => $top_refs->fetchAll(),
        ];
    } catch (Throwable $e) {
        error_log('admin stats: ' . $e->getMessage());
        $error = 'Kunde inte läsa statistiken från databasen.';
    }
}

$weekdays = ['mån', 'tis', 'ons', 'tor', 'fre', 'lör', 'sön'];

if (!auth_is_logged_in()):
    admin_page_start('Logga in');
?>
    <form class="card login" method="post" action="/admin/">
        <h1>Tre Dagens admin</h1>
        <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
        <?php if (!auth_configured()): ?>
            <p class="error">ADMIN_PASSWORD_HASH är inte satt på servern.</p>
        <?php endif; ?>
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="csrf" value="<?= h(auth_csrf_token()) ?>">
        <label for="password">Lösenord</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required autofocus>
        <button type="submit">Logga in</button>
    </form>
<?php
    admin_page_end();
    exit;
endif;

admin_page_start('Statistik', 'stats');
?>
    <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>

    <?php if ($stats): ?>
    <nav class="ranges" aria-label="Tidsperiod">
        <?php foreach ($ranges as $n => $label): ?>
            <a href="?dagar=<?= $n ?>"<?= $n === $range ? ' class="active" aria-current="page"' : '' ?>><?= h($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="grid kpis">
        <div class="card kpi"><div class="label">Unika besökare idag</div><div class="value"><?= fmt($stats['today_visitors']) ?></div></div>
        <div class="card kpi"><div class="label">Sidvisningar idag</div><div class="value"><?= fmt($stats['today_views']) ?></div></div>
        <div class="card kpi"><div class="label">Besök, <?= h($ranges[$range]) ?></div><div class="value"><?= fmt($stats['visitors']) ?></div></div>
        <div class="card kpi"><div class="label">Sidvisningar, <?= h($ranges[$range]) ?></div><div class="value"><?= fmt($stats['views']) ?></div></div>
    </div>

    <section class="card chart">
        <h2>Unika besökare per dag</h2>
        <?php
        $n = count($stats['days']);
        $max = max(1, max(array_column($stats['days'], 'visitors')));
        $slot = 1000 / $n;
        $gap = $n > 120 ? 0 : min(4, $slot * 0.2);
        ?>
        <svg viewBox="0 0 1000 200" preserveAspectRatio="none" role="img" aria-label="Stapeldiagram över unika besökare per dag">
            <?php foreach ($stats['days'] as $i => $d):
                $hgt = $d['visitors'] / $max * 196;
                $dt = new DateTimeImmutable($d['day']);
                $label = $weekdays[(int) $dt->format('N') - 1] . ' ' . $dt->format('j/n');
            ?>
                <rect class="bar" x="<?= round($i * $slot + $gap / 2, 2) ?>" y="<?= round(200 - $hgt, 2) ?>"
                      width="<?= round(max(0.5, $slot - $gap), 2) ?>" height="<?= round(max($hgt, 0), 2) ?>">
                    <title><?= h($label) ?>: <?= $d['visitors'] ?> besökare, <?= $d['views'] ?> sidvisningar</title>
                </rect>
            <?php endforeach; ?>
        </svg>
        <div class="axis">
            <span><?= h((new DateTimeImmutable($stats['days'][0]['day']))->format('j/n')) ?></span>
            <span>max <?= fmt($max) ?>/dag</span>
            <span>idag</span>
        </div>
    </section>

    <div class="grid two">
        <section class="card">
            <h2>Populära sidor</h2>
            <?php if (!$stats['pages']): ?>
                <p class="empty">Inga besök ännu.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>Sida</th><th class="num">Besök</th><th class="num">Visningar</th></tr></thead>
                <tbody>
                <?php foreach ($stats['pages'] as $p): ?>
                    <tr><td class="path"><?= h($p['path']) ?></td><td class="num"><?= fmt((int) $p['visitors']) ?></td><td class="num"><?= fmt((int) $p['views']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Hänvisande sajter</h2>
            <?php if (!$stats['refs']): ?>
                <p class="empty">Inga hänvisningar ännu.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>Källa</th><th class="num">Besök</th></tr></thead>
                <tbody>
                <?php foreach ($stats['refs'] as $r): ?>
                    <tr><td class="path"><?= h($r['referrer']) ?></td><td class="num"><?= fmt((int) $r['visitors']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>
    </div>

    <p class="note">Besökare räknas utan cookies och utan att IP-adresser sparas. En person räknas som en unik besökare per dag,
        så ”Besök” för en period är summan av dagliga unika besökare. Botar och crawlers räknas inte.</p>
    <?php endif; ?>
<?php admin_page_end();
