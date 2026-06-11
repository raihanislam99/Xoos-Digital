<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
$pageTitle ??= 'Xoos Digital — Creative Agency, Dhaka, Bangladesh';
$pageDesc  ??= 'Xoos Digital — eXcellence | Opportunity | Outcome | Success. Creative agency based in Dhaka, Bangladesh specializing in branding, web development, digital marketing, SEO, and video production.';
$pageImage ??= BASE_URL . '/images/logo.png';
$canonical = BASE_URL . rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$ogType    = 'website';
if (strpos($_SERVER['REQUEST_URI'], '/post/') === 0) $ogType = 'article';
?>
  <title><?= h($pageTitle) ?></title>
  <meta name="description" content="<?= h($pageDesc) ?>">
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/favicon-96x96.png" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/favicon.svg" />
  <link rel="shortcut icon" href="<?= BASE_URL ?>/favicon.ico" />
  <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>/apple-touch-icon.png" />
  <meta name="apple-mobile-web-app-title" content="Xoos Digital" />
  <link rel="manifest" href="<?= BASE_URL ?>/site.webmanifest" />
  <link rel="canonical" href="<?= $canonical ?>">
  <meta property="og:title" content="<?= h($pageTitle) ?>">
  <meta property="og:description" content="<?= h($pageDesc) ?>">
  <meta property="og:url" content="<?= $canonical ?>">
  <meta property="og:type" content="<?= $ogType ?>">
  <meta property="og:image" content="<?= $pageImage ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($pageTitle) ?>">
  <meta name="twitter:description" content="<?= h($pageDesc) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="preload" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;600;700;800;900&family=Inter:wght@500;600;700;800;900&display=swap" as="style">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;600;700;800;900&family=Inter:wght@500;600;700;800;900&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.31.0/dist/tabler-icons.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.min.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
<base href="<?= BASE_URL ?>/">
</head>

<body class="overflow-safe">

  <!-- TOP BAR -->
  <div class="top-bar"></div>

  <!-- CUSTOM CURSOR -->
  <div class="cursor-dot" id="cursorDot"></div>
  <div class="cursor-ring" id="cursorRing"></div>

  <!-- LIGHTBOX -->
  <div class="lightbox" id="lightbox">
    <button class="lightbox-close" id="lightboxClose">✕</button>
    <div class="lightbox-content">
      <img id="lightboxImg" src="" alt="">
      <div class="lightbox-badge" id="lightboxCategory"></div>
      <div class="lightbox-title" id="lightboxTitle"></div>
      <div class="lightbox-desc" id="lightboxDesc"></div>
      <a class="lightbox-case-study-link" id="lightboxCaseStudyLink" style="display:none" href="">View Full Case Study →</a>
    </div>
  </div>
