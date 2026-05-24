<?php
require_once __DIR__ . '/inc/functions.php';
$pdo = db();

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
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "INSERT IGNORE INTO company_info (id) VALUES (1)",

    "CREATE TABLE IF NOT EXISTS quotations (
        id INT AUTO_INCREMENT PRIMARY KEY,
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
        status ENUM('draft','sent','accepted','rejected') DEFAULT 'draft',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS quotation_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quotation_id INT NOT NULL,
        description TEXT,
        quantity DECIMAL(10,2) DEFAULT 1,
        unit VARCHAR(50) DEFAULT '',
        rate DECIMAL(12,2) DEFAULT 0,
        amount DECIMAL(12,2) DEFAULT 0,
        FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
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
        status ENUM('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
        paid_date DATE DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS invoice_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        description TEXT,
        quantity DECIMAL(10,2) DEFAULT 1,
        unit VARCHAR(50) DEFAULT '',
        rate DECIMAL(12,2) DEFAULT 0,
        amount DECIMAL(12,2) DEFAULT 0,
        FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($queries as $q) {
    try { $pdo->exec($q); echo "OK\n"; }
    catch (Exception $e) { echo $e->getMessage() . "\n"; }
}
echo "Done\n";
