<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init_db.php';
require_once __DIR__ . '/includes/auth.php';

$db       = get_db();
$settings = get_settings();
$projects = $db->query("SELECT * FROM projects ORDER BY sort_order ASC, id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= h($settings['name'] ?? 'Portfolio') ?></title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/Work%20Sans.css">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/fonts/font-awesome.min.css">
    <link rel="stylesheet" href="assets/fonts/fontawesome5-overrides.min.css">
    <link rel="stylesheet" href="assets/css/bs-theme-overrides.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
    <header>
        <div data-bss-parallax-bg="true" style="height: 600px;background: url('<?= h($settings['header_image'] ?? 'assets/img/header.jpeg') ?>') center / cover;">
            <div class="container d-flex flex-column justify-content-end align-items-start">
                <h1 class="display-2 fw-bold"><span style="color: rgb(255, 254, 254);"><?= h($settings['name'] ?? '') ?></span></h1>
                <p class="fs-3 text-badge" style="color: var(--bs-primary);"><?= h($settings['title'] ?? '') ?></p>
            </div>
        </div>
    </header>
    <main>
        <section>
            <div class="container">
                <p class="fs-3"><?= h($settings['bio'] ?? '') ?></p>
                <ul class="list-inline fs-3">
                    <?php if (!empty($settings['social_fb'])): ?>
                    <li class="list-inline-item"><a href="<?= h($settings['social_fb']) ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-facebook"></i></a></li>
                    <?php endif; ?>
                    <?php if (!empty($settings['social_tw'])): ?>
                    <li class="list-inline-item"><a href="<?= h($settings['social_tw']) ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-twitter"></i></a></li>
                    <?php endif; ?>
                    <?php if (!empty($settings['social_yt'])): ?>
                    <li class="list-inline-item"><a href="<?= h($settings['social_yt']) ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-youtube-play"></i></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </section>

        <?php if (!empty($settings['available']) && $settings['available'] === '1'): ?>
        <section>
            <div class="container">
                <div class="banner" style="padding: 2em 1em;text-align: center;background: var(--bs-primary);border-radius: 10px;">
                    <p class="fs-3">I am currently <strong>available</strong> for hire.</p>
                    <a href="mailto:<?= h($settings['hire_email'] ?? '') ?>" class="btn btn-primary text-uppercase fs-5" style="background: var(--bs-dark-text-emphasis);">
                        Hire Me<i class="fas fa-chevron-right" style="padding-right: 0px;padding-left: 7px;"></i>
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section>
            <div class="container">
                <h1>Projects</h1>
                <?php if (empty($projects)): ?>
                <p class="text-muted">No projects yet.</p>
                <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 projects-list">
                    <?php foreach ($projects as $project): ?>
                    <div class="col">
                        <div class="card">
                            <img class="card-img w-100 d-block" src="<?= h($project['image_path']) ?>" alt="<?= h($project['title']) ?>">
                            <div class="card-img-overlay d-flex flex-column justify-content-end" style="background: rgba(0,0,0,0.3); opacity: 0; transition: opacity 0.3s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0">
                                <h5 class="card-title text-white"><?= h($project['title']) ?></h5>
                                <?php if (!empty($project['description'])): ?>
                                <p class="card-text text-white"><?= h($project['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="text-start">
                        <p class="fs-4"><?= h($settings['footer_text'] ?? '') ?></p>
                        <p><?= h($settings['footer_sub'] ?? '') ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <ul class="list-inline fs-3 text-center text-md-start d-flex justify-content-center align-items-center justify-content-md-start justify-content-lg-start">
                        <?php if (!empty($settings['social_fb'])): ?>
                        <li class="list-inline-item" style="margin: 4px;"><a href="<?= h($settings['social_fb']) ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-facebook" style="margin: 4px;"></i></a></li>
                        <?php endif; ?>
                        <?php if (!empty($settings['social_tw'])): ?>
                        <li class="list-inline-item" style="margin: 4px;"><a href="<?= h($settings['social_tw']) ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-twitter" style="margin: 4px;"></i></a></li>
                        <?php endif; ?>
                        <?php if (!empty($settings['social_yt'])): ?>
                        <li class="list-inline-item" style="margin: 4px;"><a href="<?= h($settings['social_yt']) ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-youtube-play" style="margin: 4px;"></i></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="container text-end" style="padding-top: 0;">
            <a href="admin/" class="text-muted small">Admin</a>
        </div>
    </footer>

    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/bs-init.js"></script>
</body>

</html>
