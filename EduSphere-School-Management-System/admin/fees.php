<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin', 'accountant']);

$pageTitle = 'Fee Management';
$activeMenu = 'fees.php';
$classes = $conn->query('SELECT * FROM classes ORDER BY name, section');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = $_POST['form'];

    if ($form === 'structure') {
        $class_id = (int)$_POST['class_id']; $title = trim($_POST['title']); $amount = (float)$_POST['amount'];
        $stmt = $conn->prepare('INSERT INTO fee_structures (class_id, title, amount) VALUES (?,?,?)');
        $stmt->bind_param('isd', $class_id, $title, $amount);
        $stmt->execute();
        set_flash('success', 'Fee structure added.');
    }

    if ($form === 'generate') {
        $class_id = (int)$_POST['gen_class_id']; $month = (int)$_POST['gen_month']; $year = (int)$_POST['gen_year'];
        $struct = $conn->query("SELECT SUM(amount) total FROM fee_structures WHERE class_id=$class_id")->fetch_assoc();
        $amount = $struct['total'] ?? 0;
        if ($amount > 0) {
            $students = $conn->query("SELECT id FROM students WHERE class_id=$class_id");
            $count = 0;
            while ($s = $students->fetch_assoc()) {
                $chk = $conn->query("SELECT id FROM fee_vouchers WHERE student_id={$s['id']} AND month=$month AND year=$year");
                if ($chk->num_rows === 0) {
                    $due = date('Y-m-d', strtotime("$year-$month-10"));
                    $stmt = $conn->prepare('INSERT INTO fee_vouchers (student_id, month, year, amount, due_date, status) VALUES (?,?,?,?,?,"Unpaid")');
                    $stmt->bind_param('iiids', $s['id'], $month, $year, $amount, $due);
                    $stmt->execute();
                    $count++;
                }
            }
            set_flash('success', "Generated $count voucher(s) for the selected class and month.");
        } else {
            set_flash('error', 'No fee structure found for this class. Add one first.');
        }
    }

    if ($form === 'payment') {
        $voucher_id = (int)$_POST['voucher_id']; $amount_paid = (float)$_POST['amount_paid']; $method = $_POST['method'];
        $v = $conn->query("SELECT * FROM fee_vouchers WHERE id=$voucher_id")->fetch_assoc();
        if ($v && $amount_paid > 0) {
            $stmt = $conn->prepare('INSERT INTO fee_payments (voucher_id, amount_paid, paid_date, method, received_by) VALUES (?,?,CURDATE(),?,?)');
            $uid = $_SESSION['user_id'];
            $stmt->bind_param('idsi', $voucher_id, $amount_paid, $method, $uid);
            $stmt->execute();
            $newPaid = $v['paid_amount'] + $amount_paid;
            $payable = $v['amount'] - $v['discount'];
            $status = $newPaid >= $payable ? 'Paid' : ($newPaid > 0 ? 'Partial' : 'Unpaid');
            $stmt2 = $conn->prepare('UPDATE fee_vouchers SET paid_amount=?, status=? WHERE id=?');
            $stmt2->bind_param('dsi', $newPaid, $status, $voucher_id);
            $stmt2->execute();
            set_flash('success', 'Payment recorded successfully.');
        }
    }
    redirect(current_role() . '/fees.php');
}

$structures = $conn->query("SELECT fs.*, c.name cname, c.section FROM fee_structures fs JOIN classes c ON fs.class_id=c.id ORDER BY c.name");

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT v.*, s.roll_no, u.name, c.name cname, c.section
        FROM fee_vouchers v JOIN students s ON v.student_id=s.id JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id
        WHERE 1=1";
if ($statusFilter) $sql .= " AND v.status='" . $conn->real_escape_string($statusFilter) . "'";
$sql .= " ORDER BY v.year DESC, v.month DESC, c.name, s.roll_no";
$vouchers = $conn->query($sql);

include '../includes/header.php';
?>
<div class="page-head"><h2>Fee Management</h2></div>

<div class="grid-2" style="margin-bottom:20px;">
    <div class="card">
        <div class="card-title">Fee Structure per Class</div>
        <form method="post" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
            <?= csrf_field() ?><input type="hidden" name="form" value="structure">
            <select class="form-control" name="class_id" required style="max-width:140px;">
                <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['name'].'-'.$c['section']) ?></option>
                <?php endwhile; ?>
            </select>
            <input class="form-control" name="title" placeholder="e.g. Tuition Fee" required style="max-width:160px;">
            <input class="form-control" type="number" step="0.01" name="amount" placeholder="Amount" required style="max-width:120px;">
            <button class="btn btn-primary btn-sm">Add</button>
        </form>
        <table class="tbl">
            <tr><th>Class</th><th>Title</th><th>Amount</th></tr>
            <?php while ($s = $structures->fetch_assoc()): ?>
                <tr><td><?= e($s['cname'].'-'.$s['section']) ?></td><td><?= e($s['title']) ?></td><td><?= fmt_money($s['amount']) ?></td></tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div class="card">
        <div class="card-title">Generate Monthly Vouchers</div>
        <p class="muted" style="font-size:12.5px;margin-bottom:12px;">Creates a voucher for every student in the class using the class's total fee structure.</p>
        <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;">
            <?= csrf_field() ?><input type="hidden" name="form" value="generate">
            <select class="form-control" name="gen_class_id" required style="max-width:140px;">
                <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['name'].'-'.$c['section']) ?></option>
                <?php endwhile; ?>
            </select>
            <select class="form-control" name="gen_month" style="max-width:130px;">
                <?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==date('n')?'selected':'' ?>><?= month_name($m) ?></option><?php endfor; ?>
            </select>
            <input class="form-control" type="number" name="gen_year" value="<?= date('Y') ?>" style="max-width:100px;">
            <button class="btn btn-teal btn-sm">Generate</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="page-head" style="margin-bottom:14px;">
        <div class="card-title" style="margin:0;">All Fee Vouchers</div>
        <form method="get">
            <select class="form-control btn-sm" name="status" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="Paid" <?= $statusFilter==='Paid'?'selected':'' ?>>Paid</option>
                <option value="Partial" <?= $statusFilter==='Partial'?'selected':'' ?>>Partial</option>
                <option value="Unpaid" <?= $statusFilter==='Unpaid'?'selected':'' ?>>Unpaid</option>
            </select>
        </form>
    </div>
    <div class="table-wrap">
    <table class="tbl">
        <tr><th>Student</th><th>Class</th><th>Month</th><th>Amount</th><th>Discount</th><th>Paid</th><th>Status</th><th>Actions</th></tr>
        <?php if ($vouchers->num_rows === 0): ?><tr><td colspan="8" class="text-center muted">No vouchers found.</td></tr>
        <?php else: while ($v = $vouchers->fetch_assoc()):
            $badge = ['Paid'=>'badge-green','Partial'=>'badge-amber','Unpaid'=>'badge-red'][$v['status']]; ?>
            <tr>
                <td><?= e($v['name']) ?> (<?= e($v['roll_no']) ?>)</td>
                <td><?= e($v['cname'].'-'.$v['section']) ?></td>
                <td><?= month_name($v['month']).' '.$v['year'] ?></td>
                <td><?= fmt_money($v['amount']) ?></td>
                <td><?= fmt_money($v['discount']) ?></td>
                <td><?= fmt_money($v['paid_amount']) ?></td>
                <td><span class="badge <?= $badge ?>"><?= e($v['status']) ?></span></td>
                <td style="white-space:nowrap;">
                    <a href="receipt.php?id=<?= $v['id'] ?>" target="_blank" class="btn btn-outline btn-sm">Receipt</a>
                    <?php if ($v['status'] !== 'Paid'): ?>
                        <button class="btn btn-teal btn-sm" onclick="document.getElementById('pay<?= $v['id'] ?>').style.display='inline-flex'">Pay</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($v['status'] !== 'Paid'): ?>
            <tr id="pay<?= $v['id'] ?>" style="display:none;">
                <td colspan="8">
                    <form method="post" style="display:flex;gap:8px;align-items:center;">
                        <?= csrf_field() ?><input type="hidden" name="form" value="payment"><input type="hidden" name="voucher_id" value="<?= $v['id'] ?>">
                        <span class="muted">Record payment:</span>
                        <input class="form-control" type="number" step="0.01" name="amount_paid" placeholder="Amount" required style="max-width:130px;">
                        <select class="form-control" name="method" style="max-width:120px;">
                            <option>Cash</option><option>Bank</option><option>Online</option>
                        </select>
                        <button class="btn btn-primary btn-sm">Save Payment</button>
                    </form>
                </td>
            </tr>
            <?php endif; endwhile; endif; ?>
    </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
