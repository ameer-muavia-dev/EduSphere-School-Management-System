<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin', 'accountant']);

$pageTitle = 'Staff Salary';
$activeMenu = 'payroll.php';
$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = $_POST['form'];

    if ($form === 'generate') {
        $gm = (int)$_POST['gen_month']; $gy = (int)$_POST['gen_year'];
        $staffList = $conn->query("SELECT id, basic_salary FROM staff");
        $count = 0;
        while ($st = $staffList->fetch_assoc()) {
            $chk = $conn->query("SELECT id FROM payroll WHERE staff_id={$st['id']} AND month=$gm AND year=$gy");
            if ($chk->num_rows === 0) {
                // Deduct for absences that month (per-day rate x absent days)
                $absentRow = $conn->query("SELECT COUNT(*) c FROM staff_attendance WHERE staff_id={$st['id']} AND status='Absent' AND MONTH(att_date)=$gm AND YEAR(att_date)=$gy")->fetch_assoc();
                $absentDays = $absentRow['c'] ?? 0;
                $perDay = $st['basic_salary'] > 0 ? $st['basic_salary'] / 26 : 0;
                $deduction = round($perDay * $absentDays, 2);
                $net = $st['basic_salary'] - $deduction;
                $stmt = $conn->prepare('INSERT INTO payroll (staff_id,month,year,basic,allowances,deductions,advance,net_salary,status) VALUES (?,?,?,?,0,?,0,?,"Pending")');
                $stmt->bind_param('iiiddd', $st['id'], $gm, $gy, $st['basic_salary'], $deduction, $net);
                $stmt->execute();
                $count++;
            }
        }
        set_flash('success', "Payroll generated for $count staff member(s). Deductions applied from attendance.");
    }

    if ($form === 'pay') {
        $pid = (int)$_POST['payroll_id'];
        $conn->query("UPDATE payroll SET status='Paid', paid_date=CURDATE() WHERE id=$pid");
        set_flash('success', 'Salary marked as paid.');
    }

    if ($form === 'edit') {
        $pid = (int)$_POST['payroll_id']; $allow = (float)$_POST['allowances']; $ded = (float)$_POST['deductions']; $adv = (float)$_POST['advance'];
        $p = $conn->query("SELECT basic FROM payroll WHERE id=$pid")->fetch_assoc();
        $net = $p['basic'] + $allow - $ded - $adv;
        $stmt = $conn->prepare('UPDATE payroll SET allowances=?, deductions=?, advance=?, net_salary=? WHERE id=?');
        $stmt->bind_param('ddddi', $allow, $ded, $adv, $net, $pid);
        $stmt->execute();
        set_flash('success', 'Payroll entry updated.');
    }
    redirect(current_role() . '/payroll.php?month=' . $month . '&year=' . $year);
}

$rows = $conn->query("SELECT p.*, u.name, st.designation FROM payroll p
    JOIN staff st ON p.staff_id=st.id JOIN users u ON st.user_id=u.id
    WHERE p.month=$month AND p.year=$year ORDER BY u.name");

include '../includes/header.php';
?>
<div class="page-head"><h2>Staff Salary &amp; Payroll</h2></div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-title">Generate Payroll</div>
    <p class="muted" style="font-size:12.5px;margin-bottom:12px;">Basic salary minus a per-day deduction for each recorded absence that month.</p>
    <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;">
        <?= csrf_field() ?><input type="hidden" name="form" value="generate">
        <select class="form-control" name="gen_month" style="max-width:150px;">
            <?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==$month?'selected':'' ?>><?= month_name($m) ?></option><?php endfor; ?>
        </select>
        <input class="form-control" type="number" name="gen_year" value="<?= $year ?>" style="max-width:110px;">
        <button class="btn btn-teal btn-sm">Generate Payroll</button>
    </form>
</div>

<div class="card">
    <div class="page-head" style="margin-bottom:14px;">
        <div class="card-title" style="margin:0;">Payroll &ndash; <?= month_name($month) ?> <?= $year ?></div>
        <form method="get" style="display:flex;gap:8px;">
            <select class="form-control btn-sm" name="month" onchange="this.form.submit()">
                <?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==$month?'selected':'' ?>><?= month_name($m) ?></option><?php endfor; ?>
            </select>
            <input class="form-control btn-sm" type="number" name="year" value="<?= $year ?>" style="max-width:90px;" onchange="this.form.submit()">
        </form>
    </div>
    <div class="table-wrap">
    <table class="tbl">
        <tr><th>Name</th><th>Designation</th><th>Basic</th><th>Allowances</th><th>Deductions</th><th>Advance</th><th>Net Salary</th><th>Status</th><th>Actions</th></tr>
        <?php if ($rows->num_rows === 0): ?><tr><td colspan="9" class="text-center muted">No payroll generated for this month yet.</td></tr>
        <?php else: while ($r = $rows->fetch_assoc()): ?>
            <tr>
                <td><?= e($r['name']) ?></td><td><?= e($r['designation']) ?></td>
                <td><?= fmt_money($r['basic']) ?></td><td><?= fmt_money($r['allowances']) ?></td>
                <td><?= fmt_money($r['deductions']) ?></td><td><?= fmt_money($r['advance']) ?></td>
                <td><b><?= fmt_money($r['net_salary']) ?></b></td>
                <td><span class="badge <?= $r['status']==='Paid'?'badge-green':'badge-amber' ?>"><?= e($r['status']) ?></span></td>
                <td style="white-space:nowrap;">
                    <a href="payslip.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-outline btn-sm">Payslip</a>
                    <?php if ($r['status'] !== 'Paid'): ?>
                    <form method="post" style="display:inline;">
                        <?= csrf_field() ?><input type="hidden" name="form" value="pay"><input type="hidden" name="payroll_id" value="<?= $r['id'] ?>">
                        <button class="btn btn-teal btn-sm" onclick="return confirm('Mark this salary as paid?')">Mark Paid</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; endif; ?>
    </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
