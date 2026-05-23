<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';

$pageTitle = 'Contact Us | Xoos Digital';
$pageDesc = 'Get in touch with Xoos Digital. Start your project, ask a question, or just say hello. We\'d love to hear from you.';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/navbar.php';
?>

<section class="page-hero">
  <div class="page-hero-content">
    <div class="t-label-pill"><span class="t-label-dot"></span><span class="t-label-text">GET IN TOUCH</span></div>
    <h1 class="page-hero-title">LET'S <span class="brand-line">CONNECT</span></h1>
    <p class="page-hero-sub">Have a project in mind? Fill out the form and we'll get back to you within 24 hours.</p>
  </div>
</section>

<section class="cta-section overflow-safe" data-animate style="border-bottom:none">
  <div class="cta-layout">
    <div class="cta-info">
      <div class="t-label-pill">
        <span class="t-label-dot"></span>
        <span class="t-label-text">CONTACT INFO</span>
      </div>
      <h2 class="cta-headline">LET'S BUILD <span class="brand-line">SOMETHING GREAT</span> TOGETHER</h2>
      <p class="cta-sub">Tell us about your project and we'll get back to you within 24 hours with a clear plan and honest quote.</p>
      <div class="cta-details">
        <div class="cta-detail-row">
          <img src="Images/Icons/mail2.svg" alt="" class="cta-icon-img" loading="lazy">
          <div>
            <div class="cta-detail-label">EMAIL</div>
            <div class="cta-detail-value"><?= h(get_setting('contact_email', 'xoosdigital@gmail.com')) ?></div>
          </div>
        </div>
        <div class="cta-detail-row">
          <img src="Images/Icons/phn2.svg" alt="" class="cta-icon-img" loading="lazy">
          <div>
            <div class="cta-detail-label">PHONE / WHATSAPP</div>
            <div class="cta-detail-value"><?= h(get_setting('contact_phone', '+8801572932943')) ?></div>
          </div>
        </div>
        <div class="cta-detail-row">
          <img src="Images/Icons/location1.svg" alt="" class="cta-icon-img" loading="lazy">
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
      <div style="margin-top:1.5rem;display:flex;gap:0.75rem;flex-wrap:wrap">
        <a href="<?= h(get_setting('facebook_url', 'https://facebook.com/xoosdigital')) ?>" target="_blank" rel="noopener noreferrer" class="cta-platform-badge" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px">Facebook</a>
        <a href="<?= h(get_setting('linkedin_url', 'https://linkedin.com/in/ri-raihanislam99')) ?>" target="_blank" rel="noopener noreferrer" class="cta-platform-badge" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px">LinkedIn</a>
        <a href="<?= h(get_setting('instagram_url', '#')) ?>" target="_blank" rel="noopener noreferrer" class="cta-platform-badge" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px">Instagram</a>
      </div>
    </div>

    <div class="cta-form-wrap">
      <form class="cta-form" id="contactForm">
        <input type="hidden" name="access_key" value="<?= WEB3FORMS_ACCESS_KEY ?>">
        <input type="hidden" name="subject" value="New Contact - Xoos Digital">
        <input type="hidden" name="from_name" value="Xoos Digital Website">
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="cf-name">NAME <span style="color:#CCFF00">*</span></label>
            <input id="cf-name" type="text" name="name" class="form-input" placeholder="Your full name" required autocomplete="name">
          </div>
          <div class="form-group">
            <label class="form-label" for="cf-email">EMAIL <span style="color:#CCFF00">*</span></label>
            <input id="cf-email" type="email" name="email" class="form-input" placeholder="your@email.com" required autocomplete="email">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="cf-phone">PHONE / WHATSAPP</label>
            <input id="cf-phone" type="tel" name="phone" class="form-input" placeholder="+1 234 567 8900">
          </div>
          <div class="form-group">
            <label class="form-label" for="cf-subject">SUBJECT</label>
            <input id="cf-subject" type="text" name="subject" class="form-input" placeholder="What's this about?">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="cf-message">MESSAGE <span style="color:#CCFF00">*</span></label>
          <textarea id="cf-message" name="message" class="form-input form-textarea" rows="5" placeholder="Tell us about your project, question, or idea..." required></textarea>
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
    <div class="success-particles">
      <span></span><span></span><span></span><span></span>
      <span></span><span></span><span></span><span></span>
    </div>
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
    modal.querySelectorAll('.success-particles span').forEach(function(el, i) {
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
