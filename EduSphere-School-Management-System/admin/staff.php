<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin']);

$pageTitle = 'Staff Management';
$activeMenu = 'staff.php';
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id) {
    $st = $conn->query("SELECT user_id FROM staff WHERE id=$id")->fetch_assoc();
    if ($st) {
        $conn->query("DELETE FROM users WHERE id=" . (int)$st['user_id']);
        set_flash('success', 'Staff member removed successfully.');
    }
    redirect('admin/staff.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $designation = trim($_POST['designation']);
    $department = trim($_POST['department']);
    $role = $_POST['role_type']; // teacher or accountant
    $basic_salary = (float)$_POST['basic_salary'];
    $joining_date = $_POST['joining_date'] ?: date('Y-m-d');
    $errors = [];
    if ($name === '' || $email === '') $errors[] = 'Name and email are required.';

    if (!$errors && $action === 'add') {
        $chk = $conn->prepare('SELECT id FROM users WHERE email=?');
        $chk->bind_param('s', $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $errors[] = 'This email is already registered.';
    }

    if (!$errors) {
        $role_id = $role === 'accountant' ? 5 : 2;
        if ($action === 'add') {
            $hash = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (name,email,password,role_id,phone) VALUES (?,?,?,?,?)');
            $stmt->bind_param('sssis', $name, $email, $hash, $role_id, $phone);
            $stmt->execute();
            $user_id = $conn->insert_id;
            $stmt2 = $conn->prepare('INSERT INTO staff (user_id,designation,department,basic_salary,joining_date) VALUES (?,?,?,?,?)');
            $stmt2->bind_param('issds', $user_id, $designation, $department, $basic_salary, $joining_date);
            $stmt2->execute();
            set_flash('success', 'Staff member added successfully. Default password: password123');
        } else {
            $edit_id = (int)($_POST['id'] ?? 0);
            $row = $conn->query("SELECT user_id FROM staff WHERE id=$edit_id")->fetch_assoc();
            if ($row) {
                $stmt = $conn->prepare('UPDATE users SET name=?, phone=?, role_id=? WHERE id=?');
                $stmt->bind_param('ssii', $name, $phone, $role_id, $row['user_id']);
                $stmt->execute();
                $stmt2 = $conn->prepare('UPDATE staff SET designation=?, department=?, basic_salary=?, joining_date=? WHERE id=?');
                $stmt2->bind_param('ssdsi', $designation, $department, $basic_salary, $joining_date, $edit_id);
                $stmt2->execute();
                set_flash('success', 'Staff member updated successfully.');
            }
        }
        redirect('admin/staff.php');
    }
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $conn->prepare('SELECT st.*, u.name, u.email, u.phone, u.role_id FROM staff st JOIN users u ON st.user_id=u.id WHERE st.id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    if (!$editData) redirect('admin/staff.php');
}

$staffList = $conn->query("SELECT st.*, u.name, u.email, u.phone, u.role_id, r.name AS role_name
    FROM staff st JOIN users u ON st.user_id=u.id JOIN roles r ON u.role_id=r.id ORDER BY u.name");

include '../includes/header.php';
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="page-head">
        <h2><?= $action === 'add' ? 'Add Staff Member' : 'Edit Staff Member' ?></h2>
        <a href="staff.php" class="btn btn-outline btn-sm">&larr; Back to List</a>
    </div>
    <div class="card" style="max-width:760px;">
        <?php if (!empty($errors)): foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <?php if ($action === 'edit'): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group"><label>Full Name</label><input class="form-control" name="name" required value="<?= e($editData['name'] ?? '') ?>"></div>
                <div class="form-group"><label>Email</label><input type="email" class="form-control" name="email" required <?= $action === 'edit' ? 'readonly' : '' ?> value="<?= e($editData['email'] ?? '') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Phone</label><input class="form-control" name="phone" value="<?= e($editData['phone'] ?? '') ?>"></div>
                <div class="form-group"><label>Staff Type</label>
                    <select class="form-control" name="role_type">
                        <option value="teacher" <?= ($editData['role_id'] ?? 2) == 2 ? 'selected' : '' ?>>Teacher</option>
                        <option value="accountant" <?= ($editData['role_id'] ?? 2) == 5 ? 'selected' : '' ?>>Accountant</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Designation</label><input class="form-control" name="designation" placeholder="e.g. Subject Teacher" value="<?= e($editData['designation'] ?? '') ?>"></div>
                <div class="form-group"><label>Department</label><input class="form-control" name="department" placeholder="e.g. Mathematics" value="<?= e($editData['department'] ?? '') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Basic Salary (Rs.)</label><input type="number" step="0.01" class="form-control" name="basic_salary" value="<?= e($editData['basic_salary'] ?? '') ?>"></div>
                <div class="form-group"><label>Joining Date</label><input type="date" class="form-control" name="joining_date" value="<?= e($editData['joining_date'] ?? date('Y-m-d')) ?>"></div>
            </div>
            <button class="btn btn-primary" type="submit"><?= $action === 'add' ? 'Add Staff' : 'Save Changes' ?></button>
        </form>
    </div>

<?php else: ?>
    <div class="page-head">
        <h2>Staff Members (<?= $staffList->num_rows ?>)</h2>
        <a href="?action=add" class="btn btn-primary btn-sm">+ Add Staff Member</a>
    </div>
    <div class="card">
        <div class="table-wrap">
        <table class="tbl">
            <tr><th>Name</th><th>Type</th><th>Designation</th><th>Department</th><th>Basic Salary</th><th>Phone</th><th>Actions</th></tr>
            <?php if ($staffList->num_rows === 0): ?>
                <tr><td colspan="7" class="text-center muted">No staff members found.</td></tr>
            <?php else: while ($s = $staffList->fetch_assoc()): ?>
                <tr>
                    <td><?= e($s['name']) ?></td>
                    <td><span class="badge badge-blue"><?= ucfirst(e($s['role_name'])) ?></span></td>
                    <td><?= e($s['designation']) ?></td>
                    <td><?= e($s['department']) ?></td>
                    <td><?= fmt_money($s['basic_salary']) ?></td>
                    <td><?= e($s['phone']) ?></td>
                    <td>
                        <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                        <a href="?action=delete&id=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this staff member permanently?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
        </table>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
