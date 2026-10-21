<?php
// login.php - Halaman Masuk
$page_title = "Masuk ke Akun Anda";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Jika sudah login, langsung ke dashboard
if (is_logged_in()) {
    header('Location: ' . (current_role() === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Harap isi email dan kata sandi Anda.';
    } else {
        // Query menggunakan Prepared Statement untuk mencegah SQL Injection
        $stmt = $conn->prepare("SELECT id, name, email, password, role, is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            login_user($user);
            set_flash('success', "Selamat datang kembali, <strong>{$user['name']}</strong>!");
            
            if ($user['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/index.php');
            } else {
                header('Location: ' . BASE_URL . '/dashboard.php');
            }
            exit;
        } else {
            $error = 'Email atau kata sandi yang Anda masukkan salah.';
        }
    }
}
?>

<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="max-w-md w-full space-y-8">
        
        <!-- Form Header -->
        <div class="text-center">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-brand-indigo to-brand-purple text-white flex items-center justify-center mx-auto shadow-md shadow-brand-indigo/30 mb-4">
                <i class="fa-solid fa-lock text-xl"></i>
            </div>
            <h2 class="text-2xl font-black tracking-tight text-navy-900">Masuk ke SponsorBridge</h2>
            <p class="text-xs text-slate-500 mt-2">
                Akses dashboard event, proposal kemitraan, dan ruang negosiasi Anda.
            </p>
        </div>

        <!-- Quick Demo Login Buttons (Untuk kemudahan pengujian dosen/reviewer) -->
        <div class="bg-indigo-50/70 border border-indigo-200/80 rounded-xl p-4">
            <p class="text-[11px] font-bold text-indigo-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <i class="fa-solid fa-bolt text-amber-500"></i> Akun Demo Sekali Klik:
            </p>
            <div class="grid grid-cols-3 gap-2">
                <button type="button" onclick="fillDemo('eo@mahakarya.id', 'password123')" class="p-2 rounded-lg bg-white border border-indigo-200 text-left hover:border-brand-indigo transition shadow-2xs">
                    <span class="block text-[11px] font-bold text-slate-800">1. EO</span>
                    <span class="block text-[9px] text-slate-400 truncate">Mahakarya</span>
                </button>
                <button type="button" onclick="fillDemo('partnership@telkonusa.co.id', 'password123')" class="p-2 rounded-lg bg-white border border-indigo-200 text-left hover:border-brand-indigo transition shadow-2xs">
                    <span class="block text-[11px] font-bold text-slate-800">2. Sponsor</span>
                    <span class="block text-[9px] text-slate-400 truncate">TelkoNusa</span>
                </button>
                <button type="button" onclick="fillDemo('admin@sponsorbridge.id', 'admin123')" class="p-2 rounded-lg bg-white border border-indigo-200 text-left hover:border-brand-indigo transition shadow-2xs">
                    <span class="block text-[11px] font-bold text-slate-800">3. Admin</span>
                    <span class="block text-[9px] text-slate-400 truncate">Trust & Safety</span>
                </button>
            </div>
        </div>

        <!-- Login Card -->
        <div class="sb-card p-8">
            <?php if (!empty($error)): ?>
                <div class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-sm"></i>
                    <span><?= clean_input($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="email">
                        Alamat Email Resmi
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm">
                            <i class="fa-regular fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" required placeholder="nama@organisasi.id" 
                            value="<?= isset($_POST['email']) ? clean_input($_POST['email']) : '' ?>"
                            class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo transition">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider" for="password">
                            Kata Sandi
                        </label>
                        <a href="#" class="text-[11px] text-brand-indigo hover:underline">Lupa sandi?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm">
                            <i class="fa-solid fa-key"></i>
                        </span>
                        <input type="password" id="password" name="password" required placeholder="••••••••" 
                            class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo transition">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label class="flex items-center text-xs text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-brand-indigo rounded border-slate-300 focus:ring-brand-indigo/30 mr-2">
                        Ingat saya di perangkat ini
                    </label>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl font-bold text-white bg-gradient-to-r from-brand-indigo to-brand-purple hover:opacity-95 shadow-md shadow-brand-indigo/25 transition text-sm">
                    Masuk Sekarang <i class="fa-solid fa-arrow-right-to-bracket ml-1 text-xs"></i>
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-500">
                Belum memiliki akun SponsorBridge? 
                <a href="<?= BASE_URL ?>/register.php" class="font-bold text-brand-indigo hover:underline">Daftar Akun Baru</a>
            </div>
        </div>

    </div>
</main>

<script>
function fillDemo(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
