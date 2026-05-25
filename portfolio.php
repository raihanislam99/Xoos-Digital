<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';

$portfolios = [];
try {
    $portfolios = get_all('portfolio', 'created_at DESC');
    // Normalize image URLs for JS
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
      ?>
      <div class="p-card" style="grid-column:span <?= $s[0] ?>;grid-row:span <?= $s[1] ?>;min-height:<?= $s[2] ?>px" data-category="<?= h($pCat) ?>" onclick="openLightbox(<?= $pi ?>)">
        <div class="p-card-bg"><?php if ($p['image_url']): ?><img src="<?= h(image_url($p['image_url'])) ?>" alt="" class="p-card-img" loading="lazy"><?php endif; ?></div>
        <div class="p-card-overlay" style="background:linear-gradient(to top,rgba(0,0,0,0.92) 0%,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0) 100%)">
          <span class="p-badge"><?= h(strtoupper($p['service'] ?? 'PROJECT')) ?></span>
          <div class="p-card-info">
            <h3 class="p-card-title"><?= h($p['project_name'] ?? '') ?></h3>
            <?php if ($p['client'] ?? ''): ?><p class="p-card-sub"><?= h($p['client']) ?></p><?php endif; ?>
          </div>
          <div class="p-view-btn"><span>↗</span></div>
        </div>
      </div>
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

function openLightbox(idx) {
  var p = portfolioData[idx];
  if (!p) return;
  document.getElementById('lightboxImg').src = p.image_url || 'images/Xoos_Digital_Facebook_Cover_Image.jpg';
  document.getElementById('lightboxCategory').textContent = p.service || '';
  document.getElementById('lightboxTitle').textContent = p.project_name || '';
  document.getElementById('lightboxDesc').textContent = p.description || '';

  var link = document.getElementById('lightboxLink');
  if (p.link) {
    link.style.display = 'inline-flex';
    link.href = p.link;
  } else {
    link.style.display = 'none';
  }

  document.getElementById('lightbox').classList.add('active');
  document.body.style.overflow = 'hidden';
}

document.getElementById('lightboxClose').addEventListener('click', function() {
  document.getElementById('lightbox').classList.remove('active');
  document.body.style.overflow = '';
});

document.getElementById('lightbox').addEventListener('click', function(e) {
  if (e.target === this) {
    this.classList.remove('active');
    document.body.style.overflow = '';
  }
});
</script>

<?php
require __DIR__ . '/inc/footer.php';
require __DIR__ . '/inc/project-form.php';
require __DIR__ . '/inc/chatbot.php';
require __DIR__ . '/inc/scripts.php';
?>
