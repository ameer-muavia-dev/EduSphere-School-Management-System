<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin']);

$pageTitle = 'Classes & Subjects';
$activeMenu = 'classes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = $_POST['form'];
    if ($form === 'class') {
        $name = trim($_POST['name']); $section = trim($_POST['section']);
        if ($name && $section) {
            $stmt = $conn->prepare('INSERT INTO classes (name, section) VALUES (?,?)');
            $stmt->bind_param('ss', $name, $section);
            $stmt->execute();
            set_flash('success', 'Class added.');
        }
    } elseif ($form === 'subject') {
        $name = trim($_POST['sname']); $code = trim($_POST['code']);
        if ($name) {
            $stmt = $conn->prepare('INSERT INTO subjects (name, code) VALUES (?,?)');
            $stmt->bind_param('ss', $name, $code);
            $stmt->execute();
            set_flash('success', 'Subject added.');
        }
    } elseif ($form === 'assign') {
        $class_id = (int)$_POST['class_id']; $subject_id = (int)$_POST['subject_id']; $teacher_id = (int)$_POST['teacher_id'] ?: null;
        $chk = $conn->prepare('SELECT id FROM class_subjects WHERE class_id=? AND subject_id=?');
        $chk->bind_param('ii', $class_id, $subject_id);
        $chk->execute();
        $existing = $chk->get_result()->fetch_assoc();
        if ($existing) {
            $stmt = $conn->prepare('UPDATE class_subjects SET teacher_id=? WHERE id=?');
            $stmt->bind_param('ii', $teacher_id, $existing['id']);
        } else {
            $stmt = $conn->prepare('INSERT INTO class_subjects (class_id,subject_id,teacher_id) VALUES (?,?,?)');
            $stmt->bind_param('iii', $class_id, $subject_id, $teacher_id);
        }
        $stmt->execute();
        set_flash('success', 'Subject assigned to class.');
    }
    redirect('admin/classes.php');
}

if (isset($_GET['del_class'])) { $conn->query('DELETE FROM classes WHERE id=' . (int)$_GET['del_class']); set_flash('success', 'Class removed.'); redirect('admin/classes.php'); }
if (isset($_GET['del_subject'])) { $conn->query('DELETE FROM subjects WHERE id=' . (int)$_GET['del_subject']); set_flash('success', 'Subject removed.'); redirect('admin/classes.php'); }
if (isset($_GET['del_assign'])) { $conn->query('DELETE FROM class_subjects WHERE id=' . (int)$_GET['del_assign']); set_flash('success', 'Assignment removed.'); redirect('admin/classes.php'); }

$classes = $conn->query('SELECT * FROM classes ORDER BY name, section');
$subjects = $conn->query('SELECT * FROM subjects ORDER BY name');
$teachers = $conn->query("SELECT u.id, u.name FROM users u WHERE u.role_id=2 ORDER BY u.name");
$assignments = $conn->query("SELECT cs.id, c.name cname, c.section, s.name sname, t.name tname
    FROM class_subjects cs JOIN classes c ON cs.class_id=c.id JOIN subjects s ON cs.subject_id=s.id
    LEFT JOIN users t ON cs.teacher_id=t.id ORDER BY c.name, s.name");

include '../includes/header.php';
?>
<div class="page-head"><h2>Classes & Subjects</h2></div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Classes</div>
        <form method="post" style="display:flex;gap:10px;margin-bottom:14px;">
            <?= csrf_field() ?><input type="hidden" name="form" value="class">
            <input class="form-control" name="name" placeholder="Class e.g. 8" required>
            <input class="form-control" name="section" placeholder="Section e.g. A" required>
            <button class="btn btn-primary btn-sm">Add</button>
        </form>
        <table class="tbl">
            <tr><th>Class</th><th></th></tr>
            <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                <tr><td><?= e($c['name'].' - '.$c['section']) ?></td>
                    <td class="text-right"><a href="?del_class=<?= $c['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete class?')">Delete</a></td></tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div class="card">
        <div class="card-title">Subjects</div>
        <form method="post" style="display:flex;gap:10px;margin-bottom:14px;">
            <?= csrf_field() ?><input type="hidden" name="form" value="subject">
            <input class="form-control" name="sname" placeholder="Subject name" required>
            <input class="form-control" name="code" placeholder="Code e.g. MATH">
            <button class="btn btn-primary btn-sm">Add</button>
        </form>
        <table class="tbl">
            <tr><th>Subject</th><th>Code</th><th></th></tr>
            <?php $subjects->data_seek(0); while ($s = $subjects->fetch_assoc()): ?>
                <tr><td><?= e($s['name']) ?></td><td><?= e($s['code']) ?></td>
                    <td class="text-right"><a href="?del_subject=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete subject?')">Delete</a></td></tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <div class="card-title">Assign Subject &amp; Teacher to a Class</div>
    <form method="post" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
        <?= csrf_field() ?><input type="hidden" name="form" value="assign">
        <select class="form-control" name="class_id" required style="max-width:180px;">
            <option value="">Class</option>
            <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['name'].'-'.$c['section']) ?></option>
            <?php endwhile; ?>
        </select>
        <select class="form-control" name="subject_id" required style="max-width:200px;">
            <option value="">Subject</option>
            <?php $subjects->data_seek(0); while ($s = $subjects->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
            <?php endwhile; ?>
        </select>
        <select class="form-control" name="teacher_id" style="max-width:220px;">
            <option value="">Teacher (optional)</option>
            <?php $teachers->data_seek(0); while ($t = $teachers->fetch_assoc()): ?>
                <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
            <?php endwhile; ?>
        </select>
        <button class="btn btn-primary btn-sm">Assign</button>
    </form>
    <table class="tbl">
        <tr><th>Class</th><th>Subject</th><th>Teacher</th><th></th></tr>
        <?php if ($assignments->num_rows === 0): ?><tr><td colspan="4" class="muted text-center">No assignments yet.</td></tr>
        <?php else: while ($a = $assignments->fetch_assoc()): ?>
            <tr><td><?= e($a['cname'].'-'.$a['section']) ?></td><td><?= e($a['sname']) ?></td><td><?= e($a['tname'] ?? 'Not assigned') ?></td>
                <td class="text-right"><a href="?del_assign=<?= $a['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove assignment?')">Remove</a></td></tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
