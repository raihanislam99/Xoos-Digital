<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

$view = $_GET['view'] ?? 'quotes';
$pdo = db();

// AJAX & form handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // ── Company Info ──
    if ($action === 'save_company') {
        $pdo->prepare("UPDATE company_info SET company_name=?, address=?, city=?, state=?, zip=?, country=?, phone=?, email=?, website=?, logo=?, tax_id=?, bank_name=?, bank_account=?, bank_routing=? WHERE id=1")
            ->execute([
                trim($_POST['company_name']), trim($_POST['address']), trim($_POST['city']), trim($_POST['state']),
                trim($_POST['zip']), trim($_POST['country']), trim($_POST['phone']), trim($_POST['email']),
                trim($_POST['website']), trim($_POST['logo']), trim($_POST['tax_id']), trim($_POST['bank_name']),
                trim($_POST['bank_account']), trim($_POST['bank_routing']),
            ]);
        $_SESSION['flash_msg'] = 'Company info saved.';
        $_SESSION['flash_type'] = 'success';
        redirect('quote-invoice.php?view=company');
    }

    // ── Quote CRUD ──
    if ($action === 'quote_save') {
        $id = (int)($_POST['id'] ?? 0);
        $items = json_decode($_POST['items'] ?? '[]', true);
        $subtotal = 0;
        foreach ($items as &$it) {
            $it['quantity'] = (float)($it['quantity'] ?? 1);
            $it['rate'] = (float)($it['rate'] ?? 0);
            $it['amount'] = $it['quantity'] * $it['rate'];
            $subtotal += $it['amount'];
        }
        $taxRate = (float)($_POST['tax_rate'] ?? 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE quotations SET date=?, valid_until=?, client_name=?, client_email=?, client_phone=?, client_address=?, notes=?, terms=?, subtotal=?, tax_rate=?, tax_amount=?, total=? WHERE id=?");
            $stmt->execute([$_POST['date'], $_POST['valid_until'], $_POST['client_name'], $_POST['client_email'], $_POST['client_phone'], $_POST['client_address'], $_POST['notes'], $_POST['terms'], $subtotal, $taxRate, $taxAmount, $total, $id]);
            $pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?")->execute([$id]);
        } else {
            $num = 'QTE-' . date('Y') . '-' . str_pad(((int)$pdo->query("SELECT COUNT(*) FROM quotations")->fetchColumn()) + 1, 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO quotations (quote_number, date, valid_until, client_name, client_email, client_phone, client_address, notes, terms, subtotal, tax_rate, tax_amount, total) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$num, $_POST['date'], $_POST['valid_until'], $_POST['client_name'], $_POST['client_email'], $_POST['client_phone'], $_POST['client_address'], $_POST['notes'], $_POST['terms'], $subtotal, $taxRate, $taxAmount, $total]);
            $id = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("INSERT INTO quotation_items (quotation_id, description, quantity, unit, rate, amount) VALUES (?,?,?,?,?,?)");
        foreach ($items as $it) {
            $stmt->execute([$id, $it['description'], $it['quantity'], $it['unit'] ?? '', $it['rate'], $it['amount']]);
        }

        $_SESSION['flash_msg'] = 'Quotation saved.';
        $_SESSION['flash_type'] = 'success';
        redirect('quote-invoice.php?view=quotes');
    }

    if ($action === 'quote_delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM quotations WHERE id = ?")->execute([$id]);
        json_response(['ok' => true]);
    }

    if ($action === 'quote_status') {
        $pdo->prepare("UPDATE quotations SET status = ? WHERE id = ?")->execute([$_POST['status'], (int)$_POST['id']]);
        json_response(['ok' => true]);
    }

    // ── Invoice CRUD ──
    if ($action === 'invoice_save') {
        $id = (int)($_POST['id'] ?? 0);
        $items = json_decode($_POST['items'] ?? '[]', true);
        $subtotal = 0;
        foreach ($items as &$it) {
            $it['quantity'] = (float)($it['quantity'] ?? 1);
            $it['rate'] = (float)($it['rate'] ?? 0);
            $it['amount'] = $it['quantity'] * $it['rate'];
            $subtotal += $it['amount'];
        }
        $taxRate = (float)($_POST['tax_rate'] ?? 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE invoices SET date=?, due_date=?, client_name=?, client_email=?, client_phone=?, client_address=?, notes=?, terms=?, subtotal=?, tax_rate=?, tax_amount=?, total=? WHERE id=?");
            $stmt->execute([$_POST['date'], $_POST['due_date'], $_POST['client_name'], $_POST['client_email'], $_POST['client_phone'], $_POST['client_address'], $_POST['notes'], $_POST['terms'], $subtotal, $taxRate, $taxAmount, $total, $id]);
            $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?")->execute([$id]);
        } else {
            $num = 'INV-' . date('Y') . '-' . str_pad(((int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn()) + 1, 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, date, due_date, client_name, client_email, client_phone, client_address, notes, terms, subtotal, tax_rate, tax_amount, total) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$num, $_POST['date'], $_POST['due_date'], $_POST['client_name'], $_POST['client_email'], $_POST['client_phone'], $_POST['client_address'], $_POST['notes'], $_POST['terms'], $subtotal, $taxRate, $taxAmount, $total]);
            $id = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit, rate, amount) VALUES (?,?,?,?,?,?)");
        foreach ($items as $it) {
            $stmt->execute([$id, $it['description'], $it['quantity'], $it['unit'] ?? '', $it['rate'], $it['amount']]);
        }

        $_SESSION['flash_msg'] = 'Invoice saved.';
        $_SESSION['flash_type'] = 'success';
        redirect('quote-invoice.php?view=invoices');
    }

    if ($action === 'invoice_delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM invoices WHERE id = ?")->execute([$id]);
        json_response(['ok' => true]);
    }

    if ($action === 'invoice_status') {
        $pdo->prepare("UPDATE invoices SET status = ?, paid_date = ? WHERE id = ?")->execute([$_POST['status'], $_POST['status'] === 'paid' ? date('Y-m-d') : null, (int)$_POST['id']]);
        json_response(['ok' => true]);
    }

    // ── AI Generate Quote/Invoice ──
    if ($action === 'ai_generate') {
        $type = $_POST['type'] ?? 'quote';
        $idea = trim($_POST['idea'] ?? '');
        $clientName = trim($_POST['client_name'] ?? '');

        if (!$idea) { json_response(['ok' => false, 'error' => 'Describe your idea first.'], 400); exit; }

        $settings = ai_feature_settings('admin');
        if (empty($settings['key'])) {
            json_response(['ok' => false, 'error' => 'AI not configured. Go to Settings.'], 400);
            exit;
        }

        $systemPrompt = "You are a professional business proposal and quoting assistant. Based on the client's idea and description, generate a detailed quotation or invoice with line items. Return ONLY a valid JSON object. No markdown fences, no explanation.\n\nFormat:\n{\n  \"items\": [\n    {\"description\": \"Item description\", \"quantity\": 1, \"unit\": \"hour/service\", \"rate\": 0.00}\n  ],\n  \"notes\": \"Optional notes about the quote\",\n  \"terms\": \"Payment terms\"\n}\n\nGenerate realistic, professional line items (3-8 items). Set appropriate rates based on the service described.";

        $userMsg = "Create a $type for client: " . ($clientName ?: 'Client') . "\n\nClient's idea: $idea";

        try {
            $reply = ai_call($settings, [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMsg],
            ], 2000, 0.7);
        } catch (Exception $e) {
            json_response(['ok' => false, 'error' => 'AI error: ' . $e->getMessage()], 500);
            exit;
        }

        $reply = preg_replace('/```(?:json)?\s*/i', '', $reply);
        $reply = preg_replace('/```/', '', $reply);
        $reply = trim($reply);
        $parsed = json_decode($reply, true);

        if (!$parsed || !isset($parsed['items']) || !count($parsed['items'])) {
            json_response(['ok' => false, 'error' => 'AI returned invalid data. Try again.']);
            exit;
        }

        json_response(['ok' => true, 'data' => $parsed]);
        exit;
    }

    // ── Email Invoice ──
    if ($action === 'email_invoice') {
        $id = (int)$_POST['id'];
        $to = trim($_POST['to']);
        $subject = trim($_POST['subject']) ?: 'Your Invoice';
        $message = trim($_POST['message']) ?: 'Please find your invoice attached.';

        // Generate PDF first
        $pdfUrl = ADMIN_URL . '/pdf-gen.php?type=invoice&id=' . $id;
        
        // Send via mail function (simple)
        $headers = "From: " . (get_setting('smtp_user') ?: 'noreply@' . $_SERVER['HTTP_HOST']) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $body = nl2br(h($message));
        $body .= '<br><br><a href="' . $pdfUrl . '">Download Invoice</a>';

        $sent = @mail($to, $subject, $body, $headers);

        if ($sent) {
            $pdo->prepare("UPDATE invoices SET status = 'sent' WHERE id = ?")->execute([$id]);
            json_response(['ok' => true]);
        } else {
            json_response(['ok' => false, 'error' => 'Email could not be sent. Check SMTP settings.']);
        }
        exit;
    }

    json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}

// Load data
$quotes = $pdo->query("SELECT * FROM quotations ORDER BY created_at DESC")->fetchAll();
$invoices = $pdo->query("SELECT * FROM invoices ORDER BY created_at DESC")->fetchAll();
$company = $pdo->query("SELECT * FROM company_info WHERE id = 1")->fetch();

// Edit data
$editQuote = null; $editQuoteItems = [];
if (!empty($_GET['edit_quote'])) { $eq = $pdo->query("SELECT * FROM quotations WHERE id = " . (int)$_GET['edit_quote'])->fetch(); if ($eq) { $editQuote = $eq; $editQuoteItems = $pdo->query("SELECT * FROM quotation_items WHERE quotation_id = " . $eq['id'])->fetchAll(); } }
$editInvoice = null; $editInvoiceItems = [];
if (!empty($_GET['edit_invoice'])) { $ei = $pdo->query("SELECT * FROM invoices WHERE id = " . (int)$_GET['edit_invoice'])->fetch(); if ($ei) { $editInvoice = $ei; $editInvoiceItems = $pdo->query("SELECT * FROM invoice_items WHERE invoice_id = " . $ei['id'])->fetchAll(); } }

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
?>
<?php require_once __DIR__ . '/../inc/header.php'; ?>

<style>
.qi-tabs { display:flex; gap:0; border-bottom:1px solid var(--border); margin-bottom:1.5rem; }
.qi-tab { padding:0.65rem 1.25rem; font-family:'Orbitron',sans-serif; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text3); cursor:pointer; border-bottom:2px solid transparent; transition:all 0.15s; background:none; border-left:none; border-right:none; border-top:none; text-decoration:none; }
.qi-tab:hover { color:var(--text); }
.qi-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
.qi-tab i { margin-right:6px; font-size:0.8rem; }
.items-table { width:100%; border-collapse:collapse; }
.items-table th { font-size:0.65rem; padding:0.5rem; text-align:left; color:var(--text3); border-bottom:1px solid var(--border); text-transform:uppercase; letter-spacing:0.08em; }
.items-table td { padding:0.35rem 0.5rem; border-bottom:1px solid var(--border); }
.items-table .item-desc input { width:100%; }
.items-table .item-qty input, .items-table .item-rate input { width:70px; text-align:center; }
.items-table .item-amt { font-weight:600; color:var(--text); text-align:center; }
.items-table .item-del { cursor:pointer; color:var(--red); font-size:1.1rem; }
.subtotal-row td { padding:0.5rem; font-weight:600; color:var(--text); border-top:2px solid var(--border); }
.status-select-sm { background:var(--bg3); border:1px solid var(--border); color:var(--text2); padding:2px 8px; border-radius:4px; font-size:0.7rem; outline:none; }
.ai-gen-area { background:rgba(204,255,0,0.03); border:1px dashed var(--accent); border-radius:var(--radius-md); padding:1rem; margin-bottom:1rem; }
.media-upload { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
</style>

<?php if ($flash_msg): ?>
<div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:<?= $flash_type==='success'?'var(--green-bg)':'var(--red-bg)' ?>;color:<?= $flash_type==='success'?'var(--green)':'var(--red)' ?>"><?= h($flash_msg) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title"><?= $view==='company'?'Company Info':($view==='invoices'?'Invoices':'Quotations') ?></h1>
</div>

<div class="qi-tabs">
    <a href="quote-invoice.php" class="qi-tab <?= $view==='quotes'||$view===''?'active':'' ?>"><i class="ti ti-file-description"></i> Quotations</a>
    <a href="quote-invoice.php?view=invoices" class="qi-tab <?= $view==='invoices'?'active':'' ?>"><i class="ti ti-receipt"></i> Invoices</a>
    <a href="quote-invoice.php?view=company" class="qi-tab <?= $view==='company'?'active':'' ?>"><i class="ti ti-building"></i> Company Info</a>
</div>

<?php if ($view === 'company'): ?>
<div class="card" style="max-width:700px">
    <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:1rem"><i class="ti ti-building"></i> Letterhead / Company Info</h3>
    <form method="post" action="quote-invoice.php">
        <input type="hidden" name="action" value="save_company">
        <div class="form-group">
            <label>Company Name</label>
            <input class="form-control" name="company_name" value="<?= h($company['company_name']??'') ?>" placeholder="Xoos Digital">
        </div>
        <div class="form-group">
            <label>Logo URL</label>
            <div class="media-upload">
                <input class="form-control" name="logo" id="ci-logo" value="<?= h($company['logo']??'') ?>" placeholder="images/logo.png" style="flex:1;min-width:200px" oninput="previewLogo(this)" onchange="previewLogo(this)">
                <input type="file" id="logo-upload" accept="image/*" style="display:none" onchange="uploadLogo(this)">
                <button type="button" class="btn btn-ai btn-sm" onclick="document.getElementById('logo-upload').click()"><i class="ti ti-upload"></i> Upload</button>
            </div>
            <div id="logo-preview" style="margin-top:6px"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Address</label><input class="form-control" name="address" value="<?= h($company['address']??'') ?>"></div>
            <div class="form-group"><label>City</label><input class="form-control" name="city" value="<?= h($company['city']??'') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>State</label><input class="form-control" name="state" value="<?= h($company['state']??'') ?>"></div>
            <div class="form-group"><label>ZIP</label><input class="form-control" name="zip" value="<?= h($company['zip']??'') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Country</label><input class="form-control" name="country" value="<?= h($company['country']??'') ?>"></div>
            <div class="form-group"><label>Phone</label><input class="form-control" name="phone" value="<?= h($company['phone']??'') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Email</label><input class="form-control" name="email" value="<?= h($company['email']??'') ?>"></div>
            <div class="form-group"><label>Website</label><input class="form-control" name="website" value="<?= h($company['website']??'') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Tax / VAT ID</label><input class="form-control" name="tax_id" value="<?= h($company['tax_id']??'') ?>"></div>
            <div class="form-group"><label>Bank Name</label><input class="form-control" name="bank_name" value="<?= h($company['bank_name']??'') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Bank Account</label><input class="form-control" name="bank_account" value="<?= h($company['bank_account']??'') ?>"></div>
            <div class="form-group"><label>Bank Routing</label><input class="form-control" name="bank_routing" value="<?= h($company['bank_routing']??'') ?>"></div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
        </div>
    </form>
</div>

<?php elseif ($view === 'invoices'): ?>
<?php if ($editInvoice): ?>
<div class="card">
    <div class="flex" style="margin-bottom:1rem">
        <button class="btn btn-secondary" onclick="window.location.href='quote-invoice.php?view=invoices'"><i class="ti ti-arrow-left"></i> Back</button>
        <a href="<?= ADMIN_URL ?>/pdf-gen.php?type=invoice&id=<?= $editInvoice['id'] ?>" class="btn btn-secondary" target="_blank"><i class="ti ti-file-download"></i> Download PDF</a>
    </div>
    <?php include __DIR__ . '/../inc/qi-form.php'; ?>
</div>
<?php else: ?>
<div class="flex" style="justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem">
    <button class="btn btn-primary" onclick="window.location.href='quote-invoice.php?view=invoices&new_invoice=1'"><i class="ti ti-plus"></i> New Invoice</button>
</div>
<div class="card">
    <?php if (count($invoices)): ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Client</th><th>Date</th><th>Due</th><th>Total</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($invoices as $inv):
                    $overdue = $inv['status'] !== 'paid' && $inv['status'] !== 'cancelled' && $inv['due_date'] && $inv['due_date'] < date('Y-m-d');
                ?>
                <tr>
                    <td><strong style="color:var(--text);font-size:0.82rem"><?= h($inv['invoice_number']) ?></strong></td>
                    <td><?= h($inv['client_name']) ?></td>
                    <td class="text-muted"><?= $inv['date'] ?></td>
                    <td style="<?= $overdue?'color:#ef4444;font-weight:700':'' ?>"><?= $inv['due_date'] ?: '-' ?></td>
                    <td><strong>$<?= number_format($inv['total'],2) ?></strong></td>
                    <td>
                        <select class="status-select-sm" onchange="updateInvoiceStatus(<?= $inv['id'] ?>,this.value)">
                            <option value="draft" <?= $inv['status']==='draft'?'selected':'' ?>>Draft</option>
                            <option value="sent" <?= $inv['status']==='sent'?'selected':'' ?>>Sent</option>
                            <option value="paid" <?= $inv['status']==='paid'?'selected':'' ?>>Paid</option>
                            <option value="overdue" <?= $inv['status']==='overdue'?'selected':'' ?>>Overdue</option>
                            <option value="cancelled" <?= $inv['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                        </select>
                    </td>
                    <td style="text-align:right">
                        <a href="?view=invoices&edit_invoice=<?= $inv['id'] ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px"><i class="ti ti-pencil"></i></a>
                        <a href="<?= ADMIN_URL ?>/pdf-gen.php?type=invoice&id=<?= $inv['id'] ?>" target="_blank" class="btn btn-secondary btn-sm" style="padding:4px 8px"><i class="ti ti-file-download"></i></a>
                        <button class="btn btn-secondary btn-sm" onclick="emailInvoice(<?= $inv['id'] ?>)" style="padding:4px 8px"><i class="ti ti-mail"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deleteInvoice(<?= $inv['id'] ?>,'<?= h(addslashes($inv['invoice_number'])) ?>')" style="padding:4px 8px"><i class="ti ti-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state"><i class="ti ti-receipt"></i><p>No invoices yet.</p><button class="btn btn-primary mt-1" onclick="window.location.href='quote-invoice.php?view=invoices&new_invoice=1'">Create Invoice</button></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php else: ?>
<?php if ($editQuote): ?>
<div class="card">
    <div class="flex" style="margin-bottom:1rem">
        <button class="btn btn-secondary" onclick="window.location.href='quote-invoice.php'"><i class="ti ti-arrow-left"></i> Back</button>
        <a href="<?= ADMIN_URL ?>/pdf-gen.php?type=quote&id=<?= $editQuote['id'] ?>" class="btn btn-secondary" target="_blank"><i class="ti ti-file-download"></i> Download PDF</a>
    </div>
    <?php include __DIR__ . '/../inc/qi-form.php'; ?>
</div>
<?php else: ?>
<div class="flex" style="justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem">
    <button class="btn btn-primary" onclick="window.location.href='quote-invoice.php?new_quote=1'"><i class="ti ti-plus"></i> New Quotation</button>
</div>
<div class="card">
    <?php if (count($quotes)): ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Client</th><th>Date</th><th>Valid Until</th><th>Total</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($quotes as $q): ?>
                <tr>
                    <td><strong style="color:var(--text);font-size:0.82rem"><?= h($q['quote_number']) ?></strong></td>
                    <td><?= h($q['client_name']) ?></td>
                    <td class="text-muted"><?= $q['date'] ?></td>
                    <td class="text-muted"><?= $q['valid_until'] ?: '-' ?></td>
                    <td><strong>$<?= number_format($q['total'],2) ?></strong></td>
                    <td>
                        <select class="status-select-sm" onchange="updateQuoteStatus(<?= $q['id'] ?>,this.value)">
                            <option value="draft" <?= $q['status']==='draft'?'selected':'' ?>>Draft</option>
                            <option value="sent" <?= $q['status']==='sent'?'selected':'' ?>>Sent</option>
                            <option value="accepted" <?= $q['status']==='accepted'?'selected':'' ?>>Accepted</option>
                            <option value="rejected" <?= $q['status']==='rejected'?'selected':'' ?>>Rejected</option>
                        </select>
                    </td>
                    <td style="text-align:right">
                        <a href="?edit_quote=<?= $q['id'] ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px"><i class="ti ti-pencil"></i></a>
                        <a href="<?= ADMIN_URL ?>/pdf-gen.php?type=quote&id=<?= $q['id'] ?>" target="_blank" class="btn btn-secondary btn-sm" style="padding:4px 8px"><i class="ti ti-file-download"></i></a>
                        <button class="btn btn-danger btn-sm" onclick="deleteQuote(<?= $q['id'] ?>,'<?= h(addslashes($q['quote_number'])) ?>')" style="padding:4px 8px"><i class="ti ti-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state"><i class="ti ti-file-description"></i><p>No quotations yet.</p><button class="btn btn-primary mt-1" onclick="window.location.href='quote-invoice.php?new_quote=1'">Create Quotation</button></div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['new_quote']) || isset($_GET['new_invoice'])): ?>
<div class="card">
    <div class="flex" style="margin-bottom:1rem">
        <button class="btn btn-secondary" onclick="window.location.href='<?= isset($_GET['new_invoice'])?'quote-invoice.php?view=invoices':'quote-invoice.php' ?>'"><i class="ti ti-arrow-left"></i> Back</button>
    </div>
    <?php include __DIR__ . '/../inc/qi-form.php'; ?>
</div>
<?php endif; ?>

<!-- Email Modal -->
<div class="modal-overlay" id="emailModal">
    <div class="modal modal-wide">
        <h3 class="modal-title">Email Invoice</h3>
        <form onsubmit="return sendEmail(event)">
            <input type="hidden" id="email-inv-id" value="0">
            <div class="form-group">
                <label>To</label>
                <input class="form-control" id="email-to" type="email" required placeholder="client@example.com">
            </div>
            <div class="form-group">
                <label>Subject</label>
                <input class="form-control" id="email-subject" value="Your Invoice" required>
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea class="form-control" id="email-message" rows="4">Please find your invoice attached. Let us know if you have any questions.</textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="ti ti-send"></i> Send</button>
                <button type="button" class="btn btn-secondary" onclick="closeEmailModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Quote/Invoice helpers ──
function addItem(tableId) {
    var tbody = document.querySelector('#' + tableId + ' tbody');
    var tr = document.createElement('tr');
    tr.innerHTML = '<td class="item-desc"><input class="form-control" name="item_desc[]" placeholder="Description" style="font-size:0.82rem;padding:6px 8px"></td>' +
        '<td><input class="form-control item-qty" name="item_qty[]" type="number" step="0.01" value="1" style="width:70px;padding:6px 8px;text-align:center" oninput="calcItem(this)"></td>' +
        '<td><input class="form-control" name="item_unit[]" placeholder="unit" style="width:50px;padding:6px 8px"></td>' +
        '<td><input class="form-control item-rate" name="item_rate[]" type="number" step="0.01" value="0" style="width:90px;padding:6px 8px;text-align:center" oninput="calcItem(this)"></td>' +
        '<td class="item-amt">$0.00</td>' +
        '<td><span class="item-del" onclick="this.closest(\'tr\').remove();calcTotal()">&times;</span></td>';
    tbody.appendChild(tr);
}

function calcItem(el) {
    var tr = el.closest('tr');
    var qty = parseFloat(tr.querySelector('.item-qty').value) || 0;
    var rate = parseFloat(tr.querySelector('.item-rate').value) || 0;
    tr.querySelector('.item-amt').textContent = '$' + (qty * rate).toFixed(2);
    calcTotal();
}

function calcTotal() {
    var rows = document.querySelectorAll('.items-table tbody tr');
    var sub = 0;
    rows.forEach(function(r) {
        var txt = r.querySelector('.item-amt').textContent.replace('$','');
        sub += parseFloat(txt) || 0;
    });
    var taxRate = parseFloat(document.querySelector('[name="tax_rate"]')?.value) || 0;
    var taxAmt = sub * (taxRate / 100);
    var total = sub + taxAmt;
    document.getElementById('calc-subtotal').textContent = '$' + sub.toFixed(2);
    document.getElementById('calc-tax').textContent = '$' + taxAmt.toFixed(2);
    document.getElementById('calc-total').textContent = '$' + total.toFixed(2);
}

function collectItems(tableId) {
    var items = [];
    document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function(r) {
        var desc = r.querySelector('[name="item_desc[]"]')?.value || '';
        var qty = parseFloat(r.querySelector('.item-qty')?.value) || 0;
        var unit = r.querySelector('[name="item_unit[]"]')?.value || '';
        var rate = parseFloat(r.querySelector('.item-rate')?.value) || 0;
        if (desc) items.push({description: desc, quantity: qty, unit: unit, rate: rate, amount: qty * rate});
    });
    return items;
}

// ── AI Generation ──
function aiGenerate(type) {
    var idea = document.getElementById('ai-idea')?.value;
    var clientName = document.getElementById('ai-client')?.value;
    if (!idea || !idea.trim()) { showToast('Describe your idea first', 'error'); return; }

    var btn = document.getElementById('ai-gen-btn');
    btn.innerHTML = '<span class="ai-spinner"></span> Generating...';
    btn.disabled = true;

    var fd = new FormData();
    fd.append('action', 'ai_generate');
    fd.append('type', type);
    fd.append('idea', idea);
    fd.append('client_name', clientName || '');

    fetch('quote-invoice.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        btn.innerHTML = '<i class="ti ti-sparkles"></i> AI Generate Items';
        btn.disabled = false;
        if (!j.ok) { showToast(j.error || 'Failed', 'error'); return; }

        var tbody = document.querySelector('#items-table tbody');
        tbody.innerHTML = '';
        j.data.items.forEach(function(it) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td class="item-desc"><input class="form-control" name="item_desc[]" value="' + escAttr(it.description) + '" style="font-size:0.82rem;padding:6px 8px"></td>' +
                '<td><input class="form-control item-qty" name="item_qty[]" type="number" step="0.01" value="' + it.quantity + '" style="width:70px;padding:6px 8px;text-align:center" oninput="calcItem(this)"></td>' +
                '<td><input class="form-control" name="item_unit[]" value="' + escAttr(it.unit || '') + '" style="width:50px;padding:6px 8px"></td>' +
                '<td><input class="form-control item-rate" name="item_rate[]" type="number" step="0.01" value="' + it.rate + '" style="width:90px;padding:6px 8px;text-align:center" oninput="calcItem(this)"></td>' +
                '<td class="item-amt">$' + (it.quantity * it.rate).toFixed(2) + '</td>' +
                '<td><span class="item-del" onclick="this.closest(\'tr\').remove();calcTotal()">&times;</span></td>';
            tbody.appendChild(tr);
        });
        if (j.data.notes) document.getElementById('qi-notes').value = j.data.notes;
        if (j.data.terms) document.getElementById('qi-terms').value = j.data.terms;
        calcTotal();
        showToast('Items generated!');
    })
    .catch(function() {
        btn.innerHTML = '<i class="ti ti-sparkles"></i> AI Generate Items';
        btn.disabled = false;
        showToast('Request failed', 'error');
    });
}

function escAttr(s) {
    if (!s) return '';
    return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Save ──
function saveQi(e, type) {
    e.preventDefault();
    var tableId = 'items-table';
    document.getElementById('qi-items').value = JSON.stringify(collectItems(tableId));
    return true;
}

// ── Delete ──
function deleteQuote(id, name) {
    if (!confirm('Delete quotation ' + name + '?')) return;
    var fd = new FormData(); fd.append('action','quote_delete'); fd.append('id',id);
    fetch('quote-invoice.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(j){if(j.ok){showToast('Deleted');location.reload()}});
}
function deleteInvoice(id, name) {
    if (!confirm('Delete invoice ' + name + '?')) return;
    var fd = new FormData(); fd.append('action','invoice_delete'); fd.append('id',id);
    fetch('quote-invoice.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(j){if(j.ok){showToast('Deleted');location.reload()}});
}
function updateQuoteStatus(id, status) {
    var fd = new FormData(); fd.append('action','quote_status'); fd.append('id',id); fd.append('status',status);
    fetch('quote-invoice.php',{method:'POST',body:fd});
}
function updateInvoiceStatus(id, status) {
    var fd = new FormData(); fd.append('action','invoice_status'); fd.append('id',id); fd.append('status',status);
    fetch('quote-invoice.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(j){if(!j.ok)showToast('Failed','error')});
}

// ── Email ──
function emailInvoice(id) {
    document.getElementById('email-inv-id').value = id;
    document.getElementById('emailModal').classList.add('open');
}
function closeEmailModal() { document.getElementById('emailModal').classList.remove('open'); }
function sendEmail(e) {
    e.preventDefault();
    var id = document.getElementById('email-inv-id').value;
    var fd = new FormData();
    fd.append('action','email_invoice');
    fd.append('id',id);
    fd.append('to',document.getElementById('email-to').value);
    fd.append('subject',document.getElementById('email-subject').value);
    fd.append('message',document.getElementById('email-message').value);
    fetch('quote-invoice.php',{method:'POST',body:fd})
    .then(function(r){return r.json()})
    .then(function(j){
        if(j.ok){showToast('Email sent!');closeEmailModal();location.reload()}
        else showToast(j.error||'Failed','error');
    });
    return false;
}

document.getElementById('emailModal')?.addEventListener('click',function(e){if(e.target===this)closeEmailModal()});

// ── Logo upload ──
function previewLogo(input) {
    var url = input.value.trim();
    var div = document.getElementById('logo-preview');
    if (!div) return;
    if (url) {
        div.innerHTML = '<img src="' + escAttr(url) + '" style="max-height:50px;border-radius:4px;border:1px solid var(--border)">';
    } else { div.innerHTML = ''; }
}
function uploadLogo(input) {
    if (!input.files || !input.files[0]) return;
    var fd = new FormData(); fd.append('file', input.files[0]);
    fetch('../upload.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.success) { document.querySelector('[name="logo"]').value = j.url; previewLogo(document.querySelector('[name="logo"]')); }
        else alert('Upload failed');
    });
}
</script>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
