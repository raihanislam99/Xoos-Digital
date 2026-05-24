<?php
$totalLeads = (int)db_val("SELECT COUNT(*) FROM leads WHERE is_blacklisted = 0");
$contacted = (int)db_val("SELECT COUNT(*) FROM leads WHERE status IN ('contacted','replied','interested','meeting_booked')");
$replied = (int)db_val("SELECT COUNT(*) FROM leads WHERE status = 'replied'");
$closedWon = (int)db_val("SELECT COUNT(*) FROM leads WHERE status = 'closed_won'");
$emailsToday = (int)db_val("SELECT COUNT(*) FROM lead_emails WHERE DATE(sent_at) = CURDATE() AND status = 'sent'");
$opened = (int)db_val("SELECT COUNT(*) FROM lead_emails WHERE status = 'opened'");
$repliedEmails = (int)db_val("SELECT COUNT(*) FROM lead_emails WHERE status = 'replied'");
$totalSent = (int)db_val("SELECT COUNT(*) FROM lead_emails WHERE status = 'sent'");
$totalWithEmail = (int)db_val("SELECT COUNT(*) FROM leads WHERE email IS NOT NULL AND email != ''");

// Status distribution
$statusDist = db_rows("SELECT status, COUNT(*) as cnt FROM leads WHERE is_blacklisted = 0 GROUP BY status ORDER BY cnt DESC");

// Leads over time (last 30 days)
$leadsOverTime = db_rows("SELECT DATE(created_at) as d, COUNT(*) as cnt FROM leads WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY d ORDER BY d");

// Top niches
$topNiches = db_rows("SELECT niche, COUNT(*) as cnt FROM leads WHERE niche != '' AND is_blacklisted = 0 GROUP BY niche ORDER BY cnt DESC LIMIT 10");

// Email performance by week
$emailPerf = db_rows("SELECT DATE(sent_at) as d, COUNT(*) as sent, SUM(CASE WHEN status='opened' THEN 1 ELSE 0 END) as opened, SUM(CASE WHEN status='replied' THEN 1 ELSE 0 END) as replied FROM lead_emails WHERE sent_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY d ORDER BY d");

$openRate = $totalSent > 0 ? round(($opened / $totalSent) * 100, 1) : 0;
$replyRate = $totalSent > 0 ? round(($repliedEmails / $totalSent) * 100, 1) : 0;
$conversionRate = $totalLeads > 0 ? round(($closedWon / $totalLeads) * 100, 1) : 0;
?>
<style>
.stat-cards { display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:1rem;margin-bottom:2rem }
.stat-card { background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:1.25rem }
.stat-card .stat-value { font-family:'Orbitron',sans-serif;font-size:1.8rem;font-weight:900;line-height:1 }
.stat-card .stat-label { font-size:0.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.06em;margin-top:4px }
.chart-container { background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:1.5rem;margin-bottom:1.5rem;height:280px;display:flex;flex-direction:column }
.chart-container canvas { flex:1;max-height:220px;width:100% !important }
.chart-title { font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:1rem;flex-shrink:0 }
.metrics-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem }
.metric-box { background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:1.25rem;text-align:center }
.metric-box .val { font-family:'Orbitron',sans-serif;font-size:1.5rem;font-weight:900;color:var(--accent) }
.metric-box .lbl { font-size:0.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.06em;margin-top:2px }
</style>

<div class="stat-cards">
    <div class="stat-card"><div class="stat-value" style="color:var(--accent)"><?= $totalLeads ?></div><div class="stat-label">Total Leads</div></div>
    <div class="stat-card"><div class="stat-value" style="color:#60A5FA"><?= $contacted ?></div><div class="stat-label">Contacted</div></div>
    <div class="stat-card"><div class="stat-value" style="color:#F59E0B"><?= $replied ?></div><div class="stat-label">Replied</div></div>
    <div class="stat-card"><div class="stat-value" style="color:#34D399"><?= $closedWon ?></div><div class="stat-label">Closed Won</div></div>
    <div class="stat-card"><div class="stat-value" style="color:var(--accent)"><?= $emailsToday ?></div><div class="stat-label">Emails Today</div></div>
    <div class="stat-card"><div class="stat-value" style="color:var(--accent)"><?= $openRate ?>%</div><div class="stat-label">Open Rate</div></div>
    <div class="stat-card"><div class="stat-value" style="color:#F59E0B"><?= $replyRate ?>%</div><div class="stat-label">Reply Rate</div></div>
    <div class="stat-card"><div class="stat-value" style="color:#34D399"><?= $conversionRate ?>%</div><div class="stat-label">Conversion</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
    <!-- Donut Chart: Status Distribution -->
    <div class="chart-container">
        <div class="chart-title">Lead Status Distribution</div>
        <canvas id="statusChart" height="200"></canvas>
    </div>

    <!-- Line Chart: Leads Over Time -->
    <div class="chart-container">
        <div class="chart-title">Leads Added (Last 30 Days)</div>
        <canvas id="trendChart" height="200"></canvas>
    </div>

    <!-- Bar Chart: Top Niches -->
    <div class="chart-container">
        <div class="chart-title">Top Niches</div>
        <canvas id="nicheChart" height="200"></canvas>
    </div>

    <!-- Funnel Chart -->
    <div class="chart-container">
        <div class="chart-title">Conversion Funnel</div>
        <canvas id="funnelChart" height="200"></canvas>
    </div>
</div>

<div class="metrics-grid">
    <div class="metric-box"><div class="val"><?= $totalSent ?></div><div class="lbl">Total Emails Sent</div></div>
    <div class="metric-box"><div class="val"><?= $opened ?></div><div class="lbl">Emails Opened</div></div>
    <div class="metric-box"><div class="val"><?= $repliedEmails ?></div><div class="lbl">Emails Replied</div></div>
    <div class="metric-box"><div class="val" style="color:#F59E0B"><?= $totalWithEmail ?></div><div class="lbl">Leads with Email</div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Status Distribution - Donut
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusColors = {
    'new': '#374151','contacted': '#1d4ed8','replied': '#854d0e',
    'interested': '#CCFF00','meeting_booked': '#581c87','closed_won': '#14532d','closed_lost': '#7f1d1d'
};
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php foreach ($statusDist as $s): ?>'<?= $s['status'] ?>',<?php endforeach; ?>],
        datasets: [{
            data: [<?php foreach ($statusDist as $s): ?><?= $s['cnt'] ?>,<?php endforeach; ?>],
            backgroundColor: [<?php foreach ($statusDist as $s): ?>'<?= $statusColors[$s['status']] ?? '#374151' ?>',<?php endforeach; ?>],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { color: '#9CA3AF', padding: 12 } } }
    }
});

// Trends - Line
const trendCtx = document.getElementById('trendChart').getContext('2d');
const trendLabels = [<?php foreach ($leadsOverTime as $r): ?>'<?= $r['d'] ?>',<?php endforeach; ?>];
const trendData = [<?php foreach ($leadsOverTime as $r): ?><?= $r['cnt'] ?>,<?php endforeach; ?>];
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [{
            label: 'Leads',
            data: trendData,
            borderColor: '#CCFF00',
            backgroundColor: 'rgba(204,255,0,0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointBackgroundColor: '#CCFF00'
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: '#6B7280', maxTicksLimit: 10 }, grid: { color: 'rgba(255,255,255,0.03)' } },
            y: { ticks: { color: '#6B7280' }, grid: { color: 'rgba(255,255,255,0.03)' }, beginAtZero: true }
        }
    }
});

// Top Niches - Horizontal Bar
const nicheCtx = document.getElementById('nicheChart').getContext('2d');
const nicheLabels = [<?php foreach ($topNiches as $n): ?>'<?= h(addslashes($n['niche'])) ?>',<?php endforeach; ?>];
const nicheData = [<?php foreach ($topNiches as $n): ?><?= $n['cnt'] ?>,<?php endforeach; ?>];
new Chart(nicheCtx, {
    type: 'bar',
    data: {
        labels: nicheLabels,
        datasets: [{
            label: 'Leads',
            data: nicheData,
            backgroundColor: 'rgba(204,255,0,0.7)',
            borderRadius: 4
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: '#6B7280' }, grid: { color: 'rgba(255,255,255,0.03)' }, beginAtZero: true },
            y: { ticks: { color: '#9CA3AF', font: { size: 10 } }, grid: { display: false } }
        }
    }
});

// Funnel Chart
const funnelCtx = document.getElementById('funnelChart').getContext('2d');
const funnelSteps = ['New', 'Contacted', 'Replied', 'Interested', 'Closed Won'];
const funnelData = [
    <?= $totalLeads ?>,
    <?= $contacted ?>,
    <?= $replied ?>,
    <?= db_val("SELECT COUNT(*) FROM leads WHERE status IN ('interested','meeting_booked')") ?>,
    <?= $closedWon ?>
];
new Chart(funnelCtx, {
    type: 'bar',
    data: {
        labels: funnelSteps,
        datasets: [{
            data: funnelData,
            backgroundColor: ['#374151','#1d4ed8','#854d0e','rgba(204,255,0,0.5)','#14532d'],
            borderRadius: 4
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: '#6B7280' }, grid: { color: 'rgba(255,255,255,0.03)' }, beginAtZero: true },
            y: { ticks: { color: '#9CA3AF' }, grid: { display: false } }
        }
    }
});
</script>
