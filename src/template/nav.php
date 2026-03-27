<?php
// Set $current_page before including this file.
// Values: 'start' | 'boka' | 'om-oss' | 'musik'
$current_page = $current_page ?? '';

function nav_item(string $href, string $label, string $page, string $current): string {
    $active = ($page === $current) ? ' class="active" aria-current="page"' : '';
    $href_attr = htmlspecialchars($href);
    $label_safe = htmlspecialchars($label);
    return "<li><a href=\"{$href_attr}\"{$active}>{$label_safe}</a></li>";
}
?>
<nav id="main-nav">
    <ul>
        <?= nav_item('/', 'Start', 'start', $current_page) ?>
        <?= nav_item('/boka.php', 'Boka', 'boka', $current_page) ?>
        <?= nav_item('/om-oss.php', 'Om oss', 'om-oss', $current_page) ?>
        <?= nav_item('/vår-musik.php', 'Vår musik', 'musik', $current_page) ?>
    </ul>
</nav>
