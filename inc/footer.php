<?php
if (!function_exists('get_setting')) {
    require_once __DIR__ . '/../admin/config.php';
    require_once __DIR__ . '/../admin/inc/functions.php';
}
$fbUrl   = get_setting('facebook_url', 'https://facebook.com/xoosdigital');
$liUrl   = get_setting('linkedin_url', 'https://linkedin.com/in/ri-raihanislam99');
$igUrl   = get_setting('instagram_url', '#');
$ctPhone = get_setting('contact_phone', '+8801572932943');
$ctAddr  = get_setting('address', 'Khilgaon, Dhaka, Bangladesh');
$ctEmail = get_setting('contact_email', 'xoosdigital@gmail.com');
$waNum   = get_setting('whatsapp_number', '8801572932943');
?>
<footer id="contact" data-animate>
    <div class="footer-mega has-corners">
      <div class="corner-tl"></div>
      <div class="corner-tr"></div>
      <div class="corner-bl"></div>
      <div class="corner-br"></div>
      <a href="contact">
        <h2>GET IN TOUCH</h2>
      </a>
    </div>
    <div class="footer-glow-line"></div>

    <div class="footer-grid-wrap">
      <div class="footer-grid">

        <div>
          <a href="."><img src="images/logo.png" alt="Xoos Digital" class="footer-logo" loading="lazy" width="119" height="36"></a>
          <p class="footer-body">Empowering brands with smart digital solutions. Your trusted partner for digital success.</p>
          <div class="social-icons">
            <a href="<?= h($fbUrl) ?>" class="social-icon" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
              <img src="images/Icons/facebook.svg" alt="Facebook" loading="lazy">
            </a>
            <a href="<?= h($liUrl) ?>" class="social-icon" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
              <img src="images/Icons/linkedin.svg" alt="LinkedIn" loading="lazy">
            </a>
            <a href="<?= h($igUrl) ?>" class="social-icon" aria-label="Instagram">
              <img src="images/Icons/instagram.svg" alt="Instagram" loading="lazy">
            </a>
          </div>
        </div>

        <div>
          <div class="footer-heading">Quick Links</div>
          <a href="about" class="footer-link">About Us</a>
          <a href="services" class="footer-link">Services</a>
          <a href="portfolio" class="footer-link">Portfolio</a>
          <a href="blog" class="footer-link">Blog</a>
          <a href="contact" class="footer-link">Contact</a>
        </div>

        <div>
          <div class="footer-heading">Contact Us</div>
          <div class="contact-item">
            <img src="images/Icons/phn2.svg" alt="" loading="lazy">
            <span><?= h($ctPhone) ?></span>
          </div>
          <div class="contact-item">
            <img src="images/Icons/location1.svg" alt="" loading="lazy">
            <span><?= h($ctAddr) ?></span>
          </div>
          <div class="contact-item">
            <img src="images/Icons/mail2.svg" alt="" loading="lazy">
            <span><?= h($ctEmail) ?></span>
          </div>
        </div>

        <div>
          <div class="footer-heading">Join Newsletter</div>
          <p class="newsletter-desc">Join 2,000+ subscribers getting digital tips weekly.</p>
          <div class="avatar-stack">
            <div class="avatar-stack-item"></div>
            <div class="avatar-stack-item"></div>
            <div class="avatar-stack-item"></div>
            <div class="avatar-stack-item"></div>
            <div class="avatar-stack-item"></div>
            <div class="avatar-stack-badge">2K+</div>
          </div>
          <div class="newsletter-form">
            <input type="email" placeholder="Email" aria-label="Email address">
            <button type="button">SUBSCRIBE</button>
          </div>
        </div>

      </div>
    </div>

    <div class="footer-bottom">
      &copy; 2026 <span class="footer-accent">Xoos Digital</span>. All Rights Reserved.
    </div>
  </footer>
