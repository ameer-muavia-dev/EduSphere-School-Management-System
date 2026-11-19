<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin', 'accountant', 'teacher']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT p.*, u.name, st.designation, st.department
    FROM payroll p JOIN staff st ON p.staff_id=st.id JOIN users u ON st.user_id=u.id WHERE p.id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if (!$p) { stop_page('Payslip Not Found', 'This payslip does not exist.'); }

if (current_role() === 'teacher') {
    $own = $conn->prepare('SELECT id FROM staff WHERE id=? AND user_id=?');
    $own->bind_param('ii', $p['staff_id'], $_SESSION['user_id']);
    $own->execute();
    if ($own->get_result()->num_rows === 0) stop_page('Access Denied', 'You are not authorized to view this payslip.', 403);
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Payslip</title>
<link rel="stylesheet" href="<?= root() ?>assets/css/style.css">
<style>
body{background:#fff;padding:30px;} .box{max-width:620px;margin:auto;border:1px solid var(--border);border-radius:12px;padding:30px;}
.hd{display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid var(--navy);padding-bottom:14px;margin-bottom:20px;}
.hd h2{color:var(--navy);margin:0;}
.row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed var(--border);font-size:14px;}
.total{font-size:18px;font-weight:800;color:var(--navy);}
</style></head>
<body>
<div class="box">
    <div class="hd">
        <div><h2>EduSphere</h2><div class="muted" style="font-size:12px;">University of Layyah &middot; Salary Payslip</div></div>
        <div class="text-right"><b><?= month_name($p['month']).' '.$p['year'] ?></b><br><span class="badge <?= $p['status']==='Paid'?'badge-green':'badge-amber' ?>"><?= e($p['status']) ?></span></div>
    </div>
    <div class="row"><span>Staff Name</span><b><?= e($p['name']) ?></b></div>
    <div class="row"><span>Designation</span><b><?= e($p['designation']) ?></b></div>
    <div class="row"><span>Department</span><b><?= e($p['department']) ?></b></div>
    <div class="row"><span>Basic Salary</span><b><?= fmt_money($p['basic']) ?></b></div>
    <div class="row"><span>Allowances</span><b style="color:#2C6E1F;">+ <?= fmt_money($p['allowances']) ?></b></div>
    <div class="row"><span>Deductions (Attendance)</span><b style="color:#C0392B;">&minus; <?= fmt_money($p['deductions']) ?></b></div>
    <div class="row"><span>Advance</span><b style="color:#C0392B;">&minus; <?= fmt_money($p['advance']) ?></b></div>
    <div class="row total"><span>Net Salary</span><span><?= fmt_money($p['net_salary']) ?></span></div>
    <p class="muted no-print" style="margin-top:20px;font-size:12px;">This is a computer-generated payslip from EduSphere.</p>
    <button class="btn btn-primary no-print" onclick="window.print()" style="margin-top:10px;">Print</button>
</div>
</body></html>
