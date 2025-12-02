<?php
$page_title = $page_title ?? "tredagens.se - Enångers största coverband";
$meta_description = $meta_description ?? "Tre Dagens bjuder på covers som får hela golvet att svänga.";
// $meta_image = $meta_image ?? "/static/img/social-share.png";
$meta_url = $meta_url
    ?? ((isset($_SERVER["REQUEST_SCHEME"]) ? $_SERVER["REQUEST_SCHEME"] : "https") .
        "://" .
        ($_SERVER["HTTP_HOST"] ?? "tredagens.se") .
        ($_SERVER["REQUEST_URI"] ?? "/"));
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title><?=htmlspecialchars($page_title)?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?=htmlspecialchars($meta_description)?>">
    <link rel="canonical" href="<?=htmlspecialchars($meta_url)?>">

    <meta property="og:locale" content="sv_SE">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="tredagens.se">
    <meta property="og:title" content="<?=htmlspecialchars($page_title)?>">
    <meta property="og:description" content="<?=htmlspecialchars($meta_description)?>">
    <meta property="og:url" content="<?=htmlspecialchars($meta_url)?>">
    <!-- <meta property="og:image" content="<?=htmlspecialchars($meta_image)?>"> -->

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">


    <link rel="stylesheet" href="https://meyerweb.com/eric/tools/css/reset/reset.css">
    
    <!-- Vår css --> 
    <link rel="stylesheet" href="/static/css/main.css">
    <link rel="stylesheet" href="/static/css/typography.css">
</head>
<body>

<!-- <header>
    <nav>
        <ul>
            <li><a href="/">Start</a></li>
            <li><a href="boka.php">Boka</a></li>
            <li><a href="om-oss.php">Om oss</a></li>
            <li><a href="musik.php">Vår musik</a></li>
        </ul>
    </nav>
</header> -->
