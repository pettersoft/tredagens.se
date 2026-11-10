<?php
// Shared page chrome for the admin pages. Not served directly (see site.conf).

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES);
}

function fmt(int $n): string {
    return number_format($n, 0, ',', "\u{202F}");
}

function admin_page_start(string $title, ?string $active = null): void {
    $css_version = @filemtime(__DIR__ . '/admin.css') ?: 1;
    ?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= h($title) ?> | Tre Dagens</title>
    <link rel="icon" href="/static/img/favicon.ico" sizes="any">
    <link rel="stylesheet" href="/admin/admin.css?v=<?= $css_version ?>">
</head>
<body>
<main>
<?php if ($active !== null):
    $unread = contact_unread_count();
    $tab = fn(string $href, string $label, string $key, string $extra = '') =>
        '<a href="' . $href . '"' . ($key === $active ? ' class="active" aria-current="page"' : '') . '>' . h($label) . $extra . '</a>';
?>
    <header class="top">
        <div class="brand">
            <img src="/static/img/logo_96x96.png" alt="Tre Dagens">
            <nav class="tabs" aria-label="Admin">
                <?= $tab('/admin/', 'Statistik', 'stats') ?>
                <?= $tab('/admin/meddelanden.php', 'Meddelanden', 'messages',
                        $unread ? '<span class="badge" title="Nya eller misstänkta">' . $unread . '</span>' : '') ?>
            </nav>
        </div>
        <form method="post" action="/admin/">
            <input type="hidden" name="action" value="logout">
            <input type="hidden" name="csrf" value="<?= h(auth_csrf_token()) ?>">
            <button class="link-btn" type="submit">Logga ut</button>
        </form>
    </header>
<?php endif;
}

function admin_page_end(): void {
    ?>
</main>
</body>
</html>
<?php
}

// Sends anyone who isn't logged in to the login page.
function admin_require_login(): void {
    if (!auth_is_logged_in()) {
        header('Location: /admin/', true, 303);
        exit;
    }
}
