<?php
require_once __DIR__ . '/lib/fpdf.php';

class LetterPDF extends FPDF {
    private $company;
    private $docType = 'Quote';
    private $docNumber = '';
    private $clientName = '';
    private $clientAddress = '';
    private $date = '';
    private $dueDate = '';
    private $items = [];
    private $notes = '';
    private $terms = '';
    private $subtotal = 0;
    private $taxRate = 0;
    private $taxAmount = 0;
    private $total = 0;

    function setCompany($data) { $this->company = $data; }
    function setDocType($t) { $this->docType = $t; }
    function setDocNumber($n) { $this->docNumber = $n; }
    function setClient($name, $addr) { $this->clientName = $name; $this->clientAddress = $addr; }
    function setDates($d, $dd) { $this->date = $d; $this->dueDate = $dd; }
    function setItems($items) { $this->items = $items; }
    function setNotes($n) { $this->notes = $n; }
    function setTerms($t) { $this->terms = $t; }
    function setTotals($sub, $rate, $tax, $total) { $this->subtotal = $sub; $this->taxRate = $rate; $this->taxAmount = $tax; $this->total = $total; }

    function Header() {
        $c = $this->company;

        // Top accent line
        $this->SetFillColor(204, 255, 0);
        $this->Rect(0, 0, $this->w, 3, 'F');

        // Logo
        $logoY = 12;
        if (!empty($c['logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($c['logo'], '/'))) {
            $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($c['logo'], '/');
            $this->Image($logoPath, $this->lMargin, $logoY, 40);
        }

        // Company info (right-aligned)
        $this->SetFont('Helvetica', 'B', 16);
        $this->SetTextColor(20, 30, 50);
        $name = !empty($c['company_name']) ? $c['company_name'] : 'Your Company';
        $this->SetXY($this->lMargin + 45, $logoY);
        $this->Cell($this->w - $this->lMargin - $this->rMargin - 45, 8, $name, 0, 2, 'L');

        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(100, 110, 120);
        $info = [];
        if (!empty($c['address'])) $info[] = $c['address'];
        if (!empty($c['city']) || !empty($c['state'])) $info[] = trim(($c['city']??'') . ', ' . ($c['state']??'') . ' ' . ($c['zip']??''));
        if (!empty($c['phone'])) $info[] = 'Phone: ' . $c['phone'];
        if (!empty($c['email'])) $info[] = 'Email: ' . $c['email'];
        if (!empty($c['website'])) $info[] = 'Web: ' . $c['website'];
        if (!empty($c['tax_id'])) $info[] = 'Tax ID: ' . $c['tax_id'];

        $this->SetX($this->lMargin + 45);
        $this->MultiCell($this->w - $this->lMargin - $this->rMargin - 45, 4, implode("\n", $info), 0, 'L');

        // Separator line
        $this->SetY(40);
        $this->SetDrawColor(200, 210, 220);
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
        $this->SetY($this->GetY() + 6);

        // Document title
        $this->SetFont('Helvetica', 'B', 20);
        $this->SetTextColor(20, 30, 50);
        $this->Cell(0, 10, $this->docType, 0, 2, 'R');

        // Doc number
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(100, 110, 120);
        $this->Cell(0, 6, $this->docNumber, 0, 2, 'R');
        $this->Ln(4);

        $this->tMarginDu = $this->GetY();
    }

    function body() {
        // Client info (left) + Dates (right)
        $y = $this->tMarginDu;

        // Bill To
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetTextColor(80, 90, 100);
        $this->SetXY($this->lMargin, $y);
        $this->Cell(60, 5, 'BILL TO', 0, 2);

        $this->SetFont('Helvetica', 'B', 11);
        $this->SetTextColor(20, 30, 50);
        $this->SetX($this->lMargin);
        $this->Cell(60, 6, $this->clientName, 0, 2);

        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(100, 110, 120);
        $this->SetX($this->lMargin);
        $this->MultiCell(70, 5, $this->clientAddress, 0, 'L');
        $clientEnd = $this->GetY();

        // Dates (right)
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(100, 110, 120);
        $rightX = $this->w - $this->rMargin - 60;

        $rows = [
            ['Date:', $this->date],
            [$this->docType === 'Invoice' ? 'Due Date:' : 'Valid Until:', $this->dueDate],
        ];

        foreach ($rows as $i => $r) {
            $ry = $y + ($i * 6);
            $this->SetXY($rightX, $ry);
            $this->SetFont('Helvetica', 'B', 9);
            $this->SetTextColor(80, 90, 100);
            $this->Cell(25, 5, $r[0], 0, 0, 'R');
            $this->SetFont('Helvetica', '', 9);
            $this->SetTextColor(20, 30, 50);
            $this->Cell(35, 5, $r[1], 0, 2, 'R');
        }

        $this->SetY(max($clientEnd + 8, $y + 25));

        // Items table
        $this->SetDrawColor(200, 210, 220);
        $this->SetFillColor(245, 247, 250);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetTextColor(80, 90, 100);

        $colW = [$this->w - $this->lMargin - $this->rMargin - 110, 25, 20, 30, 35];
        $headers = ['Description', 'Qty', 'Unit', 'Rate', 'Amount'];
        $startX = $this->lMargin;
        $h = 8;

        // Header row
        $this->SetX($startX);
        foreach ($headers as $i => $hdr) {
            $this->Cell($colW[$i], $h, $hdr, 'LTB', 0, 'C', true);
        }
        $this->Ln();
        $yAfterHeader = $this->GetY();

        // Items
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(40, 50, 60);
        $fill = false;
        foreach ($this->items as $item) {
            if ($this->GetY() > $this->PageBreakTrigger - 15) {
                $this->AddPage();
                $this->SetDrawColor(200, 210, 220);
                $this->SetFont('Helvetica', 'B', 8);
                $this->SetTextColor(80, 90, 100);
                $this->SetX($startX);
                foreach ($headers as $i => $hdr) {
                    $this->Cell($colW[$i], $h, $hdr, 'LTB', 0, 'C', true);
                }
                $this->Ln();
                $this->SetFont('Helvetica', '', 9);
                $this->SetTextColor(40, 50, 60);
            }

            $desc = $item['description'] ?? '';
            $qty = number_format((float)($item['quantity'] ?? 1), 2);
            $unit = $item['unit'] ?? '';
            $rate = number_format((float)($item['rate'] ?? 0), 2);
            $amt = number_format((float)($item['amount'] ?? 0), 2);

            $this->SetX($startX);
            if ($fill) $this->SetFillColor(248, 250, 252);
            else $this->SetFillColor(255, 255, 255);
            $this->Cell($colW[0], 8, ' ' . $desc, 'LB', 0, 'L', true);
            $this->Cell($colW[1], 8, $qty, 'B', 0, 'C', true);
            $this->Cell($colW[2], 8, $unit, 'B', 0, 'C', true);
            $this->Cell($colW[3], 8, $rate, 'B', 0, 'C', true);
            $this->Cell($colW[4], 8, $amt, 'BR', 0, 'R', true);
            $this->Ln();
            $fill = !$fill;
        }

        // Bottom line
        $this->SetDrawColor(200, 210, 220);
        $this->SetX($startX);
        $this->Cell(array_sum($colW), 0, '', 'T');
        $this->Ln(6);

        // Totals (right-aligned)
        $totalX = $this->w - $this->rMargin - 60;
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(80, 90, 100);
        $this->SetXY($totalX, $this->GetY());
        $this->Cell(30, 6, 'Subtotal:', 0, 0, 'R');
        $this->SetTextColor(20, 30, 50);
        $this->Cell(30, 6, '$' . number_format($this->subtotal, 2), 0, 2, 'R');

        if ($this->taxRate > 0) {
            $this->SetTextColor(80, 90, 100);
            $this->SetX($totalX);
            $this->Cell(30, 6, 'Tax (' . number_format($this->taxRate, 1) . '%):', 0, 0, 'R');
            $this->SetTextColor(20, 30, 50);
            $this->Cell(30, 6, '$' . number_format($this->taxAmount, 2), 0, 2, 'R');
        }

        // Total line
        $this->SetDrawColor(204, 255, 0);
        $this->SetLineWidth(0.5);
        $this->SetX($totalX);
        $this->Cell(60, 0, '', 'T');
        $this->Ln(5);

        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor(20, 30, 50);
        $this->SetX($totalX);
        $this->Cell(30, 8, 'Total:', 0, 0, 'R');
        $this->Cell(30, 8, '$' . number_format($this->total, 2), 0, 2, 'R');
        $this->SetLineWidth(0.2);

        // Notes & Terms
        $this->Ln(6);
        if (!empty($this->notes)) {
            $this->SetFont('Helvetica', 'B', 9);
            $this->SetTextColor(80, 90, 100);
            $this->Cell(0, 5, 'NOTES:', 0, 2);
            $this->SetFont('Helvetica', '', 8);
            $this->SetTextColor(100, 110, 120);
            $this->MultiCell(0, 4, $this->notes, 0, 'L');
            $this->Ln(2);
        }

        if (!empty($this->terms)) {
            $this->SetFont('Helvetica', 'B', 9);
            $this->SetTextColor(80, 90, 100);
            $this->Cell(0, 5, 'TERMS:', 0, 2);
            $this->SetFont('Helvetica', '', 8);
            $this->SetTextColor(100, 110, 120);
            $this->MultiCell(0, 4, $this->terms, 0, 'L');
        }
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetDrawColor(200, 210, 220);
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
        $this->SetY($this->GetY() + 3);
        $this->SetFont('Helvetica', '', 7);
        $this->SetTextColor(150, 160, 170);
        $c = $this->company;
        $name = !empty($c['company_name']) ? $c['company_name'] : '';
        $this->Cell(0, 4, 'Page ' . $this->PageNo() . ' / {nb} | ' . $name . ' | ' . (!empty($c['email']) ? $c['email'] : '') . ' | ' . (!empty($c['phone']) ? $c['phone'] : ''), 0, 0, 'C');
    }
}

// === Generate handler ===
if (isset($_GET['type']) && isset($_GET['id'])) {
    require_once __DIR__ . '/inc/functions.php';
    require_login();

    $pdo = db();
    $type = $_GET['type'];
    $id = (int)$_GET['id'];

    $company = $pdo->query("SELECT * FROM company_info WHERE id = 1")->fetch();
    if (!$company) $company = [];

    $pdf = new LetterPDF();
    $pdf->AliasNbPages();
    $pdf->setCompany($company);

    if ($type === 'quote') {
        $q = $pdo->query("SELECT * FROM quotations WHERE id = $id")->fetch();
        if (!$q) { header('HTTP/1.0 404 Not Found'); exit; }
        $items = $pdo->query("SELECT * FROM quotation_items WHERE quotation_id = $id")->fetchAll();
        $pdf->setDocType('Quotation');
        $pdf->setDocNumber($q['quote_number']);
        $pdf->setClient($q['client_name'], $q['client_address']);
        $pdf->setDates($q['date'], $q['valid_until']);
        $pdf->setItems($items);
        $pdf->setNotes($q['notes']);
        $pdf->setTerms($q['terms']);
        $pdf->setTotals($q['subtotal'], $q['tax_rate'], $q['tax_amount'], $q['total']);
    } elseif ($type === 'invoice') {
        $inv = $pdo->query("SELECT * FROM invoices WHERE id = $id")->fetch();
        if (!$inv) { header('HTTP/1.0 404 Not Found'); exit; }
        $items = $pdo->query("SELECT * FROM invoice_items WHERE invoice_id = $id")->fetchAll();
        $pdf->setDocType('Invoice');
        $pdf->setDocNumber($inv['invoice_number']);
        $pdf->setClient($inv['client_name'], $inv['client_address']);
        $pdf->setDates($inv['date'], $inv['due_date']);
        $pdf->setItems($items);
        $pdf->setNotes($inv['notes']);
        $pdf->setTerms($inv['terms']);
        $pdf->setTotals($inv['subtotal'], $inv['tax_rate'], $inv['tax_amount'], $inv['total']);
    } else {
        header('HTTP/1.0 400 Bad Request');
        exit;
    }

    $pdf->AddPage();
    $pdf->body();
    $pdf->Output('D', str_replace(['/', '\\'], '-', $type . '_' . $pdf->docNumber) . '.pdf');
    exit;
}
