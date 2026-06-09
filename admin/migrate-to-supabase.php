<?php
/**
 * Migration Script: MySQL → Supabase PostgreSQL + Supabase Auth
 *
 * 1. Reads all data from MySQL (existing)
 * 2. Creates tables in Supabase PostgreSQL
 * 3. Writes all data to Supabase
 * 4. Creates admin user in Supabase Auth
 *
 * Usage: Run from browser while logged in to admin panel
 *        or from CLI: php admin/migrate-to-supabase.php
 */

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/supabase.php';

$output = [];

// Ensure MySQL connection works
$mysql = mysql_db();

if ($mysql === null) {
    die("MySQL connection failed. Check DB_HOST/DB_USER/DB_PASS/DB_NAME in .env\n");
}

$pg = db();
$isRestMode = ($pg instanceof SupabaseRestDB);

// ── Helper: check if table has data (works with PDO or REST) ──
function table_has_data($pdo_or_rest, string $table): bool {
    try {
        $stmt = $pdo_or_rest->prepare("SELECT COUNT(*) FROM {$table}");
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// ── Step 1: Create tables in PostgreSQL (skip in REST mode, already done via SQL file) ──
if ($isRestMode) {
    $output[] = "=== REST mode: tables already created via SQL file. Skipping DDL. ===";
} else {
    $output[] = "=== Step 1: Creating tables in PostgreSQL ===";

    $createQueries = [
        "CREATE TABLE IF NOT EXISTS services (
            id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, description TEXT, features TEXT,
            hashtags VARCHAR(500), price VARCHAR(50) DEFAULT '', sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS packages (
            id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, tier VARCHAR(50), tagline VARCHAR(255),
            price VARCHAR(50), features TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS testimonials (
            id SERIAL PRIMARY KEY, client_name VARCHAR(255) NOT NULL, quote TEXT NOT NULL,
            rating SMALLINT DEFAULT 5, service_used VARCHAR(255), client_image VARCHAR(500),
            client_country VARCHAR(100) DEFAULT '', country_flag VARCHAR(10) DEFAULT '',
            platform VARCHAR(50) DEFAULT '', avatar_gradient VARCHAR(255) DEFAULT '',
            avatar_letter VARCHAR(2) DEFAULT '', sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS faq (
            id SERIAL PRIMARY KEY, question VARCHAR(500) NOT NULL, answer TEXT NOT NULL,
            category VARCHAR(100), sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS portfolio_categories (
            id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE,
            sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS portfolio (
            id SERIAL PRIMARY KEY, project_name VARCHAR(255) NOT NULL, client VARCHAR(255),
            service VARCHAR(255), description TEXT, image_url VARCHAR(500), link VARCHAR(500),
            slug VARCHAR(255) UNIQUE, category_id INT DEFAULT 0, challenge TEXT, solution TEXT,
            results TEXT, client_testimonial TEXT, technologies VARCHAR(500), video_url VARCHAR(500),
            meta_title VARCHAR(255), meta_description TEXT, sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS brands (
            id SERIAL PRIMARY KEY, name VARCHAR(100) NOT NULL, logo_url VARCHAR(500) NOT NULL,
            industry VARCHAR(100) DEFAULT '', country VARCHAR(100) DEFAULT '',
            service VARCHAR(100) DEFAULT '', bloom_color VARCHAR(50) DEFAULT 'rgba(0,0,0,0.18)',
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL,
            phone VARCHAR(100) DEFAULT '', company VARCHAR(255) DEFAULT '',
            country VARCHAR(100) DEFAULT '', services TEXT, budget VARCHAR(100) DEFAULT '',
            timeline VARCHAR(100) DEFAULT '', message TEXT, ip_address VARCHAR(45) DEFAULT '',
            is_read SMALLINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS settings (
            id SERIAL PRIMARY KEY, setting_key VARCHAR(100) NOT NULL UNIQUE, setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS media_files (
            id SERIAL PRIMARY KEY, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL,
            filepath VARCHAR(500) NOT NULL, filesize INT DEFAULT 0, mime_type VARCHAR(100) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS blog_categories (
            id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE,
            sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS blog_posts (
            id SERIAL PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE,
            content TEXT, meta_title VARCHAR(255), meta_description TEXT, tags VARCHAR(500),
            status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','published')),
            featured_image VARCHAR(500), category_id INT DEFAULT 0, read_time INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS post_training_data (
            id SERIAL PRIMARY KEY, content TEXT NOT NULL, type VARCHAR(50) DEFAULT 'topic',
            profile_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS post_profiles (
            id SERIAL PRIMARY KEY, platform VARCHAR(50) NOT NULL, profile_url VARCHAR(500) NOT NULL,
            name VARCHAR(255) DEFAULT '', notes TEXT, language VARCHAR(50) DEFAULT 'english',
            tone VARCHAR(50) DEFAULT 'semi-professional', niche VARCHAR(500) DEFAULT '',
            color VARCHAR(7) DEFAULT '#c8f135', post_length INT DEFAULT 200,
            type VARCHAR(20) DEFAULT 'personal', business_type VARCHAR(200) DEFAULT '',
            target_audience TEXT DEFAULT '', brand_voice TEXT DEFAULT '', avoid_topics TEXT DEFAULT '',
            avatar_url VARCHAR(500) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS generated_posts (
            id SERIAL PRIMARY KEY, platform VARCHAR(50) NOT NULL, content TEXT,
            language VARCHAR(50) DEFAULT 'en',
            status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','published')),
            topic VARCHAR(255) DEFAULT '', training_ids TEXT, profile_ids TEXT,
            profile_id INT DEFAULT NULL, linkedin_content TEXT, facebook_content TEXT,
            hashtags_used TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS post_hashtags (
            id SERIAL PRIMARY KEY, platform VARCHAR(50) NOT NULL, tag VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS admin_tasks (
            id SERIAL PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT,
            status VARCHAR(50) DEFAULT 'pending', priority VARCHAR(50) DEFAULT 'medium',
            due_date TIMESTAMP DEFAULT NULL, category VARCHAR(255) DEFAULT '',
            lead_id INT DEFAULT NULL, assignee_type VARCHAR(50) DEFAULT NULL,
            assignee_name VARCHAR(255) DEFAULT NULL, sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS leads (
            id SERIAL PRIMARY KEY, business_name VARCHAR(255) NOT NULL, owner_name VARCHAR(255) DEFAULT '',
            email VARCHAR(255) DEFAULT '', phone VARCHAR(100) DEFAULT '', whatsapp VARCHAR(100) DEFAULT '',
            website VARCHAR(500) DEFAULT '', facebook VARCHAR(500) DEFAULT '',
            instagram VARCHAR(500) DEFAULT '', city VARCHAR(100) DEFAULT '',
            country VARCHAR(100) DEFAULT '', address TEXT, niche VARCHAR(255) DEFAULT '',
            lead_score INT DEFAULT 0, status VARCHAR(50) DEFAULT 'new', source VARCHAR(100) DEFAULT '',
            tags TEXT, is_blacklisted SMALLINT DEFAULT 0, google_maps_url VARCHAR(500) DEFAULT '',
            has_website SMALLINT DEFAULT 0, website_score INT DEFAULT 0, ai_audit TEXT, notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS lead_emails (
            id SERIAL PRIMARY KEY, lead_id INT NOT NULL, subject VARCHAR(500) DEFAULT '',
            body TEXT, status VARCHAR(50) DEFAULT 'draft', sent_at TIMESTAMP DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS lead_whatsapp (
            id SERIAL PRIMARY KEY, lead_id INT NOT NULL, message TEXT,
            status VARCHAR(50) DEFAULT 'sent', sent_at TIMESTAMP DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS lead_activity (
            id SERIAL PRIMARY KEY, lead_id INT NOT NULL, type VARCHAR(50) DEFAULT 'note',
            content TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS outreach_templates (
            id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, type VARCHAR(50) DEFAULT 'email',
            subject VARCHAR(500) DEFAULT '', body TEXT, is_default SMALLINT DEFAULT 0,
            use_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS company_info (
            id INT PRIMARY KEY DEFAULT 1, company_name VARCHAR(200) DEFAULT '', address TEXT,
            phone VARCHAR(100) DEFAULT '', email VARCHAR(100) DEFAULT '', website VARCHAR(200) DEFAULT '',
            logo VARCHAR(500) DEFAULT '', tax_id VARCHAR(100) DEFAULT '', bank_name VARCHAR(200) DEFAULT '',
            bank_account VARCHAR(100) DEFAULT '', bank_routing VARCHAR(100) DEFAULT '',
            city VARCHAR(100) DEFAULT '', state VARCHAR(100) DEFAULT '', zip VARCHAR(20) DEFAULT '',
            country VARCHAR(100) DEFAULT '', updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS quotations (
            id SERIAL PRIMARY KEY, quote_number VARCHAR(50) NOT NULL, date DATE DEFAULT NULL,
            valid_until DATE DEFAULT NULL, client_name VARCHAR(200) DEFAULT '',
            client_email VARCHAR(200) DEFAULT '', client_phone VARCHAR(100) DEFAULT '',
            client_address TEXT, notes TEXT, terms TEXT, subtotal DECIMAL(12,2) DEFAULT 0,
            tax_rate DECIMAL(5,2) DEFAULT 0, tax_amount DECIMAL(12,2) DEFAULT 0,
            total DECIMAL(12,2) DEFAULT 0, currency VARCHAR(10) DEFAULT 'USD',
            status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','sent','accepted','rejected')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS quotation_items (
            id SERIAL PRIMARY KEY, quotation_id INT NOT NULL REFERENCES quotations(id) ON DELETE CASCADE,
            description TEXT, quantity DECIMAL(10,2) DEFAULT 1,
            unit VARCHAR(50) DEFAULT '', rate DECIMAL(12,2) DEFAULT 0, amount DECIMAL(12,2) DEFAULT 0
        )",
        "CREATE TABLE IF NOT EXISTS invoices (
            id SERIAL PRIMARY KEY, invoice_number VARCHAR(50) NOT NULL, date DATE DEFAULT NULL,
            due_date DATE DEFAULT NULL, client_name VARCHAR(200) DEFAULT '',
            client_email VARCHAR(200) DEFAULT '', client_phone VARCHAR(100) DEFAULT '',
            client_address TEXT, notes TEXT, terms TEXT, subtotal DECIMAL(12,2) DEFAULT 0,
            tax_rate DECIMAL(5,2) DEFAULT 0, tax_amount DECIMAL(12,2) DEFAULT 0,
            total DECIMAL(12,2) DEFAULT 0, currency VARCHAR(10) DEFAULT 'USD',
            status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','sent','paid','overdue','cancelled')),
            paid_date DATE DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS invoice_items (
            id SERIAL PRIMARY KEY, invoice_id INT NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
            description TEXT, quantity DECIMAL(10,2) DEFAULT 1,
            unit VARCHAR(50) DEFAULT '', rate DECIMAL(12,2) DEFAULT 0, amount DECIMAL(12,2) DEFAULT 0
        )",
    ];

    foreach ($createQueries as $q) {
        try {
            $pg->exec($q);
        } catch (Exception $e) {
            $output[] = "  ERROR creating table: " . $e->getMessage();
        }
    }
    $output[] = "  All tables created.";
}

// ── Step 2: Set up updated_at triggers (skip in REST mode) ──
if (!$isRestMode) {
    $triggerTables = ['services','packages','testimonials','faq','portfolio','portfolio_categories','brands','blog_posts','settings','leads','outreach_templates'];
    foreach ($triggerTables as $t) {
        try {
            $funcName = 'update_' . $t . '_updated_at';
            $triggerName = 'trg_' . $t . '_updated_at';
            $pg->exec("CREATE OR REPLACE FUNCTION {$funcName}() RETURNS TRIGGER AS $$ BEGIN NEW.updated_at = CURRENT_TIMESTAMP; RETURN NEW; END; $$ LANGUAGE plpgsql");
            $pg->exec("DROP TRIGGER IF EXISTS {$triggerName} ON {$t}");
            $pg->exec("CREATE TRIGGER {$triggerName} BEFORE UPDATE ON {$t} FOR EACH ROW EXECUTE FUNCTION {$funcName}()");
        } catch (Exception $e) {
            $output[] = "  WARNING: Could not create trigger for {$t}: " . $e->getMessage();
        }
    }
} else {
    $output[] = "  Skipping triggers (already created via SQL file).";
}

// ── Step 3: Migrate data ──
$output[] = "\n=== Step 2: Migrating data ===";

$tables = [
    'services', 'packages', 'testimonials', 'faq', 'portfolio', 'brands',
    'contact_messages', 'settings', 'media_files', 'blog_categories', 'blog_posts',
    'post_training_data', 'post_profiles', 'generated_posts', 'post_hashtags',
    'admin_tasks',
    'leads', 'lead_emails', 'lead_whatsapp', 'lead_activity', 'outreach_templates',
    'company_info', 'quotations', 'quotation_items', 'invoices', 'invoice_items',
];

foreach ($tables as $table) {
    try {
        // Check if MySQL has this table with data
        if (!table_has_data($mysql, $table)) {
            $output[] = "  {$table}: no data (skipping)";
            continue;
        }

        // Check if PostgreSQL already has data
        if (table_has_data($pg, $table)) {
            $output[] = "  {$table}: already has data (skipping)";
            continue;
        }

        // Read from MySQL
        $rows = $mysql->query("SELECT * FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) continue;

        // Build INSERT for PostgreSQL
        $cols = array_keys($rows[0]);
        $colList = implode(', ', $cols);
        $paramList = ':' . implode(', :', $cols);

        $stmt = $pg->prepare("INSERT INTO {$table} ({$colList}) VALUES ({$paramList})");

        $count = 0;
        foreach ($rows as $row) {
            try {
                $stmt->execute($row);
                $count++;
            } catch (Exception $e) {
                $output[] = "  {$table}: row error: " . $e->getMessage();
            }
        }

        // Reset sequence (skip in REST mode)
        if (!$isRestMode) {
            try {
                $pg->exec("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1))");
            } catch (Exception $e) {}
        }

        $output[] = "  {$table}: {$count} rows migrated";
    } catch (Exception $e) {
        $output[] = "  {$table}: ERROR - " . $e->getMessage();
    }
}

// ── Step 4: Create admin user in Supabase Auth ──
$output[] .= "\n=== Step 3: Creating admin user in Supabase Auth ===";
try {
    $supabase = Supabase::getInstance();
    $existing = $supabase->authAdminGetUserByEmail('xoosdigital@gmail.com');
    if ($existing) {
        $output[] .= "  Admin user xoosdigital@gmail.com already exists.";
    } else {
        $result = $supabase->authAdminCreateUser('xoosdigital@gmail.com', 'Xoos@2025!', ['role' => 'admin']);
        if (!empty($result['id'])) {
            $output[] .= "  Admin user created: xoosdigital@gmail.com / Xoos@2025!";
        } else {
            $output[] .= "  Could not create admin user: " . json_encode($result);
        }
    }
} catch (Exception $e) {
    $output[] .= "  Error creating admin user: " . $e->getMessage();
}

$output[] .= "\n=== Migration complete! ===";

echo implode("\n", $output);
