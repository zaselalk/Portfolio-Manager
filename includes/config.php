<?php
/**
 * Database configuration and connection helper.
 */

define('DB_PATH', __DIR__ . '/../data/portfolio.db');
define('UPLOAD_DIR', __DIR__ . '/../uploads/projects/');
define('UPLOAD_URL', 'uploads/projects/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

/**
 * Returns a PDO connection to the SQLite database.
 * Initialises the schema on the first run.
 */
function get_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // WAL mode improves concurrent read performance; gracefully falls back
        // to the default DELETE journal if the filesystem does not support it.
        try {
            $pdo->exec('PRAGMA journal_mode=WAL;');
        } catch (PDOException $e) {
            // Non-fatal: continue with the default journal mode
        }
        $pdo->exec('PRAGMA foreign_keys=ON;');
        init_db($pdo);
    }
    return $pdo;
}
