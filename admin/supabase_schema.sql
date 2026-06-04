-- ============================================================
-- Xoos Digital — Supabase PostgreSQL Schema + Seed Data
-- Paste this entire script into the Supabase Dashboard SQL Editor
-- (https://supabase.com/dashboard/project/rhxxkolsjtppebqibonu/sql/new)
-- ============================================================

-- ── Drop if resetting ──
-- Uncomment these if you want to reset:
/*
DROP TABLE IF EXISTS invoice_items CASCADE;
DROP TABLE IF EXISTS invoices CASCADE;
DROP TABLE IF EXISTS quotation_items CASCADE;
DROP TABLE IF EXISTS quotations CASCADE;
DROP TABLE IF EXISTS company_info CASCADE;
DROP TABLE IF EXISTS outreach_templates CASCADE;
DROP TABLE IF EXISTS lead_activity CASCADE;
DROP TABLE IF EXISTS lead_whatsapp CASCADE;
DROP TABLE IF EXISTS lead_emails CASCADE;
DROP TABLE IF EXISTS leads CASCADE;
DROP TABLE IF EXISTS post_hashtags CASCADE;
DROP TABLE IF EXISTS generated_posts CASCADE;
DROP TABLE IF EXISTS post_profiles CASCADE;
DROP TABLE IF EXISTS post_training_data CASCADE;
DROP TABLE IF EXISTS media_files CASCADE;
DROP TABLE IF EXISTS settings CASCADE;
DROP TABLE IF EXISTS blog_posts CASCADE;
DROP TABLE IF EXISTS blog_categories CASCADE;
DROP TABLE IF EXISTS admin_tasks CASCADE;
DROP TABLE IF EXISTS brands CASCADE;
DROP TABLE IF EXISTS portfolio CASCADE;
DROP TABLE IF EXISTS faq CASCADE;
DROP TABLE IF EXISTS testimonials CASCADE;
DROP TABLE IF EXISTS packages CASCADE;
DROP TABLE IF EXISTS services CASCADE;
DROP TABLE IF EXISTS contact_messages CASCADE;
*/

-- ── Trigger function for updated_at ──
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ── Tables ───────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS services (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    features TEXT,
    hashtags VARCHAR(500),
    price VARCHAR(50) DEFAULT '',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS packages (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    tier VARCHAR(50),
    tagline VARCHAR(255),
    price VARCHAR(50),
    features TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS testimonials (
    id SERIAL PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    quote TEXT NOT NULL,
    rating SMALLINT DEFAULT 5,
    service_used VARCHAR(255),
    client_image VARCHAR(500),
    client_country VARCHAR(100) DEFAULT '',
    country_flag VARCHAR(10) DEFAULT '',
    platform VARCHAR(50) DEFAULT '',
    avatar_gradient VARCHAR(255) DEFAULT '',
    avatar_letter VARCHAR(2) DEFAULT '',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS faq (
    id SERIAL PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS portfolio (
    id SERIAL PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    client VARCHAR(255),
    service VARCHAR(255),
    description TEXT,
    image_url VARCHAR(500),
    link VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS brands (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    logo_url VARCHAR(500) NOT NULL,
    industry VARCHAR(100) DEFAULT '',
    country VARCHAR(100) DEFAULT '',
    service VARCHAR(100) DEFAULT '',
    bloom_color VARCHAR(50) DEFAULT 'rgba(0,0,0,0.18)',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id SERIAL PRIMARY KEY,
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
    is_read SMALLINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS media_files (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    filesize INT DEFAULT 0,
    mime_type VARCHAR(100) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS blog_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS blog_posts (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    tags VARCHAR(500),
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','published')),
    featured_image VARCHAR(500),
    category_id INT DEFAULT 0,
    read_time INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS post_training_data (
    id SERIAL PRIMARY KEY,
    content TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'topic',
    profile_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS post_profiles (
    id SERIAL PRIMARY KEY,
    platform VARCHAR(50) NOT NULL,
    profile_url VARCHAR(500) NOT NULL,
    name VARCHAR(255) DEFAULT '',
    notes TEXT,
    language VARCHAR(50) DEFAULT 'english',
    tone VARCHAR(50) DEFAULT 'semi-professional',
    niche VARCHAR(500) DEFAULT '',
    color VARCHAR(7) DEFAULT '#c8f135',
    post_length INT DEFAULT 200,
    type VARCHAR(20) DEFAULT 'personal',
    business_type VARCHAR(200) DEFAULT '',
    target_audience TEXT DEFAULT '',
    brand_voice TEXT DEFAULT '',
    avoid_topics TEXT DEFAULT '',
    avatar_url VARCHAR(500) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS generated_posts (
    id SERIAL PRIMARY KEY,
    platform VARCHAR(50) NOT NULL,
    content TEXT,
    language VARCHAR(50) DEFAULT 'en',
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','published')),
    topic VARCHAR(255) DEFAULT '',
    training_ids TEXT,
    profile_ids TEXT,
    profile_id INT DEFAULT NULL,
    linkedin_content TEXT,
    facebook_content TEXT,
    hashtags_used TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS post_hashtags (
    id SERIAL PRIMARY KEY,
    platform VARCHAR(50) NOT NULL,
    tag VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_tasks (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    priority VARCHAR(50) DEFAULT 'medium',
    due_date TIMESTAMP DEFAULT NULL,
    category VARCHAR(255) DEFAULT '',
    lead_id INT DEFAULT NULL,
    assignee_type VARCHAR(50) DEFAULT NULL,
    assignee_name VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS post_versions (
    id SERIAL PRIMARY KEY,
    post_id INT NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS leads (
    id SERIAL PRIMARY KEY,
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
    is_blacklisted SMALLINT DEFAULT 0,
    google_maps_url VARCHAR(500) DEFAULT '',
    has_website SMALLINT DEFAULT 0,
    website_score INT DEFAULT 0,
    ai_audit TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS lead_emails (
    id SERIAL PRIMARY KEY,
    lead_id INT NOT NULL,
    subject VARCHAR(500) DEFAULT '',
    body TEXT,
    status VARCHAR(50) DEFAULT 'draft',
    sent_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS lead_whatsapp (
    id SERIAL PRIMARY KEY,
    lead_id INT NOT NULL,
    message TEXT,
    status VARCHAR(50) DEFAULT 'sent',
    sent_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS lead_activity (
    id SERIAL PRIMARY KEY,
    lead_id INT NOT NULL,
    type VARCHAR(50) DEFAULT 'note',
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS outreach_templates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'email',
    subject VARCHAR(500) DEFAULT '',
    body TEXT,
    is_default SMALLINT DEFAULT 0,
    use_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS company_info (
    id INT PRIMARY KEY DEFAULT 1,
    company_name VARCHAR(200) DEFAULT '',
    address TEXT,
    phone VARCHAR(100) DEFAULT '',
    email VARCHAR(100) DEFAULT '',
    website VARCHAR(200) DEFAULT '',
    logo VARCHAR(500) DEFAULT '',
    tax_id VARCHAR(100) DEFAULT '',
    bank_name VARCHAR(200) DEFAULT '',
    bank_account VARCHAR(100) DEFAULT '',
    bank_routing VARCHAR(100) DEFAULT '',
    city VARCHAR(100) DEFAULT '',
    state VARCHAR(100) DEFAULT '',
    zip VARCHAR(20) DEFAULT '',
    country VARCHAR(100) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO company_info (id) VALUES (1) ON CONFLICT (id) DO NOTHING;

CREATE TABLE IF NOT EXISTS quotations (
    id SERIAL PRIMARY KEY,
    quote_number VARCHAR(50) NOT NULL,
    date DATE DEFAULT NULL,
    valid_until DATE DEFAULT NULL,
    client_name VARCHAR(200) DEFAULT '',
    client_email VARCHAR(200) DEFAULT '',
    client_phone VARCHAR(100) DEFAULT '',
    client_address TEXT,
    notes TEXT,
    terms TEXT,
    subtotal DECIMAL(12,2) DEFAULT 0,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'USD',
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','sent','accepted','rejected')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS quotation_items (
    id SERIAL PRIMARY KEY,
    quotation_id INT NOT NULL REFERENCES quotations(id) ON DELETE CASCADE,
    description TEXT,
    quantity DECIMAL(10,2) DEFAULT 1,
    unit VARCHAR(50) DEFAULT '',
    rate DECIMAL(12,2) DEFAULT 0,
    amount DECIMAL(12,2) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS invoices (
    id SERIAL PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL,
    date DATE DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    client_name VARCHAR(200) DEFAULT '',
    client_email VARCHAR(200) DEFAULT '',
    client_phone VARCHAR(100) DEFAULT '',
    client_address TEXT,
    notes TEXT,
    terms TEXT,
    subtotal DECIMAL(12,2) DEFAULT 0,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'USD',
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','sent','paid','overdue','cancelled')),
    paid_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS invoice_items (
    id SERIAL PRIMARY KEY,
    invoice_id INT NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    description TEXT,
    quantity DECIMAL(10,2) DEFAULT 1,
    unit VARCHAR(50) DEFAULT '',
    rate DECIMAL(12,2) DEFAULT 0,
    amount DECIMAL(12,2) DEFAULT 0
);

-- ── updated_at triggers ──

CREATE TRIGGER trg_services_updated_at
    BEFORE UPDATE ON services FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_packages_updated_at
    BEFORE UPDATE ON packages FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_testimonials_updated_at
    BEFORE UPDATE ON testimonials FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_faq_updated_at
    BEFORE UPDATE ON faq FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_portfolio_updated_at
    BEFORE UPDATE ON portfolio FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_brands_updated_at
    BEFORE UPDATE ON brands FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_blog_posts_updated_at
    BEFORE UPDATE ON blog_posts FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_settings_updated_at
    BEFORE UPDATE ON settings FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_leads_updated_at
    BEFORE UPDATE ON leads FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_outreach_templates_updated_at
    BEFORE UPDATE ON outreach_templates FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_company_info_updated_at
    BEFORE UPDATE ON company_info FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_quotations_updated_at
    BEFORE UPDATE ON quotations FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trg_invoices_updated_at

-- ── Performance Indexes ───────────────────────────────────────

CREATE INDEX IF NOT EXISTS idx_post_versions_post_id ON post_versions (post_id);
CREATE INDEX IF NOT EXISTS idx_generated_posts_status ON generated_posts (status);
CREATE INDEX IF NOT EXISTS idx_lead_activity_lead_id ON lead_activity (lead_id);
CREATE INDEX IF NOT EXISTS idx_lead_emails_lead_id ON lead_emails (lead_id);
CREATE INDEX IF NOT EXISTS idx_lead_whatsapp_lead_id ON lead_whatsapp (lead_id);
CREATE INDEX IF NOT EXISTS idx_quotation_items_quotation_id ON quotation_items (quotation_id);
CREATE INDEX IF NOT EXISTS idx_invoice_items_invoice_id ON invoice_items (invoice_id);

-- ── Seed Data ────────────────────────────────────────────────

-- Blog Category
INSERT INTO blog_categories (name, slug, sort_order)
VALUES ('General', 'general', 0)
ON CONFLICT (slug) DO NOTHING;

-- Services
INSERT INTO services (name, description, features, hashtags, price, sort_order) VALUES
('BRANDING IDENTITY',
 'Strategic brand identity that makes your business unforgettable. We build the visual foundation your brand needs — logo, color system, typography, and guidelines that scale across every touchpoint from business cards to billboards.',
 E'Logo Design (3 unique concepts)\nBrand Color Palette & Typography\nBrand Guidelines Document\nBusiness Card & Stationery\nSocial Media Profile Kit\nDelivered in 5–7 business days',
 '#BrandIdentityDesign #LogoAndBranding #VisualIdentity #BrandStrategy',
 '$299', 0),
('WORDPRESS & E-COMMERCE',
 'Custom WordPress websites and WooCommerce stores built for performance, conversion, and growth. Mobile-first, blazing fast, and designed to turn visitors into customers — not just look pretty.',
 E'Custom WordPress Theme Design\nWooCommerce Store Setup\nMobile-Responsive Development\nSEO Foundation & Speed Optimization\nPayment Gateway Integration\nDelivered in 2–4 weeks',
 '#CustomWordPress #WooCommerce #EcommerceExperts #WebDevelopment',
 '$499', 1),
('DIGITAL MARKETING',
 'Data-driven marketing campaigns that connect you with the right audience at the right time. From Facebook and Instagram ads to Google PPC — every campaign is tied to KPIs that actually matter to your business.',
 E'Social Media Strategy & Management\nFacebook & Instagram Ad Campaigns\nGoogle Ads (Search & Display)\nContent Creation & Copywriting\nMonthly Performance Reports\nA/B Testing & Optimization',
 '#SocialMediaMarketing #PPCCampaigns #ContentStrategy #GrowthMarketing',
 '$399/mo', 2),
('SEO & ORGANIC GROWTH',
 'Long-term organic growth that compounds over time. Our SEO strategy combines technical optimization, content planning, and link building to move your business up the rankings and keep it there — driving free traffic month after month.',
 E'Technical SEO Audit & Fixes\nKeyword Research & Strategy\nOn-Page Optimization\nContent SEO Planning\nGoogle Search Console Setup\nMonthly Ranking Reports',
 '#TechnicalSEO #KeywordResearch #OrganicGrowth #SearchRankings',
 '$349/mo', 3),
('VIDEO PRODUCTION',
 'Video content that stops the scroll and tells your brand story in a way that text and images never can. From cinematic brand films to social media reels and motion graphics — we make your brand move.',
 E'Brand Film & Corporate Video\nSocial Media Reels (10 videos)\nMotion Graphics & Animation\nScript & Storyboard Development\nProfessional Editing & Color Grade\nDelivered in 1–2 weeks',
 '#BrandFilms #MotionGraphics #SocialContent #VideoStrategy',
 '$599', 4),
('UI/UX DESIGN',
 'User-centered design that makes your digital products intuitive, accessible, and delightful. From wireframes to high-fidelity prototypes — we design experiences that users love and businesses benefit from.',
 E'User Research & Personas\nWireframing & Prototyping\nVisual UI Design\nInteraction Design\nUsability Testing\nDesign System Creation',
 '#UIDesign #UXDesign #ProductDesign #UserExperience #Figma',
 '$449', 5),
('SOCIAL MEDIA MANAGEMENT',
 'Strategic social media management that builds communities and drives engagement. We handle content creation, scheduling, community management, and analytics so you can focus on running your business.',
 E'Content Calendar Planning\nGraphic & Video Content Creation\nDaily Community Management\nHashtag & Trend Research\nMonthly Analytics Reports\nCompetitor Analysis',
 '#SocialMediaManagement #ContentCreation #CommunityManagement #InstagramMarketing #FacebookMarketing',
 '$299/mo', 6),
('CONTENT WRITING & COPYWRITING',
 'Compelling content that tells your brand story and drives action. From website copy and blog posts to email campaigns and ad copy — every word is crafted to convert.',
 E'Website Copywriting\nBlog Post Writing\nEmail Newsletter Copy\nAd Copy (Google & Social)\nProduct Descriptions\nBrand Voice Guide',
 '#Copywriting #ContentWriting #BrandStorytelling #EmailMarketing #BlogWriting',
 '$199', 7);

-- Packages
INSERT INTO packages (name, tier, tagline, price, features) VALUES
('STARTER', 'starter', 'Perfect for new businesses that need a professional brand presence fast.', '$299',
 E'Logo Design (3 concepts)\nBrand Color Palette\nTypography Selection\nBusiness Card Design\nSocial Media Kit\n2 Revision Rounds'),
('GROWTH', 'popular', 'For growing brands that need a website, identity, and digital presence that converts.', '$799',
 E'Everything in Starter\nWordPress Website (5 pages)\nMobile Responsive Design\nSEO Foundation Setup\nGoogle Analytics Integration\nSocial Media Setup\n3 Revision Rounds\n30-Day Post-Launch Support'),
('PREMIUM', 'premium', 'Full-service partnership for brands ready to dominate their market completely.', 'CUSTOM',
 E'Everything in Growth\nCustom WooCommerce Store\nFull SEO Campaign (3 months)\nSocial Media Management\nVideo Production (1 brand film)\nPerformance Marketing Setup\nMonthly Analytics Reports\nPriority Support\nDedicated Account Manager\nUnlimited Revisions'),
('BRAND STARTER', 'starter', 'A quick, affordable brand identity package for startups and side projects.', '$199',
 E'Logo Design (2 concepts)\nBrand Color Palette\nTypography Selection\nSocial Media Profile Kit\nBusiness Card Design\n1 Revision Round'),
('ENTERPRISE', 'premium', 'The complete digital partner for established brands scaling to the next level.', '$1,999',
 E'Everything in Premium\nCustom Web Application\nDedicated Project Manager\nPriority 24/7 Support\nQuarterly Strategy Sessions\nAdvanced Analytics Dashboard\nAPI Integrations\nMulti-language Support\nPerformance Marketing Retainer\nSEO Retainer (6 months)\nUnlimited Brand Assets');

-- Testimonials
INSERT INTO testimonials (client_name, quote, rating, service_used) VALUES
('Raihan Ahmed', 'Xoos Digital completely transformed our online store. Within 2 months of launch our sales doubled.', 5, 'WordPress Dev'),
('Sarah Mitchell', 'We hired Xoos Digital for a complete brand overhaul. The logo, color system, and guidelines they delivered were exceptional.', 5, 'Branding'),
('Farhan Hossain', 'Our Google rankings moved from page 4 to page 1 in just 3 months. Saw a 280% increase in organic traffic.', 5, 'SEO'),
('Liam O''Brien', 'Partnering with Xoos Digital on our client''s rebrand was seamless. Professional, responsive, and creatively sharp.', 5, 'Branding'),
('Nusrat Jahan', 'From our logo to our packaging, Xoos Digital understood our brand vision perfectly.', 5, 'Branding'),
('Mohammed Al-Rashid', 'Xoos Digital stands out for communication quality and delivery speed. The WordPress site is blazing fast.', 5, 'WordPress Dev'),
('James Thornton', 'As a designer myself I am extremely picky. Xoos Digital exceeded my standards.', 5, 'Branding'),
('Sadia Islam', 'Xoos Digital understood our edtech brand from day one. The visual identity communicates innovation and trust.', 5, 'Branding');

-- FAQ
INSERT INTO faq (question, answer, sort_order) VALUES
('How long does a project take?', 'It depends on the scope. A brand identity takes 5–7 business days. A WordPress website takes 2–4 weeks.', 0),
('How much does a project cost?', 'Our projects start from $299 for branding and $499 for website development.', 1),
('Do you work with international clients?', 'Absolutely. We work with clients across 12+ countries worldwide.', 2),
('What do you need from me to get started?', 'Just a brief conversation about your business, your goals, and any existing brand materials.', 3),
('How many revisions do I get?', 'Starter projects include 2 revision rounds. Growth and Premium packages include 3+ rounds.', 4),
('How do I make payment?', 'We accept bank transfer, bKash, Nagad, Payoneer, Wise, and PayPal. 50% upfront, 50% on delivery.', 5),
('Can you maintain my website after launch?', 'Yes. Monthly maintenance packages starting from $49/month.', 6),
('Why choose Xoos Digital over other agencies?', '80+ clients across 12 countries trust us. You work directly with the founder.', 7);

-- Blog Posts
INSERT INTO blog_posts (title, slug, content, meta_title, meta_description, tags, status, featured_image) VALUES
('Why Branding & Identity Design Matters More Than Ever',
 'why-branding-matters',
 E'<h2>The Foundation of Brand Recognition</h2><p>Your brand identity is the visual voice of your business.</p>',
 'Why Branding Matters | Xoos Digital',
 'Learn why professional branding is crucial for business growth.',
 'branding, identity design, logo design',
 'published', 'images/Xoos_Digital_Facebook_Cover_Image.jpg'),
('The Complete Guide to Building a High-Converting Website',
 'high-converting-website-guide',
 E'<h2>What Makes a Website Convert?</h2><p>A high-converting website guides visitors toward action.</p>',
 'High-Converting Website Guide | Xoos Digital',
 'Learn how to build a website that converts.',
 'web development, wordpress, conversion optimization',
 'published', 'images/Xoos_Digital_Facebook_Cover_Image.jpg'),
('Effective SEO & Digital Marketing Strategies to Grow Your Brand',
 'seo-digital-marketing-strategies',
 E'<h2>The Power of Organic Growth</h2><p>SEO is the most cost-effective way to drive long-term traffic.</p>',
 'SEO & Marketing Strategies | Xoos Digital',
 'Discover effective SEO and digital marketing strategies.',
 'seo, digital marketing, social media',
 'published', 'images/Xoos_Digital_Facebook_Cover_Image.jpg'),
('How to Choose the Right Color Palette for Your Brand',
 'choose-brand-color-palette',
 E'<h2>Color Psychology in Branding</h2><p>Colors evoke emotions. Blue conveys trust. Red creates urgency.</p>',
 'Choose Your Brand Color Palette | Xoos Digital',
 'Learn how to choose the perfect color palette.',
 'brand colors, color palette, brand identity',
 'published', 'images/Xoos_Digital_Facebook_Cover_Image.jpg'),
('The Ultimate Guide to WooCommerce SEO for Online Stores',
 'woocommerce-seo-guide',
 E'<h2>Why WooCommerce SEO Matters</h2><p>WooCommerce powers millions of online stores.</p>',
 'WooCommerce SEO Guide | Xoos Digital',
 'Complete guide to optimizing your WooCommerce store.',
 'woocommerce, seo, ecommerce',
 'published', 'images/Xoos_Digital_Facebook_Cover_Image.jpg'),
('Video Marketing Trends to Watch in 2025',
 'video-marketing-trends-2025',
 E'<h2>The Rise of Short-Form Video</h2><p>Short-form video dominates social media algorithms.</p>',
 'Video Marketing Trends 2025 | Xoos Digital',
 'Discover the top video marketing trends.',
 'video marketing, social media, reels',
 'published', 'images/Xoos_Digital_Facebook_Cover_Image.jpg');

-- Portfolio
INSERT INTO portfolio (project_name, client, service, description, image_url) VALUES
('XOOS DIGITAL BRAND', 'Xoos Digital', 'Branding', 'Full brand identity & digital presence.', 'images/Xoos_Digital_Facebook_Cover_Image.jpg'),
('BRIGHT HASH', 'Bright Hash Ltd.', 'Branding', 'Logo design and comprehensive brand system.', 'images/Brands_that_we work_with/Bright-hash-Logo-1.webp'),
('HOLY BASKET', 'Holy Basket', 'Branding', 'Complete visual identity system for food & retail brand.', 'images/Brands_that_we work_with/Holy-Basket-Logo.webp'),
('FLY DREAM AVIATION', 'Fly Dream Aviation', 'Branding', 'Corporate brand identity for aviation.', 'images/Brands_that_we work_with/Fly-Dream-Logo-1.webp'),
('GUG', 'GUG', 'Branding', 'Logo design and brand guidelines.', 'images/Brands_that_we work_with/GUG-Logo-2.webp'),
('HOLY AGRO', 'Holy Agro', 'Branding', 'Agricultural brand identity system.', 'images/Brands_that_we work_with/Holy-Agro-Logo.webp');

-- Brands
INSERT INTO brands (name, logo_url, industry, country, service, bloom_color, sort_order) VALUES
('JDP',              'images/Brands_that_we work_with/JDP-Logo.webp',              'Technology',       'DHAKA', 'BRANDING',     'rgba(0, 120, 255, 0.18)', 0),
('SKILL PLANET',     'images/Brands_that_we work_with/Skill-Planet-Logo-1.webp',   'Education',        'DHAKA', 'BRANDING',     'rgba(255, 150, 0, 0.18)', 1),
('HOLY BASKET',      'images/Brands_that_we work_with/Holy-Basket-Logo.webp',      'Food & Retail',    'DHAKA', 'BRANDING',     'rgba(255, 60, 60, 0.18)', 2),
('FLY DREAM AVIATION','images/Brands_that_we work_with/Fly-Dream-Logo-1.webp',     'Aviation',         'DHAKA', 'BRANDING',     'rgba(0, 180, 255, 0.18)', 3),
('BRIGHT HASH',      'images/Brands_that_we work_with/Bright-hash-Logo-1.webp',    'Technology',       'DHAKA', 'SEO & WEB',   'rgba(80, 220, 80, 0.18)', 4),
('MISKAT TOURS',     'images/Brands_that_we work_with/Miskat-Logo.webp',           'Travel & Tourism', 'DHAKA', 'BRANDING',     'rgba(180, 80, 255, 0.18)', 5),
('HOLY AGRO',        'images/Brands_that_we work_with/Holy-Agro-Logo.webp',        'Agriculture',      'DHAKA', 'BRANDING',     'rgba(80, 200, 80, 0.18)', 6),
('GUG',              'images/Brands_that_we work_with/GUG-Logo-2.webp',            'Business Group',   'DHAKA', 'BRANDING',     'rgba(255, 200, 0, 0.18)', 7);

-- ── Enable Row Level Security (optional, for client-side usage) ──
-- Uncomment to enable RLS for anon key access
-- ALTER TABLE services ENABLE ROW LEVEL SECURITY;
-- GRANT ALL ON ALL TABLES IN SCHEMA public TO service_role;
-- GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO service_role;

-- ── Verify ──
SELECT 'Schema setup complete!' AS status,
       (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public') AS tables_created;
