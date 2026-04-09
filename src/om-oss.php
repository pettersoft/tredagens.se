<?php
$current_page = 'om-oss';
$page_title = 'Om Tre Dagens | Trubadurer från Hudiksvall';
$meta_description = 'Lär känna Tre Dagens, Petter och Ivar, en trubadurduo och ett coverband från Hudiksvall med rötter i Enånger.';
$meta_url = 'https://tredagens.se/om-oss.php';
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
        <h1>Om oss</h1>
        <p>Vilka är vi? Vi är två barndomskamrater från Hälsingekustens pärla, Enånger.</p>
        <p>Båda har djupt rotat musikintresse som går långt tillbaka i tiden. Med olika influenser och vägar som vi tagit, för att komma dit vi är idag</p>

        <h2>Trubadurer i Hudiksvall</h2>
        <p>Vi är det självklara valet för en trubadurafton i Hudiksvall med omnejd</p>
    </div>
</section>

<section class="band-container">
    <h2>Vilka är vi?</h2>
    <div class="band-members">
        <div class="band-member">
            <div class="band-member-img">
                <img src="/static/img/petter.png" alt="Bild på Petter Pettersson" loading="lazy">
            </div>
            <div class="band-member-info">
                <h3>Petter Pettersson</h3>
                <h4>Gitarr</h4>
                <p>Förutom att spela gitarr är Petter även vass i studion och har producerat flera låtar. Hans favoritband är The Who och det kan bjudas på windmills på scenen!</p>
            </div>
        </div>

        <div class="band-member">
            <div class="band-member-info">
                <h3>Ivar Engnell</h3>
                <h4>Sång, gitarr och stomp</h4>
                <p>Det är inte bara musik, det är en livsstil. Ivar, spelman i grunden, är förutom en riktigt bra
                    vokalist, även riktigt vass på dragspel och folkmusik.</p>
            </div>
            <div class="band-member-img">
                <img src="/static/img/ivar.png" alt="Bild på Ivar Engnell" loading="lazy">
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/template/template_bot.php'; ?>
