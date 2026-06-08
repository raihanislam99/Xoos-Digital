<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';

$services = [];
try {
    $services = get_all('services', 'sort_order ASC');
} catch (Exception $e) {}

$pageTitle = 'Services | Xoos Digital';
$pageDesc = 'Explore Xoos Digital\'s full range of digital services — branding, web development, SEO, digital marketing, video production, and more.';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/navbar.php';
?>

<section class="page-hero">
  <div class="page-hero-content">
    <div class="t-label-pill"><span class="t-label-dot"></span><span class="t-label-text">OUR SERVICES</span></div>
    <h1 class="page-hero-title">WHAT WE <span class="brand-line">DO</span></h1>
    <p class="page-hero-sub">From brand identity to digital growth — we offer end-to-end solutions to take your business to the next level.</p>
  </div>
</section>

<section class="svc-section overflow-safe" data-animate>
  <div class="svc-bg-glow"></div>
  <div class="container-xl">
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
        <p class="svc-header-sub">From brand identity to digital growth — every service designed to move the needle for your business.</p>
        <a href="#" onclick="event.preventDefault();openProjectForm()" class="svc-header-link">DISCUSS YOUR PROJECT →</a>
      </div>
    </div>

    <?php if (count($services)): ?>
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
            <div class="svc-icon-circle"><img src="images/Icons/service<?= (($si % 4) + 1) ?>.svg" alt="" loading="lazy"></div>
            <div class="svc-expand-btn"><svg class="svc-plus-icon" width="14" height="14" viewBox="0 0 14 14" fill="none"><line x1="7" y1="0" x2="7" y2="14" stroke="currentColor" stroke-width="2"/><line x1="0" y1="7" x2="14" y2="7" stroke="currentColor" stroke-width="2"/></svg></div>
          </div>
        </div>
        <div class="svc-body">
          <div class="svc-body-inner">
            <div class="svc-desc-col">
              <p class="svc-desc"><?= h($s['description']) ?></p>
              <?php if (count($features)): ?>
              <ul class="svc-features">
                <?php foreach ($features as $f): ?>
                <li><?= h(trim($f)) ?></li>
                <?php endforeach; ?>
              </ul>
              <?php endif; ?>
              <div class="svc-action-row">
                <?php if (!empty($s['price'])): ?>
                <div class="svc-price">
                  <span class="svc-price-from">Starting from</span>
                  <span class="svc-price-amount"><?= h($s['price']) ?></span>
                </div>
                <?php endif; ?>
                <a href="#" onclick="event.preventDefault();openProjectForm()" class="svc-cta-btn">START THIS PROJECT →</a>
              </div>
            </div>
            <div class="svc-visual-panel desktop-only">
              <div>
                <div class="panel-icon"><img src="images/Icons/service<?= (($si % 4) + 1) ?>.svg" alt="" loading="lazy"></div>
                <div class="panel-stat">50+</div>
                <div class="panel-stat-label">Clients Served</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php $si++; endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:4rem 2rem;color:#555;font-family:'Inter',sans-serif">
      <p>No services listed yet. Check back soon!</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="cta-section overflow-safe" data-animate>
  <div class="cta-layout">
    <div class="cta-info">
      <div class="t-label-pill">
        <span class="t-label-dot"></span>
        <span class="t-label-text">START A PROJECT</span>
      </div>
      <h2 class="cta-headline"><span class="brand-line">READY TO BUILD</span> SOMETHING GREAT?</h2>
      <p class="cta-sub">Tell us about your project and we'll get back to you within 24 hours with a clear plan and honest quote.</p>
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
      </div>
      <div class="cta-availability">
        <span class="cta-pulse-dot"></span>
        <span>Currently Accepting New Projects</span>
      </div>
    </div>
    <div class="cta-form-wrap">
      <form class="cta-form" id="contactForm">
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
          <textarea id="cf-message" name="message" class="form-input form-textarea" rows="4" placeholder="Tell us about your project, brand, and goals..."></textarea>
        </div>
        <button type="submit" class="form-submit" id="cf-submit">
          <span class="cf-btn-text">SEND MESSAGE &#8594;</span>
          <span class="cf-btn-loading" style="display:none">
            <svg class="cf-spinner" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            SENDING...
          </span>
        </button>
      </form>
      <p class="form-footnote">Or reach us directly on <a href="https://wa.me/<?= h(get_setting('whatsapp_number', '8801572932943')) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a> · We respond within 24 hours</p>
    </div>
  </div>
</section>

<?php
require __DIR__ . '/inc/footer.php';
require __DIR__ . '/inc/project-form.php';
require __DIR__ . '/inc/chatbot.php';
require __DIR__ . '/inc/scripts.php';
?>
