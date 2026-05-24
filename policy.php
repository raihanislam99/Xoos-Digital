<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';
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
      <p>We collect information you voluntarily provide when you fill out a contact form, subscribe to our newsletter, or initiate a project inquiry. This includes your name, email address, phone number, company name, and project details. We also automatically collect certain data when you visit our site, including your IP address, browser type, operating system, referring URLs, and pages viewed.</p>

      <h3>2. How We Use Your Information</h3>
      <p>We use the collected data to respond to your inquiries and project requests, send marketing communications only with your explicit consent, improve our website and services based on usage patterns, comply with applicable legal obligations, and detect and prevent fraudulent or unauthorized activity.</p>

      <h3>3. Data Sharing & Third Parties</h3>
      <p>We do not sell, trade, or rent your personal information. We may share your data with trusted third-party service providers who assist us in operating our website and conducting our business, including Web3Forms (contact form processing), Google Analytics (website analytics), and payment processors. All third parties are contractually bound to keep your information confidential and use it solely for the purposes we specify.</p>

      <h3>4. Data Retention</h3>
      <p>We retain your personal data only as long as necessary to fulfill the purposes outlined in this policy, or as required by law. When data is no longer needed, we securely delete or anonymize it. Newsletter subscribers can unsubscribe at any time, and we will remove your data from our mailing lists promptly.</p>

      <h3>5. Data Security</h3>
      <p>We implement appropriate technical and organizational security measures to protect your personal data against unauthorized access, alteration, disclosure, or destruction. These include SSL/TLS encryption, firewalls, secure server infrastructure, and access controls. However, no method of transmission over the Internet is 100% secure, and we cannot guarantee absolute security.</p>

      <h3>6. Your Rights</h3>
      <p>You have the right to access, correct, or delete your personal data at any time, withdraw consent where processing is based on consent, request a portable copy of the data we hold about you, object to or restrict certain processing activities, and file a complaint with a data protection authority if you believe your rights have been violated. To exercise any of these rights, contact us at the email below.</p>

      <h3>7. International Transfers</h3>
      <p>Your information may be transferred to and processed in countries outside your country of residence. We ensure appropriate safeguards are in place through standard contractual clauses or equivalent mechanisms to protect your data in accordance with this policy.</p>

      <h3>8. Changes to This Policy</h3>
      <p>We reserve the right to update this Privacy Policy at any time. Changes will be posted on this page with an updated revision date. We encourage you to review this policy periodically.</p>

      <h3>9. Contact</h3>
      <p>If you have questions, concerns, or requests regarding this Privacy Policy, please contact us at <a href="mailto:xoosdigital@gmail.com">xoosdigital@gmail.com</a> or write to us at Khilgaon, Dhaka, Bangladesh.</p>

      <?php elseif ($type === 'terms'): ?>
      <h2>Terms of Service</h2>
      <p class="policy-date">Last updated: May 20, 2026</p>
      <p>By accessing or using the Xoos Digital website and services, you agree to be bound by these Terms of Service. If you do not agree with any part of these terms, you must not use our website or services.</p>

      <h3>1. Services Overview</h3>
      <p>Xoos Digital provides digital services including branding and identity design, web development and design, digital marketing and advertising, search engine optimization (SEO), video production and motion graphics, and content creation. The specific scope, deliverables, timeline, and pricing for each engagement will be defined in a separate project proposal or service agreement signed by both parties.</p>

      <h3>2. Project Proposals & Acceptance</h3>
      <p>A project proposal outlining the scope, deliverables, timeline, and fees will be provided for each engagement. The proposal must be accepted in writing (email acceptance is valid) before work begins. The proposal constitutes the complete agreement and supersedes any prior discussions or representations.</p>

      <h3>3. Intellectual Property Rights</h3>
      <p>Upon receipt of full payment for the completed project, we transfer ownership of all final deliverables specifically created for you. Xoos Digital retains the right to display the work in our portfolio, case studies, and marketing materials unless a non-disclosure agreement is in place. We retain ownership of our underlying tools, frameworks, libraries, and pre-existing intellectual property. Any third-party assets (fonts, stock images, plugins) are licensed under their respective terms.</p>

      <h3>4. Payment Terms</h3>
      <p>Standard payment terms require 50% of the total project fee upfront before work commences and the remaining 50% upon delivery and approval of final deliverables. All prices are quoted in USD unless explicitly stated otherwise. We accept bank transfers, bKash, Payoneer, and Wise. Invoices are due within 14 days of the invoice date. Late payments may result in a 5% monthly service charge and suspension of work until payment is received.</p>

      <h3>5. Revisions & Change Requests</h3>
      <p>Our packages include a defined number of revision rounds as specified in your project proposal. Revision rounds cover changes to the selected concept, not new concepts or scope additions. Additional revisions or out-of-scope work will be quoted separately and require approval before implementation. Minor text changes and bug fixes during the development phase are not counted as revisions.</p>

      <h3>6. Client Responsibilities</h3>
      <p>You agree to provide timely feedback, approvals, and all required materials (content, images, brand assets, login credentials) within the agreed timeframe. Delays in providing these materials may result in project timeline adjustments. You are responsible for ensuring that any content, images, or materials you provide do not infringe on third-party rights.</p>

      <h3>7. Project Cancellation</h3>
      <p>If a project is canceled after work has begun, you are responsible for payment for all work completed up to the cancellation date. Completed work will be delivered upon payment. Cancellation must be communicated in writing. Any deposits paid are non-refundable as they cover the initial project scoping, planning, and resource allocation.</p>

      <h3>8. Limitation of Liability</h3>
      <p>Xoos Digital shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from or related to the use of our services, including but not limited to loss of revenue, profits, data, or business interruption. Our total liability for any claim arising from our services is strictly limited to the total amount paid by you for the specific service giving rise to the claim.</p>

      <h3>9. Warranties & Disclaimers</h3>
      <p>We warrant that our services will be performed in a professional and workmanlike manner. All services are provided "as is" without any other warranties, express or implied. We do not guarantee specific outcomes such as search engine rankings, sales increases, or traffic levels, as these depend on factors beyond our control.</p>

      <h3>10. Confidentiality</h3>
      <p>Both parties agree to maintain the confidentiality of proprietary information shared during the course of the project. This obligation survives the termination of the agreement for a period of two years. Confidential information does not include information that is publicly available or independently developed.</p>

      <h3>11. Website Content & Third-Party Services</h3>
      <p>Xoos Digital is not responsible for the accuracy, completeness, or legality of content provided by you for inclusion on your website. We are not liable for any issues arising from third-party services, plugins, or platforms integrated into your website. We recommend maintaining regular backups of your website data.</p>

      <h3>12. Governing Law</h3>
      <p>These terms are governed by the laws of Bangladesh. Any disputes arising from these terms shall be resolved through amicable negotiation. If negotiation fails, disputes shall be submitted to the courts of Dhaka, Bangladesh.</p>

      <h3>13. Modifications</h3>
      <p>We reserve the right to modify these Terms of Service at any time. Changes will be effective immediately upon posting. Your continued use of our services after changes constitutes acceptance of the modified terms.</p>

      <h3>14. Contact</h3>
      <p>For questions about these Terms of Service, contact us at <a href="mailto:xoosdigital@gmail.com">xoosdigital@gmail.com</a>.</p>

      <?php elseif ($type === 'cookies'): ?>
      <h2>Cookie Policy</h2>
      <p class="policy-date">Last updated: May 20, 2026</p>
      <p>This Cookie Policy explains how Xoos Digital uses cookies, web beacons, pixel tags, and similar tracking technologies on our website. By continuing to browse our site, you consent to the use of cookies as described in this policy.</p>

      <h3>1. What Are Cookies</h3>
      <p>Cookies are small text files stored on your device (computer, tablet, or mobile) when you visit a website. They are widely used to make websites work more efficiently, enhance the user experience, and provide analytical information to site owners. Cookies cannot access your hard drive, transmit viruses, or identify you personally unless you have voluntarily provided personal information.</p>

      <h3>2. Types of Cookies We Use</h3>
      <p><strong>Essential / Strictly Necessary Cookies:</strong> These cookies are required for the website to function properly and cannot be disabled. They enable core functionality such as security, network management, and accessibility. Without these cookies, some parts of our site may not function correctly.</p>
      <p><strong>Analytics / Performance Cookies:</strong> These cookies help us understand how visitors interact with our website by collecting and reporting anonymous information. We use Google Analytics to track page visits, time spent on site, traffic sources, and user behavior patterns. This data helps us improve the performance and relevance of our content.</p>
      <p><strong>Functional Cookies:</strong> These cookies remember your preferences and choices (such as language selection or region) to provide a more personalized experience. They may also enable enhanced features like live chat support.</p>
      <p><strong>Marketing / Targeting Cookies:</strong> These cookies track your browsing habits across websites to deliver advertisements that are relevant to you. We may use Facebook Pixel and similar technologies for retargeting and conversion measurement purposes.</p>

      <h3>3. Third-Party Cookies</h3>
      <p>We use several third-party services that may set their own cookies on your device:</p>
      <p><strong>Google Analytics</strong> — collects anonymized usage data to help us analyze website traffic and improve user experience. Google's privacy policy can be found at policies.google.com/privacy.</p>
      <p><strong>Facebook Pixel</strong> — helps us measure the effectiveness of our advertising and deliver targeted content. Facebook's data policy is available at facebook.com/policy.php.</p>
      <p><strong>Web3Forms</strong> — processes our contact form submissions. Their privacy practices are documented at web3forms.com/privacy.</p>
      <p>These third-party services have their own privacy and cookie policies governing the use of your information. We recommend reviewing their policies for complete information.</p>

      <h3>4. How Long Cookies Stay</h3>
      <p><strong>Session Cookies:</strong> These are temporary cookies that expire when you close your browser. They enable the website to link your actions during a single browsing session.</p>
      <p><strong>Persistent Cookies:</strong> These remain on your device for a set period or until manually deleted. They remember your preferences and actions across multiple visits. The retention period varies by cookie but typically ranges from 30 days to 2 years.</p>

      <h3>5. Managing & Deleting Cookies</h3>
      <p>You can control and manage cookies in several ways:</p>
      <p><strong>Browser Settings:</strong> Most browsers allow you to view, block, or delete cookies through your settings. The following links provide guidance for common browsers:</p>
      <p>Google Chrome: Settings → Privacy and Security → Cookies and other site data</p>
      <p>Mozilla Firefox: Options → Privacy & Security → Cookies and Site Data</p>
      <p>Safari: Preferences → Privacy → Cookies and website data</p>
      <p>Microsoft Edge: Settings → Cookies and site permissions</p>
      <p><strong>Opt-Out Tools:</strong> You can opt out of Google Analytics tracking by installing the Google Analytics Opt-out Browser Add-on (tools.google.com/dlpage/gaoptout). You can adjust your Facebook ad preferences at facebook.com/ad_preferences.</p>
      <p>Please note that disabling certain cookies may affect the functionality and performance of our website, and some features may not be available.</p>

      <h3>6. Changes to This Policy</h3>
      <p>We may update this Cookie Policy from time to time to reflect changes in technology, legislation, or our data practices. Changes will be posted on this page with an updated revision date. We encourage you to review this policy periodically.</p>

      <h3>7. Contact</h3>
      <p>If you have any questions or concerns about our use of cookies, please contact us at <a href="mailto:xoosdigital@gmail.com">xoosdigital@gmail.com</a>.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<style>
.policy-section { padding: 4rem 0; background: #0d1117; min-height: 60vh; }
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
