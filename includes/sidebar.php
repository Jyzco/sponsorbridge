<?php
// includes/sidebar.php - Navigasi Sidebar Modern Dashboard
$currentScript = basename($_SERVER['PHP_SELF']);
$currentRole = current_role();
?>

<!-- Mobile Overlay -->
<div id="sidebarOverlay" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-30 lg:hidden"></div>

<!-- Sidebar Container -->
<aside id="sidebar" class="fixed top-16 bottom-0 left-0 w-64 bg-navy-900 text-slate-300 border-r border-navy-700 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col justify-between">
    
    <div class="py-5 px-4 overflow-y-auto flex-1">
        
        <!-- User Badge Mini Card -->
        <?php if ($currentUser): ?>
        <div class="mb-6 p-3 rounded-xl bg-navy-800/80 border border-navy-700 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-indigo to-brand-purple flex items-center justify-center font-bold text-white text-sm shadow-md">
                <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-white truncate"><?= clean_input($currentUser['name']) ?></p>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="w-2 h-2 rounded-full <?= $currentUser['is_verified'] ? 'bg-emerald-400' : 'bg-amber-400' ?>"></span>
                    <span class="text-[10px] text-slate-400 font-medium">
                        <?= $currentUser['is_verified'] ? 'Terverifikasi' : 'Belum Diverifikasi' ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- NAV SECTION PER ROLE -->
        <div class="space-y-1">
            <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Navigasi Utama</p>
            
            <a href="<?= $currentRole === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/dashboard.php' ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= ($currentScript == 'dashboard.php' || $currentScript == 'index.php' && strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? 'bg-brand-indigo text-white shadow-md' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                <i class="fa-solid fa-gauge-high w-4 text-center"></i>
                <span>Dashboard</span>
            </a>

            <?php if ($currentRole === 'eo'): ?>
                <!-- EO SPECIFIC MENU -->
                <a href="<?= BASE_URL ?>/create-event.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'create-event.php' ? 'bg-brand-indigo text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-calendar-plus w-4 text-center text-emerald-400"></i>
                    <span>Buat Event Baru</span>
                </a>

                <a href="<?= BASE_URL ?>/proposal-builder.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'proposal-builder.php' ? 'bg-brand-indigo text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-wand-magic-sparkles w-4 text-center text-brand-purple"></i>
                    <span>Smart Proposal Builder</span>
                </a>

                <a href="<?= BASE_URL ?>/proposals.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'proposals.php' ? 'bg-brand-indigo text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-file-invoice-dollar w-4 text-center"></i>
                        <span>Pelacak Proposal</span>
                    </div>
                    <span class="text-[10px] bg-navy-700 px-1.5 py-0.5 rounded text-slate-300 font-bold">7</span>
                </a>

                <a href="<?= BASE_URL ?>/negotiation.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'negotiation.php' ? 'bg-brand-indigo text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-comments-dollar w-4 text-center text-amber-400"></i>
                        <span>Negotiation Room</span>
                    </div>
                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">E2EE</span>
                </a>

                <a href="<?= BASE_URL ?>/escrow.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= ($currentScript == 'escrow.php' || $currentScript == 'contract.php') ? 'bg-brand-indigo text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-file-contract w-4 text-center text-blue-400"></i>
                    <span>Kontrak & Escrow</span>
                </a>

                <a href="<?= BASE_URL ?>/sponsors.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'sponsors.php' ? 'bg-brand-indigo text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-building-circle-check w-4 text-center"></i>
                    <span>Marketplace Sponsor</span>
                </a>

                <a href="<?= BASE_URL ?>/roi-calculator.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'roi-calculator.php' ? 'bg-brand-indigo text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-calculator w-4 text-center text-indigo-400"></i>
                    <span>Kalkulator ROI</span>
                </a>

                <a href="<?= BASE_URL ?>/post-event-report.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= ($currentScript == 'post-event-report.php' || $currentScript == 'analytics.php') ? 'bg-brand-indigo text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-chart-pie w-4 text-center text-teal-400"></i>
                    <span>Laporan & Analitik</span>
                </a>

            <?php elseif ($currentRole === 'sponsor'): ?>
                <!-- SPONSOR SPECIFIC MENU -->
                <a href="<?= BASE_URL ?>/events.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'events.php' ? 'bg-brand-purple text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-compass w-4 text-center text-sky-400"></i>
                    <span>Event Discovery</span>
                </a>

                <a href="<?= BASE_URL ?>/proposals.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'proposals.php' ? 'bg-brand-purple text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-inbox w-4 text-center"></i>
                        <span>Proposal Masuk</span>
                    </div>
                    <span class="text-[10px] bg-brand-purple/40 px-1.5 py-0.5 rounded text-white font-bold">Baru</span>
                </a>

                <a href="<?= BASE_URL ?>/negotiation.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'negotiation.php' ? 'bg-brand-purple text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-comments-dollar w-4 text-center text-amber-400"></i>
                        <span>Negotiation Room</span>
                    </div>
                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">E2EE</span>
                </a>

                <a href="<?= BASE_URL ?>/escrow.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= ($currentScript == 'escrow.php' || $currentScript == 'contract.php') ? 'bg-brand-purple text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-vault w-4 text-center text-emerald-400"></i>
                    <span>Kontrak & Escrow</span>
                </a>

                <a href="<?= BASE_URL ?>/analytics.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'analytics.php' ? 'bg-brand-purple text-white' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-chart-line w-4 text-center text-brand-purple"></i>
                    <span>Impact Analytics</span>
                </a>

            <?php elseif ($currentRole === 'admin'): ?>
                <!-- ADMIN SPECIFIC MENU -->
                <a href="<?= BASE_URL ?>/admin/users.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'users.php' ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-user-check w-4 text-center text-amber-400"></i>
                        <span>Verifikasi Akun</span>
                    </div>
                    <span class="text-[10px] bg-amber-500/30 text-amber-300 px-1.5 py-0.5 rounded font-bold">2 Pending</span>
                </a>

                <a href="<?= BASE_URL ?>/admin/events.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'events.php' ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-calendar-days w-4 text-center"></i>
                    <span>Moderasi Event</span>
                </a>

                <a href="<?= BASE_URL ?>/admin/disputes.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'disputes.php' ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-scale-balanced w-4 text-center text-rose-400"></i>
                    <span>Sengketa Escrow</span>
                </a>

                <a href="<?= BASE_URL ?>/admin/negotiations.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'negotiations.php' ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-lock w-4 text-center text-emerald-400"></i>
                    <span>Status Room (E2EE)</span>
                </a>

                <a href="<?= BASE_URL ?>/admin/categories.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $currentScript == 'categories.php' ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-300 hover:bg-navy-800 hover:text-white' ?>">
                    <i class="fa-solid fa-tags w-4 text-center"></i>
                    <span>Taksonomi & CSR</span>
                </a>
            <?php endif; ?>

            <div class="pt-4 mt-4 border-t border-navy-700">
                <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Akun</p>
                <a href="<?= BASE_URL ?>/profile.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium text-slate-300 hover:bg-navy-800 hover:text-white">
                    <i class="fa-solid fa-id-card w-4 text-center"></i>
                    <span>Profil & Portofolio</span>
                </a>
                <a href="<?= BASE_URL ?>/logout.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium text-rose-400 hover:bg-rose-950/40 hover:text-rose-300">
                    <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center"></i>
                    <span>Keluar</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Escrow & Security Trust Seal in Sidebar -->
    <div class="p-4 border-t border-navy-700 bg-navy-950/60">
        <div class="flex items-center gap-2 text-emerald-400 text-xs font-bold mb-1">
            <i class="fa-solid fa-shield-check"></i> Escrow Terlindungi
        </div>
        <p class="text-[10px] text-slate-400 leading-tight">
            Pencairan dana bertahap 30% - 40% - 30% melalui rekening bersama terverifikasi.
        </p>
    </div>
</aside>
