# Portfolio-Manager

Portfolio Manager is a simple tool that can be self hosted to display a simple portfolio and manage it dynamically through an admin panel.

## Requirements

- PHP 7.4 or later (PHP 8.x recommended)
- PHP extensions: `pdo_sqlite`, `fileinfo`
- A web server such as Apache, Nginx, or PHP's built-in development server

## Quick Start

1. **Clone the repository** and place it in your web root.
2. Ensure the `data/` and `uploads/projects/` directories are **writable** by the web server:
   ```bash
   chmod 775 data/ uploads/projects/
   ```
3. Open the site in your browser – the database is created automatically on the first visit.
4. Navigate to `/admin/` to access the admin panel.
   - **Default credentials:** username `admin`, password `admin123`
   - ⚠️ **Change the password immediately** after your first login.

## Features

- **Dynamic portfolio** – name, title, bio, social links, and "available for hire" banner are all editable through the admin panel.
- **Project management** – add, edit, delete, and reorder portfolio projects with optional image uploads.
- **Admin panel** – protected by session-based authentication with CSRF protection.
- **SQLite database** – lightweight, zero-configuration storage (`data/portfolio.db`).
- **Image uploads** – validated by MIME type, extension, and size (max 5 MB).

## Security Notes

- The `data/` and `includes/` directories are protected by `.htaccess` rules (Apache).
  For Nginx, add equivalent `deny all` rules to those paths in your server config.
- Uploaded images are stored with randomly generated filenames.
- All database queries use PDO prepared statements to prevent SQL injection.
- All user-facing output is escaped via `htmlspecialchars` to prevent XSS.
