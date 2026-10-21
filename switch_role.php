<?php
// switch_role.php - Beralih Akun Demo Cepat (EO, Sponsor, Admin, Guest)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$role = clean_input($_GET['role'] ?? '');
$redirect = clean_input($_GET['redirect'] ?? BASE_URL . '/dashboard.php');

if ($role === 'guest') {
    logout_user();
    set_flash('info', 'Anda sekarang berada dalam mode Tamu / Pengunjung Publik.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$targetUserId = null;
if ($role === 'eo') {
    $targetUserId = 2; // Mahakarya Studio Promosindo
} elseif ($role === 'sponsor') {
    $targetUserId = 8; // PT TelkoNusa Tbk
} elseif ($role === 'admin') {
    $targetUserId = 1; // SponsorBridge Administrator
}

if ($targetUserId) {
    $stmt = $conn->prepare("SELECT id, name, email, role, is_verified FROM users WHERE id = ?");
    $stmt->bind_param("i", $targetUserId);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if ($u) {
        login_user($u);
        set_flash('success', "Beralih peran berhasil! Anda sekarang masuk sebagai: <strong>{$u['name']}</strong> (" . strtoupper($u['role']) . ")");
        if ($role === 'admin') {
            header('Location: ' . BASE_URL . '/admin/index.php');
            exit;
        }
        header('Location: ' . $redirect);
        exit;
    }
}

set_flash('error', 'Role tidak valid.');
header('Location: ' . BASE_URL . '/index.php');
exit;
