<?php
// notifications.php - Pusat Notifikasi Pengguna
$page_title = "Pusat Notifikasi";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

require_login();
$currentUser = current_user();
$userId = $currentUser['id'];

// Handler tandai semua telah dibaca
if (isset($_GET['mark_all_read'])) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = {$userId}");
    header('Location: ' . BASE_URL . '/notifications.php');
    exit;
}

$stmtNotifs = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmtNotifs->bind_param("i", $userId);
$stmtNotifs->execute();
$allNotifs = $stmtNotifs->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-3xl mx-auto space-y-6">
            
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-navy-900 tracking-tight">Semua Notifikasi</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Pembaruan penawaran, aktivitas negosiasi, dan pencairan escrow.</p>
                </div>
                <a href="?mark_all_read=1" class="text-xs font-bold text-brand-indigo hover:underline">
                    <i class="fa-solid fa-check-double mr-1"></i> Tandai Semua Dibaca
                </a>
            </div>

            <div class="sb-card divide-y divide-slate-100 bg-white overflow-hidden">
                <?php if (empty($allNotifs)): ?>
                    <div class="p-12 text-center text-xs text-slate-400">Tidak ada notifikasi untuk akun Anda.</div>
                <?php else: ?>
                    <?php foreach ($allNotifs as $n): ?>
                    <a href="<?= !empty($n['link']) ? BASE_URL . '/' . ltrim($n['link'], '/') : '#' ?>" class="block p-4 hover:bg-slate-50 transition flex items-start justify-between gap-4 <?= $n['is_read'] ? 'opacity-70' : 'bg-indigo-50/20' ?>">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full <?= $n['is_read'] ? 'bg-slate-100 text-slate-400' : 'bg-brand-indigo/10 text-brand-indigo' ?> flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-bell text-xs"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-extrabold text-navy-900"><?= clean_input($n['title']) ?></h4>
                                <p class="text-xs text-slate-600 mt-0.5 leading-relaxed"><?= clean_input($n['message']) ?></p>
                                <span class="text-[10px] text-slate-400 mt-1 block"><i class="fa-regular fa-clock mr-1"></i><?= time_ago($n['created_at']) ?></span>
                            </div>
                        </div>
                        <?php if (!$n['is_read']): ?>
                            <span class="w-2 h-2 rounded-full bg-brand-indigo shrink-0 mt-2"></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
