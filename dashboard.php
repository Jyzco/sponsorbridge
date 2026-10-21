<?php
// dashboard.php - Dashboard Terpusat (Berdasar Peran: EO vs Sponsor)
$page_title = "Dashboard Utama";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

require_login();
$currentUser = current_user();
$userId = $currentUser['id'];
$role = $currentUser['role'];

if ($role === 'admin') {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

// -------------------------------------------------------------
// DATA KHUSUS EVENT ORGANIZER (EO)
// -------------------------------------------------------------
if ($role === 'eo') {
    // 1. Ringkasan Total Event
    $stmtEvents = $conn->prepare("SELECT COUNT(*) AS total, SUM(funds_raised) AS total_raised FROM events WHERE eo_id = ?");
    $stmtEvents->bind_param("i", $userId);
    $stmtEvents->execute();
    $statsEvents = $stmtEvents->get_result()->fetch_assoc();
    $totalEvents = (int)($statsEvents['total'] ?? 0);
    $totalFundsRaised = (float)($statsEvents['total_raised'] ?? 0);

    // 2. Proposal Aktif & Proposal Diterima
    $stmtProp = $conn->prepare("
        SELECT 
            COUNT(*) AS total_proposals,
            SUM(CASE WHEN status IN ('Dikirim', 'Dibaca', 'Review', 'Negosiasi') THEN 1 ELSE 0 END) AS active_proposals,
            SUM(CASE WHEN status IN ('Diterima', 'Kontrak Ditandatangani', 'Selesai') THEN 1 ELSE 0 END) AS accepted_proposals
        FROM proposals 
        WHERE eo_id = ?
    ");
    $stmtProp->bind_param("i", $userId);
    $stmtProp->execute();
    $statsProp = $stmtProp->get_result()->fetch_assoc();
    $activeProposals = (int)($statsProp['active_proposals'] ?? 0);
    $acceptedProposals = (int)($statsProp['accepted_proposals'] ?? 0);

    // 3. Event Milik EO
    $stmtMyEvents = $conn->prepare("SELECT * FROM events WHERE eo_id = ? ORDER BY created_at DESC LIMIT 4");
    $stmtMyEvents->bind_param("i", $userId);
    $stmtMyEvents->execute();
    $myEvents = $stmtMyEvents->get_result()->fetch_all(MYSQLI_ASSOC);

    // 4. Proposal Terbaru EO
    $stmtRecentProps = $conn->prepare("
        SELECT p.*, e.title as event_title, u.name as sponsor_name, sp.industry as sponsor_industry
        FROM proposals p
        JOIN events e ON p.event_id = e.id
        JOIN users u ON p.sponsor_id = u.id
        LEFT JOIN sponsor_profiles sp ON sp.user_id = u.id
        WHERE p.eo_id = ?
        ORDER BY p.updated_at DESC
        LIMIT 5
    ");
    $stmtRecentProps->bind_param("i", $userId);
    $stmtRecentProps->execute();
    $recentProposals = $stmtRecentProps->get_result()->fetch_all(MYSQLI_ASSOC);

    // 5. Escrow Milestones Mendatang
    $stmtMilestones = $conn->prepare("
        SELECT em.*, c.contract_number, e.title as event_title, u.name as sponsor_name
        FROM escrow_milestones em
        JOIN contracts c ON em.contract_id = c.id
        JOIN proposals p ON c.proposal_id = p.id
        JOIN events e ON p.event_id = e.id
        JOIN users u ON p.sponsor_id = u.id
        WHERE p.eo_id = ?
        ORDER BY em.milestone_number ASC
        LIMIT 3
    ");
    $stmtMilestones->bind_param("i", $userId);
    $stmtMilestones->execute();
    $upcomingMilestones = $stmtMilestones->get_result()->fetch_all(MYSQLI_ASSOC);
}

// -------------------------------------------------------------
// DATA KHUSUS SPONSOR / BRAND
// -------------------------------------------------------------
if ($role === 'sponsor') {
    // 1. Proposal Masuk
    $stmtInProp = $conn->prepare("
        SELECT 
            COUNT(*) AS total_inbox,
            SUM(CASE WHEN status IN ('Dikirim', 'Dibaca', 'Review', 'Negosiasi') THEN 1 ELSE 0 END) AS pending_action
        FROM proposals 
        WHERE sponsor_id = ?
    ");
    $stmtInProp->bind_param("i", $userId);
    $stmtInProp->execute();
    $statsInProp = $stmtInProp->get_result()->fetch_assoc();
    $totalInbox = (int)($statsInProp['total_inbox'] ?? 0);
    $pendingAction = (int)($statsInProp['pending_action'] ?? 0);

    // 2. Kontrak Aktif & Total Investasi
    $stmtContracts = $conn->prepare("
        SELECT COUNT(*) AS total_active_contracts, SUM(c.agreement_amount) AS total_invested
        FROM contracts c
        JOIN proposals p ON c.proposal_id = p.id
        WHERE p.sponsor_id = ? AND c.status IN ('Aktif', 'Selesai')
    ");
    $stmtContracts->bind_param("i", $userId);
    $stmtContracts->execute();
    $contractStats = $stmtContracts->get_result()->fetch_assoc();
    $activeContracts = (int)($contractStats['total_active_contracts'] ?? 0);
    $totalInvested = (float)($contractStats['total_invested'] ?? 0);

    // 3. Rekomendasi Event (AI Match Score)
    $sponsorProfile = $currentUser['profile'] ?? [];
    $allOpenEvents = $conn->query("
        SELECT e.*, u.name as eo_name 
        FROM events e 
        JOIN users u ON e.eo_id = u.id 
        WHERE e.status = 'open' 
        ORDER BY e.created_at DESC 
        LIMIT 6
    ")->fetch_all(MYSQLI_ASSOC);

    $recommendedEvents = [];
    foreach ($allOpenEvents as $ev) {
        $match = calculate_match_score($ev, $sponsorProfile);
        $ev['match_score'] = $match['score'];
        $ev['match_reason'] = $match['reason'];
        $recommendedEvents[] = $ev;
    }
    usort($recommendedEvents, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
    $recommendedEvents = array_slice($recommendedEvents, 0, 3);

    // 4. Proposal Masuk Terbaru untuk Sponsor
    $stmtRecentInProps = $conn->prepare("
        SELECT p.*, e.title as event_title, e.attendee_estimate, e.category as event_category, u.name as eo_name
        FROM proposals p
        JOIN events e ON p.event_id = e.id
        JOIN users u ON p.eo_id = u.id
        WHERE p.sponsor_id = ?
        ORDER BY p.updated_at DESC
        LIMIT 5
    ");
    $stmtRecentInProps->bind_param("i", $userId);
    $stmtRecentInProps->execute();
    $incomingProposals = $stmtRecentInProps->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="flex-grow flex">
    <!-- Sidebar Component -->
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <!-- Main Content Area with Pl-64 for Desktop Sidebar -->
    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        
        <!-- Welcome Header Banner -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-navy-900 via-navy-800 to-indigo-950 p-6 rounded-2xl text-white shadow-sm">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-white/20 backdrop-blur-md">
                        <?= $role === 'eo' ? 'Event Organizer Portal' : 'Corporate Brand Portal' ?>
                    </span>
                    <span class="text-xs text-slate-300">
                        <i class="fa-regular fa-clock mr-1"></i><?= date('d M Y') ?>
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                    Selamat Datang, <?= clean_input($currentUser['name']) ?>!
                </h1>
                <p class="text-xs text-slate-300 mt-1 max-w-xl">
                    <?= $role === 'eo' 
                        ? 'Kelola event Anda, buat proposal B2B cerdas, dan jalin kemitraan sponsorship dengan brand nasional.' 
                        : 'Eksplorasi event potensial dengan rekomendasi AI, negosiasikan paket kemitraan, dan pantau ROI kampanye Anda.' ?>
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <?php if ($role === 'eo'): ?>
                    <a href="<?= BASE_URL ?>/create-event.php" class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-calendar-plus"></i> Buat Event
                    </a>
                    <a href="<?= BASE_URL ?>/proposal-builder.php" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-900 bg-white hover:bg-slate-100 transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-wand-magic-sparkles text-brand-purple"></i> Susun Proposal
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/events.php" class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-purple hover:bg-brand-purple/90 transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-compass"></i> Cari Event Baru
                    </a>
                    <a href="<?= BASE_URL ?>/analytics.php" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-900 bg-white hover:bg-slate-100 transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-chart-line text-brand-indigo"></i> Lihat Analytics
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($role === 'eo'): ?>
        <!-- ============================================================== -->
        <!-- TAMPILAN DASHBOARD KHUSUS EO -->
        <!-- ============================================================== -->

        <!-- 4 Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="sb-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Event</span>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-brand-indigo flex items-center justify-center text-sm">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-navy-900 mt-2"><?= $totalEvents ?></div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-arrow-trend-up text-emerald-500"></i> Event aktif terdaftar
                </div>
            </div>

            <div class="sb-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Proposal Aktif</span>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-navy-900 mt-2"><?= $activeProposals ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Dalam review & negosiasi</div>
            </div>

            <div class="sb-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Proposal Diterima</span>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-navy-900 mt-2"><?= $acceptedProposals ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Kesepakatan & MoU aktif</div>
            </div>

            <div class="sb-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Dana Terkumpul</span>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-vault"></i>
                    </div>
                </div>
                <div class="text-xl font-black text-navy-900 mt-2 truncate"><?= format_rupiah($totalFundsRaised) ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Via Rekening Escrow</div>
            </div>
        </div>

        <!-- Section: Event Aktif & Escrow Milestones -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Event Aktif EO (2 Cols) -->
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-extrabold text-navy-900">Event Yang Sedang Dikelola</h2>
                        <p class="text-xs text-slate-500">Progress capaian pendanaan sponsorship per event</p>
                    </div>
                    <a href="<?= BASE_URL ?>/create-event.php" class="text-xs font-bold text-brand-indigo hover:underline">
                        + Tambah Event
                    </a>
                </div>

                <div class="space-y-3">
                    <?php if (empty($myEvents)): ?>
                        <div class="sb-card p-8 text-center text-xs text-slate-500">
                            Anda belum memiliki event aktif. <a href="<?= BASE_URL ?>/create-event.php" class="text-brand-indigo font-bold underline">Buat event pertama</a> sekarang!
                        </div>
                    <?php else: ?>
                        <?php foreach ($myEvents as $me): 
                            $pct = min(100, round(($me['funds_raised'] / max(1, $me['sponsorship_target'])) * 100));
                        ?>
                        <div class="sb-card p-4 hover:border-brand-indigo/40 transition flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-brand-indigo uppercase">
                                        <?= clean_input($me['category']) ?>
                                    </span>
                                    <span class="text-[11px] text-slate-400">
                                        <i class="fa-regular fa-calendar mr-1"></i><?= format_date($me['start_date']) ?>
                                    </span>
                                </div>
                                <h3 class="text-sm font-extrabold text-navy-900 truncate">
                                    <?= clean_input($me['title']) ?>
                                </h3>
                                <div class="flex items-center gap-3 text-xs text-slate-500 mt-1">
                                    <span><i class="fa-solid fa-users text-slate-400 mr-1"></i><?= number_format($me['attendee_estimate']) ?> Peserta</span>
                                    <span>&bull;</span>
                                    <span><i class="fa-solid fa-location-dot text-slate-400 mr-1"></i><?= clean_input($me['city']) ?></span>
                                </div>
                            </div>

                            <!-- Progress & Actions -->
                            <div class="w-full sm:w-56 shrink-0">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-[10px] text-slate-500"><?= format_rupiah($me['funds_raised']) ?></span>
                                    <span class="text-[11px] font-extrabold text-brand-indigo"><?= $pct ?>%</span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden mb-2">
                                    <div class="h-full bg-gradient-to-r from-brand-indigo to-brand-purple rounded-full" style="width: <?= $pct ?>%"></div>
                                </div>
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= BASE_URL ?>/proposal-builder.php?event_id=<?= $me['id'] ?>" class="text-[11px] px-2.5 py-1 rounded-lg bg-indigo-50 text-brand-indigo font-bold hover:bg-brand-indigo hover:text-white transition">
                                        Buat Proposal
                                    </a>
                                    <a href="<?= BASE_URL ?>/event-detail.php?id=<?= $me['id'] ?>" class="text-[11px] px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-bold hover:bg-slate-200 transition">
                                        Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Escrow Milestones (1 Col) -->
            <div class="space-y-4">
                <div>
                    <h2 class="text-base font-extrabold text-navy-900">Jadwal Escrow Mendatang</h2>
                    <p class="text-xs text-slate-500">Pencairan termin 30% - 40% - 30%</p>
                </div>

                <div class="sb-card p-5 space-y-4">
                    <?php if (empty($upcomingMilestones)): ?>
                        <div class="py-6 text-center text-xs text-slate-400">Belum ada jadwal pencairan aktif</div>
                    <?php else: ?>
                        <?php foreach ($upcomingMilestones as $idx => $ms): ?>
                        <div class="pb-3 border-b border-slate-100 last:border-0 last:pb-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[10px] font-bold text-brand-indigo uppercase">Termin <?= $ms['milestone_number'] ?> (<?= $ms['percentage'] ?>%)</span>
                                <?= render_escrow_milestone_badge($ms['status']) ?>
                            </div>
                            <h3 class="text-xs font-bold text-slate-800 line-clamp-1"><?= clean_input($ms['milestone_title']) ?></h3>
                            <div class="flex items-center justify-between text-xs mt-1.5">
                                <span class="font-extrabold text-navy-900"><?= format_rupiah($ms['amount']) ?></span>
                                <span class="text-[10px] text-slate-400"><?= clean_input($ms['sponsor_name']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>/escrow.php" class="block text-center py-2 text-xs font-bold text-brand-indigo hover:underline border-t border-slate-100 mt-2">
                        Buka Pusat Escrow & Kontrak &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- Section: Tabel Pelacak Proposal Terbaru -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-navy-900">Pelacak Status Proposal Terkini</h2>
                    <p class="text-xs text-slate-500">Status 9 tahapan proposal sponsorship Anda kepada sponsor</p>
                </div>
                <a href="<?= BASE_URL ?>/proposals.php" class="text-xs font-bold text-brand-indigo hover:underline">
                    Lihat Semua Proposal &rarr;
                </a>
            </div>

            <div class="sb-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5">Judul Proposal</th>
                                <th class="px-5 py-3.5">Event Terkait</th>
                                <th class="px-5 py-3.5">Target Sponsor</th>
                                <th class="px-5 py-3.5">Nominal Penawaran</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($recentProposals)): ?>
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada proposal yang dibuat.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentProposals as $rp): ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-5 py-3.5 font-bold text-slate-800">
                                        <?= clean_input($rp['title']) ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600">
                                        <?= clean_input($rp['event_title']) ?>
                                    </td>
                                    <td class="px-5 py-3.5 font-semibold text-navy-900">
                                        <?= clean_input($rp['sponsor_name']) ?>
                                    </td>
                                    <td class="px-5 py-3.5 font-extrabold text-slate-800">
                                        <?= format_rupiah($rp['proposed_amount']) ?>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <?= render_proposal_status_badge($rp['status']) ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-right space-x-2">
                                        <?php if ($rp['status'] === 'Negosiasi'): ?>
                                            <a href="<?= BASE_URL ?>/negotiation.php?id=1" class="px-2.5 py-1 rounded bg-purple-50 text-brand-purple font-bold hover:bg-brand-purple hover:text-white transition inline-flex items-center gap-1">
                                                <i class="fa-solid fa-comments-dollar text-[10px]"></i> Chat Room
                                            </a>
                                        <?php elseif ($rp['status'] === 'Kontrak Ditandatangani'): ?>
                                            <a href="<?= BASE_URL ?>/contract.php?id=1" class="px-2.5 py-1 rounded bg-teal-50 text-teal-700 font-bold hover:bg-teal-600 hover:text-white transition inline-flex items-center gap-1">
                                                <i class="fa-solid fa-file-contract text-[10px]"></i> MoU
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>/proposal-detail.php?id=<?= $rp['id'] ?>" class="px-2.5 py-1 rounded bg-slate-100 text-slate-700 font-bold hover:bg-slate-200 transition">
                                                Detail
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- ============================================================== -->
        <!-- TAMPILAN DASHBOARD KHUSUS SPONSOR / BRAND -->
        <!-- ============================================================== -->

        <!-- 4 Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="sb-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Proposal Masuk</span>
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-brand-purple flex items-center justify-center text-sm">
                        <i class="fa-solid fa-inbox"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-navy-900 mt-2"><?= $totalInbox ?></div>
                <div class="text-[11px] text-slate-400 mt-1"><?= $pendingAction ?> menunggu keputusan Anda</div>
            </div>

            <div class="sb-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Kontrak Aktif</span>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-file-signature"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-navy-900 mt-2"><?= $activeContracts ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Dalam pelaksanaan & pengawasan</div>
            </div>

            <div class="sb-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Investasi</span>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-money-bill-trend-up"></i>
                    </div>
                </div>
                <div class="text-xl font-black text-navy-900 mt-2 truncate"><?= format_rupiah($totalInvested) ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Komitmen sponsorship & CSR</div>
            </div>

            <div class="sb-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Rata-Rata ROI</span>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-brand-indigo flex items-center justify-center text-sm">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-emerald-600 mt-2">3.4x</div>
                <div class="text-[11px] text-slate-400 mt-1">+340% nilai earned media</div>
            </div>
        </div>

        <!-- Section: Rekomendasi Event AI & Proposal Masuk -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            
            <!-- Rekomendasi Event AI (2 Cols) -->
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-extrabold text-navy-900 flex items-center gap-2">
                            <i class="fa-solid fa-wand-magic-sparkles text-brand-purple"></i> Rekomendasi Event Cerdas (AI Match)
                        </h2>
                        <p class="text-xs text-slate-500">Dipilih berdasarkan keselarasan demografi audiens dan fokus CSR brand Anda</p>
                    </div>
                    <a href="<?= BASE_URL ?>/events.php?view=match" class="text-xs font-bold text-brand-purple hover:underline">
                        Lihat Semua Rekomendasi &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($recommendedEvents as $rev): ?>
                    <div class="sb-card p-5 flex flex-col justify-between border-t-4 border-brand-purple">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-brand-purple uppercase">
                                    <?= clean_input($rev['category']) ?>
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    <i class="fa-solid fa-sparkles text-amber-500 mr-1"></i><?= $rev['match_score'] ?>% Cocok
                                </span>
                            </div>

                            <h3 class="text-sm font-extrabold text-navy-900 line-clamp-1 mb-1">
                                <?= clean_input($rev['title']) ?>
                            </h3>
                            <p class="text-[11px] text-slate-400 mb-2 font-medium">
                                Oleh: <?= clean_input($rev['eo_name']) ?>
                            </p>
                            <p class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100 leading-relaxed mb-3">
                                <strong class="text-slate-700">Analisis:</strong> <?= clean_input($rev['match_reason']) ?>
                            </p>
                            
                            <div class="grid grid-cols-2 gap-2 text-xs py-2 border-t border-slate-100 text-slate-600">
                                <div>
                                    <span class="text-[10px] text-slate-400 block">Peserta:</span>
                                    <strong class="text-navy-900"><?= number_format($rev['attendee_estimate']) ?></strong>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400 block">Target Sponsor:</span>
                                    <strong class="text-emerald-700"><?= format_rupiah($rev['sponsorship_target']) ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 mt-2 border-t border-slate-100">
                            <a href="<?= BASE_URL ?>/event-detail.php?id=<?= $rev['id'] ?>" class="w-full py-2 rounded-xl text-xs font-bold text-center block text-white bg-brand-purple hover:bg-brand-purple/90 transition shadow-sm">
                                Buka Detail & Paket Sponsor
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Action / Profil Brand Snapshot (1 Col) -->
            <div class="space-y-4">
                <div>
                    <h2 class="text-base font-extrabold text-navy-900">Profil Kemitraan Brand</h2>
                    <p class="text-xs text-slate-500">Kriteria sponsorship perusahaan Anda</p>
                </div>

                <div class="sb-card p-5 space-y-4">
                    <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-navy-800 to-brand-purple text-white font-bold flex items-center justify-center text-lg shadow-sm">
                            <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <h3 class="text-xs font-extrabold text-navy-900"><?= clean_input($currentUser['name']) ?></h3>
                            <span class="text-[10px] font-semibold text-brand-purple"><?= clean_input($sponsorProfile['industry'] ?? 'Korporat') ?></span>
                        </div>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">Alokasi Budget:</span>
                            <span class="font-bold text-emerald-600">
                                <?= format_rupiah($sponsorProfile['min_budget'] ?? 10000000) ?> - <?= format_rupiah($sponsorProfile['max_budget'] ?? 500000000) ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">Target Audiens:</span>
                            <span class="text-slate-600 line-clamp-2"><?= clean_input($sponsorProfile['target_audience'] ?? 'Gen Z, Milenial, Mahasiswa') ?></span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">Fokus Pilar CSR:</span>
                            <span class="text-slate-600 line-clamp-2"><i class="fa-solid fa-leaf text-emerald-500 mr-1"></i><?= clean_input($sponsorProfile['csr_focus'] ?? 'Pendidikan & Literasi Digital') ?></span>
                        </div>
                    </div>

                    <a href="<?= BASE_URL ?>/profile.php" class="block text-center py-2 text-xs font-bold text-brand-purple hover:underline border-t border-slate-100 mt-2">
                        Perbarui Kriteria Kemitraan &rarr;
                    </a>
                </div>
            </div>

        </div>

        <!-- Section: Tabel Proposal Masuk Terbaru -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-navy-900">Proposal Masuk Untuk Di-Review</h2>
                    <p class="text-xs text-slate-500">Proposal event yang diajukan oleh Event Organizer kepada brand Anda</p>
                </div>
                <a href="<?= BASE_URL ?>/proposals.php" class="text-xs font-bold text-brand-purple hover:underline">
                    Buka Kotak Masuk Proposal &rarr;
                </a>
            </div>

            <div class="sb-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5">Judul Proposal</th>
                                <th class="px-5 py-3.5">Event Organizer</th>
                                <th class="px-5 py-3.5">Kategori & Peserta</th>
                                <th class="px-5 py-3.5">Nilai Kemitraan</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-right">Keputusan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($incomingProposals)): ?>
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada proposal yang masuk.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($incomingProposals as $ip): ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-5 py-3.5 font-bold text-slate-800">
                                        <?= clean_input($ip['title']) ?>
                                    </td>
                                    <td class="px-5 py-3.5 font-semibold text-navy-900">
                                        <?= clean_input($ip['eo_name']) ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-500">
                                        <?= clean_input($ip['event_category']) ?> &bull; <?= number_format($ip['attendee_estimate']) ?> org
                                    </td>
                                    <td class="px-5 py-3.5 font-extrabold text-slate-900">
                                        <?= format_rupiah($ip['proposed_amount']) ?>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <?= render_proposal_status_badge($ip['status']) ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-right space-x-2">
                                        <?php if ($ip['status'] === 'Negosiasi'): ?>
                                            <a href="<?= BASE_URL ?>/negotiation.php?id=1" class="px-3 py-1.5 rounded-lg bg-brand-purple text-white font-bold hover:opacity-90 transition inline-flex items-center gap-1.5">
                                                <i class="fa-solid fa-comments-dollar text-[10px]"></i> Buka Room
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>/proposal-detail.php?id=<?= $ip['id'] ?>" class="px-3 py-1.5 rounded-lg bg-indigo-50 text-brand-indigo font-bold hover:bg-brand-indigo hover:text-white transition">
                                                Review & Tanggapi
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
