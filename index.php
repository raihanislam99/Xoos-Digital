<?php
require_once __DIR__ . '/inc/page-cache.php';
page_cache_start('index', 300);

require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';

$services = $portfolios = $packages = $faqs = $testimonials = $brands = [];
$blogs = [];

try {
    $services    = get_all('services', 'sort_order ASC');
    $tmp  = get_all('portfolio', 'sort_order ASC, created_at DESC');
    $tmp = array_values(array_filter($tmp, fn($p) => !empty($p['is_active'])));
    $portfolios = array_slice($tmp, 0, 5);
    foreach ($portfolios as &$p) { $p['image_url'] = image_url($p['image_url'] ?? ''); }
    unset($p);
    $packages    = get_all('packages', 'created_at ASC');
    $faqs        = get_all('faq', 'sort_order ASC');
    $testimonials = get_all('testimonials', 'sort_order ASC');
    $brands      = get_all('brands', 'sort_order ASC');
    $blogStmt = db()->query("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC");
    $blogs = $blogStmt->fetchAll();
} catch (Exception $e) {
    error_log('Blog fetch error on index: ' . $e->getMessage());
}
?>
<?php
$pageTitle = 'Xoos Digital — Creative Agency';
$pageDesc = 'Xoos Digital — eXcellence | Opportunity | Outcome | Success. Creative agency specializing in branding, web development, digital marketing, SEO, and video production.';
require __DIR__ . '/inc/head.php';
?>

<?php require __DIR__ . '/inc/navbar.php'; ?>

  <!-- ══════════════════════════════════════════
       SECTION 2 — HERO
  ══════════════════════════════════════════ -->
  <section class="hero-section has-corners overflow-safe" id="home">
    <!-- Corner bracket markers -->
    <div class="corner-tl"></div>
    <div class="corner-tr"></div>
    <div class="corner-bl"></div>
    <div class="corner-br"></div>
    <!-- Animated rays container -->
    <div class="hero-rays" id="heroRays"></div>
    <!-- Center glow -->
    <div class="hero-glow"></div>
    <!-- Corner bracket -->
    <div class="hero-corner"></div>

    <!-- Spinning badge -->
    <a href="#" onclick="event.preventDefault();openProjectForm()" class="hero-badge hero-badge-pos">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A"
        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="7" y1="17" x2="17" y2="7" />
        <polyline points="7 7 17 7 17 17" />
      </svg>
      <span>Get<br>Started</span>
    </a>

    <!-- Hero content -->
    <div class="hero-content hero-content-wrap">
      <a href="."><img src="images/logo.png" alt="Xoos Digital" class="hero-logo" loading="eager" width="240" height="73"></a>
      <p class="hero-tagline"><span class="accent-sq">◼</span> e<span class="highlight">X</span>cellence | <span
          class="highlight">O</span>pportunity | <span class="highlight">O</span>utcome | <span
          class="highlight">S</span>uccess <span class="accent-sq">◼</span></p>
    </div>

    <!-- Ticker tape -->
    <div class="hero-ticker">
      <div class="ticker-inner">
        <span class="ticker-text">✳ CREATIVE BRANDING & GRAPHIC DESIGN</span>
        <span class="ticker-text">✳ CUSTOM WORDPRESS WEBSITE DEVELOPMENT</span>
        <span class="ticker-text">✳ PERFORMANCE-DRIVEN DIGITAL MARKETING</span>
        <span class="ticker-text">✳ SEO & ORGANIC GROWTH</span>
        <span class="ticker-text">✳ RESULT-ORIENTED VIDEO PRODUCTION</span>
        <span class="ticker-text">✳ CREATIVE BRANDING & GRAPHIC DESIGN</span>
        <span class="ticker-text">✳ CUSTOM WORDPRESS WEBSITE DEVELOPMENT</span>
        <span class="ticker-text">✳ PERFORMANCE-DRIVEN DIGITAL MARKETING</span>
        <span class="ticker-text">✳ SEO & ORGANIC GROWTH</span>
        <span class="ticker-text">✳ RESULT-ORIENTED VIDEO PRODUCTION</span>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       SECTION 3 — FOUNDER / ABOUT
  ══════════════════════════════════════════ -->
  <section id="about" class="about-section overflow-safe">
    <div class="section-container">
      <div class="founder-grid">
        <!-- Left: Photo -->
        <div class="founder-img-wrap" data-animate="slide-left">
          <img src="images/founder-photo-Raihan-1.jpg" alt="Raihan Islam — Founder, Xoos Digital" width="410" height="512" loading="lazy">
          <div class="founder-img-glow"></div>
        </div>
        <!-- Right: Content -->
        <div class="founder-content" data-animate="slide-right">
          <div class="brands-pill">
            <span class="brands-pill-dot"></span>
            <span class="brands-pill-text">FOUNDER & DIGITAL GROWTH STRATEGIST</span>
          </div>
          <h2 class="founder-headline">CRAFTING DIGITAL EXPERIENCES WITH STRATEGY AND CREATIVITY</h2>
          <p class="founder-body">
            I'm Raihan Islam, the Founder & Digital Growth Strategist at Xoos
            Digital. With over 4 years of experience in design, development, and digital
            marketing, I help businesses build strong online identities and achieve measurable growth. My
            focus is on combining creative design, modern technology, and strategic
            marketing to deliver impactful digital solutions for brands worldwide.
          </p>
          <!-- Skill Bars -->
          <div class="skill-item skill-item-first" data-animate>
            <div class="skill-label-row">
              <span>CREATIVE BRANDING & GRAPHIC DESIGN</span>
              <span>95%</span>
            </div>
            <div class="skill-track">
              <div class="skill-fill" data-width="95"></div>
            </div>
          </div>
          <div class="skill-item" data-animate>
            <div class="skill-label-row">
              <span>CUSTOM WORDPRESS DEVELOPMENT</span>
              <span>97%</span>
            </div>
            <div class="skill-track">
              <div class="skill-fill" data-width="97"></div>
            </div>
          </div>
          <a href="https://ri.xoosdigital.com/" class="founder-cta" target="_blank" rel="noopener noreferrer">
            LEARN MORE ABOUT ME
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12" />
              <polyline points="12 5 19 12 12 19" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       SECTION 4 — STATS BAR
  ══════════════════════════════════════════ -->
  <section id="stats" class="stats-bar overflow-safe">
    <div class="stats-grid">
      <div class="stat-item" data-animate>
        <div class="stat-number"><span data-count="<?= (int)get_setting('stat_brands','50') ?>" data-suffix="+">0</span></div>
        <div class="stat-label">BRANDS DESIGNED</div>
      </div>
      <div class="stat-item" data-animate>
        <div class="stat-number"><span data-count="<?= (int)get_setting('stat_websites','30') ?>" data-suffix="+">0</span></div>
        <div class="stat-label">WEBSITES LAUNCHED</div>
      </div>
      <div class="stat-item" data-animate>
        <div class="stat-number"><span data-count="<?= (int)get_setting('stat_clients','80') ?>" data-suffix="+">0</span></div>
        <div class="stat-label">CLIENTS SERVED</div>
      </div>
      <div class="stat-item" data-animate>
        <div class="stat-number"><span data-count="<?= (int)get_setting('stat_years','4') ?>" data-suffix="+">0</span></div>
        <div class="stat-label">YEARS EXPERIENCE</div>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       SECTION 5 — PROCESS / HOW WE WORK
  ══════════════════════════════════════════ -->
  <section id="process" class="process-section overflow-safe">
    <div class="process-header" data-animate>
      <div class="t-label-pill">
        <span class="t-label-dot"></span>
        <span class="t-label-text">OUR PROCESS</span>
      </div>
      <h2 class="process-headline">YOUR VISION<br><span class="brand-line">INTO REALITY</span></h2>
      <p class="process-sub">A clear, collaborative process — so you always know what's happening and what comes next.
      </p>
    </div>

    <div class="process-timeline">

      <!-- Step 01 — left -->
      <div class="process-step process-step-left">
        <div class="process-card">
          <div class="process-time">Within 24 hours</div>
          <h3 class="process-title">DISCOVERY & BRIEFING</h3>
          <p class="process-desc">We start with a deep dive into your brand, goals, target audience, and competition. A
            detailed brief ensures we're aligned from day one — no guesswork, no surprises.</p>
          <ul class="process-list">
            <li>✓ Brand discovery call</li>
            <li>✓ Competitor analysis</li>
            <li>✓ Goal setting & KPIs</li>
          </ul>
        </div>
        <div class="process-node">01</div>
      </div>

      <!-- Step 02 — right -->
      <div class="process-step process-step-right">
        <div class="process-card">
          <div class="process-time">Days 2-3</div>
          <h3 class="process-title">STRATEGY & PLANNING</h3>
          <p class="process-desc">We build a tailored strategy — design direction, content plan, technical roadmap, or
            marketing blueprint depending on your service. You approve before we build a single pixel.</p>
          <ul class="process-list">
            <li>✓ Creative direction deck</li>
            <li>✓ Project timeline</li>
            <li>✓ Milestone sign-off</li>
          </ul>
        </div>
        <div class="process-node">02</div>
      </div>

      <!-- Step 03 — left -->
      <div class="process-step process-step-left">
        <div class="process-card">
          <div class="process-time">Days 4-14</div>
          <h3 class="process-title">DESIGN, BUILD & PRODUCE</h3>
          <p class="process-desc">This is where the magic happens. Our team executes with precision — whether that's
            crafting a brand identity, building a WordPress site, or producing a campaign. Regular updates keep you in
            the loop.</p>
          <ul class="process-list">
            <li>✓ Design iterations</li>
            <li>✓ 2-3 revision rounds</li>
            <li>✓ Progress check-ins</li>
          </ul>
        </div>
        <div class="process-node">03</div>
      </div>

      <!-- Step 04 — right -->
      <div class="process-step process-step-right">
        <div class="process-card">
          <div class="process-time">Day 14-21</div>
          <h3 class="process-title">LAUNCH & GROW</h3>
          <p class="process-desc">We deliver, launch, and don't disappear. Post-launch support, performance tracking,
            and growth optimization are part of how we work. Your success is our reputation.</p>
          <ul class="process-list">
            <li>✓ Final delivery & handover</li>
            <li>✓ Performance tracking setup</li>
            <li>✓ 30-day post-launch support</li>
          </ul>
        </div>
        <div class="process-node">04</div>
      </div>

    </div>

    <div class="process-cta">
      <p>Ready to start your project?</p>
      <a href="#" onclick="event.preventDefault();openProjectForm()" class="process-cta-btn">START THE PROCESS →</a>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       SECTION 6 — BRANDS WE WORK WITH
  ══════════════════════════════════════════ -->
<script>window.brandsData = <?= json_encode($brands) ?>;window.testimonialsData = <?= json_encode($testimonials) ?>;</script>

<?php if (count($brands)): ?>
  <section id="brands" class="brands-section overflow-safe" data-animate>

    <!-- Atmospheric background gradient -->
    <div class="brands-atmos-bg"></div>

    <!-- Top edge glow -->
    <div class="brands-edge-top"></div>

    <!-- Bottom edge glow -->
    <div class="brands-edge-bottom"></div>

    <!--═══ HEADER ═══-->
    <div class="brands-glass-header">

      <!-- Label pill -->
      <div class="brands-pill">
        <span class="brands-pill-dot"></span>
        <span class="brands-pill-text">BRANDS WE WORK WITH</span>
      </div>

      <!-- Headline -->
      <h2 class="brands-glass-headline">
        TRUSTED BY
        <span class="brands-headline-outline">GROWING BRANDS</span>
      </h2>

      <p class="brands-glass-sub">From Dhaka startups to international businesses — brands that chose to grow with Xoos
        Digital.</p>

    </div>

    <!--═══ CAROUSEL ═══-->
    <div class="brands-carousel-outer">

      <!-- Track: cards injected by JS -->
      <div class="brands-carousel-track" id="brandsTrack"></div>

    </div>

    <!--═══ NAVIGATION ═══-->
    <div class="brands-nav" id="brandsNav">

      <button class="brands-arrow" id="brandsPrev" aria-label="Previous brand">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
          stroke-linecap="round">
          <path d="M19 12H5M12 5l-7 7 7 7" />
        </svg>
      </button>

      <div class="brands-dots" id="brandsDots"></div>

      <button class="brands-arrow" id="brandsNext" aria-label="Next brand">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
          stroke-linecap="round">
          <path d="M5 12h14M12 5l7 7-7 7" />
        </svg>
      </button>

    </div>

  </section>
<?php endif; ?>

  <!-- ══════════════════════════════════════════
       SECTION 7 — SERVICES (Expanding Accordion)
   ══════════════════════════════════════════ -->
  <section id="services" class="svc-section overflow-safe" data-animate>
    <div class="svc-bg-glow"></div>
    <div class="container-xl">

      <!-- Two-column header -->
      <div class="svc-header-grid">
        <div>
          <div class="brands-pill">
            <span class="brands-pill-dot"></span>
            <span class="brands-pill-text">EXCELLENCE IN EVERY PIXEL</span>
          </div>
          <h2 class="svc-header-title">
            INNOVATIVE<br>
            SERVICES FOR<br>
            <span class="svc-header-title-accent">MODERN BRANDS</span>
          </h2>
        </div>
        <div class="svc-header-sub-col">
          <p class="svc-header-sub">From brand identity to digital growth — every service designed to move the needle
            for your business.</p>
          <a href="#" onclick="event.preventDefault();openProjectForm()" class="svc-header-link">DISCUSS YOUR PROJECT →</a>
        </div>
      </div>

<?php if (count($services)): ?>
      <!-- Accordion List -->
      <div class="svc-list">
        <?php $si = 0; foreach ($services as $s): ?>
        <?php
          $tags = json_decode($s['hashtags'] ?? '[]', true);
          if (!is_array($tags)) $tags = array_filter(array_map('trim', explode(',', $s['hashtags'] ?? '')));
          $features = json_decode($s['features'] ?? '[]', true);
          if (!is_array($features)) $features = array_filter(array_map('trim', explode("\n", $s['features'] ?? '')));
        ?>
        <div class="svc-item" data-index="<?= $si ?>">
          <div class="svc-trigger">
            <div class="svc-left">
              <span class="svc-num"><?= str_pad($si + 1, 2, '0', STR_PAD_LEFT) ?></span>
              <h3 class="svc-title"><?= h($s['name']) ?></h3>
            </div>
            <div class="svc-tags-row">
              <?php foreach (array_slice($tags, 0, 4) as $tag): ?>
              <span class="svc-tag"><?= h(trim($tag)) ?></span>
              <?php endforeach; ?>
            </div>
            <div class="svc-right">
              <div class="svc-icon-circle"><img src="images/Icons/service<?= (($si % 4) + 1) ?>.svg" alt="" width="48" height="48" loading="lazy"></div>
              <div class="svc-expand-btn"><svg class="svc-plus-icon" width="14" height="14" viewBox="0 0 14 14" fill="none"><line x1="7" y1="0" x2="7" y2="14" stroke="currentColor" stroke-width="2"/><line x1="0" y1="7" x2="14" y2="7" stroke="currentColor" stroke-width="2"/></svg></div>
            </div>
          </div>
          <div class="svc-body">
            <div class="svc-body-inner">
              <div class="svc-desc-col">
                <p class="svc-desc"><?= h($s['description']) ?></p>
                <ul class="svc-features">
                  <?php foreach ($features as $f): ?>
                  <li><?= h(trim($f)) ?></li>
                  <?php endforeach; ?>
                </ul>
                <div class="svc-action-row">
                  <div class="svc-price"><span class="svc-price-from">Starting from</span><span class="svc-price-amount"><?= h($s['price'] ?: 'Contact us') ?></span></div>
                  <a href="#" onclick="event.preventDefault();openProjectForm()" class="svc-cta-btn">START THIS PROJECT →</a>
                </div>
              </div>
              <div class="svc-visual-panel desktop-only">
                <div>
                  <div class="panel-icon"><img src="images/Icons/service<?= (($si % 4) + 1) ?>.svg" alt="" width="48" height="48" loading="lazy"></div>
                  <div class="panel-stat">50+</div>
                  <div class="panel-stat-label">Clients Served</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php $si++; endforeach; ?>
      </div>
<?php endif; ?>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       SECTION 8 — PORTFOLIO (Bento Grid)
  ══════════════════════════════════════════ -->
  <section id="portfolio" class="p-section overflow-safe" data-animate>
    <div class="container-xl">

      <div class="brands-pill">
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
      <!-- Filter Tabs -->
      <div class="p-filters">
        <span class="p-filter active" data-filter="all">All</span>
        <?php foreach ($allCats as $cat): ?>
        <span class="p-filter" data-filter="<?= h($cat) ?>"><?= h(ucfirst($cat)) ?></span>
        <?php endforeach; ?>
      </div>

      <!-- Bento Grid -->
      <div class="p-bento-grid">

        <?php
        $patIdx = 0; $patPos = 0;
        $rowPatterns = [
          [[6, 1, 280], [6, 1, 280]],
          [[4, 1, 220], [4, 1, 220], [4, 1, 220]],
        ];
        ?>
        <?php foreach ($portfolios as $p): ?>
        <?php $pCat = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p['service'] ?? '')); ?>
        <?php
          $row = $rowPatterns[$patIdx % count($rowPatterns)];
          $s = $row[$patPos];
          $patPos++;
          if ($patPos >= count($row)) { $patIdx++; $patPos = 0; }
          $detailSlug = !empty($p['slug']) ? 'portfolio?slug=' . urlencode($p['slug']) : 'portfolio?slug=' . $p['id'];
        ?>
        <a href="<?= $detailSlug ?>" class="p-card" style="grid-column:span <?= $s[0] ?>;grid-row:span <?= $s[1] ?>;min-height:<?= $s[2] ?>px" data-category="<?= h($pCat) ?>">
          <div class="p-card-bg"><?php if ($p['image_url']): ?><img src="<?= h($p['image_url']) ?>" alt="" class="p-card-img" loading="lazy"><?php endif; ?>
          </div>
          <div class="p-card-overlay">
            <span class="p-badge"><?= h(strtoupper($p['service'] ?? 'PROJECT')) ?></span>
            <div class="p-card-info">
              <h3 class="p-card-title"><?= h($p['project_name']) ?></h3>
              <?php if ($p['client']): ?><p class="p-card-sub"><?= h($p['client']) ?></p><?php endif; ?>
            </div>
            <div class="p-view-btn"><span>↗</span></div>
          </div>
        </a>
        <?php endforeach; ?>

        <!-- CTA Card (full 12-col) -->
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
<?php endif; ?>
      <div class="see-more-wrap">
        <a href="portfolio" class="see-more-link">SEE MORE PROJECTS →</a>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       SECTION 9 — PRICING PACKAGES
  ══════════════════════════════════════════ -->
  <section id="pricing" class="pricing-section overflow-safe">
    <div class="pricing-header" data-animate>
      <div class="t-label-pill">
        <span class="t-label-dot"></span>
        <span class="t-label-text">TRANSPARENT PRICING</span>
      </div>
      <h2 class="pricing-headline">PACKAGES<br><span class="brand-line">FOR EVERY STAGE</span></h2>
      <p class="pricing-sub">No hidden fees. No surprises. Choose a package or get a custom quote for your project.</p>
      <div class="pricing-toggle">
        <span class="pricing-toggle-btn active">ONE-TIME PROJECT</span>
        <span class="pricing-toggle-btn">MONTHLY RETAINER</span>
      </div>
    </div>

<?php if (count($packages)): ?>
    <div class="pricing-carousel" data-animate>
      <div class="pricing-track" id="pricingTrack">

      <?php foreach ($packages as $pkg): ?>
      <?php
        $pkgFeatures = json_decode($pkg['features'] ?? '[]', true);
        if (!is_array($pkgFeatures)) $pkgFeatures = array_filter(array_map('trim', explode("\n", $pkg['features'] ?? '')));
        $isPopular = ($pkg['tier'] ?? '') === 'popular';
      ?>
      <div class="pricing-card<?= $isPopular ? ' pricing-popular' : '' ?>" data-billing-cycle="<?= h($pkg['billing_cycle'] ?? 'one-time') ?>">
        <?php if ($isPopular): ?><div class="pricing-popular-badge">MOST POPULAR</div><?php endif; ?>
        <div class="pricing-card-top">
          <div class="pricing-name"><?= h($pkg['name']) ?></div>
          <div class="pricing-price-row">
            <span class="pricing-amount<?= $pkg['price'] === 'CUSTOM' ? ' pricing-amount-sm' : '' ?>"><?= h($pkg['price']) ?><?= ($pkg['billing_cycle'] ?? '') === 'monthly' ? '<span class="pricing-period" style="font-size:1rem;font-weight:400;color:var(--text3)">/mo</span>' : '' ?></span>
            <span class="pricing-from"><?= $pkg['price'] === 'CUSTOM' ? 'tailored to you' : 'starting from' ?></span>
          </div>
          <p class="pricing-tagline"><?= h($pkg['tagline'] ?? '') ?></p>
        </div>
        <div class="pricing-divider"></div>
        <ul class="pricing-features">
          <?php foreach ($pkgFeatures as $f): ?>
          <?php $isYes = !str_starts_with(trim($f), '✗') && !str_starts_with(trim($f), 'x'); ?>
          <li><span class="<?= $isYes ? 'pf-yes' : 'pf-no' ?>"><?= $isYes ? '✓' : '✗' ?></span> <?= h(trim(ltrim(trim($f), '✓✗'))) ?></li>
          <?php endforeach; ?>
        </ul>
        <a href="#" onclick="event.preventDefault();openProjectForm()" class="pricing-cta<?= $isPopular ? ' pricing-cta-primary' : '' ?>"><?= $isPopular ? 'START GROWING →' : ($pkg['price'] === 'CUSTOM' ? 'GET A QUOTE →' : 'GET STARTED →') ?></a>
      </div>
      <?php endforeach; ?>

      </div>
      <button class="carousel-arrow carousel-arrow-left" id="pricingPrev" aria-label="Previous">‹</button>
      <button class="carousel-arrow carousel-arrow-right" id="pricingNext" aria-label="Next">›</button>
    </div>
    <div class="pricing-dots" id="pricingDots"></div>
    <div class="see-more-wrap">
      <a href="services" class="see-more-link">SEE ALL SERVICES →</a>
    </div>
<?php endif; ?>

  </section>

  <!-- ══════════════════════════════════════════
       SECTION 10 — TESTIMONIALS (Focus Carousel)
  ══════════════════════════════════════════ -->
<?php if (count($testimonials)): ?>
  <section id="testimonials" class="t-section overflow-safe" data-animate>

    <div class="t-glow-line t-glow-top"></div>
    <div class="t-glow-line t-glow-bottom"></div>
    <div class="t-radial-glow"></div>

    <div class="t-header">
      <div class="t-label-pill">
        <span class="t-label-dot"></span>
        <span class="t-label-text">REAL FEEDBACK</span>
      </div>
      <h2 class="t-headline">
        CLIENT
        <span class="t-headline-outline">SUCCESS</span>
        STORIES
      </h2>
      <p class="t-subtext">
        Brands across Bangladesh and beyond — real words,
        real results, real growth.
      </p>
    </div>

    <div class="t-carousel-outer">
      <div class="t-carousel-track" id="tCarouselTrack"></div>
    </div>

    <div class="t-nav">
      <button class="t-arrow t-arrow-prev" id="tPrev" aria-label="Previous">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
          stroke-linecap="round">
          <path d="M19 12H5M12 5l-7 7 7 7" />
        </svg>
      </button>
      <div class="t-dots" id="tDots"></div>
      <button class="t-arrow t-arrow-next" id="tNext" aria-label="Next">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
          stroke-linecap="round">
          <path d="M5 12h14M12 5l7 7-7 7" />
        </svg>
      </button>
    </div>

  </section>
<?php endif; ?>

  <!-- ══════════════════════════════════════════
       SECTION 11 — BLOG
  ══════════════════════════════════════════ -->
  <section id="blog" class="blog-section overflow-safe">
    <div class="blog-header" data-animate>
      <div class="brands-pill">
        <span class="brands-pill-dot"></span>
        <span class="brands-pill-text">OUR LATEST BLOG AND NEWS</span>
      </div>
      <h2 class="blog-headline"><span class="brand-line">STAY</span> INFORMED</h2>
    </div>
<?php if (count($blogs)): ?>
    <div class="blog-carousel" data-animate>
      <div class="blog-track" id="blogTrack">

      <?php $bi = 0; foreach ($blogs as $post): ?>
      <?php $bi++; ?>
      <article class="blog-card" data-animate data-href="post/<?= h($post['slug']) ?>">
        <div class="blog-img-wrap">
          <img src="<?= h(image_url($post['featured_image'] ?: 'images/placeholder.svg')) ?>" alt="<?= h($post['title']) ?>" loading="lazy">
          <div class="blog-img-overlay blog-overlay-<?= $bi ?>"></div>
          <div class="blog-author-badge">
            <img src="images/Icons/user1.svg" alt="" loading="lazy">
            XOOS DIGITAL
          </div>
        </div>
        <div class="blog-body">
          <div class="blog-date">
            <img src="images/Icons/date1.svg" alt="" loading="lazy">
            <span><?= strtoupper(date('d M Y', strtotime($post['created_at']))) ?></span>
          </div>
          <h3><?= h($post['title']) ?></h3>
          <a href="post/<?= h($post['slug']) ?>" class="blog-read-more">READ MORE →</a>
        </div>
      </article>
      <?php endforeach; ?>

      </div>
      <button class="carousel-arrow carousel-arrow-left" id="blogPrev" aria-label="Previous">‹</button>
      <button class="carousel-arrow carousel-arrow-right" id="blogNext" aria-label="Next">›</button>
    </div>
    <div class="blog-dots" id="blogDots"></div>
    <div class="see-more-wrap">
      <a href="blog" class="see-more-link">READ ALL ARTICLES →</a>
    </div>
<?php endif; ?>
  </section>

  <!-- ══════════════════════════════════════════
       SECTION 12 — FAQ
  ══════════════════════════════════════════ -->
  <section id="faq" class="faq-section overflow-safe" data-animate>
    <div class="faq-layout">

      <!-- Left column (sticky header) -->
      <div class="faq-left">
        <div class="t-label-pill">
          <span class="t-label-dot"></span>
          <span class="t-label-text">FAQ</span>
        </div>
        <h2 class="faq-headline"><span class="brand-line">QUESTIONS</span> WE GET ALL THE TIME</h2>
        <p class="faq-sub">Can't find your answer? <a href="#contact" class="faq-contact-link">Just ask us directly
            →</a>
        </p>
        <div class="faq-deco">?</div>
      </div>

<?php if (count($faqs)): ?>
      <!-- Right column (accordion) -->
      <div class="faq-right">
        <?php $fi = 0; foreach ($faqs as $f): ?>
        <?php $fi++; ?>
        <div class="faq-item<?= $fi === 1 ? ' active' : '' ?>">
          <button class="faq-trigger">
            <span><?= h($f['question']) ?></span>
            <span class="faq-icon">+</span>
          </button>
          <div class="faq-answer<?= $fi === 1 ? ' faq-answer-open' : '' ?>">
            <?= h($f['answer']) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
<?php endif; ?>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       SECTION 13 — CTA / CONTACT FORM
  ══════════════════════════════════════════ -->
  <section id="contact-form" class="cta-section overflow-safe" data-animate>
    <div class="cta-layout">

      <!-- Left: Contact info -->
      <div class="cta-info">
        <div class="t-label-pill">
          <span class="t-label-dot"></span>
          <span class="t-label-text">START A PROJECT</span>
        </div>
        <h2 class="cta-headline"><span class="brand-line">READY TO BUILD</span> SOMETHING GREAT?</h2>
        <p class="cta-sub">Tell us about your project and we'll get back to you within 24 hours with a clear plan and
          honest quote.</p>

        <div class="cta-details">
          <div class="cta-detail-row">
            <img src="images/Icons/phn2.svg" alt="" class="cta-icon-img" loading="lazy">
            <div>
              <div class="cta-detail-label">PHONE</div>
              <div class="cta-detail-value"><?= h(get_setting('contact_phone', '+8801572932943')) ?></div>
            </div>
          </div>
          <div class="cta-detail-row">
            <img src="images/Icons/mail2.svg" alt="" class="cta-icon-img" loading="lazy">
            <div>
              <div class="cta-detail-label">EMAIL</div>
              <div class="cta-detail-value"><?= h(get_setting('contact_email', 'xoosdigital@gmail.com')) ?></div>
            </div>
          </div>
          <div class="cta-detail-row">
            <img src="images/Icons/location1.svg" alt="" class="cta-icon-img" loading="lazy">
            <div>
              <div class="cta-detail-label">LOCATION</div>
              <div class="cta-detail-value"><?= h(get_setting('address', 'Khilgaon, Dhaka, Bangladesh')) ?></div>
            </div>
          </div>
        </div>

        <div class="cta-availability">
          <span class="cta-pulse-dot"></span>
          <span>Currently Accepting New Projects</span>
        </div>

        <div class="cta-social-proof">
          <p>Join <?= h(get_setting('stat_clients', '80')) ?>+ brands who chose Xoos Digital</p>
          <div class="cta-platform-badges">
            <span class="cta-platform-badge">★ 4.9 Fiverr</span>
            <span class="cta-platform-badge">★ 5.0 Upwork</span>
          </div>
        </div>
      </div>

      <!-- Right: Contact form -->
      <div class="cta-form-wrap">
        <form class="cta-form" id="contactForm">
          <!-- Web3Forms hidden fields -->
          <input type="hidden" name="access_key" value="<?= WEB3FORMS_ACCESS_KEY ?>">
          <input type="hidden" name="subject" value="New Project Inquiry - Xoos Digital">
          <input type="hidden" name="from_name" value="Xoos Digital Website">
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label" for="cf-name">NAME</label>
              <input id="cf-name" type="text" name="name" class="form-input" placeholder="Your full name" required autocomplete="name">
            </div>
            <div class="form-group">
              <label class="form-label" for="cf-email">EMAIL</label>
              <input id="cf-email" type="email" name="email" class="form-input" placeholder="your@email.com" required autocomplete="email">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="cf-service">SERVICE</label>
            <select id="cf-service" name="service" class="form-input form-select">
              <option value="" disabled selected>Select a service...</option>
              <option>Creative Branding & Logo Design</option>
              <option>WordPress Website Development</option>
              <option>WooCommerce / E-Commerce Store</option>
              <option>Digital Marketing Campaign</option>
              <option>SEO & Organic Growth</option>
              <option>Video Production</option>
              <option>Full Package (Multiple Services)</option>
              <option>Other / Not Sure Yet</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">BUDGET</label>
            <select id="cf-budget" name="budget" class="form-input form-select">
              <option value="" disabled selected>Select your budget range...</option>
              <option>Under $300</option>
              <option>$300 – $500</option>
              <option>$500 – $1,000</option>
              <option>$1,000 – $3,000</option>
              <option>$3,000+</option>
              <option>Not sure — need a quote</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="cf-message">MESSAGE</label>
            <textarea id="cf-message" name="message" class="form-input form-textarea" rows="4"
              placeholder="Tell us about your project, brand, and goals..."></textarea>
          </div>
          <button type="submit" class="form-submit" id="cf-submit">
            <span class="cf-btn-text">SEND MESSAGE &#8594;</span>
            <span class="cf-btn-loading" style="display:none">
              <svg class="cf-spinner" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
              </svg>
              SENDING...
            </span>
          </button>
        </form>
        <p class="form-footnote">Or reach us directly on <a href="https://wa.me/<?= h(get_setting('whatsapp_number', '8801572932943')) ?>" target="_blank"
            rel="noopener noreferrer">WhatsApp</a> · We respond within 24 hours</p>
      </div>

    </div>
  </section>

  <!-- ══════════════════════════════════════════
       SUCCESS MODAL
  ══════════════════════════════════════════ -->
  <div id="successModal" class="success-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="success-title">
    <div class="success-modal-backdrop"></div>
    <div class="success-modal-card">
      <!-- Floating particles -->
      <div class="success-particles">
        <span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span>
      </div>

      <!-- Animated checkmark -->
      <div class="success-icon-wrap">
        <svg class="success-check" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle class="success-check-circle" cx="26" cy="26" r="24" stroke="url(#successGrad)" stroke-width="2.5"/>
          <path class="success-check-tick" d="M14 27l8 8 16-16" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
          <defs>
            <linearGradient id="successGrad" x1="0" y1="0" x2="52" y2="52" gradientUnits="userSpaceOnUse">
              <stop stop-color="#a78bfa"/>
              <stop offset="1" stop-color="#60a5fa"/>
            </linearGradient>
          </defs>
        </svg>
        <div class="success-icon-glow"></div>
      </div>

      <p class="success-pill">MESSAGE RECEIVED ✦</p>
      <h3 id="success-title" class="success-title">You're All Set!</h3>
      <p class="success-body">Thank you for reaching out. Our team will review your project details and get back to you within <strong>24 hours</strong> with a clear plan and honest quote.</p>

      <div class="success-divider"></div>

      <div class="success-next-steps">
        <div class="success-step">
          <div class="success-step-num">01</div>
          <p>We'll review your requirements</p>
        </div>
        <div class="success-step">
          <div class="success-step-num">02</div>
          <p>You'll get a personalised proposal</p>
        </div>
        <div class="success-step">
          <div class="success-step-num">03</div>
          <p>We kick off your project together</p>
        </div>
      </div>

      <div class="success-actions">
        <a href="https://wa.me/<?= h(get_setting('whatsapp_number', '8801572932943')) ?>" target="_blank" rel="noopener noreferrer" class="success-wa-btn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.982 0C5.377 0 0 5.364 0 11.955c0 2.128.559 4.121 1.533 5.847L0 24l6.335-1.652A11.932 11.932 0 0011.982 24c6.605 0 11.982-5.364 11.982-11.955S18.587 0 11.982 0zm0 21.818a9.762 9.762 0 01-4.952-1.344l-.355-.211-3.755.979.998-3.65-.232-.374a9.724 9.724 0 01-1.502-5.218c0-5.393 4.409-9.786 9.798-9.786 5.39 0 9.798 4.393 9.798 9.786 0 5.394-4.408 9.818-9.798 9.818z"/></svg>
          Chat on WhatsApp
        </a>
        <button class="success-close-btn" id="successCloseBtn">CLOSE ✕</button>
      </div>
    </div>
  </div>

<?php require __DIR__ . '/inc/footer.php'; ?>

  <script>
  /* ─── Web3Forms Contact Form ─── */
  (function () {
    const form      = document.getElementById('contactForm');
    const submitBtn = document.getElementById('cf-submit');
    const btnText   = submitBtn.querySelector('.cf-btn-text');
    const btnLoad   = submitBtn.querySelector('.cf-btn-loading');
    const modal     = document.getElementById('successModal');
    const closeBtn  = document.getElementById('successCloseBtn');

    function showModal() {
      modal.classList.add('is-visible');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      modal.querySelectorAll('.success-particles span').forEach((el, i) => {
        el.style.animationDelay = (i * 0.12) + 's';
      });
      closeBtn.focus();
    }

    function hideModal() {
      modal.classList.remove('is-visible');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    closeBtn.addEventListener('click', hideModal);
    modal.querySelector('.success-modal-backdrop').addEventListener('click', hideModal);
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && modal.classList.contains('is-visible')) hideModal();
    });

    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      btnText.style.display = 'none';
      btnLoad.style.display = 'inline-flex';
      submitBtn.disabled = true;

      const data = new FormData(form);

      try {
        const res  = await fetch('https://api.web3forms.com/submit', {
          method : 'POST',
          body   : data
        });
        const json = await res.json();

        if (json.success) {
          form.reset();
          showModal();
        } else {
          alert('Something went wrong: ' + (json.message || 'Please try again.'));
        }
      } catch (err) {
        alert('Network error. Please check your connection and try again.');
      } finally {
        btnText.style.display = 'inline';
        btnLoad.style.display = 'none';
        submitBtn.disabled = false;
      }
    });
  })();
  </script>

<?php
require __DIR__ . '/inc/project-form.php';
require __DIR__ . '/inc/chatbot.php';
require __DIR__ . '/inc/scripts.php';
page_cache_end();
?>
