<?php
// admin/index.php - Dashboard Administrator Utama
$page_title = "Panel Administrator";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

require_role('admin');

// Statistik Platform
$totalUsers = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetch_row()[0];
$totalEo = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'eo'")->fetch_row()[0];
$totalSponsor = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'sponsor'")->fetch_row()[0];
$pendingVerif = (int)$conn->query("SELECT COUNT(*) FROM users WHERE is_verified = 0 AND role != 'admin'")->fetch_row()[0];

$totalEvents = (int)$conn->query("SELECT COUNT(*) FROM events")->fetch_row()[0];
$totalProposals = (int)$conn->query("SELECT COUNT(*) FROM proposals")->fetch_row()[0];
$totalContracts = (int)$conn->query("SELECT COUNT(*) FROM contracts")->fetch_row()[0];
$totalEscrowVol = (float)$conn->query("SELECT COALESCE(SUM(agreement_amount), 0) FROM contracts")->fetch_row()[0];
$totalDisputes = (int)$conn->query("SELECT COUNT(*) FROM disputes WHERE status = 'open'")->fetch_row()[0];

// Pengguna menunggu verifikasi terbaru
$pendingUsers = $conn->query("SELECT * FROM users WHERE is_verified = 0 AND role != 'admin' ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Kontrak aktif terbaru
$recentContracts = $conn->query("
    SELECT c.*, e.title as event_title, eo.name as eo_name, sp.name as sponsor_name
    FROM contracts c
    JOIN proposals p ON c.proposal_id = p.id
    JOIN events e ON p.event_id = e.id
    JOIN users eo ON p.eo_id = eo.id
    JOIN users sp ON p.sponsor_id = sp.id
    ORDER BY c.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto space-y-6">
            
            <!-- Admin Top Banner -->
            <div class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 rounded-2xl text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-500 text-slate-950">
                            Super Admin
                        </span>
                        <span class="text-xs text-slate-300">Trust & Safety Platform</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-white">SponsorBridge Control Center</h1>
                    <p class="text-xs text-slate-300 mt-0.5">Kelola verifikasi legalitas, moderasi kontrak, resolusi sengketa, dan kepatuhan escrow.</p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="<?= BASE_URL ?>/admin/users.php" class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-950 bg-amber-400 hover:bg-amber-300 transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-user-check"></i> Verifikasi Pengguna (<?= $pendingVerif ?>)
                    </a>
                </div>
            </div>

            <!-- 6 Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
                <div class="sb-card p-4 bg-white">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Pengguna</span>
                    <div class="text-xl font-black text-navy-900 mt-1"><?= $totalUsers ?></div>
                    <span class="text-[10px] text-slate-400 mt-0.5 block"><?= $totalEo ?> EO &bull; <?= $totalSponsor ?> Brand</span>
                </div>

                <div class="sb-card p-4 bg-white">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Event</span>
                    <div class="text-xl font-black text-navy-900 mt-1"><?= $totalEvents ?></div>
                    <span class="text-[10px] text-emerald-600 font-bold mt-0.5 block">Terdaftar aktif</span>
                </div>

                <div class="sb-card p-4 bg-white">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Proposal</span>
                    <div class="text-xl font-black text-navy-900 mt-1"><?= $totalProposals ?></div>
                    <span class="text-[10px] text-brand-indigo font-bold mt-0.5 block">Dalam pipeline</span>
                </div>

                <div class="sb-card p-4 bg-white">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Kontrak Resmi</span>
                    <div class="text-xl font-black text-navy-900 mt-1"><?= $totalContracts ?></div>
                    <span class="text-[10px] text-teal-600 font-bold mt-0.5 block">MoU Disepakati</span>
                </div>

                <div class="sb-card p-4 bg-white">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Volume Escrow</span>
                    <div class="text-base font-black text-emerald-700 mt-1 truncate"><?= format_rupiah($totalEscrowVol) ?></div>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Rekening Bersama</span>
                </div>

                <div class="sb-card p-4 bg-white">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Laporan Sengketa</span>
                    <div class="text-xl font-black <?= $totalDisputes > 0 ? 'text-rose-600' : 'text-slate-400' ?> mt-1"><?= $totalDisputes ?></div>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Memerlukan review</span>
                </div>
            </div>

            <!-- Verification Queue & Contracts Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Antrian Verifikasi Pengguna -->
                <div class="sb-card p-6 bg-white space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-black text-navy-900 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-user-clock text-amber-500"></i> Antrian Verifikasi Legalitas
                        </h3>
                        <a href="<?= BASE_URL ?>/admin/users.php" class="text-xs font-bold text-brand-indigo hover:underline">Semua &rarr;</a>
                    </div>

                    <div class="divide-y divide-slate-100 text-xs">
                        <?php if (empty($pendingUsers)): ?>
                            <div class="py-6 text-center text-slate-400">Tidak ada pengguna yang menunggu verifikasi.</div>
                        <?php else: ?>
                            <?php foreach ($pendingUsers as $pu): ?>
                            <div class="py-3 flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-extrabold text-navy-900"><?= clean_input($pu['name']) ?></div>
                                    <div class="text-[11px] text-slate-500"><?= clean_input($pu['email']) ?> &bull; Role: <strong class="uppercase text-slate-700"><?= $pu['role'] ?></strong></div>
                                </div>
                                <a href="<?= BASE_URL ?>/admin/users.php?verify_id=<?= $pu['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition">
                                    Verifikasi
                                </a>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Kontrak MoU Berjalan -->
                <div class="sb-card p-6 bg-white space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-black text-navy-900 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-file-contract text-teal-600"></i> Transaksi Kontrak Terbaru
                        </h3>
                        <a href="<?= BASE_URL ?>/admin/events.php" class="text-xs font-bold text-brand-indigo hover:underline">Semua &rarr;</a>
                    </div>

                    <div class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($recentContracts as $rc): ?>
                        <div class="py-3 flex items-center justify-between gap-3">
                            <div>
                                <div class="font-extrabold text-navy-900 line-clamp-1"><?= clean_input($rc['event_title']) ?></div>
                                <div class="text-[11px] text-slate-500"><?= clean_input($rc['eo_name']) ?> &harr; <?= clean_input($rc['sponsor_name']) ?></div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-black text-slate-900"><?= format_rupiah($rc['agreement_amount']) ?></div>
                                <?= render_contract_status_badge($rc['status']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- E2EE Privacy Architecture Guarantee in Admin -->
            <div class="sb-card p-6 bg-slate-900 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xl shrink-0 border border-emerald-500/30">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-white">Privasi Terjamin: Admin Tanpa Akses Pesan E2EE</h4>
                        <p class="text-xs text-slate-300 mt-0.5 max-w-2xl leading-relaxed">
                            Sesuai standar arsitektur keamanan B2B, isi ruang obrolan dienkripsi langsung di sisi klien pengguna (End-to-End Encryption). Administrator hanya dapat memantau metadata log room tanpa membaca isi percakapan privat.
                        </p>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/admin/negotiations.php" class="px-4 py-2 rounded-xl text-xs font-bold bg-navy-800 text-emerald-400 border border-emerald-500/30 hover:bg-navy-700 transition shrink-0">
                    Lihat Metadata Room
                </a>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
