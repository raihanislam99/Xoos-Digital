<?php
require_once __DIR__ . '/inc/functions.php';
require_login();

$pdo = db();
$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

if (!$type || !$id) {
    header('HTTP/1.0 400 Bad Request');
    exit('Missing parameters');
}

$company = $pdo->query("SELECT * FROM company_info WHERE id = 1")->fetch();

if ($type === 'quote') {
    $doc = $pdo->query("SELECT * FROM quotations WHERE id = $id")->fetch();
    if (!$doc) { header('HTTP/1.0 404 Not Found'); exit('Quote not found'); }
    $items = $pdo->query("SELECT * FROM quotation_items WHERE quotation_id = $id")->fetchAll();
    $docTypeLabel   = 'QUOTATION';
    $dateLabel      = 'Issue Date';
    $dueLabel       = 'Valid Until';
    $dueField       = $doc['valid_until'];
    $docNumberField = $doc['quote_number'];
    $status         = $doc['status'] ?? 'draft';
} elseif ($type === 'invoice') {
    $doc = $pdo->query("SELECT * FROM invoices WHERE id = $id")->fetch();
    if (!$doc) { header('HTTP/1.0 404 Not Found'); exit('Invoice not found'); }
    $items = $pdo->query("SELECT * FROM invoice_items WHERE invoice_id = $id")->fetchAll();
    $docTypeLabel   = 'INVOICE';
    $dateLabel      = 'Issue Date';
    $dueLabel       = 'Due Date';
    $dueField       = $doc['due_date'];
    $docNumberField = $doc['invoice_number'];
    $status         = $doc['status'] ?? 'draft';
} else {
    header('HTTP/1.0 400 Bad Request');
    exit('Invalid type');
}

$date          = $doc['date'] ?? date('Y-m-d');
$dueDate       = $dueField ?? '';
$currency      = $doc['currency'] ?? 'USD';
$clientName    = $doc['client_name'] ?? '';
$clientAddress = $doc['client_address'] ?? '';
$clientEmail   = $doc['client_email'] ?? '';
$clientPhone   = $doc['client_phone'] ?? '';
$subtotal      = (float)($doc['subtotal'] ?? 0);
$taxRate       = (float)($doc['tax_rate'] ?? 0);
$tax           = (float)($doc['tax_amount'] ?? 0);
$total         = (float)($doc['total'] ?? 0);
$notes         = $doc['notes'] ?? '';
$terms         = $doc['terms'] ?? '';

$coName    = $company['company_name'] ?? 'Xoos Digital';
$coAddress = $company['address'] ?? 'Khilgaon';
$coCity    = $company['city'] ?? 'Dhaka';
$coZip     = $company['zip'] ?? '1219';
$coCountry = $company['country'] ?? 'Bangladesh';
$coPhone   = $company['phone'] ?? '+880 1572-932943';
$coEmail   = $company['email'] ?? 'xoosdigital@gmail.com';
$coWebsite = $company['website'] ?? 'xoosdigital.com';
$coLogo    = $company['logo'] ?? '';
$coFullAddr = trim(implode(', ', array_filter([$coAddress, $coCity, $coZip, $coCountry])));

$sym       = $currency === 'BDT' ? '&#2547;' : '$';
$statusUp  = strtoupper($status);
$daysLeft  = $dueDate ? ceil((strtotime($dueDate) - time()) / 86400) : 0;
$finalTerms = $terms ?: "This quotation is valid for 10 days from the date of issue.\nA 50% advance payment is required to commence the project.\nThe remaining 50% is due upon project completion before final delivery.\nAccepted payment methods: bKash, Nagad, Payoneer, Wise, bank transfer.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $docTypeLabel ?> <?= htmlspecialchars($docNumberField) ?> — <?= htmlspecialchars($coName) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter','Segoe UI',Arial,sans-serif;background:#FFFFFF;color:#111;width:210mm;min-height:297mm;display:flex;flex-direction:column}

/* PRINT */
@media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact}.no-print{display:none!important}@page{size:A4;margin:0}}

/* TOOLBAR */
.toolbar{position:fixed;top:0;left:0;right:0;background:#fff;padding:12px 40px;display:flex;align-items:center;gap:12px;z-index:9999;border-bottom:1px solid #e5e7eb;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.toolbar .info{flex:1;color:#374151;font-size:14px;font-weight:600}
.toolbar button{background:#111;color:#fff;border:none;padding:8px 22px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter','Segoe UI',sans-serif;transition:opacity .2s}
.toolbar button:hover{opacity:.85}
.toolbar button.sec{background:#f3f4f6;color:#374151}
.page-wrap{margin-top:50px}

/* HEADER */
.header{background:#000;padding:32px 44px 24px;display:flex;justify-content:space-between;align-items:flex-start}
.header-left{flex:1}
.logo-row{display:flex;align-items:center;gap:14px;margin-bottom:8px}
.logo-img{max-width:48px;max-height:48px;border-radius:4px;display:block}
.logo-mark{width:44px;height:44px;flex-shrink:0}
.logo-x{font-size:26px;font-weight:800;color:#CCFF00;letter-spacing:-.02em}
.logo-rest{font-size:26px;font-weight:800;color:#fff;letter-spacing:-.02em}
.tagline{font-size:10px;color:#9CA3AF;letter-spacing:.15em;margin-top:2px}
.tagline .hl{color:#CCFF00}
.header-contact{font-size:11px;color:#6b7280;line-height:1.8;margin-top:4px}
.header-contact span{color:#9CA3AF}
.header-right{text-align:right;flex-shrink:0}
.doc-type{font-size:24px;font-weight:800;color:#CCFF00;letter-spacing:.08em;line-height:1}
.doc-number-top{font-size:12px;color:#9CA3AF;margin-top:4px;letter-spacing:.04em}

/* STATUS BADGE */
.status-badge{display:inline-block;margin-top:12px;padding:5px 18px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
.status-draft{background:#CCFF00;color:#111}
.status-sent{background:#dbeafe;color:#1d4ed8}
.status-accepted{background:#CCFF00;color:#111}
.status-paid{background:#d1fae5;color:#065f46}
.status-declined,.status-rejected{background:#fee2e2;color:#991b1b}
.status-overdue{background:#fee2e2;color:#991b1b}
.status-cancelled{background:#f3f4f6;color:#6b7280}

/* SEPARATOR */
.sep{height:2px;background:linear-gradient(90deg,#CCFF00 0%,#CCFF00 60%,#e5e7eb 60%,#e5e7eb 100%);margin:0 44px}

/* BODY */
.body{padding:28px 44px 44px;flex:1}

/* BILL TO */
.bill-wrap{display:flex;justify-content:space-between;gap:32px;margin-bottom:28px}
.bill-left{flex:1}
.bill-label{font-size:10px;font-weight:700;color:#333;letter-spacing:.18em;text-transform:uppercase;margin-bottom:4px}
.bill-name{font-size:18px;font-weight:700;color:#111;margin-bottom:4px}
.bill-detail{font-size:13px;color:#6b7280;line-height:1.7}
.bill-right{flex-shrink:0;text-align:right}
.bill-meta{display:flex;gap:28px;flex-wrap:wrap;max-width:360px;justify-content:flex-end}
.bill-meta-item{text-align:right}
.bill-meta-label{font-size:9px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.1em;margin-bottom:2px}
.bill-meta-value{font-size:14px;font-weight:600;color:#111;white-space:nowrap}
.bill-meta-sub{font-size:11px;color:#9CA3AF;margin-top:1px}

/* TABLE */
.items-table{width:100%;border-collapse:collapse;margin-bottom:24px}
.items-table thead th{padding:12px 16px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#555;background:#f9fafb;border-bottom:1px solid #e5e7eb}
.items-table thead th:last-child,.items-table thead th:nth-child(2),.items-table thead th:nth-child(3),.items-table thead th:nth-child(4){text-align:right}
.items-table tbody td{padding:13px 16px;font-size:13px;color:#374151;vertical-align:top;border-bottom:1px solid #f3f4f6}
.items-table tbody td:last-child,.items-table tbody td:nth-child(2),.items-table tbody td:nth-child(3),.items-table tbody td:nth-child(4){text-align:right;font-variant-numeric:tabular-nums}
.items-table tbody tr:last-child td{border-bottom:none}
.item-desc-name{font-weight:600;color:#111}

/* TOTALS */
.totals{display:flex;justify-content:flex-end;margin-bottom:28px}
.totals-box{width:280px}
.total-row{display:flex;justify-content:space-between;padding:6px 0;font-size:13px;color:#6b7280}
.total-row .label{}
.total-row .value{font-weight:600;color:#111;font-variant-numeric:tabular-nums}
.total-final{display:flex;justify-content:space-between;align-items:center;margin-top:6px;padding:12px 0 0;border-top:2px solid #CCFF00}
.total-final .label{font-size:12px;font-weight:700;color:#111;text-transform:uppercase;letter-spacing:.06em}
.total-final .value{font-size:20px;font-weight:800;color:#111;font-variant-numeric:tabular-nums}
.total-currency-note{text-align:right;font-size:10px;color:#9CA3AF;margin-top:4px}

/* NOTES / TERMS */
.notes-terms{display:flex;gap:24px;padding-top:18px;border-top:1px solid #e5e7eb}
.notes-block,.terms-block{flex:1}
.section-label{font-size:10px;font-weight:700;color:#333;text-transform:uppercase;letter-spacing:.15em;margin-bottom:6px}
.section-text{font-size:13px;color:#6b7280;line-height:1.7}

/* FOOTER */
.footer{margin-top:auto;padding:18px 44px;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;font-size:10px;color:#9CA3AF}
.footer a{color:#6b7280;text-decoration:none}
</style>
</head>
<body>

<div class="toolbar no-print">
    <div class="info"><?= htmlspecialchars($docTypeLabel) ?> — <?= htmlspecialchars($docNumberField) ?></div>
    <button class="sec" onclick="window.close()">Close</button>
    <button onclick="window.print()">&#128196; Save / Print PDF</button>
</div>

<div class="page-wrap">

<div class="header">
    <div class="header-left">
        <div class="logo-row">
            <?php if ($coLogo): ?>
            <img class="logo-img" src="<?= htmlspecialchars(str_starts_with($coLogo, 'http') ? $coLogo : BASE_URL . '/' . $coLogo) ?>" alt="Logo">
            <?php else: ?>
            <svg class="logo-mark" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="44" height="44" rx="8" fill="#CCFF00"/>
                <path d="M9 9L22 22M22 22L35 35M22 22L35 9M22 22L9 35" stroke="#1a1a2e" stroke-width="4.5" stroke-linecap="round"/>
            </svg>
            <?php endif; ?>
            <div>
                <div><span class="logo-x">X</span><span class="logo-rest">OOS DIGITAL</span></div>
                <div class="tagline">e<span class="hl">X</span>cellence · <span class="hl">O</span>pportunity · <span class="hl">O</span>utcome · <span class="hl">S</span>uccess</div>
            </div>
        </div>
        <div class="header-contact">
            <?= htmlspecialchars($coFullAddr) ?><br>
            <?= htmlspecialchars($coPhone) ?> &nbsp;|&nbsp; <?= htmlspecialchars($coEmail) ?> &nbsp;|&nbsp; <?= htmlspecialchars($coWebsite) ?>
        </div>
    </div>
    <div class="header-right">
        <div class="doc-type"><?= htmlspecialchars($docTypeLabel) ?></div>
        <div class="doc-number-top"><?= htmlspecialchars($docNumberField) ?></div>
    </div>
</div>

<div class="sep"></div>

<div class="body">
    <div class="bill-wrap">
        <div class="bill-left">
            <div class="bill-label">Bill To</div>
            <div class="bill-name"><?= htmlspecialchars($clientName) ?></div>
            <div class="bill-detail">
                <?= nl2br(htmlspecialchars($clientAddress ?? '')) ?>
                <?php if ($clientEmail || $clientPhone): ?>
                <br><?= implode(' &nbsp;|&nbsp; ', array_map('htmlspecialchars', array_filter([$clientEmail, $clientPhone]))) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="bill-right">
            <div class="bill-meta">
                <div class="bill-meta-item">
                    <div class="bill-meta-label">Issue Date</div>
                    <div class="bill-meta-value"><?= date('d M Y', strtotime($date)) ?></div>
                </div>
                <div class="bill-meta-item">
                    <div class="bill-meta-label"><?= $dueLabel ?></div>
                    <div class="bill-meta-value"><?= $dueDate ? date('d M Y', strtotime($dueDate)) : '—' ?></div>
                    <?php if ($daysLeft > 0 && $daysLeft <= 5): ?>
                    <div class="bill-meta-sub" style="color:#EF4444">Expires in <?= $daysLeft ?> day(s)</div>
                    <?php endif; ?>
                </div>
                <div class="bill-meta-item">
                    <div class="bill-meta-label">Payment</div>
                    <div class="bill-meta-value">50% Advance</div>
                    <div class="bill-meta-sub">50% on delivery</div>
                </div>
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:44%">Description</th>
                <th style="width:9%">Qty</th>
                <th style="width:11%">Unit</th>
                <th style="width:16%">Rate</th>
                <th style="width:20%">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it):
                $qty  = (float)($it['quantity'] ?? 1);
                $rate = (float)($it['rate'] ?? 0);
                $amt  = (float)($it['amount'] ?? $qty * $rate);
                $desc = $it['description'] ?? '';
                $unit = $it['unit'] ?? '';
            ?>
            <tr>
                <td>
                    <div class="item-desc-name"><?= htmlspecialchars($desc) ?></div>
                </td>
                <td><?= number_format($qty, 2) ?></td>
                <td><?= htmlspecialchars($unit) ?></td>
                <td><?= $sym ?><?= number_format($rate, 2) ?></td>
                <td><strong><?= $sym ?><?= number_format($amt, 2) ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-box">
            <div class="total-row">
                <span class="label">Subtotal</span>
                <span class="value"><?= $sym ?><?= number_format($subtotal, 2) ?></span>
            </div>
            <?php if ($taxRate > 0 && $tax > 0): ?>
            <div class="total-row">
                <span class="label">Tax (<?= number_format($taxRate, 1) ?>%)</span>
                <span class="value"><?= $sym ?><?= number_format($tax, 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="total-final">
                <span class="label">TOTAL</span>
                <span class="value"><?= $sym ?><?= number_format($total, 2) ?></span>
            </div>
            <?php if ($currency === 'BDT'): ?>
            <div class="total-currency-note">All amounts in Bangladeshi Taka (BDT)</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="notes-terms">
        <?php if ($notes): ?>
        <div class="notes-block">
            <div class="section-label">Notes</div>
            <div class="section-text"><?= nl2br(htmlspecialchars($notes)) ?></div>
        </div>
        <?php endif; ?>
        <div class="terms-block">
            <div class="section-label">Terms &amp; Conditions</div>
            <div class="section-text"><?= nl2br(htmlspecialchars($finalTerms)) ?></div>
        </div>
    </div>
</div>

<div class="footer">
    <span><?= htmlspecialchars($coName) ?> — <?= htmlspecialchars($coWebsite) ?></span>
    <span>Generated on <?= date('d M Y') ?></span>
</div>

</div>

<?php if (!isset($_GET['preview'])): ?>
<script>
(function(){setTimeout(function(){window.print()},600)})();
</script>
<?php endif; ?>
</body>
</html>