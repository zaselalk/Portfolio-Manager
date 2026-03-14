<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init_db.php';
require_once __DIR__ . '/../includes/auth.php';

start_session();
require_login();

$db       = get_db();
$settings = get_settings();
$success  = '';
$error    = '';

// ── Shared helper: upsert a list of keys into settings ────────────────────
function save_settings(PDO $db, array $keysAndValues): void
{
    $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (:key, :value)
                          ON CONFLICT(key) DO UPDATE SET value = excluded.value");
    foreach ($keysAndValues as $key => $value) {
        $stmt->execute([':key' => $key, ':value' => $value]);
    }
}

// ── Handle profile form ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'settings_profile') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $data = [
            'name'       => trim($_POST['name']       ?? ''),
            'title'      => trim($_POST['title']      ?? ''),
            'bio'        => trim($_POST['bio']         ?? ''),
            'hire_email' => trim($_POST['hire_email']  ?? ''),
            'available'  => isset($_POST['available']) ? '1' : '0',
        ];

        // Handle optional header image upload
        if (!empty($_FILES['header_image']['tmp_name'])) {
            $imgResult = handle_image_upload($_FILES['header_image'], 'header_');
            if ($imgResult['error'] !== '') {
                $error = $imgResult['error'];
            } else {
                $data['header_image'] = $imgResult['path'];
            }
        }

        if ($error === '') {
            save_settings($db, $data);
            $settings = get_settings();
            $success  = 'Profile saved successfully.';
        }
    }
    regenerate_csrf();
}

// ── Handle social & footer form ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'settings_social') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $error = 'Invalid request. Please try again.';
    } else {
        save_settings($db, [
            'social_fb'   => trim($_POST['social_fb']   ?? ''),
            'social_tw'   => trim($_POST['social_tw']   ?? ''),
            'social_yt'   => trim($_POST['social_yt']   ?? ''),
            'footer_text' => trim($_POST['footer_text'] ?? ''),
            'footer_sub'  => trim($_POST['footer_sub']  ?? ''),
        ]);
        $settings = get_settings();
        $success  = 'Social &amp; footer saved successfully.';
    }
    regenerate_csrf();
}

// ── Handle password change ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'password') {
    $token       = $_POST['csrf_token'] ?? '';
    $current     = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (!verify_csrf($token)) {
        $error = 'Invalid request. Please try again.';
    } elseif ($current === '' || $newPass === '' || $confirmPass === '') {
        $error = 'All password fields are required.';
    } elseif (strlen($newPass) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'New passwords do not match.';
    } else {
        $userId = $_SESSION[SESSION_KEY];
        $row    = $db->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
        $row->execute([$userId]);
        $row = $row->fetch();

        if ($row && password_verify($current, $row['password_hash'])) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")
               ->execute([$hash, $userId]);
            $success = 'Password changed successfully.';
        } else {
            $error = 'Current password is incorrect.';
        }
    }
    regenerate_csrf();
}

// ── Check if still using the default password ──────────────────────────────
$usingDefaultPassword = false;
$userId = $_SESSION[SESSION_KEY] ?? 0;
$adminRow = $db->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
$adminRow->execute([$userId]);
$adminRow = $adminRow->fetch();
if ($adminRow && password_verify('admin123', $adminRow['password_hash'])) {
    $usingDefaultPassword = true;
}

function handle_image_upload(array $file, string $prefix = ''): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload failed (code ' . $file['error'] . ').', 'path' => ''];
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['error' => 'File is too large (max 5 MB).', 'path' => ''];
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        return ['error' => 'Invalid image type. Allowed: JPEG, PNG, GIF, WebP.', 'path' => ''];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTENSIONS, true)) {
        return ['error' => 'Invalid file extension.', 'path' => ''];
    }
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        return ['error' => 'Could not create upload directory.', 'path' => ''];
    }
    $filename = $prefix . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => 'Failed to save uploaded file.', 'path' => ''];
    }
    return ['error' => '', 'path' => UPLOAD_URL . $filename];
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Dashboard – Portfolio Manager</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body { background: #f8f9fa; }
        .sidebar {
            width: 220px;
            min-height: 100vh;
            background: #2c2c2e;
            color: #fff;
            position: fixed;
            top: 0; left: 0;
        }
        .sidebar a { color: #ccc; text-decoration: none; display: block; padding: 10px 20px; }
        .sidebar a:hover, .sidebar a.active { background: #444; color: #fff; }
        .sidebar .brand { padding: 20px; font-weight: bold; font-size: 1.1rem; border-bottom: 1px solid #444; }
        .main-content { margin-left: 220px; padding: 30px; }
        .nav-tabs .nav-link.active { font-weight: bold; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand">📁 Portfolio Admin</div>
    <a href="dashboard.php" class="active">⚙️ Site Settings</a>
    <a href="projects.php">🖼️ Projects</a>
    <hr style="border-color:#444;margin:8px 0;">
    <a href="../index.php" target="_blank">🌐 View Site</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- Main content -->
<div class="main-content">
    <h2 class="mb-4">Site Settings</h2>

    <?php if ($success !== ''): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= h($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= h($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($usingDefaultPassword): ?>
    <div class="alert alert-warning">
        <strong>⚠️ Security notice:</strong> You are using the default password (<code>admin123</code>).
        Please change it immediately using the <strong>Change Password</strong> tab.
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-profile">Profile</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-social">Social &amp; Footer</button></li>
        <li class="nav-item"><button class="nav-link <?= $usingDefaultPassword ? 'text-warning fw-bold' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-password">Change Password<?= $usingDefaultPassword ? ' ⚠️' : '' ?></button></li>
    </ul>

    <!-- Profile Tab -->
    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-profile">
            <form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="settings_profile">

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= h($settings['name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Professional Title</label>
                    <input type="text" name="title" class="form-control" value="<?= h($settings['title'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Bio / About</label>
                    <textarea name="bio" class="form-control" rows="4" required><?= h($settings['bio'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hire Me Email</label>
                    <input type="email" name="hire_email" class="form-control" value="<?= h($settings['hire_email'] ?? '') ?>">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="available" id="available" class="form-check-input"
                        <?= ($settings['available'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="available">Show "Available for hire" banner</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Header Background Image</label>
                    <?php if (!empty($settings['header_image'])): ?>
                    <div class="mb-2">
                        <img src="../<?= h($settings['header_image']) ?>" alt="Current header" style="max-height:80px;border-radius:4px;">
                        <small class="text-muted ms-2">Current image</small>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="header_image" class="form-control" accept="image/*">
                    <div class="form-text">Leave blank to keep the current image. Max 5 MB.</div>
                </div>
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </form>
        </div>

        <!-- Social & Footer Tab -->
        <div class="tab-pane fade" id="tab-social">
            <form method="post" class="card p-4 shadow-sm">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="settings_social">

                <div class="mb-3">
                    <label class="form-label">Facebook URL</label>
                    <input type="url" name="social_fb" class="form-control" value="<?= h($settings['social_fb'] ?? '') ?>" placeholder="https://facebook.com/yourpage">
                </div>
                <div class="mb-3">
                    <label class="form-label">Twitter / X URL</label>
                    <input type="url" name="social_tw" class="form-control" value="<?= h($settings['social_tw'] ?? '') ?>" placeholder="https://twitter.com/yourhandle">
                </div>
                <div class="mb-3">
                    <label class="form-label">YouTube URL</label>
                    <input type="url" name="social_yt" class="form-control" value="<?= h($settings['social_yt'] ?? '') ?>" placeholder="https://youtube.com/@yourchannel">
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label">Footer Text</label>
                    <input type="text" name="footer_text" class="form-control" value="<?= h($settings['footer_text'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Footer Sub-text</label>
                    <input type="text" name="footer_sub" class="form-control" value="<?= h($settings['footer_sub'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary">Save Social &amp; Footer</button>
            </form>
        </div>

        <!-- Change Password Tab -->
        <div class="tab-pane fade" id="tab-password">
            <form method="post" class="card p-4 shadow-sm" style="max-width:480px;">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="password">
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password <small class="text-muted">(min 8 chars)</small></label>
                    <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-warning">Change Password</button>
            </form>
        </div>
    </div><!-- /.tab-content -->
</div><!-- /.main-content -->

<script src="../assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
