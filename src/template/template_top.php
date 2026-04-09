<?php
$page_title = $page_title ?? "Tre Dagens | Trubadur & coverband i Hudiksvall";
$meta_description = $meta_description ?? "Tre Dagens är en trubadurduo och ett coverband i Hudiksvall som spelar på fester, krogar och evenemang.";
$meta_robots = $meta_robots ?? "index,follow,max-image-preview:large";

$scheme = isset($_SERVER["REQUEST_SCHEME"]) ? $_SERVER["REQUEST_SCHEME"] : "https";
$host = $_SERVER["HTTP_HOST"] ?? "tredagens.se";
$request_uri = $_SERVER["REQUEST_URI"] ?? "/";
$request_path = parse_url($request_uri, PHP_URL_PATH) ?: "/";

$site_url = $scheme . "://" . $host;
$meta_url = $meta_url ?? ($site_url . $request_path);
$meta_image = $meta_image ?? ($site_url . "/static/img/bg3-16x9.JPG");

$schema = $schema ?? [
    "@context" => "https://schema.org",
    "@type" => "MusicGroup",
    "name" => "Tre Dagens",
    "url" => $site_url,
    "description" => $meta_description,
    "image" => $meta_image,
    "genre" => ["Coverband", "Trubadur", "Gypsy jazz"],
    "email" => "pettersoft@gmail.com",
    "telephone" => "+46725240777",
    "address" => [
        "@type" => "PostalAddress",
        "addressLocality" => "Hudiksvall",
        "addressRegion" => "Gävleborg",
        "addressCountry" => "SE",
    ],
];
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title><?=htmlspecialchars($page_title)?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?=htmlspecialchars($meta_description)?>">
    <meta name="robots" content="<?=htmlspecialchars($meta_robots)?>">
    <link rel="canonical" href="<?=htmlspecialchars($meta_url)?>">

    <meta property="og:locale" content="sv_SE">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="tredagens.se">
    <meta property="og:title" content="<?=htmlspecialchars($page_title)?>">
    <meta property="og:description" content="<?=htmlspecialchars($meta_description)?>">
    <meta property="og:url" content="<?=htmlspecialchars($meta_url)?>">
    <meta property="og:image" content="<?=htmlspecialchars($meta_image)?>">
    <meta property="og:image:alt" content="Tre Dagens livebild">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?=htmlspecialchars($page_title)?>">
    <meta name="twitter:description" content="<?=htmlspecialchars($meta_description)?>">
    <meta name="twitter:image" content="<?=htmlspecialchars($meta_image)?>">

    <script type="application/ld+json"><?=json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)?></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">


    <link rel="stylesheet" href="https://meyerweb.com/eric/tools/css/reset/reset.css">
    
    <!-- Vår css --> 
    <link rel="stylesheet" href="/static/css/main.css">
    <link rel="stylesheet" href="/static/css/typography.css">

    <link rel="icon" type="image/png" href="/static/img/favicon.ico">
</head>
<body>
<button class="nav-toggle" id="nav-toggle" aria-label="Öppna meny" aria-expanded="false">
    <span></span><span></span><span></span>
</button>
