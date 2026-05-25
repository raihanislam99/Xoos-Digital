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
.chart-container { background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:1.5rem;margin-bottom:1.5rem;height:280px;display:flex;flex-direction:column }
.chart-container canvas { flex:1;max-height:220px;width:100% !important }
.chart-title { font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:1rem;flex-shrink:0 }
</style>

<div class="outreach-stat-strip">
    <div class="oss-item"><div class="oss-icon" style="background:#CCFF0015;color:var(--accent)"><i class="ti ti-users"></i></div><div><div class="oss-val"><?= $totalLeads ?></div><div class="oss-lbl">Total Leads</div></div></div>
    <div class="oss-item"><div class="oss-icon" style="background:#60A5FA15;color:#60A5FA"><i class="ti ti-phone"></i></div><div><div class="oss-val"><?= $contacted ?></div><div class="oss-lbl">Contacted</div></div></div>
    <div class="oss-item"><div class="oss-icon" style="background:#F59E0B15;color:#F59E0B"><i class="ti ti-message-reply"></i></div><div><div class="oss-val"><?= $replied ?></div><div class="oss-lbl">Replied</div></div></div>
    <div class="oss-item"><div class="oss-icon" style="background:#34D39915;color:#34D399"><i class="ti ti-trophy"></i></div><div><div class="oss-val"><?= $closedWon ?></div><div class="oss-lbl">Closed Won</div></div></div>
    <div class="oss-item"><div class="oss-icon" style="background:#CCFF0015;color:var(--accent)"><i class="ti ti-mail"></i></div><div><div class="oss-val"><?= $emailsToday ?></div><div class="oss-lbl">Emails Today</div></div></div>
    <div class="oss-item"><div class="oss-icon" style="background:#CCFF0015;color:var(--accent)"><i class="ti ti-percentage"></i></div><div><div class="oss-val"><?= $openRate ?>%</div><div class="oss-lbl">Open Rate</div></div></div>
    <div class="oss-item"><div class="oss-icon" style="background:#F59E0B15;color:#F59E0B"><i class="ti ti-reply"></i></div><div><div class="oss-val"><?= $replyRate ?>%</div><div class="oss-lbl">Reply Rate</div></div></div>
    <div class="oss-item"><div class="oss-icon" style="background:#34D39915;color:#34D399"><i class="ti ti-trending-up"></i></div><div><div class="oss-val"><?= $conversionRate ?>%</div><div class="oss-lbl">Conversion</div></div></div>
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

<div class="outreach-stat-strip" style="margin-top:1.5rem">
    <div class="oss-item"><div><div class="oss-val"><?= $totalSent ?></div><div class="oss-lbl">Total Emails Sent</div></div></div>
    <div class="oss-item"><div><div class="oss-val"><?= $opened ?></div><div class="oss-lbl">Emails Opened</div></div></div>
    <div class="oss-item"><div><div class="oss-val"><?= $repliedEmails ?></div><div class="oss-lbl">Emails Replied</div></div></div>
    <div class="oss-item"><div><div class="oss-val" style="color:#F59E0B"><?= $totalWithEmail ?></div><div class="oss-lbl">Leads with Email</div></div></div>
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
