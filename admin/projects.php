<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init_db.php';
require_once __DIR__ . '/../includes/auth.php';

start_session();
require_login();

$db      = get_db();
$success = '';
$error   = '';

// ── Helpers ────────────────────────────────────────────────────────────────
function handle_project_image(array $file, string $prefix = 'proj_'): array
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['error' => '', 'path' => ''];   // no file selected – not an error
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload failed (code ' . $file['error'] . ').', 'path' => ''];
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

// ── Handle POST actions ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token  = $_POST['csrf_token'] ?? '';
    $action = $_POST['action']     ?? '';

    if (!verify_csrf($token)) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            // ── Add project ──────────────────────────────────────────────
            case 'add':
                $title       = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $sortOrder   = (int) ($_POST['sort_order'] ?? 0);

                if ($title === '') {
                    $error = 'Project title is required.';
                    break;
                }

                $imgResult = handle_project_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
                if ($imgResult['error'] !== '') {
                    $error = $imgResult['error'];
                    break;
                }

                $imagePath = $imgResult['path'];
                $db->prepare("INSERT INTO projects (title, description, image_path, sort_order) VALUES (?,?,?,?)")
                   ->execute([$title, $description, $imagePath, $sortOrder]);
                $success = 'Project added successfully.';
                break;

            // ── Edit project ─────────────────────────────────────────────
            case 'edit':
                $id          = (int) ($_POST['id'] ?? 0);
                $title       = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $sortOrder   = (int) ($_POST['sort_order'] ?? 0);

                if ($id <= 0 || $title === '') {
                    $error = 'Invalid project data.';
                    break;
                }

                // Fetch existing row to preserve current image
                $existing = $db->prepare("SELECT image_path FROM projects WHERE id = ?");
                $existing->execute([$id]);
                $existing = $existing->fetch();
                if (!$existing) {
                    $error = 'Project not found.';
                    break;
                }

                $imgResult = handle_project_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
                if ($imgResult['error'] !== '') {
                    $error = $imgResult['error'];
                    break;
                }

                $imagePath = $imgResult['path'] !== '' ? $imgResult['path'] : $existing['image_path'];

                $db->prepare("UPDATE projects SET title=?, description=?, image_path=?, sort_order=? WHERE id=?")
                   ->execute([$title, $description, $imagePath, $sortOrder, $id]);
                $success = 'Project updated successfully.';
                break;

            // ── Delete project ────────────────────────────────────────────
            case 'delete':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $error = 'Invalid project ID.';
                    break;
                }
                // Delete uploaded image if it's inside our uploads folder
                $row = $db->prepare("SELECT image_path FROM projects WHERE id = ?");
                $row->execute([$id]);
                $row = $row->fetch();
                if ($row && strpos($row['image_path'], UPLOAD_URL) === 0) {
                    $filePath = __DIR__ . '/../' . $row['image_path'];
                    if (is_file($filePath)) {
                        unlink($filePath);
                    }
                }
                $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
                $success = 'Project deleted.';
                break;
        }
    }
    regenerate_csrf();
}

// ── Load data ──────────────────────────────────────────────────────────────
$projects    = $db->query("SELECT * FROM projects ORDER BY sort_order ASC, id ASC")->fetchAll();
$editProject = null;
$editId      = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$editId]);
    $editProject = $stmt->fetch() ?: null;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Projects – Portfolio Manager</title>
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
        .project-thumb { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand">📁 Portfolio Admin</div>
    <a href="dashboard.php">⚙️ Site Settings</a>
    <a href="projects.php" class="active">🖼️ Projects</a>
    <hr style="border-color:#444;margin:8px 0;">
    <a href="../index.php" target="_blank">🌐 View Site</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- Main content -->
<div class="main-content">
    <h2 class="mb-4">Projects</h2>

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

    <div class="row">
        <!-- Left: project list -->
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>All Projects (<?= count($projects) ?>)</span>
                    <a href="projects.php" class="btn btn-sm btn-primary">+ Add New</a>
                </div>
                <?php if (empty($projects)): ?>
                <div class="card-body text-muted">No projects yet. Add one using the form.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $p): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($p['image_path'])): ?>
                                    <img src="../<?= h($p['image_path']) ?>" class="project-thumb" alt="<?= h($p['title']) ?>">
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= h($p['title']) ?></strong>
                                    <?php if (!empty($p['description'])): ?>
                                    <br><small class="text-muted"><?= h(mb_strimwidth($p['description'], 0, 60, '…')) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) $p['sort_order'] ?></td>
                                <td>
                                    <a href="?edit=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('Delete this project?');">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: add / edit form -->
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header">
                    <?= $editProject ? 'Edit Project' : 'Add New Project' ?>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="<?= $editProject ? 'edit' : 'add' ?>">
                        <?php if ($editProject): ?>
                        <input type="hidden" name="id" value="<?= (int) $editProject['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control"
                                   value="<?= h($editProject['title'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?= h($editProject['description'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <?php if ($editProject && !empty($editProject['image_path'])): ?>
                            <div class="mb-2">
                                <img src="../<?= h($editProject['image_path']) ?>" class="project-thumb" alt="">
                                <small class="text-muted ms-2">Current image</small>
                            </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <div class="form-text">
                                <?= $editProject ? 'Leave blank to keep current image.' : 'Optional.' ?>
                                Max 5 MB.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="<?= (int) ($editProject['sort_order'] ?? 0) ?>" min="0">
                            <div class="form-text">Lower numbers appear first.</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <?= $editProject ? 'Update Project' : 'Add Project' ?>
                            </button>
                            <?php if ($editProject): ?>
                            <a href="projects.php" class="btn btn-outline-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
