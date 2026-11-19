<?php
require_once 'config/db.php';
require_once 'config/functions.php';

if (is_logged_in()) {
    redirect(current_role() . '/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role_name'];
        redirect($user['role_name'] . '/dashboard.php');
    } else {
        $error = 'Invalid email or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | EduSphere</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-side">
            <h1>Edu<span style="color:#F2A93B">Sphere</span></h1>
            <p>A Web-Based Smart School Management System<br>University of Layyah &middot; BS Computer Science &middot; Web Technologies</p>
            <p style="margin-top:24px;">Attendance &middot; Fees &middot; Results &middot; LMS &middot; Timetable &mdash; all in one secure platform.</p>
        </div>
        <div class="login-form">
            <h2>Welcome Back</h2>
            <div class="sub">Sign in to your account to continue</div>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <form method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px;">Login</button>
            </form>
            <div class="demo-box">
                <b>Demo accounts</b> (password for all: <b>password123</b>)<br>
                Admin: admin@edusphere.edu<br>
                Teacher: imran.ali@edusphere.edu<br>
                Student: ali.hamza@edusphere.edu<br>
                Parent: tariq.mehmood@edusphere.edu<br>
                Accountant: waqas.sheikh@edusphere.edu
            </div>
        </div>
    </div>
</div>
</body>
</html>
