<?php
// proposals.php - Proposal Tracker (Pelacak Status Proposal 9 Tahapan)
$page_title = "Pelacak Status Proposal";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

require_login();
$currentUser = current_user();
$role = $currentUser['role'];
$userId = $currentUser['id'];

// Filter status tab
$selectedStatus = clean_input($_GET['status'] ?? 'all');

// Query proposal berdasarkan peran (EO: proposal yang dikirim; Sponsor: proposal yang masuk)
$whereClause = $role === 'eo' ? "p.eo_id = ?" : "p.sponsor_id = ?";
$params = [&$userId];
$types = "i";

if ($selectedStatus !== 'all' && !empty($selectedStatus)) {
    $whereClause .= " AND p.status = ?";
    $params[] = &$selectedStatus;
    $types .= "s";
}

$sql = "
    SELECT p.*, e.title as event_title, e.category as event_category, e.attendee_estimate,
           eo.name as eo_name, sp.name as sponsor_name, spp.industry as sponsor_industry
    FROM proposals p
    JOIN events e ON p.event_id = e.id
    JOIN users eo ON p.eo_id = eo.id
    JOIN users sp ON p.sponsor_id = sp.id
    LEFT JOIN sponsor_profiles spp ON sp.id = spp.user_id
    WHERE {$whereClause}
    ORDER BY p.updated_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$proposals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung rekap proposal per status untuk tab badge
$countSql = "SELECT status, COUNT(*) as cnt FROM proposals WHERE " . ($role === 'eo' ? "eo_id = ?" : "sponsor_id = ?") . " GROUP BY status";
$stmtCount = $conn->prepare($countSql);
$stmtCount->bind_param("i", $userId);
$stmtCount->execute();
$countsRaw = $stmtCount->get_result()->fetch_all(MYSQLI_ASSOC);
$statusCounts = ['all' => 0];
foreach ($countsRaw as $cr) {
    $statusCounts[$cr['status']] = (int)$cr['cnt'];
    $statusCounts['all'] += (int)$cr['cnt'];
}

// 9 Tahapan status standar
$allStatuses = [
    'Draft', 'Dikirim', 'Dibaca', 'Review', 'Negosiasi', 'Diterima', 'Ditolak', 'Kontrak Ditandatangani', 'Selesai'
];
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto space-y-6">
            
            <!-- Header Page -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-indigo">Sponsorship Pipeline</span>
                    <h1 class="text-2xl font-black text-navy-900 tracking-tight mt-1">
                        <?= $role === 'eo' ? 'Pelacak Status Proposal Kemitraan' : 'Kotak Masuk & Review Proposal' ?>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Pantau 9 tahapan proposal sponsorship dari draf hingga kontrak selesai dan pencairan dana.
                    </p>
                </div>

                <?php if ($role === 'eo'): ?>
                <a href="<?= BASE_URL ?>/proposal-builder.php" class="px-4 py-2.5 rounded-xl font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 shadow-sm transition text-xs flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Susun Proposal Baru
                </a>
                <?php endif; ?>
            </div>

            <!-- Pipeline Status Tabs (Horizontal Scrollable) -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-200 text-xs font-semibold">
                <a href="?status=all" class="px-3.5 py-2 rounded-xl transition shrink-0 <?= $selectedStatus === 'all' ? 'bg-navy-900 text-white font-bold shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
                    Semua (<?= $statusCounts['all'] ?? 0 ?>)
                </a>
                <?php foreach ($allStatuses as $st): 
                    $cnt = $statusCounts[$st] ?? 0;
                ?>
                <a href="?status=<?= urlencode($st) ?>" class="px-3.5 py-2 rounded-xl transition shrink-0 flex items-center gap-1.5 <?= $selectedStatus === $st ? 'bg-brand-indigo text-white font-bold shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
                    <span><?= $st ?></span>
                    <?php if ($cnt > 0): ?>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $selectedStatus === $st ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' ?>"><?= $cnt ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Proposal List / Table -->
            <div class="sb-card overflow-hidden bg-white">
                <?php if (empty($proposals)): ?>
                    <div class="p-12 text-center text-slate-400">
                        <i class="fa-solid fa-folder-open text-4xl mb-3 text-slate-300"></i>
                        <p class="text-sm font-bold text-navy-900">Tidak ada proposal dengan status ini</p>
                        <p class="text-xs text-slate-500 mt-1">Silakan pilih status lain atau buat proposal baru.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-200">
                                <tr>
                                    <th class="px-5 py-4">Judul Proposal</th>
                                    <th class="px-5 py-4">Event Terkait</th>
                                    <th class="px-5 py-4"><?= $role === 'eo' ? 'Brand Target' : 'Event Organizer' ?></th>
                                    <th class="px-5 py-4">Nominal Pengajuan</th>
                                    <th class="px-5 py-4">Status Tahapan</th>
                                    <th class="px-5 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($proposals as $p): ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-5 py-4">
                                        <div class="font-extrabold text-slate-900 line-clamp-1"><?= clean_input($p['title']) ?></div>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><i class="fa-regular fa-clock mr-1"></i>Diperbarui: <?= format_date($p['updated_at']) ?></div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-800 truncate max-w-[180px]"><?= clean_input($p['event_title']) ?></div>
                                        <span class="text-[10px] text-slate-400"><?= clean_input($p['event_category']) ?></span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-navy-900">
                                            <?= clean_input($role === 'eo' ? $p['sponsor_name'] : $p['eo_name']) ?>
                                        </div>
                                        <?php if ($role === 'eo' && !empty($p['sponsor_industry'])): ?>
                                            <span class="text-[10px] text-slate-400"><?= clean_input($p['sponsor_industry']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4 font-black text-slate-900 text-sm">
                                        <?= format_rupiah($p['proposed_amount']) ?>
                                    </td>
                                    <td class="px-5 py-4">
                                        <?= render_proposal_status_badge($p['status']) ?>
                                    </td>
                                    <td class="px-5 py-4 text-right space-x-1.5 whitespace-nowrap">
                                        <?php if ($p['status'] === 'Negosiasi'): ?>
                                            <a href="<?= BASE_URL ?>/negotiation.php?id=1" class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-brand-purple hover:bg-brand-purple/90 transition inline-flex items-center gap-1 shadow-xs">
                                                <i class="fa-solid fa-comments-dollar text-[10px]"></i> Ruang Chat
                                            </a>
                                        <?php elseif ($p['status'] === 'Kontrak Ditandatangani' || $p['status'] === 'Selesai'): ?>
                                            <a href="<?= BASE_URL ?>/contract.php?id=1" class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 transition inline-flex items-center gap-1 shadow-xs">
                                                <i class="fa-solid fa-file-contract text-[10px]"></i> MoU Kontrak
                                            </a>
                                            <a href="<?= BASE_URL ?>/escrow.php?id=1" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition inline-flex items-center gap-1">
                                                <i class="fa-solid fa-vault text-[10px]"></i> Escrow
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>/proposal-detail.php?id=<?= $p['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition inline-flex items-center gap-1">
                                                Detail & Review
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
