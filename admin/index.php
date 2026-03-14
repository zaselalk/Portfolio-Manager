<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init_db.php';
require_once __DIR__ . '/../includes/auth.php';

start_session();

// Already logged in → go to dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = $_POST['csrf_token'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!verify_csrf($token)) {
        $error = 'Invalid request. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $db   = get_db();
        $stmt = $db->prepare("SELECT id, password_hash FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password_hash'])) {
            login_user((int) $row['id']);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
    regenerate_csrf();
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Admin Login – Portfolio Manager</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body { background: #f8f9fa; }
        .login-card {
            max-width: 420px;
            margin: 80px auto;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="card-title mb-1 text-center">Portfolio Manager</h2>
            <p class="text-center text-muted mb-4">Admin Login</p>

            <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_input() ?>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?= h($_POST['username'] ?? '') ?>" required autofocus autocomplete="username">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>

            <div class="mt-3 text-center">
                <a href="../index.php" class="text-muted small">← Back to portfolio</a>
            </div>
        </div>
    </div>
</div>
<script src="../assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
