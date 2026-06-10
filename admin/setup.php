<?php
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/supabase.php';

$message = '';
$error = false;
$loggedIn = !empty($_SESSION['supabase_access_token']);

// Check if we have direct PDO or REST-only mode
$pdo = db();
$isRestMode = ($pdo instanceof SupabaseRestDB);

if ($isRestMode) {
    $message = 'Direct PostgreSQL PDO is unavailable (IPv6-only host). Tables must be created via the Supabase Dashboard SQL Editor. See instructions below.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = db();

        // Drop & Recreate — only works with direct PDO
        if (!empty($_POST['reset'])) {
            if ($isRestMode) {
                $message = 'Cannot drop tables in REST mode. Use Supabase Dashboard SQL Editor.';
                throw new Exception('');
            }
            $dropTables = ['post_hashtags','generated_posts','post_profiles','post_training_data','invoice_items','invoices','quotation_items','quotations','company_info','outreach_templates','lead_activity','lead_whatsapp','lead_emails','leads','media_files','settings','blog_categories','blog_posts','admin_tasks','brands','portfolio','faq','testimonials','packages','services','blog_categories','contact_messages'];
            foreach ($dropTables as $t) {
                $pdo->exec("DROP TABLE IF EXISTS {$t} CASCADE");
            }
            $message = 'All tables dropped.';
        }

        // ── Create Tables (PostgreSQL) ──
        // Skipped in REST mode — tables must be created via Supabase Dashboard SQL Editor
        if (!$isRestMode) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS services (
                id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, description TEXT, features TEXT,
                hashtags VARCHAR(500), price VARCHAR(50) DEFAULT '', sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS packages (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, tier VARCHAR(50), tagline VARCHAR(255), price VARCHAR(50), features TEXT, billing_cycle VARCHAR(50) DEFAULT 'one-time', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS testimonials (id SERIAL PRIMARY KEY, client_name VARCHAR(255) NOT NULL, quote TEXT NOT NULL, rating SMALLINT DEFAULT 5, service_used VARCHAR(255), client_image VARCHAR(500), client_country VARCHAR(100) DEFAULT '', country_flag VARCHAR(10) DEFAULT '', platform VARCHAR(50) DEFAULT '', avatar_gradient VARCHAR(255) DEFAULT '', avatar_letter VARCHAR(2) DEFAULT '', sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS faq (id SERIAL PRIMARY KEY, question VARCHAR(500) NOT NULL, answer TEXT NOT NULL, category VARCHAR(100), sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS portfolio_categories (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE, sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS portfolio (id SERIAL PRIMARY KEY, project_name VARCHAR(255) NOT NULL, client VARCHAR(255), service VARCHAR(255), description TEXT, image_url VARCHAR(500), link VARCHAR(500), slug VARCHAR(255) UNIQUE, category_id INT DEFAULT 0, challenge TEXT, solution TEXT, results TEXT, client_testimonial TEXT, technologies VARCHAR(500), video_url VARCHAR(500), meta_title VARCHAR(255), meta_description TEXT, sort_order INT DEFAULT 0, is_active BOOLEAN DEFAULT true, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS brands (id SERIAL PRIMARY KEY, name VARCHAR(100) NOT NULL, logo_url VARCHAR(500) NOT NULL, industry VARCHAR(100) DEFAULT '', country VARCHAR(100) DEFAULT '', service VARCHAR(100) DEFAULT '', bloom_color VARCHAR(50) DEFAULT 'rgba(0,0,0,0.18)', sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(100) DEFAULT '', company VARCHAR(255) DEFAULT '', country VARCHAR(100) DEFAULT '', services TEXT, budget VARCHAR(100) DEFAULT '', timeline VARCHAR(100) DEFAULT '', message TEXT, ip_address VARCHAR(45) DEFAULT '', is_read SMALLINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS settings (id SERIAL PRIMARY KEY, setting_key VARCHAR(100) NOT NULL UNIQUE, setting_value TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS media_files (id SERIAL PRIMARY KEY, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, filepath VARCHAR(500) NOT NULL, filesize INT DEFAULT 0, mime_type VARCHAR(100) DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS blog_categories (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE, sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (id SERIAL PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE, content TEXT, meta_title VARCHAR(255), meta_description TEXT, tags VARCHAR(500), status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','published')), featured_image VARCHAR(500), category_id INT DEFAULT 0, read_time INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS post_training_data (id SERIAL PRIMARY KEY, content TEXT NOT NULL, type VARCHAR(50) DEFAULT 'topic', profile_id INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS post_profiles (id SERIAL PRIMARY KEY, platform VARCHAR(50) NOT NULL, profile_url VARCHAR(500) NOT NULL, name VARCHAR(255) DEFAULT '', notes TEXT, language VARCHAR(50) DEFAULT 'english', tone VARCHAR(50) DEFAULT 'semi-professional', niche VARCHAR(500) DEFAULT '', color VARCHAR(7) DEFAULT '#c8f135', post_length INT DEFAULT 200, type VARCHAR(20) DEFAULT 'personal', business_type VARCHAR(200) DEFAULT '', target_audience TEXT DEFAULT '', brand_voice TEXT DEFAULT '', avoid_topics TEXT DEFAULT '', avatar_url VARCHAR(500) DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS generated_posts (id SERIAL PRIMARY KEY, platform VARCHAR(50) NOT NULL, content TEXT, language VARCHAR(50) DEFAULT 'en', status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','published')), topic VARCHAR(255) DEFAULT '', training_ids TEXT, profile_ids TEXT, profile_id INT DEFAULT NULL, linkedin_content TEXT, facebook_content TEXT, hashtags_used TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS post_hashtags (id SERIAL PRIMARY KEY, platform VARCHAR(50) NOT NULL, tag VARCHAR(100) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS admin_tasks (id SERIAL PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT, status VARCHAR(50) DEFAULT 'pending', priority VARCHAR(50) DEFAULT 'medium', due_date TIMESTAMP DEFAULT NULL, category VARCHAR(255) DEFAULT '', lead_id INT DEFAULT NULL, assignee_type VARCHAR(50) DEFAULT NULL, assignee_name VARCHAR(255) DEFAULT NULL, sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS leads (id SERIAL PRIMARY KEY, business_name VARCHAR(255) NOT NULL, owner_name VARCHAR(255) DEFAULT '', email VARCHAR(255) DEFAULT '', phone VARCHAR(100) DEFAULT '', whatsapp VARCHAR(100) DEFAULT '', website VARCHAR(500) DEFAULT '', facebook VARCHAR(500) DEFAULT '', instagram VARCHAR(500) DEFAULT '', city VARCHAR(100) DEFAULT '', country VARCHAR(100) DEFAULT '', address TEXT, niche VARCHAR(255) DEFAULT '', lead_score INT DEFAULT 0, status VARCHAR(50) DEFAULT 'new', source VARCHAR(100) DEFAULT '', tags TEXT, is_blacklisted SMALLINT DEFAULT 0, google_maps_url VARCHAR(500) DEFAULT '', has_website SMALLINT DEFAULT 0, website_score INT DEFAULT 0, ai_audit TEXT, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

            // ── Set up updated_at triggers ──
            $triggerTables = ['services','packages','testimonials','faq','portfolio','portfolio_categories','brands','blog_posts','settings','leads','outreach_templates','company_info','quotations','invoices'];
            foreach ($triggerTables as $t) {
                pg_ensure_updated_at_trigger($t);
            }

            // ── Performance indexes ──
            $indexes = [
                'CREATE INDEX IF NOT EXISTS idx_leads_status ON leads (status)',
                'CREATE INDEX IF NOT EXISTS idx_leads_is_blacklisted ON leads (is_blacklisted)',
                'CREATE INDEX IF NOT EXISTS idx_leads_created_at ON leads (created_at)',
                'CREATE INDEX IF NOT EXISTS idx_contact_messages_is_read ON contact_messages (is_read)',
                'CREATE INDEX IF NOT EXISTS idx_contact_messages_created_at ON contact_messages (created_at)',
                'CREATE INDEX IF NOT EXISTS idx_admin_tasks_status ON admin_tasks (status)',
                'CREATE INDEX IF NOT EXISTS idx_admin_tasks_due_date ON admin_tasks (due_date)',
                'CREATE INDEX IF NOT EXISTS idx_admin_tasks_created_at ON admin_tasks (created_at)',
                'CREATE INDEX IF NOT EXISTS idx_blog_posts_status ON blog_posts (status)',
                'CREATE INDEX IF NOT EXISTS idx_blog_posts_created_at ON blog_posts (created_at)',
                'CREATE INDEX IF NOT EXISTS idx_blog_posts_category_id ON blog_posts (category_id)',
                'CREATE INDEX IF NOT EXISTS idx_generated_posts_status ON generated_posts (status)',
                'CREATE INDEX IF NOT EXISTS idx_lead_activity_lead_id ON lead_activity (lead_id)',
                'CREATE INDEX IF NOT EXISTS idx_lead_emails_lead_id ON lead_emails (lead_id)',
                'CREATE INDEX IF NOT EXISTS idx_lead_whatsapp_lead_id ON lead_whatsapp (lead_id)',
                'CREATE INDEX IF NOT EXISTS idx_quotation_items_quotation_id ON quotation_items (quotation_id)',
                'CREATE INDEX IF NOT EXISTS idx_invoice_items_invoice_id ON invoice_items (invoice_id)',
                'CREATE INDEX IF NOT EXISTS idx_post_versions_post_id ON post_versions (post_id)',
            ];
            foreach ($indexes as $idx) {
                try { $pdo->exec($idx); } catch (Exception $e) {}
            }
        } else {
            $message = 'Tables must be created via Supabase Dashboard SQL Editor (see instructions below). Skip to seed data section below.';
        }

        // ── Seed default blog category ──
        try {
            if ($pdo->query("SELECT COUNT(*) FROM blog_categories")->fetchColumn() == 0) {
                $pdo->exec("INSERT INTO blog_categories (name, slug, sort_order) VALUES ('General', 'general', 0)");
            }
        } catch (Exception $e) {}

        // ── Seed Services ──
        if ($pdo->query("SELECT COUNT(*) FROM services")->fetchColumn() == 0) {
            $svcStmt = $pdo->prepare("INSERT INTO services (name, description, features, hashtags, price, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $svcStmt->execute(['BRANDING IDENTITY', 'Strategic brand identity that makes your business unforgettable. We build the visual foundation your brand needs — logo, color system, typography, and guidelines that scale across every touchpoint from business cards to billboards.', "Logo Design (3 unique concepts)\nBrand Color Palette & Typography\nBrand Guidelines Document\nBusiness Card & Stationery\nSocial Media Profile Kit\nDelivered in 5–7 business days", '#BrandIdentityDesign #LogoAndBranding #VisualIdentity #BrandStrategy', '$299', 0]);
            $svcStmt->execute(['WORDPRESS & E-COMMERCE', 'Custom WordPress websites and WooCommerce stores built for performance, conversion, and growth. Mobile-first, blazing fast, and designed to turn visitors into customers — not just look pretty.', "Custom WordPress Theme Design\nWooCommerce Store Setup\nMobile-Responsive Development\nSEO Foundation & Speed Optimization\nPayment Gateway Integration\nDelivered in 2–4 weeks", '#CustomWordPress #WooCommerce #EcommerceExperts #WebDevelopment', '$499', 1]);
            $svcStmt->execute(['DIGITAL MARKETING', 'Data-driven marketing campaigns that connect you with the right audience at the right time. From Facebook and Instagram ads to Google PPC — every campaign is tied to KPIs that actually matter to your business.', "Social Media Strategy & Management\nFacebook & Instagram Ad Campaigns\nGoogle Ads (Search & Display)\nContent Creation & Copywriting\nMonthly Performance Reports\nA/B Testing & Optimization", '#SocialMediaMarketing #PPCCampaigns #ContentStrategy #GrowthMarketing', '$399/mo', 2]);
            $svcStmt->execute(['SEO & ORGANIC GROWTH', 'Long-term organic growth that compounds over time. Our SEO strategy combines technical optimization, content planning, and link building to move your business up the rankings and keep it there — driving free traffic month after month.', "Technical SEO Audit & Fixes\nKeyword Research & Strategy\nOn-Page Optimization\nContent SEO Planning\nGoogle Search Console Setup\nMonthly Ranking Reports", '#TechnicalSEO #KeywordResearch #OrganicGrowth #SearchRankings', '$349/mo', 3]);
            $svcStmt->execute(['VIDEO PRODUCTION', 'Video content that stops the scroll and tells your brand story in a way that text and images never can. From cinematic brand films to social media reels and motion graphics — we make your brand move.', "Brand Film & Corporate Video\nSocial Media Reels (10 videos)\nMotion Graphics & Animation\nScript & Storyboard Development\nProfessional Editing & Color Grade\nDelivered in 1–2 weeks", '#BrandFilms #MotionGraphics #SocialContent #VideoStrategy', '$599', 4]);
            $svcStmt->execute(['UI/UX DESIGN', 'User-centered design that makes your digital products intuitive, accessible, and delightful. From wireframes to high-fidelity prototypes — we design experiences that users love and businesses benefit from.', "User Research & Personas\nWireframing & Prototyping\nVisual UI Design\nInteraction Design\nUsability Testing\nDesign System Creation", '#UIDesign #UXDesign #ProductDesign #UserExperience #Figma', '$449', 5]);
            $svcStmt->execute(['SOCIAL MEDIA MANAGEMENT', 'Strategic social media management that builds communities and drives engagement. We handle content creation, scheduling, community management, and analytics so you can focus on running your business.', "Content Calendar Planning\nGraphic & Video Content Creation\nDaily Community Management\nHashtag & Trend Research\nMonthly Analytics Reports\nCompetitor Analysis", '#SocialMediaManagement #ContentCreation #CommunityManagement #InstagramMarketing #FacebookMarketing', '$299/mo', 6]);
            $svcStmt->execute(['CONTENT WRITING & COPYWRITING', 'Compelling content that tells your brand story and drives action. From website copy and blog posts to email campaigns and ad copy — every word is crafted to convert.', "Website Copywriting\nBlog Post Writing\nEmail Newsletter Copy\nAd Copy (Google & Social)\nProduct Descriptions\nBrand Voice Guide", '#Copywriting #ContentWriting #BrandStorytelling #EmailMarketing #BlogWriting', '$199', 7]);
            $message .= ' Services seeded.';
        }

        // ── Seed Packages ──
        if ($pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn() == 0) {
            $pkgStmt = $pdo->prepare("INSERT INTO packages (name, tier, tagline, price, features) VALUES (?, ?, ?, ?, ?)");
            $pkgStmt->execute(['STARTER', 'starter', 'Perfect for new businesses that need a professional brand presence fast.', '$299', "Logo Design (3 concepts)\nBrand Color Palette\nTypography Selection\nBusiness Card Design\nSocial Media Kit\n2 Revision Rounds\n✗ Website Development\n✗ SEO Setup\n✗ Marketing Campaign"]);
            $pkgStmt->execute(['GROWTH', 'popular', 'For growing brands that need a website, identity, and digital presence that converts.', '$799', "Everything in Starter\nWordPress Website (5 pages)\nMobile Responsive Design\nSEO Foundation Setup\nGoogle Analytics Integration\nSocial Media Setup\n3 Revision Rounds\n30-Day Post-Launch Support\n✗ Custom WooCommerce Store\n✗ Ongoing Marketing Campaign"]);
            $pkgStmt->execute(['PREMIUM', 'premium', 'Full-service partnership for brands ready to dominate their market completely.', 'CUSTOM', "Everything in Growth\nCustom WooCommerce Store\nFull SEO Campaign (3 months)\nSocial Media Management\nVideo Production (1 brand film)\nPerformance Marketing Setup\nMonthly Analytics Reports\nPriority Support\nDedicated Account Manager\nUnlimited Revisions"]);
            $pkgStmt->execute(['BRAND STARTER', 'starter', 'A quick, affordable brand identity package for startups and side projects.', '$199', "Logo Design (2 concepts)\nBrand Color Palette\nTypography Selection\nSocial Media Profile Kit\nBusiness Card Design\n1 Revision Round\n✗ Website Development\n✗ SEO Setup\n✗ Marketing Campaign\n✗ Brand Guidelines"]);
            $pkgStmt->execute(['ENTERPRISE', 'premium', 'The complete digital partner for established brands scaling to the next level.', '$1,999', "Everything in Premium\nCustom Web Application\nDedicated Project Manager\nPriority 24/7 Support\nQuarterly Strategy Sessions\nAdvanced Analytics Dashboard\nAPI Integrations\nMulti-language Support\nPerformance Marketing Retainer\nSEO Retainer (6 months)\nUnlimited Brand Assets"]);
            $message .= ' Packages seeded.';
        }

        // ── Seed Testimonials ──
        if ($pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn() == 0) {
            $testStmt = $pdo->prepare("INSERT INTO testimonials (client_name, quote, rating, service_used) VALUES (?, ?, ?, ?)");
            $testStmt->execute(['Raihan Ahmed', 'Xoos Digital completely transformed our online store. Within 2 months of launch our sales doubled.', 5, 'WordPress Dev']);
            $testStmt->execute(['Sarah Mitchell', 'We hired Xoos Digital for a complete brand overhaul. The logo, color system, and guidelines they delivered were exceptional.', 5, 'Branding']);
            $testStmt->execute(['Farhan Hossain', 'Our Google rankings moved from page 4 to page 1 in just 3 months. Saw a 280% increase in organic traffic.', 5, 'SEO']);
            $testStmt->execute(['Liam O\'Brien', 'Partnering with Xoos Digital on our client\'s rebrand was seamless. Professional, responsive, and creatively sharp.', 5, 'Branding']);
            $testStmt->execute(['Nusrat Jahan', 'From our logo to our packaging, Xoos Digital understood our brand vision perfectly.', 5, 'Branding']);
            $testStmt->execute(['Mohammed Al-Rashid', 'Xoos Digital stands out for communication quality and delivery speed. The WordPress site is blazing fast.', 5, 'WordPress Dev']);
            $testStmt->execute(['James Thornton', 'As a designer myself I am extremely picky. Xoos Digital exceeded my standards.', 5, 'Branding']);
            $testStmt->execute(['Sadia Islam', 'Xoos Digital understood our edtech brand from day one. The visual identity communicates innovation and trust.', 5, 'Branding']);
            $message .= ' Testimonials seeded.';
        }

        // ── Seed FAQ ──
        if ($pdo->query("SELECT COUNT(*) FROM faq")->fetchColumn() == 0) {
            $faqStmt = $pdo->prepare("INSERT INTO faq (question, answer, sort_order) VALUES (?, ?, ?)");
            $faqStmt->execute(['How long does a project take?', 'It depends on the scope. A brand identity takes 5–7 business days. A WordPress website takes 2–4 weeks.', 0]);
            $faqStmt->execute(['How much does a project cost?', 'Our projects start from $299 for branding and $499 for website development.', 1]);
            $faqStmt->execute(['Do you work with international clients?', 'Absolutely. We work with clients across 12+ countries worldwide.', 2]);
            $faqStmt->execute(['What do you need from me to get started?', 'Just a brief conversation about your business, your goals, and any existing brand materials.', 3]);
            $faqStmt->execute(['How many revisions do I get?', 'Starter projects include 2 revision rounds. Growth and Premium packages include 3+ rounds.', 4]);
            $faqStmt->execute(['How do I make payment?', 'We accept bank transfer, bKash, Nagad, Payoneer, Wise, and PayPal. 50% upfront, 50% on delivery.', 5]);
            $faqStmt->execute(['Can you maintain my website after launch?', 'Yes. Monthly maintenance packages starting from $49/month.', 6]);
            $faqStmt->execute(['Why choose Xoos Digital over other agencies?', '80+ clients across 12 countries trust us. You work directly with the founder.', 7]);
            $message .= ' FAQ seeded.';
        }

        // ── Seed Blog Posts ──
        if ($pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn() == 0) {
            $blogStmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, content, meta_title, meta_description, tags, status, featured_image) VALUES (?, ?, ?, ?, ?, ?, 'published', ?)");
            $blogStmt->execute(['Why Branding & Identity Design Matters More Than Ever', 'why-branding-matters', '<h2>The Foundation of Brand Recognition</h2><p>Your brand identity is the visual voice of your business.</p>', 'Why Branding Matters | Xoos Digital', 'Learn why professional branding is crucial for business growth.', 'branding, identity design, logo design', 'images/placeholder.svg']);
            $blogStmt->execute(['The Complete Guide to Building a High-Converting Website', 'high-converting-website-guide', '<h2>What Makes a Website Convert?</h2><p>A high-converting website guides visitors toward action.</p>', 'High-Converting Website Guide | Xoos Digital', 'Learn how to build a website that converts.', 'web development, wordpress, conversion optimization', 'images/placeholder.svg']);
            $blogStmt->execute(['Effective SEO & Digital Marketing Strategies to Grow Your Brand', 'seo-digital-marketing-strategies', '<h2>The Power of Organic Growth</h2><p>SEO is the most cost-effective way to drive long-term traffic.</p>', 'SEO & Marketing Strategies | Xoos Digital', 'Discover effective SEO and digital marketing strategies.', 'seo, digital marketing, social media', 'images/placeholder.svg']);
            $blogStmt->execute(['How to Choose the Right Color Palette for Your Brand', 'choose-brand-color-palette', '<h2>Color Psychology in Branding</h2><p>Colors evoke emotions. Blue conveys trust. Red creates urgency.</p>', 'Choose Your Brand Color Palette | Xoos Digital', 'Learn how to choose the perfect color palette.', 'brand colors, color palette, brand identity', 'images/placeholder.svg']);
            $blogStmt->execute(['The Ultimate Guide to WooCommerce SEO for Online Stores', 'woocommerce-seo-guide', '<h2>Why WooCommerce SEO Matters</h2><p>WooCommerce powers millions of online stores.</p>', 'WooCommerce SEO Guide | Xoos Digital', 'Complete guide to optimizing your WooCommerce store.', 'woocommerce, seo, ecommerce', 'images/placeholder.svg']);
            $blogStmt->execute(['Video Marketing Trends to Watch in 2025', 'video-marketing-trends-2025', '<h2>The Rise of Short-Form Video</h2><p>Short-form video dominates social media algorithms.</p>', 'Video Marketing Trends 2025 | Xoos Digital', 'Discover the top video marketing trends.', 'video marketing, social media, reels', 'images/placeholder.svg']);
            $message .= ' Blog posts seeded.';
        }

        // ── Seed Portfolio Categories ──
        if ($pdo->query("SELECT COUNT(*) FROM portfolio_categories")->fetchColumn() == 0) {
            $catStmt = $pdo->prepare("INSERT INTO portfolio_categories (name, slug, sort_order) VALUES (?, ?, ?)");
            $catStmt->execute(['Branding', 'branding', 1]);
            $catStmt->execute(['Web Development', 'web-development', 2]);
            $catStmt->execute(['Digital Marketing', 'digital-marketing', 3]);
            $catStmt->execute(['Social Media', 'social-media', 4]);
            $catStmt->execute(['Video Production', 'video-production', 5]);
            $message .= ' Portfolio categories seeded.';
        }

        // ── Seed Portfolio ──
        if ($pdo->query("SELECT COUNT(*) FROM portfolio")->fetchColumn() == 0) {
            $portStmt = $pdo->prepare("INSERT INTO portfolio (project_name, client, service, description, image_url, slug, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $portStmt->execute(['XOOS DIGITAL BRAND', 'Xoos Digital', 'Branding', 'Full brand identity & digital presence.', 'images/placeholder.svg', 'xoos-digital-brand', true, 1]);
            $portStmt->execute(['BRIGHT HASH', 'Bright Hash Ltd.', 'Branding', 'Logo design and comprehensive brand system.', 'images/Brands_that_we work_with/Bright-hash-Logo-1.webp', 'bright-hash-brand', true, 2]);
            $portStmt->execute(['HOLY BASKET', 'Holy Basket', 'Branding', 'Complete visual identity system for food & retail brand.', 'images/Brands_that_we work_with/Holy-Basket-Logo.webp', 'holy-basket-brand', true, 3]);
            $portStmt->execute(['FLY DREAM AVIATION', 'Fly Dream Aviation', 'Branding', 'Corporate brand identity for aviation.', 'images/Brands_that_we work_with/Fly-Dream-Logo-1.webp', 'fly-dream-aviation-brand', true, 4]);
            $portStmt->execute(['GUG', 'GUG', 'Branding', 'Logo design and brand guidelines.', 'images/Brands_that_we work_with/GUG-Logo-2.webp', 'gug-brand', true, 5]);
            $portStmt->execute(['HOLY AGRO', 'Holy Agro', 'Branding', 'Agricultural brand identity system.', 'images/Brands_that_we work_with/Holy-Agro-Logo.webp', 'holy-agro-brand', true, 6]);
            $message .= ' Portfolio seeded.';
        }

        // ── Seed Brands ──
        if ($pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn() == 0) {
            $brandSeeds = [
                ['JDP',              'images/Brands_that_we work_with/JDP-Logo.webp',              'Technology',       'DHAKA', 'BRANDING',     'rgba(0, 120, 255, 0.18)'],
                ['SKILL PLANET',     'images/Brands_that_we work_with/Skill-Planet-Logo-1.webp',    'Education',        'DHAKA', 'BRANDING',     'rgba(255, 150, 0, 0.18)'],
                ['HOLY BASKET',      'images/Brands_that_we work_with/Holy-Basket-Logo.webp',       'Food & Retail',    'DHAKA', 'BRANDING',     'rgba(255, 60, 60, 0.18)'],
                ['FLY DREAM AVIATION','images/Brands_that_we work_with/Fly-Dream-Logo-1.webp',      'Aviation',         'DHAKA', 'BRANDING',     'rgba(0, 180, 255, 0.18)'],
                ['BRIGHT HASH',      'images/Brands_that_we work_with/Bright-hash-Logo-1.webp',     'Technology',       'DHAKA', 'SEO & WEB',   'rgba(80, 220, 80, 0.18)'],
                ['MISKAT TOURS',     'images/Brands_that_we work_with/Miskat-Logo.webp',            'Travel & Tourism', 'DHAKA', 'BRANDING',     'rgba(180, 80, 255, 0.18)'],
                ['HOLY AGRO',        'images/Brands_that_we work_with/Holy-Agro-Logo.webp',         'Agriculture',      'DHAKA', 'BRANDING',     'rgba(80, 200, 80, 0.18)'],
                ['GUG',              'images/Brands_that_we work_with/GUG-Logo-2.webp',             'Business Group',   'DHAKA', 'BRANDING',     'rgba(255, 200, 0, 0.18)'],
            ];
            $stmt = $pdo->prepare("INSERT INTO brands (name, logo_url, industry, country, service, bloom_color, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($brandSeeds as $i => $b) {
                $stmt->execute([$b[0], $b[1], $b[2], $b[3], $b[4], $b[5], $i]);
            }
            $message .= ' Brands seeded.';
        }

        // ── Create admin user in Supabase Auth ──
        $adminCreated = false;
        try {
            $supabase = Supabase::getInstance();
            // Check if admin already exists
            $existing = $supabase->authAdminGetUserByEmail('xoosdigital@gmail.com');
            if ($existing) {
                $message .= ' Admin user already exists in Supabase.';
            } else {
                $result = $supabase->authAdminCreateUser('xoosdigital@gmail.com', 'Xoos@2025!', ['role' => 'admin']);
                if (!empty($result['id'])) {
                    $message .= ' Admin user created (xoosdigital@gmail.com / Xoos@2025!).';
                    $adminCreated = true;
                }
            }
        } catch (Exception $e) {
            $message .= ' Note: Could not create admin user in Supabase Auth. You may need to create it manually at: ' . SUPABASE_URL . '/project/default/auth/users. Error: ' . $e->getMessage();
        }

        if (empty($message)) $message = 'All tables ready. Admin user should be created in Supabase Auth.';
    } catch (Exception $e) {
        $error = true;
        $message = 'Error: ' . $e->getMessage();
    }
}

// If logged in, use admin shell; otherwise use standalone page
if ($loggedIn):
    require_once __DIR__ . '/inc/header.php'; ?>
<div class="admin-main">
<?php else: ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — Xoos Digital Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:#0A0A0A;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem}
        .card{background:#111;border:1px solid #222;border-radius:1.25rem;padding:2.5rem;max-width:500px;width:100%;position:relative;overflow:hidden;text-align:center}
        .card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#CCFF00,transparent)}
        .logo{font-family:'Orbitron',sans-serif;font-size:1.25rem;font-weight:900;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem}
        .logo span{color:#CCFF00}
        .subtitle{color:#9CA3AF;font-size:0.8rem;margin-bottom:1.5rem}
        .msg{padding:1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;line-height:1.5}
        .msg-ok{background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.2);color:#4ade80}
        .msg-err{background:rgba(255,50,50,0.1);border:1px solid rgba(255,50,50,0.2);color:#ff4444}
        .text-muted{color:#9CA3AF;font-size:0.85rem;margin-bottom:1.5rem;line-height:1.6}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:0.65rem 1.5rem;border-radius:8px;font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;cursor:pointer;border:none;text-decoration:none;transition:opacity 0.2s}
        .btn-primary{background:#CCFF00;color:#0A0A0A}
        .btn-primary:hover{opacity:0.85}
        .btn-secondary{background:#181818;color:#9CA3AF;border:1px solid #2a2a2a}
        .btn-secondary:hover{color:#fff}
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">Xoos <span>Digital</span></div>
        <div class="subtitle">Supabase Setup</div>
<?php endif; ?>

    <?php if ($message): ?>
        <p class="msg <?= $error ? 'msg-err' : 'msg-ok' ?>"><?= h($message) ?></p>
    <?php endif; ?>

    <p class="text-muted">Creates all database tables in Supabase PostgreSQL, seeds demo data, and creates the admin user in Supabase Auth (xoosdigital@gmail.com / Xoos@2025!).</p>

    <?php if ($isRestMode): ?>
    <div style="border:1px solid rgba(255,204,0,0.3);border-radius:12px;padding:1.25rem;background:rgba(255,204,0,0.05);margin-bottom:1.5rem;text-align:left;font-size:0.8rem;line-height:1.6">
        <strong style="color:#FFCC00;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.08em">⚠ REST Mode — Tables Required</strong>
        <p style="margin-top:0.5rem;color:#9CA3AF">
            Direct PostgreSQL connection is unavailable (IPv6-only host). You must create the tables first:
        </p>
        <ol style="margin-top:0.5rem;padding-left:1.25rem;color:#9CA3AF;display:grid;gap:0.4rem">
            <li>Open the <a href="https://supabase.com/dashboard/project/rhxxkolsjtppebqibonu/sql/new" target="_blank" style="color:#CCFF00">Supabase SQL Editor</a></li>
            <li>Paste and run the schema SQL file: <code style="color:#CCFF00;font-size:0.75rem">admin/supabase_schema.sql</code></li>
            <li>Come back here and click "Run Setup" to seed data + create admin user</li>
        </ol>
        <p style="margin-top:0.5rem;color:#6b7280;font-size:0.75rem">
            The schema file contains all CREATE TABLE statements, triggers, and seed data.
        </p>
    </div>
    <?php endif; ?>

    <div style="border:1px solid rgba(204,255,0,0.15);border-radius:12px;padding:1.25rem;background:rgba(204,255,0,0.03);margin-bottom:1.5rem;text-align:left;font-size:0.8rem;line-height:1.6">
        <strong style="color:#CCFF00;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.08em"><?= $isRestMode ? 'ℹ About Setup' : '⚠ Prerequisites' ?></strong>
        <ul style="margin-top:0.5rem;padding-left:1.25rem;color:#9CA3AF">
            <li>Supabase project must be running</li>
            <li>.env must have valid SUPABASE_* credentials</li>
            <?php if (!$isRestMode): ?>
            <li>PostgreSQL SSL connection must work</li>
            <?php endif; ?>
        </ul>
    </div>

    <form method="post" style="margin-top:1rem">
        <button type="submit" class="btn btn-primary"><?= $message ? 'Re-run Setup' : 'Run Setup' ?></button>
        <?php if ($message && !$error): ?>
            <a href="login.php" class="btn btn-secondary" style="margin-left:6px">Go to Login</a>
            <?php if ($loggedIn): ?>
                <a href="modules/blog.php" class="btn btn-secondary" style="margin-left:6px">Go to Blog</a>
            <?php endif; ?>
        <?php endif; ?>
    </form>

    <?php if (!$isRestMode): ?>
    <div style="margin-top:2.5rem;padding-top:1.5rem;border-top:1px solid var(--border);text-align:left;display:grid;gap:1rem">
        <div style="border:1px solid rgba(255,50,50,0.3);border-radius:12px;padding:1.5rem;background:rgba(255,50,50,0.03)">
            <h3 style="color:#ff4444;font-family:'Orbitron',sans-serif;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.5rem">⚠ Danger Zone</h3>
            <p class="text-muted" style="font-size:0.8rem;margin-bottom:1rem">This will <strong>DELETE ALL DATA</strong> and reset all tables to their initial state.</p>
            <form method="post">
                <input type="hidden" name="reset" value="1">
                <button type="submit" class="btn btn-danger" style="background:rgba(255,50,50,0.15);color:#ff4444;border:1px solid rgba(255,50,50,0.3)" onclick="return confirm('Are you sure? This will DELETE ALL existing data.')"> Drop & Recreate All Tables</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

<?php if ($loggedIn): ?>
</div></body></html>
<?php else: ?>
</div></body></html>
<?php endif; ?>
