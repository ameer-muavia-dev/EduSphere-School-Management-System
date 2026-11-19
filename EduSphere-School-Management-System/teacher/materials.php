<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['teacher']);

$pageTitle = 'Study Material';
$activeMenu = 'materials.php';
$uid = $_SESSION['user_id'];

$myAssign = $conn->query("SELECT cs.class_id, c.name cname, c.section, cs.subject_id, s.name sname
    FROM class_subjects cs JOIN classes c ON cs.class_id=c.id JOIN subjects s ON cs.subject_id=s.id WHERE cs.teacher_id=$uid");
$assignArr = []; while ($r = $myAssign->fetch_assoc()) $assignArr[] = $r;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $class_id = (int)$_POST['class_id']; $subject_id = (int)$_POST['subject_id'];
    $title = trim($_POST['title']); $description = trim($_POST['description']);
    $filePath = null;

    if (!empty($_FILES['file']['name'])) {
        $allowed = ['pdf','doc','docx','ppt','pptx','txt','jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['file']['size'] < 10 * 1024 * 1024) {
            $safeName = uniqid('mat_') . '.' . $ext;
            $dest = '../uploads/materials/' . $safeName;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                $filePath = 'uploads/materials/' . $safeName;
            }
        } else {
            set_flash('error', 'Invalid file type or file too large (max 10MB).');
            redirect('teacher/materials.php');
        }
    }

    $stmt = $conn->prepare('INSERT INTO study_materials (class_id, subject_id, teacher_id, title, description, file_path) VALUES (?,?,?,?,?,?)');
    $stmt->bind_param('iiisss', $class_id, $subject_id, $uid, $title, $description, $filePath);
    $stmt->execute();
    set_flash('success', 'Study material uploaded.');
    redirect('teacher/materials.php');
}
if (isset($_GET['del'])) {
    $conn->query("DELETE FROM study_materials WHERE id=" . (int)$_GET['del'] . " AND teacher_id=$uid");
    redirect('teacher/materials.php');
}

$materials = $conn->query("SELECT m.*, c.name cname, c.section, s.name sname FROM study_materials m
    JOIN classes c ON m.class_id=c.id JOIN subjects s ON m.subject_id=s.id WHERE m.teacher_id=$uid ORDER BY m.uploaded_at DESC");

include '../includes/header.php';
?>
<div class="page-head"><h2>Study Material</h2></div>

<div class="card" style="margin-bottom:20px;max-width:700px;">
    <div class="card-title">Upload New Material</div>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>Class & Subject</label>
                <select class="form-control" name="class_subject" onchange="
                    var v=this.value.split('|'); document.getElementById('cid').value=v[0]; document.getElementById('sid').value=v[1];">
                    <?php foreach ($assignArr as $a): ?>
                        <option value="<?= $a['class_id'] ?>|<?= $a['subject_id'] ?>"><?= e($a['cname'].'-'.$a['section'].' &middot; '.$a['sname']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" id="cid" name="class_id" value="<?= $assignArr[0]['class_id'] ?? '' ?>">
                <input type="hidden" id="sid" name="subject_id" value="<?= $assignArr[0]['subject_id'] ?? '' ?>">
            </div>
            <div class="form-group"><label>Title</label><input class="form-control" name="title" required></div>
        </div>
        <div class="form-group"><label>Description</label><textarea class="form-control" name="description"></textarea></div>
        <div class="form-group"><label>File (optional, PDF/DOC/Image, max 10MB)</label><input type="file" class="form-control" name="file"></div>
        <button class="btn btn-primary">Upload</button>
    </form>
</div>

<div class="card">
    <div class="card-title">My Uploaded Material</div>
    <table class="tbl">
        <tr><th>Title</th><th>Class</th><th>Subject</th><th>File</th><th>Uploaded</th><th></th></tr>
        <?php if ($materials->num_rows === 0): ?><tr><td colspan="6" class="text-center muted">You haven't uploaded any material yet.</td></tr>
        <?php else: while ($m = $materials->fetch_assoc()): ?>
            <tr>
                <td><?= e($m['title']) ?></td><td><?= e($m['cname'].'-'.$m['section']) ?></td><td><?= e($m['sname']) ?></td>
                <td><?= $m['file_path'] ? '<a href="../'.e($m['file_path']).'" target="_blank">Download</a>' : '-' ?></td>
                <td><?= fmt_date($m['uploaded_at']) ?></td>
                <td><a href="?del=<?= $m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this material?')">Delete</a></td>
            </tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
