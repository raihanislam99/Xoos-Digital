<?php
require_once __DIR__ . '/inc/functions.php';

$message = '';
$error = false;
$loggedIn = !empty($_SESSION['admin_logged_in']);
$reset_admin_done = false;
$reset_admin_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = db();

        // Drop & Recreate
        if (!empty($_POST['reset'])) {
            $dropTables = ['brands','portfolio','faq','testimonials','packages','services','blog_posts','admin_users','post_training_data','post_profiles','generated_posts'];
            foreach ($dropTables as $t) {
                $pdo->exec("DROP TABLE IF EXISTS {$t}");
            }
            $message = 'All tables dropped.';
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content TEXT,
            meta_title VARCHAR(255),
            meta_description TEXT,
            tags VARCHAR(500),
            status ENUM('draft','published') DEFAULT 'draft',
            featured_image VARCHAR(500),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            features TEXT,
            hashtags VARCHAR(500),
            price VARCHAR(50) DEFAULT '',
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS packages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            tier VARCHAR(50),
            tagline VARCHAR(255),
            price VARCHAR(50),
            features TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_name VARCHAR(255) NOT NULL,
            quote TEXT NOT NULL,
            rating TINYINT DEFAULT 5,
            service_used VARCHAR(255),
            client_image VARCHAR(500) DEFAULT NULL,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS faq (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question VARCHAR(500) NOT NULL,
            answer TEXT NOT NULL,
            category VARCHAR(100),
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS portfolio (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_name VARCHAR(255) NOT NULL,
            client VARCHAR(255),
            service VARCHAR(255),
            description TEXT,
            image_url VARCHAR(500),
            link VARCHAR(500),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS brands (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            logo_url VARCHAR(500) NOT NULL,
            industry VARCHAR(100) DEFAULT '',
            country VARCHAR(100) DEFAULT '',
            service VARCHAR(100) DEFAULT '',
            bloom_color VARCHAR(50) DEFAULT 'rgba(0,0,0,0.18)',
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(100) DEFAULT '',
            company VARCHAR(255) DEFAULT '',
            country VARCHAR(100) DEFAULT '',
            services TEXT,
            budget VARCHAR(100) DEFAULT '',
            timeline VARCHAR(100) DEFAULT '',
            message TEXT,
            ip_address VARCHAR(45) DEFAULT '',
            is_read TINYINT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS media_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            filepath VARCHAR(500) NOT NULL,
            filesize INT DEFAULT 0,
            mime_type VARCHAR(100) DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS blog_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS post_training_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            content TEXT NOT NULL,
            type VARCHAR(50) DEFAULT 'topic',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS post_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            platform VARCHAR(50) NOT NULL,
            profile_url VARCHAR(500) NOT NULL,
            name VARCHAR(255) DEFAULT '',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS generated_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            platform VARCHAR(50) NOT NULL,
            content TEXT,
            language VARCHAR(50) DEFAULT 'en',
            status ENUM('draft','published') DEFAULT 'draft',
            topic VARCHAR(255) DEFAULT '',
            training_ids TEXT,
            profile_ids TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            status VARCHAR(50) DEFAULT 'pending',
            priority VARCHAR(50) DEFAULT 'medium',
            due_date DATETIME DEFAULT NULL,
            category VARCHAR(255) DEFAULT '',
            lead_id INT DEFAULT NULL,
            assignee_type VARCHAR(50) DEFAULT NULL,
            assignee_name VARCHAR(255) DEFAULT NULL,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS task_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            note TEXT NOT NULL,
            category VARCHAR(255) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES admin_tasks(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            business_name VARCHAR(255) NOT NULL,
            owner_name VARCHAR(255) DEFAULT '',
            email VARCHAR(255) DEFAULT '',
            phone VARCHAR(100) DEFAULT '',
            whatsapp VARCHAR(100) DEFAULT '',
            website VARCHAR(500) DEFAULT '',
            facebook VARCHAR(500) DEFAULT '',
            instagram VARCHAR(500) DEFAULT '',
            city VARCHAR(100) DEFAULT '',
            country VARCHAR(100) DEFAULT '',
            address TEXT,
            niche VARCHAR(255) DEFAULT '',
            lead_score INT DEFAULT 0,
            status VARCHAR(50) DEFAULT 'new',
            source VARCHAR(100) DEFAULT '',
            tags TEXT,
            is_blacklisted TINYINT(1) DEFAULT 0,
            google_maps_url VARCHAR(500) DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS lead_emails (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            subject VARCHAR(500) DEFAULT '',
            body TEXT,
            status VARCHAR(50) DEFAULT 'draft',
            sent_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS lead_whatsapp (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            message TEXT,
            status VARCHAR(50) DEFAULT 'sent',
            sent_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS lead_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            type VARCHAR(50) DEFAULT 'note',
            content TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS outreach_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(50) DEFAULT 'email',
            subject VARCHAR(500) DEFAULT '',
            body TEXT,
            is_default TINYINT(1) DEFAULT 0,
            use_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Migrate — safely add missing columns
        $migrations = [
            'blog_posts'  => [['slug', 'VARCHAR(255)'], ['category_id', 'INT DEFAULT 0'], ['read_time', 'INT DEFAULT 0']],
            'services'    => [['sort_order', 'INT DEFAULT 0'], ['hashtags', 'VARCHAR(500)'], ['features', 'TEXT'], ['price', 'VARCHAR(50) DEFAULT \'\'']],
            'testimonials'=> [['sort_order', 'INT DEFAULT 0'], ['service_used', 'VARCHAR(255)'], ['client_image', 'VARCHAR(500) DEFAULT NULL'], ['client_country', 'VARCHAR(100) DEFAULT \'\''], ['country_flag', 'VARCHAR(10) DEFAULT \'\''], ['platform', 'VARCHAR(50) DEFAULT \'\''], ['avatar_gradient', 'VARCHAR(255) DEFAULT \'\''], ['avatar_letter', 'VARCHAR(2) DEFAULT \'\'']],
            'faq'         => [['sort_order', 'INT DEFAULT 0'], ['category', 'VARCHAR(100)']],
            'packages'    => [['tagline', 'VARCHAR(255)']],
            'admin_users' => [['recovery_hash', 'VARCHAR(64) DEFAULT NULL AFTER password_hash'], ['role', "VARCHAR(50) NOT NULL DEFAULT 'admin' AFTER recovery_hash"]],
        ];
        foreach ($migrations as $table => $cols) {
            $existing = [];
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM {$table}");
                $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (Exception $e) {}
            foreach ($cols as $col) {
                if (!in_array($col[0], $existing)) {
                    $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$col[0]} {$col[1]}");
                }
            }
        }
        // Backfill slugs for existing blog posts
        try {
            $pdo->exec("UPDATE blog_posts SET slug = CONCAT('post-', id) WHERE slug IS NULL OR slug = ''");
        } catch (Exception $e) {}

        // Seed default blog category
        try {
            if ($pdo->query("SELECT COUNT(*) FROM blog_categories")->fetchColumn() == 0) {
                $pdo->exec("INSERT INTO blog_categories (name, slug, sort_order) VALUES ('General', 'general', 0)");
            }
        } catch (Exception $e) {}

        $check = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
        if ($check == 0) {
            $hash = password_hash('Xoos@2025!', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
            $stmt->execute(['xoosdigital@gmail.com', $hash]);
            $recovery_code = generate_recovery_code();
            set_recovery_hash($recovery_code);
            $message = 'Tables created and admin user seeded (xoosdigital@gmail.com / Xoos@2025!).';
        } else {
            ensure_recovery_column();
            $message = 'All tables ready. Admin user already exists.';
        }

        // Handle Reset Admin (non-destructive)
        $reset_admin_done = false;
        $reset_admin_code = '';
        if (!empty($_POST['reset_admin'])) {
            $pdo->exec("DELETE FROM admin_users WHERE 1");
            $hash = password_hash('Xoos@2025!', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
            $stmt->execute(['xoosdigital@gmail.com', $hash]);
            $reset_admin_code = generate_recovery_code();
            set_recovery_hash($reset_admin_code);
            $reset_admin_done = true;
        }

        // ── Seed Demo Data ──

        // Services
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

        // Packages
        if ($pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn() == 0) {
            $pkgStmt = $pdo->prepare("INSERT INTO packages (name, tier, tagline, price, features) VALUES (?, ?, ?, ?, ?)");
            $pkgStmt->execute(['STARTER', 'starter', 'Perfect for new businesses that need a professional brand presence fast.', '$299', "Logo Design (3 concepts)\nBrand Color Palette\nTypography Selection\nBusiness Card Design\nSocial Media Kit\n2 Revision Rounds\n✗ Website Development\n✗ SEO Setup\n✗ Marketing Campaign"]);
            $pkgStmt->execute(['GROWTH', 'popular', 'For growing brands that need a website, identity, and digital presence that converts.', '$799', "Everything in Starter\nWordPress Website (5 pages)\nMobile Responsive Design\nSEO Foundation Setup\nGoogle Analytics Integration\nSocial Media Setup\n3 Revision Rounds\n30-Day Post-Launch Support\n✗ Custom WooCommerce Store\n✗ Ongoing Marketing Campaign"]);
            $pkgStmt->execute(['PREMIUM', 'premium', 'Full-service partnership for brands ready to dominate their market completely.', 'CUSTOM', "Everything in Growth\nCustom WooCommerce Store\nFull SEO Campaign (3 months)\nSocial Media Management\nVideo Production (1 brand film)\nPerformance Marketing Setup\nMonthly Analytics Reports\nPriority Support\nDedicated Account Manager\nUnlimited Revisions"]);
            $pkgStmt->execute(['BRAND STARTER', 'starter', 'A quick, affordable brand identity package for startups and side projects.', '$199', "Logo Design (2 concepts)\nBrand Color Palette\nTypography Selection\nSocial Media Profile Kit\nBusiness Card Design\n1 Revision Round\n✗ Website Development\n✗ SEO Setup\n✗ Marketing Campaign\n✗ Brand Guidelines"]);
            $pkgStmt->execute(['ENTERPRISE', 'premium', 'The complete digital partner for established brands scaling to the next level.', '$1,999', "Everything in Premium\nCustom Web Application\nDedicated Project Manager\nPriority 24/7 Support\nQuarterly Strategy Sessions\nAdvanced Analytics Dashboard\nAPI Integrations\nMulti-language Support\nPerformance Marketing Retainer\nSEO Retainer (6 months)\nUnlimited Brand Assets"]);
            $message .= ' Packages seeded.';
        }

        // Testimonials
        if ($pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn() == 0) {
            $testStmt = $pdo->prepare("INSERT INTO testimonials (client_name, quote, rating, service_used) VALUES (?, ?, ?, ?)");
            $testStmt->execute(['Raihan Ahmed', 'Xoos Digital completely transformed our online store. Within 2 months of launch our sales doubled. The WooCommerce build was exceptional — every feature we needed was perfectly executed.', 5, 'WordPress Dev']);
            $testStmt->execute(['Sarah Mitchell', 'We hired Xoos Digital for a complete brand overhaul. The logo, color system, and guidelines they delivered were better than agencies charging five times the price. Truly world-class creative work.', 5, 'Branding']);
            $testStmt->execute(['Farhan Hossain', 'Our Google rankings moved from page 4 to page 1 in just 3 months. Raihan\'s SEO strategy was methodical, transparent, and data-driven. We saw a 280% increase in organic traffic. Results speak for themselves.', 5, 'SEO']);
            $testStmt->execute(['Liam O\'Brien', 'Partnering with Xoos Digital on our client\'s rebrand was seamless. Professional, responsive, and creatively sharp. The identity package was delivered ahead of schedule with zero revisions needed.', 5, 'Branding']);
            $testStmt->execute(['Nusrat Jahan', 'From our logo to our packaging, Xoos Digital understood our brand vision perfectly. The final identity felt authentic and professional. Our customers constantly compliment how polished our brand has become.', 5, 'Branding']);
            $testStmt->execute(['Mohammed Al-Rashid', 'I have worked with agencies across the Middle East and South Asia. Xoos Digital stands out for communication quality and delivery speed. The WordPress site is blazing fast and beautifully designed.', 5, 'WordPress Dev']);
            $testStmt->execute(['James Thornton', 'As a designer myself I am extremely picky. Xoos Digital not only met my standards — they exceeded them. The brand system had clear hierarchy, thoughtful typography, and scales beautifully across all media.', 5, 'Branding']);
            $testStmt->execute(['Sadia Islam', 'Xoos Digital understood our edtech brand from day one. The visual identity communicates innovation and trust simultaneously — exactly what we needed to build credibility with both students and investors.', 5, 'Branding']);
            $message .= ' Testimonials seeded.';
        }

        // FAQ
        if ($pdo->query("SELECT COUNT(*) FROM faq")->fetchColumn() == 0) {
            $faqStmt = $pdo->prepare("INSERT INTO faq (question, answer, sort_order) VALUES (?, ?, ?)");
            $faqStmt->execute(['How long does a project take?', 'It depends on the scope. A brand identity takes 5–7 business days. A WordPress website takes 2–4 weeks. SEO is ongoing with results typically visible in 60–90 days. We provide a clear timeline before every project starts.', 0]);
            $faqStmt->execute(['How much does a project cost?', 'Our projects start from $299 for branding and $499 for website development. We offer the packages above or custom quotes for complex projects. We believe in transparent pricing — no hidden fees, ever.', 1]);
            $faqStmt->execute(['Do you work with international clients?', 'Absolutely. We currently work with clients across 12+ countries including the USA, UK, Australia, UAE, Canada, Germany, and Japan. All communication happens via email, WhatsApp, or video call — location is never a barrier.', 2]);
            $faqStmt->execute(['What do you need from me to get started?', 'Just a brief conversation about your business, your goals, and any existing brand materials you have. We\'ll ask the right questions and guide you through the rest. Getting started takes less than 10 minutes.', 3]);
            $faqStmt->execute(['How many revisions do I get?', 'Starter projects include 2 revision rounds. Growth and Premium packages include 3+ rounds. We work collaboratively so revisions are typically minor by the time they come — we get it right early.', 4]);
            $faqStmt->execute(['How do I make payment?', 'We accept bank transfer, bKash, Nagad, Payoneer, Wise, and PayPal. Payment structure is 50% upfront to begin the project and 50% upon final delivery. No payment is ever asked 100% upfront.', 5]);
            $faqStmt->execute(['Can you maintain my website after launch?', 'Yes. We offer monthly maintenance packages starting from $49/month covering updates, security monitoring, backups, and minor content changes. Many of our clients stay with us long-term for ongoing growth support.', 6]);
            $faqStmt->execute(['Why choose Xoos Digital over other agencies?', 'We combine creative quality with strategic thinking and honest communication. You work directly with Raihan — the founder — not a junior account manager. 80+ clients across 12 countries trust us, and every single project gets our full attention.', 7]);
            $message .= ' FAQ seeded.';
        }

        // Blog Posts (3 published)
        if ($pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn() == 0) {
            $blogStmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, content, meta_title, meta_description, tags, status, featured_image) VALUES (?, ?, ?, ?, ?, ?, 'published', ?)");
            $blogStmt->execute(['Why Branding & Identity Design Matters More Than Ever', 'why-branding-matters', '<h2>The Foundation of Brand Recognition</h2><p>Your brand identity is the visual voice of your business. It is the first impression, the lasting memory, and the emotional connection that turns a first-time visitor into a loyal customer. In a crowded digital landscape, a cohesive brand identity is no longer optional — it is essential.</p><h2>The Science Behind Great Design</h2><p>Studies show that consistent brand presentation across all platforms can increase revenue by up to 23%. From your logo and color palette to your typography and imagery — every element communicates something about your values, quality, and professionalism.</p><h2>Why Professional Branding Wins</h2><p>DIY branding may save money upfront, but it costs you credibility in the long run. Professional brand identity design ensures your business looks established, trustworthy, and memorable from day one.</p>', 'Why Branding & Identity Design Matters | Xoos Digital', 'Learn why professional branding and identity design is crucial for business growth in 2025. Expert insights from Xoos Digital.', 'branding, identity design, logo design, visual identity, brand strategy', 'images/Xoos_Digital_Facebook_Cover_Image.jpg']);
            $blogStmt->execute(['The Complete Guide to Building a High-Converting Website', 'high-converting-website-guide', '<h2>What Makes a Website Convert?</h2><p>A high-converting website is not just about looking good — it is about guiding visitors toward a specific action. Whether that is making a purchase, filling out a form, or contacting your sales team, every element must serve a purpose.</p><h2>Key Elements of Conversion-Focused Design</h2><p>Fast loading speeds, clear calls-to-action, mobile responsiveness, and intuitive navigation are the cornerstones of a website that sells. Combined with compelling copy and social proof, these elements create a seamless user experience that drives results.</p><h2>WordPress: The Platform for Growth</h2><p>WordPress powers over 40% of all websites on the internet — and for good reason. It offers unmatched flexibility, SEO capabilities, and scalability. Whether you need a simple business site or a full e-commerce store, WordPress delivers.</p>', 'High-Converting Website Guide | Xoos Digital', 'Learn how to build a website that converts visitors into customers. Expert tips on design, performance, and user experience.', 'web development, wordpress, conversion optimization, web design, ux', 'images/Xoos_Digital_Facebook_Cover_Image.jpg']);
            $blogStmt->execute(['Effective SEO & Digital Marketing Strategies to Grow Your Brand', 'seo-digital-marketing-strategies', '<h2>The Power of Organic Growth</h2><p>Search engine optimization (SEO) is the most cost-effective way to drive long-term, sustainable traffic to your website. Unlike paid advertising, the results of good SEO compound over time — creating a growing stream of free leads month after month.</p><h2>Digital Marketing That Delivers ROI</h2><p>From social media campaigns to Google Ads, digital marketing allows you to reach your target audience with precision. The key is data-driven decision making — constantly testing, measuring, and optimizing to maximize your return on investment.</p><h2>Integrating SEO and Marketing</h2><p>The most successful brands integrate their SEO and digital marketing strategies. Your content should serve both organic search rankings and social media engagement. When these channels work together, your brand grows faster, stronger, and more profitably.</p>', 'SEO & Digital Marketing Strategies | Xoos Digital', 'Discover effective SEO and digital marketing strategies to grow your brand online. Expert advice from Xoos Digital.', 'seo, digital marketing, social media, organic growth, ppc', 'images/Xoos_Digital_Facebook_Cover_Image.jpg']);
            $blogStmt->execute(['How to Choose the Right Color Palette for Your Brand', 'choose-brand-color-palette', '<h2>Color Psychology in Branding</h2><p>Colors evoke emotions. Blue conveys trust and professionalism. Red creates urgency and excitement. Green represents growth and nature. Understanding color psychology is the first step to choosing a palette that aligns with your brand values and speaks to your target audience.</p><h2>Building a Cohesive Palette</h2><p>A strong brand color palette typically includes one primary color, two secondary colors, and one or two accent colors. Tools like Adobe Color and Coolors can help you find harmonious combinations that work across digital and print media.</p><h2>Testing Across Touchpoints</h2><p>Before finalizing your palette, test it across different applications — website, social media, business cards, packaging, and signage. A palette that works beautifully on screen might not translate well to print. Always check contrast ratios for accessibility too.</p>', 'How to Choose Your Brand Color Palette | Xoos Digital', 'Learn how to choose the perfect color palette for your brand. Expert guide on color psychology and brand identity.', 'brand colors, color palette, brand identity, color psychology, logo design', 'images/Xoos_Digital_Facebook_Cover_Image.jpg']);
            $blogStmt->execute(['The Ultimate Guide to WooCommerce SEO for Online Stores', 'woocommerce-seo-guide', '<h2>Why WooCommerce SEO Matters</h2><p>With over 5 million active installations, WooCommerce powers a significant portion of the internet\'s online stores. But without proper SEO, your products will never reach the customers searching for them. WooCommerce SEO is the difference between a store that waits and a store that sells.</p><h2>Product Page Optimization</h2><p>Each product page is a landing page opportunity. Optimize your product titles, meta descriptions, image alt text, and URLs. Use unique product descriptions — never copy from the manufacturer. Schema markup for products helps search engines display rich results including price and availability.</p><h2>Technical WooCommerce SEO</h2><p>Site speed, mobile responsiveness, and clean URL structures are critical for WooCommerce stores. Use caching plugins, optimize images, and ensure your site passes Core Web Vitals. A fast store not only ranks better — it converts better too.</p>', 'WooCommerce SEO Guide | Xoos Digital', 'Complete guide to optimizing your WooCommerce store for search engines. Boost rankings and drive more sales.', 'woocommerce, seo, ecommerce, online store, wordpress', 'images/Xoos_Digital_Facebook_Cover_Image.jpg']);
            $blogStmt->execute(['Video Marketing Trends to Watch in 2025', 'video-marketing-trends-2025', '<h2>The Rise of Short-Form Video</h2><p>Short-form video continues to dominate social media algorithms. Platforms like Instagram Reels, TikTok, and YouTube Shorts prioritize quick, engaging content that hooks viewers in the first 3 seconds. Brands that master short-form storytelling see significantly higher reach and engagement rates.</p><h2>Live Streaming for Brand Connection</h2><p>Live video builds authentic connections that pre-recorded content cannot match. From product launches to Q&A sessions, live streaming humanizes your brand and creates real-time engagement. Audiences spend 3x longer watching live video compared to pre-recorded content.</p><h2>AI-Powered Video Production</h2><p>Artificial intelligence is transforming video production — from scriptwriting and voiceovers to automated editing and personalized video content. AI tools make professional video production more accessible and affordable for businesses of all sizes.</p>', 'Video Marketing Trends 2025 | Xoos Digital', 'Discover the top video marketing trends shaping 2025. Short-form video, live streaming, and AI-powered production.', 'video marketing, social media, reels, live streaming, ai video', 'images/Xoos_Digital_Facebook_Cover_Image.jpg']);
            $message .= ' Blog posts seeded.';
        }

        // Portfolio
        if ($pdo->query("SELECT COUNT(*) FROM portfolio")->fetchColumn() == 0) {
            $portStmt = $pdo->prepare("INSERT INTO portfolio (project_name, client, service, description, image_url) VALUES (?, ?, ?, ?, ?)");
            $portStmt->execute(['XOOS DIGITAL BRAND', 'Xoos Digital', 'Branding', 'Full brand identity & digital presence for Xoos Digital — a complete visual system from logo to digital assets.', 'images/Xoos_Digital_Facebook_Cover_Image.jpg']);
            $portStmt->execute(['BRIGHT HASH', 'Bright Hash Ltd.', 'Branding', 'Logo design and comprehensive brand system for Bright Hash, a technology company based in Dhaka.', 'images/Brands_that_we work_with/Bright-hash-Logo-1.webp']);
            $portStmt->execute(['HOLY BASKET', 'Holy Basket', 'Branding', 'Complete visual identity system for Holy Basket, a food & retail brand. Logo, packaging, and brand guidelines.', 'images/Brands_that_we work_with/Holy-Basket-Logo.webp']);
            $portStmt->execute(['FLY DREAM AVIATION', 'Fly Dream Aviation', 'Branding', 'Corporate brand identity for Fly Dream Aviation — modern, trustworthy, and aviation-inspired design system.', 'images/Brands_that_we work_with/Fly-Dream-Logo-1.webp']);
            $portStmt->execute(['GUG', 'GUG', 'Branding', 'Logo design and brand guidelines for GUG, a diversified business group. Clean, professional, and scalable identity.', 'images/Brands_that_we work_with/GUG-Logo-2.webp']);
            $portStmt->execute(['HOLY AGRO', 'Holy Agro', 'Branding', 'Agricultural brand identity system for Holy Agro. Earthy tones, strong typography, and versatile brand assets.', 'images/Brands_that_we work_with/Holy-Agro-Logo.webp']);
            $message .= ' Portfolio seeded.';
        }

        // Brands
        if ($pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn() == 0) {
            $brandSeeds = [
                ['JDP',              'images/Brands_that_we work_with/JDP-Logo.webp',              'Technology',       '🇧🇩 DHAKA', 'BRANDING',     'rgba(0, 120, 255, 0.18)'],
                ['SKILL PLANET',     'images/Brands_that_we work_with/Skill-Planet-Logo-1.webp',    'Education',        '🇧🇩 DHAKA', 'BRANDING',     'rgba(255, 150, 0, 0.18)'],
                ['HOLY BASKET',      'images/Brands_that_we work_with/Holy-Basket-Logo.webp',       'Food & Retail',    '🇧🇩 DHAKA', 'BRANDING',     'rgba(255, 60, 60, 0.18)'],
                ['FLY DREAM AVIATION','images/Brands_that_we work_with/Fly-Dream-Logo-1.webp',      'Aviation',         '🇧🇩 DHAKA', 'BRANDING',     'rgba(0, 180, 255, 0.18)'],
                ['BRIGHT HASH',      'images/Brands_that_we work_with/Bright-hash-Logo-1.webp',     'Technology',       '🇧🇩 DHAKA', 'SEO & WEB',   'rgba(80, 220, 80, 0.18)'],
                ['MISKAT TOURS',     'images/Brands_that_we work_with/Miskat-Logo.webp',            'Travel & Tourism', '🇧🇩 DHAKA', 'BRANDING',     'rgba(180, 80, 255, 0.18)'],
                ['HOLY AGRO',        'images/Brands_that_we work_with/Holy-Agro-Logo.webp',         'Agriculture',      '🇧🇩 DHAKA', 'BRANDING',     'rgba(80, 200, 80, 0.18)'],
                ['GUG',              'images/Brands_that_we work_with/GUG-Logo-2.webp',             'Business Group',   '🇧🇩 DHAKA', 'BRANDING',     'rgba(255, 200, 0, 0.18)'],
            ];
            $stmt = $pdo->prepare("INSERT INTO brands (name, logo_url, industry, country, service, bloom_color, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($brandSeeds as $i => $b) {
                $stmt->execute([$b[0], $b[1], $b[2], $b[3], $b[4], $b[5], $i]);
            }
            $message .= ' Brands seeded.';
        }
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
        <div class="subtitle">Admin Setup</div>
<?php endif; ?>

    <?php if ($message): ?>
        <p class="msg <?= $error ? 'msg-err' : 'msg-ok' ?>"><?= h($message) ?></p>
    <?php endif; ?>

    <?php if (isset($recovery_code) && $recovery_code): ?>
        <div style="border:1px solid rgba(204,255,0,0.3);border-radius:12px;padding:1.25rem;background:rgba(204,255,0,0.04);margin-bottom:1rem;text-align:left">
            <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#CCFF00;margin-bottom:0.5rem">⚠ Save Your Recovery Code</div>
            <p style="font-size:0.75rem;color:#9CA3AF;margin-bottom:0.75rem;line-height:1.5">This code is <strong>shown only once</strong>. Store it in a password manager. If you lose your password, this is the only way to regain access.</p>
            <div style="background:#0A0A0A;border:1px solid #2a2a2a;border-radius:8px;padding:0.75rem 1rem;font-family:monospace;font-size:1.1rem;text-align:center;color:#fff;letter-spacing:0.15em;user-select:all"><?= h($recovery_code) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($reset_admin_done && $reset_admin_code): ?>
        <div style="border:1px solid rgba(74,222,128,0.3);border-radius:12px;padding:1.25rem;background:rgba(74,222,128,0.04);margin-bottom:1rem;text-align:left">
            <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#4ade80;margin-bottom:0.5rem">✔ Admin Reset Complete</div>
            <p style="font-size:0.75rem;color:#9CA3AF;margin-bottom:0.75rem;line-height:1.5">Admin user has been reset to <strong>xoosdigital@gmail.com / Xoos@2025!</strong>. All other data is preserved.</p>
            <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#CCFF00;margin-bottom:0.4rem">New Recovery Code (save this)</div>
            <div style="background:#0A0A0A;border:1px solid #2a2a2a;border-radius:8px;padding:0.75rem 1rem;font-family:monospace;font-size:1.1rem;text-align:center;color:#fff;letter-spacing:0.15em;user-select:all"><?= h($reset_admin_code) ?></div>
        </div>
    <?php endif; ?>

    <p class="text-muted">Creates all database tables, adds missing columns, and seeds the admin user (xoosdigital@gmail.com / Xoos@2025!).</p>
    <form method="post" style="margin-top:1rem">
        <button type="submit" class="btn btn-primary"><?= $message ? 'Re-run Setup' : 'Run Setup' ?></button>
        <?php if ($message && !$error): ?>
            <a href="login.php" class="btn btn-secondary" style="margin-left:6px">Go to Login</a>
            <?php if ($loggedIn): ?>
                <a href="modules/blog.php" class="btn btn-secondary" style="margin-left:6px">Go to Blog</a>
            <?php endif; ?>
        <?php endif; ?>
    </form>

    <div style="margin-top:2.5rem;padding-top:1.5rem;border-top:1px solid var(--border);text-align:left;display:grid;gap:1rem">
        <div style="border:1px solid rgba(255,50,50,0.3);border-radius:12px;padding:1.5rem;background:rgba(255,50,50,0.03)">
            <h3 style="color:#ff4444;font-family:'Orbitron',sans-serif;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.5rem">⚠ Danger Zone</h3>
            <p class="text-muted" style="font-size:0.8rem;margin-bottom:1rem">This will <strong>DELETE ALL DATA</strong> and reset all tables to their initial state.</p>
            <form method="post">
                <input type="hidden" name="reset" value="1">
                <button type="submit" class="btn btn-danger" style="background:rgba(255,50,50,0.15);color:#ff4444;border:1px solid rgba(255,50,50,0.3)" onclick="return confirm('Are you sure? This will DELETE ALL existing data.')">🗑 Drop & Recreate All Tables</button>
            </form>
        </div>
        <div style="border:1px solid rgba(255,200,0,0.25);border-radius:12px;padding:1.5rem;background:rgba(255,200,0,0.03)">
            <h3 style="color:#facc15;font-family:'Orbitron',sans-serif;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.5rem">🔑 Reset Admin Only</h3>
            <p class="text-muted" style="font-size:0.8rem;margin-bottom:1rem">Resets the admin user to <strong>xoosdigital@gmail.com / Xoos@2025!</strong> with a new recovery code. <strong>All other data is preserved.</strong></p>
            <form method="post">
                <input type="hidden" name="reset_admin" value="1">
                <button type="submit" class="btn btn-secondary" style="color:#facc15;border:1px solid rgba(255,200,0,0.3);background:rgba(255,200,0,0.06);" onclick="return confirm('Reset the admin user only? All other data will be kept.')">Reset Admin User</button>
            </form>
        </div>
    </div>

<?php if ($loggedIn): ?>
</div></body></html>
<?php else: ?>
</div></body></html>
<?php endif; ?>
