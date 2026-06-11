<?php
require_once __DIR__ . '/inc/page-cache.php';
page_cache_start('portfolio', 300);

require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';

$isDetail = !empty($_GET['slug']);

// ── Detail View ──
if ($isDetail) {
    $slug = trim($_GET['slug']);
    $project = get_all('portfolio', 'slug ASC');
    $project = array_filter($project, fn($p) => ($p['slug'] ?? '') === $slug || (string)($p['id'] ?? '') === $slug);
    $project = reset($project) ?: null;

    if (!$project || empty($project['is_active'])) {
        http_response_code(404);
        $pageTitle = 'Project Not Found | Xoos Digital';
        $pageDesc = 'The requested portfolio project could not be found.';
        require __DIR__ . '/inc/head.php';
        require __DIR__ . '/inc/navbar.php';
        echo '<section class="page-hero"><div class="page-hero-content"><h1 class="page-hero-title">PROJECT NOT <span class="brand-line">FOUND</span></h1><p class="page-hero-sub" style="margin-top:1rem"><a href="portfolio" style="color:#CCFF00">← Back to Portfolio</a></p></div></section>';
        require __DIR__ . '/inc/footer.php';
        require __DIR__ . '/inc/scripts.php';
        exit;
    }

    $project['image_url'] = image_url($project['image_url'] ?? '');
    $pageTitle = ($project['meta_title'] ?: $project['project_name'] . ' | Xoos Digital Portfolio');
    $pageDesc = $project['meta_description'] ?: strip_tags($project['description'] ?? '');

    // Get related projects (same category, excluding current)
    $allProjects = get_all('portfolio', 'sort_order ASC, created_at DESC');
    $related = array_filter($allProjects, fn($p) =>
        ($p['id'] !== $project['id']) && !empty($p['is_active']) &&
        ($p['category_id'] ?? 0) === ($project['category_id'] ?? 0)
    );
    $related = array_slice(array_values($related), 0, 3);

    require __DIR__ . '/inc/head.php';
    require __DIR__ . '/inc/navbar.php';
?>

<section class="cs-hero">
  <div class="container-xl">
    <a href="portfolio" class="cs-back-link">← Back to Portfolio</a>
    <div class="cs-hero-content">
      <?php if ($project['image_url']): ?>
      <div class="cs-hero-img-wrap">
        <img src="<?= h($project['image_url']) ?>" alt="<?= h($project['project_name']) ?>" class="cs-hero-img">
      </div>
      <?php endif; ?>
      <div class="cs-hero-text">
        <span class="p-badge"><?= h(strtoupper($project['service'] ?? 'PROJECT')) ?></span>
        <h1 class="cs-title"><?= h($project['project_name']) ?></h1>
        <?php if ($project['client']): ?><p class="cs-client">for <strong><?= h($project['client']) ?></strong></p><?php endif; ?>
        <?php if ($project['description']): ?><p class="cs-desc"><?= h($project['description']) ?></p><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="cs-body">
  <div class="container-xl">
    <div class="cs-grid">
      <div class="cs-main">
        <?php if ($project['challenge']): ?>
        <div class="cs-section">
          <h2 class="cs-section-title"><span class="cs-section-icon">◆</span> The Challenge</h2>
          <p class="cs-section-text"><?= nl2br(h($project['challenge'])) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($project['solution']): ?>
        <div class="cs-section">
          <h2 class="cs-section-title"><span class="cs-section-icon">◇</span> The Solution</h2>
          <div class="cs-section-text"><?= nl2br(h($project['solution'])) ?></div>
        </div>
        <?php endif; ?>

        <?php if ($project['results']): ?>
        <div class="cs-section cs-results-section">
          <h2 class="cs-section-title"><span class="cs-section-icon">◆</span> The Results</h2>
          <div class="cs-section-text"><?= nl2br(h($project['results'])) ?></div>
        </div>
        <?php endif; ?>

        <?php if ($project['client_testimonial']): ?>
        <div class="cs-testimonial-card">
          <div class="cs-testimonial-quote">"<?= h($project['client_testimonial']) ?>"</div>
          <?php if ($project['client']): ?>
          <div class="cs-testimonial-author">— <?= h($project['client']) ?></div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <aside class="cs-sidebar">
        <?php if ($project['technologies']): ?>
        <div class="cs-sidebar-block">
          <h3 class="cs-sidebar-title">Technologies Used</h3>
          <div class="cs-tech-tags">
            <?php foreach (explode(',', $project['technologies']) as $tech): ?>
            <span class="cs-tech-tag"><?= h(trim($tech)) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($project['service']): ?>
        <div class="cs-sidebar-block">
          <h3 class="cs-sidebar-title">Service</h3>
          <p class="cs-sidebar-text"><?= h($project['service']) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($project['client']): ?>
        <div class="cs-sidebar-block">
          <h3 class="cs-sidebar-title">Client</h3>
          <p class="cs-sidebar-text"><?= h($project['client']) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($project['link']): ?>
        <a href="<?= h($project['link']) ?>" target="_blank" rel="noopener" class="cs-visit-btn">Visit Live Site →</a>
        <?php endif; ?>

        <?php if ($project['video_url']): ?>
        <a href="<?= h($project['video_url']) ?>" target="_blank" rel="noopener" class="cs-video-btn">▶ Watch Case Study Video</a>
        <?php endif; ?>

        <div class="cs-sidebar-block cs-share-block">
          <h3 class="cs-sidebar-title">Share</h3>
          <div class="cs-share-links">
            <a href="https://www.facebook.com/sharer.php?u=<?= urlencode(BASE_URL . '/portfolio?slug=' . $project['slug']) ?>" target="_blank" rel="noopener" class="cs-share-link" title="Share on Facebook">FB</a>
            <a href="https://twitter.com/intent/tweet?text=<?= urlencode($project['project_name']) ?>&url=<?= urlencode(BASE_URL . '/portfolio?slug=' . $project['slug']) ?>" target="_blank" rel="noopener" class="cs-share-link" title="Share on Twitter">X</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(BASE_URL . '/portfolio?slug=' . $project['slug']) ?>" target="_blank" rel="noopener" class="cs-share-link" title="Share on LinkedIn">IN</a>
          </div>
        </div>

        <div class="cs-sidebar-cta">
          <p>Want a similar project?</p>
          <a href="#" onclick="event.preventDefault();openProjectForm()" class="p-start-link">START A PROJECT →</a>
        </div>
      </aside>
    </div>
  </div>
</section>

<?php if (count($related)): ?>
<section class="cs-related">
  <div class="container-xl">
    <h2 class="cs-related-title">Related Projects</h2>
    <div class="cs-related-grid">
      <?php foreach ($related as $rp):
        $rp['image_url'] = image_url($rp['image_url'] ?? '');
      ?>
      <a href="portfolio?slug=<?= h($rp['slug'] ?: $rp['id']) ?>" class="cs-related-card">
        <div class="cs-related-img-wrap">
          <?php if ($rp['image_url']): ?><img src="<?= h($rp['image_url']) ?>" alt="" loading="lazy"><?php endif; ?>
        </div>
        <div class="cs-related-info">
          <span class="cs-related-badge"><?= h($rp['service'] ?? '') ?></span>
          <h3><?= h($rp['project_name']) ?></h3>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
require __DIR__ . '/inc/footer.php';
require __DIR__ . '/inc/project-form.php';
require __DIR__ . '/inc/chatbot.php';
require __DIR__ . '/inc/scripts.php';
exit;
}

// ── Listing View ──
$portfolios = [];
try {
    $tmp = get_all('portfolio', 'sort_order ASC, created_at DESC');
    $portfolios = array_values(array_filter($tmp, fn($p) => !empty($p['is_active'])));
    foreach ($portfolios as &$p) {
        $p['image_url'] = image_url($p['image_url'] ?? '');
    }
    unset($p);
} catch (Exception $e) {}

$pageTitle = 'Portfolio | Xoos Digital';
$pageDesc = 'Browse Xoos Digital\'s portfolio of branding, web development, and digital marketing projects. See our work in action.';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/navbar.php';
?>

<section class="page-hero">
  <div class="page-hero-content">
    <div class="t-label-pill"><span class="t-label-dot"></span><span class="t-label-text">OUR WORK</span></div>
    <h1 class="page-hero-title">FEATURED <span class="brand-line">PROJECTS</span></h1>
    <p class="page-hero-sub">A showcase of brands, websites, and digital campaigns we've delivered with care and creativity for clients worldwide.</p>
  </div>
</section>

<section class="p-section overflow-safe" data-animate>
  <div class="container-xl">
    <div class="brands-pill" style="margin-bottom:1.25rem">
      <span class="brands-pill-dot"></span>
      <span class="brands-pill-text">OUR LATEST PROJECTS</span>
    </div>
    <div class="p-header-row">
      <h2 class="p-heading">OUR SUCCESSFUL PROJECTS</h2>
    </div>

    <?php
    $allCats = [];
    foreach ($portfolios as $p) {
        if (!empty($p['service'])) $allCats[] = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p['service']));
    }
    $allCats = array_unique($allCats);
    ?>

    <?php if (count($portfolios)): ?>
    <div class="p-filters">
      <span class="p-filter active" data-filter="all" onclick="filterPortfolio(this, 'all')">All</span>
      <?php foreach ($allCats as $cat): ?>
      <span class="p-filter" data-filter="<?= h($cat) ?>" onclick="filterPortfolio(this, '<?= h($cat) ?>')"><?= h(ucfirst($cat)) ?></span>
      <?php endforeach; ?>
    </div>

    <div class="p-bento-grid">
      <?php
      $pi = 0;
      $patIdx = 0; $patPos = 0;
      $rowPatterns = [
        [[6, 1, 280], [6, 1, 280]],
        [[4, 1, 220], [4, 1, 220], [4, 1, 220]],
      ];
      ?>
      <?php foreach ($portfolios as $p): ?>
      <?php
        $pCat = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p['service'] ?? ''));
        $row = $rowPatterns[$patIdx % count($rowPatterns)];
        $s = $row[$patPos];
        $patPos++;
        if ($patPos >= count($row)) { $patIdx++; $patPos = 0; }
        $detailSlug = !empty($p['slug']) ? '?slug=' . urlencode($p['slug']) : '?slug=' . $p['id'];
      ?>
      <a href="portfolio<?= $detailSlug ?>" class="p-card" style="grid-column:span <?= $s[0] ?>;grid-row:span <?= $s[1] ?>;min-height:<?= $s[2] ?>px" data-category="<?= h($pCat) ?>" onclick="event.stopPropagation();">
        <div class="p-card-bg"><?php if ($p['image_url']): ?><img src="<?= h($p['image_url']) ?>" alt="" class="p-card-img" loading="lazy"><?php endif; ?></div>
        <div class="p-card-overlay" style="background:linear-gradient(to top,rgba(0,0,0,0.92) 0%,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0) 100%)">
          <span class="p-badge"><?= h(strtoupper($p['service'] ?? 'PROJECT')) ?></span>
          <div class="p-card-info">
            <h3 class="p-card-title"><?= h($p['project_name'] ?? '') ?></h3>
            <?php if ($p['client'] ?? ''): ?><p class="p-card-sub"><?= h($p['client']) ?></p><?php endif; ?>
          </div>
          <div class="p-view-btn"><span>↗</span></div>
        </div>
      </a>
      <?php $pi++; endforeach; ?>

      <div class="p-card-cta">
        <div>
          <div class="p-cta-text">GOT A PROJECT? LET'S <span>BUILD IT.</span></div>
          <p class="p-cta-sub">View all our work or start your own project today.</p>
        </div>
        <div class="p-cta-actions">
          <a href="#" onclick="event.preventDefault();openProjectForm()" class="p-start-link">START A PROJECT →</a>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:4rem 2rem;color:#555;font-family:'Inter',sans-serif">
      <p>No portfolio items yet. Check back soon!</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<script>
var portfolioData = <?= json_encode($portfolios) ?>;

function filterPortfolio(el, filter) {
  document.querySelectorAll('.p-filter').forEach(function(t) { t.classList.remove('active'); });
  el.classList.add('active');
  document.querySelectorAll('.p-card').forEach(function(card) {
    var cat = card.getAttribute('data-category');
    var match = filter === 'all' || cat === filter;
    card.style.visibility = match ? '' : 'hidden';
    card.style.pointerEvents = match ? '' : 'none';
  });
}
</script>

<?php
require __DIR__ . '/inc/footer.php';
require __DIR__ . '/inc/project-form.php';
require __DIR__ . '/inc/chatbot.php';
require __DIR__ . '/inc/scripts.php';
page_cache_end();
?>
