<?php
// admin/users.php - Verifikasi Akun EO & Sponsor oleh Administrator
$page_title = "Verifikasi Pengguna";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

require_role('admin');

// Handler Aksi Verifikasi / Cabut Verifikasi
if (isset($_GET['verify_id'])) {
    $vId = (int)$_GET['verify_id'];
    $conn->query("UPDATE users SET is_verified = 1 WHERE id = {$vId}");
    create_notification($vId, 'Akun Anda Telah Terverifikasi!', 'Selamat! Dokumen legalitas organisasi Anda telah disetujui oleh Administrator.', 'profile.php');
    set_flash('success', 'Akun berhasil diverifikasi resmi.');
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

if (isset($_GET['unverify_id'])) {
    $uvId = (int)$_GET['unverify_id'];
    $conn->query("UPDATE users SET is_verified = 0 WHERE id = {$uvId}");
    set_flash('info', 'Status verifikasi akun dicabut.');
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

// Filter role
$roleFilter = clean_input($_GET['role'] ?? 'all');
$where = "role != 'admin'";
if ($roleFilter === 'eo') $where .= " AND role = 'eo'";
if ($roleFilter === 'sponsor') $where .= " AND role = 'sponsor'";

$users = $conn->query("SELECT * FROM users WHERE {$where} ORDER BY is_verified ASC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto space-y-6">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-amber-500">Moderasi Legalitas</span>
                    <h1 class="text-2xl font-black text-navy-900 tracking-tight mt-1">Verifikasi Akun EO & Sponsor</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Tinjau legalitas dan keabsahan profil organisasi Event Organizer dan Perusahaan.</p>
                </div>

                <div class="flex items-center gap-2 text-xs font-semibold">
                    <a href="?role=all" class="px-3 py-1.5 rounded-lg <?= $roleFilter === 'all' ? 'bg-navy-900 text-white' : 'bg-white text-slate-600 border border-slate-200' ?>">Semua</a>
                    <a href="?role=eo" class="px-3 py-1.5 rounded-lg <?= $roleFilter === 'eo' ? 'bg-brand-indigo text-white' : 'bg-white text-slate-600 border border-slate-200' ?>">Event Organizer</a>
                    <a href="?role=sponsor" class="px-3 py-1.5 rounded-lg <?= $roleFilter === 'sponsor' ? 'bg-brand-purple text-white' : 'bg-white text-slate-600 border border-slate-200' ?>">Sponsor / Brand</a>
                </div>
            </div>

            <div class="sb-card overflow-hidden bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5">Nama Pengguna / Organisasi</th>
                                <th class="px-5 py-3.5">Peran</th>
                                <th class="px-5 py-3.5">Kontak Email / Telp</th>
                                <th class="px-5 py-3.5">Status Verifikasi</th>
                                <th class="px-5 py-3.5">Tanggal Bergabung</th>
                                <th class="px-5 py-3.5 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-5 py-3.5 font-bold text-navy-900">
                                    <?= clean_input($u['name']) ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase <?= $u['role'] === 'eo' ? 'bg-indigo-50 text-brand-indigo' : 'bg-purple-50 text-brand-purple' ?>">
                                        <?= $u['role'] === 'eo' ? 'Event Organizer' : 'Sponsor' ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-500">
                                    <?= clean_input($u['email']) ?><br>
                                    <span class="text-[10px] text-slate-400"><?= clean_input($u['phone'] ?? '-') ?></span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <?php if ($u['is_verified']): ?>
                                        <span class="inline-flex items-center gap-1 text-emerald-600 font-bold text-[11px]">
                                            <i class="fa-solid fa-circle-check"></i> Terverifikasi
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-amber-600 font-bold text-[11px]">
                                            <i class="fa-solid fa-clock"></i> Pending Review
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 text-slate-400">
                                    <?= format_date($u['created_at']) ?>
                                </td>
                                <td class="px-5 py-3.5 text-right space-x-2">
                                    <?php if (!$u['is_verified']): ?>
                                        <a href="?verify_id=<?= $u['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition">
                                            <i class="fa-solid fa-check mr-1"></i> Verifikasi
                                        </a>
                                    <?php else: ?>
                                        <a href="?unverify_id=<?= $u['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                                            Cabut
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/profile.php?id=<?= $u['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                                        Lihat Profil
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
