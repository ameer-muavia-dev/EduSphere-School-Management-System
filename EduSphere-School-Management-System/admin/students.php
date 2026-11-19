<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin']);

$pageTitle = 'Student Management';
$activeMenu = 'students.php';
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

$classes = $conn->query("SELECT * FROM classes ORDER BY name, section");

// ---- DELETE ----
if ($action === 'delete' && $id) {
    $stu = $conn->query("SELECT user_id FROM students WHERE id=$id")->fetch_assoc();
    if ($stu) {
        $conn->query("DELETE FROM users WHERE id=" . (int)$stu['user_id']); // cascades to students
        set_flash('success', 'Student deleted successfully.');
    }
    redirect('admin/students.php');
}

// ---- ADD / EDIT SUBMIT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $class_id = (int)$_POST['class_id'];
    $roll_no = trim($_POST['roll_no']);
    $dob = $_POST['dob'] ?: null;
    $gender = $_POST['gender'];
    $guardian_name = trim($_POST['guardian_name']);
    $guardian_phone = trim($_POST['guardian_phone']);
    $address = trim($_POST['address']);
    $errors = [];

    if ($name === '' || $email === '' || $roll_no === '') $errors[] = 'Name, email and roll number are required.';

    if (!$errors && $action === 'add') {
        $chk = $conn->prepare('SELECT id FROM users WHERE email=?');
        $chk->bind_param('s', $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $errors[] = 'This email is already registered.';
    }

    if (!$errors) {
        if ($action === 'add') {
            $hash = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (name,email,password,role_id,phone) VALUES (?,?,?,3,?)');
            $stmt->bind_param('ssss', $name, $email, $hash, $phone);
            $stmt->execute();
            $user_id = $conn->insert_id;
            $stmt2 = $conn->prepare('INSERT INTO students (user_id,class_id,roll_no,dob,gender,guardian_name,guardian_phone,address) VALUES (?,?,?,?,?,?,?,?)');
            $stmt2->bind_param('iissssss', $user_id, $class_id, $roll_no, $dob, $gender, $guardian_name, $guardian_phone, $address);
            $stmt2->execute();
            set_flash('success', 'Student admitted successfully. Default password: password123');
        } else {
            $edit_id = (int)($_POST['id'] ?? 0);
            $row = $conn->query("SELECT user_id FROM students WHERE id=$edit_id")->fetch_assoc();
            if ($row) {
                $stmt = $conn->prepare('UPDATE users SET name=?, phone=? WHERE id=?');
                $stmt->bind_param('ssi', $name, $phone, $row['user_id']);
                $stmt->execute();
                $stmt2 = $conn->prepare('UPDATE students SET class_id=?, roll_no=?, dob=?, gender=?, guardian_name=?, guardian_phone=?, address=? WHERE id=?');
                $stmt2->bind_param('issssssi', $class_id, $roll_no, $dob, $gender, $guardian_name, $guardian_phone, $address, $edit_id);
                $stmt2->execute();
                set_flash('success', 'Student updated successfully.');
            }
        }
        redirect('admin/students.php');
    }
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $conn->prepare('SELECT s.*, u.name, u.email, u.phone FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    if (!$editData) redirect('admin/students.php');
}

$search = trim($_GET['q'] ?? '');
$classFilter = (int)($_GET['class_id'] ?? 0);
$sql = "SELECT s.*, u.name, u.email, u.phone, c.name AS cname, c.section
        FROM students s JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id WHERE 1=1";
if ($search !== '') {
    $searchEsc = $conn->real_escape_string($search);
    $sql .= " AND (u.name LIKE '%$searchEsc%' OR s.roll_no LIKE '%$searchEsc%')";
}
if ($classFilter) $sql .= " AND s.class_id=$classFilter";
$sql .= " ORDER BY c.name, s.roll_no";
$students = $conn->query($sql);

include '../includes/header.php';
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="page-head">
        <h2><?= $action === 'add' ? 'Admit New Student' : 'Edit Student' ?></h2>
        <a href="students.php" class="btn btn-outline btn-sm">&larr; Back to List</a>
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
                <div class="form-group"><label>Roll Number</label><input class="form-control" name="roll_no" required value="<?= e($editData['roll_no'] ?? '') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Class</label>
                    <select class="form-control" name="class_id" required>
                        <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= ($editData['class_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name'].' - '.$c['section']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Date of Birth</label><input type="date" class="form-control" name="dob" value="<?= e($editData['dob'] ?? '') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Gender</label>
                    <select class="form-control" name="gender">
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= ($editData['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Guardian Name</label><input class="form-control" name="guardian_name" value="<?= e($editData['guardian_name'] ?? '') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Guardian Phone</label><input class="form-control" name="guardian_phone" value="<?= e($editData['guardian_phone'] ?? '') ?>"></div>
                <div class="form-group"><label>Address</label><input class="form-control" name="address" value="<?= e($editData['address'] ?? '') ?>"></div>
            </div>
            <button class="btn btn-primary" type="submit"><?= $action === 'add' ? 'Admit Student' : 'Save Changes' ?></button>
        </form>
    </div>

<?php else: ?>
    <div class="page-head">
        <h2>Students (<?= $students->num_rows ?>)</h2>
        <a href="?action=add" class="btn btn-primary btn-sm">+ Admit New Student</a>
    </div>
    <div class="card">
        <form method="get" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
            <input class="form-control" style="max-width:260px;" name="q" placeholder="Search by name or roll no." value="<?= e($search) ?>">
            <select class="form-control" style="max-width:200px;" name="class_id" onchange="this.form.submit()">
                <option value="0">All Classes</option>
                <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>" <?= $classFilter == $c['id'] ? 'selected' : '' ?>><?= e($c['name'].' - '.$c['section']) ?></option>
                <?php endwhile; ?>
            </select>
            <button class="btn btn-outline" type="submit">Filter</button>
        </form>
        <div class="table-wrap">
        <table class="tbl">
            <tr><th>Roll No.</th><th>Name</th><th>Class</th><th>Gender</th><th>Guardian</th><th>Phone</th><th>Actions</th></tr>
            <?php if ($students->num_rows === 0): ?>
                <tr><td colspan="7" class="text-center muted">No students found.</td></tr>
            <?php else: while ($s = $students->fetch_assoc()): ?>
                <tr>
                    <td><?= e($s['roll_no']) ?></td>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['cname'].'-'.$s['section']) ?></td>
                    <td><?= e($s['gender']) ?></td>
                    <td><?= e($s['guardian_name']) ?></td>
                    <td><?= e($s['guardian_phone']) ?></td>
                    <td>
                        <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                        <a href="?action=delete&id=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this student permanently?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
        </table>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
