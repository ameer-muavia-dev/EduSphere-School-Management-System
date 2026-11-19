<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin', 'parent', 'teacher']);

$id = (int)($_GET['id'] ?? 0);
$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));

$stmt = $conn->prepare("SELECT s.*, u.name, c.name cname, c.section FROM students s JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id WHERE s.id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) { stop_page('Student Not Found', 'This student record does not exist.'); }

if (current_role() === 'parent') {
    if ($student['parent_user_id'] != $_SESSION['user_id']) stop_page('Access Denied', 'You are not authorized to view this student report.', 403);
}

$from = date('Y-m-01', strtotime("$year-$month-01"));
$to = date('Y-m-t', strtotime("$year-$month-01"));
$pct = attendance_percent($conn, $id, $from, $to);

$attSummary = $conn->query("SELECT status, COUNT(*) c FROM student_attendance WHERE student_id=$id AND att_date BETWEEN '$from' AND '$to' GROUP BY status");
$attCounts = ['Present'=>0,'Absent'=>0,'Late'=>0,'Leave'=>0];
while ($a = $attSummary->fetch_assoc()) $attCounts[$a['status']] = $a['c'];

$exams = $conn->query("SELECT e.title, e.term, SUM(m.marks_obtained) obt, SUM(m.total_marks) tot
    FROM exam_marks m JOIN exams e ON m.exam_id=e.id
    WHERE m.student_id=$id AND MONTH(e.exam_date)=$month AND YEAR(e.exam_date)=$year
    GROUP BY e.id ORDER BY e.exam_date");

$assignments = $conn->query("SELECT a.title, a.due_date, sub.submitted_at, sub.marks
    FROM assignments a JOIN students st ON st.id=$id
    LEFT JOIN assignment_submissions sub ON sub.assignment_id=a.id AND sub.student_id=$id
    WHERE a.class_id=st.class_id AND MONTH(a.due_date)=$month AND YEAR(a.due_date)=$year");

$fees = $conn->query("SELECT * FROM fee_vouchers WHERE student_id=$id AND month=$month AND year=$year")->fetch_assoc();

$contacts = $conn->query("SELECT pc.*, u.name tname FROM parent_contact_log pc JOIN users u ON pc.staff_id=u.id
    WHERE pc.student_id=$id AND MONTH(pc.contact_date)=$month AND YEAR(pc.contact_date)=$year ORDER BY pc.contact_date DESC");

$pageTitle = 'Monthly Report';
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Monthly Report - <?= e($student['name']) ?></title>
<link rel="stylesheet" href="<?= root() ?>assets/css/style.css">
<style>
body{background:#fff;padding:30px;} .box{max-width:800px;margin:auto;}
.hd{display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid var(--navy);padding-bottom:14px;margin-bottom:20px;}
.hd h2{color:var(--navy);margin:0;}
.section-title{font-weight:700;color:var(--navy);margin:22px 0 10px;font-size:15px;}
</style></head>
<body>
<div class="box">
    <div class="hd">
        <div><h2>EduSphere</h2><div class="muted" style="font-size:12px;">University of Layyah &middot; Monthly Overall Education Report</div></div>
        <div class="text-right"><b><?= month_name($month) ?> <?= $year ?></b></div>
    </div>

    <div class="stat-grid">
        <div class="stat-card"><div><div class="stat-num"><?= e($student['name']) ?></div><div class="stat-label">Student Name</div></div></div>
        <div class="stat-card"><div><div class="stat-num"><?= e($student['roll_no']) ?></div><div class="stat-label">Roll No. / Class <?= e($student['cname'].'-'.$student['section']) ?></div></div></div>
        <div class="stat-card"><div><div class="stat-num" style="color:<?= ($pct ?? 0) >= 75 ? '#2C6E1F' : '#C0392B' ?>;"><?= $pct !== null ? $pct.'%' : '-' ?></div><div class="stat-label">Monthly Attendance</div></div></div>
    </div>

    <div class="section-title">Attendance Summary</div>
    <table class="tbl">
        <tr><th>Present</th><th>Absent</th><th>Late</th><th>Leave</th></tr>
        <tr><td><?= $attCounts['Present'] ?></td><td><?= $attCounts['Absent'] ?></td><td><?= $attCounts['Late'] ?></td><td><?= $attCounts['Leave'] ?></td></tr>
    </table>

    <div class="section-title">Test / Exam Performance</div>
    <table class="tbl">
        <tr><th>Exam</th><th>Term</th><th>Obtained / Total</th><th>Percentage</th></tr>
        <?php if ($exams->num_rows === 0): ?><tr><td colspan="4" class="text-center muted">No exams recorded this month.</td></tr>
        <?php else: while ($ex = $exams->fetch_assoc()):
            $ep = $ex['tot'] > 0 ? round(($ex['obt']/$ex['tot'])*100,1) : 0; ?>
            <tr><td><?= e($ex['title']) ?></td><td><?= e($ex['term']) ?></td><td><?= $ex['obt'].' / '.$ex['tot'] ?></td><td><?= $ep ?>%</td></tr>
        <?php endwhile; endif; ?>
    </table>

    <div class="section-title">Assignment Record</div>
    <table class="tbl">
        <tr><th>Assignment</th><th>Due Date</th><th>Submitted</th><th>Marks</th></tr>
        <?php if ($assignments->num_rows === 0): ?><tr><td colspan="4" class="text-center muted">No assignments due this month.</td></tr>
        <?php else: while ($a = $assignments->fetch_assoc()): ?>
            <tr><td><?= e($a['title']) ?></td><td><?= fmt_date($a['due_date']) ?></td>
                <td><?= $a['submitted_at'] ? '<span class="badge badge-green">Submitted</span>' : '<span class="badge badge-red">Missing</span>' ?></td>
                <td><?= $a['marks'] !== null ? $a['marks'] : '-' ?></td></tr>
        <?php endwhile; endif; ?>
    </table>

    <div class="section-title">Fee Status</div>
    <table class="tbl">
        <tr><th>Amount</th><th>Paid</th><th>Status</th></tr>
        <?php if (!$fees): ?><tr><td colspan="3" class="text-center muted">No voucher generated this month.</td></tr>
        <?php else: ?>
            <tr><td><?= fmt_money($fees['amount']) ?></td><td><?= fmt_money($fees['paid_amount']) ?></td>
                <td><span class="badge <?= ['Paid'=>'badge-green','Partial'=>'badge-amber','Unpaid'=>'badge-red'][$fees['status']] ?>"><?= e($fees['status']) ?></span></td></tr>
        <?php endif; ?>
    </table>

    <div class="section-title">Teacher Remarks / Parent Contact Log</div>
    <table class="tbl">
        <tr><th>Date</th><th>Teacher</th><th>Mode</th><th>Notes</th></tr>
        <?php if ($contacts->num_rows === 0): ?><tr><td colspan="4" class="text-center muted">No remarks logged this month.</td></tr>
        <?php else: while ($c = $contacts->fetch_assoc()): ?>
            <tr><td><?= fmt_date($c['contact_date']) ?></td><td><?= e($c['tname']) ?></td><td><?= e($c['mode']) ?></td><td><?= e($c['notes']) ?></td></tr>
        <?php endwhile; endif; ?>
    </table>

    <p class="muted no-print" style="margin-top:20px;font-size:12px;">This is a computer-generated report from EduSphere.</p>
    <button class="btn btn-primary no-print" onclick="window.print()" style="margin-top:10px;">Print / Save as PDF</button>
</div>
</body></html>
