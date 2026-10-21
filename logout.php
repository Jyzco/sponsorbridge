<?php
// logout.php - Handler Logout
require_once __DIR__ . '/includes/auth.php';

logout_user();
set_flash('info', 'Anda telah berhasil keluar dari sesi.');
header('Location: ' . BASE_URL . '/login.php');
exit;
