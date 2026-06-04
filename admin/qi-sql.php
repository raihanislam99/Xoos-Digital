<?php
require_once __DIR__ . '/inc/functions.php';
$pdo = db();

// Helper to create updated_at trigger
function qi_ensure_trigger(string $table): void {
    try {
        $pdo = db();
        $funcName = 'update_' . $table . '_updated_at';
        $triggerName = 'trg_' . $table . '_updated_at';
        $pdo->exec("CREATE OR REPLACE FUNCTION {$funcName}() RETURNS TRIGGER AS $$ BEGIN NEW.updated_at = CURRENT_TIMESTAMP; RETURN NEW; END; $$ LANGUAGE plpgsql");
        $pdo->exec("DROP TRIGGER IF EXISTS {$triggerName} ON {$table}");
        $pdo->exec("CREATE TRIGGER {$triggerName} BEFORE UPDATE ON {$table} FOR EACH ROW EXECUTE FUNCTION {$funcName}()");
    } catch (Exception $e) {}
}

$queries = [
    "CREATE TABLE IF NOT EXISTS company_info (
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
    )",

    "INSERT INTO company_info (id) VALUES (1) ON CONFLICT (id) DO NOTHING",

    "CREATE TABLE IF NOT EXISTS quotations (
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
        status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','sent','accepted','rejected')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS quotation_items (
        id SERIAL PRIMARY KEY,
        quotation_id INT NOT NULL REFERENCES quotations(id) ON DELETE CASCADE,
        description TEXT,
        quantity DECIMAL(10,2) DEFAULT 1,
        unit VARCHAR(50) DEFAULT '',
        rate DECIMAL(12,2) DEFAULT 0,
        amount DECIMAL(12,2) DEFAULT 0
    )",

    "CREATE TABLE IF NOT EXISTS invoices (
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
        status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','sent','paid','overdue','cancelled')),
        paid_date DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS invoice_items (
        id SERIAL PRIMARY KEY,
        invoice_id INT NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
        description TEXT,
        quantity DECIMAL(10,2) DEFAULT 1,
        unit VARCHAR(50) DEFAULT '',
        rate DECIMAL(12,2) DEFAULT 0,
        amount DECIMAL(12,2) DEFAULT 0
    )",
];

foreach ($queries as $q) {
    try { $pdo->exec($q); echo "OK\n"; }
    catch (Exception $e) { echo $e->getMessage() . "\n"; }
}

// Set up triggers for updated_at
qi_ensure_trigger('company_info');
qi_ensure_trigger('quotations');
qi_ensure_trigger('invoices');

echo "Done\n";
