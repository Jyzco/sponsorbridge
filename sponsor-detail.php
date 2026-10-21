<?php
// sponsor-detail.php - Profil Lengkap Brand & Kriteria Kemitraan
$sponsorUserId = (int)($_GET['id'] ?? 0);
if ($sponsorUserId <= 0) {
    header('Location: ' . BASE_URL . '/sponsors.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$stmt = $conn->prepare("
    SELECT sp.*, u.name as brand_name, u.email as brand_email, u.phone as brand_phone, u.is_verified
    FROM sponsor_profiles sp
    JOIN users u ON sp.user_id = u.id
    WHERE sp.user_id = ?
");
$stmt->bind_param("i", $sponsorUserId);
$stmt->execute();
$sponsor = $stmt->get_result()->fetch_assoc();

if (!$sponsor) {
    echo "<div class='p-12 text-center text-slate-500'>Sponsor tidak ditemukan. <a href='" . BASE_URL . "/sponsors.php' class='text-brand-indigo underline'>Kembali</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Event yang pernah didanai oleh sponsor ini (via contracts atau event_analytics)
$sponsoredHistory = $conn->query("
    SELECT e.title, e.category, e.city, ea.actual_attendees, ea.roi_multiplier
    FROM event_analytics ea
    JOIN events e ON ea.event_id = e.id
    WHERE ea.sponsor_id = {$sponsorUserId}
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);
?>

<main class="flex-grow py-8 bg-slate-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-slate-400 mb-6 font-medium">
            <a href="<?= BASE_URL ?>/index.php" class="hover:text-slate-600">Beranda</a>
            <span>/</span>
            <a href="<?= BASE_URL ?>/sponsors.php" class="hover:text-slate-600">Marketplace Sponsor</a>
            <span>/</span>
            <span class="text-slate-800 font-bold"><?= clean_input($sponsor['company_name']) ?></span>
        </nav>

        <!-- Brand Card Header -->
        <div class="sb-card p-6 sm:p-8 mb-8 bg-white">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 pb-6 border-b border-slate-100">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-navy-900 via-brand-purple to-indigo-600 text-white font-black text-2xl flex items-center justify-center shadow-md">
                        <?= strtoupper(substr($sponsor['company_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-black text-navy-900"><?= clean_input($sponsor['company_name']) ?></h1>
                            <?php if ($sponsor['is_verified']): ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    <i class="fa-solid fa-circle-check"></i> Terverifikasi
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-brand-purple font-bold mt-0.5"><?= clean_input($sponsor['industry']) ?></p>
                        <span class="text-xs text-slate-400 mt-1 block"><i class="fa-solid fa-location-dot mr-1"></i><?= clean_input($sponsor['city']) ?>, Indonesia</span>
                    </div>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <a href="<?= BASE_URL ?>/proposal-builder.php?sponsor_id=<?= $sponsor['user_id'] ?>" class="w-full sm:w-auto px-6 py-3 rounded-xl font-bold text-white bg-brand-purple hover:bg-brand-purple/90 shadow-md shadow-brand-purple/20 transition text-xs flex items-center justify-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i> Ajukan Proposal ke Brand Ini
                    </a>
                </div>
            </div>

            <!-- Detail Kriteria Kemitraan -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 text-xs">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase tracking-wider mb-1">Rentang Anggaran Kemitraan</span>
                    <strong class="text-sm text-emerald-600 font-extrabold block">
                        <?= format_rupiah($sponsor['min_budget']) ?> - <?= format_rupiah($sponsor['max_budget']) ?>
                    </strong>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Per event / program sponsorship</span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase tracking-wider mb-1">Target Segmen Audiens</span>
                    <strong class="text-xs text-navy-900 font-bold block leading-relaxed">
                        <?= clean_input($sponsor['target_audience'] ?? 'Pelajar, Mahasiswa, Komunitas Muda') ?>
                    </strong>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase tracking-wider mb-1">Pilar Program CSR</span>
                    <strong class="text-xs text-emerald-700 font-bold block leading-relaxed">
                        <i class="fa-solid fa-leaf text-emerald-500 mr-1"></i><?= clean_input($sponsor['csr_focus'] ?? 'Pendidikan & Literasi Digital') ?>
                    </strong>
                </div>
            </div>

            <!-- Deskripsi Perusahaan -->
            <div class="mt-8 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Profil & Visi Kemitraan Brand</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    <?= clean_input($sponsor['description']) ?>
                </p>
            </div>

            <!-- Kontak Kemitraan Resmi -->
            <div class="mt-8 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs text-slate-600">
                <?php if (!empty($sponsor['contact_person'])): ?>
                    <div><span class="text-slate-400 block text-[10px] font-semibold">Kontak PIC:</span> <strong><?= clean_input($sponsor['contact_person']) ?></strong></div>
                <?php endif; ?>
                <?php if (!empty($sponsor['contact_email'])): ?>
                    <div><span class="text-slate-400 block text-[10px] font-semibold">Email Partnership:</span> <a href="mailto:<?= clean_input($sponsor['contact_email']) ?>" class="text-brand-indigo hover:underline"><?= clean_input($sponsor['contact_email']) ?></a></div>
                <?php endif; ?>
                <?php if (!empty($sponsor['website'])): ?>
                    <div><span class="text-slate-400 block text-[10px] font-semibold">Situs Resmi:</span> <a href="<?= clean_input($sponsor['website']) ?>" target="_blank" class="text-brand-indigo hover:underline truncate block"><?= clean_input($sponsor['website']) ?></a></div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
