<?php
require_once __DIR__ . '/admin/config.php';
$type = $_GET['type'] ?? 'privacy';

$pages = [
  'privacy' => [
    'title' => 'Privacy Policy | Xoos Digital',
    'desc' => 'Xoos Digital Privacy Policy — how we collect, use, and protect your personal data.',
    'heading' => 'Privacy Policy',
  ],
  'terms' => [
    'title' => 'Terms of Service | Xoos Digital',
    'desc' => 'Xoos Digital Terms of Service — the terms governing the use of our website and services.',
    'heading' => 'Terms of Service',
  ],
  'cookies' => [
    'title' => 'Cookie Policy | Xoos Digital',
    'desc' => 'Xoos Digital Cookie Policy — how we use cookies and similar technologies on our website.',
    'heading' => 'Cookie Policy',
  ],
];

if (!isset($pages[$type])) $type = 'privacy';

$pageTitle = $pages[$type]['title'];
$pageDesc = $pages[$type]['desc'];

require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/navbar.php';
?>

<section class="page-hero page-hero-sm">
  <div class="page-hero-content">
    <div class="t-label-pill"><span class="t-label-dot"></span><span class="t-label-text">LEGAL</span></div>
    <h1 class="page-hero-title"><?= $pages[$type]['heading'] ?></h1>
  </div>
</section>

<section class="policy-section overflow-safe">
  <div class="policy-layout">
    <nav class="policy-sidebar">
      <a href="policy?type=privacy" class="<?= $type === 'privacy' ? 'policy-link-active' : '' ?>">Privacy Policy</a>
      <a href="policy?type=terms" class="<?= $type === 'terms' ? 'policy-link-active' : '' ?>">Terms of Service</a>
      <a href="policy?type=cookies" class="<?= $type === 'cookies' ? 'policy-link-active' : '' ?>">Cookie Policy</a>
    </nav>
    <div class="policy-content">
      <?php if ($type === 'privacy'): ?>
      <h2>Privacy Policy</h2>
      <p class="policy-date">Last updated: May 20, 2026</p>
      <p>Xoos Digital ("we", "us", or "our") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or use our services.</p>

      <h3>1. Information We Collect</h3>
      <p>We may collect personal information that you voluntarily provide to us when you fill out a contact form, subscribe to our newsletter, or initiate a project inquiry. This includes your name, email address, phone number, company name, and project details.</p>

      <h3>2. How We Use Your Information</h3>
      <p>We use the information we collect to: respond to your inquiries and project requests; send you marketing communications (with your consent); improve our website and services; comply with legal obligations.</p>

      <h3>3. Data Sharing</h3>
      <p>We do not sell your personal information. We may share your data with trusted third-party service providers (e.g., Web3Forms for contact form processing, Google Analytics for website analytics) who are bound by confidentiality agreements.</p>

      <h3>4. Data Security</h3>
      <p>We implement appropriate technical and organizational measures to protect your personal data against unauthorized access, alteration, disclosure, or destruction.</p>

      <h3>5. Your Rights</h3>
      <p>You have the right to: access, correct, or delete your personal data; withdraw consent at any time; request a copy of the data we hold about you; file a complaint with a data protection authority.</p>

      <h3>6. Contact</h3>
      <p>If you have any questions about this Privacy Policy, please contact us at <a href="mailto:xoosdigital@gmail.com">xoosdigital@gmail.com</a>.</p>
      <?php elseif ($type === 'terms'): ?>
      <h2>Terms of Service</h2>
      <p class="policy-date">Last updated: May 20, 2026</p>
      <p>By accessing or using the Xoos Digital website and services, you agree to be bound by these Terms of Service. If you do not agree, please do not use our services.</p>

      <h3>1. Services</h3>
      <p>Xoos Digital provides digital services including branding, web development, digital marketing, SEO, and video production. The scope, deliverables, timeline, and pricing for each project will be defined in a separate agreement or project proposal.</p>

      <h3>2. Intellectual Property</h3>
      <p>Upon full payment, we transfer ownership of the final deliverables to you. Xoos Digital retains the right to display the work in our portfolio unless otherwise agreed in writing.</p>

      <h3>3. Payment Terms</h3>
      <p>Standard payment terms are 50% upfront and 50% upon delivery. All prices are in USD unless stated otherwise. We accept bank transfer, bKash, Payoneer, and Wise.</p>

      <h3>4. Revisions</h3>
      <p>Our packages include a defined number of revision rounds. Additional revisions may incur extra charges as outlined in your project agreement.</p>

      <h3>5. Cancellation</h3>
      <p>If you cancel a project after work has begun, you are responsible for payment for all work completed up to the cancellation date.</p>

      <h3>6. Limitation of Liability</h3>
      <p>Xoos Digital shall not be liable for any indirect, incidental, or consequential damages arising from the use of our services. Our total liability is limited to the amount paid for the specific service.</p>
      <?php elseif ($type === 'cookies'): ?>
      <h2>Cookie Policy</h2>
      <p class="policy-date">Last updated: May 20, 2026</p>
      <p>This Cookie Policy explains how Xoos Digital uses cookies and similar tracking technologies on our website.</p>

      <h3>1. What Are Cookies</h3>
      <p>Cookies are small text files stored on your device when you visit a website. They help us improve your browsing experience by remembering preferences, analyzing site traffic, and enabling certain functionality.</p>

      <h3>2. Types of Cookies We Use</h3>
      <p><strong>Essential Cookies:</strong> Required for the website to function properly. These cannot be disabled. <strong>Analytics Cookies:</strong> Help us understand how visitors interact with our site (e.g., Google Analytics). <strong>Functional Cookies:</strong> Remember your preferences and settings.</p>

      <h3>3. Third-Party Cookies</h3>
      <p>We may use third-party services like Google Analytics, Facebook Pixel, and Tailwind CSS that set their own cookies. These services have their own cookie policies.</p>

      <h3>4. Managing Cookies</h3>
      <p>You can control and delete cookies through your browser settings. Disabling certain cookies may affect the functionality of our website.</p>

      <h3>5. Contact</h3>
      <p>If you have questions about our use of cookies, please contact us at <a href="mailto:xoosdigital@gmail.com">xoosdigital@gmail.com</a>.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<style>
.policy-section { padding: 4rem 0; background: #0A0A0A; min-height: 60vh; }
.policy-layout { display: grid; grid-template-columns: 240px 1fr; gap: 3rem; max-width: 1100px; margin: 0 auto; padding: 0 2rem; align-items: start; }
.policy-sidebar { position: sticky; top: 100px; display: flex; flex-direction: column; gap: 0.5rem; }
.policy-sidebar a { font-family: 'Orbitron', sans-serif; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #555; text-decoration: none; padding: 0.625rem 1rem; border-radius: 0.5rem; transition: all 0.2s; border: 1px solid transparent; }
.policy-sidebar a:hover { color: #CCFF00; background: rgba(204,255,0,0.04); border-color: rgba(204,255,0,0.1); }
.policy-link-active { color: #CCFF00 !important; background: rgba(204,255,0,0.06) !important; border-color: rgba(204,255,0,0.2) !important; }
.policy-content { max-width: 720px; }
.policy-content h2 { font-family: 'Orbitron', sans-serif; font-size: 1.5rem; font-weight: 900; color: white; text-transform: uppercase; letter-spacing: 0.02em; margin-bottom: 0.5rem; }
.policy-content h3 { font-family: 'Orbitron', sans-serif; font-size: 0.85rem; font-weight: 700; color: #CCFF00; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2rem; margin-bottom: 0.75rem; }
.policy-content p { font-family: 'Inter', sans-serif; font-size: 0.88rem; color: #9CA3AF; line-height: 1.8; margin-bottom: 1rem; }
.policy-content a { color: #CCFF00; }
.policy-date { font-size: 0.75rem; color: #555; margin-bottom: 2rem; font-family: 'Inter', sans-serif; }
@media (max-width: 768px) { .policy-layout { grid-template-columns: 1fr; } .policy-sidebar { position: static; flex-direction: row; flex-wrap: wrap; } }
</style>

<?php
require __DIR__ . '/inc/footer.php';
require __DIR__ . '/inc/project-form.php';
require __DIR__ . '/inc/chatbot.php';
require __DIR__ . '/inc/scripts.php';
?>
