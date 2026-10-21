<?php
// profile.php - Halaman Profil Pengguna & Organisasi (EO / Sponsor)
$page_title = "Profil & Pengaturan Akun";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

require_login();
$currentUser = current_user();
$userId = $currentUser['id'];
$role = $currentUser['role'];

// Profil ID dari URL jika melihat profil orang lain, atau profil sendiri jika kosong
$targetUserId = (int)($_GET['id'] ?? $userId);
$isOwnProfile = ($targetUserId === $userId);

$stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->bind_param("i", $targetUserId);
$stmtUser->execute();
$viewUser = $stmtUser->get_result()->fetch_assoc();

if (!$viewUser) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$eoProfile = null;
$sponsorProfile = null;
if ($viewUser['role'] === 'eo') {
    $stmtEo = $conn->prepare("SELECT * FROM eo_profiles WHERE user_id = ?");
    $stmtEo->bind_param("i", $targetUserId);
    $stmtEo->execute();
    $eoProfile = $stmtEo->get_result()->fetch_assoc();
} elseif ($viewUser['role'] === 'sponsor') {
    $stmtSp = $conn->prepare("SELECT * FROM sponsor_profiles WHERE user_id = ?");
    $stmtSp->bind_param("i", $targetUserId);
    $stmtSp->execute();
    $sponsorProfile = $stmtSp->get_result()->fetch_assoc();
}

// Handler Update Profil Sendiri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwnProfile) {
    $name = clean_input($_POST['name'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');

    $stmtUpUser = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmtUpUser->bind_param("ssi", $name, $phone, $userId);
    $stmtUpUser->execute();

    if ($role === 'eo') {
        $orgName = clean_input($_POST['organization_name'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $city = clean_input($_POST['city'] ?? '');
        $address = clean_input($_POST['address'] ?? '');
        $categories = clean_input($_POST['categories'] ?? '');
        $website = clean_input($_POST['website'] ?? '');
        $instagram = clean_input($_POST['instagram'] ?? '');
        $bankName = clean_input($_POST['bank_name'] ?? '');
        $bankAccount = clean_input($_POST['bank_account'] ?? '');
        $bankHolder = clean_input($_POST['bank_holder'] ?? '');

        $stmtUpEo = $conn->prepare("
            UPDATE eo_profiles SET 
                organization_name = ?, description = ?, city = ?, address = ?, 
                categories = ?, website = ?, instagram = ?, bank_name = ?, 
                bank_account = ?, bank_holder = ?
            WHERE user_id = ?
        ");
        $stmtUpEo->bind_param("ssssssssssi", $orgName, $description, $city, $address, $categories, $website, $instagram, $bankName, $bankAccount, $bankHolder, $userId);
        $stmtUpEo->execute();

    } elseif ($role === 'sponsor') {
        $compName = clean_input($_POST['company_name'] ?? '');
        $industry = clean_input($_POST['industry'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $city = clean_input($_POST['city'] ?? '');
        $minBudget = (float)($_POST['min_budget'] ?? 10000000);
        $maxBudget = (float)($_POST['max_budget'] ?? 500000000);
        $targetAudience = clean_input($_POST['target_audience'] ?? '');
        $csrFocus = clean_input($_POST['csr_focus'] ?? '');

        $stmtUpSp = $conn->prepare("
            UPDATE sponsor_profiles SET 
                company_name = ?, industry = ?, description = ?, city = ?,
                min_budget = ?, max_budget = ?, target_audience = ?, csr_focus = ?
            WHERE user_id = ?
        ");
        $stmtUpSp->bind_param("ssssddssi", $compName, $industry, $description, $city, $minBudget, $maxBudget, $targetAudience, $csrFocus, $userId);
        $stmtUpSp->execute();
    }

    set_flash('success', 'Data profil organisasi Anda berhasil diperbarui.');
    header("Location: " . BASE_URL . "/profile.php");
    exit;
}
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto space-y-6">
            
            <!-- Profil Top Card -->
            <div class="sb-card p-6 sm:p-8 bg-white">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 pb-6 border-b border-slate-100">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-navy-900 to-brand-indigo text-white font-black text-2xl flex items-center justify-center shadow-md">
                            <?= strtoupper(substr($viewUser['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-xl sm:text-2xl font-black text-navy-900"><?= clean_input($viewUser['name']) ?></h1>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase <?= $viewUser['role'] === 'eo' ? 'bg-indigo-50 text-brand-indigo border border-indigo-200' : 'bg-purple-50 text-brand-purple border border-purple-200' ?>">
                                    <?= $viewUser['role'] === 'eo' ? 'Event Organizer' : 'Sponsor / Brand' ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                                <i class="fa-regular fa-envelope"></i> <?= clean_input($viewUser['email']) ?>
                                <?php if (!empty($viewUser['phone'])): ?>
                                    <span>&bull;</span> <i class="fa-solid fa-phone"></i> <?= clean_input($viewUser['phone']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <?php if ($viewUser['is_verified']): ?>
                            <span class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-xs flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i> Akun Terverifikasi
                            </span>
                        <?php else: ?>
                            <span class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 font-bold text-xs flex items-center gap-1.5">
                                <i class="fa-solid fa-clock text-amber-500"></i> Menunggu Verifikasi Dokumen
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($isOwnProfile): ?>
                <!-- Edit Profile Form -->
                <form method="POST" action="" class="space-y-6 pt-6">
                    <h3 class="text-sm font-black text-navy-900 uppercase tracking-wider">Perbarui Informasi Organisasi & Rekening</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama PIC / Penanggung Jawab</label>
                            <input type="text" name="name" value="<?= clean_input($viewUser['name']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nomor Kontak Resmi</label>
                            <input type="text" name="phone" value="<?= clean_input($viewUser['phone']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30">
                        </div>
                    </div>

                    <?php if ($role === 'eo'): ?>
                    <!-- Form EO Details -->
                    <div class="space-y-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Resmi Organisasi / Agency</label>
                            <input type="text" name="organization_name" value="<?= clean_input($eoProfile['organization_name'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-brand-indigo/30">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kota Domisili</label>
                                <input type="text" name="city" value="<?= clean_input($eoProfile['city'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kategori Spesialisasi</label>
                                <input type="text" name="categories" value="<?= clean_input($eoProfile['categories'] ?? '') ?>" placeholder="Musik, Kampus, Tech" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deskripsi & Track Record EO</label>
                            <textarea name="description" rows="3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30"><?= clean_input($eoProfile['description'] ?? '') ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Akun Instagram</label>
                                <input type="text" name="instagram" value="<?= clean_input($eoProfile['instagram'] ?? '') ?>" placeholder="@organisasi" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Situs Web Portofolio</label>
                                <input type="text" name="website" value="<?= clean_input($eoProfile['website'] ?? '') ?>" placeholder="https://domain.id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30">
                            </div>
                        </div>

                        <!-- Data Rekening Pencairan Escrow -->
                        <div class="pt-4 border-t border-slate-100 space-y-3">
                            <h4 class="text-xs font-black text-navy-900 uppercase tracking-wider">Rekening Bank untuk Pencairan Escrow</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Bank</label>
                                    <input type="text" name="bank_name" value="<?= clean_input($eoProfile['bank_name'] ?? 'BCA') ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nomor Rekening</label>
                                    <input type="text" name="bank_account" value="<?= clean_input($eoProfile['bank_account'] ?? '') ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Atas Nama Pemilik</label>
                                    <input type="text" name="bank_holder" value="<?= clean_input($eoProfile['bank_holder'] ?? '') ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php elseif ($role === 'sponsor'): ?>
                    <!-- Form Sponsor Details -->
                    <div class="space-y-4 pt-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Perusahaan / Brand</label>
                                <input type="text" name="company_name" value="<?= clean_input($sponsorProfile['company_name'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-brand-purple/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Sektor Industri</label>
                                <input type="text" name="industry" value="<?= clean_input($sponsorProfile['industry'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-purple/30">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Misi Kemitraan</label>
                            <textarea name="description" rows="3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-purple/30"><?= clean_input($sponsorProfile['description'] ?? '') ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Min. Alokasi Budget (Rp)</label>
                                <input type="number" name="min_budget" value="<?= clean_input($sponsorProfile['min_budget'] ?? 10000000) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-brand-purple/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Maks. Alokasi Budget (Rp)</label>
                                <input type="number" name="max_budget" value="<?= clean_input($sponsorProfile['max_budget'] ?? 500000000) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-brand-purple/30">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Target Segmen Audiens</label>
                                <input type="text" name="target_audience" value="<?= clean_input($sponsorProfile['target_audience'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-purple/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Fokus Program CSR</label>
                                <input type="text" name="csr_focus" value="<?= clean_input($sponsorProfile['csr_focus'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-purple/30">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-navy-900 hover:bg-navy-800 shadow-sm transition">
                            Simpan Perubahan Profil
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <!-- View-only Public Snapshot of User -->
                <div class="pt-6 space-y-4 text-xs text-slate-600">
                    <p class="leading-relaxed">
                        <?= clean_input(($role === 'eo' ? $eoProfile['description'] : $sponsorProfile['description']) ?? 'Profil terverifikasi di platform SponsorBridge.') ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
