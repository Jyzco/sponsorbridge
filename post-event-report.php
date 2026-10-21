<?php
// post-event-report.php - Form Pengisian Laporan Hasil Event oleh EO
$page_title = "Isi Laporan Hasil Event";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

require_role('eo');
$currentUser = current_user();
$userId = $currentUser['id'];

// Ambil event milik EO yang selesai atau ongoing
$stmtEvents = $conn->prepare("SELECT id, title, attendee_estimate FROM events WHERE eo_id = ? ORDER BY created_at DESC");
$stmtEvents->bind_param("i", $userId);
$stmtEvents->execute();
$events = $stmtEvents->get_result()->fetch_all(MYSQLI_ASSOC);

// Ambil sponsor yang pernah bermitra dengan EO ini
$allSponsors = $conn->query("SELECT u.id, sp.company_name FROM users u JOIN sponsor_profiles sp ON u.id = sp.user_id")->fetch_all(MYSQLI_ASSOC);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $sponsorId = (int)($_POST['sponsor_id'] ?? 0);
    $actualAttendees = (int)($_POST['actual_attendees'] ?? 0);
    $socialImpressions = (int)($_POST['social_impressions'] ?? 0);
    $engagementRate = (float)($_POST['engagement_rate'] ?? 0);
    $mediaCoverage = (int)($_POST['media_coverage_count'] ?? 0);
    $roiMultiplier = (float)($_POST['roi_multiplier'] ?? 3.5);
    $roiPct = round(($roiMultiplier - 1) * 100, 1);
    
    $reportDoc = clean_input($_POST['report_file_name'] ?? 'LPJ_Event_Lengkap.pdf');
    
    // Benefit realization simulation
    $benefitArray = [
        ['benefit' => 'Penempatan Logo di Main Backdrop', 'status' => 'Tercapai 100%', 'notes' => clean_input($_POST['note_logo'] ?? 'Terpasang di panggung')],
        ['benefit' => 'Aktivasi Booth Promosi & Sampling', 'status' => 'Tercapai 100%', 'notes' => clean_input($_POST['note_booth'] ?? 'Aktif selama acara')],
        ['benefit' => 'Penyebutan Ad-lib MC & Media Sosial', 'status' => 'Tercapai 100%', 'notes' => clean_input($_POST['note_social'] ?? 'Tayang di Instagram & TikTok')]
    ];
    $benefitJson = json_encode($benefitArray);

    $photos = json_encode([
        'assets/img/analytics/photo_1.jpg',
        'assets/img/analytics/photo_2.jpg',
        'assets/img/analytics/photo_3.jpg',
        'assets/img/analytics/photo_4.jpg'
    ]);

    if ($eventId <= 0 || $sponsorId <= 0 || $actualAttendees <= 0) {
        $error = 'Harap lengkapi pilihan event, sponsor mitra, dan jumlah peserta aktual.';
    } else {
        $stmtInsert = $conn->prepare("
            INSERT INTO event_analytics (event_id, sponsor_id, actual_attendees, social_impressions, engagement_rate, media_coverage_count, estimated_roi_pct, roi_multiplier, documentation_photos_json, benefit_realization_json, report_document, submitted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmtInsert->bind_param("iiiididdsss", $eventId, $sponsorId, $actualAttendees, $socialImpressions, $engagementRate, $mediaCoverage, $roiPct, $roiMultiplier, $photos, $benefitJson, $reportDoc);

        if ($stmtInsert->execute()) {
            create_notification($sponsorId, 'Laporan Pasca-Event Telah Tersedia', "EO telah mengunggah laporan hasil kegiatan dan impact analytics.", "analytics.php?event_id={$eventId}");
            set_flash('success', 'Laporan dampak event berhasil disimpan dan dapat ditinjau oleh pihak sponsor.');
            header("Location: " . BASE_URL . "/analytics.php?event_id={$eventId}");
            exit;
        } else {
            $error = 'Gagal menyimpan analitik: ' . $conn->error;
        }
    }
}
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-3xl mx-auto space-y-6">
            
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-brand-indigo">Pelaporan Pasca-Acara</span>
                <h1 class="text-2xl font-black text-navy-900 tracking-tight mt-1">Form Pengisian Hasil Event (LPJ)</h1>
                <p class="text-xs text-slate-500 mt-1">Masukkan metrik aktual yang dicapai selama penyelenggaraan acara untuk diserahkan kepada pihak sponsor mitra.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
                    <span><?= clean_input($error) ?></span>
                </div>
            <?php endif; ?>

            <div class="sb-card p-6 sm:p-8 bg-white">
                <form method="POST" action="" class="space-y-5">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1" for="event_id">Pilih Event *</label>
                            <select id="event_id" name="event_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo bg-white">
                                <option value="">Pilih Event Anda</option>
                                <?php foreach ($events as $ev): ?>
                                    <option value="<?= $ev['id'] ?>"><?= clean_input($ev['title']) ?> (Target: <?= number_format($ev['attendee_estimate']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1" for="sponsor_id">Sponsor Mitra Penerima Laporan *</label>
                            <select id="sponsor_id" name="sponsor_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo bg-white">
                                <option value="">Pilih Sponsor</option>
                                <?php foreach ($allSponsors as $sp): ?>
                                    <option value="<?= $sp['id'] ?>"><?= clean_input($sp['company_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Metrik Kuantitatif -->
                    <div class="pt-4 border-t border-slate-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Metrik Kuantitatif Aktual</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jumlah Peserta Aktual yang Hadir *</label>
                                <input type="number" name="actual_attendees" value="13500" required min="1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-brand-indigo/30">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Total Impresi Media Sosial *</label>
                                <input type="number" name="social_impressions" value="480000" required min="1000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-brand-indigo/30">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Engagement Rate (%) *</label>
                                <input type="number" name="engagement_rate" step="0.1" value="7.8" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-brand-indigo/30">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jumlah Publikasi Media / Press *</label>
                                <input type="number" name="media_coverage_count" value="12" required min="1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-brand-indigo/30">
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan Realisasi Benefit -->
                    <div class="pt-4 border-t border-slate-100 space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Realisasi & Bukti Benefit</h3>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Catatan Penempatan Logo:</label>
                            <input type="text" name="note_logo" value="Logo ukuran L terpasang di backdrop panggung dan 20 umbul-umbul venue" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Catatan Aktivasi Booth Promosi:</label>
                            <input type="text" name="note_booth" value="Booth 3x3 meter dikunjungi oleh lebih dari 3.200 pengunjung selama 2 hari" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Catatan Publikasi Media Sosial:</label>
                            <input type="text" name="note_social" value="5 Feed Reels & 15 IG Stories ditayangkan dengan tagar resmi sponsor" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                        </div>
                    </div>

                    <!-- Unggah Dokumen / Link -->
                    <div class="pt-4 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Dokumen Laporan / Tautan Bukti Dokumentasi</label>
                        <input type="text" name="report_file_name" value="LPJ_Dokumentasi_Lengkap_Sponsorship.pdf" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs">
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                        <a href="<?= BASE_URL ?>/dashboard.php" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 shadow-sm">
                            Kirim Laporan & Buat Impact Analytics
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
