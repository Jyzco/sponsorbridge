<?php
// admin/events.php - Moderasi Event & Kurasi Unggulan oleh Administrator
$page_title = "Moderasi Event";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

require_role('admin');

// Toggle Featured Event
if (isset($_GET['toggle_featured'])) {
    $eId = (int)$_GET['toggle_featured'];
    $conn->query("UPDATE events SET is_featured = IF(is_featured=1, 0, 1) WHERE id = {$eId}");
    set_flash('success', 'Status unggulan event berhasil diubah.');
    header('Location: ' . BASE_URL . '/admin/events.php');
    exit;
}

$events = $conn->query("
    SELECT e.*, u.name as eo_name 
    FROM events e 
    JOIN users u ON e.eo_id = u.id 
    ORDER BY e.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto space-y-6">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-amber-500">Katalog & Kurasi</span>
                    <h1 class="text-2xl font-black text-navy-900 tracking-tight mt-1">Moderasi Event Platform</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Kelola kelayakan penayangan event dan sematkan badge unggulan untuk landing page.</p>
                </div>
            </div>

            <div class="sb-card overflow-hidden bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5">Judul Event</th>
                                <th class="px-5 py-3.5">Penyelenggara (EO)</th>
                                <th class="px-5 py-3.5">Target Peserta</th>
                                <th class="px-5 py-3.5">Target Dana</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5">Kurasi Unggulan</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($events as $e): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-5 py-3.5">
                                    <div class="font-extrabold text-navy-900 line-clamp-1"><?= clean_input($e['title']) ?></div>
                                    <span class="text-[10px] text-slate-400"><?= clean_input($e['category']) ?> &bull; <?= clean_input($e['city']) ?></span>
                                </td>
                                <td class="px-5 py-3.5 font-bold text-slate-700">
                                    <?= clean_input($e['eo_name']) ?>
                                </td>
                                <td class="px-5 py-3.5 font-semibold text-slate-900">
                                    <?= number_format($e['attendee_estimate']) ?> org
                                </td>
                                <td class="px-5 py-3.5 font-extrabold text-emerald-600">
                                    <?= format_rupiah($e['sponsorship_target']) ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase">
                                        <?= clean_input($e['status']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <a href="?toggle_featured=<?= $e['id'] ?>" class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1 transition <?= $e['is_featured'] ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-400 hover:text-slate-600' ?>">
                                        <i class="fa-solid fa-star <?= $e['is_featured'] ? 'text-amber-500' : '' ?>"></i>
                                        <?= $e['is_featured'] ? 'Unggulan Landing' : 'Biasa' ?>
                                    </a>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <a href="<?= BASE_URL ?>/event-detail.php?id=<?= $e['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold text-brand-indigo bg-indigo-50 hover:bg-brand-indigo hover:text-white transition">
                                        Lihat
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
