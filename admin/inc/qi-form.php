<?php
// This partial is included by quote-invoice.php
$isEdit = isset($editQuote) && $editQuote !== null;
$data = null;
$items = [];
$formAction = 'quote_invoice.php';
$type = 'quote';

if (isset($editInvoice) && $editInvoice !== null) {
    $isEdit = true;
    $data = $editInvoice;
    $items = $editInvoiceItems;
    $type = 'invoice';
    $formAction = 'quote_invoice.php?view=invoices';
} elseif ($isEdit) {
    $data = $editQuote;
    $items = $editQuoteItems;
} elseif (isset($_GET['new_invoice'])) {
    $type = 'invoice';
}

$clientName = $data['client_name'] ?? '';
$clientEmail = $data['client_email'] ?? '';
$clientPhone = $data['client_phone'] ?? '';
$clientAddress = $data['client_address'] ?? '';
$date = $data['date'] ?? date('Y-m-d');
$dueDate = $data['due_date'] ?? $data['valid_until'] ?? '';
$notes = $data['notes'] ?? '';
$terms = $data['terms'] ?? '';
$taxRate = $data['tax_rate'] ?? 0;
$subtotal = $data['subtotal'] ?? 0;
$taxAmount = $data['tax_amount'] ?? 0;
$total = $data['total'] ?? 0;
$docTitle = $type === 'invoice' ? 'Invoice' : 'Quotation';
$docNumber = $data['invoice_number'] ?? $data['quote_number'] ?? ($type === 'invoice' ? 'INV-2025-0001' : 'QTE-2025-0001');
$saveAction = $type === 'invoice' ? 'invoice_save' : 'quote_save';
?>

<h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:1rem">
    <i class="ti <?= $type==='invoice'?'ti-receipt':'ti-file-description' ?>"></i>
    <?= $isEdit ? 'Edit ' . $docTitle : 'New ' . $docTitle ?>
    <span class="text-muted" style="font-weight:400;text-transform:none;letter-spacing:0;margin-left:12px;font-size:0.75rem"><?= $docNumber ?></span>
</h3>

<div class="ai-gen-area">
    <div class="form-row" style="align-items:end">
        <div class="form-group" style="margin-bottom:0">
            <label style="color:var(--accent)"><i class="ti ti-sparkles"></i> AI Assist — Describe your idea</label>
            <textarea class="form-control" id="ai-idea" rows="2" placeholder="e.g. I need a website redesign package with SEO optimization and social media setup for a local restaurant..." style="font-size:0.82rem"><?= h($clientName ? "Client: $clientName - " : '') ?></textarea>
        </div>
        <div class="form-group" style="margin-bottom:0">
            <label>Client Name</label>
            <input class="form-control" id="ai-client" value="<?= h($clientName) ?>" placeholder="Client name" style="font-size:0.82rem">
        </div>
        <div class="form-group" style="margin-bottom:0">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-ai" id="ai-gen-btn" onclick="aiGenerate('<?= $type ?>')"><i class="ti ti-sparkles"></i> Generate Items</button>
        </div>
    </div>
</div>

<form method="post" action="<?= $formAction ?>" onsubmit="return saveQi(event,'<?= $type ?>')">
    <input type="hidden" name="action" value="<?= $saveAction ?>">
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $data['id'] ?>"><?php endif; ?>
    <input type="hidden" name="items" id="qi-items" value="">

    <div class="form-row">
        <div class="form-group">
            <label>Client Name</label>
            <input class="form-control" name="client_name" value="<?= h($clientName) ?>" required placeholder="Client or company name">
        </div>
        <div class="form-group">
            <label>Client Email</label>
            <input class="form-control" name="client_email" type="email" value="<?= h($clientEmail) ?>" placeholder="client@example.com">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>Client Phone</label>
            <input class="form-control" name="client_phone" value="<?= h($clientPhone) ?>" placeholder="+880...">
        </div>
        <div class="form-group">
            <label>Date</label>
            <input class="form-control" name="date" type="date" value="<?= h($date) ?>" required>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>Client Address</label>
            <textarea class="form-control" name="client_address" rows="2" placeholder="Client address"><?= h($clientAddress) ?></textarea>
        </div>
        <div class="form-group">
            <label><?= $type==='invoice' ? 'Due Date' : 'Valid Until' ?></label>
            <input class="form-control" name="<?= $type==='invoice'?'due_date':'valid_until' ?>" type="date" value="<?= h($dueDate) ?>">
        </div>
    </div>

    <div class="form-group">
        <label>Line Items</label>
        <table class="items-table" id="items-table">
            <thead>
                <tr><th style="width:40%">Description</th><th style="width:10%">Qty</th><th style="width:8%">Unit</th><th style="width:12%">Rate</th><th style="width:12%">Amount</th><th style="width:5%"></th></tr>
            </thead>
            <tbody>
                <?php if (count($items)): 
                    foreach ($items as $it): ?>
                    <tr>
                        <td class="item-desc"><input class="form-control" name="item_desc[]" value="<?= h($it['description']) ?>" style="font-size:0.82rem;padding:6px 8px"></td>
                        <td><input class="form-control item-qty" name="item_qty[]" type="number" step="0.01" value="<?= h($it['quantity']) ?>" style="width:70px;padding:6px 8px;text-align:center" oninput="calcItem(this)"></td>
                        <td><input class="form-control" name="item_unit[]" value="<?= h($it['unit']) ?>" style="width:50px;padding:6px 8px"></td>
                        <td><input class="form-control item-rate" name="item_rate[]" type="number" step="0.01" value="<?= h($it['rate']) ?>" style="width:90px;padding:6px 8px;text-align:center" oninput="calcItem(this)"></td>
                        <td class="item-amt">$<?= number_format($it['amount'],2) ?></td>
                        <td><span class="item-del" onclick="this.closest('tr').remove();calcTotal()">&times;</span></td>
                    </tr>
                    <?php endforeach; 
                else: ?>
                <tr>
                    <td class="item-desc"><input class="form-control" name="item_desc[]" placeholder="e.g. Website Design" style="font-size:0.82rem;padding:6px 8px"></td>
                    <td><input class="form-control item-qty" name="item_qty[]" type="number" step="0.01" value="1" style="width:70px;padding:6px 8px;text-align:center" oninput="calcItem(this)"></td>
                    <td><input class="form-control" name="item_unit[]" placeholder="hr" style="width:50px;padding:6px 8px"></td>
                    <td><input class="form-control item-rate" name="item_rate[]" type="number" step="0.01" value="0" style="width:90px;padding:6px 8px;text-align:center" oninput="calcItem(this)"></td>
                    <td class="item-amt">$0.00</td>
                    <td><span class="item-del" onclick="this.closest('tr').remove();calcTotal()">&times;</span></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <button type="button" class="btn btn-secondary btn-sm" onclick="addItem('items-table')" style="margin-top:6px"><i class="ti ti-plus"></i> Add Item</button>
    </div>

    <div class="form-row" style="max-width:300px;margin-left:auto">
        <div class="form-group">
            <label>Tax Rate (%)</label>
            <input class="form-control" name="tax_rate" type="number" step="0.01" value="<?= h($taxRate) ?>" oninput="calcTotal()" style="text-align:center">
        </div>
    </div>

    <div style="text-align:right;margin:0.75rem 0;padding:0.75rem;background:var(--bg3);border-radius:var(--radius-sm)">
        <div style="font-size:0.85rem;color:var(--text2)">Subtotal: <strong id="calc-subtotal">$<?= number_format($subtotal,2) ?></strong></div>
        <div style="font-size:0.85rem;color:var(--text2)">Tax: <strong id="calc-tax">$<?= number_format($taxAmount,2) ?></strong></div>
        <div style="font-size:1.1rem;color:var(--text);margin-top:4px;border-top:2px solid var(--accent);padding-top:4px">Total: <strong id="calc-total">$<?= number_format($total,2) ?></strong></div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Notes</label>
            <textarea class="form-control" name="notes" id="qi-notes" rows="3" placeholder="Additional notes / instructions..."><?= h($notes) ?></textarea>
        </div>
        <div class="form-group">
            <label>Terms</label>
            <textarea class="form-control" name="terms" id="qi-terms" rows="3" placeholder="Payment terms, delivery terms..."><?= h($terms) ?></textarea>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save <?= $docTitle ?></button>
        <button type="button" class="btn btn-secondary" onclick="window.location.href='<?= $type==='invoice'?'quote-invoice.php?view=invoices':'quote-invoice.php' ?>'">Cancel</button>
    </div>
</form>
