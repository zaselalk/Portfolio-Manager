<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init_db.php';
require_once __DIR__ . '/../includes/auth.php';

start_session();
logout_user();
header('Location: index.php');
exit;
