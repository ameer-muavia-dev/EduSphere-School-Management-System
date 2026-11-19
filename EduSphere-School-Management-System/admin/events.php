<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin']);

$pageTitle = 'Events & Announcements';
$activeMenu = 'events.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim($_POST['title']); $description = trim($_POST['description']);
    $event_date = $_POST['event_date']; $audience = $_POST['audience'];
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare('INSERT INTO events (title, description, event_date, audience, created_by) VALUES (?,?,?,?,?)');
    $stmt->bind_param('ssssi', $title, $description, $event_date, $audience, $uid);
    $stmt->execute();
    set_flash('success', 'Event published.');
    redirect('admin/events.php');
}
if (isset($_GET['del'])) { $conn->query('DELETE FROM events WHERE id=' . (int)$_GET['del']); set_flash('success', 'Event removed.'); redirect('admin/events.php'); }

$events = $conn->query('SELECT * FROM events ORDER BY event_date DESC');

include '../includes/header.php';
?>
<div class="page-head"><h2>Events &amp; Announcements</h2></div>

<div class="card" style="margin-bottom:20px;max-width:700px;">
    <div class="card-title">Publish New Event / Announcement</div>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>Title</label><input class="form-control" name="title" required></div>
            <div class="form-group"><label>Date</label><input type="date" class="form-control" name="event_date" required></div>
        </div>
        <div class="form-group"><label>Description</label><textarea class="form-control" name="description"></textarea></div>
        <div class="form-group"><label>Audience</label>
            <select class="form-control" name="audience">
                <option>All</option><option>Students</option><option>Staff</option><option>Parents</option>
            </select>
        </div>
        <button class="btn btn-primary">Publish</button>
    </form>
</div>

<div class="card">
    <div class="card-title">All Events</div>
    <table class="tbl">
        <tr><th>Title</th><th>Description</th><th>Date</th><th>Audience</th><th></th></tr>
        <?php if ($events->num_rows === 0): ?><tr><td colspan="5" class="text-center muted">No events yet.</td></tr>
        <?php else: while ($ev = $events->fetch_assoc()): ?>
            <tr>
                <td><?= e($ev['title']) ?></td><td><?= e($ev['description']) ?></td><td><?= fmt_date($ev['event_date']) ?></td>
                <td><span class="badge badge-blue"><?= e($ev['audience']) ?></span></td>
                <td><a href="?del=<?= $ev['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this event?')">Remove</a></td>
            </tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
