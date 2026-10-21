<?php
// includes/navbar.php - Navigasi Header Atas
$notifications = $currentUser ? get_user_notifications($currentUser['id'], 4) : [];
?>
<nav class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Logo & Brand -->
            <div class="flex items-center gap-8">
                <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-2.5 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-indigo via-brand-purple to-indigo-500 flex items-center justify-center text-white shadow-md shadow-brand-indigo/25 group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-handshake-angle text-lg"></i>
                    </div>
                    <div>
                        <div class="text-xl font-black tracking-tight text-navy-900 leading-none">
                            Sponsor<span class="text-brand-indigo">Bridge</span>
                        </div>
                        <div class="text-[10px] font-semibold text-slate-400 tracking-wider uppercase">B2B Sponsorship Hub</div>
                    </div>
                </a>

                <!-- Desktop Nav Links -->
                <div class="hidden lg:flex items-center gap-6 text-sm font-semibold text-slate-600">
                    <a href="<?= BASE_URL ?>/events.php" class="hover:text-brand-indigo transition flex items-center gap-1.5 <?= basename($_SERVER['PHP_SELF']) == 'events.php' ? 'text-brand-indigo font-bold' : '' ?>">
                        <i class="fa-solid fa-compass text-slate-400"></i> Eksplorasi Event
                    </a>
                    <a href="<?= BASE_URL ?>/sponsors.php" class="hover:text-brand-indigo transition flex items-center gap-1.5 <?= basename($_SERVER['PHP_SELF']) == 'sponsors.php' ? 'text-brand-indigo font-bold' : '' ?>">
                        <i class="fa-solid fa-briefcase text-slate-400"></i> Marketplace Sponsor
                    </a>
                    <a href="<?= BASE_URL ?>/roi-calculator.php" class="hover:text-brand-indigo transition flex items-center gap-1.5 <?= basename($_SERVER['PHP_SELF']) == 'roi-calculator.php' ? 'text-brand-indigo font-bold' : '' ?>">
                        <i class="fa-solid fa-calculator text-slate-400"></i> Kalkulator ROI
                    </a>
                    <a href="<?= BASE_URL ?>/index.php#cara-kerja" class="hover:text-brand-indigo transition">
                        Cara Kerja
                    </a>
                </div>
            </div>

            <!-- Right Actions (Auth / User Profile / Dashboard) -->
            <div class="flex items-center gap-3">
                <?php if ($currentUser): ?>
                    <!-- Shortcut Ke Dashboard -->
                    <a href="<?= $currentUser['role'] === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/dashboard.php' ?>" class="hidden md:inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-sm font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        <i class="fa-solid fa-gauge text-slate-500"></i> Dashboard
                    </a>

                    <!-- Notification Bell & Dropdown -->
                    <div class="relative">
                        <button id="notifDropdownBtn" type="button" class="relative p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition focus:outline-none">
                            <i class="fa-regular fa-bell text-lg"></i>
                            <?php if ($unreadNotifs > 0): ?>
                                <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-rose-500 rounded-full ring-2 ring-white"></span>
                            <?php endif; ?>
                        </button>

                        <!-- Dropdown Panel -->
                        <div id="notifDropdownMenu" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-50">
                            <div class="px-4 py-2 border-b border-slate-100 flex items-center justify-between">
                                <span class="font-bold text-sm text-navy-900">Notifikasi</span>
                                <span class="text-xs bg-brand-indigo/10 text-brand-indigo font-semibold px-2 py-0.5 rounded-full"><?= $unreadNotifs ?> Baru</span>
                            </div>
                            <div class="max-h-72 overflow-y-auto divide-y divide-slate-50">
                                <?php if (empty($notifications)): ?>
                                    <div class="py-6 text-center text-xs text-slate-400">Belum ada notifikasi baru</div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <a href="<?= !empty($notif['link']) ? BASE_URL . '/' . ltrim($notif['link'], '/') : '#' ?>" class="block px-4 py-3 hover:bg-slate-50 transition">
                                            <p class="text-xs font-bold text-slate-800 line-clamp-1"><?= clean_input($notif['title']) ?></p>
                                            <p class="text-xs text-slate-500 line-clamp-2 mt-0.5"><?= clean_input($notif['message']) ?></p>
                                            <span class="text-[10px] text-slate-400 mt-1 block"><i class="fa-regular fa-clock mr-1"></i><?= time_ago($notif['created_at']) ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="px-4 py-2 border-t border-slate-100 text-center">
                                <a href="<?= BASE_URL ?>/notifications.php" class="text-xs font-semibold text-brand-indigo hover:underline">Lihat Semua Notifikasi</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="relative">
                        <button id="userMenuBtn" type="button" class="flex items-center gap-2.5 p-1 rounded-lg hover:bg-slate-100 transition focus:outline-none">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-navy-800 to-brand-indigo text-white font-bold text-xs flex items-center justify-center ring-2 ring-slate-200">
                                <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                            </div>
                            <div class="hidden md:block text-left">
                                <p class="text-xs font-bold text-slate-800 leading-tight truncate max-w-[120px]"><?= clean_input($currentUser['name']) ?></p>
                                <span class="text-[10px] font-semibold uppercase px-1.5 py-0.2 rounded <?= $currentUser['role'] === 'admin' ? 'bg-amber-100 text-amber-800' : ($currentUser['role'] === 'eo' ? 'bg-indigo-100 text-indigo-800' : 'bg-purple-100 text-purple-800') ?>">
                                    <?= $currentUser['role'] ?>
                                </span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 hidden md:block"></i>
                        </button>

                        <!-- User Dropdown Menu -->
                        <div id="userMenuDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-50">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs font-bold text-slate-900"><?= clean_input($currentUser['name']) ?></p>
                                <p class="text-[11px] text-slate-500 truncate"><?= clean_input($currentUser['email']) ?></p>
                            </div>
                            <a href="<?= $currentUser['role'] === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/dashboard.php' ?>" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-gauge text-slate-400 w-4"></i> Dashboard
                            </a>
                            <a href="<?= BASE_URL ?>/profile.php" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-id-card text-slate-400 w-4"></i> Profil Saya
                            </a>
                            <a href="<?= BASE_URL ?>/proposals.php" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-file-invoice-dollar text-slate-400 w-4"></i> Pelacak Proposal
                            </a>
                            <div class="border-t border-slate-100 my-1"></div>
                            <a href="<?= BASE_URL ?>/logout.php" class="flex items-center gap-2.5 px-4 py-2 text-xs text-rose-600 hover:bg-rose-50">
                                <i class="fa-solid fa-arrow-right-from-bracket text-rose-400 w-4"></i> Keluar
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Tombol Auth untuk Guest -->
                    <a href="<?= BASE_URL ?>/login.php" class="text-sm font-semibold text-slate-700 hover:text-brand-indigo px-3 py-2">
                        Masuk
                    </a>
                    <a href="<?= BASE_URL ?>/register.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-brand-indigo to-brand-purple hover:opacity-95 shadow-md shadow-brand-indigo/25 transition">
                        <i class="fa-solid fa-user-plus text-xs"></i> Registrasi
                    </a>
                <?php endif; ?>

                <!-- Mobile Menu Button -->
                <button id="mobileMenuBtn" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
            </div>
        </div>
    </div>
</nav>
