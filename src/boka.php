<?php
$current_page = 'boka';
$page_title = 'Boka Tre Dagens';
$meta_description = 'Boka Tre Dagens för din fest, kräftskiva eller evenemang i Hudiksvall och Enånger. Kontakta oss för pris och lediga datum.';
require_once __DIR__ . '/template/template_top.php';
?>
<section class="page bg3">
    <header>
        <div class="header-wrapper">
            <img src="/static/img/logo_white.png" alt="Tre Dagens logga">
            <?php require __DIR__ . '/template/nav.php'; ?>
        </div>
    </header>

    <div class="content">
        <h1>Kontakta oss</h1>
        <p>För bokningar och förfrågningar, vänligen kontakta oss via e-post på <a
                href="mailto:pettersoft@gmail.com">pettersoft@gmail.com</a>
            eller ring oss på <a href="tel:+46725240777">072-52 40 777</a>. Vi ser fram emot att höra från dig och göra
            ditt evenemang oförglömligt!</p>
    </div>
</section>

<?php require_once __DIR__ . '/template/template_bot.php'; ?>
