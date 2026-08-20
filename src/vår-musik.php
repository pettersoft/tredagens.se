<?php
$current_page = 'musik';
$page_title = 'Vår musik | Covers & egna låtar | Tre Dagens';
$meta_description = 'Lyssna på Tre Dagens musik med covers, setlist och egna låtar från vårt coverband och vår trubadurduo i Hudiksvall.';
$meta_url = 'https://tredagens.se/vår-musik.php';
require_once __DIR__ . '/template/template_top.php';
?>
<section class="page bg1">
    <header>
        <div class="header-wrapper">
            <img src="/static/img/logo_white.png" alt="Tre Dagens logga">
            <?php require __DIR__ . '/template/nav.php'; ?>
        </div>
    </header>

    <div class="content">
        <h1>Vår musik</h1>
        <p>När vi spelar live så kan ni räkna med Mando Diao, Creedence, Chuck Berry, Eric Clapton, Bob Dylan, Eddie Cochran, Elvis & Johnny Cash, med mera!</p>
        <p>Vi uppdaterar ofta vår setlist och brukar kunna skräddarsy efter vem det är som anordnar!</p>
    </div>
</section>

<?php require_once __DIR__ . '/template/template_bot.php'; ?>
