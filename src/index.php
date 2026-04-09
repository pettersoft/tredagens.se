<?php
$current_page = 'start';
$page_title = 'Tre Dagens | Trubadur & coverband i Hudiksvall';
$meta_description = 'Tre Dagens är en trubadurduo och ett coverband i Hudiksvall för fest, krog, quiz och evenemang med välkända låtar och hög energi.';
$meta_url = 'https://tredagens.se/';
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
        <h1>Tre dagens</h1>
        <p>Välkommen till Tre Dagens officiella hemsida! Vi är ett två trubadurer / ett coverband i Hudiksvall. Vi älskar att få
            folk att dansa och ha kul. Oavsett om det är en 50-årsfest, kräftskiva eller något annat värt att fira, så
            levererar vi alltid en energifylld show med låtar som alla känner igen.</p>
        <p><blockquote>"Spela Creedence annars slår jag ihjäl dig!"</blockquote> - Vi lever än, vi levererar alltid Creedence.</p>
        <p><em>PS. Vi erbjuder även Quiz! Kontakta oss för mer information och bokning.</em></p>
    </div>
</section>

<?php require_once __DIR__ . '/template/template_bot.php'; ?>
