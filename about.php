<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';

$testimonials = [];
$brands = [];
try {
    $testimonials = get_all('testimonials', 'sort_order ASC');
    $brands = get_all('brands', 'sort_order ASC');
} catch (Exception $e) {}

$founderName  = get_setting('founder_name', 'Raihan Islam');
$founderTitle = get_setting('founder_title', 'Founder & Creative Director');
$founderBio   = get_setting('founder_bio', '');
$contactEmail = get_setting('contact_email', 'xoosdigital@gmail.com');
$contactPhone = get_setting('contact_phone', '+8801572932943');
$statBrands   = get_setting('stat_brands', '50');
$statWebsites = get_setting('stat_websites', '30');
$statClients  = get_setting('stat_clients', '80');
$statYears    = get_setting('stat_years', '5');

$pageTitle = 'About Us | Xoos Digital';
$pageDesc = 'Learn about Xoos Digital — a creative agency. Meet our founder ' . $founderName . ' and discover our process, stats, and client success stories.';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/navbar.php';
?>

<section class="page-hero">
  <div class="page-hero-content">
    <div class="t-label-pill"><span class="t-label-dot"></span><span class="t-label-text">ABOUT US</span></div>
    <h1 class="page-hero-title">We Are <span class="brand-line">Xoos Digital</span></h1>
    <p class="page-hero-sub">A creative agency delivering smart digital solutions — from branding and web development to marketing and video production — for clients worldwide.</p>
  </div>
</section>

<section id="about-founder" class="about-section overflow-safe">
  <div class="section-container">
    <div class="founder-grid">
      <div class="founder-img-wrap" data-animate="slide-left">
        <img src="images/founder-photo-Raihan-1.jpg" alt="<?= h($founderName) ?> — Founder, Xoos Digital" loading="lazy">
        <div class="founder-img-glow"></div>
      </div>
      <div class="founder-content" data-animate="slide-right">
        <div class="brands-pill">
          <span class="brands-pill-dot"></span>
          <span class="brands-pill-text"><?= h(strtoupper($founderTitle)) ?></span>
        </div>
        <h2 class="founder-headline">HI, I'M <span class="brand-line"><?= h(strtoupper($founderName)) ?></span></h2>
        <?php if ($founderBio): ?>
        <p class="founder-body"><?= h($founderBio) ?></p>
        <?php else: ?>
        <p class="founder-body">Creative Director & Digital Strategist with a passion for building brands that leave a lasting impact. With years of experience across branding, web development, and digital marketing, I founded Xoos Digital to help businesses tell their story — and tell it well.</p>
        <p class="founder-body">Every brand has a unique story. My mission is to bring that story to life through bold design, smart strategy, and measurable results. Whether you're a startup looking to launch or an established brand ready to level up, I'm here to help.</p>
        <?php endif; ?>
        <div class="skill-item skill-item-first" data-animate>
          <div class="skill-label-row">
            <span>Brand Strategy</span>
            <span>95%</span>
          </div>
          <div class="skill-track">
            <div class="skill-fill" data-width="95"></div>
          </div>
        </div>
        <div class="skill-item" data-animate>
          <div class="skill-label-row">
            <span>Web Development</span>
            <span>90%</span>
          </div>
          <div class="skill-track">
            <div class="skill-fill" data-width="90"></div>
          </div>
        </div>
        <div class="skill-item" data-animate>
          <div class="skill-label-row">
            <span>Digital Marketing</span>
            <span>88%</span>
          </div>
          <div class="skill-track">
            <div class="skill-fill" data-width="88"></div>
          </div>
        </div>
        <div class="skill-item" data-animate>
          <div class="skill-label-row">
            <span>Video Production</span>
            <span>82%</span>
          </div>
          <div class="skill-track">
            <div class="skill-fill" data-width="82"></div>
          </div>
        </div>
        <a href="https://ri.xoosdigital.com" target="_blank" rel="noopener noreferrer" class="founder-cta">View Raihan's Portfolio →</a>
      </div>
    </div>
  </div>
</section>

<section class="stats-bar overflow-safe">
  <div class="stats-grid">
    <div class="stat-item" data-animate>
      <div class="stat-number"><span data-count="<?= (int)$statBrands ?>" data-suffix="+">0</span></div>
      <div class="stat-label">Brands Designed</div>
    </div>
    <div class="stat-item" data-animate>
      <div class="stat-number"><span data-count="<?= (int)$statWebsites ?>" data-suffix="+">0</span></div>
      <div class="stat-label">Websites Built</div>
    </div>
    <div class="stat-item" data-animate>
      <div class="stat-number"><span data-count="<?= (int)$statClients ?>" data-suffix="+">0</span></div>
      <div class="stat-label">Happy Clients</div>
    </div>
    <div class="stat-item" data-animate>
      <div class="stat-number"><span data-count="<?= (int)$statYears ?>" data-suffix="+">0</span></div>
      <div class="stat-label">Years Experience</div>
    </div>
  </div>
</section>

<section id="process" class="process-section overflow-safe">
  <div class="process-header" data-animate>
    <div class="t-label-pill">
      <span class="t-label-dot"></span>
      <span class="t-label-text">HOW WE WORK</span>
    </div>
    <h2 class="process-headline">YOUR VISION<br><span class="brand-line">INTO REALITY</span></h2>
    <p class="process-sub">A clear, collaborative process — so you always know what's happening and what comes next.</p>
  </div>
  <div class="process-timeline">
    <div class="process-step process-step-left">
      <div class="process-card">
        <div class="process-time">Within 24 hours</div>
        <h3 class="process-title">DISCOVERY & BRIEFING</h3>
        <p class="process-desc">We start with a deep dive into your brand, goals, target audience, and competition. A detailed brief ensures we're aligned from day one — no guesswork, no surprises.</p>
        <ul class="process-list">
          <li>✓ Brand discovery call</li>
          <li>✓ Competitor analysis</li>
          <li>✓ Goal setting & KPIs</li>
        </ul>
      </div>
      <div class="process-node">01</div>
    </div>
    <div class="process-step process-step-right">
      <div class="process-card">
        <div class="process-time">Days 2-3</div>
        <h3 class="process-title">STRATEGY & PLANNING</h3>
        <p class="process-desc">We build a tailored strategy — design direction, content plan, technical roadmap, or marketing blueprint depending on your service. You approve before we build a single pixel.</p>
        <ul class="process-list">
          <li>✓ Creative direction deck</li>
          <li>✓ Project timeline</li>
          <li>✓ Milestone sign-off</li>
        </ul>
      </div>
      <div class="process-node">02</div>
    </div>
    <div class="process-step process-step-left">
      <div class="process-card">
        <div class="process-time">Days 4-14</div>
        <h3 class="process-title">DESIGN, BUILD & PRODUCE</h3>
        <p class="process-desc">This is where the magic happens. Our team executes with precision — whether that's crafting a brand identity, building a website, or producing a campaign. Regular updates keep you in the loop.</p>
        <ul class="process-list">
          <li>✓ Design iterations</li>
          <li>✓ 2-3 revision rounds</li>
          <li>✓ Progress check-ins</li>
        </ul>
      </div>
      <div class="process-node">03</div>
    </div>
    <div class="process-step process-step-right">
      <div class="process-card">
        <div class="process-time">Day 14-21</div>
        <h3 class="process-title">LAUNCH & GROW</h3>
        <p class="process-desc">We deliver, launch, and don't disappear. Post-launch support, performance tracking, and growth optimization are part of how we work. Your success is our reputation.</p>
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

<?php if (count($brands)): ?>
<section id="brands" class="brands-section overflow-safe" data-animate>
  <div class="brands-atmos-bg"></div>
  <div class="brands-edge-top"></div>
  <div class="brands-edge-bottom"></div>
  <div class="brands-glass-header">
    <div class="brands-pill">
      <span class="brands-pill-dot"></span>
      <span class="brands-pill-text">TRUSTED BY LEADING BRANDS</span>
    </div>
    <h2 class="brands-glass-headline">BRANDS WE <span class="brands-headline-outline">WORK WITH</span></h2>
    <p class="brands-glass-sub">From startups to international businesses — brands that chose to grow with Xoos Digital.</p>
  </div>
  <div class="brands-carousel-outer">
    <div class="brands-carousel-track" id="brandsTrack"></div>
  </div>
  <div class="brands-nav" id="brandsNav">
    <button class="brands-arrow" id="brandsPrev" aria-label="Previous brand">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </button>
    <div class="brands-dots" id="brandsDots"></div>
    <button class="brands-arrow" id="brandsNext" aria-label="Next brand">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </button>
  </div>
</section>
<script>window.brandsData = <?= json_encode($brands) ?>;</script>
<?php endif; ?>

<?php if (count($testimonials)): ?>
<section id="testimonials" class="t-section overflow-safe" data-animate>
  <div class="t-glow-line t-glow-top"></div>
  <div class="t-glow-line t-glow-bottom"></div>
  <div class="t-radial-glow"></div>
  <div class="t-header">
    <div class="t-label-pill"><span class="t-label-dot"></span><span class="t-label-text">CLIENT SUCCESS</span></div>
    <h2 class="t-headline">WHAT OUR <span class="t-headline-outline">CLIENTS SAY</span></h2>
    <p class="t-subtext">Real words, real results, real growth.</p>
  </div>
  <div class="t-carousel-outer">
    <div class="t-carousel-track" id="tCarouselTrack"></div>
  </div>
  <div class="t-nav">
    <button class="t-arrow t-arrow-prev" id="tPrev" aria-label="Previous">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </button>
    <div class="t-dots" id="tDots"></div>
    <button class="t-arrow t-arrow-next" id="tNext" aria-label="Next">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </button>
  </div>
</section>
<script>window.testimonialsData = <?= json_encode($testimonials) ?>;</script>
<?php endif; ?>

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
            <div class="cta-detail-value"><?= h($contactPhone) ?></div>
          </div>
        </div>
        <div class="cta-detail-row">
          <img src="images/Icons/mail2.svg" alt="" class="cta-icon-img" loading="lazy">
          <div>
            <div class="cta-detail-label">EMAIL</div>
            <div class="cta-detail-value"><?= h($contactEmail) ?></div>
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

<div id="successModal" class="success-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="success-title">
  <div class="success-modal-backdrop"></div>
  <div class="success-modal-card">
    <div class="success-particles"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
    <div class="success-icon-wrap">
      <svg class="success-check" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle class="success-check-circle" cx="26" cy="26" r="24" stroke="url(#successGrad)" stroke-width="2.5"/>
        <path class="success-check-tick" d="M14 27l8 8 16-16" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <defs><linearGradient id="successGrad" x1="0" y1="0" x2="52" y2="52" gradientUnits="userSpaceOnUse"><stop stop-color="#a78bfa"/><stop offset="1" stop-color="#60a5fa"/></linearGradient></defs>
      </svg>
      <div class="success-icon-glow"></div>
    </div>
    <p class="success-pill">MESSAGE RECEIVED ✦</p>
    <h3 id="success-title" class="success-title">You're All Set!</h3>
    <p class="success-body">Thank you for reaching out. Our team will review your message and get back to you within <strong>24 hours</strong>.</p>
    <div class="success-divider"></div>
    <div class="success-actions">
      <a href="https://wa.me/<?= h(get_setting('whatsapp_number', '8801572932943')) ?>" target="_blank" rel="noopener noreferrer" class="success-wa-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.982 0C5.377 0 0 5.364 0 11.955c0 2.128.559 4.121 1.533 5.847L0 24l6.335-1.652A11.932 11.932 0 0011.982 24c6.605 0 11.982-5.364 11.982-11.955S18.587 0 11.982 0zm0 21.818a9.762 9.762 0 01-4.952-1.344l-.355-.211-3.755.979.998-3.65-.232-.374a9.724 9.724 0 01-1.502-5.218c0-5.393 4.409-9.786 9.798-9.786 5.39 0 9.798 4.393 9.798 9.786 0 5.394-4.408 9.818-9.798 9.818z"/></svg>
        Chat on WhatsApp
      </a>
      <button class="success-close-btn" id="successCloseBtn">CLOSE ✕</button>
    </div>
  </div>
</div>

<script>
(function() {
  var form = document.getElementById('contactForm');
  var submitBtn = document.getElementById('cf-submit');
  var modal = document.getElementById('successModal');
  var closeBtn = document.getElementById('successCloseBtn');
  function showModal() {
    modal.classList.add('is-visible');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
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
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    var btnText = submitBtn.querySelector('.cf-btn-text');
    var btnLoad = submitBtn.querySelector('.cf-btn-loading');
    btnText.style.display = 'none';
    btnLoad.style.display = 'inline-flex';
    submitBtn.disabled = true;
    var data = new FormData(form);
    try {
      var formDataObj = {};
      data.forEach(function(v, k) { formDataObj[k] = v; });
      fetch('/contact-save.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams(formDataObj)
      }).catch(function(){});
      var res = await fetch('https://api.web3forms.com/submit', { method: 'POST', body: data });
      var json = await res.json();
      if (json.success) { form.reset(); showModal(); }
      else { alert('Something went wrong: ' + (json.message || 'Please try again.')); }
    } catch(err) { alert('Network error. Please check your connection and try again.'); }
    finally {
      btnText.style.display = 'inline';
      btnLoad.style.display = 'none';
      submitBtn.disabled = false;
    }
  });
})();
</script>

<?php
require __DIR__ . '/inc/footer.php';
require __DIR__ . '/inc/project-form.php';
require __DIR__ . '/inc/chatbot.php';
require __DIR__ . '/inc/scripts.php';
?>
