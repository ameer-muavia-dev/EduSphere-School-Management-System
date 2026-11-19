<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin']);

$pageTitle = 'Admin Dashboard';
$activeMenu = 'dashboard.php';

$totalStudents = $conn->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'];
$totalStaff = $conn->query("SELECT COUNT(*) c FROM staff")->fetch_assoc()['c'];
$totalClasses = $conn->query("SELECT COUNT(*) c FROM classes")->fetch_assoc()['c'];

$today = date('Y-m-d');
$todayAtt = $conn->query("SELECT
        SUM(status IN ('Present','Late')) present_c, COUNT(*) total_c
        FROM student_attendance WHERE att_date = '$today'")->fetch_assoc();
$todayPct = ($todayAtt['total_c'] ?? 0) > 0 ? round(($todayAtt['present_c'] / $todayAtt['total_c']) * 100, 1) : null;

$month = date('n'); $year = date('Y');
$feeRow = $conn->query("SELECT SUM(paid_amount) paid, SUM(amount-discount) due FROM fee_vouchers WHERE month=$month AND year=$year")->fetch_assoc();
$feeCollected = $feeRow['paid'] ?? 0;
$feeDue = ($feeRow['due'] ?? 0) - $feeCollected;

$upcoming = $conn->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5");

$classChart = $conn->query("SELECT c.name, c.section, COUNT(s.id) cnt FROM classes c LEFT JOIN students s ON s.class_id=c.id GROUP BY c.id ORDER BY c.name");
$classLabels = []; $classData = [];
while ($r = $classChart->fetch_assoc()) { $classLabels[] = $r['name'].'-'.$r['section']; $classData[] = (int)$r['cnt']; }

$attTrend = $conn->query("SELECT att_date, SUM(status IN ('Present','Late')) present_c, COUNT(*) total_c
    FROM student_attendance WHERE att_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY att_date ORDER BY att_date");
$trendLabels = []; $trendData = [];
while ($r = $attTrend->fetch_assoc()) {
    $trendLabels[] = date('D', strtotime($r['att_date']));
    $trendData[] = $r['total_c'] > 0 ? round(($r['present_c'] / $r['total_c']) * 100, 1) : 0;
}

include '../includes/header.php';
?>
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#1F8A8A;">&#127891;</div>
        <div><div class="stat-num"><?= $totalStudents ?></div><div class="stat-label">Total Students</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#2F5D8C;">&#128100;</div>
        <div><div class="stat-num"><?= $totalStaff ?></div><div class="stat-label">Total Staff</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#E4572E;">&#9989;</div>
        <div><div class="stat-num"><?= $todayPct !== null ? $todayPct.'%' : '&ndash;' ?></div><div class="stat-label">Today's Attendance</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#F2A93B;">&#128176;</div>
        <div><div class="stat-num"><?= fmt_money($feeCollected) ?></div><div class="stat-label">Fees Collected (<?= month_name($month) ?>)</div></div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Attendance Trend (Last 7 Days)</div>
        <canvas id="attChart" height="160"></canvas>
    </div>
    <div class="card">
        <div class="card-title">Students per Class</div>
        <canvas id="classChart" height="160"></canvas>
    </div>
</div>

<div class="grid-2" style="margin-top:20px;">
    <div class="card">
        <div class="card-title">Fee Overview &ndash; <?= month_name($month) ?> <?= $year ?></div>
        <table class="tbl">
            <tr><td>Collected</td><td class="text-right"><b style="color:#2C6E1F;"><?= fmt_money($feeCollected) ?></b></td></tr>
            <tr><td>Pending / Due</td><td class="text-right"><b style="color:#C0392B;"><?= fmt_money(max(0,$feeDue)) ?></b></td></tr>
        </table>
        <a href="fees.php" class="btn btn-outline btn-sm" style="margin-top:10px;">Manage Fees &rarr;</a>
    </div>
    <div class="card">
        <div class="card-title">Upcoming Events</div>
        <?php if ($upcoming->num_rows === 0): ?>
            <p class="muted">No upcoming events.</p>
        <?php else: while ($ev = $upcoming->fetch_assoc()): ?>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
                <span><?= e($ev['title']) ?> <span class="badge badge-blue"><?= e($ev['audience']) ?></span></span>
                <span class="muted"><?= fmt_date($ev['event_date']) ?></span>
            </div>
        <?php endwhile; endif; ?>
        <a href="events.php" class="btn btn-outline btn-sm" style="margin-top:10px;">Manage Events &rarr;</a>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('attChart'), {
    type: 'line',
    data: { labels: <?= json_encode($trendLabels) ?>, datasets: [{ label: 'Attendance %', data: <?= json_encode($trendData) ?>, borderColor: '#1F8A8A', backgroundColor: 'rgba(31,138,138,0.12)', fill: true, tension: 0.35 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 100 } } }
});
new Chart(document.getElementById('classChart'), {
    type: 'bar',
    data: { labels: <?= json_encode($classLabels) ?>, datasets: [{ label: 'Students', data: <?= json_encode($classData) ?>, backgroundColor: '#2F5D8C', borderRadius: 6 }] },
    options: { plugins: { legend: { display: false } } }
});
</script>
<?php include '../includes/footer.php'; ?>
