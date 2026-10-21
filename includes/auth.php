<?php
// includes/auth.php - Manajemen Sesi, Autentikasi, & Peran Pengguna
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Cek apakah pengguna sedang login
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Ambil data user yang sedang login beserta profilnya
 */
function current_user(): ?array {
    global $conn;
    if (!is_logged_in()) {
        return null;
    }

    static $cachedUser = null;
    if ($cachedUser !== null) {
        return $cachedUser;
    }

    $userId = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, name, email, role, phone, avatar, is_verified, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user) {
        // Ambil data profil spesifik sesuai role
        if ($user['role'] === 'eo') {
            $stmtEo = $conn->prepare("SELECT * FROM eo_profiles WHERE user_id = ?");
            $stmtEo->bind_param("i", $userId);
            $stmtEo->execute();
            $user['profile'] = $stmtEo->get_result()->fetch_assoc() ?? [];
        } elseif ($user['role'] === 'sponsor') {
            $stmtSp = $conn->prepare("SELECT * FROM sponsor_profiles WHERE user_id = ?");
            $stmtSp->bind_param("i", $userId);
            $stmtSp->execute();
            $user['profile'] = $stmtSp->get_result()->fetch_assoc() ?? [];
        }
        $cachedUser = $user;
        return $user;
    }

    return null;
}

/**
 * Ambil ID user aktif
 */
function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

/**
 * Ambil role user aktif ('eo', 'sponsor', 'admin', atau 'guest')
 */
function current_role(): string {
    return $_SESSION['role'] ?? 'guest';
}

/**
 * Proteksi halaman: Harus login
 */
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('error', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Proteksi halaman: Harus role tertentu
 * @param array|string $roles contoh: ['admin'] atau ['eo', 'sponsor']
 */
function require_role($roles): void {
    require_login();
    $rolesArray = is_array($roles) ? $roles : [$roles];
    $currentRole = current_role();
    if (!in_array($currentRole, $rolesArray)) {
        set_flash('error', 'Akses ditolak. Anda tidak memiliki izin untuk melihat halaman tersebut.');
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

/**
 * Login user ke sesi
 */
function login_user(array $user): void {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['is_verified'] = $user['is_verified'];
}

/**
 * Logout user dari sesi
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Set flash message
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

/**
 * Ambil flash message dan hapus setelah ditampilkan
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
