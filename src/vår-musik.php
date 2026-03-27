<?php
$current_page = 'musik';
$page_title = 'Vår musik – Tre Dagens';
$meta_description = 'Lyssna på Tre Dagens musik – covers och egna låtar från bandet i Hudiksvall.';
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

        <h2>Men "er" musik då?</h2>
        <p>Givetvis! Vi skriver också egen musik. Några av våra främst alster faller inom genren gypsy jazz, detta är vår gode vän, Lars Gillman Jonsson, favoritlåt!</p>
        <iframe width="560" height="315" src="https://www.youtube.com/embed/3a2IwAtyhCQ?si=b3TMmLC-QkdZh5Gz" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
    </div>
</section>

<?php require_once __DIR__ . '/template/template_bot.php'; ?>
