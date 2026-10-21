<?php
// includes/header.php - Template Header Utama Reusable
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$currentUser = current_user();
$currentRole = current_role();
$pageTitle = isset($page_title) ? $page_title . " | SponsorBridge" : "SponsorBridge - Marketplace Sponsorship Event & Brand";
$unreadNotifs = $currentUser ? get_unread_notifications_count($currentUser['id']) : 0;
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean_input($pageTitle) ?></title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!-- Tailwind CSS CDN dengan Konfigurasi Brand SponsorBridge -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            950: '#070d1e',
                            900: '#0b1329',
                            800: '#0f172a',
                            700: '#1e293b',
                            600: '#334155'
                        },
                        brand: {
                            purple: '#7c3aed',
                            indigo: '#4f46e5',
                            blue: '#2563eb',
                            sky: '#0284c7'
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Chart.js & Confetti CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="h-full flex flex-col text-slate-800 antialiased bg-slate-50">

    <!-- TOP DEMO ROLE SWITCHER BAR (Kemudahan Evaluasi Dosen/Penguji) -->
    <div class="bg-navy-950 border-b border-navy-700 text-slate-300 text-xs py-2 px-4 select-none z-50">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-brand-indigo/20 text-brand-indigo border border-brand-indigo/30 font-semibold uppercase tracking-wider text-[10px]">
                    <i class="fa-solid fa-graduation-cap"></i> BINUS Demo Mode
                </span>
                <span class="hidden sm:inline text-slate-400">Pilih Peran untuk Simulasi:</span>
            </div>
            <div class="flex items-center gap-1.5 overflow-x-auto text-[11px]">
                <a href="<?= BASE_URL ?>/switch_role.php?role=eo" class="px-2.5 py-1 rounded transition flex items-center gap-1.5 <?= $currentRole === 'eo' ? 'bg-brand-indigo text-white font-bold shadow-sm' : 'bg-navy-800 text-slate-300 hover:bg-navy-700' ?>">
                    <i class="fa-solid fa-calendar-check text-[10px]"></i> 1. Event Organizer (Mahakarya)
                </a>
                <a href="<?= BASE_URL ?>/switch_role.php?role=sponsor" class="px-2.5 py-1 rounded transition flex items-center gap-1.5 <?= $currentRole === 'sponsor' ? 'bg-brand-purple text-white font-bold shadow-sm' : 'bg-navy-800 text-slate-300 hover:bg-navy-700' ?>">
                    <i class="fa-solid fa-building text-[10px]"></i> 2. Sponsor / Brand (TelkoNusa)
                </a>
                <a href="<?= BASE_URL ?>/switch_role.php?role=admin" class="px-2.5 py-1 rounded transition flex items-center gap-1.5 <?= $currentRole === 'admin' ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'bg-navy-800 text-slate-300 hover:bg-navy-700' ?>">
                    <i class="fa-solid fa-shield-halved text-[10px]"></i> 3. Admin Panel
                </a>
                <a href="<?= BASE_URL ?>/switch_role.php?role=guest" class="px-2 py-1 rounded transition flex items-center gap-1 <?= $currentRole === 'guest' ? 'bg-slate-700 text-white font-bold' : 'text-slate-400 hover:text-white' ?>" title="Lihat sebagai pengunjung umum">
                    <i class="fa-solid fa-arrow-right-from-bracket text-[10px]"></i> Publik
                </a>
            </div>
        </div>
    </div>

    <?php
    // Flash Messages Alert
    $flash = get_flash();
    if ($flash):
        $alertBg = [
            'success' => 'bg-emerald-50 border-emerald-300 text-emerald-800',
            'error'   => 'bg-rose-50 border-rose-300 text-rose-800',
            'warning' => 'bg-amber-50 border-amber-300 text-amber-800',
            'info'    => 'bg-blue-50 border-blue-300 text-blue-800'
        ][$flash['type']] ?? 'bg-slate-50 border-slate-300 text-slate-800';
        
        $alertIcon = [
            'success' => 'fa-circle-check text-emerald-500',
            'error'   => 'fa-circle-exclamation text-rose-500',
            'warning' => 'fa-triangle-exclamation text-amber-500',
            'info'    => 'fa-circle-info text-blue-500'
        ][$flash['type']] ?? 'fa-bell text-slate-500';
    ?>
    <div class="max-w-7xl mx-auto px-4 mt-3 w-full">
        <div class="flex items-center justify-between p-3.5 rounded-xl border <?= $alertBg ?> shadow-sm">
            <div class="flex items-center gap-2.5 text-sm">
                <i class="fa-solid <?= $alertIcon ?> text-base"></i>
                <span><?= $flash['message'] ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>
