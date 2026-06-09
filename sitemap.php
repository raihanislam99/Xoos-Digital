<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';

$base = rtrim(BASE_URL, '/');

header('Content-Type: application/xml; charset=utf-8');

$pages = [
  ['loc' => $base . '/',                    'priority' => '1.0', 'changefreq' => 'weekly'],
  ['loc' => $base . '/about',               'priority' => '0.8', 'changefreq' => 'monthly'],
  ['loc' => $base . '/services',            'priority' => '0.8', 'changefreq' => 'monthly'],
  ['loc' => $base . '/portfolio',           'priority' => '0.7', 'changefreq' => 'weekly'],
  ['loc' => $base . '/blog',                'priority' => '0.9', 'changefreq' => 'weekly'],
  ['loc' => $base . '/contact',             'priority' => '0.7', 'changefreq' => 'monthly'],
  ['loc' => $base . '/policy?type=privacy', 'priority' => '0.4', 'changefreq' => 'yearly'],
  ['loc' => $base . '/policy?type=terms',   'priority' => '0.4', 'changefreq' => 'yearly'],
  ['loc' => $base . '/policy?type=cookies', 'priority' => '0.4', 'changefreq' => 'yearly'],
];

$posts = [];
$portfolioItems = [];
try {
  $stmt = db()->query("SELECT slug, updated_at FROM blog_posts WHERE status = 'published' ORDER BY updated_at DESC");
  while ($row = $stmt->fetch()) {
    $posts[] = [
      'loc' => $base . '/post/' . urlencode($row['slug']),
      'lastmod' => date('c', strtotime($row['updated_at'] ?: $row['created_at'])),
    ];
  }
  $stmt2 = db()->query("SELECT slug, updated_at FROM portfolio WHERE is_active = true AND slug IS NOT NULL AND slug != '' ORDER BY updated_at DESC");
  while ($row = $stmt2->fetch()) {
    $portfolioItems[] = [
      'loc' => $base . '/portfolio?slug=' . urlencode($row['slug']),
      'lastmod' => date('c', strtotime($row['updated_at'])),
    ];
  }
} catch (Exception $e) {}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as $p): ?>
  <url>
    <loc><?= h($p['loc']) ?></loc>
    <priority><?= $p['priority'] ?></priority>
    <changefreq><?= $p['changefreq'] ?></changefreq>
  </url>
<?php endforeach; ?>
<?php foreach ($posts as $p): ?>
  <url>
    <loc><?= h($p['loc']) ?></loc>
    <lastmod><?= $p['lastmod'] ?></lastmod>
    <priority>0.6</priority>
    <changefreq>monthly</changefreq>
  </url>
<?php endforeach; ?>
<?php foreach ($portfolioItems as $p): ?>
  <url>
    <loc><?= h($p['loc']) ?></loc>
    <lastmod><?= $p['lastmod'] ?></lastmod>
    <priority>0.7</priority>
    <changefreq>monthly</changefreq>
  </url>
<?php endforeach; ?>
</urlset>
