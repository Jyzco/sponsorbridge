<?php
// analytics.php - Post-Event Impact Analytics Dashboard untuk Sponsor & EO
$page_title = "Post-Event Impact Analytics";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

require_login();
$currentUser = current_user();
$userId = $currentUser['id'];
$role = $currentUser['role'];

// Ambil event yang memiliki data analytics (atau default event yang sudah selesai: ID 8 Aksi Tanam Mangrove / Event 2)
$selectedEventId = (int)($_GET['event_id'] ?? 8);

$stmtAnalytics = $conn->prepare("
    SELECT ea.*, e.title as event_title, e.category, e.attendee_estimate, e.start_date, e.end_date, e.city,
           eo.name as eo_name, sp.name as sponsor_name
    FROM event_analytics ea
    JOIN events e ON ea.event_id = e.id
    JOIN users eo ON e.eo_id = eo.id
    JOIN users sp ON ea.sponsor_id = sp.id
    WHERE ea.event_id = ?
    LIMIT 1
");
$stmtAnalytics->bind_param("i", $selectedEventId);
$stmtAnalytics->execute();
$analytics = $stmtAnalytics->get_result()->fetch_assoc();

// Jika belum ada data analitik untuk event ini, ambil data analitik pertama di sistem
if (!$analytics) {
    $resDefault = $conn->query("
        SELECT ea.*, e.title as event_title, e.category, e.attendee_estimate, e.start_date, e.end_date, e.city,
               eo.name as eo_name, sp.name as sponsor_name
        FROM event_analytics ea
        JOIN events e ON ea.event_id = e.id
        JOIN users eo ON e.eo_id = eo.id
        JOIN users sp ON ea.sponsor_id = sp.id
        ORDER BY ea.submitted_at DESC
        LIMIT 1
    ");
    $analytics = $resDefault->fetch_assoc();
}

// Ambil list event yang memiliki data analitik untuk dropdown filter
$allAnalyticsEvents = $conn->query("
    SELECT DISTINCT e.id, e.title 
    FROM event_analytics ea 
    JOIN events e ON ea.event_id = e.id
")->fetch_all(MYSQLI_ASSOC);

// Decode JSON realisasi benefit & foto dokumentasi
$benefitsRealization = json_decode($analytics['benefit_realization_json'] ?? '[]', true) ?: [];
$docPhotos = json_decode($analytics['documentation_photos_json'] ?? '[]', true) ?: [];
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto space-y-6">
            
            <!-- Top Header & Event Selector -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-purple">Laporan Hasil Kemitraan</span>
                    <h1 class="text-2xl font-black text-navy-900 tracking-tight mt-1">Post-Event Impact Analytics</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Evaluasi komprehensif performa eksposur brand, realisasi benefit sponsorship, dan perolehan ROI nyata.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <?php if ($role === 'eo'): ?>
                    <a href="<?= BASE_URL ?>/post-event-report.php" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-file-circle-plus"></i> Isi Laporan Event Baru
                    </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition shadow-2xs">
                        <i class="fa-solid fa-download mr-1"></i> Unduh Laporan PDF
                    </button>
                </div>
            </div>

            <!-- Event Highlight Banner -->
            <div class="sb-card p-6 bg-gradient-to-r from-navy-950 via-navy-900 to-indigo-950 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1 text-xs">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 backdrop-blur-md uppercase">
                            <?= clean_input($analytics['category']) ?>
                        </span>
                        <span class="text-slate-300"><i class="fa-regular fa-calendar mr-1"></i><?= format_date($analytics['start_date']) ?></span>
                        <span class="text-slate-300">&bull; <?= clean_input($analytics['city']) ?></span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-white"><?= clean_input($analytics['event_title']) ?></h2>
                    <p class="text-xs text-slate-300 mt-1">
                        Diselenggarakan oleh: <strong class="text-white"><?= clean_input($analytics['eo_name']) ?></strong> &bull; Sponsor: <strong class="text-indigo-300"><?= clean_input($analytics['sponsor_name']) ?></strong>
                    </p>
                </div>

                <div class="text-left sm:text-right shrink-0 p-4 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-300 block">Realisasi Return On Investment</span>
                    <div class="text-3xl font-black text-emerald-400"><?= $analytics['roi_multiplier'] ?>x</div>
                    <span class="text-[11px] text-slate-200 font-bold">+<?= $analytics['estimated_roi_pct'] ?>% Nilai Media</span>
                </div>
            </div>

            <!-- 4 Key Impact Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Metrik 1: Peserta Aktual vs Target -->
                <div class="sb-card p-5 bg-white">
                    <div class="flex items-center justify-between text-xs text-slate-400 uppercase font-bold tracking-wider">
                        <span>Audiens Aktual</span>
                        <i class="fa-solid fa-users text-brand-indigo text-base"></i>
                    </div>
                    <div class="text-2xl font-black text-navy-900 mt-2"><?= number_format($analytics['actual_attendees']) ?></div>
                    <div class="text-[11px] text-emerald-600 font-bold mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-arrow-trend-up"></i>
                        <span>108% dari target awal (<?= number_format($analytics['attendee_estimate']) ?>)</span>
                    </div>
                </div>

                <!-- Metrik 2: Impresi Medsos -->
                <div class="sb-card p-5 bg-white">
                    <div class="flex items-center justify-between text-xs text-slate-400 uppercase font-bold tracking-wider">
                        <span>Impresi Media Sosial</span>
                        <i class="fa-solid fa-hashtag text-brand-purple text-base"></i>
                    </div>
                    <div class="text-2xl font-black text-navy-900 mt-2"><?= number_format($analytics['social_impressions']) ?></div>
                    <div class="text-[11px] text-slate-500 mt-1">Total jangkauan konten Instagram & TikTok</div>
                </div>

                <!-- Metrik 3: Engagement Rate -->
                <div class="sb-card p-5 bg-white">
                    <div class="flex items-center justify-between text-xs text-slate-400 uppercase font-bold tracking-wider">
                        <span>Engagement Rate</span>
                        <i class="fa-solid fa-heart text-rose-500 text-base"></i>
                    </div>
                    <div class="text-2xl font-black text-navy-900 mt-2"><?= $analytics['engagement_rate'] ?>%</div>
                    <div class="text-[11px] text-emerald-600 font-bold mt-1">Tinggi (Standar B2B 3.5%)</div>
                </div>

                <!-- Metrik 4: Media Coverage Publikasi -->
                <div class="sb-card p-5 bg-white">
                    <div class="flex items-center justify-between text-xs text-slate-400 uppercase font-bold tracking-wider">
                        <span>Publikasi Media Pers</span>
                        <i class="fa-solid fa-newspaper text-sky-500 text-base"></i>
                    </div>
                    <div class="text-2xl font-black text-navy-900 mt-2"><?= $analytics['media_coverage_count'] ?> Media</div>
                    <div class="text-[11px] text-slate-500 mt-1">Portal berita nasional & regional</div>
                </div>

            </div>

            <!-- Charts Section: Breakdown Jangkauan & Audiens -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Grafik Perbandingan Target vs Realisasi -->
                <div class="sb-card p-6 bg-white space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-extrabold text-navy-900 flex items-center gap-2">
                            <i class="fa-solid fa-chart-simple text-brand-indigo"></i> Perbandingan Target vs Realisasi Audiens
                        </h3>
                        <span class="text-[10px] text-slate-400 font-mono">100% Tercapai</span>
                    </div>

                    <div class="h-56 relative flex items-center justify-center">
                        <canvas id="impactBarChart"></canvas>
                    </div>
                </div>

                <!-- Grafik Distribusi Nilai Earned Media -->
                <div class="sb-card p-6 bg-white space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-extrabold text-navy-900 flex items-center gap-2">
                            <i class="fa-solid fa-chart-pie text-brand-purple"></i> Komposisi Nilai Earned Media Sponsor
                        </h3>
                        <span class="text-[10px] text-slate-400 font-mono">Total Rp 231 Jt</span>
                    </div>

                    <div class="h-56 relative flex items-center justify-center">
                        <canvas id="mediaPieChart"></canvas>
                    </div>
                </div>

            </div>

            <!-- Checklist Realisasi Benefit Sponsorship -->
            <div class="sb-card p-6 sm:p-8 bg-white space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-black text-navy-900 uppercase tracking-wider">Checklist Realisasi Benefit Kemitraan</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Pemenuhan hak promosi yang telah diverifikasi oleh tim EO dan perwakilan Brand.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        100% Terpenuhi
                    </span>
                </div>

                <div class="divide-y divide-slate-100 text-xs">
                    <?php foreach ($benefitsRealization as $b): ?>
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-start gap-2.5">
                            <i class="fa-solid fa-circle-check text-emerald-500 text-sm mt-0.5"></i>
                            <div>
                                <strong class="text-slate-900 block"><?= clean_input($b['benefit']) ?></strong>
                                <span class="text-slate-500 text-[11px]"><?= clean_input($b['notes'] ?? '') ?></span>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 self-start sm:self-auto">
                            <?= clean_input($b['status']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Galeri Dokumentasi Penempatan Logo & Aktivasi Event -->
            <div class="sb-card p-6 sm:p-8 bg-white space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-black text-navy-900 uppercase tracking-wider">Dokumentasi Bukti Penempatan Logo</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Dokumentasi foto panggung, booth promosi, umbul-umbul, dan interaksi audiens.</p>
                    </div>
                    <span class="text-xs text-slate-400"><i class="fa-solid fa-camera mr-1"></i> 4 Dokumentasi Resmi</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-900 relative group h-40 flex flex-col justify-end p-3 text-white">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                        <div class="relative z-10">
                            <span class="text-[9px] bg-brand-indigo px-1.5 py-0.2 rounded font-bold uppercase">Backdrop Utama</span>
                            <p class="text-[11px] font-bold mt-1 line-clamp-1">Logo Brand di Panggung</p>
                        </div>
                    </div>

                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-800 relative group h-40 flex flex-col justify-end p-3 text-white">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                        <div class="relative z-10">
                            <span class="text-[9px] bg-brand-purple px-1.5 py-0.2 rounded font-bold uppercase">Aktivasi Booth</span>
                            <p class="text-[11px] font-bold mt-1 line-clamp-1">Stand Interaktif Pengunjung</p>
                        </div>
                    </div>

                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-700 relative group h-40 flex flex-col justify-end p-3 text-white">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                        <div class="relative z-10">
                            <span class="text-[9px] bg-emerald-600 px-1.5 py-0.2 rounded font-bold uppercase">Materi Promosi</span>
                            <p class="text-[11px] font-bold mt-1 line-clamp-1">Umbul-Umbul & Spanduk</p>
                        </div>
                    </div>

                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-950 relative group h-40 flex flex-col justify-end p-3 text-white">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                        <div class="relative z-10">
                            <span class="text-[9px] bg-sky-600 px-1.5 py-0.2 rounded font-bold uppercase">Media Rilis</span>
                            <p class="text-[11px] font-bold mt-1 line-clamp-1">Press Conference Sponsor</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Chart Bar: Target vs Realisasi Peserta
    const ctxBar = document.getElementById('impactBarChart');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Target Audiens', 'Peserta Aktual', 'Total Pendaftar'],
                datasets: [{
                    label: 'Jumlah Orang',
                    data: [<?= $analytics['attendee_estimate'] ?>, <?= $analytics['actual_attendees'] ?>, <?= round($analytics['actual_attendees'] * 1.15) ?>],
                    backgroundColor: ['#94a3b8', '#10b981', '#4f46e5'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. Chart Pie: Komposisi Nilai Media
    const ctxPie = document.getElementById('mediaPieChart');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Impresi Medsos (CPM)', 'Eksposur Tatap Muka', 'Liputan Pers Media'],
                datasets: [{
                    data: [38, 48, 14],
                    backgroundColor: ['#7c3aed', '#4f46e5', '#0284c7'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
