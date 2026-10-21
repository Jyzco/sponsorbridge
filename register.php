<?php
// register.php - Halaman Registrasi Akun Baru (Pilih Peran: EO atau Sponsor)
$page_title = "Daftar Akun Baru";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$defaultRole = clean_input($_GET['role'] ?? 'eo');
if (!in_array($defaultRole, ['eo', 'sponsor'])) {
    $defaultRole = 'eo';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = clean_input($_POST['role'] ?? 'eo');
    $organizationName = clean_input($_POST['org_name'] ?? $name);
    $categoryOrIndustry = clean_input($_POST['extra_info'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Nama, email, dan kata sandi wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal harus 6 karakter.';
    } elseif (!in_array($role, ['eo', 'sponsor'])) {
        $error = 'Peran yang dipilih tidak valid.';
    } else {
        // Cek duplikasi email
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $error = 'Email tersebut sudah terdaftar. Silakan gunakan email lain atau masuk.';
        } else {
            // Hash password dengan algoritma BCRYPT
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert ke tabel users
            $stmtUser = $conn->prepare("INSERT INTO users (name, email, password, role, phone, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
            $stmtUser->bind_param("sssss", $name, $email, $hashed, $role, $phone);
            
            if ($stmtUser->execute()) {
                $newUserId = $conn->insert_id;

                // Insert profil sesuai peran
                if ($role === 'eo') {
                    $stmtProfile = $conn->prepare("INSERT INTO eo_profiles (user_id, organization_name, categories, total_events_held, avg_rating) VALUES (?, ?, ?, 0, 5.0)");
                    $stmtProfile->bind_param("iss", $newUserId, $organizationName, $categoryOrIndustry);
                    $stmtProfile->execute();
                } else {
                    $stmtProfile = $conn->prepare("INSERT INTO sponsor_profiles (user_id, company_name, industry, min_budget, max_budget) VALUES (?, ?, ?, 20000000, 500000000)");
                    $stmtProfile->bind_param("iss", $newUserId, $organizationName, $categoryOrIndustry);
                    $stmtProfile->execute();
                }

                // Kirim notifikasi selamat datang
                create_notification($newUserId, 'Selamat Datang di SponsorBridge!', 'Akun Anda berhasil dibuat. Silakan lengkapi profil organisasi untuk mempercepat proses verifikasi.', 'profile.php');

                // Otomatis login
                login_user([
                    'id' => $newUserId,
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'is_verified' => 0
                ]);

                set_flash('success', "Registrasi berhasil! Selamat datang di SponsorBridge sebagai <strong>" . ($role === 'eo' ? 'Event Organizer' : 'Sponsor / Brand') . "</strong>.");
                header('Location: ' . BASE_URL . '/dashboard.php');
                exit;
            } else {
                $error = 'Terjadi kesalahan sistem saat mendaftar: ' . $conn->error;
            }
        }
    }
}
?>

<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="max-w-xl w-full space-y-6">
        
        <div class="text-center">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-brand-indigo to-brand-purple text-white flex items-center justify-center mx-auto shadow-md shadow-brand-indigo/30 mb-4">
                <i class="fa-solid fa-user-plus text-xl"></i>
            </div>
            <h2 class="text-2xl font-black tracking-tight text-navy-900">Buat Akun SponsorBridge Baru</h2>
            <p class="text-xs text-slate-500 mt-1">
                Pilih peran Anda untuk memulai ekosistem kemitraan sponsorship modern.
            </p>
        </div>

        <div class="sb-card p-8">
            <?php if (!empty($error)): ?>
                <div class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-sm"></i>
                    <span><?= clean_input($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                
                <!-- Role Selector Tabs -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Pilih Peran Akun Anda:
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer transition focus-within:ring-2 focus-within:ring-brand-indigo select-none" id="roleLabelEo">
                            <input type="radio" name="role" value="eo" class="sr-only" <?= $defaultRole === 'eo' ? 'checked' : '' ?> onchange="updateRoleUi('eo')">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fa-solid fa-calendar-check text-brand-indigo"></i>
                                <span class="text-xs font-extrabold text-navy-900">Event Organizer</span>
                            </div>
                            <span class="text-[11px] text-slate-500 leading-tight">Saya menyelenggarakan event & mencari sponsor pendanaan.</span>
                        </label>

                        <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer transition focus-within:ring-2 focus-within:ring-brand-purple select-none" id="roleLabelSponsor">
                            <input type="radio" name="role" value="sponsor" class="sr-only" <?= $defaultRole === 'sponsor' ? 'checked' : '' ?> onchange="updateRoleUi('sponsor')">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fa-solid fa-building text-brand-purple"></i>
                                <span class="text-xs font-extrabold text-navy-900">Sponsor / Brand</span>
                            </div>
                            <span class="text-[11px] text-slate-500 leading-tight">Saya mewakili perusahaan yang ingin mensponsori event.</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="name">
                            Nama Lengkap Penanggung Jawab
                        </label>
                        <input type="text" id="name" name="name" required placeholder="Contoh: Dimas Aditya"
                            value="<?= isset($_POST['name']) ? clean_input($_POST['name']) : '' ?>"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" id="orgNameLabel" for="org_name">
                            Nama Organisasi / Komunitas
                        </label>
                        <input type="text" id="org_name" name="org_name" required placeholder="Contoh: Mahakarya Promosindo"
                            value="<?= isset($_POST['org_name']) ? clean_input($_POST['org_name']) : '' ?>"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="email">
                            Email Resmi
                        </label>
                        <input type="email" id="email" name="email" required placeholder="partnership@domain.id"
                            value="<?= isset($_POST['email']) ? clean_input($_POST['email']) : '' ?>"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="phone">
                            Nomor WhatsApp / Kontak
                        </label>
                        <input type="text" id="phone" name="phone" placeholder="08123456789"
                            value="<?= isset($_POST['phone']) ? clean_input($_POST['phone']) : '' ?>"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" id="extraInfoLabel" for="extra_info">
                            Spesialisasi Kategori Event
                        </label>
                        <input type="text" id="extra_info" name="extra_info" placeholder="Contoh: Musik, Kampus, Tech"
                            value="<?= isset($_POST['extra_info']) ? clean_input($_POST['extra_info']) : '' ?>"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="password">
                            Kata Sandi (Min 6 Karakter)
                        </label>
                        <input type="password" id="password" name="password" required placeholder="••••••••"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 rounded-xl font-bold text-white bg-gradient-to-r from-brand-indigo to-brand-purple hover:opacity-95 shadow-md shadow-brand-indigo/25 transition text-sm">
                        Daftar Akun Sekarang <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-500">
                Sudah memiliki akun? 
                <a href="<?= BASE_URL ?>/login.php" class="font-bold text-brand-indigo hover:underline">Masuk di sini</a>
            </div>
        </div>

    </div>
</main>

<script>
function updateRoleUi(role) {
    const labelEo = document.getElementById('roleLabelEo');
    const labelSp = document.getElementById('roleLabelSponsor');
    const orgLabel = document.getElementById('orgNameLabel');
    const extraLabel = document.getElementById('extraInfoLabel');
    const extraInput = document.getElementById('extra_info');

    if (role === 'eo') {
        labelEo.className = "relative flex flex-col p-4 border-2 border-brand-indigo bg-indigo-50/50 rounded-xl cursor-pointer select-none";
        labelSp.className = "relative flex flex-col p-4 border border-slate-200 bg-white rounded-xl cursor-pointer select-none opacity-80";
        orgLabel.innerText = "Nama Organisasi / Komunitas";
        extraLabel.innerText = "Spesialisasi Kategori Event";
        extraInput.placeholder = "Contoh: Musik, Kampus, Tech";
    } else {
        labelSp.className = "relative flex flex-col p-4 border-2 border-brand-purple bg-purple-50/50 rounded-xl cursor-pointer select-none";
        labelEo.className = "relative flex flex-col p-4 border border-slate-200 bg-white rounded-xl cursor-pointer select-none opacity-80";
        orgLabel.innerText = "Nama Perusahaan / Brand";
        extraLabel.innerText = "Sektor Industri Brand";
        extraInput.placeholder = "Contoh: Telekomunikasi, Perbankan, FMCG";
    }
}
// Init UI on load
updateRoleUi('<?= $defaultRole ?>');
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
