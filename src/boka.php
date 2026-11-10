<?php
require_once __DIR__ . '/lib/contact.php';

$form = ['values' => array_fill_keys(CONTACT_FIELDS, ''), 'errors' => []];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $result = contact_handle($_POST);
    if ($result['ok']) {
        header('Location: /boka.php?skickat=1#kontakt', true, 303);
        exit;
    }
    $form = $result;
}
$sent = isset($_GET['skickat']) && !$form['errors'];

function field_value(array $form, string $field): string {
    return htmlspecialchars($form['values'][$field] ?? '', ENT_QUOTES);
}

function field_error(array $form, string $field): string {
    $error = $form['errors'][$field] ?? null;
    return $error ? '<p class="field-error" id="' . $field . '-error">' . htmlspecialchars($error) . '</p>' : '';
}

function field_attrs(array $form, string $field): string {
    return isset($form['errors'][$field]) ? ' aria-invalid="true" aria-describedby="' . $field . '-error"' : '';
}

$current_page = 'boka';
$page_title = 'Boka Tre Dagens | Trubadur & coverband i Hudiksvall';
$meta_description = 'Boka Tre Dagens till fest, kräftskiva, krog eller företagsevent i Hudiksvall och Enånger. Kontakta oss för pris och lediga datum.';
$meta_url = 'https://tredagens.se/boka.php';
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

        <h2 id="kontakt">Skicka ett meddelande</h2>
        <?php if ($sent): ?>
            <p class="form-success" role="status">Tack för ditt meddelande! Vi hör av oss så snart vi kan.</p>
        <?php else: ?>
        <form class="contact-form" method="post" action="/boka.php#kontakt">
            <?php if (isset($form['errors']['form'])): ?>
                <p class="form-error" role="alert"><?= htmlspecialchars($form['errors']['form']) ?></p>
            <?php elseif ($form['errors']): ?>
                <p class="form-error" role="alert">Kontrollera fälten nedan.</p>
            <?php endif; ?>

            <input type="hidden" name="token" value="<?= htmlspecialchars(contact_form_token()) ?>">
            <div class="hp" aria-hidden="true">
                <label for="website">Lämna tomt</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="name">Namn</label>
                    <input id="name" name="name" type="text" maxlength="100" autocomplete="name" required
                           value="<?= field_value($form, 'name') ?>"<?= field_attrs($form, 'name') ?>>
                    <?= field_error($form, 'name') ?>
                </div>
                <div class="form-field">
                    <label for="phone">Telefon</label>
                    <input id="phone" name="phone" type="tel" maxlength="30" autocomplete="tel" required
                           value="<?= field_value($form, 'phone') ?>"<?= field_attrs($form, 'phone') ?>>
                    <?= field_error($form, 'phone') ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label for="email">E-post <span class="optional">(frivilligt)</span></label>
                    <input id="email" name="email" type="email" maxlength="254" autocomplete="email"
                           value="<?= field_value($form, 'email') ?>"<?= field_attrs($form, 'email') ?>>
                    <?= field_error($form, 'email') ?>
                </div>
                <div class="form-field">
                    <label for="company">Företag <span class="optional">(frivilligt)</span></label>
                    <input id="company" name="company" type="text" maxlength="100" autocomplete="organization"
                           value="<?= field_value($form, 'company') ?>"<?= field_attrs($form, 'company') ?>>
                    <?= field_error($form, 'company') ?>
                </div>
            </div>
            <div class="form-field">
                <label for="message">Meddelande</label>
                <textarea id="message" name="message" rows="6" maxlength="3000" required
                          placeholder="Datum, plats, typ av tillställning, ungefär hur många gäster…"<?= field_attrs($form, 'message') ?>><?= field_value($form, 'message') ?></textarea>
                <?= field_error($form, 'message') ?>
            </div>

            <div class="form-field">
                <altcha-widget challenge="/altcha-challenge.php" auto="onfocus" language="sv" name="altcha"></altcha-widget>
                <?= field_error($form, 'captcha') ?>
            </div>

            <p class="form-privacy">Vi sparar uppgifterna du skickar för att kunna svara dig, och raderar dem senast efter
                ett år. De delas inte med någon annan.</p>
            <button type="submit">Skicka</button>
        </form>
        <script type="module" src="/static/js/altcha-3.5.0-sv.js"></script>
        <script type="module" src="/static/js/altcha-3.5.0.min.js"></script>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/template/template_bot.php'; ?>
