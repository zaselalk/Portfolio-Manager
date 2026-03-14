<?php
/**
 * Database initialisation – creates tables and seeds default data.
 * Called automatically by get_db() on the first connection.
 */
function init_db(PDO $pdo): void
{
    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id    INTEGER PRIMARY KEY AUTOINCREMENT,
            key   TEXT    NOT NULL UNIQUE,
            value TEXT    NOT NULL DEFAULT ''
        );

        CREATE TABLE IF NOT EXISTS projects (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            title       TEXT    NOT NULL DEFAULT '',
            description TEXT    NOT NULL DEFAULT '',
            image_path  TEXT    NOT NULL DEFAULT '',
            sort_order  INTEGER NOT NULL DEFAULT 0,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS admin_users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT    NOT NULL UNIQUE,
            password_hash TEXT    NOT NULL,
            created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Seed default settings if they don't exist yet
    $defaults = [
        'name'         => 'John Andrews',
        'title'        => 'Front-end and UX Designer',
        'bio'          => 'I am John. I am an experienced front-end designer. I help companies reinvent themselves through beautiful and expressive UI.',
        'available'    => '1',
        'hire_email'   => 'john@example.com',
        'footer_text'  => 'Crafted by John Andrews',
        'footer_sub'   => 'Made with ❤️ in Canada',
        'social_fb'    => '#',
        'social_tw'    => '#',
        'social_yt'    => '#',
        'header_image' => 'assets/img/header.jpeg',
    ];

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)");
    foreach ($defaults as $key => $value) {
        $stmt->execute([':key' => $key, ':value' => $value]);
    }

    // Seed default projects if the table is empty
    $count = (int) $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    if ($count === 0) {
        $projects = [
            ['Project One',   'A showcase of beautiful UI design.',   'assets/img/project-1.png', 1],
            ['Project Two',   'Interactive dashboard experience.',     'assets/img/project-2.png', 2],
            ['Project Three', 'Mobile-first responsive application.',  'assets/img/project-3.png', 3],
        ];
        $ins = $pdo->prepare("INSERT INTO projects (title, description, image_path, sort_order) VALUES (?,?,?,?)");
        foreach ($projects as $p) {
            $ins->execute($p);
        }
    }

    // Create default admin account (admin / admin123) if none exists
    $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    if ($adminCount === 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)")
            ->execute(['admin', $hash]);
    }
}
