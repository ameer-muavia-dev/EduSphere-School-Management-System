<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['parent']);

$pageTitle = 'Message Teacher';
$activeMenu = 'messages.php';
$uid = $_SESSION['user_id'];

$childrenArr = get_parent_children($conn);
$classIds = array_column($childrenArr, 'class_id');
$teachers = [];
if ($classIds) {
    $ids = implode(',', $classIds);
    $t = $conn->query("SELECT DISTINCT u.id, u.name FROM class_subjects cs JOIN users u ON cs.teacher_id=u.id WHERE cs.class_id IN ($ids) ORDER BY u.name");
    while ($r = $t->fetch_assoc()) $teachers[] = $r;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $receiver_id = (int)$_POST['receiver_id']; $message = trim($_POST['message']);
    if ($message !== '') {
        $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)');
        $stmt->bind_param('iis', $uid, $receiver_id, $message);
        $stmt->execute();
        set_flash('success', 'Message sent.');
    }
    redirect('parent/messages.php');
}

$myMessages = $conn->query("SELECT m.*, u.name FROM messages m JOIN users u ON (m.sender_id=u.id OR m.receiver_id=u.id)
    WHERE (m.sender_id=$uid OR m.receiver_id=$uid) AND u.id != $uid ORDER BY m.sent_at DESC LIMIT 30");

include '../includes/header.php';
?>
<div class="page-head"><h2>Message a Teacher</h2></div>

<div class="card" style="margin-bottom:20px;max-width:600px;">
    <div class="card-title">Send a Message</div>
    <?php if (empty($teachers)): ?><p class="muted">No teachers found for your child's class yet.</p>
    <?php else: ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label>Teacher</label>
            <select class="form-control" name="receiver_id" required>
                <?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Message</label><textarea class="form-control" name="message" required></textarea></div>
        <button class="btn btn-primary">Send</button>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-title">Message History</div>
    <table class="tbl">
        <tr><th>With</th><th>Message</th><th>Date</th></tr>
        <?php if ($myMessages->num_rows === 0): ?><tr><td colspan="3" class="text-center muted">No messages yet.</td></tr>
        <?php else: while ($m = $myMessages->fetch_assoc()): ?>
            <tr><td><?= e($m['name']) ?></td><td><?= e($m['message']) ?></td><td><?= fmt_date($m['sent_at']) ?></td></tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
