<?php
require __DIR__ . '/../inc/bootstrap.php';
start_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $_SESSION = [];
    session_destroy();
}
redirect(base_url('admin/login.php'));
